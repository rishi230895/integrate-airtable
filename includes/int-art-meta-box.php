<?php

/**
 * Renders a meta box that displays a data table of all custom meta fields for the given post.
 *
 * The table is rendered with the following columns:
 *
 * 1. Meta Field Name: The name of the custom meta field, with underscores replaced by spaces and the first letter of each word capitalized.
 * 2. Meta Data: The value of the custom meta field. If the value is an array, it is displayed as an unordered list.
 *
 * If no custom meta fields are found, a message is displayed indicating that no data is available.
 *
 * @param WP_Post $post The post object to retrieve custom meta fields for.
 *
 * @since 1.0.0
 */

if ( ! function_exists('int_render_data_table_meta_box') ) {
    function int_render_data_table_meta_box( $post ) {
        if ( $post ) {
            ob_start();
            ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>
                            <?php echo __("Meta Field Name", INT_ART_TEXT_DOMAIN );  ?>
                        </th>
                        <th>
                            <?php echo __("Meta Data", INT_ART_TEXT_DOMAIN );  ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
            <?php

            // Get custom meta keys
            $columns_keys = int_art_get_custom_meta_keys_by_prefix( $post->ID );
            if ( $columns_keys ) {
                foreach ( $columns_keys as $meta_key ) {
                    $column_name = int_art_split_meta_key( $meta_key );
                    $column_data = get_post_meta( $post->ID, $meta_key, true );

                    ?>
                    <tr>
                        <td><?php echo __( esc_html( $column_name ) , INT_ART_TEXT_DOMAIN); ?></td>
                        <td>
                    <?php
                    if ( empty( $column_data ) ) {
                        echo '-'; 
                    } 
                    elseif ( is_array( $column_data ) ) {
                        echo '<ul>';
                            foreach ( $column_data as $item ) {
                                echo '<li>' .  __( esc_html( $item ) , INT_ART_TEXT_DOMAIN) . '</li>';
                            }
                        echo '</ul>';
                    } 
                    else {
                        
                        echo __(esc_html( $column_data ) , INT_ART_TEXT_DOMAIN);
                    }

                    ?>
                        </td>
                    </tr>
                    <?php
                }
            } 
            else {
                ?>
                <tr>
                    <td colspan="2">
                        <?php echo __("No data available." , INT_ART_TEXT_DOMAIN); ?>
                    </td>
                </tr>
                <?php
            }

            ?>
                </tbody>
            </table>
            <?php

            // Get the buffered content
            $output = ob_get_clean();
            // Output the final HTML
            echo $output;
        }
    }
}

/**
 * Adds a meta box to the Airtable Sync post type if the "Show meta fields" option is enabled.
 *
 * The meta box displays a data table of all custom meta fields for the given post.
 *
 * @since 1.0.0
 */

if(  ! function_exists('int_add_data_table_meta_box')   ) {

    function int_add_data_table_meta_box() {
        $is_checked = get_option("int_art_show_meta_fields");
        if( $is_checked ) {
            add_meta_box(
                'int_data_table_meta_box',
                __( 'Airtable Meta fields information', INT_ART_TEXT_DOMAIN ),
                'int_render_data_table_meta_box',
                'air-sync',
                'normal',
                'high'
            );
        }
    }

    add_action( 'add_meta_boxes', 'int_add_data_table_meta_box' );
}