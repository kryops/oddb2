<?php
/**
 * pages/toxx.php
 * raiden & toxxen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	$_GET['sp'] = 'raid';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Raiden & Toxxen';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'raid'=>true,
	'toxx'=>true,
	'send'=>true
);


 
// keine Berechtigung
if(!$user->rechte['toxxraid']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->output();
}
// 404-Error
else if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

// Raid- und Toxxziele anzeigen (AJAX)
else if($_GET['sp'] == 'send') {
	// Tabellenklasse laden
	if(!class_exists('datatable')) {
		include './common/datatable.php';
	}
	
	
	// keine Berechtigung
	if(!$user->rechte['toxxraid']) $tmpl->error = 'Du hast keine Berechtigung!';
	// Daten unvollständig
	else if(!isset($_POST['start'], $_POST['count'], $_POST['antrieb'], $_POST['player'], $_POST['ally'], $_POST['status'], $_POST['kategorie'], $_POST['sektor'])) {
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
		$_POST['count'] = (int)$_POST['count'];
		$_POST['antrieb'] = (int)$_POST['antrieb'];
		$_POST['player'] = escape($_POST['player']);
		$_POST['ally'] = escape($_POST['ally']);
		$_POST['status'] = (int)$_POST['status'];
		$_POST['rasse'] = (int)$_POST['rasse'];
		$_POST['sektor'] = (int)$_POST['sektor'];
		if($_POST['kategorie'] != '') {
			$_POST['kategorie'] = (int)$_POST['kategorie'];
		}
		
		// Anzahl begrenzen
		if($_POST['count'] > 200) {
			$_POST['count'] = 200;
		}
		
		// Titel
		$tmpl->name = 'Ziele von '.htmlspecialchars($_POST['start'], ENT_QUOTES, 'UTF-8').' aus';
		
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
					"planeten_playerID NOT IN(0,1,2)",
					"((playerDeleted = 0 AND playerUmod = 0) OR (planeten_playerID = -2 OR planeten_playerID = -3))"
				);
				
				// eigene Allianz und Freunde ausblenden
				if($user->allianz) {
					$conds[] = "player_allianzenID != ".$user->allianz;
					$st = $status_freund;
					// in Ruhe lassen
					$st[] = 5;
					// Antitoxxpakt
					if(isset($_POST['toxx'])) {
						$st[] = 6;
					}
					$conds[] = "(statusStatus IS NULL OR statusStatus NOT IN(".implode(",", $st)."))";
				}
				
				// Startplaneten ausblenden
				if(is_numeric($_POST['start'])) {
					$conds[] = "planetenID != ".(int)$_POST['start'];
				}
				
				// raiden
				$modeconds = array();
				if(isset($_POST['raid'])) {
					$_POST['raiddays'] = (int)$_POST['raiddays'];
					$modeconds[] = "planetenGeraidet < ".(time()-86400*$_POST['raiddays']);
				}
				// toxxen
				if(isset($_POST['toxx'])) {
					// eigene Rasse herausfinden
					$query = query("
						SELECT
							playerRasse
						FROM
							".GLOBPREFIX."player
						WHERE
							playerID = ".$user->id."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					$lux = false;
					
					if(mysql_num_rows($query)) {
						$data = mysql_fetch_assoc($query);
						if($data['playerRasse'] == 10) {
							$lux = true;
						}
					}
					
					$modeconds[] = "(planetenGetoxxt < ".time()." AND (playerRasse ".($lux ? "!" : "")."= 10 OR player_allianzenID = 0 OR statusStatus = ".$status_krieg." OR planeten_playerID = ".($lux ? "-3" : "-2")."))";
				}
				if(count($modeconds)) {
					$conds[] = "(".implode(" OR ", $modeconds).")";
				}
				
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
				
				// Sektor
				if($_POST['sektor']) {
					if($_POST['sektor'] == 1) $conds[] = 'systemeX > 0 AND systemeZ > 0';
					else if($_POST['sektor'] == 2) $conds[] = 'systemeX < 0 AND systemeZ > 0';
					else if($_POST['sektor'] == 3) $conds[] = 'systemeX < 0 AND systemeZ < 0';
					else $conds[] = 'systemeX > 0 AND systemeZ < 0';
				}
				
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
					if($_POST['rasse'] == 10) {
						$conds[] = "(playerRasse = 10 OR planeten_playerID = -2)";
					}
					else {
						$conds[] = "playerRasse = ".$_POST['rasse'];
					}
				}
				
				// Kategorie
				if($_POST['kategorie'] != '') {
					// normale Kategorie
					if($_POST['kategorie'] >= 0 AND $_POST['kategorie'] <= 13) {
						$conds[] = 'planetenKategorie = '.$_POST['kategorie'];
					}
					// Sammelkategorien
					// alle Ressplaneten
					else if($_POST['kategorie'] == 14) {
						$conds[] = 'planetenKategorie IN(1,2,3,4,5,12)';
					}
					// Ressplaneten und Werften
					else if($_POST['kategorie'] == 15) {
						$conds[] = 'planetenKategorie IN(1,2,3,4,5,12,13)';
					}
					// alle Forschungsplaneten
					else {
						$conds[] = 'planetenKategorie >= 6 AND planetenKategorie <= 11';
					}
				}
				
				// Orbiter
				if(isset($_POST['o']) AND $_POST['o'] != '') {
					if($_POST['o'] == 1) {
						$conds[] = 'planetenOrbiter = 0 AND planetenUpdateOverview > 0';
					}
					else if($_POST['o'] == 2) {
						$conds[] = 'planetenOrbiter <= 1 AND planetenUpdateOverview > 0';
					}
					else if($_POST['o'] == 3) {
						$conds[] = 'planetenOrbiter <= 2 AND planetenUpdateOverview > 0';
					}
					else if($_POST['o'] == 4) {
						$conds[] = 'planetenOrbiter >= 2';
					}
					else if($_POST['o'] == 5) {
						$conds[] = 'planetenOrbiter >= 3';
					}
					else {
						$conds[] = 'planetenOrbiter >= 1';
					}
				}
				
				
				// Toxxrouten-Planeten ausblenden
				if(isset($_POST['toxxroute'])) {
					route::in_toxxroute(0);
					if(count($toxxroute)) {
						$conds[] = 'planetenID NOT IN('.implode(',', array_keys($toxxroute)).')';
					}
				}
				
				// reservierte Planeten ausblenden
				if(isset($_POST['reserv'])) {
					$conds[] = 'planetenReserv < '.(time()-86400);
				}
				
				
				// Favoritenlink
				$fav = $_POST;
				foreach($fav as $key=>$val) {
					if($val === '') {
						unset($fav[$key]);
					}
				}
				if($fav['status'] == -1) {
					unset($fav['status']);
				}
				if($fav['rasse'] == -1) {
					unset($fav['rasse']);
				}
				if(isset($fav['toxxroute'])) {
					unset($fav['toxxroute']);
				}
				else {
					$fav['toxxroute'] = 1;
				}
				if(isset($fav['reserv'])) {
					unset($fav['reserv']);
				}
				else {
					$fav['reserv'] = 1;
				}
				
				foreach($fav as $key=>$val) {
					$fav[$key] = urlencode($key).'='.urlencode($val);
				}
				
				$tmpl->content = '<div class="favadd" style="float:right" title="zu den Favoriten hinzuf&uuml;gen" onclick="fav_add(\'index.php?p=toxx&amp;'.implode('&amp;', $fav).'\', 3)"></div>';
				
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
						planetenKategorie,
						planetenKommentar,
						planetenGeraidet,
						planetenGetoxxt,
						planetenRMErz,
						planetenRMMetall,
						planetenRMWolfram,
						planetenRMKristall,
						planetenRMFluor,
						planetenReserv,
						".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $point[1], $point[2], $point[3], $point[4])." AS planetenEntfernung,
						
						systemeX,
						systemeZ,
						
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
					$intoxxroute = false;
					
					// Tabellen-Headline ausgeben
					$tmpl->content .= '
					<br /><br />
					<table class="data searchtbl" style="margin:auto">
					<tr>
						<th>G</th>
						<th>Sys</th>
						<th>ID</th>
						<th>Name</th>
						<th>Inhaber</th>
						<th>Allianz</th>
						<th>Gr</th>
						<th>&nbsp;</th>
						<th>Entf</th>
						<th>Scan</th>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
						<th><div class="ress erz"></div></th>
						<th><div class="ress metall"></div></th>
						<th><div class="ress wolfram"></div></th>
						<th><div class="ress kristall"></div></th>
						<th><div class="ress fluor"></div></th>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
						<th>geraidet</th>
						<th>Toxx bis</th>
						<th>&nbsp;</th>
					</tr>';
				
					while($row = mysql_fetch_assoc($query)) {
						// ist der PLanet in einer Toxxroute enthalten?
						$t = route::in_toxxroute($row['planetenID']);
						if($t) {
							$intoxxroute = true;
						}
						
						$tmpl->content .= '
					<tr'.($t ? ' style="opacity:0.4"' : '').'>
					<td>'.datatable::galaxie($point[0], $row['systemeX'], $row['systemeZ']).'</td>
					<td>'.datatable::system($row['planeten_systemeID']).'</td>
	<td>'.datatable::planet($row['planetenID']).'</a></td>
	<td>'.datatable::planet($row['planetenID'], $row['planetenName']).'</td>
					<td>'.datatable::inhaber($row['planeten_playerID'], $row['playerName'], $row['playerUmod'], $row['playerRasse']).'</td>
					<td>'.datatable::allianz($row['player_allianzenID'], $row['allianzenTag'], $row['statusStatus']).'</td>
					<td>'.$row['planetenGroesse'].'</td>
					<td>'.datatable::typ($row['planetenTyp']).'</td>
					<td>'.flugdauer($row['planetenEntfernung'], $_POST['antrieb']).'</td>
					<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet'], $row['planetenUnscannbar']).'</td>';
						// Miniaturansicht
						// Berechtigung überprüfen, den Scan zu sehen
						$r = $user->rechte['show_planet'];
						
						// bei eigenen Planeten immer Berechtigung, falls globale Berechtigung
						if($r AND $row['planeten_playerID'] != $user->id) {
							// keine Berechtigung (Ally)
							if(!$user->rechte['show_planet_ally'] AND $user->allianz AND $row['player_allianzenID'] == $user->allianz) {
								$r = false;
							}
							// keine Berechtigung (Meta)
							else if($user->allianz AND !$user->rechte['show_planet_meta'] AND $row['statusStatus'] == $status_meta AND $row['player_allianzenID'] != $user->allianz) {
								$r = false;
							}
							// keine Berechtigung (registrierte Allianzen)
							else if(!$user->rechte['show_planet_register'] AND $row['register_allianzenID'] !== NULL AND $row['statusStatus'] != $status_meta) {
								$r = false;
							}
						}
						
						$tmpl->content .= '
					<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
					<td>'.datatable::kategorie($row['planetenKategorie']).'</td>
					<td>';
						// Ressvorkommen
						if($row['planetenUpdateOverview'] AND $r) {
							$tmpl->content .= ressmenge2($row['planetenRMErz']).'</td>
					<td>'.ressmenge2($row['planetenRMMetall']).'</td>
					<td>'.ressmenge2($row['planetenRMWolfram']).'</td>
					<td>'.ressmenge2($row['planetenRMKristall']).'</td>
					<td>'.ressmenge2($row['planetenRMFluor']).'</td>
					<td>';
						}
						else {
							$tmpl->content .= '&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>';
						}
						
						// Kommentar
						$tmpl->content .= datatable::kommentar($row['planetenKommentar'], $row['planetenID']);
						
						// reservieren, geraidet- oder getoxxt-Datum und Link
						$tmpl->content .= '</td>
						<td>'.((time()-$row['planetenReserv'] < 86400) ? '<i>reserv</i>' : '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=toxxraidreserv&amp;id='.$row['planetenID'].'&amp;ajax\', this.parentNode, false, false)" class="hint">reserv</a>').'</td>
						<td>'.datatable::geraidet($row['planetenGeraidet'], $row['planetenID']).'</td>
						<td>'.datatable::getoxxt($row['planetenGetoxxt'], $row['planetenID']).'</td>
						<td class="userlistaction"><img src="img/layout/leer.gif" class="hoverbutton" style="background-position:-1060px -91px" title="von hier aus weiterfliegen" onclick="scout_weiter('.$row['planetenID'].', this)" /></td>
					</tr>';
					}
					
					// Tabellenfooter
					$tmpl->content .= '
					</table>';
					
					// Toxxrouten-Label
					if($intoxxroute) {
						$tmpl->content .= '
					<br />
					<div class="small hint center">(ausgegraute Planeten sind schon in Toxxrouten enthalten)</div>';
					}
				}
				// keine Treffer
				else {
					$tmpl->content .= '
						<br /><br />
						Keine Planeten entsprechen den Kriterien!';
				}
				
				$additional = '';
				
				// Log-Eintrag
				if($config['logging'] >= 2) {
					insertlog(17, 'lässt sich die nächsten '.$_POST['count'].' Toxx/Raid-Planeten von '.$_POST['start'].' aus anzeigen');
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
}

/**
 * normale Seiten
 */
else {
	$time = time();
	
	$tmpl->content = '
<div class="icontent raidtoxx'.$time.'">
	<form action="#" name="raiden" onsubmit="form_send(this, \'index.php?p=toxx&amp;sp=send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
	<div class="formcontent">
		<table style="width:100%">
		<tr>
		<td style="width:50%;vertical-align:top">
		Startpunkt: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:90px" name="start" value="'.(isset($_GET['start']) ? htmlspecialchars($_GET['start'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp;
		<span class="small hint">(Planet, System oder Koordinaten)</span>
		<br />
		Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.(isset($_GET['antrieb']) ? (int)$_GET['antrieb'] : $user->settings['antrieb']).'" />
		<br /><br />
		die n&auml;chsten 
		&nbsp;<input type="text" class="smalltext" name="count" value="'.(isset($_GET['count']) ? htmlspecialchars($_GET['count'], ENT_COMPAT, 'UTF-8') : '25').'" />&nbsp; 
		Planeten anzeigen,
		<br />
		<b>
		<input type="checkbox" name="raid"'.(isset($_GET['raid']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="raid">die vor mehr als</span>
		&nbsp;<input type="text" class="smalltext" name="raiddays" value="'.(isset($_GET['raiddays']) ? htmlspecialchars($_GET['raiddays'], ENT_COMPAT, 'UTF-8') : '3').'" />&nbsp; 
		<span class="togglecheckbox" data-name="raid">Tagen geraidet wurden</span>
		<br />
		<input type="checkbox" name="toxx"'.(isset($_GET['toxx']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="toxx">die toxxbar sind</span>
		</b>
		<br />
		beschr&auml;nken auf Sektor 
		<select name="sektor" size="1">
			<option value="0">alle</option>
			<option value="1"'.((isset($_GET['sektor']) AND $_GET['sektor'] == 1) ? ' selected="selected"' : '').'>rot</option>
			<option value="2"'.((isset($_GET['sektor']) AND $_GET['sektor'] == 2) ? ' selected="selected"' : '').'>gr&uuml;n</option>
			<option value="3"'.((isset($_GET['sektor']) AND $_GET['sektor'] == 3) ? ' selected="selected"' : '').'>blau</option>
			<option value="4"'.((isset($_GET['sektor']) AND $_GET['sektor'] == 4) ? ' selected="selected"' : '').'>gelb</option>
		</select>
		<div style="height:10px"></div>
		<input type="checkbox" name="toxxroute"'.(!isset($_GET['toxxroute']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="toxxroute">Planeten aus Toxxrouten ausblenden</span>
		<br />
		<input type="checkbox" name="reserv"'.(!isset($_GET['reserv']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="reserv">reservierte Planeten ausblenden</span>
		</td>
		<td style="vertical-align:top">
		nur Planeten anzeigen
		<br />
		des Spielers <input type="text" class="text" style="width:90px" name="player" value="'.(isset($_GET['player']) ? htmlspecialchars($_GET['player'], ENT_COMPAT, 'UTF-8') : '').'" /> <span class="small hint">(ID oder Name)</span>
		<br />
		der Allianz <input type="text" class="text" style="width:90px" name="ally" value="'.(isset($_GET['ally']) ? htmlspecialchars($_GET['ally'], ENT_COMPAT, 'UTF-8') : '').'" /> <span class="small hint">(ID, Tag oder Name)</span>
		<br />
		mit Status <select name="status" size="1">
		<option value="-1">alle</option>';
	foreach($status as $key=>$name) {
		$tmpl->content .= '<option value="'.$key.'"'.((isset($_GET['status']) AND $_GET['status'] == $key) ? ' selected="selected"' : '').'>'.$name.'</option>';
	}
	$tmpl->content .= '
		</select>
		<br />
		der Rasse <select name="rasse" size="1">
		<option value="-1">alle</option>
		<option value="0"'.((isset($_GET['rasse']) AND $_GET['rasse'] == 0) ? ' selected="selected"' : '').'>alle Altrassen</option>';
	foreach($rassen as $key=>$name) {
		$tmpl->content .= '<option value="'.$key.'"'.((isset($_GET['rasse']) AND $_GET['rasse']) == $key ? ' selected="selected"' : '').'>'.$name.'</option>';
	}
	$tmpl->content .= '
		</select>
		<br /><br />
		kategorisiert als <select name="kategorie">
			<option value=""></option>
			<option value="0"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 0) ? ' selected="selected"' : '').'>nicht kategorisiert</option>
			<option value="13"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 13) ? ' selected="selected"' : '').'>Werft</option>
			<option value="15"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 15) ? ' selected="selected"' : '').'>- Ressplanis und Werften -</option>
			<option value="14"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 14) ? ' selected="selected"' : '').'>- alle Ressplaneten -</option>
			<option value="1"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 1) ? ' selected="selected"' : '').'>Erz</option>
			<option value="2"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 2) ? ' selected="selected"' : '').'>Metall</option>
			<option value="3"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 3) ? ' selected="selected"' : '').'>Wolfram</option>
			<option value="4"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 4) ? ' selected="selected"' : '').'>Kristall</option>
			<option value="5"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 5) ? ' selected="selected"' : '').'>Fluor</option>
			<option value="12"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 12) ? ' selected="selected"' : '').'>Umsatzfabriken</option>
			<option value="16"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 16) ? ' selected="selected"' : '').'>- alle  Forschungsplaneten -</option>
			<option value="6"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 6) ? ' selected="selected"' : '').'>Forschungseinrichtungen</option>
			<option value="7"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 7) ? ' selected="selected"' : '').'>UNI-Labore</option>
			<option value="8"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 8) ? ' selected="selected"' : '').'>Forschungszentren</option>
			<option value="9"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 9) ? ' selected="selected"' : '').'>Myriforschung</option>
			<option value="10"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 10) ? ' selected="selected"' : '').'>orbitale Forschung</option>
			<option value="11"'.((isset($_GET['kategorie']) AND $_GET['kategorie'] == 11) ? ' selected="selected"' : '').'>Gedankenkonzentratoren</option>
		</select>
		<br />
		Orbiter 
		<select name="o" size="1">
			<option value="">egal</option>
			<option value="1"'.((isset($_GET['o']) AND $_GET['o'] == 1) ? ' selected="selected"' : '').'>keine</option>
			<option value="6"'.((isset($_GET['o']) AND $_GET['o'] == 6) ? ' selected="selected"' : '').'>ja</option>
			<option value="2"'.((isset($_GET['o']) AND $_GET['o'] == 2) ? ' selected="selected"' : '').'>max. Stufe 1</option>
			<option value="3"'.((isset($_GET['o']) AND $_GET['o'] == 3) ? ' selected="selected"' : '').'>max. Stufe 2</option>
			<option value="4"'.((isset($_GET['o']) AND $_GET['o'] == 4) ? ' selected="selected"' : '').'>mind. Stufe 2</option>
			<option value="5"'.((isset($_GET['o']) AND $_GET['o'] == 5) ? ' selected="selected"' : '').'>mind. Stufe 3</option>
		</select>
		</td>
		</tr>
		</table>
		<br />
		<div class="center">
			<input type="submit" class="button" style="width:120px" value="Ziele anzeigen" />
		</div>
	</div>
	</form>
	<div class="ajax center"></div>
</div>';
	
	// Favorit
	if(isset($_GET['start'])) {
		$tmpl->script = '
$(\'.raidtoxx'.$time.' form\').trigger(\'onsubmit\');
';
	}
	
	
	$tmpl->output();
}
?>