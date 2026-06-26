<?php
/**
 * Hover Prefetch module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Prefetches an internal page when the visitor hovers (or touches) a link, so
 * the next navigation feels instant. Tiny inline script, no library.
 */
final class Hover_Prefetch extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'hover_prefetch';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Instant hover prefetch', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Preload internal pages on link hover so navigation feels instant.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'performance';
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
			'front_js_kb'   => 0.7,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * Output the prefetch script.
	 *
	 * @return void
	 */
	public function render() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		?>
		<script>
		(function(){
		var done={},host=<?php echo wp_json_encode( $host ); ?>;
		function pre(url){if(done[url])return;done[url]=1;var l=document.createElement('link');l.rel='prefetch';l.href=url;document.head.appendChild(l);}
		function handler(e){var a=e.target.closest&&e.target.closest('a');if(!a)return;var href=a.href||'';try{var u=new URL(href);if(u.host===host&&u.pathname!==location.pathname){pre(href);}}catch(x){}}
		document.addEventListener('mouseover',handler,{passive:true});
		document.addEventListener('touchstart',handler,{passive:true});
		})();
		</script>
		<?php
	}
}
