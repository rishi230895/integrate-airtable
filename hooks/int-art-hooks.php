<?php

/**
 * Displays custom admin notices for error and success messages.
 *
 * This function hooks into the 'admin_notices' action in WordPress and displays
 * either an error or success message. The notice is displayed if a transient
 * ('int_art_error' for errors or 'int_art_success' for success) is set.
 *
 * @since 1.0.0
 */
if ( ! function_exists("int_art_admin_notices") ) {
    function int_art_admin_notices() {
        // Display error message if set
        if ( $error_message = get_transient( 'int_art_error' ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html( $error_message ); ?></p>
            </div>
            <?php
            delete_transient( 'int_art_error' );
        }

        // Display success message if set
        if ( $success_message = get_transient( 'int_art_success' ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $success_message ); ?></p>
            </div>
            
            <?php
            delete_transient( 'int_art_success' );
        }
    }
    
    add_action( 'admin_notices', 'int_art_admin_notices' , 1);
}

/**
 * Set an error message to be displayed in the admin area.
 *
 * @param string $message The error message to display.
 * @return void
 */
if ( ! function_exists('int_art_set_error_message') ) { 
    function int_art_set_error_message( $message ) {
        set_transient( 'int_art_error', $message, 30 );
    }
}

/**
 * Set a success message to be displayed in the admin area.
 *
 * @param string $message The success message to display.
 *
 * This function sets a transient named 'int_art_success' with the given
 * message and a duration of 30 seconds. The transient can be retrieved and
 * displayed using the `int_art_admin_notices` function, which is hooked into
 * the 'admin_notices' action.
 */
if ( ! function_exists("int_art_set_success_message") ) {
    function int_art_set_success_message( $message ) {
        set_transient( 'int_art_success', $message, 30 ); 
    }
}


/**
 * Renders a script that moves admin notices to the top of the page.
 *
 * The script, which is rendered in the footer of the admin page, selects all
 * admin notices and moves them to the top of the page by appending them to
 * the #notices div. The notices are removed from their original position to
 * prevent them from being displayed twice.
 *
 * This function is hooked into the 'admin_footer' action to ensure that the
 * script is rendered at the appropriate time during the WordPress
 * initialization process.
 *
 * @since 1.0.0
 */

if( ! function_exists('int_art_admin_footer') ) {
    function int_art_admin_footer() {
    ?>
        <script>
            window.onload = function() {
                var notices = Array.from(document.querySelectorAll('.notice'));
                var noticesDiv = document.getElementById('notices');

                if (noticesDiv && notices.length) {      
                    noticesDiv.innerHTML = '';

                    notices.forEach(function(notice) {
                        noticesDiv.innerHTML += notice.outerHTML;
                        notice.remove();
                    });
                }
            }
        </script>
    <?php
    }
    add_action('admin_footer', 'int_art_admin_footer');
}



