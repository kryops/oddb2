<?php
/**
 * pages/player/actions.php
 * Spieleriste - Verwaltungsaktionen:
 * Spieler freischalten
 * Spieler löschen
 * automatische Sperrung überprüfen
 * allianzunabhängige Registrierungserlaubnis erteilen
 * Spieler sperren
 * Spieler bearbeiten
 * das Rechtelevel eines Spielers ändern
 * Sitterprflicht ändern
 * einzelne Berechtigungen eines Spielers ändern
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Spieler freischalten
if($_GET['sp'] == 'free') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_POST['rechtelevel'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// ungültiges Rechtelevel
	if(!isset($rechte[$_POST['rechtelevel']])) {
		$tmpl->error = 'Ungültiges Rechtelevel!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// darf Rechtelevel nicht vergeben
	else if($_POST['rechtelevel'] > $user->rechte['verwaltung_user_maxlevel']) {
		$tmpl->error = 'Du hast keine Berechtigung, dieses Rechtelevel zu vergeben!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// alles ok
			else {
				// freischalten
				query("
					UPDATE ".PREFIX."user
					SET
						userBanned = 0,
						userRechtelevel = ".$_POST['rechtelevel']."
					WHERE
						user_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Cache löschen
				$cache->remove('user'.$_GET['id']);
				
				// Ausgabe
				$tmpl->content = 'Der Spieler wurde als <b>'.$rechte[$_POST['rechtelevel']]['name'].'</b> freigeschaltet. &nbsp;<img src="img/layout/leer.gif" style="background-position:-1020px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=player&amp;sp=edit&amp;id='.$_GET['id'].'" alt="bearbeiten" title="'.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' bearbeiten" />';
				
				// User-Zeile aktualisieren
				$tmpl->script = 'ajaxcall(\'index.php?p=player&sp=update&id='.$_GET['id'].'&ajax\', false, false, false);$(\'.userlist'.$_GET['id'].'\').css(\'opacity\', 1)';
				
				// Log-Eintrag
				if($config['logging']) {
					insertlog(21, 'schaltet den Spieler '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') mit dem Rechtelevel '.$rechte[$_POST['rechtelevel']]['name'].' frei');
				}
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Spieler löschen
else if($_GET['sp'] == 'del') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// eigener Account
	else if((int)$_GET['id'] == $user->id) {
		$tmpl->error = 'Du kannst dich nicht selbst löschen!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// alles ok
			else {
				// User löschen
				user_del($_GET['id']);
				
				// Cache löschen
				$cache->remove('user'.$_GET['id']);
				
				// Ausgabe
				if(isset($_GET['list'])) {
					$tmpl->content = 'gel&ouml;scht';
				}
				// Tabelle "noch nicht freigeschaltet"
				else {
					$tmpl->content = 'Der Spieler wurde gel&ouml;scht';
					
					// Buttons der Tabellen-Zeile entfernen
					$tmpl->script = '$(\'.userlist'.$_GET['id'].' .userlistaction\').html(\'gelöscht\')';
				}
				
				// Log-Eintrag
				if($config['logging']) {
					insertlog(21, 'löscht den Spieler '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')');
				}
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// automatische Sperrung überprüfen
else if($_GET['sp'] == 'autoban_status') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// das odrequest erledigt das meiste
		odrequest($_GET['id'], true);
		
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				userBanned,
				
				register_allianzenID
			FROM
				".PREFIX."user
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = user_allianzenID
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Ausgabe
			// nicht mehr gesperrt
			if(!$data['userBanned']) {
				$tmpl->content = 'Status &uuml;berpr&uuml;ft, Sperrung aufgehoben';
			}
			// trotz Registriererlaubnis noch gebannt
			else if($data['register_allianzenID'] !== NULL) {
				// Sperre manuell aufheben
				query("
					UPDATE ".PREFIX."user
					SET
						userBanned = 0
					WHERE
						user_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Ausgabe
				$tmpl->content = 'Status &uuml;berpr&uuml;ft, Sperrung aufgehoben';
				
				// Transparenz der Tabellen-Zeile entfernen
				$tmpl->script = '$(\'.userlist'.$_GET['id'].'\').css(\'opacity\', 1)';
			}
			// immer noch gesperrt
			else {
				$tmpl->content = 'Sperrung nicht aufgehoben';
				// allianzunabhängige Registriererlaubnis geben
				if($user->rechte['verwaltung_user_register']) {
					$tmpl->content .= '&nbsp;
				<a class="link" onclick="if(window.confirm(\'Dem Spieler wirklich eine allianzunabhängige Registriererlaubnis geben?\')){ajaxcall(\'index.php?p=player&amp;sp=autoban_register&amp;id='.$_GET['id'].'&amp;ajax\', this.parentNode, false, false)}" style="font-weight:bold">allianzunabh&auml;ngige Registriererlaubnis geben</a>';
				}
			}
			
			// Cache löschen
			$cache->remove('user'.$_GET['id']);
			
			// Log-Eintrag
			if($config['logging']) {
				insertlog(21, 'lässt den Autoban-Status von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') erneut überprüfen');
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// allianzunabhängige Registrierungserlaubnis geben
else if($_GET['sp'] == 'autoban_register') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				userBanned
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// hat er schon eine Registrierungserlaubnis
			$query = query("
				SELECT
					registerID
				FROM
					".PREFIX."register
				WHERE
					register_playerID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(!mysql_num_rows($query)) {
				// Registriererlaubnis erteilen
				query("
					INSERT INTO
						".PREFIX."register
					SET
						register_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
			
			
			// Sperrung nur aufheben, wenn automatisch gebannt
			$banned = 0;
			if($data['userBanned'] != 2) $banned = $data['userBanned'];
			
			query("
				UPDATE ".PREFIX."user
				SET
					userBanned = ".$banned."
				WHERE
					user_playerID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Cache löschen
			$cache->remove('user'.$_GET['id']);
			
			// Ausgabe
			$tmpl->content = 'allianzunabh&auml;ngige Registriererlaubnis erteilt';
			
			// Transparenz der Tabellen-Zeile entfernen
			$tmpl->script = '$(\'.userlist'.$_GET['id'].'\').css(\'opacity\', 1)';
			
			// Log-Eintrag
			if($config['logging']) {
				insertlog(21, 'erteilt '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') eine allianzunabhängige Registriererlaubnis');
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Spieler bearbeiten
else if($_GET['sp'] == 'edit') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID,
				userRechtelevel,
				userBanned,
				userSitterpflicht
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// noch nicht freigeschaltet
			else if($data['userBanned'] == 3) {
				$tmpl->error = 'Du musst den Spieler erst freischalten, bevor du ihn bearbeiten kannst!';
			}
			// alles ok
			else {
				$tmpl->name = htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' bearbeiten';
				$tmpl->content = '
			<div class="icontent">
				<div class="center ajax"></div>
				<br />';
				// nur zugelassen, wenn nicht eigener Account
				if($_GET['id'] != $user->id) {
					if($data['userBanned'] <= 1) {
						$tmpl->content .= '
				<div>';
						if(!$data['userBanned']) {
							$tmpl->content .= '<a onclick="if(window.confirm(\'Soll der Spieler wirklich gesperrt werden?\')){ajaxcall(\'index.php?p=player&amp;sp=ban&amp;id='.$_GET['id'].'&amp;state=1&amp;ajax\', this.parentNode, false, false)}">Den Spieler sperren</a>';
						}
						else {
							$tmpl->content .= '<a onclick="if(window.confirm(\'Soll die Sperrung des Spielers wirklich aufgehoben werden?\')){ajaxcall(\'index.php?p=player&amp;sp=ban&amp;id='.$_GET['id'].'&amp;state=0&amp;ajax\', this.parentNode, false, false)}">Sperrung des Spielers aufheben</a>';
						}
						$tmpl->content .= '
				</div>';
					}
					$tmpl->content .= '
				<br />';
					// Berechtigung zum Ändern des Rechtelevels
					if($data['userRechtelevel'] <= $user->rechte['verwaltung_user_maxlevel']) {
						$tmpl->content .= '
				<div>
					Rechtelevel &auml;ndern: 
					<select name="rechtelevel" size="1">';
						foreach($rechte as $key=>$val) {
							// Berechtigung zum Vergeben
							if($key <= $user->rechte['verwaltung_user_maxlevel']) {
								$tmpl->content .= '
						<option value="'.$key.'"'.(($key == $data['userRechtelevel']) ? ' selected="selected"' : '').'>'.$val['name'].'</option>';
							}
							// ausgegraut
							else {
								$tmpl->content .= '
						<option value="'.$key.'" disabled="disabled">'.$val['name'].'</option>';
							}
						}
						$tmpl->content .='
					</select> 
					<input type="button" class="button" value="zuweisen" onclick="ajaxcall(\'index.php?p=player&amp;sp=rechtelevel&amp;id='.$_GET['id'].'&amp;rechtelevel=\'+$(this).siblings(\'select\').val()+\'&amp;ajax\', $(this.parentNode).siblings(\'.ajax\'), false, false)" />
				</div>
				<br />';
					}
					// keine Berechtigung zum Ändern des Rechtelevels
					else {
						$tmpl->content .= '
					<div>
						Rechtelevel: '.$rechte[$data['userRechtelevel']]['name'].' <span class="small hint">(Du hast keine Berechtigung zum &Auml;ndern)</span>
					</div>
					<br />';
					}
					// userspezifische Berechtigungen
					if($user->rechte['verwaltung_user_custom']) {
						$tmpl->content .= '
				<div>
					<a class="link winlink contextmenu" data-link="index.php?p=player&amp;sp=rechte&amp;id='.$_GET['id'].'">userspezifische Berechtigungen bearbeiten</a>
				</div>
				<br />';
					}
				}
				// Sitterpflicht
				$tmpl->content .= '
				<div>
					Sitterpflicht: 
					<select name="sitterpflicht" size="1">
						<option value="0">nein</option>
						<option value="1"'.($data['userSitterpflicht'] ? ' selected="selected"' : '').'>ja</option>
					</select> 
					<input type="button" class="button" value="zuweisen" onclick="ajaxcall(\'index.php?p=player&amp;sp=sitterpflicht&amp;id='.$_GET['id'].'&amp;state=\'+$(this).siblings(\'select\').val()+\'&amp;ajax\', $(this.parentNode).siblings(\'.ajax\'), false, false)" />
				</div>
				<br />
			</div>';
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// sperren / manuelle Sperrung aufheben
else if($_GET['sp'] == 'ban') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_GET['state'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// eigener Account
	else if((int)$_GET['id'] == $user->id) {
		$tmpl->error = 'Du kannst dich nicht selbst sperren!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		$_GET['state'] = $_GET['state'] ? 1 : 0;
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID,
				userBanned
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// automatisch gesperrt oder noch nicht freigeschaltet
			else if($data['userBanned'] > 1) {
				$tmpl->error = 'Der Spieler wurde automatisch gesperrt, du kannst ihn nicht manuell wieder entsperren. Ändere stattdessen die Registrierungseinstellungen!';
			}
			// alles ok
			else {
				// Ban aufheben
				query("
					UPDATE ".PREFIX."user
					SET
						userBanned = ".$_GET['state']."
					WHERE
						user_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Cache löschen
				$cache->remove('user'.$_GET['id']);
				
				// Ausgabe
				// über die "gesperrte User"-Liste entsperrt
				if(isset($_GET['list2'])) {
					$tmpl->content = 'Die Sperrung wurde aufgehoben';
				}
				// gesperrt
				else if($_GET['state']) {
					$tmpl->content = '<a onclick="if(window.confirm(\'Soll die Sperrung des Spielers wirklich aufgehoben werden?\')){ajaxcall(\'index.php?p=player&amp;sp=ban&amp;id='.$_GET['id'].'&amp;state=0&amp;ajax\', this.parentNode, false, false)}">Sperrung des Spielers aufheben</a>';
					
					// Transparenz der Tabellen-Zeile
					$tmpl->script = '$(\'.userlist'.$_GET['id'].'\').css(\'opacity\', 0.4)';
				}
				// entsperrt
				else {
					$tmpl->content = '<a onclick="if(window.confirm(\'Soll der Spieler wirklich gesperrt werden?\')){ajaxcall(\'index.php?p=player&amp;sp=ban&amp;id='.$_GET['id'].'&amp;state=1&amp;ajax\', this.parentNode, false, false)}">Den Spieler sperren</a>';
					
					// Transparenz der Tabellen-Zeile entfernen
					$tmpl->script = '$(\'.userlist'.$_GET['id'].'\').css(\'opacity\', 1)';
				}
				
				// Log-Eintrag
				if($config['logging']) {
					// gesperrt
					if($_GET['state']) {
						insertlog(21, 'Sperrt den Spieler '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')');
					}
					// entsperrt
					else {
						insertlog(21, 'hebt die Sperrung von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') auf');
					}
				}
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Rechtelevel ändern
else if($_GET['sp'] == 'rechtelevel') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_GET['rechtelevel'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// ungültiges Rechtelevel
	else if(!isset($rechte[$_GET['rechtelevel']])) {
		$tmpl->error = 'Ungültiges Rechtelevel ausgewählt!';
	}
	// darf Rechtelevel nicht vergeben
	else if($_GET['rechtelevel'] > $user->rechte['verwaltung_user_maxlevel']) {
		$tmpl->error = 'Du hast keine Berechtigung, dieses Rechtelevel zu vergeben';
	}
	// eigener Account
	else if((int)$_GET['id'] == $user->id) {
		$tmpl->error = 'Du kannst dich nicht selbst bearbeiten!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID,
				userRechtelevel
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// Rechtelevel des Spielers zu hoch
			else if($data['userRechtelevel'] > $user->rechte['verwaltung_user_maxlevel']) {
				$tmpl->error = 'Du hast keine Berechtigung, da der Spieler ein zu hohes Rechtelevel hat!';
			}
			// alles ok
			else {
				// Rechtelevel zuweisen
				query("
					UPDATE ".PREFIX."user
					SET
						userRechtelevel = ".$_GET['rechtelevel']."
					WHERE
						user_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Cache löschen
				$cache->remove('user'.$_GET['id']);
				
				// Ausgabe
				$name = htmlspecialchars($rechte[$_GET['rechtelevel']]['name'], ENT_COMPAT, 'UTF-8');
				$tmpl->content = 'Rechtelevel '.$name.' zugewiesen';
				
				// User-Zeile aktualisieren
				$tmpl->script = 'ajaxcall(\'index.php?p=player&sp=update&id='.$_GET['id'].'&ajax\', false, false, false)';
				
				// Log-Eintrag
				if($config['logging']) {
						insertlog(21, 'Weist dem Spieler '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].') das Rechtelevel '.$name.' zu');
				}
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Sitterpflicht ändern
else if($_GET['sp'] == 'sitterpflicht') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_GET['state'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		$_GET['state'] = $_GET['state'] ? 1 : 0;
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// alles ok
			else {
				// Rechtelevel zuweisen
				query("
					UPDATE ".PREFIX."user
					SET
						userSitterpflicht = ".$_GET['state']."
					WHERE
						user_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Ausgabe
				if($_GET['state']) {
					$tmpl->content = 'Sitterpflicht zugewiesen';
				}
				else {
					$tmpl->content = 'Sitterpflicht entfernt';
				}
				
				// User-Zeile aktualisieren
				$tmpl->script = 'ajaxcall(\'index.php?p=player&sp=update&id='.$_GET['id'].'&ajax\', false, false, false)';
				
				// Log-Eintrag
				if($config['logging']) {
						insertlog(21, 'Ändert die Sitterpflicht von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')');
				}
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Berechtigungen einzeln ändern
else if($_GET['sp'] == 'rechte') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_user_custom'] OR (!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register'])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// eigener Account
	else if((int)$_GET['id'] == $user->id) {
		$tmpl->error = 'Du kannst dich nicht selbst bearbeiten!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID,
				userRechtelevel,
				userRechte,
				
				registerAllyRechte
			FROM
				".PREFIX."user
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = user_allianzenID
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = user_allianzenID
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// alles ok
			else {
				$tmpl->name = 'Berechtigungen von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' bearbeiten';
				
				// effektive Berechtigungen ermitteln
				$r = getrechte(
					$data['userRechtelevel'],
					'',
					'',
					$data['registerAllyRechte'],
					''
				);
				
				$r = $r[1];
				
				// Daten aufbereiten
				if($data['userRechte'] != '') $data['userRechte'] = unserialize($data['userRechte']);
				
				
				// Rechte bereinigen
				unset($rechtenamen['override_allies']);
				unset($rechtenamen['override_galas']);
				
				$tmpl->content = '
				<div class="icontent">
					Graue Berechtigungen sind durch das Rechtelevel oder die Allianzrechte des Spielers gesperrt.
					<br />
					Du kannst nur die Berechtigungen bearbeiten, die du auch selbst hast.
					<br /><br />
					<span class="small2">
					<span style="font-weight:bold">normal</span>: vom Rechtelevel abh&auml;ngig
					<br />
					<span style="font-weight:bold;color:#00aa00">gr&uuml;n</span>: Funktion nutzbar
					<br />
					<span style="font-weight:bold;color:#ff3322">rot</span>: Funktion gesperrt
					</span>
					<br /><br />
					<form name="rechte">
					<table class="tsmall tnarrow trechte">';
				foreach($rechtenamen as $key=>$name) {
					// Berechtigung zum Ändern?
					$rchange = $user->rechte[$key];
					
					// Daten aufbereiten
					$rtyp = -1;
					if($data['userRechte'] AND isset($data['userRechte'][$key])) {
						$rtyp = $data['userRechte'][$key] ? 1 : 0;
					}
					// Zeile ausgeben
					$tmpl->content .= '
					<tr>
						<td><input type="radio" name="'.$key.'" value="-1"'.(($rtyp == -1) ? ' checked="checked" ' : '').($rchange ? '' : 'disabled="disabled"').' /></td>
						<td style="background-color:#005500"><input type="radio" name="'.$key.'" value="1"'.(($rtyp == 1) ? ' checked="checked" ' : '').($rchange ? '' : 'disabled="disabled"').' /></td>
						<td style="background-color:#aa0000"><input type="radio" name="'.$key.'" value="0"'.(($rtyp == 0) ? ' checked="checked" ' : '').($rchange ? '' : 'disabled="disabled"').' /></td>
						<td';
					// von Rechtelevel oder Allianz gesperrt
					if(!$r[$key]) $tmpl->content .= ' class="rechtedisabled"';
					// ausgeblendet
					if($rtyp == -1 AND !$r[$key]) {
						$tmpl->content .= ' style="opacity:0.4;filter:alpha(opacity=40)"';
					}
					// erlaubt -> grün
					else if($rtyp == 1) {
						$tmpl->content .= ' style="color:#00aa00"';
					}
					// gesperrt -> rot
					else if($rtyp == 0) {
						$tmpl->content .= ' style="color:#ff3322"';
					}
					$tmpl->content .= '> &nbsp;'.htmlspecialchars($name, ENT_COMPAT, 'UTF-8').'</td>
					</tr>';
				}
				$tmpl->content .= '
					</table>';
				// maximales Rechtelevel, alle Allianzen und Galaxien sichtbar
				$tmpl->content .= '
					<br />
					<div class="small2" style="line-height:24px">
						h&ouml;chstes zu vergebendes Rechtelevel: ';
				// keine Berechtigung zum Ändern des maximalen Rechtelevels
				if(isset($data['userRechte']['verwaltung_user_maxlevel']) AND $data['userRechte']['verwaltung_user_maxlevel'] > $user->rechte['verwaltung_user_maxlevel']) {
					$tmpl->content .= $rechte[$data['userRechte']['verwaltung_user_maxlevel']]['name']. ' <span class="small hint">(keine Berechtigung zum &Auml;ndern)</span>';
				}
				else if(!isset($data['userRechte']['verwaltung_user_maxlevel']) AND $rechte[$data['userRechtelevel']]['verwaltung_user_maxlevel'] > $user->rechte['verwaltung_user_maxlevel']) {
					$tmpl->content .= 'unver&auml;ndert ('.$rechte[$rechte[$data['userRechtelevel']]['verwaltung_user_maxlevel']]['name'].') <span class="small hint">(keine Berechtigung zum &Auml;ndern)</span>';
				}
				else {
					$tmpl->content .= '
						<select name="verwaltung_user_maxlevel" size="1">
							<option value="-1">- unver&auml;ndert ('.$rechte[$rechte[$data['userRechtelevel']]['verwaltung_user_maxlevel']]['name'].') -</option>';
					foreach($rechte as $key=>$val) {
						// Berechtigung zum Ändern?
						$rchange = $user->rechte['verwaltung_user_maxlevel'] >= $key;
						
						$tmpl->content .= '
								<option value="'.$key.'"'.((isset($data['userRechte']['verwaltung_user_maxlevel']) AND $key == $data['userRechte']['verwaltung_user_maxlevel']) ? ' selected="selected" ' : '').($rchange ? '' : 'disabled="disabled"').'>'.$val['name'].'</option>';
					}
					
					$tmpl->content .= '
						</select>';
				}
				$tmpl->content .= '
						<br />
						<input type="checkbox" name="override_allies"'.(isset($data['userRechte']['override_allies']) ? ' checked="checked" ' : '').($user->protectedAllies ? 'disabled="disabled" style="opacity:0.5"' : '').' /> 
						<span class="togglecheckbox" data-name="override_allies">alle Allianzen sichtbar</span>
						<br />
						<input type="checkbox" name="override_galas"'.(isset($data['userRechte']['override_galas']) ? ' checked="checked" ' : '').($user->protectedGalas ? 'disabled="disabled" style="opacity:0.5"' : '').' /> 
						<span class="togglecheckbox" data-name="override_galas">alle Galaxien sichtbar</span>
					</div>
					<br />
					<div class="center">
						<div class="center ajax"></div>
						<input type="button" class="button" value="Berechtigungen speichern" onclick="form_send(this.parentNode.parentNode, \'index.php?p=player&amp;sp=rechte_send&amp;id='.$_GET['id'].'&amp;ajax\', $(this).siblings(\'.ajax\'))" />
					</div>
					</form>
				</div>';
				
				// Script zum Ändern der Farbe
				$tmpl->script = '$(\'.trechte input\').click(function(){rechte_click(this)});';
			}
		}
	}
	
	// Fehler rendern
	if($tmpl->error) $tmpl->error = '<br />'.$tmpl->error;
	
	// Ausgabe
	$tmpl->output();
}

// Berechtigungen einzeln ändern: abschicken
else if($_GET['sp'] == 'rechte_send') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_user_custom'] OR (!$user->rechte['verwaltung_userally'] AND !$user->rechte['verwaltung_user_register'])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// eigener Account
	else if((int)$_GET['id'] == $user->id) {
		$tmpl->error = 'Du kannst dich nicht selbst bearbeiten!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID,
				userRechtelevel,
				userRechte
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			if($data['userRechte'] != '') {
				$data['userRechte'] = unserialize($data['userRechte']);
			}
			else $data['userRechte'] = array();
			
			// Spieler nicht in der eigenen Ally
			if(!$user->rechte['verwaltung_user_register'] AND (!$user->allianz OR $data['user_allianzenID'] != $user->allianz)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
			}
			// alles ok
			else {
				// Daten aufbereiten
				foreach($_POST as $key=>$val) {
					// falsche und unveränderte Werte löschen
					if($key != 'verwaltung_user_maxlevel') {
						if($val == -1 OR !isset($rechtenamen[$key])) {
							unset($_POST[$key]);
						}
						else if($val) $_POST[$key] = true;
						else $_POST[$key] = false;
					}
					else if($val == -1) {
						unset($_POST[$key]);
					}
				}
				
				if(isset($_POST['verwaltung_user_maxlevel'])) {
					$_POST['verwaltung_user_maxlevel'] = (int)$_POST['verwaltung_user_maxlevel'];
				}
				
				// alte Einstellungen übernehmen, die man nicht ändern durfte
				foreach($user->rechte as $key=>$val) {
					if(!$val AND $key != 'verwaltung_user_maxlevel') {
						// altes übernehmen
						if(isset($data['userRechte'][$key])) {
							$_POST[$key] = $data['userRechte'][$key];
						}
						// neues löschen
						else if(isset($_POST[$key])) {
							unset($_POST[$key]);
						}
					}
				}
				
				// höchstes zu vergebendes Rechtelevel
				$rchange = true;
				if(isset($data['userRechte']['verwaltung_user_maxlevel']) AND $data['userRechte']['verwaltung_user_maxlevel'] > $user->rechte['verwaltung_user_maxlevel']) {
					$rchange = false;
				}
				else if(!isset($data['userRechte']['verwaltung_user_maxlevel']) AND $rechte[$data['userRechtelevel']]['verwaltung_user_maxlevel'] > $user->rechte['verwaltung_user_maxlevel']) {
					$rchange = false;
				}
				
				// keine Berechtigung zum Ändern
				if(!$rchange) {
					// altes übernehmen
					if(isset($data['userRechte']['verwaltung_user_maxlevel'])) {
						$_POST['verwaltung_user_maxlevel'] = $data['userRechte']['verwaltung_user_maxlevel'];
					}
					// neues löschen
					else if(isset($_POST['verwaltung_user_maxlevel'])) {
						unset($_POST['verwaltung_user_maxlevel']);
					}
				}
				
				// gesperrte Allianzen und Galaxien
				if($user->protectedAllies) {
					// altes übernehmen
					if(isset($data['userRechte']['override_allies'])) {
						$_POST['override_allies'] = true;
					}
					// neues löschen
					else if(isset($_POST['override_allies'])) {
						unset($_POST['override_allies']);
					}
				}
				
				if($user->protectedGalas) {
					// altes übernehmen
					if(isset($data['userRechte']['override_galas'])) {
						$_POST['override_galas'] = true;
					}
					// neues löschen
					else if(isset($_POST['override_galas'])) {
						unset($_POST['override_galas']);
					}
				}
				
				// serialisieren
				if(!count($_POST)) $r = '';
				else $r = escape(serialize($_POST));
				
				// speichern
				query("
					UPDATE ".PREFIX."user
					SET
						userRechte = '".$r."'
					WHERE
						user_playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Ausgabe
				$tmpl->content = '
				Die Berechtigungen wurden gespeichert.
				<br /><br />';
				
				// User-Zeile aktualisieren
				$tmpl->script = 'ajaxcall(\'index.php?p=player&sp=update&id='.$_GET['id'].'&ajax\', false, false, false);$(\'.userlist'.$_GET['id'].'\').css(\'opacity\', 1)';
				
				// Cache löschen
				$cache->remove('user'.$_GET['id']);
				
				
				// Log-Eintrag
				if($config['logging']) {
						insertlog(21, 'Ändert die Berechtigungen von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')');
				}
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}



?>