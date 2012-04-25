<?php
/**
 * admin/pages/oview.php
 * Übersichtsseite
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template_admin;
$tmpl->name = 'Übersicht';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Seite
 */

// Übersichtsseite anzeigen
else {
	$tmpl->content = '
		<br /><br />
		<div align="center">
			ODDB V'.VERSION.' - OD '.ODWORLD.'
			<br /><br />
			Willkommen im Administrationsbereich der ODDB!
		</div>
		<br /><br />
	';
	
	// Ausgabe
	$tmpl->output();
}

?>