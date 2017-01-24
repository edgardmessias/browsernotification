<?php

include ('../../../inc/includes.php');

Session::checkLoginUser();

if (isset($_POST["update"])) {

   $prefer = new PluginBrowsernotificationPreference(Session::getLoginUserID());
   $prefer->computePreferences();

   $prefer->update($_POST);

   Html::back();
} else {
   Html::back();
}