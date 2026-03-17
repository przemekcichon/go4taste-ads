import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ProductPreview( { product, onRemove } ) {
	return (
		<div className="g4t-ads-content g4t-ads-content--editor">
			<Button
				className="g4t-product-remove"
				icon="no-alt"
				label={ __( 'Remove product', 'go4taste-ads' ) }
				onClick={ onRemove }
				isDestructive
				size="small"
			/>
			<div className="g4t-ads-image">
				{ product.picture ? (
					<img src={ product.picture } alt={ product.name } />
				) : (
					<div className="g4t-ads-image-placeholder">
						{ __( 'No image', 'go4taste-ads' ) }
					</div>
				) }
			</div>
			<div className="g4t-ads-details">
				<div className="g4t-ads-name">
					<span className="g4t-ads-name-link">
						{ product.name }
					</span>
				</div>
				<div className="g4t-ads-prices">
					{ product.price !== product.price_without_reduction &&
						product.price_without_reduction && (
							<span className="g4t-ads-old-price">
								{ product.price_without_reduction }
							</span>
						) }
					<span className="g4t-ads-sale-price">
						{ product.price ||
							__( 'Price N/A', 'go4taste-ads' ) }
					</span>
				</div>
			</div>
			<div className="g4t-ads-button g4t-ads-button--preview">
				Kup w sklepie
			</div>
		</div>
	);
}
