<?php
/*
Plugin Name: <img style="vertical-align:middle;" src="http://blogpig.com/favicon.ico"> TagPiG
Description: TagPiG automatically tags all your posts with themed keywords. TagPiG Pro includes a 'Tag Now' button and archive auto-tagging feature. <a href="http://blogpig.com/api_key" target="_blank">You need a BlogPiG API key</a> to use it. Don't forget to try out our other <a href="http://blogpig.com/products/" target="_blank">WordPress Automation Plugins.</a>
Plugin URI:  http://blogpig.com/products/tagpig
Version:     2.2.2
Author:      BlogPiG
Author URI:  http://blogpig.com
*/

function wptagpig_activate() {
  $this_app = 'tagpig';

  $installed_apps_string = trim(get_option('blogpig_apps'));
  $installed_apps = array();
  if($installed_apps_string != '') {
    $installed_apps = explode(',', get_option('blogpig_apps'));
  }
  if(array_search($this_app, $installed_apps) == FALSE) {
    array_push($installed_apps, $this_app);
    $installed_apps = array_unique($installed_apps);
    update_option('blogpig_apps', implode(',',  $installed_apps));
  }
  unset($installed_apps);

  # Initial Settings
  if(function_exists('wptagpig_read_initial_settings')) {
    $filename = dirname(__FILE__) . "/initial_settings.txt";
    wptagpig_read_initial_settings($filename);
  }
  else {
    wptagpig_read_default_settings();
  }

}
register_activation_hook(__FILE__, 'wptagpig_activate');

function wptagpig_deactivate() {
  $this_app = 'tagpig';
  $installed_apps = explode(',', get_option('blogpig_apps'));
  if(count($installed_apps) > 0) {
    $output_apps = array();
    foreach($installed_apps as $app) {
      if($app != $this_app) {
        array_push($output_apps, $app);
      }
    }
    update_option('blogpig_apps', implode(',',  $output_apps));
    unset($output_apps);
  }
  unset($installed_apps);
}
register_deactivation_hook(__FILE__, 'wptagpig_deactivate');

function blogpig_tag_conf(){
  $pluginpath = dirname(__FILE__);
  include("$pluginpath/config.php");
}

function blogpig_tag_not_conf(){
  echo "Not Available!";
}

function add_blogpig_tag_to_submenu() {
  # TagPiG
  if(function_exists('blogpig_tag_conf')) {
    define('TAG_CONF_FUNCTION', 'blogpig_tag_conf', TRUE);
  }
  else {
    define('TAG_CONF_FUNCTION', 'blogpig_tag_not_conf', TRUE);
  }
  if(!defined('BLOGPIG_CONF_PARENT')) {
    define('BLOGPIG_CONF_PARENT', __FILE__, TRUE);
    add_menu_page('BlogPiG Page', 'BlogPiG', 8, __FILE__, TAG_CONF_FUNCTION);
  }
  add_submenu_page(BLOGPIG_CONF_PARENT, 'TagPiG Page', 'TagPiG', 8, __FILE__, TAG_CONF_FUNCTION);
}
add_action('admin_menu', 'add_blogpig_tag_to_submenu', 10);

/*
 * Add a plugin meta link
 */
function wptagpig_set_plugin_meta($links, $file) {
  $plugin = plugin_basename(__FILE__);
  if ($file == $plugin) {
    return array_merge(
      $links,
      array(sprintf('<a href="admin.php?page=%s">%s</a>', $plugin, __('Settings')))
    );
  }
  return $links;
}
add_filter('plugin_row_meta', 'wptagpig_set_plugin_meta', 10, 2);

require_once dirname(__FILE__) . "/tagpig_main.php";

# Add the PRO functionality...
$pro_file = dirname(__FILE__) . "/tagpig_pro.php";
tagpig_load_ioncube();
if(file_exists($pro_file) && (extension_loaded('ionCube Loader') || strpos(file_get_contents($pro_file), 'tagpigpro'))) {
  @include_once "$pro_file";
}

# Add the notices...
$notices_file = dirname(__FILE__) . "/tagpig_notices.php";
@include_once "$notices_file";

?>
