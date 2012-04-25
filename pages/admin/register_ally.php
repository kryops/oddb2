<?php
/**
 * pages/admin/register_ally.php
 * Verwaltung -> Registrierung -> Allianzen
 * hinzufügen, löschen, bearbeiten
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Registriererlaubnis für Allianz hinzufügen
if($_GET['sp'] == 'register_addally') {
	// keine Berechtigung
	if(!$user->rechte['verwaltung_user_register']) $tmpl->error = 'Du hast keine Berechtigung!';
	// keine Daten
	else if(!isset($_POST['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_POST['id'] = (int)$_POST['id'];
		
		// ID ungültig
		if($_POST['id'] < 1) {
			$tmpl->error = 'Ung&uuml;ltige ID eingegeben!';
		}
		else {
			// Existenz überprüfen
			$query = query("
				SELECT
					allianzenID,
					allianzenTag,
					allianzenName,
					registerAllyRechte,
					registerProtectedAllies,
					registerProtectedGalas
				FROM
					".GLOBPREFIX."allianzen
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
				WHERE
					allianzenID = ".$_POST['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			
			
			// Allianz nicht eingetragen
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Die Allianz wurde nicht gefunden!
				<br />
				Zum Erfassen einer Allianz musst du eins ihrer Mitglieder &uuml;ber die Spieler-ID in der Schnellzugriffsleiste aufrufen.';
			}
			else {
				$data = mysql_fetch_assoc($query);
				
				// Erlaubnis hinzufügen
				if($data['registerAllyRechte'] === NULL) {
					query("
						INSERT INTO ".PREFIX."register
						SET
							register_allianzenID = ".$_POST['id']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
				
				// Ausgabe
				$tmpl->content = '
					<br />
					Die Allianz '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' wurde hinzugef&uuml;gt';
				
				// Tabellen-Script
				$tmpl->script = '
					$(\'.registerallianzrow'.$_POST['id'].'\').remove();
					$(\'.registerallianzen .keine\').remove();
					$(\'.registerallianzen\').append(\''.addslashes(allianzrow($data, true)).'\');';
				
				
				// Log-Eintrag
				if($config['logging'] >= 1) {
					insertlog(24, 'fügt eine Registrierungserlaubnis für die Allianz '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' ('.$_POST['id'].') hinzu');
				}
			}
		}
	}
	// Ausgabe
	if($tmpl->error) {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	$tmpl->output();
}

// Registriererlaubnis für Allianz entziehen -> Dialog
else if($_GET['sp'] == 'register_delally') {
	// keine Berechtigung
	if(!$user->rechte['verwaltung_user_register']) $tmpl->error = 'Du hast keine Berechtigung!';
	// keine Daten
	else if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// ID ungültig
		if($_GET['id'] < 1) {
			$tmpl->error = 'Ung&uuml;ltige ID eingegeben!';
		}
		else {
			// Existenz überprüfen
			$query = query("
				SELECT
					allianzenTag
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz nicht eingetragen
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Die Allianz wurde nicht gefunden!';
			}
			// Dialog ausgeben
			else {
				$data = mysql_fetch_assoc($query);
				
				$tmpl->name = 'Registrierungserlaubnis entziehen';
				
				$tmpl->content = '
		<br /><br />
		<div align="center">
			<form action="#" onsubmit="form_send(this, \'index.php?p=admin&amp;sp=register_delally2&amp;id='.$_GET['id'].'&amp;ajax&amp;win=\'+win_getid(this), $(this).siblings(\'.ajax\'));return false">
			Soll der Allianz <a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$_GET['id'].'">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a> wirklich die Registrierungserlaubnis entzogen werden?
			<br /><br />
			angemeldete Mitglieder
			&nbsp;<select name="action" size="1">
				<option value="0">l&ouml;schen</option>
				<option value="1">sperren</option>
			</select>
			<br />
			<span class="small hint"><br />(ausgenommen Spieler mit eigener Registriererlaubnis)</span>
			<br /><br /><br />
			<input type="submit" class="button" value="fortfahren" /> 
			<input type="button" class="button" value="abbrechen" onclick="win_close(win_getid(this))" />
			</form>
			<div class="ajax center"></div>
		</div>
		<br /><br />';
			}
		}
	}
	// Ausgabe
	if($tmpl->error) {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	$tmpl->output();
}

// Registriererlaubnis für Allianz entziehen -> ausführen
else if($_GET['sp'] == 'register_delally2') {
	// keine Berechtigung
	if(!$user->rechte['verwaltung_user_register']) $tmpl->error = 'Du hast keine Berechtigung!';
	// keine Daten
	else if(!isset($_GET['id'], $_GET['win'], $_POST['action'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		$_GET['win'] = (int)$_GET['win'];
		
		// ID ungültig
		if($_GET['id'] < 1) {
			$tmpl->error = 'Ung&uuml;ltige ID eingegeben!';
		}
		else {
			// Existenz überprüfen
			$query = query("
				SELECT
					allianzenTag
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz nicht eingetragen
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Die Allianz wurde nicht gefunden!';
			}
			// Erlaubnis entziehen
			else {
				$data = mysql_fetch_assoc($query);
				
				// Erlaubnis entziehen
				query("
					DELETE FROM
						".PREFIX."register
					WHERE
						register_allianzenID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Cache löschen
				$cache->delally($_GET['id']);
				
				// Spieler sperren
				if($_POST['action']) {
					query("
						UPDATE
							".PREFIX."user
							LEFT JOIN ".GLOBPREFIX."player
								ON playerID = user_playerID
							LEFT JOIN ".PREFIX."register
								ON register_playerID = user_playerID
						SET
							userBanned = 2
						WHERE
							user_allianzenID = ".$_GET['id']."
							AND register_playerID IS NULL
							AND userBanned = 0
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					$log = 'entzieht der Allianz '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') die Registrierungserlaubnis (Spieler werden gesperrt)';
				}
				// Spieler löschen
				else {
					$query = query("
						SELECT
							user_playerID
						FROM
							".PREFIX."user
							LEFT JOIN ".GLOBPREFIX."player
								ON playerID = user_playerID
							LEFT JOIN ".PREFIX."register
								ON register_playerID = user_playerID
						WHERE
							user_allianzenID = ".$_GET['id']."
							AND register_playerID IS NULL
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					$pl = array();
					
					while($row = mysql_fetch_assoc($query)) {
						$pl[] = $row['user_playerID'];
					}
					
					if(count($pl)) {
						user_del($pl);
					}
					
					$log = 'entzieht der Allianz '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') die Registrierungserlaubnis (Spieler werden gelöscht)';
				}
				
				// Script
				$tmpl->script = '
					win_close('.$_GET['win'].');
					$(\'.registerallianzrow'.$_GET['id'].'\').remove();';
				
				// Log-Eintrag
				if($config['logging'] >= 1) {
					insertlog(24, $log);
				}
			}
		}
	}
	// Ausgabe
	if($tmpl->error) {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	$tmpl->output();
}

// Allianz bearbeiten
else if($_GET['sp'] == 'register_allyconfig') {
	// keine Berechtigung
	if(!$user->rechte['verwaltung_user_register']) $tmpl->error = 'Du hast keine Berechtigung!';
	// keine Daten
	else if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// ID ungültig
		if($_GET['id'] < 1) {
			$tmpl->error = 'Ung&uuml;ltige ID eingegeben!';
		}
		else {
			// Existenz überprüfen
			$query = query("
				SELECT
					allianzenTag,
					
					registerAllyRechte,
					registerProtectedAllies,
					registerProtectedGalas
				FROM
					".GLOBPREFIX."allianzen
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
				WHERE
					allianzenID = ".$_GET['id']."
					AND registerAllyRechte IS NOT NULL
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz nicht eingetragen
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Die Allianz wurde nicht gefunden oder hat keine Registrierungserlaubnis!';
			}
			// Dialog ausgeben
			else {
				$data = mysql_fetch_assoc($query);
				$arechte = $data['registerAllyRechte'] ? explode('+', $data['registerAllyRechte']) : array();
				
				// Rechte bereinigen
				unset($rechtenamen['override_allies']);
				unset($rechtenamen['override_galas']);
				
				
				$tmpl->name = 'Allianz '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') bearbeiten';
				
				$tmpl->content = '
		<br />
		<div class="icontent">
			<span class="small hint">Die Einschr&auml;nkungen gelten unabh&auml;ngig vom Rechtelevel.
			<br />
			Sie k&ouml;nnen durch userspezifische Berechtigungen &uuml;berschrieben werden.</span>
			<br /><br />
			<form action="#" onsubmit="form_send(this, \'index.php?p=admin&amp;sp=register_allyconfig2&amp;id='.$_GET['id'].'&amp;ajax&amp;win=\'+win_getid(this), $(this).siblings(\'.ajax\'));return false">
			<b>Sichtbarkeit einschr&auml;nken</b>
			<br />
			<div class="icontent formcontent">
				Allianzen sperren:
				<input type="text" class="text" name="protectedallies" value="'.str_replace('+', ',', $data['registerProtectedAllies']).'" /> <span class="small hint">(IDs mit Kommas getrennt)</span>
				<br />
				Galaxien sperren:
				<input type="text" class="text" name="protectedgalas" value="'.str_replace('+', ',', $data['registerProtectedGalas']).'" /> <span class="small hint">(mit Kommas getrennt)</span>
			</div>
			
			<br />
			<b>Berechtigungen einschr&auml;nken</b>
			<br />
			<div class="icontent">
				<table class="tsmall tnarrow trechte">';
				foreach($rechtenamen as $key=>$name) {
					// Daten aufbereiten
					$rtyp = !in_array($key, $arechte);
					
					// Zeile ausgeben
					$tmpl->content .= '
					<tr>
						<td style="background-color:#005500"><input type="radio" name="'.$key.'" value="1"'.($rtyp ? ' checked="checked"' : '').' /></td>
						<td style="background-color:#aa0000"><input type="radio" name="'.$key.'" value="0"'.(!$rtyp ? ' checked="checked"' : '').' /></td>
						<td';
					// erlaubt -> grün
					if($rtyp == 1) {
						$tmpl->content .= ' style="color:#00aa00"';
					}
					// gesperrt -> rot
					else {
						$tmpl->content .= ' style="color:#ff3322"';
					}
					$tmpl->content .= '> &nbsp;'.htmlspecialchars($name, ENT_COMPAT, 'UTF-8').'</td>
					</tr>';
				}
				$tmpl->content .= '
				</table>
			</div>
			<br />
			<div class="center">
				<input type="submit" class="button" value="speichern" /> 
				<input type="button" class="button" value="abbrechen" onclick="win_close(win_getid(this))" />
			</div>
			</form>
			<div class="ajax center"></div>
		</div>
		<br />';
				
				// Script zum Ändern der Farbe
				$tmpl->script = '$(\'.trechte input\').click(function(){rechte_click(this)});';
			}
		}
	}
	// Ausgabe
	if($tmpl->error) {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	$tmpl->output();
}

// Allianz bearbeiten -> ausführen
else if($_GET['sp'] == 'register_allyconfig2') {
	// keine Berechtigung
	if(!$user->rechte['verwaltung_user_register']) $tmpl->error = 'Du hast keine Berechtigung!';
	// keine Daten
	else if(!isset($_GET['id'], $_GET['win'], $_POST['protectedallies'], $_POST['protectedgalas'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		$_GET['win'] = (int)$_GET['win'];
		
		// ID ungültig
		if($_GET['id'] < 1) {
			$tmpl->error = 'Ung&uuml;ltige ID eingegeben!';
		}
		else {
			// Existenz überprüfen und Daten abfragen
			$query = query("
				SELECT
					allianzenID,
					allianzenTag,
					allianzenName
				FROM
					".GLOBPREFIX."allianzen
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
				WHERE
					allianzenID = ".$_GET['id']."
					AND register_allianzenID IS NOT NULL
				ORDER BY
					allianzenID ASC
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz nicht eingetragen
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Die Allianz wurde nicht gefunden!';
			}
			// Änderungen speichern
			else {
				$data = mysql_fetch_assoc($query);
				
				// Allianzen
				$pallies = '';
				if(trim($_POST['protectedallies']) != '') {
					$pallies = explode(',', $_POST['protectedallies']);
					foreach($pallies as $key=>$val) {
						$val = (int)$val;
						if($val) $pallies[$key] = $val;
						else unset($pallies[$key]);
					}
					if(count($pallies)) {
						$pallies = implode('+', $pallies);
					}
					else $pallies = '';
				}
				
				// Galaxien
				$pgalas = '';
				if(trim($_POST['protectedgalas']) != '') {
					$pgalas = explode(',', $_POST['protectedgalas']);
					foreach($pgalas as $key=>$val) {
						$val = (int)$val;
						if($val) $pgalas[$key] = $val;
						else unset($pgalas[$key]);
					}
					if(count($pgalas)) {
						$pgalas = implode('+', $pgalas);
					}
					else $pgalas = '';
				}
				
				// Rechte-Array erzeugen
				$arechte = array();
				
				foreach($_POST as $key=>$val) {
					if(isset($rechtenamen[$key]) AND !$val AND !in_array($key, $arechte)) {
						$arechte[] = $key;
					}
				}
				
				$arechte = implode('+', $arechte);
				
				// speichern
				query("
					UPDATE
						".PREFIX."register
					SET
						registerProtectedAllies = '".$pallies."',
						registerProtectedGalas = '".$pgalas."',
						registerAllyRechte = '".escape($arechte)."'
					WHERE
						register_allianzenID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Cache löschen
				$cache->delally($_GET['id']);
				
				// Script
				$data['registerAllyRechte'] = $arechte;
				$data['registerProtectedAllies'] = $pallies;
				$data['registerProtectedGalas'] = $pgalas;
				
				$c = addslashes(str_replace(
					array('<tr class="registerallianzrow'.$_GET['id'].'">', '</tr>'),
					array('', ''),
					allianzrow($data, true)
				));
				
				$tmpl->script = '
					win_close('.$_GET['win'].');
					$(\'.registerallianzrow'.$_GET['id'].'\').html(\''.$c.'\');';
				
				// Log-Eintrag
				if($config['logging'] >= 1) {
					insertlog(24, 'bearbeitet die Einschränkungen der Allianz'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')');
				}
			}
		}
	}
	// Ausgabe
	if($tmpl->error) {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	$tmpl->output();
}

?>