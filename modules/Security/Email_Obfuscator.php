<?php
/**
 * Email Obfuscator module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Encodes email addresses in post content so spam harvesters can't scrape them,
 * while remaining clickable for humans (via WordPress's antispambot()).
 */
final class Email_Obfuscator extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'email_obfuscator';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Email obfuscator', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide email addresses in content from spam bots while keeping them clickable.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'security';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'mail';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'obfuscate' ), 20 );
		add_filter( 'widget_text', array( $this, 'obfuscate' ), 20 );
	}

	/**
	 * Replace plain email addresses with antispambot-encoded versions.
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	public function obfuscate( $content ) {
		if ( ! is_string( $content ) || strpos( $content, '@' ) === false ) {
			return $content;
		}

		$pattern = '/([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/';

		return preg_replace_callback(
			$pattern,
			static function ( $matches ) {
				return antispambot( $matches[1] );
			},
			$content
		);
	}
}
