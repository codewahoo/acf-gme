# Advanced Custom Fields: Google Map Extended

This plugin creates a new field for [Advanced Custom Fields (ACF)](http://www.advancedcustomfields.com/ "Advanced Custom Fields") 
extending the functionality of the built-in Google Map field with several handy features:
* Saves map center. You can center your maps wherever you want and indicate that you want to save that place as map center. This can be handy, if you want your front-end map to show some specific place in the center of the map (not necessary the location marker).
* Saves zoom level.
* Disables (optionally) map zooming with a scrollwheel. Sometimes you can get annoyed with your maps starting to zoom, when you scroll the post in the admin area. This feature comes handy here.
* Shows location coordinates. It is easy to get any place's location coordinates (latitude and longitude) with this plugin by setting a marker to the place you need using user friendly map interface.
* Compatible with the ACF built-in Google Map field.
* Saves all maps shown at a page in the global array. This is a bonus for programmers.

The plugin makes use of the Google Maps API version 3.<br>
As the API key is now required, a new filter `acf/fields/google_map_extended/api` was configured to allow the user to add the key.

## Usage

Please see the [F.A.Q.](https://wordpress.org/plugins/advanced-custom-fields-google-map-extended/faq/) at WordPress.org