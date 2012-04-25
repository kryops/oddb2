<?php

/**
 * pages/login.php
 * Login
 * Registrierung
 * Passwort vergessen
 * User gesperrt oder noch nicht freigeschaltet
 * Instanz auswählen
 * DB deaktiviert
 * Flooding-Anzeige
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Login-Template
class template_login {
	// Seitentitel
	public $title = 'ODDB V2 by Kryops';
	// Inhalt
	public $error = '';
	public $content = '';
	public $script = '';
	
	/**
	 * Template ausgeben
	 */
	function output() {
		global $time_start, $queries;
		
		// Fehlerbehandlung
		if($this->error) {
			$this->content = '<div class="error" style="padding:20px">'.$this->error.'</div>';
		}
		
		// HTML-Header
		header('Content-Type: text/html; charset=utf-8');
		
		echo '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" type="text/css" href="css/main.css'.FILESTAMP.'" />
<link rel="shortcut icon" href="favicon.ico" />
<title>'.$this->title.'</title>
</head>
<body>

<div style="text-align:center;margin-top:25px">
	<img src="img/layout/login.jpg" alt="ODDB V2" width="478" height="115" style="margin-left:-90px" />
	<div id="loginbox">
		'.$this->content.'
	</div>';
		if($_GET['p'] != 'impressum') {
			echo '
		<span id="footerlinks">
			<a href="index.php?p=impressum">Impressum</a> &nbsp;&bull;&nbsp; 
			<a href="http://www.kryops.de/" target="_blank">Projekt-Homepage</a>
		</span>';
		}
		echo '
</div>

<div id="tooltip"></div>

<div id="ajax">
	<img src="img/layout/ajax.gif" width="24" height="24" alt="laden" />
</div>

<script type="text/javascript" src="js/jquery.js'.FILESTAMP.'"></script>
<script type="text/javascript" src="js/general.js'.FILESTAMP.'"></script>
<script type="text/javascript" src="js/login.js'.FILESTAMP.'"></script>
<script type="text/javascript">
instance = '.INSTANCE.';
'.$this->script.'
</script>
<!-- Rendertime '.number_format(microtime(true)-$time_start, 6).'s, '.$queries.' Queries -->

</body>
</html>';
		
	}
}

//
// Struktur
//

// öffentlich zugängliche Seiten
// Impressum
if($_GET['p'] == 'impressum') {
	include './pages/impressum.php';
}



// Instanz auswählen
if(!INSTANCE) {
	include './pages/login/instance.php';
}

// Ist die DB deaktiviert?
if(!$config['active']) {
	if(isset($_GET['ajax'])) {
		$tmpl = new template;
		$tmpl->error = 'Die Datenbank ist momentan deaktiviert!';
		// Grund für die Deaktivierung
		if($config['offlinemsg'] != '') {
			$tmpl->error .= '
			<br /><br />
			Grund: '.nl2br(htmlspecialchars($config['offlinemsg'], ENT_COMPAT, 'utf-8'));
		}
	}
	// FoW abfangen
	else if(isset($_GET['p']) AND $_GET['p'] == 'fow') {
		diefow('Die Datenbank ist momentan deaktiviert! Grund: '.htmlspecialchars($config['offlinemsg'], ENT_COMPAT, 'utf-8'));
	}
	else {
		$tmpl = new template_login;
		$tmpl->content = '
		<br /><br />
		<div class="center">
			<span style="font-weight:bold">Die Datenbank ist momentan deaktiviert!</span>';
		// Grund für die Deaktivierung
		if($config['offlinemsg'] != '') {
			$tmpl->content .= '
			<br /><br />
			Grund: '.nl2br(htmlspecialchars($config['offlinemsg'], ENT_COMPAT, 'utf-8'));
		}
		$tmpl->content .= '
		</div>
		<br /><br />
		';
	}
	$tmpl->output();
	die();
}

// Flooding-Schutz
if($flooding) {
	// FoW abfangen
	if(isset($_GET['p']) AND $_GET['p'] == 'fow') {
		diefow('Flooding-Schutz! Du kannst innerhalb von '.$config['flooding_time'].' Sekunden maximal '.$config['flooding_pages'].' Seiten aufrufen!');
	}
	
	$tmpl = new template;
	$tmpl->error = 'Flooding-Schutz! Du kannst innerhalb von '.$config['flooding_time'].' Sekunden maximal '.$config['flooding_pages'].' Seiten aufrufen!';
	$tmpl->output();
	die();
}

// einloggen (AJAX)
if($_GET['sp'] == 'login') {
	include './pages/login/login.php';
}
// registrieren (AJAX)
else if($_GET['sp'] == 'register') {
	include './pages/login/register.php';
}

// Passwort vergessen (AJAX)
else if($_GET['sp'] == 'sendpw') {
	include './pages/login/password.php';
}

// neues Passwort generieren
else if($_GET['sp'] == 'retpw') {
	include './pages/login/password.php';
}

// andere AJAX-Abfrage -> nicht mehr eingeloggt oder gebannt
else if(isset($_GET['ajax'])) {
	$tmpl = new template;
	
	// gebannt
	if($user->login) $tmpl->error = 'Du wurdest gesperrt!
<br /><br />
<a href="index.php">zur Meldung</a>';
	
	// nicht mehr eingeloggt
	else $tmpl->error = '
Du bist nicht mehr eingeloggt!
<br /><br />
<a href="index.php">neu einloggen</a>
';
	$tmpl->output();
}

// gesperrt, Ally gewechselt oder noch nicht freigeschaltet
else if($user->login) {
	include './pages/login/banned.php';
}

// normaler Loginscreen
else {
	include './pages/login/loginscreen.php';
}

?>