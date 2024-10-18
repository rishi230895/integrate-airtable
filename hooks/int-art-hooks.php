<?php

/**
 * Display custom admin notices for error and success messages.
 *
 * This function hooks into the 'admin_notices' action in WordPress and displays
 * either an error or success message. The notice is displayed if a transient
 * ('int_art_error' for errors or 'int_art_success' for success) is set.
 */


if( ! function_exists("int_art_admin_notices") ) {

    
    function int_art_admin_notices() {
        // Retrieve and display the error message, if available
        if ( $error_message = get_transient( 'int_art_error' ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html( $error_message ); ?></p>
            </div>
            <?php
            // Delete the transient after displaying the error message
            delete_transient( 'int_art_error' );
        }

        // Retrieve and display the success message, if available
        if ( $success_message = get_transient( 'int_art_success' ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $success_message ); ?></p>
            </div>
            <?php
            // Delete the transient after displaying the success message
            delete_transient( 'int_art_success' );
        }
    }
    
    // Hook the 'int_art_admin_notices' function into the 'admin_notices' action
    add_action( 'admin_notices', 'int_art_admin_notices' );
}

/**
 * Set an error message to be displayed in the admin area.
 *
 * @param string $message The error message to display.
 */


if( ! function_exists('int_art_set_error_message') ) { 
    function int_art_set_error_message( $message ) {
        set_transient( 'int_art_error', $message, 30 );
    }
}

/**
 * Set a success message to be displayed in the admin area.
 *
 * @param string $message The success message to display.
 */


if( ! function_exists("int_art_set_success_message")) {
    function int_art_set_success_message( $message ) {
        set_transient( 'int_art_success', $message, 30 ); 
    }
}
