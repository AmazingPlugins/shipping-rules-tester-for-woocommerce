<?php
/** Catalog search and REST permission regressions, disposable sandbox only. */
defined( 'ABSPATH' ) || exit;
$ids = array();
$parent = new WC_Product_Variable();
try {
	$parent->set_name( 'SRT catalog parent' );
	$parent->save();
	$variation = new WC_Product_Variation();
	$variation->set_parent_id( $parent->get_id() );
	$variation->set_regular_price( '12' );
	$variation->set_sku( 'SRT-VARIATION-UNIQUE' );
	$variation->set_status( 'publish' );
	$ids[] = $variation->save();
	for ( $i = 0; $i < 22; $i++ ) {
		$product = new WC_Product_Simple();
		$product->set_name( sprintf( 'SRT catalog %02d', $i ) );
		$product->set_regular_price( '10' );
		$product->set_sku( 'SRT-CATALOG-' . $i );
		$ids[] = $product->save();
	}
	$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
	wp_set_current_user( $admin[0] );
	$_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce( 'wp_rest' );
	$suggestions = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/srt/v1/products' ) );
	if ( 200 !== $suggestions->get_status() || 10 !== count( $suggestions->get_data()['products'] ) ) {
		throw new RuntimeException( 'Initial catalog suggestions did not return ten products.' );
	}
	foreach ( array( 'SRT-VARIATION-UNIQUE' => $ids[0], 'SRT-CATALOG-21' => end( $ids ) ) as $search => $expected ) {
		$request = new WP_REST_Request( 'GET', '/srt/v1/products' );
		$request->set_param( 'search', $search );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();
		if ( 200 !== $response->get_status() || ! in_array( $expected, array_column( $data['products'], 'id' ), true ) ) {
			throw new RuntimeException( 'Catalog search missed a product or variation: ' . $search );
		}
	}
	$_SERVER['HTTP_X_WP_NONCE'] = 'invalid';
	if ( 403 !== rest_get_server()->dispatch( $request )->get_status() ) {
		throw new RuntimeException( 'Catalog search accepted an invalid nonce.' );
	}
	wp_set_current_user( 0 );
	if ( 403 !== rest_get_server()->dispatch( $request )->get_status() ) {
		throw new RuntimeException( 'Catalog search allowed anonymous access.' );
	}
} finally {
	wp_set_current_user( 0 );
	unset( $_SERVER['HTTP_X_WP_NONCE'] );
	foreach ( $ids as $id ) { wc_get_product( $id )->delete( true ); }
	$parent->delete( true );
}
echo "Catalog search and permissions passed.\n";
