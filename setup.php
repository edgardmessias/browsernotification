<?php

define('PLUGIN_BROWSERNOTIFICATION_VERSION', '1.1.9');

// Init the hooks of the plugins -Needed
function plugin_init_browsernotification() {
   global $PLUGIN_HOOKS, $CFG_GLPI, $CFG_BROWSER_NOTIF;

   $PLUGIN_HOOKS['csrf_compliant']['browsernotification'] = true;

   $CFG_BROWSER_NOTIF = Config::getConfigurationValues('browsernotification');

   if (Session::getLoginUserID()) {
      $user_prefer = Config::getConfigurationValues('browsernotification (' . Session::getLoginUserID() . ')');
      $CFG_BROWSER_NOTIF = array_merge($CFG_BROWSER_NOTIF, $user_prefer);
   }

   Plugin::registerClass('PluginBrowsernotificationConfig', [
      'addtabon' => ['Config']
   ]);

   Plugin::registerClass('PluginBrowsernotificationPreference', [
      'addtabon' => ['Preference', 'User']
   ]);

   $PLUGIN_HOOKS['config_page']['browsernotification'] = '../../front/config.form.php?forcetab=PluginBrowsernotificationConfig$1';

   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/notification.js';
   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/browser_notification.js';
   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/locale.js.php';
   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/user_notification.js.php';
}

// Get the name and the version of the plugin - Needed
function plugin_version_browsernotification() {
   return array(
      'name'           => __bn('Browser Notification'),
      'version'        => PLUGIN_BROWSERNOTIFICATION_VERSION,
      'author'         => 'Edgard Lorraine Messias',
      'homepage'       => 'https://github.com/edgardmessias/browsernotification',
      'license'        => 'BSD-3-Clause',
      'minGlpiVersion' => '0.85'
   );
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_browsernotification_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '0.85', 'lt')) {
      echo __bn("This plugin requires GLPI >= 0.85");
      return false;
   } else {
      return true;
   }
}

function plugin_browsernotification_check_config() {
   return true;
}

function __bn($str) {
   return __($str, 'browsernotification');
}
