<?php
/*
Plugin Name:    Boltron - Merchant Reviews
Plugin URI:     https://boltron.co/
Description:    Boltron is an e-commerce reviews platform that use order data to determine an e-commerce store reputation.
Version:        1.4
Author:         ncej2
Author URI:     https://boltron.co/
Text Domain:    boltron
Domain Path:    /i18n/languages/
*/

namespace Boltron {

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

use Boltron\Backend;
use Boltron\Frontend;

/**
 * Boltron class functions and definitions
 *
 * @package Boltron
 */
class Boltron
{
    const REQUIRES_WP = 4.1;
    const REQUIRES_WC = 3.2;

    /**
     * Holds the name
     */
    public $name;

    /**
     * Holds the slug
     */
    public $slug;

    /**
     * Holds the version number
     */
    public $version;

    /**
     * Holds the author's name
     */
    public $author;

    /**
     * Holds the author's uri
     */
    public $author_uri;

    /**
     * Holds the directory path
     */
    public $path;

    /**
     * Holds the file uri
     */
    public $uri;

    /**
     * The single instance of the class.
     * 
     * @access private
     * @since 1.3
     */
    private static $backend;

    /**
     * The single instance of the class.
     * 
     * @access private
     * @since 1.3
     */
    private static $frontend;

    /**
     * The single instance of the class.
     *
     * @var Boltron
     * @since 1.2
     */
    protected static $_instance = null;

    /**
     * Check if plugin is running correctly.
     * @access public
     * @since 1.2
     */
    public $inactive = false;

    /**
    * Holds all notice
    * @var string
    * @access public
    * @since 1.2
    */
    public static $_notice = '';

    /**
     * Main Boltron Instance.
     *
     * Ensures only one instance of Boltron is loaded or can be loaded.
     *
     * @since 1.2
     * @static
     * @see Boltron()
     * @return Boltron - Main instance.
     */
    public static function instance()
    {
        if ( is_null( self::$_instance ) ) self::$_instance = new self();

        return self::$_instance;
    }

    /**
     * Cloning is forbidden.
     * @since 1.2
     */
    public function __clone()
    {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'boltron' ), $this->version );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * @since 1.2
     */
    public function __wakeup()
    {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'boltron' ), $this->version );
    }

    /**
    * Boltron Constructor
    * @since 1.0
    */
    public function __construct()
    {
        $this->set_attributes();
        $this->define_constants();

        add_action( 'plugins_loaded', [ $this, 'i18n' ], 0 );
        add_action( 'init', [ $this, '__check' ], 1);
        add_action( 'init', [ $this, 'includes' ], 2);

        do_action( 'boltron_loaded' );
    }

    /**
     * Define attributes.
     */
    private function set_attributes()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin             =  get_plugin_data( __FILE__ );

        $this->host         = 'https://app.boltron.co';
        // $this->host         = 'https://app.boltron.co';
        $this->host_api     = $this->host . '/api/v2';
        $this->name         = $plugin['Name'];
        $this->slug         = $plugin['TextDomain'];
        $this->version      = $plugin['Version'];
        $this->author       = $plugin['Author'];
        $this->author_uri   = $plugin['AuthorURI'];
        $this->theme_uri    = $plugin['PluginURI'];
        $this->basename     = plugin_basename( __FILE__ );
        $this->path         = plugin_dir_path( __FILE__ );
        $this->uri          = plugin_dir_url( __FILE__ );
    }

    /**
     * Define Boltron Constants.
     */
    private function define_constants()
	{
    }

    /**
    * Internationalization
    *
    */
    public function i18n()
    {
        load_plugin_textdomain( 'boltron', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
    * Add Notice
    * 
    * @since 1.0
    * @param mixed $msg The message to display
    * @param mixed $class The css class of notice, Accepts "updated and error".
    */
    public function add_notice( $msg, $class, $dismiss = true, $echo = false )
    {
        if ( ! is_admin() ) return;

        $dismiss = $dismiss === true ?
            '<button type="button" class="notice-dismiss">
            	<span class="screen-reader-text">' . __('Dismiss this notice.', 'boltron') . '</span>
            </button>' : '';

        $notice = '<div id="message" class="' . $class . ' notice is-dismissible"><p>' . $msg . '</p>' . $dismiss . '</div>';

        if ( $echo === true )
            echo $notice;
        else
            self::$_notice .= $notice;
    }

    /**
    * Check Requirements
    * @since 1.0
    */
    public function __check()
    {
        if ( ! version_compare( $GLOBALS['wp_version'], self::REQUIRES_WP, '>=' ) ) {
            $this->add_notice(
                sprintf(
                    __( '<strong>%s</strong> plugin requires a
                        <a href="http://wordpress.org/latest.zip">newer version</a>
                        of WordPress to work properly.', 'boltron'
                    ),
                    $this->name
                ), 'error', false
            );

            $this->inactive = true;

            return;
        }

        if ( ! class_exists( 'WooCommerce' ) ) {

            $this->add_notice(
                sprintf(
                    __( '<strong>%s</strong> requires WooCommerce to be activated to start working.', 'boltron' ),
                    $this->name
                ), 'error', false
            );
            
            $this->inactive = true;

            return;
        }
        
        if ( version_compare( WC()->version, self::REQUIRES_WC, '<' ) ) {

            $this->add_notice(
                sprintf(
                    __( '<strong>%s</strong> requires a <a href="https://woocommerce.com/">newer version</a> of WooCommerce to work properly.', 'boltron' ),
                    $this->name
                ), 'error', false
            );
            
            $this->inactive = true;

            return;
        }

        $version = get_option( 'boltron-version', 'none' );
        $options = get_option( 'boltron-options', [] );

        if (
            $version == 'none' || version_compare( $version, $this->version, '<' ) ||
            ( isset( $_REQUEST['boltron-setup'] ) && $_REQUEST['boltron-setup'] == 'setup' )
        ) $this->run_setup();

        if ( isset( $_REQUEST['boltron-setup'] ) && $_REQUEST['boltron-setup'] == 'complete' ) {
            $this->add_notice(
                sprintf(
                    __( 'Thank you for using <strong>%s</strong>. Setup is complete.', 'boltron' ),
                    $this->name
                ), 'updated boltron-inner-notice'
            );
        }

        if ( empty( $options ) ) {
            $this->add_notice(
                sprintf(
                    __( 'Add your Merchant API Key for <strong>%s</strong> <a href="%s">here</a>.', 'boltron' ),
                    $this->name,
                    admin_url( 'admin.php?page=boltron' )
                ), 'boltron-update-nag'
            );
        }

    }

    /**
     * Loads required files
     * 
     * @since 1.2
     */
    public function includes()
    {
        /**
         * Require the backend
         */
        require_once $this->path . 'core/backend.php';

        /**
         * Require the frontend
         */
        require_once $this->path . 'core/frontend.php';

        // Assign the loaded classes.
        $this->backend    = new Backend;
        $this->frontend   = new Frontend;
    }

    /**
     * Returns the option given the key
     * 
     * @param string $key
     */
    public function get_option( $key = null )
    {
        if ( $key == 'grade' ) $this->backend->precall_api();

        $options = wp_parse_args( (array) get_option( 'boltron-options' ), [
            'merchant_id'       => '',
            'api_key'           => '',
            'grade'             => '',
            'floating_widget'   => 'right', // left, right or disabled
            'enable_cs'         => false, // true or false
        ] );

        return empty( $key ) ? json_decode( json_encode( $options ) ) : ( isset($options[$key]) ? $options[$key] : '' );
    }

    /**
     * Updated the plugin option
     * 
     * @param string|array $key
     * @param string $value
     */
    public function set_option( $key, $value = null )
    {
        if ( ! is_array( $key ) && ! is_string( $key ) ) return;

        if ( is_array( $key ) ) {
            $data = $key;
        } else {
            $data = [ $key => $value ];
        }

        $args = wp_parse_args( $data, (array) get_option( 'boltron-options' ) );

        update_option( 'boltron-options', array_filter( $args ) );
    }

    /**
    * Run Setup
    * @since 1.0
    */
    public function run_setup()
    {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) return;

        $old_key = get_option( 'boltron_merchant_review_key' );

        if ( ! empty( $old_key ) ) {
            $options = wp_parse_args( [
                'api_key' => $old_key,
            ], (array) $this->get_option() );

            update_option( 'boltron-options', $options );

            delete_option( 'boltron_merchant_review_key' );
        }

        update_option( 'boltron-version', $this->version );

        // Create default woocommerce attributes if not set
        $this->process_attributes();

        exit( wp_redirect( admin_url( 'admin.php?page=boltron' ) ) );
    }

    public function process_attributes()
    {
        global $wpdb;

        delete_transient( 'wc_attribute_taxonomies' );

        $attributes = [ 'Color', 'Size', 'Material', 'Pattern', 'Age Group' ];
        $table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $added = (array) $wpdb->get_results( "SELECT `attribute_label` FROM $table" );
        $added = empty( $added ) ? [] : array_map( function( $val ) { return $val->attribute_label; }, $added );

        if ( in_array( 'Colour', $added ) ) {
            if ( $color_key = array_search( 'Color', $attributes ) ) unset( $attributes[ $color_key ] );
        }

        $added = array_diff( $attributes, $added );

        foreach ( $added as $attribute ) {
            $slug = str_replace( '-', '_', sanitize_title( $attribute ) );

            $create = wc_create_attribute([
                'name'          => $attribute,
                'slug'          => $slug,
                'type'          => 'select',
                'has_archives'  => true,
            ]);

            if ( $attribute == 'Age Group' ) {

                $attribute_taxonomies = wc_get_attribute_taxonomies();
                $tax = $attribute_taxonomies[ "id:$create" ];
                $permalinks = wc_get_permalink_structure();
                $name = wc_attribute_taxonomy_name( $tax->attribute_name );

                $tax->attribute_public          = absint( isset( $tax->attribute_public ) ? $tax->attribute_public : 1 );
                $label                          = ! empty( $tax->attribute_label ) ? $tax->attribute_label : $tax->attribute_name;
                $taxonomy_data                  = [
                    'hierarchical'          => false,
                    'update_count_callback' => '_update_post_term_count',
                    'labels'                => [
                            'name'              => sprintf( _x( 'Product %s', 'Product Attribute', 'woocommerce' ), $label ),
                            'singular_name'     => $label,
                            'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
                            'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
                            'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
                            'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
                            'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
                            'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
                            'add_new_item'      => sprintf( __( 'Add new %s', 'woocommerce' ), $label ),
                            'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label ),
                            'not_found'         => sprintf( __( 'No &quot;%s&quot; found', 'woocommerce' ), $label ),
                        ],
                    'show_ui'            => true,
                    'show_in_quick_edit' => false,
                    'show_in_menu'       => false,
                    'meta_box_cb'        => false,
                    'query_var'          => 1 === $tax->attribute_public,
                    'rewrite'            => false,
                    'sort'               => false,
                    'public'             => 1 === $tax->attribute_public,
                    'show_in_nav_menus'  => 1 === $tax->attribute_public && apply_filters( 'woocommerce_attribute_show_in_nav_menus', false, $name ),
                    'capabilities'       => [
                        'manage_terms' => 'manage_product_terms',
                        'edit_terms'   => 'edit_product_terms',
                        'delete_terms' => 'delete_product_terms',
                        'assign_terms' => 'assign_product_terms',
                    ],
                ];

                if ( 1 === $tax->attribute_public && sanitize_title( $tax->attribute_name ) ) {
                    $taxonomy_data['rewrite'] = [
                        'slug'         => trailingslashit( $permalinks['attribute_rewrite_slug'] ) . sanitize_title( $tax->attribute_name ),
                        'with_front'   => false,
                        'hierarchical' => true,
                    ];
                }

                register_taxonomy( $name, apply_filters( "woocommerce_taxonomy_objects_{$name}", [ 'product' ] ), apply_filters( "woocommerce_taxonomy_args_{$name}", $taxonomy_data ) );

                foreach (['Adult', 'Infant', 'kids', 'Newborn', 'Toddler'] as $term ) $insert = wp_insert_term( $term, $name, [ 'slug' => sanitize_title( $term ) ] );
            }
        }
    }

}

}

namespace { // Global namepsace
$GLOBALS['boltron'] = new Boltron\Boltron;

function BTRN() { return $GLOBALS['boltron']; }
}