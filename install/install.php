<?php

/**
 * install/install.php
 * Eingegebene Daten prüfen, Installation abschließen
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

$errors = array();

// Daten unvollständig
if(!isset($_POST['addr'], $_POST['server'], $_POST['key'], $_POST['mysql_host'], $_POST['mysql_user'], $_POST['mysql_pw'], $_POST['mysql_db'], $_POST['mysql_globprefix'], $_POST['impressum'], $_POST['adcode'], $_POST['caching'], $_POST['caching_prefix'], $_POST['memcached_host'], $_POST['memcached_port'], $_POST['ipban'], $_POST['ipban_time'], $_POST['flooding'], $_POST['flooding_time'], $_POST['flooding_pages'], $_POST['db_name'], $_POST['admin'])) {
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

	// Kein Key
	if($_POST['key'] == '') {
		$errors[] = 'Kein Sicherheitsschl&uuml;ssel eingegeben!';
	}
	else if(preg_replace('/[a-zA-Z0-9\-_]/', '', $_POST['key']) != '') {
		$errors[] = 'Der Sicherheitsschl&uuml;ssel darf nur Buchstaben und Zahlen beinhalten!';
	}
	
	// Kein Name
	if(trim($_POST['db_name']) == '') {
		$errors[] = 'Kein Instanz-Name eingegeben!';
	}
	
	// Instanz-Admin-Passwort
	if($_POST['admin_passwort'] != $_POST['admin_passwort2']) {
		$errors[] = 'Die Passwörter sind unterschiedlich!';
	}
	else if(trim($_POST['admin_passwort']) == '') {
		$errors[] = 'Kein Instanz-Admin-Passwort eingegeben!';
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
	/*
	 * Konfigurationsdateien schreiben
	 */
	General::loadClass('config');
	
	// Passwort erzeugen
	$pw = General::encryptPassword($_POST['admin_passwort'], $_POST['key']);
	
	// globale Konfiguration
	$c = $_POST;
	$c['passwort'] = $pw;
	unset($c['db_name']);
	unset($c['admin']);
	unset($c['admin_passwort']);
	unset($c['admin_passwort2']);
	
	config::saveGlobal('global', 'config', $c);
	
	
	// Instanz-Verzeichnis
	$dbs = array(
		1=>$_POST['db_name']
	);
	
	config::saveGlobal('dbs', 'dbs', $dbs);
	
	
	// Instanzkonfiguration
	$c = array(
		'instancekey'=>generate_key()
	);
	
	config::save(1, $c, false);
	
	$config['instancekey'] = $c['instancekey'];
	
	foreach($_POST as $key=>$val) {
		$config[$key] = $val;
	}
	
	
	
	
	/*
	 * Tabellen anlegen
	 */
	// MySQL-Präfixe
	$globprefix = $_POST['mysql_globprefix'];
	$prefix = $globprefix.'1_';
	
	
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
	$pw = General::encryptPassword($_POST['admin_passwort'], $config['instancekey']);
	
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
				userPassword = '".$pw."',
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
Der Account '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' wurde mit dem eingegebenen Passwort angelegt.
<br /><br />
Den Administrationsbereich findest du unter <a href="'.h($_POST['addr']).'admin/" target="_blank"><b>'.h($_POST['addr']).'admin/</b></a>. Auch hier kannst du dich mit dem eingegebenen Passwort anmelden.
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
<a href="../" target="_blank"><b>&raquo; zum Login</b></a>
<br />
<a href="'.h($_POST['addr']).'admin/" target="_blank"><b>&raquo; zum Administrationsbereich</b></a>';

}

$tmpl->output();



?>