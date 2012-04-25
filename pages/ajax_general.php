<?php
/**
 * pages/ajax_general.php
 * Favoriten verwalten
 * System-Scan reservieren
 * Raiden
 * Toxxen
 * offene Invas aktualisieren
 * Planeten zu Routen hinzufügen (Planet, Spieler, Ally, Meta)
 * markierte Planeten zu Route hinzufügen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'fav_add'=>true,
	'fav_edit'=>true,
	'fav_del'=>true,
	'reserve'=>true,
	'raid'=>true,
	'toxx'=>true,
	'toxxraidreserv'=>true,
	'openinvas'=>true,
	'add2route'=>true,
	'add2route_send'=>true,
	'route_addmarked'=>true,
	'route_addmarked_send'=>true
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

// Favoriten hinzufügen, bearbeiten und löschen
else if($_GET['sp'] == 'fav_add' OR $_GET['sp'] == 'fav_edit' OR $_GET['sp'] == 'fav_del') {
	include './pages/ajax_general/favoriten.php';
}

/**
 * Systeme
 */

// System-Scan reservieren
else if($_GET['sp'] == 'reserve') {
	// Daten vorhanden?
	if(!isset($_GET['sys'])) {
		$tmpl->content = '<span class="error">Fehler!</span>';
	}
	// alles OK
	else {
		// Daten sichern
		$_GET['sys'] = (int)$_GET['sys'];
		
		query("
			UPDATE ".PREFIX."systeme
			SET
				systemeScanReserv = ".time().",
				systemeReservUser = '".escape($user->name)."'
			WHERE
				systemeID = ".$_GET['sys']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(10, 'reserviert den Scan für das System '.$_GET['sys']);
		}
		
		// Ausgabe
		if(isset($_GET['long'])) $tmpl->content = '<i>Scan reserviert von '.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'</i>';
		else $tmpl->content = '<i>'.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'</i>';
		
		// global reservieren
		$tmpl->script = '$(".sysreserv'.$_GET['sys'].'").html("<i>'.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'</i>");';
	}
	// Ausgabe
	$tmpl->output();
}

// raiden und toxxen, Planeten reservieren
else if($_GET['sp'] == 'raid' OR $_GET['sp'] == 'toxx' OR $_GET['sp'] == 'toxxraidreserv') {
	include './pages/ajax_general/toxxraid.php';
}

// offene Invasionen ermitteln
else if($_GET['sp'] == 'openinvas') {
	$tmpl->content = getopeninvas();
	
	// Ausgabe
	$tmpl->output();
}

// Planeten zu Routen hinzufügen
else if($_GET['sp'] == 'add2route' OR $_GET['sp'] == 'add2route_send' OR $_GET['sp'] == 'route_addmarked' OR $_GET['sp'] == 'route_addmarked_send') {
	include './pages/ajax_general/route.php';
}

?>