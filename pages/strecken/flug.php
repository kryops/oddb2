<?php
/**
 * pages/strecken/flug.php
 * Strecken- und Flugberechnungen:
 * - Entfernung nach
 * - die nächsten Systeme
 * - Flugposition
 * - die nächsten/entferntesten Planeten von
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// Entfernung berechnen (AJAX)
if($_GET['sp'] == 'flug_entf') {
	// keine Berechtigung
	if(!$user->rechte['strecken_flug']) $tmpl->error = 'Du hast keine Berechtigung!';
	// Daten unvollständig
	else if(!isset($_POST['start'], $_POST['dest_entf'], $_POST['antrieb'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// Antrieb ungültig
	else if((int)$_POST['antrieb'] < 1) {
		$tmpl->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
	}
	// Berechtigung
	else {
		// Titel
		$tmpl->name = 'Entfernung von '.htmlspecialchars($_POST['start'], ENT_QUOTES, 'UTF-8').' nach '.htmlspecialchars($_POST['dest_entf'], ENT_QUOTES, 'UTF-8');
		
		
		// Daten sichern
		$_POST['antrieb'] = (int)$_POST['antrieb'];
		
		// Startpunkt und Zielpunkt in Koordinaten umwandeln
		$points = array($_POST['start'], $_POST['dest_entf']);
		
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
		
		// bis jetzt noch keine Fehler
		if(!$tmpl->error) {
			// gleiche Galaxie
			if($points[0][0] != $points[1][0]) {
				$tmpl->error = 'Start- und Zielpunkt sind in einer unterschiedlichen Galaxie!';
			}
			// kein Zugriff auf die Galaxie
			else if($user->protectedGalas AND in_array($points[0][0], $user->protectedGalas)) {
				$tmpl->error = 'Deine Allianz hat keinen Zugriff auf diese Galaxie!';
			}
			// Entfernung ausgeben
			else {
				$entf = entf(
					$points[0][1],
					$points[0][2],
					$points[0][3],
					$points[0][4],
					$points[1][1],
					$points[1][2],
					$points[1][3],
					$points[1][4]
				);
				
				// einfache Ausgabe
				if(isset($_GET['simple'])) {
					$tmpl->content = '<b>'.flugdauer($entf, $_POST['antrieb'], true).'</b> bei Antrieb '.$_POST['antrieb'];
				}
				// detaillierte Ausgabe
				else {
					$tmpl->content = '
						<br />
						<div align="center">
							Entfernung von '.$_POST['start'].' nach '.$_POST['dest_entf'].' bei Antrieb '.$_POST['antrieb'].':
							<br /><br />
							<b>'.flugdauer($entf, $_POST['antrieb'], true).'</b>
						</div>
					';
				}
				
				// Log-Eintrag
				if($config['logging'] >= 2) {
					insertlog(6, 'berechnet die Entfernung zwischen '.$_POST['start'].' und '.$_POST['dest_entf']);
				}
			}
		}
	}
	// Ausgabe
	$tmpl->output();
}

// die nächsten Systeme anzeigen (AJAX)
else if($_GET['sp'] == 'flug_next') {
	// evtl GET-Daten in POST umwandeln
	if(!count($_POST) AND isset($_GET['start'], $_GET['syscount'], $_GET['antrieb'])) {
		$_POST['start'] = $_GET['start'];
		$_POST['syscount'] = $_GET['syscount'];
		$_POST['antrieb'] = $_GET['antrieb'];
	}
	
	// Daten sichern
	$_POST['syscount'] = (int)$_POST['syscount'];
	$_POST['antrieb'] = (int)$_POST['antrieb'];
	
	// keine Berechtigung
	if(!$user->rechte['strecken_flug']) $tmpl->error = 'Du hast keine Berechtigung!';
	// Daten unvollständig
	else if(!isset($_POST['start'], $_POST['syscount'], $_POST['antrieb'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// Antrieb ungültig
	else if((int)$_POST['antrieb'] < 1) {
		$tmpl->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
	}
	// Anzahl ungültig
	else if((int)$_POST['syscount'] < 1) {
		$tmpl->error = 'Ung&uuml;ltige Anzahl eingegeben!';
	}
	// Berechtigung
	else {
		// Titel
		$tmpl->name = 'benachbarte Systeme von '.htmlspecialchars($_POST['start'], ENT_QUOTES, 'UTF-8');
		
		// Ausgangskoordinaten berechnen
		$point = flug_point($_POST['start']);
		
		// Fehler
		if(!is_array($point) AND !$tmpl->error) {
			if($point == 'coords') $tmpl->error = 'Ung&uuml;ltige Koordinaten beim Ausgangspunkt eingegeben!';
			else if($point == 'data') $tmpl->error = 'Ung&uuml;ltige Daten beim Ausgangspunkt eingegeben!';
			else $tmpl->error = 'Ausgangspunkt nicht gefunden!';
		}
		
		// bis jetzt noch keine Fehler
		if(!$tmpl->error) {
			// kein Zugriff auf die Galaxie
			if($user->protectedGalas AND in_array($point[0], $user->protectedGalas)) {
				$tmpl->error = 'Deine Allianz hat keinen Zugriff auf diese Galaxie!';
			}
			// Systeme ermitteln und ausgeben
			else {
				// Allianztags ermitteln
				$query = query("
					SELECT
						allianzenID,
						allianzenTag
					FROM
						".GLOBPREFIX."allianzen
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$allianzen = array();
				while($row = mysql_fetch_array($query)) {
					$allianzen[$row[0]] = $row[1];
				}
				
				$heute = strtotime('today');
				
				// Tabellen-Headline ausgeben
				$tmpl->content = '
					<br />
					<div align="center">
						Die '.$_POST['syscount'].' n&auml;chsten Systeme ausgehend von '.$_POST['start'].':
						<br /><br />
						<table class="data">
						<tr>
							<th>Gala</td>
							<th>System</td>
							<th>erster Planet</td>
							<th>Inhaber</td>
							<th>Allianzen im System</td>
							<th>Entf (A'.$_POST['antrieb'].')</td>
							<th>Scan</td>
							<th>Reservierung</td>
						</tr>';
				
				$t = time();
				$sids = array();
				
				// nächste Systeme abfragen
				$query = query("
					SELECT
						systemeID,
						systemeX,
						systemeZ,
						systemeUpdateHidden,
						systemeUpdate,
						systemeScanReserv,
						systemeReservUser,
						systemeAllianzen,
						
						planetenID,
						
						playerID,
						playerName,
						player_allianzenID,
						playerUmod,
						".entf_mysql("systemeX", "systemeY", "systemeZ", "1", $point[1], $point[2], $point[3], $point[4])." AS systemeEntfernung
					FROM
						".PREFIX."systeme
						LEFT JOIN ".PREFIX."planeten
							ON systemeID = planeten_systemeID
						LEFT JOIN ".GLOBPREFIX."player
							ON planeten_playerID = playerID
					WHERE
						systeme_galaxienID = ".$point[0]."
						".(isset($point[5]) ? "AND systemeID != ".$point[5] : '')."
					GROUP BY
						systemeID
					ORDER BY
						systemeEntfernung ASC,
						planetenID ASC
					LIMIT ".$_POST['syscount']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					// Allianzen auswerten -> Zugriff?
					$access = true;
					$data = str_replace('+', '', explode('++', $row['systemeAllianzen']));
					
					if($user->protectedAllies) {
						foreach($data as $val) {
							if($val AND in_array($val, $user->protectedAllies)) {
								$access = false;
							}
						}
					}
					
					// Gala und System
					$tmpl->content .= '
					<tr>
						<td><span style="color:'.sektor_coord($row['systemeX'], $row['systemeZ']).'">'.$point[0].'</span></td>
						<td><a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$row['systemeID'].'&amp;nav='.$t.'&amp;ajax">'.$row['systemeID'].'</a></td>';
					
					// System verdeckt gescannt
					if($row['systemeUpdateHidden'] AND (!$row['systemeUpdate'] OR !$access)) {
						// Planeten-ID
						$tmpl->content .= '
						<td>
							<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$row['planetenID'].'&amp;ajax">'.$row['planetenID'].'</a>
						</td>
						<td colspan="2">';
						// System noch nicht gescannt
						if(!$row['systemeUpdate']) {
							$tmpl->content .= '<span class="small" style="font-style:italic">System noch nicht voll gescannt</span>';
						}
						// kein Zugriff
						else {
							$tmpl->content .= '<span class="small" style="font-style:italic">Deine Allianz hat keinen Zugriff auf dieses System!</span>';
						}
						$tmpl->content .= '
						</td>';
					}
					
					// System gescannt
					else if($row['systemeUpdate']) {
						// Planeten-ID
						$tmpl->content .= '
						<td>
							<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$row['planetenID'].'&amp;ajax">'.$row['planetenID'].'</a>
						</td>
						<td>';
						// Inhaber
						if($row['playerName'] != NULL) {
							$tmpl->content .= '
							<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['playerID'].'&amp;ajax">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
							// Urlaubsmodus
							if($row['playerUmod']) {
								$tmpl->content .= '<sup class="small red">zzZ</sup>';
							}
							$tmpl->content .= ' &nbsp;';
							// Allianz
							if($row['player_allianzenID']) {
								$tmpl->content .= '
								<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.(
									isset($allianzen[$row['player_allianzenID']]) 
									? htmlspecialchars($allianzen[$row['player_allianzenID']], ENT_COMPAT, 'UTF-8')
									: '<i>unbekannt</i>'
								).'</a>';
							}
							// allianzlos
							else $tmpl->content .= '<span class="small" style="font-style:italic">allianzlos</span>';
						}
						else if($row['playerID'] == 0) $tmpl->content .= '<i>keiner</i>';
						else if($row['playerID'] == -2) $tmpl->content .= '<i>Lux</i>';
						else if($row['playerID'] == -3) $tmpl->content .= '<i>Altrasse</i>';
						else $tmpl->content .= '<i>unbekannt</i>';
						
						// Allianzen im System
						$tmpl->content .= '
						</td>
						<td>';
						foreach($data as $val) {
							// allianzlos
							if($val === '0') {
								$tmpl->content .= '<span class="small" style="font-style:italic">allianzlos</span>&nbsp; ';
							}
							// allianzlos
							if($val === '-1') {
								$tmpl->content .= '<span class="small" style="font-style:italic">frei</span>&nbsp; ';
							}
							// Allianz
							else if(isset($allianzen[$val])) {
								$tmpl->content .= '<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$val.'&amp;ajax">'.htmlspecialchars($allianzen[$val], ENT_COMPAT, 'UTF-8').'</a>&nbsp; ';
							}
						}
						$tmpl->content .= '
						</td>';
					}
					
					// System nicht gescannt
					else {
						$tmpl->content .= '
						<td colspan="3">
							<span class="small" style="font-style:italic">System noch nicht gescannt</span>
						</td>';
					}
					// Entfernung, Scan und Reservierung
					$color = (time()-($config['scan_veraltet']*86400) > $row['systemeUpdate']) ? 'red' : 'green';
					if($row['systemeUpdate'] > $heute) $scan = 'heute';
					else if($row['systemeUpdate']) $scan = strftime('%d.%m.%y', $row['systemeUpdate']);
					else $scan = 'nie';
					
					$tmpl->content .= '
						<td>'.flugdauer($row['systemeEntfernung'], $_POST['antrieb']).'</td>
						<td class="'.$color.'">'.$scan.'</td>
						<td>'.($row['systemeScanReserv'] > time()-86400 ? '<i>'.htmlspecialchars($row['systemeReservUser'], ENT_COMPAT, 'UTF-8').'</i>' : '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=reserve&amp;sys='.$row['systemeID'].'&amp;ajax\', this.parentNode, false, false)">reservieren</a>').'</td>
					</tr>';
					
					$sids[] = $row['systemeID'];
				}
				
				// Tabellenfooter
				$tmpl->content .= '
					</table>
					</div>';
				
				// Ergebnis-Navigation
				$tmpl->content .= '<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
				
				// Log-Eintrag
				if($config['logging'] >= 2) {
					insertlog(6, 'lässt sich die nächsten '.$_POST['syscount'].' Systeme von '.$_POST['start'].' aus anzeigen');
				}
			}
		}
	}
	// Ausgabe
	$tmpl->output();
}

// Flugposition berechnen (AJAX)
else if($_GET['sp'] == 'flug_pos') {
	// keine Berechtigung
	if(!$user->rechte['strecken_flug']) $tmpl->error = 'Du hast keine Berechtigung!';
	// Daten unvollständig
	else if(!isset($_POST['start'], $_POST['antrieb'], $_POST['dest_flug'], $_POST['postag'], $_POST['posstunde'], $_POST['posminute'], $_POST['possekunde'])) {
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
		$_POST['postag'] = (int)$_POST['postag'];
		$_POST['posstunde'] = (int)$_POST['posstunde'];
		$_POST['posminute'] = (int)$_POST['posminute'];
		$_POST['possekunde'] = (int)$_POST['possekunde'];
		
		// Startpunkt und Zielpunkt in Koordinaten umwandeln
		$points = array($_POST['start'], $_POST['dest_flug']);
		
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
		
		// Zeit berechnen
		if(!$tmpl->error) {
			if(!checktime($_POST['posstunde'], $_POST['posminute'], $_POST['possekunde'])) {
				$tmpl->error = 'Ung&uuml;ltige Zeit eingegeben!';
			}
			else {
				// Timestamp berechnen
				$ankunft = strtotime("today") + 3600*$_POST['posstunde'] + 60*$_POST['posminute'] + $_POST['possekunde'] - time();
				if($_POST['postag']) $ankunft += 86400;
				// Zeit in der Vergangenheit
				if($ankunft < 0) {
					$tmpl->error = 'Die Ankunftszeit darf nicht in der Vergangenheit liegen!';
				}
			}
		}
		
		// gleiche Galaxie und Zugriffsrechte prüfen und ausgeben
		if(!$tmpl->error) {
			// gleiche Galaxie
			if($points[0][0] != $points[1][0]) {
				$tmpl->error = 'Start- und Zielpunkt sind in einer unterschiedlichen Galaxie!';
			}
			// kein Zugriff auf die Galaxie
			else if($user->protectedGalas AND in_array($points[0][0], $user->protectedGalas)) {
				$tmpl->error = 'Deine Allianz hat keinen Zugriff auf diese Galaxie!';
			}
			// Flugposition berechnen und ausgeben
			else {
				$entf = entf(
					$points[0][1],
					$points[0][2],
					$points[0][3],
					$points[0][4],
					$points[1][1],
					$points[1][2],
					$points[1][3],
					$points[1][4]
				);
				
				// Flugdauer des gesamten Wegs in Sekunden berechnen
				$dauer = 12*$entf/$_POST['antrieb'];
				
				// Flugdauer größer als gesamter Weg
				if($ankunft > $dauer) {
					$tmpl->error = 'Die Flugdauer kann nicht länger als der gesamte Weg sein!';
				}
				// Koordinaten berechnen
				else {
					// Streckfaktor berechnen
					$fak = ($dauer-$ankunft)/$dauer;
					
					// Koordinaten
					$coords = array();
					for($i=1;$i<=3;$i++) {
						$coords[$i] = round($points[0][$i] + $fak * ($points[1][$i] - $points[0][$i]));
					}
					
					// Uhrzeit formatieren
					if($_POST['posminute'] < 10) $_POST['posminute'] = '0'.$_POST['posminute'];
					if($_POST['possekunde'] < 10) $_POST['possekunde'] = '0'.$_POST['possekunde'];
					
					$ankunft = 
					
					// Ausgabe
					$tmpl->content = '
					<br />
					<div align="center">
						aktuelle Flugposition von '.$_POST['start'].' nach '.$_POST['dest_flug'].' mit Ankunft 
						'.($_POST['postag'] ? 'morgen' : 'heute').', 
						'.$_POST['posstunde'].':'.$_POST['posminute'].':'.$_POST['possekunde'].' Uhr:
						<br /><br />
						<b>'.$points[0][0].'|'.$coords[1].'|'.$coords[2].'|'.$coords[3].'</b>
						<br /><br />
						<span class="small hint">(Das entspricht den Koordinaten im Format <b>Gala|X|Y|Z</b>)</span>
					</div>
					';
					
					// Log-Eintrag
					if($config['logging'] >= 2) {
						insertlog(6, 'berechnet eine Flugposition zwischen '.$_POST['start'].' und '.$_POST['dest_flug']);
					}
				}
				
				
			}
		}
	}
	// Ausgabe
	$tmpl->output();
}

// nächsten / entferntesten Planet finden (AJAX)
else if($_GET['sp'] == 'flug_search') {
	// keine Berechtigung
	if(!$user->rechte['strecken_flug']) $tmpl->error = 'Du hast keine Berechtigung!';
	// Daten unvollständig
	else if(!isset($_POST['start'], $_POST['search'], $_POST['searchtyp'], $_POST['searchid'], $_POST['antrieb'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// Antrieb ungültig
	else if((int)$_POST['antrieb'] < 1) {
		$tmpl->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
	}
	// Berechtigung
	else {
		$order = $_POST['search'] ? 'DESC' : 'ASC';
		
		// Daten sichern
		$_POST['antrieb'] = (int)$_POST['antrieb'];
		$_POST['searchcount'] = (int)$_POST['searchcount'];
		if($_POST['searchcount'] < 1) $_POST['searchcount'] = 1;
		$_POST['searchid'] = escape($_POST['searchid']);
		
		// Startpunkt in Koordinaten umwandeln
		$point = flug_point($_POST['start']);
		
		// Fehler
		if(!is_array($point) AND !$tmpl->error) {
			if($point == 'coords') $tmpl->error = 'Ung&uuml;ltige Koordinaten beim Startpunkt eingegeben!';
			else if($point == 'data') $tmpl->error = 'Ung&uuml;ltige Daten beim Startpunkt eingegeben!';
			else $tmpl->error = 'Startpunkt nicht gefunden!';
		}
		// kein Zugriff auf die Galaxie
		else if($user->protectedGalas AND in_array($points[0][0], $user->protectedGalas)) {
			$tmpl->error = 'Deine Allianz hat keinen Zugriff auf diese Galaxie!';
		}
	}	
		
	// bis jetzt noch keine Fehler
	if(!$tmpl->error) {
		// Bedingungen bestimmen
		$data = false;
		
		$conds = array();
		
		// gesperrte Allianzen ausblenden
		if($user->protectedAllies) {
			$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN (".implode(', ', $user->protectedAllies)."))";
		}
		
		// fehlende Berechtigungen
		if(!$user->rechte['search_ally'] AND $user->allianz) {
			$conds[] = "(planeteten_playerID = ".$user->id." OR player_allianzenID IS NULL OR player_allianzenID != ".$user->allianz.")";
		}
		if(!$user->rechte['search_meta'] AND $user->allianz) {
			$conds[] = "(statusStatus IS NULL OR statusStatus != ".$status_meta." OR player_allianzenID = ".$user->allianz.")";
		}
		if(!$user->rechte['search_register'] AND $user->allianz) {
			$conds[] = "(allianzenID IS NULL OR register_allianzenID IS NULL OR statusStatus = ".$status_meta.")";
		}
		
		// eigene Planeten
		if($_POST['searchtyp'] == 1) {
			$conds[] = "planeten_playerID = ".$user->id;
			$title = $user->name;
		}
		// Planeten der eigenen Allianz
		else if($_POST['searchtyp'] == 2) {
			$conds[] = "player_allianzenID = ".$user->allianz;
		}
		// Planeten der eigenen Meta
		else if($_POST['searchtyp'] == 3) {
			$conds[] = "statusStatus = ".$status_meta;
			
			//$title = "der eigenen Meta";
		}
		// feindliche Planeten
		else if($_POST['searchtyp'] == 4) {
			$conds[] = "statusStatus IN (".implode(', ', $status_feind).")";
			
			//$title = "feindlicher Allianzen";
		}
		// Planeten eines Spielers
		else if($_POST['searchtyp'] == 5) {
			// analysieren
			$val = db_multiple($_POST['searchid'], true);
			
			// User-ID(s)
			if($val) {
				$conds[] = "planeten_playerID ".$val;
			}
			// Username
			else {
				$val = escape(str_replace('*', '%', $_POST['searchid']));
				$conds[] = "playerName LIKE '".$val."'";
			}
		}
		// Planeten einer Allianz
		else if($_POST['searchtyp'] == 6) {			
			// analysieren
			$val = db_multiple($_POST['searchid'], true);
			
			// Ally-ID(s)
			if($val) {
				$conds[] = "player_allianzenID ".$val;
			}
			// Ally-Tag oder Ally-Name
			else {
				$val = escape(str_replace('*', '%', $_POST['searchid']));
				$conds[] = "(allianzenTag LIKE '".$_POST['searchid']."' OR allianzenName LIKE '".$val."')";
			}
		}
	}
	
	// kein Fehler
	if(!$tmpl->error) {
		
		$t = time();
		$ids = array();
		
		// Daten abfragen
		$query = query("
			SELECT
				planetenID,
				planeten_systemeID,
				planeten_playerID,
				
				systemeX,
				systemeZ,
				
				playerName,
				player_allianzenID,
				playerDeleted,
				playerUmod,
				
				allianzenTag,
				
				statusStatus,
				
				".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $point[1], $point[2], $point[3], $point[4])." AS planetenEntfernung
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			WHERE
				systeme_galaxienID = ".$point[0]."
				AND ".implode(' AND ', $conds)."
			ORDER BY planetenEntfernung ".$order."
			LIMIT ".$_POST['searchcount']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// keine Planeten gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->content .= '
			<div align="center">
				Es wurden keine Planeten gefunden, die den Kriterien entsprechen!
			</div>
			';
		}
		// Planeten gefunden
		else {
			$tmpl->content .= '
			<div align="center">
				<table class="data">
				<tr>
					<th>Gala</td>
					<th>System</td>
					<th>Planet</td>
					<th>Inhaber</td>
					<th>Entf (A'.$_POST['antrieb'].')</td>
				</tr>';
			
			while($row = mysql_fetch_assoc($query)) {
				$tmpl->content .= '
				<tr'.($row['playerDeleted'] ? ' style="opacity:0.4;filter:alpha(opacity=40)"' : '').'>
					<td><span style="color:'.sektor_coord($row['systemeX'], $row['systemeZ']).'">'.$point[0].'</span></td>
					<td><a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$row['planeten_systemeID'].'&amp;ajax">'.$row['planeten_systemeID'].'</a></td>
					<td><a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$row['planetenID'].'&amp;nav='.$t.'&amp;ajax">'.$row['planetenID'].'</a></td>
					<td>';
				// Inhaber
				if($row['playerName'] != NULL) {
					$tmpl->content .= '
						<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['planeten_playerID'].'&amp;ajax">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
					// Urlaubsmodus
					if($row['playerUmod']) {
						$tmpl->content .= '<sup class="small red">zzZ</sup>';
					}
					$tmpl->content .= ' &nbsp;';
					// Allianz
					if($row['player_allianzenID']) {
						$tmpl->content .= '
						<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.(
							($row['allianzenTag'] != NULL) 
							? htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8')
							: '<i>unbekannt</i>'
						).'</a>';
					}
					// allianzlos
					else $tmpl->content .= '<span class="small" style="font-style:italic">allianzlos</span>';
				}
				else if($row['planeten_playerID'] == 0) $tmpl->content .= '<i>keiner</i>';
				else if($row['planeten_playerID'] == -2) $tmpl->content .= '<i>Lux</i>';
				else if($row['planeten_playerID'] == -3) $tmpl->content .= '<i>Altrasse</i>';
				else $tmpl->content .= '<i>unbekannt</i>';
				$tmpl->content .= '
					</td>
					<td>'.flugdauer($row['planetenEntfernung'], $_POST['antrieb']).'</td>
				</tr>';
				
				$ids[] = $row['planetenID'];
			}
			
			$tmpl->content .= '
				</table>
			</div>';
			
			// hidden-Feld für die Suchnavigation
			$tmpl->content .= '
				<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />';
		}
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			$typen = array(
				1=>'sich selbst',
				2=>'seiner Allianz',
				3=>'seiner Meta',
				4=>'des Spielers',
				5=>'der Allianz',
				6=>'der Meta'
			);
			if(isset($typen[$_POST['searchtyp']])) {
				$typ = $typen[$_POST['searchtyp']];
			}
			else $typ = 'unbekannt';
			
			insertlog(6, 'sucht die '.($_POST['search'] ? 'entferntesten' : 'nächsten').'  Planeten  von '.$typ.' '.$_POST['searchid'].' von '.$_POST['start'].' aus');
		}
	}
	// Ausgabe
	$tmpl->output();
}




?>