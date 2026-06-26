<?php
/**
 * RSS Item Count module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Controls how many items appear in RSS feeds, independent of the posts-per-page
 * setting.
 */
final class RSS_Item_Count extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'rss_item_count';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'RSS feed item count', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Set how many posts appear in your RSS feeds.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'site';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'world';
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'count',
				'label'   => __( 'Items in feed', 'dicestack' ),
				'type'    => 'number',
				'default' => 10,
				'min'     => 1,
				'max'     => 100,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'pre_get_posts', array( $this, 'apply' ) );
	}

	/**
	 * Apply the feed item count to feed queries.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public function apply( $query ) {
		if ( $query->is_feed() && $query->is_main_query() ) {
			$query->set( 'posts_per_rss', (int) $this->get_setting( 'count', 10 ) );
			$query->set( 'posts_per_page', (int) $this->get_setting( 'count', 10 ) );
		}
	}
}
