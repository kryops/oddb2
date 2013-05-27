<?php
/**
 * pages/scout/extern.php
 * extern scouten
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Tabellenklasse laden
if(!class_exists('datatable')) {
	include './common/datatable.php';
}


// evtl GET-Daten in POST umwandeln
if(!count($_POST) AND isset($_GET['start'], $_GET['syscount'], $_GET['days'], $_GET['antrieb'])) {
	$_POST['start'] = $_GET['start'];
	$_POST['syscount'] = $_GET['syscount'];
	$_POST['days'] = $_GET['days'];
	$_POST['antrieb'] = $_GET['antrieb'];
	if(isset($_GET['hidereserv'])) $_POST['hidereserv'] = true;
}

// keine Berechtigung
if(!$user->rechte['scout']) $tmpl->error = 'Du hast keine Berechtigung!';
// Daten unvollständig
else if(!isset($_POST['start'], $_POST['syscount'], $_POST['antrieb'], $_POST['days'])) {
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
// Alter ungültig
else if((int)$_POST['days'] < 1) {
	$tmpl->error = 'Ung&uuml;ltiges Alter eingegeben!';
}
// Berechtigung
else {
	// Daten sichern
	$_POST['syscount'] = (int)$_POST['syscount'];
	$_POST['days'] = (int)$_POST['days'];
	$_POST['antrieb'] = (int)$_POST['antrieb'];
	
	// Anzahl begrenzen
	if($_POST['syscount'] > 200) {
		$_POST['syscount'] = 200;
	}
	
	// Titel
	$tmpl->name = 'Scoutziele von '.htmlspecialchars($_POST['start'], ENT_QUOTES, 'UTF-8').' aus';
	
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
					AND systemeUpdate < ".(time()-$_POST['days']*86400)."
					".(isset($point[5]) ? "AND systemeID != ".$point[5] : '')."
					".(isset($_POST['hidereserv']) ? "AND systemeScanReserv < ".(time()-86400) : '')."
				GROUP BY
					systemeID
				ORDER BY
					systemeEntfernung ASC,
					planetenID ASC
				LIMIT ".$_POST['syscount']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query)) {
				// Tabellen-Headline ausgeben
				$tmpl->content = '
				<br /><br />
				<table class="data" style="margin:auto">
				<tr>
					<th>Gala</td>
					<th>System</td>
					<th>erster Planet</td>
					<th>Inhaber</td>
					<th>Allianzen im System</td>
					<th>Entf (A'.$_POST['antrieb'].')</td>
					<th>Scan</td>
					<th>Reservierung</td>
					'.(isset($_GET['scan']) ? '' : '<th>&nbsp;</th>').'
				</tr>';
			}
			
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
					<td>'.datatable::galaxie($point[0], $row['systemeX'], $row['systemeZ']).'</td>
					<td>'.datatable::system($row['systemeID'], $t).'</td>';
				
				// System verdeckt gescannt
				if($row['systemeUpdateHidden'] AND (!$row['systemeUpdate'] OR !$access)) {
					// Planeten-ID
					$tmpl->content .= '
					<td>'.datatable::planet($row['planetenID']).'</a></td>
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
					<td>'.datatable::planet($row['planetenID']).'</a></td>
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
						// frei
						else if($val === '-1') {
							$tmpl->content .= '<span class="small" style="font-style:italic">frei</span>&nbsp; ';
						}
						// unbekannt
						else if($val === '-2') {
							$tmpl->content .= '<span class="small" style="font-style:italic">unbekannt</span>&nbsp; ';
						}
						// Allianz
						else if(isset($allianzen[$val])) {
							$tmpl->content .= '<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$val.'&ajax">'.htmlspecialchars($allianzen[$val], ENT_COMPAT, 'UTF-8').'</a>&nbsp; ';
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
				$tmpl->content .= '
					<td>'.flugdauer($row['systemeEntfernung'], $_POST['antrieb']).'</td>
					<td>'.datatable::scan($row['systemeUpdate'], $config['scan_veraltet']).'</td>
					<td class="sysreserv'.$row['systemeID'].'">'.($row['systemeScanReserv'] > time()-86400 ? '<i>'.htmlspecialchars($row['systemeReservUser'], ENT_COMPAT, 'UTF-8').'</i>' : '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=reserve&amp;sys='.$row['systemeID'].'&amp;ajax\', this.parentNode, false, false)">reservieren</a>').'</td>
					'.(isset($_GET['scan']) ? '' : '<td class="userlistaction"><img src="img/layout/leer.gif" class="hoverbutton arrowbutton" title="von hier aus weiterscouten" onclick="scout_weiter(\'sys'.$row['systemeID'].'\', this)" /></td>').'
				</tr>';
				
				$sids[] = $row['systemeID'];
			}
			
			if(mysql_num_rows($query)) {
				// Tabellenfooter
				$tmpl->content .= '
					</table>';
				
				// Ergebnis-Navigation
				$tmpl->content .= '<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
			}
			// alle Systeme aktuell
			else {
				$tmpl->content .= '
					<br /><br />
					Alle Systeme der Galaxie sind aktueller als '.$_POST['days'].' Tage.';
			}
			
			// Log-Eintrag
			if($config['logging'] >= 2 AND !isset($_GET['scan'])) {
				insertlog(17, 'lässt sich die nächsten '.$_POST['syscount'].' Scoutziele von '.$_POST['start'].' aus anzeigen');
			}
		}
	}
}

// Leerzeile vor Fehlermeldung setzen
if($tmpl->error != '') {
	$tmpl->error = '<br />'.$tmpl->error;
}

// Ausgabe
$tmpl->output();



?>