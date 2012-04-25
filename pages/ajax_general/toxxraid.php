<?php

/**
 * pages/ajax_general/toxxraid.php
 * Planet als getoxxt/geraidet markieren
 * Planet reservieren
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// raiden
if($_GET['sp'] == 'raid') {
	if($user->rechte['toxxraid'] AND isset($_GET['id'])) {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Raid-Datum aktualisieren
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenGeraidet = ".time()."
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(11, 'raidet den Planeten '.$_GET['id']);
		}
		
		// Ausgabe
		if(isset($_GET['typ']) AND $_GET['typ'] == 'search') {
			$tmpl->content = date('d.m.y');
		}
		else {
			$tmpl->content = '<i>Planet geraidet</i>';
		}
	}
	// keine Berechtigung
	else {
		if(isset($_GET['id'])) $tmpl->error = 'Du hast keine Berechtigung!';
		else $tmpl->error = 'Keine ID übergeben!';
	}
	// Ausgabe
	$tmpl->output();
}
// toxxen
else if($_GET['sp'] == 'toxx') {
	if($user->rechte['toxxraid'] AND isset($_GET['id'])) {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Planetendaten abfragen
		$query = query("
			SELECT
				planetenBevoelkerung
			FROM
				".PREFIX."planeten
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
			
			// wenn nicht anders angegeben, 500k als Bevölkerung annehmen
			$bev = isset($_GET['bev']) ? ($data['planetenBevoelkerung']-(int)$_GET['bev']) : 500000;
			
			$getoxxt = getoxxt($data['planetenBevoelkerung'], $bev);
			
			// Toxx-Datum aktualisieren
			query("
				UPDATE ".PREFIX."planeten
				SET
					planetenGetoxxt = ".$getoxxt."
				WHERE
					planetenID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(11, 'toxxt den Planeten '.$_GET['id']);
			}
			
			// Ausgabe
			if(isset($_GET['typ']) AND $_GET['typ'] == 'search') {
				$tmpl->content = date('d.m.y', $getoxxt);
			}
			else {
				$tmpl->content = '<i>Planet getoxxt</i>';
			}
		}
		// Planet nicht gefunden
		else {
			$tmpl->error = 'Planet nicht gefunden!';
		}
	}
	// keine Berechtigung
	else {
		if(isset($_GET['id'])) $tmpl->error = 'Du hast keine Berechtigung!';
		else $tmpl->error = 'Keine ID übergeben!';
	}
	// Ausgabe
	$tmpl->output();
}
// ToxxRaid reservieren
else if($_GET['sp'] == 'toxxraidreserv') {
	if($user->rechte['toxxraid'] AND isset($_GET['id'])) {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// reservieren
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenReserv = ".time()."
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(11, 'reserviert den Planeten '.$_GET['id']);
		}
		
		// Ausgabe
		$tmpl->content = '<i>reserv</i>';
	}
	// keine Berechtigung
	else {
		if(isset($_GET['id'])) $tmpl->error = 'Du hast keine Berechtigung!';
		else $tmpl->error = 'Keine ID übergeben!';
	}
	// Ausgabe
	$tmpl->output();
}

?>