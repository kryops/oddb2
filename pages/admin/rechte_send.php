<?php
/**
 * pages/admin/rechte_send.php
 * Verwaltung -> Berechtigungen -> Rechtelevel bearbeiten (speichern)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// keine Berechtigung
if(!$user->rechte['verwaltung_rechte']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
}

// Vorhandensein der Daten
else if(!isset($_GET['id'], $rechte[$_GET['id']]) OR $_GET['id'] == 4) {
	$tmpl->error = 'Ung&uuml;ltiges Rechtelevel &uuml;bergeben!';
}

// alles OK
else {
	// Daten sichern
	$id = (int)$_GET['id'];
	
	// bisherige Konfiguration laden
	if(!class_exists('config')) {
		include './common/config.php';
	}
	
	$br = $brechte[$id];
	$br = array_merge_recursive($br, config::getcustomGlobal('global', 'rechte'));
	
	// Rechte-Container generieren
	$rechte = array(
		0=>array(),
		1=>array(),
		2=>array(),
		3=>array(),
		5=>array()
	);
	
	// Rechte-Konfifiguration laden
	include './config/config'.INSTANCE.'.php';
	
	$r = $rechte;
	
	// neu zu speicherndes Rechtelevel wieder zurücksetzen
	$r[$id] = array();
	$rneu =& $r[$id];
	
	// Rechte bereinigen
	unset($rechtenamen['override_allies']);
	unset($rechtenamen['override_galas']);
	
	// Name und Beschreibung
	if($_POST['name'] != $br['name'] AND trim($_POST['name']) != '') {
		$rneu['name'] = $_POST['name'];
	}
	if($_POST['desc'] != $br['desc'] AND trim($_POST['desc']) != '') {
		$rneu['desc'] = $_POST['desc'];
	}
	
	// Berechtigungen durchgehen
	foreach($_POST as $key=>$val) {
		// existiert und nicht standard
		if($val != -1 AND isset($rechtenamen[$key])) {
			$rneu[$key] = (bool)$val;
		}
	}
	
	// Konfiguration speichern
	config::save(INSTANCE, false, $r);
	
	// Log-Eintrag
	if($config['logging'] >= 1) {
		insertlog(25, 'ändert das Rechtelevel '.$id);
	}
	
	// Update von Name und Beschreibung, Fenster schließen
	if(trim($_POST['name']) == '') {
		$_POST['name'] = $br['name'];
	}
	if(trim($_POST['desc']) == '') {
		$_POST['desc'] = $br['desc'];
	}
	
	$content = '<b>'.addslashes(h($_POST['name'])).'</b><br />'.addslashes(h($_POST['desc']));
	
	$tmpl->script = '
parentwin_close(\'.rechteedit'.$id.'\');
$(\'.rechtelevel'.$id.'\').html(\''.$content.'\')';
}

// Ausgabe
$tmpl->output();


?>