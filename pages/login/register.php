<?php

/**
 * pages/login/register.php
 * anmelden
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// XML-Template
$tmpl = new template;

// Downtime -> keine Registrierung mehr
if(DOWNTIME AND date('G') == 4) {
	$tmpl->error = 'W&auml;hrend der Downtime von OD ist keine Registrierung m&ouml;glich!';
}
// Validierung
else if(!isset($_POST['id'], $_POST['email'], $_POST['pw'], $_POST['pw2'])) {
	$tmpl->error = 'Daten unvollst&auml;ndig!';
}
else if(stripos($_POST['email'], '@') === false OR stripos($_POST['email'], '.') === false) {
	$tmpl->error = 'Ung&uuml;ltige E-Mail-Adresse!';
}
else if(trim($_POST['pw']) == '') {
	$tmpl->error = 'Kein Passwort eingegeben!';
}
// Daten valide
else {
	// Daten sichern
	$_POST['id'] = (int)$_POST['id'];
	$_POST['email'] = escape($_POST['email']);
	
	// Ban ermitteln
	$ipban = ban_get();
	
	// zu viele Fehlversuche
	if($config['ipban'] AND $ipban > $config['ipban']) {
		$tmpl->error = 'Deine IP ist aufgrund vieler Fehlversuche gesperrt.<br />Bitte versuche es sp&auml;ter wieder!';
		$tmpl->output();
		die();
	}
	
	// ist der User schon angemeldet?
	$query = query("
		SELECT
			COUNT(user_playerID)
		FROM
			".PREFIX."user
		WHERE
			user_playerID = '".$_POST['id']."'
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$exist = mysql_fetch_array($query);
	$exist = $exist[0];
	
	// schon angemeldet
	if($exist) {
		$tmpl->error = 'Der Account mit dieser OD-UserID ist schon angemeldet!';
		// Ban-Eintrag
		ban_add($ipban);
	}
	else {
		// OD-Request absetzen
		odrequest($_POST['id'], true);
		
		// Existenz und Erlaubnis überprüfen
		$query = query("
			SELECT
				playerName,
				r1.register_playerID,
				player_allianzenID,
				r2.register_allianzenID
			FROM
				".GLOBPREFIX."player
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON player_allianzenID = allianzenID
				LEFT JOIN ".PREFIX."register r1
					ON r1.register_playerID = playerID
				LEFT JOIN ".PREFIX."register r2
					ON r2.register_allianzenID = allianzenID
			WHERE
				playerID = '".$_POST['id']."'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// User existiert nicht
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Ung&uuml;ltige OD-UserID eingegeben!';
			// Ban-Eintrag
			ban_add($ipban);
		}
		// User existiert
		else {
			$data = mysql_fetch_assoc($query);
			
			// keine Erlaubnis
			if($data['register_playerID'] === NULL AND $data['register_allianzenID'] === NULL) {
				$tmpl->error = 'Du hast keine Erlaubnis, dich zu registrieren!';
				// Ban-Eintrag
				ban_add($ipban);
			}
			// Erlaubnis -> fertigstellen
			else {
				// Passwort verschlüsseln
				$pw = md5($_POST['pw']);
				
				$settings = $bsettings;
				$settings['scout'] = $config['scan_veraltet'];
				$settings['fow'] = serialize($bfowsettings);
				$settings = serialize($settings);
				
				// automatisch freischalten?
				$banned = 3;
				if($config['disable_freischaltung']) {
					$banned = 0;
					$rechtelevel = $config['disable_freischaltung_level'];
				}
				else {
					$rechtelevel = 0;
				}
				
				// User eintragen
				query("
					INSERT INTO ".PREFIX."user
					SET
						user_playerID = '".$_POST['id']."',
						user_playerName = '".escape($data['playerName'])."',
						userPassword = '".$pw."',
						user_allianzenID = '".$data['player_allianzenID']."',
						userSettings = '".escape($settings)."',
						userEmail = '".$_POST['email']."',
						userBanned = ".$banned.",
						userRechtelevel = ".$rechtelevel."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// UID in die Session laden
				$_SESSION['oddbuid'] = $_POST['id'];
				$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['inst'] = INSTANCE;
				
				// Allianz evtl auf Status Meta setzen
				if($data['player_allianzenID']) {
					$query = query("
						SELECT
							statusStatus
						FROM
							".PREFIX."allianzen_status
						WHERE
							statusDBAllianz = ".$data['player_allianzenID']."
							AND status_allianzenID = ".$data['player_allianzenID']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					if(!mysql_num_rows($query)) {
						$query = query("
						INSERT INTO
							".PREFIX."allianzen_status
						SET
							statusDBAllianz = ".$data['player_allianzenID'].",
							status_allianzenID = ".$data['player_allianzenID'].",
							statusStatus = ".$status_meta."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
				}
				
				// Logeintrag
				if($config['logging'] >= 2) {
					$user->id = $_POST['id'];
					insertlog(1, 'registriert sich');
				}
				
				// eventuelle Bans löschen
				ban_del();
				
				// weiterleiten
				$tmpl->script = 'url("index.php")';
			}
		}
	}
}
$tmpl->output();



?>