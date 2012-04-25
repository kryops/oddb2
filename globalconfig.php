<?php

/**
 * globalconfig.php
 * - globale Einstellungen
 * - Einstellungen über verschiedene Instanzen der DB,
 *   falls mehrere Allianzen/Metas eine DB benutzen
 * - Basis-Einstellungen und Basis-Rechte für alle Instanzen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

define('INSTALLED', true);

// Konstanten
if(!defined('ADDR')) {
	define('ADDR', 'http://localhost/oddbr9/');	// Adresse der DB (mit abschließendem /)
	define('SERVER', 'localhost');				// Server der DB (ohne http://, ohne abschließendes /)
	define('KEY', '1272fba2332d3fb6');		// globaler Sicherheitsschlüssel (Cronjobs)
	define('IMPRESSUM', '');
	define('ADCODE', ''); // optionaler Werbecode
}

// globale Einstellungen
$gconfig = array(
	'odrequest' => 72,		// nach wie viel Stunden muss ein Spieler-Profil aktualisiert werden? (0 für nie)
	'odrequest_max' => 10,	// wie viele Spielerprofile sollen pro Minute  maximal aktualisiert werden?
	'odrequest_mintime' => 15,	// wie viele Minuten muss zwischen 2 gleichen odrequests gewartet werden? (Login und Sperre)
	'odrequest_mintime2' => 240,	// wie viele Minuten muss  zwischen 2 gleichen odrequests gewartet werden? (normale Requests)
);


/**
 * $dbs array / false - Instanzen der DB
 * 1 Instanz - false (Konfigurationsdatei: config/config1.php)
 * mehrere Instanzen - Key=>Name der Instanz (Konfigurationsdatei: config/config[Key].php)
 */
/***_dbs_***/

$dbs = false;

/***_/dbs_***/



/**
 * Einstellungen
 */
$bconfig = array(
	// general
	'key' => '94d62f53164e2cde', 		// instanzbasierter Sicherheitsschlüssel
	'active' => true,					// bool DB aktivieren/deaktivieren
	'offlinemsg' => '',  				// Nachricht bei deaktivierter DB
	
	// MySQL
	'mysql_host' => '127.0.0.1',		// Hostname/IP des MySQL-Servers
	'mysql_user' => 'root',				// MySQL-Benutzername
	'mysql_pw' => '',					// MySQL-Passwort
	'mysql_db' => 'oddbr9',				// Name der MySQL-Datenbank
	'mysql_globprefix' => 'oddb_',		// Präfix vor den globalen Tabellen
	'mysql_prefix' => '',				// Präfix vor den Instanz-Tabellen
	
	// Caching
	'caching' => 1,						// 0 - deaktiviert, 1 - APC, 2 - memcached -> verbessert die Performance
	'caching_prefix' => 'oddb',
	'memcached_host' => 'localhost',	// Hostname/IP des Memcached-Servers
	'memcached_port' => 11211,			// Port des Memcached-Servers
	
	// Sicherheit
	'ipban' => 10,						// nach wie vielen Fehlversuchen soll eine IP gesperrt werden? (0 für nie)
	'ipban_time' => 15,					// wie viele Minuten lang soll eine IP gebannt bleiben?
	
	'flooding' => false,				// bool Flooding-Schutz aktivieren (max x Seitenaufrufe innerhalb von y Sekunden)
	'flooding_time' => 10,				// innerhalb wie vieler Sekunden soll gemessen werden?
	'flooding_pages' => 30,				// wie viele Seiten darf ein User innerhalb dieser Zeit aufrufen?
	
	// Freischaltung
	'disable_freischaltung' => false,	// neu registrierte Accounts automatisch freischalten?
	'disable_freischaltung_level' => 1,	// wenn die Freischaltung deaktiviert ist, werden alle User mit diesem Level erstellt
	
	// Logging
	/*
		0 - kein Logging
		1 - nur Verwaltungsaktionen werden geloggt (User, Allianzen, Registrierung, Galaxien)
		2 - [vorsichtig] alle Aktionen werden geloggt
		3 - [paranoid] zusätzlich wird alles geloggt, was sich welcher User ansieht
			(nur dynamische Seiten wie Invasionen und Spielerlisten)
	*/
	'logging' => 2,						// Logging-Level
	'logging_time' => 3,				// nach wie vielen Tagen sollen Einträge aus dem Logfile gelöscht werden?
	
	// Scans
	'scan_veraltet' => 21,				// nach wie vielen Tagen gelten Scans als veraltet? (Systeme und Planeten)
	'scan_veraltet_ally' => 14,			// nach wie vielen Tagen gelten Ally- und eigene Scans als veraltet? (Systeme und Planeten)
	'scan_veraltet_oview' => 12,	// nach wie vielen Stunden sind Planetenübersicht-Scans veraltet?
	'scan_veraltet_einst' => 7,			// nach wie vielen Tagen ist der Scan der eigenen Einstellungen und der Sitter veraltet?
	'scan_veraltet_flotten' => 7,		// nach wie vielen Tagen ist der Scan der eigenen Flottenübersicht veraltet?
	'scan_veraltet_geld' => 7,			// nach wie vielen Tagen ist der Scan der eigenen Einnahmen veraltet?
	
	// Invasionen
	'invasionen_update' => 300,			// nach wie vielen Sekunden soll die anzeige der offenen Invasionen aktualisiert werden?
	
	// Spprunggeneratoren
	'sprunggenerator_del' => 28			// nach wie vielen Tagen sollen Sprunggeneratoren automatisch gelöscht werden? (0 für nie)
);


// Default-Settings für neue Accounts
/**
 *	$bsettings:
 *	
 *	antrieb int Antrieb
 *	szgr bool Schnellzugriffsleiste per default einblenden
 *	szgrtype int Schnellzugriffsleisten-Typ (1 aktiver Tab, 2 neuer Tab, 3 DB-Fenster)
 *	szgrwildcard bool Szgr - bei nicht IDs automatisch Wildcards um den String schließen
 *	wminoncontent bool Fenster minimieren, wenn was mit den Tabs passiert
 *	newtabswitch bool direkt in einen neuen Tab wechseln, wenn er erstellt wird
 *	winlinknew bool Fensterlinks innerhalb von Fenstern in einem neuen Tab/Fenster öffnen
 *	winlink2tab bool Fensterlinks wie Tab-Links behandeln
 *	closeontransfer bool Fenster oder Tab schließen, wenn Inhalt in jeweils anderes Medium transferiert wird
 *	suchspalten string welche Spalten sollen standardmäßig bei der Suche angezeigt werden?
 *	fow array (serialisiert) FoW-Settings
 *	
 *	
 *	$bfowsettings:
 *	
 *	gate bool Gate anzeigen
 *	mgate bool nächstes Freund-Myrigate anzeigen
 *	scan bool nicht erfasste Systeme und veraltet Allysysteme anzeigen
 *	scout int Scoutziel älter als ~ Tage anzeigen
 *	next int nächste Systeme anzeigen
 *	udef array benutzerdefinierter Bereich
 */
$bsettings = array(
	'antrieb'=>32,
	'szgr'=>true,
	'szgrtype'=>5,
	'szgrwildcard'=>true,
	'wminoncontent'=>true,
	'newtabswitch'=>false,
	'winlinknew'=>true,
	'winlink2tab'=>false,
	'closeontransfer'=>true,
	'suchspalten'=>'1-2-3-4-5-6-8-9-10-11-12-13-14-15'
);

$bfowsettings = array(
	'gate'=>true,
	'mgate'=>true,
	'scan'=>true,
	'scout'=>$bconfig['scan_veraltet']
);



// Rechteverteilung
/*
	Rechtelevel dürfen nur ganze Zahlen sein
	die Rechte sollten mit der Zahl zunehmen
*/
$brechte = array(
	// Level 0: eingeschränkt
	0 => array(
		// generell
		'name'=>'eingeschränkt', 					// Name des Rechtelevels
		'desc'=>'Sichtbarkeit der Allianz, Meta und anderer angemeldeter Allianzen weitgehend eingeschränkt',		// Beschreibung
		
		// Einscannen
		'scan'=>true,						// Quelltexte einscannen
		'scan_del'=>false,					// durch die Scans dürfen Planeten und Systeme gelöscht werden
											// (z.B. wenn sich ein User löscht; potentielle Sicherheitslücke)
		
		// Planetenanzeige
		// eigene Planeten können immer angezeigt werden, wenn show_planet true ist
		// wirkt sich auf alle Bereiche der DB aus
		'show_planet'=>true,				// darf der User überhaupt Planeten anzeigen?
		'show_planet_ally'=>false,			// Planeten der Allianz anzeigen
		'show_planet_meta'=>false,			// Planeten der Meta anzeigen
		'show_planet_register'=>false,		// Planeten von anderen registrierten Allianzen anzeigen
		
		// Systemanzeige
		// die Optionen gelten auch für den FoW
		// ist die Option show_system_ally aktiv, können auch alle anderen Systeme angezeigt werden, die Allianzplaneten enthalten
		// bei Systemen ohne Allianzplaneten treten die Regeln für Meta- und andere registrierte Systeme ein
		'show_system'=>true,				// darf der User überhaupt Systeme anzeigen?
		'show_system_ally'=>false,			// Systeme der Allianz anzeigen
		'show_system_meta'=>false,			// Systeme der Meta anzeigen
		'show_system_register'=>false,		// Systeme von anderen registrierten Allianzen anzeigen
		
		'show_player'=>true,				// Spieler anzeigen
		// die folgenden Einstellungen haben auch Einfluss auf die Liste angemeldeter Spieler
		'show_player_db_ally'=>false,		// DB-Daten angemeldeter Spieler der Allianz anzeigen (Sitter...)
		'show_player_db_meta'=>false,		// DB-Daten angemeldeter Spieler der Meta anzeigen
		'show_player_db_other'=>false,		// DB-Daten angemeldeter Spieler anderer Allianzen anzeigen
		
		'show_ally'=>true,					// Allianzen anzeigen
		'show_meta'=>true,					// Metas anzeigen
		
		// Sichtbarkeit und Editierbarkeit der Einteilung eines Planeten
		// die Einteilung eigener Planeten kann immer vollständig gesehen und bearbeitet werden
		'ressplani_ally'=>false,				// Ressplaneten der Ally anzeigen
		'ressplani_meta'=>false,				// Ressplaneten der Meta anzeigen
		'ressplani_register'=>false,			// Ressplaneten anderer angemeldeter Allianzen anzeigen
		'ressplani_feind'=>true,			// kürzlich gescannte feindliche Ressplaneten anzeigen
		
		'bunker_ally'=>false,				// Bunker der Ally anzeigen
		'bunker_meta'=>false,				// Bunker der Meta anzeigen
		'bunker_register'=>false,			// Bunker anderer angemeldeter Allianzen anzeigen
		
		'werft_ally'=>false,					// Werften der Ally anzeigen
		'werft_meta'=>false,					// Werften der Meta anzeigen
		'werft_register'=>false,				// Werften anderer angemeldeter Allianzen anzeigen
		
		'flags_edit_ally'=>false,			// Ressplanet, Bunker und Werft bei Allyplaneten ändern
		'flags_edit_meta'=>false,			// Ressplanet, Bunker und Werft bei Metaplaneten ändern
		'flags_edit_register'=>false,		// Ressplanet, Bunker und Werft bei Planeten anderer angemeldeter Allianzen ändern
		'flags_edit_other'=>false,			// Ressplanet, Bunker und Werft bei Planeten nicht angemeldeter Allianzen ändern
		
		// Myrigates und Risse
		// wirkt sich auf alle Bereiche der DB aus
		'show_myrigates'=>true,				// Myrigates anzeigen
		'show_myrigates_ally'=>false,		// Myrigates der Allianz anzeigen
		'show_myrigates_meta'=>false,		// Myrigates der Meta anzeigen
		'show_myrigates_register'=>false,	// Myrigates anderer registrierter Allianzen anzeigen
		
		// FoW
		'fow'=>true,						// FoW-Ausgleich benutzen
		
		// Suche
		// wirkt sich auch auf die Anzeige der nächsten/entferntesten Planeten aus (Streckenberechnung und FoW)
		'search'=>true,						// Suchfunktionen benutzen
		'search_ally'=>false,				// Planeten der eigenen Allianz finden
		'search_meta'=>false,				// Planeten der eigenen Meta finden
		'search_register'=>false,			// Planeten anderer registrierter Allianzen finden
		
		// Scouten
		'scout'=>true,						// Scout-Funktionen benutzen
		
		// Strecken
		'strecken_flug'=>true,				// Entfernungs- und Flugberechnung
		'strecken_weg'=>false,				// schnellster Weg-Funktion
		'strecken_saveroute'=>false,			// Saverouten-Generator
		'strecken_ueberflug'=>false,			// System-Überflug
		
		'toxxraid'=>true,					// Toxx- und Raid-Funktion benutzen
		
		// Routen
		'routen'=>true,						// Routen-Funktion benutzen
											// eigene Routen und Listen erstellen
		'routen_ally'=>false,				// Routen der Allianz sehen
		'routen_meta'=>false,				// Routen der Meta sehen
		'routen_other'=>false,				// Routen anderer angemeldeter Allianzen sehen
		
		'karte'=>true,						// Karte benutzen
		
		'gates'=>true,						// Gate- und Myrigateliste einsehen
		
		// Invasionen
		'invasionen'=>true,					// Invasionen, Resonationen, Genesis-Projekte und Besatzungen sehen
		'fremdinvakolos'=>false,			// Kolonisationen und Fremd-Invasionen (Opfer nicht in der DB angemeldet) sehen
		'invasionen_admin'=>false,			// Invasionen auf freundlich setzen
		'masseninva'=>false,				// Masseninva-Koordinator benutzen
		'masseninva_admin'=>false,			// Masseninva-Koordinator verwalten
		
		// Spieler
		'userlist'=>false,					// Liste angemeldeter Spieler sehen
		'allywechsel'=>true,				// kürzliche Allywechsel anzeigen
		'inaktivensuche'=>false,				// die Inaktiven-Suchfunktion benutzen
		
		// Statistiken
		'stats_scan'=>false,					// Scan-Statistik anzeigen
		'stats_highscore'=>false,			// Scan-Highscore anzeigen
		
		
		// Verwaltung
		'verwaltung_userally'=>false,		// User der eigenen Allianz verwalten
		'verwaltung_user_register'=>false,	// alle User + Registrierungserlaubnis für User und Allianzen verwalten
		// von den beiden obigen Rechten abhängig
		'verwaltung_user_maxlevel'=>0,		// maximales Rechtelevel, das vergeben werden kann
		'verwaltung_user_custom'=>false,	// Userberechtigungen einzeln ändern
		
		'verwaltung_allianzen'=>false,		// Status von Allianzen ändern
		'verwaltung_galaxien'=>false,		// Galaxien einparsen
		'verwaltung_galaxien2'=>false,		// Galaxien verschmelzen und löschen
		'verwaltung_rechte'=>false,			// Rechtelevel bearbeiten
		'verwaltung_logfile'=>false,			// Logfile ansehen
		'verwaltung_settings'=>false,		// Grundeinstellungen der DB ändern
		'verwaltung_backup'=>false			// Backups der DB speichern oder einspielen
	),
	// Level 1: User
	1 => array(
		// generell
		'name'=>'User', 					// Name des Rechtelevels
		'desc'=>'normaler Account ohne Verwaltungsfunktionen und mit Sichtbarkeitssperren für empfindliche Daten',		// Beschreibung
		
		// Einscannen
		'scan'=>true,						// Quelltexte einscannen
		'scan_del'=>false,					// durch die Scans dürfen Planeten und Systeme gelöscht werden
											// (z.B. wenn sich ein User löscht; potentielle Sicherheitslücke)
		
		// Planetenanzeige
		// eigene Planeten können immer angezeigt werden, wenn show_planet true ist
		// wirkt sich auf alle Bereiche der DB aus
		'show_planet'=>true,				// darf der User überhaupt Planeten anzeigen?
		'show_planet_ally'=>true,			// Planeten der Allianz anzeigen
		'show_planet_meta'=>true,			// Planeten der Meta anzeigen
		'show_planet_register'=>true,		// Planeten von anderen registrierten Allianzen anzeigen
		
		// Systemanzeige
		// die Optionen gelten auch für den FoW
		// ist die Option show_system_ally aktiv, können auch alle anderen Systeme angezeigt werden, die Allianzplaneten enthalten
		// bei Systemen ohne Allianzplaneten treten die Regeln für Meta- und andere registrierte Systeme ein
		'show_system'=>true,				// darf der User überhaupt Systeme anzeigen?
		'show_system_ally'=>true,			// Systeme der Allianz anzeigen
		'show_system_meta'=>true,			// Systeme der Meta anzeigen
		'show_system_register'=>true,		// Systeme von anderen registrierten Allianzen anzeigen
		
		'show_player'=>true,				// Spieler anzeigen
		// die folgenden Einstellungen haben auch Einfluss auf die Liste angemeldeter Spieler
		'show_player_db_ally'=>false,		// DB-Daten angemeldeter Spieler der Allianz anzeigen (Sitter...)
		'show_player_db_meta'=>false,		// DB-Daten angemeldeter Spieler der Meta anzeigen
		'show_player_db_other'=>false,		// DB-Daten angemeldeter Spieler anderer Allianzen anzeigen
		
		'show_ally'=>true,					// Allianzen anzeigen
		'show_meta'=>true,					// Metas anzeigen
		
		// Sichtbarkeit und Editierbarkeit der Einteilung eines Planeten
		// die Einteilung eigener Planeten kann immer vollständig gesehen und bearbeitet werden
		'ressplani_ally'=>true,				// Ressplaneten der Ally anzeigen
		'ressplani_meta'=>false,				// Ressplaneten der Meta anzeigen
		'ressplani_register'=>false,			// Ressplaneten anderer angemeldeter Allianzen anzeigen
		'ressplani_feind'=>true,			// kürzlich gescannte feindliche Ressplaneten anzeigen
		
		'bunker_ally'=>true,				// Bunker der Ally anzeigen
		'bunker_meta'=>false,				// Bunker der Meta anzeigen
		'bunker_register'=>false,			// Bunker anderer angemeldeter Allianzen anzeigen
		
		'werft_ally'=>true,					// Werften der Ally anzeigen
		'werft_meta'=>false,					// Werften der Meta anzeigen
		'werft_register'=>false,				// Werften anderer angemeldeter Allianzen anzeigen
		
		'flags_edit_ally'=>false,			// Ressplanet, Bunker und Werft bei Allyplaneten ändern
		'flags_edit_meta'=>false,			// Ressplanet, Bunker und Werft bei Metaplaneten ändern
		'flags_edit_register'=>false,		// Ressplanet, Bunker und Werft bei Planeten anderer angemeldeter Allianzen ändern
		'flags_edit_other'=>false,			// Ressplanet, Bunker und Werft bei Planeten nicht angemeldeter Allianzen ändern
		
		// Myrigates und Risse
		// wirkt sich auf alle Bereiche der DB aus
		'show_myrigates'=>true,				// Myrigates anzeigen
		'show_myrigates_ally'=>true,		// Myrigates der Allianz anzeigen
		'show_myrigates_meta'=>true,		// Myrigates der Meta anzeigen
		'show_myrigates_register'=>true,	// Myrigates anderer registrierter Allianzen anzeigen
		
		// FoW
		'fow'=>true,						// FoW-Ausgleich benutzen
		
		// Suche
		// wirkt sich auch auf die Anzeige der nächsten/entferntesten Planeten aus (Streckenberechnung und FoW)
		'search'=>true,						// Suchfunktionen benutzen
		'search_ally'=>true,				// Planeten der eigenen Allianz finden
		'search_meta'=>true,				// Planeten der eigenen Meta finden
		'search_register'=>true,			// Planeten anderer registrierter Allianzen finden
		
		// Scouten
		'scout'=>true,						// Scout-Funktionen benutzen
		
		// Strecken
		'strecken_flug'=>true,				// Entfernungs- und Flugberechnung
		'strecken_weg'=>true,				// schnellster Weg-Funktion
		'strecken_saveroute'=>false,			// Saverouten-Generator
		'strecken_ueberflug'=>false,			// System-Überflug
		
		'toxxraid'=>true,					// Toxx- und Raid-Funktion benutzen
		
		// Routen
		'routen'=>true,						// Routen-Funktion benutzen
											// eigene Routen und Listen erstellen
		'routen_ally'=>true,				// Routen der Allianz sehen
		'routen_meta'=>true,				// Routen der Meta sehen
		'routen_other'=>false,				// Routen anderer angemeldeter Allianzen sehen
		
		'karte'=>true,						// Karte benutzen
		
		'gates'=>true,						// Gate- und Myrigateliste einsehen
		
		// Invasionen
		'invasionen'=>true,					// Invasionen, Resonationen, Genesis-Projekte und Besatzungen sehen
		'fremdinvakolos'=>true,				// Kolonisationen und Fremd-Invasionen (Opfer nicht in der DB angemeldet) sehen
		'invasionen_admin'=>false,			// Invasionen auf freundlich setzen
		'masseninva'=>true,					// Masseninva-Koordinator benutzen
		'masseninva_admin'=>false,			// Masseninva-Koordinator verwalten
		
		// Spieler
		'userlist'=>true,					// Liste angemeldeter Spieler sehen
		'allywechsel'=>true,				// kürzliche Allywechsel anzeigen
		'inaktivensuche'=>true,				// die Inaktiven-Suchfunktion benutzen
		
		// Statistiken
		'stats_scan'=>true,					// Scan-Statistik anzeigen
		'stats_highscore'=>true,			// Scan-Highscore anzeigen
		
		
		// Verwaltung
		'verwaltung_userally'=>false,		// User der eigenen Allianz verwalten
		'verwaltung_user_register'=>false,	// alle User + Registrierungserlaubnis für User und Allianzen verwalten
		// von den beiden obigen Rechten abhängig
		'verwaltung_user_maxlevel'=>0,		// maximales Rechtelevel, das vergeben werden kann
		'verwaltung_user_custom'=>false,	// Userberechtigungen einzeln ändern
		
		'verwaltung_allianzen'=>false,		// Status von Allianzen ändern
		'verwaltung_galaxien'=>false,		// Galaxien einparsen
		'verwaltung_galaxien2'=>false,		// Galaxien verschmelzen und löschen
		'verwaltung_rechte'=>false,			// Rechtelevel bearbeiten
		'verwaltung_logfile'=>false,			// Logfile ansehen
		'verwaltung_settings'=>false,		// Grundeinstellungen der DB ändern
		'verwaltung_backup'=>false			// Backups der DB speichern oder einspielen
	),
	// Level 2: FA
	2 => array(
		// generell
		'name'=>'FA', 					// Name des Rechtelevels
		'desc'=>'fast alles sichtbar, Galaxien können eingeparst werden',		// Beschreibung
		
		// Einscannen
		'scan'=>true,						// Quelltexte einscannen
		'scan_del'=>true,					// durch die Scans dürfen Planeten und Systeme gelöscht werden
											// (z.B. wenn sich ein User löscht; potentielle Sicherheitslücke)
		
		// Planetenanzeige
		// eigene Planeten können immer angezeigt werden, wenn show_planet true ist
		// wirkt sich auf alle Bereiche der DB aus
		'show_planet'=>true,				// darf der User überhaupt Planeten anzeigen?
		'show_planet_ally'=>true,			// Planeten der Allianz anzeigen
		'show_planet_meta'=>true,			// Planeten der Meta anzeigen
		'show_planet_register'=>true,		// Planeten von anderen registrierten Allianzen anzeigen
		
		// Systemanzeige
		// die Optionen gelten auch für den FoW
		// ist die Option show_system_ally aktiv, können auch alle anderen Systeme angezeigt werden, die Allianzplaneten enthalten
		// bei Systemen ohne Allianzplaneten treten die Regeln für Meta- und andere registrierte Systeme ein
		'show_system'=>true,				// darf der User überhaupt Systeme anzeigen?
		'show_system_ally'=>true,			// Systeme der Allianz anzeigen
		'show_system_meta'=>true,			// Systeme der Meta anzeigen
		'show_system_register'=>true,		// Systeme von anderen registrierten Allianzen anzeigen
		
		'show_player'=>true,				// Spieler anzeigen
		// die folgenden Einstellungen haben auch Einfluss auf die Liste angemeldeter Spieler
		'show_player_db_ally'=>true,		// DB-Daten angemeldeter Spieler der Allianz anzeigen (Sitter...)
		'show_player_db_meta'=>true,		// DB-Daten angemeldeter Spieler der Meta anzeigen
		'show_player_db_other'=>false,		// DB-Daten angemeldeter Spieler anderer Allianzen anzeigen
		
		'show_ally'=>true,					// Allianzen anzeigen
		'show_meta'=>true,					// Metas anzeigen
		
		// Sichtbarkeit und Editierbarkeit der Einteilung eines Planeten
		// die Einteilung eigener Planeten kann immer vollständig gesehen und bearbeitet werden
		'ressplani_ally'=>true,				// Ressplaneten der Ally anzeigen
		'ressplani_meta'=>true,				// Ressplaneten der Meta anzeigen
		'ressplani_register'=>true,			// Ressplaneten anderer angemeldeter Allianzen anzeigen
		'ressplani_feind'=>true,			// kürzlich gescannte feindliche Ressplaneten anzeigen
		
		'bunker_ally'=>true,				// Bunker der Ally anzeigen
		'bunker_meta'=>true,				// Bunker der Meta anzeigen
		'bunker_register'=>true,			// Bunker anderer angemeldeter Allianzen anzeigen
		
		'werft_ally'=>true,					// Werften der Ally anzeigen
		'werft_meta'=>true,					// Werften der Meta anzeigen
		'werft_register'=>true,				// Werften anderer angemeldeter Allianzen anzeigen
		
		'flags_edit_ally'=>true,			// Ressplanet, Bunker und Werft bei Allyplaneten ändern
		'flags_edit_meta'=>true,			// Ressplanet, Bunker und Werft bei Metaplaneten ändern
		'flags_edit_register'=>false,		// Ressplanet, Bunker und Werft bei Planeten anderer angemeldeter Allianzen ändern
		'flags_edit_other'=>true,			// Ressplanet, Bunker und Werft bei Planeten nicht angemeldeter Allianzen ändern
		
		// Myrigates und Risse
		// wirkt sich auf alle Bereiche der DB aus
		'show_myrigates'=>true,				// Myrigates anzeigen
		'show_myrigates_ally'=>true,		// Myrigates der Allianz anzeigen
		'show_myrigates_meta'=>true,		// Myrigates der Meta anzeigen
		'show_myrigates_register'=>true,	// Myrigates anderer registrierter Allianzen anzeigen
		
		// FoW
		'fow'=>true,						// FoW-Ausgleich benutzen
		
		// Suche
		// wirkt sich auch auf die Anzeige der nächsten/entferntesten Planeten aus (Streckenberechnung und FoW)
		'search'=>true,						// Suchfunktionen benutzen
		'search_ally'=>true,				// Planeten der eigenen Allianz finden
		'search_meta'=>true,				// Planeten der eigenen Meta finden
		'search_register'=>true,			// Planeten anderer registrierter Allianzen finden
		
		// Scouten
		'scout'=>true,						// Scout-Funktionen benutzen
		
		// Strecken
		'strecken_flug'=>true,				// Entfernungs- und Flugberechnung
		'strecken_weg'=>true,				// schnellster Weg-Funktion
		'strecken_saveroute'=>true,			// Saverouten-Generator
		'strecken_ueberflug'=>true,			// System-Überflug
		
		'toxxraid'=>true,					// Toxx- und Raid-Funktion benutzen
		
		// Routen
		'routen'=>true,						// Routen-Funktion benutzen
											// eigene Routen und Listen erstellen
		'routen_ally'=>true,				// Routen der Allianz sehen
		'routen_meta'=>true,				// Routen der Meta sehen
		'routen_other'=>true,				// Routen anderer angemeldeter Allianzen sehen
		
		'karte'=>true,						// Karte benutzen
		
		'gates'=>true,						// Gate- und Myrigateliste einsehen
		
		// Invasionen
		'invasionen'=>true,					// Invasionen, Resonationen, Genesis-Projekte und Besatzungen sehen
		'fremdinvakolos'=>true,				// Kolonisationen und Fremd-Invasionen (Opfer nicht in der DB angemeldet) sehen
		'invasionen_admin'=>true,			// Invasionen auf freundlich setzen
		'masseninva'=>true,					// Masseninva-Koordinator benutzen
		'masseninva_admin'=>false,			// Masseninva-Koordinator verwalten
		
		// Spieler
		'userlist'=>true,					// Liste angemeldeter Spieler sehen
		'allywechsel'=>true,				// kürzliche Allywechsel anzeigen
		'inaktivensuche'=>true,				// die Inaktiven-Suchfunktion benutzen
		
		// Statistiken
		'stats_scan'=>true,					// Scan-Statistik anzeigen
		'stats_highscore'=>true,			// Scan-Highscore anzeigen
		
		
		// Verwaltung
		'verwaltung_userally'=>false,		// User der eigenen Allianz verwalten
		'verwaltung_user_register'=>false,	// alle User + Registrierungserlaubnis für User und Allianzen verwalten
		// von den beiden obigen Rechten abhängig
		'verwaltung_user_maxlevel'=>0,		// maximales Rechtelevel, das vergeben werden kann
		'verwaltung_user_custom'=>false,	// Userberechtigungen einzeln ändern
		
		'verwaltung_allianzen'=>false,		// Status von Allianzen ändern
		'verwaltung_galaxien'=>true,		// Galaxien einparsen
		'verwaltung_galaxien2'=>false,		// Galaxien verschmelzen und löschen
		'verwaltung_rechte'=>false,			// Rechtelevel bearbeiten
		'verwaltung_logfile'=>false,			// Logfile ansehen
		'verwaltung_settings'=>false,		// Grundeinstellungen der DB ändern
		'verwaltung_backup'=>false			// Backups der DB speichern oder einspielen
	),
	// Level 3: Leader
	3 => array(
		// generell
		'name'=>'Leader', 					// Name des Rechtelevels
		'desc'=>'Verwaltungsberechtigungen nur für die eigene Allianz',	// Beschreibung
		
		// Einscannen
		'scan'=>true,						// Quelltexte einscannen
		'scan_del'=>true,					// durch die Scans dürfen Planeten und Systeme gelöscht werden
											// (z.B. wenn sich ein User löscht; potentielle Sicherheitslücke)
		
		// Planetenanzeige
		// eigene Planeten können immer angezeigt werden, wenn show_planet true ist
		// wirkt sich auf alle Bereiche der DB aus
		'show_planet'=>true,				// darf der User überhaupt Planeten anzeigen?
		'show_planet_ally'=>true,			// Planeten der Allianz anzeigen
		'show_planet_meta'=>true,			// Planeten der Meta anzeigen
		'show_planet_register'=>true,		// Planeten von anderen registrierten Allianzen anzeigen
		
		// Systemanzeige
		// die Optionen gelten auch für den FoW
		// ist die Option show_system_ally aktiv, können auch alle anderen Systeme angezeigt werden, die Allianzplaneten enthalten
		// bei Systemen ohne Allianzplaneten treten die Regeln für Meta- und andere registrierte Systeme ein
		'show_system'=>true,				// darf der User überhaupt Systeme anzeigen?
		'show_system_ally'=>true,			// Systeme der Allianz anzeigen
		'show_system_meta'=>true,			// Systeme der Meta anzeigen
		'show_system_register'=>true,		// Systeme von anderen registrierten Allianzen anzeigen
		
		'show_player'=>true,				// Spieler anzeigen
		// die folgenden Einstellungen haben auch Einfluss auf die Liste angemeldeter Spieler
		'show_player_db_ally'=>true,		// DB-Daten angemeldeter Spieler der Allianz anzeigen (Sitter...)
		'show_player_db_meta'=>true,		// DB-Daten angemeldeter Spieler der Meta anzeigen
		'show_player_db_other'=>true,		// DB-Daten angemeldeter Spieler anderer Allianzen anzeigen
		
		'show_ally'=>true,					// Allianzen anzeigen
		'show_meta'=>true,					// Metas anzeigen
		
		// Sichtbarkeit und Editierbarkeit der Einteilung eines Planeten
		// die Einteilung eigener Planeten kann immer vollständig gesehen und bearbeitet werden
		'ressplani_ally'=>true,				// Ressplaneten der Ally anzeigen
		'ressplani_meta'=>true,				// Ressplaneten der Meta anzeigen
		'ressplani_register'=>true,			// Ressplaneten anderer angemeldeter Allianzen anzeigen
		'ressplani_feind'=>true,			// kürzlich gescannte feindliche Ressplaneten anzeigen
		
		'bunker_ally'=>true,				// Bunker der Ally anzeigen
		'bunker_meta'=>true,				// Bunker der Meta anzeigen
		'bunker_register'=>true,			// Bunker anderer angemeldeter Allianzen anzeigen
		
		'werft_ally'=>true,					// Werften der Ally anzeigen
		'werft_meta'=>true,					// Werften der Meta anzeigen
		'werft_register'=>true,				// Werften anderer angemeldeter Allianzen anzeigen
		
		'flags_edit_ally'=>true,			// Ressplanet, Bunker und Werft bei Allyplaneten ändern
		'flags_edit_meta'=>true,			// Ressplanet, Bunker und Werft bei Metaplaneten ändern
		'flags_edit_register'=>true,		// Ressplanet, Bunker und Werft bei Planeten anderer angemeldeter Allianzen ändern
		'flags_edit_other'=>true,			// Ressplanet, Bunker und Werft bei Planeten nicht angemeldeter Allianzen ändern
		
		// Myrigates und Risse
		// wirkt sich auf alle Bereiche der DB aus
		'show_myrigates'=>true,				// Myrigates anzeigen
		'show_myrigates_ally'=>true,		// Myrigates der Allianz anzeigen
		'show_myrigates_meta'=>true,		// Myrigates der Meta anzeigen
		'show_myrigates_register'=>true,	// Myrigates anderer registrierter Allianzen anzeigen
		
		// FoW
		'fow'=>true,						// FoW-Ausgleich benutzen
		
		// Suche
		// wirkt sich auch auf die Anzeige der nächsten/entferntesten Planeten aus (Streckenberechnung und FoW)
		'search'=>true,						// Suchfunktionen benutzen
		'search_ally'=>true,				// Planeten der eigenen Allianz finden
		'search_meta'=>true,				// Planeten der eigenen Meta finden
		'search_register'=>true,			// Planeten anderer registrierter Allianzen finden
		
		// Scouten
		'scout'=>true,						// Scout-Funktionen benutzen
		
		// Strecken
		'strecken_flug'=>true,				// Entfernungs- und Flugberechnung
		'strecken_weg'=>true,				// schnellster Weg-Funktion
		'strecken_saveroute'=>true,			// Saverouten-Generator
		'strecken_ueberflug'=>true,			// System-Überflug
		
		'toxxraid'=>true,					// Toxx- und Raid-Funktion benutzen
		
		// Routen
		'routen'=>true,						// Routen-Funktion benutzen
											// eigene Routen und Listen erstellen
		'routen_ally'=>true,				// Routen der Allianz sehen
		'routen_meta'=>true,				// Routen der Meta sehen
		'routen_other'=>true,				// Routen anderer angemeldeter Allianzen sehen
		
		'karte'=>true,						// Karte benutzen
		
		'gates'=>true,						// Gate- und Myrigateliste einsehen
		
		// Invasionen
		'invasionen'=>true,					// Invasionen, Resonationen, Genesis-Projekte und Besatzungen sehen
		'fremdinvakolos'=>true,				// Kolonisationen und Fremd-Invasionen (Opfer nicht in der DB angemeldet) sehen
		'invasionen_admin'=>true,			// Invasionen auf freundlich setzen
		'masseninva'=>true,					// Masseninva-Koordinator benutzen
		'masseninva_admin'=>true,			// Masseninva-Koordinator verwalten
		
		// Spieler
		'userlist'=>true,					// Liste angemeldeter Spieler sehen
		'allywechsel'=>true,				// kürzliche Allywechsel anzeigen
		'inaktivensuche'=>true,				// die Inaktiven-Suchfunktion benutzen
		
		// Statistiken
		'stats_scan'=>true,					// Scan-Statistik anzeigen
		'stats_highscore'=>true,			// Scan-Highscore anzeigen
		
		
		// Verwaltung
		'verwaltung_userally'=>true,		// User der eigenen Allianz verwalten
		'verwaltung_user_register'=>false,	// alle User + Registrierungserlaubnis für User und Allianzen verwalten
		// von den beiden obigen Rechten abhängig
		'verwaltung_user_maxlevel'=>3,		// maximales Rechtelevel, das vergeben werden kann
		'verwaltung_user_custom'=>true,		// Userberechtigungen einzeln ändern
		
		'verwaltung_allianzen'=>true,		// Status von Allianzen ändern
		'verwaltung_galaxien'=>true,		// Galaxien einparsen
		'verwaltung_galaxien2'=>true,		// Galaxien verschmelzen und löschen
		'verwaltung_rechte'=>false,			// Rechtelevel bearbeiten
		'verwaltung_logfile'=>true,			// Logfile ansehen
		'verwaltung_settings'=>false,		// Grundeinstellungen der DB ändern
		'verwaltung_backup'=>false			// Backups der DB speichern oder einspielen
	),
	// Level 4: Administrator
	4 => array(
		// generell
		'name'=>'Administrator', 			// Name des Rechtelevels
		'desc'=>'das höchste Rechtelevel mit allen Berechtigungen',	// Beschreibung
		
		// Einscannen
		'scan'=>true,						// Quelltexte einscannen
		'scan_del'=>true,					// durch die Scans dürfen Planeten und Systeme gelöscht werden
											// (z.B. wenn sich ein User löscht; potentielle Sicherheitslücke)
		
		// Planetenanzeige
		// eigene Planeten können immer angezeigt werden, wenn show_planet true ist
		// wirkt sich auf alle Bereiche der DB aus
		'show_planet'=>true,				// darf der User überhaupt Planeten anzeigen?
		'show_planet_ally'=>true,			// Planeten der Allianz anzeigen
		'show_planet_meta'=>true,			// Planeten der Meta anzeigen
		'show_planet_register'=>true,		// Planeten von anderen registrierten Allianzen anzeigen
		
		// Systemanzeige
		// die Optionen gelten auch für den FoW
		// ist die Option show_system_ally aktiv, können auch alle anderen Systeme angezeigt werden, die Allianzplaneten enthalten
		// bei Systemen ohne Allianzplaneten treten die Regeln für Meta- und andere registrierte Systeme ein
		'show_system'=>true,				// darf der User überhaupt Systeme anzeigen?
		'show_system_ally'=>true,			// Systeme der Allianz anzeigen
		'show_system_meta'=>true,			// Systeme der Meta anzeigen
		'show_system_register'=>true,		// Systeme von anderen registrierten Allianzen anzeigen
		
		'show_player'=>true,				// Spieler anzeigen
		// die folgenden Einstellungen haben auch Einfluss auf die Liste angemeldeter Spieler
		'show_player_db_ally'=>true,		// DB-Daten angemeldeter Spieler der Allianz anzeigen (Sitter...)
		'show_player_db_meta'=>true,		// DB-Daten angemeldeter Spieler der Meta anzeigen
		'show_player_db_other'=>true,		// DB-Daten angemeldeter Spieler anderer Allianzen anzeigen
		
		'show_ally'=>true,					// Allianzen anzeigen
		'show_meta'=>true,					// Metas anzeigen
		
		// Sichtbarkeit und Editierbarkeit der Einteilung eines Planeten
		// die Einteilung eigener Planeten kann immer vollständig gesehen und bearbeitet werden
		'ressplani_ally'=>true,				// Ressplaneten der Ally anzeigen
		'ressplani_meta'=>true,				// Ressplaneten der Meta anzeigen
		'ressplani_register'=>true,			// Ressplaneten anderer angemeldeter Allianzen anzeigen
		'ressplani_feind'=>true,			// kürzlich gescannte feindliche Ressplaneten anzeigen
		
		'bunker_ally'=>true,				// Bunker der Ally anzeigen
		'bunker_meta'=>true,				// Bunker der Meta anzeigen
		'bunker_register'=>true,			// Bunker anderer angemeldeter Allianzen anzeigen
		
		'werft_ally'=>true,					// Werften der Ally anzeigen
		'werft_meta'=>true,					// Werften der Meta anzeigen
		'werft_register'=>true,				// Werften anderer angemeldeter Allianzen anzeigen
		
		'flags_edit_ally'=>true,			// Ressplanet, Bunker und Werft bei Allyplaneten ändern
		'flags_edit_meta'=>true,			// Ressplanet, Bunker und Werft bei Metaplaneten ändern
		'flags_edit_register'=>true,		// Ressplanet, Bunker und Werft bei Planeten anderer angemeldeter Allianzen ändern
		'flags_edit_other'=>true,			// Ressplanet, Bunker und Werft bei Planeten nicht angemeldeter Allianzen ändern
		
		// Myrigates und Risse
		// wirkt sich auf alle Bereiche der DB aus
		'show_myrigates'=>true,				// Myrigates anzeigen
		'show_myrigates_ally'=>true,		// Myrigates der Allianz anzeigen
		'show_myrigates_meta'=>true,		// Myrigates der Meta anzeigen
		'show_myrigates_register'=>true,	// Myrigates anderer registrierter Allianzen anzeigen
		
		// FoW
		'fow'=>true,						// FoW-Ausgleich benutzen
		
		// Suche
		// wirkt sich auch auf die Anzeige der nächsten/entferntesten Planeten aus (Streckenberechnung und FoW)
		'search'=>true,						// Suchfunktionen benutzen
		'search_ally'=>true,				// Planeten der eigenen Allianz finden
		'search_meta'=>true,				// Planeten der eigenen Meta finden
		'search_register'=>true,			// Planeten anderer registrierter Allianzen finden
		
		// Scouten
		'scout'=>true,						// Scout-Funktionen benutzen
		
		// Strecken
		'strecken_flug'=>true,				// Entfernungs- und Flugberechnung
		'strecken_weg'=>true,				// schnellster Weg-Funktion
		'strecken_saveroute'=>true,			// Saverouten-Generator
		'strecken_ueberflug'=>true,			// System-Überflug
		
		'toxxraid'=>true,					// Toxx- und Raid-Funktion benutzen
		
		// Routen
		'routen'=>true,						// Routen-Funktion benutzen
											// eigene Routen und Listen erstellen
		'routen_ally'=>true,				// Routen der Allianz sehen
		'routen_meta'=>true,				// Routen der Meta sehen
		'routen_other'=>true,				// Routen anderer angemeldeter Allianzen sehen
		
		'karte'=>true,						// Karte benutzen
		
		'gates'=>true,						// Gateliste einsehen
		
		// Invasionen
		'invasionen'=>true,					// Invasionen, Resonationen, Genesis-Projekte und Besatzungen sehen
		'fremdinvakolos'=>true,				// Kolonisationen und Fremd-Invasionen (Opfer nicht in der DB angemeldet) sehen
		'invasionen_admin'=>true,			// Invasionen auf freundlich setzen
		'masseninva'=>true,					// Masseninva-Koordinator benutzen
		'masseninva_admin'=>true,			// Masseninva-Koordinator verwalten
		
		// Spieler
		'userlist'=>true,					// Liste angemeldeter Spieler sehen
		'allywechsel'=>true,				// kürzliche Allywechsel anzeigen
		'inaktivensuche'=>true,				// die Inaktiven-Suchfunktion benutzen
		
		// Statistiken
		'stats_scan'=>true,					// Scan-Statistik anzeigen
		'stats_highscore'=>true,			// Scan-Highscore anzeigen
		
		
		// Verwaltung
		'verwaltung_userally'=>true,		// User der eigenen Allianz verwalten
		'verwaltung_user_register'=>true,	// alle User + Registrierungserlaubnis für User und Allianzen verwalten
		// von den beiden obigen Rechten abhängig
		'verwaltung_user_maxlevel'=>4,		// maximales Rechtelevel, das vergeben werden kann
		'verwaltung_user_custom'=>true,		// Userberechtigungen einzeln ändern
		
		'verwaltung_allianzen'=>true,		// Status von Allianzen ändern
		'verwaltung_galaxien'=>true,		// Galaxien einparsen
		'verwaltung_galaxien2'=>true,		// Galaxien verschmelzen und löschen
		'verwaltung_rechte'=>true,			// Rechtelevel bearbeiten
		'verwaltung_logfile'=>true,			// Logfile ansehen
		'verwaltung_settings'=>true,		// Grundeinstellungen der DB ändern
		'verwaltung_backup'=>true			// Backups der DB speichern oder einspielen
	)
);


// Basis-Einstellungen für die Instanz kopieren
$config = $bconfig;
$rechte = $brechte;

// globale Prefix-Konstante setzen
define('GLOBPREFIX', $bconfig['mysql_globprefix']);


?>