<?php
/**
 * Robots.txt manager module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Lets you edit the virtual robots.txt from wp-admin. Only affects the virtual
 * robots.txt (no physical file is written), so it's safe and reversible.
 */
final class Robots_Txt extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'robots_txt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Robots.txt manager', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Edit your robots.txt rules from the dashboard — no FTP needed.', 'dicestack' );
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
		return 'file-code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 20,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'rules',
				'label'   => __( 'robots.txt content', 'dicestack' ),
				'type'    => 'textarea',
				'default' => "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php",
				'help'    => __( 'These rules replace the default WordPress robots.txt output.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'robots_txt', array( $this, 'filter_robots' ), 20, 2 );
	}

	/**
	 * Replace the virtual robots.txt output.
	 *
	 * @param string $output Existing output.
	 * @param bool   $public Whether the site is public.
	 * @return string
	 */
	public function filter_robots( $output, $public ) {
		$rules = trim( (string) $this->get_setting( 'rules', '' ) );

		// Respect the "discourage search engines" setting.
		if ( ! $public ) {
			return $output;
		}

		if ( '' === $rules ) {
			return $output;
		}

		$sitemap = "\nSitemap: " . esc_url_raw( home_url( '/wp-sitemap.xml' ) ) . "\n";
		return $rules . "\n" . $sitemap;
	}
}
