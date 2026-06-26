<?php
/**
 * WooCommerce Custom Order Statuses module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers custom, dashboard-selectable order statuses (e.g. "Packaging",
 * "Dispatched"). HPOS-compatible. Replaces Custom Order Status for WooCommerce.
 */
final class Custom_Order_Statuses extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_custom_order_statuses';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Custom order statuses', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add your own order statuses like Packaging or Dispatched to match your workflow.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'woocommerce';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'tags';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium order-status plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function dependencies() {
		return array( 'woocommerce' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'statuses',
				'label'   => __( 'Custom statuses', 'dicestack' ),
				'type'    => 'textarea',
				'default' => "packaging|Packaging\ndispatched|Dispatched",
				'help'    => __( 'One per line as slug|Label, e.g. packaging|Packaging', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_statuses' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_to_dropdown' ) );
	}

	/**
	 * Parse the configured statuses into slug => label.
	 *
	 * @return array<string,string>
	 */
	private function parse() {
		$raw = (string) $this->get_setting( 'statuses', '' );
		$map = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || strpos( $line, '|' ) === false ) {
				continue;
			}
			list( $slug, $label ) = array_map( 'trim', explode( '|', $line, 2 ) );
			$slug = sanitize_key( $slug );
			if ( '' !== $slug && '' !== $label ) {
				$map[ $slug ] = $label;
			}
		}
		return $map;
	}

	/**
	 * Register each custom status as a post status.
	 *
	 * @return void
	 */
	public function register_statuses() {
		foreach ( $this->parse() as $slug => $label ) {
			// Labels are user-defined (not translatable literals), so build the
			// count structure directly rather than passing a variable to _n_noop().
			$count_label = $label . ' (%s)';
			register_post_status(
				'wc-' . $slug,
				array(
					'label'                     => $label,
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => array(
						0          => $count_label,
						1          => $count_label,
						'singular' => $count_label,
						'plural'   => $count_label,
						'context'  => null,
						'domain'   => 'dicestack',
					),
				)
			);
		}
	}

	/**
	 * Add custom statuses to the WooCommerce order status dropdown.
	 *
	 * @param array $statuses Existing statuses.
	 * @return array
	 */
	public function add_to_dropdown( $statuses ) {
		foreach ( $this->parse() as $slug => $label ) {
			$statuses[ 'wc-' . $slug ] = $label;
		}
		return $statuses;
	}
}
