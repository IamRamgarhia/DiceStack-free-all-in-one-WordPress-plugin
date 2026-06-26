<?php
/**
 * File Manager module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * An administrator-only file manager for the WordPress install: browse folders,
 * upload, download, edit text files, rename, delete, and create folders — no FTP
 * needed. Every path is validated to stay inside the site root, only
 * administrators can use it, and it disables itself when DISALLOW_FILE_MODS/EDIT
 * is set.
 */
final class File_Manager extends Abstract_Module {

	/**
	 * Editable text extensions.
	 *
	 * @var string[]
	 */
	private $editable = array( 'php', 'css', 'js', 'txt', 'html', 'htm', 'json', 'xml', 'md', 'htaccess', 'log', 'csv', 'svg', 'ini', 'yml', 'yaml', 'sql' );

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'file_manager';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'File manager', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Browse, upload, download, edit, rename and delete site files from the dashboard — no FTP needed.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'admin';
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
	public function replaces() {
		return 'premium file managers';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 35,
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
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_dicestack_fm_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_dicestack_fm_upload', array( $this, 'handle_upload' ) );
		add_action( 'admin_post_dicestack_fm_mkdir', array( $this, 'handle_mkdir' ) );
		add_action( 'admin_post_dicestack_fm_rename', array( $this, 'handle_rename' ) );
		add_action( 'admin_post_dicestack_fm_delete', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_dicestack_fm_download', array( $this, 'handle_download' ) );
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'File manager', 'dicestack' ),
			__( 'File manager', 'dicestack' ),
			'manage_options',
			'dicestack-files',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Resolve an existing path safely inside ABSPATH.
	 *
	 * @param string $rel Relative path.
	 * @return string|false Absolute path or false if outside the root / missing.
	 */
	private function resolve( $rel ) {
		$root = wp_normalize_path( realpath( ABSPATH ) );
		$rel  = ltrim( str_replace( '\\', '/', (string) $rel ), '/' );
		$path = wp_normalize_path( realpath( ABSPATH . $rel ) );
		if ( false === $path || '' === $root ) {
			return false;
		}
		if ( 0 !== strpos( $path, $root ) ) {
			return false; // Path traversal attempt.
		}
		return $path;
	}

	/**
	 * Resolve an existing directory (for creating children inside it).
	 *
	 * @param string $rel Relative directory path.
	 * @return string|false
	 */
	private function resolve_dir( $rel ) {
		$dir = $this->resolve( $rel );
		return ( $dir && is_dir( $dir ) ) ? $dir : false;
	}

	/**
	 * Whether file modification is disabled by WordPress constants.
	 *
	 * @return bool
	 */
	private function mods_blocked() {
		return ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) || ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT );
	}

	/**
	 * Render the file manager.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}

		echo '<div class="wrap dicestack-fm"><h1>' . esc_html__( 'File manager', 'dicestack' ) . '</h1>';
		echo '<style>'
			. '.dicestack-fm{max-width:1100px}'
			. '.dicestack-fm .dsfm-panel{background:#fff;border:1px solid #e4e7ec;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(16,24,40,.05)}'
			. '.dicestack-fm .dsfm-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid #eef1f5;background:#fafbfc}'
			. '.dicestack-fm .dsfm-crumbs{display:flex;align-items:center;gap:4px;flex-wrap:wrap;font-size:13px;flex:1;min-width:200px}'
			. '.dicestack-fm .dsfm-crumbs a{color:#2563eb;text-decoration:none}.dicestack-fm .dsfm-crumbs a:hover{text-decoration:underline}'
			. '.dicestack-fm .dsfm-crumbs .sep{color:#9aa3af}'
			. '.dicestack-fm .dsfm-crumbs .cur{color:#101828;font-weight:600}'
			. '.dicestack-fm .dsfm-search{display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #e4e7ec;border-radius:8px;padding:4px 10px}'
			. '.dicestack-fm .dsfm-search input{border:0;outline:none;box-shadow:none;font-size:13px;width:180px;background:transparent}'
			. '.dicestack-fm .dsfm-tools{display:flex;gap:8px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid #eef1f5}'
			. '.dicestack-fm .dsfm-tools form{display:flex;gap:6px;align-items:center}'
			. '.dicestack-fm .dsfm-tools input[type=text],.dicestack-fm .dsfm-tools input[type=file]{font-size:13px}'
			. '.dicestack-fm table{width:100%;border-collapse:collapse}'
			. '.dicestack-fm thead th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;padding:10px 18px;border-bottom:1px solid #eef1f5;background:#fff}'
			. '.dicestack-fm tbody td{padding:10px 18px;border-bottom:1px solid #f1f3f6;font-size:13.5px;vertical-align:middle}'
			. '.dicestack-fm tbody tr:hover{background:#f5f9ff}'
			. '.dicestack-fm tbody tr:last-child td{border-bottom:0}'
			. '.dicestack-fm .dsfm-ico{display:inline-flex;width:30px;height:30px;border-radius:7px;align-items:center;justify-content:center;font-size:15px;margin-right:10px;vertical-align:middle}'
			. '.dicestack-fm .dsfm-name a{text-decoration:none;color:#101828;font-weight:500}.dicestack-fm .dsfm-name a:hover{color:#2563eb}'
			. '.dicestack-fm .dsfm-up a{color:#6b7280;font-weight:600}'
			. '.dicestack-fm .dsfm-meta{color:#6b7280;font-size:12.5px;white-space:nowrap}'
			. '.dicestack-fm .dsfm-act{display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end}'
			. '.dicestack-fm .dsfm-act a{display:inline-block;font-size:12px;padding:3px 9px;border:1px solid #e4e7ec;border-radius:6px;text-decoration:none;color:#475467;background:#fff}'
			. '.dicestack-fm .dsfm-act a:hover{border-color:#2563eb;color:#2563eb}'
			. '.dicestack-fm .dsfm-act a.ds-fm-delete:hover{border-color:#dc2626;color:#dc2626}'
			. '.dsfm-ico-folder{background:#fef3c7}.dsfm-ico-image{background:#dcfce7}.dsfm-ico-code{background:#e0e7ff}.dsfm-ico-doc{background:#e0f2fe}.dsfm-ico-zip{background:#fae8ff}.dsfm-ico-file{background:#f1f3f6}'
			. '</style>';

		if ( $this->mods_blocked() ) {
			echo '<p>' . esc_html__( 'File editing is disabled on this site by the DISALLOW_FILE_MODS / DISALLOW_FILE_EDIT setting.', 'dicestack' ) . '</p></div>';
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only browsing; all writes are nonce-protected separately.
		$rel  = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';
		$edit = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
		$msg  = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' !== $edit ) {
			$this->render_editor( $edit );
			echo '</div>';
			return;
		}

		$notices = array(
			'uploaded' => __( 'File uploaded.', 'dicestack' ),
			'created'  => __( 'Folder created.', 'dicestack' ),
			'renamed'  => __( 'Renamed.', 'dicestack' ),
			'deleted'  => __( 'Deleted.', 'dicestack' ),
			'saved'    => __( 'File saved.', 'dicestack' ),
			'error'    => __( 'That action could not be completed.', 'dicestack' ),
		);
		if ( isset( $notices[ $msg ] ) ) {
			$class = 'error' === $msg ? 'notice-error' : 'notice-success';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $notices[ $msg ] ) . '</p></div>';
		}

		$dir = $this->resolve( $rel );
		if ( false === $dir || ! is_dir( $dir ) ) {
			$dir = wp_normalize_path( realpath( ABSPATH ) );
			$rel = '';
		}

		echo '<div class="dsfm-panel">';

		// Top bar: clickable breadcrumb + in-folder search.
		echo '<div class="dsfm-top"><nav class="dsfm-crumbs">';
		echo '<a href="' . esc_url( $this->link( '' ) ) . '">🏠 ' . esc_html__( 'Home', 'dicestack' ) . '</a>';
		if ( '' !== $rel ) {
			$parts = explode( '/', $rel );
			$accum = '';
			$last  = count( $parts ) - 1;
			foreach ( $parts as $i => $part ) {
				$accum .= ( '' === $accum ? '' : '/' ) . $part;
				echo '<span class="sep">›</span>';
				if ( $i === $last ) {
					echo '<span class="cur">' . esc_html( $part ) . '</span>';
				} else {
					echo '<a href="' . esc_url( $this->link( $accum ) ) . '">' . esc_html( $part ) . '</a>';
				}
			}
		}
		echo '</nav>';
		echo '<span class="dsfm-search">🔍 <input type="text" id="dsfm-search" placeholder="' . esc_attr__( 'Search this folder…', 'dicestack' ) . '" /></span>';
		echo '</div>';

		// Toolbar: new folder + upload.
		echo '<div class="dsfm-tools">';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_fm_mkdir' );
		echo '<input type="hidden" name="action" value="dicestack_fm_mkdir" /><input type="hidden" name="path" value="' . esc_attr( $rel ) . '" />';
		echo '<input type="text" name="name" required placeholder="' . esc_attr__( 'New folder name', 'dicestack' ) . '" />';
		echo '<button class="button">📁 ' . esc_html__( 'Create folder', 'dicestack' ) . '</button>';
		echo '</form>';
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_fm_upload' );
		echo '<input type="hidden" name="action" value="dicestack_fm_upload" /><input type="hidden" name="path" value="' . esc_attr( $rel ) . '" />';
		echo '<input type="file" name="upload" required />';
		echo '<button class="button button-primary">⬆ ' . esc_html__( 'Upload', 'dicestack' ) . '</button>';
		echo '</form>';
		echo '</div>';

		// Listing: folders first, then files, each alphabetical.
		$entries = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$entries = is_array( $entries ) ? $entries : array();
		$dirs    = array();
		$files   = array();
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			if ( is_dir( $dir . '/' . $entry ) ) {
				$dirs[] = $entry;
			} else {
				$files[] = $entry;
			}
		}
		natcasesort( $dirs );
		natcasesort( $files );

		echo '<table id="dsfm-table"><thead><tr><th>' . esc_html__( 'Name', 'dicestack' ) . '</th><th style="width:110px;">' . esc_html__( 'Size', 'dicestack' ) . '</th><th style="width:150px;">' . esc_html__( 'Modified', 'dicestack' ) . '</th><th style="width:240px;text-align:right;">' . esc_html__( 'Actions', 'dicestack' ) . '</th></tr></thead><tbody>';

		if ( '' !== $rel ) {
			$parent = trim( dirname( $rel ), '.' );
			echo '<tr class="dsfm-up"><td colspan="4"><a href="' . esc_url( $this->link( $parent ) ) . '">⬆ ' . esc_html__( 'Up one level', 'dicestack' ) . '</a></td></tr>';
		}

		foreach ( array_merge( $dirs, $files ) as $entry ) {
			$abs       = $dir . '/' . $entry;
			$child_rel = ( '' !== $rel ? $rel . '/' : '' ) . $entry;
			$is_dir    = is_dir( $abs );
			$actions   = $this->row_actions( $rel, $child_rel, $entry, $is_dir );
			$ico       = $this->type_icon( $entry, $is_dir );
			$modified  = date_i18n( 'M j, Y H:i', (int) filemtime( $abs ) );

			echo '<tr data-name="' . esc_attr( strtolower( $entry ) ) . '">';
			if ( $is_dir ) {
				echo '<td class="dsfm-name"><span class="dsfm-ico ' . esc_attr( $ico[1] ) . '">' . $ico[0] . '</span><a href="' . esc_url( $this->link( $child_rel ) ) . '">' . esc_html( $entry ) . '</a></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon is a fixed emoji.
				echo '<td class="dsfm-meta">—</td>';
			} else {
				echo '<td class="dsfm-name"><span class="dsfm-ico ' . esc_attr( $ico[1] ) . '">' . $ico[0] . '</span>' . esc_html( $entry ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon is a fixed emoji.
				echo '<td class="dsfm-meta">' . esc_html( size_format( (int) filesize( $abs ) ) ) . '</td>';
			}
			echo '<td class="dsfm-meta">' . esc_html( $modified ) . '</td>';
			echo '<td><span class="dsfm-act">' . $actions . '</span></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $actions built from escaped pieces.
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div>'; // .dsfm-panel
		echo '<p style="color:#a32d2d;margin-top:14px;">' . esc_html__( 'Editing or deleting core files can break your site. Make a backup first.', 'dicestack' ) . '</p>';
		echo '</div>';

		// One small, clean script for rename prompt + delete confirm.
		$js = 'document.addEventListener("click",function(e){'
			. 'var r=e.target.closest(".ds-fm-rename");'
			. 'if(r){e.preventDefault();var cur=r.getAttribute("data-name");var n=window.prompt(' . wp_json_encode( __( 'New name:', 'dicestack' ) ) . ',cur);if(!n||n===cur)return;'
			. 'var f=document.createElement("form");f.method="post";f.action=' . wp_json_encode( admin_url( 'admin-post.php' ) ) . ';'
			. 'var data={action:"dicestack_fm_rename",_wpnonce:' . wp_json_encode( wp_create_nonce( 'dicestack_fm_rename' ) ) . ',path:r.getAttribute("data-path"),old:cur,"new":n};'
			. 'for(var k in data){var i=document.createElement("input");i.type="hidden";i.name=k;i.value=data[k];f.appendChild(i);}'
			. 'document.body.appendChild(f);f.submit();return;}'
			. 'var d=e.target.closest(".ds-fm-delete");'
			. 'if(d){if(!window.confirm(' . wp_json_encode( __( 'Delete this item? This cannot be undone.', 'dicestack' ) ) . ')){e.preventDefault();}}'
			. '});'
			. 'var s=document.getElementById("dsfm-search");'
			. 'if(s){s.addEventListener("input",function(){var v=this.value.toLowerCase();var rows=document.querySelectorAll("#dsfm-table tbody tr[data-name]");for(var i=0;i<rows.length;i++){rows[i].style.display=rows[i].getAttribute("data-name").indexOf(v)>-1?"":"none";}});}';
		if ( function_exists( 'wp_print_inline_script_tag' ) ) {
			wp_print_inline_script_tag( $js );
		}
	}

	/**
	 * Build the per-row action links (download / edit / rename / delete).
	 *
	 * @param string $dir_rel   Current directory relative path.
	 * @param string $child_rel Item relative path.
	 * @param string $entry     Item name.
	 * @param bool   $is_dir    Whether the item is a directory.
	 * @return string
	 */
	private function row_actions( $dir_rel, $child_rel, $entry, $is_dir ) {
		$out = array();

		if ( ! $is_dir ) {
			$ext = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
			if ( in_array( $ext, $this->editable, true ) ) {
				$out[] = '<a href="' . esc_url( $this->link( $dir_rel, $child_rel ) ) . '">' . esc_html__( 'Edit', 'dicestack' ) . '</a>';
			}
			$dl = wp_nonce_url( add_query_arg(
				array(
					'action' => 'dicestack_fm_download',
					'file'   => rawurlencode( $child_rel ),
				),
				admin_url( 'admin-post.php' )
			), 'dicestack_fm_download' );
			$out[] = '<a href="' . esc_url( $dl ) . '">' . esc_html__( 'Download', 'dicestack' ) . '</a>';
		}

		// Rename — clean: a class + data attributes, handled by one script (below).
		$out[] = '<a href="#" class="ds-fm-rename" data-path="' . esc_attr( $dir_rel ) . '" data-name="' . esc_attr( $entry ) . '">' . esc_html__( 'Rename', 'dicestack' ) . '</a>';

		// Delete.
		$del = wp_nonce_url( add_query_arg(
			array(
				'action' => 'dicestack_fm_delete',
				'file'   => rawurlencode( $child_rel ),
			),
			admin_url( 'admin-post.php' )
		), 'dicestack_fm_delete' );
		$out[] = '<a href="' . esc_url( $del ) . '" class="ds-fm-delete" style="color:#a32d2d;">' . esc_html__( 'Delete', 'dicestack' ) . '</a>';

		return implode( ' ', $out );
	}

	/**
	 * An icon (emoji + colour class) for a file/folder by type.
	 *
	 * @param string $entry  Item name.
	 * @param bool   $is_dir Whether it's a directory.
	 * @return array{0:string,1:string} [ emoji, css class ]
	 */
	private function type_icon( $entry, $is_dir ) {
		if ( $is_dir ) {
			return array( '📁', 'dsfm-ico-folder' );
		}
		$ext = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'ico' ), true ) ) {
			return array( '🖼️', 'dsfm-ico-image' );
		}
		if ( in_array( $ext, array( 'php', 'js', 'css', 'json', 'xml', 'html', 'htm', 'sql', 'sh', 'yml', 'yaml', 'ini' ), true ) ) {
			return array( '📜', 'dsfm-ico-code' );
		}
		if ( in_array( $ext, array( 'zip', 'gz', 'tar', 'rar', '7z', 'bz2' ), true ) ) {
			return array( '🗜️', 'dsfm-ico-zip' );
		}
		if ( in_array( $ext, array( 'txt', 'md', 'pdf', 'doc', 'docx', 'csv', 'log' ), true ) ) {
			return array( '📄', 'dsfm-ico-doc' );
		}
		return array( '📄', 'dsfm-ico-file' );
	}

	/**
	 * Build a browse/edit link.
	 *
	 * @param string $path Directory relative path.
	 * @param string $edit File to edit (optional).
	 * @return string
	 */
	private function link( $path, $edit = '' ) {
		$args = array( 'page' => 'dicestack-files' );
		if ( '' !== $path ) {
			$args['path'] = $path;
		}
		if ( '' !== $edit ) {
			$args['edit'] = $edit;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Redirect back to a directory listing with a message.
	 *
	 * @param string $rel Directory relative path.
	 * @param string $msg Message key.
	 * @return void
	 */
	private function back( $rel, $msg ) {
		wp_safe_redirect( add_query_arg( 'msg', $msg, $this->link( $rel ) ) );
		exit;
	}

	/**
	 * Render the file editor.
	 *
	 * @param string $rel Relative file path.
	 * @return void
	 */
	private function render_editor( $rel ) {
		$abs = $this->resolve( $rel );
		$ext = strtolower( pathinfo( (string) $rel, PATHINFO_EXTENSION ) );
		if ( false === $abs || ! is_file( $abs ) || ! in_array( $ext, $this->editable, true ) ) {
			echo '<p>' . esc_html__( 'That file cannot be edited.', 'dicestack' ) . '</p>';
			return;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a site file for the admin editor.
		$contents = (string) file_get_contents( $abs );

		echo '<h2>' . esc_html( $rel ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_fm_save' );
		echo '<input type="hidden" name="action" value="dicestack_fm_save" />';
		echo '<input type="hidden" name="file" value="' . esc_attr( $rel ) . '" />';
		echo '<textarea name="content" style="width:100%;height:480px;font-family:monospace;font-size:13px;" spellcheck="false">' . esc_textarea( $contents ) . '</textarea>';
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Save file', 'dicestack' ) . '</button> <a href="' . esc_url( $this->link( trim( dirname( $rel ), '.' ) ) ) . '" class="button">' . esc_html__( 'Cancel', 'dicestack' ) . '</a></p>';
		echo '</form>';
	}

	/**
	 * Save an edited file.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_fm_save' ) || $this->mods_blocked() ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$rel = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
		$abs = $this->resolve( $rel );
		$ext = strtolower( pathinfo( (string) $rel, PATHINFO_EXTENSION ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- writability check before editing a site file; WP_Filesystem is not initialised in this admin context.
		if ( false === $abs || ! is_file( $abs ) || ! in_array( $ext, $this->editable, true ) || ! is_writable( $abs ) ) {
			wp_die( esc_html__( 'That file cannot be saved.', 'dicestack' ) );
		}

		$content = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw file content authored by an administrator.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $abs, $content );
		if ( false === $written ) {
			wp_die( esc_html__( 'Could not write the file. Check file permissions.', 'dicestack' ) );
		}

		$this->back( trim( dirname( $rel ), '.' ), 'saved' );
	}

	/**
	 * Handle a file upload into the current directory.
	 *
	 * @return void
	 */
	public function handle_upload() {
		$rel = $this->guard_write( 'dicestack_fm_upload' );
		$dir = $this->resolve_dir( $rel );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in guard_write().
		if ( false === $dir || empty( $_FILES['upload']['name'] ) ) {
			$this->back( $rel, 'error' );
		}

		// Use WordPress's own upload handler (validates the upload), but route the
		// destination to the folder being browsed via the upload_dir filter.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$target = $dir;
		$to_dir = function ( $dirs ) use ( $target ) {
			$dirs['path']   = $target;
			$dirs['url']    = '';
			$dirs['subdir'] = '';
			return $dirs;
		};
		add_filter( 'upload_dir', $to_dir );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in guard_write(); wp_handle_upload validates the file.
		$result = wp_handle_upload( $_FILES['upload'], array( 'test_form' => false, 'test_type' => false ) );
		remove_filter( 'upload_dir', $to_dir );

		$this->back( $rel, ( is_array( $result ) && empty( $result['error'] ) ) ? 'uploaded' : 'error' );
	}

	/**
	 * Create a new folder in the current directory.
	 *
	 * @return void
	 */
	public function handle_mkdir() {
		$rel  = $this->guard_write( 'dicestack_fm_mkdir' );
		$dir  = $this->resolve_dir( $rel );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in guard_write().
		$name = isset( $_POST['name'] ) ? sanitize_file_name( wp_unslash( $_POST['name'] ) ) : '';
		if ( false === $dir || '' === $name ) {
			$this->back( $rel, 'error' );
		}
		$ok = wp_mkdir_p( $dir . '/' . $name );
		$this->back( $rel, $ok ? 'created' : 'error' );
	}

	/**
	 * Rename a file or folder within its directory.
	 *
	 * @return void
	 */
	public function handle_rename() {
		$rel = $this->guard_write( 'dicestack_fm_rename' );
		$dir = $this->resolve_dir( $rel );
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified in guard_write().
		$old = isset( $_POST['old'] ) ? sanitize_file_name( wp_unslash( $_POST['old'] ) ) : '';
		$new = isset( $_POST['new'] ) ? sanitize_file_name( wp_unslash( $_POST['new'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( false === $dir || '' === $old || '' === $new ) {
			$this->back( $rel, 'error' );
		}
		$src = $this->resolve( ( '' !== $rel ? $rel . '/' : '' ) . $old );
		if ( false === $src ) {
			$this->back( $rel, 'error' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename, WordPress.PHP.NoSilencedErrors.Discouraged
		$ok = @rename( $src, $dir . '/' . $new );
		$this->back( $rel, $ok ? 'renamed' : 'error' );
	}

	/**
	 * Delete a file or folder (recursively, within the site root).
	 *
	 * @return void
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_fm_delete' ) || $this->mods_blocked() ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
		$file = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';
		$abs  = $this->resolve( $file );
		$root = wp_normalize_path( realpath( ABSPATH ) );
		$rel  = trim( dirname( $file ), '.' );
		if ( false === $abs || $abs === $root ) {
			$this->back( $rel, 'error' );
		}
		$ok = is_dir( $abs ) ? $this->rrmdir( $abs ) : @unlink( $abs ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged
		$this->back( $rel, $ok ? 'deleted' : 'error' );
	}

	/**
	 * Stream a file download.
	 *
	 * @return void
	 */
	public function handle_download() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_fm_download' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
		$file = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';
		$abs  = $this->resolve( $file );
		if ( false === $abs || ! is_file( $abs ) ) {
			wp_die( esc_html__( 'File not found.', 'dicestack' ) );
		}
		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . basename( $abs ) );
		header( 'Content-Length: ' . filesize( $abs ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $abs );
		exit;
	}

	/**
	 * Shared guard for POST write actions; returns the sanitized path.
	 *
	 * @param string $nonce Nonce action.
	 * @return string Relative path.
	 */
	private function guard_write( $nonce ) {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $nonce ) || $this->mods_blocked() ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		return isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
	}

	/**
	 * Recursively delete a directory (already validated inside ABSPATH).
	 *
	 * @param string $dir Absolute directory path.
	 * @return bool
	 */
	private function rrmdir( $dir ) {
		$items = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}
				$path = $dir . '/' . $item;
				if ( is_dir( $path ) ) {
					$this->rrmdir( $path );
				} else {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged
					@unlink( $path );
				}
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rmdir_rmdir, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPress.PHP.NoSilencedErrors.Discouraged
		return @rmdir( $dir );
	}
}
