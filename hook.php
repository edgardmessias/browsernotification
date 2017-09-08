<?php

function plugin_browsernotification_install() {

   $default = array(
      'ignore_deleted_items'             => 1,
      'check_interval'                   => 5,
      'icon_url'                         => '',
      'sound'                            => 'sound_a',
      'show_new_ticket'                  => 1,
      'my_changes_new_ticket'            => 0,
      'sound_new_ticket'                 => 'default',
      'show_assigned_ticket'             => 1,
      'my_changes_assigned_ticket'       => 0,
      'sound_assigned_ticket'            => 'default',
      'show_assigned_group_ticket'       => 1,
      'my_changes_assigned_group_ticket' => 0,
      'sound_assigned_group_ticket'      => 'default',
      'show_ticket_followup'             => 1,
      'my_changes_ticket_followup'       => 0,
      'sound_ticket_followup'            => 'default',
      'show_ticket_validation'           => 1,
      'my_changes_ticket_validation'     => 0,
      'sound_ticket_validation'          => 'default',
      'show_ticket_status'               => 1,
      'my_changes_ticket_status'         => 0,
      'sound_ticket_status'              => 'default',
      'show_ticket_task'                 => 1,
      'my_changes_ticket_task'           => 0,
      'sound_ticket_task'                => 'default',
      'show_ticket_document'             => 1,
      'my_changes_ticket_document'       => 0,
      'sound_ticket_document'            => 'default',
      'show_ticket_scheduled_task'       => 1,
      'sound_ticket_scheduled_task'      => 'default',
   );

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
      $config->delete(array('id' => $id));
   }

   return true;
}
