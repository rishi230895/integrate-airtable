<?php

use GuzzleHttp\Client;


/**
 * Logs data to a daily debug log file.
 *
 * @param mixed $data The data to be logged. Can be a string, array, or object.
 *
 * This function logs the provided data to a log file in the 'logs' directory.
 * The log file is named with the current date. Each log entry includes a 
 * timestamp indicating when the logging started and ended.
 */

if (! function_exists('int_art_sync_log') ) {
    function int_art_sync_log( $data ) {
        $datetime = new DateTime();
        $current_date = $datetime->format('d_m_Y');
        $current_time = $datetime->format('h:i:s a');

        $logfile = INT_ART_PLUGIN_PATH . 'logs/debug_log_' . $current_date . '.txt';

        if (is_array($data) || is_object($data)) {
            $data = print_r($data, true);
        }

        $file = fopen($logfile, 'a');

        fwrite($file, "\n");
        fwrite($file, "********* START  Date - {$datetime->format('d-F-Y')} | Time - {$current_time} *************\n\n");
        fwrite($file, $data . "\n\n");
        $end_time = $datetime->format('h:i:s a');
        fwrite($file, "********* END    Date - {$datetime->format('d-F-Y')} | Time - {$end_time} *************\n\n");
        fclose($file);
    }
}


/**
 * Converts spaces to underscores and transforms the string to lowercase.
 *
 * @param string $str The input string to be transformed.
 * 
 * @return string The transformed string with spaces replaced by underscores
 *                and all characters in lowercase. Returns an empty string if 
 *                the input is empty.
 */

if( ! function_exists( 'int_separate_underscore' ) ) {
    function int_separate_underscore($str) {
        if( ! $str ) return '';
        return strtolower(str_replace(" ", "_", $str));
    }
}


/**
 * Outputs the given data in a <pre> block using var_dump.
 *
 * This function is useful for debugging purposes.
 *
 * @param mixed $data The data to be output.
 */


if( ! function_exists('int_art_debugger')  ) {

    function int_art_debugger($data) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}


/**
 * Checks if meta fields for Airtable columns have been added.
 *
 * @return bool Returns true (1) if the meta fields are added, false (0) otherwise.
 * 
 * This function checks the WordPress options table for the 'int_airtable_columns'
 * option. If it exists and has a value, the function returns true; otherwise, it returns false.
 */


if( ! function_exists( 'int_is_meta_fields_added' ) ) {
    function int_is_meta_fields_added() {
        $is_fields_added = get_option('int_airtable_columns', []);
        return $is_fields_added ? 1 : 0;
    }
}


/**
 * Sanitizes a string by removing special characters and formatting it.
 *
 * @param string $input The input string to be sanitized.
 *
 * @return string The sanitized string where:
 *                - Non-alphanumeric characters are replaced with spaces.
 *                - The string is converted to lowercase.
 *                - Spaces are replaced with underscores.
 *                - Multiple underscores are reduced to a single underscore.
 *                - Leading and trailing underscores are removed.
 */

if ( ! function_exists('int_sync_sanatize_string') ) {
    function int_sync_sanatize_string($input)
    {
        $input = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $input);
        $input = strtolower($input);
        $input = preg_replace('/\s+/', '_', $input);
        $input = preg_replace('/_+/', '_', $input);
        $input = trim($input, '_');
        return 'int_art_'.$input;
    }
}

/**
 * Checks if all Airtable credential fields are saved.
 *
 * @return bool Returns true if the Airtable Base ID, Table ID, and API Token 
 *              are all saved in the WordPress options table; false otherwise.
 */

if( ! function_exists("int_are_airtable_credentials_saved") ) {
    function int_are_airtable_credentials_saved() {
        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');
        return !empty($base_id) && !empty($table_id) && !empty($api_token);
    }
}

/**
 * Fetches column names (field names) from an Airtable table.
 *
 * @return array|string Returns an array of column names if successful, or a string 
 *                      with an error message if credentials are missing or if there is 
 *                      an issue retrieving the column names.
 *
 * This function checks if the Airtable credentials (Base ID, Table ID, API Token) 
 * are saved. If credentials are valid, it makes a GET request to the Airtable API 
 * to fetch the first record and extract the column names (field names). If there is an
 * issue fetching the column names, it sets an error message and logs it.
 */

if( ! function_exists("int_fetch_airtable_column_names") ) {
    function int_fetch_airtable_column_names() {

        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');

        if ( empty($base_id) || empty($table_id) || empty($api_token) ) {
            $message = __('Bases ID and Table ID or Name and API Token are required.', INT_ART_TEXT_DOMAIN);
            int_art_set_error_message($message);
            int_art_sync_log($message);
            return; 
        }

        $client = new Client([
            'base_uri' => 'https://api.airtable.com/v0/'
        ]);

        try {

            $response = $client->request('GET', "$base_id/$table_id", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_token
                ],
                'query' => [
                    'maxRecords' => 1
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ( ! isset($data['records']) || empty($data['records']) ) {

                delete_option("int_column_keys" );
                delete_option("int_column_selected_keys");

                $message = __( 'No records found in the Airtable table.' , INT_ART_TEXT_DOMAIN);
                int_art_set_error_message($message);
                int_art_sync_log($message);

                return;
            }

            $first_record = $data['records'][0]['fields'];
            $column_names = array_keys($first_record);

            return $column_names;

        } 
        catch (Exception $e) {
            $message = __( 'Error fetching column names: ' . $e->getMessage() , INT_ART_TEXT_DOMAIN);
            int_art_set_error_message($message);
            int_art_sync_log($message);
            return;
        }
    }
}


/**
 * Checks if column keys are saved after fetching from the API.
 *
 * @return bool Returns true (1) if column keys exist in the WordPress options table,
 *              false (0) otherwise.
 *
 * This function verifies whether the 'int_column_keys' option is set, which indicates
 * that columns have been fetched and saved from Airtable.
 */

if( ! function_exists( "int_column_key_exists" ) ) {
    function int_column_key_exists() {
        return get_option("int_column_keys") ? 1 : 0;
    }
}


/**
 * Checks if a meta key exists within the selected column keys.
 *
 * @param string $meta_key The meta key to check.
 * @return array|bool Returns the matching data array if the meta key is found, or false if not found.
 *
 * This function retrieves the selected column keys from the 'int_column_selected_keys' option
 * and checks if the given meta key exists within the selected keys.
 */

if( ! function_exists( 'int_check_meta_key_exists' ) ) {
    function int_check_meta_key_exists( $meta_key ) {
        $meta_data = get_option( 'int_column_selected_keys' );

        if ( ! $meta_data || empty( $meta_key ) ) {
            return false;
        }
        if ( is_array( $meta_data ) && count( $meta_data ) > 0 ) {
            foreach ( $meta_data as $data ) {
                if ( isset( $data['selected'] ) && $data['selected'] === $meta_key ) {
                    return $data;
                }
            }
        }
        return false;
    }
}


/**
 * Removes special characters from a string, allowing only letters, numbers, and underscores.
 *
 * @param string $inputString The input string to be sanitized.
 * @return string The sanitized string with all non-alphanumeric characters 
 *                (except underscores) removed.
 */


if( ! function_exists('int_art_remove_special_chars') ) {

    function int_art_remove_special_chars($inputString) {
        $outputString = preg_replace('/[^A-Za-z0-9_]/', '', $inputString);
        return $outputString;
    }
}


/**
 * Replaces spaces with underscores in a given string.
 *
 * @param string $inputString The input string where spaces need to be replaced.
 * @return string The modified string with all spaces replaced by underscores.
 */

if( ! function_exists('int_art_connect_underscore') ) {
    function int_art_connect_underscore($inputString) {
        $outputString = str_replace(' ', '_', $inputString);
        return $outputString;
    }
}


/**
 * Fetches an image from a given URL, saves it as an attachment in WordPress,
 * and updates the associated post with the attachment ID and thumbnail.
 *
 * @param string $image_url The URL of the image to be fetched.
 * @param int $post_id The ID of the post to which the image will be attached.
 * @return int|WP_Error Returns the attachment ID on success or a WP_Error object on failure.
 *
 * This function validates the image URL, checks for an existing logo attachment,
 * deletes it if found, and retrieves the image data using cURL. It then saves
 * the image to the uploads directory, creates a new attachment in WordPress,
 * and updates the post's metadata and thumbnail.
 */


if ( ! function_exists('int_art_get_attachment_id') ) {
    function int_art_get_attachment_id( $image_url, $post_id ) {
     
        if (filter_var($image_url, FILTER_VALIDATE_URL) === false) {
            $error_message = 'Title - ' . get_the_title() . ' | ' . 'Post ID - ' . $post_id . ' | ' . ' Error -  The provided URL is not valid.';  
            return new WP_Error('invalid_url',  __( $error_message, INT_ART_TEXT_DOMAIN ) );
        }

        // Delete existing attachment if it exists

        $existing_attachment_id = get_post_thumbnail_id($post_id);
        if ( $existing_attachment_id ) {
            $delete_result = wp_delete_attachment($existing_attachment_id, true); 
            if ($delete_result) {
                delete_post_thumbnail($post_id); // Remove from post  
            } 
        }

        // Initialize cURL to fetch the image data
        $ch = curl_init($image_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $image_data = curl_exec($ch);

        // Handle cURL errors
        if (curl_errno($ch)) {

            $message = __( 'Error: image_fetch_failed - ' . json_encode(curl_error($ch)) , INT_ART_TEXT_DOMAIN  );
            int_art_set_error_message($message);
            int_art_sync_log('Error: image_fetch_failed - ' . json_encode(curl_error($ch)));
            return new WP_Error('image_fetch_failed', curl_error($ch));
        }

        // Get the content type of the fetched image
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        // Determine the file extension
        $ext = explode('/', $content_type)[1];
        if (empty($ext)) {
            $message = __( 'Error: invalid_content_type Unable to determine the file extension.' , INT_ART_TEXT_DOMAIN  );
            int_art_set_error_message($message);
            int_art_sync_log('Error: invalid_content_type Unable to determine the file extension.');
            return new WP_Error('invalid_content_type', $message );
        }

        // Prepare the filename and path
        $title = get_the_title($post_id);
        $title = int_art_remove_special_chars($title);
        $filename = 'int_art_sync_logo_' . strtolower($title) . '.' . $ext;
        $filename = int_art_connect_underscore($filename);

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Save the image data to the specified path
        $file_saved = file_put_contents($file_path, $image_data);
        if (!$file_saved) {
            $message = __( 'Error: file_save_failed Failed to save the image to the upload directory.' , INT_ART_TEXT_DOMAIN  );
            int_art_set_error_message($message);
            int_art_sync_log('file_save_failed Failed to save the image to the upload directory.');
            return new WP_Error('file_save_failed', 'Failed to save the image to the upload directory.');
        }

        // Prepare the attachment data
        $attachment_data = array(
            'guid'           => $upload_dir['url'] . '/' . basename($file_path),
            'post_mime_type' => $content_type,
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file_path)),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_parent'    => $post_id 
        );

        // Insert the attachment
        $attachment_id = wp_insert_attachment($attachment_data, $file_path);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate and update attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);

        // Set the post thumbnail
        set_post_thumbnail($post_id, $attachment_id);

    }
}


/**
 * Retrieves a list of user IDs for all administrators.
 *
 * This function fetches all users with the 'administrator' role
 * and returns an array containing their user IDs. If the WordPress
 * function `get_users` is not available, it returns an empty array.
 *
 * @return array List of user IDs of administrators. An empty array if no admin users are found or if the function is not available.
 */


if ( ! function_exists('int_art_get_admin_user_ids') ) {

    function int_art_get_admin_user_ids()
    {
        if (!function_exists('get_users')) {
            return [];
        }

        $args = [
            'role' => 'administrator',
            'fields' => 'ID',
        ];

        $user_ids = get_users($args);
        return $user_ids;
    }
    add_action('admin_init', 'int_art_get_admin_user_ids');
}



/**
 * Deletes posts from the 'air-sync' post type that are not present in the provided list of IDs.
 *
 * This function queries all published posts of the 'air-sync' type that have a meta key
 * 'int_art_column_id' but whose values are not in the given array of `$all_lists_ids`.
 * It permanently deletes each of these posts and logs the deletion along with their IDs
 * and the associated 'int_art_column_id' meta value.
 *
 * @param array $all_lists_ids An array of IDs to be retained. Posts with 'int_art_column_id'
 *                             not in this list will be deleted.
 */
if(  ! function_exists("int_art_delete_company_posts")  ) {
    function int_art_delete_company_posts( $all_lists_ids ) {

         $args = [
            'post_type'      => 'air-sync',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'int_art_column_id',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => 'int_art_column_id',
                    'value'   => $all_lists_ids,
                    'compare' => 'NOT IN',
                ],
            ],
        ];

        $query = new WP_Query( $args );
        $delete_count = 0;

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $air_row_id = get_post_meta( $post_id, 'int_art_column_id', true );
                wp_delete_post( $post_id, true ); // true for permanent deletion
                int_art_sync_log( 'Deleted post ID: ' . $post_id . ' with int_art_column_id: ' . ( $air_row_id ? $air_row_id : 'not set' ) );
                $delete_count++;
            }
        }

        wp_reset_postdata();
    }
}


/** 
 * Creates a new post in the Airtable sync.
 *
 * This function takes an array of data, validates it, and creates a new post of the type 'air-sync'.
 * It also adds various metadata to the post based on the provided data.
 *
 * @param array $data The data to create the post, including title, description, and other metadata.
 * @return int|false The ID of the newly created post on success, or false on failure.
 */
  

if( ! function_exists( "int_create_new_airtable_data" )  ) {

    function int_create_new_airtable_data( $data ) {

        if ( ! $data ) {
            $message = __('Error creating post Airtable: Data is empty.', INT_ART_TEXT_DOMAIN);
            int_art_set_error_message($message);
            int_art_sync_log('Error creating post Airtable: Data is empty - ' . json_encode($data));
            return;
        }

        $author_ids = int_art_get_admin_user_ids();
        $author_id  = $author_ids && is_array($author_ids) ? $author_ids[0] : 1;

        $post_title         = $data['create_post']['title'];
        $post_desc          = $data['create_post']['desc'];
        $post_feature_img   = $data['create_post']['feature_img'];
        $taxonomy_args      = $data['create_post']['taxonomy'];
        
        if( $post_title ) {
            
            $post_data = [
                'post_type'     => 'air-sync',
                'post_status'   => 'publish',
                'post_title'    => $post_title,
                'post_author'   => $author_id,
                'post_content'  => $post_desc ? $post_desc : ''
            ];

            $post_id = wp_insert_post( $post_data );

            /** if error , log the error message */

            if (is_wp_error($post_id)) {
                $message = __('Error creating post Airtable: ' . $post_id->get_error_message() , INT_ART_TEXT_DOMAIN);
                int_art_set_error_message($message);
                int_art_sync_log($message);
                return;
            }

            /** Get image attachment id if image url is exist. */

            if( $post_feature_img && is_array($post_feature_img) ) {
                int_art_get_attachment_id( $post_feature_img[0]['url'] , $post_id);
            }
          
            /** Add meta field */

            if( is_array($data)) {
                foreach( $data as $key => $value) {
                    if( $key != 'create_post' ) {
                        $meta_key = int_sync_sanatize_string($key);
                        update_post_meta( $post_id , $meta_key , $value );
                    }
                }
            }


            /** Assign Terms to the taxonomy */

            if( ! empty( $taxonomy_args ) && is_array( $taxonomy_args) ) {
                foreach(  $taxonomy_args as $key => $value ) {
                    $taxonomy_name = $key;
                    $terms_data    = $value;
                    if( $terms_data && is_array( $terms_data ) ) {
                        foreach( $terms_data as $term ) {
                            $term_id = int_art_get_or_create_term_by_name( $term , $taxonomy_name );
                            if( $term_id && ! empty($term_id)) {
                                wp_set_post_terms( $post_id, [ $term_id ], $taxonomy_name, true );
                            }
                        }
                    }
                }
            }

            int_art_sync_log('Post created:  Post ID - ' . $post_id);

            return $post_id;
        }

        return '';
    }
}


/**
 * Update post data in WordPress based on the Airtable data.
 *
 * If the data is empty, it will log an error message and return.
 *
 * @param array $data The data from Airtable.
 * @param int $post_id The ID of the WordPress post to update.
 *
 * @return void
 */

 if( ! function_exists( "int_update_airtable_data" )  ) {
    function int_update_airtable_data( $data , $post_id ) {

        if ( ! $data ) {
            $message = __('Error update post Airtable: Data is empty.', INT_ART_TEXT_DOMAIN);
            int_art_set_error_message($message);
            int_art_sync_log('Error update post Airtable: Data is empty - ' . json_encode($data));
            return;
        }

        $author_ids         = int_art_get_admin_user_ids();
        $author_id          = $author_ids && is_array($author_ids) ? $author_ids[0] : 1;
        $post_title         = $data['create_post']['title'];
        $post_desc          = $data['create_post']['desc'];
        $post_feature_img   = $data['create_post']['feature_img']; 
        $taxonomy_args      = $data['create_post']['taxonomy'];

        if( $post_title ) {
            
            $post_data = [
                'ID'            => $post_id,
                'post_type'     => 'air-sync',
                'post_status'   => 'publish',
                'post_title'    => $post_title,
                'post_author'   => $author_id,
                'post_content'  =>  $post_desc ?  $post_desc : ''
            ];

            $post_id = wp_update_post( $post_data );

            /** if error , log the error message */

            if (is_wp_error($post_id)) {
                $message = __('Error update post Airtable: ' . $post_id->get_error_message() , INT_ART_TEXT_DOMAIN);
                int_art_set_error_message( $message );
                int_art_sync_log('Error update post Airtable: ' . $post_id->get_error_message() );
                return;
            }

            /** Remove the feature image */

            $existing_attachment_id = get_post_thumbnail_id($post_id);

            if ( $existing_attachment_id ) {
                $delete_result = wp_delete_attachment($existing_attachment_id, true); 
                if ($delete_result) {
                    delete_post_thumbnail($post_id); // Remove from post  
                } 
            }

            /** Get image attachment id if image url is exist. */

            if( $post_feature_img && is_array($post_feature_img) ) {
                int_art_get_attachment_id($post_feature_img[0]['url'], $post_id);
            }
          
            /** Add meta field */

            if( is_array($data)) {
                foreach($data as $key => $value) {
                    if( $key != 'create_post' ) {
                        $meta_key = int_sync_sanatize_string($key);
                        update_post_meta( $post_id , $meta_key , $value );
                    }
                }
            }


            /** Assign Terms to the taxonomy */

            if( ! empty( $taxonomy_args ) && is_array( $taxonomy_args) ) {
                foreach(  $taxonomy_args as $key => $value ) {
                    $taxonomy_name = $key;
                    $terms_data    = $value;
                    if( $terms_data && is_array( $terms_data ) ) {
                        foreach( $terms_data as $term ) {
                            $term_id = int_art_get_or_create_term_by_name( $term , $taxonomy_name );
                            if( $term_id && ! empty($term_id)) {
                                wp_set_post_terms( $post_id, [ $term_id ], $taxonomy_name, true );
                            }
                        }
                    }
                }
            }

            int_art_sync_log( 'Post updated :  Post ID - ' . $post_id );
        }
    }
 }


/** 
 * Check if a post exists with the specified meta key and value.
 *
 * @param string $meta_key The meta key to search for.
 * @param mixed $meta_val The value associated with the meta key.
 * @return array|false Returns the post data if found, or false if not found.
 */

if ( ! function_exists('int_art_check_post') ) {
    function int_art_check_post($meta_key, $meta_val)
    {
        $args = [
            'post_type' => 'air-sync',
            'meta_query' => [
                [
                    'key' => $meta_key,
                    'value' => $meta_val,
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
        ];

        $post_data = get_posts($args);

        if ($post_data) {
            return $post_data;
        }

        return;
    }
}


/**
 * Fetches all column names from an Airtable table and handles pagination with offset.
 *
 * It retrieves the base ID, table ID, and API token from the options table, checks for their validity,
 * and then makes requests to the Airtable API to fetch records in batches. The function processes the records
 * to extract necessary column data and updates or creates posts in WordPress based on the fetched data.
 *
 * @return void
 */

if (!function_exists("int_initalize_columns_fetch")) {
    function int_initalize_columns_fetch() {

        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');
        
        if (empty($base_id) || empty($table_id) || empty($api_token)) {

            $message = __( 'Bases ID and Table ID or Name and API Token are required.' , INT_ART_TEXT_DOMAIN  );
            int_art_set_error_message($message);
            int_art_sync_log($message);
            return;
        }
        
        $client = new Client([
            'base_uri' => 'https://api.airtable.com/v0/'
        ]);
        
        $all_records = [];
        $offset = null;
        $post_create = 0;
        $post_update = 0;
        
        try {
            do {
                $query_params = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_token
                    ],
                    'query' => [
                        'pageSize' => 100,
                    ]
                ];

                if ($offset) {
                    $query_params['query']['offset'] = $offset;
                }

                $response = $client->request('GET', "$base_id/$table_id", $query_params);
                $data = json_decode($response->getBody(), true);


                /**
                 *   
                 *  From api if no columns are fetched...
                 * 
                 */

                if (!isset($data['records']) || empty($data['records'])) {

                    $message = __( 'Fields are not fetched from api.' , INT_ART_TEXT_DOMAIN  );
                    int_art_set_error_message($message);

                    return;
                }

                $all_records = array_merge($all_records, $data['records']); 
                $offset = isset($data['offset']) ? $data['offset'] : null;

                if ($all_records && is_array($all_records)) {

                    // Initializing column variables
                    $title_column_name       = "";
                    $feature_column_name     = "";
                    $desc_column_name        = "";
                    $meta_field_columns_name = [];

                    // Dynamically get column names
                    $title_column_name      = int_check_meta_key_exists('title')['column_name'] ?? '';
                    $feature_column_name    = int_check_meta_key_exists('feature_img')['column_name'] ?? '';
                    $desc_column_name       = int_check_meta_key_exists('desc')['column_name'] ?? '';
        

                    // Get all selected meta fields
                    $columns_keys = get_option('int_column_selected_keys');

                    if ($columns_keys) {
                        foreach ($columns_keys as $key) {
                            if ($key['selected'] == 'meta_field') {
                                $meta_field_columns_name[] = $key['column_name'];
                            }
                        }
                    }

                    $taxonomy_columns = int_art_fetch_taxonomy_keys();

                    // int_art_debugger($all_records);
                    // exit;

                    foreach ($all_records as $field) {
                        if (  array_key_exists("fields", $field)) {
                            $field_data = $field['fields'];
                            if( $field_data ) { 
                                $prepare_data = [];
                                $id = $field['id'] ?? '';
                                $created_time = $field['createdTime'] ?? '';
                                $title = $title_column_name && isset($field_data[$title_column_name]) ? $field_data[$title_column_name] : '';
                                $feature_img = $feature_column_name && isset($field_data[$feature_column_name]) ? $field_data[$feature_column_name] : '';
                                $desc = $desc_column_name && isset($field_data[$desc_column_name]) ? $field_data[$desc_column_name] : '';
                                $taxonomy_data = [];
    
                                if ($id) {
                                    $prepare_data['Column id'] = $id;
                                }
    
                                if ($created_time) {
                                    $prepare_data['Created Time'] = $created_time;
                                }
    
                                if (!empty($meta_field_columns_name)) {
                                    foreach ($meta_field_columns_name as $col_name) {
                                        $prepare_data[$col_name] = $field_data[$col_name] ?? '';
                                    }
                                }
    
                                /** Taxonomy */
    
                                if( ! empty( $taxonomy_columns ) && is_array( $taxonomy_columns ) ) {
                                    foreach( $taxonomy_columns as $tax_field_name ) {
                                        
                                        // Check if the key exists in $field_data before accessing it
    
                                        if (isset($field_data[ $tax_field_name ])) {
                                            $tax_data = $field_data[ $tax_field_name ];
                                            $meta_key = int_art_split_into_hypens( $tax_field_name );
                                            $temp_data = [];
                                
                                            if( ! empty( $tax_data ) && is_array( $tax_data ) ) {
                                                foreach( $tax_data as $key => $value ) {
                                                    $temp_data[] = $value;
                                                }
                                            }
                                
                                            if( ! empty( $tax_data ) && ( is_numeric( $tax_data ) || is_string( $tax_data ) ) ) {
                                                $temp_data[] = $tax_data;
                                            }
                                
                                            if( $temp_data ) {
                                                $taxonomy_data[$meta_key] = $temp_data;
                                            }
                                        }
                                    }
                                }
                                
                                $post_creation = [
                                    'id'            => $id,
                                    'title'         => $title,
                                    'desc'          => $desc,
                                    'feature_img'   => $feature_img,
                                    'taxonomy'      => $taxonomy_data
                                ];
    
                                $prepare_data['create_post'] = $post_creation;
    
                                $post_data = int_art_check_post('int_art_column_id', $id);
    
                                if ($post_data) {
                                    $post_id = $post_data[0]->ID;
                                    int_update_airtable_data($prepare_data, $post_id);
                                    $post_update++;
                                } 
                                else {
                                    int_create_new_airtable_data($prepare_data);
                                    $post_create++;
                                }
                            }
                        }
                    }
                }
                
            } while ($offset);

        } catch (Exception $e) {
            int_art_set_error_message($e->getMessage());
            int_art_sync_log($e->getMessage());
            return;
        }


        $message = __("Total update records - " . $post_update . " | Total new create records - " . $post_create , INT_ART_TEXT_DOMAIN);
        int_art_set_success_message( $message);
        int_art_sync_log( $message );


        // Optionally delete posts that no longer exist in Airtable...

        int_art_synchronization_companies();
    }
}


/**
 * This function synchronizes companies from airtable to wordpress posts.
 * It fetches all records from airtable, loops through each record, creates or updates
 * a post with the same id, title, description, and feature image. It also sets the
 * post meta with the column names and respective values. After all records are processed,
 * it removes companies from airtable posts which added in airtable platform.
 *
 * @return void
 */

if( ! function_exists("int_art_synchronization_companies") ) {
    function int_art_synchronization_companies() {

        set_time_limit(0);

        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');

        if (empty($base_id) || empty($table_id) || empty($api_token)) {

            $message = __( 'Bases ID and Table ID or Name and API Token are required.' , INT_ART_TEXT_DOMAIN  );
            int_art_set_error_message($message);
            int_art_sync_log($message);
            return;
        }

        $all_lists_ids = [];
        $total_records = 0; // Counter to track total records

        $client = new Client([
            'base_uri' => 'https://api.airtable.com/v0/'
        ]);
 

        $is_offset = true;
        $offset = null; // Initialize offset

        while( $is_offset ) {
            try {
                // Add the 'offset' query param only if it's set
                $query_params = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_token
                    ],
                    'query' => [
                        'pageSize' => 100,
                    ]
                ];

                // If there's an offset, add it to the request

                if( $offset ) {
                    $request_params['query']['offset'] = $offset;
                }

                $response = $client->request('GET', "$base_id/$table_id", $query_params);
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);

                if( ! $data ) {
                    int_art_set_error_message('Error: Fetch Columns - data is empty');
                    int_art_sync_log('Error: Fetch Columns - data is empty.' . json_encode($data));
                    return;
                }

                if( array_key_exists('records', $data) ) {
                    foreach( $data['records'] as $row_data ) {
                        if( $row_data ) {
                            if( array_key_exists('id', $row_data) ) {
                                $all_lists_ids[] = $row_data['id'];
                                $total_records++;
                            }
                        }
                    }
                }

                // Check for the 'offset' in the response to continue fetching
                if( array_key_exists('offset', $data) ) {
                    $offset = $data['offset'];
                } else {
                    $is_offset = false;
                }

            } 
            catch (GuzzleHttp\Exception\RequestException $e) {
                int_art_set_error_message($e->getMessage());
                int_art_sync_log($e->getMessage()); 
                $is_offset = false;
            }
        }

        /** Remove companies from airtable posts which added in airtable platform */

        int_art_delete_company_posts( $all_lists_ids );

    }
}


/**
 * Slice the columns array stored in the "int_column_selected_keys" option down to the maximum number of columns that can be accessed.
 * This is necessary because the Airtable API limits the number of fields that can be accessed in a single request, and we need to make sure that we don't exceed that limit.
 *
 * The function first checks if the "int_column_selected_keys" option exists and if it is an array.
 * If it does, it checks if the count of the array exceeds the maximum number of columns that can be accessed.
 * If it does, it loops through the array and adds only the first INT_ART_FIELDS_ACCESS_COUNT columns to a temporary array.
 * Finally, it updates the "int_column_selected_keys" option with the temporary array.
 */

if( ! function_exists("int_art_slice_columns") ) {
    function int_art_slice_columns() {
        $columns = get_option("int_column_selected_keys");
        if( $columns ) {
            if( is_array( $columns) ) {
                if( count( $columns ) > INT_ART_FIELDS_ACCESS_COUNT ) {
                    $temp = [];
                    $counter = 0;
                    foreach(  $columns as $col ) {
                        if( $counter > INT_ART_FIELDS_ACCESS_COUNT ) break; 
                        $temp[] = $col;
                        $counter++;
                    }
                    update_option( "int_column_selected_keys", $temp );
                }
            }
        }
    }
}


/**
 * Retrieves all distinct meta keys from the WordPress database that match a given prefix.
 *
 * @param string $prefix The prefix to search for in the meta keys.
 * @return array An array of distinct meta keys that match the given prefix.
 */

if( ! function_exists('int_art_get_meta_keys') ) {
    function int_art_get_meta_keys($prefix = 'int_art_') {
        global $wpdb;
        $meta_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
                $wpdb->esc_like($prefix) . '%'
            )
        );
        return $meta_keys;
    }
}


/**
 * Splits a meta key string by underscores after removing a specific prefix and capitalizes each word.
 *
 * This function takes a meta key, removes the 'int_art_' prefix, splits the remaining string by underscores,
 * and then returns the resulting words as a single string with each word capitalized.
 *
 * @param string $meta_key The meta key to be transformed.
 * @return string Returns the transformed string with capitalized words, or 0 if the input meta key is empty.
 */

if( ! function_exists("int_art_split_meta_key") ) {
    function int_art_split_meta_key($meta_key) {
        if( ! $meta_key ) return 0;
        $meta_key = str_replace("int_art_", "", $meta_key);
        $exploded = explode( "_" , $meta_key );
        $imploded = implode(" " , $exploded );
        return ucwords( $imploded );
    }
}


/**
 * Fetches taxonomy column names from saved columns in the option table.
 *
 * Retrieves the saved column names from the 'int_column_selected_keys' option, loops through the array to find the taxonomy column names, and returns them in an array.
 *
 * @return array An array of taxonomy column names.
 */

 if( ! function_exists('int_art_fetch_taxonomy_keys') ) {
    function int_art_fetch_taxonomy_keys() {

        $taxonomies = [];
        $columns_names  = get_option("int_column_selected_keys");
        $columns_names  = $columns_names ? $columns_names : [];

        if( $columns_names && is_array( $columns_names ) ) {

            foreach( $columns_names as $col ) {
                if( $col['selected'] == 'taxonomy' ) {
                    $taxonomies[] = $col['column_name'];
                }
            }
        }

        return $taxonomies;
    }
}


/**
 * Registers dynamic taxonomies based on the column names that are selected as taxonomies in the Airtable integration settings.
 *
 * This function registers a taxonomy for each column name that is selected as a taxonomy in the Airtable integration settings.
 * The taxonomy name is the column name with spaces and special characters replaced, and the taxonomy slug is the taxonomy name
 * with spaces and special characters replaced and converted to lowercase.
 *
 * @since 1.0.0
 */

 
if( ! function_exists("int_art_register_taxonomy") ) {
    function int_art_register_taxonomy() {

        $taxonomies = int_art_fetch_taxonomy_keys();
        
        if( $taxonomies  ) {

            foreach( $taxonomies as $taxonomy_name ) {

                $taxonomy_name = sanitize_text_field($taxonomy_name);
                $taxonomy_name = ucwords($taxonomy_name);

                $taxonomy_slug = sanitize_title($taxonomy_name);
                $taxonomy_slug = int_art_split_into_hypens( $taxonomy_slug );

                if ( ! taxonomy_exists($taxonomy_slug) ) {

                    $taxonomy_labels = array(
                        'name'              => sprintf( _x('%s Categories', 'taxonomy general name', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'singular_name'     => sprintf( _x('%s Category', 'taxonomy singular name', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'search_items'      => sprintf( __('Search %s Categories', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'all_items'         => sprintf( __('All %s Categories', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'parent_item'       => sprintf( __('Parent %s Category', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'parent_item_colon' => sprintf( __('Parent %s Category:', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'edit_item'         => sprintf( __('Edit %s Category', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'update_item'       => sprintf( __('Update %s Category', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'add_new_item'      => sprintf( __('Add New %s Category', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'new_item_name'     => sprintf( __('New %s Category Name', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                        'menu_name'         => sprintf( __('%s Categories', INT_ART_TEXT_DOMAIN), $taxonomy_name ),
                    );

                    $taxonomy_args = array(
                        'hierarchical'      => true,
                        'labels'            => $taxonomy_labels,
                        'show_ui'           => true,
                        'show_admin_column' => true,
                        'query_var'         => true,
                        'rewrite'           => array( 'slug' => $taxonomy_slug ),
                    );

                    register_taxonomy( $taxonomy_slug, array( 'air-sync' ), $taxonomy_args );
                }
            }
        }
    }
}

/**
 * Unregisters taxonomies and deletes all associated terms.
 *
 * This function retrieves taxonomy keys from the Airtable integration settings,
 * sanitizes and formats them into slugs, and checks if each taxonomy exists. 
 * If a taxonomy exists, it retrieves all associated terms and deletes them from 
 * the WordPress database. Finally, it unregisters the taxonomy.
 *
 * The function is useful for cleaning up dynamic taxonomies and terms that 
 * were registered and created through the integration.
 *
 * @return void
 */

if ( ! function_exists( "int_art_unregister_taxonomies_and_delete_terms" ) ) {
    function int_art_unregister_taxonomies_and_delete_terms() {
        $taxonomies = int_art_fetch_taxonomy_keys();
        if ( $taxonomies ) {
            foreach ( $taxonomies as $taxonomy_name ) {
                $taxonomy_name = sanitize_text_field( $taxonomy_name );
                $taxonomy_slug = sanitize_title( ucwords( $taxonomy_name ) );
                $taxonomy_slug = int_art_split_into_hypens( $taxonomy_slug );
                if ( taxonomy_exists( $taxonomy_slug ) ) {
                    $terms = get_terms( [
                        'taxonomy'   => $taxonomy_slug,
                        'hide_empty' => false,
                    ] );

                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) {
                            wp_delete_term( $term->term_id, $taxonomy_slug );
                        }
                    }
                    unregister_taxonomy( $taxonomy_slug );
                }
            }
        }
    }
}


/**
 * Converts a string to lowercase, splits it by spaces, and joins the words with hyphens.
 *
 * @param string $str The input string to be transformed.
 * 
 * @return string The transformed string with words joined by hyphens, or an empty string if input is empty.
 */

if( ! function_exists('int_art_split_into_hypens') ) {
    function int_art_split_into_hypens($str) {
        if(! $str ) return '';
        $str = strtolower($str);
        $words = explode(' ', $str);
        return implode('-', $words);    
    }   
}   



/**
 * Retrieves the ID of a term by name, or creates a new term if none exists
 *
 * @param string $term_name The name of the term to retrieve or create
 * @param string $taxonomy_name The name of the taxonomy to which the term belongs
 *
 * @return int|false The ID of the term if found, the new term ID if created, or false if an error occurred
 */


if( ! function_exists('int_art_get_or_create_term_by_name') ) {
    function int_art_get_or_create_term_by_name( $term_name, $taxonomy_name ) {
       
        $term = term_exists( $term_name, $taxonomy_name );
        if ( $term !== 0 && $term !== null ) {
            return is_array( $term ) ? $term['term_id'] : $term;
        }
        
        $new_term = wp_insert_term( $term_name, $taxonomy_name );
        
        if ( ! is_wp_error( $new_term ) ) {
            return $new_term['term_id'];
        }
        
        return false;
    }      
}

/**
 * Retrieves custom meta fields associated with a post by a given prefix.
 *
 * @param int $post_id The ID of the post to retrieve custom meta fields from.
 * @param string $prefix The prefix to filter the meta fields by. Defaults to 'int_art_'.
 *
 * @return array An associative array of custom meta fields, where the keys are the field names and the values are arrays of field values.
 */

if(  ! function_exists('int_art_get_custom_meta_keys_by_prefix') ) {
    function int_art_get_custom_meta_keys_by_prefix( $post_id, $prefix = 'int_art_' ) {
        $all_meta = get_post_meta( $post_id );
        $filtered_meta = [];
        foreach ( $all_meta as $meta_key => $meta_values ) {
            if ( strpos( $meta_key, $prefix ) === 0 ) {
                $filtered_meta[] = $meta_key;
            }
        }
        return $filtered_meta;
    }
}


/**
 * Retrieves an array of unique custom meta field names associated with a given post type, filtered by a given prefix.
 *
 * @param string $cpt_name The name of the custom post type to retrieve custom meta fields from. Defaults to 'air-sync'.
 * @param string $prefix The prefix to filter the meta fields by. Defaults to 'int_art_'.
 *
 * @return array An array of unique custom meta field names associated with the given post type and prefix.
 */

if ( ! function_exists('int_get_unique_meta_keys_for_cpt') ) {
    function int_get_unique_meta_keys_for_cpt( $cpt_name = 'air-sync', $prefix = 'int_art_' ) {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_key 
            FROM $wpdb->postmeta 
            WHERE post_id IN (
                SELECT ID 
                FROM $wpdb->posts 
                WHERE post_type = %s 
                AND post_status = 'publish'
            )
            AND meta_key LIKE %s",
            $cpt_name,
            $wpdb->esc_like( $prefix ) . '%'
        );

        $results = $wpdb->get_col( $query );

        return $results;
    }
}

