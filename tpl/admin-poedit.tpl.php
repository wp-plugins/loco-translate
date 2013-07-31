<?php
/**
 * PO file editor screen
 */
$nav = array (
    Loco::__('Packages') => LocoAdmin::uri(),
    $name => '',
);  

$phpbase = Loco::html( Loco::baseurl() ).'/php';
//$relpath = str_replace( $root.'/', '', $path );

?>
<div class="wrap loco-admin loco-edit">
    
    <?php Loco::render('admin-nav', compact('nav') )?> 
    
    <h3 class="title">
        <?php Loco::h( $locale ? $locale->get_name() : Loco::__('Template file') )?>:
        <span class="loco-meta">
            <?php Loco::h( Loco::__('Updated') )?>:
            <span id="loco-po-modified">
            <?php if( $modified ):?> 
                 <?php Loco::h($modified)?>
            <?php else:?> 
                <em><?php Loco::h( Loco::__('never') )?></em>
            <?php endif?> 
            </span>
            &mdash;
            <span id="loco-po-status">
                <!-- js will load status -->
            </span>
        </span>
    </h3>
    
    
    <?php foreach( $warnings as $text ): LocoAdmin::warning($text); endforeach?> 
    
    
    <div id="loco-poedit">
        
        <nav id="loco-nav" class="wp-core-ui">
            <form action="<?php echo $phpbase?>/loco-fail.php" method="post">
                <input type="hidden" name="po" value="" />
                <input type="hidden" name="action" value="loco-posave" />
                <input type="hidden" name="path" value="<?php Loco::h($path)?>" />
                <button class="button loco-save" data-loco="save" type="submit" disabled>
                    <span><?php Loco::h( Loco::_x('Save','Editor button') )?></span>
                </button>
            </form>
            <form action="<?php echo $phpbase?>/loco-download.php" method="post">
                <input type="hidden" name="po" value="" />
                <input type="hidden" name="path" value="<?php Loco::h($path)?>" />
                <button class="button loco-download" data-loco="download" type="submit" disabled>
                    <span><?php Loco::h( Loco::_x('Download','Editor button') )?></span>
                </button>
            </form>
            <form action="<?php echo $phpbase?>/loco-fail.php" method="post">
                <input type="hidden" name="action" value="loco-posync" />
                <input type="hidden" name="root" value="<?php Loco::h($root)?>" />
                <input type="hidden" name="path" value="<?php Loco::h($path)?>" />
                <button class="button loco-sync" data-loco="sync" disabled>
                    <span><?php Loco::h( Loco::_x('Sync','Editor button') )?></span>
                </button>
            </form>
            <form action="<?php echo $phpbase?>/loco-fail.php" method="get">
                <button class="button loco-revert" data-loco="revert" disabled>
                    <span><?php Loco::h( Loco::_x('Revert','Editor button') )?></span>
                </button>
            </form>
            <form action="<?php echo $phpbase?>/loco-fail.php">
                <button class="button loco-add" data-loco="add" disabled>
                    <span><?php Loco::h( Loco::_x('Add','Editor button') )?></span>
                </button>
            </form>
            <form action="<?php echo $phpbase?>/loco-fail.php">
                <button class="button loco-del" data-loco="del" disabled>
                    <span><?php Loco::h( Loco::_x('Del','Editor button') )?></span>
                </button>
            </form>
            <form action="<?php echo $phpbase?>/loco-fail.php">
                <button class="button loco-fuzzy" data-loco="fuzzy" disabled>
                    <span><?php Loco::h( Loco::_x('Fuzzy','Editor button') )?></span>
                </button>
            </form>
            <form action="<?php echo $phpbase?>/loco-fail.php" id="loco-filter">
                <input type="text" maxlength="100" name="q" id="loco-search" placeholder="<?php Loco::h(Loco::__('Filter translations'))?>" autocomplete="off" disabled />
            </form>
            <form action="http://wordpress.org/support/plugin/<?php echo Loco::NS?>" target="_blank">
                <button class="button loco-help" data-loco="help" type="submit">
                    <span><?php Loco::h( Loco::_x('Help','Editor button') )?></span>
                </button>
            </form>
        </nav>
    
        <div id="loco-poedit-inner" class="loco-editor loading">
            <span>Loading..</span>
        </div>
    
    </div>
    
    
    <script>
        loco = window.loco || {};
        loco.conf = <?php echo json_encode( array(
            'po'  => $po,
            'pot' => $pot,
            'locale' => $locale ? $locale->export() : null,
            'writable' => $writable,
            'modified' => $modified,
        ) )?>;
    </script>
    
</div>