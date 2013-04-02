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
 * - bei fehlgeschlagenen Importen aufräumen
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
		INSERT IGNORE INTO ".$prefix."invasionen_archiv
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
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Eintrag aus den Invasionen löschen
	$query = query("
		DELETE FROM ".$prefix."invasionen
		WHERE
			invasionenID = ".$id."
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// InvaLog-Eintrag
	$query = query("
		INSERT INTO ".$prefix."invasionen_log
		SET
			invalog_invasionenID = ".$id.",
			invalogTime = ".time().",
			invalog_playerID = 0,
			invalogText = '".mysql_real_escape_string($log)."'
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}


// Statistik-Counter
$countInvaArchiv = 0;
$countOdRequest = 0;
$countToxxroute = 0;
$countSprung = 0;
$countBbsTf = 0;


//
// odrequest-Statistik leeren
//
if(CACHING) {
	$cache->removeglobal('odrequest');
}


//
// liegen gebliebene Importe löschen
//
if($dir = @opendir('../admin/cache')) {
	while($file = readdir($dir)) {
		if(substr($file, 0, 6) == 'import') {
			@unlink('../admin/cache/'.$file);
		}
	}
	
	closedir($dir);
}

//
// altes Cronjob-Log leeren
//
$query = query("
	DELETE FROM
		".GLOBPREFIX."cronjobs
	WHERE
		cronjobsTime < ".(time()-604800)."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());


// Instanzen durchgehen
foreach($dbs as $instance) {
	
	$prefix = mysql::getPrefix($instance);
	
	// Transaktion starten
	query("START TRANSACTION") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	//
	// Log löschen
	//
	$query = query("
		DELETE FROM ".$prefix."log
		WHERE
			logTime < ".(time()-86400*$config['logging_time'])."
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
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
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$kolo_planeten = array();
	
	while($row = mysql_fetch_assoc($query)) {
		$kolo_planeten[] = $row['invasionen_planetenID'];
		$countInvaArchiv++;
	}
	
	if(count($kolo_planeten)) {
		query("
			UPDATE ".$prefix."planeten
			SET
				planeten_playerID = -1
			WHERE
				planetenID IN(".implode(",", $kolo_planeten).")
		") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	foreach($kolo_planeten as $plid) {
		query("
			INSERT INTO ".$prefix."planeten_history
			SET
				history_planetenID = ".$plid.",
				history_playerID = -1,
				historyLast = 0,
				historyTime = ".time()."
		") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// alte Kolonisationen löschen
	$query = query("
		DELETE FROM ".$prefix."invasionen
		WHERE
			invasionenTyp = 5
			AND invasionenEnde < ".time()."
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
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
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$countInvaArchiv++;
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
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		if($row['statusStatus'] == NULL) {
			query("
				INSERT INTO ".$prefix."allianzen_status
				SET
					statusDBAllianz = ".$row['user_allianzenID'].",
					status_allianzenID = ".$row['user_allianzenID'].",
					statusStatus = ".$status_meta."
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		else {
			query("
				UPDATE ".$prefix."allianzen_status
				SET
					statusStatus = ".$status_meta."
				WHERE
					statusDBAllianz = ".$row['user_allianzenID']."
					AND status_allianzenID = ".$row['user_allianzenID']."
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
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
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
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
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
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
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$countToxxroute += mysql_affected_rows();
	
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
		") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$ids[] = $row['myrigates_planetenID'];
			$countSprung++;
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
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// aus der Planeten-Tabelle löschen
			query("
				UPDATE
					".$prefix."planeten
				SET
					planetenMyrigate = 0
				WHERE
					planetenID IN(".$ids.")
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	//
	// gelöschte oder 7 Tage alte BBS und Terraformer löschen
	// 
	
	query("
		DELETE FROM ".$prefix."planeten_schiffe
		WHERE
			(schiffeBergbau IS NULL AND schiffeTerraformer IS NULL)
			OR
			(schiffeBergbauUpdate < ".(time()-604800)." AND schiffeTerraformerUpdate < ".(time()-604800).")
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$countBbsTf++;
	
	query("
		UPDATE ".$prefix."planeten_schiffe
		SET
			schiffeBergbau = NULL
		WHERE
			schiffeBergbauUpdate < ".(time()-604800)."
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	query("
		UPDATE ".$prefix."planeten_schiffe
		SET
			schiffeTerraformer = NULL
		WHERE
			schiffeTerraformerUpdate < ".(time()-604800)."
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	//
	// Galaxiestatistiken bei abgebrochenen Importen neu berechnen 
	//
	
	// Galaxien aktualisieren
	query("
		UPDATE
			".$prefix."galaxien
		SET
			galaxienSysScanned = (
				SELECT
					COUNT(*)
				FROM
					".$prefix."systeme
				WHERE
					systemeUpdate > 0
					AND systeme_galaxienID = galaxienID
			)
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	// Transaktion beenden
	query("COMMIT") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
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
	
	// Transaktion starten
	query("START TRANSACTION") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
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
	") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
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
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		$c++;
		$countOdRequest++;
		
		// bei 100 abbrechen
		if($c > 100) {
			break;
		}
	}
	
	// Transaktion beenden
	query("COMMIT") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}


// Log- und Ausgabe-Nachricht generieren
$message = "Cronjob erfolgreich";

if($countBbsTf OR $countInvaArchiv OR $countOdRequest OR $countSprung OR $countToxxroute) {
	$messageAdd = array();
	
	if($countBbsTf) {
		$messageAdd[] = $countBbsTf.' Bergbauschiffe/Terraformer entfernt';
	}
	
	if($countInvaArchiv) {
		$messageAdd[] = $countInvaArchiv.' Invasionen archiviert';
	}
	
	if($countOdRequest) {
		$messageAdd[] = $countOdRequest.' Spielerprofile eingetragen';
	}
	
	if($countSprung) {
		$messageAdd[] = $countSprung.' Sprunggeneratoren entfernt';
	}
	
	if($countToxxroute) {
		$messageAdd[] = $countToxxroute.' Toxxrouten entfernt';
	}
	
	$message .= '. '.implode(", ", $messageAdd);
}

cronlog(2, $message);

echo $message;

?>