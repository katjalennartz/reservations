# Reservierungsplugin
Mit diesem Plugin können über das ACP verschiedene Reservierungen erstellt werden. Das heißt, du kannst einen Reservierungstyp für zum Beispiel Avatare, einen für Gesuche, einen für Positionen etc. anlegen.  

Für jeden Typen können verschiedene Einstellungsmöglichkeiten festgelegt werden.  

Es wird eine Seite generiert, auf der die verschiedenen Typen zu finden sind und User Reservierungen vornehmen können. Eigene Einträge können verlängert/gelöscht/editiert werden.  

Moderatoren können auch abgelaufene Reservierungen einsehen und löschen.  

Es gibt einen Task der die Tabelle regelmäßig selbstständig aufräumt und alte Einträge löscht.  


# Installation
Dateien aus dem Upload Ordner hochladen.  
Plugin installieren. 


# To Do
1. Konfiguration -> Reservierungen. 
    Einen Listentyp erstellen.  
    Zum Beispiel Avatare. 
2. Einstellen welche Reservierung als Defaulttab angezeigt werden soll. 
    Einstellungen -> Reservierung. 
3. Listentypen sind erreichbar über misc.php?action=reservations.  
    -> Konfiguration -> Reservierungen. 

# Erweitert 
Wenn ihr wollt, dasss nur Moderatoren in ihren Alertsettings die Einstellungen sehen können, ob sie einen Alert für neue Reservierungen bekommen oder nicht, könnt ihr folgende Änderung händisch durchführen:

/alerts.php
suche nach:  
				`eval("\$alertSettings .= \"" . $templates->get(
						'myalerts_setting_row'
					) . "\";");`
                    
ersetzen mit:  
			`	if ($key == 'reservations_newEntry' && !is_member(4, $mybb->user['uid'])) {
					$alertSettings .= "";
				} else {
					eval("\$alertSettings .= \"" . $templates->get(
						'myalerts_setting_row'
					) . "\";");
				}
                `
		  
wobei 4 hier die Administratorgruppe ist, evt. müsst ihr das für eure Moderatoren anpassen. 
