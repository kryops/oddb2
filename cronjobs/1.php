<?php

/**
 * cronjobs/1.php
 * Laufzeit: jede Minute (außer in der Downtime)
 *
 * - veraltetes Flooding löschen
 * - odrequests absetzen
 */


// alle Fehlermeldungen aktivieren
error_reporting(E_ALL);

// Rendertime-Messung starten
$time = microtime(true);

// User-Abort deaktivieren, um eventuelle Fehler zu verhinden
ignore_user_abort(true);

// Query-Zähler initialisieren
$queries = 0;

// Sicherheitskonstante
define('ODDB', true);
define('ODDBADMIN', true);
define('INSTANCE', 0);

// Zeitzone setzen -> Performance
date_default_timezone_set('Europe/Berlin');

// HTTP-Cache-Header ausgeben (Caching der Seite verhindern)
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
//header('Pragma: no-cache');

// in der Downtime abbrechen
if(DOWNTIME AND date('G') == 4) {
	die('Downtime!');
}

// Funktionsdateien einbinden
include '../common.php';

// globale Konfiguration einbinden
include '../globalconfig.php';

// nicht installiert
if(!defined('INSTALLED') OR !INSTALLED) {
	die('Die ODDB wurde noch nicht installiert!');
}

// falscher Sicherheitsschlüssel
if(!isset($_GET['key']) OR $_GET['key'] != KEY) {
	die('Sicherheitscode falsch!');
}

// Instanz-Array umformen
if(!$dbs) $dbs = array(1=>'');
$dbs = array_keys($dbs);

// normale MySQL-Klasse umgehen
$mysql_conn = new mysql;
$mysql_conn->connected = true;

$mysqlconns = array();
$instance = 0;

$mysqlhash = $config['mysql_host'].'-'.$config['mysql_user'];

${'mysqlconn'.$instance} = @mysql_connect(
	$config['mysql_host'],
	$config['mysql_user'],
	$config['mysql_pw']
);

// Verbindung fehlgeschlagen
if(!${'mysqlconn'.$instance}) {
	echo 'MySQL-Verbindung fehlgeschlagen: '.$mysqlhash.' ('.$instance.')<br />';
	continue;
}

$conn =& ${'mysqlconn'.$instance};
$mysqlconns[0] = $mysqlhash;

// MySQL auf UTF-8 stellen
if(function_exists('mysql_set_charset')) {
	mysql_set_charset('utf8', $conn);
}
else {
	mysql_query("
		SET NAMES 'UTF8'
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

// Datenbank auswählen
if(!mysql_select_db($config['mysql_db'], $conn)) {
	echo 'Datenbank konnte nicht ausgewählt werden: '.$mysqlhash.'-'.$config['mysql_db'].' ('.$instance.')<br />';
	continue;
}


// Caching initialisieren
$cache = new cache();



/**
 * globale Operationen
 */
//
// Flooding löschen (wenn kein Caching)
//

if(!$config['caching']) {
	mysql_query("
		DELETE FROM ".GLOBPREFIX."flooding
		WHERE floodingTime < ".(time()-300)."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

//
// odrequests absetzen
//

// Allianzen und Metas für das odrequest zurücksetzen
$odrallies = array();

$query = mysql_query("
	SELECT
		playerID
	FROM ".GLOBPREFIX."player
	WHERE
		playerID > 2
		AND playerDeleted = 0
		AND playerUpdate < ".(time()-3600*$gconfig['odrequest'])."
	ORDER BY
		playerID ASC
	LIMIT
		".$gconfig['odrequest_max']."
", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

while($row = mysql_fetch_assoc($query)) {
	// nach 50 Sekunden odrequests abbrechen
	if(time()-$_SERVER['REQUEST_TIME'] > 50) {
		break;
	}
	
	odrequest($row['playerID']);
}

//
// Spieler mit kürlichen Allywechseln ermitteln
//

$query = mysql_query("
	SELECT
		allyhistory_playerID,
		allyhistory_allianzenID
	FROM
		".GLOBPREFIX."player_allyhistory
	WHERE
		allyhistoryFinal = 0
		AND allyhistoryLastAlly IS NOT NULL
	ORDER BY
		allyhistoryTime ASC
", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$allywechsel = array();

while($row = mysql_fetch_assoc($query)) {
	$allywechsel[] = $row;
}

// bei allen auf übertragen setzen
mysql_query("
	UPDATE
		".GLOBPREFIX."player_allyhistory
	SET
		allyhistoryFinal = 1
	WHERE
		allyhistoryFinal = 0
", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());




/**
 * Instanzen durchgehen
 */
foreach($dbs as $instance) {
	// Konfigurationsdatei einbinden
	$config = $bconfig;
	if(!(@include('../config/config'.$instance.'.php'))) {
		continue;
	}
	
	// MySQL-Verbindung
	$mysqlhash = $config['mysql_host'].'-'.$config['mysql_user'];
	
	// Verbindung besteht schon
	if(in_array($mysqlhash, $mysqlconns)) {
		$conn =& ${'mysqlconn'.array_search($mysqlhash, $mysqlconns)};
	}
	// Verbindung aufbauen
	else {
		${'mysqlconn'.$instance} = @mysql_connect(
			$config['mysql_host'],
			$config['mysql_user'],
			$config['mysql_pw']
		);
		
		// Verbindung fehlgeschlagen
		if(!${'mysqlconn'.$instance}) {
			echo 'MySQL-Verbindung fehlgeschlagen: '.$mysqlhash.' ('.$instance.')<br />';
			continue;
		}
		
		$conn =& ${'mysqlconn'.$instance};
		$mysqlconns[$instance] = $mysqlhash;
		
		// MySQL auf UTF-8 stellen
		if(function_exists('mysql_set_charset')) {
			mysql_set_charset('utf8', $conn);
		}
		else {
			mysql_query("
				SET NAMES 'UTF8'
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	// Datenbank auswählen
	if(!mysql_select_db($config['mysql_db'], $conn)) {
		echo 'Datenbank konnte nicht ausgewählt werden: '.$mysqlhash.'-'.$config['mysql_db'].' ('.$instance.')<br />';
		continue;
	}
	
	$prefix = $config['mysql_prefix'];
	
	//
	// Allianzwechsel in die Usertabellen übertragen
	//
	
	// Registriererlaubnisse abfragen
	$register_player = array();
	$register_allies = array();
	
	$query = mysql_query("
		SELECT
			register_playerID,
			register_allianzenID
		FROM
			".$prefix."register
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		if($row['register_playerID']) {
			$register_player[] = $row['register_playerID'];
		}
		else {
			$register_allies[] = $row['register_allianzenID'];
		}
	}
	
	// Allianzwechsel übertragen
	foreach($allywechsel as $row) {
		// Sperr-Status überprüfen
		$query = mysql_query("
			SELECT
				userBanned
			FROM
				".$prefix."user
			WHERE
				user_playerID = ".(int)$row['allyhistory_playerID']."
		", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			// darf der Spieler die Instanz benutzen?
			$register = 0;
			if(in_array($row['allyhistory_playerID'],$register_player)) {
				$register = 1;
			}
			else if($row['allyhistory_allianzenID'] AND in_array($row['allyhistory_allianzenID'],$register_allies)) {
				$register = 1;
			}
			
			$data = mysql_fetch_assoc($query);
			
			$banned = $data['userBanned'];
			
			// sperren
			if(!$register AND !$banned) {
				$banned = 2;
			}
			// entsperren
			else if($register AND $banned == 2) {
				$banned = 0;
			}
			
			// in DB übernehmen
			mysql_query("
				UPDATE
					".$prefix."user
				SET
					user_allianzenID = ".(int)$row['allyhistory_allianzenID'].",
					userBanned = ".$banned."
				WHERE
					user_playerID = ".(int)$row['allyhistory_playerID']."
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	//
	// Statistiken der Übersichtsseite
	//
	if($config['caching']) {
		// Invasionen
		$query = mysql_query("
			SELECT
				COUNT(*) AS invasionenCount
			FROM
				".$prefix."invasionen
			WHERE
				(invasionenEnde = 0 OR invasionenEnde > ".time().")
				AND invasionenFremd = 0
				AND invasionenTyp != 5
		", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_assoc($query);
		
		$cache->setglobal($instance.'invas', $data['invasionenCount'], 120);
		
		// Spieler noch nicht freigeschaltet oder gesperrt
		$query = mysql_query("
			SELECT
				COUNT(*) AS userCount
			FROM
				".$prefix."user
			WHERE
				userBanned = 2
				OR userBanned = 3
		", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_assoc($query);
		
		$cache->setglobal($instance.'userbanned', $data['userCount'], 120);
	}
}



?>