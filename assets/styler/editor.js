/**
 * DiceStack Theme Styler — front-end visual editor.
 * Click any element, change it with the panel, see it live, and save.
 * Loaded only for administrators in editor mode.
 */
( function () {
	'use strict';

	var cfg   = window.DiceStackStyler || {};
	var i18n  = cfg.i18n || {};
	var rules = {}; // selector -> { property: value }
	var live, bar, panel, selInput, current = null, hoverEl = null;

	( cfg.rules || [] ).forEach( function ( r ) {
		if ( r && r.selector && r.property ) {
			rules[ r.selector ] = rules[ r.selector ] || {};
			rules[ r.selector ][ r.property ] = r.value;
		}
	} );

	function el( tag, attrs ) {
		var e = document.createElement( tag );
		if ( attrs ) { Object.keys( attrs ).forEach( function ( k ) { e.setAttribute( k, attrs[ k ] ); } ); }
		return e;
	}
	function qsa( s, c ) { return Array.prototype.slice.call( ( c || document ).querySelectorAll( s ) ); }
	function isUI( node ) { return node && node.closest && ( node.closest( '#dicestack-ve-bar' ) || node.closest( '#dicestack-ve-panel' ) ); }

	function rgbToHex( rgb ) {
		var m = rgb && rgb.match( /\d+/g );
		if ( ! m || m.length < 3 ) { return ''; }
		return '#' + m.slice( 0, 3 ).map( function ( n ) {
			var h = parseInt( n, 10 ).toString( 16 );
			return h.length < 2 ? '0' + h : h;
		} ).join( '' );
	}

	function getSelector( node ) {
		if ( node.id ) { return '#' + node.id; }
		var tag = node.tagName.toLowerCase();
		var cls = Array.prototype.slice.call( node.classList ).filter( function ( c ) { return c.indexOf( 'dicestack-ve-' ) !== 0; } );
		if ( cls.length ) { return tag + '.' + cls.slice( 0, 2 ).join( '.' ); }
		var parent = node.parentElement;
		if ( parent && parent !== document.body ) {
			var pcls = Array.prototype.slice.call( parent.classList ).filter( function ( c ) { return c.indexOf( 'dicestack-ve-' ) !== 0; } );
			var psel = parent.id ? '#' + parent.id : ( pcls.length ? parent.tagName.toLowerCase() + '.' + pcls[ 0 ] : parent.tagName.toLowerCase() );
			var idx  = Array.prototype.indexOf.call( parent.children, node ) + 1;
			return psel + ' > ' + tag + ':nth-child(' + idx + ')';
		}
		return tag;
	}

	function rebuildLive() {
		var css = '';
		Object.keys( rules ).forEach( function ( sel ) {
			var decl = '';
			Object.keys( rules[ sel ] ).forEach( function ( p ) {
				if ( rules[ sel ][ p ] !== '' ) { decl += p + ':' + rules[ sel ][ p ] + ' !important;'; }
			} );
			if ( decl ) { css += sel + '{' + decl + '}'; }
		} );
		if ( live ) { live.textContent = css; }
	}

	function setRule( sel, prop, val ) {
		if ( ! sel ) { return; }
		rules[ sel ] = rules[ sel ] || {};
		if ( val === '' ) { delete rules[ sel ][ prop ]; } else { rules[ sel ][ prop ] = val; }
		rebuildLive();
	}

	function clearSelected() { qsa( '.dicestack-ve-selected' ).forEach( function ( e ) { e.classList.remove( 'dicestack-ve-selected' ); } ); }

	function buildToolbar() {
		bar = el( 'div', { id: 'dicestack-ve-bar' } );
		bar.innerHTML =
			'<span class="dicestack-ve-brand"><span class="dicestack-ve-mark">DC</span>' + ( i18n.title || 'DiceStack Visual Editor' ) + '</span>' +
			'<span class="dicestack-ve-hint">' + ( i18n.hint || 'Click any element to style it.' ) + '</span>' +
			'<span id="dicestack-ve-status"></span>' +
			'<button id="dicestack-ve-save">' + ( i18n.save || 'Save' ) + '</button>' +
			'<button id="dicestack-ve-exit">' + ( i18n.exit || 'Exit' ) + '</button>';
		document.body.appendChild( bar );
		document.getElementById( 'dicestack-ve-save' ).addEventListener( 'click', save );
		document.getElementById( 'dicestack-ve-exit' ).addEventListener( 'click', function () { window.location = cfg.exitUrl || '/'; } );
	}

	function buildPanel() {
		panel = el( 'div', { id: 'dicestack-ve-panel' } );
		panel.innerHTML =
			'<button class="dicestack-ve-close" aria-label="Close">&times;</button>' +
			'<h3>' + ( i18n.selector || 'Selector' ) + '</h3>' +
			'<input type="text" id="dicestack-ve-selector" class="dicestack-ve-sel" />' +
			'<label>' + ( i18n.text || 'Text colour' ) + '</label><div class="dicestack-ve-color"><input type="color" data-c="color" /><input type="text" data-prop="color" placeholder="#222" /></div>' +
			'<label>' + ( i18n.bg || 'Background' ) + '</label><div class="dicestack-ve-color"><input type="color" data-c="background-color" /><input type="text" data-prop="background-color" placeholder="#fff" /></div>' +
			'<label>' + ( i18n.size || 'Font size (px)' ) + '</label><input type="number" data-prop="font-size" data-unit="px" />' +
			'<label>' + ( i18n.pad || 'Padding' ) + '</label><input type="text" data-prop="padding" placeholder="10px 16px" />' +
			'<label>' + ( i18n.radius || 'Corner radius (px)' ) + '</label><input type="number" data-prop="border-radius" data-unit="px" />';
		document.body.appendChild( panel );

		panel.querySelector( '.dicestack-ve-close' ).addEventListener( 'click', function () { panel.classList.remove( 'is-open' ); clearSelected(); } );
		selInput = document.getElementById( 'dicestack-ve-selector' );
		selInput.addEventListener( 'input', function () { current = selInput.value; } );

		panel.querySelectorAll( '[data-prop]' ).forEach( function ( inp ) {
			inp.addEventListener( 'input', function () {
				var prop = inp.getAttribute( 'data-prop' );
				var unit = inp.getAttribute( 'data-unit' ) || '';
				var v = inp.value.trim();
				if ( v !== '' && unit && /^\d+$/.test( v ) ) { v = v + unit; }
				setRule( current, prop, v );
				var picker = panel.querySelector( 'input[type=color][data-c="' + prop + '"]' );
				if ( picker && /^#[0-9a-f]{6}$/i.test( v ) ) { picker.value = v; }
			} );
		} );
		panel.querySelectorAll( 'input[type=color][data-c]' ).forEach( function ( pick ) {
			pick.addEventListener( 'input', function () {
				var prop = pick.getAttribute( 'data-c' );
				setRule( current, prop, pick.value );
				var txt = panel.querySelector( 'input[data-prop="' + prop + '"]' );
				if ( txt ) { txt.value = pick.value; }
			} );
		} );
	}

	function setField( prop, val ) {
		var p = panel.querySelector( 'input[type=color][data-c="' + prop + '"]' );
		var t = panel.querySelector( 'input[data-prop="' + prop + '"]' );
		if ( p && /^#[0-9a-f]{6}$/i.test( val ) ) { p.value = val; }
		if ( t ) { t.value = val || ''; }
	}
	function setNum( prop, val ) { var t = panel.querySelector( 'input[data-prop="' + prop + '"]' ); if ( t ) { t.value = ( val || 0 === val ) ? val : ''; } }

	function selectEl( node ) {
		clearSelected();
		node.classList.add( 'dicestack-ve-selected' );
		current = getSelector( node );
		selInput.value = current;
		var cs = getComputedStyle( node );
		setField( 'color', rgbToHex( cs.color ) );
		setField( 'background-color', rgbToHex( cs.backgroundColor ) );
		setNum( 'font-size', parseInt( cs.fontSize, 10 ) );
		setNum( 'border-radius', parseInt( cs.borderTopLeftRadius, 10 ) || '' );
		var pad = panel.querySelector( 'input[data-prop="padding"]' );
		if ( pad ) { pad.value = ''; }
		panel.classList.add( 'is-open' );
	}

	function onOver( e ) {
		if ( isUI( e.target ) ) { return; }
		if ( hoverEl ) { hoverEl.classList.remove( 'dicestack-ve-hover' ); }
		hoverEl = e.target;
		if ( hoverEl && hoverEl.classList ) { hoverEl.classList.add( 'dicestack-ve-hover' ); }
	}
	function onOut( e ) { if ( e.target && e.target.classList ) { e.target.classList.remove( 'dicestack-ve-hover' ); } }
	function onClick( e ) {
		if ( isUI( e.target ) ) { return; }
		e.preventDefault();
		e.stopPropagation();
		if ( hoverEl ) { hoverEl.classList.remove( 'dicestack-ve-hover' ); }
		selectEl( e.target );
	}

	function save() {
		var arr = [];
		Object.keys( rules ).forEach( function ( sel ) {
			Object.keys( rules[ sel ] ).forEach( function ( p ) { arr.push( { selector: sel, property: p, value: rules[ sel ][ p ] } ); } );
		} );
		var body = new URLSearchParams();
		body.append( 'action', 'dicestack_styler_visual_save' );
		body.append( 'nonce', cfg.nonce );
		body.append( 'rules', JSON.stringify( arr ) );
		var st = document.getElementById( 'dicestack-ve-status' );
		if ( st ) { st.textContent = '…'; }
		fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( st ) { st.textContent = ( res && res.success ) ? ( i18n.saved || 'Saved!' ) : 'Error'; setTimeout( function () { st.textContent = ''; }, 2500 ); }
			} );
	}

	function start() {
		document.documentElement.classList.add( 'dicestack-ve-active' );
		document.body.classList.add( 'dicestack-ve-active' );
		live = el( 'style', { id: 'dicestack-ve-live' } );
		document.head.appendChild( live );
		buildToolbar();
		buildPanel();
		rebuildLive();
		document.addEventListener( 'mouseover', onOver, true );
		document.addEventListener( 'mouseout', onOut, true );
		document.addEventListener( 'click', onClick, true );
	}

	if ( 'loading' !== document.readyState ) { start(); } else { document.addEventListener( 'DOMContentLoaded', start ); }
} )();
