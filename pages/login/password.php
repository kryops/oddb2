<?php

/**
 * pages/login/password.php
 * Passwort vergessen
 * E-Mail zuschicken, neues Passwort generieren
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Passwort vergessen (AJAX)
if($_GET['sp'] == 'sendpw') {
	$tmpl = new template;
	
	// Validierung
	if(!isset($_POST['username'], $_POST['email'])) {
		$tmpl->error = 'Daten unvollst&auml;ndig!';
	}
	else if(trim($_POST['username']) == '') {
		$tmpl->error = 'Kein Username eingegeben!';
	}
	else if(stripos($_POST['email'], '@') === false OR stripos($_POST['email'], '.') === false) {
		$tmpl->error = 'Ung&uuml;ltige E-Mail-Adresse!';
	}
	else {
		// Daten sichern
		$_POST['username'] = escape($_POST['username']);
		$_POST['email'] = escape($_POST['email']);
		
		// Ban ermitteln
		$ipban = ban_get();
		
		// zu viele Fehlversuche
		if($config['ipban'] AND $ipban > $config['ipban']) {
			$tmpl->error = 'Deine IP ist aufgrund vieler Fehlversuche gesperrt.<br />Bitte versuche es sp&auml;ter wieder!';
			$tmpl->output();
			die();
		}
		
		// User-Daten abfragen
		$query = query("
			SELECT
				user_playerID,
				user_playerName,
				userEmail,
				userPassword,
				userPwSend
			FROM
				".PREFIX."user
			WHERE
				user_playerName = '".$_POST['username']."'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// User nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account wurde nicht gefunden!';
			ban_add($ipban);
		}
		// User gefunden
		else {
			$data = mysql_fetch_assoc($query);
			// vor 24 Stunden schon eine Mail schicken lassen
			if(time()-$data['userPwSend'] < 86400) {
				$tmpl->error = 'Du hast dir schon eine E-Mail schicken lassen!';
			}
			// Falsche Adresse
			else if($_POST['email'] != $data['userEmail']) {
				$tmpl->error = 'Die E-Mail-Adresse ist falsch!';
				ban_add($ipban);
			}
		}
		
		// kein Fehler -> Mail abschicken
		if(!$tmpl->error) {
			$ts = time();
			
			$msg = 'Hallo '.$data['user_playerName'].'!

Wenn du dir ein neues Passwort für die ODDB generieren lassen möchtest, klicke auf den nachfolgenden Link oder kopiere ihn in die Adressleiste deines Browsers:

'.ADDR.'index.php?p=login&sp=retpw&inst='.INSTANCE.'&uid='.$data['user_playerID'].'&pw='.$data['userPassword'].'&time='.$ts.'

Der Link ist 24 Stunden lang gültig.

mit freundlichen Grüßen
dein ODDB-Team';
			
			@mail(
				$data['userEmail'],
				'ODDB Passwortmailer',
				$msg,
				"From: passwortmailer@".SERVER."\nContent-type: text/plain; charset=utf-8\nX-Mailer: PHP/".phpversion()
			);
			
			// Timestamp in der DB aktualisieren
			query("
				UPDATE
					".PREFIX."user
				SET
					userPwSend = ".$ts."
				WHERE
					user_playerID = ".$data['user_playerID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Ausgabe
			$tmpl->content = 'Die E-Mail wurde versendet.';
		}
	}
	// Ausgabe
	$tmpl->output();
}

// neues Passwort generieren
else if($_GET['sp'] == 'retpw') {
	$tmpl = new template_login;
	
	// Validierung
	if(!isset($_GET['uid'], $_GET['pw'], $_GET['time'])) {
		$tmpl->error = 'Daten unvollst&auml;ndig!';
	}
	else if(!INSTANCE) {
		$tmpl->error = 'Keine Datenbank ausgew&auml;hlt!';
	}
	// alles ok
	else {
		// Daten sichern
		$_GET['uid'] = (int)$_GET['uid'];
		$_GET['time'] = (int)$_GET['time'];
		
		// Ban ermitteln
		$ipban = ban_get();
		
		// zu viele Fehlversuche
		if($config['ipban'] AND $ipban > $config['ipban']) {
			$tmpl->error = 'Deine IP ist aufgrund vieler Fehlversuche gesperrt.<br />Bitte versuche es sp&auml;ter wieder!';
			$tmpl->output();
			die();
		}
		
		// User-Daten abfragen
		$query = query("
			SELECT
				user_playerName,
				userPassword,
				userPwSend
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$_GET['uid']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// User existiert nicht
		if(!mysql_num_rows($query)) {
			ban_add($ipban);
			$tmpl->error = 'Der Account wurde gel&ouml;scht!';
			$tmpl->output();
			die();
		}
		
		$data = mysql_fetch_assoc($query);
		
		// Passwort schon geändert oder Zeit abgelaufen
		if($_GET['pw'] != $data['userPassword'] OR !$data['userPwSend'] OR $_GET['time'] != $data['userPwSend'] OR time()-$_GET['time'] > 86400) {
			ban_add($ipban);
			$tmpl->error = 'Der Link ist nicht mehr g&uuml;ltig!';
			$tmpl->output();
			die();
		}
		
		// kein Fehler -> neues Passwort erzeugen
		$v = array("a", "e", "i", "o", "u");
		$c = array("b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v", "w", "x", "z");
		$vc = count($v)-1;
		$cc = count($c)-1;
		
		$pw = '';
		for($i=1;$i<=3;$i++) {
			$pw .= $c[rand(0, $cc)].$v[rand(0, $vc)];
		}
		$pw .= rand(10,99);
		
		// neues Passwort speichern und Timer zurücksetzen
		query("
			UPDATE
				".PREFIX."user
			SET
				userPassword = '".md5($pw)."',
				userPwSend = 0
			WHERE
				user_playerID = ".$_GET['uid']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Ausgabe
		$tmpl->content = '
		<div style="padding:20px">
			Hallo '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').'!
			<br /><br />
			Dein neues Passwort lautet: <span style="font-weight:bold">'.$pw.'</span><br /><br />
			Merk es dir gut, denn dieser Link ist ab sofort ung&uuml;ltig! Nach dem Einloggen kannst du das Passwort unter &quot;Einstellungen&quot; wieder &auml;ndern.
			<br /><br />
			<a href="index.php?inst='.INSTANCE.'" style="font-weight:bold">zum Login</a>
		</div>';
	}
	
	// Ausgabe
	$tmpl->output();
}



?>