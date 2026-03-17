import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Placeholder, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import AlgoliaProductSearch from './components/AlgoliaProductSearch';
import ProductPreview from './components/ProductPreview';

const MAX_PRODUCTS = 3;

const MOBILE_LAYOUT_OPTIONS = [
	{
		value: 'column',
		label: __( 'Kolumna (jeden pod drugim)', 'go4taste-ads' ),
	},
	{
		value: 'carousel',
		label: __( 'Karuzela (przesuwana)', 'go4taste-ads' ),
	},
	{
		value: 'single',
		label: __( 'Tylko pierwszy produkt', 'go4taste-ads' ),
	},
];

export default function Edit( { attributes, setAttributes } ) {
	const { products, mobileLayout } = attributes;

	const blockProps = useBlockProps( {
		className: `g4t-ads-banner g4t-banner-inline g4t-banner-inline--mobile-${ mobileLayout }`,
	} );

	const addProduct = ( product ) => {
		if ( products.length >= MAX_PRODUCTS ) {
			return;
		}
		if ( products.some( ( p ) => p.id === product.id ) ) {
			return;
		}
		setAttributes( { products: [ ...products, product ] } );
	};

	const removeProduct = ( index ) => {
		setAttributes( {
			products: products.filter( ( _, i ) => i !== index ),
		} );
	};

	const hasProducts = products.length > 0;
	const canAddMore = products.length < MAX_PRODUCTS;

	if ( ! hasProducts ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="megaphone"
					label={ __( 'Go4Taste Inline Banner', 'go4taste-ads' ) }
					instructions={ __(
						'Search and select 1-3 products to display inline.',
						'go4taste-ads'
					) }
				>
					<AlgoliaProductSearch
						onSelect={ addProduct }
						existingIds={ [] }
					/>
				</Placeholder>
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Ustawienia mobilne', 'go4taste-ads' ) }
				>
					<SelectControl
						label={ __( 'Układ na komórce', 'go4taste-ads' ) }
						value={ mobileLayout }
						options={ MOBILE_LAYOUT_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { mobileLayout: value } )
						}
						help={ __(
							'Sposób wyświetlania produktów na urządzeniach mobilnych.',
							'go4taste-ads'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div
					className={ `g4t-ads-inline-products g4t-banner-inline--count-${ products.length }` }
				>
					{ products.map( ( product, index ) => (
						<ProductPreview
							key={ product.id }
							product={ product }
							onRemove={ () => removeProduct( index ) }
						/>
					) ) }
				</div>
				{ canAddMore && (
					<div className="g4t-inline-banner-add-product">
						<AlgoliaProductSearch
							onSelect={ addProduct }
							existingIds={ products.map( ( p ) => p.id ) }
						/>
					</div>
				) }
			</div>
		</>
	);
}
