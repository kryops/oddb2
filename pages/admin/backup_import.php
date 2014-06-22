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
<input type="file" name="import" style="background:#161616" />
<br /><br />
oder URL: <input type="text" class="text" name="url" style="width:300px" />
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

<script type="text/javascript" src="js/jquery.js'.FILESTAMP.'"></script>
<script type="text/javascript" src="js/general'.(DEBUG ? '_src' : '').'.js'.FILESTAMP.'"></script>
'.($this->script != '' ? '<script type="text/javascript">
'.$this->script.'
</script>' : '').'

<!-- '.number_format(microtime(true)-$time_start, 6).'s, '.$queries.' Queries, '.(function_exists('memory_get_peak_usage') ? ressmenge(@memory_get_peak_usage(true)) : ressmenge(@memory_get_usage(true))).' Bytes RAM -->

</body>
</html>';
	}
}


class BackupImport {
	
	// Zeitlimit, wie lange ein Importschritt höchstens dauern darf
	public static $import_timelimit = 10;
	
	
	
	
	public static function uploadFile() {
		
		global $cache, $config;
		
		$tmpl = new template_import;
		
		// Memory-Limit erhöhen
		@ini_set('memory_limit', '768M');
		
		// max_input_vars-Problem
		@ini_set('max_input_vars', 65536);
		
		$isUrl = false;
		$fileUrl = trim($_POST['url']);
		
		// Fehler beim Upload
		if($_FILES['import']['error']) {
			if($fileUrl === '') {
				$tmpl->error = 'Fehler beim Upload!';
				$tmpl->output();
				die();				
			}
			// Datei von URL
			else {
				if(substr($fileUrl, 0, 7) !== 'http://') {
					$tmpl->error = 'Ungültige URL! (muss mit http:// beginnen)';
				}
				else if(($data = @file_get_contents($fileUrl)) === false) {
					$tmpl->error = 'Ungültige URL! (konnte Datei nicht laden)';
				}
				else {
					$isUrl = true;
				}
				
				if($tmpl->error) {
					$tmpl->output();
					die();
				}
			}
		}
		
		// 1 Minute Mindestabstand
		else if($cache->get('oddb_import') !== false) {
			$tmpl->error = 'Du kannst maximal alle 30 Sekunden Daten importieren!';
			$tmpl->output();
			die();
		}
		
		// Dateiinhalt auslesen
		if(!$isUrl) {
			$data = file_get_contents($_FILES['import']['tmp_name']);
		}
		
		// dekomprimieren
		if(($data = @gzuncompress($data)) === false) {
			$tmpl->error = 'Ung&uuml;ltige Datei! (Komprimierungs-Fehler)';
			$tmpl->output();
			die();
		}
		
		$data_raw = $data;
		
		/*
		 * Datei validieren
		*/
		$abgleich_runde = 0;
		$abgleich_version = 0;
		
		$data = explode('""""', $data);
		
		// Grunddaten-Import
		if(count($data) < 2) {
			self::importBasicData($data_raw);
			die();
		}
		
		// Datensätze durchgehen
		for($i=0; $i<=1; $i++) {
		
			$val = $data[$i];
		
			// Zeile auswerten
			if(preg_match("/^([A-Z])(\d+)=(.+)$/Uis", $val, $row)) {
					
				// Umgebungsdaten
				if($row[1] == 'C') {
					if($row[2] == 1) {
						$abgleich_runde = $row[3];
					}
					else if($row[2] == 2) {
						$abgleich_version = $row[3];
					}
				}
					
			}
		}
		
		if($abgleich_runde != ODWORLD) {
			$tmpl->error = 'Die Runde des Abgleichs stimmt nicht mit der aktuellen Runde &uuml;berein!';
			$tmpl->output();
			die();
		}
		if($abgleich_version != ABGLEICH_VERSION) {
			$tmpl->error = 'Der Abgleich wurde mit einer anderen, inkompatiblen Version der ODDB erzeugt!';
			$tmpl->output();
			die();
		}
		
		
		// Cache setzen
		$cache->set('oddb_import', 1, 30);
		
		// Log-Eintrag
		if($config['logging'] >= 1) {
			insertlog(25, 'importiert Daten in die Datenbank');
		}
		
		// Datei speichern
		$filename = "import".time().substr(md5(microtime(true)), 0, 6);
		
		$fp = fopen('./admin/cache/'.$filename, "w");
		fwrite($fp, $data_raw);
		fclose($fp);
		
		$tmpl->form = false;
		
		$tmpl->content = '
		<div id="content" class="center">
			<p>Import läuft. <b>Bitte dieses Fenster nicht schlie&szlig;en!</b></p>
			<br />
			<div class="balken" id="import_balken" style="width:300px;height:15px;margin:auto">
			<div class="balkenfill" style="width:0px"></div>
			</div>
			<br />
			<p id="import_status">&nbsp;</p>
			<img src="img/layout/ajax.gif" style="width:24px;height:24px;" />
		</div>
	';
		
		$tmpl->script = '
window.setTimeout(function() {
	ajaxcall(\'index.php?p=admin&sp=backup_import&import='.$filename.'\', false, {\'offset\':2, \'sys\':0, \'pl\':0, \'sysmin\':0, \'plmin\':0, \'pladded\':0}, false);
}, 250);';
		
		$tmpl->output();
		
	}
	
	
	public static function importData() {
		
		$tmpl = new template;
		
		// Memory-Limit erhöhen
		@ini_set('memory_limit', '768M');
		
		// max_input_vars-Problem
		@ini_set('max_input_vars', 65536);
		
		if(substr($_GET['import'], 0, 6) != 'import' OR !preg_match("/^[a-zA-Z0-9]+$/Uis", $_GET['import'])) {
			$tmpl->error = 'Ungültiger Dateiname!';
			$tmpl->output();
			die();
		}
		
		if(!file_exists('./admin/cache/'.$_GET['import'])) {
			$tmpl->error = 'Die Datei existiert nicht!';
			$tmpl->output();
			die();
		}
		
		$offset = (int)$_POST['offset'];
		
		// Statistiken
		if(!isset($_POST['sys'])) {
			$_POST['sys'] = 0;
		}
		
		if(!isset($_POST['pl'])) {
			$_POST['pl'] = 0;
		}
		
		$syscount = (int)$_POST['sys'];
		$plcount = (int)$_POST['pl'];
		
		
		// Systeme und Planeten, die schon abgeglichen wurden, nicht mehr auslesen
		if(isset($_POST['sysmin'])) {
			$sysmin = (int)$_POST['sysmin'];
		}
		else {
			$sysmin = 0;
		}
		
		if(isset($_POST['plmin'])) {
			$plmin = (int)$_POST['plmin'];
		}
		else {
			$plmin = 0;
		}
		
		if(isset($_POST['pladded'])) {
			$pladded = (int)$_POST['pladded'];
		}
		else {
			$pladded = 0;
		}
		
		
		// Dateiinhalt auslesen
		$data = file_get_contents('./admin/cache/'.$_GET['import']);
		
		$data = explode('""""', $data);
		
		$count = count($data);
		
		// Ungültige Daten
		if($count < 2) {
			$tmpl->error = 'Ung&uuml;ltige Datei!';
			$tmpl->output();
			die();
		}
		
		if($offset >= $count) {
			$tmpl->error = 'Ung&uuml;ltige Startposition!';
			$tmpl->output();
			die();
		}
		
		// Daten-Container
		$sys = array();
		$pl = array();
		$mgates = array();
		
		
		// System-Aktualität auslesen
		$query = query("
			SELECT
				systemeID,
				systemeUpdateHidden,
				systemeUpdate
			FROM
				".PREFIX."systeme
			WHERE
				systemeID > ".$sysmin."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($sysrow = mysql_fetch_array($query)) {
			$sys[$sysrow[0]] = array(
					(int)$sysrow[1], // 0 => systemeUpdateHidden
					(int)$sysrow[2]  // 1 => systemeUpdate
			);
		}
		
		mysql_free_result($query);
		
		// Planeten-Aktualität und Inhaber auslesen
		$query = query("
			SELECT
				planetenID,
				planeten_playerID,
				planetenUpdateOverview,
				planetenUpdate
			FROM
				".PREFIX."planeten
			WHERE
				planetenID > ".$plmin."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($plrow = mysql_fetch_array($query)) {
			$pl[$plrow[0]] = array(
					(int)$plrow[1], // 0 => planeten_playerID
					(int)$plrow[2], // 1 => planetenUpdateOverview
					(int)$plrow[3]  // 2 => planetenUpdate
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
		
		
		$time = time();
		$updateSystem = false;
		$addPlanets = false;
		$systemId = 0;
		
		
		// Transaktion starten
		query("START TRANSACTION") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Datensätze durchgehen
		for($i=$offset; $i < $count; $i++) {
		
			$val = $data[$i];
		
			// Zeile auswerten
			if(preg_match("/^([A-Z])(\d+)=(.+)$/Uis", $val, $row)) {
					
				/*
				 * System-Datensatz
				 */
				if($row[1] == 'S') {
		
					// nach dem Zeitlimit unterbrechen
					if(time()-$time > self::$import_timelimit AND $count-$i > 10) {
						
						// Transaktion ausführen
						query("COMMIT") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						// Balken berechnen
						$maxwidth = 300;
						$width = round(($i+1)/$count*$maxwidth);
						if($width > $maxwidth) {
							$width = $maxwidth;
						}
						else if($width < 0) {
							$width = 0;
						}
						
						$tmpl->script = '
				$(\'#import_balken > .balkenfill\').css(\'width\', \''.$width.'px\');
				$(\'#import_status\').html(\''.$syscount.' System- und '.$plcount.' Planetenscans wurden &uuml;bernommen.\');
				window.setTimeout(function() {
					ajaxcall(\'index.php?p=admin&sp=backup_import&import='.$_GET['import'].'\', false, {\'offset\':'.$i.', \'sys\':'.$syscount.', \'pl\':'.$plcount.', \'sysmin\':'.$sysmin.', \'plmin\':'.$plmin.', \'pladded\':'.$pladded.'}, false);
				}, 250);';
						
						$tmpl->output();
						die();		
					}
					
					$id = $row[2];
					$sysmin = $id;
					$systemId = $id;
		
					$row = json_decode($row[3], true);
					$sysupd = (int)$row[1];
		
					// neues System aktueller
					$updateSystem = (isset($sys[$id]) AND (!$sys[$id][0] OR $sys[$id][1] < $sysupd));
		
					// noch nicht gescannt
					$addPlanets = ($updateSystem AND $sys[$id][0] == 0);
		
					if($updateSystem) {
						// System aktualisieren
						query("
						UPDATE
							".PREFIX."systeme
						SET
							systemeUpdateHidden = ".(int)$row[0].",
							systemeUpdate = ".$sysupd.",
							systemeName = '".escape($row[2])."'
						WHERE
							systemeID = ".(int)$id."
					") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
							
						$syscount++;
					}
					
				}
					
				/*
				 * Planeten-Datensatz
				 */
				else if($row[1] == 'P') {
		
					$id = $row[2];
					
					$row = json_decode($row[3], true);
					$rowCount = count($row);
					$planetExists = isset($pl[$id]);
					
					$playerHistory = false;
					
					$plmin = $id;
		
					if($rowCount < 13) {
						continue;
					}
		
					// Planet neu eintragen
					if($addPlanets AND !$planetExists) {
						
						$planetUpdates = array("
							planetenID = ".$id.",
							planeten_systemeID = ".$systemId.",
							
							planetenPosition = ".(int)$row[1],								
							
							self::getPlanetUpdateSystem($row)
						);
						
						// Inhaber nicht unbekannt
						if($row[5] != -1) {
							$planetUpdates[] = "planetenHistory = 1";
							$playerHistory = true;
						}
						
						// Scan des Planeten vorhanden
						$updateOverview = ($rowCount >= 23);
						$updateFull = ($rowCount >= 30);
						
						if($updateOverview) {
							$planetUpdates[] = self::getPlanetUpdateOverview($row);
						}
						
						if($updateFull) {
							$planetUpdates[] = self::getPlanetUpdateFull($row);
						}
						
						// speichern
						query("
							INSERT INTO
								".PREFIX."planeten
							SET
								".implode(" , ", $planetUpdates)."
						") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						
						// Zähler aktualisieren
						if($updateOverview OR $updateFull) {
							$plcount++;
						}
						
						$pladded++;
					}
					
					
					// Planet aktualisieren
					else if($planetExists) {
						
						$updateOverview = ($pl[$id][1] < $row[6] AND $rowCount >= 23);
						$updateFull = ($pl[$id][2] < $row[7] AND $rowCount >= 31);
						
						if($updateSystem OR $updateOverview OR $updateFull) {
							
							$planetUpdates = array();
							
							if($updateSystem) {
								$planetUpdates[] = self::getPlanetUpdateSystem($row);
								
								// Inhaberwechsel
								if($pl[$id][0] != $row[5]) {
									$playerHistory = true;
									
									$planetUpdates[] = "
										planetenHistory = planetenHistory+1	
									";
								}
							}
							
							// Planeten-Oberfläche aktueller
							if($updateOverview) {
								$planetUpdates[] = self::getPlanetUpdateOverview($row);
							}
							
							// voller Scan aktueller
							if($updateFull) {
								$planetUpdates[] = self::getPlanetUpdateFull($row);
							}
							
							// speichern
							query("
								UPDATE
									".PREFIX."planeten
								SET
									".implode(" , ", $planetUpdates)."
								WHERE
									planetenID = ".$id."
							") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
							
							
							// Zähler aktualisieren
							if($updateOverview OR $updateFull) {
								$plcount++;
							}
						}
					}
					
					
					if($updateSystem AND ($addPlanets OR $planetExists)) {
						// Myrigate eintragen
						if($row[12] AND !isset($mgates[$id])) {
							
							$gala = self::getGalaForSystem($systemId);
							
							query("
								INSERT INTO ".PREFIX."myrigates
								SET
									myrigates_planetenID = ".(int)$id.",
									myrigates_galaxienID = ".(int)$gala.",
									myrigatesSprung = ".($row[12] == 2 ? $sysupd : "0")."
							") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						}
						
						// Myrigate entfernen
						else if(!$row[12] AND isset($mgates[$id])) {
							query("
								DELETE FROM
									".PREFIX."myrigates
								WHERE
									myrigates_planetenID = ".(int)$id."
							") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						}
					}
					
					// Inhaberwechsel eintragen
					if($playerHistory) {
						query("
							INSERT INTO
								".PREFIX."planeten_history
							SET
								history_planetenID = ".(int)$id.",
								history_playerID = ".(int)$row[5].",
								historyLast = ".($planetExists ? (int)$pl[$id][0] : "-1").",
								historyTime = ".$sysupd."
						") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
				}
			}
		}
		
		// Transaktion ausführen
		query("COMMIT") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		self::cleanup();
		
		
		// Ausgabe
		$tmpl->script = '
$(\'#content\').html(\'<div class="center">Der Import wurde erfolgreich abgeschlossen.<br /><br />'.$syscount.' System- und '.$plcount.' Planetenscans wurden &uuml;bernommen.'.($pladded ? '<br ><br />'.$pladded.' Planeten neu hinzugef&uuml;gt.' : '').'</div><br /><br />\');';
		
		$tmpl->output();
		
	}
	
	/**
	 * Update-Anweisungen für Planeten bei aktuellerem System-Scan
	 * @param array $row Abgleich-Datensatz
	 * @return string
	 */
	private static function getPlanetUpdateSystem($row) {
		return "
			planetenTyp = ".(int)$row[0].",
			planetenGroesse = ".(int)$row[2].",
			planetenBevoelkerung = ".(int)$row[3].",
			planetenName = '".escape($row[4])."',
			planeten_playerID = ".(int)$row[5].",

			planetenRWErz = ".(int)$row[8].",
			planetenRWWolfram = ".(int)$row[9].",
			planetenRWKristall = ".(int)$row[10].",
			planetenRWFluor = ".(int)$row[11].",

			planetenMyrigate = ".(int)$row[12].",

			planetenNatives = ".(int)$row[13]."
		";
	}
	
	/**
	 * Update-Anweisungen für Planeten mit Oberflächen-Scan
	 * @param array $row Abgleich-Datensatz
	 * @return string
	 */
	private static function getPlanetUpdateOverview($row) {
		return "
			planetenUpdateOverview = ".(int)$row[6].",
			
			planetenKategorie = ".(int)$row[14].",
			planetenGebPlanet = '".escape($row[15])."',
			planetenGebOrbit = '".escape($row[16])."',
			planetenGebSpezial = '".escape($row[17])."',
			planetenOrbiter = ".(int)$row[18].",

			planetenRMErz = ".(int)$row[19].",
			planetenRMMetall = ".(int)$row[20].",
			planetenRMWolfram = ".(int)$row[21].",
			planetenRMKristall = ".(int)$row[22].",
			planetenRMFluor = ".(int)$row[23].",
			
			planetenRMGesamt = ".(int)($row[19]+$row[20]+$row[21]+$row[22]+$row[23])."
		";
	}

	/**
	 * Update-Anweisungen für Planeten mit vollem Scan
	 * @param array $row Abgleich-Datensatz
	 * @return string
	 */
	private static function getPlanetUpdateFull($row) {
		return "
			planetenUpdate = ".(int)$row[7].",
			
			planetenForschung = ".(int)$row[24].",
			planetenIndustrie = ".(int)$row[25].",

			planetenRPErz = ".(int)$row[26].",
			planetenRPMetall = ".(int)$row[27].",
			planetenRPWolfram = ".(int)$row[28].",
			planetenRPKristall = ".(int)$row[29].",
			planetenRPFluor = ".(int)$row[30].",
					
			planetenRPGesamt = ".(int)($row[26]+$row[27]+$row[28]+$row[29]+$row[30])."
		";
	}
	
	
	private static $galaSystemId = false;
	
	/**
	 * Galaxie anhand der System-ID ermitteln
	 * @param int $id System-ID
	 * @return int Galaxie
	 */
	private static function getGalaForSystem($id) {
		
		// niedrigste System-ID pro Galaxie einlesen
		if(self::$galaSystemId === false) {
			
			self::$galaSystemId = array();
			
			$query = query("
				SELECT
					systeme_galaxienID,
					MIN(systemeID) as MinSystem,
					MAX(systemeID) as MaxSystem
				FROM
					".PREFIX."systeme
				GROUP BY
					systeme_galaxienID
			") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				self::$galaSystemId[$row['systeme_galaxienID']] = array($row['MinSystem'], $row['MaxSystem']);
			}
			
			mysql_free_result($query);
		}
		
		foreach(self::$galaSystemId as $gala=>$data) {
			if($data[0] <= $id AND $data[1] >= $id) {
				return $gala;
			}
		}
		
		return 0;
	}
	
	/**
	 * Aufräumen und Aktualisieren nach einem Abgleich
	 * - Risse zurücksetzen
	 * - Galaxien aktualisieren
	 * - Gate-Entfernung neu berechnen (für neu eingetragene Planeten)
	 * - Statistik-Cache löschen
	 * - Importdatei löschen
	 */
	private static function cleanup() {
		
		// Risse zurücksetzen
		query("
			UPDATE
				".PREFIX."planeten
			SET
				planetenRiss = 0
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		query("
			UPDATE
				".PREFIX."planeten p1
				LEFT JOIN ".PREFIX."planeten p2
					ON p1.planetenID = p2.planetenMyrigate
			SET
				p1.planetenRiss = p2.planetenID
			WHERE
				p1.planetenID > 2 AND
				p2.planetenID IS NOT NULL
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
		
		if(isset($_POST['pladded']) AND $_POST['pladded'] > 0) {
			// Gate-Entfernung der Planeten neu berechnen
			query("
				UPDATE
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON planeten_systemeID = systemeID
					LEFT JOIN ".PREFIX."galaxien
						ON galaxienID = systeme_galaxienID
				SET
					planetenGateEntf = ".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", "galaxienGateX", "galaxienGateY", "galaxienGateZ", "CONVERT(galaxienGatePos, SIGNED)")."
				WHERE
					galaxienGate > 0
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		global $cache;
		
		for($i=1; $i<=100; $i++) {
			$cache->remove('stats'.$i);
		}
		
		// Import löschen
		@unlink('./admin/cache/'.$_GET['import']);
		
	}
	
	
	/**
	 * Grunddaten aus OD importieren
	 * @param string $data dekomprimierter Datei-Inhalt
	 */
	public static function importBasicData($data) {
		
		$tmpl = new template_import();
		
		// Dateiinhalt parsen
		if(!($data = @json_decode($data, true))) {
			$tmpl->error = 'Fehler beim Parsen der Datei! (kein gültiges JSON)';
		}
		else {
			// Gültige Welt und Version
			if(!isset($data['world'], $data['version'], $data['galaxien']) OR $data['version'] != GRUNDDATEN_VERSION) {
				$tmpl->error = 'Ungültige Datei! (Falsche Version)';
			}
			else if($data['world'] != ODWORLD) {
				$tmpl->error = 'Ungültige Datei! (Falsche OD-Welt)';
			}
			else {
				
				$t = time();
				
				$gcount = 0;
				$scount = 0;
				$pcount = 0;
				
				$galaxien = array();
				$galaxienImported = array();
				
				// eingetragene Galaxien auslesen
				$query = query("
					SELECT
						galaxienID
					FROM
						".PREFIX."galaxien
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					$galaxien[] = $row['galaxienID'];
				}
				
				// Transaktion starten
				query("START TRANSACTION") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				
				foreach($data['galaxien'] as $gala=>$gdata) {
					
					// bereits eingetragene Galaxien überspringen
					if(in_array($gala, $galaxien)) {
						continue;
					}
					
					$gcount++;
					$galaxienImported[] = $gala;
					
					// Galaxie eintragen
					query("
						INSERT IGNORE INTO
							".PREFIX."galaxien
						SET
							galaxienID = ".(int)$gala.",
							galaxienSysteme = ".count($gdata)."
					") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					foreach($gdata as $system=>$sdata) {
						
						// System eintragen
						query("
							INSERT IGNORE INTO
								".PREFIX."systeme
							SET
								systemeID = ".(int)$system.",
								systeme_galaxienID = ".(int)$gala.",
								systemeName = '".escape($sdata['name'])."',
								systemeX = ".(int)$sdata['x'].",
								systemeY = ".(int)$sdata['y'].",
								systemeZ = ".(int)$sdata['z'].",
								systemeUpdateHidden = ".$t."
						") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						$scount++;
						
						foreach($sdata['planeten'] as $planet=>$pdata) {
							
							// unbewohnbar-Filter
							if((int)$pdata['groesse'] <= 3) {
								$pdata['groesse'] = 0;
							}
							
							// Planet eintragen
							query("
								INSERT IGNORE INTO
									".PREFIX."planeten
								SET
									planetenID = ".(int)$planet.",
									planeten_systemeID = ".(int)$system.",
									planeten_playerID = -1,
									planetenName = '".escape($pdata['name'])."',
									planetenPosition = ".(int)$pdata['position'].",
									planetenTyp = ".(int)$pdata['typ'].",
									planetenGroesse = ".(int)$pdata['groesse'].",
									planetenBevoelkerung = ".(int)$pdata['bevoelkerung'].",
									planetenRWErz = ".(int)$pdata['erz'].",
									planetenRWWolfram = ".(int)$pdata['wolfram'].",
									planetenRWKristall = ".(int)$pdata['kristall'].",
									planetenRWFluor = ".(int)$pdata['fluor']."
							") OR dieTransaction("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
							
							$pcount++;
						}
					}
				}
				
				// Transaktion ausführen
				query("COMMIT") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Statistik-Cache leeren
				global $cache;
				
				for($i=1; $i<=100; $i++) {
					$cache->remove('stats'.$i);
				}
				
				// Ausgabe
				$tmpl->content = '
					<div id="content" class="center">
						<p>
							Der Grunddaten-Import wurde erfolgreich abgeschlossen.
							<br /><br />';
				
				if($gcount == 1) {
					$tmpl->content .= 'Galaxie '.$galaxienImported[0];
				}
				// mehrere Galaxien als "a-b, c-d" formatieren
				else {
					$galaxienImportedFormat = array();
					$firstGala = false;
					$currentGala = false;
					
					foreach($galaxienImported as $gala) {
						if($firstGala === false) {
							$firstGala = $gala;
							$currentGala = $gala;
						}
						else {
							if($gala != $currentGala+1) {
								if($firstGala == $currentGala) {
									$galaxienImportedFormat[] = $firstGala;
								}
								else {
									$galaxienImportedFormat[] = $firstGala.'-'.$currentGala;
								}
								
								$firstGala = $gala;
							}
							
							$currentGala = $gala;
						}
					}
					
					if($firstGala == $currentGala) {
						$galaxienImportedFormat[] = $firstGala;
					}
					else {
						$galaxienImportedFormat[] = $firstGala.'-'.$currentGala;
					}
					
					$tmpl->content .= 'Die Galaxien '.implode(', ', $galaxienImportedFormat);
				}
				
				$tmpl->content .= ' mit '.$scount.' Systemen und '.$pcount.' Planeten wurde'.($gcount != 1 ? 'n' : '').' eingetragen.
						</p>
					</div>
				';
			}
		}
		
		$tmpl->output();
	}
	
	
}




// keine Berechtigung
if(!$user->rechte['verwaltung_backup']) {
	$tmpl = new template_import;
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->form = false;
	$tmpl->output();
}

// Datei hochladen
else if(isset($_FILES['import'])) {
	
	BackupImport::uploadFile();
	
}

// Daten importieren
else if(isset($_GET['import'], $_POST['offset'])) {
	
	BackupImport::importData();
	
}

// Seiteninhalt
else {
	$tmpl = new template_import;
	$tmpl->output();
}

?>