<?php

/**
 * install/install.php
 * Eingegebene Daten prüfen, Installation abschließen
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

$errors = array();

// Daten unvollständig
if(!isset($_POST['addr'], $_POST['server'], $_POST['key'], $_POST['mysql_host'], $_POST['mysql_user'], $_POST['mysql_pw'], $_POST['mysql_db'], $_POST['mysql_globprefix'], $_POST['caching'], $_POST['caching_prefix'], $_POST['memcached_host'], $_POST['memcached_port'], $_POST['ipban'], $_POST['ipban_time'], $_POST['flooding'], $_POST['flooding_time'], $_POST['flooding_pages'], $_POST['mysql_prefix'], $_POST['admin'])) {
	$errors[] = 'Daten unvollst&auml;ndig!';
}
else {
	// MySQL-Fehler
	if(!@mysql_connect($_POST['mysql_host'], $_POST['mysql_user'], $_POST['mysql_pw'])) {
		$errors[] = 'Verbindung zum MySQL-Server konnte nicht hergestellt werden: '.mysql_error();
	}
	else if(!@mysql_select_db($_POST['mysql_db'])) {
		$errors[] = 'MySQL-Datenbank kann nicht ausgew&auml;hlt werden: '.mysql_error();
	}

	// Präfix ungültig
	if(preg_replace('/[a-zA-Z0-9\-_]/', '', $_POST['mysql_globprefix']) != '') {
		$errors[] = 'Ung&uuml;ltiger globaler Tabellenpr&auml;fix!';
	}
	if(preg_replace('/[a-zA-Z0-9\-_]/', '', $_POST['mysql_prefix']) != '') {
		$errors[] = 'Ung&uuml;ltiger Tabellenpr&auml;fix f&uuml;r die 1. Instanz!';
	}

	// Kein Key
	if($_POST['key'] == '') {
		$errors[] = 'Kein Sicherheitsschl&uuml;ssel eingegeben!';
	}
	else if(preg_replace('/[a-zA-Z0-9\-_]/', '', $_POST['key']) != '') {
		$errors[] = 'Der Sicherheitsschl&uuml;ssel darf nur Buchstaben und Zahlen beinhalten!';
	}

	// User-ID
	if((int)$_POST['admin'] < 10) {
		$errors[] = 'Ung&uuml;ltuge Administrator-UserID eingegeben!';
	}
	// Administrator prüfen
	else {
		$admin = (int)$_POST['admin'];
		$file = 'http://www.omega-day.com/game/states/live_state.php?userid='.$admin.'&world='.ODWORLD;
		$connection = @fopen($file,'r');
		if(!$connection) {
			$errors[] = 'Konnte keine Verbindung zum OD-Server aufbauen!';
		}
		else {
			$buffer = fgets($connection, 4096);
			fclose($connection);
			
			// als Array parsen
			parse_str($buffer, $oddata);
			
			// User nicht vorhanden oder gelöscht
			if(!isset($oddata['name']) OR $oddata['name'] == '') {
				$errors[] = 'Der Account mit der User-ID '.$admin.' ist nicht vorhanden oder hat sich gel&ouml;scht, oder die ODDB kann sich nicht mit Omega-Day verbinden!';
			}
		}
	}

	// Konfigurationsdatei auslesen
	if(($gc = file_get_contents('../globalconfig.php')) === false) {
		$errors[] = 'Konnte die Datei globalconfig.php nicht lesen!';
	}
}


// Fehler aufgetreten
if(count($errors)) {
	$tmpl->name = 'Fehler aufgetreten';
	
	$tmpl->content = '<div class="error center">'.implode('<br /><br />', $errors).'</div>

<br /><br />
'.$tmpl->form();
}

// Installation abschließen
else {
	// Konfigurationsdateien schreiben
	
	// globalconfig.php
	$gc = str_replace(
		"define('INSTALLED', false);",
		"define('INSTALLED', true);",
		$gc
	);
	$gc = str_replace(
		"define('ADDR', '".str_replace('\\"', '"', addslashes(ADDR))."');",
		"define('ADDR', '".str_replace('\\"', '"', addslashes($_POST['addr']))."');",
		$gc
	);
	$gc = str_replace(
		"define('SERVER', '".str_replace('\\"', '"', addslashes(SERVER))."');",
		"define('SERVER', '".str_replace('\\"', '"', addslashes($_POST['server']))."');",
		$gc
	);
	$gc = str_replace(
		"define('KEY', '".str_replace('\\"', '"', addslashes(KEY))."');",
		"define('KEY', '".str_replace('\\"', '"', addslashes($_POST['key']))."');",
		$gc
	);
	
	$impressum = nl2br($_POST['impressum']);
	$impressum = str_replace(array("\r\n", "\n"), "", $impressum);
	$gc = str_replace(
		"define('IMPRESSUM', '".str_replace('\\"', '"', addslashes(IMPRESSUM))."');",
		"define('IMPRESSUM', '".str_replace('\\"', '"', addslashes($impressum))."');",
		$gc
	);
	
	
	$gc = str_replace(
		"'mysql_host' => '".str_replace('\\"', '"', addslashes($bconfig['mysql_host']))."',",
		"'mysql_host' => '".str_replace('\\"', '"', addslashes($_POST['mysql_host']))."',",
		$gc
	);
	$gc = str_replace(
		"'mysql_user' => '".str_replace('\\"', '"', addslashes($bconfig['mysql_user']))."',",
		"'mysql_user' => '".str_replace('\\"', '"', addslashes($_POST['mysql_user']))."',",
		$gc
	);
	$gc = str_replace(
		"'mysql_pw' => '".str_replace('\\"', '"', addslashes($bconfig['mysql_pw']))."',",
		"'mysql_pw' => '".str_replace('\\"', '"', addslashes($_POST['mysql_pw']))."',",
		$gc
	);
	$gc = str_replace(
		"'mysql_db' => '".str_replace('\\"', '"', addslashes($bconfig['mysql_db']))."',",
		"'mysql_db' => '".str_replace('\\"', '"', addslashes($_POST['mysql_db']))."',",
		$gc
	);
	$gc = str_replace(
		"'mysql_globprefix' => '".str_replace('\\"', '"', addslashes($bconfig['mysql_globprefix']))."',",
		"'mysql_globprefix' => '".str_replace('\\"', '"', addslashes($_POST['mysql_globprefix']))."',",
		$gc
	);
	
	$_POST['caching'] = (int)$_POST['caching'];
	if($_POST['caching'] < 0 OR $_POST['caching'] > 2) {
		$_POST['caching'] = 0;
	}
	$_POST['memcached_port'] = (int)$_POST['memcached_port'];
	
	$gc = str_replace(
		"'caching' => ".(int)$bconfig['caching'].",",
		"'caching' => ".$_POST['caching'].",",
		$gc
	);
	$gc = str_replace(
		"'caching_prefix' => '".str_replace('\\"', '"', addslashes($bconfig['caching_prefix']))."',",
		"'caching_prefix' => '".str_replace('\\"', '"', addslashes($_POST['caching_prefix']))."',",
		$gc
	);
	$gc = str_replace(
		"'memcached_host' => '".str_replace('\\"', '"', addslashes($bconfig['memcached_host']))."',",
		"'memcached_host' => '".str_replace('\\"', '"', addslashes($_POST['memcached_host']))."',",
		$gc
	);
	$gc = str_replace(
		"'memcached_port' => ".(int)$bconfig['memcached_port'].",",
		"'memcached_port' => ".$_POST['memcached_port'].",",
		$gc
	);
	
	$_POST['ipban'] = (int)$_POST['ipban'];
	if($_POST['ipban'] < 0) {
		$_POST['ipban'] = 0;
	}
	$_POST['ipban_time'] = (int)$_POST['ipban_time'];
	
	$gc = str_replace(
		"'ipban' => ".(int)$bconfig['ipban'].",",
		"'ipban' => ".$_POST['ipban'].",",
		$gc
	);
	$gc = str_replace(
		"'ipban_time' => ".(int)$bconfig['ipban_time'].",",
		"'ipban_time' => ".$_POST['ipban_time'].",",
		$gc
	);
	
	$_POST['flooding_time'] = (int)$_POST['flooding_time'];
	if($_POST['flooding_time'] < 1) {
		$_POST['flooding_time'] = 10;
	}
	$_POST['flooding_pages'] = (int)$_POST['flooding_pages'];
	if($_POST['flooding_pages'] < 1) {
		$_POST['flooding_pages'] = 30;
	}
	
	$gc = str_replace(
		"'flooding' => ".($bconfig['flooding'] ? "true" : "false").",",
		"'flooding' => ".($_POST['flooding'] ? "true" : "false").",",
		$gc
	);
	$gc = str_replace(
		"'flooding_time' => ".(int)$bconfig['flooding_time'].",",
		"'flooding_time' => ".$_POST['flooding_time'].",",
		$gc
	);
	$gc = str_replace(
		"'flooding_pages' => ".(int)$bconfig['flooding_pages'].",",
		"'flooding_pages' => ".$_POST['flooding_pages'].",",
		$gc
	);
	
	
	$fp = @fopen('../globalconfig.php', 'w');
	if($fp) {
		fwrite($fp, $gc);
		fclose($fp);
	}
	
	
	// Instanz-Konfiguration
	if(!class_exists('config')) {
		include '../common/config.php';
	}
	
	$c = array(
		'active'=>true,
		'mysql_prefix'=>$_POST['mysql_prefix'],
		'key'=>generate_key()
	);
	
	config::save(1, $c, false);
	
	foreach($_POST as $key=>$val) {
		if($key != 'key') {
			$config[$key] = $val;
		}
	}
	
	$globprefix = $_POST['mysql_globprefix'];
	$prefix = $_POST['mysql_prefix'];
	
	
	// Tabellen anlegen
	$mysql_conn = new mysql;
	$mysql_conn->connected = true;
	
	include '../common/mysql_tables.php';
	
	// MySQL auf UTF-8 stellen
	if(function_exists('mysql_set_charset')) {
		mysql_set_charset('utf8');
	}
	else {
		mysql_query("
			SET NAMES 'UTF8'
		");
	}
	
	// globale Tabellen anlegen
	foreach($globtables_add as $sql) {
		$query = query($sql);
		if(!$query) {
			$tmpl->error = 'Anlegen der MySQL-Tabellen fehlgeschlagen: '.mysql_error().'<br /><br />(Query '.htmlspecialchars($sql, ENT_COMPAT, 'UTF-8').')';
			$tmpl->output();
			die();
		}
	}
	
	// Tabellen für 1. Instanz anlegen
	foreach($tables_add as $sql) {
		$query = query($sql);
		if(!$query) {
			$tmpl->error = 'Anlegen der MySQL-Tabellen fehlgeschlagen: '.mysql_error().'<br /><br />(Query '.htmlspecialchars($sql, ENT_COMPAT, 'UTF-8').')';
			$tmpl->output();
			die();
		}
	}
	
	
	// Administrator anlegen
	$cache = new cache();
	odrequest($admin, true);
	
	// Passwort erzeugen
	$v = array("a", "e", "i", "o", "u");
	$c = array("b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v", "w", "x", "z");
	$vc = count($v)-1;
	$cc = count($c)-1;
	
	$pw = '';
	for($i=1;$i<=3;$i++) {
		$pw .= $c[rand(0, $cc)].$v[rand(0, $vc)];
	}
	$pw .= rand(10,99);
	
	// Registrierungserlaubnis
	$query = query("
		INSERT INTO
			".$prefix."register
		SET
			register_playerID = ".$admin."
	");
	if(!$query) {
		$tmpl->error = 'Fehler beim Anlegen des Administrator-Accounts! '.mysql_error().'<br />';
	}
	
	$query = query("
		SELECT
			playerName,
			player_allianzenID
		FROM
			".$globprefix."player
		WHERE
			playerID = ".$admin."
	");
	if(!$query OR !mysql_num_rows($query)) {
		$tmpl->error = 'Fehler beim Anlegen des Administrator-Accounts!';
	}
	else {
		$data = mysql_fetch_assoc($query);
		
		$settings = $bsettings;
		$settings['scout'] = $config['scan_veraltet'];
		$settings['fow'] = serialize($bfowsettings);
		$settings = serialize($settings);
		
		$query = query("
			INSERT INTO
				".$prefix."user
			SET
				user_playerID = ".$admin.",
				user_playerName = '".escape($data['playerName'])."',
				user_allianzenID = ".$data['player_allianzenID'].",
				userRechtelevel = 4,
				userPassword = '".md5($pw)."',
				userSettings = '".escape($settings)."'
		");
		if(!$query) {
			$tmpl->error = 'Fehler beim Anlegen des Administrator-Accounts! '.mysql_error().'<br />';
		}
		else {
			// Administrator-Ally auf Status Meta setzen
			if($data['player_allianzenID']) {
				$query = query("
					INSERT INTO
						".$prefix."allianzen_status
					SET
						status_allianzenID = ".$data['player_allianzenID'].",
						statusDBAllianz = ".$data['player_allianzenID'].",
						statusStatus = ".$status_meta."
				");
			}
		}
	}
	
	// Fehler-Ausgabe
	if($tmpl->error != '') {
		$tmpl->name = 'Es ist ein Fehler aufgetreten!';
		$tmpl->output();
		die();
	}
	
	// Erfolgreich-Ausgabe
	$tmpl->name = 'Installation abgeschlossen';
	
	$tmpl->content = '
Die ODDB wurde erfolgreich installiert.
<br /><br />
Der Account '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' wurde mit dem Passwort <b>'.$pw.'</b> angelegt.
<br /><br />
Den Administrationsbereich findest du unter <a href="'.h($_POST['addr']).'admin/" target="_blank"><b>'.h($_POST['addr']).'admin/</b></a>. Das Standardpasswort lautet <b>oddb</b>, du kannst es unter admin/config.php &auml;ndern.
<br /><br />
Zum korrekten Betrieb der ODDB musst du noch 2 Cronjobs einrichten:
<br />
<ul>
<li><b>'.h($_POST['addr']).'cronjobs/1.php?key='.$_POST['key'].'</b> sollte jede Minute aufgerufen werden</li>
<li><b>'.h($_POST['addr']).'cronjobs/2.php?key='.$_POST['key'].'</b> sollte einmal am Tag aufgerufen werden</li>
</ul>
<br /><br />
Weitere Informationen findest du im Readme.
<br>
Fehler, Fragen und Anregungen bitte an michael@kryops.de
<br /><br /><br />
Und jetzt viel Spa&szlig; mit der ODDB!
<br /><br />
<a href="../" target="_blank"><b>&raquo; zum Login</b></a>';

}

$tmpl->output();



?>