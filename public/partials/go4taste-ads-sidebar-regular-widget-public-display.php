<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://go4taste.pl/techlab
 * @since      1.0.0
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/public/partials
 */
global $post;

/**
 * Format price paramater
 */
// Original price format
$price = esc_attr( $product_data->price );

// Remove the currency symbol and any whitespace
$price_without_currency = preg_replace('/\s*zł$/', '', $price);

// Replace the comma with a dot
$formatted_price = str_replace(',', '.', $price_without_currency);

/**
 * Format price_tax_exc paramater
 */
// Original price_tax_exc format
$price_tax_exc = esc_attr( $product_data->price_tax_exc );

// Remove the currency symbol and any whitespace
$price_tax_exc_without_currency = preg_replace('/\s*zł$/', '', $price_tax_exc);

// Replace the comma with a dot
$formatted_price_tax_exc = str_replace(',', '.', $price_tax_exc_without_currency);

/**
 * Format item_category2 parameter
 */
// Original category format
$category = esc_attr( $product_data->category );

// Replace hyphens with spaces
$category_with_spaces = str_replace('-', ' ', $category);

// Capitalize the first letter of each word
$formatted_category = ucwords($category_with_spaces);

?>
<div class="g4t-ads-banner g4t-banner-sidebar-regular">
    <div class="g4t-ads-content">
        <div class="g4t-ads-image">
            <img src="<?php echo esc_url( $product_data->picture ); ?>" alt="<?php echo esc_attr( $product_data->name ); ?>">
        </div>
        <div class="g4t-ads-details">
            <div class="g4t-ads-name"><?php echo esc_html( $product_data->name ); ?></div>
            <div class="g4t-ads-prices">
                <?php if ( $product_data->price != $product_data->price_without_reduction ) : ?>
                    <span class="g4t-ads-old-price"><?php echo esc_html( $product_data->price ); ?></span>
                    <span class="g4t-ads-sale-price"><?php echo esc_html( $product_data->price_without_reduction ); ?></span>
                <?php else : ?>
                    <span class="g4t-ads-sale-price"><?php echo esc_html( $product_data->price ); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?php echo esc_url( $product_data->link ); ?>" data-object-type="promotion" data-reference="<?php echo esc_attr( $product_data->reference )?>" data-price-tax-exc="<?php echo esc_attr( $formatted_price_tax_exc )?>" data-price="<?php echo esc_attr( $formatted_price )?>" data-item-name="<?php echo esc_attr( $product_data->name )?>" data-item-id="<?php echo esc_attr( $product_data->id )?>" data-creative-slot="sidebar-primary" data-creative-name="Product Widget Image" data-promotion-name="Go4Taste Product Ads" data-promotion-id="g4t-ads-<?php echo $post->ID?>-<?php echo esc_attr( $product_data->id ); ?>" class="g4t-ads-button lg">Kup w sklepie</a>
        <a href="<?php echo esc_url( $product_data->link ); ?>" data-object-type="promotion" data-reference="<?php echo esc_attr( $product_data->reference )?>" data-price-tax-exc="<?php echo esc_attr( $formatted_price_tax_exc )?>" data-price="<?php echo esc_attr( $formatted_price )?>" data-item-name="<?php echo esc_attr( $product_data->name )?>" data-item-id="<?php echo esc_attr( $product_data->id )?>" data-creative-slot="sidebar-primary" data-creative-name="Product Widget Image" data-promotion-name="Go4Taste Product Ads" data-promotion-id="g4t-ads-<?php echo $post->ID?>-<?php echo esc_attr( $product_data->id ); ?>" class="g4t-ads-button md">Kup</a>
    </div>
</div>

