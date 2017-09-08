<?php

class PluginBrowsernotificationCheckerByDatetime {

   protected static function executeQuery($table, $field_datetime, $last_timestamp = 0, $select = '', $where = '', $join = '', $limit = 10) {
      /* @var $DB DB */
      global $DB;

      $last_date_escaped = $DB->escape(date('Y-m-d H:i:s', $last_timestamp));

      $date_time_now = date('Y-m-d H:i:s');
      $date_time_now_escaped = $DB->escape($date_time_now);

      $found = array();

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

      $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `$table`.$field_datetime AS datetime,  $select
FROM `$table`
$join
WHERE `$table`.$field_datetime > '$last_date_escaped'
  AND `$table`.$field_datetime <= '$date_time_now_escaped'
  AND $where
ORDER BY `$table`.$field_datetime DESC
LIMIT $limit";

      $date_formats = array(0 => 'Y-m-d H:i:s',
         1 => 'd/m/Y H:i:s',
         2 => 'm/d/Y H:i:s');
      $date_format = $date_formats[$_SESSION["glpidate_format"]];


      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
            $timestamp = strtotime($data['datetime']);
            $data['datetime_format'] = date($date_format, $timestamp);

            $found[] = $data;
         }
      }

      // get result full row counts
      $query_numtotalrow = "SELECT FOUND_ROWS()";
      $result_numtotalrow = $DB->query($query_numtotalrow);
      $data_numtotalrow = $DB->fetch_assoc($result_numtotalrow);
      $totalcount = $data_numtotalrow['FOUND_ROWS()'];

      // get the max id
      $query_lastidrow = "SELECT IFNULL(MAX(`$table`.$field_datetime),'$date_time_now_escaped') id"
            . " FROM `$table` $join"
            . " WHERE `$table`.$field_datetime <= '$date_time_now_escaped'"
            . " AND $where";

      $result_lastidrow = $DB->query($query_lastidrow);
      if ($result_lastidrow) {
         $data_lastidrow = $DB->fetch_assoc($result_lastidrow);
         $last_timestamp = strtotime($data_lastidrow['id']);
      } else {
         $last_timestamp = -1;
      }

      if ($totalcount > count($found)) {
         $found = array();
      }
      if (!empty($found)) {
         $found = array_reverse($found);
      }

      return array(
         'last_id' => (int) $last_timestamp,
         'items'   => $found,
         'count'   => (int) $totalcount,
//         'query'   => $query,
//         'query_lastidrow'   => $query_lastidrow,
      );
   }

   public static function getTicketScheduledTasks($last_timestamp = 0, $max_items = 3) {
      global $CFG_BROWSER_NOTIF;

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

      $user_where = array();
      $user_where[] = "`$table`.users_id_tech = " . Session::getLoginUserID(); //User to validate

      if (version_compare(GLPI_VERSION, '9.1', '>=')) {
         //Show new task from group
         if (!empty($_SESSION['glpigroups'])) {
            $group_list = implode(',', $_SESSION['glpigroups']);
            $user_where[] = "`$table`.groups_id_tech IN ($group_list)";
         }
      }

      $where[] = '(' . implode(" OR ", $user_where) . ')';

      //Join with tickets
      $join[] = "INNER JOIN `glpi_tickets` ON `glpi_tickets`.id = `$table`.tickets_id";
      if ($CFG_BROWSER_NOTIF['ignore_deleted_items']) {
         $where[] = "`glpi_tickets`.is_deleted = 0"; //Not deleted
      }

      if ($seepublic && !$seeprivate) {
         $where[] = "`$table`.is_private = 0"; //Only Public
      } elseif (!$seepublic && $seeprivate) {
         $where[] = "`$table`.is_private = 1"; //Only Private
      } //else showall
      //
      //Only current and active entities
      $where[] = getEntitiesRestrictRequest("", 'glpi_tickets', '', $_SESSION['glpiactiveentities'], false, true);

      return self::executeQuery($table, 'begin', $last_timestamp, $select, $where, $join, $max_items);
   }

}
