<?php

/**
 * admin/index.php - Hauptdatei des Administrationsbereichs
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
define('INSTANCE', 0);

// Session starten
@session_start();

// Zeitzone setzen -> Performance
date_default_timezone_set('Europe/Berlin');

// HTTP-Cache-Header ausgeben (Caching der Seite verhindern)
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);

// magic quotes abfangen
if(get_magic_quotes_gpc()) {
	function strsl(&$item, $key) {
		$item = stripslashes($item);
	}
	array_walk_recursive($_GET, 'strsl');
	array_walk_recursive($_POST, 'strsl');
}

// Übersichtsseite als Standard definieren
if(!isset($_GET['p'])) $_GET['p'] = 'oview';

// Logout
if($_GET['p'] == 'logout') {
	@session_destroy();
	$_SESSION = array();
}

// Umgebungsdaten und globale Funktionen einbinden
include '../common.php';
include './common.php';

// Einstellungen einbinden
include '../globalconfig.php';
include './config.php';

// noch nicht installiert
if(!INSTALLED) {
	die('Die ODDB wurde noch nicht installiert!');
}

// Instanz-Array umformen
if(!$dbs) $dbs = array(1=>'');

// Cache laden
$cache = new cache();
if(!$config['caching']) {
	$config['ipban'] = 0;
}

// Login überprüfen
$user = new user;

// aktive Session vorhanden?
if(isset($_SESSION['oddbadmin'], $_SESSION['ip'])) {
	// Änderung der IP überprüfen
	if($_SERVER['REMOTE_ADDR'] != $_SESSION['ip']) {
		$user->loginerror = 'Deine IP hat sich ge&auml;ndert!';
	}
	// ansonsten eingeloggt
	else {
		$user->login = true;
	}
}


// welche Seite will der User aufrufen?

// nicht eingeloggt
if(!$user->login) {
	include './pages/login.php';
}
// eingeloggt
else {
	// Seite nicht vorhanden -> 404
	if(!isset($pages[$_GET['p']])) {
		$tmpl = new template_admin;
		$tmpl->error = 'Die Seite existiert nicht!';
		$tmpl->output();
	}
	// Seite vorhanden
	else {
		include './pages/'.$_GET['p'].'.php';
	}
}

?>