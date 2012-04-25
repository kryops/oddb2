<?php

/**
 * admin/pages/login.php
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Login-Template
class template_login_admin {
	// Seitentitel
	public $title = 'ODDB Administrationsbereich';
	// Inhalt
	public $error = '';
	public $content = '';
	public $script = '';
	
	/**
	 * Template ausgeben
	 */
	function output() {
		global $time, $queries;
		
		// AJAX -> nicht mehr eingeloggt
		if(isset($_GET['ajax'])) {
			// bei Fehlern Inhalt und Scripts zurücksetzen
			if($this->error) {
				$this->content = '';
				$this->script = '';
			}
			
			// Content-Type-Header XML
			header('Content-Type:text/xml; charset=utf-8');
			
			// Rendertime berechnen
			$time = number_format(microtime(true)-$time, 6);
			
			// Template ausgeben
			echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
		<page>
			<error><![CDATA[Du bist nicht mehr eingeloggt!]]></error>
			<time><![CDATA['.$time.']]></time>
			<queries><![CDATA['.$queries.']]></queries>
			<icon><![CDATA[]]></icon>
			<name><![CDATA[]]></name>
			<content><![CDATA[]]></content>
			<script><![CDATA[]]></script>
		</page>';
			die();
		}
		
		
		// Fehlerbehandlung
		if($this->error) {
			$this->content = '<div class="error" style="padding:20px">'.$this->error.'</div>';
		}
		
		// HTML-Header
		header('Content-Type: text/html; charset=utf-8');
		
		echo '<?xml version="1.0" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-language" content="de" />
	<link rel="stylesheet" type="text/css" href="../css/main.css'.FILESTAMP.'" />
	<link rel="shortcut icon" href="favicon.ico" />
	<title>'.$this->title.'</title>
</head>
<body>

<div style="text-align:center;margin-top:25px">
	<img src="../img/layout/login.jpg" alt="ODDB V2" width="478" height="115" style="margin-left:-90px" />
	<div id="loginbox">
		'.$this->content.'
	</div>
</div>

<div id="tooltip"></div>

<div id="ajax">
	<img src="img/layout/ajax.gif" width="24" height="24" alt="laden" />
</div>

<script type="text/javascript" src="../js/jquery.js'.FILESTAMP.'"></script>
<script type="text/javascript" src="../js/general.js'.FILESTAMP.'"></script>
<script type="text/javascript">
if(!("autofocus" in document.createElement("input"))) {
	document.loginform.pw.focus();
}
</script>

<!-- Rendertime '.number_format(microtime(true)-$time, 6).'s, '.$queries.' Queries -->

</body>
</html>';
		
	}
}

//
// Struktur
//


// einloggen (AJAX)
if($_GET['sp'] == 'login') {
	// XML-Template
	$tmpl = new template_admin;
	// Validierung
	if(!isset($_POST['pw'])) {
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
			// Daten falsch
			if(md5($_POST['pw']) != $config['adminpw']) {
				// Fehlermeldung
				$tmpl->error = 'Die eingegebenen Daten sind ung&uuml;ltig!';
				
				// IP-Ban eintragen / erhöhen
				ban_add($ipban);
			}
			// Daten richtig
			else {
				// Bans der IP löschen
				ban_del();
				
				// UID und IP in die Session laden
				$_SESSION['oddbadmin'] = true;
				$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
				
				// weiterleiten
				$tmpl->script = 'url("index.php")';
			}
		}
	}
	$tmpl->output();
}


// Loginscreen
else {
	// Loginerror formatieren
	if($user->loginerror) $user->loginerror .= '<br /><br />';
	
	// Login-Template
	$tmpl = new template_login_admin;
	$tmpl->content = '
	<div class="hl1">Administrations-Login</div>
		
		<div class="icontent" id="contentlogin">
			<noscript>
				<br />
				<span class="error">Die Datenbank funktioniert nur mit aktiviertem JavaScript!</span>
				<br />
			</noscript>
			<br />
			<span class="error" id="loginerror">'.$user->loginerror.'</span>
			<br />
			<form action="#" name="loginform" onsubmit="return form_send(this, \'index.php?p=login&amp;sp=login&amp;ajax\', $(\'#loginerror\'))">
			Passwort <input type="password" class="text" name="pw" autofocus />
			<br /><br />
			<input type="submit" class="button" style="width:100px" value="Login" />
			</form>
			<br /><br />
		</div>';
	$tmpl->output();
}

?>