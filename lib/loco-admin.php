<?php
/**
 * Loco admin
 */
abstract class LocoAdmin { 

    /**
     * Print error
     */
    public static function error( $message, $label = '' ){
        if( defined('DOING_AJAX') && DOING_AJAX ){
            throw new Exception( $message );
        }
        // Translators: Bold text label in admin error messages
        $label or $label = _x('Error','Message label');
        echo '<div class="loco-message error loco-error"><p><strong>',$label,':</strong> ',Loco::html($message),'</p></div>';
    }
    
    
    /**
     * Print warning notice
     */
    public static function warning( $message, $label = '' ){
        $label or $label = _x('Warning','Message label');
        echo '<div class="loco-message updated loco-warning"><p><strong>',$label,':</strong> ',Loco::html($message),'</p></div>';
    }
    
    
    /**
     * Print success
     */
    public static function success( $message, $label = '' ){
        $label or $label = _x('OK','Message label');
        echo '<div class="loco-message updated loco-success"><p><strong>',$label,':</strong> ',Loco::html($message),'</p></div>';
    }
    
    
      
    /**
     * Main admin page render call
     */
    public static function render_page(){
        do {
            try {
                
                // libs required for all admin pages
                loco_require('loco-locales');
                
                // most actions define a package root directory.
                $root = isset($_GET['root']) ? self::resolve_path( $_GET['root'], true ) : '';


                // Extract messages if 'xgettext' contains a valid package directory
                //
                if( isset($_GET['xgettext']) && $root ){
                    $name = basename($root);
                    $files = self::find_po( $root );
                    $pot_path = current($files['pot']);
                    // extract from all PHP source files
                    $export = self::xgettext( $root );
                    // POT could already exist if we're regenerating
                    if( $pot_path ){
                        // @todo: should we merge with existing?
                    }
                    // if creating a new POT file we will guess likely location
                    else {
                        $dir = $files['po'] ? dirname( current($files['po']) ) : $root.'/languages';
                        $pot_path = $dir.'/'.$name.'.pot';
                    }
                    self::render_poeditor( $root, $pot_path, $export );
                    break;
                }


                // Initialize a new PO file if 'msginit' contains a valid package directory
                //
                if( isset($_GET['msginit']) && $root ){
                    // handle PO file creation if locale is set
                    if( isset($_GET['custom-locale']) ){
                        try {
                            $locale = $_GET['custom-locale'] or $locale = $_GET['common-locale'];
                            $po_path = self::msginit( $root, $locale, $export );
                            if( $po_path ){
                                self::render_poeditor( $root, $po_path, $export );
                                break;
                            }
                        }
                        catch( Exception $Ex ){
                            // fall through to msginit screen with error
                            self::error( $Ex->getMessage() );
                        }
                    }    
                    // else do a dry run to pre-empt failures
                    else {
                        $dummy = self::msginit( $root, 'en', $export );
                    }
                    // else render msginit start screen
                    // @todo list available locales in drop down?
                    $title = Loco::__('New PO file');
                    $locales = loco_require('build/locales-compiled');
                    Loco::enqueue_scripts('admin-poinit');
                    Loco::render('admin-poinit', compact('root','title','locales') );
                    break;
                }


                // Render existing file in editor if 'poedit' contains a valid file path
                //
                if( isset($_GET['poedit']) && $po_path = self::resolve_path( $root.'/'.$_GET['poedit'] ) ){
                    $export = self::parse_po( $po_path );
                    self::render_poeditor( $root, $po_path, $export );
                    break;
                }
                
                
                
            }
            catch( Exception $Ex ){
                self::error( $Ex->getMessage() );
            }
            
            // default screen renders root page with available themes and plugins to translate
            $themes  = array();
            $plugins = array();
            // @var $theme WP_Theme;
            foreach( wp_get_themes( array( 'allowed' => true ) ) as $name => $theme ){
                $root = $theme->get_theme_root().'/'.$name;
                $name = $theme->get('Name');
                $themes[] = self::init_package_args( $root, $name, 'theme' );
            }
            // @var $plugin array
            foreach( get_plugins() as $subpath => $plugin ){
                $root = WP_PLUGIN_DIR.'/'.dirname($subpath);
                $name = $plugin['Name'];
                $plugins[] = self::init_package_args( $root, $name, 'plugin' );
            }
            // order most active first
            $sorter = array( __CLASS__, 'sort_packages' );
            usort( $themes, $sorter );
            usort( $plugins, $sorter );
            // upgrade notice
            $update = '';
            if( $updates = get_site_transient('update_plugins') ){
                $key = Loco::NS.'/loco.php';
                if( isset($updates->checked[$key]) && 1 === version_compare( $updates->checked[$key], Loco::VERSION ) ){
                    $update = $updates->checked[$key];
                }
            }
            Loco::render('admin-root', compact('themes','plugins','update') );
        }
        while( false );
    } 
    
    
    
    
    /**
     * initialize template arguments for a plugin or theme table row
     * @return array
     */
    private function init_package_args( $root, $name, $type ){
        $files = self::find_po( $root );
        // filesystem warning. Only want one though
        $warnings = array();
        foreach( $files as $ext => $paths ){
            foreach( $paths as $path ){
                $dir = dirname($path);
                if( ! is_writable($path) ){
                    $warnings[] = Loco::__('Some files not writable');
                    break 2;
                }
            }
        }
        if( ! $warnings ){
            if( ! isset($dir) ){
                $dir = $root.'/languages';
            }
            if( ! is_writable($dir) ){
                $warnings[] = sprintf( Loco::__('"%s" folder not writable'), basename($dir) );
            }
        }
        // find newest file in package to establish cache invalidation
        $mtime = self::newest_mtime_recursive( $files['po'], $files['pot'] );
        // get meta data or re-generate meta data from files
        $mkey = $type.'_'.$name;
        $meta = Loco::cached( $mkey );
        if( ! $meta || $mtime > $meta['mtime'] || Loco::VERSION !== $meta['v'] ){
            $pot = $po = array();
            foreach( $files['pot'] as $pot_path ){
                $pot[] = array (
                    'path' => $pot_path,
                );
            }
            // get progress and locale for each PO file
            foreach( $files['po'] as $po_path ){
                try {
                    unset($headers);    
                    $export = self::parse_po_with_headers( $po_path, $headers );
                    $stats  = loco_po_stats( $export );
                }
                catch( Exception $Ex ){
                    // self::warning( $Ex->getMessage() );
                    continue;
                }
                $po[] = array (
                    'path'   => $po_path,
                    'name'   => str_replace( array('.po',$name), array('',''), basename($po_path) ),
                    'stats'  => $stats,
                    'status' => self::format_progress_summary($stats),
                    'length' => count( $export ),
                    'locale' => LocoAdmin::resolve_file_locale($po_path),
                );
            }
            $meta = compact('mtime','po','pot');
            $meta['v'] = Loco::VERSION;
            Loco::cache( $mkey, $meta );
        }
        return $meta + compact('root','warnings','name');
    }    
    

    
    /**
     * Sort packages according to most recently updated language files
     */
    private static function sort_packages( array $a, array $b ){
        if( $a['mtime'] > $b['mtime'] ){
            return -1;
        }
        if( $b['mtime'] > $a['mtime'] ){
            return 1;
        }
        return 0;
    }    



    /**
     * utility gets newest file modification from an array of files
     */
    private static function newest_mtime_recursive( array $files ){
        $mtime = 0;    
        foreach( func_get_args() as $files ){
            foreach( $files as $path ){
                $mtime = max( $mtime, filemtime($path) );
            }
        }
        return $mtime;
    }    
    
    
    
    /**
     * Initialize a new PO file from a locale code
     * @return string path where PO file will be saved to
     */
    private static function msginit( $root, $code, &$export ){

        $locale = loco_locale_resolve( $code );
        if( ! $locale ){
            throw new Exception( Loco::__('You must specify a valid locale for a new PO file') );
        }
        
        // extract POT if possible, falling back to source code if empty
        $pot_path = '';
        $export = array();
        $files = self::find_po( $root );
        foreach( $files['pot'] as $pot_path ){
            $pot = self::parse_po( $pot_path );    
            if( $pot && ! ( 1 === count($pot) && '' === $pot[0]['source'] ) ){
                $export = $pot;
                break;
            }
        }
        if( ! $export ){
            $export = self::xgettext( $root );
            if( ! $export ){
                throw new Exception( Loco::__('No translatable strings found').'. '.Loco::__('Cannot create a PO file.') );
            }
        }

        // decide on location for PO file and check it does't exist already
        $po_name = $po_dir = '';
        foreach( $files['po'] as $po_path ){
            if( $existing = self::resolve_file_locale( $po_path ) ){
                if( $locale->equal_to($existing) ){
                    throw new Exception( sprintf(Loco::__('PO file already exists with locale %s'), $existing->get_code() ) );
                }
                // attempt to name new file according to this one
                if( ! $po_name ){
                    $pattern = '/(^|[^a-z])'.$existing->preg().'([^a-z]|$)/i';
                    $basename = preg_replace( $pattern, '\\1'.$locale->get_code().'\\2', basename($po_path), 1, $count );
                    if( $count ){
                        $po_name = $basename;
                        $po_dir  = dirname($po_path);
                    }
                }
            }
        }

        // ok to create PO file, but we may not have a suitable name
        if( ! $po_name ){
            if( $pot_path ){
                $po_dir = dirname($pot_path);
                $po_name = str_replace( '.pot', '-'.$locale->get_code().'.po', basename($pot_path) );
            }
            // with no POT file and no PO files, we'll place it in the default location
            else {
                $po_dir = $root.'/languages';
                $po_name = $locale->get_code().'.po';
            }
        }
        
        // return path, export is set as reference
        return $po_dir.'/'.$po_name;
    }     
    
    
    
    
    
    /**
     * Render poedit screen
     * @param string optional package root directory
     * @param string PO or PO file path
     * @param array data to load into editor
     */
    private static function render_poeditor( $root, $path, array $data ){
        $pot = $po = $locale = null;
        $warnings = array();
        // remove header and check if empty
        $minlength = 1;
        if( isset($data[0]) && $data[0]['source'] === '' ){
            $data[0] = array();
            $minlength = 2;
        }
        // template file is developer-editable and has no locale
        $ispot = self::is_pot($path);
        if( $ispot ){
            $pot = $data;
            $type = 'POT';
        }
        // else PO is locked and has a locale
        else {
            $po = $data;
            $type = 'PO';
            $locale = self::resolve_file_locale($path);
            $haspot = self::find_pot( $root ) and $haspot = current($haspot);
        }
        // path may not exist if we're creating a new one
        if( file_exists($path) ){
            $modified = self::format_datetime( filemtime($path) );
        }
        else {
            $modified = 0;
        }
        // warn if new file can't be written
        $writable = self::is_writable( $path );
        if( ! $writable && ! $modified ){
            //$message = $modified ? Loco::__('File cannot be saved to disk automatically'): Loco::__('File cannot be created automatically');
            //$warnings[] = $message.'. '.sprintf(Loco::__('Fix the file permissions on %s'),$path);
            $warnings[] = Loco::__('File cannot be created automatically. Fix the file permissions or use Download instead of Save');
        }
        
        // Warnings if file is empty
        if( count($data) < $minlength ){
            $lines = array();
            if( $ispot ){
                if( $modified ){
                    // existing POT, may need sync
                    $lines[] = sprintf( Loco::__('%s file is empty'), 'POT' );
                    $lines[] = Loco::__('Run Sync to update from source code');
                }
                else {
                    // new POT, would have tried to extract from source. Fine you can add by hand
                    $lines[] = Loco::__('No strings could be extracted from source code');
                }
            }
            else if( $modified ){
                $lines[] = sprintf( Loco::__('%s file is empty'), 'PO' );
                if( $haspot ){
                    // existing PO that might be updatable from POT
                    $lines[] = sprintf( Loco::__('Run Sync to update from %s'), basename($haspot) );
                }
                else {
                    // existing PO that might be updatable from sources
                    $lines[] = Loco::__('Run Sync to update from source code');
                }
            }
            else {
                // this shouldn't happen if we throw an error during msginit
                throw new Exception( Loco::__('No translatable strings found') );
            }
            $warnings[] = implode('. ', $lines );
        }

        // warning if file needs syncing
        else if( $modified ){
            if( $ispot ){
                if( filemtime($path) < self::newest_mtime_recursive( self::find_php($root) ) ){
                    $warnings[] = Loco::__('Source code has changed, run Sync to update POT');
                }
            }
            else if( $haspot && filemtime($haspot) > filemtime($path) ){
                $warnings[] = Loco::__('POT has changed since PO file was saved, run Sync to update');
            }
        }

        // no longer need the full local paths
        $path = self::trim_path( $path );
        $root = self::trim_path( $root );
        $name = basename( $path );

        Loco::enqueue_scripts('build/admin-poedit');
        Loco::render('admin-poedit', compact('root','path','file','po','pot','locale','name','type','modified','writable','warnings') );
        return true;
    }
    
    
    
    /**
     * test if a file path is a POT (template) file
     */
    public static function is_pot( $path ){
        return 'pot' === strtolower( pathinfo($path,PATHINFO_EXTENSION) );
    }
    
    
    
    /**
     * resolve file path that may be relative to wp-content
     */
    public static function resolve_path( $path, $isdir = false ){
        if( $path && '/' !== $path{0} ){
            $path = WP_CONTENT_DIR.'/'.$path;
        }
        $realpath = realpath( $path );
        if( ! $realpath || ! is_readable($realpath) || ( $isdir && ! is_dir($realpath) ) || ( ! $isdir && ! is_file($realpath) ) ){
            self::error( Loco::__('Bad file path').' '.var_export($path,1) );
            return '';
        }
        return $realpath;
    }
    
    
    
    /**
     * Establish root of package (theme/plugin) that contains this file. It doesn't have to exist
     *
    private static function resolve_file_package( $path ){
        return 'todo';
    }*/     
    
    
    
    
    /**
     * remove wp-content from path for more compact display in urls and such
     */
    private static function trim_path( $path ){
        return str_replace( WP_CONTENT_DIR.'/', '', $path );
    }    
    
    
    
    /**
     * Test whether a file can be written to, whether it exists or not
     */
    public static function is_writable( $path ){
        // if file exists it must be writable itself:
        if( file_exists($path) ){
            return is_writable($path);
        }
        // else file must be created, which may mean recursive directory permissions
        $dir = dirname( $path );
        return is_dir($dir) && is_writable($dir);
    }
    
    
    
    
    
    /**
     * Recursively find all PO and POT files anywhere under a directory
     */
    public static function find_po( $dir ){
        return self::find( $dir, array('po','pot') );
    }

    
    /**
     * Recursively find all POT files anywhere under a directory
     */
    public static function find_pot( $dir ){
        $files = self::find( $dir, array('pot') );
        return $files['pot'];
    }
    
    
    
    /**
     * Recursively find all PHP source files anywhere under a directory
     */
    public static function find_php( $dir ){
        $files = self::find( $dir, array('php') );
        return $files['php'];
    }
    
    
    
    /**
     * Recursively find files of any given extensions
     */
    private static function find( $dir, array $exts ){
        $options = 0;
        $found = array_fill_keys( $exts, array() );
        $exts = implode(',',$exts);
        if( isset($exts[1]) ){
            $options |= GLOB_BRACE;
            $exts = '{'.$exts.'}';
        }
        return self::find_recursive( $dir, '/*.'.$exts, $options, $found );
    }
    
    
    
    /**
     * @internal
     */
    private static function find_recursive( $dir, $pattern, $options, array $found ){
        $files = glob( $dir.$pattern, GLOB_NOSORT|$options );
        if( is_array($files) ){
            foreach( $files as $path ){
                $ext = strtolower( pathinfo($path,PATHINFO_EXTENSION ) );
                $found[$ext][] = $path;
            }
        }
        // recurse
        $sub = glob( $dir.'/*', GLOB_ONLYDIR|GLOB_NOSORT );
        if( is_array($sub) ){
            foreach( $sub as $dir ){
                $found = self::find_recursive( $dir, $pattern, $options, $found );
            }
        }
        return $found;
    }
    
    
    
    /**
     * Perform xgettext style extraction from PHP source files
     * @todo JavaScript files too
     * @return array Loco's internal array format
     */
    public static function xgettext( $dir ){
        class_exists('LocoPHPExtractor') or loco_require('build/gettext-compiled');
        $extractor = new LocoPHPExtractor;
        $export = array();
        foreach( self::find_php($dir) as $path ){
            $source = file_get_contents($path) and
            $tokens = token_get_all($source) and
            $export = $extractor->extract( $tokens );
        }
        return $export;
    }
    
    
    /**
     * Parse PO or POT file
     */
    public static function parse_po( $path ){
        function_exists('loco_parse_po') or loco_require('build/gettext-compiled');
        $export = array();
        $source = file_get_contents($path) and
        $export = loco_parse_po( file_get_contents($path) );
        return $export;
    }
    
    
    
    /**
     * Parse PO or POT file, placing header object into argument
     */
    private static function parse_po_with_headers( $path, &$headers ){
        $export = self::parse_po( $path );
        if( ! isset($export[0]) ){
            throw new Exception('Empty or invalid PO file');
        }
        if( $export[0]['source'] !== '' ){
            throw new Exception('PO file has no header');
        }
        $headers = loco_parse_po_headers( $export[0]['target'] );
        $export[0] = array(); // <- avoid index errors as json
        return $export;
    }
    
    
    
    /**
     * Resolve a list of PO file paths to locale instances
     */
    private static function resolve_file_locales( array $files ){
        $locales = array();
        foreach( $files as $key => $path ){
            $locale = self::resolve_file_locale( $path );            
            $locales[$key] = $locale;
        }
        return $locales;
    }
    
    
    
    /**
     * Resolve a PO file path or file name to a locale.
     * Note that this does not read the file and the PO header, but perhaps it should. (performance!)
     * @return LocoLocale
     */
    public static function resolve_file_locale( $path ){
        $stub = str_replace( '.po', '', basename($path) );
        $locale = loco_locale_resolve($stub);
        return $locale;
    }
    
    
     
    /**
     * Generate an admin page URI with custom args
     */
    public static function uri( array $args = array() ){
        static $base_uri;
        if( ! isset($base_uri) ){
            $snip = 'page='.Loco::NS;
            $base_uri = current( explode($snip,$_SERVER['REQUEST_URI']) ).$snip;
        }
        if( ! $args ){
            return $base_uri;
        }
        return $base_uri.'&'.http_build_query( $args );
    }
    
    
    
    /**
     * Test if we're on our own admin page
     */
    public static function is_self(){
        static $bool;
        return isset($bool) ? $bool : ( $bool = false !== strpos($_SERVER['REQUEST_URI'], '?page='.Loco::NS ) );
    }
    
    
    
    /**
     * Generate a link to edit a po/pot file
     */
    public static function edit_link( $root, $path, $label = '' ){
        $url = self::uri( array(
            'root'   => self::trim_path( $root ),
            'poedit' => str_replace( $root.'/', '', $path ),
        ) );
        if( ! $label ){
            $label = basename( $path );
        }
        return '<a href="'.Loco::html($url).'">'.Loco::html($label).'</a>';
    }
    
    
    
    /**
     * Generate a link to edit/update a POT file
     */
    public static function xgettext_link( $root, $label ){
        $url = self::uri( array(
            'root'     => self::trim_path( $root ),
            'xgettext' => '1',
        ) );
        return '<a href="'.Loco::html($url).'">'.Loco::html($label).'</a>';
        
    }
    
    
    
    /**
     * Generate a link to create a new PO file
     */
    public static function msginit_link( $root, $label ){
        $url = self::uri( array(
            'root'    => self::trim_path( $root ),
            'msginit' => '1',
        ) );
        return '<a href="'.Loco::html($url).'">'.Loco::html($label).'</a>';
        
    }
    
     
     
    /**
     * Date format util
     */ 
    public static function format_datetime( $u ){
        static $tf, $df;
        if( ! $tf ){
            $tf = get_option('time_format') or $tf = 'g:i A';
            $df = get_option('date_format') or $df= 'M jS Y'; 
        }
        return date_i18n( $df.' '.$tf, $u ); 
    } 
    
    
    
    /**
     * PO translate progress summary
     */   
    public static function format_progress_summary( array $stats ){
        extract( $stats );
        $text = sprintf( Loco::__('%s%% translated'), $p ).', '.sprintf( Loco::_n('1 string', '%s strings', $t ), number_format($t) );
        $extra = array();
        if( $f ){
            $extra[] = sprintf( Loco::__('%s fuzzy'), number_format($f) );
        }   
        if( $u ){
            $extra[] = sprintf( Loco::__('%s unstranslated'), number_format($f) );
        }
        if( $extra ){
            $text .= ' ('.implode(', ',$extra).')';
        }
        return $text;
    }

}


    
    
    
// filter and action callbacks
 

/**
 * Admin init callback
 */
function _loco_hook__admin_init(){
    // @todo handle postdata?
}

  
/**
 * Enqueue only admin styles we need
 */  
function _loco_hook__admin_print_styles(){
    if( LocoAdmin::is_self() ){
        Loco::enqueue_styles('build/poedit-compiled','loco-admin');
    }
}  


/**
 * Admin menu registration callback
 */
function _loco_hook__admin_menu() {
    $page = array( 'LocoAdmin', 'render_page' );
    $hook = add_management_page( Loco::__('Loco, Translation Management'), Loco::__('Manage Translations'), LOCO::CAPABILITY, Loco::NS, $page );
    add_action('admin_print_styles', '_loco_hook__admin_print_styles' );
}


/**
 * extra visibility of settings link
 */
function _loco_hook__plugin_row_meta( $links, $file = '' ){
    if( false !== strpos($file,'/loco.php') ){
        $links[] = '<a href="tools.php?page='.Loco::NS.'"><strong>'.Loco::__('Manage translations').'</strong></a>';
    } 
    return $links;
}


/**
 * execute ajax actions
 */
function _lock_hook__wp_ajax(){
    extract( Loco::postdata() );
    if( isset($action) ){
        require loco_basedir().'/php/loco-ajax.php';
    }
}




add_action('admin_init', '_loco_hook__admin_init' );
add_action('admin_menu', '_loco_hook__admin_menu' );
add_action('plugin_row_meta', '_loco_hook__plugin_row_meta', 10, 2 );

// ajax hooks all going through one central function
add_action('wp_ajax_loco-posave', '_lock_hook__wp_ajax' );
add_action('wp_ajax_loco-posync', '_lock_hook__wp_ajax' );
