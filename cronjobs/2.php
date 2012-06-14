<?php

/**
 * cronjobs/1.php
 * Laufzeit: täglich in der Downtime
 *
 * - veraltete Log-Einträge löschen
 * - veraltete Invasionen ins Archiv verschieben
 * - eigene Allianz für alle auf Status Meta setzen
 * - systemeAllianzen aktualisieren
 * - veraltete Toxxrouten löschen
 * - veraltete Sprunggeneratoren löschen
 * - veraltete BBS und Terraformer löschen
 * - Tabellen nach ID sortieren (galaxien, systeme, planeten, player)
 */

// alle Fehlermeldungen aktivieren
error_reporting(E_ALL);

// Rendertime-Messung starten
$time = microtime(true);

// Zeitlimit erhöhen (15 Minuten)
set_time_limit(900);

// User-Abort deaktivieren, um eventuelle Fehler zu verhinden
ignore_user_abort(true);

// Query-Zähler initialisieren
$queries = 0;

// Sicherheitskonstante
define('ODDB', true);
define('ODDBADMIN', true);

// Zeitzone setzen -> Performance
date_default_timezone_set('Europe/Berlin');

// HTTP-Cache-Header ausgeben (Caching der Seite verhindern)
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

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

// Caching initialisieren
if(function_exists('apc_fetch') AND !$config['caching']) {
	$config['caching'] = 1;
}
$cache = new cache();


/**
 * Invasion ins Archiv verschieben
 * @param $id int Invasions-ID
 * @param $log string log-Eintrag
 */
function inva_archiv($id, $log) {
	global $prefix, $conn;
	
	// Daten sichern
	$id = (int)$id;
	
	// ins Archiv kopieren
	$query = mysql_query("
		INSERT INTO ".$prefix."invasionen_archiv
		SELECT
			invasionenID,
			invasionenTime,
			invasionen_planetenID,
			invasionen_systemeID,
			invasionen_playerID,
			invasionenTyp,
			invasionenFremd,
			invasionenAggressor,
			invasionenEnde,
			invasionenSchiffe,
			invasionenKommentar
		FROM ".$prefix."invasionen
		WHERE
			invasionenID = ".$id."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Eintrag aus den Invasionen löschen
	$query = mysql_query("
		DELETE FROM ".$prefix."invasionen
		WHERE
			invasionenID = ".$id."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// InvaLog-Eintrag
	$query = mysql_query("
		INSERT INTO ".$prefix."invasionen_log
		SET
			invalog_invasionenID = ".$id.",
			invalogTime = ".time().",
			invalog_playerID = 0,
			invalogText = '".mysql_real_escape_string($log)."'
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}


//
// odrequest-Statistik leeren
//
if(CACHING) {
	$cache->removeglobal('odrequest');
}


// Instanzen durchgehen
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
	// Log löschen
	//
	$query = mysql_query("
		DELETE FROM ".$prefix."log
		WHERE
			logTime < ".(time()-86400*$config['logging_time'])."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// veraltete Kolonisationen löschen
	//
	$query = mysql_query("
		DELETE FROM ".$prefix."invasionen
		WHERE
			invasionenTyp = 5
			AND invasionenEnde < ".time()."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// veraltete Invasionen und andere Aktionen ins Archiv verschieben,
	// nachdem sie zu Ende sind, spätestens nach 7 Tagen
	//
	$query = mysql_query("
		SELECT
			invasionenID
		FROM ".$prefix."invasionen
		WHERE
			(invasionenEnde < ".time()." AND invasionenEnde != 0)
			OR invasionenTime < ".(time()-604800)."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		inva_archiv($row['invasionenID'], 'Die Aktion wurde automatisch ins Archiv verschoben');
	}
	
	//
	// eigene Allianz für alle auf Status Meta setzen
	//
	$query = mysql_query("
		SELECT
			DISTINCT user_allianzenID,
			statusStatus
		FROM
			".$prefix."user
			LEFT JOIN ".$prefix."allianzen_status
				ON statusDBAllianz = user_allianzenID
				AND status_allianzenID = user_allianzenID
		WHERE
			user_allianzenID > 0
			AND (statusStatus IS NULL
				OR statusStatus != ".$status_meta.")
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		if($row['statusStatus'] == NULL) {
			mysql_query("
				INSERT INTO ".$prefix."allianzen_status
				SET
					statusDBAllianz = ".$row['user_allianzenID'].",
					status_allianzenID = ".$row['user_allianzenID'].",
					statusStatus = ".$status_meta."
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		else {
			mysql_query("
				UPDATE ".$prefix."allianzen_status
				SET
					statusStatus = ".$status_meta."
				WHERE
					statusDBAllianz = ".$row['user_allianzenID']."
					AND status_allianzenID = ".$row['user_allianzenID']."
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	mysql_free_result($query);
	
	//
	// systemeAllianzen aktualisieren
	//
	$query = mysql_query("
		SELECT
			planeten_systemeID,
			systemeAllianzen,
			GROUP_CONCAT(DISTINCT CAST(player_allianzenID as CHAR) ORDER BY planetenID) AS allianzen,
			GROUP_CONCAT(DISTINCT CAST(planeten_playerID as CHAR) ORDER BY NULL) AS player
		FROM
			".$prefix."planeten
			LEFT JOIN ".$prefix."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
		WHERE
			systemeUpdate > 0
			AND systemeUpdate < ".(time()-172800)."
		GROUP BY planeten_systemeID
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$allies = explode(',', $row['allianzen']);
		$player = explode(',', $row['player']);
		// freie Planeten
		if(in_array(0, $player)) {
			$allies[] = -1;
		}
		
		// unbekannte Planeten
		if(in_array(-1, $player) OR in_array(-2, $player) OR in_array(-3, $player)) {
			$allies[] = -2;
		}
		
		$allies = '+'.implode('++', $allies).'+';
		if($allies != $row['systemeAllianzen']) {
			mysql_query("
				UPDATE ".$prefix."systeme
				SET
					systemeAllianzen = '".$allies."'
				WHERE
					systemeID = ".$row['planeten_systemeID']."
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	mysql_free_result($query);
	
	//
	// 7 Tage alte Toxxrouten löschen
	//
	mysql_query("
		DELETE FROM
			".$prefix."routen
		WHERE
			routenListe = 2
			AND routenDate < ".(time()-604800)."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// veraltete Sprunggeneratoren löschen
	//
	if($config['sprunggenerator_del']) {
		// Planeten-IDs abfragen
		$ids = array();
		
		$query = mysql_query("
			SELECT
				myrigates_planetenID
			FROM
				".$prefix."myrigates
			WHERE
				myrigatesSprung > 0
				AND myrigatesSprung < ".(time()-86400*$config['sprunggenerator_del'])."
		", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$ids[] = $row['myrigates_planetenID'];
		}
		
		mysql_free_result($query);
		
		if(count($ids)) {
			
			$ids = implode(',', $ids);
			
			// aus der Myrigate-Tabelle löschen
			mysql_query("
				DELETE FROM
					".$prefix."myrigates
				WHERE
					myrigates_planetenID IN(".$ids.")
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// aus der Planeten-Tabelle löschen
			mysql_query("
				UPDATE
					".$prefix."planeten
				SET
					planetenMyrigate = 0
				WHERE
					planetenID IN(".$ids.")
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	//
	// gelöschte oder 3 Tage alte BBS und Terraformer löschen
	// 
	
	mysql_query("
		DELETE FROM ".$prefix."planeten_schiffe
		WHERE
			(schiffeBergbau IS NULL AND schiffeTerraformer IS NULL)
			OR
			(schiffeBergbauUpdate < ".(time()-259200)." AND schiffeTerraformerUpdate < ".(time()-259200).")
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	mysql_query("
		UPDATE ".$prefix."planeten_schiffe
		SET
			schiffeBergbau = NULL
		WHERE
			schiffeBergbauUpdate < ".(time()-259200)."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	mysql_query("
		UPDATE ".$prefix."planeten_schiffe
		SET
			schiffeTerraformer = NULL
		WHERE
			schiffeTerraformerUpdate < ".(time()-259200)."
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// Tabellen nach ID sortieren (galaxien, systeme, planeten, player)
	//
	mysql_query("
		ALTER TABLE ".$prefix."galaxien
		ORDER BY galaxienID
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	mysql_query("
		ALTER TABLE ".$prefix."systeme
		ORDER BY systemeID
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	mysql_query("
		ALTER TABLE ".$prefix."planeten
		ORDER BY planetenID
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

//
// neue Spieler eintragen
//
$query = mysql_query("
	SELECT
		MIN(playerID) AS minid
	FROM
		".GLOBPREFIX."player
	WHERE
		playerID > 1000
", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$data = mysql_fetch_assoc($query);

$minid = $data['minid'];


// OD-Konnektivität testen
$file = 'http://www.omega-day.com/game/states/live_state.php?userid=999&world='.ODWORLD;
$connection = @fopen($file,'r');
$od_up = false;

if($connection) {
	$buffer = fread($connection, 4096);
	fclose($connection);
	
	
	// Internal Server Error
	if(stripos($buffer, 'Internal Server Error') === false) {
		// OD-MySQL-Fehler umgehen
		if(strpos($buffer, '<b>68</b><br />') !== false) {
			$buffer = preg_replace('#^(.*)<b>68</b><br />#Uis', '', $buffer);
		}
		
		// als Array parsen
		parse_str($buffer, $oddata);
		
		// bei ungewöhnlicher Rückgabe abbrechen
		if(isset($oddata['name'], $oddata['version'])) {
			$od_up = true;
		}
	}
}


if($minid AND $od_up) {
	
	$counter = $minid;
	$ids = array();
	
	// Spieler abfragen
	$query = mysql_query("
		SELECT
			playerID
		FROM
			".GLOBPREFIX."player
		WHERE
			playerID > 1000
		ORDER BY
			playerID ASC
	", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		
		// nicht eingetragene IDs hinzufügen
		for($i=$counter; $i < $row['playerID']; $i++) {
			$ids[] = $i;
		}
		
		// Zähler erhöhen
		$counter = $row['playerID']+1;
	}
	
	$c = 0;
	foreach($ids as $id) {
		
		// Spieler nicht gefunden: Leeren Spieler eintragen
		if(!odrequest($id, false, 0, true)) {
			mysql_query("
				INSERT INTO
					".GLOBPREFIX."player
				SET
					playerID = ".$id.",
					playerDeleted = 1
			", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		$c++;
		
		// bei 100 abbrechen
		if($c > 100) {
			break;
		}
	}
}

//
// globale Tabellen sortieren
//
mysql_query("
	ALTER TABLE ".GLOBPREFIX."player
	ORDER BY playerID
", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

?>