<?php
/**
 * pages/show_planet/actions.php
 * Planeten-Aktionen
 * - Einteilung in Ressplanet, Werft und Bunker ändern
 * - Orbiter löschen
 * - Ress auf 0 setzen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Planeten-Flags editieren (Ressplanet, Bunker, Werft)
if($_GET['sp'] == 'typ') {
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	
	$data = false;
	
	// Existenz und Berechtigung ermitteln, Daten abfragen
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			planetenRessplani,
			planetenBunker,
			planetenWerft,
			
			systeme_galaxienID,
			
			player_allianzenID,
			
			register_allianzenID,
			
			statusStatus
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = player_allianzenID
		WHERE
			planetenID = ".$_GET['id']."
		ORDER BY planetenID ASC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit dieser ID existiert
	if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
	
	// Rechte ermitteln, den Planeten anzuzeigen
	show_planet_rechte($data);
	
	// Rechte ermitteln, die Flags anzuzeigen und zu editieren
	$r = show_planet_typrechte($data);
	
	// keine Berechtigung
	if(!$r['flags']) {
		$tmpl->error = 'Du hast keine Berechtigung, die Planeten-Einteilung zu bearbeiten!';
	}
	// Berechtigung
	else {
		// Flag-Bearbeitung abschicken
		if(isset($_GET['send'])) {
			$query = array();
			// Ressplanet
			if($r['ressplani']) {
				$data['planetenRessplani'] = isset($_POST['ressplani']) ? 1 : 0;
				$query[] = 'planetenRessplani = '.$data['planetenRessplani'];
			}
			// Bunker
			if($r['bunker']) {
				$data['planetenBunker'] = isset($_POST['bunker']) ? 1 : 0;
				$query[] = 'planetenBunker = '.$data['planetenBunker'];
			}
			// Ressplanet
			if($r['werft']) {
				$data['planetenWerft'] = isset($_POST['werft']) ? 1 : 0;
				$query[] = 'planetenWerft = '.$data['planetenWerft'];
			}
			
			// Anfrage abschicken
			query("
				UPDATE ".PREFIX."planeten
				SET
					".implode(', ', $query)."
				WHERE
					planetenID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(12, 'ändert die Einteilung des Planeten '.$_GET['id'].' (Ressplanet, Bunker, Werft)');
			}
			
			// geänderte Flags wieder anzeigen
			$flags = array();
			
			if($r['ressplani'] AND $data['planetenRessplani']) $flags[] = 'Ressplanet';
			if($r['bunker'] AND $data['planetenBunker']) $flags[] = 'Bunker';
			if($r['werft'] AND $data['planetenWerft']) $flags[] = 'Werft';
			if(count($flags) == 3) $flagtext = 'Ressplanet, Bunker und Werft';
			else $flagtext = implode(' und ', $flags);
			
			if(count($flags)) {
				$tmpl->content .= 'eingetragen als '.$flagtext.' &nbsp;';
			}
			else if($r['flags']) {
				$tmpl->content .= '<i>nicht eingeteilt</i> &nbsp; ';
			}
			$tmpl->content .= '
									<a onclick="ajaxcall(\'index.php?p=show_planet&amp;id='.$_GET['id'].'&amp;sp=typ&amp;ajax\', this.parentNode, false, false)" class="hint">[&auml;ndern]</a>';
		}
		// Formular anzeigen
		else {
			$tmpl->content .= '<form action="#" onsubmit="form_send(this, \'index.php?p=show_planet&amp;id='.$_GET['id'].'&amp;sp=typ&amp;send&amp;ajax\', $(this.parentNode));return false">';
			
			// Ressplanet
			if($r['ressplani']) {
				$tmpl->content .= '<input type="checkbox" name="ressplani"'.($data['planetenRessplani'] ? ' checked="checked"' : '').' /> 
					<span class="togglecheckbox" data-name="ressplani">Ressplanet</span> &nbsp; ';
			}
			// Bunker
			if($r['bunker']) {
				$tmpl->content .= '<input type="checkbox" name="bunker"'.($data['planetenBunker'] ? ' checked="checked"' : '').' /> 
					<span class="togglecheckbox" data-name="bunker">Bunker</span> &nbsp; ';
			}
			// Werft
			if($r['werft']) {
				$tmpl->content .= '<input type="checkbox" name="werft"'.($data['planetenWerft'] ? ' checked="checked"' : '').' /> 
					<span class="togglecheckbox" data-name="werft">Werft</span> &nbsp; ';
			}
			
			$tmpl->content .= '<a href="javascript:void(0)" onclick="$(this.parentNode).trigger(\'onsubmit\')" class="hint">abschicken</a>
				</form>';
		}
	}
}


// Orbiter löschen
else if($_GET['sp'] == 'orbiter_del') {
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	
	$data = false;
	
	// Existenz und Berechtigung ermitteln
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			planetenUpdateOverview,
			planetenGebOrbit,
			
			systeme_galaxienID,
			
			player_allianzenID,
			
			register_allianzenID,
			
			statusStatus
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = player_allianzenID
		WHERE
			planetenID = ".$_GET['id']."
		ORDER BY planetenID ASC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit dieser ID existiert
	if(mysql_num_rows($query)) {
		$data = mysql_fetch_assoc($query);
	}
	
	// Rechte ermitteln, den Planeten anzuzeigen
	show_planet_rechte($data);
	
	if($data['planetenUpdateOverview']) {
		$gor = explode('+', $data['planetenGebOrbit']);
		foreach($gor as $key=>$val) {
			if(isset($orbiter[$val])) {
				$gor[$key] = -3;
			}
		}
		$gor = implode('+', $gor);
		
		// speichern
		if($gor != $data['planetenGebOrbit']) {
			query("
				UPDATE ".PREFIX."planeten
				SET
					planetenGebOrbit = '".escape($gor)."',
					planetenOrbiter = 0
				WHERE
					planetenID = ".$_GET['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(13, 'löscht die Orbiter des Planeten '.$_GET['id']);
		}
	}
	
	// Ausgabe
	$tmpl->content = '<i>Orbiter gel&ouml;scht</i>';
}


// Ress auf 0 setzen
else if($_GET['sp'] == 'ress_del') {
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	
	$data = false;
	
	// Existenz und Berechtigung ermitteln
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			planetenUpdateOverview,
			
			systeme_galaxienID,
			
			player_allianzenID,
			
			register_allianzenID,
			
			statusStatus
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = player_allianzenID
		WHERE
			planetenID = ".$_GET['id']."
		ORDER BY planetenID ASC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit dieser ID existiert
	if(mysql_num_rows($query)) {
		$data = mysql_fetch_assoc($query);
	}
	
	// Rechte ermitteln, den Planeten anzuzeigen
	show_planet_rechte($data);
	
	if($data['planetenUpdateOverview']) {
		// speichern
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenRMErz = 0,
				planetenRMMetall = 0,
				planetenRMWolfram = 0,
				planetenRMKristall = 0,
				planetenRMFluor = 0,
				planetenRMGesamt = 0
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(13, 'setzt die Ress des Planeten '.$_GET['id'].' auf 0 zurück');
		}
	}
	
	// Ausgabe
	$tmpl->content = '<i>Ress auf 0 gesetzt</i>';
}



?>