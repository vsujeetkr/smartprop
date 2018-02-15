<?php
/**
 * @file
 * The primary PHP file for this theme.
 */


function smart_preprocess_page(&$variables) {
  if ($variables['is_front']) {
	drupal_add_js(path_to_theme().'/js/events.js', array('type' => 'file', 'scope' => 'footer'));
	$variables['scripts'] = drupal_get_js();
  }
}