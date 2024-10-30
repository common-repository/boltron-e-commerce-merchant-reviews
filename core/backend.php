<?php

namespace Boltron;

ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

use WP_Error;

/**
 * Boltron Backend
 *
 * @package Boltron
 */
class Backend
{
    /**
    * The Constructor
    */
    public function __construct()
    {
        if ( ! is_admin() ) return;

        add_action( 'admin_head', [ $this, 'admin_head' ], 9999 );
        add_action( 'admin_notices', [ $this, 'display_notices' ], 9999 );
        add_filter( 'plugin_action_links_' . BTRN()->basename, [ $this, 'plugin_links' ] );

        if ( BTRN()->inactive ) return;

        add_action( 'admin_menu', [ $this, 'admin_menu' ], 9 );
        add_action( 'admin_enqueue_scripts',  [ $this, 'admin_scripts' ], 10 );
        add_action( 'enqueue_block_editor_assets',  [ $this, 'enqueue_block' ], 10 );
        add_action( 'boltron_single_event',  [ $this, 'single_event' ], 9999 );
        add_action( 'wp_insert_post', [ $this, 'save_order' ], 9999 );
        add_action( 'wp_insert_post', [ $this, 'save_product' ], 9999 );
        add_action( 'before_delete_post', [ $this, 'delete_order' ], 9999 );
        add_action( 'before_delete_post', [ $this, 'delete_product' ], 9999 );
        add_action( 'comment_post', [ $this, 'save_review' ], 9999 );
        add_action( 'edit_comment', [ $this, 'save_review' ], 9999 );
        add_action( 'comment_approved_review', [ $this, 'save_review' ], 9999 );
        add_action( 'comment_unapproved_review', [ $this, 'delete_review' ], 9999 );
        add_action( 'delete_comment', [ $this, 'delete_review' ], 9999 );
        add_action( 'woocommerce_order_status_changed', [ $this, 'order_status_history' ], 9998, 4 );
        add_action( 'woocommerce_product_options_stock_status', [ $this, 'product_panels' ], 9999 );
        add_action( 'product_cat_add_form_fields', [ $this, 'extra_category_fields' ], 1 );
        add_action( 'product_cat_edit_form_fields', [ $this, 'extra_category_fields' ], 1 );
        add_action( 'create_product_cat', [ $this, 'save_extra_category_fields' ], 9999 );
        add_action( 'edited_product_cat', [ $this, 'save_extra_category_fields' ], 9999 );

        add_filter( 'mce_buttons',  function( $buttons ) {
            array_push( $buttons, 'separator', BTRN()->slug );
            return $buttons;
        }, 9999 );

        add_filter( 'mce_external_plugins',  function( $plugins ) {
            $plugins[ BTRN()->slug ] = BTRN()->uri . 'assets/js/editor-classic.js';
            return $plugins;
        }, 9999 );
    }

    /**
    * Display registered notices
    */
    public function display_notices()
    {
        echo BTRN()::$_notice;
    }

    /**
    * Admin Head content
    */
    public function admin_head()
    {
        ?>
        <style>
        .boltron-update-nag {
            display: block !important;
            padding: 1px 12px !important;
            border-left-color: #0288d1;
        }

        .form-table > tbody > tr > th {
            width: 200px !important;
        }

        .wp-core-ui .button,
        .wp-core-ui .button-secondary {
            margin-left: 0 !important;
        }
        </style>

        <script>
        if ( typeof jQuery != 'undefined' ) {
            jQuery(document).ready( function () {})
        }
        </script>
        <?php

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) return;
    }

    /**
    * Add Extra Links for Plugin
    *
    * @param mixed $links
    */
    public function plugin_links( $links )
    {
        $settings_link = '
            <a href="' . admin_url( 'admin.php?page=boltron' ) . '">' .
                __( 'Settings', 'boltron' ) .
            '</a>';

        $refresh_link = '
            <a id="boltron-reset-btn" style="color:#b66517;" href="' .
                add_query_arg( 'boltron-setup', 'setup' ) . '">' .
                __( 'Reset Plugin', 'boltron' ) .
            '</a>';

        array_unshift( $links, $settings_link );
        array_push( $links, $refresh_link );

        return $links;
    }

    public function product_panels()
    {
        global $woocommerce, $post;
        ?><div class="boltron_inventory"><?php

            // Global Trade Item Number (GTIN) Field
            woocommerce_wp_text_input([
                'id'          => '_gtin',
                'label'       => '<abbr title="' . __( 'Global Trade Item Number', 'boltron' ) . '">GTIN</abbr>',
                'desc_tip'    => true,
                'description' => __( "Unique trade number for ex. 3234567890126. Also supported for UPC, EAN, JAN, ISBN or ITF-14", 'boltron' ),
            ]);

            // Brand field
            woocommerce_wp_text_input([
                'id'          => '_brand',
                'label'       => __( 'Brand', 'boltron' ),
                'desc_tip'    => true,
                'description' => __( "Product's brand name for ex. Google. Required for product with a clearly associated brand. Optional for custom-made product.", 'boltron' )
            ]);

            // Add product condition drop-down
            woocommerce_wp_select([
                'id'		    => '_condition',
                'label'		    => __( 'Condition', 'boltron' ),
                'desc_tip'	    => true,
                'description'   => __( 'The condition of this product at time of sale', 'boltron' ),
                'options'	    => [
                    'new'		    => __( 'New', 'boltron' ),
                    'refurbished'	=> __( 'Refurbished', 'boltron' ),
                    'used'		    => __( 'Used', 'boltron' ),
                ]
            ]);

            // Contain Adult content
            woocommerce_wp_checkbox([
                'id'            => '_adult_content',
                'label'         => __( 'Has adult content', 'boltron' ),
                'description'   => __( 'Enable this if this product contains sexual content.', 'boltron' ),
                'value'         =>  boolval( get_post_meta( $post->ID, '_adult_content', true ) ) ? 'yes' : 'no'
            ]);

            // Gender
            woocommerce_wp_select([
                'id'		=> '_gender',
                'label'		=> __( 'Gender', 'boltron' ),
                'desc_tip'	=> true,
                'description'   => __( 'The gender for which your product is intended.', 'boltron' ),
                'options'       => [
                    'unisex'    => __( 'All', 'boltron' ),
                    'male'      => __( 'Male', 'boltron' ),
                    'female'    => __( 'Female', 'boltron' ),
                ]
            ]);

            $categories         = json_decode( file_get_contents( BTRN()->path . 'assets/google-categories.json' ), true );
            $product_cats       = (array) get_post_meta( $post->ID, '_product_cat', true );
            $product_cats       = empty( $product_cats ) ? [ -1 ] : $product_cats;
            $total_product_cats = count( $product_cats );
            $loop               = $total_product_cats > 7 ? $total_product_cats : 7;

            ?>
            <p class=" form-field _product_cat_field">
                <label for="_product_cat"><?php esc_html_e( 'Gshop Category', 'boltron' ); ?></label>

                <?php for( $i = 0; $i <= 7; $i++ ) : ?>
                <select name="_product_cat[<?php echo $i; ?>]" style="display: <?php echo $i > 0 && empty( $categories ) ? 'none' : 'block'; ?>">
                    <option value="-1"><?php esc_html_e( '== Select a Category ==', 'boltron' ); ?></option>
                    <?php foreach( $categories as $key => $children ) : ?>
                    <option value="<?php echo $key; ?>" <?php selected( $key, @$product_cats[ $i ] ) ?>><?php echo $key; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php
                $categories = $categories[ $product_cats[ $i ] ] ?? [];
                endfor; ?>

                <span class="description" style="float:left;"><?php esc_html_e( 'If not selected, the gshop category associated with the product category will be used if available.' ); ?></span>
            </p>
        </div>
        <?php
    }

    public function order_status_history( int $order_id, string $old_status, string $new_status, \WC_Order $order )
    {
        // Get order status history
        $history = $order->get_meta( '_b_status_history' ) ? $order->get_meta( '_b_status_history' ) : [];
        $history = array_filter( (array) $history );

        if ( isset( $history[ $new_status ] ) && ! empty( $history[ $new_status ] ) ) return;

        // Add the new order status with modified timestamp to the history array
        $history[ $new_status ] = $order->get_date_modified()->getTimestamp();

        // Update the order status history (as order meta data)
        $order->update_meta_data( '_b_status_history', $history );
        $order->save(); // Save
    }

    public function extra_category_fields( $tag )
    {
        $edit               = $tag instanceof \WP_Term;
        $wrap               = $edit ? 'tr' : 'div';
        $wrap2              = $edit ? [ '<th>', '</th>' ] : [];
        $wrap3              = $edit ? [ '<td>', '</td>' ] : [];
        $meta               = get_term_meta( $tag->term_id, 'boltron_cat_meta', true );
        $categories         = json_decode( file_get_contents( BTRN()->path . 'assets/google-categories.json' ), true );
        $product_cats       = $meta[ 'product_cat' ] ?? [];
        $product_cats       = empty( $product_cats ) ? [ -1 ] : $product_cats;
        $total_product_cats = count( $product_cats );
        $loop               = $total_product_cats > 7 ? $total_product_cats : 7;
        ?>

        <<?php echo $wrap ?> class="form-field term-boltron-cat-ghsop-cat-wrap" style="<?php echo $edit ? '' : 'margin: 20px 0; float: left;' ?>">
            <?php echo @$wrap2[0] ?>
                <label><?php _e( 'Gshop Product Category', 'boltron' ); ?></label>
            <?php echo @$wrap2[1] ?>

            <?php echo @$wrap3[0] ?>
                <?php for( $i = 0; $i <= 7; $i++ ) : ?>
                <select name="boltron_cat_meta[product_cat][<?php echo $i; ?>]" style="width: 220px;float: left;display: <?php echo $i > 0 && empty( $categories ) ? 'none' : 'block'; ?>">
                    <option value="-1"><?php esc_html_e( '== Select a Category ==', 'boltron' ); ?></option>
                    <?php foreach( $categories as $key => $children ) : ?>
                    <option value="<?php echo $key; ?>" <?php selected( $key, @$product_cats[ $i ] ) ?>><?php echo $key; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php
                $categories = $categories[ $product_cats[ $i ] ] ?? [];
                endfor; ?>

                <p class="description" style="width: 100%; float: left;"><?php _e( 'The google shoping product cateory to be associated with this category.' ) ?></p>
            <?php echo @$wrap3[1] ?>
        </<?php echo $wrap ?>>

        <?php
    }

    public function save_extra_category_fields( $term_id )
    {
        if ( ! isset( $_POST['boltron_cat_meta'] ) ) return;

        $meta = array_filter( (array) get_term_meta( $tag->term_id, 'boltron_cat_meta', true ) );

        // error_log( print_r( $meta, true ) );

        foreach ( $_POST['boltron_cat_meta'] as $key => $value ) $meta[$key] = $value;

        // error_log( print_r( $meta, true ) );

        update_term_meta( $term_id, 'boltron_cat_meta', array_filter( $meta ) );
    }
    
    /**
    * Admin Menu
    */
    public function admin_menu()
    {
        add_menu_page(
            'Boltron',
            'Boltron',
            'manage_options',
            BTRN()->slug,
            [ $this, 'settings' ],
            BTRN()->uri . 'assets/images/icon.png',
            45.28473
        );
    }

    /**
    * Save post hook
    * 
    * @param int     $post_ID Post ID.
    */
    public function save_order( int $id )
    {
        $this->send_orders( [ $id ] );
    }

    /**
    * Save comment hook
    */
    public function save_review( $id )
    {
        $this->send_reviews( [ (int) $id ] );
    }

    /**
    * Save Product hook
    */
    public function save_product( int $id )
    {
        update_post_meta( $id, '_brand', sanitize_text_field( $_POST[ '_brand' ] ) );
        update_post_meta( $id, '_gtin', sanitize_text_field( $_POST[ '_gtin' ] ) );
        update_post_meta( $id, '_condition', sanitize_text_field( $_POST[ '_condition' ] ) );
        update_post_meta( $id, '_adult_content', isset( $_POST[ '_adult_content' ] ) && $_POST[ '_adult_content' ] == 'yes' ? 1 : 0 );
        update_post_meta( $id, '_gender', sanitize_text_field( $_POST[ '_gender' ] ) );
        update_post_meta( $id, '_product_cat', (array) $_POST[ '_product_cat' ] );

        $this->send_products( [ $id ] );
    }

    /**
    * Delete order hook
    * 
    * @param int  $id Post ID.
    */
    public function delete_order( int $id )
    {
        global $post_type;

        if ( $post_type !== 'shop_order' ) return;

        $this->send_orders( [ $id ], true );
    }

    /**
    * Delete review hook
    * 
    * @param int  $review WP_Comment object.
    */
    public function delete_review( int $id )
    {
        $this->send_reviews( [ (int) $id ], true );
    }

    /**
    * Delete product hook
    * 
    * @param int  $id Post ID.
    */
    public function delete_product( int $id )
    {
        global $post_type;

        if ( $post_type !== 'product' ) return;

        $this->send_products( [ $id ], true );
    }

    public function single_event()
    {
        $default    = [
            'limit'     => -1,
            'orderby'   => 'created',
            'order'     => 'DESC',
        ];
        $completed  = (array) wc_get_orders( $default + [ 'status' => 'completed' ] );
        $refunded   = (array) wc_get_orders( $default + [ 'status' => 'refunded' ] );
        $cancelled  = (array) wc_get_orders( $default + [ 'status' => 'cancelled' ] );
        $trashed    = (array) wc_get_orders( $default + [ 'status' => 'trash' ] );
        $orders     = $completed + $refunded + $cancelled + $trashed;

        $c_review   = get_comments( [ 'post_type' => 'review' ] );
        $c_product  = get_comments( [ 'post_type' => 'product' ] );
        $reviews    = (array) $c_review + (array) $c_product;

        $products   = wc_get_products( [ 'status' => 'publish' ] );

        $this->send_orders( $orders ); // Send Orders
        $this->send_reviews( $reviews ); // Send Reviews
        $this->send_products( $products ); // Send Products
    }

    public function send_orders( array $orders, $delete = false )
    {
        if ( wp_doing_ajax() ) return;

        $data = [];
        $action = 'merchant_orders';
        $action .= $delete ? '/delete' : '';

        foreach( $orders as $order ) {

            if ( is_int( $order ) ) $order = wc_get_order( $order );

            if ( $order instanceof \WC_Order_Refund ) $order = wc_get_order( @$order->get_parent_id() );

            if ( empty( $order ) ) continue;

            $order_id = $order->get_id();

            if ( $delete ) {
                $args = [ 'order_id' => $order_id ];
            } else {
                $status = $order->get_status();

                if ( isset( $data[ $order_id ] ) || ! in_array( $status, ['completed','processing','refunded','trash','cancelled'] ) ) continue;

                $refunded = $order->get_total_refunded() > 0 || stripos( 'refund', $status ) !== false ? true : false;

                if ( ! $order->is_paid() && $order->needs_payment() ) continue;

                $status_history = array_filter( (array) $order->get_meta( '_b_status_history' ) );

                $args = [
                    'order_id'      => $order_id,
                    'total'         => $order->get_total(),
                    'currency'      => $order->get_currency(),
                    'status'        => $status,
                    'customer_id'   => $order->get_customer_id(),
                    'email'         => $order->get_billing_email(),
                    'first_name'    => $order->get_billing_first_name(),
                    'last_name'     => $order->get_billing_last_name(),
                    'refunded'      => $refunded,
                    'meta'          => [
                        'status_history' => $status_history,
                    ],
                    'created_at'    => $order->get_date_created()->getTimestamp(),
                    'updated_at'    => $order->get_date_modified()->getTimestamp()
                ];
            }

            $data[ $order_id ] = $args;
        }

        // error_log( print_r( $data, true ) );

        if ( empty( $data ) ) return;

        $this->send_to_api( $action, $data );
    }

    public function send_reviews( array $comments, $delete = false )
    {
        if ( wp_doing_ajax() ) return;

        $data = [];
        $action = 'merchant_reviews';
        $action .= $delete ? '/delete' : '';

        foreach( $comments as $comment ) {

            if ( is_int( $comment ) ) $comment = get_comment( $comment );

            if ( $comment == null ) continue;

            $review_id  = intval( $comment->comment_ID );

            if ( $delete ) {
                $args = [ 'review_id' => $review_id ];
            } else {
                $time       = strtotime( $comment->comment_date_gmt );
                $rating     = get_comment_meta( $review_id, 'rating', true );
                // $rating     = is_null( $rating ) || $rating == '' ? 3.5 : (float) $rating;

                // If comment has no rating
                if ( is_null( $rating ) || $rating == '' ) continue;

                // If comment has not been approved
                if ( ! in_array( wp_get_comment_status( $review_id ), [ 'approve', 'approved' ] ) ) continue;

                $post = get_post( $comment->comment_post_ID );

                $args = [
                    'review_id'     => $review_id,
                    'title'         => $post->post_title ?? 'Product',
                    'content'       => $comment->comment_content,
                    'rating'        => (float) $rating,
                    'name'          => $comment->comment_author,
                    'email'         => $comment->comment_author_email,
                    'created_at'    => $time,
                    'updated_at'    => $time,
                ];
            }

            $data[ $review_id ] = $args;
        }

        // error_log( print_r( $data, true ) );

        if ( empty( $data ) ) return;

        $this->send_to_api( $action, $data );
    }

    public function send_products( array $products, $delete = false )
    {
        if ( wp_doing_ajax() ) return;

        $data = [];
        $action = 'merchant_products';
        $action .= $delete ? '/delete' : '';
        $currency = get_option( 'woocommerce_currency' );
        $weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
        $dimension_unit = get_option( 'woocommerce_dimension_unit', 'in' );
        $zones = \WC_Shipping_Zones::get_zones();

        foreach( $zones as $zone ) {
            $settings = get_option( "woocommerce_flat_rate_{$zone['id']}_settings" );

            if ( empty( $settings ) || ! isset( $settings['cost'] ) || empty( $settings['cost'] ) ) continue;

            $rates[] = $settings;
        }

        foreach( $products as $product ) {

            if ( is_int( $product ) ) $product = wc_get_product( $product );

            if ( $product instanceof \WC_Product_Variation ) $product = wc_get_product( @$product->get_parent_id() );

            if ( $product == null ) continue; // Skip if empty product.

            $product_id  = intval( $product->get_id() );

            if ( wp_is_post_revision( $product_id ) ) continue; // Skip if it's a revision.

            if ( $delete ) {
                $args = [ 'product_id' => $product_id ];
            } else {

                if ( ! $product->is_visible() ) continue;

                $cat_ids        = $product->get_category_ids();
                $tag_ids        = $product->get_tag_ids();
                $attrs          = $product->get_attributes();
                $brand          = get_post_meta( $product_id, '_brand', true );
                $gtin           = get_post_meta( $product_id, '_gtin', true );
                $condition      = get_post_meta( $product_id, '_condition', true );
                $gender         = get_post_meta( $product_id, '_gender', true );
                $product_cat    = (array) get_post_meta( $product_id, '_product_cat', true );
                $adult_content  = boolval( get_post_meta( $product_id, '_adult_content', true ) );
                $categories     = [];
                $tags           = [];
                $attributes     = [];
                $variations     = [];

                foreach( $attrs as $attribute ) {
                    if ( ! $attribute->get_visible() ) continue;

                    $taxonomy   = $attribute->get_name();
                    $name       = str_replace( [ '-', 'pa_' ], [ '_', '' ], $taxonomy );

                    $attributes[ $name ] = array_map( function( $option ) use ( $taxonomy ) {
                        $term = get_term( $option, $taxonomy );

                        return $term->name;
                    }, $attribute->get_options() );
                }

                if ( $product->get_type() == 'variable' ) {
                    $variants = (array) @$product->get_available_variations();

                    foreach ( $variants as $variant ) {
                        $var = wc_get_product( $variant['variation_id'] );

                        if ( ! $var->is_visible() ) continue;

                        $summary = $var->get_attribute_summary() ?? '';
                        $summary = explode( ',', $summary );
                        $summary = array_map( function( $part ) {
                            list( $name, $value ) = explode( ':', trim( $part ) );

                            return [ strtolower( str_replace( '-', '_', sanitize_title( $name ) ) ) => trim( $value ) ];
                        }, $summary);

                        $the_attrs = call_user_func_array( 'array_merge', $summary );
                        $summary = @implode( ' - ', $the_attrs );
                        $summary = empty( $summary ) ? '' : " - $summary";

                        foreach ( $attributes as $name => $attribute_array ) {
                            if ( isset( $the_attrs[ $name ] ) && ! empty( $the_attrs[ $name ] ) ) continue;

                            $the_attrs[ $name ] = $name == 'age_group' ? 'adult' : 'Any ' . str_replace( '_', ' ', ucwords( $name ) );
                        }

                        $variations[ $var->get_id() ] = [
                            'id'        => $var->get_id(),
                            'sku'       => $var->get_sku() ?? '',
                            'name'      => $product->get_name() . $summary,
                            'attributes'=> $the_attrs,
                            'prices'    => [
                                'price'         => floatval( $var->get_price() ),
                                'sale_price'    => floatval( $var->get_sale_price() ),
                                'regular_price' => floatval( $var->get_regular_price() )
                            ],
                        ];
                    }
                }

                // $product_cat = str_replace( '-1', '', implode( ' > ', $product_cat ) );
                // $cnt = array_count_values( $product_cat );
                $product_cat = array_filter( $product_cat, function( $a ) { return ! in_array( $a, [ '-1', -1 ] ); } );

                foreach( $cat_ids as $term_id ) {

                    if ( empty( $product_cat ) ) {
                        $meta           = get_term_meta( $term_id, 'boltron_cat_meta', true );
                        $product_cat   = $meta[ 'product_cat' ] ?? [];
                        $product_cat   = array_filter( $product_cat, function( $a ) { return ! in_array( $a, [ '-1', -1 ] ); } );
                    }

                    $categories[] = trim( get_term_parents_list( $term_id, 'product_cat', [
                        'format'    => 'name',
                        'separator' => ' > ',
                        'link'      => false,
                    ] ), ' > ' );
                }

                $product_cat = trim( str_replace( '-1', '', implode( ' > ', $product_cat ) ) );

                foreach( $tag_ids as $term_id ) {
                    $tag = get_term( $term_id, 'product_tag' );
                    $tags[] = $tag->name ?? '';
                }

                // Prices
                $prices = [
                    'price'         => floatval( $product->get_price() ),
                    'sale_price'    => floatval( $product->get_sale_price() ),
                    'regular_price' => floatval( $product->get_regular_price() )
                ];


                // Images
                $images = array_filter( array_map( function( $image_id ) {

                    return wp_get_attachment_url( $image_id );

                }, $product->get_gallery_image_ids() ) );

                $images = [ 'primary' => wp_get_attachment_url( $product->get_image_id() ) ] + $images;


                // Stock
                $stock = [
                    'status'    => $product->get_stock_status(),
                    'quantity'  => $product->get_stock_quantity() ?? '',
                ];


                // Sale Dates
                $sale_dates = [
                    'from'  => empty( $product->get_date_on_sale_from() ) ? '' : $product->get_date_on_sale_from()->getTimestamp(),
                    'to'    => empty( $product->get_date_on_sale_to() ) ? '' : $product->get_date_on_sale_to()->getTimestamp(),
                ];

                $created_at = empty( $product->get_date_created() ) ? '' : $product->get_date_created()->getTimestamp();
                $updated_at = empty( $product->get_date_modified() ) ? '' : $product->get_date_modified()->getTimestamp();

                $shipping = [];

                if ( $product->needs_shipping() ) {
                    $shipping_class =  get_term( $product->get_shipping_class_id(), 'product_shipping_class' );
                    $shipping = [
                        'name' => $shiiping_class->name,
                        'cost' => 0,
                    ];

                    if ( isset( $rates ) ) {
                        foreach( $rates as $rate ) {
                            $class_cost = (float) $rate[ 'class_cost_' . $shipping_class->term_id ];
                            $cost       = (float) $rate[ 'cost' ];

                            $shipping[ 'cost' ] += $rate[ 'type' ] == 'class' ? $class_cost : $cost;
                        }
                    }
                }

                $weight = [
                    'value' => floatval( $product->get_weight() ),
                    'unit'  => $weight_unit,
                ];

                $dimensions = [ 'unit' => $dimension_unit ] + $product->get_dimensions( false );

                $args = [
                    'product_id'    => $product_id,
                    'name'          => $product->get_name(),
                    'description'   => $product->get_description() ?? '',
                    'excerpt'       => $product->get_short_description() ?? '',
                    'sku'           => $product->get_sku() ?? '',
                    'url'           => $product->get_permalink(),
                    'type'          => $product->get_type(),
                    'status'        => $product->get_status(),
                    'meta'          => [
                        'brand'         => $brand,
                        'gtin'          => $gtin,
                        'condition'     => $condition,
                        'gender'        => $gender,
                        'product_cat'   => $product_cat,
                        'adult_content' => $adult_content,
                        'currency'      => $currency,
                        'prices'        => $prices,
                        'shipping'      => $shipping,
                        'weight'        => $weight,
                        'dimensions'    => $dimensions,
                        'stock'         => $stock,
                        'attributes'    => $attributes,
                        'variations'    => $variations,
                        'images'        => $images,
                        'categories'    => $categories,
                        'tags'          => $tags,
                        'sale_dates'    => $sale_dates,
                    ],
                    'created_at'    => $created_at,
                    'updated_at'    => $updated_at,
                ];
            }

            $data[ $product_id ] = $args;
        }

        // error_log( print_r( $data, true ) );

        if ( empty( $data ) ) return;

        $this->send_to_api( $action, $data );
    }

    public function precall_api()
    {
        $precall = (bool) get_transient( 'boltron_api_precall' );

        if ( ! $precall ) $this->send_to_api();
    }

    private function send_to_api( string $action = '', array $body = [] )
    {
        $url        = untrailingslashit( BTRN()->host_api . "/$action" );
        $api_key    = sanitize_text_field( @$_REQUEST['boltron_api_key'] );
        $api_key    = empty( $api_key ) ? BTRN()->get_option( 'api_key' ) : $api_key;

        $response = wp_remote_post( $url, [
            'timeout'       => 30,
            'sslverify'     => false,
            'headers'       => [
                'Merchant-Api-Key'  => $api_key,
                'Merchant-Origin'   => home_url(),
            ],
            'body'          => $body
        ]);

        if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
            $err = $response->get_error_message() ?? __( 'Empty body content.', 'boltron' );

            BTRN()->add_notice( __( '<strong>Something went wrong!</strong>', 'boltron' ) . $err, 'error', true, true );

            return false;
        }

        $json = json_decode( $response['body'] );

        // error_log( print_r( (array) $json, true ) );

        if ( $json == null ) {
            BTRN()->add_notice( __( '<strong>Error Occured!</strong> No data returned', 'boltron' ), 'error', true, true );

            return false;

        } else if ( $json->type == 'error' ) {
            $err = explode( ':', $json->message );
            $msg = @explode( '/', $err[1] );
            $code = count( $msg ) > 1 ? $msg[0] : $err[0];
            $msg = count( $msg ) > 1 ? $msg[1] : $err[1];

            BTRN()->add_notice( __( "<strong>$code</strong>: $msg", 'boltron' ), 'error', true, true );

            return false;
        }

        // Always save grade and update precall transient
        if ( isset( $json->grade ) ) {
            BTRN()->set_option( 'grade', $json->grade );

            set_transient( 'boltron_api_precall', true, 3600 );
        }

        return $json;
    }

    /**
    * Load Admin page
    */
    public function settings()
    {
        if (
            ( isset( $_POST['boltron_submit'] ) && BTRN()->get_option( 'merchant_id' ) == '' ) ||
            ( isset( $_POST['boltron_submit'] ) && BTRN()->get_option( 'api_key' ) != sanitize_text_field( $_POST['boltron_api_key'] ) ) ||
            ( isset( $_POST['key_check'] ) && $_POST['key_check'] == 'yes' ) ) {

            if ( ! check_admin_referer( 'boltron_nonce', 'boltron_settings' ) ) return;

            $resp = $this->send_to_api();

            if ( $resp != false ) {

                // wp_schedule_single_event( current_time( 'timestamp' ) + 5, 'boltron_single_event' ); // Runs in 5 seconds.

                $this->single_event();

                BTRN()->set_option( [
                    'api_key'       => sanitize_text_field( $_POST['boltron_api_key'] ),
                    'merchant_id'   => sanitize_text_field( $resp->merchant_id ),
                ] );

                BTRN()->add_notice( __( '<strong> Valid! </strong> API key verified successfully.', 'boltron' ), 'updated', true, true );
            }
        }

        if ( isset( $_POST['boltron_submit'] ) ) {

            if ( ! check_admin_referer( 'boltron_nonce', 'boltron_settings' ) ) return;

            $error = false;

            foreach ( $_POST as $name => $value ) {

                if ( strpos( $name, 'boltron_' ) === false ) continue;

                if ( $value == '' ) {
                    $error = true;

                    break;
                }
            }

            if ( ! $error ) {

                BTRN()->set_option( [
                    'api_key'           => sanitize_text_field( $_POST['boltron_api_key'] ),
                    'floating_widget'   => sanitize_text_field( $_POST['boltron_floating_widget'] ),
                    'enable_cs'         => boolval( @$_POST['boltron_enable_cs'] ),
                ] );

                BTRN()->add_notice( __( '<strong> Done! </strong> Settings saved.', 'boltron' ), 'updated', true, true );

            } else {

                BTRN()->add_notice( __( '<strong> Doing wrong! </strong> All fields are required.', 'boltron' ), 'error', true, true );

            }

        }

        require_once BTRN()->path . 'templates/backend/settings.php';
    }

    /**
     * Add new Guternberg block for phanes3dp shortcode
     */
    public function enqueue_block()
    {
        wp_enqueue_script( BTRN()->name . ' block', BTRN()->uri . 'assets/js/editor-block.js', [ 'wp-blocks', 'wp-editor' ], BTRN()->version, true );
    }

    /**
    * Enqueue Admin Scripts
    */
    public function admin_scripts()
    {
        $screen = get_current_screen();

        wp_enqueue_script( BTRN()->name . '-backend-general', BTRN()->uri . 'assets/js/backend-general.js', [ 'jquery', 'wp-i18n' ], BTRN()->version, true );

        $categories = json_decode( file_get_contents( BTRN()->path . 'assets/google-categories.json' ), true );
        wp_localize_script( BTRN()->name . '-backend-general', 'Boltron_Product_Cats', $categories );

        if ( ! isset( $screen->id ) || strstr( $screen->id, 'boltron' ) == false ) return;

        /**
        * Enqueue Styles
        */
        wp_enqueue_style( BTRN()->name, BTRN()->uri . 'assets/css/backend.css', [], BTRN()->version, 'all' );

        /**
        * Enqueue Scripts
        */
        // wp_enqueue_media();

        wp_enqueue_script( BTRN()->name . ' URL SCRIPT', BTRN()->uri . 'assets/js/uri.min.js', [], BTRN()->version, true );
        wp_enqueue_script( BTRN()->name . ' URL MOD', BTRN()->uri . 'assets/js/urlmod.js', [ BTRN()->name . ' URL SCRIPT' ], BTRN()->version, true );
        wp_enqueue_script( BTRN()->name, BTRN()->uri . 'assets/js/backend.js', [], BTRN()->version, true );

        $params = [ 'action', '_wpnonce', 'boltron-setup' ];

        /**
        * Whether to do the the url modification or not
        *
        * @var boolean
        */
        $do_url_mod = apply_filters( 'boltron_do_url_mod', true );

        /**
        * Add url parameters to remove
        *
        * @var array
        */
        array_push( $params, apply_filters( 'boltron_mod_params', [] ) );

        wp_localize_script( BTRN()->name . ' URL MOD', 'Boltron_Backend', [
                'do_url_mod' => $do_url_mod === false ? false : true,
                'url_params' => $params,
            ]
        );
    }
}