<?php

/**
 * Renders the shortcode for displaying Airtable data in a table.
 *
 * The shortcode supports the following attributes:
 *
 * - meta_field (string): Comma-separated list of column names to include in the table.
 *                        If not provided, all columns will be included.
 *
 * The function first checks if the 'meta_field' attribute is provided and if it is an
 * array. If it is, it loops through the array and adds each column name to an array
 * of meta keys. If not, it fetches all meta keys using the int_art_get_meta_keys()
 * function.
 *
 * The function then queries all published posts of the 'air-sync' type and
 * generates an HTML table using the int-art-datatable.php template. The table
 * includes columns for each of the meta keys specified in the 'meta_field'
 * attribute or all columns if not provided.
 *
 * @param array $attr Shortcode attributes.
 * @return string HTML table containing the Airtable data.
 */

if( ! function_exists('int_art_show_data') ) {
    function int_art_show_data($attr) {
        $is_meta_field_exist = false;     
        $all_meta_keys = []; 
        $stored_meta_keys = int_art_get_meta_keys();
        if( $attr ) {
            if( is_array( $attr ) ) {   
                $attr_keys = [];
                foreach( $attr as $key => $col ) {
                    if(  $key == 'meta_field' ) {
                        if( $col ) {
                            $temp_exploded = explode("," , $col);
                            if( $temp_exploded && is_array( $temp_exploded ) ) {
                                $temp_col_names = [];
                                foreach( $temp_exploded as $col_name ) {
                                    if( $col_name ) {
                                        $temp_col_names[] = trim($col_name);
                                    }
                                }
                                if( $temp_col_names ) {
                                    $is_meta_field_exist = true;
                                    foreach( $temp_col_names as $key ) {
                                        $attr_keys[] = 'int_art_' . int_separate_underscore( $key );
                                    }
                                }
                            }
                        }
                    }
                }
                $all_meta_keys = $attr_keys;
            }
        } else {
            $all_meta_keys = int_art_get_meta_keys();
        }

        $args = [
            'post_type'         => 'air-sync',
            'post_status'       => 'publish',
            'posts_per_page'    => -1
        ];
        
        $post_data = new WP_Query($args);
        ob_start();
        require_once INT_ART_PLUGIN_PATH . 'templates/int-art-datatable.php';
        $html = ob_get_clean();
        return $html;
    }
    add_shortcode('int_art_get_meta_data_table', 'int_art_show_data');
}


/**
 * Retrieves a meta field value from a given post ID and meta key.
 *
 * If the meta key is not provided or the post ID is not valid, an empty string will be returned.
 *
 * @param array $attr {
 *     Associative array of attributes.
 *
 *     @type string $meta_field The meta key for which the value is to be retrieved.
 *     @type int    $post_id    The ID of the post from which the meta value is to be retrieved.
 * }
 *
 * @return string The value of the meta field if found, otherwise an empty string.
 */


if( ! function_exists('int_art_get_meta_field_data') ) {
    function int_art_get_meta_field_data($attr) {

        if( ! $attr ) return '';
        if( is_array( $attr ) ) {
            if( array_key_exists('meta_field' , $attr) && array_key_exists('post_id' , $attr)) {
                $meta_key = $attr['meta_field'];
                $post_id  = (int)$attr['post_id'];
                if( $meta_key ) {
                    $meta_key = trim($meta_key);
                    $meta_key = 'int_art_' . int_separate_underscore( $meta_key );
                    return get_post_meta( $post_id  ,$meta_key , true  );
                }
                return '';
            }
        }
        return '';
    }
    add_shortcode('int_art_get_meta_value', 'int_art_get_meta_field_data');
}




