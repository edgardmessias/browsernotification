<?php

include ('../../../inc/includes.php');

Session::checkRight(User::$rightname, UPDATE);

if (isset($_POST["update"]) && isset($_POST["user_id"])) {

   $prefer = new PluginBrowsernotificationPreference((int) $_POST["user_id"]);
   $prefer->computePreferences();

   $prefer->update($_POST);

   Html::back();
} else {
   Html::back();
}