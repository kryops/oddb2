<?php
/**
 * pages/route/add.php
 * Planeten zu einer Route/Liste hinzufügen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Planeten hinzufügen / entfernen
if($_GET['sp'] == 'addoptions') {
	$tmpl->name = 'Planeten hinzufügen / entfernen';
	$route = new route;
	
	// keine Berechtigung
	if(!$user->rechte['routen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Vorhandensein der Daten
	else if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// Route laden
	else if(($error = $route->load($_GET['id'])) !== true) {
		$tmpl->error = $error;
	}
	// keine Berechtigung zum Bearbeiten
	else if(!$route->rechte_edit()) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// schon berechnet
	else if($route->finished) {
		$tmpl->error = 'Die Route wurde schon berechnet! Um Planeten hinzuzufügen oder zu entfernen, musst du sie wieder in den Bearbeitungsmodus zurücksetzen!';
	}
	
	// alles in Ordnung
	else {
		$time = time();
		
		$tmpl->content .= '
	<div class="icontent small2" id="routeadd'.$time.'">
	<form action="#" name="routen_addoptions" onsubmit="form_send(this, this.action, $(this).find(\'.ajax\'));return false">
	<input type="hidden" name="time" value="'.$time.'" />
	
	<table border="0" style="margin:auto">
	<tr>
		<td style="font-weight:bold">Modus</td>
		<td style="line-height:1.5em">
			<input type="radio" name="mode" id="mode0'.$time.'" value="0" checked="checked" onclick="$(\'#routeadd'.$time.' input[type=button]\').val(\'hinzuf&uuml;gen\')" /> 
			<label for="mode0'.$time.'">Planeten zur '.$rnames[$route->info['routenListe']].' '.htmlspecialchars($route->info['routenName'], ENT_COMPAT, 'UTF-8').' hinzuf&uuml;gen</label>
			<br />
			<input type="radio" name="mode" id="mode1'.$time.'" value="1" onclick="$(\'#routeadd'.$time.' input[type=button]\').val(\'entfernen\')" /> 
			<label for="mode1'.$time.'">Planeten aus der '.$rnames[$route->info['routenListe']].' '.htmlspecialchars($route->info['routenName'], ENT_COMPAT, 'UTF-8').' entfernen</label>
		</td>
	</tr>
	</table>
	<br />
	<div class="center" style="margin-bottom:15px">
	<span class="hint">(optional)</span>
	nur so viele Planeten hinzuf&uuml;gen, bis die '.($route->liste ? 'Liste' : 'Route').' &nbsp;<input type="text" class="smalltext" style="width:40px" name="max" />&nbsp; Planeten enth&auml;lt
	</div>
	<div class="ajax center"></div>
	<div class="fcbox icontent">
		Planet(en)
		&nbsp;<input type="text" class="text enter" name="planet" style="width:200px" data-action="index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=planet&amp;ajax" />
		&nbsp;<input type="button" class="button" value="hinzuf&uuml;gen" onclick="form_submit(this, \'index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=planet&amp;ajax\')" /> &nbsp;
		<span class="small hint">(IDs mit Komma getrennt)</span>
	</div>
	<div class="fcbox icontent">
		Planeten der Route/Liste
		&nbsp;<select name="route" size="1">
		'.route::getselect($route->gala,0,$_GET['id']).'
		</select>
		&nbsp;<input type="button" class="button" value="hinzuf&uuml;gen" onclick="form_submit(this, \'index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=route&amp;ajax\')" />
	</div>
	<br />
	<div class="fcbox icontent">
		Spieler &nbsp; &nbsp; &nbsp;
		&nbsp;<input type="text" class="text enter" name="player" style="width:200px" data-action="index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=player&amp;ajax" />
		&nbsp;<input type="button" class="button" value="hinzuf&uuml;gen" onclick="form_submit(this, \'index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=player&amp;ajax\')" /> &nbsp;
		<span class="small hint">(Name / IDs mit Komma getrennt)</span>
	</div>
	<div class="fcbox icontent">
		Allianz(en) 
		&nbsp;<input type="text" class="text enter" name="ally" style="width:200px" data-action="index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=ally&amp;ajax" />
		&nbsp;<input type="button" class="button" value="hinzuf&uuml;gen" onclick="form_submit(this, \'index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=ally&amp;ajax\')" /> &nbsp;
		<span class="small hint">(Tag oder Name / IDs mit Komma getrennt)</span>
	</div>
	<div class="fcbox icontent">
		Status
		&nbsp;<select name="status" size="1">';
		foreach($status as $key=>$val) {
			$tmpl->content .= '
					<option value="'.$key.'">'.$val.'</option>';
		}
		$tmpl->content .= '
		</select>
		&nbsp;<input type="button" class="button" value="hinzuf&uuml;gen" onclick="form_submit(this, \'index.php?p=route&amp;sp=add&amp;id='.$_GET['id'].'&amp;typ=status&amp;ajax\')" />
	</div>';
		
		if($user->rechte['toxxraid']) {
			$tmpl->content .= '
	<table border="0" style="margin:auto">
	<tr>
		<td style="font-weight:bold">Optionen f&uuml;r Spieler-,<br />Ally- und Status-Filter</td>
		<td style="line-height:2.5em;padding-left:12px;text-align:center">
			nur Planeten, die vor mehr als 
			&nbsp;<input type="text" class="smalltext" style="width:40px" name="raid" />&nbsp;
			Tagen geraidet wurden
			<br />
			<input type="checkbox" name="toxx" /> 
			<span class="togglecheckbox" data-name="toxx">nur Planeten, die nicht getoxxt sind</span>
		</td>
	</tr>
	</table>';
		}
	
		$tmpl->content .= '
	<br />';
		if($user->rechte['search']) {
			$tmpl->content .= '
	<div class="center" style="width:80%;margin:auto;line-height:1.4em">
		F&uuml;r umfangreichere Filter zum Hinzuf&uuml;gen von Planeten benutze bitte die Suchfunktion.
	</div>';
		}
		$tmpl->content .= '
	</form>
	</div>';
	}
	
	// Ausgabe
	$tmpl->output();
}


// Planeten hinzufügen / entfernen
else if($_GET['sp'] == 'add') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_GET['typ'], $_POST['mode'], $_POST['planet'], $_POST['player'], $_POST['ally'], $_POST['time'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		$route = new route;
		if($route->load($_GET['id']) AND !$route->finished AND $route->rechte_edit()) {
			// Modus
			$remove = (bool)$_POST['mode'];
			
			// Limit
			$max = (int)$_POST['max'];
			if($max < 0) {
				$max = 0;
			}
			else if($max > 2000) {
				$max = 2000;
			}
			if($max) {
				$route->limit = $max;
			}
			
			// globale Bedingungen
			$conds = array();
			
			// geraidet oder getoxxt
			if($user->rechte['toxxraid'] AND isset($_POST['raid'])) {
				$_POST['raid'] = (int)$_POST['raid'];
				
				if($_POST['raid']) {
					$conds[] = "planetenGeraidet < ".(time()-86400*$_POST['raid']);
					$conds[] = "planetenReserv < ".(time()-86400);
				}
				if(isset($_POST['toxx'])) {
					$conds[] = "planetenGetoxxt < ".time();
					$conds[] = "planetenReserv < ".(time()-86400);
				}
			}
			
			
			// Planeten
			if($_GET['typ'] == 'planet') {
				$_POST['planet'] = trim($_POST['planet']);
				
				// nichts eingegeben
				if($_POST['planet'] == '') {
					$tmpl->error = 'Daten ungültig!';
				}
				// einzelner Planet
				else if(strpos($_POST['planet'], ',') === false) {
					// entfernen
					if($remove) {
						$route->remove($_POST['planet']);
					}
					// Fehler beim Hinzufügen
					else if(($error = $route->add($_POST['planet'])) !== true) {
						$tmpl->error = $error;
						if($error === false) {
							$tmpl->error = 'Daten ungültig!';
						}
					}
				}
				// mehrere Planeten
				else {
					$ids = explode(',', $_POST['planet']);
					
					// entfernen
					if($remove) {
						foreach($ids as $id) {
							$route->remove($id);
						}
					}
					// Fehler beim Hinzufügen
					else if(($error = $route->add_batch($ids)) !== true) {
						$tmpl->error = '';
					}
				}
				
				if($tmpl->error == '') {
					$tmpl->script = '$(\'#routeadd'.$_POST['time'].' input[name="planet"]\').val(\'\');';
				}
			}
			
			// Planeten einer Route/Liste
			else if($_GET['typ'] == 'route' AND isset($_POST['route'])) {
				// Route laden
				$route2 = new route;
				if(($error = $route2->load($_POST['route'])) !== true) {
					$tmpl->error = $error;
				}
				else {
					// Planeten entfernen
					if($remove) {
						$ids = array_keys($route2->data);
						foreach($ids as $id) {
							$route->remove($id);
						}
					}
					// Planeten hinzufügen
					else {
						$route->add_batch(array_keys($route2->data));
					}
				}
			}
			
			// Planeten eines Spielers
			else if($_GET['typ'] == 'player') {
				
				// nichts eingegeben
				if($_POST['player'] == '') {
					$tmpl->error = 'Daten ungültig!';
				}
				else {
					// Name eingegeben
					if(preg_replace('/[\d, ]/', '', $_POST['player']) != '') {
						$query = query("
							SELECT
								playerID,
								player_allianzenID
							FROM
								".GLOBPREFIX."player
							WHERE
								playerName = '".escape($_POST['player'])."'
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						if(!mysql_num_rows($query)) {
							$tmpl->error = 'Der Spieler wurde nicht gefunden!';
						}
						else {
							$data = mysql_fetch_assoc($query);
							// Allianz gesperrt
							if($user->protectedAllies AND in_array($data['player_allianzenID'], $user->protectedAllies)) {
								$tmpl->error = 'Du hast keinen Zugriff auf die Allianz dieses Spielers!';
							}
							else {
								$conds[] = "planeten_playerID = ".$data['playerID'];
							}
						}
					}
					// eine ID eingegeben
					else if(strpos($_POST['player'], ',') === false) {
						$_POST['player'] = (int)$_POST['player'];
						if($_POST['player'] > 0) {
							$conds[] = "(planeten_playerID = ".$_POST['player']." OR playerName = '".$_POST['player']."')";
						}
					}
					// mehrere IDs eingegeben
					else {
						$_POST['player'] = explode(',', $_POST['player']);
						foreach($_POST['player'] as $key=>$val) {
							$val = (int)$val;
							if($val > 0) $_POST['player'][$key] = $val;
							else unset($_POST['player'][$key]);
						}
						if(count($_POST['player'])) {
							$conds[] = "planeten_playerID IN(".implode(",", $_POST['player']).")";
						}
					}
					
					// hinzufügen
					if($tmpl->error == '' AND count($conds)) {
						// Galaxie
						if($route->gala) {
							$conds[] = "systeme_galaxienID = ".$route->gala;
						}
						
						// gesperrte Allianzen
						if($user->protectedAllies) {
							$conds[] = "player_allianzenID NOT IN(".implode(",", $user->protectedAllies).")";
						}
						
						$query = query("
							SELECT
								planetenID
							FROM
								".PREFIX."planeten
								".($route->gala ? "LEFT JOIN ".PREFIX."systeme
									ON systemeID = planeten_systemeID" : "")."
								LEFT JOIN ".GLOBPREFIX."player
									ON playerID = planeten_playerID
							WHERE
								".implode(" AND ", $conds)."
							LIMIT 4000
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						while($row = mysql_fetch_assoc($query)) {
							// Planeten entfernen
							if($remove) {
								$route->remove($row['planetenID']);
							}
							// Planeten hinzufügen
							else {
								$route->add($row['planetenID'],false);
							}
						}
					}
				}
				
				if($tmpl->error == '') {
					$tmpl->script = '$(\'#routeadd'.$_POST['time'].' input[name="player"]\').val(\'\');';
				}
			}
			
			// Planeten einer Allianz
			else if($_GET['typ'] == 'ally') {
				// nichts eingegeben
				if($_POST['ally'] == '') {
					$tmpl->error = 'Daten ungültig!';
				}
				else {
					// Name eingegeben
					if(preg_replace('/[\d, ]/', '', $_POST['ally']) != '') {
						$_POST['ally'] = escape($_POST['ally']);
						
						$query = query("
							SELECT
								allianzenID
							FROM
								".GLOBPREFIX."allianzen
							WHERE
								allianzenTag = '".$_POST['ally']."'
								OR allianzenName = '".$_POST['ally']."'
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						if(!mysql_num_rows($query)) {
							$tmpl->error = 'Die Allianz wurde nicht gefunden!';
						}
						else {
							$ids = array();
							while($row = mysql_fetch_assoc($query)) {
								// Allianz gesperrt
								if(!$user->protectedAllies OR !in_array($row['allianzenID'], $user->protectedAllies)) {
									$ids[] = $row['allianzenID'];
								}
							}
							if(count($ids)) {
								if(count($ids) == 1) {
									$conds[] = "player_allianzenID = ".$ids[0];
								}
								else {
									$conds[] = "player_allianzenID IN(".implode(",", $ids).")";
								}
							}
							else {
								$tmpl->error = 'Du hast keinen Zugriff auf diese Allianz!';
							}
						}
					}
					// eine ID eingegeben
					else if(strpos($_POST['ally'], ',') === false) {
						$_POST['ally'] = (int)$_POST['ally'];
						if($user->protectedAllies AND in_array($_POST['ally'], $user->protectedAllies)) {
							$tmpl->error = 'Du hast keinen Zugriff auf diese Allianz!';
						}
						else if($_POST['ally'] > 0) {
							$conds[] = "(player_allianzenID = ".$_POST['ally']." OR allianzenTag = '".$_POST['ally']."' OR allianzenName = '".$_POST['ally']."')";
						}
					}
					// mehrere IDs eingegeben
					else {
						$_POST['ally'] = explode(',', $_POST['ally']);
						foreach($_POST['ally'] as $key=>$val) {
							$val = (int)$val;
							if($val > 0) $_POST['ally'][$key] = $val;
							else unset($_POST['ally'][$key]);
						}
						if(count($_POST['ally'])) {
							$conds[] = "player_allianzenID IN(".implode(",", $_POST['ally']).")";
						}
					}
					
					// hinzufügen
					if($tmpl->error == '' AND count($conds)) {
						// Galaxie
						if($route->gala) {
							$conds[] = "systeme_galaxienID = ".$route->gala;
						}
						
						// gesperrte Allianzen
						if($user->protectedAllies) {
							$conds[] = "player_allianzenID NOT IN(".implode(",", $user->protectedAllies).")";
						}
						
						$query = query("
							SELECT
								planetenID
							FROM
								".PREFIX."planeten
								".($route->gala ? "LEFT JOIN ".PREFIX."systeme
									ON systemeID = planeten_systemeID" : "")."
								LEFT JOIN ".GLOBPREFIX."player
									ON playerID = planeten_playerID
								LEFT JOIN ".GLOBPREFIX."allianzen
									ON allianzenID = player_allianzenID
							WHERE
								".implode(" AND ", $conds)."
							LIMIT 4000
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						while($row = mysql_fetch_assoc($query)) {
							// Planeten entfernen
							if($remove) {
								$route->remove($row['planetenID']);
							}
							// Planeten hinzufügen
							else {
								$route->add($row['planetenID'],false);
							}
						}
					}
				}
				
				if($tmpl->error == '') {
					$tmpl->script = '$(\'#routeadd'.$_POST['time'].' input[name="ally"]\').val(\'\');';
				}
			}
			
			
			// Status-Planeten
			else if($_GET['typ'] == 'status' AND isset($_POST['status'], $status[$_POST['status']])) {
				// Status
				if(!$_POST['status']) {
					$conds[] = "(statusStatus IS NULL OR statusStatus = 0)";
				}
				else {
					$conds[] = "statusStatus = ".$_POST['status'];
				}
				
				// Galaxie
				if($route->gala) {
					$conds[] = "systeme_galaxienID = ".$route->gala;
				}
				
				// gesperrte Allianzen
				if($user->protectedAllies) {
					$conds[] = "player_allianzenID NOT IN(".implode(",", $user->protectedAllies).")";
				}
				
				$query = query("
					SELECT
						planetenID
					FROM
						".PREFIX."planeten
						".($route->gala ? "LEFT JOIN ".PREFIX."systeme
							ON systemeID = planeten_systemeID" : "")."
						LEFT JOIN ".GLOBPREFIX."player
							ON playerID = planeten_playerID
						LEFT JOIN ".GLOBPREFIX."allianzen
							ON allianzenID = player_allianzenID
						LEFT JOIN ".PREFIX."allianzen_status
							ON statusDBAllianz = ".$user->allianz."
							AND status_allianzenID = allianzenID
					WHERE
						".implode(" AND ", $conds)."
					LIMIT 4000
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				while($row = mysql_fetch_assoc($query)) {
					// Planeten entfernen
					if($remove) {
						$route->remove($row['planetenID']);
					}
					// Planeten hinzufügen
					else {
						$route->add($row['planetenID'],false);
					}
				}
			}
			
			
			// speichern
			$route->save();
			
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				if($remove) {
					insertlog(26, 'entfernt Planeten aus der Route '.$route->info['routenName'].' ('.$_GET['id'].')');
				}
				else {
					insertlog(26, 'fügt Planeten zur Route '.$route->info['routenName'].' ('.$_GET['id'].') hinzu');
				}
			}
		}
		
		$tmpl->script .= '
if($(\'.route'.$_GET['id'].'\').length > 0) {ajaxcall("index.php?p=route&sp=view&id='.$_GET['id'].'&update", false, false, false);}';
	}
	
	$tmpl->output();
}



?>