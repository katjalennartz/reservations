<?php
// error_reporting ( -1 );
// ini_set ( 'display_errors', true ); 
// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


//TODO ALLE EINSTELLUNGEN BERÜCKSICHTIGT?????
function reservations_info()
{
  global $lang;
  $lang->load('reservations');
  return array(
    "name" => $lang->reservations_name,
    "description" => $lang->reservations_descr,
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
  if ($db->table_exists("reservationstype")) {
    return true;
  }
  return false;
}

function reservations_install()
{
  global $db, $cache;
  //reste löschen wenn was schiefgegangen ist
  reservations_uninstall();

  // Erstellen der Tabellen
  // Die Typen und ihre Einstellungen
  $db->query("CREATE TABLE " . TABLE_PREFIX . "reservationstype (
    `type_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    `type` varchar(200) NOT NULL,
    `descr` VARCHAR(200) NOT NULL,
    `selections` varchar(500),
    `guest_view` tinyint(1) DEFAULT 1,
    `guest_duration` int(20) NOT NULL,
    `member_duration` int(20) NOT NULL,
    `member_lock` int(20) NOT NULL,
    `member_extend` int(20) NOT NULL,
    `member_extendtime` int(20) NOT NULL,
    `member_extendcnt` int(20) NOT NULL,
    `member_max` int(20) NOT NULL,
    `pfid` int(20) NOT NULL,
    PRIMARY KEY (`type_id`)
     ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");

  // Die Einträge selber
  $db->query("CREATE TABLE " . TABLE_PREFIX . "reservationsentry (
    `entry_id` int(11) NOT NULL AUTO_INCREMENT,
    `uid` int(11) NOT NULL,
    `name` varchar(200) NOT NULL,
    `type` varchar(200) NOT NULL,
    `content` varchar(500) NOT NULL,
    `selection` varchar(200) NOT NULL,
    `startdate` date NOT NULL,
    `enddate` date NOT NULL,
    `lastupdate` int(20) NOT NULL,
    `ext_cnt` int(20) NOT NULL DEFAULT '0',
    PRIMARY KEY (`entry_id`)
     ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");

  // Einstellungen
  $setting_group = array(
    'name' => 'reservations',
    'title' => 'Reservierungen',
    'description' => 'Einstellungen für die Reservierungen.',
    'disporder' => 8, // The order your setting group will display
    'isdefault' => 0
  );
  $gid = $db->insert_query("settinggroups", $setting_group);
  $setting_array = array(
    'reservations_days_reminder' => array(
      'title' => 'Indexanzeige',
      'description' => 'Wieviele Tage bevor ihre Reservierung ausläuft, sollen User darauf hingewiesen werden?',
      'optionscode' => 'numeric',
      'value' => '7', // Default
      'disporder' => 1
    ),

    'reservations_defaulttab' => array(
      'title' => 'Default tab',
      'description' => 'Welches Tab soll beim Aufrufen der Liste als erstes angezeigt werden? Den maschinenlesbaren Typ der Liste angeben.',
      'optionscode' => 'text',
      'value' => '', // Default
      'disporder' => 1
    )
  );

  foreach ($setting_array as $name => $setting) {
    $setting['name'] = $name;
    $setting['gid'] = $gid;
    $db->insert_query('settings', $setting);
  }
  rebuild_settings();

  //Templates erstellen
  // templategruppe
  $templategrouparray = array(
    'prefix' => 'reservations',
    'title'  => $db->escape_string('Reservierungen'),
    'isdefault' => 1
  );

  $db->insert_query("templategroups", $templategrouparray);

  //Templates erstellen
  $template[0] = array(
    "title" => 'reservations_add',
    "template" => '<form method="post" action="misc.php?action=reservations&type={$res_type}">				
    <div class="res_add">
    <h2>Reservieren</h2>
      <div class="res_add_select res_item">
        {$res_selects}
      </div>
      <div class="res_add_inputs res_item">
        {$res_inputs}
      </div>
      <div class="res_add_save res_item">
        <input type="hidden" value="{$res_type}" name="type_hid" />
        <input type="submit" value="speichern" name="res_save" />
      </div>
    </div>
  </form> 
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );


  $template[1] = array(
    "title" => 'reservations_bit',
    "template" => '<div class="res_bit">
	<h3>{$sel}</h3>
	{$reservations_bituser}
</div>
  ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[2] = array(
    "title" => 'reservations_bituser',
    "template" => '<div class="res_bit">
    <strong>{$entry[\\\'content\\\']}</strong> -  für {$name} bis {$enddate} {$edit} {$delete} {$extend}
    <div class="modal" id="edit_{$eid}" style="display: none; padding: 10px; margin: auto; text-align: center;">
      <form method="post" action="misc.php?action=reservations&type={$res_type}">
        {$res_selects_edit}
      <input type="hidden" name="edit" value="{$entry[\\\'entry_id\\\']}"/> 
      <br/>{$type[\\\'descr\\\']}: <input type="text" value="{$entry[\\\'content\\\']}" name="edit_content"/> <br />
      Name: <input type="text" value="{$entry[\\\'name\\\']}" name="edit_name"/><br />
      <input type="submit" value="speichern" name="edit_save" />
      </form>
    </div>
  </div>
  ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[3] = array(
    "title" => 'reservations_indexalert',
    "template" => '<div class="reservations_index">
    <strong>folgende Reservierungen laufen aus:</strong><br />
    {$reservations_indexuserbit} <br/>
    <a href="misc.php?action=reservations">Zu allen Reservierungen</a>
  </div>
  ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[4] = array(
    "title" => 'reservations_indexuserbit',
    "template" => '{$thisentry[\\\'type\\\']}:  {$thisentry[\\\'content\\\']} läuft am  {$thisentry[\\\'enddate\\\']} aus {$extend} <br/>
  ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );


  $template[5] = array(
    "title" => 'reservations_main',
    "template" => '
    <html>
	<head>
    <title>{$mybb->settings[\\\'bbname\\\']} - {$lang->lists}</title>
		{$headerinclude}
	</head>
	<body>
	{$header}
	<table width="100%" cellspacing="5" cellpadding="5">
	<tr>
	<td valign="top">
			<div class="res_tab">
				{$reservations_tabbit}

			</div>
		
			{$reservations_typ}


	</td>
	</tr>
	</table>
			<script>
		function openRestype(evt, restype) {
 			 // Declare all variables
  			var i, res_tabcontent, res_tablinks;

  			// Get all elements with class="tabcontent" and hide them
  			res_tabcontent = document.getElementsByClassName("res_tabcontent");
  			for (i = 0; i < res_tabcontent.length; i++) {
    			res_tabcontent[i].style.display = "none";
  			}

  			// Get all elements with class="tablinks" and remove the class "active"
 			res_tablinks = document.getElementsByClassName("res_tablinks");
 			for (i = 0; i < res_tablinks.length; i++) {
   				res_tablinks[i].className = res_tablinks[i].className.replace(" active", "");
			}

	 	 	// Show the current tab, and add an "active" class to the button that opened the tab
  			document.getElementById(restype).style.display = "block";
  			evt.currentTarget.className += " active";
		}

	</script>
<script>
// Get the element with id="defaultOpen" and click on it
document.getElementById("but_tabdefault").click();
</script>
	{$footer}

</body>
</html>
    
    
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[6] = array(
    "title" => 'reservations_tabbit',
    "template" => '<button class="res_tablinks" onclick="openRestype(event, \\\'tab_{$res_type}\\\')" id="{$tabbuttonid}">{$res_name}</button>
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[7] = array(
    "title" => 'reservations_typ',
    "template" => '<div class="reservierung_show res_tabcontent" id="tab_{$res_type}">
    <div class="res_ausgabe">
      <h1>{$res_name}</h1>
      {$reservations_bit}
    </div>
    {$reservations_add}
  </div>',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[8] = array(
    "title" => 'reservations_main_mod',
    "template" => '<div class="res_mod">
    <h1>Moderatoren Verwaltung - abgelaufene Reservierungen</h1>
  <p>Achtung, wenn ihr einen Eintrag löscht, kann er nicht mehr berücksichtig werden in Bezug auf den Sperrzeitraum. Also für den Fall, dass ein User die gleiche Reservierung einen bestimmten Zeitraum nicht noch einmal tätigen darf. Einträge sollten erst aus der Datenbank gelöscht werden, wenn dieser Zeitraum abgelaufen ist.<br /> 
    Es gibt einen Task der die Reservierungen automatisch aufräumt, das hier ist also nur für Ausnahmen nötig ;)  .<br /></p>
  <div class="res_mod_bit">
	{$reservations_main_modbit}
  </div>
  </div>',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  foreach ($template as $row) {
    $db->insert_query("templates", $row);
  }

  $css = array(
    'name' => 'reservations.css',
    'tid' => 1,
    'attachedto' => '',
    "stylesheet" =>    '
    /* Style the tab */
    .res_tab {
      overflow: hidden;
      border: 1px solid #b3b3b3;
      background-color: #fafafa;
    }
    
    .res_bit form {
      display: inline;
    }
    
    /* Style the buttons that are used to open the tab content */
    .res_tab button {
      background-color: #fafafa;
      background-image: none;
      border-radius: 0;
      float: left;
      border: none;
      outline: none;
      cursor: pointer;
      padding: 5px 10px;
      transition: 0.3s;
      font-size: 1.1em;
      font-weight: bold;
    }
    
    /* Change background color of buttons on hover */
    .res_tab button:hover {
      background-color: #ddd;
    }
    
    /* Create an active/current tablink class */
    .res_tab button.active {
      /* background-color: #dadbda; */
      border-bottom: 3px solid #3b3b3b;
      /* font-weight: 600; */
    }
    
    /* Style the tab content */
    .res_tabcontent {
      background: #e9e9e9;
      display: none;
      padding: 6px 12px;
      border: 1px solid #ccc;
      border-top: none;
      animation: fadeEffect 1s; /* Fading effect takes 1 second */
    }
    
    /* Go from zero to full opacity */
    @keyframes fadeEffect {
      from {opacity: 0;}
      to {opacity: 1;}
    }
    
    .res_ausgabe {
        display: flex;
        flex-wrap: wrap;
    }
    
    .res_ausgabe h1 {
        flex-basis: 100%;
        text-align: center;
    }
    
    .res_ausgabe .res_bit {
        flex-grow: 1;
    }
    .res_add {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
    }
    .res_add h2 {
      flex-basis: 100%;
      text-align:center;
    }
    
    .res_add_save {
        flex-basis: 100%;
        text-align: center;
    }

    .res_mod {
      background: #e9e9e9;
      padding: 6px 12px;
      border: 1px solid #ccc;
      border-top: none;
  }
    
    .res_add_inputs, .res_add_select {
        /* flex-grow: 1; */
        margin: 5px 25px;
    }
    
    .res_add_inputs {
        text-align: right;
    }    
  
    ',
    'cachefile' => $db->escape_string(str_replace('/', '', 'reservations.css')),
    'lastmodified' => time()
  );

  require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

  $sid = $db->insert_query("themestylesheets", $css);
  $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

  $tids = $db->simple_select("themes", "tid");
  while ($theme = $db->fetch_array($tids)) {
    update_theme_stylesheet_list($theme['tid']);
  }

  //TASK einfügen:

    $db->insert_query('tasks', array(
      'title' => 'Reservierungen',
      'description' => 'Räumt die Reservierungen auf und löscht abgelaufene, die auch keine Sperre mehr berücksichtigen müssen .',
      'file' => 'reservations',
      'minute' => '*',
      'hour' => '*',
      'day' => '*',
      'month' => '*',
      'weekday' => '*',
      'nextrun' => TIME_NOW,
      'lastrun' => 0,
      'enabled' => 1,
      'logging' => 1,
      'locked' => 0,
    ));
  
    $cache->update_tasks();
}

function reservations_uninstall()
{
  global $db;
  //Tabelle löschen wenn existiert
  if ($db->table_exists("reservationstype")) {
    $db->drop_table("reservationstype");
  }
  if ($db->table_exists("reservationsentry")) {
    $db->drop_table("reservationsentry");
  }
  //TEMPLATES LÖSCHEN 
  $db->delete_query("templates", "title LIKE 'reservations%'");
  $db->delete_query("templategroups", "prefix = 'reservations'");

  //CSS LÖSCHEN
  require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
  $db->delete_query("themestylesheets", "name = 'reservations.css'");
  $query = $db->simple_select("themes", "tid");
  while ($theme = $db->fetch_array($query)) {
    update_theme_stylesheet_list($theme['tid']);
  }
  //EINSTELLUNGEN LÖSCHEN
  $db->delete_query('settings', "name LIKE 'reservations%'");
  $db->delete_query('settinggroups', "name = 'reservations'");
  rebuild_settings();

//TASK LÖSCHEN
$db->delete_query('tasks', "file='reservations'");
$cache->update_tasks();
}

function reservations_activate()
{
  //VARIABLEN IN TEMPLATES EINFÜGEN
  include MYBB_ROOT . "/inc/adminfunctions_templates.php";
  find_replace_templatesets("index", "#" . preg_quote('{$header}') . "#i", '{$header}{$reservations_indexalert}');
}

function reservations_deactivate()
{
  //VARIABLEN AUS TEMPLATES LÖSCHEN
  include MYBB_ROOT . "/inc/adminfunctions_templates.php";
  find_replace_templatesets("index", "#" . preg_quote('{$reservations_indexalert}') . "#i", '');
}

/**
 * action handler fürs acp konfigurieren
 */
$plugins->add_hook("admin_config_action_handler", "reservations_admin_config_action_handler");
function reservations_admin_config_action_handler(&$actions)
{
  $actions['reservations'] = array('active' => 'reservations', 'file' => 'reservations');
}

/**
 * Berechtigungen im ACP
 */
$plugins->add_hook("admin_config_permissions", "reservations_admin_config_permissions");
function reservations_admin_config_permissions(&$admin_permissions)
{
  global $lang;
  $lang->load('reservations');

  $admin_permissions['reservations'] = $lang->reservations_permission;

  return $admin_permissions;
}
/**
 * Menü einfügen
 */
$plugins->add_hook("admin_config_menu", "reservations_admin_config_menu");
function reservations_admin_config_menu(&$sub_menu)
{
  global $mybb, $lang;
  $lang->load('reservations');

  $sub_menu[] = [
    "id" => "reservations",
    "title" => $lang->reservations_menu,
    "link" => "index.php?module=config-reservations"
  ];
}
/**
 * Verwaltung der Reservierungen im ACP
 * (Anlegen/Löschen/etc)
 */
$plugins->add_hook("admin_load", "reservations_admin_load");
function reservations_admin_load()
{
  global $mybb, $db, $lang, $page, $run_module, $action_file;
  //Sprachvariable laden
  $lang->load('reservations');

  if ($page->active_action != 'reservations') {
    return false;
  }
  //Übersicht
  if ($run_module == 'config' && $action_file == 'reservations') {
    // Reservierungverwaltung: 
    if ($mybb->input['action'] == "" || !isset($mybb->input['action'])) {
      //breadcrumb hinzufügen
      $page->add_breadcrumb_item($lang->reservations_menu);
      $page->output_header($lang->reservations_menu);

      //Untermenüs erstellen 
      $sub_tabs['reservations'] = [
        "title" => $lang->reservations_overview,
        "link" => "index.php?module=config-reservations",
        "description" => $lang->reservations_overview_descr
      ];
      $sub_tabs['reservations_list_add'] = [
        "title" => $lang->reservations_overview_typecreate,
        "link" => "index.php?module=config-reservations&amp;action=create_type",
        "description" => $lang->reservations_overview_typecreatedescr
      ];

      $page->output_nav_tabs($sub_tabs, 'reservations');

      //fehleranzeige
      if (isset($errors)) {
        $page->output_inline_error($errors);
      }

      //Hier erstellen wir jetzt unsere ganzen Felder
      //erst brauchen wir ein Formular
      $form = new Form("index.php?module=config-reservations", "post");
      //Container erstellen
      $form_container = new FormContainer($lang->reservations_overview);
      $form_container->output_row_header($lang->reservations_overview_options);
      //Überschrift
      $form_container->output_row_header("<div style=\"text-align: center;\">" . $lang->reservations_overview_options . "</div>");

      //Alle Einträge aus Reservierungstabelle bekommen um sie anzuzeigen, nach Namen sortiert
      $get_reservations = $db->simple_select("reservationstype", "*", "", ["order_by" => 'name']);
      //alle durchgehen und Spalte erstellen
      while ($reservations = $db->fetch_array($get_reservations)) {
        $form_container->output_cell('<strong>' . htmlspecialchars_uni($reservations['name']) . '</strong>');
        //menü für löschen & editieren
        $popup = new PopupMenu("reservations_{$reservations['type_id']}", $lang->reservations_edit_but);
        $popup->add_item(
          $lang->reservations_edit,
          "index.php?module=config-reservations&amp;action=reservations_edit&amp;tid={$reservations['type_id']}"
        );

        $popup->add_item(
          $lang->reservations_delete,
          "index.php?module=config-reservations&amp;action=reservations_delete&amp;tid={$reservations['type_id']}"
            . "&amp;my_post_key={$mybb->post_code}"
        );

        $form_container->output_cell($popup->fetch(), array("class" => "align_center"));
        $form_container->construct_row();
      }

      $form_container->end();
      $form->end();
      $page->output_footer();

      die();
    }
    //Einen Typen erstellen
    if ($mybb->input['action'] == "create_type") {
      if ($mybb->request_method == "post") {
        //sind alle nötigen felder ausgefüllt? Fehler abfangen
        if (empty($mybb->input['name'])) {
          $errors[] = $lang->reservations_error_name;
        }
        if (empty($mybb->input['type'])) {
          $errors[] = $lang->reservations_error_type;
        }
        if (empty($mybb->input['descr'])) {
          $errors[] = $lang->reservations_error_descr;
        }
        // if (empty($mybb->input['guest_view'])) {
        //   $errors[] = $lang->reservations_error_guestview;
        // }
        // if (empty($mybb->input['selections'])) {
        //   $errors[] = $lang->reservations_error_selections;
        // }
        // if (isset($mybb->input['guest_duration'])) {
        //   $errors[] = $lang->reservations_error_guestduration;
        // }
        // if (empty($mybb->input['member_lock'])) {
        //   $errors[] = $lang->reservations_error_memberlock;
        // }
        // if (empty($mybb->input['member_extend'])) {
        //   $errors[] = $lang->reservations_error_memberlock;
        // }
        // if (empty($mybb->input['member_extendtime'])) {
        //   $errors[] = $lang->reservations_member_extendtime;
        // }
        // if (empty($mybb->input['member_extendcnt'])) {
        //   $errors[] = $lang->reservations_member_extendcnt;
        // }
        // if (empty($mybb->input['member_max'])) {
        //   $errors[] = $lang->reservations_member_max;
        // }
        // if (empty($mybb->input['pfid'])) {
        //   $errors[] = $lang->reservations_error_pfid;
        // }

        //wenn alles passt eintragen
        if (empty($errors)) {
          $insert = [
            "name" => $db->escape_string($mybb->input['name']),
            "type" => $db->escape_string($mybb->input['type']),
            "descr" => $db->escape_string($mybb->input['descr']),
            "selections" => $db->escape_string($mybb->input['selections']),
            "guest_view" => intval($mybb->input['guest_view']),
            "guest_duration" => intval($mybb->input['guest_duration']),
            "member_duration" => intval($mybb->input['member_duration']),
            "member_lock" => intval($mybb->input['member_lock']),
            "member_extend" => intval($mybb->input['member_extend']),
            "member_extendtime" => intval($mybb->input['member_extendtime']),
            "member_extendcnt" => intval($mybb->input['member_extendcnt']),
            "member_max" => intval($mybb->input['member_max']),
            "pfid" => intval($mybb->input['pfid']),
          ];
          $db->insert_query("reservationstype", $insert);

          $mybb->input['module'] = "reservations";
          $mybb->input['action'] = $lang->reservations_success;
          log_admin_action(htmlspecialchars_uni($mybb->input['name']));

          flash_message($lang->reservations_success, 'success');
          admin_redirect("index.php?module=config-reservations");
        }
      }

      $page->add_breadcrumb_item($lang->reservations_overview_typecreate);

      // Navigation oben erstellen
      $page->output_header("Reservierung");
      $sub_tabs['reservations'] = [
        "title" => $lang->reservations_overview,
        "link" => "index.php?module=config-reservations",
        "description" => $lang->reservations_overview_descr
      ];
      $sub_tabs['reservations_reservations_add'] = [
        "title" => $lang->reservations_overview_typecreate,
        "link" => "index.php?module=config-reservations&amp;action=create_type",
        "description" => $lang->reservations_overview_typecreatedescr
      ];

      $page->output_nav_tabs($sub_tabs, 'reservations_reservations_add');

      // Fehler Zeigen, wenn es welche gibt
      if (isset($errors)) {
        $page->output_inline_error($errors);
      }

      //formular und felder erstellen für Admin cp 
      $form = new Form("index.php?module=config-reservations&amp;action=create_type", "post", "", 1);
      $form_container = new FormContainer($lang->reservations_overview_typecreate);

      $form_container->output_row(
        $lang->reservations_typecreate_name . "<em>*</em>", //name
        $lang->reservations_typecreate_name_descr,
        $form->generate_text_box('name', $mybb->input['name'])
      );

      $form_container->output_row(
        $lang->reservations_typecreate_type . "<em>*</em>", //typname maschinenlesbar
        $lang->reservations_typecreate_type_descr,
        $form->generate_text_box('type', $mybb->input['type'])
      );

      $form_container->output_row(
        $lang->reservations_typecreate_descr . "<em>*</em>", //beschreibung fürs input
        $lang->reservations_typecreate_descr_descr,
        $form->generate_text_box('descr', $mybb->input['descr'])
      );

      $form_container->output_row(
        $lang->reservations_typecreate_selections, //Was kann ausgewählt werden?
        $lang->reservations_typecreate_selections_descr,
        $form->generate_text_box('selections', $mybb->input['selections'])
      );

      $form_container->output_row(
        $lang->reservations_typecreate_guest_view, //dürfen gäste reservieren
        $lang->reservations_typecreate_guest_view_descr,
        $form->generate_yes_no_radio('guest_view', $mybb->get_input('guest_view'))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_guest_duration, //wie lange dürfen gäste reservieren
        $lang->reservations_typecreate_guest_duration_descr,
        $form->generate_numeric_field('guest_duration', $mybb->input['guest_duration'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_duration, //wie lange dürfen mitglieder reservieren
        $lang->reservations_typecreate_member_duration_descr,
        $form->generate_numeric_field('member_duration', $mybb->input['member_duration'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_lock, //zeitraum für sperre
        $lang->reservations_typecreate_member_lock_descr,
        $form->generate_numeric_field('member_lock', $mybb->input['member_lock'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_extend . "<em>*</em>", //dürfen mitglieder verländern
        $lang->reservations_typecreate_member_extend_descr,
        $form->generate_yes_no_radio('member_extend', $mybb->get_input('member_extend'))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_extendtime, //wie lange
        $lang->reservations_typecreate_member_extendtime_descr,
        $form->generate_numeric_field('member_extendtime', $mybb->input['member_extendtime'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_extendcnt . //Wie oft
          $lang->reservations_typecreate_member_extendcnt_descr,
        $form->generate_numeric_field('member_extendcnt', $mybb->input['member_extendcnt'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_max, //wieviele einträge maximal
        $lang->reservations_typecreate_member_max_descr,
        $form->generate_numeric_field('member_max', $mybb->input['member_max'], array('id' => 'disporder', 'min' => 0))
      );
      $form_container->output_row(
        $lang->reservations_typecreate_pfid . "<em>*</em>",
        $lang->reservations_typecreate_pfid_descr,
        $form->generate_numeric_field('pfid', $mybb->input['pfid'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->end();
      $buttons[] = $form->generate_submit_button($lang->reservations_overview_typecreate);
      $form->output_submit_wrapper($buttons);
      $form->end();
      $page->output_footer();
      die();
    }

    //Editieren 
    if ($mybb->input['action'] == "reservations_edit") {
      if ($mybb->request_method == "post") {
        //sind alle nötigen felder ausgefüllt? Fehler abfangen
        if (empty($mybb->input['name'])) {
          $errors[] = $lang->reservations_error_name;
        }
        if (empty($mybb->input['type'])) {
          $errors[] = $lang->reservations_error_type;
        }
        if (empty($mybb->input['descr'])) {
          $errors[] = $lang->reservations_error_descr;
        }
        // if (empty($mybb->input['guest_view'])) {
        //   $errors[] = $lang->reservations_error_guestview;
        // }
        // if (empty($mybb->input['selections'])) {
        //   $errors[] = $lang->reservations_error_selections;
        // }
        // if (isset($mybb->input['guest_duration'])) {
        //   $errors[] = $lang->reservations_error_guestduration;
        // }
        // if (empty($mybb->input['member_lock'])) {
        //   $errors[] = $lang->reservations_error_memberlock;
        // }
        // if (empty($mybb->input['member_extend'])) {
        //   $errors[] = $lang->reservations_error_memberlock;
        // }
        // if (empty($mybb->input['member_extendtime'])) {
        //   $errors[] = $lang->reservations_member_extendtime;
        // }
        // if (empty($mybb->input['member_extendcnt'])) {
        //   $errors[] = $lang->reservations_member_extendcnt;
        // }
        // if (empty($mybb->input['member_max'])) {
        //   $errors[] = $lang->reservations_member_max;
        // }
        // if (empty($mybb->input['pfid'])) {
        //   $errors[] = $lang->reservations_error_pfid;
        // }

        // Keine Felder, dann einfügen
        if (empty($errors)) {
          $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
          $oldtype = $mybb->get_input('oldtype', MyBB::INPUT_STRING);
          $update = [
            "name" => $db->escape_string($mybb->input['name']),
            "type" => $db->escape_string($mybb->input['type']),
            "descr" => $db->escape_string($mybb->input['descr']),
            "selections" => $db->escape_string($mybb->input['selections']),
            "guest_view" => intval($mybb->input['guest_view']),
            "guest_duration" => intval($mybb->input['guest_duration']),
            "member_duration" => intval($mybb->input['member_duration']),
            "member_lock" => intval($mybb->input['member_lock']),
            "member_extend" => intval($mybb->input['member_extend']),
            "member_extendtime" => intval($mybb->input['member_extendtime']),
            "member_extendcnt" => intval($mybb->input['member_extendcnt']),
            "member_max" => intval($mybb->input['member_max']),
            "pfid" => intval($mybb->input['pfid']),
          ];

          $db->update_query("reservationstype", $update, "type_id='{$tid}'");
          $update_entry = [
            "type" => $db->escape_string($mybb->input['type']),
          ];
          $db->update_query("reservationsentry", $update_entry, "type='{$oldtype}'");

          $mybb->input['module'] = "reservations";
          $mybb->input['action'] = $lang->reservations_success;
          log_admin_action(htmlspecialchars_uni($mybb->input['name']));

          flash_message($lang->reservations_success, 'success');
          admin_redirect("index.php?module=config-reservations");
        }
      }

      //Felder etc. zum editieren erstellen. 
      $page->add_breadcrumb_item($lang->reservations_edit);
      $page->output_header($lang->reservations_name);
      $sub_tabs['reservations'] = [
        "title" => $lang->reservations_overview_typeedit,
        "link" => "index.php?module=config-reservations",
        "description" => $lang->reservations_overview_typeeditdescr
      ];

      $page->output_nav_tabs($sub_tabs, 'reservations');
      if (isset($errors)) {
        $page->output_inline_error($errors);
      }

      $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
      $reservation = $db->simple_select("reservationstype", "*", "type_id={$tid}");
      $edit_res = $db->fetch_array($reservation);

      $form = new Form("index.php?module=config-reservations&amp;action=reservations_edit", "post", "", 1);
      echo $form->generate_hidden_field('tid', $tid);
      echo $form->generate_hidden_field('oldtype', htmlspecialchars_uni($edit_res['type']));

      $form_container = new FormContainer($lang->reservations_create_edit);
      $form_container->output_row(
        $lang->reservations_typecreate_name . "<em>*</em>", //name
        $lang->reservations_typecreate_name_descr,
        $form->generate_text_box('name', htmlspecialchars_uni($edit_res['name']))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_type . "<em>*</em>", //typname maschinenlesbar
        $lang->reservations_typecreate_type_descr,
        $form->generate_text_box('type', htmlspecialchars_uni($edit_res['type']))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_descr . "<em>*</em>", //beschreibung fürs input
        $lang->reservations_typecreate_descr_descr,
        $form->generate_text_box('descr', htmlspecialchars_uni($edit_res['descr']))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_selections, //Was kann ausgewählt werden?
        $lang->reservations_typecreate_selections_descr,
        $form->generate_text_box('selections', htmlspecialchars_uni($edit_res['selections']))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_guest_view, //dürfen gäste reservieren
        $lang->reservations_typecreate_guest_view_descr,
        $form->generate_yes_no_radio('guest_view', $edit_res['guest_view'])
      );

      $form_container->output_row(
        $lang->reservations_typecreate_guest_duration, //wie lange dürfen gäste reservieren
        $lang->reservations_typecreate_guest_duration_descr,
        $form->generate_numeric_field('guest_duration', $edit_res['guest_duration'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_duration, //wie lange dürfen mitglieder reservieren
        $lang->reservations_typecreate_member_duration_descr,
        $form->generate_numeric_field('member_duration', $edit_res['member_duration'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_lock, //zeitraum für sperre
        $lang->reservations_typecreate_member_lock_descr,
        $form->generate_numeric_field('member_lock', $edit_res['member_lock'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_extend . "<em>*</em>", //dürfen mitglieder verländern
        $lang->reservations_typecreate_member_extend_descr,
        $form->generate_yes_no_radio('member_extend', $edit_res['member_extend'])
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_extendtime, //wie lange
        $lang->reservations_typecreate_member_extendtime_descr,
        $form->generate_numeric_field('member_extendtime', $edit_res['member_extendtime'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_extendcnt . //Wie oft
          $lang->reservations_typecreate_member_extendcnt_descr,
        $form->generate_numeric_field('member_extendcnt', $edit_res['member_extendcnt'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_member_max, //wieviele einträge maximal
        $lang->reservations_typecreate_member_max_descr,
        $form->generate_numeric_field('member_max', $edit_res['member_max'], array('id' => 'disporder', 'min' => 0))
      );

      $form_container->output_row(
        $lang->reservations_typecreate_pfid . "<em>*</em>",
        $lang->reservations_typecreate_pfid_descr,
        $form->generate_numeric_field('pfid', $edit_res['pfid'], array('id' => 'disporder', 'min' => 0))
      );
      $form_container->end();
      $buttons[] = $form->generate_submit_button($lang->reservations_overview_typecreate_edit);
      $form->output_submit_wrapper($buttons);
      $form->end();
      $page->output_footer();

      die();
    }
    //Einträge löschen 
    if ($mybb->input['action'] == "reservations_delete") {
      $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
      $get_res = $db->simple_select("reservationstype", "*", "type_id={$tid}");
      $del_res = $db->fetch_array($get_res);

      if (empty($tid)) {
        flash_message($lang->reservations_error_delete, 'error');
        admin_redirect("index.php?module=config-reservations");
      }

      if (isset($mybb->input['no']) && $mybb->input['no']) {
        admin_redirect("index.php?module=config-reservations");
      }

      if (!verify_post_check($mybb->input['my_post_key'])) {
        flash_message($lang->invalid_post_verify_key2, 'error');
        admin_redirect("index.php?module=config-reservations");
      } else {
        if ($mybb->request_method == "post") {
          $typename = $db->fetch_field($db->simple_select("reservationstype", "type", "type_id='{$tid}'"), "type");
          $db->delete_query("reservationsentry", "type='{$typename}'");
          $db->delete_query("reservationstype", "type_id='{$tid}'");
          $mybb->input['module'] = "reservations";
          $mybb->input['action'] = $lang->reservations_delete;
          log_admin_action(htmlspecialchars_uni($del_res['name']));
          flash_message($lang->reservations_delete, 'success');
          admin_redirect("index.php?module=config-reservations");
        } else {
          $page->output_confirm_action(
            "index.php?module=config-reservations&amp;action=reservations_delete&amp;tid={$tid}",
            $lang->reservations_delete_ask
          );
        }
      }
      die();
    }
  }
}

/**
 * Verwaltung der Darstellung im Forum (Ausgabe der Liste)
 * und ermöglicht das Reservieren an sich 
 */
$plugins->add_hook("misc_start", "reservations_main");
function reservations_main()
{
  global $mybb, $db, $templates, $header, $footer, $theme, $headerinclude, $res_name, $reservations_main, $reservations_bituser;


  //Reservierungsseite
  if ($mybb->get_input('action', MyBB::INPUT_STRING) == "reservations") {
    $thisuser = $mybb->user['uid'];
    //welches tab soll Default zu sehen sein?
    $tabtoshow = $mybb->settings['reservations_defaulttab'];

    // $defaultTab = true;

    $get_types = $db->simple_select("reservationstype", "*");
    //Typen bekommen
    while ($type = $db->fetch_array($get_types)) {
      // welchen typ haben wir
      $res_type = $type['type'];
      //soll der tab default sein?
      if ($res_type ==  $tabtoshow) {
        $defaultTab = true;
      } else {
        $defaultTab = false;
      }

      if ($defaultTab) {
        $tabbuttonid = "but_tabdefault";
        $defaultTab = false;
      } else {
        $tabbuttonid = "but_" . $type['type'];
      }

      $res_name = $type['name'];
      //inputs erstellen zum reservieren
      $res_inputs =
        $type['descr'] . ': <input type="text" name="' . $res_type . '_con" /><br/>
        Spielername: <input type="text" name="name" /> ';

      //radiobuttons erstellen
      $selections = explode(",", $type['selections']);
      $res_selects = "";
      $reservations_bit = "";

      //Reservierungstypen nacheinander durchgehen, sortiert nach Auswahlmöglichkeiten
      foreach ($selections as $sel) {
        //input für radio button erstellen
        if ($sel != "") {
          $res_selects .= '<input type="radio" name="' . $res_type . '_sel" value="' . $sel . '"/> ' . $sel . '<br/>';
        }

        //ausgabe aufgetrennt nach selection
        $reservations_bituser = "";

        //die dazugehörigen einträge holen
        $get_entry = $db->simple_select("reservationsentry", "*", "type = '{$res_type}' AND trim(selection) = trim('{$sel}') AND enddate >= CURDATE()", array('order_by' => 'content'));
        while ($entry = $db->fetch_array($get_entry)) {
          // Variablen leeren.
          $delete =  "";
          $edit = "";
          $extend =  "";
          
          $eid = $entry['entry_id'];
          $uid = $entry['uid'];
          //edit/delete/verlängern erstellen
          if (($thisuser == $entry['uid']) || ($mybb->usergroup['canmodcp'] == 1)) {
            $delete =  "<a href=\"misc.php?action=reservations&delete=do&id={$eid}&uid={$uid}\" onClick=\"return confirm('Möchtest du den Eintrag wirklich löschen?');\">[-]</a>";
            $edit = "<a onclick=\"$('#edit_{$eid}').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== 'undefined' ? modal_zindex : 9999) }); return false;\" style=\"cursor: pointer;\">[e]</a>";
            $extend =  "<a href=\"misc.php?action=reservations&extend=do&id={$eid}&uid={$uid}&type={$res_type}\" onClick=\"return confirm('Möchtest du den Eintrag verlängern?');\">[+]</a>";

            $res_selects_edit = "";
            //Fürs edit radiobuttons
            foreach ($selections as $save) {
              //input für radio button
              if (trim($save) == trim($entry['selection'])) $check = "CHECKED";
              else $check = "";
              $res_selects_edit .= '<input type="radio" name="edit_sel" value="' . $save . '" ' . $check . '/> ' . $save;
            }
          }
          //userinfos bekommen, wenn kein Gast
          if ($entry['uid'] != 0) {
            $user = get_user($entry['uid']);
            $userlink =  "(".build_profile_link($user['username'], $entry['uid']).")";
          } else {
            $userlink ="";
          }
         
          $name = $entry['name'];

          $enddate =  date("d.m.Y", strtotime($entry['enddate']));

          eval("\$reservations_bituser .= \"" . $templates->get("reservations_bituser") . "\";");
        }
        eval("\$reservations_bit .= \"" . $templates->get("reservations_bit") . "\";");
      }

      // Template  mit formfeldern zum reserviere
      if ($thisuser == 0 && $type['guest_view'] == 0) {
        $reservations_add = "";
      } else {
        eval("\$reservations_add = \"" . $templates->get("reservations_add") . "\";");
      }

      eval("\$reservations_tabbit .= \"" . $templates->get("reservations_tabbit") . "\";");
      eval("\$reservations_typ .= \"" . $templates->get("reservations_typ") . "\";");
    }

    $reservations_bituser = "";
    
    /** **********************
     * Anzeige für Moderatoren 
     * ***********************/

    if ($mybb->usergroup['canmodcp'] == 1) {
      //Einträge 
      $get_entry = $db->simple_select("reservationsentry", "*", "enddate < CURDATE()", array('order_by' => 'type, content'));
      while ($entry = $db->fetch_array($get_entry)) {
        //wieviele tage muss der User warten, bis er wieder reservieren darf
        $lockdays = $db->fetch_field($db->simple_select("reservationstype", "member_lock", "type = '{$entry['type']}'"), "member_lock");
        //Das Enddatum bekommen
        $enddate =  date("d.m.Y", strtotime($entry['enddate'])); // enddate + frist;
        //umwandeln 
        $newdate = strtotime($enddate);
        //Sperrzeitraum dazurechnen
        $newdate = strtotime("+{$lockdays} day", $newdate);
        //Hier haben wir unser Datum, wann der User wieder darf
        $newdate = date('d.m.Y', $newdate);
          //userinfos bekommen
          if ($entry['uid'] != 0) {
            $user = get_user($entry['uid']);
            $userlink =  "(".build_profile_link($user['username'], $entry['uid']).")";
          } else {
            $userlink ="";
          }

       eval("\$reservations_main_modbit .= \"" . $templates->get("reservations_main_modbit") . "\";");
      }
      eval("\$reservations_main_mod = \"" . $templates->get("reservations_main_mod") . "\";");
    }

    //Eintrag speichern
    if (isset($mybb->input['res_save'])) {
      //infos bekommen
      $name = $mybb->get_input('name', MYBB::INPUT_STRING);
      $res_type = $mybb->get_input('type_hid', MyBB::INPUT_STRING);
      $content = $mybb->get_input("{$res_type}_con", MyBB::INPUT_STRING);

      //entsprechende infos zur liste bekommen
      $type_opt = $db->fetch_array($db->simple_select("reservationstype", "*", "type= '{$res_type}'"));

      //Es gab Auswahlmöglichkeiten, der User hat aber nichts ausgewählt
      if ($type_opt['selections'] != "" && $mybb->get_input("{$res_type}_sel", MyBB::INPUT_STRING) == "") {
        error("Du hast keine Auswahloption gewählt.", "Reservierung nicht möglich.");
        die();
      }
      //Prüfen ob der User reservieren darf
      $check = reservations_check($thisuser, $res_type, $content);

      // alles gut, der User darf. 
      if ($check[0]) {
        if ($thisuser == 0) {
          $duration = $type_opt['guest_duration'];
        } else {
          $duration = $type_opt['member_duration'];
        }
        //Enddatum berechnen
        $date = new DateTime("+" . $duration . " days");
        //wir brauchen das richtige format für die Datenbank
        $enddate =  $date->format("Y-m-d");

        $insert = array(
          "uid" => $thisuser,
          "name" => $name,
          "type" => $res_type,
          "content" => $content,
          "selection" => $mybb->get_input("{$res_type}_sel", MyBB::INPUT_STRING),
          "startdate" => date("Y-m-d"),
          "enddate" => $enddate,
          "lastupdate" => date("Y-m-d"),
        );
        //speichern
        $db->insert_query("reservationsentry", $insert);
        redirect("misc.php?action=reservations");
      } else {
        error($check[1], "Reservierung nicht möglich");
        die();
      }
    }

    //eintrag löschen
    if ($mybb->input['delete'] == "do") {
      $entry = $mybb->get_input('id', MyBB::INPUT_INT);
      $uid = $mybb->get_input('uid', MyBB::INPUT_INT);
      if ($mybb->user['uid'] != 0 && ($mybb->user['uid'] == $uid || $mybb->usergroup['canmodcp'] == 1)) {
        $db->delete_query('reservationsentry', "entry_id = {$entry}");
      }
    }

    //eintrag verlängern
    if ($mybb->input['extend'] == "do") {
      //daten aus dem link holen und direkt schauen, dass sie das richtige format haben
      $entryid = $mybb->get_input('id', MyBB::INPUT_INT);
      $uid = $mybb->get_input('uid', MyBB::INPUT_INT);
      $type = $mybb->get_input('type', MyBB::INPUT_STRING);
      //Eintrag holen
      $entry = $db->fetch_array($db->simple_select("reservationsentry", "*", "entry_id='{$entryid}'"));
      //Einstellungen des Typs holen
      $options = $db->fetch_array($db->simple_select("reservationstype", "*", "type='{$type}'"));
      $days = $options['member_extendtime'];
      $cnt = $options['member_extendcnt'];
      //neues enddatum berechnen
      $date = date("Y-m-d", strtotime($entry['enddate']));
      $enddate =  date("Y-m-d", strtotime($date . " + {$days} days"));

      // darf der user verlängern? 
      if ($mybb->user['uid'] != 0 && ($mybb->user['uid'] == $uid || $mybb->usergroup['canmodcp'] == 1)) {
        if ($entry['ext_cnt'] >= $cnt) {
          //fehler
          error("Du hast diese Reservierung schon zu häufig verlängert.", "Reservierung nicht möglich.");
        } else {
          //counter hochzählen
          $extcounter = $entry['ext_cnt'] + 1;
          $update = array(
            "enddate" => $enddate,
            "lastupdate" => date("Y-m-d"),
            "ext_cnt" => $extcounter,
          );
          $db->update_query("reservationsentry", $update, "entry_id = {$entryid}");
          redirect("misc.php?action=reservations");
        }
      }
    }

    //Editieren
    if (isset($mybb->input['edit_save'])) {
      $entry = $mybb->get_input('edit', MyBB::INPUT_INT);
      $content = $mybb->get_input('edit_content', MyBB::INPUT_STRING);
      $selection = $mybb->get_input('edit_sel', MyBB::INPUT_STRING);

      $update = array(
        "name" => $name,
        "content" => $content,
        "selection" => $selection,
      );
      //darf er? 
      if ($mybb->user['uid'] != 0 && ($mybb->user['uid'] == $uid || $mybb->usergroup['canmodcp'] == 1)) {
        $db->update_query("reservationsentry", $update, "entry_id = {$entry}");
        redirect("misc.php?action=reservations");
      }
    }

    eval("\$reservations_main = \"" . $templates->get("reservations_main") . "\";");
    output_page($reservations_main);
    die();
  }
}

/**
 * Meldung auf Index wenn Reservierung abläuft.
 */
$plugins->add_hook('index_start', 'reservations_alert');
function reservations_alert()
{
  global $templates, $db, $mybb, $reservations_indexalert;
  // Reservierung läuft ab
  // Erst einmal gucken, ob es überhaupt schon Listen/Einträge gibt

  //Einstellunge bekommen
  $days = $mybb->settings['reservations_days_reminder'];
  //abfangen wenn es noch keine einstellung gibt
  if ($days == "") {
    $days = "0";
  }
  $thisuser = $mybb->user['uid'];
  $charas = reserverations_get_allchars($thisuser);
  $charastring = implode(",", array_keys($charas));
  $entry = $db->write_query("SELECT * FROM " . TABLE_PREFIX . "reservationsentry WHERE uid IN ({$charastring}) AND DATEDIFF(enddate, CURDATE()) >= {$days} ORDER BY uid, type");

  while ($thisentry = $db->fetch_array($entry)) {
    $eid = $thisentry['entry_id'];
    $uid = $thisentry['uid'];
    $res_type = $thisentry['type'];
    $extend =  "<a href=\"misc.php?action=reservations&extend=do&id={$eid}&uid={$uid}&type={$res_type}\" onClick=\"return confirm('Möchtest du den Eintrag verlängern?');\">[verlängern]</a>";

    $thisentry['enddate'] =  date("d.m.Y", strtotime($thisentry['enddate']));
    eval("\$reservations_indexuserbit .= \"" . $templates->get("reservations_indexuserbit") . "\";");
  }
  if ($db->num_rows($entry) > 0) {

    eval("\$reservations_indexalert = \"" . $templates->get("reservations_indexalert") . "\";");
  }
}

/**
 * Diese Funktion überprüft ob der User eintragen/Verlängern darf oder nicht.
 * Gibt ein Array zurück, welches die eventuelle Fehlermeldung enthält. 
 */
function reservations_check($thisuser, $res_type, $content)
{
  global $db;
  $check = array();
  $type_opt = $db->fetch_array($db->simple_select("reservationstype", "*", "type= '{$res_type}'"));
  $type_lock = $type_opt['member_lock'];
  $type_max = $type_opt['member_max'];
  $opt_ext_max = $type_opt['member_extendcnt'];

  $fid = $type_opt['pfid'];
  $check[0] = true;

  //schon reserviert? 
  $entry = $db->simple_select("reservationsentry", "*", "trim(lower(content)) like trim(lower('{$content}' AND enddate >= CURDATE()))");
  if ($db->num_rows($entry) > 0) {
    $check[0] = false;
    $check[1] = "Es gibt schon eine Reservierung mit diesen Eintrag.";
    return $check;
  }

  //hat irgendeinuser das im profilfeld eingetragen? 
  if ($fid != 0) {
    $testfid = $db->simple_select("userfields", "*", "trim(lower(fid{$fid})) like trim(lower('{$content}'))");
    if ($db->num_rows($testfid) > 0) {
      $check[0] = false;
      $check[1] = "Es gibt schon einen solchen Eintrag von einem der Mitglieder.";
      return $check;
    }
  }

  $countentrys = 0;
  if ($thisuser != 0) {
    $charas = reserverations_get_allchars($thisuser);
    //wir wollen alle Accounts des Users überprüfen
    foreach ($charas as $uid => $username) {
      $cnt = $db->fetch_field($db->simple_select("reservationsentry", "count(*) as cnt", "uid = {$uid} and type = '{$res_type}'"), "cnt");
      $countentrys += $cnt;
    }
    //Wir testen wie oft der Account reserviert hat
    if ($countentrys >= $type_max && $type_max != 0) {
      $check[0] = false;
      $check[1] = "Du hast schon die maximale Zahl der Reservierungen erreicht. Du hast gerade " . $db->num_rows($countentrys) . " Reservierungen. Erlaubt sind: " . $type_max;
      return $check;
    }
    if ($type_lock != 0) {
      //Wir wollen testen ob der Charakter einen Eintrag erneut reinstellen kann.
      foreach ($charas as $uid => $username) {
        $entry = $db->simple_select("reservationsentry", "*", "uid = {$uid} and type = '{$res_type}' and trim(lower(content)) like trim(lower('{$content}'))");
        if ($db->num_rows($entry) > 0) {
          while ($thisentry = $db->fetch_array($entry)) {
            $enddate = $thisentry['enddate'];
            //Heute minus dem Zeitraum, in der der User für erneuten Eintrag gesperrt ist
            $date = new DateTime("-" . $type_lock . " days");
            $checkdate = $date->format("Y-m-d");
            //Vergleichen mit dem Enddatum des eingetragenen Eintrags
            if ($enddate > $checkdate) {
              $check[0] = false;
              $check[1] = "Du hast diese Reservierung schon einmal getätigt und der Zeitraum bis du sie erneut vornehmen kannst ist noch nicht verstrichen.";
              return $check;
            }
          }
        }
        // member_extendcnt
        $summe = $db->fetch_field($db->simple_select("reservationsentry", "sum(ext_cnt) as sum", "uid = {$uid} and type = '{$res_type}'"), "sum");
        if ($summe >= $opt_ext_max) {
          $check[0] = false;
          $check[1] = "Du hast das erlaubte Maximum der Reservierungen dieser Art erreicht.";
          return $check;
        }
      }
    }
  }

  return $check;
}

/*#######################################
#Hilfsfunktion für Mehrfachcharaktere (accountswitcher)
#Alle angehangenen Charas holen
#an die Funktion übergeben: Wer ist Online, die dazugehörige accountswitcher ID (ID des Hauptcharas) 
######################################*/
function reserverations_get_allchars($thisuser)
{
  global $mybb, $db;
  //wir brauchen die id des Hauptcharas
  $as_uid = $mybb->user['as_uid'];
  $charas = array();
  if ($as_uid == 0) {
    // as_uid = 0 wenn hauptaccount oder keiner angehangen
    $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $thisuser) OR (uid = $thisuser) ORDER BY username");
  } else if ($as_uid != 0) {
    //id des users holen wo alle an gehangen sind 
    $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $thisuser) OR (uid = $as_uid) ORDER BY username");
  }
  while ($users = $db->fetch_array($get_all_users)) {

    $uid = $users['uid'];
    $charas[$uid] = $users['username'];
  }
  return $charas;
}
