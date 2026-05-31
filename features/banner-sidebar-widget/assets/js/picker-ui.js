( function( window, $ ) {
	'use strict';

	window.G4TAdsPicker = function( config ) {
		if ( ! config || ! $ ) {
			return null;
		}

		var searchBoxSelector = config.searchBoxSelector || '#algolia-searchbox';
		var selectedContainerSelector = config.selectedContainerSelector || '#selected-product-container';
		var previewContainerSelector = config.previewContainerSelector || '#g4t-ad-preview';
		var hiddenFieldSelector = config.hiddenFieldSelector;
		var indexName = config.indexName || 'go4taste_product_list';
		var algoliaAppId = config.algoliaAppId || '';
		var algoliaApiKey = config.algoliaApiKey || '';
		var ajaxUrl = config.ajaxUrl || ( window.ajaxurl || '' );
		var nonce = config.nonce || '';
		var postId = Number.isFinite( Number( config.postId ) ) ? Number( config.postId ) : 0;
		var $hiddenField = hiddenFieldSelector ? $( hiddenFieldSelector ) : $();
		var $selectedContainer = $( selectedContainerSelector );
		var $previewContainer = $( previewContainerSelector );

		if ( ! $hiddenField.length || ! $( searchBoxSelector ).length || ! $selectedContainer.length || ! $previewContainer.length ) {
			return null;
		}

		if ( ! algoliaAppId || ! algoliaApiKey || ! window.algoliasearch || ! window.instantsearch ) {
			$selectedContainer.html( '<p>Algolia is not configured.</p>' );
			return null;
		}

		var searchClient = window.algoliasearch( algoliaAppId, algoliaApiKey );
		var index = searchClient.initIndex( indexName );

		var search = window.instantsearch( {
			indexName: indexName,
			searchClient: searchClient,
			searchFunction: function( helper ) {
				if ( helper.state.query === '' ) {
					return;
				}
				helper.search();
			},
		} );

		search.addWidget(
			window.instantsearch.widgets.searchBox( {
				container: searchBoxSelector,
				placeholder: 'Szukaj po nazwie lub identyfikatorze produktu ...',
				showReset: false,
				showSubmit: false,
			} )
		);

		search.addWidget(
			window.instantsearch.widgets.hits( {
				container: selectedContainerSelector,
				templates: {
					item: function( hit ) {
						return '<div class="algolia-suggestion" data-id="' + hit.objectID + '">'
							+ '<strong>' + ( hit.name || '' ) + '</strong><br>'
							+ '<em>Kategoria: ' + ( hit.category || '' ) + '</em><br>'
							+ '<em>Identyfikator: ' + hit.objectID + '</em>'
							+ '</div>';
					},
				},
			} )
		);

		function displaySelectedProduct( productId ) {
			if ( ! productId ) {
				$selectedContainer.html( '' );
				return;
			}

			index
				.getObject( productId )
				.then( function( product ) {
					$selectedContainer.html(
						'<div class="selected-product">'
						+ '<span>' + ( product.name || '' ) + '</span><br>'
						+ '<span>ID: ' + productId + '</span>'
						+ '<button type="button" class="remove-selected-product">&times;</button>'
						+ '</div>'
					);
				} )
				.catch( function() {
					$selectedContainer.html( '<p>Product not found.</p>' );
				} );
		}

		function fetchProductPreview( productId ) {
			if ( ! productId || ! ajaxUrl || ! nonce ) {
				$previewContainer.html( '' );
				return;
			}

			$.ajax( {
				url: ajaxUrl,
				method: 'POST',
				data: {
					action: 'g4t_fetch_product_preview',
					product_id: productId,
					post_id: postId,
					nonce: nonce,
				},
				success: function( response ) {
					if ( response && response.success ) {
						$previewContainer.html( response.data );
						return;
					}
					$previewContainer.html( '<p>Error fetching product data.</p>' );
				},
				error: function() {
					$previewContainer.html( '<p>Error fetching product data.</p>' );
				},
			} );
		}

		$( document ).on( 'click', '.algolia-suggestion', function() {
			var productId = String( $( this ).data( 'id' ) || '' );
			$hiddenField.val( productId );
			displaySelectedProduct( productId );
			$( searchBoxSelector + ' input' ).val( '' );
			fetchProductPreview( productId );
		} );

		$( document ).on( 'click', '.remove-selected-product', function() {
			$hiddenField.val( '' );
			$selectedContainer.html( '' );
			$previewContainer.html( '' );
		} );

		var savedProductId = String( $hiddenField.val() || '' ).trim();
		if ( savedProductId ) {
			displaySelectedProduct( savedProductId );
			fetchProductPreview( savedProductId );
		}

		search.start();

		return {
			refreshPreview: function() {
				var productId = String( $hiddenField.val() || '' ).trim();
				if ( productId ) {
					fetchProductPreview( productId );
				}
			},
		};
	};
} )( window, window.jQuery );
