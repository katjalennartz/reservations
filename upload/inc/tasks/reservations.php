<?php

/**
 * Reservations plugin for MyBB 1.8
 * Automatische Aktualisierung der Reservierungen
 *
 */


/***
 * all the magic 
 * 
 */
function task_reservations($task)
{
  global $db, $mybb, $lang;
  //alle typen bekommen
  $get_type = $db->simple_select("reservationstype", "*");
  //für jeden typen nacheinander schauen
  while ($entry = $db->fetch_array($get_type)) {
    //jetzt die abgelaufenen löschen
    $db->delete_query("reservationsentry", "type = '{$entry['type']}' and DATE_ADD(enddate, INTERVAL {$entry['member_lock']} DAY) < CURDATE()");
    $db->delete_query("reservationsmodread", "entry_id NOT IN (SELECT entry_id FROM ".TABLE_PREFIX."reservationsentry");
  }
  add_task_log($task, "Reservierungen bereinigt");

}
