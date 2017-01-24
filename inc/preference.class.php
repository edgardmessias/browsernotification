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

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'> " . __('Notifications for my changes') . "</td><td  width='20%'>";
      Dropdown::showYesNo("notification_my_changes", $CONFIG["notification_my_changes"]);
      echo "</td></tr>";
   }

}
