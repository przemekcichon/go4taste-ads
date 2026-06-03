/**
 * G4TAdsProvider — shared Algolia data layer for all picker surfaces.
 *
 * Singleton. Initialized once by the ads plugin from PHP-localized credentials.
 * All surfaces (metabox, term edit, global settings, recipe creator) call
 * G4TAdsProvider.getInstance() to get search/fetch access without owning client logic.
 *
 * Public API:
 *   G4TAdsProvider.init({ appId, apiKey, indexName })
 *   G4TAdsProvider.getInstance()
 *     .search(query, { hitsPerPage })  → Promise<NormalizedProduct[]>
 *     .getById(id)                     → Promise<NormalizedProduct>
 *     .getByIds(ids[])                 → Promise<NormalizedProduct[]>
 *
 * NormalizedProduct shape: { id, name, picture, link, price, category }
 */
window.G4TAdsProvider = ( function () {
	'use strict';

	var instance = null;

	function normalize( hit ) {
		return {
			id:       String( hit.objectID || hit.id || '' ),
			name:     hit.name     || hit.title    || '',
			picture:  hit.picture  || hit.image    || '',
			link:     hit.link     || hit.url      || '',
			price:    hit.price    != null ? hit.price : null,
			category: hit.category || '',
		};
	}

	function create( config ) {
		var client = window.algoliasearch( config.appId, config.apiKey );
		var index  = client.initIndex( config.indexName || 'go4taste_product_list' );

		return {
			search: function ( query, opts ) {
				var hitsPerPage = ( opts && opts.hitsPerPage ) ? Number( opts.hitsPerPage ) : 12;
				return index
					.search( String( query || '' ), { hitsPerPage: hitsPerPage } )
					.then( function ( r ) { return r.hits.map( normalize ); } );
			},
			getById: function ( id ) {
				return index.getObject( String( id ) ).then( normalize );
			},
			getByIds: function ( ids ) {
				if ( ! Array.isArray( ids ) || ids.length === 0 ) {
					return Promise.resolve( [] );
				}
				return Promise.all(
					ids.map( function ( id ) {
						return index.getObject( String( id ) ).then( normalize );
					} )
				);
			},
		};
	}

	var provider = {
		init: function ( config ) {
			if ( instance ) {
				return;
			}
			if ( ! config || ! config.appId || ! config.apiKey ) {
				return;
			}
			if ( typeof window.algoliasearch !== 'function' ) {
				return;
			}
			instance = create( config );
		},
		getInstance: function () {
			return instance;
		},
	};

	// Auto-init from PHP-localized data when algoliasearch is already loaded.
	document.addEventListener( 'DOMContentLoaded', function () {
		var data = window.G4T_ADS_PROVIDER_DATA;
		if ( data && data.appId && data.apiKey ) {
			provider.init( data );
		}
	} );

	return provider;
} )();
