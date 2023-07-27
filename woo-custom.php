<?php
/*
Plugin Name: Woo Custom
Description: For adding new field on checkout page and getting that value in orders table
Version: 1.0
Author: Lena Hovhannisyan
*/

if (!defined("ABSPATH")) {
    exit();
}

// Add menu item in admin menu
add_action("admin_menu", "woocustom_admin_menu");

function woocustom_admin_menu() {
    add_menu_page(
        __("Woo Custom", "woocustom-textdomain"),
        __("Woo Custom", "woocustom-textdomain"),
        "manage_woocommerce",
        "woo-custom",
        "woocustom_page_contents",
        "dashicons-forms",
        10
    );
}

// Plugin page content
function woocustom_page_contents() {
    if(isset($_POST['field'])){
        insert_row($_POST['field']);
    }
?>

    <div>
        <h2><?php esc_html_e("Welcome!!!", "woocustom-textdomain"); ?></h2>
    
        <form method="POST">
            <label for="cars">Choose a field type which you want to see on checkout page:</label>
            <select name="field" id="field" require>
                <option value="select">Select Box</option>
                <option value="radio">Check Box</option>
                <option value="text">Text</option>
                <option value="file">File</option>
            </select>
            <br><br>
            <input type="submit" value="Submit">
        </form>
    <div>

<?php
}

// Save data to DB
function insert_row($chosen_field_type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'woo_custom';  

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            field_type VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'field_type' => $chosen_field_type
            )
        );

        print_insert_message(
            $insert_result, 
            "Your custom field type was saved successfully!",
            "Failed to save field type: " . $wpdb->last_error
        );

    } else {
        $existing_row = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");

        if ($existing_row) {
            $insert_result = $wpdb->update(
                $table_name,
                array(
                    'field_type' => $chosen_field_type
                ),
                array(
                    'id' => $existing_row->id,
                )
            );

            print_insert_message(
                $insert_result, 
                "Your custom field type was updated successfully!",
                "Failed to save field type: " . $wpdb->last_error
            );

        } else {
            $insert_result = $wpdb->insert(
                $table_name,
                array(
                    'field_type' => $chosen_field_type
                )
            );

            print_insert_message(
                $insert_result, 
                "Your custom field type was saved successfully!",
                "Failed to save field type: " . $wpdb->last_error
            );
        }
    }
}

// Show saving result 
function print_insert_message($insert_result, $success_message, $error_message) {
    if ($insert_result) {
        echo "<script>
            window.onload = function() {
                alert('$success_message');
            };
        </script>";
    } else {
        echo "<script>
            window.onload = function() {
                alert('$error_message');
            };
        </script>";
    }
}

include( plugin_dir_path( __FILE__ ) . 'checkout-action.php');