<?php
// Include WP files for access to WP functions and DB
$doc_root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING);
require_once $doc_root . '/wp-load.php';
require_once $doc_root . '/wp-blog-header.php';
// Include classes
require_once (plugin_dir_path(__FILE__) . 'class-click.php');

$bvillage_settings = get_option('bvillage_settings');

$click = new Click($bvillage_settings);

// Get home url of WP site to match referrer
$home_url = parse_url(get_home_url(), PHP_URL_HOST);

// Referrer must be from this WP site
if ($click->check_referrer($home_url))
{
    // Send iframe click to website
    header("HTTP/1.1 301 Moved Permanently", TRUE, 301);
    header("Location: " . $click->getSettings()['target_click_url']);
}