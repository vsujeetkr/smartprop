<?php
/**
 * @file
 * The primary PHP file for this theme.
 */


function smart_preprocess_page(&$variables) {
  if ($variables['is_front']) {
  	drupal_add_js('https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.0.0-beta.2.4/owl.carousel.min.js', 'external');
  	drupal_add_css('https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.0.0-beta.2.4/assets/owl.carousel.min.css', 'external');
	drupal_add_css('https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.0.0-beta.2.4/assets/owl.theme.default.css', 'external');
	drupal_add_js(path_to_theme().'/js/events.js', array('type' => 'file', 'scope' => 'header'));
	$variables['scripts'] = drupal_get_js();
  }
}