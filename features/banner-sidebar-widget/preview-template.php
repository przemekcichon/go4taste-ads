<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var object $product_data Product object injected by metabox preview runtime.
 * @var int    $post_id      Post ID from edit form context.
 */

$img_url = isset( $product_data->picture ) ? (string) $product_data->picture : '';

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
<div class="g4t-ads-banner g4t-banner-metabox-preview">
	<div class="g4t-ads-content">
		<div class="g4t-ads-image">
			<img src="<?php echo esc_url( $product_data->picture ); ?>" alt="<?php echo esc_attr( $product_data->name ); ?>" width="<?php echo esc_attr( $img_dimensions['width'] ); ?>" height="<?php echo esc_attr( $img_dimensions['height'] ); ?>" />
		</div>
		<div class="g4t-ads-details">
			<div class="g4t-ads-name">
				<a href="<?php echo esc_url( $product_data->link ); ?>" class="g4t-ads-name-link"><?php echo esc_html( $product_data->name ); ?></a>
			</div>
			<div class="g4t-ads-prices">
				<?php if ( $product_data->price != $product_data->price_without_reduction ) : ?>
					<span class="g4t-ads-old-price"><?php echo esc_html( $product_data->price_without_reduction ); ?></span>
					<span class="g4t-ads-sale-price"><?php echo esc_html( $product_data->price ); ?></span>
				<?php else : ?>
					<span class="g4t-ads-sale-price"><?php echo esc_html( $product_data->price ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<span class="g4t-ads-button lg" aria-hidden="true">Kup w sklepie</span>
		<span class="g4t-ads-button md" aria-hidden="true">Kup</span>
	</div>
</div>
