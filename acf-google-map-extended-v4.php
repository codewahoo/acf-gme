<?php

class acf_field_google_map_extended_v4 extends acf_field_google_map_extended {
	
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
    $this->category = 'jQuery';
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
    //workaround script blocking ACF ver 4 from loading google maps twice
    wp_register_script('acf-input-google-load-workaround', $this->settings['url'] . '/js/acf4-fix.js', array('googlemaps-api'), $this->settings['version'],false);
    wp_register_script($this->settings['script-handle'], $this->settings['url'] . '/js/input.js', array('acf-input-google-load-workaround',$this->settings['acf-script-handle'],'jquery','googlemaps-api'), $this->settings['version'],false);
    wp_register_style($this->settings['script-handle'], $this->settings['url'] . '/css/input.css', array($this->settings['acf-script-handle']), $this->settings['version']); 

    wp_enqueue_script(array($this->settings['script-handle']));
    wp_enqueue_style(array($this->settings['script-handle']));
  }
  
  /**
  * create_field($field)
  *
  * Create the HTML interface for the field
  *
  * @param  $field - an array holding all the field's data
  * @return n/a
  */
  function create_field( $field ) {
    
    // validate value
    if( empty($field['value']) ) $field['value'] = array();
    
    
    // value
    $field['value'] = wp_parse_args($field['value'], array(
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
    
    $atts_str = '';
    
    foreach( $atts as $k => $v )
    {
      $atts_str .= ' ' . $k . '="' . esc_attr( $v ) . '"';  
    }
    
?>
<div <?php echo $atts_str; ?>>
  
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
  * create_options($field)
  *
  * Creates extra options for the field settings page.
  * The value of $field['name'] can be used to save extra data to the $field
  *
  * @param  $field  - an array holding all the field's data
  * @return n/a
  */
	function create_options( $field ) {
		
    $key = $field['name'];// key is needed in the field names to correctly save the data
    
    // populate default options
    foreach( $this->defaults as $k => $v ) {
      if( empty($field[$k]) ) {
        $field[$k] = $v;
      }
    }    
		
		?>
<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e('Scrollwheel support','acf-gme'); ?></label>
		<p class="description"><?php _e("Enables scrollwheel for map zooming",'acf-gme'); ?></p>
	</td>
	<td>
		<?php
		
		do_action('acf/create_field', array(
			'type'		=>	'radio',
			'name'		=>	'fields['.$key.'][scrollwheel]',
			'value'		=>	$field['scrollwheel'],//$this->defaults['scrollwheel'],
			'layout'	=>	'horizontal',
			'choices'    => array(
        1        => __("Yes",'acf-gme'),
        0        => __("No",'acf-gme'),
      ),
		));
		?>
	</td>
</tr>
<tr class="field_option field_option_<?php echo $this->name; ?>">
  <td class="label">
    <label><?php _e('Center','acf-gme'); ?></label>
    <p class="description"><?php _e("Center the initial map",'acf-gme'); ?></p>
  </td>
  <td>
    <ul class="hl clearfix">
      <li style="width:48%;">
        <?php 
      
        do_action('acf/create_field', array(
          'type'      => 'text',
          'name'      => 'fields['.$key.'][center_lat]',
          'value'      => $field['center_lat'],
          'prepend'    => 'lat',
          'placeholder'  => $this->defaults['center_lat']
        ));
        
        ?>
      </li>
      <li style="width:48%; margin-left:4%;">
        <?php 
      
        do_action('acf/create_field', array(
          'type'      => 'text',
          'name'      => 'fields['.$key.'][center_lng]',
          'value'      => $field['center_lng'],
          'prepend'    => 'lng',
          'placeholder'  => $this->defaults['center_lng']
        ));
        
        ?>
      </li>
    </ul>  
  </td>
</tr>
<tr class="field_option field_option_<?php echo $this->name; ?>">
  <td class="label">
    <label><?php _e('Zoom','acf-gme'); ?></label>
    <p class="description"><?php _e("Set the initial zoom level",'acf-gme'); ?></p>
  </td>
  <td>
    <?php
    
    do_action('acf/create_field', array(
      'type'      => 'number',
      'name'      => 'fields['.$key.'][zoom]',
      'value'      => $field['zoom'],
      'min'      => '0',
      'max'      => '18',
      'step'      => '1',
      'placeholder'  => $this->defaults['zoom']
    ));    
    ?>
  </td>
</tr>
<tr class="field_option field_option_<?php echo $this->name; ?>">
  <td class="label">
    <label><?php _e('Height','acf-gme'); ?></label>
    <p class="description"><?php _e("Customise the map height",'acf-gme'); ?></p>
  </td>
  <td>
    <?php
    
    do_action('acf/create_field', array(
      'type'      => 'text',
      'name'      => 'fields['.$key.'][height]',
      'value'      => $field['height'],
      'append'    => 'px',
      'placeholder'  => $this->defaults['height']
    ));    
    ?>
  </td>
</tr>
		<?php
		
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


new acf_field_google_map_extended_v4();
?>