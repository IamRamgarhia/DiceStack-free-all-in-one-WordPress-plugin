<?php
/**
 * WordPress Site Health integration.
 *
 * @package DiceStack
 */

namespace DiceStack;

defined( 'ABSPATH' ) || exit;

/**
 * Surfaces DiceStack status inside Tools → Site Health, so the value (and any
 * problems) show up where WordPress already guides site owners.
 */
final class Site_Health {

	/**
	 * Hook into Site Health.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'site_status_tests', array( $this, 'register_tests' ) );
		add_filter( 'debug_information', array( $this, 'debug_information' ) );
	}

	/**
	 * Register DiceStack direct tests.
	 *
	 * @param array $tests Existing tests.
	 * @return array
	 */
	public function register_tests( $tests ) {
		$tests['direct']['dicestack_failures']     = array(
			'label' => __( 'DiceStack module errors', 'dicestack' ),
			'test'  => array( $this, 'test_failures' ),
		);
		$tests['direct']['dicestack_object_cache'] = array(
			'label' => __( 'DiceStack persistent object cache', 'dicestack' ),
			'test'  => array( $this, 'test_object_cache' ),
		);
		return $tests;
	}

	/**
	 * Test: were any modules auto-disabled after an error?
	 *
	 * @return array
	 */
	public function test_failures() {
		$fails  = get_option( Core::FAILURES_OPTION, array() );
		$result = array(
			'label'       => __( 'No DiceStack tools have errored', 'dicestack' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'DiceStack', 'dicestack' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'All enabled DiceStack tools are loading without errors.', 'dicestack' ) . '</p>',
			'test'        => 'dicestack_failures',
		);

		if ( ! empty( $fails ) && is_array( $fails ) ) {
			$result['status']         = 'recommended';
			$result['label']          = __( 'Some DiceStack tools were turned off after an error', 'dicestack' );
			$result['badge']['color'] = 'orange';
			$result['description']    = '<p>' . esc_html__( 'DiceStack automatically disabled one or more tools that caused an error, so your site kept running. Review them on the DiceStack dashboard.', 'dicestack' ) . '</p>';
		}
		return $result;
	}

	/**
	 * Test: is a persistent object cache active, and could one be used?
	 *
	 * @return array
	 */
	public function test_object_cache() {
		$active    = Environment::object_cache_active();
		$available = Environment::has( 'object_cache_backend' );

		$result = array(
			'label'       => __( 'A persistent object cache is in use', 'dicestack' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'DiceStack', 'dicestack' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'Database query results are cached in memory for faster page builds.', 'dicestack' ) . '</p>',
			'test'        => 'dicestack_object_cache',
		);

		if ( ! $active ) {
			if ( $available ) {
				$result['status']         = 'recommended';
				$result['label']          = __( 'A persistent object cache is available but not enabled', 'dicestack' );
				$result['badge']['color'] = 'orange';
				$result['description']    = '<p>' . esc_html__( 'Your server supports Redis or Memcached. Enable DiceStack’s Object cache tool to speed up your site.', 'dicestack' ) . '</p>';
				$result['actions']        = '<p><a href="' . esc_url( admin_url( 'admin.php?page=dicestack-object-cache' ) ) . '">' . esc_html__( 'Open DiceStack object cache', 'dicestack' ) . '</a></p>';
			} else {
				$result['status']      = 'good';
				$result['label']       = __( 'No persistent object cache (your host does not offer one)', 'dicestack' );
				$result['description'] = '<p>' . esc_html__( 'This is normal on many hosts and is not a problem.', 'dicestack' ) . '</p>';
			}
		}
		return $result;
	}

	/**
	 * Add an DiceStack section to Site Health → Info (handy for support).
	 *
	 * @param array $info Existing debug info.
	 * @return array
	 */
	public function debug_information( $info ) {
		$core    = Core::instance();
		$active  = $core->get_active_modules();
		$info['dicestack'] = array(
			'label'  => 'DiceStack',
			'fields' => array(
				'version'        => array(
					'label' => __( 'Version', 'dicestack' ),
					'value' => DICESTACK_VERSION,
				),
				'active_modules' => array(
					'label' => __( 'Active tools', 'dicestack' ),
					'value' => count( $active ),
				),
				'active_list'    => array(
					'label' => __( 'Active tool IDs', 'dicestack' ),
					'value' => empty( $active ) ? '—' : implode( ', ', $active ),
				),
			),
		);
		return $info;
	}
}
