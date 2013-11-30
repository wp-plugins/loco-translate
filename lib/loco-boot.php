<?php
/**
 * Loco | bootstraps plugin when it's needed.
 * Top-level Loco class holds some basic utilities
 */ 
abstract class Loco {

    /** plugin namespace */
    const NS = 'loco-translate';
    
    const VERSION = '1.2.3';
    const CAPABILITY = 'manage_options';
    
    /* whether to enable APC cache */
    public static $apc_enabled;

    /* call Wordpress __ with our text domain  */
    public static function __( $msgid = '' ){
        return __( $msgid, self::NS );
    }

    /* call Wordpress _n with our text domain  */
    public static function _n( $msgid = '', $msgid_plural = '', $n = 0 ){
        return _n( $msgid, $msgid_plural, $n, self::NS );
    }

    /* call Wordpress _x with our text domain  */
    public static function _x( $msgid = '', $msgctxt = '', $n = 0 ){
        return _x( $msgid, $msgctxt, self::NS );
    }
    
    
    /**
     * Bootstrap localisation of self
     */
    public static function load_textdomain(){
        $locale = get_locale();
        if( 0 === strpos($locale,'en') ){
            return;
        }
        // see if MO file exists with exact name
        $mopath = loco_basedir().'/languages/'.Loco::NS.'-'.$locale.'.mo';
        if( ! file_exists($mopath) ){
            // Try some locale sanitization to find suitable MO file.
            function_exists('loco_locale_resolve') or loco_require('loco-locales');
            $locale = loco_locale_resolve($locale)->get_code();
            $mopath = loco_basedir().'/languages/'.Loco::NS.'-'.$locale.'.mo';
            if( ! file_exists($mopath) ){
                // translations really not found - check PO is compiled to MO
                return;
            }
        }
        load_textdomain( Loco::NS, $mopath );
    }
    
    
    /**
     * Get plugin local base directory in case __DIR__ isn't available (php<5.3)
     */
    public static function basedir(){
        static $dir;
        isset($dir) or $dir = dirname(__FILE__);
        return $dir;    
    }
    
    
    /**
     * Get plugin base URL path.
     */
    public static function baseurl(){
        static $url;
        if( ! isset($url) ){
            $here = __FILE__;
            if( 0 !== strpos( WP_PLUGIN_DIR, $here ) ){
                // something along this path has been symlinked into the document tree
                // temporary measure assumes name of plugin folder is unchanged.
                $here = WP_PLUGIN_DIR.'/'.Loco::NS.'/loco.php';
            }
            $url = plugins_url( '', $here );
        }
        return $url;
    }
    

    /**
     * Simple template renderer
     */
    public static function render( $tpl, array $arguments = array() ){
        extract( $arguments );
        include loco_basedir().'/tpl/'.$tpl.'.tpl.php';
    }

 
    /**
     * replacement for bloated esc_html function
     */ 
    public static function html( $text ){
        return htmlspecialchars( $text, ENT_COMPAT, 'UTF-8' );
    }
    
    
    /**
     * html output printer with printf built-in
     */
    public static function h( $text, $_ = null ){
        if( isset($_) ){
            $args = func_get_args();
            $text = call_user_func_array('sprintf', $args );
        }
        echo self::html( $text );
        return '';
    }    
    
    
    /**
     * Abstract enquement of JavaScript
     */
    public static function enqueue_scripts(){
        static $v, $i = 0;
        $stubs = func_get_args();
        if( ! isset($v) ){
            $v = WP_DEBUG ? time() : Loco::VERSION;
            // @todo enqueue JavaScript translations here
        }
        foreach( $stubs as $stub ){
            $js = Loco::baseurl().'/pub/js/'.$stub.'.js';
            $id = self::NS.'-js-'.( ++$i );
            wp_enqueue_script( $id, $js, array('jquery'), $v, true );
        }
    }
    
    
    
    /**
     * Abstract enquement of Stylesheets
     */
    public static function enqueue_styles(){
        static $v, $i = 0;
        isset($v) or $v = WP_DEBUG ? time() : Loco::VERSION;
        foreach( func_get_args() as $stub ){
            $css = Loco::baseurl().'/pub/css/'.$stub.'.css';
            wp_enqueue_style( self::NS.'-css-'.(++$i), $css, array(), $v );
        }
    }
    
    
    
    /**
     * 
     */
    public static function utm_query( $utm_medium = 'wp', $utm_campaign = 'wp' ){
        static $utm_source, $utm_content;
        if( ! isset($utm_source) ){
            $utm_source = parse_url( get_bloginfo('url'), PHP_URL_HOST ) or $utm_source = $_SERVER['HTTP_HOST'];
            $utm_content = Loco::NS.'-'.Loco::VERSION;
        }
        return http_build_query( compact('utm_campaign','utm_medium','utm_content','utm_source') );
    }
    
    
    
    /**
     * Get actual postdata, not hacked postdata Wordpress ruined with wp_magic_quotes
     * @return array
     */
    public static function postdata(){
        static $post;
        if( ! is_array($post) ){
            // Not using Wordpress's hacked POST collection.
            $str = file_get_contents('php://input') or 
            // preferred way is to parse original data
            $str = isset($_SERVER['HTTP_RAW_POST_DATA']) ? $_SERVER['HTTP_RAW_POST_DATA'] : '';
            if( $str ){
                parse_str( $str, $post );
            }
            // fall back to undoing Wordpress 'magic'
            else {
                $post = stripslashes_deep( $_POST );
            }
        }
        return $post;
    }    
    
    
    
    /**
     * Abstraction of cache retrieval, using apc where possible
     * @return mixed 
     */
    public static function cached( $key ){
        $key = self::cache_key($key);
        if( self::$apc_enabled ){
            return apc_fetch( $key );
        }
        return get_transient( $key );
    } 



    /**
     * Abstraction of cache storage, using apc where possible
     * @return void
     */
     public static function cache( $key, $value, $ttl = 0 ){
        $key = self::cache_key($key);
        if( self::$apc_enabled ){
            apc_store( $key, $value, $ttl );
            return;
        }
        if( ! $ttl ){
            // WP would expire immediately as opposed to never
            $ttl = 31536000;
        }
        set_transient( $key, $value, $ttl );
    }    

     
     
    /**
     * Sanitize a cache key
     */    
    private static function cache_key( $key ){
        $key = 'loco_'.preg_replace('/[^a-z]+/','_', strtolower($key) );
        if( isset($key{45}) ){
            $key = 'loco_'.md5($key);
        }        
        return $key;
    }
    
    
    /**
     * Plugin option getter/setter
     */
    public static function config( array $update = array() ){
        static $conf;
        if( ! isset($conf) ){
            $conf = array (
                'which_msgfmt' => '/usr/bin/msgfmt',
            );
            foreach( $conf as $key => $val ){
                $conf[$key] = get_option( Loco::NS.'-'.$key);
                if( empty($conf[$key]) && ! is_string($conf[$key]) ){
                    $conf[$key] = $val;
                }
            }
        }
        foreach( $update as $key => $val ){
            if( isset($conf[$key]) ){
                update_option( Loco::NS.'-'.$key, $val );
                $conf[$key] = $val;
            }
        }
        return $conf;
    }    
}




// minimum config
Loco::$apc_enabled = function_exists('apc_fetch') && ini_get('apc.enabled');
Loco::load_textdomain();



