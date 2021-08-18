<?php

/**
 * Reservierungen 1.0
 * https://lslv.de/risu
 * flexible Reservierung 
 * Je Reservierungstyp:
 * Was für Reservierungentypen können im ACP eingestellt werden
 * z.B Avatare, Positionen, etc.
 */

// error_reporting ( -1 );
// ini_set ( 'display_errors', true );

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function reservations_info()
{
  return array(
    "name" => "Reservierungsplugin von Risuena",
    "description" => "Automatische Reservierungen aller Art",
    "website" => "https://github.com/katjalennartz",
    "author" => "risuena",
    "authorsite" => "https://github.com/katjalennartz",
    "version" => "1.0",
    "compatibility" => "18*"
  );
}

function reservations_is_installed()
{
  global $db;
  if ($db->table_exists("reservations")) {
    return true;
  }
  return false;
}

function reservations_install()
{
  global $db;
  reservations_uninstall();

  //Create Databases
  // TYPES
  // ID | NAME | TYPE | GUEST | AUSWAHL | VERLÄNGERN GAST | VERLÄNGERN MEMBER | SPERRE | MAX

  //ENTRIES
  // ID | UID | USERNAME | TYPE | CONTENT | STARTDATE | LÄUFT AUS | LAST UPDATE  

  //EINSTELLUNGEN
  //EXTRA REITER AMDIN CP um Typen zu erstellen

  // -> Typ erstellen

  //TEMPLATES hinzufügen
}

function reservations_uninstall()
{
  //TABELLEN LÖSCHEN

  //EINSTELLUNGEN LÖSCHEN

  //TEMPLATES LÖSCHEN 
}

function reservations_activate()
{
  //VARIABLEN IN TEMPLATES EINFÜGEN
}

function reservations_deactivate()
{
  //VARIABLEN AUS TEMPLATES LÖSCHEN
}

/**
 * Verwaltung der Darstellung (Ausgabe der Liste)
 * und ermöglicht das Reservieren an sich 
 */
$plugins->add_hook("misc_start", "reservations_main");
function reservations_main()
{
  //Dynamisch ->
  //Wir müssen alle erstellten Listen bekommen
  //-> Type muss auch die adresse definieren (misc.php?action=reservation&type=typename)
  //if action = reservation
  // get input type 

  //Output Maintemplane

}
/**
 * Meldung auf Index wenn Reservierung abläuft.
 */
$plugins->add_hook('index_start', 'reservation_alert');
function reservations_alert()
{
}
