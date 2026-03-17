import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { ColorPalette, PanelBody, Placeholder, SelectControl, __experimentalUnitControl as UnitControl } from '@wordpress/components';
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

const BORDER_STYLE_OPTIONS = [
	{ value: '', label: __( 'Domyślny', 'go4taste-ads' ) },
	{ value: 'solid', label: __( 'Ciągły', 'go4taste-ads' ) },
	{ value: 'dashed', label: __( 'Kreskowany', 'go4taste-ads' ) },
	{ value: 'dotted', label: __( 'Kropkowany', 'go4taste-ads' ) },
	{ value: 'none', label: __( 'Brak', 'go4taste-ads' ) },
];

const SHADOW_OPTIONS = [
	{ value: '', label: __( 'Domyślny', 'go4taste-ads' ) },
	{ value: 'none', label: __( 'Brak', 'go4taste-ads' ) },
	{ value: 'small', label: __( 'Mały', 'go4taste-ads' ) },
	{ value: 'medium', label: __( 'Średni', 'go4taste-ads' ) },
	{ value: 'large', label: __( 'Duży', 'go4taste-ads' ) },
];

const SHADOW_VALUES = {
	none: 'none',
	small: '0 1px 3px rgba(0,0,0,0.12)',
	medium: '0 4px 12px rgba(0,0,0,0.15)',
	large: '0 8px 24px rgba(0,0,0,0.2)',
};

const FONT_WEIGHT_OPTIONS = [
	{ value: '', label: __( 'Domyślny', 'go4taste-ads' ) },
	{ value: '400', label: 'Normal (400)' },
	{ value: '500', label: 'Medium (500)' },
	{ value: '600', label: 'Semi Bold (600)' },
	{ value: '700', label: 'Bold (700)' },
	{ value: '800', label: 'Extra Bold (800)' },
];

const TEXT_TRANSFORM_OPTIONS = [
	{ value: '', label: __( 'Domyślny', 'go4taste-ads' ) },
	{ value: 'none', label: __( 'Brak', 'go4taste-ads' ) },
	{ value: 'uppercase', label: __( 'WIELKIE LITERY', 'go4taste-ads' ) },
	{ value: 'lowercase', label: __( 'małe litery', 'go4taste-ads' ) },
	{ value: 'capitalize', label: __( 'Każde Słowo', 'go4taste-ads' ) },
];

function buildCardStyle( attributes ) {
	const style = {};
	if ( attributes.cardBackground ) {
		style.backgroundColor = attributes.cardBackground;
	}
	if ( attributes.cardBorderColor ) {
		style.borderColor = attributes.cardBorderColor;
	}
	if ( attributes.cardBorderWidth ) {
		style.borderWidth = attributes.cardBorderWidth;
	}
	if ( attributes.cardBorderStyle ) {
		style.borderStyle = attributes.cardBorderStyle;
	}
	if ( attributes.cardBorderRadius ) {
		style.borderRadius = attributes.cardBorderRadius;
	}
	if ( attributes.cardBoxShadow && SHADOW_VALUES[ attributes.cardBoxShadow ] ) {
		style.boxShadow = SHADOW_VALUES[ attributes.cardBoxShadow ];
	}
	return style;
}

function buildButtonStyle( attributes ) {
	const style = {};
	if ( attributes.btnBackground ) {
		style.backgroundColor = attributes.btnBackground;
	}
	if ( attributes.btnColor ) {
		style.color = attributes.btnColor;
	}
	if ( attributes.btnFontSize ) {
		style.fontSize = attributes.btnFontSize;
	}
	if ( attributes.btnFontWeight ) {
		style.fontWeight = attributes.btnFontWeight;
	}
	if ( attributes.btnTextTransform ) {
		style.textTransform = attributes.btnTextTransform;
	}
	if ( attributes.btnLetterSpacing ) {
		style.letterSpacing = attributes.btnLetterSpacing;
	}
	if ( attributes.btnPadding ) {
		style.padding = attributes.btnPadding;
	}
	if ( attributes.btnBorderColor ) {
		style.borderColor = attributes.btnBorderColor;
	}
	if ( attributes.btnBorderWidth ) {
		style.borderWidth = attributes.btnBorderWidth;
	}
	if ( attributes.btnBorderStyle ) {
		style.borderStyle = attributes.btnBorderStyle;
	}
	if ( attributes.btnBorderRadius ) {
		style.borderRadius = attributes.btnBorderRadius;
	}
	return style;
}

export default function Edit( { attributes, setAttributes } ) {
	const { products, mobileLayout, productsGap, productsWidth } = attributes;
	const cardStyle = buildCardStyle( attributes );
	const buttonStyle = buildButtonStyle( attributes );

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
				<PanelBody
					title={ __( 'Układ produktów', 'go4taste-ads' ) }
				>
					<UnitControl
						label={ __( 'Odstęp (gap)', 'go4taste-ads' ) }
						value={ productsGap }
						onChange={ ( value ) =>
							setAttributes( { productsGap: value } )
						}
						help={ __(
							'Odstęp między kartami produktów.',
							'go4taste-ads'
						) }
					/>
					<UnitControl
						label={ __( 'Szerokość', 'go4taste-ads' ) }
						value={ productsWidth }
						onChange={ ( value ) =>
							setAttributes( { productsWidth: value } )
						}
						help={ __(
							'Szerokość kontenera produktów.',
							'go4taste-ads'
						) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Styl karty produktu', 'go4taste-ads' ) }
					initialOpen={ false }
				>
					<p className="components-base-control__label">
						{ __( 'Kolor tła', 'go4taste-ads' ) }
					</p>
					<ColorPalette
						value={ attributes.cardBackground }
						onChange={ ( value ) =>
							setAttributes( { cardBackground: value || '' } )
						}
					/>
					<p className="components-base-control__label">
						{ __( 'Kolor obramowania', 'go4taste-ads' ) }
					</p>
					<ColorPalette
						value={ attributes.cardBorderColor }
						onChange={ ( value ) =>
							setAttributes( { cardBorderColor: value || '' } )
						}
					/>
					<UnitControl
						label={ __( 'Grubość obramowania', 'go4taste-ads' ) }
						value={ attributes.cardBorderWidth }
						onChange={ ( value ) =>
							setAttributes( { cardBorderWidth: value } )
						}
					/>
					<SelectControl
						label={ __( 'Styl obramowania', 'go4taste-ads' ) }
						value={ attributes.cardBorderStyle }
						options={ BORDER_STYLE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { cardBorderStyle: value } )
						}
					/>
					<UnitControl
						label={ __( 'Zaokrąglenie rogów', 'go4taste-ads' ) }
						value={ attributes.cardBorderRadius }
						onChange={ ( value ) =>
							setAttributes( { cardBorderRadius: value } )
						}
					/>
					<SelectControl
						label={ __( 'Cień', 'go4taste-ads' ) }
						value={ attributes.cardBoxShadow }
						options={ SHADOW_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { cardBoxShadow: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Styl przycisku', 'go4taste-ads' ) }
					initialOpen={ false }
				>
					<p className="components-base-control__label">
						{ __( 'Kolor tła', 'go4taste-ads' ) }
					</p>
					<ColorPalette
						value={ attributes.btnBackground }
						onChange={ ( value ) =>
							setAttributes( { btnBackground: value || '' } )
						}
					/>
					<p className="components-base-control__label">
						{ __( 'Kolor tekstu', 'go4taste-ads' ) }
					</p>
					<ColorPalette
						value={ attributes.btnColor }
						onChange={ ( value ) =>
							setAttributes( { btnColor: value || '' } )
						}
					/>
					<UnitControl
						label={ __( 'Rozmiar czcionki', 'go4taste-ads' ) }
						value={ attributes.btnFontSize }
						onChange={ ( value ) =>
							setAttributes( { btnFontSize: value } )
						}
					/>
					<SelectControl
						label={ __( 'Grubość czcionki', 'go4taste-ads' ) }
						value={ attributes.btnFontWeight }
						options={ FONT_WEIGHT_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { btnFontWeight: value } )
						}
					/>
					<SelectControl
						label={ __( 'Wielkość liter', 'go4taste-ads' ) }
						value={ attributes.btnTextTransform }
						options={ TEXT_TRANSFORM_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { btnTextTransform: value } )
						}
					/>
					<UnitControl
						label={ __( 'Odstęp między literami', 'go4taste-ads' ) }
						value={ attributes.btnLetterSpacing }
						onChange={ ( value ) =>
							setAttributes( { btnLetterSpacing: value } )
						}
					/>
					<UnitControl
						label={ __( 'Padding', 'go4taste-ads' ) }
						value={ attributes.btnPadding }
						onChange={ ( value ) =>
							setAttributes( { btnPadding: value } )
						}
					/>
					<p className="components-base-control__label">
						{ __( 'Kolor obramowania', 'go4taste-ads' ) }
					</p>
					<ColorPalette
						value={ attributes.btnBorderColor }
						onChange={ ( value ) =>
							setAttributes( { btnBorderColor: value || '' } )
						}
					/>
					<UnitControl
						label={ __( 'Grubość obramowania', 'go4taste-ads' ) }
						value={ attributes.btnBorderWidth }
						onChange={ ( value ) =>
							setAttributes( { btnBorderWidth: value } )
						}
					/>
					<SelectControl
						label={ __( 'Styl obramowania', 'go4taste-ads' ) }
						value={ attributes.btnBorderStyle }
						options={ BORDER_STYLE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { btnBorderStyle: value } )
						}
					/>
					<UnitControl
						label={ __( 'Zaokrąglenie rogów', 'go4taste-ads' ) }
						value={ attributes.btnBorderRadius }
						onChange={ ( value ) =>
							setAttributes( { btnBorderRadius: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div
					className={ `g4t-ads-inline-products g4t-banner-inline--count-${ products.length }` }
					style={ {
						...( productsGap ? { gap: productsGap } : {} ),
						...( productsWidth ? { width: productsWidth } : {} ),
					} }
				>
					{ products.map( ( product, index ) => (
						<ProductPreview
							key={ product.id }
							product={ product }
							onRemove={ () => removeProduct( index ) }
							cardStyle={ cardStyle }
							buttonStyle={ buttonStyle }
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
