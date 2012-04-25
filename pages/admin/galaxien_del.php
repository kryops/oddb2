<?php
/**
 * pages/admin/galaxien_del.php
 * Verwaltung -> Galaxieverwaltung -> Galaxie löschen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// keine Berechtigung
if(!$user->rechte['verwaltung_galaxien2']) $tmpl->error = 'Du hast keine Berechtigung!';
// keine Daten
else if(!isset($_POST['gala'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// alles OK
else {
	// Daten sichern
	$_POST['gala'] = (int)$_POST['gala'];
	
	// Ziel-Gala ungültig
	if($_POST['gala'] < 1) {
		$tmpl->error = 'Ung&uuml;ltige Galaxie eingegeben!';
	}
	else {
		// Existenz überprüfen
		$query = query("
			SELECT
				COUNT(*)
			FROM
				".PREFIX."galaxien
			WHERE
				galaxienID = ".$_POST['gala']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_array($query);
		
		// Galaxie nicht eingetragen
		if(!$data[0]) {
			$tmpl->error = 'Die Galaxie ist nicht in der Datenbank eingetragen!';
		}
		// Galaxie eingetragen -> löschen
		else {
			// Galaxie löschen
			query("
				DELETE FROM
					".PREFIX."galaxien
				WHERE
					galaxienID = ".$_POST['gala']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Planeten und Systeme löschen
			query("
				DELETE
					".PREFIX."planeten.*,
					".PREFIX."systeme.*
				FROM
					".PREFIX."systeme
					LEFT JOIN ".PREFIX."planeten
						ON systemeID = planeten_systemeID
				WHERE
					systeme_galaxienID = ".$_POST['gala']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Ausgabe
			$tmpl->content = '
				<br />
				Die Galaxie wurde erfolgreich gel&ouml;scht.';
			
			// Log-Eintrag
			if($config['logging'] >= 1) {
				insertlog(14, 'löscht die Galaxie '.$_POST['gala']);
			}
		}
	}
}
// Ausgabe
if($tmpl->error) {
	$tmpl->error = '<br />'.$tmpl->error;
}
$tmpl->output();


?>