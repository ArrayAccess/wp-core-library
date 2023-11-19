<?php
declare(strict_types=1);

require __DIR__ .'/../vendor/autoload.php';

$count = 6;
$wpLoad = "wp-load.php";
while (--$count > 0 && !file_exists(__DIR__ .'/' . $wpLoad)) {
    $wpLoad = "../" . $wpLoad;
}
if (file_exists(__DIR__ .'/'. $wpLoad)) {
    $baseDir = dirname(__DIR__ . '/' . $wpLoad);
    if (file_exists($baseDir .'/wp-includes/plugin.php')) {
        // load filter
        /** @noinspection PhpIncludeInspection */
        require_once $baseDir .'/wp-includes/plugin.php';
        // don't load theme
        add_filter('wp_using_themes', static fn () => false);
        // don't load plugin
        add_action('option_active_plugins', static fn () => false);
    }
    require_once __DIR__ . '/' . $wpLoad;
}
