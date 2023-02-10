<?php
define("IN_MYBB", 1);
require("global.php");
// error_reporting(E_ERROR | E_PARSE);
// ini_set('display_errors', true);

global $db, $mybb, $lang;

if (!$db->field_exists("checkfield_typ", "reservationstype")) {
  $db->add_column("reservationstype", "checkfield_typ", "VARCHAR(200) NOT NULL");
  echo "checkfield_typ zu reservationstype table hinzugefügt.<br>";
}
if (!$db->field_exists("pfid", "reservationstype")) {
  $db->add_column("reservationstype", "pfid", "VARCHAR(200) NOT NULL");
    echo "pfid in reservationstype hinzugefügt<br>";
} else  {
  $db->write_query("ALTER TABLE `mybb_reservationstype` CHANGE `pfid` `pfid` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
  echo "pfid in reservationstype zu geändert zu varchar .<br>";
}
if (!$db->field_exists("extra", "reservationstype")) {
  $db->add_column("extra", "checkfield_typ", "VARCHAR(500) NOT NULL");
  echo "extra zu reservationstype table hinzugefügt.<br>";
}
if (!$db->field_exists("showindex", "reservationsentry")) {
  $db->add_column("reservationsentry", "showindex", "int(1) NOT NULL DEFAULT 1");
  echo "showindex hinzugefügt.
  <br>
  <b style='color: red;'>Im reservations_bituser template noch manuell die variable 'extra' hinzufügen (z.b. hinter extend),
  <br>
  im reservations_indexalert markallentrys am besten vor dem link zu allen reservierungen<br>
   (mit geschweiften klammern und Dollarzeichen)</a></b><br>
  Datei jetzt löschen!";
}

if ($db->field_exists("checkfield_typ", "reservationstype")) {
  echo "Die Felder existieren schon, bitte die Datei löschen";
}