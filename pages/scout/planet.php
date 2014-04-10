<?php
/**
 * pages/scout/planet.php
 * Planeten scouten
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Tabellenklasse laden
if(!class_exists('datatable')) {
	include './common/datatable.php';
}


// keine Berechtigung
if(!$user->rechte['scout']) $tmpl->error = 'Du hast keine Berechtigung!';
// Daten unvollständig
else if(!isset($_POST['start'], $_POST['count'], $_POST['antrieb'], $_POST['days'], $_POST['player'], $_POST['ally'], $_POST['status'])) {
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
// Alter ungültig
else if((int)$_POST['days'] < 1) {
	$tmpl->error = 'Ung&uuml;ltiges Alter eingegeben!';
}
// Berechtigung
else {
	// Daten sichern
	$_POST['count'] = (int)$_POST['count'];
	$_POST['days'] = (int)$_POST['days'];
	$_POST['antrieb'] = (int)$_POST['antrieb'];
	$_POST['player'] = escape($_POST['player']);
	$_POST['ally'] = escape($_POST['ally']);
	$_POST['status'] = (int)$_POST['status'];
	$_POST['rasse'] = (int)$_POST['rasse'];
	
	// Anzahl begrenzen
	if($_POST['count'] > 200) {
		$_POST['count'] = 200;
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
		// Planeten ermitteln und ausgeben
		else {
			$heute = strtotime('today');
			
			$conds = array(
				"systeme_galaxienID = ".$point[0],
				"planetenUpdateOverview < ".(time()-86400*$_POST['days']),
				"planeten_playerID != 0"
			);
			
			// Einschränkungen und Sperrungen der Rechte
			if($user->protectedAllies) {
				$conds[] = '(player_allianzenID IS NULL OR player_allianzenID NOT IN ('.implode(', ', $user->protectedAllies).'))';
			}
			if(!$user->rechte['search_ally'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID IS NULL OR player_allianzenID != '.$user->allianz.')';
			}
			if(!$user->rechte['search_meta'] AND $user->allianz) {
				$conds[] = '(player_allianzenID = '.$user->allianz.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
			}
			if(!$user->rechte['search_register']) {
				$conds[] = '(allianzenID IS NULL OR register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
			}
			
			// Suchfilter
			
			// Spieler
			if($_POST['player'] != '') {
				$val = escape(str_replace('*', '%', $_POST['player']));
				if(is_numeric($val)) {
					$conds[] = "(planeten_playerID = ".(int)$val." OR playerName LIKE '".$val."')";
				}
				else if($val2 = db_multiple($val, true)) {
					$conds[] = "(planeten_playerID ".$val2." OR playerName LIKE '".$val."')";
				}
				else {
					$conds[] = "playerName LIKE '".$val."'";
				}
			}
			
			// Ally
			if($_POST['ally'] != '') {
				$val = escape(str_replace('*', '%', $_POST['ally']));
				if(is_numeric($val)) {
					$conds[] = "(player_allianzenID = ".(int)$val." OR allianzenTag LIKE '".$val."' OR allianzenName LIKE '".$val."')";
				}
				else if($val2 = db_multiple($val, true)) {
					$conds[] = "(player_allianzenID ".$val2." OR allianzenTag LIKE '".$val."' OR allianzenName LIKE '".$val."')";
				}
				else {
					$conds[] = "(allianzenTag LIKE '".$val."' OR allianzenName LIKE '".$val."')";
				}
			}
			
			// Status
			if($_POST['status'] != -1) {
				if($_POST['status'] == 0) {
					$conds[] = "(statusStatus IS NULL OR statusStatus = 0)";
				}
				else {
					$conds[] = "statusStatus = ".$_POST['status'];
				}
			}
			
			// Rasse
			if($_POST['rasse'] != -1) {
				if($_POST['rasse'] == 0) {
					$conds[] = "(playerRasse != 10 OR planeten_playerID = -3)";
				}
				else if($_POST['rasse'] == 10) {
					$conds[] = "(playerRasse = 10 OR planeten_playerID = -2)";
				}
				else {
					$conds[] = "playerRasse = ".$_POST['rasse'];
				}
			}
			
			// reservierte ausblenden
			if(isset($_POST['hidereserv'])) {
				$conds[] = "systemeScanReserv < ".(time()-86400);
			}
			
			$t = time();
			$ids = array();
			$sids = array();
			
			// Planeten abfragen
			$query = query("
				SELECT
					planetenID,
					planetenName,
					planeten_playerID,
					planeten_systemeID,
					planetenGroesse,
					planetenTyp,
					planetenUpdateOverview,
					planetenUnscannbar,
					planetenGebPlanet,
					planetenGebOrbit,
					planetenGebSpezial,
					planetenKategorie,
					planetenRMErz,
					planetenRMMetall,
					planetenRMWolfram,
					planetenRMKristall,
					planetenRMFluor,
					planetenKommentar,
					".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $point[1], $point[2], $point[3], $point[4])." AS planetenEntfernung,
					
					systemeX,
					systemeZ,
					systemeScanReserv,
					systemeReservUser,
					
					playerName,
					player_allianzenID,
					playerUmod,
					playerRasse,
					
					allianzenTag,
					
					register_allianzenID,
					
					statusStatus
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
					LEFT JOIN ".GLOBPREFIX."player
						ON planeten_playerID = playerID
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
					LEFT JOIN ".PREFIX."allianzen_status
						ON statusDBAllianz = ".$user->allianz."
						AND status_allianzenID = allianzenID
				WHERE
					".implode(' AND ', $conds)."
				ORDER BY
					planetenEntfernung ASC
				LIMIT ".$_POST['count']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query)) {
				// Tabellen-Headline ausgeben
				$tmpl->content = '
				<br /><br />
				<table class="data searchtbl" style="margin:auto">
				<tr>
					<th>G</td>
					<th>Sys</td>
					<th>ID</td>
					<th>Name</td>
					<th>Inhaber</td>
					<th>Allianz</td>
					<th>Status</td>
					<th>Gr&ouml;&szlig;e</th>
					<th>&nbsp;</td>
					<th>Entf (A'.$_POST['antrieb'].')</td>
					<th>Scan</td>
					<th>&nbsp;</td>
					<th>&nbsp;</td>
					<th>&nbsp;</td>
					<th>&nbsp;</td>
					<th>&nbsp;</td>
				</tr>';
			
				while($row = mysql_fetch_assoc($query)) {
					$tmpl->content .= '
				<tr>
					<td>'.datatable::galaxie($point[0], $row['systemeX'], $row['systemeZ']).'</td>
					<td>'.datatable::system($row['planeten_systemeID'], $t).'</td>
	<td>'.datatable::planet($row['planetenID'], false, $t).'</a></td>
	<td>'.datatable::planet($row['planetenID'], $row['planetenName'], $t).'</td>
					<td>'.datatable::inhaber($row['planeten_playerID'], $row['playerName'], $row['playerUmod'], $row['playerRasse']).'</td>
					<td>'.datatable::allianz($row['player_allianzenID'], $row['allianzenTag']).'</td>
					<td>'.datatable::status($row['statusStatus'], $row['player_allianzenID']).'</td>
					<td>'.$row['planetenGroesse'].'</td>
					<td>'.datatable::typ($row['planetenTyp']).'</td>
					<td>'.flugdauer($row['planetenEntfernung'], $_POST['antrieb']).'</td>
					<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet'], $row['planetenUnscannbar']).'</td>
					<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
					<td>'.datatable::kategorie($row['planetenKategorie'], $row['planetenUpdateOverview'], $row).'</td>
					<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>
					<td class="sysreserv'.$row['planeten_systemeID'].'">'.($row['systemeScanReserv'] > $t-86400 ? '<i>'.htmlspecialchars($row['systemeReservUser'], ENT_COMPAT, 'UTF-8').'</i>' : '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=reserve&amp;sys='.$row['planeten_systemeID'].'&amp;ajax\', this.parentNode, false, false)">reservieren</a>').'</td>
					<td class="userlistaction"><img src="img/layout/leer.gif" class="hoverbutton arrowbutton"  title="von hier aus weiterscouten" onclick="scout_weiter('.$row['planetenID'].', this)" /></td>
				</tr>';
					
					$ids[] = $row['planetenID'];
					
					if(!in_array($row['planeten_systemeID'], $sids)) {
						$sids[] = $row['planeten_systemeID'];
					}
				}
				
				// Tabellenfooter
				$tmpl->content .= '
					</table>';
				
				// Ergebnis-Navigation
				$tmpl->content .= '
					<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />
					<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
			}
			// alle Systeme aktuell
			else {
				$tmpl->content .= '
					<br /><br />
					Alle ausgew&auml;hlten Planeten in der Galaxie sind aktueller als '.$_POST['days'].' Tage.';
			}
			
			$additional = '';
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(17, 'lässt sich die nächsten '.$_POST['count'].' Planeten '.$additional.' von '.$_POST['start'].' aus anzeigen');
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