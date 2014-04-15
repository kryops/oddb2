<?php
/**
 * pages/admin/galaxien_parse.php
 * Verwaltung -> Galaxieverwaltung -> Galaxie einparsen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Workaround für max_input_vars
if(isset($_POST['data'])) {
	$data = $_POST['data'];
	@ini_set('max_input_vars', 15000);
	parse_str($data, $_POST);
}


// keine Berechtigung
if(!$user->rechte['verwaltung_galaxien']) $tmpl->error = 'Du hast keine Berechtigung!';
// Daten unvollständig
else if(!isset($_POST['gala'], $_POST['s']) OR !count($_POST['s'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// alles OK
else {
	// Daten sichern
	$_POST['gala'] = (int)$_POST['gala'];
	foreach($_POST['s'] as $key=>$data) {
		$_POST['s'][$key][0] = (int)$data[0];
		$_POST['s'][$key][2] = (int)$data[2];
		$_POST['s'][$key][3] = (int)$data[3];
		$_POST['s'][$key][4] = (int)$data[4];
		$_POST['s'][$key][1] = escape(html_entity_decode($data[1], ENT_QUOTES, 'utf-8'));
	}
	
	// Anzahl der Systeme ermitteln
	$count = count($_POST['s']);
	
	// Galaxie-ID invalid
	if($_POST['gala'] < 1) {
		$tmpl->error = 'Invalide Galaxie-ID!';
	}
	else {
		// Systeme eintragen
		$s = array();
		foreach($_POST['s'] as $data) {
			$s[] = "(".$data[0].", ".$_POST['gala'].", '".$data[1]."', ".$data[2].", ".$data[3].", ".$data[4].")";
		}
		query("
			INSERT IGNORE INTO ".PREFIX."systeme
				(systemeID, systeme_galaxienID, systemeName, systemeX, systemeY, systemeZ)
			VALUES
				".implode(', ', $s)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// wie viele Systeme wurden wirklich eingetragen?
		$count2 = mysql_affected_rows();
		
		// Galaxie normal eingescannt, alle Systeme wurden eingetragen
		if($count == $count2) {
			// Galaxie eintragen
			query("
				INSERT IGNORE INTO ".PREFIX."galaxien
				SET
					galaxienID = ".$_POST['gala'].",
					galaxienSysteme = ".$count."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Ausgabe
			$tmpl->content = '
				Galaxie '.$_POST['gala'].' erfolgreich eingetragen.
				<br /><br />
				Damit alle Planeten erfasst werden k&ouml;nnen, muss jedes System mindestens einmal verdeckt gescannt werden.
				<br /><br />';
		}
		// nicht alle Systeme eingetragen -> verschmolzen
		else {
			// Zahl der letztendlichen eingetragenen Systeme ermitteln
			$query = query("
				SELECT
					COUNT(systemeID)
				FROM ".PREFIX."systeme
				WHERE
					systeme_galaxienID = ".$_POST['gala']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$data = mysql_fetch_array($query);
			$count3 = $data[0];
			
			
			// Galaxie eintragen oder Systeme aktualisieren, wenn Systeme eingetragen
			if($count3) {
				query("
					INSERT INTO ".PREFIX."galaxien
					SET
						galaxienID = ".$_POST['gala'].",
						galaxienSysteme = ".$count3."
					ON DUPLICATE KEY UPDATE
						galaxienSysteme = ".$count3."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
			
			// System-IDs in ein Array laden
			$s = array();
			foreach($_POST['s'] as $data) {
				$s[] = $data[0];
			}
			
			// Galas der übrigen Systeme ermitteln
			$query = query("
				SELECT DISTINCT
					systeme_galaxienID
				FROM ".PREFIX."systeme
				WHERE
					systeme_galaxienID != ".$_POST['gala']."
					AND systemeID IN (".implode(', ', $s).")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			
			
			// Galaxie war schon zumindest teilweise eingetragen
			if(!mysql_num_rows($query)) {
				// Ausgabe
				$tmpl->content = '
					Galaxie '.$_POST['gala'].' erfolgreich aktualisiert
					<br /><br />';
			}
			// fehlgeschlagene Verschmelzung reparieren
			else if(isset($_POST['repair']) AND $user->rechte['verwaltung_galaxien2']) {
				// Verschmelzungsgalaxien ermitteln
				$g = array();
				while($row = mysql_fetch_array($query)) {
					$g[] = $row[0];
				}
				// neue Gala anhängen
				if(!in_array($_POST['gala'], $g)) $g[] = $_POST['gala'];
				
				$gids = implode(', ', $g);
				
				// Systeme in die eingescannte Galaxie verschieben
				$sids = implode(', ', $s);
				
				query("
					UPDATE
						".PREFIX."systeme
					SET
						systeme_galaxienID = ".$_POST['gala']."
					WHERE
						systemeID IN (".$sids.")
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// betroffene Planeten auslesen und Myrigates übertragen
				$query = query("
					SELECT
						planetenID
					FROM
						".PREFIX."planeten
					WHERE
						planetenMyrigate > 0
						AND planeten_systemeID IN(".$sids.")
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$plids = array();
				
				while($row = mysql_fetch_assoc($query)) {
					$plids[] = $row['planetenID'];
				}
				
				if(count($plids)) {
					query("
						UPDATE
							".PREFIX."myrigates
						SET
							myrigates_galaxienID = ".$_POST['gala']."
						WHERE
							myrigates_planetenID IN(".implode(", ", $plids).")
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
				
				// Systemzahlen der beteiligen Galaxien ermitteln
				$query = query("
					SELECT
						systeme_galaxienID,
						COUNT(*) as systemeAnzahl
					FROM
						".PREFIX."systeme
					WHERE
						systeme_galaxienID IN(".$gids.")
					GROUP BY
						systeme_galaxienID
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$scount = array();
				
				while($row = mysql_fetch_assoc($query)) {
					$scount[$row['systeme_galaxienID']] = $row['systemeAnzahl'];
				}
				
				// Zahl der eingescannten Systeme ermitteln
				$query = query("
					SELECT
						systeme_galaxienID,
						COUNT(*) as systemeAnzahl
					FROM
						".PREFIX."systeme
					WHERE
						systeme_galaxienID IN(".$gids.")
						AND systemeUpdate > 0
					GROUP BY
						systeme_galaxienID
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$scount2 = array();
				
				while($row = mysql_fetch_assoc($query)) {
					$scount2[$row['systeme_galaxienID']] = $row['systemeAnzahl'];
				}
				
				// Galaxien eintragen, aktualisieren oder löschen
				foreach($g as $gala) {
					$s2 = isset($scount2[$gala]) ? $scount2[$gala] : 0;
					
					// Galaxie löschen
					if(!isset($scount[$gala])) {
						query("
							DELETE FROM
								".PREFIX."galaxien
							WHERE
								galaxienID = ".$gala."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
					// neue Galaxie -> eintragen bzw aktualisieren
					else if($gala == $_POST['gala']) {
						query("
							INSERT INTO
								".PREFIX."galaxien
							SET
								galaxienID = ".$gala.",
								galaxienSysteme = ".$scount[$gala].",
								galaxienSysScanned = ".$s2."
							ON DUPLICATE KEY UPDATE
								galaxienSysteme = ".$scount[$gala].",
								galaxienSysScanned = ".$s2.",
								galaxienGate = 0,
								galaxienGateSys = 0,
								galaxienGateX = 0,
								galaxienGateY = 0,
								galaxienGateZ = 0,
								galaxienGatePos = 0
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
					// Galaxie aktualisieren, Gate löschen
					else {
						query("
							UPDATE
								".PREFIX."galaxien
							SET
								galaxienSysteme = ".$scount[$gala].",
								galaxienSysScanned = ".$s2.",
								galaxienGate = 0,
								galaxienGateSys = 0,
								galaxienGateX = 0,
								galaxienGateY = 0,
								galaxienGateZ = 0,
								galaxienGatePos = 0
							WHERE
								galaxienID = ".$gala."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
				}
				
				// Gates und Gate-Entfernung von Planeten und Systemen löschen
				query("
					UPDATE
						".PREFIX."planeten
						LEFT JOIN ".PREFIX."systeme
							ON systemeID = planeten_systemeID
					SET
						planetenGateEntf = NULL
					WHERE
						systeme_galaxienID IN(".$gids.")
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				query("
					UPDATE
						".PREFIX."systeme
					SET
						systemeGateEntf = NULL
					WHERE
						systeme_galaxienID IN(".$gids.")
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Ausgabe
				$tmpl->content = '
				Galaxie '.$_POST['gala'].' erfolgreich eingescannt.
				<br /><br />
				Die Verschmelzung wurde repariert.';
				
				// Log-Eintrag
				if($config['logging'] >= 1) {
					insertlog(14, 'benutzt die Funktion zum Reparieren von Verschmelzungen');
				}
			}
			// ein Teil der Systeme in anderen Galaxien -> Verschmelzung
			else {
				// Verschmelzungsgalaxien ermitteln
				$s = array();
				while($row = mysql_fetch_array($query)) {
					$s[] = $row[0];
				}
				$s = implode(', ', $s);
				
				// Ausgabe
				$tmpl->content = '
				Galaxie '.$_POST['gala'].' erfolgreich eingetragen.
				<br /><br />
				Damit alle Planeten erfasst werden k&ouml;nnen, muss jedes System mindestens einmal verdeckt gescannt werden.
				<br /><br />
				<span style="font-weight:bold">
					Einige der Systeme sind schon in folgenden Galaxien eingetragen: '.$s.'
					<br /><br />
					'.($user->rechte['verwaltung_galaxien2'] ? 'Bitte benutze die Verschmelzungs-Funktion, um sie in Galaxie '.$_POST['gala'].' zu transferieren!' : 'Bitte jemanden mit Verschmelzungs-Rechten, sie in Galaxie '.$_POST['gala'].' zu transferieren!').'
				</span>
				<br /><br />
				';
			}
			
			// nur ein Teil schon eingetragen -> Gate-Entfernung bei allen eintragen
			// Gate ermitteln
			$query = query("
				SELECT
					galaxienGate,
					galaxienGateX,
					galaxienGateY,
					galaxienGateZ,
					galaxienGatePos
				FROM
					".PREFIX."galaxien
				WHERE
					galaxienID = ".$_POST['gala']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$data = mysql_fetch_assoc($query);
			
			// Gate eingetragen -> Gate-Entfernungen berechnen
			if($data['galaxienGate']) {
				query("
					UPDATE ".PREFIX."systeme
					SET
						systemeGateEntf = ".entf_mysql("systemeX", "systemeY", "systemeZ", "1", $data['galaxienGateX'], $data['galaxienGateY'], $data['galaxienGateZ'], $data['galaxienGatePos'])."
					WHERE
						systeme_galaxienID = ".$_POST['gala']."
						AND systemeGateEntf IS NULL
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
		}
		
		// Cache-Eintrag löschen
		$cache->remove('fow_erfasst');
		
		for($i=1; $i<=100; $i++) {
			$cache->remove('stats'.$i);
		}
		
		// Log-Eintrag
		if($config['logging'] >= 1) {
			insertlog(14, 'trägt die Galaxie '.$_POST['gala'].' ein');
		}
	}
}


// Ausgabe
$tmpl->output();



?>