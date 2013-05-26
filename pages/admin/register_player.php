<?php
/**
 * pages/admin/register_player.php
 * Verwaltung -> Registrierung -> Spieler
 * hinzufügen, löschen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Registriererlaubnis für Spieler hinzufügen
if($_GET['sp'] == 'register_addplayer') {
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
			
			odrequest($_POST['id']);
			
			// Existenz überprüfen
			$query = query("
				SELECT
					playerID,
					playerName,
					player_allianzenID,
					
					allianzenTag,
					allianzenName,
					
					user_playerID,
					
					register_playerID
				FROM
					".GLOBPREFIX."player
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
					LEFT JOIN ".PREFIX."user
						ON user_playerID = playerID
					LEFT JOIN ".PREFIX."register
						ON register_playerID = playerID
				WHERE
					playerID = ".$_POST['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			
			
			// Spieler nicht eingetragen
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Der Spieler wurde nicht gefunden!
				<br />
				Zum Erfassen eines Spielers musst du ihn &uuml;ber die Spieler-ID in der Schnellzugriffsleiste aufrufen.';
			}
			else {
				$data = mysql_fetch_assoc($query);
				
				// Erlaubnis hinzufügen
				if($data['register_playerID'] === NULL) {
					query("
						INSERT INTO ".PREFIX."register
						SET
							register_playerID = ".$_POST['id']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
				
				// Ausgabe
				$tmpl->content = '
					<br />
					Der Spieler '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' wurde hinzugef&uuml;gt';
				
				// Tabellen-Script
				$tmpl->script = '
					$(\'.registeruserrow'.$_POST['id'].'\').remove();
					$(\'.registerplayer .keine\').remove();
					$(\'.registerplayer\').append(\''.addslashes(userrow($data, true)).'\');';
				
				
				// Log-Eintrag
				if($config['logging'] >= 1) {
					insertlog(24, 'fügt eine Registrierungserlaubnis für den Spieler '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' ('.$_POST['id'].') hinzu');
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

// Registriererlaubnis für Spieler entziehen -> Dialog
else if($_GET['sp'] == 'register_delplayer') {
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
					playerName,
					user_playerID,
					
					register_allianzenID
				FROM
					".GLOBPREFIX."player
					LEFT JOIN ".PREFIX."user
						ON user_playerID = playerID
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = player_allianzenID
				WHERE
					playerID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Der Spieler wurde nicht gefunden!';
			}
			// Dialog ausgeben
			else {
				$data = mysql_fetch_assoc($query);
				
				$tmpl->name = 'Registrierungserlaubnis entziehen';
				
				$tmpl->content = '
		<br /><br />
		<div align="center">
			<form action="#" onsubmit="form_send(this, \'index.php?p=admin&amp;sp=register_delplayer2&amp;id='.$_GET['id'].'&amp;ajax&amp;win=\'+win_getid(this), $(this).siblings(\'.ajax\'));return false">
			Soll dem Spieler <a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$_GET['id'].'">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a> wirklich die Registrierungserlaubnis entzogen werden?
			<br /><br />';
				
				$allyRegister = ($data['register_allianzenID'] !== NULL AND $data['register_allianzenID'] != 0);
				
				// Spieler angemeldet
				if($data['user_playerID'] !== NULL) {
					// Ally hat Registrierungserlaubnis
					if($allyRegister) {
						$tmpl->content .= '
				Der Spieler kann die Datenbank weiterhin benutzen,<br />weil seine Allianz eine Registrierungserlaubnis hat.
				<input type="hidden" name="action" value="1" />';
					}
					// Ally hat keine Registrierungserlaubnis -> löschen oder sperren
					else {
						$tmpl->content .= '
				den Spieler
				&nbsp;<select name="action" size="1">
					<option value="0">l&ouml;schen</option>
					<option value="1">sperren</option>
				</select>';
					}
				}
				
				// Spieler nicht angemeldet
				else {
					if($allyRegister) {
						$tmpl->content .= '
				Der Spieler kann sich trotzdem registrieren,<br />weil seine Allianz eine Registrierungserlaubnis hat.';
					}
					
					$tmpl->content .= '<input type="hidden" name="action" value="1" />';
				}
				
				
				$tmpl->content .= '
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

// Registriererlaubnis für Spieler entziehen -> ausführen
else if($_GET['sp'] == 'register_delplayer2') {
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
					playerName,
					
					register_allianzenID
				FROM
					".GLOBPREFIX."player
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = player_allianzenID
				WHERE
					playerID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Spieler nicht eingetragen
			if(!mysql_num_rows($query)) {
				$tmpl->error = 'Der Spieler wurde nicht gefunden!';
			}
			// Erlaubnis entziehen
			else {
				$data = mysql_fetch_assoc($query);
				
				// Erlaubnis entziehen
				query("
					DELETE FROM
						".PREFIX."register
					WHERE
						register_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Cache löschen
				$cache->remove('user'.$_GET['id']);
				
				// Ally hat keine Registrierungserlaubnis
				if($data['register_allianzenID'] === NULL OR $data['register_allianzenID'] == 0) {
					// Spieler sperren
					if($_POST['action']) {
						query("
							UPDATE
								".PREFIX."user
							SET
								userBanned = 2
							WHERE
								user_playerID = ".$_GET['id']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
						
						$log = 'entzieht dem Spieler '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') die Registrierungserlaubnis (wird gesperrt)';
					}
					// Spieler löschen
					else {
						
						user_del($_GET['id']);
						
						$log = 'entzieht dem Spieler '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') die Registrierungserlaubnis (wird gelöscht)';
					}
				}
				else $log = 'entzieht dem Spieler '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') die Registrierungserlaubnis (bleibt angemeldet)';
				
				// Script
				$tmpl->script = '
					win_close('.$_GET['win'].');
					$(\'.registeruserrow'.$_GET['id'].'\').remove();';
				
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



?>