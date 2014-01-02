<?php
/**
 * PO/MO download script
 */
try {
    
    if( 'POST' !== $_SERVER['REQUEST_METHOD'] ){
        throw new Exception( 'Method not permitted', 405 );
    }

    // no errors ruining response please
    if( false === ini_set( 'display_errors', 0 ) ){
        error_reporting(0);
    }

    if( ! function_exists('current_user_can') || ! class_exists('LocoAdmin') ){
        throw new Exception('Wordpress not bootstrapped');
    }
    
    if( ! current_user_can(Loco::CAPABILITY) ){
        throw new Exception( Loco::__('User does not have permission to manage translations'), 403 );
    }

    if( empty($po) ){
        throw new Exception( 'Empty source data', 422 );
    }
    
    if( empty($path) ){
        $name = 'messages.po';
        $ext = 'po';
    }
    else {
        $name = basename($path);
        $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
    }
    
    // Simple post-through for PO and POT
    if( 'mo' !== $ext ){
        header('Content-Type: application/x-gettext; charset=UTF-8', true );        
        header('Content-Length: '.strlen($po), true );
        header('Content-Disposition: attachment; filename='.$name, true );
        echo $po;
        exit(0);
    }


    // Else compile binary MO file

    $conf = Loco::config();

    // attempt to compile MO direct to file via shell
    if( $conf['use_msgfmt'] && $conf['which_msgfmt'] ){
        try {
            loco_require('build/shell-compiled');
            define( 'WHICH_MSGFMT', $conf['which_msgfmt'] );
            // @todo use temp file if over max stdin size
            $mo = loco_compile_mo( $po );
        }
        catch( Exception $Ex ){
            error_log( $Ex->getMessage(), 0 );
        }
        if( ! $mo ){
            throw new Exception( sprintf( Loco::__('Failed to compile MO file with %s, check your settings'), WHICH_MSGFMT ) );
        }
    }

    // Fall back to in-built MO compiler - requires PO is parsed too
    else {
        try {
            loco_require('build/gettext-compiled');
            $mo = loco_msgfmt( $po );
        }
        catch( Exception $Ex ){
            error_log( $Ex->getMessage(), 0 );
        }
        if( ! $mo ){
            throw new Exception( sprintf( Loco::__('Failed to compile MO file with built-in compiler') ) );
        }
    }

    // exit with binary MO    
    header('Content-Type: application/x-gettext-translation; charset=UTF-8', true );        
    header('Content-Length: '.strlen($mo), true );
    header('Content-Disposition: attachment; filename='.$name, true );
    echo $mo;
    exit(0);
    
     
}  
catch( Exception $Ex ){
    require dirname(__FILE__).'/loco-fatal.php';
}
