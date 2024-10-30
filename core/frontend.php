<?php

namespace Boltron;

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Boltron Frontend
 *
 * @package Boltron
 */
class Frontend
{
    private static $shortcode_render_counter = 1;

    /**
     * Constructor
     */
    public function __construct()
    {
        if ( BTRN()->inactive ) return;

        add_shortcode( 'boltron', [ $this, 'shortcode' ] );

        add_action( 'wp_footer', [ $this, 'footer' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ] );
    }

    public function shortcode( $args = [] )
    {
		if ( self::$shortcode_render_counter > 1 ) return;

		++self::$shortcode_render_counter;

        $args = (object) shortcode_atts( [
            'type'  => 'small', // large
        ], $args );

        // error_log($this->prepare_shortcode('boltron', $args));

        ob_start();

        require_once BTRN()->path . 'templates/frontend/shortcode-widget.php';

        return $this->send_response( ob_get_clean() );
    }

    private function prepare_shortcode( $tag, $args )
    {
        $args = (array) $args;

        $content = join(' ', array_map(function($key, $value) {
            return "$key=\"$value\"";
        }, array_keys($args), $args));

        return "[$tag $content]";
    }

    public function send_response( $content )
    {
        // Remove newline whitespaces
        return preg_replace('~>\s*\n\s*<~', '><', $content);
    }

    public function footer()
    {
        require_once BTRN()->path . 'templates/frontend/cs-widget.php';
        require_once BTRN()->path . 'templates/frontend/grade-widget.php';
    }

    public function scripts()
    {
        /**
        * Enqueue Styles
        */
        wp_enqueue_style( BTRN()->name, BTRN()->uri . 'assets/css/frontend.css', [], BTRN()->version, 'all' );
        // wp_enqueue_style( BTRN()->name . '-review', BTRN()->uri . 'assets/css/review.css', [], BTRN()->version, 'all' );
        // wp_enqueue_style( BTRN()->name . '-bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css', [], BTRN()->version, 'all' );

        // wp_enqueue_script( BTRN()->name . '-jquery', '//code.jquery.com/jquery-1.11.1.min.js', [], BTRN()->version, 'all' );
        // wp_enqueue_script( BTRN()->name . '-bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js', [ 'jquery' ], BTRN()->version, 'all' );
        // wp_enqueue_script( BTRN()->name . '-review', BTRN()->uri . 'assets/js/review.js', [ 'jquery' ], BTRN()->version, 'all' );
    }
}