# last update       
10.05.2023      
- kleinere bugfixes
                
10.02.2023 - DB änderungen
- Kompatibilität mit Steckbriefplugin hinzugefügt
- optionales extrafield 
- alle einträge als mod als gelesen markieren
- einträge verstecken user (wieder zeigen und auflisten folgt mit dem nächsten update!)
- bugfix verlängerung
- **Unbedingt** Update script durchführen.
        
evt im template 'reservations_indexalert'  folgende Variablen hinzufügen:   
```{$reservations_indexmodnewentry} {$markallentrys} ```
  
  vor   
  ```<a href="misc.php?action=reservations">Zu allen Reservierungen</a>``` 


# Reservierungsplugin

Mit diesem Plugin können über das ACP verschiedene Reservierungen erstellt werden. Das heißt, du kannst einen Reservierungstyp für zum Beispiel Avatare, einen für Gesuche, einen für Positionen etc. anlegen.  

Für jeden Typen können verschiedene Einstellungsmöglichkeiten festgelegt werden.  

Es wird eine Seite generiert, auf der die verschiedenen Typen zu finden sind und User Reservierungen vornehmen können. Eigene Einträge können verlängert/gelöscht/editiert werden.  

Es kann eingestellt werden, ob die Reservierungen untereinander oder als Tabs dargestellt werden.

Moderatoren können auch abgelaufene Reservierungen einsehen und löschen.  

Moderatoren werden über einen Hinweis auf der Indexseite darüber informiert, wenn es einen neuen Eintrag gibt

Es gibt einen Task der die Tabelle regelmäßig selbstständig aufräumt und alte Einträge löscht.  


# Installation
**Wichtig:** der erweiterte Accountswitcher von Doylecc muss installiert sein.      
Dateien aus dem Upload Ordner hochladen.       
Plugin installieren.        
Berechtigungen setzen!      
admin/index.php?module=user-admin_permissions&action=edit&uid=0         

# To Do
1. Konfiguration -> Reservierungen. 
    Einen Listentyp erstellen.  
    Zum Beispiel Avatare. 
2. Einstellen welche Reservierung als Defaulttab angezeigt werden soll. 
    Einstellungen -> Reservierung. 
3. Listentypen sind erreichbar über misc.php?action=reservations.  

