<?php
/*
Plugin Name: Loco Translate
Plugin URI: http://wordpress.org/extend/plugins/loco-translate
Description: Translate Wordpress plugins and themes directly in your browser
Author: Tim Whitlock
Version: 1.1.0
Author URI: http://localise.biz/
*/



/**
 * Get plugin local base directory in case __DIR__ isn't available (php<5.3)
 */
function loco_basedir(){
    static $dir;
    isset($dir) or $dir = dirname(__FILE__);
    return $dir;    
}



/** 
 * Include a component from lib subdirectory
 * @param string $subpath e.g. "loco-admin"
 * @return mixed value from last included file
 */
function loco_require(){
    $dir = loco_basedir();
    $ret = '';
    foreach( func_get_args() as $subpath ){
        $ret = require_once $dir.'/lib/'.$subpath.'.php';
    }
    return $ret;
} 



// Inialize admin screen
if( is_admin() ){
    loco_require('loco-boot','loco-admin');
}

// else fire up theme functionality for admins
else {
    add_action( 'after_setup_theme', 'loco_after_setup_theme' );
    function loco_after_setup_theme(){
        if( is_user_logged_in() ){
            loco_require('loco-boot');
            if( current_user_can(Loco::CAPABILITY) ){
                // @todo font end functionality
            }
        }
    }
}
