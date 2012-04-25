<?php
/**
 * pages/strecken/saveroute.php
 * Saveroutengenerator
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



/**
 * System beim Saveroutengenerator ausgeben
 * @param $data Array System-Datensatz
 *
 * @return string HTML-Ausgabe
 */
function saveroute_output($data) {
	$content = '
		System <a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$data['planeten_systemeID'].'&amp;ajax" style="font-weight:bold">'.$data['planeten_systemeID'].'</a> 
		Planet <a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$data['planetenID'].'&amp;ajax" style="font-weight:bold">'.$data['planetenID'].'</a> 
		von ';
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
	$content .= '</span>';
	
	// HTML zurückgeben
	return $content;
}




// keine Berechtigung
if(!$user->rechte['strecken_saveroute']) $tmpl->error = 'Du hast keine Berechtigung!';
// Daten unvollständig
else if(!isset($_POST['gala'], $_POST['antrieb'], $_POST['count'], $_POST['data'], $_POST['datamanuell'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// Antrieb ungültig
else if((int)$_POST['antrieb'] < 1) {
	$tmpl->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
}
// Anzahl ungültig
else if((int)$_POST['count'] < 1) {
	$tmpl->error = 'Ung&uuml;ltige Anzahl eingegeben!';
}
// Berechtigung
else {
	// Daten sichern
	$_POST['antrieb'] = (int)$_POST['antrieb'];
	$_POST['gala'] = (int)$_POST['gala'];
	$_POST['count'] = (int)$_POST['count'];
	if(!$_POST['gala']) {
		$tmpl->error = 'Ung&uuml;ltige Galaxie eingegeben!';
	}
	// kein Zugriff auf die Galaxie
	else if($user->protectedGalas AND in_array($_POST['gala'], $user->protectedGalas)) {
		$tmpl->error = 'Deine Allianz hat keinen Zugriff auf diese Galaxie!';
	}
	
	$data = array('meta', 'ally', 'user', 'free', 'all');
	if(!in_array($_POST['data'], $data)) {
		$tmpl->error = 'Ung&uuml;ltigen Typ angegeben!';
	}
	
	// manuell eingetragene Allianzen validieren
	$_POST['datamanuell'] = preg_replace('/[^\d,]/Uis', '', $_POST['datamanuell']);
	if($_POST['datamanuell'] != '') {
		$_POST['datamanuell'] = explode(',', $_POST['datamanuell']);
		foreach($_POST['datamanuell'] as $key=>$val) {
			if(!is_numeric($val)) {
				unset($_POST['datamanuell'][$key]);
			}
		}
		
		// gültig -> implodieren
		if(count($_POST['datamanuell'])) {
			$_POST['datamanuell'] = implode(', ', $_POST['datamanuell']);
		}
		// ungültig -> andere Einstellung nehmen
		else {
			$_POST['datamanuell'] = '';
		}
	}
	
	if(!$tmpl->error) {
		// Bedingungen aufstellen
		$tables = "
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID";
		$cond = '';
		$cond2 = '';
		$allies = array();
		// Allianzen manuell eingetragen
		if($_POST['datamanuell'] != '') {
			$cond = " AND player_allianzenID IN (".$_POST['datamanuell'].")";
		}
		// Planeten der Meta
		else if($_POST['data'] == 'meta') {
			$allies2 = array();
			// Allianzen der Meta ermitteln
			$query = query("
				SELECT
					status_allianzenID
				FROM
					".PREFIX."allianzen_status
				WHERE
					statusDBAllianz = ".$user->allianz."
					AND statusStatus = ".$status_meta."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$allies[] = $row['status_allianzenID'];
			}
			
			// eigene Allianz hinzufügen
			if(!in_array($user->allianz, $allies)) {
				$allies[] = $user->allianz;
			}
			
			$cond = " AND player_allianzenID IN (".implode(', ', $allies).")";
		}
		// Planeten der Allianz
		else if($_POST['data'] == 'ally') {
			$cond = " AND player_allianzenID = ".$user->allianz;
			
			$allies[] = $user->allianz;
		}
		// eigene Planeten
		else if($_POST['data'] == 'user') {
			$cond = " AND planeten_playerID = ".$user->id;
			
			$tables = "";
			
			$allies[] = $user->allianz;
		}
		// freie Planeten
		else if($_POST['data'] == 'free') {
			$cond = " AND planeten_playerID = 0";
			
			$tables = "";
		}
		
		// Allianzen erweitern: allianzlose, leere und unbekannte dürfen immer drin sein
		$allies[] = 0;
		$allies[] = -1;
		
		// Myrigate-Systeme ermitteln
		if(isset($_POST['mgates'])) {
			$mgates = array();
			
			$query = query("
				SELECT
					DISTINCT planeten_systemeID
				FROM
					".PREFIX."myrigates
					LEFT JOIN ".PREFIX."planeten
						ON planetenID = myrigates_planetenID
				WHERE
					myrigates_galaxienID = ".$_POST['gala']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$mgates[] = $row['planeten_systemeID'];
			}
			
			// Saverouten-Systeme filtern
			if(count($mgates)) {
				$cond2 = " AND planeten_systemeID IN (".implode(', ', $mgates).")";
			}
		}
		
		// Systeme ermitteln
		$systeme = array();
			
		$query = query("
			SELECT
				systemeID,
				systemeX,
				systemeY,
				systemeZ,
				systemeAllianzen
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				".$tables."
			WHERE
				systeme_galaxienID = ".$_POST['gala']."
				AND systemeUpdate > 0
				".$cond.$cond2." 
			GROUP BY planeten_systemeID
			ORDER BY NULL
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$r = true;
			// nur die ausgewählten Allianzen dürfen im System vertreten sein
			if(isset($_POST['allysonly']) AND $_POST['data'] != 'all') {
				foreach($allies as $val) {
					$row['systemeAllianzen'] = str_replace('+'.$val.'+', '', $row['systemeAllianzen']);
				}
				if(trim($row['systemeAllianzen']) != '') {
					$r = false;
				}
			}
			
			if($r) {
				$systeme[$row['systemeID']] = array(
					$row['systemeX'],
					$row['systemeY'],
					$row['systemeZ']
				);
			}
		}
		
		// keine Saveroute gefunden
		if(count($systeme) < 2) {
			$tmpl->content .= '
			<br />
			<div class="center" style="font-weight:bold">
				Es wurde keine Saveroute gefunden, die den Kriterien entspricht!
			</div>';
		}
		// Saverouten berechnen
		else {
			$entf = array();
			
			foreach($systeme as $key=>$data) {
				foreach($systeme as $key2=>$data2) {
					// nur berechnen, wenn es die Strecke andersherum nicht schon gibt
					if($key != $key2 AND !isset($entf[$key2.'-'.$key])) {
						$entf[$key.'-'.$key2] = entf(
													$data[0],
													$data[1],
													$data[2],
													1,
													$data2[0],
													$data2[1],
													$data2[2],
													1
												);
					}
				}
			}
			
			// Entfernungen sortieren
			arsort($entf);
			
			// Array evtl kürzen
			if(count($entf) > $_POST['count']) {
				$entf = array_slice($entf, 0, $_POST['count'], true);
			}
			
			// System-Informationen ermitteln
			$sysinfo = array();
			
			foreach($entf as $key=>$val) {
				$data = explode('-', $key);
				if(!in_array($data[0], $sysinfo)) $sysinfo[] = $data[0];
				if(!in_array($data[1], $sysinfo)) $sysinfo[] = $data[1];
			}
			
			$query = query("
				SELECT
					planetenID,
					planetenName,
					planetenPosition,
					planeten_playerID,
					planeten_systemeID,
					
					playerName,
					player_allianzenID,
					
					allianzenTag
				FROM
					".PREFIX."planeten
					LEFT JOIN ".GLOBPREFIX."player
						ON playerID = planeten_playerID
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
				WHERE
					planeten_systemeID IN(".implode(', ', $sysinfo).")
					".$cond."
				GROUP BY planeten_systemeID
				ORDER BY NULL
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$sysinfo = array();
			
			while($row = mysql_fetch_assoc($query)) {
				$sysinfo[$row['planeten_systemeID']] = $row;
			}
			
			// Saverouten ausgeben
			foreach($entf as $key=>$data) {
				$key = explode('-', $key);
				
				if(isset($sysinfo[$key[0]], $sysinfo[$key[1]])) {
					$data += abs($sysinfo[$key[0]]['planetenPosition']-$sysinfo[$key[1]]['planetenPosition'])*300;
					$dauer = flugdauer($data, $_POST['antrieb']);
					$sys1 =& $sysinfo[$key[0]];
					$sys2 =& $sysinfo[$key[1]];
					
					$tmpl->content .= '
					
					<div class="fcbox" style="margin-top:8px;width:80%">
						<table style="width:100%">
						<tr>
							<td style="width:70px;font-weight:bold">'.$dauer.'</td>
							<td>
								'.saveroute_output($sys1).'
								<br />
								'.saveroute_output($sys2).'
							</td>
							<td style="width:140px;line-height:14px">
								Saveroute G'.$_POST['gala'].'<br />
								'.$sys1['planetenID'].'='.htmlspecialchars($sys1['planetenName'], ENT_COMPAT, 'UTF-8').'<br />
								'.$sys2['planetenID'].'='.htmlspecialchars($sys2['planetenName'], ENT_COMPAT, 'UTF-8').'<br />
								'.$dauer.' (A'.$_POST['antrieb'].')
							</td>
						</tr>
						</table>
					</div>';
				}
			}
		}
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(8, 'berechnet eine Saveroute f&uuml;r Galaxie '.$_POST['gala']);
		}
	}
}
// Ausgabe
if($tmpl->error) $tmpl->error = '<br />'.$tmpl->error;
$tmpl->output();


?>