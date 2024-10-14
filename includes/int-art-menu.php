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
        echo '<p>' . __('Enter Airtable API Credentials.', INT_ART_TEXT_DOMAIN) . '</p>';
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


/** This function register credentials fields and section... */

if(  ! function_exists("int_register_settings") ) {

    function int_register_settings() {

        /** Credentials section */

        add_settings_section(
            'int_airtable_credentials_section',
            __('Airtable Credentials', INT_ART_TEXT_DOMAIN),
            'int_airtable_credentials_section_cb',
            'int_airtable_integration'
        );

        add_settings_field(
            'int_airtable_base_id',
            __('Base ID', INT_ART_TEXT_DOMAIN),
            'int_airtable_text_field_cb',
            'int_airtable_integration',
            'int_airtable_credentials_section',
            ['label_for' => 'int_airtable_base_id']
        );

        register_setting('int_airtable_group', 'int_airtable_base_id');


        add_settings_field(
            'int_airtable_table_id',
            __('Table ID or Name', INT_ART_TEXT_DOMAIN),
            'int_airtable_text_field_cb',
            'int_airtable_integration',
            'int_airtable_credentials_section',
            ['label_for' => 'int_airtable_table_id']
        );

        register_setting('int_airtable_group', 'int_airtable_table_id');

        
        add_settings_field(
            'int_airtable_api_token',
            __('API Token', INT_ART_TEXT_DOMAIN),
            'int_airtable_text_field_cb',
            'int_airtable_integration',
            'int_airtable_credentials_section',
            ['label_for' => 'int_airtable_api_token']
        );
        
        register_setting('int_airtable_group', 'int_airtable_api_token');
    }

    add_action('admin_init', 'int_register_settings');
}


/**  This function is a callback function for admin menu section */

if( ! function_exists("int_render_admin_page") ) { 
    
    function int_render_admin_page() {
        ?>
        <div class="wrap"> 

            <!-- Form for Airtable Credentials -->

            <form method="post" action="options.php">
                <?php
                    settings_fields('int_airtable_group');
                    do_settings_sections('int_airtable_integration');
                    submit_button(__('Save Credentials', INT_ART_TEXT_DOMAIN));
                ?>
            </form>

            <?php if ( int_are_airtable_credentials_saved() ) : ?>
                <form method="post" action="">
                    <button type="submit" name="fetch_airtable_data" class="button button-primary">
                        <?php _e('Fetch Airtable Columns Data', INT_ART_TEXT_DOMAIN); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php 

                if (isset($_POST['save_columns'])) {
                    $new_columns = [];
                    if (isset($_POST['column_select']) && is_array($_POST['column_select'])) {
                        foreach ($_POST['column_select'] as $key => $data) {
                            if (isset($data['column_name'], $data['selected'])) {
                                if($data['selected']) {
                                    $new_columns[$key] = [
                                        'column_name'   => sanitize_text_field($data['column_name']),
                                        'selected'      => sanitize_text_field($data['selected'])
                                    ];
                                }
                            }
                        }
                    }
                    
                    update_option('int_column_selected_keys', $new_columns); 


                    /** Hit api to create or update the airtable records */

                    if(  get_option( 'int_column_selected_keys' )  ) {
                        if( int_check_meta_key_exists('title') ) {
                           int_initalize_columns_fetch();
                        }                        
                    }


                }


                if ( int_column_key_exists() ) {

                    $columns_keys = get_option("int_column_keys");
                    $saved_columns = get_option('int_column_selected_keys', []);
                    $saved_columns = $saved_columns ? $saved_columns : [];

                    if ( $columns_keys && is_array($columns_keys) && count($columns_keys) > 0) {
                        ?>
                        <form method="post" action="">
                            <?php foreach ($columns_keys as $key => $col) { 
                                $selected_value = isset($saved_columns[$key]['selected']) ? $saved_columns[$key]['selected'] : ''; 
                                ?>
                                <div id="<?php echo 'int_meta_row-' . $key; ?>">
                                    <input type="text" id="<?php echo strtolower($col) . '-' . $key; ?>" value="<?php echo esc_attr($col); ?>" readonly />
                                    <select name="column_select[<?php echo $key; ?>][selected]" id="<?php echo 'select_' . strtolower($col) . '_' . $key; ?>" class="column-select">
                                        <option value="">Select an option</option>
                                        <option value="title" <?php echo ($selected_value === 'title') ? 'selected' : ''; ?>>Title</option>
                                        <option value="desc" <?php echo ($selected_value === 'desc') ? 'selected' : ''; ?>>Description</option>
                                        <option value="feature_img" <?php echo ($selected_value === 'feature_img') ? 'selected' : ''; ?>>Feature Image</option>
                                        <option value="meta_field" <?php echo ($selected_value === 'meta_field') ? 'selected' : ''; ?>>Meta Field</option>
                                    </select>
                                    <!-- Hidden field for column name -->
                                    <input type="hidden" name="column_select[<?php echo $key; ?>][column_name]" value="<?php echo esc_attr($col); ?>" />
                                </div>
                            <?php } ?>
                            <input type="submit" name="save_columns" value="Save" />
                        </form>
                        <?php
                    }
                }
               
            ?>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const selects = document.querySelectorAll('.column-select');
                        const restrictedValues = ['title', 'desc', 'feature_img'];

                        selects.forEach(select => {
                            select.addEventListener('change', function() {
                                const selectedValue = this.value;
                                if (restrictedValues.includes(selectedValue)) {
                                    selects.forEach(otherSelect => {
                                        if (otherSelect !== this && restrictedValues.includes(otherSelect.value) && otherSelect.value === selectedValue) {
                                            otherSelect.selectedIndex = 0; 
                                        }
                                    });
                                }
                            });
                        });
                    });
                </script>
        </div>
        <?php
    }   
}

/** Fetch and display column names on button click */

if ( isset($_POST['fetch_airtable_data'] ) ) {

    $columns = int_fetch_airtable_column_names();

    /** Update option */
    
    if( get_option('int_column_keys') ) {
        update_option("int_column_keys", $columns );
    }
    else {
        add_option("int_column_keys" , $columns);
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


// echo "<pre>";
//     var_dump(get_option("int_column_selected_keys"));
// echo "</pre>";
// exit;