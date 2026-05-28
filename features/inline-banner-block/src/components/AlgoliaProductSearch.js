import { useState, useCallback, useRef, useEffect } from '@wordpress/element';
import { TextControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import algoliasearch from 'algoliasearch/lite';

const { algoliaAppId, algoliaSearchKey, algoliaIndexName } =
	window.g4tAdsBlockData || {};

let searchIndex = null;
if ( algoliaAppId && algoliaSearchKey ) {
	const client = algoliasearch( algoliaAppId, algoliaSearchKey );
	searchIndex = client.initIndex(
		algoliaIndexName || 'go4taste_product_list'
	);
}

function debounce( fn, delay ) {
	let timer;
	return ( ...args ) => {
		clearTimeout( timer );
		timer = setTimeout( () => fn( ...args ), delay );
	};
}

export default function AlgoliaProductSearch( {
	onSelect,
	existingIds = [],
} ) {
	const [ query, setQuery ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ isSearching, setIsSearching ] = useState( false );
	const wrapperRef = useRef( null );

	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				wrapperRef.current &&
				! wrapperRef.current.contains( event.target )
			) {
				setResults( [] );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () =>
			document.removeEventListener( 'mousedown', handleClickOutside );
	}, [] );

	// eslint-disable-next-line react-hooks/exhaustive-deps
	const performSearch = useCallback(
		debounce( async ( q ) => {
			if ( ! q.trim() || ! searchIndex ) {
				setResults( [] );
				return;
			}
			setIsSearching( true );
			try {
				const { hits } = await searchIndex.search( q, {
					hitsPerPage: 10,
				} );
				const existingSet = new Set( existingIds.map( String ) );
				setResults(
					hits.filter(
						( h ) => ! existingSet.has( String( h.objectID ) )
					)
				);
			} catch ( err ) {
				// eslint-disable-next-line no-console
				console.error( 'Algolia search error:', err );
				setResults( [] );
			}
			setIsSearching( false );
		}, 300 ),
		[ existingIds ]
	);

	const handleChange = ( value ) => {
		setQuery( value );
		performSearch( value );
	};

	const handleSelect = ( hit ) => {
		onSelect( {
			id: String( hit.objectID ),
			name: hit.name || '',
			picture: hit.picture || '',
			price: hit.price || '',
			price_without_reduction: hit.price_without_reduction || '',
			category: hit.category || '',
		} );
		setQuery( '' );
		setResults( [] );
	};

	if ( ! searchIndex ) {
		return (
			<p className="g4t-algolia-error">
				{ __(
					'Algolia is not configured. Check plugin settings.',
					'go4taste-ads'
				) }
			</p>
		);
	}

	return (
		<div className="g4t-algolia-search" ref={ wrapperRef }>
			<TextControl
				label={ __( 'Search products', 'go4taste-ads' ) }
				value={ query }
				onChange={ handleChange }
				placeholder={ __(
					'Search by name or product ID...',
					'go4taste-ads'
				) }
			/>
			{ isSearching && <Spinner /> }
			{ results.length > 0 && (
				<ul className="g4t-algolia-results" role="listbox">
					{ results.map( ( hit ) => (
						<li
							key={ hit.objectID }
							onClick={ () => handleSelect( hit ) }
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' ) {
									handleSelect( hit );
								}
							} }
							role="option"
							tabIndex={ 0 }
						>
							{ hit.picture && (
								<img
									src={ hit.picture }
									alt=""
									className="g4t-algolia-result-thumb"
								/>
							) }
							<div className="g4t-algolia-result-info">
								<strong>{ hit.name }</strong>
								<span className="g4t-algolia-result-meta">
									ID: { hit.objectID }
									{ hit.category &&
										` | ${ hit.category }` }
								</span>
							</div>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}
