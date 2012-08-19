<?php

/**
 * install/index.php - Installation
 */

// alle Fehlermeldungen aktivieren
error_reporting(E_ALL);

// Rendertime-Messung starten
$time = microtime(true);

// User-Abort deaktivieren, um eventuelle Fehler zu verhinden
ignore_user_abort(true);

// Query-Zähler initialisieren
$queries = 0;

// Sicherheitskonstanten
define('ODDB', true);
define('ODDBADMIN', true);
define('INSTANCE', 1);

// Session starten
@session_start();

// Zeitzone setzen -> Performance
date_default_timezone_set('Europe/Berlin');

// HTTP-Cache-Header ausgeben (Caching der Seite verhindern)
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
// Content-Type setzen
header('Content-Type: text/html; charset=utf-8');

// magic quotes abfangen
if(get_magic_quotes_gpc()) {
	function strsl(&$item, $key) {
		$item = stripslashes($item);
	}
	array_walk_recursive($_GET, 'strsl');
	array_walk_recursive($_POST, 'strsl');
}

// gemeinsame Dateien einbinden
include '../common.php';
include './common.php';
include '../common/general.php';

// bestehende Installation prüfen
if(file_exists('../config/global.php')) {
	die('Die ODDB wurde schon installiert!');
}


$tmpl = new template_install;

// Installation starten
if(isset($_POST['admin'])) {
	include './install.php';
}
// Überprüfen, ob Installation möglich ist
else {
	include './check.php';
}
