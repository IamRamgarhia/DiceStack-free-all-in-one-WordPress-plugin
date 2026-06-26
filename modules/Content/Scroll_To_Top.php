<?php
/**
 * Scroll To Top module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A configurable back-to-top button. Ships its CSS/JS inline in the footer so it
 * adds no extra HTTP requests, and the JS is tiny (~0.6 KB).
 */
final class Scroll_To_Top extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'scroll_to_top';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Scroll to top', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'A floating button that smoothly returns visitors to the top of the page.', 'dicestack' );
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
		return 'arrow-up';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
			'front_js_kb'   => 0.6,
			'front_css_kb'  => 0.4,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'position',
				'label'   => __( 'Position', 'dicestack' ),
				'type'    => 'select',
				'default' => 'right',
				'options' => array(
					'right' => __( 'Bottom right', 'dicestack' ),
					'left'  => __( 'Bottom left', 'dicestack' ),
				),
			),
			array(
				'key'     => 'bg_color',
				'label'   => __( 'Button colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#1b2a4a',
			),
			array(
				'key'     => 'offset',
				'label'   => __( 'Show after scrolling (pixels)', 'dicestack' ),
				'type'    => 'number',
				'default' => 300,
				'min'     => 50,
				'max'     => 2000,
				'step'    => 50,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * Output the button plus its inline CSS/JS.
	 *
	 * @return void
	 */
	public function render() {
		$pos    = 'left' === $this->get_setting( 'position', 'right' ) ? 'left' : 'right';
		$color  = sanitize_hex_color( (string) $this->get_setting( 'bg_color', '#1b2a4a' ) );
		$color  = $color ? $color : '#1b2a4a';
		$offset = (int) $this->get_setting( 'offset', 300 );
		?>
		<button id="dicestack-stt" aria-label="<?php esc_attr_e( 'Scroll to top', 'dicestack' ); ?>" style="position:fixed;bottom:24px;<?php echo esc_attr( $pos ); ?>:24px;width:44px;height:44px;border:0;border-radius:50%;background:<?php echo esc_attr( $color ); ?>;color:#fff;cursor:pointer;opacity:0;visibility:hidden;transition:opacity .2s;z-index:9999;font-size:20px;line-height:1;">&#8593;</button>
		<style>#dicestack-stt.dicestack-show{opacity:1;visibility:visible}</style>
		<script>
		(function(){var b=document.getElementById('dicestack-stt');if(!b)return;var o=<?php echo (int) $offset; ?>;
		window.addEventListener('scroll',function(){if(window.pageYOffset>o){b.classList.add('dicestack-show');}else{b.classList.remove('dicestack-show');}},{passive:true});
		b.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});})();
		</script>
		<?php
	}
}
