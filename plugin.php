<?php

/**
 * Plugin Name: Developer System
 * Plugin URI:
 * Description: Developer System
 * Version: 1.0.0
 * Author: Sławomir Oruba
 */

// Set constants
define('DEVELOPER_SYSTEM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEVELOPER_SYSTEM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once DEVELOPER_SYSTEM_PLUGIN_DIR . 'helpers.php';
require_once DEVELOPER_SYSTEM_PLUGIN_DIR . 'init.php';


// Require all files from shortcodes directory
foreach (glob(DEVELOPER_SYSTEM_PLUGIN_DIR . 'shortcodes/*.php') as $file) {
    require_once $file;
}

// Sprawdź czy istnieje zmienna mieszkanie w GET i czy jest równa identyfikatorowi jednego z mieszkań
if (isset($_GET['flat_id'])) {
    echo '<script>var mieszkanieIndex = ' . intval($_GET['flat_id']) . ';</script>';
}

?>