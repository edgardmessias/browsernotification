<?php

function plugin_browsernotification_install() {

   $default = [
      'ignore_deleted_items'    => 1,
      'check_interval'          => 5,
      'notification_my_changes' => 0,
      'icon_url'                => '',
   ];

   $current = Config::getConfigurationValues('browsernotification');

   foreach ($default as $key => $value) {
      if (!isset($current[$key])) {
         $current[$key] = $value;
      }
   }

   Config::setConfigurationValues('browsernotification', $current);
   return true;
}

function plugin_browsernotification_uninstall() {

   $config = new Config();
   $rows = $config->find("`context` LIKE 'browsernotification%'");

   foreach ($rows as $id => $row) {
      $config->delete(['id' => $id]);
   }

   return true;
}
