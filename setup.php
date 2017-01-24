<?php

define('PLUGIN_BROWSERNOTIFICATION_VERSION', '0.1.0');

// Init the hooks of the plugins -Needed
function plugin_init_browsernotification() {
   global $PLUGIN_HOOKS, $CFG_BROWSER_NOTIF;

   $PLUGIN_HOOKS['csrf_compliant']['browsernotification'] = true;

   $CFG_BROWSER_NOTIF = Config::getConfigurationValues('browsernotification');

   Plugin::registerClass('PluginBrowsernotificationConfig', [
      'addtabon' => ['Config']
   ]);

   $locale = strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][2]);

   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/notification.js';
   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/browser_notification.js';
   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/locale/' . $locale . '.js';
   $PLUGIN_HOOKS['add_javascript']['browsernotification'][] = 'js/user_notification.js.php';
}

// Get the name and the version of the plugin - Needed
function plugin_version_browsernotification() {
   return array(
      'name'           => __bn('Browser Notification'),
      'version'        => PLUGIN_BROWSERNOTIFICATION_VERSION,
      'author'         => 'Edgard Lorraine Messias',
      'homepage'       => 'https://github.com/edgardmessias/browsernotification',
      'minGlpiVersion' => '9.1'
   );
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_browsernotification_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.1', 'lt')) {
      echo __bn("This plugin requires GLPI >= 9.1");
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
