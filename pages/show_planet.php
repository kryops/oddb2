<?php
/**
 * pages/show_planet.php
 * Planet anzeigen
 * Inhaber-History laden
 * Einteilung in Ressplanet, Bunker und Werft ändern
 * Kommentar schreiben
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'history'=>true,
	'kommentar'=>true,
	'kommentar_editgame'=>true,
	'kommentar_editgame2'=>true,
	'typ'=>true,
	'orbiter_del'=>true,
	'ress_del'=>true,
);

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;

/**
 * Funktionen
 */
 
/**
 * Rechte ermitteln, einen Planet anzuzeigen
 * bei fehlenden Berechtigungen wird eine Fehlermeldung ausgegeben und abgebrochen
 * @param $data array MySQL-Datensatz
 */
function show_planet_rechte($data) {
	global $user, $tmpl, $status_meta;
	
	// der Planet existiert nicht
	if(!$data) {
		$tmpl->error = 'Der Planet wurde nicht gefunden!';
	}
	// bei eigenen Planeten immer Berechtigung
	else if($data['planeten_playerID'] == $user->id) {}
	// keine Berechtigung (Ally)
	else if(!$user->rechte['show_planet_ally'] AND $user->allianz AND $data['player_allianzenID'] == $user->allianz) {
		$tmpl->error = 'Du hast keine Berechtigung, den Planet '.$data['planetenID'].' anzeigen zu lassen!';
	}
	// keine Berechtigung (Meta)
	else if(!$user->rechte['show_planet_meta'] AND $data['statusStatus'] == $status_meta AND $data['player_allianzenID'] != $user->allianz) {
		$tmpl->error = 'Du hast keine Berechtigung, den Planet '.$data['planetenID'].' anzeigen zu lassen!';
	}
	// keine Berechtigung (Allianz gesperrt)
	else if($user->protectedAllies AND in_array($data['player_allianzenID'], $user->protectedAllies)) {
		$tmpl->error = 'Du hast keine Berechtigung, den Planet '.$data['planetenID'].' anzeigen zu lassen!';
	}
	// keine Berechtigung (Galaxie gesperrt)
	else if($user->protectedGalas AND in_array($data['systeme_galaxienID'], $user->protectedGalas)) {
		$tmpl->error = 'Du hast keine Berechtigung, Planeten der Galaxie '.$data['systeme_galaxienID'].' anzeigen zu lassen!';
	}
	// keine Berechtigung (registrierte Allianzen)
	else if(!$user->rechte['show_planet_register'] AND $data['register_allianzenID'] !== NULL AND $data['statusStatus'] != $status_meta) {
		$tmpl->error = 'Du hast keine Berechtigung, den Planet '.$data['planetenID'].' anzeigen zu lassen!';
	}
	
	// Fehler ausgeben und abbrechen
	if($tmpl->error != '') {
		// beim ingame-Editieren von Kommentaren nur abbrechen
		if($_GET['sp'] == 'kommentar_editgame' OR $_GET['sp'] == 'kommentar_editgame2') {
			return false;
		}
		
		// Suchnavigation erzeugen
		searchnav();
		$tmpl->output();
		die();
	}
}

/**
 * Berechtigungen ermitteln, welche Planeten-Flags man sehen und editieren darf
 * @param $data array MySQL-Datensatz
 *
 * @return array Sichtbarkeit von Ressplanet, Bunker und Werft, Editier-Berechtigung
 */
function show_planet_typrechte($data) {
	global $user, $status_meta;
	
	// Berechtigungen default auf false setzen
	$r = array(
		'ressplani'=>false,
		'bunker'=>false,
		'werft'=>false,
		'flags'=>false
	);
	// eigener Planet -> alle Berechtigungen
	if($data['planeten_playerID'] == $user->id) {
		$r = array(
			'ressplani'=>true,
			'bunker'=>true,
			'werft'=>true,
			'flags'=>true
		);
	}
	// kein eigener Planet
	else {
		if($user->allianz AND $data['player_allianzenID'] == $user->allianz) $suffix = 'ally';
		else if($user->allianz AND $data['statusStatus'] == $status_meta) $suffix = 'meta';
		else if($data['register_allianzenID'] !== NULL) $suffix = 'register';
		else $suffix = 'other';
		
		// bei fremden Planeten darf man alle Eintragungen sehen
		if($suffix == 'other') {
			$r['ressplani'] = true;
			$r['bunker'] = true;
			$r['werft'] = true;
		}
		else {
			if($user->rechte['ressplani_'.$suffix]) $r['ressplani'] = true;
			if($user->rechte['bunker_'.$suffix]) $r['bunker'] = true;
			if($user->rechte['werft_'.$suffix]) $r['werft'] = true;
		}
		// editieren-Link nur anzeigen, wenn er überhaupt was editieren kann
		if($user->rechte['flags_edit_'.$suffix] AND ($r['ressplani'] OR $r['bunker'] OR $r['werft'])) $r['flags'] = true;
	}
	
	// Berechtigungen zurückgeben
	return $r;
}

/**
 * eventuell Suchnavigation erzeugen und vor den Content setzen
 */
function searchnav() {
	global $tmpl;
	
	$nav = false;
	
	if(isset($_GET['nav']) AND is_numeric($_GET['nav'])) {
		// bei nichtexistenten Planeten Wildcards  entfernen
		$_GET['id'] = str_replace('%', '', $_GET['id']);
		$t = time();
		
		// Navileiste erzeugen
		$nav = '
			<div class="fcbox center small2" id="snavbox'.$_GET['nav'].'-'.$_GET['id'].'-'.$t.'">Suchnavigation wird geladen...</div>';
		// JavaScript starten
		$tmpl->script = '
searchnav('.$_GET['id'].', '.$_GET['nav'].', '.$t.');';
	}
	
	// Fehler in Content umwandeln und Navigation davor setzen
	if($nav AND $tmpl->error) {
		$tmpl->content = $nav.'<div class="icontent" style="text-align:center;margin:20px;font-size:16px;font-weight:bold"><img src="img/layout/error.png" width="150" height="137" alt="Fehler" /><br /><br />'.$tmpl->error.'</div>';
		// ursprünglichen Fehler entfernen
		$tmpl->error = '';
		$tmpl->name = 'Fehler!';
	}
	// Navigation vor den Content setzen
	else if($nav) {
		$tmpl->content = $nav.$tmpl->content;
	}
}


// keine Berechtigung, irgendwelche Planeten anzuzeigen
if(!$user->rechte['show_planet']) {
	$tmpl->error = 'Du hast keine Berechtigung, Planeten anzuzeigen!';
	$tmpl->output();
	die();
}


// keine ID übergeben
if(!isset($_GET['id']) OR trim($_GET['id']) == '') {
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

// Planet anzeigen
else if($_GET['sp'] == '') {
	include './pages/show_planet/show.php';
}

// Inhaber-History laden
else if($_GET['sp'] == 'history') {
	include './pages/show_planet/history.php';
}

// Planeten-Flags editieren (Ressplanet, Bunker, Werft)
else if($_GET['sp'] == 'typ') {
	include './pages/show_planet/actions.php';
}

// Kommentar ändern
else if($_GET['sp'] == 'kommentar') {
	include './pages/show_planet/kommentar.php';
}
// Kommentar aus OD heraus ändern (Formular anzeigen)
else if($_GET['sp'] == 'kommentar_editgame') {
	include './pages/show_planet/kommentar.php';
}
// Kommentar aus OD heraus ändern (abschicken)
else if($_GET['sp'] == 'kommentar_editgame2') {
	include './pages/show_planet/kommentar.php';
}


// Orbiter löschen
else if($_GET['sp'] == 'orbiter_del') {
	include './pages/show_planet/actions.php';
}

// Ress auf 0 setzen
else if($_GET['sp'] == 'ress_del') {
	include './pages/show_planet/actions.php';
}

// Suchnavigation erzeugen
searchnav();

// Ausgabe
$tmpl->output();

?>