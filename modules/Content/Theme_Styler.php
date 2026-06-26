<?php
/**
 * Theme Styler module — no-code visual CSS editor.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A no-code styler. Change common things (text/link/button/heading colours,
 * sizes, page background) with simple paste-a-value fields, or target any class
 * from your theme — DiceStack scans the active theme and offers its class names as
 * autocomplete so you don't have to hunt for selectors. Everything compiles to
 * clean CSS output on the front end.
 */
final class Theme_Styler extends Abstract_Module {

	/**
	 * Option storing the styler configuration.
	 */
	const OPTION = 'dicestack_styler';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'theme_styler';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Theme styler (no-code CSS)', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Change colours, fonts, and buttons with simple fields — no CSS knowledge needed.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'content';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium visual CSS editors';
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
		add_action( 'wp_head', array( $this, 'output' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_editor' ) );
		add_action( 'wp_ajax_dicestack_styler_visual_save', array( $this, 'ajax_visual_save' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'admin_post_dicestack_styler_save', array( $this, 'handle_save' ) );
		}
	}

	/**
	 * Load the front-end visual editor when an admin opens it with a valid nonce.
	 *
	 * @return void
	 */
	public function maybe_editor() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- nonce explicitly verified on the next line.
		if ( empty( $_GET['dicestack_styler'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$nonce = isset( $_GET['dicestack_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['dicestack_nonce'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( $nonce, 'dicestack_styler_visual' ) ) {
			return;
		}

		wp_enqueue_style( 'dicestack-styler-editor', DICESTACK_URL . 'assets/styler/editor.css', array(), DICESTACK_VERSION );
		wp_enqueue_script( 'dicestack-styler-editor', DICESTACK_URL . 'assets/styler/editor.js', array(), DICESTACK_VERSION, true );

		$c     = $this->config();
		$rules = ! empty( $c['rules'] ) && is_array( $c['rules'] ) ? $c['rules'] : array();
		wp_localize_script(
			'dicestack-styler-editor',
			'DiceStackStyler',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dicestack_styler_visual' ),
				'rules'   => array_values( $rules ),
				'exitUrl' => esc_url_raw( remove_query_arg( array( 'dicestack_styler', 'dicestack_nonce' ) ) ),
				'i18n'    => array(
					'title'    => __( 'DiceStack Visual Editor', 'dicestack' ),
					'hint'     => __( 'Click any element on the page to style it.', 'dicestack' ),
					'selector' => __( 'Selector', 'dicestack' ),
					'text'     => __( 'Text colour', 'dicestack' ),
					'bg'       => __( 'Background', 'dicestack' ),
					'size'     => __( 'Font size (px)', 'dicestack' ),
					'pad'      => __( 'Padding', 'dicestack' ),
					'radius'   => __( 'Corner radius (px)', 'dicestack' ),
					'save'     => __( 'Save changes', 'dicestack' ),
					'saved'    => __( 'Saved!', 'dicestack' ),
					'exit'     => __( 'Exit', 'dicestack' ),
				),
			)
		);
	}

	/**
	 * Persist rules captured by the visual editor.
	 *
	 * @return void
	 */
	public function ajax_visual_save() {
		if ( ! check_ajax_referer( 'dicestack_styler_visual', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		$incoming = isset( $_POST['rules'] ) ? json_decode( wp_unslash( $_POST['rules'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded + cleaned below.
		if ( ! is_array( $incoming ) ) {
			wp_send_json_error();
		}

		$c            = $this->config();
		$rules        = ! empty( $c['rules'] ) && is_array( $c['rules'] ) ? $c['rules'] : array();
		$index        = array();
		foreach ( $rules as $i => $r ) {
			$index[ ( isset( $r['selector'] ) ? $r['selector'] : '' ) . '|' . ( isset( $r['property'] ) ? $r['property'] : '' ) ] = $i;
		}

		foreach ( $incoming as $r ) {
			$sel  = $this->clean_selector( isset( $r['selector'] ) ? $r['selector'] : '' );
			$prop = $this->clean_value( isset( $r['property'] ) ? $r['property'] : '' );
			$val  = $this->clean_value( isset( $r['value'] ) ? $r['value'] : '' );
			if ( '' === $sel || '' === $prop || '' === $val ) {
				continue;
			}
			$key = $sel . '|' . $prop;
			if ( isset( $index[ $key ] ) ) {
				$rules[ $index[ $key ] ]['value'] = $val;
			} else {
				$rules[]        = array( 'selector' => $sel, 'property' => $prop, 'value' => $val );
				$index[ $key ]  = count( $rules ) - 1;
			}
		}

		$c['rules'] = array_values( $rules );
		update_option( self::OPTION, $c );
		wp_send_json_success( array( 'count' => count( $rules ) ) );
	}

	/**
	 * Read the stored config.
	 *
	 * @return array
	 */
	private function config() {
		$c = get_option( self::OPTION, array() );
		return is_array( $c ) ? $c : array();
	}

	/**
	 * Sanitise a CSS value (block selector/brace breakouts).
	 *
	 * @param string $v Value.
	 * @return string
	 */
	private function clean_value( $v ) {
		return trim( str_replace( array( '<', '>', '{', '}', ';' ), '', (string) $v ) );
	}

	/**
	 * Sanitise a selector.
	 *
	 * @param string $v Selector.
	 * @return string
	 */
	private function clean_selector( $v ) {
		return trim( str_replace( array( '<', '>', '{', '}', ';' ), '', (string) $v ) );
	}

	/**
	 * Compile the stored config into a CSS string.
	 *
	 * @return string
	 */
	private function compile() {
		$c   = $this->config();
		$css = '';

		$g = function ( $k ) use ( $c ) {
			return isset( $c[ $k ] ) ? $this->clean_value( $c[ $k ] ) : '';
		};

		if ( $g( 'text_color' ) ) {
			$css .= 'body{color:' . $g( 'text_color' ) . '}';
		}
		if ( $g( 'body_bg' ) ) {
			$css .= 'body{background-color:' . $g( 'body_bg' ) . '}';
		}
		if ( $g( 'font_size' ) ) {
			$css .= 'body{font-size:' . (int) $g( 'font_size' ) . 'px}';
		}
		if ( $g( 'link_color' ) ) {
			$css .= 'a{color:' . $g( 'link_color' ) . '}';
		}
		if ( $g( 'link_hover' ) ) {
			$css .= 'a:hover{color:' . $g( 'link_hover' ) . '}';
		}
		if ( $g( 'heading_color' ) ) {
			$css .= 'h1,h2,h3,h4,h5,h6{color:' . $g( 'heading_color' ) . '}';
		}

		$btn_sel = 'button,.button,.btn,input[type="submit"],.wp-block-button__link,.wp-element-button';
		$btn     = '';
		if ( $g( 'button_bg' ) ) {
			$btn .= 'background-color:' . $g( 'button_bg' ) . ';';
		}
		if ( $g( 'button_text' ) ) {
			$btn .= 'color:' . $g( 'button_text' ) . ';';
		}
		if ( '' !== $g( 'button_radius' ) ) {
			$btn .= 'border-radius:' . (int) $g( 'button_radius' ) . 'px;';
		}
		if ( '' !== $btn ) {
			$css .= $btn_sel . '{' . $btn . '}';
		}

		// Custom targeted rules.
		if ( ! empty( $c['rules'] ) && is_array( $c['rules'] ) ) {
			foreach ( $c['rules'] as $rule ) {
				$sel  = $this->clean_selector( isset( $rule['selector'] ) ? $rule['selector'] : '' );
				$prop = $this->clean_value( isset( $rule['property'] ) ? $rule['property'] : '' );
				$val  = $this->clean_value( isset( $rule['value'] ) ? $rule['value'] : '' );
				if ( '' !== $sel && '' !== $prop && '' !== $val ) {
					$css .= $sel . '{' . $prop . ':' . $val . '}';
				}
			}
		}

		if ( ! empty( $c['raw'] ) ) {
			$css .= "\n" . wp_strip_all_tags( (string) $c['raw'] );
		}

		return $css;
	}

	/**
	 * Output compiled CSS on the front end.
	 *
	 * @return void
	 */
	public function output() {
		$css = $this->compile();
		if ( '' !== trim( $css ) ) {
			echo "<style id=\"dicestack-theme-styler\">\n" . $css . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values sanitised in compile().
		}
	}

	/**
	 * Scan the active theme's stylesheet for class names (for autocomplete).
	 *
	 * @return string[]
	 */
	private function theme_classes() {
		$files   = array(
			get_stylesheet_directory() . '/style.css',
			get_template_directory() . '/style.css',
		);
		$classes = array();
		foreach ( array_unique( $files ) as $file ) {
			if ( ! is_readable( $file ) ) {
				continue;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading the theme stylesheet for autocomplete.
			$contents = file_get_contents( $file, false, null, 0, 500000 );
			if ( false === $contents ) {
				continue;
			}
			if ( preg_match_all( '/\.([a-zA-Z][a-zA-Z0-9_-]{1,40})/', $contents, $m ) ) {
				foreach ( $m[1] as $cls ) {
					$classes[ '.' . $cls ] = true;
				}
			}
		}
		$list = array_keys( $classes );
		sort( $list );
		return array_slice( $list, 0, 400 );
	}

	/**
	 * Register the styler page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Theme styler', 'dicestack' ),
			__( 'Theme styler', 'dicestack' ),
			'manage_options',
			'dicestack-styler',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Save the styler config.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_styler' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}

		$fields = array( 'text_color', 'body_bg', 'font_size', 'link_color', 'link_hover', 'heading_color', 'button_bg', 'button_text', 'button_radius' );
		$config = array();
		foreach ( $fields as $f ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised by clean_value().
			$config[ $f ] = isset( $_POST[ $f ] ) ? $this->clean_value( wp_unslash( $_POST[ $f ] ) ) : '';
		}

		$config['rules'] = array();
		if ( isset( $_POST['rule_selector'], $_POST['rule_property'], $_POST['rule_value'] ) && is_array( $_POST['rule_selector'] ) ) {
			$sels  = array_map( 'wp_unslash', (array) $_POST['rule_selector'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- unslashed here, cleaned below.
			$props = array_map( 'wp_unslash', (array) $_POST['rule_property'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- unslashed here, cleaned below.
			$vals  = array_map( 'wp_unslash', (array) $_POST['rule_value'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- unslashed here, cleaned below.
			foreach ( $sels as $i => $sel ) {
				$config['rules'][] = array(
					'selector' => $this->clean_selector( $sel ),
					'property' => $this->clean_value( isset( $props[ $i ] ) ? $props[ $i ] : '' ),
					'value'    => $this->clean_value( isset( $vals[ $i ] ) ? $vals[ $i ] : '' ),
				);
			}
		}

		$config['raw'] = isset( $_POST['raw'] ) ? wp_strip_all_tags( (string) wp_unslash( $_POST['raw'] ) ) : '';

		update_option( self::OPTION, $config );
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-styler&saved=1' ) );
		exit;
	}

	/**
	 * Render a labelled paste-a-value field.
	 *
	 * @param string $key   Field key.
	 * @param string $label Label.
	 * @param string $ph    Placeholder.
	 * @param array  $c     Config.
	 * @return void
	 */
	private function field( $key, $label, $ph, $c ) {
		$val = isset( $c[ $key ] ) ? $c[ $key ] : '';
		echo '<tr><th style="text-align:left;font-weight:500;">' . esc_html( $label ) . '</th><td>';
		echo '<input type="text" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $ph ) . '" class="regular-text" />';
		echo '</td></tr>';
	}

	/**
	 * Render the styler page.
	 *
	 * @return void
	 */
	public function render_page() {
		$c       = $this->config();
		$classes = $this->theme_classes();
		$rules   = ! empty( $c['rules'] ) && is_array( $c['rules'] ) ? $c['rules'] : array();
		// Always show at least 6 rule rows.
		while ( count( $rules ) < 6 ) {
			$rules[] = array(
				'selector' => '',
				'property' => '',
				'value'    => '',
			);
		}
		$props = array( 'color', 'background-color', 'font-size', 'font-weight', 'padding', 'margin', 'border', 'border-radius', 'text-align', 'line-height', 'width', 'max-width', 'display' );

		echo '<div class="wrap"><h1>' . esc_html__( 'Theme styler', 'dicestack' ) . '</h1>';
		if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Styles saved.', 'dicestack' ) . '</p></div>';
		}
		echo '<p>' . esc_html__( 'Paste a colour or value into any field below — no CSS knowledge needed. Leave a field blank to skip it.', 'dicestack' ) . '</p>';

		// Launch the point-and-click visual editor on the live site.
		$editor_url = add_query_arg(
			array(
				'dicestack_styler' => 1,
				'dicestack_nonce'  => wp_create_nonce( 'dicestack_styler_visual' ),
			),
			home_url( '/' )
		);
		echo '<p style="margin:0 0 20px;"><a class="button button-primary button-hero" href="' . esc_url( $editor_url ) . '">&#128396; ' . esc_html__( 'Open visual editor — click any element to style it', 'dicestack' ) . '</a></p>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_styler' );
		echo '<input type="hidden" name="action" value="dicestack_styler_save" />';

		echo '<h2>' . esc_html__( 'Quick styles', 'dicestack' ) . '</h2>';
		echo '<table class="form-table"><tbody>';
		$this->field( 'text_color', __( 'Body text colour', 'dicestack' ), '#222222', $c );
		$this->field( 'body_bg', __( 'Page background colour', 'dicestack' ), '#ffffff', $c );
		$this->field( 'font_size', __( 'Base font size (px)', 'dicestack' ), '16', $c );
		$this->field( 'link_color', __( 'Link colour', 'dicestack' ), '#0a66c2', $c );
		$this->field( 'link_hover', __( 'Link hover colour', 'dicestack' ), '#003d80', $c );
		$this->field( 'heading_color', __( 'Heading colour', 'dicestack' ), '#111111', $c );
		$this->field( 'button_bg', __( 'Button background', 'dicestack' ), '#1b2a4a', $c );
		$this->field( 'button_text', __( 'Button text colour', 'dicestack' ), '#ffffff', $c );
		$this->field( 'button_radius', __( 'Button corner radius (px)', 'dicestack' ), '6', $c );
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Target anything in your theme', 'dicestack' ) . '</h2>';
		echo '<p>' . esc_html( sprintf( /* translators: %d: class count. */ __( 'Start typing in "Element/class" to pick from %d classes found in your theme, choose what to change, and paste a value.', 'dicestack' ), count( $classes ) ) ) . '</p>';

		// Datalist of theme classes for autocomplete.
		echo '<datalist id="dicestack-theme-classes">';
		foreach ( $classes as $cls ) {
			echo '<option value="' . esc_attr( $cls ) . '"></option>';
		}
		echo '</datalist>';
		echo '<datalist id="dicestack-css-props">';
		foreach ( $props as $p ) {
			echo '<option value="' . esc_attr( $p ) . '"></option>';
		}
		echo '</datalist>';

		echo '<table class="widefat" style="max-width:820px;"><thead><tr><th>' . esc_html__( 'Element / class', 'dicestack' ) . '</th><th>' . esc_html__( 'What to change', 'dicestack' ) . '</th><th>' . esc_html__( 'Value', 'dicestack' ) . '</th></tr></thead><tbody>';
		foreach ( $rules as $r ) {
			echo '<tr>';
			echo '<td><input type="text" name="rule_selector[]" list="dicestack-theme-classes" value="' . esc_attr( $r['selector'] ) . '" placeholder=".my-button" style="width:100%;" /></td>';
			echo '<td><input type="text" name="rule_property[]" list="dicestack-css-props" value="' . esc_attr( $r['property'] ) . '" placeholder="background-color" style="width:100%;" /></td>';
			echo '<td><input type="text" name="rule_value[]" value="' . esc_attr( $r['value'] ) . '" placeholder="#ff6600" style="width:100%;" /></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Advanced: raw CSS', 'dicestack' ) . '</h2>';
		echo '<textarea name="raw" rows="6" class="large-text" placeholder=".site-header { box-shadow: 0 2px 8px rgba(0,0,0,.1); }">' . esc_textarea( isset( $c['raw'] ) ? $c['raw'] : '' ) . '</textarea>';

		echo '<p style="margin-top:16px;"><button type="submit" class="button button-primary button-hero">' . esc_html__( 'Save styles', 'dicestack' ) . '</button></p>';
		echo '</form></div>';
	}
}
