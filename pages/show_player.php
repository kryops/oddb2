<?php
/**
 * pages/show_player.php
 * Spieler anzeigen
 * Allianz-History des Spielers anzeigen
 * DB-Daten des Spielers anzeigen
 * komplette Berechtigungsliste des Spielers anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'history'=>true,
	'dbdata'=>true,
	'rechte'=>true
);

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;


// keine Berechtigung, irgendwelche Spieler anzuzeigen
if(!$user->rechte['show_player']) {
	$tmpl->error = 'Du hast keine Berechtigung, Spieler anzuzeigen!';
	$tmpl->output();
	die();
}

// keine ID übergeben
if(!isset($_GET['id'])) {
	$tmpl->error = 'Keine ID übergeben!';
	$tmpl->output();
	die();
}


// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
}


/**
 * Seiten
 */

// Spieler anzeigen
else if($_GET['sp'] == '') {
	include './pages/show_player/show.php';
}

// Allianz-History anzeigen
else if($_GET['sp'] == 'history') {
	include './pages/show_player/history.php';
}

// Datenbank-Daten anzeigen
else if($_GET['sp'] == 'dbdata') {
	include './pages/show_player/dbdata.php';
}

// komplette Berechtigungsliste anzeigen
else if($_GET['sp'] == 'rechte') {
	include './pages/show_player/rechte.php';
}

// Ausgabe
$tmpl->output();

?>