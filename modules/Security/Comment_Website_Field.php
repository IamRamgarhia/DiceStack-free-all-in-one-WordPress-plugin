<?php
/**
 * Remove Comment Website Field module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the "Website" URL field from the comment form — it exists almost
 * solely to attract link spam.
 */
final class Comment_Website_Field extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'comment_website_field';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Remove comment website field', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove the URL field from comments to cut down on link spam.', 'dicestack' );
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
		return 'message-off';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 10,
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
		add_filter( 'comment_form_default_fields', array( $this, 'remove_url' ) );
	}

	/**
	 * Remove the url field.
	 *
	 * @param array $fields Comment form fields.
	 * @return array
	 */
	public function remove_url( $fields ) {
		if ( isset( $fields['url'] ) ) {
			unset( $fields['url'] );
		}
		return $fields;
	}
}
