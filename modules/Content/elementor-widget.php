<?php
/**
 * Generic shortcode-backed Elementor widget.
 *
 * This file is only ever loaded from inside the `elementor/widgets/register`
 * hook (see Elementor_Widgets::register_widgets), so \Elementor\Widget_Base is
 * guaranteed to exist at that point.
 *
 * @package DiceStack
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\Elementor\Widget_Base' ) && ! class_exists( 'DiceStack_Elementor_Shortcode_Widget' ) ) {

	/**
	 * Renders an DiceStack shortcode as an Elementor widget.
	 */
	class DiceStack_Elementor_Shortcode_Widget extends \Elementor\Widget_Base { // phpcs:ignore

		/**
		 * DiceStack widget name.
		 *
		 * @var string
		 */
		private $dicestack_name;

		/**
		 * DiceStack widget title.
		 *
		 * @var string
		 */
		private $dicestack_title;

		/**
		 * Shortcode to render.
		 *
		 * @var string
		 */
		private $dicestack_shortcode;

		/**
		 * Constructor.
		 *
		 * @param array $data Widget data.
		 * @param mixed $args Widget args (carries our dicestack_* keys).
		 */
		public function __construct( $data = array(), $args = null ) {
			$this->dicestack_name      = isset( $args['dicestack_name'] ) ? $args['dicestack_name'] : 'dicestack_widget';
			$this->dicestack_title     = isset( $args['dicestack_title'] ) ? $args['dicestack_title'] : 'DiceStack';
			$this->dicestack_shortcode = isset( $args['dicestack_shortcode'] ) ? $args['dicestack_shortcode'] : '';
			parent::__construct( $data, $args );
		}

		/**
		 * Widget machine name.
		 *
		 * @return string
		 */
		public function get_name() {
			return $this->dicestack_name;
		}

		/**
		 * Widget display title.
		 *
		 * @return string
		 */
		public function get_title() {
			return $this->dicestack_title;
		}

		/**
		 * Widget icon.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-shortcode';
		}

		/**
		 * Widget categories.
		 *
		 * @return string[]
		 */
		public function get_categories() {
			return array( 'general' );
		}

		/**
		 * Render the widget on the front end.
		 *
		 * @return void
		 */
		protected function render() {
			if ( '' !== $this->dicestack_shortcode ) {
				echo do_shortcode( '[' . $this->dicestack_shortcode . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is escaped within each module.
			}
		}
	}
}
