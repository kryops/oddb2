<?php
/**
 * pages/scan/poview.php
 * Planetenübersicht einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Flooding-Schutz 5 Minuten
if($cache->get('scanpoview'.$_POST['uid']) AND !isset($_GET['force'])) {
	$tmpl->error = 'Die Planeten&uuml;bersicht wurde in den letzten 5 Minuten schon eingescannt!';
	$tmpl->output();
	die();
}
else if((int)$_POST['uid'] <= 2) {
	$tmpl->error = 'Ung&uuml;ltige ID &uuml;bertragen!';
	$tmpl->output();
	die();
}
// Flooding-Schutz in den Cache laden
$cache->set('scanpoview'.$_POST['uid'], 1, 300);


// Terraformer-Cointainer
$terraformer_set = array();
$terraformer_unset = array();


// Daten sichern
$_POST['uid'] = (int)$_POST['uid'];
foreach($_POST['pl'] as $key=>$data) {
	$_POST['pl'][$key]['id'] = (int)$data['id'];
	$_POST['pl'][$key]['sid'] = (int)$data['sid'];
	$_POST['pl'][$key]['name'] = escape(html_entity_decode($data['name'], ENT_QUOTES, 'utf-8'));
	$_POST['pl'][$key]['gr'] = (int)$data['gr'];
	
	// Resswerte
	$_POST['pl'][$key]['rw'] = explode('X', $_POST['pl'][$key]['rw']);
	for($i=0; $i<4; $i++) {
		$_POST['pl'][$key]['rw'][$i] = isset($_POST['pl'][$key]['rw'][$i]) ? (int)$_POST['pl'][$key]['rw'][$i] : 0;
	}
	
	// Ressvorrat
	$_POST['pl'][$key]['rv'] = explode('X', $_POST['pl'][$key]['rv']);
	for($i=0; $i<5; $i++) {
		$_POST['pl'][$key]['rv'][$i] = isset($_POST['pl'][$key]['rv'][$i]) ? (int)$_POST['pl'][$key]['rv'][$i] : 0;
	}
	
	// Oberfläche
	$_POST['pl'][$key]['scr'] = str_replace(
		array('X', 'Y'),
		array('&s', '='),
		$_POST['pl'][$key]['scr']
	);
	
	parse_str($_POST['pl'][$key]['scr'], $data);

	$_POST['pl'][$key]['typ'] = (int)$data['typ'];
	$_POST['pl'][$key]['gpl'] = array();
	$_POST['pl'][$key]['gor'] = array();

	for($i=1; $i<=36; $i++) {
		if(!isset($data['s'.$i])) {
			break;
		}
		
		$_POST['pl'][$key]['gpl'][] = $data['s'.$i];
	}
	
	for($i=37; $i<=48; $i++) {
		if(!isset($data['s'.$i])) {
			break;
		}
		
		$_POST['pl'][$key]['gor'][] = $data['s'.$i];
	}

	$_POST['pl'][$key]['gpl'] = implode('+', $_POST['pl'][$key]['gpl']);
	$_POST['pl'][$key]['gor'] = implode('+', $_POST['pl'][$key]['gor']);
	
	
	// laufende Aktionen mappen
	if(isset($_POST['pl'][$key]['inva'])) {
		
		// Terraformer 
		if(strpos($_POST['pl'][$key]['inva'], 'TF') !== false) {
			$terraformer_set[] = $_POST['pl'][$key]['id'];
		}
		else {
			$terraformer_unset[] = $_POST['pl'][$key]['id'];
		}
		
		
		if(strpos($_POST['pl'][$key]['inva'], 'Inv') !== false) {
			$_POST['pl'][$key]['inva'] = 1;
		}
		else if(strpos($_POST['pl'][$key]['inva'], 'Res') !== false) {
			$_POST['pl'][$key]['inva'] = 2;
		}
		else if(strpos($_POST['pl'][$key]['inva'], 'Gen') !== false) {
			$_POST['pl'][$key]['inva'] = 3;
		}
		else if(strpos($_POST['pl'][$key]['inva'], 'Bes') !== false) {
			$_POST['pl'][$key]['inva'] = 4;
		}
		else {
			unset($_POST['pl'][$key]['inva']);
		}
	}
	// keine laufende Aktion -> Terraformer entfernen
	else {
		$terraformer_unset[] = $_POST['pl'][$key]['id'];
	}
}

// IN()-String der Planeten-IDs erzeugen
$plids = array();
foreach($_POST['pl'] as $data) {
	$plids[] = $data['id'];
}
$plids = implode(', ', $plids);

// Planeten des Spielers und neue abfragen
$query = query("
	SELECT
		planetenID,
		planeten_playerID
	FROM ".PREFIX."planeten
	WHERE
		planetenID IN (".$plids.")
		OR planeten_playerID = ".$_POST['uid']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$pl = array();

while($row = mysql_fetch_assoc($query)) {
	$pl[$row['planetenID']] = $row['planeten_playerID'];
}

// Ist der Spieler in der DB angemeldet?
// eigene Planetenübersicht
if($_POST['uid'] == $user->id) {
	$registered = true;
	$uname =& $user->name;
}
else {
	$registered = false;
	$query = query("
		SELECT
			user_playerName
		FROM ".PREFIX."user
		WHERE
			user_playerID = ".$_POST['uid']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	if(mysql_num_rows($query)) {
		$registered = true;
		$data = mysql_fetch_assoc($query);
		$uname = $data['user_playerName'];
	}
}

// Invasionen der Planeten abfragen
$invas = array();

$query = query("
	SELECT
		invasionenID,
		invasionen_planetenID,
		invasionenTyp
	FROM ".PREFIX."invasionen
	WHERE
		invasionen_planetenID IN (".$plids.")
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

while($row = mysql_fetch_assoc($query)) {
	$invas[$row['invasionen_planetenID']] = array($row['invasionenID'], $row['invasionenTyp']);
}

$sys = array();

// Planeten durchgehen
foreach($_POST['pl'] as $data) {
	// nur aktualisieren, wenn Planet in der DB vorhanden
	if(isset($pl[$data['id']])) {
		// Schiffbau ermitteln
		$schiff = '';
		if(isset($data['schiff'])) {
			$schiff = ", planetenWerftFinish = ".(time()+(int)$data['schiff']);
		}
		
		// Kategorie ermitteln
		$gor = explode('+', $data['gor']);
		$cat = categorize(explode('+', $data['gpl']), $gor, $data['gr']);
		
		// Orbiter einstufen
		$orb = 0;
		
		// Orbiter-Stufe
		foreach($gor as $geb) {
			if(isset($orbiter[$geb]) AND $orbiter[$geb] > $orb) {
				$orb = $orbiter[$geb];
			}
		}
		
		// Inhaberwechsel
		if($pl[$data['id']] != $_POST['uid']) {
			// History-Eintrag
			query("
				INSERT INTO ".PREFIX."planeten_history
				SET
					history_planetenID = ".$data['id'].",
					history_playerID = ".$_POST['uid'].",
					historyLast = ".$pl[$data['id']].",
					historyTime = ".time()."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$his = 'planetenHistory = planetenHistory+1,';
			
			// im System vertretene Allianzen aktualisieren
			if(!in_array($data['sid'], $sys)) {
				$allies = ", systemeAllianzen = '".sysallianzen($data['sid'], true)."'";
			}
			else $allies = '';
		}
		// bei gleichem Inhaber Allianzen im System nicht aktualisieren
		else {
			$allies = '';
			$his = '';
		}
		
		// Planeten aktualisieren
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenUpdateOverview = ".time().",
				planeten_playerID = ".$_POST['uid'].",
				planetenName = '".$data['name']."',
				planetenTyp = ".$data['typ'].",
				planetenGroesse = ".$data['gr'].",
				planetenRWErz = ".$data['rw'][0].",
				planetenRWWolfram = ".$data['rw'][1].",
				planetenRWKristall = ".$data['rw'][2].",
				planetenRWFluor = ".$data['rw'][3].",
				planetenRMErz = ".$data['rv'][0].",
				planetenRMMetall = ".$data['rv'][1].",
				planetenRMWolfram = ".$data['rv'][2].",
				planetenRMKristall = ".$data['rv'][3].",
				planetenRMFluor = ".$data['rv'][4].",
				planetenRMGesamt = ".array_sum($data['rv']).",
				planetenGebPlanet = '".$data['gpl']."',
				planetenGebOrbit = '".$data['gor']."',
				planetenOrbiter = ".$orb.",
				".$his."
				planetenKategorie = ".$cat."
				".$schiff."
			WHERE
				planetenID = ".$data['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// bei invalidem Invasionstyp Inva entfernen
		if(isset($data['inva'])) {
			$data['inva'] = (int)$data['inva'];
			if(!$data['inva'] OR $data['inva'] > 5) unset($data['inva']);
		}
		
		// läuft eine andere Aktion als eingetragen?
		$other = (isset($data['inva'])
				AND isset($invas[$data['id']])
				AND $invas[$data['id']][1] != $data['inva']) ? true : false;
		
		// Invasion, Resonation, Genesis, Besatzung löschen
		if($other OR (!isset($data['inva']) AND isset($invas[$data['id']]))) {	
			// Aktion ins Archiv verschieben
			if($invas[$data['id']][1] != 5) {
				inva_archiv($invas[$data['id']][0], 'löscht die Aktion durch Einscannen der Planetenübersicht');
			}
			// Kolo nur löschen
			else {
				query("
						DELETE FROM ".PREFIX."invasionen
						WHERE
							invasionenID = ".$invas[$data['id']][0]."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
		}
		// Invasion, Resonation, Genesis, Besatzung eintragen
		if($other OR (isset($data['inva']) AND !isset($invas[$data['id']]))) {
			/*
				1 - Invasion
				2 - Resonation
				3 - Genesis-Projekt
				4 - Besatzung
				5 - Kolo
			*/
			
			// alles außer fremde Besatzungen eintragen
			if($registered OR $data['inva'] != 4) {
				// fremde Aktion?
				$fremd = $registered ? 0 : 1;
				// offen, wenn Opfer registriert
				$open = $registered ? 1 : 0;
				
				// Aktion eintragen
				query("
					INSERT INTO ".PREFIX."invasionen
					SET
						invasionenTime = ".time().",
						invasionen_playerID = ".$_POST['uid'].",
						invasionen_planetenID = ".$data['id'].",
						invasionen_systemeID = ".$data['sid'].",
						invasionenTyp = ".$data['inva'].",
						invasionenFremd = ".$fremd.",
						invasionenOpen = ".$open."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$id = mysql_insert_id();
				
				// InvaLog-Eintrag
				query("
					INSERT INTO ".PREFIX."invasionen_log
					SET
						invalog_invasionenID = ".$id.",
						invalogTime = ".time().",
						invalog_playerID = ".$user->id.",
						invalogText = 'erfasst die Aktion durch Einscannen der Planetenübersicht'
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// offene Invasionen aus dem Cache löschen
				$cache->remove('openinvas');
				
				if(!isset($_GET['plugin']) AND $user->rechte['invasionen']) {
					$tmpl->script = 'openinvas();';
				}
			}
		}
		
		
		// Planet aus Liste löschen (damit die übrigen auf unbekannt gesetzt werden können)
		unset($pl[$data['id']]);
	}
}

// ODRequest absetzen
odrequest($_POST['uid']);

// Planetenzahl des Spielers aktualisieren
query("
	UPDATE ".GLOBPREFIX."player
	SET
		playerPlaneten = '".count($_POST['pl'])."'
	WHERE
		playerID = ".$_POST['uid']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Aktualisierungszeitpunkt eintragen
if($registered) {
	query("
		UPDATE ".PREFIX."user
		SET
			userOverviewUpdate = ".time()."
		WHERE
			user_playerID = ".$_POST['uid']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

// übrige Planeten des Spielers auf unbekannt setzen
if(count($pl)) {
	query("
		UPDATE ".PREFIX."planeten
		SET
			planeten_playerID = -1,
			planetenHistory = planetenHistory+1
		WHERE
			planetenID IN (".implode(', ', array_keys($pl)).")
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// History-Einträge
	$vals = array();
	foreach($pl as $id=>$data) {
		$vals[] = '('.$id.', -1, '.$_POST['uid'].', '.time().')';
	}
	query("
		INSERT INTO ".PREFIX."planeten_history
		VALUES ".implode(', ', $vals)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

// Terraformer aktualisieren
if(count($terraformer_set)) {
	// bestehende Einträge aktualisieren
	query("
		UPDATE ".PREFIX."planeten_schiffe
		SET
			schiffeTerraformer = 1,
			schiffeTerraformerUpdate = ".time()."
		WHERE
			schiffe_planetenID IN(".implode(",", $terraformer_set).")
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// neue eintragen
	$sql = "
		INSERT IGNORE INTO
			".PREFIX."planeten_schiffe
			(schiffe_planetenID, schiffeTerraformer, schiffeTerraformerUpdate)
		VALUES
		";
	
	foreach($terraformer_set as $key=>$val) {
		$terraformer_set[$key] = "(".$val.", 1, ".time().")";
	}
	
	$sql .= implode(", ", $terraformer_set);
	
	query($sql) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

if(count($terraformer_unset)) {
	// Einträge entfernen
	query("
		UPDATE ".PREFIX."planeten_schiffe
		SET
			schiffeTerraformer = NULL
		WHERE
			schiffe_planetenID IN(".implode(",", $terraformer_unset).")
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}

// Log-Eintrag
if($config['logging'] >= 2) {
	// eigene Planetenübersicht
	if($_POST['uid'] == $user->id) {
		insertlog(4, 'scannt die eigene Planetenübersicht ein');
	}
	// Planetenübersicht von jemand anderem
	else {
		// falls nicht registriert, Namen noch fetchen
		if(!$registered) {
			$query = query("
				SELECT
					playerName
				FROM ".GLOBPREFIX."player
				WHERE
					playerID = ".$_POST['uid']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query)) {
				$data = mysql_fetch_assoc($query);
				$uname = $data['playerName'];
			}
		}
		
		if($registered OR mysql_num_rows($query)) {
			insertlog(4, 'scannt die Planetenübersicht von '.$uname.' ('.$_POST['uid'].') ein');
		}
	}
	
	// Ausgabe
	$tmpl->content = 'Planeten&uuml;bersicht erfolgreich eingescannt';
}



?>