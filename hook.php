<?php

function plugin_browsernotification_install() {

   $default = [
      'ignore_deleted_items'             => 1,
      'check_interval'                   => 5,
      'icon_url'                         => '',
      'show_new_ticket'                  => 1,
      'my_changes_new_ticket'            => 0,
      'show_assigned_ticket'             => 1,
      'my_changes_assigned_ticket'       => 0,
      'show_assigned_group_ticket'       => 1,
      'my_changes_assigned_group_ticket' => 0,
      'show_ticket_followup'             => 1,
      'my_changes_ticket_followup'       => 0,
      'show_ticket_validation'           => 1,
      'my_changes_ticket_validation'     => 0,
      'show_ticket_status'               => 1,
      'my_changes_ticket_status'         => 0,
      'show_ticket_task'                 => 1,
      'my_changes_ticket_task'           => 0,
      'sound'                            => 'sound_a',
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
