<?php

class Click
{

    private $wpdb;
    private $click_time;
    private $ip;
    private $user_agent;
    private $referrer;
    private $settings;

    public function __construct($settings)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->click_time = time();
        $this->ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING);
        $this->user_agent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING);
        $this->referrer = filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING);

        // Plugin settings from WP Options table
        $this->settings = $settings;
    }

    /**
     * Checks if plugin is enabled
     * 
     * @return boolean
     */
    public function plugin_enabled()
    {
        if (isset($this->settings['enable_bvillage'])) {
            // Plugin enabled
            return true;
        }
        else {
            // Plugin disabled
            return false;
        }
    }

    /**
     * Checks that a specified URL is not contained in referrer
     * 
     * @param string $str_url
     * @return boolean
     */
    public function check_referrer($str_url)
    {
        $pos = false;

        if ($this->referrer && $str_url) {
            // Checks if str_url is present anywhere in base url of referrer
            $pos = strpos(parse_url($this->referrer, PHP_URL_HOST), $str_url);
        }

        return $pos !== false ? true : false;
    }

    /**
     * Checks that current click time has passed global next allowed click time
     * 
     * @return boolean
     */
    public function check_global_next_click_time()
    {
        $table_name = $this->wpdb->prefix . 'bvillage_click_time';
        $global_next_click_time = $this->wpdb->get_var("SELECT global_next_click_time FROM $table_name WHERE id = 1");

        if ($global_next_click_time) {
            // Returns true if current click time has passed next allowed click
            return $this->click_time > $global_next_click_time;
        }
        else {
            // No global_next_click_time set. Click allowed
            return true;
        }
    }

    /**
     * Checks that current click time is allowed for this IP address
     * 
     * @return boolean
     */
    public function check_next_ip_click_time()
    {
        // Check next allowed click time
        $next_click_time = $this->get_next_ip_click_time();

        if ($next_click_time) {
            // Returns true if current click time has passed next allowed click
            return $this->click_time > $next_click_time;
        }
        else {
            // No next IP click time set. Click allowed
            return true;
        }
    }

    /**
     * Checks that user agent does not contain common bot string
     * 
     * @return boolean
     */
    public function check_user_agent()
    {
        if (preg_match('/bot|crawl|slurp|spider/i', $this->user_agent)) {
            // Return true if common bot string found
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Checks if JavaScript is disabled
     * 
     * Creates a hidden form and submits it with JavaScript. If hidden form 
     * value is found in POST, then JavaScript is enabled.
     * 
     * @return boolean
     */
    public function js_disabled()
    {
        if (isset($_POST['jscheck'])) {
            // Javascript enabled
            return false;
        }
        else {
            // Create hidden form and submit it with javascript
            echo '<form name="jsform" id="jsform" method="post" style="display:none">';
            echo '<input name="jscheck" type="text" value="true" />';
            echo '<script language="javascript">';
            echo 'document.jsform.submit();';
            echo '</script>';
            echo '</form>';
            // Javascript disabled
            return true;
        }
    }

    /**
     * Adds clicktime to current clicktime and inserts next clicktime to DB
     * 
     * @param integer $int_add_click_time
     */
    private function set_next_ip_click_time($int_add_click_time)
    {
        // Set the next click time for this IP address
        $next_click_time = $this->click_time + $int_add_click_time;

        $table_name = $this->wpdb->prefix . 'bvillage_ip';
        $this->wpdb->query(
                $this->wpdb->prepare(
                        "
                        INSERT INTO $table_name (ip, next_ip_click_time) VALUES (%s, %d)
                            ON DUPLICATE KEY UPDATE next_ip_click_time = %d
                        ", $this->ip, $next_click_time, $next_click_time
                )
        );
    }

    /**
     * Updates global_last_click_time, global_next_click_time, and total_clicks in DB
     * 
     * @param integer $new_global_next_click_time
     */
    private function set_global_next_click_time($new_global_next_click_time)
    {
        $table_name = $this->wpdb->prefix . 'bvillage_click_time';
        $this->wpdb->query(
                $this->wpdb->prepare(
                        "
                        UPDATE $table_name
                        SET global_last_click_time = %d,
                        global_next_click_time = %d,
                        total_clicks = total_clicks + 1
                        WHERE id = 1
                        ", $this->click_time, $new_global_next_click_time
                )
        );
    }

    /**
     * Returns the next IP click time from DB
     * 
     * @return integer
     */
    public function get_next_ip_click_time()
    {
        // Check next click time in DB for this IP address
        $table_name = $this->wpdb->prefix . 'bvillage_ip';
        $next_click_time = $this->wpdb->get_var(
                $this->wpdb->prepare(
                        "
                        SELECT next_ip_click_time FROM $table_name
                        WHERE ip = %s
                        ", $this->ip
                )
        );
        return $next_click_time;
    }

    /**
     * Checks if a click is valid
     * 
     * @return boolean
     */
    public function is_click_valid()
    {
        // Check that plugin is enabled
        if (!$this->plugin_enabled()) {
            return false;
        }
        // Check that blocked referrer is not found
        elseif ($this->check_referrer($this->settings['block_referrer'])) {
            // Blocked referrer found. Set next click time delay and forbid click
            $this->set_next_ip_click_time($this->settings['referrer_add_click_time']);
            return false;
        }
        // Check that user agent does not contain a common bot string (true if Bot)
        elseif ($this->check_user_agent()) {
            return false;
        }
        // Check that global next click time has passed (true if Passed)
        elseif (!$this->check_global_next_click_time()) {
            return false;
        }
        // Check if this IP is allowed to send another click yet
        elseif (!$this->check_next_ip_click_time()) {
            return false;
        }
        // Check that JavaScript is not disabled
        elseif ($this->js_disabled()) {
            return false;
        }
        else {
            // Click is valid
            return true;
        }
    }

    /**
     * Saves data for this click
     */
    public function save_click()
    {
        // Create random time in seconds for new global next click time
        $rand_time = mt_rand($this->settings['min_add_click_time'], $this->settings['max_add_click_time']);

        // Add random time to current time for new global next click time
        $new_global_next_click_time = ($this->click_time + $rand_time);

        // Set new global next click time
        $this->set_global_next_click_time($new_global_next_click_time);

        // Set next click time allowed for this IP
        $this->set_next_ip_click_time($this->settings['visitor_add_click_time']);
    }

    /**
     * Returns settings array
     * 
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

}
