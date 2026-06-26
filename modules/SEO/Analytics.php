<?php
/**
 * Analytics & Tracking module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds Google Analytics (GA4), Google Tag Manager, and Meta (Facebook) Pixel
 * with nothing more than the ID — no theme editing. Each field links to where to
 * find its ID. Admins are excluded from tracking by default.
 */
final class Analytics extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'analytics';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Analytics & tracking', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add Google Analytics (GA4), Tag Manager, and Meta Pixel by ID — no code.', 'dicestack' );
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
		return 'chart-bar';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium analytics plugins';
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
	public function external_service() {
		return array(
			'service' => __( 'Google Analytics / Tag Manager / Meta Pixel', 'dicestack' ),
			'url'     => 'https://policies.google.com/privacy',
			'data'    => __( 'When configured, visitor analytics data is sent to the tracking services you enable.', 'dicestack' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'ga4_id',
				'label'   => __( 'Google Analytics 4 Measurement ID', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Looks like G-XXXXXXXXXX.', 'dicestack' ),
				'guide'   => array(
					'url'   => 'https://support.google.com/analytics/answer/9539598',
					'label' => __( 'How to find your GA4 ID', 'dicestack' ),
				),
			),
			array(
				'key'     => 'gtm_id',
				'label'   => __( 'Google Tag Manager ID', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Looks like GTM-XXXXXXX.', 'dicestack' ),
				'guide'   => array(
					'url'   => 'https://support.google.com/tagmanager/answer/6103696',
					'label' => __( 'How to find your GTM ID', 'dicestack' ),
				),
			),
			array(
				'key'     => 'meta_pixel',
				'label'   => __( 'Meta (Facebook) Pixel ID', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'A 15–16 digit number.', 'dicestack' ),
				'guide'   => array(
					'url'   => 'https://www.facebook.com/business/help/952192354843755',
					'label' => __( 'How to find your Meta Pixel ID', 'dicestack' ),
				),
			),
			array(
				'key'     => 'exclude_admins',
				'label'   => __( 'Do not track logged-in administrators', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_ga4' ) );
		add_action( 'wp_head', array( $this, 'head' ), 5 );
		add_action( 'wp_body_open', array( $this, 'body' ) );
	}

	/**
	 * Enqueue Google's gtag.js properly (external script) with an inline config.
	 *
	 * @return void
	 */
	public function enqueue_ga4() {
		if ( ! $this->should_track() ) {
			return;
		}
		$ga4 = trim( (string) $this->get_setting( 'ga4_id', '' ) );
		if ( '' === $ga4 || ! preg_match( '/^G-[A-Z0-9]+$/i', $ga4 ) ) {
			return;
		}
		wp_enqueue_script( 'dicestack-ga4', 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $ga4 ), array(), null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves an unversioned URL.
		$id = esc_js( $ga4 );
		wp_add_inline_script( 'dicestack-ga4', "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . $id . "');" );
	}

	/**
	 * Should tracking run for the current visitor?
	 *
	 * @return bool
	 */
	private function should_track() {
		if ( ! empty( $this->get_setting( 'exclude_admins', true ) ) && current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Output head tags (GA4, GTM, Meta Pixel).
	 *
	 * @return void
	 */
	public function head() {
		if ( ! $this->should_track() ) {
			return;
		}
		$ga4   = trim( (string) $this->get_setting( 'ga4_id', '' ) );
		$gtm   = trim( (string) $this->get_setting( 'gtm_id', '' ) );
		$pixel = trim( (string) $this->get_setting( 'meta_pixel', '' ) );

		// GA4 (gtag.js) is enqueued in enqueue_ga4(); only GTM and Meta Pixel are inline here.

		if ( '' !== $gtm && preg_match( '/^GTM-[A-Z0-9]+$/i', $gtm ) ) {
			$id = esc_js( $gtm );
			echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . $id . "');</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- id escaped via esc_js.
		}

		if ( '' !== $pixel && ctype_digit( $pixel ) ) {
			$id = esc_js( $pixel );
			echo "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . $id . "');fbq('track','PageView');</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- id escaped via esc_js.
		}
	}

	/**
	 * Output the GTM noscript right after <body>.
	 *
	 * @return void
	 */
	public function body() {
		if ( ! $this->should_track() ) {
			return;
		}
		$gtm = trim( (string) $this->get_setting( 'gtm_id', '' ) );
		if ( '' !== $gtm && preg_match( '/^GTM-[A-Z0-9]+$/i', $gtm ) ) {
			echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $gtm ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
		}
	}
}
