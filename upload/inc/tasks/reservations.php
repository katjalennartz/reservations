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
    echo "test".$entry['member_lock'];
    $db->delete_query("reservationsentry", "type = '{$entry['type']}' and 4 < CURDATE()");
   }
}


//TODO Templates hinzufügen bzw. ändern 
//TODO löschen bei modcp programmieren 
//TODO Task hinzufügen