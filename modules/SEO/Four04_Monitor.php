<?php
/**
 * 404 Monitor module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Logs 404 (not found) hits and lets you redirect them in one click, recovering
 * lost traffic and SEO from broken or changed URLs.
 */
final class Four04_Monitor extends Abstract_Module {

	/**
	 * Option storing the 404 log.
	 */
	const LOG = 'dicestack_404_log';

	/**
	 * Option storing redirects this module performs.
	 */
	const REDIRECTS = 'dicestack_404_redirects';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'four04_monitor';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( '404 monitor', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Log broken URLs visitors hit and redirect them in one click.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'seo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium redirect plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 25,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
		add_action( 'template_redirect', array( $this, 'log_404' ), 20 );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'admin_post_dicestack_404_redirect', array( $this, 'handle_save_redirect' ) );
		}
	}

	/**
	 * Current request path.
	 *
	 * @return string
	 */
	private function path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		return untrailingslashit( (string) $uri );
	}

	/**
	 * Redirect known 404s.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		$map  = get_option( self::REDIRECTS, array() );
		$path = $this->path();
		if ( is_array( $map ) && isset( $map[ $path ] ) && $map[ $path ] ) {
			$target = ( 0 === strpos( $map[ $path ], 'http' ) ) ? $map[ $path ] : home_url( $map[ $path ] );
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}

	/**
	 * Log a 404 hit.
	 *
	 * @return void
	 */
	public function log_404() {
		if ( ! is_404() ) {
			return;
		}
		$path = $this->path();
		if ( '' === $path || '/' === $path ) {
			return;
		}
		$log = get_option( self::LOG, array() );
		$log = is_array( $log ) ? $log : array();
		if ( isset( $log[ $path ] ) ) {
			$log[ $path ]['count']++;
			$log[ $path ]['last'] = time();
		} else {
			if ( count( $log ) > 200 ) {
				array_shift( $log );
			}
			$log[ $path ] = array( 'count' => 1, 'last' => time() );
		}
		update_option( self::LOG, $log, false );
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( '404 monitor', 'dicestack' ),
			__( '404 monitor', 'dicestack' ),
			'manage_options',
			'dicestack-404',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Save a redirect for a logged 404.
	 *
	 * @return void
	 */
	public function handle_save_redirect() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_404' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$from = isset( $_POST['from'] ) ? '/' . ltrim( sanitize_text_field( wp_unslash( $_POST['from'] ) ), '/' ) : '';
		$to   = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
		if ( '' !== $from && '' !== $to ) {
			$map          = get_option( self::REDIRECTS, array() );
			$map          = is_array( $map ) ? $map : array();
			$map[ $from ] = $to;
			update_option( self::REDIRECTS, $map );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-404' ) );
		exit;
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render_page() {
		$log = get_option( self::LOG, array() );
		$log = is_array( $log ) ? $log : array();
		$map = get_option( self::REDIRECTS, array() );
		$map = is_array( $map ) ? $map : array();
		uasort( $log, static function ( $a, $b ) { return $b['count'] - $a['count']; } );

		echo '<div class="wrap"><h1>' . esc_html__( '404 monitor', 'dicestack' ) . '</h1>';
		if ( empty( $log ) ) {
			echo '<p>' . esc_html__( 'No 404s logged yet.', 'dicestack' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Broken URL', 'dicestack' ) . '</th><th>' . esc_html__( 'Hits', 'dicestack' ) . '</th><th>' . esc_html__( 'Redirect to', 'dicestack' ) . '</th></tr></thead><tbody>';
		foreach ( $log as $path => $info ) {
			$current = isset( $map[ $path ] ) ? $map[ $path ] : '';
			echo '<tr><td><code>' . esc_html( $path ) . '</code></td><td>' . esc_html( number_format_i18n( $info['count'] ) ) . '</td><td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:flex;gap:6px;">';
			wp_nonce_field( 'dicestack_404' );
			echo '<input type="hidden" name="action" value="dicestack_404_redirect" /><input type="hidden" name="from" value="' . esc_attr( $path ) . '" />';
			echo '<input type="text" name="to" value="' . esc_attr( $current ) . '" placeholder="/new-page" class="regular-text" />';
			echo '<button class="button">' . esc_html__( 'Save', 'dicestack' ) . '</button>';
			echo '</form></td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
