<?php
/**
 * pages/inva/masseninva_actions.php
 * Masseninva-Koordinator-Aktionen
 * Allianzen hinzufügen und entfernen
 * Planeten reservieren, Reservierung aufheben
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Masseninva: Allianzen hinzufügen
if($_GET['sp'] == 'masseninva_add') {
	// Daten unvollständig
	if(!isset($_POST['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['masseninva_admin']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern und aufbereiten
		$ids = explode(',', $_POST['id']);
		
		foreach($ids as $key=>$val) {
			$val = (int)$val;
			
			if($val > 0) {
				$ids[$key] = $val;
			}
			else {
				unset($ids[$key]);
			}
		}
		
		// keine gültige ID
		if(!count($ids)) {
			$tmpl->error = 'Eingabe ung&uuml;ltig!';
			$tmpl->output();
			die();
		}
		
		// Existenz abfragen
		$query = query("
			SELECT
				allianzenID
			FROM ".GLOBPREFIX."allianzen
			WHERE
				allianzenID IN(".implode(", ", $ids).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$ids2 = array();
		
		// Allianzen nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Die Allianz'.(count($ids) > 1 ? 'en' : '').' wurde'.(count($ids) > 1 ? 'n' : '').' nicht gefunden!';
			$tmpl->output();
			die();
		}
		
		while($row = mysql_fetch_assoc($query)) {
			$ids2[] = (int)$row['allianzenID'];
		}
		
		// Masseninva-Status bei den Planeten der Allianzen zurücksetzen
		query("
			UPDATE
				".PREFIX."planeten
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
			SET
				planetenMasseninva = 0
			WHERE
				player_allianzenID IN(".implode(", ", $ids2).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Konfigurationsklasse einbinden
		if(!class_exists('config')) {
			include './common/config.php';
		}
		
		$c = config::getcustom(INSTANCE);
		
		// noch keine Masseninva eingetragen
		if(!isset($c['masseninva'])) {
			$c['masseninva'] = $ids2;
		}
		// IDs hinzufügen
		else {
			foreach($ids2 as $id) {
				if(!in_array($id, $c['masseninva'])) {
					$c['masseninva'][] = $id;
				}
			}
		}
		
		// Konfiguration speichern
		config::save(INSTANCE, $c);
		
		// Masseninva-Status auch bei allen anderen Planeten zurücksetzen
		query("
			UPDATE
				".PREFIX."planeten
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
			SET
				planetenMasseninva = 0
			WHERE
				player_allianzenID NOT IN(".implode(", ", $c['masseninva']).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging']) {
			insertlog(27, 'fügt die Allianzen '.implode(', ', $ids2).' zum Masseninva-Koordinator hinzu');
		}
		
		$tmpl->script = 'ajaxcall(\'index.php?p=inva&sp=masseninva&update\', $(\'.masseninva_ziele\'), false, true);';
	}
	
	// Ausgabe
	$tmpl->output();
}

// Masseninva: Allianzen entfernen
else if($_GET['sp'] == 'masseninva_del') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['masseninva_admin']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Masseninva-Status bei den Planeten der Allianzen zurücksetzen
		query("
			UPDATE
				".PREFIX."planeten
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
			SET
				planetenMasseninva = 0
			WHERE
				player_allianzenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Konfigurationsklasse einbinden
		if(!class_exists('config')) {
			include './common/config.php';
		}
		
		$c = config::getcustom(INSTANCE);
		$r = config::getcustom_rechte(INSTANCE);
		
		
		if(isset($c['masseninva'])) {
			// Allianz entfernen
			foreach($c['masseninva'] as $key=>$val) {
				if($val == $_GET['id']) {
					unset($c['masseninva'][$key]);
				}
			}
			// kein Ziel mehr eingetragen
			if(!count($c['masseninva'])) {
				unset($c['masseninva']);
			}
			
			// Konfiguration speichern
			config::save(INSTANCE, $c, $r);
		}
		
		// Log-Eintrag
		if($config['logging']) {
			insertlog(27, 'entfernt die Allianz '.$_GET['id'].' aus dem Masseninva-Koordinator');
		}
		
		$tmpl->script = 'ajaxcall(\'index.php?p=inva&sp=masseninva&update\', $(\'.masseninva_ziele\'), false, true);';
	}
	
	// Ausgabe
	$tmpl->output();
}

// Masseninva: Planet reservieren
else if($_GET['sp'] == 'masseninva_set') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['masseninva']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Existenz und bereits vorhandene Reservierung
		$query = query("
			SELECT
				planetenMasseninva
			FROM
				".PREFIX."planeten
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Planet nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Planet wurde nicht gefunden!';
			$tmpl->output();
			die();
		}
		
		$data = mysql_fetch_assoc($query);
		
		// bereits reserviert
		if($data['planetenMasseninva'] AND $data['planetenMasseninva'] != $user->id) {
			$tmpl->error = 'Der Planet wurde bereits reserviert!';
			$tmpl->output();
			die();
		}
		
		// Reservierung eintragen
		query("
			UPDATE
				".PREFIX."planeten
			SET
				planetenMasseninva = ".$user->id."
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Ausgabe
		$tmpl->content = '&rarr; <a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$user->id.'">'.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'</a> <img title="Reservierung aufheben" onclick="ajaxcall(\'index.php?p=inva&amp;sp=masseninva_unset&amp;id='.$_GET['id'].'\', this.parentNode, false, false)" class="hoverbutton delbutton" src="img/layout/leer.gif" />';
		
		// in die Tabelle der eigenen Ziele übernehmen
		$tmpl->script = '
$(\'.masseninva_own_empty\').hide();
$(\'.masseninva_own\').show();
$(\'.masseninva_own\').append(\'<tr class="masseninva'.$_GET['id'].'">\'+$(\'.masseninva'.$_GET['id'].'\').html()+\'</tr>\');';
		
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(27, 'reserviert den Planet '.$_GET['id'].' im Masseninva-Koordinator');
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Masseninva: Reservierung aufheben
else if($_GET['sp'] == 'masseninva_unset') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['masseninva']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Existenz und bereits vorhandene Reservierung
		$query = query("
			SELECT
				planetenMasseninva
			FROM
				".PREFIX."planeten
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Planet nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Planet wurde nicht gefunden!';
			$tmpl->output();
			die();
		}
		
		$data = mysql_fetch_assoc($query);
		
		// bereits reserviert
		if($data['planetenMasseninva'] AND $data['planetenMasseninva'] != $user->id) {
			$tmpl->error = 'Der Planet wurde von jemand anderem reserviert!';
			$tmpl->output();
			die();
		}
		
		// Reservierung entfernen
		query("
			UPDATE
				".PREFIX."planeten
			SET
				planetenMasseninva = 0
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Ausgabe
		$tmpl->content = '<img title="als Ziel reservieren" onclick="ajaxcall(\'index.php?p=inva&amp;sp=masseninva_set&amp;id='.$_GET['id'].'\', this.parentNode, false, false)" class="hoverbutton arrowbutton" src="img/layout/leer.gif" />';
		
		// aus der Tabelle der eigenen Ziele entfernen
		$tmpl->script = '
$(\'.masseninvao'.$_GET['id'].'\').html(\'<img title="als Ziel reservieren" onclick="ajaxcall(\\\'index.php?p=inva&amp;sp=masseninva_set&amp;id='.$_GET['id'].'\\\', this.parentNode, false, false)" class="hoverbutton arrowbutton" src="img/layout/leer.gif" />\');
$(\'.masseninva_own .masseninva'.$_GET['id'].'\').remove();';
		
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(27, 'hebt die Reservierung des Planets '.$_GET['id'].' im Masseninva-Koordinator auf');
		}
	}
	
	// Ausgabe
	$tmpl->output();
}


?>