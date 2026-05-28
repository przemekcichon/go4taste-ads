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

$options = get_option( 'go4taste_ads_options' );

$products_data = array();
foreach ( $products_attr as $product_entry ) {
	$product_id = isset( $product_entry['id'] ) ? sanitize_text_field( $product_entry['id'] ) : '';
	if ( empty( $product_id ) ) {
		continue;
	}
	$data = g4t_core_fetch_product_data( $product_id, $options );
	if ( $data ) {
		$products_data[] = $data;
	}
}

if ( empty( $products_data ) ) {
	return '';
}

$product_count  = count( $products_data );
$mobile_layout  = isset( $attributes['mobileLayout'] ) ? $attributes['mobileLayout'] : 'carousel';
$wrapper_attrs  = get_block_wrapper_attributes( array(
	'class' => 'g4t-ads-banner g4t-banner-inline g4t-banner-inline--count-' . $product_count . ' g4t-banner-inline--mobile-' . esc_attr( $mobile_layout ),
) );

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

$btn_style_parts = array();
if ( ! empty( $attributes['btnBackground'] ) ) {
	$btn_style_parts[] = 'background-color:' . esc_attr( $attributes['btnBackground'] );
}
if ( ! empty( $attributes['btnColor'] ) ) {
	$btn_style_parts[] = 'color:' . esc_attr( $attributes['btnColor'] );
}
if ( ! empty( $attributes['btnFontSize'] ) ) {
	$btn_style_parts[] = 'font-size:' . esc_attr( $attributes['btnFontSize'] );
}
if ( ! empty( $attributes['btnFontWeight'] ) ) {
	$btn_style_parts[] = 'font-weight:' . esc_attr( $attributes['btnFontWeight'] );
}
if ( ! empty( $attributes['btnTextTransform'] ) ) {
	$btn_style_parts[] = 'text-transform:' . esc_attr( $attributes['btnTextTransform'] );
}
if ( ! empty( $attributes['btnLetterSpacing'] ) ) {
	$btn_style_parts[] = 'letter-spacing:' . esc_attr( $attributes['btnLetterSpacing'] );
}
if ( ! empty( $attributes['btnPadding'] ) ) {
	$btn_style_parts[] = 'padding:' . esc_attr( $attributes['btnPadding'] );
}
if ( ! empty( $attributes['btnBorderColor'] ) ) {
	$btn_style_parts[] = 'border-color:' . esc_attr( $attributes['btnBorderColor'] );
}
if ( ! empty( $attributes['btnBorderWidth'] ) ) {
	$btn_style_parts[] = 'border-width:' . esc_attr( $attributes['btnBorderWidth'] );
}
if ( ! empty( $attributes['btnBorderStyle'] ) ) {
	$btn_style_parts[] = 'border-style:' . esc_attr( $attributes['btnBorderStyle'] );
}
if ( ! empty( $attributes['btnBorderRadius'] ) ) {
	$btn_style_parts[] = 'border-radius:' . esc_attr( $attributes['btnBorderRadius'] );
}
if ( ! empty( $attributes['btnHoverBackground'] ) ) {
	$btn_style_parts[] = '--btn-hover-bg:' . esc_attr( $attributes['btnHoverBackground'] );
}
if ( ! empty( $attributes['btnHoverColor'] ) ) {
	$btn_style_parts[] = '--btn-hover-color:' . esc_attr( $attributes['btnHoverColor'] );
}
if ( ! empty( $attributes['btnHoverBorderColor'] ) ) {
	$btn_style_parts[] = '--btn-hover-border-color:' . esc_attr( $attributes['btnHoverBorderColor'] );
}
$btn_style = ! empty( $btn_style_parts ) ? ' style="' . implode( ';', $btn_style_parts ) . '"' : '';
$ga4_items = array();
?>
<div <?php echo $wrapper_attrs; ?>>
	<div class="g4t-ads-inline-products"<?php echo $products_style; ?>>
		<?php foreach ( $products_data as $product_data ) :
			// Format price
			$price           = htmlentities( $product_data->price, ENT_QUOTES, 'utf-8' );
			$formatted_price = str_replace( array( ',', '&nbsp;zł' ), array( '.', '' ), $price );
			$formatted_price = html_entity_decode( $formatted_price );

			// Format price_tax_exc
			$price_tax_exc           = htmlentities( $product_data->price_tax_exc, ENT_QUOTES, 'utf-8' );
			$formatted_price_tax_exc = str_replace( array( ',', '&nbsp;zł' ), array( '.', '' ), $price_tax_exc );
			$formatted_price_tax_exc = html_entity_decode( $formatted_price_tax_exc );

			$category_raw         = isset( $product_data->category ) ? (string) $product_data->category : '';
			$category_for_ga4     = ucfirst( strtolower( str_replace( '-', ' ', $category_raw ) ) );

			// Avoid runtime remote requests for image metadata during page render.
			$img_width  = isset( $product_data->image_width ) ? absint( $product_data->image_width ) : 0;
			$img_height = isset( $product_data->image_height ) ? absint( $product_data->image_height ) : 0;
			if ( $img_width < 1 ) {
				$img_width = 320;
			}
			if ( $img_height < 1 ) {
				$img_height = 180;
			}

			$ga4_items[] = array(
				'item_id'       => isset( $product_data->id ) ? (string) $product_data->id : '',
				'item_name'     => isset( $product_data->name ) ? (string) $product_data->name : '',
				'item_category' => $category_for_ga4,
				'price'         => is_numeric( $formatted_price ) ? (float) $formatted_price : null,
				'creative_slot' => 'inline-content',
				'creative_name' => 'Product Inline Banner',
			);
		?>
		<div class="g4t-ads-content"<?php echo $card_style; ?>>
			<div class="g4t-ads-image">
				<img
					src="<?php echo esc_url( $product_data->picture ); ?>"
					alt="<?php echo esc_attr( $product_data->name ); ?>"
					width="<?php echo esc_attr( $img_width ); ?>"
					height="<?php echo esc_attr( $img_height ); ?>"
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
			   class="g4t-ads-button"<?php echo $btn_style; ?>
			>Kup w sklepie</a>
		</div>
		<?php endforeach; ?>
	</div>
	<?php if ( 'carousel' === $mobile_layout && $product_count > 1 ) : ?>
	<div class="g4t-carousel-dots" aria-label="Nawigacja karuzeli">
		<?php for ( $i = 0; $i < $product_count; $i++ ) : ?>
		<button class="g4t-carousel-dot<?php echo 0 === $i ? ' g4t-carousel-dot--active' : ''; ?>" data-index="<?php echo $i; ?>" type="button" aria-label="Produkt <?php echo $i + 1; ?>"></button>
		<?php endfor; ?>
	</div>
	<?php endif; ?>
</div>
<?php g4t_ga4_output_view_promotion_script( $ga4_items ); ?>
