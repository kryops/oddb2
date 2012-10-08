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

// nicht installiert
if(!@include('../config/global.php')) {
	die('Die ODDB wurde noch nicht installiert!');
}

$gconfig = $config;
define('GLOBPREFIX', $config['mysql_globprefix']);

include '../config/dbs.php';


// falscher Sicherheitsschlüssel
if(!isset($_GET['key']) OR $_GET['key'] != $config['key']) {
	die('Sicherheitscode falsch!');
}

// Instanz-Array umformen
if(!$dbs) $dbs = array(1=>'');
$dbs = array_keys($dbs);

// MySQL-Verbindung
$mysql_conn = new mysql;

// Caching initialisieren
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
	$query = query("
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
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Eintrag aus den Invasionen löschen
	$query = query("
		DELETE FROM ".$prefix."invasionen
		WHERE
			invasionenID = ".$id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// InvaLog-Eintrag
	$query = query("
		INSERT INTO ".$prefix."invasionen_log
		SET
			invalog_invasionenID = ".$id.",
			invalogTime = ".time().",
			invalog_playerID = 0,
			invalogText = '".mysql_real_escape_string($log)."'
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}


//
// odrequest-Statistik leeren
//
if(CACHING) {
	$cache->removeglobal('odrequest');
}


// Instanzen durchgehen
foreach($dbs as $instance) {
	
	$prefix = mysql::getPrefix($instance);
	
	//
	// Log löschen
	//
	$query = query("
		DELETE FROM ".$prefix."log
		WHERE
			logTime < ".(time()-86400*$config['logging_time'])."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// veraltete Kolonisationen löschen
	//
	
	// Inhaber auf unbekannt setzen
	$query = query("
		SELECT
			invasionen_planetenID
		FROM
			".$prefix."invasionen
			LEFT JOIN ".$prefix."planeten
				ON planetenID = invasionen_planetenID
			LEFT JOIN ".$prefix."systeme
				ON systemeID = invasionen_systemeID
		WHERE
			invasionenTyp = 5
			AND invasionenEnde < ".time()."
			AND systemeUpdate < invasionenEnde
			AND planeten_playerID = 0
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$kolo_planeten = array();
	
	while($row = mysql_fetch_assoc($query)) {
		$kolo_planeten[] = $row['invasionen_planetenID'];
	}
	
	if(count($kolo_planeten)) {
		query("
			UPDATE ".$prefix."planeten
			SET
				planeten_playerID = -1
			WHERE
				planetenID IN(".implode(",", $kolo_planeten).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	foreach($kolo_planeten as $plid) {
		query("
			INSERT INTO ".$prefix."planeten_history
			SET
				history_planetenID = ".$plid.",
				history_playerID = -1,
				historyLast = 0,
				historyTime = ".time()."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// alte Kolonisationen löschen
	$query = query("
		DELETE FROM ".$prefix."invasionen
		WHERE
			invasionenTyp = 5
			AND invasionenEnde < ".time()."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	//
	// veraltete Invasionen und andere Aktionen ins Archiv verschieben,
	// nachdem sie zu Ende sind, spätestens nach 7 Tagen
	//
	$query = query("
		SELECT
			invasionenID
		FROM ".$prefix."invasionen
		WHERE
			(invasionenEnde < ".time()." AND invasionenEnde != 0)
			OR invasionenTime < ".(time()-604800)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		inva_archiv($row['invasionenID'], 'Die Aktion wurde automatisch ins Archiv verschoben');
	}
	
	//
	// eigene Allianz für alle auf Status Meta setzen
	//
	$query = query("
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
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		if($row['statusStatus'] == NULL) {
			query("
				INSERT INTO ".$prefix."allianzen_status
				SET
					statusDBAllianz = ".$row['user_allianzenID'].",
					status_allianzenID = ".$row['user_allianzenID'].",
					statusStatus = ".$status_meta."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		else {
			query("
				UPDATE ".$prefix."allianzen_status
				SET
					statusStatus = ".$status_meta."
				WHERE
					statusDBAllianz = ".$row['user_allianzenID']."
					AND status_allianzenID = ".$row['user_allianzenID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	mysql_free_result($query);
	
	//
	// systemeAllianzen aktualisieren
	//
	$query = query("
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
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
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
			query("
				UPDATE ".$prefix."systeme
				SET
					systemeAllianzen = '".$allies."'
				WHERE
					systemeID = ".$row['planeten_systemeID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	mysql_free_result($query);
	
	//
	// 7 Tage alte Toxxrouten löschen
	//
	query("
		DELETE FROM
			".$prefix."routen
		WHERE
			routenListe = 2
			AND routenDate < ".(time()-604800)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// veraltete Sprunggeneratoren löschen
	//
	if($config['sprunggenerator_del']) {
		// Planeten-IDs abfragen
		$ids = array();
		
		$query = query("
			SELECT
				myrigates_planetenID
			FROM
				".$prefix."myrigates
			WHERE
				myrigatesSprung > 0
				AND myrigatesSprung < ".(time()-86400*$config['sprunggenerator_del'])."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$ids[] = $row['myrigates_planetenID'];
		}
		
		mysql_free_result($query);
		
		if(count($ids)) {
			
			$ids = implode(',', $ids);
			
			// aus der Myrigate-Tabelle löschen
			query("
				DELETE FROM
					".$prefix."myrigates
				WHERE
					myrigates_planetenID IN(".$ids.")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// aus der Planeten-Tabelle löschen
			query("
				UPDATE
					".$prefix."planeten
				SET
					planetenMyrigate = 0
				WHERE
					planetenID IN(".$ids.")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	//
	// gelöschte oder 3 Tage alte BBS und Terraformer löschen
	// 
	
	query("
		DELETE FROM ".$prefix."planeten_schiffe
		WHERE
			(schiffeBergbau IS NULL AND schiffeTerraformer IS NULL)
			OR
			(schiffeBergbauUpdate < ".(time()-259200)." AND schiffeTerraformerUpdate < ".(time()-259200).")
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	query("
		UPDATE ".$prefix."planeten_schiffe
		SET
			schiffeBergbau = NULL
		WHERE
			schiffeBergbauUpdate < ".(time()-259200)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	query("
		UPDATE ".$prefix."planeten_schiffe
		SET
			schiffeTerraformer = NULL
		WHERE
			schiffeTerraformerUpdate < ".(time()-259200)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// Tabellen nach ID sortieren (galaxien, systeme, planeten, player)
	//
	query("
		ALTER TABLE ".$prefix."galaxien
		ORDER BY galaxienID
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	query("
		ALTER TABLE ".$prefix."systeme
		ORDER BY systemeID
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	query("
		ALTER TABLE ".$prefix."planeten
		ORDER BY planetenID
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

//
// neue Spieler eintragen
//
$query = query("
	SELECT
		MIN(playerID) AS minid
	FROM
		".GLOBPREFIX."player
	WHERE
		playerID > 1000
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

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
	$query = query("
		SELECT
			playerID
		FROM
			".GLOBPREFIX."player
		WHERE
			playerID > 1000
		ORDER BY
			playerID ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
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
		
		$odrequest_delete = false;
		
		// Spieler nicht gefunden: Leeren Spieler eintragen
		if(!odrequest($id, false, true) AND $odrequest_delete) {
			query("
				INSERT INTO
					".GLOBPREFIX."player
				SET
					playerID = ".$id.",
					playerDeleted = 1
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
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
query("
	ALTER TABLE ".GLOBPREFIX."player
	ORDER BY playerID
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());


echo "Cronjob erfolgreich.";

?>