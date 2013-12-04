<?php
/**
 * List of either plugins or themes that are translatable
 */
?> 

        <table class="wp-list-table widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col">
                        <?php Loco::h( Loco::_x('Package details','Table header') )?> 
                    </th>
                    <th scope="col">
                        <?php Loco::h( Loco::_x('Translations (PO)','Table header') )?> 
                    </th>    
                    <th scope="col">
                        <?php Loco::h( Loco::_x('Template (POT)','Table header') )?> 
                    </th>    
                    <th scope="col">
                        <?php Loco::h( Loco::_x('File permissions','Table header') )?> 
                    </th>    
                </tr>
            </thead>
            <tbody><?php 
            foreach( $items as $r ): 
                extract( $r );
                $n = count($po);
                ?> 
                <tr class="inactive">
                    <td>
                        <ul class="loco-details">
                            <li title="<?php Loco::h($domain)?>">
                                <strong><?php Loco::h($name)?></strong>
                            </li>
                            <li>
                                <?php Loco::h( _n( '1 language', '%u languages', $n ), $n )?>
                            </li><?php 
                            if( $mtime ):?> 
                            <li class="loco-mtime">
                                <small>
                                    <?php Loco::h( Loco::_x('Updated','Modified time') )?> 
                                    <?php Loco::h( LocoAdmin::format_datetime($mtime) )?> 
                                </small>
                            </li><?php
                            endif?> 
                        </ul>
                    </td>
                    <td>
                        <ul>
                            <li class="loco-add">
                                <?php echo LocoAdmin::msginit_link( $root, $domain )?> 
                            </li><?php
                            foreach( $po as $po_data ):
                                extract( $po_data, EXTR_PREFIX_ALL, 'po' );
                                $code = $po_locale->get_code();
                                $label = $code ? $code.' : '.$po_locale->get_name() : $po_name;
                            ?> 
                            <li class="loco-edit-po">
                                <?php echo LocoAdmin::edit_link( $root, $po_path, $label, $po_locale->icon_class() )?> 
                                <small class="loco-progress">
                                    <?php echo $po_stats['p']?>%
                                </small>
                            </li><?php
                            endforeach;?> 
                        </ul>
                    </td>
                    <td>
                        <ul><?php // show POT files (should be no more than one)
                        if( $pot ):
                            foreach( $pot as $pot_data ):
                                extract( $pot_data, EXTR_PREFIX_ALL, 'pot' );
                            ?> 
                            <li class="loco-edit-pot">
                                <?php echo LocoAdmin::edit_link( $root, $pot_path )?> 
                            </li><?php
                            endforeach;
                         // else no POT file
                         else:?> 
                            <li class="loco-add">
                                <?php echo LocoAdmin::xgettext_link( $root, $domain, $type )?> 
                            </li><?php 
                         endif;?>
                        </ul>
                    </td>
                    <td>
                        <ul>
                            <?php if( $warnings ): foreach( $warnings as $warning ):?> 
                            <li class="loco-warn">
                                <span><?php Loco::h( $warning )?></span> 
                            </li>                                
                            <?php endforeach; else:?> 
                            <li class="loco-ok">
                                <span>OK</span>
                            </li>
                            <?php endif?> 
                        </ul>
                    </td>
                </tr><?php 
                endforeach?> 
            </tbody>
        </table>
        