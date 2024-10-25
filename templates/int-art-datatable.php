<?php 

if( $post_data->have_posts() ) {

?>

<table id="int_art_data_table" class="display" cellspacing="0" width="100%">
    <thead>

        <?php if( ! $is_meta_field_exist   ) { ?>
        <tr>
            <?php
                if( $all_meta_keys ) {
                    foreach( $all_meta_keys as $key ) {
                    ?>
                        <th><?php echo __(  int_art_split_meta_key($key) , INT_ART_TEXT_DOMAIN );  ?></th>
                    <?php 
                    }
                }
            ?>
        </tr>
        <?php 
            } 
            else { 
                if( ! empty( $all_meta_keys ) ) {
                    if( is_array( $all_meta_keys ) ) {
                    ?>
                        <tr>
                            <?php 
                                foreach( $all_meta_keys as $key => $val ) { ?>
                                    <th><?php echo __( int_art_split_meta_key($val) , INT_ART_TEXT_DOMAIN );  ?></th>    
                                <?php 
                                }
                            ?>
                        </tr>
                    <?php
                    }
                }
            } 
        ?>
    </thead>
<tbody>
<?php 
    $counter = 0;
    while( $post_data->have_posts() ) {
        $post_data->the_post();
        $title          = get_the_title();
        $post_id        = get_the_ID();
        $post_desc      = get_the_content();
        $post_feature   = get_the_post_thumbnail_url( get_the_ID(), 'full' );
        $col_id         = get_post_meta($post_id , 'int_art_column_id' , true);

    ?>
        <?php if( ! $is_meta_field_exist  ) {  ?>
                <tr>
                    <?php 
                        if( $all_meta_keys ) { 
                            foreach(  $all_meta_keys as $key ) {
                                $meta_data = get_post_meta( $post_id , $key , true);
                        ?>
                                <td>
                                    <?php echo __( esc_html( $meta_data ) , INT_ART_TEXT_DOMAIN );  ?>
                                </td>
                    <?php   } 
                        }
                    ?>
                </tr>
        <?php 
            } 
            else {  

                if( ! empty( $all_meta_keys ) ) {
                    if( is_array($all_meta_keys ) ) {
                        ?>
                            <tr>
                                <?php
                                foreach( $all_meta_keys as $key ) {
                                    $meta_data = get_post_meta( $post_id , $key, true );
                                ?>
                                    <td>
                                        <?php echo __( esc_html( $meta_data ) , INT_ART_TEXT_DOMAIN );  ?>
                                    </td>
                                <?php
                                }     
                                ?>
                            </tr>
                        <?php
                    }
                }
        ?>
        <?php }  ?>
    <?php
        $counter++;
    }
    wp_reset_postdata();
?>

    </tbody>
</table>

<?php 

    } else {  ?>

    <h3 class="no-data"><?php echo __( "No data found." , INT_ART_TEXT_DOMAIN ); ?></h3>

<?php } ?>