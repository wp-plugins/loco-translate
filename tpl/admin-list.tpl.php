<?php
/**
 * List of either plugins or themes that are translatable
 */
?> 

        <table class="wp-list-table widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col">
                        <?php Loco::h( Loco::_x('Package name','Table header') )?> 
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
                        <strong><?php Loco::h($name)?></strong>
                        <br />
                        <span><?php Loco::h( _n( '1 language', '%u languages', $n ), $n )?></span>
                    </td>
                    <td>
                        <ul>
                            <li class="loco-add">
                                <?php echo LocoAdmin::msginit_link( $root, Loco::_x('New language','Add button') )?> 
                            </li><?php
                            foreach( $po as $po_path ): 
                                $locale = LocoAdmin::resolve_file_locale($po_path);
                                $label = $locale->get_code().' : '.$locale->get_name();
                            ?> 
                            <li class="loco-edit">
                                <?php echo LocoAdmin::edit_link( $root, $po_path, $label )?> 
                            </li><?php
                            endforeach;?> 
                        </ul>
                    </td>
                    <td>
                        <ul><?php // show POT files (should be no more than one)
                        if( $pot ):
                            foreach( $pot as $pot_path ):
                            ?> 
                            <li class="loco-edit">
                                <?php echo LocoAdmin::edit_link( $root, $pot_path )?> 
                            </li><?php
                            endforeach;
                         // else no POT file
                         else:?> 
                            <li class="loco-add">
                                <?php echo LocoAdmin::xgettext_link( $root, Loco::_x('New template','Add button') )?> 
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
        