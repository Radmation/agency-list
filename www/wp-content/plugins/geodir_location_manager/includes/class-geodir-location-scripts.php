<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Scripts.
 *
 *
 * @class    GeoDir_Location_Scripts
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Scripts{

	public static function init(){

		add_action( 'wp_enqueue_scripts', array(__CLASS__,'enqueue') );
		add_action( 'admin_enqueue_scripts', array(__CLASS__,'admin_enqueue'), 15 );


	}

	public static function enqueue(){
		// core
		wp_add_inline_script( 'geodir', self::frontend() );

		// add listing
		wp_add_inline_script( 'geodir-add-listing', self::add_listing() );
	}

	public static function admin_enqueue(){
		// core
//		wp_add_inline_script( 'geodir', self::frontend() );

		// add listing
		wp_add_inline_script( 'geodir-add-listing', self::add_listing() );
	}
	
	public static function frontend(){
		return self::switcher();
	}

	public static function switcher(){

		ob_start();
		 if(0){ ?><script><?php }?>
			
		<?php if(0){ ?></script><?php }
		
		return ob_get_clean();
	}

	

	public static function add_listing(){

		ob_start();
	if(0){ ?><script><?php }?>
			jQuery(function($) {
				GeoDir_Location_Fields.init();
			});
			var GeoDir_Location_Fields = {
				init: function() {
					var $self, $form, $country, $region, $city;

					if (!jQuery('#address_country').closest('form').length) {
						return false;
					}

					this.$form = jQuery('#address_country').closest('form');
					this.$country = jQuery('[name="country"]', this.$form);
					this.$region = jQuery('[name="region"]', this.$form);
					this.$city = jQuery('[name="city"]', this.$form);
					this.$neighbourhood = jQuery('[name="neighbourhood"]', this.$form);
					this.$zip = jQuery('[name="zip"]', this.$form);

					$self = this;

					this.$country.on('change', function(e) {
						e.preventDefault();
						$self.onCountryChanged(jQuery(this));
					});
					this.$region.on('change', function(e) {
						e.preventDefault();
						$self.onRegionChanged(jQuery(this));
					});
					this.$city.on('change', function(e) {
						e.preventDefault();
						$self.onCityChanged(jQuery(this));
					});
				},
				onCountryChanged: function($el) {
					var $self = this;
					country = $el.val();

					$self.showLoader($self.$region);
					data = {
						action: 'geodir_fill_location_on_add_listing',
						type: 'region',
						country: country
					};
					jQuery.ajax({
						url: geodir_params.gd_ajax_url,
						type: 'POST',
						dataType: 'json',
						data: data,
						beforeSend: function() {
							// blank fields
							$self.$region.val('');
							$self.$city.html('');
							$self.$neighbourhood.html('');
							$self.$zip.val('');
							// set the map to the country
							var lat = $el.find(':selected').data("country_lat");
							var lon = $el.find(':selected').data("country_lon");
							if (lat && lon) {
								if (window.gdMaps == 'google') {
									var postiton = {lat:lat, lng:lon};
									baseMarker.setPosition(postiton);
									jQuery.goMap.map.setCenter(postiton);
									jQuery.goMap.map.setZoom(4);
									updateMarkerPosition(baseMarker.getPosition());
								} else if (window.gdMaps == 'osm') {
									baseMarker.setLatLng(new L.latLng(lat, lon));
									centerMap(new L.latLng(lat, lon));
									jQuery.goMap.map.setZoom(4);
									updateMarkerPositionOSM(baseMarker.getLatLng());
								}
							}
						},
						success: function(res, textStatus, xhr) {
							$self.hideLoader($self.$region);
							if (res.success && res.data.options) {
								val = $self.$region.val();
								$self.$region.html(res.data.options);
								$self.$region.val(val);
							}
						},
						error: function(xhr, textStatus, errorThrown) {
							console.log(errorThrown);
							$self.hideLoader($self.$region);
						}
					});
				},
				onRegionChanged: function($el) {
					var $self = this;
					country = $self.$country.val();
					region = $el.val();

					$self.showLoader($self.$city);
					data = {
						action: 'geodir_fill_location_on_add_listing',
						type: 'city',
						country: country,
						region: region
					};
					jQuery.ajax({
						url: geodir_params.gd_ajax_url,
						type: 'POST',
						dataType: 'json',
						data: data,
						beforeSend: function() {
							$self.$city.html('');
							$self.$neighbourhood.html('');
						},
						success: function(res, textStatus, xhr) {
							$self.hideLoader($self.$city);
							if (res.success && res.data.options) {
								val = $self.$city.val();
								$self.$city.html(res.data.options);
								$self.$city.val(val);
							}
						},
						error: function(xhr, textStatus, errorThrown) {
							console.log(errorThrown);
							$self.hideLoader($self.$city);
						}
					});
				},
				onCityChanged: function($el) {
					var $self = this;
					var $neighbourhood_is_active = <?php echo GeoDir_Location_Neighbourhood::is_active() ? "true" : "false";?>;
					if ($self.$neighbourhood.length && $neighbourhood_is_active) {
						country = $self.$country.val();
						region = $self.$region.val();
						city = $el.val();
						neighbourhood = window.neighbourhood ? window.neighbourhood : '';

						$self.showLoader($self.$neighbourhood);
						data = {
							action: 'geodir_fill_location_on_add_listing',
							type: 'neighbourhood',
							country: country,
							region: region,
							city: city,
							neighbourhood: neighbourhood
						};
						jQuery.ajax({
							url: geodir_params.gd_ajax_url,
							type: 'POST',
							dataType: 'json',
							data: data,
							beforeSend: function() {},
							success: function(res, textStatus, xhr) {
								$self.hideLoader($self.$neighbourhood);
								if (res.success && res.data.options) {
									val = $self.$neighbourhood.val();
									$self.$neighbourhood.html(res.data.options);
									$self.$neighbourhood.val(val);
								}
							},
							error: function(xhr, textStatus, errorThrown) {
								console.log(errorThrown);
								$self.hideLoader($self.$neighbourhood);
							}
						});
					}
				},
				showLoader: function($el) {
					$el.closest('[data-argument]').find('.select2').hide();
					$el.closest('[data-argument]').find('.geodir-location-loader').remove();
					$el.after('<div class="geodir-location-loader spinner-border text-muted d-block" role="status"><span class="sr-only visually-hidden"><?php _e("Loading...","geodirlocation"); ?></span></div>');
				},
				hideLoader: function($el) {
					$el.closest('[data-argument]').find('.geodir-location-loader').remove();
					$el.closest('[data-argument]').find('.select2').show();
				}
			};

			function geodir_set_map_default_location(mapid, lat, lng) {
				if (mapid != '' && lat != '' && lng != '') {
					jQuery("#" + mapid).goMap();
					if (window.gdMaps == 'google') {
						jQuery.goMap.map.setCenter(new google.maps.LatLng(lat, lng));
						baseMarker.setPosition(new google.maps.LatLng(lat, lng));
						updateMarkerPosition(baseMarker.getPosition());
						geocodePosition(baseMarker.getPosition());
					} else if (window.gdMaps == 'osm') {
						centerMap(new L.latLng(lat, lng));
						baseMarker.setLatLng(new L.latLng(lat, lng));
						updateMarkerPositionOSM(baseMarker.getLatLng());
						geocodePositionOSM(baseMarker.getLatLng());
					}
				}
			}
			<?php if(0){ ?></script><?php }

		return ob_get_clean();
	}

}