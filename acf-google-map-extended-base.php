<?php
/**
* Base class defining same constructor for both ver 4 and 5 of the field
*/
class acf_field_google_map_extended extends acf_field {
  
  var $settings;
  
  /**
  * __construct
  *
  * Setup the field
  *
  * @param   n/a
  * @return  n/a
  */
  function __construct() {

    $this->name = 'google_map_extended';
    $this->label = __("Google Map Extended",'acf-gme');
    $this->category = 'jquery';
    
    // Array of default settings which are merged into the field object
    $this->defaults = array(
      'height'    => '400',
      'center_lat'  => '-7.60786',
      'center_lng'  => '110.20375',
      'zoom'      => '17',
      'scrollwheel' => 0
    );
    $this->l10n = array(
      'locating'      => __("Locating",'acf-gme'),
      'browser_support'  => __("Sorry, this browser does not support geolocation",'acf-gme'),
      'geocoder_failed' => __('Google geocoder failed','acf-gme'),
      'geocoder_no_results' => __('Google geocoder found no results','acf-gme'),
      'google_failed' => __('Init failed to avoid conflict with another google library already loaded','acf-gme'),
      'none' => __("none",'acf-gme')
    );
    
    
    parent::__construct();

    $this->settings = array(
      'path' => plugin_dir_path(__FILE__),
      'url' => plugins_url('',__FILE__),
      'version' => acf_field_google_map_extended_plugin::version,
      'script-handle' => 'acf-input-google-map-extended',
      'acf-script-handle' => 'acf-input'
    );
    
  }
}
?>