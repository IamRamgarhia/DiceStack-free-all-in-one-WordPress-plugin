/**
 * DiceStack admin dashboard controller. Vanilla JS, no jQuery.
 */
( function () {
	'use strict';

	var cfg = window.DiceStackAdmin || {};

	function post( action, data ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', cfg.nonce );
		Object.keys( data || {} ).forEach( function ( k ) {
			body.append( k, data[ k ] );
		} );
		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( r ) {
			return r.json();
		} );
	}

	function qs( sel, ctx ) { return ( ctx || document ).querySelector( sel ); }
	function qsa( sel, ctx ) { return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) ); }

	/* ---------------- Live stat recalculation ---------------- */
	function recalcStats() {
		var cards = qsa( '.dicestack-card' );
		var active = 0, mem = 0, js = 0;
		cards.forEach( function ( card ) {
			if ( card.getAttribute( 'data-status' ) === 'enabled' ) {
				active++;
				mem += parseInt( card.getAttribute( 'data-mem' ), 10 ) || 0;
				js += parseFloat( card.getAttribute( 'data-js' ) ) || 0;
			}
		} );
		var aEl = qs( '#dicestack-stat-active' );
		var mEl = qs( '#dicestack-stat-mem' );
		var jEl = qs( '#dicestack-stat-js' );
		if ( aEl ) { aEl.textContent = active; }
		if ( mEl ) { mEl.textContent = mem.toLocaleString(); }
		if ( jEl ) { jEl.textContent = Math.round( js ).toLocaleString(); }

		var total = cards.length;
		var fe = qs( '#dicestack-fc-enabled' ), fd = qs( '#dicestack-fc-disabled' ), fa = qs( '#dicestack-fc-all' );
		if ( fe ) { fe.textContent = active; }
		if ( fd ) { fd.textContent = total - active; }
		if ( fa ) { fa.textContent = total; }
		var na = qs( '#dicestack-nav-active' );
		if ( na ) { na.textContent = active; }
	}

	/* ---------------- Jump to a card ---------------- */
	function jumpToCard( id ) {
		qsa( '.dicestack-nav-item' ).forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
		var allBtn = qs( '.dicestack-nav-item[data-filter="all"]' );
		if ( allBtn ) { allBtn.classList.add( 'is-active' ); }
		qsa( '.dicestack-filter-btn' ).forEach( function ( b ) { b.classList.toggle( 'is-active', b.getAttribute( 'data-status' ) === 'all' ); } );
		var search = qs( '#dicestack-search' );
		if ( search ) { search.value = ''; }
		applyFilters();
		var card = qs( '.dicestack-card[data-module="' + id + '"]' );
		if ( card ) {
			card.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			card.classList.add( 'dicestack-flash' );
			setTimeout( function () { card.classList.remove( 'dicestack-flash' ); }, 1200 );
		}
	}

	function toastContainer() {
		var wrap = qs( '#dicestack-toasts' );
		if ( ! wrap ) {
			wrap = document.createElement( 'div' );
			wrap.id = 'dicestack-toasts';
			document.body.appendChild( wrap );
		}
		return wrap;
	}

	/* ---------------- "Ready" toast after a reload (tools that add a page) ---------------- */
	function showReadyToast() {
		var raw;
		try { raw = sessionStorage.getItem( 'dicestackJustEnabled' ); sessionStorage.removeItem( 'dicestackJustEnabled' ); } catch ( e ) { return; }
		if ( ! raw ) { return; }
		var data;
		try { data = JSON.parse( raw ); } catch ( e ) { return; }
		if ( ! data || ! data.id ) { return; }

		var toast = document.createElement( 'div' );
		toast.className = 'dicestack-toast';
		var h = document.createElement( 'div' );
		h.className = 'dicestack-toast-h';
		h.textContent = '✓ ' + ( data.name || 'Tool' ) + ' ' + ( cfg.i18n.readyToOpen || 'is enabled and ready' );
		toast.appendChild( h );
		var p = document.createElement( 'div' );
		p.className = 'dicestack-toast-d';
		p.textContent = cfg.i18n.clickOpen || 'It was added to your dashboard. Click Open to configure it now.';
		toast.appendChild( p );
		var actions = document.createElement( 'div' );
		actions.className = 'dicestack-toast-a';
		var open = document.createElement( 'button' );
		open.type = 'button';
		open.className = 'dicestack-toast-btn';
		open.textContent = cfg.i18n.openIt || 'Open';
		function remove() { if ( toast.parentNode ) { toast.parentNode.removeChild( toast ); } }
		open.addEventListener( 'click', function () {
			remove();
			var card = qs( '.dicestack-card[data-module="' + data.id + '"]' );
			var btn = card ? qs( '.dicestack-settings-toggle', card ) : null;
			if ( btn && ! btn.disabled ) { btn.click(); }
		} );
		actions.appendChild( open );
		toast.appendChild( actions );
		var x = document.createElement( 'button' );
		x.className = 'dicestack-toast-x';
		x.textContent = '×';
		x.addEventListener( 'click', remove );
		toast.appendChild( x );
		toastContainer().appendChild( toast );
		setTimeout( remove, 12000 );
	}

	/* ---------------- Toast on enable ---------------- */
	function showToast( card ) {
		var id   = card.getAttribute( 'data-module' );
		var name = ( ( qs( '.dicestack-card-title', card ) || {} ).textContent || id ).trim();
		var desc = ( ( qs( '.dicestack-card-desc', card ) || {} ).textContent || '' ).trim();

		var wrap = qs( '#dicestack-toasts' );
		if ( ! wrap ) {
			wrap = document.createElement( 'div' );
			wrap.id = 'dicestack-toasts';
			document.body.appendChild( wrap );
		}

		var toast = document.createElement( 'div' );
		toast.className = 'dicestack-toast';

		var h = document.createElement( 'div' );
		h.className = 'dicestack-toast-h';
		h.textContent = '✓ ' + name + ' ' + ( cfg.i18n.enabledLower || 'enabled' );
		toast.appendChild( h );

		if ( desc ) {
			var p = document.createElement( 'div' );
			p.className = 'dicestack-toast-d';
			p.textContent = desc;
			toast.appendChild( p );
		}

		var actions = document.createElement( 'div' );
		actions.className = 'dicestack-toast-a';

		// Configure (opens this tool's settings modal) when it has settings.
		var setBtn = qs( '.dicestack-settings-toggle', card );
		if ( setBtn && ! setBtn.disabled ) {
			var cfgLink = document.createElement( 'button' );
			cfgLink.type = 'button';
			cfgLink.className = 'dicestack-toast-btn';
			cfgLink.textContent = cfg.i18n.configure || 'Configure';
			cfgLink.addEventListener( 'click', function () { remove(); setBtn.click(); } );
			actions.appendChild( cfgLink );
		}

		var help = document.createElement( 'a' );
		help.className = 'dicestack-toast-link';
		help.href = 'https://dicecodes.com/dicestack/docs/#mod-' + id;
		help.target = '_blank';
		help.rel = 'noopener';
		help.textContent = cfg.i18n.howToUse || 'How to use';
		actions.appendChild( help );

		toast.appendChild( actions );

		var x = document.createElement( 'button' );
		x.className = 'dicestack-toast-x';
		x.setAttribute( 'aria-label', 'Dismiss' );
		x.textContent = '×';
		function remove() { if ( toast.parentNode ) { toast.parentNode.removeChild( toast ); } }
		x.addEventListener( 'click', remove );
		toast.appendChild( x );

		wrap.appendChild( toast );
		setTimeout( remove, 8000 );
	}

	/* ---------------- Toggle module ---------------- */
	function bindToggles() {
		qsa( '.dicestack-toggle-input' ).forEach( function ( input ) {
			input.addEventListener( 'change', function () {
				var card = input.closest( '.dicestack-card' );
				var id = card.getAttribute( 'data-module' );
				var enable = input.checked ? '1' : '0';

				// Warn before enabling a tool that overlaps with another active plugin.
				var conflict = card.getAttribute( 'data-conflict' );
				if ( input.checked && conflict ) {
					var warn = conflict + ' ' + ( ( cfg.i18n && cfg.i18n.conflictWarn ) || 'is already active and handles this too. Running both can conflict — enable this anyway?' );
					if ( ! window.confirm( warn ) ) {
						input.checked = false;
						return;
					}
				}

				input.disabled = true;

				post( 'dicestack_toggle_module', { module: id, enable: enable } ).then( function ( res ) {
					input.disabled = false;
					if ( ! res || ! res.success ) {
						input.checked = ! input.checked;
						return;
					}
					var on = res.data.active;
					card.setAttribute( 'data-status', on ? 'enabled' : 'disabled' );
					card.classList.toggle( 'is-enabled', on );

					var badge = qs( '.dicestack-status-badge', card );
					if ( badge ) {
						badge.textContent = on ? cfg.i18n.enabled : cfg.i18n.disabled;
						badge.classList.toggle( 'is-on', on );
						badge.classList.toggle( 'is-off', ! on );
					}
					updateChips( card, on );
					recalcStats();
					if ( on ) {
						// Tools that add their own admin page only appear after a reload —
						// reload, then prompt the user to open the newly added tool.
						if ( card.getAttribute( 'data-haspage' ) === '1' ) {
							var nm = ( ( qs( '.dicestack-card-title', card ) || {} ).textContent || id ).trim();
							try { sessionStorage.setItem( 'dicestackJustEnabled', JSON.stringify( { id: id, name: nm } ) ); } catch ( e ) {}
							window.location.reload();
							return;
						}
						showToast( card );
					}
				} ).catch( function () {
					input.disabled = false;
					input.checked = ! input.checked;
				} );
			} );
		} );
	}

	function updateChips( card, on ) {
		var mem = parseInt( card.getAttribute( 'data-mem' ), 10 ) || 0;
		var js = parseFloat( card.getAttribute( 'data-js' ) ) || 0;
		var chips = qsa( '.dicestack-chip', card );
		if ( chips[ 0 ] ) { chips[ 0 ].innerHTML = '<i class="ti ti-cpu"></i>' + ( on ? mem.toLocaleString() + ' KB' : '0 KB' ); }
		if ( chips[ 1 ] ) { chips[ 1 ].innerHTML = '<i class="ti ti-code"></i>' + ( on ? Math.round( js ).toLocaleString() + ' KB JS' : '0 KB JS' ); }
	}

	/* ---------------- Category nav ---------------- */
	function bindNav() {
		qsa( '.dicestack-nav-item' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				qsa( '.dicestack-nav-item' ).forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
				btn.classList.add( 'is-active' );
				var title = qs( '#dicestack-current-category' );
				if ( title ) { title.textContent = btn.querySelector( 'span' ).textContent; }
				applyFilters();
			} );
		} );
	}

	/* ---------------- Status filter ---------------- */
	function bindStatusFilter() {
		qsa( '.dicestack-filter-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				qsa( '.dicestack-filter-btn' ).forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
				btn.classList.add( 'is-active' );
				applyFilters();
			} );
		} );
	}

	/* ---------------- Search ---------------- */
	function bindSearch() {
		var input = qs( '#dicestack-search' );
		if ( ! input ) { return; }
		var t;
		input.addEventListener( 'input', function () {
			clearTimeout( t );
			t = setTimeout( applyFilters, 120 );
		} );
	}

	function applyFilters() {
		var navEl = qs( '.dicestack-nav-item.is-active' );
		var filter = navEl ? navEl.getAttribute( 'data-filter' ) : 'all';
		var statusEl = qs( '.dicestack-filter-btn.is-active' );
		var status = statusEl ? statusEl.getAttribute( 'data-status' ) : 'all';
		var term = ( qs( '#dicestack-search' ) || { value: '' } ).value.trim().toLowerCase();

		var visible = 0;
		qsa( '.dicestack-card' ).forEach( function ( card ) {
			var match;
			if ( filter === '__active' ) {
				match = card.getAttribute( 'data-status' ) === 'enabled';
			} else if ( filter === '__unavailable' ) {
				match = card.getAttribute( 'data-supported' ) === '0';
			} else {
				var okCat = filter === 'all' || card.getAttribute( 'data-category' ) === filter;
				var okStatus = status === 'all' || card.getAttribute( 'data-status' ) === status;
				match = okCat && okStatus;
			}
			var okTerm = ! term || card.getAttribute( 'data-name' ).indexOf( term ) !== -1;
			var show = match && okTerm;
			card.classList.toggle( 'is-hidden', ! show );
			if ( show ) { visible++; }
		} );

		var empty = qs( '#dicestack-empty' );
		if ( empty ) {
			if ( visible === 0 ) {
				var msg = ( cfg.i18n && cfg.i18n.noMatch ) || 'No tools match.';
				if ( filter === '__active' ) {
					msg = ( cfg.i18n && cfg.i18n.noActive ) || 'No tools are enabled yet. Open Recommended setup to get started.';
				} else if ( filter === '__unavailable' ) {
					msg = ( cfg.i18n && cfg.i18n.allSupported ) || 'Good news — your server supports every DiceStack tool.';
				}
				empty.textContent = msg;
				empty.hidden = false;
			} else {
				empty.hidden = true;
			}
		}
	}

	/* ---------------- Settings modal ---------------- */
	function bindSettings() {
		var modal = qs( '#dicestack-modal' );
		if ( ! modal ) { return; }
		var body  = qs( '.dicestack-modal-body', modal );
		var titleEl = qs( '.dicestack-modal-title', modal );
		var subEl = qs( '.dicestack-modal-sub', modal );

		function openModal() { modal.removeAttribute( 'hidden' ); document.addEventListener( 'keydown', escClose ); }
		function closeModal() { modal.setAttribute( 'hidden', '' ); body.innerHTML = ''; modal.classList.remove( 'is-wide' ); document.removeEventListener( 'keydown', escClose ); }
		function escClose( e ) { if ( e.key === 'Escape' ) { closeModal(); } }

		qsa( '.dicestack-modal-close, .dicestack-modal-backdrop', modal ).forEach( function ( e ) { e.addEventListener( 'click', closeModal ); } );

		// Open a dedicated tool page inside the modal (chrome stripped via iframe).
		function openPageModal( page, title ) {
			titleEl.textContent = title || '';
			subEl.textContent = '';
			modal.classList.add( 'is-wide' );
			openModal();
			body.innerHTML = '<div class="dicestack-modal-loading">' + ( cfg.i18n.saving || 'Loading…' ) + '</div>';
			var frame = document.createElement( 'iframe' );
			frame.className = 'dicestack-modal-frame';
			frame.src = cfg.adminUrl + 'admin.php?page=' + encodeURIComponent( page ) + '&dicestack_modal=1';
			frame.addEventListener( 'load', function () { var l = qs( '.dicestack-modal-loading', body ); if ( l ) { l.remove(); } } );
			body.innerHTML = '';
			body.appendChild( frame );
		}

		// Sidebar items (Agency Mode, Recommended setup) open in the modal too.
		qsa( '[data-modal-page]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				openPageModal( el.getAttribute( 'data-modal-page' ), el.getAttribute( 'data-modal-title' ) );
			} );
		} );

		qsa( '.dicestack-card .dicestack-settings-toggle' ).forEach( function ( btn ) {
			if ( btn.disabled ) { return; }
			btn.addEventListener( 'click', function () {
				var card = btn.closest( '.dicestack-card' );
				var id   = card.getAttribute( 'data-module' );
				var page = btn.getAttribute( 'data-page' );
				var cardTitle = ( qs( '.dicestack-card-title', card ) || {} ).textContent || '';

				if ( page ) {
					// Dedicated tools load their own page inside the modal.
					openPageModal( page, cardTitle );
					return;
				}

				titleEl.textContent = cardTitle;
				subEl.textContent   = ( qs( '.dicestack-card-desc', card ) || {} ).textContent || '';
				modal.classList.remove( 'is-wide' );
				openModal();

				body.innerHTML = '<p class="dicestack-no-settings">' + ( cfg.i18n.saving || 'Loading…' ) + '</p>';
				post( 'dicestack_get_settings_form', { module: id } ).then( function ( res ) {
					if ( res && res.success ) {
						// First-party, server-escaped HTML from a nonce+capability gated endpoint.
						body.innerHTML = res.data.html;
						bindForm( body, id, closeModal );
					} else {
						body.innerHTML = '<p class="dicestack-no-settings">' + cfg.i18n.error + '</p>';
					}
				} );
			} );
		} );
	}

	function bindForm( container, id, onClose ) {
		var form = qs( '.dicestack-settings-form', container );
		if ( ! form ) { return; }

		var cancel = qs( '.dicestack-settings-cancel', form );
		if ( cancel ) { cancel.addEventListener( 'click', function () { if ( onClose ) { onClose(); } } ); }

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var status = qs( '.dicestack-settings-status', form );
			var data = new FormData( form );
			var payload = { module: id };
			data.forEach( function ( v, k ) { payload[ k ] = v; } );
			if ( status ) { status.textContent = cfg.i18n.saving; }

			post( 'dicestack_save_settings', payload ).then( function ( res ) {
				if ( status ) { status.textContent = ( res && res.success ) ? cfg.i18n.saved : cfg.i18n.error; }
				setTimeout( function () { if ( status ) { status.textContent = ''; } }, 1800 );
			} );
		} );
	}

	/* ---------------- Expandable sidebar ---------------- */
	function bindSidebarExpand() {
		qsa( '.dicestack-nav-expand' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				var cat = btn.getAttribute( 'data-cat' );
				var sub = qs( '.dicestack-nav-sub[data-cat="' + cat + '"]' );
				btn.classList.toggle( 'is-open' );
				if ( sub ) { sub.classList.toggle( 'is-open' ); }
			} );
		} );

		qsa( '.dicestack-nav-sub button[data-jump]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				jumpToCard( btn.getAttribute( 'data-jump' ) );
			} );
		} );
	}

	/* ---------------- Bulk enable / disable ---------------- */
	function bindBulk() {
		function run( enable ) {
			var visible = qsa( '.dicestack-card' ).filter( function ( c ) { return ! c.classList.contains( 'is-hidden' ); } );
			var ids = visible.map( function ( c ) { return c.getAttribute( 'data-module' ); } );
			if ( ! ids.length ) { return; }
			var verb = enable ? cfg.i18n.enabled : cfg.i18n.disabled;
			if ( ! window.confirm( ids.length + ' modules → ' + verb ) ) { return; }
			post( 'dicestack_bulk_toggle', { enable: enable ? '1' : '0', modules: ids.join( ',' ) } ).then( function ( res ) {
				if ( res && res.success ) { window.location.reload(); }
			} );
		}
		var en = qs( '#dicestack-enable-all' );
		var di = qs( '#dicestack-disable-all' );
		if ( en ) { en.addEventListener( 'click', function () { run( true ); } ); }
		if ( di ) { di.addEventListener( 'click', function () { run( false ); } ); }
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		bindToggles();
		bindNav();
		bindStatusFilter();
		bindSearch();
		bindSettings();
		bindSidebarExpand();
		bindBulk();
		showReadyToast();
	} );
} )();
