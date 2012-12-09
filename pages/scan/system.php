<?php
/**
 * pages/scan/system.php
 * sichtbares System einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



class ScanSystem {
	
	/**
	 * Planeten-Aktion eintragen; erfasst werden Invasionen, Besatzungen, Genesis und Kolos
	 * @param array $pldata Planetendaten aus dem Scan
	 * @param array $plrow gespeicherter Planetendatensatz @default false
	 */
	public static function addInvasion($pldata, $plrow=false) {
		
		global $invasionen, $cache, $user, $tmpl;
		
		// keine Aktion erfasst
		if(!isset($pldata['inva']) AND !isset($pldata['kolo']) AND !isset($pldata['besatzung']) AND !isset($pldata['genesis'])) {
			return false;
		}
		
		// Abbrechen, wenn schon eine Aktion eingetragen ist
		if(isset($invasionen[$pldata['id']])) {
			
			if(isset($pldata['inva']) AND in_array(1, $invasionen[$pldata['id']])) {
				return false;
			}
			
			if(isset($pldata['genesis']) AND in_array(3, $invasionen[$pldata['id']])) {
				return false;
			}
			
			if(isset($pldata['besatzung']) AND in_array(4, $invasionen[$pldata['id']])) {
				return false;
			}
			
			if(isset($pldata['kolo']) AND in_array(5, $invasionen[$pldata['id']])) {
				return false;
			}
			
		}
		
		
		// Planeten-Verschleierung umgehen
		$inhaber = $pldata['inhaber'];
		
		if($plrow !== false AND $inhaber < 0) {
			$inhaber = $plrow['planeten_playerID'];
		}
		
		
		// Inva, Kolo oder Genesis können erfasst werden
		if(isset($pldata['kolo'])) {
			$invatyp = 5;
		}
		
		else if(isset($pldata['genesis'])) {
			$invatyp = 3;
		}
		
		else if(isset($pldata['besatzung'])) {
			$invatyp = 4;
		}
		
		else if(isset($pldata['inva'])) {
			$invatyp = 1;
		}
		
		// frei oder verschleiert
		if($inhaber < 3) {
			$open = 0;
			$fremd = 1;
		}
		// ermitteln, ob Opfer registiert ist
		else {
			$query = query("
				SELECT
					COUNT(*) AS Anzahl
				FROM
					".PREFIX."user
				WHERE
					user_playerID = ".$inhaber."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$regdata = mysql_fetch_assoc($query);
			
			if($regdata['Anzahl']) {
				$open = 1;
				$fremd = 0;
			}
			else {
				$open = 0;
				$fremd = 1;
			}
		}
		
		// Kolos sind nie offen
		if($invatyp == 5) {
			$open = 0;
		}
			
		// Aktion eintragen
		query("
			INSERT INTO ".PREFIX."invasionen
			SET
				invasionenTime = ".time().",
				invasionen_playerID = ".$inhaber.",
				invasionen_planetenID = ".$pldata['id'].",
				invasionen_systemeID = ".$_POST['id'].",
				invasionenTyp = ".$invatyp.",
				invasionenFremd = ".$fremd.",
				invasionenOpen = ".$open."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$id = mysql_insert_id();
		
		// InvaLog-Eintrag
		query("
			INSERT INTO ".PREFIX."invasionen_log
			SET
				invalog_invasionenID = ".$id.",
				invalogTime = ".time().",
				invalog_playerID = ".$user->id.",
				invalogText = 'erfasst die Aktion durch Einscannen des Systems'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// offene Invasionen aus dem Cache löschen
		$cache->remove('openinvas');
		
		if(!isset($_GET['plugin']) AND $user->rechte['invasionen']) {
			$tmpl->script = 'openinvas();';
		}
	}
	
	
	/**
	 * Bergbau oder Terraformer eintragen
	 * @param array $pldata Planeten-Datensatz
	 */
	public static function addBergbauTf($pldata) {
		
		// Bergbau eintragen
		if(isset($pldata['bb'])) {
			query("
				INSERT INTO ".PREFIX."planeten_schiffe
				SET
					schiffe_planetenID = ".$pldata['id'].",
					schiffeBergbau = -1,
					schiffeBergbauUpdate = ".time()."
				ON DUPLICATE KEY UPDATE
					schiffeBergbau = -1,
					schiffeBergbauUpdate = ".time()."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		// Terraformer eintragen
		if(isset($pldata['tf'])) {
			query("
				INSERT INTO ".PREFIX."planeten_schiffe
				SET
					schiffe_planetenID = ".$pldata['id'].",
					schiffeTerraformer = 1,
					schiffeTerraformerUpdate = ".time()."
				ON DUPLICATE KEY UPDATE
					schiffeTerraformer = 1,
					schiffeTerraformerUpdate = ".time()."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
	}
	
}




// eingehende Daten sichern und Allianzen im System ermitteln
$allies = '';
$allyarray = array();

$_POST['id'] = (int)$_POST['id'];
$_POST['name'] = escape(html_entity_decode($_POST['name'], ENT_QUOTES, 'utf-8'));

foreach($_POST['pl'] as $key=>$data) {
	if($data) {
		$_POST['pl'][$key]['id'] = (int)$data['id'];
		$_POST['pl'][$key]['name'] = escape(html_entity_decode(html_entity_decode($data['name'], ENT_QUOTES, 'utf-8'), ENT_QUOTES, 'utf-8'));
		$_POST['pl'][$key]['typ'] = (int)$data['typ'];
		$_POST['pl'][$key]['groesse'] = (int)$data['groesse'];
		$_POST['pl'][$key]['bev'] = (int)$data['bev'];
		$_POST['pl'][$key]['erz'] = (int)$data['erz'];
		$_POST['pl'][$key]['wolfram'] = (int)$data['wolfram'];
		$_POST['pl'][$key]['kristall'] = (int)$data['kristall'];
		$_POST['pl'][$key]['fluor'] = (int)$data['fluor'];
		$_POST['pl'][$key]['inhaber'] = (int)$data['inhaber'];
		$_POST['pl'][$key]['allianz'] = (int)$data['allianz'];
		if(isset($data['mgate'])) $_POST['pl'][$key]['mgate'] = (int)$data['mgate'];
		if(isset($data['riss'])) $_POST['pl'][$key]['riss'] = (int)$data['riss'];
		
		// Allianz hinzufügen
		// freie Planeten: Allianz -1
		if(trim($data['allianz']) == '') $data['allianz'] = -1;
		else $data['allianz'] = (int)$data['allianz'];
		
		if(!isset($allyarray[$data['allianz']])) {
			$allies .= '+'.$data['allianz'].'+';
			$allyarray[$data['allianz']] = true;
		}
	}
}

// System-Daten abfragen
$query = query("
	SELECT
		systeme_galaxienID,
		systemeUpdateHidden,
		systemeUpdate,
		systemeX,
		systemeY,
		systemeZ,
		
		galaxienGate,
		galaxienGateX,
		galaxienGateY,
		galaxienGateZ,
		galaxienGatePos
	FROM
		".PREFIX."systeme
		LEFT JOIN ".PREFIX."galaxien
			ON systeme_galaxienID = galaxienID
	WHERE
		systemeID = ".$_POST['id']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// System nicht vorhanden -> Galaxie nicht eingescannt?
if(!mysql_num_rows($query)) {
	$tmpl->error = 'Das System ist unbekannt. Eventuell wurde die Galaxie noch nicht eingetragen!';
}
// System vorhanden
else {
	$data = mysql_fetch_assoc($query);
	
	// Flooding-Schutz 5 Minuten
	if(time()-$data['systemeUpdate'] < 300 AND !isset($_GET['force'])) {
		$tmpl->error = 'Das System wurde in den letzten 5 Minuten schon eingescannt!';
		$tmpl->output();
		die();
	}
	
	
	$gate = 0;
	
	
	// eingetragene Aktionen im System ermitteln
	$invasionen = array();
				
	$conds = array(
		"invasionen_systemeID = ".$_POST['id'],
		"(invasionenEnde > ".time()." OR invasionenEnde = 0)"
	);
	
	$query = query("
		SELECT
			invasionen_planetenID,
			invasionenTyp
		FROM
			".PREFIX."invasionen
		WHERE
			".implode(' AND ', $conds)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		
		if(!isset($invasionen[$row['invasionen_planetenID']])) {
			$invasionen[$row['invasionen_planetenID']] = array();
		}
		
		$invasionen[$row['invasionen_planetenID']][] = $row['invasionenTyp'];
		
	}
	
	
	
	
	// Ausgabe
	$out = 'eingetragen';
	
	// Anzahl der Planeten im System ermitteln
	$count = 0;
	
	foreach($_POST['pl'] as $key=>$pldata) {
		if($pldata) {
			$count++;
		}
	}
	
	// bei mehr als 7 Planeten abbrechen
	if($count > 7) {
		$tmpl->error = 'Input invalid!';
		$tmpl->output();
		die();
	}
	
	// Systemdaten aktualisieren
	if($count) {
		query("
			UPDATE ".PREFIX."systeme
			SET
				systemeUpdateHidden = ".time().",
				systemeUpdate = ".time().",
				systemeName = '".$_POST['name']."',
				systemeScanReserv = 0,
				systemeAllianzen = '".$allies."'
			WHERE
				systemeID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// falls noch nicht gescannt, Galaxie aktualisieren
		if(!$data['systemeUpdate']) {
			query("
				UPDATE ".PREFIX."galaxien
				SET
					galaxienSysScanned = galaxienSysScanned+1
				WHERE
					galaxienID = ".$data['systeme_galaxienID']."
					AND galaxienSysScanned < galaxienSysteme
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	// Keine Planeten im System -> löschen
	else if($user->rechte['scan_del']) {
		// System löschen
		query("
			DELETE FROM ".PREFIX."systeme
			WHERE
				systemeID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Galaxie aktualisieren
		query("
			UPDATE ".PREFIX."galaxien
			SET
				galaxienSysteme = galaxienSysteme-1
				".($data['systemeUpdate'] ? ', galaxienSysScanned = galaxienSysScanned-1' : '')."
			WHERE
				galaxienID = ".$data['systeme_galaxienID']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging']) {
			insertlog(4, 'löscht das System '.$_POST['id'].' durch Einscannen');
		}
	}
	
	// ODRequest-Spieler
	$odreq = array();
	
	// System noch nie gescannt -> Planeten eintragen
	if(!$data['systemeUpdateHidden']) {
		// Planeten durchgehen
		foreach($_POST['pl'] as $key=>$pldata) {
			if($pldata) {
				$pos = $key+1;
				// Gate-Entfernung berechnen
				if(!$data['galaxienGate']) $gentf = 'NULL';
				else $gentf = entf(
					$data['galaxienGateX'],
					$data['galaxienGateY'],
					$data['galaxienGateZ'],
					$data['galaxienGatePos'],
					$data['systemeX'],
					$data['systemeY'], 
					$data['systemeZ'],
					$pos);
				
				// eintragen
				query("
					INSERT IGNORE INTO ".PREFIX."planeten
					SET
						planetenID = ".$pldata['id'].",
						planeten_systemeID = ".$_POST['id'].",
						planetenPosition = ".$pos.",
						planetenName = '".$pldata['name']."',
						planeten_playerID = ".$pldata['inhaber'].",
						planetenTyp = ".$pldata['typ'].",
						planetenGroesse = ".$pldata['groesse'].",
						planetenGateEntf = ".$gentf.",
						planetenRWErz = ".$pldata['erz'].",
						planetenRWWolfram = ".$pldata['wolfram'].",
						planetenRWKristall = ".$pldata['kristall'].",
						planetenRWFluor = ".$pldata['fluor'].",
						planetenBevoelkerung = ".$pldata['bev'].",
						planetenMyrigate = ".(isset($pldata['mgate']) ? $pldata['mgate'] : '0').",
						planetenRiss = ".(isset($pldata['riss']) ? $pldata['riss'] : '0').",
						planetenNatives = ".(isset($pldata['natives']) ? $pldata['groesse'] : '0').",
						planetenHistory = 1
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// in History eintragen
				query("
					INSERT INTO ".PREFIX."planeten_history
					SET
						history_planetenID = ".$pldata['id'].",
						history_playerID = ".$pldata['inhaber'].",
						historyLast = -1,
						historyTime = ".time()."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Inhaber in ODRequest-Array eintragen
				if(!isset($odreq[$pldata['inhaber']])) {
					$odreq[$pldata['inhaber']] = $pldata['allianz'];
				}
				
				// Myrigate eintragen
				if(isset($pldata['mgate'])) {
					query("
						INSERT INTO ".PREFIX."myrigates
						SET
							myrigates_planetenID = ".$pldata['id'].",
							myrigates_galaxienID = ".$data['systeme_galaxienID']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Riss des Ziels eintragen
					query("
						UPDATE ".PREFIX."planeten
						SET
							planetenRiss = ".$pldata['id']."
						WHERE
							planetenID = ".$pldata['mgate']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
				
				// neues Gate?
				if(isset($pldata['gate']) AND $pldata['id'] != $data['galaxienGate']) {
					$gate = array($pldata['id'], $pos);
				}
				
				// Bergbau und Terraformer eintragen
				ScanSystem::addBergbauTf($pldata);
				
				// Kolo und Genesis eintragen
				ScanSystem::addInvasion($pldata);
				
			}
		}
	}
	// System schon mal gescannt (zumindest verdeckt)
	else {
		// Ausgabe
		if($data['systemeUpdate']) $out = 'aktualisiert';
		
		// Planeten-Daten abfragen
		$pl = array();
		
		$query = query("
			SELECT
				planetenID,
				planetenName,
				planeten_playerID,
				planetenMyrigate,
				planetenRiss,
				planetenBevoelkerung,
				planetenNatives,
				
				schiffeBergbau,
				
				playerRasse
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."planeten_schiffe
					ON schiffe_planetenID = planetenID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
			WHERE
				planeten_systemeID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$pl[$row['planetenID']] = $row;
		}
		
		// gescannte Planeten durcharbeiten
		foreach($_POST['pl'] as $key=>$pldata) {
			if($pldata) {
				$pos = $key+1;
				
				// Planet auf einmal aufgetaucht -> eintragen
				if(!isset($pl[$pldata['id']])) {
					// Gate-Entfernung berechnen
					if(!$data['galaxienGate']) $gentf = 'NULL';
					else $gentf = entf(
						$data['galaxienGateX'],
						$data['galaxienGateY'],
						$data['galaxienGateZ'],
						$data['galaxienGatePos'],
						$data['systemeX'],
						$data['systemeY'], 
						$data['systemeZ'],
						$pos);
					
					// eintragen
					query("
						INSERT IGNORE INTO ".PREFIX."planeten
						SET
							planetenID = ".$pldata['id'].",
							planeten_systemeID = ".$_POST['id'].",
							planetenPosition = ".$pos.",
							planetenName = '".$pldata['name']."',
							planeten_playerID = ".$pldata['inhaber'].",
							planetenTyp = ".$pldata['typ'].",
							planetenGroesse = ".$pldata['groesse'].",
							planetenGateEntf = ".$gentf.",
							planetenRWErz = ".$pldata['erz'].",
							planetenRWWolfram = ".$pldata['wolfram'].",
							planetenRWKristall = ".$pldata['kristall'].",
							planetenRWFluor = ".$pldata['fluor'].",
							planetenBevoelkerung = ".$pldata['bev'].",
							planetenMyrigate = ".(isset($pldata['mgate']) ? $pldata['mgate'] : '0').",
							planetenRiss = ".(isset($pldata['riss']) ? $pldata['riss'] : '0').",
							planetenNatives = ".(isset($pldata['natives']) ? $pldata['groesse'] : '0').",
							planetenHistory = 1
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// in History eintragen
					query("
						INSERT INTO ".PREFIX."planeten_history
						SET
							history_planetenID = ".$pldata['id'].",
							history_playerID = ".$pldata['inhaber'].",
							historyLast = -1,
							historyTime = ".time()."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Myrigate eintragen
					if(isset($pldata['mgate'])) {
						query("
							INSERT INTO ".PREFIX."myrigates
							SET
								myrigates_planetenID = ".$pldata['id'].",
								myrigates_galaxienID = ".$data['systeme_galaxienID']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						// Riss des Ziels eintragen
						query("
							UPDATE ".PREFIX."planeten
							SET
								planetenRiss = ".$pldata['id']."
							WHERE
								planetenID = ".$pldata['mgate']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
					
					// Bergbau und Terraformer eintragen
					ScanSystem::addBergbauTf($pldata);
					
					// Inva, Kolo und Genesis eintragen
					ScanSystem::addInvasion($pldata);
					
				}
				// Planet aktualisieren
				else {
					// bei verschleierten Planeten Inhaber nicht immer übernehmen
					$saveowner = true;
					// Planet verschleiert
					if($pldata['inhaber'] == -2 OR $pldata['inhaber'] == -3) {
						$saveowner = false;
						
						// eingetragener Inhaber ganz unbekannt oder Planet vorher frei
						if($pl[$pldata['id']]['planeten_playerID'] == 0 OR $pl[$pldata['id']]['planeten_playerID'] == -1) {
							$saveowner = true;
						}
						// Altrasse / Lux hat gewechselt
						if($pldata['inhaber'] == -2 AND ($pl[$pldata['id']]['planeten_playerID'] == -3 OR $pl[$pldata['id']]['playerRasse'] != 10)) {
							$saveowner = true;
						}
						// Altrasse-Planet war als Lux eingetragen
						else if($pldata['inhaber'] == -3 AND ($pl[$pldata['id']]['planeten_playerID'] == -2 OR $pl[$pldata['id']]['playerRasse'] == 10)) {
							$saveowner = true;
						}
					}
					
					
					// Inhaberwechsel in History eintragen
					if($pl[$pldata['id']]['planeten_playerID'] != $pldata['inhaber'] AND $saveowner) {
						query("
							INSERT INTO ".PREFIX."planeten_history
							SET
								history_planetenID = ".$pldata['id'].",
								history_playerID = ".$pldata['inhaber'].",
								historyLast = ".$pl[$pldata['id']]['planeten_playerID'].",
								historyTime = ".time()."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						$his = 'planetenHistory = planetenHistory+1,';
					}
					else $his = '';
					
					
					// Planet aktualisieren
					query("
						UPDATE ".PREFIX."planeten
						SET
							planetenName = '".$pldata['name']."',
							".($saveowner ? "planeten_playerID = ".$pldata['inhaber']."," : "")."
							planetenTyp = ".$pldata['typ'].",
							planetenGroesse = ".$pldata['groesse'].",
							planetenRWErz = ".$pldata['erz'].",
							planetenRWWolfram = ".$pldata['wolfram'].",
							planetenRWKristall = ".$pldata['kristall'].",
							planetenRWFluor = ".$pldata['fluor'].",
							planetenBevoelkerung = ".$pldata['bev'].",
							".$his."
							".(($pldata['inhaber'] != 0) ? "planetenNatives = 0," : "")."
							planetenMyrigate = ".(isset($pldata['mgate']) ? $pldata['mgate'] : ($pl[$pldata['id']]['planetenMyrigate'] == 2 ? '2' : '0')).",
							planetenRiss = ".(isset($pldata['riss']) ? $pldata['riss'] : '0').",
							planetenNatives = ".((isset($pldata['natives']) AND !$pl[$pldata['id']]['planetenNatives']) ? $pldata['groesse'] : $pl[$pldata['id']]['planetenNatives'])."
						WHERE
							planetenID = ".$pldata['id']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Myrigate eintragen oder aktualisieren
					if(isset($pldata['mgate']) AND $pl[$pldata['id']]['planetenMyrigate'] != $pldata['mgate']) {
						// Myrigate eintragen
						if($pl[$pldata['id']]['planetenMyrigate'] <= 2) {
							// evtl vorhandenen Sprunggenerator löschen
							query("
								DELETE FROM ".PREFIX."myrigates
								WHERE
									myrigates_planetenID = ".$pldata['id']."
							") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
							
							// Myrigate eintragen
							query("
								INSERT INTO ".PREFIX."myrigates
								SET
									myrigates_planetenID = ".$pldata['id'].",
									myrigates_galaxienID = ".$data['systeme_galaxienID']."
							") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
							
							// Riss des Ziels eintragen
							query("
								UPDATE ".PREFIX."planeten
								SET
									planetenRiss = ".$pldata['id']."
								WHERE
									planetenID = ".$pldata['mgate']."
							") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						}
						// anderes Myrigate
						else {
							// alten Riss löschen
							query("
								UPDATE ".PREFIX."planeten
								SET
									planetenRiss = 0
								WHERE
									planetenID = ".$pl[$pldata['id']]['planetenMyrigate']."
							") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
							
							// neuen Riss eintragen
							query("
								UPDATE ".PREFIX."planeten
								SET
									planetenRiss = ".$pldata['id']."
								WHERE
									planetenID = ".$pldata['mgate']."
							") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						}
					}
					// Myrigate löschen
					else if(!isset($pldata['mgate']) AND $pl[$pldata['id']]['planetenMyrigate'] > 2) {
						query("
							DELETE FROM ".PREFIX."myrigates
							WHERE
								myrigates_planetenID = ".$pldata['id']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						// Riss des Ziels löschen
						query("
							UPDATE ".PREFIX."planeten
							SET
								planetenRiss = 0
							WHERE
								planetenID = ".$pl[$pldata['id']]['planetenMyrigate']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
					
					// Riss entfernt oder geändert -> altes Myrigate entfernen
					if((!isset($pldata['riss']) AND $pl[$pldata['id']]['planetenRiss']) OR (isset($pldata['riss']) AND $pldata['riss'] != $pl[$pldata['id']]['planetenRiss'])) {
						query("
							DELETE FROM ".PREFIX."myrigates
							WHERE
								myrigates_planetenID = ".$pl[$pldata['id']]['planetenRiss']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						// Riss des Ziels löschen
						query("
							UPDATE ".PREFIX."planeten
							SET
								planetenMyrigate = 0
							WHERE
								planetenID = ".$pl[$pldata['id']]['planetenRiss']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
					
					// Bergbau und Terraformer eintragen
					ScanSystem::addBergbauTf($pldata);
					
					// Kolo und Genesis eintragen
					ScanSystem::addInvasion($pldata, $pl[$pldata['id']]);
					
				}
				
				// Inhaber in ODRequest-Array eintragen
				if(!isset($odreq[$pldata['inhaber']])) {
					$odreq[$pldata['inhaber']] = $pldata['allianz'];
				}
				
				// neues Gate?
				if(isset($pldata['gate']) AND $pldata['id'] != $data['galaxienGate']) {
					$gate = array($pldata['id'], $pos);
				}
				
				// Planet aus dem Array löschen
				unset($pl[$pldata['id']]);
			}
		}
		
		// Planeten löschen, die nicht im Scan sind
		if(count($pl) AND $user->rechte['scan_del']) {
			$ids = implode(', ', array_keys($pl));
			
			// Planet löschen
			query("
				DELETE FROM ".PREFIX."planeten
				WHERE
					planetenID IN(".$ids.")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// History löschen
			query("
				DELETE FROM ".PREFIX."planeten_history
				WHERE
					history_planetenID IN(".$ids.")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Myrigates löschen
			query("
				DELETE FROM ".PREFIX."myrigates
				WHERE
					myrigates_planetenID IN(".$ids.")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// BBS und Terraformer löschen
			query("
				DELETE FROM ".PREFIX."planeten_schiffe
				WHERE
					schiffe_planetenID IN(".$ids.")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			//  ins Archiv verschieben
			$query = query("
				SELECT
					invasionenID,
					invasionenTyp
				FROM ".PREFIX."invasionen
				WHERE
					invasionen_planetenID IN(".$ids.")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				// ins Archiv verschieben, wenn es keine Kolo war
				if($row['invasionenTyp'] != 5) {
					inva_archiv($row['invasionenID'], 'löscht die Aktion durch Einscannen des Systems (Planet existiert nicht mehr)');
				}
				// Kolos nur löschen
				else {
					query("
						DELETE FROM ".PREFIX."invasionen
						WHERE
							invasionenID = ".$row['invasionenID']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
			}
			
			// Log-Eintrag
			if($config['logging']) {
				insertlog(4, 'löscht die Planeten '.$ids.' beim Systemscan');
			}
		}
	}
	
	// Gate eintragen / ändern
	if($gate) {
		
		// neues Gate eintragen
		query("
			UPDATE ".PREFIX."galaxien
			SET
				galaxienGate = ".$gate[0].",
				galaxienGateSys = ".$_POST['id'].",
				galaxienGateX = ".$data['systemeX'].",
				galaxienGateY = ".$data['systemeY'].",
				galaxienGateZ = ".$data['systemeZ'].",
				galaxienGatePos = ".$gate[1]."
			WHERE
				galaxienID = ".$data['systeme_galaxienID']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Gate-Entfernung für Systeme berechnen
		query("
			UPDATE ".PREFIX."systeme
			SET
				systemeGateEntf = ".entf_mysql("systemeX", "systemeY", "systemeZ", "1", $data['systemeX'], $data['systemeY'], $data['systemeZ'], $gate[1])."
			WHERE
				systeme_galaxienID = ".$data['systeme_galaxienID']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// das Gatesystem hat die Gate-Entfernung 0
		query("
			UPDATE ".PREFIX."systeme
			SET
				systemeGateEntf = 0
			WHERE
				systemeID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Gate-Entfernung für Planeten berechnen
		query("
			UPDATE
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON planeten_systemeID = systemeID
						AND systeme_galaxienID = ".$data['systeme_galaxienID']."
			SET
				planetenGateEntf = ".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $data['systemeX'], $data['systemeY'], $data['systemeZ'], $gate[1])."
			WHERE
				systeme_galaxienID = ".$data['systeme_galaxienID']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// ODRequests
	if(count($odreq)) {
		// Userdaten abfragen
		$query = query("
			SELECT
				playerID,
				player_allianzenID,
				playerUpdate
			FROM ".GLOBPREFIX."player
			WHERE
				playerID IN (".implode(', ', array_keys($odreq)).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			// Allianzänderung -> auf jeden Fall ein ODRequest
			if($row['player_allianzenID'] != $odreq[$row['playerID']]) {
				odrequest($row['playerID'], true);
			}
			// keine Allianzänderung -> normales ODRequest
			else if(time()-$gconfig['odrequest_mintime']*60 > $row['playerUpdate']) {
				odrequest($row['playerID']);
			}
			
			// User aus dem Array löschen
			unset($odreq[$row['playerID']]);
		}
		
		// ODRequest für noch nicht erfasste User
		foreach($odreq as $key=>$data2) {
			odrequest($key);
		}
	}
	
	// User-Statistik
	// System gilt als aktualisiert, wenn es älter als 1 Tag war
	if($data['systemeUpdate'] AND time()-$data['systemeUpdate'] > 86400) {
		query("
			UPDATE ".PREFIX."user
			SET
				userSysUpdated = userSysUpdated+1,
				userDBPunkte = userDBPunkte+1
			WHERE
				user_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	// System eingetragen -> 2 Punkte
	else if(!$data['systemeUpdate']) {
		query("
			UPDATE ".PREFIX."user
			SET
				userSysScanned = userSysScanned+1,
				userDBPunkte = userDBPunkte+2
			WHERE
				user_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(4, 'scannt das System '.$_POST['id'].' ein');
	}
	
	// Ausgabe
	$tmpl->content = 'System '.(isset($_GET['plugin']) ? $_POST['id'] : '<a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$_POST['id'].'">'.$_POST['id'].'</a>').' erfolgreich '.$out;
	
	// Scoutziele
	if(!isset($_GET['plugin'])) {
		$time = time();
		$tmpl->content .= '
	<br />
	<div id="scan'.$time.'"></div>';
		
		if($user->rechte['scout']) {
			$tmpl->script = "ajaxcall('index.php?p=scout&sp=extern_send&start=".$_POST['id']."&antrieb=".$user->settings['antrieb']."&syscount=10&days=".$config['scan_veraltet']."&hidereserv&scan', $('#scan".$time."'), false, true);";
		}
	}
}



?>