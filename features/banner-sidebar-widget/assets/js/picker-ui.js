( function ( window ) {
	'use strict';

	/**
	 * G4TAdsPicker — admin product picker UI.
	 *
	 * UI-only layer. Data (search, fetch) delegated to G4TAdsProvider.
	 * Requires G4TAdsProvider to be initialized before this runs.
	 *
	 * @param {Object} config
	 * @param {string} config.searchBoxSelector
	 * @param {string} config.selectedContainerSelector
	 * @param {string} config.previewContainerSelector
	 * @param {string} config.hiddenFieldSelector
	 * @param {string} [config.ajaxUrl]
	 * @param {string} [config.nonce]
	 * @param {number} [config.postId]
	 */
	window.G4TAdsPicker = function ( config ) {
		if ( ! config ) {
			return null;
		}

		var provider = window.G4TAdsProvider && window.G4TAdsProvider.getInstance();
		if ( ! provider ) {
			return null;
		}

		var searchBoxSelector       = config.searchBoxSelector       || '#algolia-searchbox';
		var selectedContainerSelector = config.selectedContainerSelector || '#selected-product-container';
		var previewContainerSelector  = config.previewContainerSelector  || '#g4t-ad-preview';
		var hiddenFieldSelector       = config.hiddenFieldSelector;
		var ajaxUrl                   = config.ajaxUrl || ( window.ajaxurl || '' );
		var nonce                     = config.nonce   || '';
		var postId                    = Number.isFinite( Number( config.postId ) ) ? Number( config.postId ) : 0;

		var $hiddenField       = hiddenFieldSelector ? document.querySelector( hiddenFieldSelector ) : null;
		var $searchBox         = document.querySelector( searchBoxSelector );
		var $selectedContainer = document.querySelector( selectedContainerSelector );
		var $previewContainer  = document.querySelector( previewContainerSelector );

		if ( ! $hiddenField || ! $searchBox || ! $selectedContainer || ! $previewContainer ) {
			return null;
		}

		// Create search input inside the container div (InstantSearch used to do this).
		var searchInput = document.createElement( 'input' );
		searchInput.type = 'search';
		searchInput.placeholder = 'Szukaj po nazwie lub identyfikatorze produktu...';
		searchInput.style.cssText = 'width:100%; padding:6px 10px; box-sizing:border-box; margin-bottom:6px;';
		$searchBox.appendChild( searchInput );

		var searchTimer  = null;
		var currentQuery = '';

		// ── Rendering helpers ──────────────────────────────────────────

		function renderHits( products ) {
			if ( ! products.length ) {
				$selectedContainer.innerHTML = '';
				return;
			}

			var html = products.map( function ( p ) {
				return '<div class="algolia-suggestion" data-id="' + escAttr( p.id ) + '">'
					+ '<strong>' + escHtml( p.name ) + '</strong><br>'
					+ '<em>Kategoria: ' + escHtml( p.category ) + '</em><br>'
					+ '<em>Identyfikator: ' + escHtml( p.id ) + '</em>'
					+ '</div>';
			} ).join( '' );

			$selectedContainer.innerHTML = html;
		}

		function renderSelected( product ) {
			if ( ! product ) {
				$selectedContainer.innerHTML = '';
				return;
			}

			$selectedContainer.innerHTML = '<div class="selected-product">'
				+ '<span>' + escHtml( product.name ) + '</span><br>'
				+ '<span>ID: ' + escHtml( product.id ) + '</span>'
				+ '<button type="button" class="remove-selected-product">&times;</button>'
				+ '</div>';
		}

		function escHtml( str ) {
			return String( str || '' )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' );
		}

		function escAttr( str ) {
			return String( str || '' ).replace( /"/g, '&quot;' );
		}

		// ── Search ────────────────────────────────────────────────────

		function runSearch( query ) {
			if ( query === '' ) {
				$selectedContainer.innerHTML = '';
				return;
			}

			provider.search( query, { hitsPerPage: 8 } ).then( renderHits ).catch( function () {
				$selectedContainer.innerHTML = '';
			} );
		}

		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				var q = searchInput.value.trim();
				clearTimeout( searchTimer );
				if ( q === currentQuery ) {
					return;
				}
				currentQuery = q;
				searchTimer  = setTimeout( function () { runSearch( q ); }, 250 );
			} );
		}

		// ── Selection ─────────────────────────────────────────────────

		function selectProduct( productId ) {
			$hiddenField.value = productId;

			provider.getById( productId ).then( function ( product ) {
				renderSelected( product );
				if ( searchInput ) {
					searchInput.value = '';
				}
				$selectedContainer.innerHTML = '';
				currentQuery = '';
				fetchProductPreview( productId );
			} ).catch( function () {
				$selectedContainer.innerHTML = '<p>Product not found.</p>';
			} );
		}

		$selectedContainer.addEventListener( 'click', function ( e ) {
			var suggestion = e.target.closest( '.algolia-suggestion' );
			if ( suggestion ) {
				selectProduct( String( suggestion.dataset.id || '' ) );
				return;
			}

			if ( e.target.classList.contains( 'remove-selected-product' ) ) {
				$hiddenField.value           = '';
				$selectedContainer.innerHTML = '';
				$previewContainer.innerHTML  = '';
			}
		} );

		// ── Preview (AJAX) ─────────────────────────────────────────────

		function fetchProductPreview( productId ) {
			if ( ! productId || ! ajaxUrl || ! nonce ) {
				$previewContainer.innerHTML = '';
				return;
			}

			var formData = new FormData();
			formData.append( 'action',     'g4t_fetch_product_preview' );
			formData.append( 'product_id', productId );
			formData.append( 'post_id',    String( postId ) );
			formData.append( 'nonce',      nonce );

			fetch( ajaxUrl, { method: 'POST', body: formData } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( response ) {
					if ( response && response.success ) {
						$previewContainer.innerHTML = response.data;
						return;
					}
					$previewContainer.innerHTML = '<p>Error fetching product data.</p>';
				} )
				.catch( function () {
					$previewContainer.innerHTML = '<p>Error fetching product data.</p>';
				} );
		}

		// ── Init ──────────────────────────────────────────────────────

		var savedProductId = String( $hiddenField.value || '' ).trim();
		if ( savedProductId ) {
			provider.getById( savedProductId ).then( function ( product ) {
				renderSelected( product );
				fetchProductPreview( savedProductId );
			} ).catch( function () {} );
		}

		return {
			refreshPreview: function () {
				var productId = String( $hiddenField.value || '' ).trim();
				if ( productId ) {
					fetchProductPreview( productId );
				}
			},
		};
	};
} )( window );
