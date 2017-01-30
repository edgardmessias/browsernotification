<?php

class PluginBrowsernotificationPreference extends CommonDBTM {

   static protected $notable = true;
   static $rightname = '';
   //
   public $user_id = null;
   public $preferences = [];

   public function __construct($user_id = null) {
      $this->user_id = $user_id;

      parent::__construct();
   }

   public function computePreferences() {
      $this->preferences = Config::getConfigurationValues('browsernotification');

      if ($this->user_id) {
         $user_prefer = Config::getConfigurationValues('browsernotification (' . $this->user_id . ')');
         $this->preferences = array_merge($this->preferences, $user_prefer);
      }
   }

   public function update(array $input, $history = 1, $options = array()) {
      $deleted = [];
      foreach ($input as $key => $value) {
         //Remove invalid options;
         if (!isset($this->preferences[$key])) {
            unset($input[$key]);
            continue;
         }
         //Removed not changed options;
         if ($this->preferences[$key] == $value) {
            $deleted[] = $key;
            unset($input[$key]);
            continue;
         }
      }

      Config::deleteConfigurationValues('browsernotification (' . $this->user_id . ')', $deleted);
      Config::setConfigurationValues('browsernotification (' . $this->user_id . ')', $input);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'Preference':
         case 'User':
            return array(1 => __bn('Browser Notification'));
         default:
            return '';
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'User':
            $prefer = new self($item->fields['id']);
            $prefer->computePreferences();
            $prefer->showFormUser();
            break;
         case 'Preference':
            $prefer = new self(Session::getLoginUserID());
            $prefer->computePreferences();
            $prefer->showFormPreference();
            break;
      }
      return true;
   }

   function showFormUser() {
      $CONFIG = $this->preferences;

      if (!User::canView()) {
         return false;
      }
      $canedit = Session::haveRight(User::$rightname, UPDATE);
      if ($canedit) {
         echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL('PluginBrowsernotificationUser') . "\" method='post'>";
      }
      echo Html::hidden('user_id', ['value' => $this->user_id]);

      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Settings') . "</th></tr>";

      $this->showFormDefault();

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Save') . "\">";
         echo "</td></tr>";
      }

      echo "</table></div>";
      Html::closeForm();
   }

   function showFormPreference() {
      $CONFIG = $this->preferences;

      $user = new self();
      if (!$user->can($this->user_id, READ) && ($this->user_id != Session::getLoginUserID())) {
         return false;
      }
      $canedit = $this->user_id == Session::getLoginUserID();

      if ($canedit) {
         echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL(__CLASS__) . "\" method='post'>";
      }

      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Settings') . "</th></tr>";

      $this->showFormDefault();

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Save') . "\">";
         echo "</td></tr>";
      }

      echo "</table></div>";
      Html::closeForm();
   }

   function showFormDefault() {
      $CONFIG = $this->preferences;

      $sounds = [
         'sound_a' => __bn('Sound') . ' A',
         'sound_b' => __bn('Sound') . ' B',
         'sound_c' => __bn('Sound') . ' C',
         'sound_d' => __bn('Sound') . ' D',
      ];

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Default notification sound') . "</td><td>";
      $rand_sound = mt_rand();
      Dropdown::showFromArray("sound", $sounds, [
         'value'               => $CONFIG["sound"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
         'rand'                => $rand_sound,
      ]);
      echo "</td><td>" . __bn('Show an example notification') . "</td><td>";
      echo "<input type='button' onclick='browsernotification && browsernotification.showExample($(\"#dropdown_sound" . $rand_sound . "\").val())' class='submit' value=\"" . __bn('Show example') . "\">";
      echo "</td></tr>";

      $sounds['default'] = __('Default value');

      //New Ticket
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __('New ticket') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_new_ticket", $CONFIG["show_new_ticket"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_new_ticket", $CONFIG["my_changes_new_ticket"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_new_ticket", $sounds, [
         'value'               => $CONFIG["sound_new_ticket"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";

      //Assigned to technicians
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __('Assigned to technicians') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_assigned_ticket", $CONFIG["show_assigned_ticket"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_assigned_ticket", $CONFIG["my_changes_assigned_ticket"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_assigned_ticket", $sounds, [
         'value'               => $CONFIG["sound_assigned_ticket"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";

      //Assigned to groups
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __('Assigned to groups') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_assigned_group_ticket", $CONFIG["show_assigned_group_ticket"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_assigned_group_ticket", $CONFIG["my_changes_assigned_group_ticket"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_assigned_group_ticket", $sounds, [
         'value'               => $CONFIG["sound_assigned_group_ticket"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";

      //New followup
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __('New followup') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_ticket_followup", $CONFIG["show_ticket_followup"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_ticket_followup", $CONFIG["my_changes_ticket_followup"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_ticket_followup", $sounds, [
         'value'               => $CONFIG["sound_ticket_followup"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";

      //Validation request
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __('Validation request') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_ticket_validation", $CONFIG["show_ticket_validation"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_ticket_validation", $CONFIG["my_changes_ticket_validation"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_ticket_validation", $sounds, [
         'value'               => $CONFIG["sound_ticket_validation"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";

      //Ticket status updated
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __bn('Ticket status updated') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_ticket_status", $CONFIG["show_ticket_status"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_ticket_status", $CONFIG["my_changes_ticket_status"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_ticket_status", $sounds, [
         'value'               => $CONFIG["sound_ticket_status"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";

      //New task
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __('New task') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_ticket_task", $CONFIG["show_ticket_task"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_ticket_task", $CONFIG["my_changes_ticket_task"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_ticket_task", $sounds, [
         'value'               => $CONFIG["sound_ticket_task"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";

      //New document
      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>" . __('Add a document') . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Show notifications') . "</td><td>";
      Dropdown::showYesNo("show_ticket_document", $CONFIG["show_ticket_document"]);
      echo "</td><td> " . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("my_changes_ticket_document", $CONFIG["my_changes_ticket_document"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __bn('Notification sound') . "</td><td>";
      Dropdown::showFromArray("sound_ticket_document", $sounds, [
         'value'               => $CONFIG["sound_ticket_document"],
         'display_emptychoice' => true,
         'emptylabel'          => __('Disabled'),
      ]);
      echo "</td><td colspan='2'></td></tr>";
   }

}
