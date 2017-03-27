<?php
//Arquivo javascript para ser usado para gerar o widget do Live Helper Chat

include ("../../../inc/includes.php");

Session::checkLoginUser();

header('Content-Type:application/javascript');

$locale = strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][2]);

$file = GLPI_ROOT . '/plugins/browsernotification/locales/' . $_SESSION['glpilanguage'] . '.mo';

//Browser cache
if (file_exists($file)) {
   $etag = md5_file($file);
   $lastModified = filemtime($file);
   // Now send the file with header() magic
   header("Date: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
   header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
   header("Etag: $etag");
   header('Pragma: private'); /// IE BUG + SSL
   header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
   header_remove('Expires');
   // HTTP_IF_NONE_MATCH takes precedence over HTTP_IF_MODIFIED_SINCE
   // http://tools.ietf.org/html/rfc7232#section-3.3
   if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
      header("HTTP/1.1 304 Not Modified"); 
      exit;
   }
   if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
      header("HTTP/1.1 304 Not Modified"); 
      exit;
   }
}

$texts = array(
   "new_ticket"            => array(
      "item_title"  => __bn("New ticket #%ticket_id%"),
      "item_body"   => __bn("%name%"),
      "count_title" => __bn("New tickets"),
      "count_body"  => __bn("You have %count% new tickets")
   ),
   "assigned_ticket"       => array(
      "item_title"  => __bn("New assignment in ticket (#%ticket_id%)"),
      "item_body"   => __bn("You assigned to ticket #%ticket_id%\n%name%"),
      "count_title" => __bn("New assignment in tickets"),
      "count_body"  => __bn("You have %count% new tickets assigned")
   ),
   "assigned_group_ticket" => array(
      "item_title"  => __bn("New group assignment in ticket (#%ticket_id%)"),
      "item_body"   => __bn("Your group assigned to ticket #%ticket_id%\n%name%"),
      "count_title" => __bn("New group assignment in tickets"),
      "count_body"  => __bn("Your group have %count% new tickets assigned")
   ),
   "ticket_followup"       => array(
      "item_title"  => __bn("New followup on ticket #%ticket_id%"),
      "item_body"   => __bn("%user% (%type_name%):\n%content%"),
      "count_title" => __bn("New followups"),
      "count_body"  => __bn("You have %count% new followups")
   ),
   "ticket_validation"     => array(
      "item_title"  => __bn("Approval request on ticket #%ticket_id%"),
      "item_body"   => __bn("An approval request has been submitted by %user%:\n%comment_submission%"),
      "count_title" => __bn("Approval requests"),
      "count_body"  => __bn("You have %count% new approval requests")
   ),
   "ticket_status"         => array(
      "item_title"  => __bn("Status updated on ticket #%ticket_id%"),
      "item_body"   => __bn("Status of #%ticket_id% is changed to\n%status%\nby %user_name%"),
      "count_title" => __bn("Tickets status updated"),
      "count_body"  => __bn("You have %count% new tickets status updated")
   ),
   "ticket_task"           => array(
      "item_title"  => __bn("New task on ticket #%ticket_id%"),
      "item_body"   => __bn("New task (%state_text%):\n%content%"),
      "count_title" => __bn("New tasks"),
      "count_body"  => __bn("You have %count% new tasks")
   ),
   "ticket_document"       => array(
      "item_title"  => __bn("New document on ticket #%ticket_id%"),
      "item_body"   => __bn("The document \"%filename%\" has added on ticket #%ticket_id%"),
      "count_title" => __bn("New documents"),
      "count_body"  => __bn("You have %count% new documents")
   ),
   "ticket_scheduled_task" => array(
      "item_title"  => __bn("Task scheduled on ticket #%ticket_id%"),
      "item_body"   => __bn("Task scheduled for %datetime_format%:\n%content%"),
      "count_title" => __bn("Scheduled Tasks"),
      "count_body"  => __bn("You have %count% scheduled tasks for now")
   ),
);

$json_opt = 0;

if (defined("JSON_PRETTY_PRINT")) {
   $json_opt = JSON_PRETTY_PRINT;
}
?>
<?php if (false): ?>
   <!--Pequeno truque para formatar em javascript-->
   <script type="text/javascript">
<?php endif; ?>
//global register
   GLPIBrowserNotification.default.texts[<?php echo json_encode($locale); ?>] = <?php echo json_encode($texts, $json_opt); ?>
<?php if (false): ?>
   </script>
<?php endif; ?>