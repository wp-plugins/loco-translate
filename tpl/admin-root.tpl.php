<?php
/**
 * Root admin screen - lists available themes and plugins
 */
$nav = array (
    Loco::__('Packages') => '',
    Loco::__('Settings') => str_replace( 'tools', 'options-general', LocoAdmin::uri() ),
); 
?>

<div class="wrap loco-admin loco-lists">

    <?php if( $update ):?>
    <div class="loco-message updated loco-warning">
        <p>
            <strong>
                <?php Loco::h( Loco::__('New version available') )?>:
            </strong>
            <a href="http://wordpress.org/extend/plugins/<?php echo Loco::NS?>">
                <?php Loco::h( Loco::__('Upgrade to version %s of Loco Translate'), $update )?>
            </a>
        </p>
    </div>
    <?php endif?> 
    
    
    <?php Loco::render('admin-nav', compact('nav') )?> 
    
    <h3 class="title">
        <?php Loco::h( Loco::__('Select a plugin or theme to translate') )?> 
    </h3>


    <?php if( $themes ):?> 
    <div class="icon32 icon-appearance"><br /></div>
    <h2>
        <?php Loco::h( Loco::_x('Themes','Package list header') )?> 
    </h2>
    <div class="loco-list loco-list-themes">
        <?php Loco::render( 'admin-list', array('items'=>$themes,'type'=>'theme') ) ?> 
    </div>
    <?php endif?> 


    <?php if( $plugins ):?> 
    <div class="icon32 icon-plugins"><br /></div>
    <h2>
        <?php Loco::h( Loco::_x('Plugins','Package list header') )?> 
    </h2>
    <div class="loco-list loco-list-plugins">
        <?php Loco::render( 'admin-list', array('items'=>$plugins,'type'=>'plugin') ) ?> 
    </div>
    <?php endif?> 


    <?php if( $core ):?> 
    <div class="icon32 icon-generic"><br /></div>
    <h2>
        <?php Loco::h( Loco::_x('Core','Package list header') )?> 
    </h2>
    <div class="loco-list loco-list-core">
        <?php Loco::render( 'admin-list', array('items'=>$core,'type'=>'core') ) ?> 
    </div>
    <?php endif?> 

</div>