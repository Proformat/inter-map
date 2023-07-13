<?php

add_action('init', function () {
    if (!function_exists('\Breakdance\Forms\Actions\registerAction') || !class_exists('\Breakdance\Forms\Actions\Action')) {
        return;
    }

    foreach (glob(DEVELOPER_SYSTEM_PLUGIN_DIR . 'actions/*.php') as $file) {
        require_once $file;
        $class_name = basename($file, '.php');
        $reflection = new ReflectionClass($class_name);
        \Breakdance\Forms\Actions\registerAction($reflection->newInstance());
    }

});

// Zmień e-mail nadawcy na e-mail administratora
function my_mail_from($email)
{
    return get_option('admin_email');
}
add_filter('wp_mail_from', 'my_mail_from');

// Zmień nazwę nadawcy na nazwę strony
function my_mail_from_name($name)
{
    return get_bloginfo('name');
}
add_filter('wp_mail_from_name', 'my_mail_from_name');

function loadFrontEndScriptsAndStyles()
{
    add_action('wp_enqueue_scripts', function () {
        wp_enqueue_script('konva', DEVELOPER_SYSTEM_PLUGIN_URL . 'assets/konva.min.js', array(), '8.4.3', true);
        wp_enqueue_script('developer-system', DEVELOPER_SYSTEM_PLUGIN_URL . 'classes/InterMap.js', array('konva'), '1.0.0', true);
    });
}
function loadBackEndScriptsAndStyles()
{
    add_action('admin_enqueue_scripts', function () {
        wp_enqueue_script('konva', DEVELOPER_SYSTEM_PLUGIN_URL . 'assets/konva.min.js', array(), '8.4.3', true);
        wp_enqueue_script('developer-system', DEVELOPER_SYSTEM_PLUGIN_URL . 'classes/InterMap.js', array('konva'), '1.0.0', true);
    });
}
