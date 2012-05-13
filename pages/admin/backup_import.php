<?php
/**
 * pages/admin/backup_import.php
 * Verwaltung -> Import/Export -> importieren
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


/**
 * Templateklasse
 */

class template_import {
	// Seitentitel (Fenster)
	public $name = 'Import';
	// Seiteninhalt
	public $content = '';
	// Fehlermeldung
	public $error = '';
	// JavaScript
	public $script = '';
	// Upload-Formular anzeigen?
	public $form = true;
	
	/**
	 * Upload-Formular zurückgeben
	 */
	public function get_form() {
		return '
<form action="index.php?p=admin&amp;sp=backup_import" method="post" enctype="multipart/form-data" class="center">
Daten-Archiv: 
<input type="file" name="import" />
<br /><br />
<input type="submit" class="button" value="importieren" style="width:120px;margin:20px 0px" onclick="this.style.display=\'none\';document.getElementById(\'importajax\').style.display=\'inline\';" />
<img src="img/layout/ajax.gif" style="width:24px;height:24px;display:none" id="importajax" />
</form>';
	}
	
	/**
	 * ausgeben
	 */
	public function output() {
		global $time_start, $queries;
		
		// Fehler
		if($this->error != '') {
			$this->content = '<div class="error center">'.$this->error.'</div><br /><br />';
			$this->script = '';
		}
		
		echo '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" type="text/css" href="css/main.css'.FILESTAMP.'" />
<link rel="shortcut icon" href="favicon.ico" />
<title>'.$this->name.' - ODDB</title>
</head>
<body>

<div class="content fcbox" style="margin:8px;font-size:13px;padding:10px;">
<div class="hl2">Daten importieren</div>
<br />
'.$this->content.'
'.($this->form ? $this->get_form() : '').'
</div>

'.($this->script != '' ? '<script type="text/javascript">
'.$this->script.'
</script>' : '').'

<!-- '.number_format(microtime(true)-$time_start, 6).'s, '.$queries.' Queries, '.(function_exists('memory_get_peak_usage') ? ressmenge(@memory_get_peak_usage(true)) : ressmenge(@memory_get_usage(true))).' Bytes RAM -->
</body>
</html>';
	}
}

$tmpl = new template_import;


// keine Berechtigung
if(!$user->rechte['verwaltung_backup']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->form = false;
	$tmpl->output();
}

// Import starten
else if(isset($_FILES['import'])) {
	// Memory-Limit erhöhen
	@ini_set('memory_limit', '768M');
	@set_time_limit(600);
	
	// Fehler beim Upload
	if($_FILES['import']['error']) {
		$tmpl->error = 'Fehler beim Upload!';
		$tmpl->output();
		die();
	}
	
	// 1 Minute Mindestabstand
	else if($cache->get('oddb_import') !== false) {
		$tmpl->error = 'Du kannst maximal 1x pro Minute Daten importieren!';
		$tmpl->output();
		die();
	}
	
	// max_input_vars-Problem
	@ini_set('max_input_vars', 65536);
	
	// Dateiinhalt auslesen
	$data = file_get_contents($_FILES['import']['tmp_name']);
	
	// dekomprimieren und JSON dekodieren
	if(($data = @gzuncompress($data)) === false OR ($data = @json_decode($data, true)) === false OR !isset($data['systems'], $data['planets'])) {
		$tmpl->error = 'Ung&uuml;ltige Datei!';
		$tmpl->output();
		die();
	}
	
	// Cache setzen
	$cache->set('oddb_import', 1, 60);
	
	// Log-Eintrag
	if($config['logging'] >= 1) {
		insertlog(25, 'importiert Daten in die Datenbank');
	}
	
	// Counter
	$syscount = 0;
	$plcount = 0;
	
	// Daten-Container
	$sys = array();
	$pl = array();
	$mgates = array();
	
	// System-Aktualität auslesen
	$query = query("
		SELECT
			systemeID,
			systemeUpdate
		FROM
			".PREFIX."systeme
		WHERE
			systemeUpdateHidden > 0
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_array($query)) {
		$sys[$row[0]] = (int)$row[1];
	}
	
	mysql_free_result($query);
	
	// Planeten-Aktualität und Inhaber auslesen
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			planetenUpdateOverview,
			planetenUpdate,
			
			playerRasse
		FROM
			".PREFIX."planeten
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_array($query)) {
		$pl[$row[0]] = array(
			(int)$row[1],
			(int)$row[2],
			(int)$row[3],
			(int)$row[4]
		);
	}
	
	mysql_free_result($query);
	
	// Myrigates auslesen
	$query = query("
		SELECT
			myrigates_planetenID
		FROM
			".PREFIX."myrigates
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$mgates[$row['myrigates_planetenID']] = true;
	}
	
	mysql_free_result($query);
	
	
	// Systemdaten abgleichen
	foreach($data['systems'] as $id=>$row) {
		$sysupd = (int)$row[0];
		unset($row[0]);
		
		// nur aktuellere Systeme abgleichen
		if(isset($sys[$id]) AND $sys[$id] < $sysupd) {
			// System aktualisieren
			query("
				UPDATE
					".PREFIX."systeme
				SET
					systemeUpdateHidden = ".$sysupd.",
					systemeUpdate = ".$sysupd."
				WHERE
					systemeID = ".(int)$id."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Planeten durchgehen
			foreach($row as $plid=>$plrow) {
				if(count($plrow) == 11 AND isset($pl[$plid])) {
					// bei verschleierten Planeten Inhaber nicht immer übernehmen
					$saveowner = true;
					// Planet verschleiert
					if($plrow[1] == -2 OR $plrow[1] == -3) {
						$saveowner = false;
						
						// eingetragener Inhaber ganz unbekannt oder Planet vorher frei
						if($pl[$plid][0] == 0 OR $pl[$plid][0] == -1) {
							$saveowner = true;
						}
						// Altrasse / Lux hat gewechselt
						if($plrow[1] == -2 AND ($pl[$plid][0] == -3 OR $pl[$plid][3] != 10)) {
							$saveowner = true;
						}
						// Altrasse-Planet war als Lux eingetragen
						else if($plrow[1] == -3 AND ($pl[$plid][0] == -2 OR $pl[$plid][3] == 10)) {
							$saveowner = true;
						}
					}
					
					
					// Planet aktualisieren
					query("
						UPDATE
							".PREFIX."planeten
						SET
							planetenName = '".escape($plrow[0])."',
							".($saveowner ? "planeten_playerID = ".(int)$plrow[1]."," : "")."
							planetenGroesse = ".(int)$plrow[2].",
							planetenBevoelkerung = ".(int)$plrow[3].",
							planetenNatives = ".(int)$plrow[4].",
							planetenRWErz = ".(int)$plrow[5].",
							planetenRWWolfram = ".(int)$plrow[6].",
							planetenRWKristall = ".(int)$plrow[7].",
							planetenRWFluor = ".(int)$plrow[8].",
							planetenMyrigate = ".(int)$plrow[9].",
							planetenRiss = ".(int)$plrow[10]."
						WHERE
							planetenID = ".(int)$plid."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Myrigate eintragen
					if($plrow[9] AND !isset($mgates[$plid])) {
						// Galaxie abfragen
						$query = query("
							SELECT
								systeme_galaxienID
							FROM
								".PREFIX."systeme
							WHERE
								systemeID = ".(int)$id."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						$gala = mysql_fetch_array($query);
						$gala = $gala[0];
						
						mysql_free_result($query);
						
						query("
							INSERT INTO ".PREFIX."myrigates
							SET
								myrigates_planetenID = ".(int)$plid.",
								myrigates_galaxienID = ".(int)$gala.",
								myrigatesSprung = ".($plrow[9] == 2 ? "1" : "0")."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
					// Myrigate entfernen
					else if(!$plrow[9] AND isset($mgates[$plid])) {
						query("
							DELETE FROM ".PREFIX."myrigates
							WHERE
								myrigates_planetenID = ".(int)$plid."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
					
					// Inhaber-History
					if($saveowner AND $pl[$plid][0] != $plrow[1]) {
						query("
							INSERT INTO ".PREFIX."planeten_history
							SET
								history_planetenID = ".(int)$plid.",
								history_playerID = ".(int)$plrow[1].",
								historyLast = ".(int)$pl[$plid][0].",
								historyTime = ".$sysupd."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
				}
			}
			
			// Zähler erhöhen
			$syscount++;
		}
		// System älter -> nur verschleierte Planeten aktualisieren
		else if(isset($sys[$id])) {
			// Planeten durchgehen
			foreach($row as $plid=>$plrow) {
				// valider Datensatz, Planet eingetragen, verschleiert in der DB, im Export nicht
				if(count($plrow) == 11 AND isset($pl[$plid]) AND $plrow[1] > 0 AND ($pl[$plid][0] == -2 OR $pl[$plid][0] == -3)) {
					// Planet aktualisieren
					query("
						UPDATE
							".PREFIX."planeten
						SET
							planeten_playerID = ".(int)$plrow[1]."
						WHERE
							planetenID = ".(int)$plid."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					query("
						INSERT INTO ".PREFIX."planeten_history
						SET
							history_planetenID = ".(int)$plid.",
							history_playerID = ".(int)$plrow[1].",
							historyLast = ".(int)$pl[$plid][0].",
							historyTime = ".$sysupd."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
			}
		}
		
		// bearbeitete Systeme löschen
		unset($data['systems'][$id]);
	}
	
	// Variablen löschen, um Speicherplatz zu sparen
	unset($data['systems']);
	unset($sys);
	unset($mgates);
	
	// Planetendaten abgleichen
	foreach($data['planets'] as $id=>$row) {
		// nur existente, vollständige, aktuellere Planeten abgleichen
		if(count($row) == 18 AND isset($pl[$id]) AND $pl[$id][1] < $row[0]) {
			// Oberflächen-Scan aktueller
			$upd = "
				planetenKategorie = ".(int)$row[2].",
				planetenGebPlanet = '".escape($row[3])."',
				planetenGebOrbit = '".escape($row[4])."',
				planetenOrbiter = ".(int)$row[5].",
				planetenRMErz = ".(int)$row[11].",
				planetenRMMetall = ".(int)$row[12].",
				planetenRMWolfram = ".(int)$row[13].",
				planetenRMKristall = ".(int)$row[14].",
				planetenRMFluor = ".(int)$row[15].",
				planetenRMGesamt = ".((int)$row[11]+(int)$row[12]+(int)$row[13]+(int)$row[14]+(int)$row[15]).",
				planetenUpdateOverview = ".(int)$row[0];
				
			
			// bei vollem Scan auch Ressproduktion, Forschung und Industrie übertragen
			if($row[1] > $pl[$id][2]) {
				$upd = "
				planetenRPErz = ".(int)$row[6].",
				planetenRPMetall = ".(int)$row[7].",
				planetenRPWolfram = ".(int)$row[8].",
				planetenRPKristall = ".(int)$row[9].",
				planetenRPFluor = ".(int)$row[10].",
				planetenRPGesamt = ".((int)$row[6]+(int)$row[7]+(int)$row[8]+(int)$row[9]+(int)$row[10]).",
				planetenForschung = ".(int)$row[16].",
				planetenIndustrie = ".(int)$row[17].",
				planetenUpdate = ".(int)$row[1].",".$upd;
			}
			
			// Planet aktualisieren
			query("
				UPDATE
					".PREFIX."planeten
				SET
					".$upd."
				WHERE
					planetenID = ".(int)$id."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Counter erhöhen
			$plcount++;
		}
		
		// bearbeitete Planeten löschen
		unset($data['planets'][$id]);
	}
	
	// Variablen löschen um Speicherplatz zu sparen
	unset($data);
	unset($pl);
	
	// Risse zurücksetzen
	query("
		UPDATE
			".PREFIX."planeten
		SET
			planetenRiss = 0
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	query("
		UPDATE
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."myrigates
				ON myrigates_planetenID = planetenID
		SET
			planetenRiss = 1
		WHERE
			myrigatesSprung = 0
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	// Galaxien aktualisieren
	query("
		UPDATE
			".PREFIX."galaxien
		SET
			galaxienSysScanned = (
				SELECT
					COUNT(*)
				FROM
					".PREFIX."systeme
				WHERE
					systemeUpdate > 0
					AND systeme_galaxienID = galaxienID
			)
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	/*
	// DEBUG
	echo count($queries);
	echo '<br />';
	echo ressmenge(memory_get_usage(true)).' Bytes RAM';
	echo '<br />';
	echo number_format(microtime(true)-$time_start, 6).' Sekunden';
	*/
	
	// Formular ausblenden
	$tmpl->form = false;
	
	$tmpl->content = '
<div class="center">
Der Import wurde erfolgreich abgeschlossen.
<br /><br />
'.$syscount.' System- und '.$plcount.' Planetenscans wurden &uuml;bernommen.
</div>
<br /><br />';
	$tmpl->output();
}

// Seiteninhalt
else {
	$tmpl->output();
}

?>