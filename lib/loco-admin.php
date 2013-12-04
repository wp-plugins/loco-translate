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
     * Admin settings page render call
     */
    public static function render_page_options(){
        // update applicaion settings if posted
        if( isset($_POST['loco']) && is_array( $update = $_POST['loco'] ) ){
            $args = Loco::config( $update );
            $args['success'] = Loco::__('Settings saved');
        }
        else {
            $args = Loco::config();
        }
        Loco::render('admin-opts', $args );
    }     
    
    
      
    /**
     * Admin tools page render call
     */
    public static function render_page_tools(){
        do {
            try {
                
                // libs required for all admin pages
                loco_require('loco-locales');
                
                // most actions define a package root directory.
                $root = isset($_GET['root']) ? self::resolve_path( $_GET['root'], true ) : '';


                // Extract messages if 'xgettext' is in query string
                //
                if( isset($_GET['xgettext']) && $root ){
                    $name = basename($root);
                    $files = self::find_po( $root );
                    $domain = $_GET['xgettext'] or $domain = preg_replace('/\W+/i','-',strtolower($name));
                    foreach( $files['pot'] as $pot_path ){
                        if( self::resolve_file_domain($pot_path) === $domain ){
                            throw new Exception('POT already exists at '.$pot_path );
                        }
                    }
                    // extract from all PHP source files
                    $export = self::xgettext( $root, $domain );
                    // Establish best/intended location for new POT file
                    foreach( $files['po'] as $po_path ){
                        if( self::resolve_file_domain($po_path) === $domain ){
                            $dir = dirname( $po_path );
                            break;
                        }
                    }
                    if( ! isset($dir) ){
                        $dir = $root;
                        if( 0 !== strpos($dir, WP_LANG_DIR) ){
                            $dir .= '/languages';
                        }
                    }
                    $pot_path = $dir.'/'.$domain.'.pot';
                    self::render_poeditor( $root, $pot_path, $export );
                    break;
                }


                // Initialize a new PO file if 'msginit' is in query string
                //
                if( isset($_GET['msginit']) && $root ){
                    $domain = $_GET['msginit'];
                    // handle PO file creation if locale is set
                    if( isset($_GET['custom-locale']) ){
                        try {
                            $locale = $_GET['custom-locale'] or $locale = $_GET['common-locale'];
                            $po_path = self::msginit( $root, $domain, $locale, $export, $head );
                            if( $po_path ){
                                self::render_poeditor( $root, $po_path, $export, $head );
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
                        $dummy = self::msginit( $root, $domain, 'en', $export, $head );
                    }
                    // else render msginit start screen
                    $title = Loco::__('New PO file');
                    $locales = loco_require('build/locales-compiled');
                    Loco::enqueue_scripts('admin-poinit');
                    Loco::render('admin-poinit', compact('root','domain','title','locales') );
                    break;
                }


                // Render existing file in editor if 'poedit' contains a valid file path relative to package root
                //
                if( isset($_GET['poedit']) && $po_path = self::resolve_path( $root.'/'.$_GET['poedit'] ) ){
                    $export = self::parse_po_with_headers( $po_path, $head );
                    self::render_poeditor( $root, $po_path, $export, $head );
                    break;
                }
                
                
                
            }
            catch( Exception $Ex ){
                self::error( $Ex->getMessage() );
            }
            
            // default screen renders root page with available themes and plugins to translate
    
            // @var WP_Theme $theme
            $themes = array();
            foreach( wp_get_themes( array( 'allowed' => true ) ) as $name => $theme ){
                $package = LocoPackage::get_theme( $name, $theme );
                $themes[] = self::init_package_args( $package, 'theme' );
            }
            // @var array $plugin
            $plugins = array();
            foreach( get_plugins() as $subpath => $plugin ){
                $name = dirname($subpath);
                $package = LocoPackage::get_plugin( $name, $plugin );
                $plugins[] = self::init_package_args( $package, 'plugin' );
            }
            // pick up remaining items under WP_LANG_DIR
            $core = array();
            $cores = array (
                //'admin-network'     => 'Network',
                'admin'             => 'Admin Network',
                'continents-cities' => 'Timezones',
                'ms'                => 'Multisite',
                ''                  => 'Other',
            );
            foreach( $cores as $domain => $name ){
                if( $package = LocoPackage::get_core( $domain, $name ) ){
                    $core[] = self::init_package_args( $package, 'core' );
                }
            }
            // order most active packges first in each set
            $sorter = array( __CLASS__, 'sort_packages' );
            usort( $core, $sorter );
            usort( $themes, $sorter );
            usort( $plugins, $sorter );
            // upgrade notice
            $update = '';
            if( $updates = get_site_transient('update_plugins') ){
                $key = Loco::NS.'/loco.php';
                if( isset($updates->checked[$key]) && isset($updates->response[$key]) ){
                    $old = $updates->checked[$key];
                    $new = $updates->response[$key]->new_version;
                    if( 1 === version_compare( $new, $old ) ){
                        // current version is lower than latest
                        $update = $new;
                    }
                }
            }
            Loco::render('admin-root', compact('themes','plugins','core','update') );
        }
        while( false );
    } 
    
    
    
    
    /**
     * initialize template arguments for a plugin or theme table row
     * @return array
     */
    private static function init_package_args( LocoPackage $package, $type ){
        $warnings = array();
        try {
            $package->check_paths();
        }
        catch( Exception $Ex ){
            $warnings[] = $Ex->getMessage();
        }
        // find newest file in package to establish cache invalidation
        // get meta data or re-generate meta data from files
        $mkey = $type.'_meta_'.$package->get_domain();
        $meta = Loco::cached( $mkey );
        if( ! $meta || $package->get_modified() > $meta['mtime'] ){
            $meta = $package->meta();
            Loco::cache( $mkey, $meta );
        }
        return $meta + compact('warnings');
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
    private static function msginit( $root, $domain, $code, &$export, &$head ){
        $head = null;
        $locale = loco_locale_resolve( $code );
        if( ! $locale ){
            throw new Exception( Loco::__('You must specify a valid locale for a new PO file') );
        }
        $pot_path = $po_name = $po_dir = '';

        // extract POT if possible, falling back to source code if empty
        $export = array();
        $files = self::find_po( $root );
        foreach( $files['pot'] as $pot_path ){
            $pot_domain = self::resolve_file_domain($pot_path);
            if( ! $domain || $pot_domain === $domain ){
                $pot = self::parse_po_with_headers( $pot_path, $head );
                if( $pot && ! ( 1 === count($pot) && '' === $pot[0]['source'] ) ){
                    $export = $pot;
                    $po_dir = dirname($pot_path);
                    $po_name = $pot_domain.'-'.$locale->get_code().'po';
                    break;
                }
            }
        }
        if( ! $export ){
            $export = self::xgettext( $root, $domain );
            if( ! $export ){
                throw new Exception( Loco::__('No translatable strings found').'. '.Loco::__('Cannot create a PO file.') );
            }
        }

        // If no POT file was found, find another PO file and use similar name
        while( ! $po_name ){
            foreach( $files['po'] as $po_path ){
                $po_domain = self::resolve_file_domain($po_path);
                if( ! $domain || $po_domain === $domain ){
                    // have po file in domain, but may be another locale
                    $po_locale = self::resolve_file_locale( $po_path );
                    if( $locale->equal_to($po_locale) ){
                        throw new Exception( sprintf(Loco::__('PO file already exists with locale %s'), $po_locale->get_code() ) );
                    }
                    // attempt to name new file according to this one
                    $po_name = $po_domain.'-'.$locale->get_code().'po';
                    $po_dir = dirname($po_path);
                    break 2;
                }
            }
            // with no matching PO files, we'll place it in the default location
            $po_dir = $root;
            $po_name = $domain.'-'.$locale->get_code().'.po';
            if( 0 !== strpos($po_dir, WP_LANG_DIR) ){
                $po_dir .= '/languages';
            }
            break;
        }

        // set some default headers
        if( ! isset($head) ){
            $head = new LocoArray( array(
                //'Project-Id-Version' => basename($root),
            ) );
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
    private static function render_poeditor( $root, $path, array $data, LocoArray $head = null ){
        $pot = $po = $locale = null;
        $warnings = array();
        // remove header and check if empty
        $minlength = 1;
        if( isset($data[0]['source']) && $data[0]['source'] === '' ){
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
                    $warnings[] = Loco::__('Source code has been modified, run Sync to update POT');
                }
            }
            else if( $haspot && filemtime($haspot) > filemtime($path) ){
                $warnings[] = Loco::__('POT has been modified since PO file was saved, run Sync to update');
            }
        }

        // no longer need the full local paths
        $path = self::trim_path( $path );
        $root = self::trim_path( $root );
        
        // get name from package
        if( $theme = self::resolve_file_theme($path) ){
            $name = $theme->get('Name');
        }
        else {
            $name = basename( $path );
        }
        
        // extract some PO headers
        if( isset($head) ){
            $proj = $head->trimmed('Project-Id-Version');
            if( $proj && 'PACKAGE VERSION' !== $proj ){
                $name = $proj;
            }
            else {
                $head->add('Project-Id-Version', $name );
            }
            $headers = $head->to_array();
        }
        else {
            $headers = array( 'Project-Id-Version' => $name );
        }

        // set Last-Translator if PO file
        if( ! $ispot ){
            /* @var WP_User $user */
            $user = wp_get_current_user() and
            $headers['Last-Translator'] = $user->get('display_name').' <'.$user->get('user_email').'>';
        }
    
        Loco::enqueue_scripts('build/admin-poedit');
        Loco::render('admin-poedit', compact('root','path','file','po','pot','locale','headers','name','type','modified','writable','warnings') );
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
     * Recursively find PO and POT files under WP_LANG_DIR (wp-content/languages)
     * Then remove them so after all packages are processed we can pick up orphans.
     */
    public static function pop_lang_dir( $domain = '', $filtered = array() ){
        static $found;
        if( ! isset($found) ){
            $found = array();
            if( is_dir(WP_LANG_DIR) ){
                $found = self::find_po( WP_LANG_DIR );
            }
        }
        if( ! $domain ){
            return $found;
        }
        foreach( $found as $ext => $paths ){
            isset($filtered[$ext]) or $filtered[$ext] = array();
            foreach( $paths as $i => $path ){
                if( 0 === strpos( basename($path), $domain.'-' ) ){
                    $filtered[$ext][] = $path;
                    unset( $found[$ext][$i] );
                }
            }
        }
        return $filtered;
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
    public static function xgettext( $dir, $domain = '' ){
        /*/ source code may not be under the same path as PO file
        while( 0 === strpos($dir, WP_LANG_DIR ) ){
            if( ! $domain ){
                throw new Exception('Unknown text domain for '.$rel);
            }
            $rel = substr_replace( $dir, '', 0, strlen(WP_LANG_DIR) );
            // may have known source location
            if( 'admin' === $domain ){
                // @todo
            }
            // source may be a theme
            if( ( $theme = wp_get_theme($domain) ) && ! $theme->errors() ){
                $dir = $theme->get_theme_root().'/'.$theme->get('TextDomain');
                break;
            }
            throw new Exception("I don't know where to find source code in text domain '".$domain."'");
        }*/
        // collect all strings
        // @todo filter on domain
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
    public static function parse_po_with_headers( $path, &$headers ){
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
     * Resolve a PO file path or file name to TextDomain
     * @param string e.g. "blah/mytheme-fr_FR.po"
     * @return string e.fg. "mytheme"
     */
    public static function resolve_file_domain( $path ){
        extract( pathinfo($path) );
        if( ! isset($filename) ){
            $filename = str_replace('.', '', $basename ); // PHP < 5.2.0
        }
        return preg_replace('/-[a-z]{2}_[A-Z]{2}$/', '', $filename );
    }
    
    
    /**
     * Resolve a PO file to a theme
     * @return WP_Theme
     */
    public static function resolve_file_theme( $path ){
        if( false !== strpos($path,'/themes/') ){
            $domain = self::resolve_file_domain($path);
            return wp_get_theme( $domain );
        }
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
    public static function edit_link( $root, $path, $label = '', $icon = '' ){
        // path may be under given root
        if( 0 === strpos($path, $root) ){
            $path = str_replace( $root.'/', '', $path );
        }
        // or under WP_LANG_DIR
        else if( 0 === strpos($path, WP_LANG_DIR) ){
            $path = str_replace( WP_LANG_DIR.'/', '', $path );
            $root = WP_LANG_DIR;
        }
        $url = self::uri( array(
            'root'   => self::trim_path( $root ),
            'poedit' => $path,
        ) );
        if( ! $label ){
            $label = basename( $path );
        }
        $inner = Loco::html($label);
        if( $icon ){
            $inner = '<span class="'.$icon.'"></span>'.$inner;
        }
        return '<a href="'.Loco::html($url).'">'.$inner.'</a>';
    }
    
    
    
    /**
     * Generate a link to generate a new POT file
     */
    public static function xgettext_link( $root, $domain, $type, $label = '' ){
        $url = self::uri( array(
            'type' => $type,
            'root' => self::trim_path( $root ),
            'xgettext' => $domain,
        ) );
        if( ! $label ){
            $label = Loco::_x('New template','Add button') ;
        }
        return '<a href="'.Loco::html($url).'">'.Loco::html($label).'</a>';
        
    }
    
    
    
    /**
     * Generate a link to create a new PO file
     */
    public static function msginit_link( $root, $domain = '', $label = '' ){
        $url = self::uri( array(
            'root'    => self::trim_path( $root ),
            'msginit' => $domain,
        ) );
        if( ! $label ){
            $label = Loco::_x('New language','Add button');
        }
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




/**
 * Object representing a theme, plugin or domain within core code
 */
class LocoPackage {
    
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
     * Construct package from name, root and domain
     */    
    public function __construct( $domain, $name ){
        $this->domain = $domain;
        $this->name = $name or $this->name = $domain;
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
     * Add multiple locations from found PO and POT files
     * @return LocoPackage
     */
    public function add_po( array $files, $domain = '' ){
        foreach( $files['pot'] as $path ){
            $domain or $domain = LocoAdmin::resolve_file_domain($path) or $domain = $this->domain;
            $this->pot[ $domain ] = $path;
            $this->mtime = max( $this->mtime, filemtime($path) );
        }
        foreach( $files['po'] as $path ){
            $domain or $domain = LocoAdmin::resolve_file_domain($path) or $domain = $this->domain;
            $locale = LocoAdmin::resolve_file_locale($path);
            $code = $locale->get_code() or $code = 'xx_XX';
            $this->po[ $domain ][ $code ] = $path;
            $this->mtime = max( $this->mtime, filemtime($path) );
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
     * Check PO/POT paths are writable
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
     * Export meta data
     * @return array
     */
    public function meta(){
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
        return compact('po','pot') + array(
            'name' => $this->name,
            'root' => $this->get_root(),
            'domain' => $this->domain,
            'mtime' => $this->mtime,
        );
    }    


    /**
     * construct package object from theme
     * @return LocoPackage
     */
    public static function get_theme( $name, WP_Theme $theme = null ){
        $key = 'theme_'.$name;
        if( ! $theme ){
            if( $package = Loco::cached($key) ){
                return $package;
            }
        }
        // else uncached update from theme object
        $domain = $theme->get('TextDomain') or $domain = $name;
        $package = new LocoPackage( $domain, $theme->get('Name') );
        $root = $theme->get_theme_root().'/'.$name;
        // add PO and POT under theme root
        if( $files = LocoAdmin::find_po($root) ){
            $package->add_po( $files, $domain );
        }
        // find additional theme PO under WP_LANG_DIR
        if( $files = LocoAdmin::pop_lang_dir($domain) ){
            $package->add_po( $files, $domain );
        }
        $package->add_source( $root );
        //
        Loco::cache( $key, $package );
        return $package;
    }    
    
    
    /**
     * construct package object from plugin array
     */
    public static function get_plugin( $name, array $plugin = null ){
        $key = 'plugin_'.$name;
        if( ! $plugin ){
            if( $package = Loco::cached($key) ){
                return $package;
            }
        }
        $domain = $plugin['TextDomain'] or $domain = $name;
        $package = new LocoPackage( $domain, $plugin['Name'] );
        $root = WP_PLUGIN_DIR.'/'.$name;
        // add PO and POT under plugin root
        if( $files = LocoAdmin::find_po($root) ){
            $package->add_po( $files, $domain );
        }
        // find additional plugin PO under WP_LANG_DIR
        if( $files = LocoAdmin::pop_lang_dir($domain) ){
            $package->add_po( $files, $domain );
        }
        $package->add_source( $root );
        //
        Loco::cache( $key, $package );
        return $package;
    }
    
    
    /**
     * construct a core package object from name
     */
    public static function get_core( $domain, $name = '' ){
        $key = 'core_'.$domain;
        if( ! $name ){
            if( $package = Loco::cached($key) ){
                return $package;
            }
        }
        $files = LocoAdmin::pop_lang_dir($domain);
        if( $files['po'] || $files['pot'] ){
            $package = new LocoPackage( $domain, $name );
            $package->add_po( $files );
            //
            Loco::cache( $key, $package );
            return $package;
        }
    }
}






    
    
    
// admin filter and action callbacks
 

/**
 * Enqueue only admin styles we need
 */  
function _loco_hook__admin_print_styles(){
    if( LocoAdmin::is_self() ){
        Loco::enqueue_styles('build/loco-compiled','loco-admin');
    }
}  


/**
 * Admin menu registration callback
 */
function _loco_hook__admin_menu() {
    // Settings menu
    $title = Loco::__('Loco, Translation Management');
    $page = array( 'LocoAdmin', 'render_page_options' );
    add_options_page( $title, Loco::__('Translation'), 'manage_options', Loco::NS, $page );
    // Tools menu
    $page = array( 'LocoAdmin', 'render_page_tools' );
    $hook = add_management_page( $title, Loco::__('Manage Translations'), LOCO::CAPABILITY, Loco::NS, $page );
    add_action('admin_print_styles', '_loco_hook__admin_print_styles' );
        
}


/**
 * extra visibility of settings link
 */
function _loco_hook__plugin_row_meta( $links, $file = '' ){
    if( false !== strpos($file,'/loco.php') ){
        $links[] = '<a href="tools.php?page='.Loco::NS.'"><strong>'.Loco::__('Manage translations').'</strong></a>';
        $links[] = '<a href="options-general.php?page='.Loco::NS.'"><strong>'.Loco::__('Settings').'</strong></a>';
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




add_action('admin_menu', '_loco_hook__admin_menu' );
add_action('plugin_row_meta', '_loco_hook__plugin_row_meta', 10, 2 );

// ajax hooks all going through one central function
add_action('wp_ajax_loco-posave', '_lock_hook__wp_ajax' );
add_action('wp_ajax_loco-posync', '_lock_hook__wp_ajax' );

// WP_LANG_DIR was introduced in Wordpress 2.1.0.
if( ! defined('WP_LANG_DIR') ){
    define('WP_LANG_DIR', WP_CONTENT_DIR.'/languages' );
} 
 
