<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var object $product_data Product object injected by widget runtime.
 */

global $post;

// Original price format.
$price = htmlentities( $product_data->price, ENT_QUOTES, 'utf-8' );

// Replace comma with dot and remove currency suffixes.
$formatted_price = str_replace( array( ',', '&nbsp;zł', ' zł', 'zł' ), array( '.', '', '', '' ), $price );
$formatted_price = html_entity_decode( $formatted_price );

// Original price_tax_exc format.
$price_tax_exc = htmlentities( $product_data->price_tax_exc, ENT_QUOTES, 'utf-8' );

// Replace comma with dot and remove currency suffixes.
$formatted_price_tax_exc = str_replace( array( ',', '&nbsp;zł', ' zł', 'zł' ), array( '.', '', '', '' ), $price_tax_exc );
$formatted_price_tax_exc = html_entity_decode( $formatted_price_tax_exc );

// Format category for data attributes.
$category             = esc_attr( $product_data->category );
$category_with_spaces = str_replace( '-', ' ', $category );
$formatted_category   = ucfirst( strtolower( $category_with_spaces ) );

$img_url = isset( $product_data->picture ) ? (string) $product_data->picture : '';
$display_price = isset( $product_data->price ) ? (string) $product_data->price : '';
$display_price_without_reduction = isset( $product_data->price_without_reduction ) ? trim( (string) $product_data->price_without_reduction ) : '';

if ( '' === $display_price_without_reduction ) {
	$display_price_without_reduction = $display_price;
}

if ( '' === trim( $img_url ) ) {
	$img_dimensions = array(
		'width'  => 320,
		'height' => 180,
	);
} else {
	$transient_key  = 'g4t_img_' . md5( $img_url );
	$img_dimensions = get_transient( $transient_key );

	if ( false === $img_dimensions ) {
		$img_size = @getimagesize( $img_url );
		if ( $img_size ) {
			$img_dimensions = array(
				'width'  => $img_size[0],
				'height' => $img_size[1],
			);
		} else {
			$img_dimensions = array(
				'width'  => 320,
				'height' => 180,
			);
		}
		set_transient( $transient_key, $img_dimensions, WEEK_IN_SECONDS );
	}
}
?>
<div class="g4t-ads-banner g4t-banner-sidebar-regular">
	<div class="g4t-ads-content">
		<div class="g4t-ads-image">
			<img src="<?php echo esc_url( $product_data->picture ); ?>" alt="<?php echo esc_attr( $product_data->name ); ?>" width="<?php echo esc_attr( $img_dimensions['width'] ); ?>" height="<?php echo esc_attr( $img_dimensions['height'] ); ?>" />
		</div>
		<div class="g4t-ads-details">
			<div class="g4t-ads-name">
				<a href="<?php echo esc_url( $product_data->link ); ?>" class="g4t-ads-name-link"><?php echo esc_html( $product_data->name ); ?></a>
			</div>
			<div class="g4t-ads-prices">
				<?php if ( $display_price != $display_price_without_reduction ) : ?>
					<span class="g4t-ads-old-price"><?php echo esc_html( $display_price_without_reduction ); ?></span>
					<span class="g4t-ads-sale-price"><?php echo esc_html( $display_price ); ?></span>
				<?php else : ?>
					<span class="g4t-ads-sale-price"><?php echo esc_html( $display_price ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<a href="<?php echo esc_url( $product_data->link ); ?>" data-object-type="promotion" data-item-category2="<?php echo esc_attr( $formatted_category ); ?>" data-reference="<?php echo esc_attr( $product_data->reference ); ?>" data-price-tax-exc="<?php echo esc_attr( $formatted_price_tax_exc ); ?>" data-price="<?php echo esc_attr( $formatted_price ); ?>" data-item-name="<?php echo esc_attr( $product_data->name ); ?>" data-item-id="<?php echo esc_attr( $product_data->id ); ?>" data-creative-slot="sidebar-primary" data-creative-name="Product Widget Image" data-promotion-name="Go4Taste Product Ads" data-promotion-id="g4t-ads-<?php echo $post->ID; ?>-<?php echo esc_attr( $product_data->id ); ?>" class="g4t-ads-button lg">Kup w sklepie</a>
		<a href="<?php echo esc_url( $product_data->link ); ?>" data-object-type="promotion" data-item-category2="<?php echo esc_attr( $formatted_category ); ?>" data-reference="<?php echo esc_attr( $product_data->reference ); ?>" data-price-tax-exc="<?php echo esc_attr( $formatted_price_tax_exc ); ?>" data-price="<?php echo esc_attr( $formatted_price ); ?>" data-item-name="<?php echo esc_attr( $product_data->name ); ?>" data-item-id="<?php echo esc_attr( $product_data->id ); ?>" data-creative-slot="sidebar-primary" data-creative-name="Product Widget Image" data-promotion-name="Go4Taste Product Ads" data-promotion-id="g4t-ads-<?php echo $post->ID; ?>-<?php echo esc_attr( $product_data->id ); ?>" class="g4t-ads-button md">Kup</a>
	</div>
</div>
