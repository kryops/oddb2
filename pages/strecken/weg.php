<?php
/**
 * pages/strecken/weg.php
 * schnellster Weg
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


/**
 * gibt "ID Inhaber Ally" für ein Myrigate aus
 * @param $id int Plani-ID des Myrigates
 *
 * @return string HTML-Content
 */
function mgateoutput($id) {
	global $mgdata;
	
	// ungültige ID -> nichts zurückgeben
	if(!is_numeric($id) OR !isset($mgdata[$id])) return '';
	
	$data =& $mgdata[$id];
	
	$content = '<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$id.'">'.$id.'</a> &nbsp;(';
	// Inhaber
	if($data['playerName'] != NULL) {
		$content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['planeten_playerID'].'&amp;ajax">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
	}
	// frei, Lux oder Altrasse
	else if($data['planeten_playerID'] == 0) $content .= '<i>frei</i>';
	else if($data['planeten_playerID'] == -2) $content .= '<i>Lux</i>';
	else if($data['planeten_playerID'] == -3) $content .= '<i>Altrasse</i>';
	// unbekannter Inhaber
	else {
		$content .= '<i>unbekannt</i>';
	}
	$content .= ' <span class="small">';
	// Allianz anzeigen, wenn Spieler bekannt
	if($data['playerName'] != NULL) {
		// hat Allianz
		if($data['allianzenTag'] != NULL) {
			$content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$data['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
		}
		// allianzlos
		else if(!$data['player_allianzenID']) {
			$content .= '<i>allianzlos</i>';
		}
		// unbekannte Allianz
		else {
			$content .= '<i>Allianz unbekannt</i>';
		}
	}
	$content .= '</span>)';
	
	// HTML zurückgeben
	return $content;
}



// Inhalt
if($_GET['sp'] == 'weg') {
	// Allianz-Tags ermitteln
		$query = query("
			SELECT
				allianzenID,
				allianzenTag
			FROM 
				".GLOBPREFIX."allianzen
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = allianzenID
			WHERE
				register_allianzenID IS NOT NULL
				OR allianzenID = ".$user->allianz."
			ORDER BY
				allianzenID ASC
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$allyopt = '';
		while($row = mysql_fetch_assoc($query)) {
			$allyopt .= '
							<option value="'.$row['allianzenID'].'"'.($row['allianzenID'] == $user->allianz ? ' selected="selected"' : '').'>'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</option>';
		}
		
		// Content erzeugen
		$csw->data['weg']['content'] = '
	<div class="hl2">
		schnellster Weg
	</div>
	
	<div class="icontent">
		Diese Funktion berechnet verschiedene M&ouml;glichkeiten, die Flugdauer innerhalb einer Galaxie zu verk&uuml;rzen:
		<ul>
			<li>Myrigate &rarr; Riss &rarr; Ziel</li>
			<li>Myrigate &rarr; Riss &rarr; anderes Myrigate &rarr; Riss &rarr; Ziel</li>
			<li>Myrigate &rarr; Gate &rarr; Ziel</li>
			<li>Myrigate &rarr; Gate &rarr; anderes Myrigate &rarr; Riss &rarr; Ziel</li>
		</ul>
		<br />
		Man kann nur bei den Myrigates zum Riss springen, mit deren Inhaber man einen NAP hat.
		<br /><br /><br />
		
		<form action="#" name="strecken_weg" onsubmit="return form_send(this, \'index.php?p=strecken&amp;sp=weg_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
		<div class="fcbox formcontent center" style="padding:10px;width:600px">
			Startpunkt: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:80px" name="start" />
			 &nbsp; &nbsp; 
			Ziel: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:80px" name="dest" />
			 &nbsp; &nbsp; 
			Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" />
			<div class="small hint" style="margin:-8px 100px 6px 0px">
				(Planet, System oder Koordinaten) &nbsp; &nbsp;(Planet oder System)
			</div>
			<div style="text-align:left;margin:0px 0px 15px 110px">
				NAPs der Allianz 
				&nbsp;<select name="hak" size="1">
					'.$allyopt.'
				</select>&nbsp; 
				benutzen
				<br />
				oder manuell eintragen: <input type="text" class="text center" name="hakmanuell" /> 
				&nbsp; <span class="small hint">(IDs mit Komma getrennt)</span>
			</div>
			<input type="submit" class="button" value="schnellsten Weg berechnen" />
		</div>
		</form>
		<div class="ajax center" style="line-height:20px"></div>
	</div>';
}


// abschicken
else if($_GET['sp'] == 'weg_send') {
	// keine Berechtigung
	if(!$user->rechte['strecken_weg']) $tmpl->error = 'Du hast keine Berechtigung!';
	// Daten unvollständig
	else if(!isset($_POST['start'], $_POST['dest'], $_POST['antrieb'], $_POST['hak'], $_POST['hakmanuell'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// Antrieb ungültig
	else if((int)$_POST['antrieb'] < 1) {
		$tmpl->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
	}
	// Berechtigung
	else {
		// Daten sichern
		$_POST['antrieb'] = (int)$_POST['antrieb'];
		$_POST['hak'] = (int)$_POST['hak'];
		$_POST['hakmanuell'] = preg_replace('/[^\d,]/Uis', '', $_POST['hakmanuell']);
		
		// Startpunkt und Zielpunkt in Koordinaten umwandeln
		$points = array($_POST['start'], $_POST['dest']);
		
		foreach($points as $key=>$val) {
			$name = $key ? 'Zielpunkt' : 'Startpunkt';
			
			// Daten in Koordinaten umwandeln
			$val = flug_point($val);
			$points[$key] = $val;
			
			// Fehler
			if(!is_array($val) AND !$tmpl->error) {
				if($val == 'coords') $tmpl->error = 'Ung&uuml;ltige Koordinaten beim '.$name.' eingegeben!';
				else if($val == 'data') $tmpl->error = 'Ung&uuml;ltige Daten beim '.$name.' eingegeben!';
				else $tmpl->error = $name.' nicht gefunden!';
			}
		}
		
		if(!$tmpl->error) {
			// unterschiedliche Galaxie
			if($points[0][0] != $points[1][0]) {
					$tmpl->error = 'Start- und Zielpunkt sind in einer unterschiedlichen Galaxie!';
			}
			// kein Zugriff auf die Galaxie
			else if($user->protectedGalas AND in_array($points[0][0], $user->protectedGalas)) {
				$tmpl->error = 'Deine Allianz hat keinen Zugriff auf diese Galaxie!';
			}
		
			$start =& $points[0];
			$ziel =& $points[1];
			$gala =& $points[0][0];
		}
		
		// implodierten HAK-String erzeugen
		if(!$tmpl->error) {
			// HAKs einer DB-Allianz
			if(trim($_POST['hakmanuell']) == '') {
				$query = query("
					SELECT
						status_allianzenID
					FROM 
						".PREFIX."allianzen_status
					WHERE
						statusDBAllianz = ".$_POST['hak']."
						AND statusStatus IN(".implode(', ', $status_freund).")
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$hak = array();
				while($row = mysql_fetch_assoc($query)) {
					$hak[] = $row['status_allianzenID'];
				}
				$hak = implode(', ', $hak);
				
				// keine HAKs eingetragen
				if($hak == '') $tmpl->error = 'F&uuml;r die Allianz sind keine NAPs eingetragen!';
			}
			// HAKs manuell eingetragen
			else {
				$hak = array();
				$_POST['hakmanuell'] = explode(',', $_POST['hakmanuell']);
				foreach($_POST['hakmanuell'] as $key=>$val) {
					$val = (int)trim($val);
					if($val) $hak[] = $val;
				}
				$hak = implode(', ', $hak);
				
				// keine HAKs eingetragen
				if($hak == '') $tmpl->error = 'Eingabe der NAPs ung&uuml;ltig!';
			}
		}
		
		if(!$tmpl->error) {
			// Gate-Position ermitteln
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
					galaxienID = ".$gala."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$gate = false;
			
			if(mysql_num_rows($query)) {
				$row = mysql_fetch_assoc($query);
				// Gate erfasst
				if($row['galaxienGate']) {
					$gate = array(
						$row['galaxienGateX'],
						$row['galaxienGateY'],
						$row['galaxienGateZ'],
						$row['galaxienGatePos']
					);
				}
			}
			
			// nächstes Myrigate ermitteln
			$nextmg = false;
			$nextmgdata = false;
			$entf_nextmg = false;
			$sprung = array();
			
			$query = query("
				SELECT
					myrigatesSprung,
					
					planetenID,
					planetenPosition,
					planetenMyrigate,
					".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $start[1], $start[2], $start[3], $start[4])." as planetenEntfernung,
					
					systemeX,
					systemeY,
					systemeZ,
					
					planeten_playerID,
					playerName,
					player_allianzenID,
					
					allianzenTag
				FROM 
					".PREFIX."myrigates
					LEFT JOIN ".PREFIX."planeten
						ON myrigates_planetenID = planetenID
					LEFT JOIN ".PREFIX."systeme
						ON planeten_systemeID = systemeID
					LEFT JOIN ".GLOBPREFIX."player
						ON planeten_playerID = playerID
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
				WHERE
					myrigates_galaxienID = ".$gala."
					AND (player_allianzenID IN (".$hak.") OR (planeten_playerID = 0 AND myrigatesSprung = 1))
				ORDER BY
					planetenEntfernung ASC
				LIMIT 1
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query)) {
				$row = mysql_fetch_assoc($query);
				$nextmg = $row['planetenID'];
				$nextmgdata = $row;
				$entf_nextmg = $row['planetenEntfernung'];
				if($row['myrigatesSprung']) {
					$sprung[$row['planetenID']] = $row['myrigatesSprung'];
				}
			}
			
			// alle anderen Myrigates ermitteln
			$mgates = array();
			$mgdata = array();
			
			$query = query("
				SELECT
					myrigatesSprung,
					planetenID,
					planetenPosition,
					planetenMyrigate,
					
					systemeX,
					systemeY,
					systemeZ,
					
					planeten_playerID,
					playerName,
					player_allianzenID,
					
					allianzenTag
				FROM 
					".PREFIX."myrigates
					LEFT JOIN ".PREFIX."planeten
						ON myrigates_planetenID = planetenID
					LEFT JOIN ".PREFIX."systeme
						ON planeten_systemeID = systemeID
					LEFT JOIN ".GLOBPREFIX."player
						ON planeten_playerID = playerID
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
				WHERE
					myrigates_galaxienID = ".$gala."
					AND player_allianzenID IN (".$hak.")
					AND myrigatesSprung = 0
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$risse = array();
			
			while($row = mysql_fetch_assoc($query)) {
				
				// Position speichern
				$mgates[$row['planetenID']] = array(
					$row['systemeX'],
					$row['systemeY'],
					$row['systemeZ'],
					$row['planetenPosition']
				);
				
				// Myrigate -> Riss
				$risse[] = $row['planetenMyrigate'];
				
				// Inhaberdaten speichern
				$mgdata[$row['planetenID']] = array(
					'planeten_playerID'=>$row['planeten_playerID'],
					'playerName'=>$row['playerName'],
					'player_allianzenID'=>$row['player_allianzenID'],
					'allianzenTag'=>$row['allianzenTag']
				);
			}
			
			// keine Myrigates gefunden
			if(!count($risse)) {
				$risse = array(0);
			}
			
			// Risse ermitteln
			$query = query("
				SELECT
					planetenID,
					planetenRiss,
					planetenPosition,
					systemeX,
					systemeY,
					systemeZ
				FROM 
					".PREFIX."planeten
					LEFT JOIN  ".PREFIX."systeme
						ON systemeID = planeten_systemeID
				WHERE
					systeme_galaxienID = ".$gala."
					AND planetenID IN (".implode(', ', $risse).")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				// Riss-Daten an die Myrigates anhängen
				if(isset($mgates[$row['planetenRiss']])) {
					array_push(
						$mgates[$row['planetenRiss']],
						$row['systemeX'],
						$row['systemeY'],
						$row['systemeZ'],
						$row['planetenPosition']
					);
				}
			}
			
			// ungültige Myrigates aufräumen
			foreach($mgates as $key=>$data) {
				if(count($data) != 8) {
					unset($mgates[$key]);
				}
			}
			
			// direkte Entfernung berechnen
			$entf_direkt = entf(
				$start[1],
				$start[2],
				$start[3],
				$start[4],
				$ziel[1],
				$ziel[2],
				$ziel[3],
				$ziel[4]
			);
			
			// gibt es einen schnelleren Weg?
			$schneller = false;
			
			// nur weitermachen, wenn Myrigates eingetragen sind
			if(count($mgates) OR $nextmg) {
				// Wenn Gate eingetragen, Start-MGate-Gate-Ziel und Start-Mgate-Gate-MGate-Riss-Ziel berechnen
				$entf_gate = false;
				$gate2 = false;
				$entf_gate2 = false;
				
				// Gate eingetragen
				if($gate) {
					// nächstes Myrigate ermitteln
					//$nextmg = false;
					//$entf_nextmg = false;
					
					if(!$nextmg) {
						foreach($mgates as $key=>$data) {
							$entf = entf(
								$start[1],
								$start[2],
								$start[3],
								$start[4],
								$data[0],
								$data[1],
								$data[2],
								$data[3]
							);
							
							if($nextmg === false OR $entf < $entf_nextmg) {
								$nextmg = $key;
								$entf_nextmg = $entf;
							}
						}
					}
					
					// Start-MGate-Gate-Ziel berechnen
					$entf_gate = $entf_nextmg +
								entf(
									$gate[0],
									$gate[1],
									$gate[2],
									$gate[3],
									$ziel[1],
									$ziel[2],
									$ziel[3],
									$ziel[4]
								);
					
					// übers Gate schneller als direkter Weg
					if($entf_gate < $entf_direkt) $schneller = true;
					
					// Start-Mgate-Gate-MGate-Riss-Ziel berechnen
					foreach($mgates as $key=>$data) {
						$entf = $entf_nextmg +
								entf(
									$gate[0],
									$gate[1],
									$gate[2],
									$gate[3],
									$data[0],
									$data[1],
									$data[2],
									$data[3]
								) + 
								entf(
									$data[4],
									$data[5],
									$data[6],
									$data[7],
									$ziel[1],
									$ziel[2],
									$ziel[3],
									$ziel[4]
								);
						
						if($gate2 === false OR $entf < $entf_gate2) {
							$gate2 = $key;
							$entf_gate2 = $entf;
						}
					}
					
					// Gate+Myrigate kürzer als direkter Weg
					if($entf_gate2 < $entf_direkt) $schneller = true;
				}
				
				// Entfernung über Myrigate / 2 Myrigates
				$mgate2 = false;
				$entf_mgate = array();
				$entf_mgatemin = false;
				$entf_mgate2 = false;
				
				foreach($mgates as $key=>$data) {
					// Entfernung Start-Myrigate berechnen
					$entf1 = entf(
								$start[1],
								$start[2],
								$start[3],
								$start[4],
								$data[0],
								$data[1],
								$data[2],
								$data[3]
							);
					
					// Entfernung über 2. Myrigate berechnen
					foreach($mgates as $key2=>$data2) {
						if($key2 != $key) {
							// Start-Mgate1-Riss1-Mgate2-Riss2-Ziel
							$entf = $entf1 + 
									entf(
										$data[4],
										$data[5],
										$data[6],
										$data[7],
										$data2[0],
										$data2[1],
										$data2[2],
										$data2[3]
									) +
									entf(
										$data2[4],
										$data2[5],
										$data2[6],
										$data2[7],
										$ziel[1],
										$ziel[2],
										$ziel[3],
										$ziel[4]
									);
							
							if($mgate2 === false OR $entf < $entf_gate2) {
								$mgate2 = array($key, $key2);
								$entf_mgate2 = $entf;
								// über 2 Myrigates schneller als direkter Weg
								if($entf < $entf_direkt) $schneller = true;
							}
						}
					}
					
					// normale Entfernung Start-Mgate-Riss-Ziel berechnen
					$entf = $entf1 + 
							entf(
								$data[4],
								$data[5],
								$data[6],
								$data[7],
								$ziel[1],
								$ziel[2],
								$ziel[3],
								$ziel[4]
							);
					
					if($entf_mgatemin === false OR $entf < $entf_mgatemin) {
						$entf_mgatemin = $entf;
					}
					
					// zur Liste hinzufügen, wenn kürzer als direkter Weg
					if($entf < $entf_direkt) {
						$entf_mgate[$key] = $entf;
					}
				}
				
				// über 1 Myrigate schneller als direkter Weg
				if($entf_mgatemin !== false AND $entf_mgatemin < $entf_direkt) $schneller = true;
			}
			
			// nächstes nicht-HAK-MG ins Array übernehmen
			if($nextmgdata AND !isset($mgates[$nextmg])) {
				// Position speichern
				$mgates[$nextmgdata['planetenID']] = array(
					$nextmgdata['systemeX'],
					$nextmgdata['systemeY'],
					$nextmgdata['systemeZ'],
					$nextmgdata['planetenPosition']
				);
				
				// Inhaberdaten speichern
				$mgdata[$nextmgdata['planetenID']] = array(
					'planeten_playerID'=>$nextmgdata['planeten_playerID'],
					'playerName'=>$nextmgdata['playerName'],
					'player_allianzenID'=>$nextmgdata['player_allianzenID'],
					'allianzenTag'=>$nextmgdata['allianzenTag']
				);
			}
			
			
			// kein schnellerer Weg gefunden
			if(!$schneller) {
				$tmpl->content = '
					<br />
					Der direkte Weg ist der schnellste: <b>'.flugdauer($entf_direkt, $_POST['antrieb']).'</b> bei A'.$_POST['antrieb'];
			}
			// es gibt einen schnelleren Weg
			else {
				$tmpl->content .= '
					<br />
					direkter Weg: <b>'.flugdauer($entf_direkt, $_POST['antrieb']).'</b> bei A'.$_POST['antrieb'].'
					<br /><br />';
				// Entfernung über Gate+Myrigate
				if($entf_gate2 !== false AND $entf_gate2 < $entf_direkt AND ($entf_mgatemin === false OR $entf_gate2 < $entf_mgatemin)) {
					$tmpl->content .= '
					vom '.(isset($sprung[$nextmg]) ? 'Sprunggenerator' : 'Myrigate').' &nbsp; <b>'.mgateoutput($nextmg).'</b> &nbsp; zum Gate springen,
					<br />
					dann &uuml;ber '.(isset($sprung[$gate2]) ? 'Sprunggenerator' : 'Myrigate').' &nbsp; <b>'.mgateoutput($gate2).'</b> &nbsp; &rarr; &nbsp; <b>'.flugdauer($entf_gate2, $_POST['antrieb']).'</b>
					<br /><br />';
				}
				// Entfernung über Gate
				if($entf_gate !== false AND $entf_gate < $entf_direkt AND ($entf_mgatemin === false OR $entf_gate < $entf_mgatemin)) {
					$tmpl->content .= '
					vom '.(isset($sprung[$nextmg]) ? 'Sprunggenerator' : 'Myrigate').' &nbsp; <b>'.mgateoutput($nextmg).'</b> &nbsp; zum Gate springen,
					<br />
					danach direkt zum Ziel &nbsp; &rarr; &nbsp; <b>'.flugdauer($entf_gate, $_POST['antrieb']).'</b>
					<br /><br />';
				}
				// Entfernung über 2 Myrigates
				if($entf_mgate2 !== false AND $entf_mgate2 < $entf_direkt AND ($entf_mgatemin === false OR $entf_mgate2 < $entf_mgatemin)) {
					$tmpl->content .= '
					&uuml;ber '.(isset($sprung[$mgate2[0]]) ? 'Sprunggenerator' : 'Myrigate').' &nbsp; <b>'.mgateoutput($mgate2[0]).'</b>,
					<br />
					danach &uuml;ber '.(isset($sprung[$mgate2[1]]) ? 'Sprunggenerator' : 'Myrigate').' &nbsp; <b>'.mgateoutput($mgate2[1]).'</b> &nbsp; &rarr; &nbsp; <b>'.flugdauer($entf_mgate2, $_POST['antrieb']).'</b>
					<br /><br />';
				}
				
				// Entfernung über einfache Myrigates
				asort($entf_mgate);
				
				$i = 1;
				foreach($entf_mgate as $key=>$entf) {
					// maximal 5 ausgeben
					if($i > 5) break;
					
					$tmpl->content .= '
					&uuml;ber '.(isset($sprung[$key]) ? 'Sprunggenerator' : 'Myrigate').' &nbsp; <b>'.mgateoutput($key).'</b> &nbsp; &rarr; &nbsp; <b>'.flugdauer($entf, $_POST['antrieb']).'</b>
					<br />';
					$i++;
				}
			}
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(7, 'berechnet den schnellsten Weg von '.$_POST['start'].' nach '.$_POST['dest']);
			}
		}
	}
	// Ausgabe
	if($tmpl->error) $tmpl->error = '<br />'.$tmpl->error;
	$tmpl->output();
}



?>