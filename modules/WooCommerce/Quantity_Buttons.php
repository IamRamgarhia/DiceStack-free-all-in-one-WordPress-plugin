<?php
/**
 * WooCommerce Quantity Buttons module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds plus/minus buttons either side of quantity inputs for an easier mobile
 * shopping experience. Tiny inline script, no library.
 */
final class Quantity_Buttons extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_quantity_buttons';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Quantity plus/minus buttons', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add + and − buttons around quantity fields for easier ordering on mobile.', 'dicestack' );
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
		return 'shopping-cart';
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
			'php_memory_kb' => 16,
			'front_js_kb'   => 0.8,
			'front_css_kb'  => 0.3,
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
	 * Output the enhancement script and styles.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! function_exists( 'is_woocommerce' ) || ! ( is_woocommerce() || is_cart() ) ) {
			return;
		}
		?>
		<style>
		.dicestack-qty{display:inline-flex;align-items:center}
		.dicestack-qty button{width:32px;height:32px;border:1px solid #ccc;background:#f6f7f9;cursor:pointer;font-size:16px;line-height:1}
		.dicestack-qty input.qty{text-align:center}
		</style>
		<script>
		(function(){
		function enhance(){
			document.querySelectorAll('.quantity:not(.dicestack-done)').forEach(function(wrap){
				var input=wrap.querySelector('input.qty');if(!input)return;
				wrap.classList.add('dicestack-qty','dicestack-done');
				var minus=document.createElement('button');minus.type='button';minus.textContent='−';
				var plus=document.createElement('button');plus.type='button';plus.textContent='+';
				function step(d){var v=parseFloat(input.value)||0;var s=parseFloat(input.step)||1;var min=parseFloat(input.min)||0;v=v+d*s;if(v<min)v=min;input.value=v;input.dispatchEvent(new Event('change',{bubbles:true}));}
				minus.addEventListener('click',function(){step(-1);});
				plus.addEventListener('click',function(){step(1);});
				wrap.insertBefore(minus,input);wrap.appendChild(plus);
			});
		}
		enhance();
		document.body.addEventListener('updated_cart_totals',enhance);
		})();
		</script>
		<?php
	}
}
