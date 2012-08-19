<?php

/**
 * install/check.php
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

$errors = array();

// allow_url_fopen
if(!ini_get('allow_url_fopen')) {
	$errors[] = 'Auf deinem Server ist die Einstellung allow_url_fopen deaktiviert. Bitte aktiviere sie (oder lasse sie von deinem Hoster aktivieren), damit die ODDB installiert werden kann!';
}

// Dateien und Verzeichnisse schreibbar
if(!is_writable('../config')) {
	$errors[] = 'PHP ben&ouml;tigt Schreibrechte für den Ordner /config!';
}
if(!is_writable('../admin/cache')) {
	$errors[] = 'PHP ben&ouml;tigt Schreibrechte für den Ordner /admin/cache!';
}

// MySQL
if(!function_exists('mysql_connect')) {
	$errors[] = 'Deine PHP-Installation scheint keine MySQL-Datenbank zu unterst&uuml;tzen!';
}


// Fehler aufgetreten
if(count($errors)) {
	$tmpl->name = 'Installation nicht m&ouml;glich';
	
	$tmpl->error = implode('<br /><br />', $errors);
}

// Daten sammeln und Installationsformular ausgeben
else {
	$tmpl->name = 'Installations-Einstellungen';
	
	$tmpl->content = '

Dieses Script speichert die wichtigsten Grundeinstellungen der ODDB und legt die erste Instanz an.
<br />
Die Einstellungen &auml;ndern sowie weitere Instanzen anlegen kannst du sp&auml;ter in der Administrationsoberfläche.
<br /><br />
	
'.$tmpl->form();
	
	// Cache-Optionen bei deaktiviertem Cache ausblenden
	$tmpl->script = '

$(document).ready(function() {
	
	if($(".cache_option :selected").val() != 2) {
		$(".show_memcached").hide();
	}
	
	if($(".cache_option :selected").val() == 0) {
		$(".show_cache").hide();
	}
	
	$(".cache_option").change(function() {
		if($(this).val() == 0) {
			$(".show_cache").hide();
			$(".show_memcached").hide();
		}
		else if($(this).val() == 1) {
			$(".show_cache").show();
			$(".show_memcached").hide();
		}
		else {
			$(".show_cache").show();
		}
	});
	
});

';
}

$tmpl->output();



?>