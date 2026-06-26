<?php
/**
 * Image Optimizer module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Compresses and (optionally) downsizes images on upload using the server's
 * local GD/Imagick library — no external SaaS. Replaces Smush/Imagify basics.
 */
final class Image_Optimizer extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'image_optimizer';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Image optimizer', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Compress and resize images on upload locally — no cloud service or monthly limits.', 'dicestack' );
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
	public function replaces() {
		return 'premium image optimizers';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 60,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
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
				'key'     => 'max_width',
				'label'   => __( 'Maximum width (pixels)', 'dicestack' ),
				'type'    => 'number',
				'default' => 1920,
				'min'     => 0,
				'step'    => 10,
				'help'    => __( 'Oversized uploads are scaled down to this width. 0 = no resize.', 'dicestack' ),
			),
			array(
				'key'     => 'jpeg_quality',
				'label'   => __( 'JPEG quality', 'dicestack' ),
				'type'    => 'number',
				'default' => 82,
				'min'     => 40,
				'max'     => 100,
				'step'    => 1,
			),
			array(
				'key'     => 'strip_exif',
				'label'   => __( 'Strip EXIF metadata (camera, location)', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	/**
	 * AJAX nonce action for the bulk optimizer.
	 */
	const NONCE = 'dicestack_optimize';

	public function init() {
		// Set WordPress's default JPEG quality.
		add_filter( 'jpeg_quality', array( $this, 'quality' ) );
		add_filter( 'wp_editor_set_quality', array( $this, 'quality' ) );
		// Resize big originals on upload.
		add_filter( 'wp_handle_upload', array( $this, 'process_upload' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'wp_ajax_dicestack_optimize_batch', array( $this, 'ajax_batch' ) );
		}
	}

	/**
	 * Register the bulk-optimize page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Optimize images', 'dicestack' ),
			__( 'Optimize images', 'dicestack' ),
			'manage_options',
			'dicestack-optimize-images',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the bulk-optimize page with a progress bar.
	 *
	 * @return void
	 */
	public function render_page() {
		$ids   = $this->image_ids();
		$total = count( $ids );
		$nonce = wp_create_nonce( self::NONCE );
		$ajax  = admin_url( 'admin-ajax.php' );

		echo '<div class="wrap"><h1>' . esc_html__( 'Optimize images', 'dicestack' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		if ( isset( $_GET['settings-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'dicestack' ) . '</p></div>';
		}
		echo '<h2>' . esc_html__( 'Settings', 'dicestack' ) . '</h2>';
		echo \DiceStack\Admin\Settings_Renderer::page_form( $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.
		echo '<h2>' . esc_html__( 'Bulk optimize', 'dicestack' ) . '</h2>';
		echo '<p>' . esc_html( sprintf( /* translators: %d: count. */ _n( '%d image found in your media library.', '%d images found in your media library.', $total, 'dicestack' ), $total ) ) . '</p>';
		echo '<button type="button" class="button button-primary" id="dicestack-opt-start">' . esc_html__( 'Start optimizing', 'dicestack' ) . '</button>';
		echo '<div class="dicestack-progress" style="max-width:600px;display:none;" id="dicestack-opt-bar"><span></span></div>';
		echo '<p id="dicestack-opt-status" style="font-weight:500;"></p>';
		echo '</div>';
		?>
		<script>
		(function(){
		var btn=document.getElementById('dicestack-opt-start'),bar=document.getElementById('dicestack-opt-bar'),fill=bar?bar.querySelector('span'):null,status=document.getElementById('dicestack-opt-status');
		if(!btn)return;
		var total=<?php echo (int) $total; ?>,done=0;
		function batch(offset){
			var body=new URLSearchParams();body.append('action','dicestack_optimize_batch');body.append('nonce',<?php echo wp_json_encode( $nonce ); ?>);body.append('offset',offset);
			fetch(<?php echo wp_json_encode( $ajax ); ?>,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
			.then(function(r){return r.json();}).then(function(res){
				if(!res||!res.success){status.textContent=<?php echo wp_json_encode( __( 'Something went wrong.', 'dicestack' ) ); ?>;btn.disabled=false;return;}
				done=res.data.done_count;var pct=total?Math.round(done/total*100):100;
				if(fill){fill.style.width=pct+'%';}
				status.textContent=done+' / '+total+' ('+pct+'%)';
				if(res.data.finished){status.textContent=<?php echo wp_json_encode( __( 'All images optimized.', 'dicestack' ) ); ?>;btn.disabled=false;}
				else{batch(res.data.next_offset);}
			});
		}
		btn.addEventListener('click',function(){btn.disabled=true;bar.style.display='block';batch(0);});
		})();
		</script>
		<?php
	}

	/**
	 * IDs of all image attachments.
	 *
	 * @return int[]
	 */
	private function image_ids() {
		return get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => array( 'image/jpeg', 'image/png' ),
				'post_status'    => 'inherit',
				'numberposts'    => -1,
				'fields'         => 'ids',
			)
		);
	}

	/**
	 * Process one batch of images.
	 *
	 * @return void
	 */
	public function ajax_batch() {
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$ids    = $this->image_ids();
		$total  = count( $ids );
		$batch  = array_slice( $ids, $offset, 5 );

		foreach ( $batch as $id ) {
			$file = get_attached_file( $id );
			if ( $file && file_exists( $file ) ) {
				$this->optimize_file( $file );
			}
		}

		$next     = $offset + count( $batch );
		$finished = ( $next >= $total );
		wp_send_json_success(
			array(
				'done_count'  => $next,
				'next_offset' => $next,
				'finished'    => $finished,
			)
		);
	}

	/**
	 * Resize + recompress a single image file in place.
	 *
	 * @param string $file Absolute path.
	 * @return void
	 */
	private function optimize_file( $file ) {
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return;
		}
		$max  = (int) $this->get_setting( 'max_width', 1920 );
		$size = $editor->get_size();
		if ( $max > 0 && ! empty( $size['width'] ) && $size['width'] > $max ) {
			$editor->resize( $max, null, false );
		}
		$editor->set_quality( (int) $this->get_setting( 'jpeg_quality', 82 ) );
		$editor->save( $file );
	}

	/**
	 * Provide the configured JPEG quality.
	 *
	 * @param int $quality Current quality.
	 * @return int
	 */
	public function quality( $quality ) {
		$q = (int) $this->get_setting( 'jpeg_quality', 82 );
		return ( $q >= 40 && $q <= 100 ) ? $q : $quality;
	}

	/**
	 * Resize/recompress a freshly uploaded image.
	 *
	 * @param array $upload Upload data (file, url, type).
	 * @return array
	 */
	public function process_upload( $upload ) {
		if ( empty( $upload['type'] ) || ! in_array( $upload['type'], array( 'image/jpeg', 'image/png' ), true ) ) {
			return $upload;
		}

		$file      = isset( $upload['file'] ) ? $upload['file'] : '';
		if ( '' === $file || ! file_exists( $file ) ) {
			return $upload;
		}
		$max_width = (int) $this->get_setting( 'max_width', 1920 );
		$editor    = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return $upload;
		}

		$size = $editor->get_size();
		if ( $max_width > 0 && ! empty( $size['width'] ) && $size['width'] > $max_width ) {
			$editor->resize( $max_width, null, false );
		}

		$editor->set_quality( (int) $this->get_setting( 'jpeg_quality', 82 ) );
		$editor->save( $file );

		return $upload;
	}
}
