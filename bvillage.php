<?php
/*
  Plugin Name: B Village
  Description: B Village Plugin experiment in clickjacking defense
  Author: south634
  Version: 1.0.0
 */

// Prevent direct access
defined('ABSPATH') OR exit();

// Include files
require_once (plugin_dir_path(__FILE__) . 'admin.php');
require_once (plugin_dir_path(__FILE__) . 'class-click.php');

// Activate Plugin
register_activation_hook(__FILE__, 'bvillage_activate');
function bvillage_activate()
{
    // Create table for plugin
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'bvillage_ip';
    $sql = "CREATE TABLE $table_name (
                id int UNSIGNED NOT NULL AUTO_INCREMENT,
                ip varchar(45) NOT NULL,
                next_ip_click_time int UNSIGNED NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY (ip)
                ) $charset_collate;";

    $table_name = $wpdb->prefix . 'bvillage_click_time';
    $sql .= "CREATE TABLE $table_name (
                id int UNSIGNED NOT NULL AUTO_INCREMENT,
                global_last_click_time int UNSIGNED NOT NULL,
                global_next_click_time int UNSIGNED NOT NULL,
                total_clicks int UNSIGNED NOT NULL,
                PRIMARY KEY  (id)
                ) $charset_collate;";
    
    $current_time = time();
    $sql .= "INSERT INTO $table_name
            (global_last_click_time, global_next_click_time, total_clicks)
            VALUES ($current_time, $current_time, 0)
            ON DUPLICATE KEY UPDATE id = 1";

    // Include file for dbDelta
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);

    // Set default plugin options if none exist yet
    if (!get_option('bvillage_settings')) {
        $bvillage_settings = array(
            'referrer_add_click_time' => 30,
            'visitor_add_click_time' => 21600,
            'block_referrer' => null,
            'target_click_url' => null,
            'min_add_click_time' => 0,
            'max_add_click_time' => 0,
            'enable_bvillage' => null
        );
        update_option('bvillage_settings', $bvillage_settings);
    }
}

// Deactivate Plugin
register_deactivation_hook(__FILE__, 'bvillage_deactivate');
function bvillage_deactivate()
{
    // Drop table for plugin
    global $wpdb;
    $table_name = $wpdb->prefix . 'bvillage_ip';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $table_name = $wpdb->prefix . 'bvillage_click_time';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// WP Footer
add_action('wp_footer', 'bvillage_click');
function bvillage_click()
{
    // Stop if user is logged in
    if (is_user_logged_in()) {
        return;
    }
    
    $bvillage_settings = get_option('bvillage_settings');
    
    $click = new Click($bvillage_settings);
    
    // Check if click is valid
    if ($click->is_click_valid()) {
        // Save click data to database
        $click->save_click();
        
        // Display iframe
        bvillage_iframe();
    }
}

function bvillage_iframe()
{
    echo '<iframe src="' . plugin_dir_url(__FILE__) . 'trak.php" width="0" height="0" marginwidth="0" marginheight="0" vspace="0" hspace="0" allowtransparency="true" scrolling="no" style="display:none;"></iframe>';
}
