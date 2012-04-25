<?php

/**
 * pages/login/login.php
 * einloggen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// XML-Template
$tmpl = new template;
// Validierung
if(!isset($_POST['username'], $_POST['pw'], $_POST['autologin'])) {
	$tmpl->error = 'Daten unvollst&auml;ndig!';
}
// Daten vollständig
else {
	// IP-Ban überprüfen
	$ipban = ban_get();
	
	// IP gebannt
	if($config['ipban'] AND $ipban > $config['ipban']) {
		$tmpl->error = 'Deine IP ist aufgrund vieler Fehlversuche gesperrt.<br />Bitte versuche es sp&auml;ter wieder!';
	}
	// IP nicht gebannt
	else {
		// eingegebene Daten überprüfen und User-ID abfragen
		$query = query("
			SELECT
				user_playerID
			FROM
				".PREFIX."user
			WHERE
				user_playerName = '".escape($_POST['username'])."'
				AND userPassword = '".md5($_POST['pw'])."'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Daten falsch
		if(!mysql_num_rows($query)) {
			// Fehlermeldung
			$tmpl->error = 'Die eingegebenen Daten sind ung&uuml;ltig!';
			
			// IP-Ban eintragen / erhöhen
			ban_add($ipban);
		}
		// Daten richtig
		else {
			$data = mysql_fetch_assoc($query);
			
			// OD-Request absetzen
			odrequest($data['user_playerID'], true);
			user_checkban($data['user_playerID']);
		
			// Bans der IP löschen
			ban_del();
			
			// UID und IP in die Session laden
			$_SESSION['oddbuid'] = $data['user_playerID'];
			$_SESSION['inst'] = INSTANCE;
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			
			// Cookie setzen bei Autologin (1 Jahr)
			if($_POST['autologin']) {
				setcookie('oddb', $data['user_playerID'].'+'.md5($_POST['pw']).'+'.INSTANCE, time()+31536000);
			}
			
			// Logeintrag
			if($config['logging'] >= 2) {
				$user->id = $data['user_playerID'];
				insertlog(2, 'loggt sich ein');
			}
			
			// weiterleiten
			$tmpl->script = 'url("index.php")';
		}
	}
}
$tmpl->output();



?>