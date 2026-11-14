<?php
/**
 * Fills the WooCommerce tax rate table from WooCommerce Tax lookups.
 */

namespace TweaksForWoo\TaxRates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TaxRateFiller {

	/**
	 * Order meta key recording the last address/item combination we filled for.
	 * Kept from the standalone plugin so existing orders are not looked up again.
	 */
	const FILLED_HASH_META = '_lstrw_filled_hash';

	/** @var OrderTaxJarIntegration|null */
	private ?OrderTaxJarIntegration $taxjar = null;

	/** @var array<int, bool> Orders currently being processed. */
	private array $processing = array();

	/**
	 * Register the order hooks the filler listens to.
	 */
	public static function register(): void {
		$filler = new self();

		add_action( 'woocommerce_new_order', array( $filler, 'on_order_event' ), 20, 1 );
		add_action( 'woocommerce_update_order', array( $filler, 'on_order_event' ), 20, 1 );
		add_action( 'woocommerce_checkout_order_processed', array( $filler, 'on_order_event' ), 20, 1 );
		add_action( 'woocommerce_order_before_calculate_taxes', array( $filler, 'on_before_calculate_taxes' ), 10, 2 );
		add_filter( 'woocommerce_ajax_add_order_item_validation', array( $filler, 'on_ajax_add_order_item_validation' ), 20, 4 );
	}

	public function on_order_event( $order_id ): void {
		$order_id = absint( $order_id );
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$this->fill_for_order( $order );
	}

	public function on_before_calculate_taxes( $args, $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$this->fill_for_order( $order, $this->get_address_from_calc_args( $args, $order ) );
	}

	public function on_ajax_add_order_item_validation( $validation_error, $product, $order, $qty ) {
		if ( is_wp_error( $validation_error ) && $validation_error->has_errors() ) {
			return $validation_error;
		}

		if ( ! $order instanceof \WC_Order || ! $product instanceof \WC_Product ) {
			return $validation_error;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) || ! TaxService::is_available() ) {
			return $validation_error;
		}

		if ( ! $product->is_taxable() || ! apply_filters( 'lstrw_should_fill_rates', true, $order ) ) {
			return $validation_error;
		}

		$address = $this->get_add_item_address( $order );
		if ( null === $address ) {
			return $validation_error;
		}

		$address = apply_filters( 'lstrw_tax_address', $address, $order );
		if ( ! is_array( $address ) || ! $this->address_is_usable( $address ) ) {
			return $validation_error;
		}

		$line_item = $this->get_product_line_item( $product, max( 1, (float) $qty ) );
		if ( empty( $line_item ) ) {
			return $validation_error;
		}

		try {
			$this->fill_location( $address, array( $line_item ), 0.0 );
		} catch ( \Throwable $e ) {
			$this->log_error( $e );
		}

		return $validation_error;
	}

	private function fill_for_order( \WC_Order $order, ?array $address = null ): void {
		$order_id = $order->get_id();
		if ( isset( $this->processing[ $order_id ] ) ) {
			return;
		}

		$this->processing[ $order_id ] = true;

		try {
			if ( ! TaxService::is_available() ) {
				return;
			}

			if ( ! apply_filters( 'lstrw_should_fill_rates', true, $order ) ) {
				return;
			}

			if ( null === $address ) {
				$address = $this->get_address( $order );
			}

			$address = apply_filters( 'lstrw_tax_address', $address, $order );
			if ( ! is_array( $address ) || ! $this->address_is_usable( $address ) ) {
				return;
			}

			$taxjar = $this->get_taxjar();
			if ( ! $taxjar || ! method_exists( $taxjar, 'calculate_tax' ) ) {
				return;
			}

			$line_items = $taxjar->get_order_line_items( $order );
			if ( empty( $line_items ) ) {
				return;
			}

			$shipping = (float) $order->get_shipping_total();
			$hash     = md5( (string) wp_json_encode( array( $address, $line_items, $shipping ) ) );

			if ( $order->get_meta( self::FILLED_HASH_META ) === $hash ) {
				return;
			}

			if ( $this->fill_location( $address, $line_items, $shipping ) ) {
				$order->update_meta_data( self::FILLED_HASH_META, $hash );
				$order->save_meta_data();
			}
		} catch ( \Throwable $e ) {
			$this->log_error( $e );
		} finally {
			unset( $this->processing[ $order_id ] );
		}
	}

	private function fill_location( array $address, array $line_items, float $shipping ): bool {
		$taxjar = $this->get_taxjar();
		if ( ! $taxjar || ! method_exists( $taxjar, 'calculate_tax' ) ) {
			return false;
		}

		$result = $taxjar->calculate_tax(
			array(
				'to_country'      => $address['country'],
				'to_state'        => $address['state'],
				'to_zip'          => $address['postcode'],
				'to_city'         => $address['city'],
				'to_street'       => $address['street'],
				'shipping_amount' => $shipping,
				'line_items'      => $line_items,
			)
		);

		return is_array( $result ) && ! empty( $result['rate_ids'] );
	}

	private function log_error( \Throwable $e ): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error(
				'Tax rate fill failed: ' . $e->getMessage(),
				array( 'source' => 'tweaks-for-woo' )
			);
		}
	}

	private function get_address( \WC_Order $order ): array {
		$based_on = get_option( 'woocommerce_tax_based_on', 'shipping' );

		if ( 'base' === $based_on ) {
			return $this->get_base_address();
		}

		$prefix = 'billing' === $based_on ? 'billing' : 'shipping';

		if ( 'shipping' === $prefix && '' === (string) $order->get_shipping_country() ) {
			$prefix = 'billing';
		}

		return array(
			'country'  => (string) $order->{'get_' . $prefix . '_country'}(),
			'state'    => (string) $order->{'get_' . $prefix . '_state'}(),
			'postcode' => (string) $order->{'get_' . $prefix . '_postcode'}(),
			'city'     => (string) $order->{'get_' . $prefix . '_city'}(),
			'street'   => (string) $order->{'get_' . $prefix . '_address_1'}(),
		);
	}

	private function get_base_address(): array {
		$countries = WC()->countries;

		return array(
			'country'  => (string) $countries->get_base_country(),
			'state'    => (string) $countries->get_base_state(),
			'postcode' => (string) $countries->get_base_postcode(),
			'city'     => (string) $countries->get_base_city(),
			'street'   => (string) $countries->get_base_address(),
		);
	}

	private function get_posted_address(): ?array {
		$country  = isset( $_POST['country'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['country'] ) ) ) : '';
		$state    = isset( $_POST['state'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['state'] ) ) ) : '';
		$postcode = isset( $_POST['postcode'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : '';
		$city     = isset( $_POST['city'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['city'] ) ) ) : '';

		if ( '' === $country && '' === $postcode ) {
			return null;
		}

		return array(
			'country'  => $country,
			'state'    => $state,
			'postcode' => $postcode,
			'city'     => $city,
			'street'   => '',
		);
	}

	private function get_add_item_address( \WC_Order $order ): ?array {
		if ( apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
			return $this->get_base_address();
		}

		if ( 'base' === get_option( 'woocommerce_tax_based_on', 'shipping' ) ) {
			return $this->get_base_address();
		}

		return $this->get_posted_address() ?? $this->get_address( $order );
	}

	private function get_product_line_item( \WC_Product $product, float $qty ): array {
		$tax_class = (string) $product->get_tax_class();

		if ( 'zero-rate' === sanitize_title( $tax_class ) ) {
			return array();
		}

		$segments = explode( '-', $tax_class );
		$code     = end( $segments );
		$tax_code = is_numeric( $code ) ? (string) $code : '';
		$price    = (float) $product->get_price();

		return array(
			'id'               => (string) $product->get_id(),
			'quantity'         => $qty,
			'product_tax_code' => $tax_code,
			'unit_price'       => $price > 0 ? $price : 0.01,
			'discount'         => 0,
			'tax_location'     => 'shipping',
		);
	}

	private function get_address_from_calc_args( $args, \WC_Order $order ): array {
		if ( 'base' === get_option( 'woocommerce_tax_based_on', 'shipping' ) ) {
			return $this->get_address( $order );
		}

		if ( ! is_array( $args ) || ( empty( $args['country'] ) && empty( $args['postcode'] ) ) ) {
			return $this->get_address( $order );
		}

		$address = $this->get_address( $order );

		foreach ( array( 'country', 'state', 'postcode', 'city' ) as $key ) {
			if ( isset( $args[ $key ] ) && '' !== (string) $args[ $key ] ) {
				$value = wc_clean( (string) $args[ $key ] );

				$address[ $key ] = in_array( $key, array( 'country', 'state' ), true )
					? wc_strtoupper( $value )
					: $value;
			}
		}

		return $address;
	}

	private function address_is_usable( array $address ): bool {
		if ( empty( $address['country'] ) ) {
			return false;
		}

		if ( in_array( $address['country'], array( 'US', 'CA' ), true ) && empty( $address['state'] ) ) {
			return false;
		}

		if ( 'US' === $address['country'] && empty( $address['postcode'] ) ) {
			return false;
		}

		return true;
	}

	private function get_taxjar(): ?OrderTaxJarIntegration {
		if ( $this->taxjar instanceof OrderTaxJarIntegration ) {
			return $this->taxjar;
		}

		if ( ! TaxService::is_available() ) {
			return null;
		}

		try {
			$api_client = new APIClient(
				new \WC_Connect_Service_Schemas_Validator(),
				new ConnectLoaderStub()
			);
			$logger = new \WC_Connect_Logger( new \WC_Logger(), 'taxes' );
			$tracks = new \WC_Connect_Tracks( $logger, TWEAKS_FOR_WOO_FILE );

			$this->taxjar = new OrderTaxJarIntegration(
				$api_client,
				$logger,
				trailingslashit( TWEAKS_FOR_WOO_URL . 'dist' ),
				$tracks,
				null
			);
		} catch ( \Throwable $e ) {
			return null;
		}

		return $this->taxjar;
	}
}
