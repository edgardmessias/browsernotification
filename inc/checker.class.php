<?php

class PluginBrowsernotificationChecker {

   protected static function executeQuery($table, $last_id = 0, $select = '', $where = '', $join = '', $limit = 3) {
      /* @var $DB DB */
      global $DB;

      $found = array();
      $last_id = (int) $last_id;

      if (is_array($select)) {
         $select = implode(", ", $select);
      }
      if (is_array($where)) {
         $where = array_filter($where);
         $where = implode("\n  AND ", $where);
      }
      if (is_array($join)) {
         $join = array_filter($join);
         $join = implode("\n", $join);
      }

      if (!$select) {
         $select = "`$table`.*";
      }

      $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `$table`.id,  $select
FROM `$table`
$join
WHERE `$table`.id > $last_id
  AND $where
ORDER BY `$table`.id DESC
LIMIT $limit";

      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
            $found[] = $data;
         }
      }

      // get result full row counts
      $query_numtotalrow = "SELECT FOUND_ROWS()";
      $result_numtotalrow = $DB->query($query_numtotalrow);
      $data_numtotalrow = $DB->fetch_assoc($result_numtotalrow);
      $totalcount = $data_numtotalrow['FOUND_ROWS()'];

      // get the max id
      $query_lastidrow = "SELECT IFNULL(MAX(`$table`.id),0) id FROM `$table`";

      $result_lastidrow = $DB->query($query_lastidrow);
      if ($result_lastidrow) {
         $data_lastidrow = $DB->fetch_assoc($result_lastidrow);
         $last_id = $data_lastidrow['id'];
      } else {
         $last_id = -1;
      }

      if ($totalcount > count($found)) {
         $found = array();
      }
      if (!empty($found)) {
         $found = array_reverse($found);
      }

      return array(
         'last_id' => (int) $last_id,
         'items'   => $found,
         'count'   => (int) $totalcount,
//         'query'   => $query,
      );
   }

   public static function getNewTicket($last_id = 0, $max_items = 3) {
      /* @var $DB DB */
      global $DB, $CFG_BROWSER_NOTIF;

      $table = 'glpi_tickets';

      $select = array();
      $join = array();
      $where = array();

      $select[] = "`$table`.id AS ticket_id";
      $select[] = "`$table`.name";

      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`$table`.is_deleted = 0"; //Not deleted
      }
      if (!$CFG_BROWSER_NOTIF['my_changes_new_ticket']) {
         $where[] = "`$table`.users_id_recipient <> " . Session::getLoginUserID(); //Ignore current user
      }
      //Join user
      $join[] = "LEFT JOIN `glpi_tickets_users` user_request"
            . "\n       ON user_request.tickets_id = `$table`.id"
            . "\n      AND user_request.type = " . Ticket_User::REQUESTER;
      $join[] = "LEFT JOIN `glpi_tickets_users` user_observer"
            . "\n       ON user_observer.tickets_id = `$table`.id"
            . "\n      AND user_observer.type = " . Ticket_User::OBSERVER;
      $join[] = "LEFT JOIN `glpi_tickets_users` user_assign"
            . "\n       ON user_assign.tickets_id = `$table`.id"
            . "\n      AND user_assign.type = " . Ticket_User::ASSIGN;

      $user_where = array();
      $user_where[] = "user_request.users_id = " . Session::getLoginUserID();
      $user_where[] = "user_observer.users_id = " . Session::getLoginUserID();
//      $user_where[] = "user_assign.users_id = " . Session::getLoginUserID(); //Use self::getAssignedTicket
      //Join Group
      $join[] = "LEFT JOIN `glpi_groups_tickets` group_request"
            . "\n       ON group_request.tickets_id = `$table`.id"
            . "\n      AND group_request.type = " . Group_Ticket::REQUESTER;
      $join[] = "LEFT JOIN `glpi_groups_tickets` group_observer"
            . "\n       ON group_observer.tickets_id = `$table`.id"
            . "\n      AND group_observer.type = " . Group_Ticket::OBSERVER;
      $join[] = "LEFT JOIN `glpi_groups_tickets` group_assign"
            . "\n       ON group_assign.tickets_id = `$table`.id"
            . "\n      AND group_assign.type = " . Group_Ticket::ASSIGN;

      //Show new ticket from group
      if (!empty($_SESSION['glpigroups'])) {
         $group_list = implode(',', $_SESSION['glpigroups']);
         $user_where[] = "group_request.groups_id IN ($group_list)";
         $user_where[] = "group_observer.groups_id IN ($group_list)";
//         $user_where[] = "group_assign.groups_id IN ($group_list)"; //Use self::getAssignedGroupTicket
      }

      //Show to technician news tickets without assign
      if (Session::haveRightsOr(Ticket::$rightname, [Ticket::STEAL, Ticket::OWN])) {
         $technician_where = array();
         $technician_where[] = "`$table`.status = " . Ticket::INCOMING;
         $technician_where[] = "user_assign.users_id IS NULL";
         $technician_where[] = "group_assign.groups_id IS NULL";

         $user_where[] = '(' . implode(" AND ", $technician_where) . ')';
      }
      $where[] = '(' . implode(" OR ", $user_where) . ')';

      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", $table, '', $_SESSION['glpiactiveentities'], false, true);

      return self::executeQuery($table, $last_id, $select, $where, $join, $max_items);
   }

   public static function getAssignedTicket($last_id = 0, $max_items = 3) {
      /* @var $DB DB */
      global $DB, $CFG_BROWSER_NOTIF;

      $table = 'glpi_tickets_users';

      $select = array();
      $join = array();
      $where = array();

      //Only assigned
      $where[] = "`$table`.type = " . Ticket_User::ASSIGN;
      $where[] = "`$table`.users_id = " . Session::getLoginUserID();
      $select[] = "`$table`.users_id AS user_id";

      $join[] = "INNER JOIN `glpi_tickets`"
            . "\n        ON `glpi_tickets`.id = `$table`.tickets_id";
      $select[] = "`glpi_tickets`.id AS ticket_id";
      $select[] = "`glpi_tickets`.name";

      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }
      if (!$CFG_BROWSER_NOTIF['my_changes_assigned_ticket']) {
         $where[] = "`glpi_tickets`.users_id_lastupdater <> " . Session::getLoginUserID(); //Ignore current user
      }
      //
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", 'glpi_tickets', '', $_SESSION['glpiactiveentities'], false, true);

      return self::executeQuery($table, $last_id, $select, $where, $join, $max_items);
   }

   public static function getAssignedGroupTicket($last_id = 0, $max_items = 3) {
      /* @var $DB DB */
      global $DB, $CFG_BROWSER_NOTIF;

      //Not search if user group is empty
      if (empty($_SESSION['glpigroups'])) {
         return false;
      }

      $table = 'glpi_groups_tickets';

      $select = array();
      $join = array();
      $where = array();

      //Only assigned
      $where[] = "`$table`.type = " . Ticket_User::ASSIGN;
      $where[] = "`$table`.groups_id IN (" . implode(', ', $_SESSION['glpigroups']) . ")";
      $select[] = "`$table`.groups_id AS group_id";

      $join[] = "INNER JOIN `glpi_tickets`"
            . "\n        ON `glpi_tickets`.id = `$table`.tickets_id";
      $select[] = "`glpi_tickets`.id AS ticket_id";
      $select[] = "`glpi_tickets`.name";

      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }
      if (!$CFG_BROWSER_NOTIF['my_changes_assigned_group_ticket']) {
         $where[] = "`glpi_tickets`.users_id_lastupdater <> " . Session::getLoginUserID(); //Ignore current user
      }
      //
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", 'glpi_tickets', '', $_SESSION['glpiactiveentities'], false, true);

      return self::executeQuery($table, $last_id, $select, $where, $join, $max_items);
   }

   public static function getTicketFollowups($last_id = 0, $max_items = 3) {
      /* @var $DB DB */
      global $DB, $CFG_BROWSER_NOTIF;

      $canseepublic = Session::haveRight(TicketFollowup::$rightname, TicketFollowup::SEEPUBLIC);
      $canseeprivate = Session::haveRight(TicketFollowup::$rightname, TicketFollowup::SEEPRIVATE);

      if (!$canseepublic && !$canseeprivate) {
         return false;
      }

      $table = 'glpi_ticketfollowups';

      $select = array();
      $join = array();
      $where = array();

      $select[] = "`$table`.tickets_id AS ticket_id";
      $select[] = "`$table`.content";
      $select[] = "`$table`.is_private";

      if (!$CFG_BROWSER_NOTIF['my_changes_ticket_followup']) {
         $where[] = "`$table`.users_id <> " . Session::getLoginUserID(); //Ignore current user
      }

      $join[] = "INNER JOIN `glpi_tickets` ON `glpi_tickets`.id = `$table`.tickets_id";
      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", 'glpi_tickets', '', $_SESSION['glpiactiveentities'], false, true);

      //Only related ticket with user
      $join[] = "INNER JOIN `glpi_tickets_users` ON `glpi_tickets_users`.tickets_id = `$table`.tickets_id";
      $where[] = "`glpi_tickets_users`.users_id = " . Session::getLoginUserID();

      if ($canseepublic && !$canseeprivate) {
         $where[] = "`$table`.is_private = 0"; //Only public
      } elseif (!$canseepublic && $canseeprivate) {
         $where [] = "`$table`.is_private = 1"; //Only private
      } //else showall
      //Add user
      $join[] = "INNER JOIN `glpi_users` ON `glpi_users`.id = `$table`.users_id";
      $select[] = "`glpi_users`.name AS login";
      $select[] = "`glpi_users`.realname";
      $select[] = "`glpi_users`.firstname";


      $return = self::executeQuery($table, $last_id, $select, $where, $join, $max_items);

      if (isset($return['items']) && count($return['items']) > 0) {
         foreach ($return['items'] as &$item) {
            $item['type_name'] = $item['is_private'] ? __('Private') : __('Public');

            $item['user'] = formatUserName('', $item['login'], $item['realname'], $item['firstname']);
            unset($item['login']);
            unset($item['realname']);
            unset($item['firstname']);
         }
      }

      return $return;
   }

   public static function getTicketValidation($last_id = 0, $max_items = 3) {
      /* @var $DB DB */
      global $DB, $CFG_BROWSER_NOTIF;

      $validateincident = Session::haveRight(TicketValidation::$rightname, TicketValidation::VALIDATEINCIDENT);
      $validaterequest = Session::haveRight(TicketValidation::$rightname, TicketValidation::VALIDATEREQUEST);

      if (!$validateincident && !$validaterequest) {
         return false;
      }

      $table = 'glpi_ticketvalidations';

      $select = array();
      $join = array();
      $where = array();

      $select[] = "`$table`.tickets_id AS ticket_id";
      $select[] = "`$table`.comment_submission";
      $select[] = "`$table`.status";

      if (!$CFG_BROWSER_NOTIF['my_changes_ticket_validation']) {
         $where[] = "`$table`.users_id <> " . Session::getLoginUserID(); //Ignore current user
      }
      $where[] = "`$table`.users_id_validate = " . Session::getLoginUserID(); //User to validate
      $where[] = "`$table`.status = " . TicketValidation::WAITING; //Waiting validation

      $join[] = "INNER JOIN `glpi_tickets` ON `glpi_tickets`.id = `$table`.tickets_id";
      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", 'glpi_tickets', '', $_SESSION['glpiactiveentities'], false, true);

      if ($validateincident && !$validaterequest) {
         $where[] = "`glpi_tickets`.type = " . Ticket::INCIDENT_TYPE; //Only Incident
      } elseif (!$validateincident && $validaterequest) {
         $where[] = "`glpi_tickets`.type = " . Ticket::DEMAND_TYPE; //Only Demand
      } //else showall
      //Add user
      $join[] = "INNER JOIN `glpi_users` ON `glpi_users`.id = `$table`.users_id";
      $select[] = "`glpi_users`.name AS login";
      $select[] = "`glpi_users`.realname";
      $select[] = "`glpi_users`.firstname";


      $return = self::executeQuery($table, $last_id, $select, $where, $join, $max_items);

      if (isset($return['items']) && count($return['items']) > 0) {
         foreach ($return['items'] as &$item) {
            $item['user'] = formatUserName('', $item['login'], $item['realname'], $item['firstname']);
            unset($item['login']);
            unset($item['realname']);
            unset($item['firstname']);
         }
      }

      return $return;
   }

   public static function getTicketStatus($last_id = 0, $max_items = 3) {
      /* @var $DB DB */
      global $DB, $CFG_BROWSER_NOTIF;

      $table = 'glpi_logs';

      $select = array();
      $join = array();
      $where = array();

      $select[] = "`$table`.items_id AS ticket_id";
      $select[] = "`$table`.user_name";
      $select[] = "`$table`.old_value";
      $select[] = "`$table`.new_value";

      $where[] = "`$table`.itemtype = 'Ticket'"; //Only ticket type
      $where[] = "`$table`.linked_action = 0"; //Not link action
      if (!$CFG_BROWSER_NOTIF['my_changes_ticket_status']) {
         $where[] = "`$table`.user_name NOT LIKE '%(" . Session::getLoginUserID() . ")'"; //Ignore current user
      }
      $where[] = "`$table`.id_search_option = 12"; //Status field
      //
      //Join ticket
      $join[] = "INNER JOIN `glpi_tickets`"
            . "\n       ON `glpi_tickets`.id = `$table`.items_id";
      //Join user
      $join[] = "LEFT JOIN `glpi_tickets_users`"
            . "\n       ON `glpi_tickets_users`.tickets_id = `glpi_tickets`.id"
            . "\n      AND `glpi_tickets_users`.type IN (" . implode(', ', array(Ticket_User::REQUESTER, Ticket_User::ASSIGN, Ticket_User::OBSERVER)) . ")";

      $user_where = array();
      $user_where[] = "`glpi_tickets_users`.users_id = " . Session::getLoginUserID();

      //Show new ticket from group
      if (!empty($_SESSION['glpigroups'])) {
         //Join Group
         $join[] = "LEFT JOIN `glpi_groups_tickets`"
               . "\n       ON `glpi_groups_tickets`.tickets_id = `glpi_tickets`.id"
               . "\n      AND `glpi_groups_tickets`.type IN (" . implode(', ', array(Group_Ticket::REQUESTER, Group_Ticket::ASSIGN, Group_Ticket::OBSERVER)) . ")";

         $group_list = implode(',', $_SESSION['glpigroups']);
         $user_where[] = "`glpi_groups_tickets`.groups_id IN ($group_list)";
      }

      $where[] = '(' . implode(" OR ", $user_where) . ')';

      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", 'glpi_tickets', '', $_SESSION['glpiactiveentities'], false, true);

      $return = self::executeQuery($table, $last_id, $select, $where, $join, $max_items);

      if (isset($return['items']) && count($return['items']) > 0) {
         foreach ($return['items'] as &$item) {
            $item['status'] = Ticket::getStatus((int) $item['new_value']);
            $item['user_name'] = preg_replace('/ ?\(.*$/', '', $item['user_name']); //Remove code
         }
      }

      return $return;
   }

   public static function getTicketTask($last_id = 0, $max_items = 3) {
      /* @var $DB DB */
      global $DB, $CFG_BROWSER_NOTIF;

      $seepublic = Session::haveRight(TicketTask::$rightname, TicketTask::SEEPUBLIC);
      $seeprivate = Session::haveRight(TicketTask::$rightname, TicketTask::SEEPRIVATE);

      if (!$seepublic && !$seeprivate) {
         return false;
      }

      $table = 'glpi_tickettasks';

      $select = array();
      $join = array();
      $where = array();

      $select[] = "`$table`.tickets_id AS ticket_id";
      $select[] = "`$table`.content";
      $select[] = "`$table`.state";

      if (!$CFG_BROWSER_NOTIF['my_changes_ticket_task']) {
         $where[] = "`$table`.users_id <> " . Session::getLoginUserID(); //Ignore current user
      }

      $user_where = array();
      $user_where[] = "`$table`.users_id_tech = " . Session::getLoginUserID(); //User to validate
      //

      if (version_compare(GLPI_VERSION, '9.1', '>=')) {
         //Show new task from group
         if (!empty($_SESSION['glpigroups'])) {
            $group_list = implode(',', $_SESSION['glpigroups']);
            $user_where[] = "`$table`.groups_id_tech IN ($group_list)";
         }
      }

      $where[] = '(' . implode(" OR ", $user_where) . ')';

      $join[] = "INNER JOIN `glpi_tickets` ON `glpi_tickets`.id = `$table`.tickets_id";
      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", 'glpi_tickets', '', $_SESSION['glpiactiveentities'], false, true);

      if ($seepublic && !$seeprivate) {
         $where[] = "`$table`.is_private = 0"; //Only Public
      } elseif (!$seepublic && $seeprivate) {
         $where[] = "`$table`.is_private = 1"; //Only Private
      } //else showall

      $return = self::executeQuery($table, $last_id, $select, $where, $join, $max_items);

      if (isset($return['items']) && count($return['items']) > 0) {
         foreach ($return['items'] as &$item) {
            $item['state_text'] = Planning::getState($item['state']);
         }
      }

      return $return;
   }

   public static function getTicketDocument($last_id = 0, $max_items = 3) {
      global $CFG_BROWSER_NOTIF;

      $table = 'glpi_documents_items';

      $select = array();
      $join = array();
      $where = array();

      $select[] = "`$table`.items_id AS ticket_id";

      $join[] = "INNER JOIN `glpi_tickets` ON `glpi_tickets`.id = `$table`.items_id AND `$table`.itemtype = 'Ticket'";
      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }
      if (!$CFG_BROWSER_NOTIF['my_changes_ticket_document']) {
         $where[] = "`glpi_tickets`.users_id_lastupdater <> " . Session::getLoginUserID(); //Ignore current user
      }
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", $table, '', $_SESSION['glpiactiveentities'], false, true);

      //Only related ticket with user
      $join[] = "INNER JOIN `glpi_tickets_users` ON `glpi_tickets_users`.tickets_id = `glpi_tickets`.id";
      $where[] = "`glpi_tickets_users`.users_id = " . Session::getLoginUserID();

      //Join document
      $join[] = "INNER JOIN `glpi_documents` ON `glpi_documents`.id = `$table`.documents_id";
      $select[] = "`glpi_documents`.filename";

      //Ignore new ticket with documents
      if (version_compare(GLPI_VERSION, '9.1', '>=')) {
         $where[] = "`$table`.date_mod <> `glpi_tickets`.date_creation";
      } else {
         //@note IF tickets is updated between checks, user is notified with a new document
         $where[] = "`$table`.date_mod <> `glpi_tickets`.date_mod";
      }

      return self::executeQuery($table, $last_id, $select, $where, $join, $max_items);
   }

}
