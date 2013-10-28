<?php
/**
 * Admin options screen - changes loco plugin settings
 */
$nav = array (
    Loco::__('Packages') => str_replace( 'options-general', 'tools', LocoAdmin::uri() ),
    Loco::__('Settings') => '',
); 
?>

<div class="wrap">
    

    <?php Loco::render('admin-nav', compact('nav') )?> 
    
    <?php isset($success) and LocoAdmin::success( $success )?> 

    <form action="" method="post">
        <p>
            <label for="loco--which_msgfmt">
                <strong>Gettext msgfmt</strong><br />
                <?php Loco::h( Loco::__('Path to msgfmt program for compiling MO files') )?>:
            </label>
            <br />
            <input type="text" size="32" name="loco[which_msgfmt]" id="loco--which_msgfmt" value="<?php Loco::h($which_msgfmt)?>" />
        </p>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php Loco::h( Loco::__('Save settings') )?>" />
        </p>
    </form>
    
</div>