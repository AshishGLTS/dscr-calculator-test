<?php
/**
 * Plugin Name: DSCR Rental Calculator Test
 * Description: DSCR real estate calculator with real-time slider logic.
 * Version: 1.0
 * Author: GLTS
 */

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'dscr-calculator-js',
        plugin_dir_url(__FILE__) . 'assets/dscr.js',
        [],
        '1.1',
        true
    );
});

add_shortcode('dscr_calculator', function () {
    ob_start();
    ?>
    <!-- PASTE YOUR FULL HTML HERE EXACTLY AS YOU SENT -->
    <?php
    return ob_get_clean();
});
