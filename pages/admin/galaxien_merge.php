<?php
/**
 * pages/admin/galaxien_merge.php
 * Verwaltung -> Galaxieverwaltung -> Galaxien verschmelzen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// keine Berechtigung
if(!$user->rechte['verwaltung_galaxien2']) $tmpl->error = 'Du hast keine Berechtigung!';
// keine Daten
else if(!isset($_POST['dest'], $_POST['source'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// alles OK
else {
	// Daten sichern
	$_POST['dest'] = (int)$_POST['dest'];
	
	$source = explode(',', $_POST['source']);
	foreach($source as $key=>$val) {
		$val = (int)$val;
		if($val < 1) {
			unset($source[$key]);
		}
		else {
			$source[$key] = $val;
		}
	}
	
	// Ziel-Gala ungültig
	if($_POST['dest'] < 1) {
		$tmpl->error = 'Ung&uuml;ltige Ziel-Galaxie eingegeben!';
	}
	// Source-Galas ungültig
	else if(!count($source)) {
		$tmpl->error = 'Ung&uuml;ltige Galaxien eingegeben!';
	}
	
	// keine Fehler
	if(!$tmpl->error) {
		// Ziel-Galaxie an die Source-Galaxien anhängen
		if(!in_array($_POST['dest'], $source)) {
			$source[] = $_POST['dest'];
		}
		
		$sq = implode(', ', $source);
		
		// alle beteiligten Galaxien zuerst löschen
		query("
			DELETE FROM
				".PREFIX."galaxien
			WHERE
				galaxienID IN(".$sq.")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Gates und Gate-Entfernungen aus der Planeten-Tabelle löschen
		query("
			UPDATE
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
			SET
				planetenGateEntf = NULL
			WHERE
				systeme_galaxienID IN(".$sq.")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Systeme verschieben und Gate-Entfernung löschen
		query("
			UPDATE
				".PREFIX."systeme
			SET
				systeme_galaxienID = ".$_POST['dest'].",
				systemeGateEntf = NULL
			WHERE
				systeme_galaxienID IN(".$sq.")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// System-Anzahl berechnen und neue Galaxie eintragen
		$query = query("
			SELECT
				COUNT(*)
			FROM
				".PREFIX."systeme
			WHERE
				systeme_galaxienID = ".$_POST['dest']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$count1 = mysql_fetch_array($query);
		$count1 = $count1[0];
		
		$query = query("
			SELECT
				COUNT(*)
			FROM
				".PREFIX."systeme
			WHERE
				systeme_galaxienID = ".$_POST['dest']."
				AND systemeUpdate > 0
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$count2 = mysql_fetch_array($query);
		$count2 = $count2[0];
		
		query("
			INSERT INTO
				".PREFIX."galaxien
			SET
				galaxienID = ".$_POST['dest'].",
				galaxienSysteme = ".$count1.",
				galaxienSysScanned = ".$count2."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Myrigates verschmelzen
		query("
			UPDATE
				".PREFIX."myrigates
			SET
				myrigates_galaxienID = ".$_POST['dest']."
			WHERE
				myrigates_galaxienID IN(".$sq.")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Routen verschmelzen
		query("
			UPDATE
				".PREFIX."routen
			SET
				routen_galaxienID = ".$_POST['dest']."
			WHERE
				routen_galaxienID IN(".$sq.")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Ausgabe
		$tmpl->content = '
			<br />
			Die Galaxien wurden erfolgreich verschmolzen.<br />
			Das neue Gatesystem muss erneut eingescannt werden.';
		
		// Log-Eintrag
		if($config['logging'] >= 1) {
			insertlog(14, 'verschmilzt die Galaxien '.$_POST['source'].' zu '.$_POST['dest']);
		}
	}
}

// Ausgabe
if($tmpl->error) {
	$tmpl->error = '<br />'.$tmpl->error;
}
$tmpl->output();


?>