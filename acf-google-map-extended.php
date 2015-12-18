<?php
/*
Plugin Name: ACF: Google Map Extended
Plugin URI: https://github.com/codewahoo/acf-gme
Description: ACF field. Saves map center, zoom level. Disables map zooming on scroll. Shows location coordinates. Bonus for programmers.
Version: 1.0.1
Author: CodeFish
Author URI: http://code.fish
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: acf-gme
Domain Path: /lang
*/

class acf_field_google_map_extended_plugin {
  
  const version = '1.0.1';

  function __construct() {
    add_action('plugins_loaded', array($this, 'plugins_loaded') );
    add_action('acf/register_fields', array($this, 'register_fields'));
    add_action('acf/include_field_types', array($this, 'include_field_types'));
  }

  function plugins_loaded() {
    load_plugin_textdomain('acf-gme', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
  }

  function register_fields() {
    include_once('acf-google-map-extended-base.php');
    include_once('acf-google-map-extended-v4.php');
  }

  function include_field_types($version) {// $version = 5 and can be ignored until ACF6 exists
    include_once('acf-google-map-extended-base.php');
    include_once('acf-google-map-extended-v5.php');
  }
}

new acf_field_google_map_extended_plugin();
?>