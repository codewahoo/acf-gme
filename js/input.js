//global array of Extended Google Maps that can be utilized to alter maps
var acf_gme_maps = [];
var acf_gme_geocoder = null; //common for all instances

(function($){
  
  function acf_gme(el) {
    this.$field = $(el);
    this.$el = this.$field.find('.acf-google-map-extended');
    if(this.$el.length) this.init();
    else this.error('init failed due to wrong HTML markup for map element');
  }
  
  acf_gme.prototype = {
    $field : null,
    $el : null,
    o : {},
    status : '', // '', 'loading', 'ready'
    map : null,
    marker : null,
    
    /**
    *  is_ready
    *
    *  Ensures google API is available and return a boolean for the current status
    *
    *  @param  n/a
    *  @returns  (boolean)
    */
    is_ready: function () {

      var self = this;
      if( this.status == 'ready' ) return true;
      if( this.status == 'loading' ) return false;
      
      if( typeof google === 'undefined' ) {// no google
        
        self.status = 'loading';
        
        // load Google API same way like ACF so that ACF can load additional libraries, if needed. 
        $.getScript('https://www.google.com/jsapi', function(){

          google.load('maps', '3', { other_params: 'sensor=false&libraries=places', callback: function(){
            self.status = 'ready';
            self.init();//try to init again
          }});
            
        });
        return false;

      }
      
      if( !google.maps || !google.maps.places ) {// no maps or places
        
        self.status = 'loading';
        
        // bail early if no load function (avoid modified google library conflict)
        if( !google.load ) {
          self.error(acf.l10n.google_map_extended.google_failed);
          return false;
        }
        
        
        google.load('maps', '3', { other_params: 'sensor=false&libraries=places', callback: function(){
          self.status = 'ready';
          self.init();//try to init again
        }});
        return false;
          
      }
      
      this.status = 'ready';

      return true;      
    },

    /**
    *  init
    *
    *  Creates the map and events
    *
    *  @param  n/a
    *  @returns  n/a
    */    
    init: function () {
      
      if( !this.is_ready() ) return false;
      
      // load geocode
      if( !acf_gme_geocoder ) acf_gme_geocoder = new google.maps.Geocoder();

      // get options
      this.o = acf.get_data ? acf.get_data(this.$el) : acf.helpers.get_atts( this.$el );
      

      var mapOptions = {
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        zoom: parseInt(this.o.zoom),
        center: new google.maps.LatLng(this.o.lat, this.o.lng),
        scrollwheel: parseInt(this.o.scrollwheel) ? true : false
      };

      this.map = new google.maps.Map(this.$el.find('.canvas')[0], mapOptions);
      
      // search
      var autocomplete = new google.maps.places.Autocomplete( this.$el.find('.search')[0] );
      autocomplete.map = this.map;
      autocomplete.bindTo('bounds', this.map);
      
      // marker
      var markerOptions = {
        draggable:     true,
        raiseOnDrag:   true,
        map:       this.map
      };

      // add marker
      this.marker = new google.maps.Marker( markerOptions );
        
      var lat = this.$el.find('.input-lat').val(),
          lng = this.$el.find('.input-lng').val(),
          mlat = this.$el.find('.input-center_lat').val(),
          mlng = this.$el.find('.input-center_lng').val();
      
      if( lat && lng ) {
        var latlng = new google.maps.LatLng(lat,lng);
        this.markerPosition(latlng);
        if( !(mlat && mlng) ) this.setCenter(latlng);
      }
      
      // reference
      var self = this,
          $el = this.$el;
        

      // ACF Google Map Extended field events -----------------
      
      this.$el.find('.acf-gme-btn-mz').on('click',function(e) {
        e.preventDefault();
        var zoom = self.map.getZoom();
        $el.find('.acf-gme-zooming-level').text(zoom);
        $el.find('.input-zoom').val(zoom);
      });
      
      this.$el.find('.acf-gme-btn-mc').on('click',function(e) {
        e.preventDefault();
        var latlng = self.map.getCenter();
        $el.find('.acf-gme-center-coords').text(latlng.lat() + ',' + latlng.lng());
        $el.find('.input-center_lat').val(latlng.lat());
        $el.find('.input-center_lng').val(latlng.lng());
      });
      
      this.$el.find('.acf-gme-btn-md').on('click',$.proxy(function(e){e.preventDefault(); self.markerClear(),self}));
      
      this.$el.find('a.acf-gme-clear-location').on('click',$.proxy(function(e){e.preventDefault(); self.markerClear(),self}));
      
      this.$el.find('a.acf-gme-find-location').on('click',$.proxy(function(e){e.preventDefault(); self.locate(),self}));
      
      
      this.$el.find('.handlediv').on('click', function() {

        $el.find('footer').toggleClass('closed');
        
      });
      
      
      this.$el.find('.search').on('blur', function(){
        
        if( $el.find('.search').val() != $el.find('.input-address').val() ) {
          
          $el.find('.search').val($el.find('.input-address').val());
          
        }
        
      });
      
      this.$el.find('.search').on('keydown', function( e ){
        
        if( e.which == 13 ) e.preventDefault(); // prevent form from submitting
        if( e.which == 27 ) this.blur();
        
      });
      
      //Google map events -----------------
    
      google.maps.event.addListener(autocomplete, 'place_changed', function( e ) {
          
        // manually update address
        var address = $el.find('.search').val();
        $el.find('.input-address').val( address );
        
        var place = this.getPlace();
        
        
        // if place exists
        if( place.geometry ) {
          $el.removeClass('no-value').addClass('has-value');
          self.markerPosition( place.geometry.location ).setCenter(place.geometry.location);
          return;
        }
        
        
        // client hit enter, manually get the place
        acf_gme_geocoder.geocode({ 'address' : address }, function( results, status ){
          
          if( status != google.maps.GeocoderStatus.OK ) {
            self.error(acf.l10n.google_map_extended.geocoder_failed + ' : ' + status);
            return;
          } else if( !results[0] ) {
            self.error(acf.l10n.google_map_extended.geocoder_no_results);
            return;
          }
          
          place = results[0];
          
          $el.removeClass('no-value').addClass('has-value');

          self.markerPosition( place.geometry.location ).setCenter(place.geometry.location);

          $el.find('.search').blur();
            
        });
          
      });
      
      google.maps.event.addListener( this.marker, 'dragend', function(){

        var position = self.marker.getPosition();
        self.markerPosition( position ).markerReverseGeocode();
          
      });
      
      
      google.maps.event.addListener( this.map, 'click', function( e ) {
        
        self.markerPosition( e.latLng ).markerReverseGeocode();
      
      });      
      
              
    },

    /**
    *  locate
    *
    *  Locates user's current geographical position
    *
    *  @param  n/a
    *  @returns  n/a
    */
    locate : function () {
      // reference
      var self = this,
          $el = this.$el,
          latlng = null;
      
      // Try HTML5 geolocation
      if( ! navigator.geolocation ) {
        alert( acf.l10n.google_map.browser_support );
        return this;
      }
      
      $el.find('.search').val(acf.l10n.google_map.locating + '...');
      
      //Clear search bar in case user doesn't grant permissions to identify Geolocation
      setTimeout(function(){
        if(!latlng) {
          $el.find('.search').val('');
        }
      },5500);
      
      navigator.geolocation.getCurrentPosition(function(position){
      
        latlng = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
        self.markerPosition( latlng ).setCenter( latlng ).markerReverseGeocode();

      },function(pError) {
        //console.log(pError.message);
        $el.find('.search').val('');
      },{
        enableHighAccuracy: false,
        timeout: 5000,
        maximumAge: 0
      });
      
    },

    /**
    *  setCenter
    *
    *  Sets map center to the specified coordinates
    *
    *  @param  google.maps.LatLng latitude longtitude coordinates of the place to set as the center of the map
    *  @returns  acf_gme instance for chaining
    */
    setCenter : function(latlng){
      
      this.map.setCenter(latlng);
      
      return this;
          
    },

    /**
    *  markerPosition
    *
    *  Sets the marker to the specified coordinates
    *
    *  @param  google.maps.LatLng latitude longtitude coordinates of the place to set the marker on
    *  @returns  acf_gme instance for chaining
    */
    markerPosition : function( latlng ){
      
      this.marker.setPosition( latlng );
        
      this.marker.setVisible( true );
      
      // update inputs
      this.$el.find('.input-lat').val(latlng.lat());
      this.$el.find('.input-lng').val(latlng.lng());
      this.$el.find('.input-address').val(this.$el.find('.search').val());
      
      // update coords in map data
      this.$el.find('.acf-gme-marker-coords').text(latlng.lat() + ',' + latlng.lng());
        
      // validation
      this.$field.removeClass('error');
      
      return this;
          
    },
    
    /**
    *  markerClear
    *
    *  Removes the marker from the map
    *
    *  @param  n/a
    *  @returns  n/a
    */
    markerClear : function(){
      // reference
      var self = this,
          $el = this.$el;
          
      // update class
      $el.removeClass('has-value').addClass('no-value');
      
      // clear search
      $el.find('.search').val('');
      
      // clear inputs
      $el.find('.input-address').val('');
      $el.find('.input-lat').val('');
      $el.find('.input-lng').val('');
      
      //clear marker coordinates
      $el.find('.acf-gme-marker-coords').text(acf.l10n.google_map_extended.none);
      
      // hide marker
      self.marker.setVisible( false );      
    },
    
    /**
    *  markerReverseGeocode
    *
    *  Get the address associated with the coordinates the marker is set on
    *
    *  @param  n/a
    *  @returns  acf_gme instance for chaining
    */
    markerReverseGeocode : function(){
      
      var self = this,
          $el  = this.$el;
        
      
      var latlng = this.marker.getPosition();
      
      
      acf_gme_geocoder.geocode({ 'latLng' : latlng }, function( results, status ){
        
        if( status != google.maps.GeocoderStatus.OK ) {
          $el.find('.search').val('');
          self.error(acf.l10n.google_map_extended.geocoder_failed + ' : ' + status);
          return;
        } else if( !results[0] ) {
          $el.find('.search').val('');
          self.error(acf.l10n.google_map_extended.geocoder_no_results);
          return;
        }
        
        var location = results[0];
        
        $el.removeClass('no-value').addClass('has-value');
        $el.find('.input-lat').val(latlng.lat());
        $el.find('.input-lng').val(latlng.lng());
        $el.find('.input-address').val(location.formatted_address);
        $el.find('.search').val(location.formatted_address);
        
      });
      
      return this;
          
    },
    
    /**
    *  refresh
    *
    *  Triggers map redraw and recenters it
    *
    *  @param  n/a
    *  @returns  n/a
    */
    refresh: function() {

      // trigger resize on div
      google.maps.event.trigger(this.map, 'resize');
      
      // center map
      var lat = this.$el.find('.input-lat').val(),
          lng = this.$el.find('.input-lng').val(),
          mlat = this.$el.find('.input-center_lat').val(),
          mlng = this.$el.find('.input-center_lng').val();
      
      if( lat && lng ) {
        var latlng = new google.maps.LatLng(lat,lng);
      } else if (mlat && mlng) {
        var latlng = new google.maps.LatLng(mlat,mlng);
      }     
      if(latlng) this.setCenter(latlng);
    },
          
    /**
    *  error
    *
    *  Outputs the error
    *
    *  @param  string Error message
    *  @returns  n/a
    */
    error: function(msg) {
      msg = 'ACF Google Map Extended: ' + msg;
      if (window.console) console.log(msg);
      else throw new Error(msg);
    }
  };
	

	if( typeof acf.add_action !== 'undefined' ) {
	
		/**
		*  ready append (ACF5)
		*
		*  These are 2 events which are fired during the page load
		*  ready = on page load similar to $(document).ready()
		*  append = on new DOM elements appended via repeater field
		*
		*/
		
		acf.add_action('ready append', function( $el ){
			
			// search $el for fields of type 'FIELD_NAME'
			acf.get_fields({ type : 'google_map_extended'}, $el).each(function(){
				
        acf_gme_maps[$(this).find('.acf-google-map-extended').attr('data-id')] = new acf_gme(this);
				
			});
			
		});
    
    acf.add_action('show', function( $el ){
      
      // search $el for fields of type 'FIELD_NAME'
      acf.get_fields({ type : 'google_map_extended'}, $el).each(function(){
        
        acf_gme_maps[$(this).find('.acf-google-map-extended').attr('data-id')].refresh();
        
      });
      
    });
		
		
	} else {
		
		
		/**
		*  acf/setup_fields (ACF4)
		*
		*  This event is triggered when ACF adds any new elements to the DOM. 
		*
		*/
		
		$(document).on('acf/setup_fields', function(e, postbox){
			
			$(postbox).find('.field[data-field_type="google_map_extended"]').each(function(){
				
				acf_gme_maps[$(this).find('.acf-google-map-extended').attr('data-id')] = new acf_gme(this);
				
			});
		
		});
    
    $(document).on('acf/fields/tab/show acf/conditional_logic/show', function( e, $field ){
      
      if( $field.attr('data-field_type') == 'google_map_extended' ) {
        acf_gme_maps[$field.find('.acf-google-map-extended').attr('data-id')].refresh();
      }
      
    });    
	
	
	}

  
})(jQuery);