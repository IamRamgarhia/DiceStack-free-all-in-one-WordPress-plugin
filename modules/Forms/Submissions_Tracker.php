<?php
/**
 * Universal Form Submissions Tracker module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Forms;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Captures submissions from popular third-party form plugins (Contact Form 7,
 * WPForms, Gravity Forms, Forminator, Elementor Pro Forms) into one searchable
 * log, so submissions are never lost even if the form plugin doesn't store them.
 */
final class Submissions_Tracker extends Abstract_Module {

	/**
	 * Log post type.
	 */
	const CPT = 'dicestack_form_log';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'submissions_tracker';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Form submissions tracker', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Log submissions from Contact Form 7, WPForms, Gravity, Forminator, and Elementor in one place.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'forms';
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
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_cpt' ) );

		// Contact Form 7.
		add_action( 'wpcf7_mail_sent', array( $this, 'capture_cf7' ) );
		// WPForms.
		add_action( 'wpforms_process_complete', array( $this, 'capture_wpforms' ), 10, 4 );
		// Gravity Forms.
		add_action( 'gform_after_submission', array( $this, 'capture_gravity' ), 10, 2 );
		// Forminator.
		add_action( 'forminator_form_after_save_entry', array( $this, 'capture_forminator' ), 10, 2 );
		// Elementor Pro forms.
		add_action( 'elementor_pro/forms/new_record', array( $this, 'capture_elementor' ), 10, 2 );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		}
	}

	/**
	 * Register the submission-detail meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'dicestack_form_log_detail',
			__( 'Submission', 'dicestack' ),
			array( $this, 'render_meta_box' ),
			self::CPT,
			'normal',
			'high'
		);
	}

	/**
	 * Render the captured fields.
	 *
	 * @param \WP_Post $post Log post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		$source = get_post_meta( $post->ID, '_dicestack_source', true );
		$fields = get_post_meta( $post->ID, '_dicestack_fields', true );
		echo '<p><strong>' . esc_html__( 'Source', 'dicestack' ) . ':</strong> ' . esc_html( $source ) . '</p>';
		if ( is_array( $fields ) && $fields ) {
			echo '<table class="widefat striped"><tbody>';
			foreach ( $fields as $key => $value ) {
				echo '<tr><th style="width:30%;">' . esc_html( $key ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
	}

	/**
	 * Register the log post type.
	 *
	 * @return void
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Form log', 'dicestack' ),
					'singular_name' => __( 'Form submission', 'dicestack' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'dicestack',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'menu_icon'       => 'dashicons-feedback',
			)
		);
	}

	/**
	 * Store one captured submission.
	 *
	 * @param string $source Source plugin label.
	 * @param array  $data   Field key => value pairs.
	 * @return void
	 */
	private function store( $source, array $data ) {
		$clean = array();
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'sanitize_text_field', array_map( 'strval', $value ) ) );
			}
			$clean[ sanitize_text_field( (string) $key ) ] = sanitize_textarea_field( (string) $value );
		}

		$id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				/* translators: %s: source plugin. */
				'post_title'  => sprintf( __( '%s submission', 'dicestack' ), $source ),
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_dicestack_source', $source );
			update_post_meta( $id, '_dicestack_fields', $clean );
		}
	}

	/**
	 * Capture a Contact Form 7 submission.
	 *
	 * @param mixed $contact_form CF7 form object.
	 * @return void
	 */
	public function capture_cf7( $contact_form ) {
		if ( ! class_exists( '\WPCF7_Submission' ) ) {
			return;
		}
		$submission = \WPCF7_Submission::get_instance();
		if ( $submission ) {
			$this->store( 'Contact Form 7', (array) $submission->get_posted_data() );
		}
	}

	/**
	 * Capture a WPForms submission.
	 *
	 * @param array $fields    Fields.
	 * @param array $entry     Entry.
	 * @param array $form_data Form data.
	 * @param int   $entry_id  Entry ID.
	 * @return void
	 */
	public function capture_wpforms( $fields, $entry, $form_data, $entry_id ) {
		$data = array();
		foreach ( (array) $fields as $field ) {
			if ( isset( $field['name'], $field['value'] ) ) {
				$data[ $field['name'] ] = $field['value'];
			}
		}
		$this->store( 'WPForms', $data );
	}

	/**
	 * Capture a Gravity Forms submission.
	 *
	 * @param array $entry Entry.
	 * @param array $form  Form.
	 * @return void
	 */
	public function capture_gravity( $entry, $form ) {
		$data = array();
		foreach ( (array) $form['fields'] as $field ) {
			$id    = isset( $field->id ) ? $field->id : null;
			$label = isset( $field->label ) ? $field->label : (string) $id;
			if ( null !== $id && isset( $entry[ $id ] ) ) {
				$data[ $label ] = $entry[ $id ];
			}
		}
		$this->store( 'Gravity Forms', $data );
	}

	/**
	 * Capture a Forminator submission.
	 *
	 * @param int   $form_id Form ID.
	 * @param mixed $entry   Entry object.
	 * @return void
	 */
	public function capture_forminator( $form_id, $entry ) {
		$data = array();
		if ( is_object( $entry ) && isset( $entry->meta_data ) && is_array( $entry->meta_data ) ) {
			foreach ( $entry->meta_data as $key => $meta ) {
				$data[ $key ] = isset( $meta['value'] ) ? ( is_array( $meta['value'] ) ? wp_json_encode( $meta['value'] ) : $meta['value'] ) : '';
			}
		}
		$this->store( 'Forminator', $data );
	}

	/**
	 * Capture an Elementor Pro form submission.
	 *
	 * @param mixed $record  Form record.
	 * @param mixed $handler AJAX handler.
	 * @return void
	 */
	public function capture_elementor( $record, $handler ) {
		if ( ! is_object( $record ) || ! method_exists( $record, 'get' ) ) {
			return;
		}
		$fields = $record->get( 'fields' );
		$data   = array();
		foreach ( (array) $fields as $field ) {
			if ( isset( $field['title'], $field['value'] ) ) {
				$data[ $field['title'] ] = $field['value'];
			}
		}
		$this->store( 'Elementor', $data );
	}
}
