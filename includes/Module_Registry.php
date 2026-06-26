<?php
/**
 * Module registry: the master catalog of categories and modules.
 *
 * @package DiceStack
 */

namespace DiceStack;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the authoritative list of categories and the id => class map for every
 * module. The map is the only place a module's existence is declared; module
 * objects are instantiated lazily (and cached) so the front end never loads
 * code for inactive modules.
 */
final class Module_Registry {

	/**
	 * Instantiated module objects, keyed by ID.
	 *
	 * @var array<string, Modules\Abstract_Module>
	 */
	private $instances = array();

	/**
	 * Category definitions: slug => label, Tabler icon, colour key.
	 *
	 * @return array<string, array{label:string,icon:string,color:string}>
	 */
	public function categories() {
		return array(
			'security'      => array(
				'label' => __( 'Security', 'dicestack' ),
				'icon'  => 'shield-lock',
				'color' => 'red',
			),
			'performance'   => array(
				'label' => __( 'Performance', 'dicestack' ),
				'icon'  => 'bolt',
				'color' => 'blue',
			),
			'seo'           => array(
				'label' => __( 'SEO', 'dicestack' ),
				'icon'  => 'search',
				'color' => 'green',
			),
			'forms'         => array(
				'label' => __( 'Forms', 'dicestack' ),
				'icon'  => 'forms',
				'color' => 'purple',
			),
			'woocommerce'   => array(
				'label' => __( 'WooCommerce', 'dicestack' ),
				'icon'  => 'shopping-cart',
				'color' => 'amber',
			),
			'media'         => array(
				'label' => __( 'Media', 'dicestack' ),
				'icon'  => 'photo',
				'color' => 'teal',
			),
			'content'       => array(
				'label' => __( 'Content & Marketing', 'dicestack' ),
				'icon'  => 'speakerphone',
				'color' => 'pink',
			),
			'admin'         => array(
				'label' => __( 'Admin & Developer', 'dicestack' ),
				'icon'  => 'tool',
				'color' => 'gray',
			),
			'site'          => array(
				'label' => __( 'Site Management', 'dicestack' ),
				'icon'  => 'server',
				'color' => 'blue',
			),
			'accessibility' => array(
				'label' => __( 'Accessibility & Legal', 'dicestack' ),
				'icon'  => 'accessible',
				'color' => 'purple',
			),
		);
	}

	/**
	 * Master module map: id => fully-qualified class name.
	 *
	 * This is the single source of truth for which modules ship. Add a line
	 * here when you add a module file. IDs are permanent once released.
	 *
	 * @return array<string, class-string>
	 */
	public function classes() {
		return array(
			// --- Security ---------------------------------------------------
			'security_hardening'    => Modules\Security\Security_Hardening::class,
			'login_protection'      => Modules\Security\Login_Protection::class,
			'rename_login'          => Modules\Security\Rename_Login::class,
			'disable_xmlrpc'        => Modules\Security\Disable_XMLRPC::class,
			'email_obfuscator'      => Modules\Security\Email_Obfuscator::class,
			'login_errors'          => Modules\Security\Login_Errors::class,
			'block_bad_bots'        => Modules\Security\Block_Bad_Bots::class,
			'disable_author_archives' => Modules\Security\Disable_Author_Archives::class,
			'comment_link_limit'    => Modules\Security\Comment_Link_Limit::class,
			'disable_app_passwords' => Modules\Security\Disable_App_Passwords::class,
			'activity_log'          => Modules\Security\Activity_Log::class,
			'disable_rest_users'    => Modules\Security\Disable_REST_Users::class,
			'comment_website_field' => Modules\Security\Comment_Website_Field::class,
			'force_https'           => Modules\Security\Force_HTTPS::class,
			'block_admin_subscribers' => Modules\Security\Block_Admin_Subscribers::class,
			'security_headers'      => Modules\Security\Security_Headers::class,
			'login_honeypot'        => Modules\Security\Login_Honeypot::class,
			'disable_feeds'         => Modules\Security\Disable_Feeds::class,
			'hide_php_errors'       => Modules\Security\Hide_PHP_Errors::class,
			'login_captcha'         => Modules\Security\Login_Captcha::class,
			'cloudflare'            => Modules\Security\Cloudflare::class,

			// --- Performance ------------------------------------------------
			'lazy_loading'          => Modules\Performance\Lazy_Loading::class,
			'heartbeat_control'     => Modules\Performance\Heartbeat_Control::class,
			'disable_emojis'        => Modules\Performance\Disable_Emojis::class,
			'database_optimizer'    => Modules\Performance\Database_Optimizer::class,
			'disable_dashicons'     => Modules\Performance\Disable_Dashicons::class,
			'remove_query_strings'  => Modules\Performance\Remove_Query_Strings::class,
			'disable_embeds'        => Modules\Performance\Disable_Embeds::class,
			'resource_hints'        => Modules\Performance\Resource_Hints::class,
			'page_cache'            => Modules\Performance\Page_Cache::class,
			'disable_self_pingbacks' => Modules\Performance\Disable_Self_Pingbacks::class,
			'optimize_comment_reply' => Modules\Performance\Optimize_Comment_Reply::class,
			'clean_head'            => Modules\Performance\Clean_Head::class,
			'defer_js'              => Modules\Performance\Defer_JS::class,
			'disable_jquery_migrate' => Modules\Performance\Disable_jQuery_Migrate::class,
			'preload_assets'        => Modules\Performance\Preload_Assets::class,
			'hover_prefetch'        => Modules\Performance\Hover_Prefetch::class,
			'disable_block_css'     => Modules\Performance\Disable_Block_CSS::class,
			'disable_rest_link'     => Modules\Performance\Disable_REST_Link::class,
			'minify_html'           => Modules\Performance\Minify_HTML::class,
			'minify_css'            => Modules\Performance\Minify_CSS::class,
			'minify_js'             => Modules\Performance\Minify_JS::class,
			'object_cache'          => Modules\Performance\Object_Cache::class,
			'disable_trackbacks'    => Modules\Performance\Disable_Trackbacks::class,
			'optimize_wc_scripts'   => Modules\Performance\Optimize_WC_Scripts::class,

			// --- SEO --------------------------------------------------------
			'meta_tags'             => Modules\SEO\Meta_Tags::class,
			'schema_jsonld'         => Modules\SEO\Schema_JSONLD::class,
			'robots_txt'            => Modules\SEO\Robots_Txt::class,
			'redirect_manager'      => Modules\SEO\Redirect_Manager::class,
			'breadcrumbs'           => Modules\SEO\Breadcrumbs::class,
			'attachment_redirect'   => Modules\SEO\Attachment_Redirect::class,
			'noindex_controls'      => Modules\SEO\Noindex_Controls::class,
			'rss_protect'           => Modules\SEO\RSS_Protect::class,
			'image_title_alt'       => Modules\SEO\Image_Title_Alt::class,
			'social_profiles'       => Modules\SEO\Social_Profiles::class,
			'default_social_image'  => Modules\SEO\Default_Social_Image::class,
			'faq_schema'            => Modules\SEO\FAQ_Schema::class,
			'site_verification'     => Modules\SEO\Site_Verification::class,
			'twitter_handles'       => Modules\SEO\Twitter_Handles::class,
			'canonical_tags'        => Modules\SEO\Canonical_Tags::class,
			'wc_product_schema'     => Modules\SEO\WC_Product_Schema::class,
			'llms_txt'              => Modules\SEO\LLMs_Txt::class,
			'seo_checker'           => Modules\SEO\SEO_Checker::class,
			'four04_monitor'        => Modules\SEO\Four04_Monitor::class,
			'analytics'             => Modules\SEO\Analytics::class,

			// --- Forms & Communication -------------------------------------
			'contact_form'          => Modules\Forms\Contact_Form::class,
			'smtp_mailer'           => Modules\Forms\SMTP_Mailer::class,
			'spam_shield'           => Modules\Forms\Spam_Shield::class,
			'newsletter_signup'     => Modules\Forms\Newsletter_Signup::class,
			'math_captcha'          => Modules\Forms\Math_Captcha::class,
			'terms_consent'         => Modules\Forms\Terms_Consent::class,
			'comment_author_no_link' => Modules\Forms\Comment_Author_No_Link::class,
			'submissions_tracker'   => Modules\Forms\Submissions_Tracker::class,

			// --- WooCommerce -----------------------------------------------
			'wc_product_labels'        => Modules\WooCommerce\Product_Labels::class,
			'wc_custom_order_statuses' => Modules\WooCommerce\Custom_Order_Statuses::class,
			'wc_min_max_order'         => Modules\WooCommerce\Min_Max_Order::class,
			'wc_extra_fees'            => Modules\WooCommerce\Extra_Fees::class,
			'wc_catalog_mode'          => Modules\WooCommerce\Catalog_Mode::class,
			'wc_free_shipping_bar'     => Modules\WooCommerce\Free_Shipping_Bar::class,
			'wc_checkout_fields'       => Modules\WooCommerce\Checkout_Fields::class,
			'wc_recently_viewed'       => Modules\WooCommerce\Recently_Viewed::class,
			'wc_product_tabs'          => Modules\WooCommerce\Product_Tabs::class,
			'wc_sales_count'           => Modules\WooCommerce\Sales_Count::class,
			'wc_trust_badges'          => Modules\WooCommerce\Trust_Badges::class,
			'wc_wishlist'              => Modules\WooCommerce\Wishlist::class,
			'wc_back_in_stock'         => Modules\WooCommerce\Back_In_Stock::class,
			'wc_hide_out_of_stock'     => Modules\WooCommerce\Hide_Out_Of_Stock::class,
			'wc_quantity_buttons'      => Modules\WooCommerce\Quantity_Buttons::class,
			'wc_continue_shopping'     => Modules\WooCommerce\Continue_Shopping::class,
			'wc_default_catalog_order' => Modules\WooCommerce\Default_Catalog_Order::class,
			'wc_sale_percentage'       => Modules\WooCommerce\Sale_Percentage::class,
			'wc_low_stock_display'     => Modules\WooCommerce\Low_Stock_Display::class,
			'wc_add_to_cart_text'      => Modules\WooCommerce\Add_To_Cart_Text::class,
			'wc_redirect_after_add'    => Modules\WooCommerce\Redirect_After_Add::class,
			'wc_disable_reviews'       => Modules\WooCommerce\Disable_Reviews::class,

			// --- Content & Marketing ---------------------------------------
			'scroll_to_top'         => Modules\Content\Scroll_To_Top::class,
			'disable_comments'      => Modules\Content\Disable_Comments::class,
			'post_cloner'           => Modules\Content\Post_Cloner::class,
			'maintenance_mode'      => Modules\Content\Maintenance_Mode::class,
			'announcement_bar'      => Modules\Content\Announcement_Bar::class,
			'table_of_contents'     => Modules\Content\Table_Of_Contents::class,
			'related_posts'         => Modules\Content\Related_Posts::class,
			'post_views'            => Modules\Content\Post_Views::class,
			'reading_time'          => Modules\Content\Reading_Time::class,
			'reading_progress'      => Modules\Content\Reading_Progress::class,
			'youtube_nocookie'      => Modules\Content\YouTube_NoCookie::class,
			'print_button'          => Modules\Content\Print_Button::class,
			'frontend_css'          => Modules\Content\Frontend_CSS::class,
			'theme_styler'          => Modules\Content\Theme_Styler::class,
			'comment_length_limit'  => Modules\Content\Comment_Length_Limit::class,
			'excerpt_control'       => Modules\Content\Excerpt_Control::class,
			'rss_featured_image'    => Modules\Content\RSS_Featured_Image::class,
			'footer_copyright'      => Modules\Content\Footer_Copyright::class,
			'widget_shortcodes'     => Modules\Content\Widget_Shortcodes::class,
			'updated_date'          => Modules\Content\Updated_Date::class,
			'heading_anchors'       => Modules\Content\Heading_Anchors::class,
			'disable_texturize'     => Modules\Content\Disable_Texturize::class,
			'search_highlight'      => Modules\Content\Search_Highlight::class,
			'auto_featured_image'   => Modules\Content\Auto_Featured_Image::class,
			'external_links'        => Modules\Content\External_Links::class,
			'copy_protection'       => Modules\Content\Copy_Protection::class,
			'author_box'            => Modules\Content\Author_Box::class,
			'responsive_tables'     => Modules\Content\Responsive_Tables::class,

			// --- Media -----------------------------------------------------
			'image_optimizer'       => Modules\Media\Image_Optimizer::class,
			'media_alt_text'        => Modules\Media\Media_Alt_Text::class,
			'image_sizes_control'   => Modules\Media\Image_Sizes_Control::class,
			'media_categories'      => Modules\Media\Media_Categories::class,
			'default_image_link'    => Modules\Media\Default_Image_Link::class,
			'allow_file_types'      => Modules\Media\Allow_File_Types::class,
			'image_lightbox'        => Modules\Media\Image_Lightbox::class,
			'sanitize_filenames'    => Modules\Media\Sanitize_Filenames::class,
			'restrict_upload_size'  => Modules\Media\Restrict_Upload_Size::class,

			// --- Site Management -------------------------------------------
			'limit_revisions'       => Modules\Site\Limit_Revisions::class,
			'update_notifications'  => Modules\Site\Update_Notifications::class,
			'wp_cron_control'       => Modules\Site\WP_Cron_Control::class,
			'disable_update_emails' => Modules\Site\Disable_Update_Emails::class,
			'config_export_import'  => Modules\Site\Config_Export_Import::class,
			'fix_missed_schedule'   => Modules\Site\Fix_Missed_Schedule::class,
			'mail_from'             => Modules\Site\Mail_From::class,
			'disable_admin_email_check' => Modules\Site\Disable_Admin_Email_Check::class,
			'rss_item_count'        => Modules\Site\RSS_Item_Count::class,
			'error_monitor'         => Modules\Site\Error_Monitor::class,
			'backup_restore'        => Modules\Site\Backup_Restore::class,
			'cloud_backup'          => Modules\Site\Cloud_Backup::class,
			'monthly_report'        => Modules\Site\Monthly_Report::class,
			'admin_custom_css'      => Modules\Admin\Admin_Custom_CSS::class,

			// --- Admin & Developer -----------------------------------------
			'svg_upload'            => Modules\Admin\SVG_Upload::class,
			'header_footer_code'    => Modules\Admin\Header_Footer_Code::class,
			'login_page_customizer' => Modules\Admin\Login_Page_Customizer::class,
			'dashboard_cleanup'     => Modules\Admin\Dashboard_Cleanup::class,
			'classic_editor'        => Modules\Admin\Classic_Editor::class,
			'admin_branding'        => Modules\Admin\Admin_Branding::class,
			'last_login'            => Modules\Admin\Last_Login::class,
			'admin_bar_control'     => Modules\Admin\Admin_Bar_Control::class,
			'hide_update_nag'       => Modules\Admin\Hide_Update_Nag::class,
			'welcome_widget'        => Modules\Admin\Welcome_Widget::class,
			'last_modified_column'  => Modules\Admin\Last_Modified_Column::class,
			'login_redirect'        => Modules\Admin\Login_Redirect::class,
			'posts_per_page'        => Modules\Admin\Posts_Per_Page::class,
			'autosave_interval'     => Modules\Admin\Autosave_Interval::class,
			'hide_help_tabs'        => Modules\Admin\Hide_Help_Tabs::class,
			'environment_badge'     => Modules\Admin\Environment_Badge::class,
			'editor_fullscreen_off' => Modules\Admin\Editor_Fullscreen_Off::class,

			// --- Accessibility & Legal -------------------------------------
			'cookie_consent'        => Modules\Accessibility\Cookie_Consent::class,
			'accessibility_toolbar' => Modules\Accessibility\Accessibility_Toolbar::class,
			'skip_to_content'       => Modules\Accessibility\Skip_To_Content::class,
			'focus_outline'         => Modules\Accessibility\Focus_Outline::class,
			'underline_links'       => Modules\Accessibility\Underline_Links::class,
			'reduce_motion'         => Modules\Accessibility\Reduce_Motion::class,
			'external_link_indicator' => Modules\Accessibility\External_Link_Indicator::class,
			'featured_image_column' => Modules\Admin\Featured_Image_Column::class,
			'show_id_column'        => Modules\Admin\Show_ID_Column::class,
			'remove_footer_version' => Modules\Admin\Remove_Footer_Version::class,
			'disable_block_patterns' => Modules\Admin\Disable_Block_Patterns::class,
			'admin_menu_editor'     => Modules\Admin\Admin_Menu_Editor::class,
			'hide_admin_notices'    => Modules\Admin\Hide_Admin_Notices::class,
			'shortcode_generator'   => Modules\Admin\Shortcode_Generator::class,
			'master_search'         => Modules\Admin\Master_Search::class,
			'file_manager'          => Modules\Admin\File_Manager::class,
			'htaccess_editor'       => Modules\Admin\Htaccess_Editor::class,
			'db_search_replace'     => Modules\Admin\DB_Search_Replace::class,
			'white_label'           => Modules\Admin\White_Label::class,
			'google_reviews'        => Modules\Content\Google_Reviews::class,
			'elementor_widgets'     => Modules\Content\Elementor_Widgets::class,
			'broken_image_finder'   => Modules\Media\Broken_Image_Finder::class,
		);
	}

	/**
	 * Back-compat alias used by Core: id => array{class:class-string}.
	 *
	 * @return array<string, array{class:class-string}>
	 */
	public function catalog() {
		$catalog = array();
		foreach ( $this->classes() as $id => $class ) {
			$catalog[ $id ] = array( 'class' => $class );
		}
		return $catalog;
	}

	/**
	 * Get (and cache) a module instance by ID. Returns null if unknown or the
	 * class is missing.
	 *
	 * @param string $id Module ID.
	 * @return Modules\Abstract_Module|null
	 */
	public function get_instance( $id ) {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		$classes = $this->classes();
		if ( ! isset( $classes[ $id ] ) ) {
			return null;
		}

		$class = $classes[ $id ];
		if ( ! class_exists( $class ) ) {
			return null;
		}

		$this->instances[ $id ] = new $class();
		return $this->instances[ $id ];
	}

	/**
	 * Instantiate every module (admin/dashboard use only).
	 *
	 * @return array<string, Modules\Abstract_Module>
	 */
	public function all_instances() {
		foreach ( array_keys( $this->classes() ) as $id ) {
			$this->get_instance( $id );
		}
		return $this->instances;
	}

	/**
	 * Are a module's declared dependencies satisfied on this site?
	 *
	 * @param string $id Module ID.
	 * @return bool
	 */
	public function dependencies_met( $id ) {
		$module = $this->get_instance( $id );
		if ( ! $module ) {
			return false;
		}

		foreach ( $module->dependencies() as $dependency ) {
			switch ( $dependency ) {
				case 'woocommerce':
					if ( ! class_exists( 'WooCommerce' ) ) {
						return false;
					}
					break;
			}
		}
		return true;
	}

	/**
	 * Plugin dependencies a module needs that are NOT active here, with a label
	 * and a one-click install search link so the dashboard can guide the user.
	 *
	 * @param string $id Module ID.
	 * @return array<int,array{slug:string,label:string,install:string}>
	 */
	public function unmet_dependencies( $id ) {
		$module = $this->get_instance( $id );
		if ( ! $module ) {
			return array();
		}

		$missing = array();
		foreach ( $module->dependencies() as $dependency ) {
			switch ( $dependency ) {
				case 'woocommerce':
					if ( ! class_exists( 'WooCommerce' ) ) {
						$missing[] = array(
							'slug'    => 'woocommerce',
							'label'   => __( 'WooCommerce', 'dicestack' ),
							'install' => 'plugin-install.php?s=woocommerce&tab=search&type=term',
						);
					}
					break;
			}
		}
		return $missing;
	}

	/**
	 * Server capabilities a module needs that are NOT available here.
	 *
	 * @param string $id Module ID.
	 * @return array<string,array{label:string,hint:string}>
	 */
	public function unmet_requirements( $id ) {
		$module = $this->get_instance( $id );
		if ( ! $module ) {
			return array();
		}
		return \DiceStack\Environment::missing( $module->requirements() );
	}

	/**
	 * Whether the environment meets a module's capability requirements.
	 *
	 * @param string $id Module ID.
	 * @return bool
	 */
	public function requirements_met( $id ) {
		return empty( $this->unmet_requirements( $id ) );
	}

	/**
	 * A promotional badge for a module so the most useful tools surface to the top
	 * of each category. Returns [ label, key, weight ] — higher weight sorts first.
	 *
	 * @param string $id Module ID.
	 * @return array{label:string,key:string,weight:int}
	 */
	public function module_badge( $id ) {
		static $essential = null, $recommended = null;
		if ( null === $essential ) {
			$essential   = \DiceStack\Core::recommended_defaults();
			$recommended = array(
				'page_cache',
				'minify_css',
				'minify_js',
				'defer_js',
				'object_cache',
				'image_optimizer',
				'force_https',
				'backup_restore',
				'cloud_backup',
				'smtp_mailer',
				'contact_form',
				'redirect_manager',
				'database_optimizer',
				'breadcrumbs',
				'table_of_contents',
				'related_posts',
				'cookie_consent',
				'faq_schema',
				'newsletter_signup',
				'accessibility_toolbar',
				'rename_login',
			);
		}
		if ( in_array( $id, $essential, true ) ) {
			return array(
				'label'  => __( 'Essential', 'dicestack' ),
				'key'    => 'essential',
				'weight' => 3,
			);
		}
		if ( in_array( $id, $recommended, true ) ) {
			return array(
				'label'  => __( 'Recommended', 'dicestack' ),
				'key'    => 'recommended',
				'weight' => 2,
			);
		}
		return array(
			'label'  => '',
			'key'    => '',
			'weight' => 0,
		);
	}

	/**
	 * Modules whose settings live on a dedicated admin page rather than an inline
	 * form: id => page slug. The dashboard "Settings" button links to these
	 * instead of opening the modal.
	 *
	 * @return array<string,string>
	 */
	public function settings_pages() {
		return array(
			'theme_styler'         => 'dicestack-styler',
			'object_cache'         => 'dicestack-object-cache',
			'backup_restore'       => 'dicestack-backups',
			'cloud_backup'         => 'dicestack-cloud',
			'cloudflare'           => 'dicestack-cloudflare',
			'image_optimizer'      => 'dicestack-optimize-images',
			'google_reviews'       => 'dicestack-reviews',
			'seo_checker'          => 'dicestack-seo-checker',
			'activity_log'         => 'dicestack-activity-log',
			'error_monitor'        => 'dicestack-errors',
			'four04_monitor'       => 'dicestack-404',
			'master_search'        => 'dicestack-search',
			'file_manager'         => 'dicestack-files',
			'db_search_replace'    => 'dicestack-search-replace',
			'config_export_import' => 'dicestack-config',
			'monthly_report'       => 'dicestack-report',
			'shortcode_generator'  => 'dicestack-shortcodes',
			'admin_menu_editor'    => 'dicestack-admin-menu',
		);
	}

	/**
	 * Total module count across the catalog.
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->classes() );
	}
}
