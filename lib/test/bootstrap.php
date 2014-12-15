<?php
/**
 * Unit test bootstrapper.
 * This is nothing close to an accurate simulation of Wordpress environment, it's just for testing utils.
 * @usage phpunit --bootstrap bootstrap.php tests
 */
 
define('WP_CONTENT_DIR', realpath(__DIR__.'/../../../..') ); 
WP_CONTENT_DIR or die("Can't find WP_CONTENT_DIR\n");

define('WP_LANG_DIR', WP_CONTENT_DIR.'/languages' );
 
function is_admin(){
    return false;
} 

function admin_url( $path = '', $scheme = 'admin' ){
    return 'http://test/wp-admin/admin.php';
}

function add_query_arg( array $args, $base_uri ){
    $u = parse_url( $base_uri );
    if( isset($u['query']) ){
        parse_str( $u['query'], $query );
        $args += $query;
    }
    $uri = $u['scheme'].'://'.$u['host'].$u['path'];
    $args and $uri .= '?'.http_build_query($args);
    return $uri;
}

function esc_html( $text ){
    return htmlspecialchars( $text, ENT_COMPAT, 'UTF-8' );
}

function add_action(){
    // noop    
}

function add_filter(){
    // noop    
}

function apply_filters( $hook, $value = null ){
    return $value;
}

function get_locale(){
    return 'en_US';
}

function __($t){
    return $t;
}

define('WP_DEBUG', true );

require __DIR__.'/../../loco.php';

loco_require( 'loco-boot','loco-admin','loco-locales','loco-packages','build/gettext-compiled','build/shell-compiled' );