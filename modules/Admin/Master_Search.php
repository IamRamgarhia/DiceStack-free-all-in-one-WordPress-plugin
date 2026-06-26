<?php
/**
 * Master Search module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A unified admin search that looks across posts, pages, products, and users at
 * once, with quick links to edit or view each result. AJAX-powered.
 */
final class Master_Search extends Abstract_Module {

	/**
	 * AJAX nonce action.
	 */
	const NONCE = 'dicestack_master_search';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'master_search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Master search', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Search posts, pages, products, and users from one box in the dashboard.', 'dicestack' );
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
		return 'search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
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
		add_action( 'wp_ajax_dicestack_master_search', array( $this, 'ajax_search' ) );
	}

	/**
	 * Register the search page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Master search', 'dicestack' ),
			__( 'Master search', 'dicestack' ),
			'edit_posts',
			'dicestack-search',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the search UI.
	 *
	 * @return void
	 */
	public function render_page() {
		$nonce = wp_create_nonce( self::NONCE );
		$ajax  = admin_url( 'admin-ajax.php' );
		echo '<div class="wrap"><h1>' . esc_html__( 'Master search', 'dicestack' ) . '</h1>';
		echo '<input type="search" id="dicestack-ms" class="regular-text" placeholder="' . esc_attr__( 'Search everything…', 'dicestack' ) . '" style="width:100%;max-width:520px;padding:10px;font-size:15px;" />';
		echo '<div id="dicestack-ms-results" style="margin-top:18px;"></div>';
		echo '</div>';
		?>
		<script>
		(function(){
		var box=document.getElementById('dicestack-ms'),out=document.getElementById('dicestack-ms-results'),t;
		if(!box)return;
		box.addEventListener('input',function(){clearTimeout(t);var q=box.value.trim();if(q.length<2){out.innerHTML='';return;}
		t=setTimeout(function(){
			var body=new URLSearchParams();body.append('action','dicestack_master_search');body.append('nonce',<?php echo wp_json_encode( $nonce ); ?>);body.append('q',q);
			fetch(<?php echo wp_json_encode( $ajax ); ?>,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
			.then(function(r){return r.json();}).then(function(res){
				if(!res||!res.success){out.innerHTML='';return;}
				var h='';res.data.groups.forEach(function(g){if(!g.items.length)return;h+='<h2>'+g.label+'</h2><ul>';g.items.forEach(function(i){h+='<li><a href="'+i.edit+'">'+i.title+'</a></li>';});h+='</ul>';});
				out.innerHTML=h||'<p><?php echo esc_js( __( 'No results.', 'dicestack' ) ); ?></p>';
			});
		},200);});
		})();
		</script>
		<?php
	}

	/**
	 * AJAX search handler.
	 *
	 * @return void
	 */
	public function ajax_search() {
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) || ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}
		$q = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
		if ( '' === $q ) {
			wp_send_json_error();
		}

		$groups = array();

		// Content.
		$types = get_post_types( array( 'public' => true ), 'names' );
		$posts = get_posts(
			array(
				's'           => $q,
				'post_type'   => array_values( $types ),
				'numberposts' => 20,
				'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
			)
		);
		$items = array();
		foreach ( $posts as $p ) {
			$edit = get_edit_post_link( $p->ID, '' );
			if ( ! $edit ) {
				continue;
			}
			$items[] = array(
				'title' => esc_html( get_the_title( $p ) ? get_the_title( $p ) : __( '(no title)', 'dicestack' ) ),
				'edit'  => esc_url_raw( $edit ),
			);
		}
		$groups[] = array( 'label' => esc_html__( 'Content', 'dicestack' ), 'items' => $items );

		// Users.
		$user_items = array();
		if ( current_user_can( 'list_users' ) ) {
			$users = get_users(
				array(
					'search'         => '*' . $q . '*',
					'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
					'number'         => 10,
				)
			);
			foreach ( $users as $u ) {
				$user_items[] = array(
					'title' => esc_html( $u->display_name . ' (' . $u->user_email . ')' ),
					'edit'  => esc_url_raw( get_edit_user_link( $u->ID ) ),
				);
			}
		}
		$groups[] = array( 'label' => esc_html__( 'Users', 'dicestack' ), 'items' => $user_items );

		wp_send_json_success( array( 'groups' => $groups ) );
	}
}
