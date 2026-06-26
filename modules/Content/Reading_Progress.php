<?php
/**
 * Reading Progress Bar module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows a thin progress bar at the top of single posts indicating how far the
 * reader has scrolled.
 */
final class Reading_Progress extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'reading_progress';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Reading progress bar', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show a scroll-progress bar at the top of posts to encourage reading.', 'dicestack' );
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
		return 'bolt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 14,
			'front_js_kb'   => 0.5,
			'front_css_kb'  => 0.2,
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
				'key'     => 'color',
				'label'   => __( 'Bar colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#0aa2c0',
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
	 * Output the progress bar.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$color = sanitize_hex_color( (string) $this->get_setting( 'color', '#0aa2c0' ) ) ?: '#0aa2c0';
		?>
		<div id="dicestack-progress" style="position:fixed;top:0;left:0;height:4px;width:0;background:<?php echo esc_attr( $color ); ?>;z-index:99999;transition:width .1s;"></div>
		<script>
		(function(){var b=document.getElementById('dicestack-progress');if(!b)return;
		window.addEventListener('scroll',function(){var h=document.documentElement;var max=h.scrollHeight-h.clientHeight;var p=max>0?(h.scrollTop/max)*100:0;b.style.width=p+'%';},{passive:true});})();
		</script>
		<?php
	}
}
