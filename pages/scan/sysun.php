<?php
/**
 * pages/scan/sysun.php
 * unsichtbares System einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// eingehende Daten sichern
$_POST['id'] = (int)$_POST['id'];
$_POST['name'] = escape(html_entity_decode($_POST['name'], ENT_QUOTES, 'utf-8'));

foreach($_POST['pl'] as $key=>$data) {
	if($data) {
		$_POST['pl'][$key]['id'] = (int)$data['id'];
		$_POST['pl'][$key]['typ'] = (int)$data['typ'];
		if(isset($_POST['pl'][$key]['groesse'])) {
			$_POST['pl'][$key]['groesse'] = (int)$data['groesse'];
			$_POST['pl'][$key]['bev'] = (int)$data['bev'];
			$_POST['pl'][$key]['erz'] = (int)$data['erz'];
			$_POST['pl'][$key]['wolfram'] = (int)$data['wolfram'];
			$_POST['pl'][$key]['kristall'] = (int)$data['kristall'];
			$_POST['pl'][$key]['fluor'] = (int)$data['fluor'];
		}
		$_POST['pl'][$key]['name'] = escape(html_entity_decode(html_entity_decode($data['name'], ENT_QUOTES, 'utf-8'), ENT_QUOTES, 'utf-8'));
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
		systemeAllianzen,
		
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
	
	// Flooding-Schutz 10 Minuten
	if(time()-$data['systemeUpdateHidden'] < 600) {
		$tmpl->error = 'Das verdeckte System wurde in den letzten 10 Minuten schon eingescannt!';
		$tmpl->output();
		die();
	}
	
	// Allianzen im System evtl ändern
	if($data['systemeAllianzen'] != '' AND isset($_POST['scannerally']) AND (int)$_POST['scannerally']) {
		$_POST['scannerally'] = (int)$_POST['scannerally'];
		
		$data['systemeAllianzen'] = str_replace('+', '', explode('++', $data['systemeAllianzen']));
		
		foreach($data['systemeAllianzen'] as $key=>$val) {
			if($val === '') {
				unset($data['systemeAllianzen'][$key]);
			}
		}
		
		// Allianz des Scanners aus der Liste löschen
		if($_POST['scannerally'] AND $key = array_search($_POST['scannerally'], $data['systemeAllianzen'])) {
			unset($data['systemeAllianzen'][$key]);
			
			// wieder in einen String umwandeln
			if(!count($data['systemeAllianzen'])) {
				$data['systemeAllianzen'] = '';
			}
			else {
				$data['systemeAllianzen'] = '+'.implode('++', $data['systemeAllianzen']).'+';
			}
			
			// Planeten allianzinterner Spieler auf unbekannt setzen
			if($user->rechte['scan_del']) {
				// infrage kommende Spieler aktualisieren
				$query = query("
					SELECT
						planeten_playerID
					FROM
						".PREFIX."planeten
						LEFT JOIN ".GLOBPREFIX."player
							ON playerID = planeten_playerID
					WHERE
						planeten_systemeID = ".$_POST['id']."
						AND player_allianzenID = ".$_POST['scannerally']."
						AND playerUpdate < ".(time()-900)."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					odrequest($row['planeten_playerID'], true);
				}
				
				// Planeten abfragen
				$query = query("
					SELECT
						planetenID,
						planeten_playerID
					FROM
						".PREFIX."planeten
						LEFT JOIN ".GLOBPREFIX."player
							ON playerID = planeten_playerID
					WHERE
						planeten_systemeID = ".$_POST['id']."
						AND player_allianzenID = ".$_POST['scannerally']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					// auf unbekannt setzen
					query("
						UPDATE ".PREFIX."planeten
						SET
							planeten_playerID = -1
						WHERE
							planetenID = ".$row['planetenID']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// History-Eintrag
					query("
						INSERT INTO ".PREFIX."planeten_history
						SET
							history_planetenID = ".$row['planetenID'].",
							history_playerID = -1,
							historyLast = ".$row['planeten_playerID'].",
							historyTime = ".time()."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
			}
		}
	}
	
	
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
		
		// systemeAllianzen wieder in String umwandeln
		if(is_array($data['systemeAllianzen'])) {
			$data['systemeAllianzen'] = '+'.implode('++', $data['systemeAllianzen']).'+';
		}
		
		query("
			UPDATE ".PREFIX."systeme
			SET
				systemeUpdateHidden = ".time().",
				systemeName = '".$_POST['name']."',
				systemeAllianzen = '".$data['systemeAllianzen']."'
			WHERE
				systemeID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
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
	
	// System noch nicht gescannt -> Planeten eintragen
	if(!$data['systemeUpdateHidden']) {
		// Ausgabe
		$out = 'eingetragen';
		
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
				
				// Genesis-Planet: alles auf 0
				if(!isset($pldata['groesse'])) {
					$pldata['groesse'] = 0;
					$pldata['bev'] = 0;
					$pldata['erz'] = 0;
					$pldata['wolfram'] = 0;
					$pldata['kristall'] = 0;
					$pldata['fluor'] = 0;
				}
				
				// eintragen
				query("
					INSERT INTO ".PREFIX."planeten
					SET
						planetenID = ".$pldata['id'].",
						planeten_systemeID = ".$_POST['id'].",
						planetenPosition = ".$pos.",
						planetenName = '".$pldata['name']."',
						planeten_playerID = -1,
						planetenTyp = ".$pldata['typ'].",
						planetenGroesse = ".$pldata['groesse'].",
						planetenGateEntf = ".$gentf.",
						planetenRWErz = ".$pldata['erz'].",
						planetenRWWolfram = ".$pldata['wolfram'].",
						planetenRWKristall = ".$pldata['kristall'].",
						planetenRWFluor = ".$pldata['fluor'].",
						planetenBevoelkerung = ".$pldata['bev']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
		}
	}
	// System schon gescannt
	else {
		// Ausgabe
		$out = 'aktualisiert';
		
		// Planeten-Daten abfragen
		$pl = array();
		
		$query = query("
			SELECT
				planetenID,
				planetenName,
				planeten_playerID
			FROM ".PREFIX."planeten
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
					
					// Genesis-Planet
					if(!isset($pldata['groesse'])) {
						$pldata['groesse'] = 0;
						$pldata['bev'] = 0;
						$pldata['erz'] = 0;
						$pldata['wolfram'] = 0;
						$pldata['kristall'] = 0;
						$pldata['fluor'] = 0;
					}
					
					// eintragen
					query("
						INSERT IGNORE INTO ".PREFIX."planeten
						SET
							planetenID = ".$pldata['id'].",
							planeten_systemeID = ".$_POST['id'].",
							planetenPosition = ".$pos.",
							planetenName = '".$pldata['name']."',
							planeten_playerID = -1,
							planetenTyp = ".$pldata['typ'].",
							planetenGroesse = ".$pldata['groesse'].",
							planetenGateEntf = ".$gentf.",
							planetenRWErz = ".$pldata['erz'].",
							planetenRWWolfram = ".$pldata['wolfram'].",
							planetenRWKristall = ".$pldata['kristall'].",
							planetenRWFluor = ".$pldata['fluor'].",
							planetenBevoelkerung = ".$pldata['bev']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
				// Planet aktualisieren
				else {
					// Planet frei, aber Namensänderung -> Inhaber auf unbekannt setzen
					if($pl[$pldata['id']]['planeten_playerID'] == 0 
						AND $pl[$pldata['id']]['planetenName'] != $pldata['name']) {
						$inh = -1;
						
						// neuen Eintrag in die History einfügen
						query("
							INSERT INTO ".PREFIX."planeten_history
							SET
								history_planetenID = ".$pldata['id'].",
								history_playerID = -1,
								historyLast = 0,
								historyTime = ".time()."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						// History-Spalte in der Planetentabelle aktualisieren
						$his = 'planetenHistory = planetenHistory+1,';
					}
					// Inhaber hat sich nicht geändert
					else {
						$inh = $pl[$pldata['id']]['planeten_playerID'];
						$his = '';
					}
					
					// Planet aktualisieren
					query("
						UPDATE ".PREFIX."planeten
						SET
							planeten_playerID = ".$inh.",
							planetenName = '".$pldata['name']."',
							".$his."
							planetenTyp = ".$pldata['typ']."
						WHERE
							planetenID = ".$pldata['id']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Planet aus dem Array löschen
					unset($pl[$pldata['id']]);
				}
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
			
			// Invasionen ins Archiv verschieben
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
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(4, 'scannt das verdeckte System '.$_POST['id'].' ein');
	}
	
	// Ausgabe
	$tmpl->content = 'Verdecktes System '.(isset($_GET['plugin']) ? $_POST['id'] : '<a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$_POST['id'].'">'.$_POST['id'].'</a>').' erfolgreich '.$out;
	
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