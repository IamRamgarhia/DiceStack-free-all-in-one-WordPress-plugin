<?php
/**
 * Image Lightbox module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A tiny, dependency-free lightbox: clicking a linked content image opens it in
 * an overlay instead of navigating away.
 */
final class Image_Lightbox extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'image_lightbox';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Image lightbox', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Open full-size images in a clean overlay instead of a new page.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'media';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'photo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 16,
			'front_js_kb'   => 1.0,
			'front_css_kb'  => 0.5,
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
	 * Output the lightbox markup, styles, and script.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! is_singular() ) {
			return;
		}
		?>
		<div id="dicestack-lb" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;align-items:center;justify-content:center;cursor:zoom-out;">
			<img id="dicestack-lb-img" src="" alt="" style="max-width:92%;max-height:92%;border-radius:4px;" />
		</div>
		<script>
		(function(){
		var lb=document.getElementById('dicestack-lb'),img=document.getElementById('dicestack-lb-img');
		if(!lb)return;
		function open(src){img.src=src;lb.style.display='flex';}
		function close(){lb.style.display='none';img.src='';}
		document.addEventListener('click',function(e){
			var a=e.target.closest('a');
			if(a&&/\.(jpe?g|png|gif|webp|avif)$/i.test(a.getAttribute('href')||'')&&a.querySelector('img')){
				e.preventDefault();open(a.getAttribute('href'));
			}
		});
		lb.addEventListener('click',close);
		document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
		})();
		</script>
		<?php
	}
}
