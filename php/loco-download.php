<?php
/**
 * Simple PO/POT post-through script.
 */
try {
    
    if( 'POST' !== $_SERVER['REQUEST_METHOD'] ){
        throw new Exception( 'Method not permitted', 405 );
    }

    extract( $_POST );
    if( empty($po) ){
        throw new Exception( 'Empty source data', 422 );
    }
    
    if( empty($path) ){
        $name = 'messages.po';
    }
    else {
        $name = basename($path);
    }
    
    header('Content-Type: application/x-gettext; charset=UTF-8', true );        
    header('Content-Length: '.strlen($po), true );
    header('Content-Disposition: attachment; filename='.$name, true );
    echo $po;
    exit(0);
    
     
}  
catch( Exception $Ex ){
    require dirname(__FILE__).'/loco-fatal.php';
}
