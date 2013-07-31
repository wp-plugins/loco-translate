<?php
/**
 * Admin ajax include that syncs PO or POT file with sources
 */
 
    DOING_AJAX or die();
    
    if( empty($path) || empty($root) ){
        throw new Exception( Loco::__('Invalid data posted to server'), 422 );
    }
  
    // path is allowed not exist
    if( '/' !== $path{0} ){
        $path = WP_CONTENT_DIR.'/'.$path;
    }

    // but root must
    $root = LocoAdmin::resolve_path( $root, true );
    
    // If file we're syncing is POT, we can only sync from sources
    if( ! LocoAdmin::is_pot($path) ){
            
        // if a POT file exists, sync from that.
        foreach( LocoAdmin::find_pot($root) as $pot_path ){
            $export = LocoAdmin::parse_po( $pot_path );
            if( ! $export || ( 1 === count($export) && '' === $export[0]['source'] ) ){
                //throw new Exception( Loco::__('POT file is empty') );
                continue;
            }
            return array (
                'pot' => basename($pot_path),
                'exp' => $export,
            );
        }

    }

    
    // Extract from sources by default        
    $export = LocoAdmin::xgettext( $root );
    if( ! $export ){
        throw new Exception( Loco::__('No strings could be extracted from source files') );
    }
    return array (
        'pot' => '',
        'exp' => $export,
    );
