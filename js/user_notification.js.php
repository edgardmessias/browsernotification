<?php
//Arquivo javascript para ser usado para gerar o widget do Live Helper Chat

include ("../../../inc/includes.php");

Session::checkLoginUser();

header('Content-Type:application/javascript');

$options = [
   'user_id'  => Session::getLoginUserID(),
   'base_url' => $CFG_GLPI['root_doc'],
   'interval' => ($CFG_BROWSER_NOTIF['check_interval'] > 5 ? $CFG_BROWSER_NOTIF['check_interval'] : 5) * 1000,
   'locale'   => strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][2]),
];

$options['sound'] = [
   'default' => $CFG_BROWSER_NOTIF['sound'] ? $CFG_BROWSER_NOTIF['sound'] : false,
];

foreach ($CFG_BROWSER_NOTIF as $key => $value) {
   if (strncmp($key, 'sound_', 6) !== 0) {
      continue;
   }
   //if default, ignore
   if ($value === 'default') {
      continue;
   }
   $name = substr($key, 6);
   $options['sound'][$name] = $value ? $value : false;
}

if ($CFG_BROWSER_NOTIF["icon_url"]) {
   $options['icon'] = $CFG_BROWSER_NOTIF["icon_url"];
}
?>
<?php if (false): ?>
   <!--Pequeno truque para formatar em javascript-->
   <script type="text/javascript">
<?php endif; ?>
   //global register
   browsernotification = new GLPIBrowserNotification(<?php echo json_encode($options) ?>);
   browsernotification.start();
<?php if (false): ?>
   </script>
<?php endif; ?>