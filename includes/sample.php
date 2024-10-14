<?php

use GuzzleHttp\Client;

// Add admin menu
function int_add_admin_menu() {
    add_menu_page(
        __('Airtable Integration', INT_ART_TEXT_DOMAIN),
        __('Airtable Integration', INT_ART_TEXT_DOMAIN),
        'manage_options',
        'int_airtable_integration',
        'int_render_admin_page'
    );
}
add_action('admin_menu', 'int_add_admin_menu');

// Render Admin Page
function int_render_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Airtable Integration', INT_ART_TEXT_DOMAIN); ?></h1>
        
        <!-- Form for Airtable Credentials -->
        <form method="post" action="options.php">
            <?php
            settings_fields('int_airtable_group');
            do_settings_sections('int_airtable_integration');
            submit_button(__('Save Credentials', INT_ART_TEXT_DOMAIN));
            ?>
        </form>

        <?php if (int_are_credentials_filled()) : ?>
        <!-- Form for Airtable Columns -->
        <form method="post" action="options.php">
            <?php
            settings_fields('int_airtable_columns_group');
            do_settings_sections('int_airtable_columns');
            submit_button(__('Save Columns Keys', INT_ART_TEXT_DOMAIN));
            ?>
        </form>

        <!-- Sync Airtable Data Section -->
        <form method="post" action="">
            <h2><?php _e('Sync Airtable Data', INT_ART_TEXT_DOMAIN); ?></h2>
            <input type="hidden" name="int_sync_airtable" value="1" />
            <?php submit_button(__('Sync Data Airtable', INT_ART_TEXT_DOMAIN)); ?>
        </form>
        <?php endif; ?>

        <!-- Handle sync post request -->
        <?php if (isset($_POST['int_sync_airtable'])) : ?>
            <?php int_sync_airtable_data(); ?>
            <div class="updated">
                <p><?php _e('Airtable data sync has been triggered.', INT_ART_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Check if credentials are filled
function int_are_credentials_filled() {
    
    $base_id = get_option('int_airtable_base_id');
    $table_id = get_option('int_airtable_table_id');
    $api_token = get_option('int_airtable_api_token');

    return !empty($base_id) && !empty($table_id) && !empty($api_token);
}

// Register settings and sections
function int_register_settings() {
    // Credentials Section
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

    // Column Section (conditionally visible)
    if (int_are_credentials_filled()) {
        add_settings_section(
            'int_airtable_columns_section',
            __('Airtable Column Names', INT_ART_TEXT_DOMAIN),
            'int_airtable_columns_section_cb',
            'int_airtable_columns'
        );

        add_settings_field(
            'int_airtable_columns',
            __('Columns', INT_ART_TEXT_DOMAIN),
            'int_airtable_repeater_cb',
            'int_airtable_columns',
            'int_airtable_columns_section'
        );
        register_setting('int_airtable_columns_group', 'int_airtable_columns');
    }
}
add_action('admin_init', 'int_register_settings');

// Callback for Credentials Section
function int_airtable_credentials_section_cb() {
    echo '<p>' . __('Enter Airtable API Credentials.', INT_ART_TEXT_DOMAIN) . '</p>';
}

// Callback for Column Section
function int_airtable_columns_section_cb() {
    echo '<p>' . __('Define the columns for Airtable.', INT_ART_TEXT_DOMAIN) . '</p>';
}

// Text field callback
function int_airtable_text_field_cb($args) {
    $option = get_option($args['label_for']);
    printf(
        '<input class="cred-field" type="text" id="%s" name="%s" value="%s" />',
        esc_attr($args['label_for']),
        esc_attr($args['label_for']),
        esc_attr($option)
    );
}

// Repeater field callback for columns
function int_airtable_repeater_cb() {
    $columns = get_option('int_airtable_columns', []);
    $columns = $columns ? $columns : [];
    $select_options = ['Title', 'Description', 'Feature Image', 'Taxonomy', 'Meta Field'];
    ?>
    <div id="int-repeater-wrapper">
        <?php if (!empty($columns)) : ?>
            <?php foreach ($columns as $index => $column) : ?>
                <div class="int-repeater-row">
                    <input class="cred-field" type="text" name="int_airtable_columns[<?php echo $index; ?>][key]" value="<?php echo esc_attr($column['key']); ?>" />
                    <select name="int_airtable_columns[<?php echo $index; ?>][value]">
                        <?php foreach ($select_options as $option) : ?>
                            <option value="<?php echo int_separate_underscore($option); ?>" <?php selected($column['value'], int_separate_underscore($option)); ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="int-remove-row remove-btn">Remove</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" id="int-add-row"><?php _e('Add Row', INT_ART_TEXT_DOMAIN); ?></button>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle row removal
            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('int-remove-row')) {
                    event.target.closest('.int-repeater-row').remove();
                }
            });

            // Handle adding new row
            let rowIndex = <?php echo count($columns); ?>; // Set index based on the number of existing rows
            document.getElementById('int-add-row').addEventListener('click', function () {
                var wrapper = document.getElementById('int-repeater-wrapper');
                var newRow = document.createElement('div');
                newRow.classList.add('int-repeater-row');
                newRow.innerHTML = `
                    <input class="cred-field" type="text" name="int_airtable_columns[` + rowIndex + `][key]" value="" />
                    <select name="int_airtable_columns[` + rowIndex + `][value]">
                        <option value="<?php echo int_separate_underscore("Title"); ?>">Title</option>
                        <option value="<?php echo int_separate_underscore("Description"); ?>">Description</option>
                        <option value="<?php echo int_separate_underscore("Feature Image"); ?>">Feature Image</option>
                        <option value="<?php echo int_separate_underscore("Meta Field"); ?>">Meta Field</option>
                    </select>
                    <button type="button" class="int-remove-row remove-btn">Remove</button>
                `;
                wrapper.appendChild(newRow);
                rowIndex++; // Increment index for each new row
            });
        });
    </script>
    <?php
}


// Function to handle Airtable data sync


if( ! function_exists( "int_sync_airtable_data" )  ) {

    function int_sync_airtable_data() {

        // Retrieve the credentials
    
        $base_id    = get_option('int_airtable_base_id');
        $table_id   = get_option('int_airtable_table_id');
        $api_token  = get_option('int_airtable_api_token');
        $columns    = get_option('int_airtable_columns', []);
    
        /** Fields added or not... */
    
        if (!$base_id || !$table_id || !$api_token) {
            echo '<div class="error"><p>' . __('Error: Missing Airtable credentials.', INT_ART_TEXT_DOMAIN) . '</p></div>';
            return;
        }
    
        /** Fields added or not... */
    
        if( ! int_is_meta_fields_added() ) {
            echo '<div class="error"><p>' . __( 'Error: Fields not added.', INT_ART_TEXT_DOMAIN ) . '</p></div>';
            return;
        }
    
        try {
    
            $client = new Client();
            $url = 'https://api.airtable.com/v0/'.$base_id.'/'. $table_id;
            $request_params = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_token,
                    'Content-Type'  => 'application/json',
                ],
                'query' => [
                    'pageSize' => 20,
                ]
            ];
    
            $response = $client->get($url, $request_params);
            $records = json_decode($response->getBody()->getContents(), true);
    
            echo "<pre>";
            var_dump($records);
            echo "</pre>";
    
            if( $records && array_key_exists("records" , $records) ) {
                if(  $records["records"] && is_array($records["records"])  ) {
                    $all_records = $records["records"];
                    foreach( $all_records as $rec  ) {
                        if( $rec && array_key_exists("fields", $rec) ) {
                            $fields             = $rec["fields"];
                            $rec_id             = array_key_exists("id", $rec) ? $rec["id"] : "";
                            $rec_createdtime    = array_key_exists("createdTime", $rec) ? $rec["createdTime"] : "";
                        }
                    }
                }
            }
        } 
        catch (Exception $e) {
            echo '<div class="error"><p>' . __('Error: Could not sync data.', INT_ART_TEXT_DOMAIN) . '</p></div>';
        }
    }
}



