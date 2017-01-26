<?php
//Arquivo javascript para ser usado para gerar o widget do Live Helper Chat

include ("../../../inc/includes.php");

Session::checkLoginUser();

header('Content-Type:application/javascript');

$options = [
   'user_id'  => Session::getLoginUserID(),
   'base_url' => $CFG_GLPI['root_doc'],
   'interval' => $CFG_BROWSER_NOTIF['check_interval'] * 1000,
   'locale'   => strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][2]),
];

if ($CFG_BROWSER_NOTIF['sound']) {
   $options['sound'] = [
      $CFG_GLPI['root_doc'] . '/plugins/browsernotification/sound/' . $CFG_BROWSER_NOTIF['sound'] . '.mp3',
      $CFG_GLPI['root_doc'] . '/plugins/browsernotification/sound/' . $CFG_BROWSER_NOTIF['sound'] . '.ogg',
      $CFG_GLPI['root_doc'] . '/plugins/browsernotification/sound/' . $CFG_BROWSER_NOTIF['sound'] . '.wav',
   ];
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