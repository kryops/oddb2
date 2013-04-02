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

// Funktionsdateien einbinden
include '../common.php';

// in der Downtime abbrechen
if(DOWNTIME AND date('G') == 4) {
	die('Downtime!');
}

// nicht installiert
if(!@include('../config/global.php')) {
	die('Die ODDB wurde noch nicht installiert!');
}

$gconfig = $config;
define('GLOBPREFIX', $config['mysql_globprefix']);

$dbs = array();
include '../config/dbs.php';

// falscher Sicherheitsschlüssel
if(!isset($_GET['key']) OR $_GET['key'] != $config['key']) {
	die('Sicherheitscode falsch!');
}

// Instanz-Array umformen
$dbs = array_keys($dbs);

// MySQL-Verbindung
$mysql_conn = new mysql;

// Caching initialisieren
$cache = new cache();


// Statistik-Counter
$countOdRequest = 0;
$countAllyWechsel = 0;



/**
 * globale Operationen
 */
//
// Flooding löschen (wenn kein Caching)
//

if(!$config['caching']) {
	query("
		DELETE FROM ".GLOBPREFIX."flooding
		WHERE floodingTime < ".(time()-300)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

//
// odrequests absetzen
//

query("START TRANSACTION") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Allianzen und Metas für das odrequest zurücksetzen
$odrallies = array();

$query = query("
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
") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

while($row = mysql_fetch_assoc($query)) {
	// nach 50 Sekunden odrequests abbrechen
	if(time()-$_SERVER['REQUEST_TIME'] > 50) {
		break;
	}
	
	odrequest($row['playerID']);
	$countOdRequest++;
}

query("COMMIT") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

query("START TRANSACTION") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

//
// Spieler mit kürlichen Allywechseln ermitteln
//

$query = query("
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
") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$allywechsel = array();

while($row = mysql_fetch_assoc($query)) {
	$allywechsel[] = $row;
}

$countAllyWechsel = count($allywechsel);

// bei allen auf übertragen setzen
query("
	UPDATE
		".GLOBPREFIX."player_allyhistory
	SET
		allyhistoryFinal = 1
	WHERE
		allyhistoryFinal = 0
") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());




/**
 * Instanzen durchgehen
 */
foreach($dbs as $instance) {
	
	$prefix = mysql::getPrefix($instance);
	
	//
	// Allianzwechsel in die Usertabellen übertragen
	//
	
	// Registriererlaubnisse abfragen
	$register_player = array();
	$register_allies = array();
	
	$query = query("
		SELECT
			register_playerID,
			register_allianzenID
		FROM
			".$prefix."register
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
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
		$query = query("
			SELECT
				userBanned
			FROM
				".$prefix."user
			WHERE
				user_playerID = ".(int)$row['allyhistory_playerID']."
		") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
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
			query("
				UPDATE
					".$prefix."user
				SET
					user_allianzenID = ".(int)$row['allyhistory_allianzenID'].",
					userBanned = ".$banned."
				WHERE
					user_playerID = ".(int)$row['allyhistory_playerID']."
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
}

query("COMMIT") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());


// Log- und Ausgabe-Nachricht generieren
$message = "Cronjob erfolgreich";

if($countAllyWechsel OR $countOdRequest) {
	$messageAdd = array();
	
	if($countOdRequest) {
		$messageAdd[] = $countOdRequest.' Spielerprofile aktualisiert';
	}
	
	if($countAllyWechsel) {
		$messageAdd[] = $countAllyWechsel.' Allianzwechsel eingetragen';
	}
	
	$message .= '. '.implode(", ", $messageAdd);
}

cronlog(1, $message);

echo $message;


?>