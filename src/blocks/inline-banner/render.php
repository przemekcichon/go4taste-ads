<?php
/**
 * Server-side rendering for the go4taste-ads/inline-banner block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 *
 * @package Go4Taste_Ads
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$products_attr = isset( $attributes['products'] ) ? $attributes['products'] : array();

if ( empty( $products_attr ) ) {
	return '';
}

$options  = get_option( 'go4taste_ads_options' );
$p_value  = isset( $options['p_parameter_value'] ) ? $options['p_parameter_value'] : '';
$lang     = isset( $options['prestashop_lang'] ) ? $options['prestashop_lang'] : 'pl';
$api_type = isset( $options['api_type'] ) ? $options['api_type'] : 'prestashop_1_5';
$api_url  = isset( $options['api_url'] ) ? $options['api_url'] : '';

$plugin_instance = Go4Taste_Ads::get_instance();

$products_data = array();
foreach ( $products_attr as $product_entry ) {
	$product_id = isset( $product_entry['id'] ) ? sanitize_text_field( $product_entry['id'] ) : '';
	if ( empty( $product_id ) ) {
		continue;
	}
	$data = $plugin_instance->fetch_product_data( $product_id, $p_value, $lang, $api_type, $api_url );
	if ( $data ) {
		$products_data[] = $data;
	}
}

if ( empty( $products_data ) ) {
	return '';
}

$product_count  = count( $products_data );
$mobile_layout  = isset( $attributes['mobileLayout'] ) ? $attributes['mobileLayout'] : 'column';
$wrapper_attrs  = get_block_wrapper_attributes( array(
	'class' => 'g4t-ads-banner g4t-banner-inline g4t-banner-inline--count-' . $product_count . ' g4t-banner-inline--mobile-' . esc_attr( $mobile_layout ),
) );

$post_id = get_the_ID();

$products_style_parts = array();
if ( ! empty( $attributes['productsGap'] ) ) {
	$products_style_parts[] = 'gap:' . esc_attr( $attributes['productsGap'] );
}
if ( ! empty( $attributes['productsWidth'] ) ) {
	$products_style_parts[] = 'width:' . esc_attr( $attributes['productsWidth'] );
}
$products_style = ! empty( $products_style_parts ) ? ' style="' . implode( ';', $products_style_parts ) . '"' : '';

$shadow_map = array(
	'none'   => 'none',
	'small'  => '0 1px 3px rgba(0,0,0,0.12)',
	'medium' => '0 4px 12px rgba(0,0,0,0.15)',
	'large'  => '0 8px 24px rgba(0,0,0,0.2)',
);

$card_style_parts = array();
if ( ! empty( $attributes['cardBackground'] ) ) {
	$card_style_parts[] = 'background-color:' . esc_attr( $attributes['cardBackground'] );
}
if ( ! empty( $attributes['cardBorderColor'] ) ) {
	$card_style_parts[] = 'border-color:' . esc_attr( $attributes['cardBorderColor'] );
}
if ( ! empty( $attributes['cardBorderWidth'] ) ) {
	$card_style_parts[] = 'border-width:' . esc_attr( $attributes['cardBorderWidth'] );
}
if ( ! empty( $attributes['cardBorderStyle'] ) ) {
	$card_style_parts[] = 'border-style:' . esc_attr( $attributes['cardBorderStyle'] );
}
if ( ! empty( $attributes['cardBorderRadius'] ) ) {
	$card_style_parts[] = 'border-radius:' . esc_attr( $attributes['cardBorderRadius'] );
}
if ( ! empty( $attributes['cardBoxShadow'] ) && isset( $shadow_map[ $attributes['cardBoxShadow'] ] ) ) {
	$card_style_parts[] = 'box-shadow:' . $shadow_map[ $attributes['cardBoxShadow'] ];
}
$card_style = ! empty( $card_style_parts ) ? ' style="' . implode( ';', $card_style_parts ) . '"' : '';
?>
<div <?php echo $wrapper_attrs; ?>>
	<div class="g4t-ads-inline-products"<?php echo $products_style; ?>>
		<?php foreach ( $products_data as $product_data ) :
			// Format price
			$price           = htmlentities( $product_data->price, null, 'utf-8' );
			$formatted_price = str_replace( array( ',', '&nbsp;zł' ), array( '.', '' ), $price );
			$formatted_price = html_entity_decode( $formatted_price );

			// Format price_tax_exc
			$price_tax_exc           = htmlentities( $product_data->price_tax_exc, null, 'utf-8' );
			$formatted_price_tax_exc = str_replace( array( ',', '&nbsp;zł' ), array( '.', '' ), $price_tax_exc );
			$formatted_price_tax_exc = html_entity_decode( $formatted_price_tax_exc );

			// Format category
			$category             = esc_attr( $product_data->category );
			$category_with_spaces = str_replace( '-', ' ', $category );
			$formatted_category   = ucfirst( strtolower( $category_with_spaces ) );

			// Image dimensions with caching
			$img_url        = (string) $product_data->picture;
			$transient_key  = 'g4t_img_' . md5( $img_url );
			$img_dimensions = get_transient( $transient_key );
			if ( false === $img_dimensions ) {
				$img_size = @getimagesize( $img_url );
				$img_dimensions = $img_size
					? array( 'width' => $img_size[0], 'height' => $img_size[1] )
					: array( 'width' => 320, 'height' => 180 );
				set_transient( $transient_key, $img_dimensions, WEEK_IN_SECONDS );
			}
		?>
		<div class="g4t-ads-content"<?php echo $card_style; ?>>
			<div class="g4t-ads-image">
				<img
					src="<?php echo esc_url( $product_data->picture ); ?>"
					alt="<?php echo esc_attr( $product_data->name ); ?>"
					width="<?php echo esc_attr( $img_dimensions['width'] ); ?>"
					height="<?php echo esc_attr( $img_dimensions['height'] ); ?>"
				/>
			</div>
			<div class="g4t-ads-details">
				<div class="g4t-ads-name">
					<a href="<?php echo esc_url( $product_data->link ); ?>" class="g4t-ads-name-link">
						<?php echo esc_html( $product_data->name ); ?>
					</a>
				</div>
				<div class="g4t-ads-prices">
					<?php if ( $product_data->price != $product_data->price_without_reduction ) : ?>
						<span class="g4t-ads-old-price"><?php echo esc_html( $product_data->price_without_reduction ); ?></span>
					<?php endif; ?>
					<span class="g4t-ads-sale-price"><?php echo esc_html( $product_data->price ); ?></span>
				</div>
			</div>
			<a href="<?php echo esc_url( $product_data->link ); ?>"
			   class="g4t-ads-button"
			   data-object-type="promotion"
			   data-item-category2="<?php echo esc_attr( $formatted_category ); ?>"
			   data-reference="<?php echo esc_attr( $product_data->reference ); ?>"
			   data-price-tax-exc="<?php echo esc_attr( $formatted_price_tax_exc ); ?>"
			   data-price="<?php echo esc_attr( $formatted_price ); ?>"
			   data-item-name="<?php echo esc_attr( $product_data->name ); ?>"
			   data-item-id="<?php echo esc_attr( $product_data->id ); ?>"
			   data-creative-slot="inline-content"
			   data-creative-name="Product Inline Banner"
			   data-promotion-name="Go4Taste Product Ads"
			   data-promotion-id="g4t-ads-<?php echo esc_attr( $post_id ); ?>-<?php echo esc_attr( $product_data->id ); ?>"
			>Kup w sklepie</a>
		</div>
		<?php endforeach; ?>
	</div>
</div>
