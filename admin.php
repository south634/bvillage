<?php
add_action('admin_menu', 'bvillage_admin_actions');

function bvillage_admin_actions()
{
    add_options_page('B Village', 'B Village', 'manage_options', __FILE__, 'bvillage_admin');
}

function bvillage_admin()
{
    // If BVillage settings form was submitted
    if (isset($_POST['bvillage_submit'])) {
        // Submit settings to DB and get message
        $submit_msg = submit_bvillage_settings();
    }

    // Get BVillage Settings stored in WP Options
    $bvillage_settings = get_option('bvillage_settings');

    $referrer_add_click_time = $bvillage_settings['referrer_add_click_time'];
    $visitor_add_click_time = $bvillage_settings['visitor_add_click_time'];
    $block_referrer = $bvillage_settings['block_referrer'];
    $target_click_url = $bvillage_settings['target_click_url'];
    $min_add_click_time = $bvillage_settings['min_add_click_time'];
    $max_add_click_time = $bvillage_settings['max_add_click_time'];
    $enable_bvillage = $bvillage_settings['enable_bvillage'];
    
    ?>
    <div class="wrap">
        <h2>B Village Settings</h2>
        <h3>Click ranking plugin</h3>
        <?php
        if (isset($submit_msg)) {
            echo $submit_msg;
        }
        ?>
        <form action="" method="POST">
            <table class="widefat" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th colspan="2">Repeat Visitor Click Delays (Seconds)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <ul>
                                <li>Target Click URL: <input type="text" name="target_click_url" <?php
                                    if (isset($target_click_url)) {
                                        echo ' value="' . esc_attr($target_click_url) . '"';
                                    }
                                    ?> /></li>
                                <li>Block 1st Click From: <input type="text" name="block_referrer" <?php
                                    if (isset($block_referrer)) {
                                        echo ' value="' . esc_attr($block_referrer) . '"';
                                    }
                                    ?> /></li>                                
                            </ul>
                        </td>
                        <td>
                            <ul>
                                <li>Referrer Click Delay: <input type="text" name="referrer_add_click_time" size="5" <?php
                                    if (isset($referrer_add_click_time)) {
                                        echo ' value="' . esc_attr($referrer_add_click_time) . '"';
                                    }
                                    ?> /></li>
                                <li>Visitor IP Click Delay: <input type="text" name="visitor_add_click_time" size="5" <?php
                                    if (isset($visitor_add_click_time)) {
                                        echo ' value="' . esc_attr($visitor_add_click_time) . '"';
                                    }
                                    ?> /></li>                                
                            </ul>
                        </td>
                    </tr>
            </table>
            <table class="widefat" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th colspan="2">Global Click Delay (Seconds)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <ul>
                                <li>Delay (Min): <input type="text" name="min_add_click_time" size="5" <?php
                                    if (isset($min_add_click_time)) {
                                        echo ' value="' . esc_attr($min_add_click_time) . '"';
                                    }
                                    ?> /></li>
                                <li>Delay (Max): <input type="text" name="max_add_click_time" size="5" <?php
                                    if (isset($max_add_click_time)) {
                                        echo ' value="' . esc_attr($max_add_click_time) . '"';
                                    }
                                    ?> /></li>
                            </ul>
                        </td>
                        <td>
                        <?php
                            // Get click time data from DB
                            $db_click_time = get_click_time_row();
                        ?>
                            <ul>
                                <li>Current time: <?php echo date('H:i:s'); ?></li>
                                <li>Last click time: <?php
                                    if (isset($db_click_time->global_last_click_time)) {
                                        echo esc_attr(date('H:i:s', $db_click_time->global_last_click_time));
                                    }
                                    ?></li>
                                <li>Next allowed click time: <?php
                                    if (isset($db_click_time->global_next_click_time)) {
                                        echo esc_attr(date('H:i:s', $db_click_time->global_next_click_time));
                                    }
                                    ?></li>
                                <li>Total clicks: <?php
                                    if (isset($db_click_time->total_clicks)) {
                                        echo esc_attr($db_click_time->total_clicks);
                                    }
                                    ?></li>
                            </ul>
                        </td>
                    </tr>                    
                </tbody>
            </table>

            <p>Enable <input type="checkbox" name="enable_bvillage" <?php
                                    if ($enable_bvillage === true) {
                                        echo ' checked="checked"';
                                    }
                                    ?> /></p>

            <p>
                <input type="submit" name="bvillage_submit" value="Submit" class="button-primary" />
            </p>
        </form>  
    </div>
    <?php
}

function get_click_time_row()
{
    // Get global next click time
    global $wpdb;
    $table_name = $wpdb->prefix . 'bvillage_click_time';
    return $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
}

// Plugin Settings
function submit_bvillage_settings()
{
    $settings = array();
    
    // Settings that must be integers
    $int_settings = array(
        'referrer_add_click_time',
        'visitor_add_click_time',
        'min_add_click_time',
        'max_add_click_time'
    );
    
    // Validate all settings in POST which must be integer values
    foreach ($int_settings as $value) {
        if (isset($_POST[$value]) && ctype_digit($_POST[$value])) {
            $settings[$value] = (int) $_POST[$value];
        }
        else {
            return '<p style="color:red;">Make sure that all form fields are filled with numeric values only before submitting.</p>';
        }
    }

    // Check that min_add_click_time <= max_add_click_time
    if ($settings['min_add_click_time'] > $settings['max_add_click_time']) {
        return '<p style="color:red;">Click Delay (Min) cannot be greater than Click Delay (Max).</p>';
    }

    // If BVillage is enabled
    $settings['enable_bvillage'] = isset($_POST['enable_bvillage']) ? true : false;
    
    if (isset($_POST['block_referrer'])) {
        $settings['block_referrer'] = $_POST['block_referrer'];
    }
    
    if (isset($_POST['target_click_url'])) {
        $settings['target_click_url'] = $_POST['target_click_url'];
    }

    // Update Options
    update_option('bvillage_settings', $settings);

    return '<p style="color:#0074A2;">Settings saved.</p>';
}
