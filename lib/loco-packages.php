<?php
/**
 * Object representing a theme, plugin or domain within core code.
 * Packages are identified uniquely by a type (e.g. "theme") and internal wordpress name, e.g. "loco-translate".
 */
class LocoPackage {
    
    /**
     * Internal identifier, could be name, or path, or anything in future
     * @var string
     */    
    private $handle;
    
    /**
     * Default text domain, e.g. "loco"
     * @var string
     */    
    private $domain;
    
    /**
     * Nice descriptive name, e.g. "Loco Translate"
     * @var string
     */    
    private $name;
    
    /**
     * Locales with available translations
     * @var array 
     */    
    private $locales = array();     
    
    /**
     * POT files, per domain
     * @var array
     */            
    private $pot = array();
    
    /**
     * PO files, per domain, per locale
     * @var array
     */    
    private $po = array();
    
    /**
     * Paths under which there may be source code in any of our domains
     * @var array
     */    
    private $src = array();    
    
    /**
     * @var int
     */    
    private $mtime = 0;
    
    /**
     * number of PO or POT files present
     * @var int
     */    
    private $nfiles = 0;

    /**
     * Cached meta data 
     * @var array
     */    
    private $_meta;    

    /**
     * Construct package from name, root and domain
     */    
    protected function __construct( $name_or_path, $domain, $name ){
        $this->handle = $name_or_path;
        $this->domain = $domain;
        $this->name = $name or $this->name = $domain;
    }   
    
    /**
     * Get package type, defaults to 'core'
     */
    public function get_type(){
        return 'core';
    }    
    
    /**
     * Get identifying pair of arguments for fetching this object
     * @return array
     */
    public function get_query(){
        return array (
            'name' => $this->handle,
            'type' => $this->get_type(),
        );        
    }    
    
    
    /**
     * Get default text domain
     */
    public function get_domain(){
        return $this->domain;
    }    
    
    /**
     * Get time most recent PO/POT file was updated
     */
    public function get_modified(){
        return $this->mtime;
    }    
    

    /**
     * Add PO or POT file and set modified state
     */
    private function add_file( $path ){
        if( filesize($path) ){
            $this->mtime = max( $this->mtime, filemtime($path) );
            $this->nfiles++;
            return true;
        }
    }     

    
    /**
     * Add multiple locations from found PO and POT files
     * @return LocoPackage
     */
    public function add_po( array $files, $domain = '' ){
        if( isset($files['pot']) && is_array($files['pot']) ){
            foreach( $files['pot'] as $path ){
                $domain or $domain = LocoAdmin::resolve_file_domain($path) or $domain = $this->domain;
                $this->add_file($path) and $this->pot[$domain] = $path;
            }
        }
        if( isset($files['po']) && is_array($files['po']) ){
            foreach( $files['po'] as $path ){
                $domain or $domain = LocoAdmin::resolve_file_domain($path) or $domain = $this->domain;
                $locale = LocoAdmin::resolve_file_locale($path);
                $code = $locale->get_code() or $code = 'xx_XX';
                $this->add_file($path) and $this->po[ $domain ][ $code ] = $path;
            }
        }
        return $this;
    }    
    
    
    /**
     * Add a location under which there may be PHP source files for one or more of our domains
     * @return LocoPackage
     */        
    public function add_source( $path ){
        $this->src[] = $path;
        return $this;
    }    
    
    
    /**
     * Get most likely intended language folder
     */    
    public function lang_dir(){
        foreach( $this->pot as $path ){
            return dirname($path);
        }
        foreach( $this->po as $paths ){
            foreach( $paths as $path ){
                return dirname($path);
            }
        }
        foreach( $this->src as $path ){
            return dirname($path).'/languages';
        }
        return WP_LANG_DIR;
    }
    
    
    /**
     * Get root of package
     */
    public function get_root(){
        foreach( $this->src as $path ){
            return $path;
        }
        return WP_LANG_DIR;        
    }   
     
    
    /**
     * Check PO/POT paths are writable.
     * Called when generating root list view for simple error indicators.
     */    
    public function check_paths(){
        foreach( $this->pot as $path ){
            if( ! is_writable($path) ){
                throw new Exception( Loco::__('Some files not writable') );
            }
        }
        foreach( $this->po as $paths ){
            foreach( $paths as $path ){
                if( ! is_writable($path) ){
                    throw new Exception( Loco::__('Some files not writable') );
                }
            }
        }
        $dir = $this->lang_dir();
        if( ! is_writable($dir) ){
            throw new Exception( sprintf( Loco::__('"%s" folder not writable'), basename($dir) ) );
        }
    }    
    
    
    /**
     * Fetch POT file for given, or default domain
     * @return string
     */    
    public function get_pot( $domain = '' ){
        if( ! $domain ){
            $domain = $this->domain;
        }
        return isset($this->pot[$domain]) ? $this->pot[$domain] : '';
    }    
    
    
    /**
     * Fetch PO paths indexed by locale for given, or default domain
     * @return array
     */
    public function get_po( $domain = '' ){
        if( ! $domain ){
            $domain = $this->domain;
        }
        return isset($this->po[$domain]) ? $this->po[$domain] : array();
    }
    

    /**
     * Find all source files, currently only PHP
     */    
    public function get_source_files(){
        $found = array();
        foreach( $this->src as $dir ){
            foreach( LocoAdmin::find_php($dir) as $path ){
                $found[] = $path;
            }
        }
        return $found;
    }    
    
    
    /**
     * Get all source root directories
     */
    public function get_source_dirs( $relative_to = '' ){
        if( ! $relative_to ){
            return $this->src;
        }
        // calculate path from location of given file (which may not exist)
        if( pathinfo($relative_to,PATHINFO_EXTENSION) ){
            $relative_to = dirname($relative_to);
        }
        $dirs = array();
        foreach( $this->src as $target_dir ){
            $dirs[] = loco_relative_path( $relative_to, $target_dir );
        }
        return $dirs;
    }
    
    
    
    /**
     * Export meta data used by templates.
     * @return array
     */
    public function meta(){
        if( ! is_array($this->_meta) ){
            $pot = $po = array();
            foreach( $this->pot as $domain => $path ){
                $pot[] = compact('domain','path');
            }
            // get progress and locale for each PO file
            foreach( $this->po as $domain => $locales ){
                foreach( $locales as $code => $path ){
                    try {
                        unset($headers);    
                        $export = LocoAdmin::parse_po_with_headers( $path, $headers );
                        $po[] = array (
                            'path'   => $path,
                            'domain' => $domain,
                            'name'   => str_replace( array('.po',$domain), array('',''), basename($path) ),
                            'stats'  => loco_po_stats( $export ),
                            'length' => count( $export ),
                            'locale' => loco_locale_resolve($code),
                        );
                    }
                    catch( Exception $Ex ){
                        continue;
                    }
                }
            }
            $this->_meta = compact('po','pot') + array(
                'name' => $this->name,
                'root' => $this->get_root(),
                'domain' => $this->domain,
            );
        }
        return $this->_meta;
    }    



    /**
     * Clear this package from the cache. Called to invalidate when something updates
     * @return LocoPackage
     */
    public function uncache(){
        $key = $this->get_type().'_'.$this->handle;
        Loco::uncache( $key );
        $this->_meta = null;
        return $this;
    }



    /**
     * construct package object from theme
     * @return LocoPackage
     */
    private static function get_theme( $handle ){
        $theme = wp_get_theme( $handle );
        if( $theme && $theme->exists() ){
            $domain = $theme->get('TextDomain') or $domain = $handle;
            $package = new LocoThemePackage( $handle, $domain, $theme->get('Name') );
            $root = $theme->get_theme_root().'/'.$handle;
            $package->add_source( $root );
            // add PO and POT under theme root
            if( $files = LocoAdmin::find_po($root) ){
                $package->add_po( $files, $domain );
            }
            // find additional theme PO under WP_LANG_DIR
            $pattern = WP_LANG_DIR.'/themes/'.$domain.'{-*.po,.pot}';
            $files = LocoAdmin::find_grouped( $pattern, GLOB_NOSORT|GLOB_BRACE ) and
            $package->add_po( $files );
            return $package;
        }
    }    
    
    
    /**
     * Construct package object from plugin array
     * note that handle is file path for plugins in Wordpress
     */
    private static function get_plugin( $handle ){
        $plugins = get_plugins();
        if( isset($plugins[$handle]) && is_array($plugins[$handle]) ){
            $plugin = $plugins[$handle];
            $domain = $plugin['TextDomain'] or $domain = str_replace('/','-',dirname($handle));
            $package = new LocoPluginPackage( $handle, $domain, $plugin['Name'] );
            $root = WP_PLUGIN_DIR.'/'.dirname($handle);
            $package->add_source( $root );
            // add PO and POT under plugin root
            if( $files = LocoAdmin::find_po($root) ){
                $package->add_po( $files, $domain );
            }
            // find additional plugin PO under WP_LANG_DIR
            $pattern = WP_LANG_DIR.'/plugins/'.$domain.'{-*.po,.pot}';
            $files = LocoAdmin::find_grouped( $pattern, GLOB_NOSORT|GLOB_BRACE ) and
            $package->add_po( $files );
            return $package;
        }
    }
    
    
    /**
     * construct a core package object from name
     */
    private static function get_core( $handle ){
        /*
        $files = LocoAdmin::pop_lang_dir($domain);
        if( $files['po'] || $files['pot'] ){
            $package = new LocoPackage( $domain, $handle );
            $package->add_po( $files );
            //
            Loco::cache( $key, $package );
            return $package;
        }
        */
    }
    
    
    
    /**
     * Get a package - from cache if possible
     * @param string unique name or identifier known to Wordpress
     * @param string "core", "theme" or "plugin"
     * @return LocoPackage
     */
    public static function get( $handle, $type ){
        $key = $type.'_'.$handle;
        $package = Loco::cached($key);
        if( ! $package instanceof LocoPackage ){
            $getter = array( __CLASS__, 'get_'.$type );
            $package = call_user_func( $getter, $handle );
            if( $package ){
                // @todo init meta
                Loco::cache( $key, $package );
            }
        }
        return $package;
    }    
    
    
    
    /**
     * @internal
     */
    private static function _sort_modified( LocoPackage $a, LocoPackage $b ){
        $a = $a->get_modified();
        $b = $b->get_modified();
        if( $a > $b ){
            return -1;
        }
        if( $b > $a ){
            return 1;
        }
        return 0;
    }      
    
    
    /**
     * Sort packages according to most recently updated language files
     */    
    public static function sort_modified( array $packages ){
        static $sorter = array( __CLASS__, '_sort_modified' );
        usort( $packages, $sorter );        
        return $packages;
    }    
    
    
    
}


/**
 * Extended package class for themes
 */
class LocoThemePackage extends LocoPackage {
    public function get_type(){
        return 'theme';
    }      
}


/**
 * Extended package class for plugins
 */
class LocoPluginPackage extends LocoPackage {
    public function get_type(){
        return 'plugin';
    }      
}

