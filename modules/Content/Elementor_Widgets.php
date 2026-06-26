<?php
/**
 * Elementor Widgets integration module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes DiceStack features (reviews, FAQ, contact form, breadcrumbs) as native
 * Elementor widgets when Elementor is active. Completely inert if Elementor is
 * not installed, so it can never break a non-Elementor site.
 */
final class Elementor_Widgets extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'elementor_widgets';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Elementor widgets', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Use DiceStack reviews, FAQ, contact form, and breadcrumbs as native Elementor widgets.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'content';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'puzzle';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 18,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		// Only do anything when Elementor is present.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register DiceStack widgets with Elementor.
	 *
	 * @param mixed $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		if ( ! class_exists( '\DiceStack_Elementor_Shortcode_Widget' ) ) {
			$this->define_widget_class();
		}

		$specs = array(
			array( 'dicestack_reviews', __( 'DiceStack Reviews', 'dicestack' ), 'dicestack_reviews' ),
			array( 'dicestack_faq', __( 'DiceStack FAQ', 'dicestack' ), 'dicestack_faq' ),
			array( 'dicestack_contact', __( 'DiceStack Contact Form', 'dicestack' ), 'dicestack_contact' ),
			array( 'dicestack_breadcrumbs', __( 'DiceStack Breadcrumbs', 'dicestack' ), 'dicestack_breadcrumbs' ),
		);

		foreach ( $specs as $spec ) {
			try {
				$widget = new \DiceStack_Elementor_Shortcode_Widget(
					array(),
					array(
						'dicestack_name'      => $spec[0],
						'dicestack_title'     => $spec[1],
						'dicestack_shortcode' => $spec[2],
					)
				);
				$widgets_manager->register( $widget );
			} catch ( \Throwable $e ) {
				// Elementor version mismatch — skip this widget rather than fatal.
				continue;
			}
		}
	}

	/**
	 * Load the generic shortcode-backed Elementor widget class. The file is only
	 * required here, inside the elementor/widgets/register hook, so the class
	 * (which extends an Elementor base class) only ever loads when Elementor is
	 * active.
	 *
	 * @return void
	 */
	private function define_widget_class() {
		require_once __DIR__ . '/elementor-widget.php';
	}
}
