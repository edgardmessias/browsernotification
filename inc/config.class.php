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

   /**
    * Print the config form for display
    *
    * @return Nothing (display)
    * */
   function showFormDisplay() {
      global $CFG_GLPI, $CFG_BROWSER_NOTIF;

      if (!Config::canView()) {
         return false;
      }
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
      echo "<td width='30%'> " . __bn('Check deleted items?') . "</td><td  width='20%'>";
      Dropdown::showYesNo("ignore_deleted_items", $CFG_BROWSER_NOTIF["ignore_deleted_items"]);
      echo "</td><td width='30%'>" . __bn('Time to check for new notifications (in seconds)') . "</td>";
      echo "<td width='20%'>";
      Dropdown::showInteger('check_interval', $CFG_BROWSER_NOTIF["check_interval"], 5, 120, 5);
      echo "</td></tr>";

      echo "<tr><th colspan='4'>" . __('Default values') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'> " . __('Notifications for my changes') . "</td><td  width='20%'>";
      Dropdown::showYesNo("notification_my_changes", $CFG_BROWSER_NOTIF["notification_my_changes"]);
      echo "</td></tr>";


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
