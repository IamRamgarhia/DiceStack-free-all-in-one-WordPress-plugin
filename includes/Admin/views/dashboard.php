<?php
/**
 * Dashboard view.
 *
 * @package DiceStack
 *
 * @var \DiceStack\Core             $core
 * @var \DiceStack\Module_Registry  $registry
 * @var array                    $modules    id => Abstract_Module
 * @var array                    $categories slug => meta
 * @var string[]                 $active
 */

defined( 'ABSPATH' ) || exit;

// Variables below are local to this template (included within a method scope),
// not globals, so the global-prefix rule does not apply.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// Group module instances by category.
$by_category = array();
foreach ( $modules as $id => $module ) {
	$by_category[ $module->category() ][ $id ] = $module;
}

// Sort each category so Essential/Recommended tools rise to the top.
foreach ( $by_category as $cat_slug => $cat_modules ) {
	uksort(
		$cat_modules,
		static function ( $a, $b ) use ( $registry, $modules ) {
			$wa = $registry->module_badge( $a )['weight'];
			$wb = $registry->module_badge( $b )['weight'];
			if ( $wa === $wb ) {
				return strcasecmp( $modules[ $a ]->name(), $modules[ $b ]->name() );
			}
			return $wb - $wa;
		}
	);
	$by_category[ $cat_slug ] = $cat_modules;
}

// Live totals for active modules.
$total_mem = 0;
$total_js  = 0.0;
$total_css = 0.0;
$total_db  = 0;
foreach ( $active as $active_id ) {
	if ( isset( $modules[ $active_id ] ) ) {
		$p          = $modules[ $active_id ]->performance_profile();
		$total_mem += (int) $p['php_memory_kb'];
		$total_js  += (float) $p['front_js_kb'];
		$total_css += (float) $p['front_css_kb'];
		$total_db  += (int) $p['db_queries'];
	}
}

$active_count   = count( $active );
$total_count    = $registry->count();
$disabled_count = max( 0, $total_count - $active_count );
$settings_pages = $registry->settings_pages();

// Other active plugins that handle the same areas (for conflict warnings on enable).
$detected_plugins = \DiceStack\Environment::detected_plugins();

// Count modules the current server can't run (shown in their own sidebar view).
$unsupported_count = 0;
foreach ( $modules as $mid => $m ) {
	if ( ! $registry->requirements_met( $mid ) ) {
		$unsupported_count++;
	}
}
?>
<div class="dicestack-app">

	<aside class="dicestack-sidebar">
		<div class="dicestack-logo">
			<span class="dicestack-logo-name">DiceStack</span>
			<span class="dicestack-logo-tag">
				<?php
				/* translators: %d: number of modules. */
				echo esc_html( sprintf( __( '%d modules · all free', 'dicestack' ), $total_count ) );
				?>
			</span>
		</div>

		<nav class="dicestack-nav">
			<button class="dicestack-nav-item is-active" data-filter="all">
				<i class="ti ti-layout-grid" aria-hidden="true"></i>
				<span><?php esc_html_e( 'All modules', 'dicestack' ); ?></span>
				<span class="dicestack-badge"><?php echo (int) $total_count; ?></span>
			</button>

			<button class="dicestack-nav-item dicestack-nav-active" data-filter="__active">
				<i class="ti ti-bolt" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Active tools', 'dicestack' ); ?></span>
				<span class="dicestack-badge" id="dicestack-nav-active"><?php echo (int) $active_count; ?></span>
			</button>

			<?php foreach ( $categories as $slug => $cat ) : ?>
				<?php $cat_count = isset( $by_category[ $slug ] ) ? count( $by_category[ $slug ] ) : 0; ?>
				<div class="dicestack-nav-row">
					<button class="dicestack-nav-item" data-filter="<?php echo esc_attr( $slug ); ?>">
						<i class="ti ti-<?php echo esc_attr( $cat['icon'] ); ?>" aria-hidden="true"></i>
						<span><?php echo esc_html( $cat['label'] ); ?></span>
						<span class="dicestack-badge"><?php echo (int) $cat_count; ?></span>
					</button>
					<?php if ( $cat_count > 0 ) : ?>
						<button class="dicestack-nav-expand" data-cat="<?php echo esc_attr( $slug ); ?>" aria-label="<?php esc_attr_e( 'Expand category', 'dicestack' ); ?>">
							<i class="ti ti-chevron-down" aria-hidden="true"></i>
						</button>
					<?php endif; ?>
				</div>
				<?php if ( $cat_count > 0 ) : ?>
					<div class="dicestack-nav-sub" data-cat="<?php echo esc_attr( $slug ); ?>">
						<?php foreach ( $by_category[ $slug ] as $mid => $mod ) : ?>
							<button data-jump="<?php echo esc_attr( $mid ); ?>"><?php echo esc_html( $mod->name() ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>

			<?php if ( $unsupported_count > 0 ) : ?>
				<button class="dicestack-nav-item dicestack-nav-warn" data-filter="__unavailable">
					<i class="ti ti-server" aria-hidden="true"></i>
					<span><?php esc_html_e( 'Needs server support', 'dicestack' ); ?></span>
					<span class="dicestack-badge"><?php echo (int) $unsupported_count; ?></span>
				</button>
			<?php endif; ?>

			<a class="dicestack-nav-item dicestack-nav-setup" href="<?php echo esc_url( admin_url( 'admin.php?page=dicestack-setup' ) ); ?>" data-modal-page="dicestack-setup" data-modal-title="<?php esc_attr_e( 'Recommended setup', 'dicestack' ); ?>">
				<i class="ti ti-tool" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Recommended setup', 'dicestack' ); ?></span>
			</a>
			<a class="dicestack-nav-item dicestack-nav-agency" href="<?php echo esc_url( admin_url( 'admin.php?page=dicestack-agency' ) ); ?>" data-modal-page="dicestack-agency" data-modal-title="<?php esc_attr_e( 'Agency Mode', 'dicestack' ); ?>">
				<i class="ti ti-star" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Agency Mode', 'dicestack' ); ?></span>
			</a>
		</nav>

		<div class="dicestack-brand">
			<div class="dicestack-brand-head">
				<?php
				// Prefer a bundled logo (PNG first, then the shipped SVG); else the "DC" mark.
				$dicestack_logo = '';
				if ( file_exists( DICESTACK_PATH . 'assets/img/dice-codes-white.png' ) ) {
					$dicestack_logo = DICESTACK_URL . 'assets/img/dice-codes-white.png';
				} elseif ( file_exists( DICESTACK_PATH . 'assets/img/dice-codes-white.svg' ) ) {
					$dicestack_logo = DICESTACK_URL . 'assets/img/dice-codes-white.svg';
				}
				?>
					<?php if ( '' !== $dicestack_logo ) : ?>
					<span class="dicestack-brand-by">
						<img class="dicestack-brand-logo" src="<?php echo esc_url( $dicestack_logo ); ?>" alt="Dice Codes" />
						<span class="dicestack-brand-tag"><?php esc_html_e( 'Your digital dream team', 'dicestack' ); ?></span>
					</span>
				<?php else : ?>
					<span class="dicestack-brand-mark">DC</span>
					<span class="dicestack-brand-by">
						<span class="dicestack-brand-name">Dice Codes</span>
						<span class="dicestack-brand-tag"><?php esc_html_e( 'Your digital dream team', 'dicestack' ); ?></span>
					</span>
				<?php endif; ?>
			</div>
			<div class="dicestack-brand-links">
				<a href="https://dicecodes.com/dicestack/docs/" target="_blank" rel="noopener"><i class="ti ti-info-circle" aria-hidden="true"></i> <?php esc_html_e( 'Docs & guides', 'dicestack' ); ?></a>
				<a href="https://dicecodes.com" target="_blank" rel="noopener"><i class="ti ti-external-link" aria-hidden="true"></i> <?php esc_html_e( 'Visit Dice Codes', 'dicestack' ); ?></a>
				<a href="<?php echo esc_url( 'mailto:Contact@dicecodes.com?subject=' . rawurlencode( 'DiceStack support request' ) ); ?>"><i class="ti ti-mail" aria-hidden="true"></i> <?php esc_html_e( 'Get support', 'dicestack' ); ?></a>
				<a href="<?php echo esc_url( 'mailto:Contact@dicecodes.com?subject=' . rawurlencode( 'DiceStack feature request' ) ); ?>"><i class="ti ti-speakerphone" aria-hidden="true"></i> <?php esc_html_e( 'Request a feature', 'dicestack' ); ?></a>
			</div>
		</div>
	</aside>

	<main class="dicestack-main">

		<header class="dicestack-topbar">
			<h1 class="dicestack-topbar-title" id="dicestack-current-category"><?php esc_html_e( 'All modules', 'dicestack' ); ?></h1>
			<div class="dicestack-stats">
				<span class="dicestack-stat-pill"><span class="dicestack-dot dicestack-dot-green"></span><span id="dicestack-stat-active"><?php echo (int) $active_count; ?></span>&nbsp;<?php esc_html_e( 'active', 'dicestack' ); ?></span>
				<span class="dicestack-stat-pill"><span class="dicestack-dot dicestack-dot-blue"></span><span id="dicestack-stat-mem"><?php echo esc_html( number_format_i18n( $total_mem ) ); ?></span>&nbsp;<?php esc_html_e( 'KB RAM', 'dicestack' ); ?></span>
				<span class="dicestack-stat-pill"><span class="dicestack-dot dicestack-dot-amber"></span><span id="dicestack-stat-js"><?php echo esc_html( number_format_i18n( $total_js, 0 ) ); ?></span>&nbsp;<?php esc_html_e( 'KB front-end JS', 'dicestack' ); ?></span>
			</div>
		</header>

		<div class="dicestack-toolbar">
			<div class="dicestack-search">
				<i class="ti ti-search" aria-hidden="true"></i>
				<input type="search" id="dicestack-search" placeholder="<?php esc_attr_e( 'Search modules…', 'dicestack' ); ?>" />
			</div>
			<div class="dicestack-filters">
				<button class="dicestack-filter-btn is-active" data-status="all"><?php esc_html_e( 'All', 'dicestack' ); ?> <span class="dicestack-fcount" id="dicestack-fc-all"><?php echo (int) $total_count; ?></span></button>
				<button class="dicestack-filter-btn" data-status="enabled"><?php esc_html_e( 'Enabled', 'dicestack' ); ?> <span class="dicestack-fcount" id="dicestack-fc-enabled"><?php echo (int) $active_count; ?></span></button>
				<button class="dicestack-filter-btn" data-status="disabled"><?php esc_html_e( 'Disabled', 'dicestack' ); ?> <span class="dicestack-fcount" id="dicestack-fc-disabled"><?php echo (int) $disabled_count; ?></span></button>
			</div>
			<div class="dicestack-actions">
				<button class="dicestack-action-btn" id="dicestack-enable-all" title="<?php esc_attr_e( 'Enable all modules shown', 'dicestack' ); ?>"><i class="ti ti-toggle-right" aria-hidden="true"></i> <?php esc_html_e( 'Enable all', 'dicestack' ); ?></button>
				<button class="dicestack-action-btn" id="dicestack-disable-all" title="<?php esc_attr_e( 'Disable all modules shown', 'dicestack' ); ?>"><i class="ti ti-power" aria-hidden="true"></i> <?php esc_html_e( 'Disable all', 'dicestack' ); ?></button>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<?php wp_nonce_field( 'dicestack_clear_cache' ); ?>
					<input type="hidden" name="action" value="dicestack_clear_cache" />
					<button type="submit" class="dicestack-action-btn"><i class="ti ti-refresh" aria-hidden="true"></i> <?php esc_html_e( 'Clear cache', 'dicestack' ); ?></button>
				</form>
			</div>
		</div>

		<div class="dicestack-grid" id="dicestack-grid">
			<?php
			foreach ( $categories as $slug => $cat ) :
				if ( empty( $by_category[ $slug ] ) ) {
					continue;
				}
				foreach ( $by_category[ $slug ] as $id => $module ) :
					$is_active   = in_array( $id, $active, true );
					$profile     = $module->performance_profile();
					$ext         = $module->external_service();
					$dep_ok      = $registry->dependencies_met( $id );
					$dep_missing = $registry->unmet_dependencies( $id );
					$req_missing = $registry->unmet_requirements( $id );
					$req_ok      = empty( $req_missing );
					$feature     = \DiceStack\Environment::module_feature( $id );
					$conflict    = ( '' !== $feature && isset( $detected_plugins[ $feature ] ) ) ? $detected_plugins[ $feature ] : '';
					?>
					<div class="dicestack-card<?php echo $is_active ? ' is-enabled' : ''; ?>"
						data-module="<?php echo esc_attr( $id ); ?>"
						data-category="<?php echo esc_attr( $slug ); ?>"
						data-status="<?php echo $is_active ? 'enabled' : 'disabled'; ?>"
						data-supported="<?php echo $req_ok ? '1' : '0'; ?>"
						data-haspage="<?php echo isset( $settings_pages[ $id ] ) ? '1' : '0'; ?>"
						data-conflict="<?php echo esc_attr( $conflict ); ?>"
						data-name="<?php echo esc_attr( strtolower( $module->name() . ' ' . $module->description() ) ); ?>"
						data-mem="<?php echo (int) $profile['php_memory_kb']; ?>"
						data-js="<?php echo esc_attr( (float) $profile['front_js_kb'] ); ?>">

						<div class="dicestack-card-head">
							<span class="dicestack-icon dicestack-icon-<?php echo esc_attr( $cat['color'] ); ?>">
								<i class="ti ti-<?php echo esc_attr( $module->icon() ); ?>" aria-hidden="true"></i>
							</span>
							<div class="dicestack-card-meta">
								<span class="dicestack-card-title">
									<?php echo esc_html( $module->name() ); ?>
									<?php $badge = $registry->module_badge( $id ); ?>
									<?php if ( '' !== $badge['label'] ) : ?>
										<span class="dicestack-tag dicestack-tag-<?php echo esc_attr( $badge['key'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
									<?php endif; ?>
								</span>
								<span class="dicestack-card-desc"><?php echo esc_html( $module->description() ); ?></span>
							</div>
							<?php if ( $dep_ok && $req_ok ) : ?>
								<label class="dicestack-toggle-wrap" title="<?php esc_attr_e( 'Enable or disable this module', 'dicestack' ); ?>">
									<input type="checkbox" class="dicestack-toggle-input" <?php checked( $is_active ); ?> />
									<span class="dicestack-toggle"></span>
								</label>
							<?php elseif ( ! $dep_ok ) : ?>
								<span class="dicestack-dep-missing" title="<?php esc_attr_e( 'Requires WooCommerce', 'dicestack' ); ?>"><i class="ti ti-plug-off" aria-hidden="true"></i></span>
							<?php else : ?>
								<span class="dicestack-dep-missing" title="<?php esc_attr_e( 'Not supported by this server', 'dicestack' ); ?>"><i class="ti ti-server" aria-hidden="true"></i></span>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $dep_missing ) ) : ?>
							<?php foreach ( $dep_missing as $dm ) : ?>
								<div class="dicestack-req-note dicestack-dep-note">
									<i class="ti ti-plug-off" aria-hidden="true"></i>
									<span>
										<strong><?php echo esc_html( sprintf( /* translators: %s: plugin name (WooCommerce). */ __( '%s is not installed', 'dicestack' ), $dm['label'] ) ); ?></strong>
										— <?php echo esc_html( sprintf( /* translators: %s: plugin name. */ __( 'this tool turns on automatically once %s is active.', 'dicestack' ), $dm['label'] ) ); ?>
										<a href="<?php echo esc_url( admin_url( $dm['install'] ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: plugin name. */ __( 'Install %s', 'dicestack' ), $dm['label'] ) ); ?> &rarr;</a>
									</span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>

						<?php if ( ! $req_ok ) : ?>
							<?php foreach ( $req_missing as $rm ) : ?>
								<div class="dicestack-req-note">
									<i class="ti ti-server" aria-hidden="true"></i>
									<span><strong><?php echo esc_html( sprintf( /* translators: %s: capability. */ __( 'Needs %s', 'dicestack' ), $rm['label'] ) ); ?></strong> — <?php echo esc_html( $rm['hint'] ); ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>

						<?php if ( $module->replaces() ) : ?>
							<div class="dicestack-replaces">
								<i class="ti ti-arrow-back-up" aria-hidden="true"></i>
								<?php
								/* translators: %s: premium plugin name. */
								echo esc_html( sprintf( __( 'Replaces %s', 'dicestack' ), $module->replaces() ) );
								?>
							</div>
						<?php endif; ?>

						<div class="dicestack-perf">
							<span class="dicestack-chip"><i class="ti ti-cpu" aria-hidden="true"></i><?php echo esc_html( $is_active ? number_format_i18n( $profile['php_memory_kb'] ) . ' KB' : '0 KB' ); ?></span>
							<span class="dicestack-chip"><i class="ti ti-code" aria-hidden="true"></i><?php echo esc_html( $is_active ? number_format_i18n( $profile['front_js_kb'], 0 ) . ' KB JS' : '0 KB JS' ); ?></span>
							<span class="dicestack-chip"><i class="ti ti-database" aria-hidden="true"></i><?php echo esc_html( $is_active ? '+' . (int) $profile['db_queries'] : '+0' ); ?></span>
							<?php if ( ! $is_active && (int) $profile['php_memory_kb'] > 0 && $dep_ok && $req_ok ) : ?>
								<span class="dicestack-chip dicestack-chip-info"><i class="ti ti-info-circle" aria-hidden="true"></i>
									<?php
									/* translators: %s: memory in KB. */
									echo esc_html( sprintf( __( 'If on: +%s KB', 'dicestack' ), number_format_i18n( $profile['php_memory_kb'] ) ) );
									?>
								</span>
							<?php endif; ?>
						</div>

						<?php if ( $ext ) : ?>
							<div class="dicestack-ext-note">
								<i class="ti ti-world" aria-hidden="true"></i>
								<?php
								/* translators: %s: external service name. */
								echo esc_html( sprintf( __( 'Uses %s (consent required on enable)', 'dicestack' ), $ext['service'] ) );
								?>
							</div>
						<?php endif; ?>

						<div class="dicestack-card-foot">
							<?php $spage = isset( $settings_pages[ $id ] ) ? $settings_pages[ $id ] : ''; ?>
							<?php if ( $spage && ! $is_active ) : ?>
								<button class="dicestack-settings-toggle is-muted" type="button" disabled title="<?php esc_attr_e( 'Enable this tool first to configure it', 'dicestack' ); ?>">
									<i class="ti ti-settings" aria-hidden="true"></i> <?php esc_html_e( 'Settings', 'dicestack' ); ?>
								</button>
							<?php else : ?>
								<button class="dicestack-settings-toggle" type="button"<?php echo $spage ? ' data-page="' . esc_attr( $spage ) . '"' : ''; ?>>
									<i class="ti ti-settings" aria-hidden="true"></i> <?php esc_html_e( 'Settings', 'dicestack' ); ?>
								</button>
							<?php endif; ?>
							<a class="dicestack-help" href="<?php echo esc_url( 'https://dicecodes.com/dicestack/docs/#mod-' . $id ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'How to use this tool', 'dicestack' ); ?>">
								<i class="ti ti-info-circle" aria-hidden="true"></i> <?php esc_html_e( 'Help', 'dicestack' ); ?>
							</a>
							<?php if ( ! $dep_ok ) : ?>
								<span class="dicestack-status-badge is-locked">
									<?php echo esc_html( ! empty( $dep_missing ) ? sprintf( /* translators: %s: plugin name. */ __( 'Needs %s', 'dicestack' ), $dep_missing[0]['label'] ) : __( 'Unavailable', 'dicestack' ) ); ?>
								</span>
							<?php elseif ( ! $req_ok ) : ?>
								<span class="dicestack-status-badge is-locked"><?php esc_html_e( 'Unavailable', 'dicestack' ); ?></span>
							<?php else : ?>
								<span class="dicestack-status-badge <?php echo $is_active ? 'is-on' : 'is-off'; ?>">
									<?php echo $is_active ? esc_html__( 'Enabled', 'dicestack' ) : esc_html__( 'Disabled', 'dicestack' ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
					<?php
				endforeach;
			endforeach;
			?>
			<div class="dicestack-empty" id="dicestack-empty" hidden></div>
		</div>

	</main>

	<div id="dicestack-modal" class="dicestack-modal" hidden>
		<div class="dicestack-modal-backdrop"></div>
		<div class="dicestack-modal-box" role="dialog" aria-modal="true" aria-labelledby="dicestack-modal-title">
			<div class="dicestack-modal-head">
				<span class="dicestack-modal-icon"><i class="ti ti-settings" aria-hidden="true"></i></span>
				<span class="dicestack-modal-titles">
					<span class="dicestack-modal-title" id="dicestack-modal-title"></span>
					<span class="dicestack-modal-sub"></span>
				</span>
				<button class="dicestack-modal-close" aria-label="<?php esc_attr_e( 'Close', 'dicestack' ); ?>">&times;</button>
			</div>
			<div class="dicestack-modal-body"></div>
		</div>
	</div>
</div>
