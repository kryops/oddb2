<?php
/**
 * pages/admin/settings_send.php
 * Verwaltung -> Einstellungen -> Einstellungen speichern
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// keine Berechtigung
if(!$user->rechte['verwaltung_settings']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
}

// alles OK
else {
	// bisherige Konfiguration laden
	if(!class_exists('config')) {
		include './common/config.php';
	}
	
	$c = config::getcustom(INSTANCE);
	
	// Daten aufbereiten
	foreach($_POST as $key=>$val) {
		if($val === '') {
			unset($_POST[$key]);
		}
	}
	
	$int_vars = array(
		'scan_veraltet',
		'scan_veraltet_ally',
		'scan_veraltet_oview',
		'scan_veraltet_einst',
		'scan_veraltet_flotten',
		'scan_veraltet_geld',
		'scan_veraltet_forschung',
		'sprunggenerator_del',
		'bbstf_del',
		'toxxroute_del'
	);
	
	foreach($int_vars as $var) {
		if(isset($_POST[$var])) {
			$_POST[$var] = (int)$_POST[$var];
		}
	}
	
	
	if(isset($_POST['disable_freischaltung'])) {
		$_POST['disable_freischaltung'] = (bool)$_POST['disable_freischaltung'];
	}
	
	if(isset($_POST['disable_freischaltung_level'])) {
		if(!isset($rechte[$_POST['disable_freischaltung_level']])) {
			unset($_POST['disable_freischaltung_level']);
		}
		else $_POST['disable_freischaltung_level'] = (int)$_POST['disable_freischaltung_level'];
	}
	
	if(isset($_POST['logging'])) {
		$_POST['logging'] = (int)$_POST['logging'];
		if($_POST['logging'] < 0 OR $_POST['logging'] > 3) {
			unset($_POST['logging']);
		}
	}
	
	if(isset($_POST['logging_time'])) {
		$_POST['logging_time'] = (int)$_POST['logging_time'];
		if($_POST['logging_time'] < 1) {
			unset($_POST['logging_time']);
		}
	}
	
	// in die Konfiguration übertragen
	$settings = array(
		'scan_veraltet',
		'scan_veraltet_ally',
		'scan_veraltet_oview',
		'scan_veraltet_einst',
		'scan_veraltet_flotten',
		'scan_veraltet_geld',
		'scan_veraltet_forschung',
		'sprunggenerator_del',
		'bbstf_del',
		'toxxroute_del',
		'disable_freischaltung',
		'disable_freischaltung_level',
		'logging',
		'logging_time',
		'oviewmsg'
	);
	
	foreach($settings as $key) {
		// in der Konfiguration eintragen oder ändern
		if(isset($_POST[$key])) {
			$c[$key] = $_POST[$key];
		}
		// aus der Konfiguration löschen
		else if(isset($c[$key])) {
			unset($c[$key]);
		}
	}
	
	// Konfiguration speichern
	if(config::save(INSTANCE, $c)) {
		// Log-Eintrag
		if($config['logging'] >= 1) {
			insertlog(25, 'ändert die Grundeinstellungen der Instanz');
		}
		
		$tmpl->content = 'Die Einstellungen wurden erfolgreich gespeichert.';
	}
	else {
		$tmpl->error = 'Fehler beim Speichern der Einstellungen!';
	}
	
	
}

// Ausgabe
$tmpl->output();


?>