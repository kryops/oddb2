<?php
/**
 * pages/stats.php
 * Scan-Status
 * User-Highscore
 * Gesinnungs-Highscore
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	if($user->rechte['stats_scan']) $_GET['sp'] = 'scan';
	else if($user->rechte['stats_highscore']) $_GET['sp'] = 'highscore';
	else $_GET['sp'] = 'gesinnung';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Statistiken';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'scan'=>true,
	'highscore'=>true,
	'gesinnung'=>true
);



// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Scan-Status
	if($user->rechte['stats_scan']) {
		$csw->data['scan'] = array(
			'link'=>'index.php?p=stats&sp=scan',
			'bg'=>'background-image:url(img/layout/csw_stats.png)',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für den Scan-Status
		if($_GET['sp'] == 'scan') {
			include './pages/stats/scan.php';
		}
	}
	
	// User-Highscore
	if($user->rechte['stats_highscore']) {
		$csw->data['highscore'] = array(
			'link'=>'index.php?p=stats&sp=highscore',
			'bg'=>'background-image:url(img/layout/csw_stats.png);background-position:-150px 0px',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für die User-Highscore
		if($_GET['sp'] == 'highscore') {
			include './pages/stats/highscore.php';
		}
	}
	
	// Gesinnungs-Highscore
	$csw->data['gesinnung'] = array(
		'link'=>'index.php?p=stats&sp=gesinnung',
		'bg'=>'background-image:url(img/layout/csw_stats.png);background-position:-300px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>''
	);
	
	// Inhalt für die Gesinnungs-Highscore
	if($_GET['sp'] == 'gesinnung') {
		include './pages/stats/gesinnung.php';
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