<?php
/**
 * pages/inva/inva_actions.php
 * Aktionen bei angemeldeten Spielern:
 * - Aktion auf freundlich setzen
 * - Abbruch übernehmen oder zurückziehen
 * - Kommentar ändern
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Invasion auf freundlich setzen
if($_GET['sp'] == 'inva_freundlich') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_GET['status'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['invasionen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Datensatz abfragen
		$data = invadata($_GET['id'], true);
		
		// Berechtigung
		if($user->rechte['invasionen_admin'] OR $data['invasionen_playerID'] == $user->id) {
			$status = $_GET['status'] ? 1 : 0;
			
			// Invasion noch offen?
			$open = 1;
			if($status OR $data['invasionenAbbrecher']) {
				$open = 0;
			}
			
			// Datensatz aktualisieren
			$data['invasionenOpen'] = $open;
			$data['invasionenFreundlich'] = $status;
			
			// Inva aktualisieren
			query("
				UPDATE
					".PREFIX."invasionen
				SET
					invasionenFreundlich = ".$status.",
					invasionenOpen = ".$open."
				WHERE
					invasionenID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// InvaLog-Eintrag
			$logtext = 'setzt die Aktion auf '.($status ? 'freundlich' : 'feindlich');
			
			query("
				INSERT INTO
					".PREFIX."invasionen_log
				SET
					invalog_invasionenID = ".$_GET['id'].",
					invalogTime = ".time().",
					invalog_playerID = ".$user->id.",
					invalogText = '".$logtext."'
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(27, 'setzt die '.$invatyp[$data['invasionenTyp']].' '.$_GET['id'].' (Planet '.$data['invasionen_planetenID'].') auf '.($status ? 'freundlich' : 'feindlich'));
			}
			
			// Ausgabe
			$tmpl->content = $status ? '<span class="green">freundlich</span>' : '<span class="red">feindlich</span>';
			
			// Zeile aktualisieren
			$tmpl->script = '
			var entf = false;
			if($(\'.invarow'.$_GET['id'].'\').children().length == 13) {
				var entf = $(\'.invarow'.$_GET['id'].' td:nth-child(10)\').html();
			}
			$(\'.invarow'.$_GET['id'].'\').replaceWith(\''.escape(invarow($data)).'\');
			if(entf) {
				$(\'.invarow'.$_GET['id'].' td:nth-child(9)\').after(\'<td>\'+entf+\'</td>\');
			}
			openinvas();';
			
			// offene Invasionen aus dem Cache löschen
			$cache->remove('openinvas');
		}
		// keine Berechtigung
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Invasion abbrechen / zurückziehen
else if($_GET['sp'] == 'inva_abbruch') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_GET['status'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['invasionen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Datensatz abfragen
		$data = invadata($_GET['id'], true);
		
		$status = $_GET['status'] ? $user->id : 0;
		
		// Invasion noch offen?
		$open = 1;
		if($status OR $data['invasionenFreundlich']) {
			$open = 0;
		}
		
		// Datensatz aktualisieren
		$data['invasionenOpen'] = $open;
		$data['invasionenAbbrecher'] = $status;
		$data['abbr_playerName'] = $status ? $user->name : NULL;
		
		// Inva aktualisieren
		query("
			UPDATE
				".PREFIX."invasionen
			SET
				invasionenAbbrecher = ".$status.",
				invasionenOpen = ".$open."
			WHERE
				invasionenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// InvaLog-Eintrag
		$logtext = $status ? 'übernimmt den Abbruch' : 'zieht den Abbruch zurück';
		
		query("
			INSERT INTO
				".PREFIX."invasionen_log
			SET
				invalog_invasionenID = ".$_GET['id'].",
				invalogTime = ".time().",
				invalog_playerID = ".$user->id.",
				invalogText = '".$logtext."'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(27, $status ? 'übernimmt den Abbruch der '.$invatyp[$data['invasionenTyp']].' '.$_GET['id'].' (Planet '.$data['invasionen_planetenID'].')' : 'zieht den Abbruch der '.$invatyp[$data['invasionenTyp']].' '.$_GET['id'].' (Planet '.$data['invasionen_planetenID'].') zurück');
		}
		
		// Ausgabe
		$tmpl->content = $status ? '<i>Abbruch &uuml;bernommen</i>' : '<i>Abbruch zur&uuml;ckgezogen</i>';
		
		// Zeile aktualisieren
		$tmpl->script = '
		var entf = false;
		if($(\'.invarow'.$_GET['id'].'\').children().length == 13) {
			var entf = $(\'.invarow'.$_GET['id'].' td:nth-child(10)\').html();
		}
		$(\'.invarow'.$_GET['id'].'\').replaceWith(\''.escape(invarow($data)).'\');
		if(entf) {
			$(\'.invarow'.$_GET['id'].' td:nth-child(9)\').after(\'<td>\'+entf+\'</td>\');
		}
		openinvas();';
		
		// offene Invasionen aus dem Cache löschen
		$cache->remove('openinvas');
	}
	
	// Ausgabe
	$tmpl->output();
}

// Kommentar ändern
else if($_GET['sp'] == 'inva_kommentar') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['invasionen'] AND !$user->rechte['fremdinvakolos']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Datensatz abfragen
		$data = invadata($_GET['id'], true);
		
		// Datensatz aktualisieren
		$data['invasionenKommentar'] = $_POST['kommentar'];
		
		// Inva aktualisieren
		query("
			UPDATE
				".PREFIX."invasionen
			SET
				invasionenKommentar = '".escape($_POST['kommentar'])."'
			WHERE
				invasionenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// InvaLog-Eintrag
		query("
			INSERT INTO
				".PREFIX."invasionen_log
			SET
				invalog_invasionenID = ".$_GET['id'].",
				invalogTime = ".time().",
				invalog_playerID = ".$user->id.",
				invalogText = 'ändert den Kommentar'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(27, 'ändert den Kommentar der '.$invatyp[$data['invasionenTyp']].' '.$_GET['id'].' (Planet '.$data['invasionen_planetenID'].')');
		}
		
		// Ausgabe
		$tmpl->content = '<div class="kommentar" style="float:left"></div> &nbsp; ';
		// Kommentar
		if(trim($data['invasionenKommentar']) != '') {
			$tmpl->content .= '
				&nbsp; <a onclick="invakommentar_edit('.$_GET['id'].', this.parentNode, true)" class="hint">[&auml;ndern]</a>
				
				<div class="kommentarc">'.nl2br(htmlspecialchars($data['invasionenKommentar'], ENT_COMPAT, 'UTF-8')).'</div>';
		}
		// kein Kommentar
		else {
			$tmpl->content .= '&nbsp; <a onclick="invakommentar_edit('.$_GET['id'].', this.parentNode, false)">Kommentar hinzuf&uuml;gen</a>';
		}
		
		// Zeile aktualisieren
		$fremd = ($data['invasionenFremd'] OR $data['invasionenTyp'] == 5);
		$tmpl->script = '
		var entf = false;
		if($(\'.invarow'.$_GET['id'].'\').children().length == 13) {
			var entf = $(\'.invarow'.$_GET['id'].' td:nth-child(10)\').html();
		}
		$(\'.invarow'.$_GET['id'].'\').replaceWith(\''.escape(invarow($data, $fremd)).'\');
		if(entf) {
			$(\'.invarow'.$_GET['id'].' td:nth-child(9)\').after(\'<td>\'+entf+\'</td>\');
		}';
	}
	
	// Ausgabe
	$tmpl->output();
}



?>