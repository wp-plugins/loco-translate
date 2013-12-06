<?php
/**
 * Admin ajax include that saves PO file from editor to disk
 * Included by loco-ajax.php during Ajax action
 */
 
    DOING_AJAX or die();

    if( empty($path) || empty($po) || empty($name) || empty($type) ){
        throw new Exception( Loco::__('Invalid data posted to server'), 422 );
    }
  
    // path is allowed to not exist yet
    if( '/' !== $path{0} ){
        $path = WP_CONTENT_DIR.'/'.$path;
    }

    // but package must exist so we can get POT or source
    /* @var $package LocoPackage */
    loco_require('loco-packages','loco-locales');
    $package = LocoPackage::get( $name, $type );
    if( ! $package ){
        throw new Exception( sprintf( Loco::__('Package not found called %s'), $name ), 404 );
    }

    $fname = basename($path);
    $dname = basename( dirname($path) );
    $ispot = LocoAdmin::is_pot( $fname );
    $ftype = $ispot ? 'POT' : 'PO';


    // construct directory tree if file does not exist
    if( ! file_exists($path) ){
        $dir = dirname($path);
        if( ! file_exists($dir) && ! mkdir( $path, 0775, true ) ){
            $pname = basename( dirname($dir) );
            throw new Exception( sprintf(Loco::__('Web server cannot create "%s" directory in "%s". Fix file permissions or create it manually.'), $dname, $pname ) );
        }
        if( ! is_dir($dir) || ! is_writable($dir) ){
            throw new Exception( sprintf(Loco::__('Web server cannot create files in the "%s" directory. Fix file permissions or use the download function.'), basename($dir) ) );
        }
    }
    
    
    // attempt to write PO file
    $bytes = file_put_contents( $path, $po );
    if( false === $bytes ){
        throw new Exception( sprintf(Loco::__('%s file is not writable by the web server. Fix file permissions or download and copy to "%s/%s".'), $ftype, $dname, $fname ) );
    }
    
    // primary action ok
    $response = array (
        'bytes'    => $bytes,
        'filename' => basename($path),
        'modified' => LocoAdmin::format_datetime( filemtime($path) ),
    );
    
    // flush package from cache, so it's regenerated next list view with new stats
    $package->uncache();

   
    // attempt to write MO file also, but may fail for numerous reasons.
    if( ! $ispot ){
        try {

            // establish msgfmt settings
            $conf = Loco::config();

            // attempt to find an appropriate msgfmt if never set
            if( false === $conf['which_msgfmt'] ){
                function_exists('loco_find_executable') or loco_require('build/shell-compiled');
                $conf['which_msgfmt'] = loco_find_executable('msgfmt') and
                Loco::config( $conf );
            }
            
            if( $conf['which_msgfmt'] ){
                define( 'WHICH_MSGFMT', $conf['which_msgfmt'] );
                // check target MO path before compiling
                $mopath = preg_replace( '/\.po$/', '.mo', $path );
                if( ! file_exists($mopath) && ! is_writable( dirname($mopath) ) ){
                    throw new Exception( Loco::__('Cannot create MO file') );
                }
                else if( file_exists($mopath) && ! is_writable($mopath) ){
                    throw new Exception( Loco::__('Cannot overwrite MO file') );
                }
                // attempt to compile MO direct to file via shell
                try {
                    $bytes = 0;
                    function_exists('loco_compile_mo_file') or loco_require('build/shell-compiled');
                    $mopath = loco_compile_mo_file( $path, $mopath );
                    $bytes  = $mopath && file_exists($mopath) ? filesize($mopath) : 0;
                }
                catch( Exception $Ex ){
                    error_log( $Ex->getMessage(), 0 );
                }
                if( ! $bytes ){
                    throw new Exception( sprintf( Loco::__('Failed to compile MO file with %s, check your settings'), WHICH_MSGFMT ) );
                }
                $response['compiled'] = $bytes;
            }
        }
        catch( Exception $e ){
            $response['compiled'] = $e->getMessage();
        }
    }
    
    
    
    return $response;
