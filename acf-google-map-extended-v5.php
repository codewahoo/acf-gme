<?php

class acf_field_google_map_extended_v5 extends acf_field_google_map_extended {
	
	/**
	* __construct
	*
	* Setup the field
	*
	* @param   n/a
	* @return  n/a
	*/
	function __construct() {
    parent::__construct();
	}
  
  /**
  * input_admin_enqueue_scripts()
  *
  * Adds CSS/JavaScript to the page, where fields are used. Called in the WP admin_enqueue_scripts action.
  *
  * @see    http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
  * @param  n/a
  * @return n/a
  */
  function input_admin_enqueue_scripts() {
    wp_register_script("googlemaps-api", "//maps.googleapis.com/maps/api/js?v=3&sensor=false&libraries=places",array(),'3',false);
    wp_register_script($this->settings['script-handle'], $this->settings['url'] . '/js/input.js', array($this->settings['acf-script-handle'],'jquery','googlemaps-api'), $this->settings['version'],false);
    wp_register_style($this->settings['script-handle'], $this->settings['url'] . '/css/input.css', array($this->settings['acf-script-handle']), $this->settings['version']); 

    wp_enqueue_script(array($this->settings['script-handle']));
    wp_enqueue_style(array($this->settings['script-handle']));    
  }
  
  /**
  * render_field($field)
  *
  * Create the HTML interface for the field
  *
  * @param  $field - an array holding all the field's data
  * @return n/a
  */
  function render_field( $field ) {
    
    // validate value
    if( empty($field['value']) ) $field['value'] = array();
    
    
    // value
    $field['value'] = acf_parse_args($field['value'], array(
      'address'     => '',
      'lat'         => '',
      'lng'         => '',
      'zoom'        => '',
      'center_lat'  => '',
      'center_lng'  => ''
    ));
    
    
    // populate default options
    foreach( $this->defaults as $k => $v ) {
      if( empty($field[$k]) ) {
        $field[$k] = $v;
      }
    }
    
    // override default settings for map zoom and center with the ones saved with the field value
    if( !empty($field['value']['zoom']) ) $field['zoom'] = $field['value']['zoom'];
    if( !empty($field['value']['center_lat']) ) $field['center_lat'] = $field['value']['center_lat'];
    if( !empty($field['value']['center_lng']) ) $field['center_lng'] = $field['value']['center_lng'];

    $atts = array(
      'id'              => $field['id'],
      'class'           => "acf-google-map-extended {$field['class']}",
      'data-id'         => $field['id'] . '-' . uniqid(), 
      'data-lat'        => $field['center_lat'],
      'data-lng'        => $field['center_lng'],
      'data-zoom'       => $field['zoom'],
      'data-scrollwheel'  => $field['scrollwheel']
    );
    
    $atts['class'] .= ( $field['value']['address'] ) ? ' has-value' : ' no-value';
    
?>
<div <?php acf_esc_attr_e($atts); ?>>
  
  <div style="display: none">
    <?php foreach( $field['value'] as $k => $v ): ?>
      <input type="hidden" class="input-<?php echo $k; ?>" name="<?php echo esc_attr($field['name']); ?>[<?php echo $k; ?>]" value="<?php echo esc_attr( $v ); ?>" autocomplete="off" />
    <?php endforeach; ?>
  </div>
  
  <header>
    <input class="search" type="text" placeholder="<?php _e("Search for address...",'acf-gme'); ?>" value="<?php echo $field['value']['address']; ?>" autocomplete="off" />
    <a class="acf-gme-icon acf-gme-clear-location" title="<?php _e("Clear location", 'acf-gme'); ?>">
      <span class="dashicons dashicons-no-alt"></span>
    </a>
    <a class="acf-gme-icon acf-gme-find-location" title="<?php _e("Find current location", 'acf-gme'); ?>">
      <span class="dashicons dashicons-location"></span>
    </a>
  </header>
  <div class="canvas" style="height: <?php echo $field['height']; ?>px"></div>
  <footer class="closed">
    <div title="<?php _e("Click to toggle", 'acf-gme'); ?>" class="handlediv"><br></div>
    <h3><?php _e("Map data", 'acf-gme'); ?></h3>
    <section class="acf-gme-options">
      <div><label><?php _e("Map zooming level",'acf-gme'); ?>:</label><em class="acf-gme-zooming-level"><?php echo empty($field['value']['zoom']) ? __("default",'acf-gme') : $field['value']['zoom']; ?></em><button class="button acf-gme-btn-mz" title="<?php esc_attr_e("Click to update with the map's zooming level",'acf-gme'); ?>"><?php _e("Update",'acf-gme'); ?></button></div>
      <div><label><?php _e("Map center coords",'acf-gme'); ?>:</label><em class="acf-gme-center-coords"><?php echo (empty($field['value']['center_lat']) && empty($field['value']['center_lng'])) ? __("default",'acf-gme') : $field['value']['center_lat'].','.$field['value']['center_lng']; ?></em><button class="button acf-gme-btn-mc" title="<?php esc_attr_e("Click to update with the map's center coordinates",'acf-gme'); ?>"><?php _e("Update",'acf-gme'); ?></button></div>
      <div><label><?php _e("Map marker coords",'acf-gme'); ?>:</label><em class="acf-gme-marker-coords"><?php echo (empty($field['value']['lat']) && empty($field['value']['lng'])) ? __("none",'acf-gme') : $field['value']['lat'].','.$field['value']['lng']; ?></em><button class="button acf-gme-btn-md" title="<?php esc_attr_e("Click to delete the current marker",'acf-gme'); ?>"><?php _e("Clear",'acf-gme'); ?></button></div>
      <div><small><?php printf(__("coordinates are presented in the %s format",'acf-gme'),'<i>'.__("latitude",'acf-gme').','.__("longitude",'acf-gme').'</i>'); ?></small></div>
    </section>
  </footer>  
  
</div>
<?php
    
  }
  
    
  /**
  * render_field_settings($field)
  *
  * Creates extra options for the field settings page.
  * The value of $field['name'] can be used to save extra data to the $field
  *
  * @param  $field  - an array holding all the field's data
  * @return n/a
  */
  function render_field_settings( $field ) {
    
    // scrolling
    acf_render_field_setting( $field, array(
      'label'      => __('Scrollwheel support','acf-gme'),
      'instructions'  => __('Enables scrollwheel for map zooming','acf-gme'),
      'type'      => 'radio',
      'name'      => 'scrollwheel',
      'value'     => $this->defaults['scrollwheel'],
      'choices'    => array(
        1        => __("Yes",'acf-gme'),
        0        => __("No",'acf-gme'),
      ),
      'layout'  =>  'horizontal'
    ));
    
    // center_lat
    acf_render_field_setting( $field, array(
      'label'      => __('Center','acf-gme'),
      'instructions'  => __('Center the initial map','acf-gme'),
      'type'      => 'text',
      'name'      => 'center_lat',
      'prepend'    => 'lat',
      'placeholder'  => $this->defaults['center_lat']
    ));
    
    
    // center_lng
    acf_render_field_setting( $field, array(
      'label'      => __('Center','acf-gme'),
      'instructions'  => __('Center the initial map','acf-gme'),
      'type'      => 'text',
      'name'      => 'center_lng',
      'prepend'    => 'lng',
      'placeholder'  => $this->defaults['center_lng'],
      'wrapper'    => array(
        'data-append' => 'center_lat'
      )
    ));
    
    
    // zoom
    acf_render_field_setting( $field, array(
      'label'      => __('Zoom','acf-gme'),
      'instructions'  => __('Set the initial zoom level','acf-gme'),
      'type'      => 'number',
      'name'      => 'zoom',
      'min'      => '0',
      'max'      => '18',
      'step'      => '1',
      'placeholder'  => $this->defaults['zoom']
    ));

   
    // height
    acf_render_field_setting( $field, array(
      'label'      => __('Height','acf-gme'),
      'instructions'  => __('Customise the map height','acf-gme'),
      'type'      => 'text',
      'name'      => 'height',
      'append'    => 'px',
      'placeholder'  => $this->defaults['height']
    ));
    
  }
  
  
  /**
  * validate_value($valid, $value, $field, $input)
  *
  * Validates the field's value
  *
  * @param  $valid
  * @param  $value - the field's value to validate
  * @param  $field - the field array holding all the field options
  * @param  $input
  * @return boolean - returns true, if the field has a valid value
  */
  function validate_value( $valid, $value, $field, $input ){
    
    if( ! $field['required'] ) return $valid; // bail early if not required
    
    if( empty($value) || empty($value['lat']) || empty($value['lng']) )  return false;

    return $valid;
    
  }
  
  
  /**
  * update_value($value, $post_id, $field)
  *
  * This filter is appied to the $value before it is updated in the db
  *
  * @param  $value - the value which will be saved in the database
  * @param  $post_id - the $post_id of which the value will be saved
  * @param  $field - the field array holding all the field options
  * @return $value - the modified value
  */
  function update_value( $value, $post_id, $field ) {
  
    if( empty($value) || empty($value['lat']) || empty($value['lng']) ) {
      
      return false;
      
    }
    
    return $value;
  }
}


new acf_field_google_map_extended_v5();
?>