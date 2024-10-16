<?php

use GuzzleHttp\Client;

/**
 * Deletes the thumbnail image and its attachment for a specific post.
 *
 * @param int $post_id The ID of the post from which to delete the thumbnail and attachment.
 */

if( ! function_exists( 'int_art_delete_post_thumbnail_and_attachment' ) ) {
    function int_art_delete_post_thumbnail_and_attachment($post_id) {
        
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            delete_post_thumbnail($post_id);
            wp_delete_attachment($thumbnail_id, true);
        } 
        else {

        }
    }
    
}


/**
 * Logs data to a daily debug log file.
 *
 * @param mixed $data The data to be logged. Can be a string, array, or object.
 *
 * This function logs the provided data to a log file in the 'logs' directory.
 * The log file is named with the current date. Each log entry includes a 
 * timestamp indicating when the logging started and ended.
 */

if (!function_exists('int_art_sync_log')) {

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
 * to fetch the first record and extract the column names (field names).
 */


if( ! function_exists("int_fetch_airtable_column_names") ) {
    function int_fetch_airtable_column_names() {

        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');

        if ( empty($base_id) || empty($table_id) || empty($api_token) ) {
            return __('Airtable credentials are missing.', INT_ART_TEXT_DOMAIN );
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

                /** Update option */

                if( get_option('int_column_keys') ) {
                    update_option("int_column_keys", "");
                }
                else {
                    add_option("int_column_keys" , "");
                }

                return __( 'No records found in the Airtable table.', INT_ART_TEXT_DOMAIN );
            }

            $first_record = $data['records'][0]['fields'];
            $column_names = array_keys($first_record);

            return $column_names;

        } 
        catch (Exception $e) {
            return __('Error fetching column names: ', INT_ART_TEXT_DOMAIN) . $e->getMessage();
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
     
        // Validate the provided URL
        if (filter_var($image_url, FILTER_VALIDATE_URL) === false) {
            return new WP_Error('invalid_url', 'The provided URL is not valid.');
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
            int_art_sync_log('Error: image_fetch_failed - ' . json_encode(curl_error($ch)));
            return new WP_Error('image_fetch_failed', curl_error($ch));
        }

        // Get the content type of the fetched image
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        // Determine the file extension
        $ext = explode('/', $content_type)[1];
        if (empty($ext)) {
            int_art_sync_log('Error: invalid_content_type Unable to determine the file extension.');
            return new WP_Error('invalid_content_type', 'Unable to determine the file extension.');
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


if (!function_exists('int_art_get_admin_user_ids')) {

    function int_art_get_admin_user_ids()
    {
        // Ensure WordPress is fully loaded
        if (!function_exists('get_users')) {
            return []; // Return an empty array if get_users is not available
        }

        $args = [
            'role' => 'administrator',
            'fields' => 'ID', // Only get user IDs
        ];

        $user_ids = get_users($args);
        return $user_ids;
    }
}

add_action('init', 'int_art_get_admin_user_ids');



/** Delete posts which is not present in airtable  */


if(  ! function_exists("int_art_delete_company_posts")  ) {

    function int_art_delete_company_posts( $all_lists_ids ) {

         $args = [
            'post_type'      => 'air-sync',
            'posts_per_page' => -1, // Fetch all matching posts
            'post_status'    => 'publish',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'int_art_column_id',
                    'compare' => 'EXISTS', // No meta key
                ],
                [
                    'key'     => 'int_art_column_id',
                    'value'   => $all_lists_ids,
                    'compare' => 'NOT IN',     // Meta key exists but doesn't match any ID in the array
                ],
            ],
        ];

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $air_row_id = get_post_meta( $post_id, 'int_art_column_id', true );
                wp_delete_post( $post_id, true ); // true for permanent deletion
                int_art_sync_log( 'Deleted post ID: ' . $post_id . ' with int_art_column_id: ' . ( $air_row_id ? $air_row_id : 'not set' ) );
            }
        }

        // Reset post data after the custom query
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
            int_art_sync_log('Error creating post Airtable: Data is empty - ' . json_encode($data));
            return false;
        }

        $author_ids = int_art_get_admin_user_ids();
        $author_id  = $author_ids && is_array($author_ids) ? $author_ids[0] : 1;

        $post_title         = $data['create_post']['title'];
        $post_desc          = $data['create_post']['desc'];
        $post_feature_img   = $data['create_post']['feature_img'];

        if( $post_title ) {
            
            $post_data = [
                'post_type'     => 'air-sync',
                'post_status'   => 'publish',
                'post_title'    => $post_title,
                'post_author'   => $author_id,
                'post_content'  => $post_desc
            ];

            $post_id = wp_insert_post( $post_data );

            /** if error , log the error message */

            if (is_wp_error($post_id)) {
                int_art_sync_log('Error creating post Airtable: ' . $post_id->get_error_message());
                return false;
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

            int_art_sync_log('Post created:  Post ID - ' . $post_id);

            return $post_id;
        }

        return '';
    }
}


/**
 * @return void  
 * Update airtable columns posts  
 */

 if( ! function_exists( "int_update_airtable_data" )  ) {

    function int_update_airtable_data( $data , $post_id ) {
        if ( ! $data ) {
            int_art_sync_log('Error creating post Airtable: Data is empty - ' . json_encode($data));
            return false;
        }

        $author_ids = int_art_get_admin_user_ids();
        $author_id  = $author_ids && is_array($author_ids) ? $author_ids[0] : 1;

        $post_title         = $data['create_post']['title'];
        $post_desc          = $data['create_post']['desc'];
        $post_feature_img   = $data['create_post']['feature_img']; 

        if( $post_title ) {
            
            $post_data = [
                'ID'            => $post_id,
                'post_type'     => 'air-sync',
                'post_status'   => 'publish',
                'post_title'    => $post_title,
                'post_author'   => $author_id,
                'post_content'  => $post_desc
            ];

            $post_id = wp_update_post( $post_data );

            /** if error , log the error message */

            if (is_wp_error($post_id)) {
                int_art_sync_log('Error update post Airtable: ' . $post_id->get_error_message());
                return false;
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

        return false;
    }
}


/** 
 * Function to fetch all column names from an Airtable table 
 * and handle pagination with offset. 
 * 
 * It retrieves the base ID, table ID, and API token from the 
 * options table, checks for their validity, and then makes 
 * requests to the Airtable API to fetch records in batches. 
 * The function processes the records to extract necessary 
 * column data and updates or creates posts in WordPress 
 * based on the fetched data.
 */

 if (!function_exists("int_initalize_columns_fetch")) {

    function int_initalize_columns_fetch() {

        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');
        
        if (empty($base_id) || empty($table_id) || empty($api_token)) {
            return __('Airtable credentials are missing.', INT_ART_TEXT_DOMAIN);
        }
        
        $client = new Client([
            'base_uri' => 'https://api.airtable.com/v0/'
        ]);
        
        $all_records = [];
        $offset = null;
        
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

                if (!isset($data['records']) || empty($data['records'])) {
                    if (get_option('int_column_keys')) {
                        update_option("int_column_keys", "");
                    } else {
                        add_option("int_column_keys", "");
                    }
                    return __('No records found in the Airtable table.', INT_ART_TEXT_DOMAIN);
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
                    $title_column_name = int_check_meta_key_exists('title')['column_name'] ?? '';
                    $feature_column_name = int_check_meta_key_exists('feature_img')['column_name'] ?? '';
                    $desc_column_name = int_check_meta_key_exists('desc')['column_name'] ?? '';

                    // Get all selected meta fields
                    $columns_keys = get_option('int_column_selected_keys');

                    if ($columns_keys) {
                        foreach ($columns_keys as $key) {
                            if ($key['selected'] == 'meta_field') {
                                $meta_field_columns_name[] = $key['column_name'];
                            }
                        }
                    }

                    // Process each record
                    foreach ($all_records as $field) {
                        if (array_key_exists("fields", $field)) {
                            $field_data = $field['fields'];
                            $prepare_data = [];

                            // Safely extract data from $field_data with default fallback values

                            $id = $field['id'] ?? '';
                            $created_time = $field['createdTime'] ?? '';
                            $title = $title_column_name && isset($field_data[$title_column_name]) ? $field_data[$title_column_name] : '';
                            $feature_img = $feature_column_name && isset($field_data[$feature_column_name]) ? $field_data[$feature_column_name] : '';
                            $desc = $desc_column_name && isset($field_data[$desc_column_name]) ? $field_data[$desc_column_name] : '';


                            // Prepare data for post creation or update
                            if ($id) {
                                $prepare_data['Column id'] = $id;
                            }
                            if ($created_time) {
                                $prepare_data['Created Time'] = $created_time;
                            }
                            if (!empty($meta_field_columns_name)) {
                                foreach ($meta_field_columns_name as $col_name) {
                                    $prepare_data[$col_name] = $field_data[$col_name] ?? '';  // Use default empty value if not found
                                }
                            }

                            // Prepare post creation array
                            $post_creation = [
                                'id'            => $id,
                                'title'         => $title,
                                'desc'          => $desc,
                                'feature_img'   => $feature_img
                            ];

                            $prepare_data['create_post'] = $post_creation;

                            // Check if post exists and update or create new

                            $post_data = int_art_check_post('int_art_column_id', $id);

                            if ($post_data) {
                                $post_id = $post_data[0]->ID;
                                int_update_airtable_data($prepare_data, $post_id);
                            } 
                            else {
                                int_create_new_airtable_data($prepare_data);
                            }
                        }
                    }
                }
                
            } while ($offset);

        } catch (Exception $e) {
            return __('Error fetching column names: ', INT_ART_TEXT_DOMAIN) . $e->getMessage();
        }

        // Optionally delete posts that no longer exist in Airtable...

        int_art_synchronization_companies();
    }
}


 /** 
  *   Delete airtable data
  */



  if( ! function_exists("int_art_synchronization_companies") ) {

    function int_art_synchronization_companies() {

        set_time_limit(0);

        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');

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
                    int_art_sync_log('Error: Fetch List - data is empty' . json_encode($data));
                    return false;
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

            } catch (GuzzleHttp\Exception\RequestException $e) {
                int_art_sync_log($e->getMessage());
                $is_offset = false;
            }
        }

        /** Remove companies from airtable posts which added in airtable platform */

        int_art_delete_company_posts( $all_lists_ids );

    }
}
