<?php

/**
 * pages/ajax_general/favoriten.php
 * Favorit hinzufügen, bearbeiten und löschen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Favorit hinzufügen
if($_GET['sp'] == 'fav_add') {
	// Daten vorhanden?
	if(!isset($_POST['link'], $_POST['name'], $_POST['typ'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_POST['link'] = escape($_POST['link']);
		$_POST['name'] = escape($_POST['name']);
		$_POST['typ'] = (int)$_POST['typ'];
		// ungültigen Typ einfach überschreiben
		if(!$_POST['typ'] OR $_POST['typ'] > 5 OR $_POST['typ'] == 4) {
			$_POST['typ'] = 1;
		}
		
		query("
			INSERT INTO ".PREFIX."favoriten
			SET
				favoriten_playerID = ".$user->id.",
				favoritenLink = '".$_POST['link']."',
				favoritenName = '".$_POST['name']."',
				favoritenTyp = ".$_POST['typ']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Favoriten-Content im Startmenü neu erzeugen
		$tmpl->content = startmenu();
	}
	// Ausgabe
	$tmpl->output();
}

// Favorit bearbeiten
else if($_GET['sp'] == 'fav_edit') {
	// Daten vorhanden?
	if(!isset($_POST['id'], $_POST['link'], $_POST['name'], $_POST['typ'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_POST['link'] = escape($_POST['link']);
		$_POST['name'] = escape($_POST['name']);
		$_POST['id'] = (int)$_POST['id'];
		$_POST['typ'] = (int)$_POST['typ'];
		// ungültigen Typ einfach überschreiben
		if(!$_POST['typ'] OR $_POST['typ'] > 5 OR $_POST['typ'] == 4) {
			$_POST['typ'] = 1;
		}
		
		query("
			UPDATE ".PREFIX."favoriten
			SET
				favoritenLink = '".$_POST['link']."',
				favoritenName = '".$_POST['name']."',
				favoritenTyp = ".$_POST['typ']."
			WHERE
				favoritenID = ".$_POST['id']."
				AND favoriten_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Favoriten-Content im Startmenü neu erzeugen
		$tmpl->content = startmenu();
	}
	// Ausgabe
	$tmpl->output();
}

// Favorit löschen
else if($_GET['sp'] == 'fav_del') {
	// Daten vorhanden?
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles OK
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		query("
			DELETE FROM ".PREFIX."favoriten
			WHERE
				favoritenID = ".$_GET['id']."
				AND favoriten_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Favoriten-Content im Startmenü neu erzeugen
		$tmpl->content = startmenu();
	}
	// Ausgabe
	$tmpl->output();
}


?>