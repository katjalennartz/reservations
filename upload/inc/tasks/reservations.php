<?php

/**
 * blacklistAlert.php
 *
 * Blacklist plugin for MyBB 1.8
 * Automatische Anzeige der BL 
 * Aktualisierung des Feldes, BL Warnung ausblenden 
 *
 */
error_reporting(-1);
ini_set('display_errors', true);


/***
 * all the magic 
 * 
 */
function task_reservations($task)
{
  global $db, $mybb, $lang;

  $get_type = $db->simple_select("reservationstype", "*");
  while ($entry = $db->fetch_array($get_type)) {
    // $db->write_query("SELECT * FROM mybb_reservationsentry WHERE type = '{$entry['type']}' and DATE_ADD(enddate, INTERVAL {$entry['member_lock']} DAY) < CURDATE()");
    $db->delete_query("reservationsentry", "type = '{$entry['type']}' and DATE_ADD(enddate, INTERVAL {$entry['member_lock']} DAY) < CURDATE()");
    $db->delete_query("reservationsmodread", "entry_id NOT IN (SELECT entry_id FROM ".TABLE_PREFIX."reservationsentry");

    // SELECT * FROM `mybb_reservationsmodread` WHERE entry_id NOT IN (SELECT entry_id FROM mybb_reservationsentry)
  }
  add_task_log($task, "Reservierungen bereinigt");

}


// global $db, $mybb, $lang;
// $get_type = $db->simple_select("reservationstype", "*");
// while ($entry = $db->fetch_array($get_type)) {
//   echo "test".$entry['member_lock'];
//   $db->delete_query("reservationsentry", "type = '{$entry['type']}' and DATE_ADD(enddate, INTERVAL {$entry['member_lock']} DAY) < CURDATE()");
//  }

// SELECT entry_id, startdate, enddate, DATE_ADD(enddate, INTERVAL 7 DAY)as test FROM mybb_reservationsentry WHERE DATE_ADD(enddate, INTERVAL 7 DAY) < CURDATE()
// }