<?php
/**
 * pages/werft/actions.php
 * eigene Werften - Aktionen
 * Werft hinzufügen
 * Bedarf einer Werft ändern
 * Bedarf aller Werften ändern
 * Werft entfernen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


/**
 * Hat der User die Berechtigung zum Bearbeiten des Werftbedarfs?
 * @param $data array MySQL-Datensatz
 *
 * @return bool Berechtigung
 */
function werft_edit_rechte($data) {
	global $user, $status_meta;
	
	$r = false;
	
	// eigene Planeten immer Berechtigung
	if($data['planeten_playerID'] == $user->id) {
		$r = true;
	}
	// ansonsten überprüfen
	else {
		if($user->allianz AND $data['player_allianzenID'] == $user->allianz) $suffix = 'ally';
		else if($user->allianz AND $data['statusStatus'] == $status_meta) $suffix = 'meta';
		else if($data['register_allianzenID'] !== NULL) $suffix = 'register';
		else $suffix = 'other';
		
		// fehlende Berechtigung abfangen
		if(isset($user->rechte['werft_'.$suffix])) {
			$wr = $user->rechte['werft_'.$suffix];
		}
		else {
			$wr = $user->rechte['werft_register'];
		}
		
		if($wr AND $user->rechte['flags_edit_'.$suffix]) $r = true;
	}
	
	return $r;
}



// Werft hinzufügen
if($_GET['sp'] == 'add') {
	// Daten überprüfen
	if(!isset($_POST['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else if(!(int)$_POST['id']) {
		$tmpl->error = 'Daten ungültig!';
	}
	else {
		// Daten sichern
		$_POST['id'] = (int)$_POST['id'];
		
		// hinzufügen
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenWerft = 1
			WHERE
				planetenID = ".$_POST['id']."
				AND planeten_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Anzeige aktualisieren
		$tmpl->script = 'ajaxcall(\'index.php?p=werft&sp=own&update\', $(\'.werften_own\'), false, true);';
		
		// Logfile-Eintrag
		if($config['logging'] >= 2) {
			insertlog(12, 'markiert den Planet '.$_POST['id'].' als Werft');
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Bedarf ändern
else if($_GET['sp'] == 'edit') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Daten abfragen
		$query = query("
			SELECT
				planetenName,
				planetenWerft,
				planetenWerftBedarf,
				
				planeten_playerID,
				
				player_allianzenID,
				
				register_allianzenID,
				
				statusStatus
			FROM
				".PREFIX."planeten
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = player_allianzenID
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Planet nicht gefunden oder falscher Besitzer
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Planet wurde nicht gefunden!';
		}
		else {
			$data = mysql_fetch_assoc($query);
			
			// Berechtigung
			$r = werft_edit_rechte($data);
			
			// keine Berechtigung
			if(!$r) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			else {
			
			
				$tmpl->name = htmlspecialchars($data['planetenName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') - Bedarf';
				
				if($data['planetenWerftBedarf'] == '') {
					$b = array(0,0,0,0,0);
				}
				else {
					$b = unserialize($data['planetenWerftBedarf']);
				}
				
				$tmpl->content = '
			<div class="icontent werftbedarf'.$_GET['id'].'">
			<form name="werft_edit" action="#" onsubmit="return form_send(this, \'index.php?p=werft&amp;sp=edit_send&amp;id='.$_GET['id'].'\', $(this).siblings(\'.ajax\'))">
				<br />
				<div class="center">
					<img src="img/layout/leer.gif" class="ress ress_form erz" /> 
					<input type="text" class="smalltext" name="erz" value="'.($b[0] ? $b[0] : '').'" /> 
					<img src="img/layout/leer.gif" class="ress ress_form metall" /> 
					<input type="text" class="smalltext" name="metall" value="'.($b[1] ? $b[1] : '').'" /> 
					<img src="img/layout/leer.gif" class="ress ress_form wolfram" /> 
					<input type="text" class="smalltext" name="wolfram" value="'.($b[2] ? $b[2] : '').'" /> 
					<img src="img/layout/leer.gif" class="ress ress_form kristall" /> 
					<input type="text" class="smalltext" name="kristall" value="'.($b[3] ? $b[3] : '').'" /> 
					<img src="img/layout/leer.gif" class="ress ress_form fluor" /> 
					<input type="text" class="smalltext" name="fluor" value="'.($b[4] ? $b[4] : '').'" />
					<br /><br /><br />
					<input type="submit" class="button" style="width:100px" value="speichern" /> 
					<input type="button" class="button" style="width:100px" value="auf 0 setzen" onclick="$(this).parents(\'form\').find(\'input\').val(\'\');$(this).parents(\'form\').trigger(\'onsubmit\');" /> 
					<br /><br />
				</div>
				</form>
				<div class="ajax center"></div>
			</div>';
				
				$tmpl->script = '$(\'.werftbedarf'.$_GET['id'].' input[name="erz"]\').select();';
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Bedarf ändern -> absenden
else if($_GET['sp'] == 'edit_send') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_POST['erz'], $_POST['metall'], $_POST['wolfram'], $_POST['kristall'], $_POST['fluor'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Daten abfragen
		$query = query("
			SELECT
				planetenName,
				planetenWerft,
				planetenWerftBedarf,
				
				planeten_playerID,
				
				player_allianzenID,
				
				register_allianzenID,
				
				statusStatus
			FROM
				".PREFIX."planeten
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = player_allianzenID
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Planet nicht gefunden oder falscher Besitzer
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Planet wurde nicht gefunden!';
		}
		else {
			$data = mysql_fetch_assoc($query);
			
			// Berechtigung
			$r = werft_edit_rechte($data);
			
			// keine Berechtigung
			if(!$r) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			else {
				// serialisiertes Array erzeugen
				$b = array(
					(int)$_POST['erz'],
					(int)$_POST['metall'],
					(int)$_POST['wolfram'],
					(int)$_POST['kristall'],
					(int)$_POST['fluor']
				);
				
				$b2 = serialize($b);
				
				// speichern
				query("
					UPDATE ".PREFIX."planeten
					SET
						planetenWerftBedarf = '".escape($b2)."'
					WHERE
						planetenID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$c = 'Werft-Bedarf: ';
				foreach($b as $key=>$val) {
					$c .= $ress[$key].' '.ressmenge2($val, true).' &nbsp; ';
				}
				
				// Anzeige aktualisieren
				$tmpl->script = '
		parentwin_close(\'.werftbedarf'.$_GET['id'].'\');
		ajaxcall(\'index.php?p=werft&sp=own&update\', $(\'.werften_own\'), false, true);
		$(\'.plwerftbedarf'.$_GET['id'].'\').html(\''.$c.'\');';
				
				// Logfile-Eintrag
				if($config['logging'] >= 2) {
					insertlog(12, 'ändert den Bedarf der Werft '.$_GET['id']);
				}
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Bedarf aller eigener Werften ändern
else if($_GET['sp'] == 'edit_all') {
	// Daten unvollständig
	if(!isset($_POST['erz'], $_POST['metall'], $_POST['wolfram'], $_POST['kristall'], $_POST['fluor'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// serialisiertes Array erzeugen
		$b = array(
			(int)$_POST['erz'],
			(int)$_POST['metall'],
			(int)$_POST['wolfram'],
			(int)$_POST['kristall'],
			(int)$_POST['fluor']
		);
		
		$b2 = serialize($b);
		
		// speichern
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenWerftBedarf = '".escape($b2)."'
			WHERE
				planeten_playerID = ".$user->id."
				AND planetenWerft = 1
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$c = 'Werft-Bedarf: ';
		foreach($b as $key=>$val) {
			$c .= $ress[$key].' '.ressmenge2($val, true).' &nbsp; ';
		}
		
		// Anzeige aktualisieren
		$tmpl->script = '
ajaxcall(\'index.php?p=werft&sp=own&update\', $(\'.werften_own\'), false, true);
$(\'.plwerftbedarfu'.$user->id.'\').html(\''.$c.'\');';
		
		// Logfile-Eintrag
		if($config['logging'] >= 2) {
			insertlog(12, 'ändert den Bedarf aller eigener Werften');
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Bedarf aller markierter verbündeter Werften ändern
else if($_GET['sp'] == 'edit_all_ally') {
	// Daten unvollständig
	if(!isset($_POST['ids'], $_POST['erz'], $_POST['metall'], $_POST['wolfram'], $_POST['kristall'], $_POST['fluor'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// serialisiertes Array erzeugen
		$b = array(
			(int)$_POST['erz'],
			(int)$_POST['metall'],
			(int)$_POST['wolfram'],
			(int)$_POST['kristall'],
			(int)$_POST['fluor']
		);
		
		$b2 = serialize($b);
		
		// Bedingungen aufstellen
		$conds = array(
			"planetenWerft = 1",
			"statusStatus = ".$status_meta
		);
		
		// markierte IDs
		parse_str($_POST['ids'], $ids);
		$ids = array_keys($ids);
		
		foreach($ids as $key=>$val) {
			$ids[$key] = (int)$val;
		}
		
		if(!count($ids)) {
			$ids[] = 0;
		}
		
		$conds[] = "planetenID IN(".implode(", ", $ids).")";
		
		// eingeschränkte Berechtigungen
		if(!$user->rechte['werft_ally'] AND $user->allianz) {
			$conds[] = "player_allianzenID != ".$user->allianz;
		}
		if(!$user->rechte['werft_meta'] AND $user->allianz) {
			$conds[] = "(player_allianzenID = ".$user->allianz." OR statusStatus IS NULL OR statusStatus != ".$status_meta.")";
		}
		if($user->protectedAllies) {
			$conds[] = "player_allianzenID NOT IN(".implode(", ", $user->protectedAllies).")";
		}
		if($user->protectedGalas) {
			$conds[] = "systeme_galaxienID NOT IN(".implode(", ", $user->protectedGalas).")";
		}
		
		if(!$user->rechte['flags_edit_ally']) {
			$conds[] = "player_allianzenID != ".$user->allianz; 
		}
		if(!$user->rechte['flags_edit_meta']) {
			$conds[] = "(player_allianzenID = ".$user->allianz." OR statusStatus IS NULL OR statusStatus != ".$status_meta.")";
		}
		
		
		// speichern
		query("
			UPDATE
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			SET
				planetenWerftBedarf = '".escape($b2)."'
			WHERE
				".implode(" AND ", $conds)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Anzeige aktualisieren
		$t = time();
		
		$tmpl->content = 'Der Bedarf wurde gespeichert.';
		
		$tmpl->script = '$("form[name=werft_ally]:visible").trigger("onsubmit");';
		
		// Logfile-Eintrag
		if($config['logging'] >= 2) {
			insertlog(12, 'ändert den Bedarf verbündeter Werften');
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Werft entfernen
else if($_GET['sp'] == 'del') {
	// Daten überprüfen
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else if(!(int)$_GET['id']) {
		$tmpl->error = 'Daten ungültig!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// hinzufügen
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenWerft = 0,
				planetenWerftBedarf = ''
			WHERE
				planetenID = ".$_GET['id']."
				AND planeten_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Anzeige aktualisieren
		$tmpl->script = 'ajaxcall(\'index.php?p=werft&sp=own&update\', $(\'.werften_own\'), false, true);';
		$tmpl->output();
		
		// Logfile-Eintrag
		if($config['logging'] >= 2) {
			insertlog(12, 'entfernt die Markierung als Werft von '.$_GET['id']);
		}
	}
}



?>