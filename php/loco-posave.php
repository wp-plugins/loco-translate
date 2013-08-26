<?php
/**
 * Admin ajax include that saves PO file from editor to disk
 * Included by loco-ajax.php during Ajax action
 */
 
    DOING_AJAX or die();

    if( empty($path) || empty($po) ){
        throw new Exception( Loco::__('Invalid data posted to server'), 422 );
    }
  
    if( '/' !== $path{0} ){
        $path = WP_CONTENT_DIR.'/'.$path;
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
    
    
    // attempt to write MO file also, but may fail for numerous reasons.
    if( ! $ispot ){
        try {
            $path = str_replace( '.po', '.mo', $path );
            if( ! file_exists($path) && ! is_writable( dirname($path) ) ){
                throw new Exception('Cannot create MO file');
            }
            else if( file_exists($path) && ! is_writable($path) ){
                throw new Exception('Cannot overwrite MO file');
            }
            // attempt to shell out to msgfmt, assuming it's under $PATH
            define( 'WHICH_MSGFMT', 'msgfmt' );
            function_exists('loco_compile_mo') or loco_require('build/gettext-compiled');
            $mo = loco_compile_mo( $po );
            if( ! $mo ){
                throw new Exception('Zero bytes from msgfmt');
            }
            $bytes = file_put_contents( $path, $mo );
            $response['compiled'] = $bytes;
        }
        catch( Exception $e ){
            $response['compiled'] = $e->getMessage();
        }
    }
    
    
    
    return $response;
