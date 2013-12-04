<?php
/**
 * Admin ajax include that syncs PO or POT file with sources
 */
 
    DOING_AJAX or die();
    
    if( empty($path) || empty($root) ){
        throw new Exception( Loco::__('Invalid data posted to server'), 422 );
    }
  
    // path is allowed to not exist
    if( '/' !== $path{0} ){
        $path = WP_CONTENT_DIR.'/'.$path;
    }

    // but root must exist
    $root = LocoAdmin::resolve_path( $root, true );

    while( true ){

        // If file we're syncing is POT, we can only sync from sources
        if( ! LocoAdmin::is_pot($path) ){
               
            // if a POT file exists, sync from that.
            foreach( LocoAdmin::find_pot($root) as $pot_path ){
                $exp = LocoAdmin::parse_po( $pot_path );
                if( ! $exp || ( 1 === count($exp) && '' === $exp[0]['source'] ) ){
                    //throw new Exception( Loco::__('POT file is empty') );
                    continue;
                }
                $pot = basename($pot_path);
                break 2;
            }
    
        }
    
        // Extract from sources by default        
        if( $exp = LocoAdmin::xgettext($root) ){
            $pot = '';
            break;
        }

        throw new Exception( Loco::__('No strings could be extracted from source files') );
    }
    

    // sync selected headers
    $headers = array();
    if( '' === $exp[0]['source'] ){
        $keep = array('Project-Id-Version'=>'','Language-Team'=>'','POT-Creation-Date'=>'','POT-Revision-Date'=>'');
        $head = loco_parse_po_headers( $exp[0]['target'] );
        $headers = array_intersect_key( $head->to_array(), $keep );
        $exp[0] = array();
    }
        

    // sync ok.
    return compact( 'pot', 'exp', 'headers' );
    
    
