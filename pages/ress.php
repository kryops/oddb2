<?php
/**
 * pages/ress.php
 * eigene Ressplanis
 * Ressplanis der Allianz (auch Meta)
 * Bunker
 * Ress zum Raiden
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	if(($user->rechte['ressplani_ally'] OR $user->rechte['ressplani_meta']) AND $user->allianz) {
		$_GET['sp'] = 'ally';
	}
	else {
		$_GET['sp'] = 'own';
	}
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Ressplaneten';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'own'=>true,
	'own_send'=>true,
	'ally'=>true,
	'bunker'=>true,
	'raid'=>true
);


// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

// eigene Ressplanis speichern
if($_GET['sp'] == 'own_send') {
	include './pages/ress/own.php';
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	
	
	// Verbündete Ressplanis
	if(($user->rechte['ressplani_ally'] OR $user->rechte['ressplani_meta']) AND $user->allianz) {
		$csw->data['ally'] = array(
			'link'=>'index.php?p=ress&sp=ally',
			'bg'=>'background-image:url(img/layout/csw_ress.png);background-position:-150px 0px',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
	}
	
	// eigene Ressplanis
	$csw->data['own'] = array(
		'link'=>'index.php?p=ress&sp=own',
		'bg'=>'background-image:url(img/layout/csw_ress.png)',
		'reload'=>'false',
		'width'=>650,
		'content'=>''
	);
	
	
	// Bunker
	$csw->data['bunker'] = array(
		'link'=>'index.php?p=ress&sp=bunker',
		'bg'=>'background-image:url(img/layout/csw_ress.png);background-position:-300px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>''
	);
	
	// Ress zum Raiden
	if($user->rechte['ressplani_feind']) {
		$csw->data['raid'] = array(
			'link'=>'index.php?p=ress&sp=raid',
			'bg'=>'background-image:url(img/layout/csw_ress.png);background-position:-450px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
	}
	
	
	// Inhalt für eigene Ressplanis
	if($_GET['sp'] == 'own') {
		include './pages/ress/own.php';
	}
	
	// Inhalt für verbündete Ressplanis
	else if($_GET['sp'] == 'ally' AND isset($csw->data['ally'])) {
		include './pages/ress/ally.php';
	}
	
	// Inhalt für Bunker
	else if($_GET['sp'] == 'bunker' AND isset($csw->data['bunker'])) {
		include './pages/ress/bunker.php';
	}
	
	// Inhalt für Ress zum Raiden
	else if($_GET['sp'] == 'raid' AND isset($csw->data['raid'])) {
		include './pages/ress/raid.php';
	}
	
	
	
	
	
	// nur Unterseite ausgeben
	if(isset($_GET['switch'])) {
		if(isset($csw->data[$_GET['sp']])) {
			$tmpl->content = $csw->data[$_GET['sp']]['content'];
		}
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
	}
	// keine Berechtigung
	else if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
	}
	$tmpl->output();
	
}
?>