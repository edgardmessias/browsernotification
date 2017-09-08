<?php

$CFG_BROWSER_NOTIF = array();

include ("../../../inc/includes.php");

Session::checkLoginUser();

$return = array();

$new_ticket = isset($_GET['new_ticket']) ? (int) $_GET['new_ticket'] : -1;
$assigned_ticket = isset($_GET['assigned_ticket']) ? (int) $_GET['assigned_ticket'] : -1;
$assigned_group_ticket = isset($_GET['assigned_group_ticket']) ? (int) $_GET['assigned_group_ticket'] : -1;
$ticket_followup = isset($_GET['ticket_followup']) ? (int) $_GET['ticket_followup'] : -1;
$ticket_validation = isset($_GET['ticket_validation']) ? (int) $_GET['ticket_validation'] : -1;
$ticket_status = isset($_GET['ticket_status']) ? (int) $_GET['ticket_status'] : -1;
$ticket_task = isset($_GET['ticket_task']) ? (int) $_GET['ticket_task'] : -1;
$ticket_document = isset($_GET['ticket_document']) ? (int) $_GET['ticket_document'] : -1;
$ticket_scheduled_task = isset($_GET['ticket_scheduled_task']) ? (int) $_GET['ticket_scheduled_task'] : -1;

if ($CFG_BROWSER_NOTIF['show_new_ticket']) {
   $return['new_ticket'] = PluginBrowsernotificationChecker::getNewTicket($new_ticket);
}
if ($CFG_BROWSER_NOTIF['show_assigned_ticket']) {
   $return['assigned_ticket'] = PluginBrowsernotificationChecker::getAssignedTicket($assigned_ticket);
}
if ($CFG_BROWSER_NOTIF['show_assigned_group_ticket']) {
   $return['assigned_group_ticket'] = PluginBrowsernotificationChecker::getAssignedGroupTicket($assigned_group_ticket);
}
if ($CFG_BROWSER_NOTIF['show_ticket_followup']) {
   $return['ticket_followup'] = PluginBrowsernotificationChecker::getTicketFollowups($ticket_followup);
}
if ($CFG_BROWSER_NOTIF['show_ticket_validation']) {
   $return['ticket_validation'] = PluginBrowsernotificationChecker::getTicketValidation($ticket_validation);
}
if ($CFG_BROWSER_NOTIF['show_ticket_status']) {
   $return['ticket_status'] = PluginBrowsernotificationChecker::getTicketStatus($ticket_status);
}
if ($CFG_BROWSER_NOTIF['show_ticket_task']) {
   $return['ticket_task'] = PluginBrowsernotificationChecker::getTicketTask($ticket_task);
}
if ($CFG_BROWSER_NOTIF['show_ticket_document']) {
   $return['ticket_document'] = PluginBrowsernotificationChecker::getTicketDocument($ticket_document);
}

if ($CFG_BROWSER_NOTIF['show_ticket_scheduled_task']) {
   $return['ticket_scheduled_task'] = PluginBrowsernotificationCheckerByDatetime::getTicketScheduledTasks($ticket_scheduled_task);
}

//echo '<pre style="word-wrap: break-word;white-space: pre-wrap;">';
//print_r($return);
//echo '</pre>';
//die();

header('Content-Type: application/json; charset=utf-8');

echo json_encode($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
