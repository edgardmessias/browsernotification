<?php

class PluginBrowsernotificationPreference extends CommonDBTM {

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'Preference':
            return array(1 => __("Notification", 'browsernotification'));
         default:
            return '';
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'Preference':
            $pref = new self();
            $id = $pref->addDefaultPreference(Session::getLoginUserID());
            $pref->showForm($id);
            break;
      }
      return true;
   }

}
