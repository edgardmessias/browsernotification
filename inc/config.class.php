<?php

class PluginBrowsernotificationConfig extends CommonDBTM {

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'Config':
            return array(1 => __bn('Browser Notification'));
         default:
            return '';
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'Config':
            $config = new self();
            $config->showFormDisplay();
            break;
      }
      return true;
   }

   static function configUpdate($input) {
      unset($input['_no_history']);
      return $input;
   }

   /**
    * Print the config form for display
    *
    * @return Nothing (display)
    * */
   function showFormDisplay() {
      global $CFG_GLPI;

      if (!Config::canView()) {
         return false;
      }
      
      $CFG_GLOBAL = Config::getConfigurationValues('browsernotification');

      $canedit = Session::haveRight(Config::$rightname, UPDATE);
      if ($canedit) {
         echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL('Config') . "\" method='post'>";
      }
      echo Html::hidden('config_context', ['value' => 'browsernotification']);
      echo Html::hidden('config_class', ['value' => __CLASS__]);

      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('General setup') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'> " . __bn('Ignore deleted items?') . "</td><td  width='20%'>";
      Dropdown::showYesNo("ignore_deleted_items", $CFG_GLOBAL["ignore_deleted_items"]);
      echo "</td><td width='30%'>" . __bn('Time to check for new notifications (in seconds)') . "</td>";
      echo "<td width='20%'>";
      Dropdown::showNumber('check_interval', [
         'value' => $CFG_GLOBAL["check_interval"],
         'min'   => 5,
         'max'   => 120,
         'step'  => 5,
      ]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __bn('URL of the icon') . "</td>";
      echo "<td colspan='3'><input type='text' name='icon_url' size='80' value='" . $CFG_GLOBAL["icon_url"] . "' "
      . "placeholder='Default: " . $CFG_GLPI['root_doc'] . "/plugins/browsernotification/pics/glpi.png'/>";
      echo "</td></tr>";

      echo "<tr><th colspan='4'>" . __('Default values') . "</th></tr>";

      $prefer = new PluginBrowsernotificationPreference();
      $prefer->computePreferences();
      $prefer->showFormDefault();

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Save') . "\">";
         echo "</td></tr>";
      }

      echo "</table></div>";
      Html::closeForm();
   }

}
