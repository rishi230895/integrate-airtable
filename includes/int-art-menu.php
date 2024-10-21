<?php 

use GuzzleHttp\Client;

/**
 * 
 *  Credentials important option table meta key...
 *  
 *  int_airtable_base_id                -       This key stores the base of airtable in option table
 *  int_airtable_table_id or name       -       This key stores the table id or name in option table.
 *  int_airtable_api_token              -       This key stores access token key in options table.
 * 
 */


/** Callback function for credentials section main heading... */

if( ! function_exists("int_airtable_credentials_section_cb")   ) {
    function int_airtable_credentials_section_cb() {
        echo '<small class="note">' . __('Note: You need to enter all Airtable credentials provided below. If any field is missing, you will not be able to access the <b>Sync Airtable Columns Names</b> section or the <b>Column Field Mapping with API</b> section.', INT_ART_TEXT_DOMAIN) . '</small>';
    }
}


/** Callback function to add text fields */

if(  ! function_exists("int_airtable_text_field_cb") ) {
    function int_airtable_text_field_cb($args) {
        $option = get_option($args['label_for']);
        printf(
            '<input class="cred-field" type="text" id="%s" name="%s" value="%s" />',
            esc_attr($args['label_for']),
            esc_attr($args['label_for']),
            esc_attr($option)
        );
    }
}



/** Debugger method */

if( ! function_exists('int_art_debugger')  ) {
    function int_art_debugger($data) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}


/** This function register credentials fields and section... */

if(  ! function_exists("int_register_settings") ) {

    function int_register_settings() {

        /** Credentials section */

        add_settings_section(
            'int_airtable_credentials_section',
            __('Airtable Credentials', INT_ART_TEXT_DOMAIN),
            'int_airtable_credentials_section_cb',
            'int_airtable_settings'
        );

        add_settings_field(
            'int_airtable_base_id',
            __('Base ID', INT_ART_TEXT_DOMAIN),
            'int_airtable_text_field_cb',
            'int_airtable_settings',
            'int_airtable_credentials_section',
            ['label_for' => 'int_airtable_base_id']
        );

        register_setting('int_airtable_group', 'int_airtable_base_id');


        add_settings_field(
            'int_airtable_table_id',
            __('Table ID or Name', INT_ART_TEXT_DOMAIN),
            'int_airtable_text_field_cb',
            'int_airtable_settings',
            'int_airtable_credentials_section',
            ['label_for' => 'int_airtable_table_id']
        );

        register_setting('int_airtable_group', 'int_airtable_table_id');

        
        add_settings_field(
            'int_airtable_api_token',
            __('API Token', INT_ART_TEXT_DOMAIN),
            'int_airtable_text_field_cb',
            'int_airtable_settings',
            'int_airtable_credentials_section',
            ['label_for' => 'int_airtable_api_token']
        );
        
        register_setting('int_airtable_group', 'int_airtable_api_token');



        /**  ================ Form submission actions  ================  */


        /**
         *  
         * API trigger button action
         * 
         */

        if (isset($_POST['save_columns'])) {
            $new_columns = [];

            if (isset($_POST['column_select']) && is_array($_POST['column_select'])) {
                foreach ($_POST['column_select'] as $key => $data) {
                    if (isset($data['column_name'], $data['selected'])) {
                        if ($data['selected']) {
                            $new_columns[$key] = [
                                'column_name' => sanitize_text_field($data['column_name']),
                                'selected' => sanitize_text_field($data['selected']),
                            ];
                        }
                    }
                }
            }
            
            update_option('int_column_selected_keys', $new_columns); 

            // Hit API to create or update the Airtable records

            if (get_option('int_column_selected_keys')) {
                if (int_check_meta_key_exists('title')) {
                    // Fetch Airtable records from platform, insert or update the records...
                    int_initalize_columns_fetch();
                } else {
                    $message = __('Please choose any one title field name to create a new Airtable record.', INT_ART_TEXT_DOMAIN);
                    int_art_set_error_message($message);
                    return;
                }
            } else {
                $message = __('Please select fields keys.', INT_ART_TEXT_DOMAIN);
                int_art_set_error_message($message);
                return;
            }
        }


       /** 
        *   Credentials save action
        *
        */


        if( isset( $_POST['submit'] ) ) {

            $base_id        = sanitize_text_field( $_POST['int_airtable_base_id'] );
            $access_token   = sanitize_text_field( $_POST['int_airtable_api_token'] );
            $table_or_id    = sanitize_text_field( $_POST['int_airtable_table_id'] );

            delete_option("int_column_keys" );
            delete_option("int_column_selected_keys");

            if( ! $access_token  ||  ! $base_id || ! $table_or_id ) {
                $message = __( 'Bases ID and Table ID or Name and API Token are required.' , INT_ART_TEXT_DOMAIN  );
                int_art_set_error_message($message);

            }
            else {
                $message = __( 'Bases ID and Table ID or Name and API Token are saved.' , INT_ART_TEXT_DOMAIN  );
                int_art_set_success_message($message);
            }
        }


        /**
         * 
         *  Fetch columns names action 
         * 
         */


        if ( isset($_POST['fetch_airtable_data'] ) ) {

            $columns = int_fetch_airtable_column_names();
            $columns = $columns ? $columns : [];

            /** Update option */
            
            if( get_option('int_column_keys') ) {
                update_option("int_column_keys", $columns );
            }
            else {
                add_option("int_column_keys" , $columns);
            }



            /** Admin notice */

            $column_names = get_option('int_column_keys');

            if( ! $column_names ) {
                $message = __( 'Airtable columns names are not synced.' , INT_ART_TEXT_DOMAIN  );
                int_art_set_error_message($message);
            }
            else{
                $message = __( 'Airtable columns names are synced.' , INT_ART_TEXT_DOMAIN  );
                int_art_set_success_message($message);
            }


        }


         /**
         * 
         *  Remove columns names action 
         * 
         */


        if ( isset($_POST['remove_columns_data'] ) ) {

            /** Remove columns names */
            delete_option("int_column_keys" );
            delete_option("int_column_selected_keys");

            $column_names = get_option('int_column_keys');

            /** Admin notice */

            if( ! $column_names ) {
                $message = __( 'Airtable columns names are removed.' , INT_ART_TEXT_DOMAIN  );
                int_art_set_success_message($message);
            }

            if( $column_names ) {
                $message = __( 'Airtable columns names are not removed.' , INT_ART_TEXT_DOMAIN  );
                int_art_set_error_message($message);
            }

        }


        /** For free version allow only limited keys stored. */

        int_art_slice_columns();


    }

    add_action('admin_init', 'int_register_settings');
}



/**  This function is a callback function for admin menu section */

if( ! function_exists("int_render_admin_page") ) { 
    
    function int_render_admin_page() {
        ?>
        <div class="wrap"> 

          <div class="">
            <h2><?php echo __("Airtable Integration Settings" , INT_ART_TEXT_DOMAIN); ?></h2>
            <p class="setting-desc">
                <?php echo __("This page features a credentials section where users must enter their credentials. Once completed, they can access the 'Fetch Columns' section to retrieve columns from Airtable. After that, the admin user can proceed to the 'Field Mapping' section, allowing them to map column names to WordPress post keys." , INT_ART_TEXT_DOMAIN);  ?>
            </p>
          </div>
            <!-- Form for Airtable Credentials -->

            <div class="credential-wrap airtable-fetched-columns-wrap">
                <form method="post" action="options.php">
                    <?php
                        settings_fields('int_airtable_group');
                        do_settings_sections('int_airtable_settings');
                        submit_button(__('Save Credentials', INT_ART_TEXT_DOMAIN));
                    ?>
                </form>
            </div>

          
            <?php if ( int_are_airtable_credentials_saved() ) : ?>
                <div class="airtable-fetched-columns-wrap">

                    <form method="post" action="">
                        <h2><?php echo __("Sync airtable columns names"); ?></h2>
                        <small class="note">
                        <?php 
                            echo __("Note: This button will fetch all columns from your Airtable table and display them in the fields below, allowing you to map your column names with corresponding Airtable posts. When you click this button, it will trigger an API request to fetch all the columns from the Airtable table." , INT_ART_TEXT_DOMAIN); 
                        ?>
                        </small>

                        <div>
                            <button type="submit" name="fetch_airtable_data" class="button button-primary">
                                <?php _e('Fetch Airtable Columns Data', INT_ART_TEXT_DOMAIN); ?>
                            </button>

                            <?php if( get_option('int_column_keys') ) : ?>

                            <button type="submit" name="remove_columns_data" class="button button-primary remove-columns">
                                <?php _e('Remove Airtable Columns Data', INT_ART_TEXT_DOMAIN); ?>
                            </button>

                            <?php endif?>
                        </div>
                        
                    </form>
                    
                </div>             
            <?php endif; ?>


            <?php 

                if ( int_column_key_exists()  && int_are_airtable_credentials_saved() ) {

                    $columns_keys = get_option("int_column_keys");
                    $saved_columns = get_option('int_column_selected_keys', []);
                    $saved_columns = $saved_columns ? $saved_columns : [];

                    // int_art_debugger($saved_columns);

                
                    if ( $columns_keys && is_array($columns_keys) && count($columns_keys) > 0) {
                        ?>
                    
                        <div class="airtable-fetched-columns-wrap">
                            <form method="post" action="">
                            <h2><?php echo __("Column Field Mapping with API"); ?></h2>
                            <small class="note">
                            <?php  
                                echo __(
                                    "In this section, you need to map each Airtable column to the appropriate field types for creating or updating WordPress posts. It is mandatory to select a field for the \"Title\" in order to create an Airtable record in your WordPress post. These fields are mapped to fetch data from Airtable and match it with corresponding fields in WordPress posts.When you click the <b>Create or Update Records</b> button, it will either create new records or update existing ones from Airtable into your WordPress Airtable posts.", 
                                    INT_ART_TEXT_DOMAIN
                                );   
                            ?>

                            </small>
                                <table class="meta-row-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo __( "Column Name" , INT_ART_TEXT_DOMAIN );  ?></th>
                                            <th><?php echo __( "Select Field Key" , INT_ART_TEXT_DOMAIN );  ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $counter = 0;
                                            foreach ($columns_keys as $key => $col) { 
                                            $counter++;
                                            $selected_value = isset($saved_columns[$key]['selected']) ? $saved_columns[$key]['selected'] : ''; 
                                        ?>
                                        <tr id="<?php echo 'int_meta_row-' . $key; ?>">
                                            <!-- Column Name (Readonly Input) -->
                                            <td>
                                                <input type="text" id="<?php echo 'column_name_' . $key; ?>" value="<?php echo esc_attr($col); ?>" readonly />
                                                <!-- Hidden field for column name -->
                                                <input type="hidden" name="column_select[<?php echo $key; ?>][column_name]" value="<?php echo esc_attr($col); ?>" />
                                            </td>
                                            
                                            <!-- Select Option -->
                                            <td>
                                                <select name="column_select[<?php echo $key; ?>][selected]" id="<?php echo 'select_option_' . $key; ?>" class="column-select" <?php echo $counter > INT_ART_FIELDS_ACCESS_COUNT ? "disabled" : ""; ?> >
                                                    <option value="">
                                                        <?php 
                                                            $message = $counter <= INT_ART_FIELDS_ACCESS_COUNT ? 'Select field key' : INT_ART_PRO_FEATURE;
                                                            echo __(  $message , INT_ART_TEXT_DOMAIN ); ?>
                                                    </option>
                                                    <?php if( $counter <= INT_ART_FIELDS_ACCESS_COUNT ) {   ?>
                                                        <option value="title" <?php echo ($selected_value === 'title') ? 'selected' : ''; ?> > 
                                                            <?php echo __("Title" , INT_ART_TEXT_DOMAIN); ?>
                                                        </option>
                                                        <option value="desc" <?php echo ($selected_value === 'desc') ? 'selected' : ''; ?>>
                                                            <?php echo __("Description" , INT_ART_TEXT_DOMAIN); ?>
                                                        </option>
                                                        <option value="feature_img" <?php echo ($selected_value === 'feature_img') ? 'selected' : ''; ?>>
                                                            <?php echo __("Feature Image" , INT_ART_TEXT_DOMAIN); ?>
                                                        </option>
                                                        <option value="meta_field" <?php echo ($selected_value === 'meta_field') ? 'selected' : ''; ?>>
                                                            <?php echo __("Meta Field" , INT_ART_TEXT_DOMAIN); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </td> 
                                            
                                        </tr>
                                        <?php } ?>
                                        <table>
                                            <tr>
                                                <td>
                                                    <input type="submit" name="save_columns" value="Create or Update Records" class="create-post-btn button button-primary"/>
                                                </td>
                                            </tr>
                                        </table>
                                    </tbody>
                                </table>
                             
                            </form>
                        </div>
                        <?php
                    }
                }
            ?>
        </div>
        <?php
    }   
}



/** This function register admin menu in dashboard. */

if(  ! function_exists("int_add_admin_menu")  ) {
    function int_add_admin_menu() {
        add_menu_page(
            __('Airtable Integration', INT_ART_TEXT_DOMAIN),
            __('Airtable Integration', INT_ART_TEXT_DOMAIN),
            'manage_options',
            'int_airtable_settings',
            'int_render_admin_page'
        );
    }
    add_action('admin_menu', 'int_add_admin_menu');
}
