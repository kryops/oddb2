<?php
/**
 * pages/scan/orbit.php
 * Orbit einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Flooding-Schutz 2 Minuten
if($c = $cache->get('scanorbit'.$_POST['id'])) {
	if($c == 2) $tmpl->error = 'Der Orbit wurde in den letzten 30 Sekunden schon eingescannt!';
	else $tmpl->error = 'Der Planet ist nicht eingetragen. Zuerst muss das System gescannt werden!';
	$tmpl->output();
	die();
}
// Flooding-Schutz in den Cache laden
$cache->set('scanorbit'.$_POST['id'], 2, 30);

// Daten sichern
$_POST['id'] = (int)$_POST['id'];
$_POST['frs'] = (int)$_POST['frs'];
$_POST['inhaber'] = (int)$_POST['inhaber'];

// Planetendaten ermitteln
$query = query("
	SELECT
		planeten_playerID,
		planeten_systemeID,
		planetenNatives,
		planetenMyrigate,
		
		systeme_galaxienID,
		
		schiffeBergbau,
		schiffeTerraformer
	FROM
		".PREFIX."planeten
		LEFT JOIN ".PREFIX."systeme
			ON systemeID = planeten_systemeID
		LEFT JOIN ".PREFIX."planeten_schiffe
			ON schiffe_planetenID = planetenID
	WHERE
		planetenID = ".$_POST['id']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Planet nicht eingetragen
if(!mysql_num_rows($query)) {
	$tmpl->error = 'Der Planet ist nicht eingetragen. Zuerst muss das System gescannt werden!';
	// im Cache ändern
	$cache->set('scanorbit'.$_POST['id'], 1, 30);
}
// Planet vorhanden
else {
	$data = mysql_fetch_assoc($query);
	
	// anderer Inhaber -> History
	// vorerst nur ändern, wenn eindeutiger Inhaber (Verschleierung)
	if($_POST['inhaber'] > 0 AND $data['planeten_playerID'] != $_POST['inhaber']) {
		query("
			INSERT INTO ".PREFIX."planeten_history
			SET
				history_planetenID = ".$_POST['id'].",
				history_playerID = ".$_POST['inhaber'].",
				historyLast = ".$data['planeten_playerID'].",
				historyTime = ".time()."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		query("
			UPDATE ".PREFIX."planeten
			SET
				planeten_playerID = ".$_POST['inhaber'].",
				planetenHistory = planetenHistory+1
			WHERE
				planetenID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// Natives aktualisieren
	if($data['planetenNatives'] != $_POST['frs'] OR (isset($_POST['natives']) XOR $data['planetenNatives'])) {
		// Zahl nicht erhöhen
		if($data['planetenNatives'] AND $_POST['frs'] > $data['planetenNatives']) {
			$_POST['frs'] = $data['planetenNatives'];
		}
		
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenNatives = ".(isset($_POST['natives']) ? $_POST['frs'] : "0")."
			WHERE
				planetenID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// Urlaubsmodus aktualisieren, falls er einen Inhaber hat
	if($_POST['inhaber'] > 3) {
		query("
			UPDATE ".GLOBPREFIX."player
			SET
				playerUmod = ".(isset($_POST['umod']) ? '1' : '0')."
			WHERE
				playerID = ".$_POST['inhaber']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	$registered = false;
	$opfer_ally = 0;
	if($_POST['inhaber'] > 3) {
		$query = query("
			SELECT
				user_allianzenID
			FROM ".PREFIX."user
			WHERE
				user_playerID = ".$_POST['inhaber']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$data2 = mysql_fetch_assoc($query);
			$registered = true;
			$opfer_ally = $data2['user_allianzenID'];
		}
	}
	
	// eingetragene Aktion ermitteln (Inva, Reso, Genesis, Besatzung, Kolo)
	$inva = false;
	
	$query = query("
		SELECT
			invasionenID,
			invasionenTyp,
			invasionenAggressor,
			invasionenEnde,
			invasionenOpen,
			invasionenFreundlich
		FROM ".PREFIX."invasionen
		WHERE
			invasionen_planetenID = ".$_POST['id']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	if(mysql_num_rows($query)) {
		$inva = mysql_fetch_assoc($query);
	}
	
	/*
		1 - Invasion
		2 - Resonation
		3 - Genesis-Projekt
		4 - Besatzung
		5 - Kolo
	*/
	
	// bei invalidem Invasionstyp Inva entfernen
	if(isset($_POST['inva'])) {
		$_POST['inva'] = (int)$_POST['inva'];
		if($_POST['inva'] < 1 OR $_POST['inva'] > 5) unset($_POST['inva']);
	}
	
	// Daten aufbereiten
	$opfer = $_POST['inhaber'];
	
	// Inhaber verschleiert
	if($opfer < 0) {
		$opfer = $data['planeten_playerID'];
	}
	
	if(isset($_POST['inva'])) {
		// Ende
		if($_POST['inva'] != 4) {
			$ende = (int)strtotime($_POST['ende']);
		}
		else $ende = 0;
		
		// Aggressor aktualisieren
		$aggr = 0;
		$aggr_ally = 0;
		
		if(isset($_POST['user'])) {
			$_POST['user'] = (int)$_POST['user'];
			if($_POST['user']) {
				odrequest($_POST['user']);
				
				$query = query("
					SELECT
						playerID,
						player_allianzenID
					FROM ".GLOBPREFIX."player
					WHERE
						playerID = ".$_POST['user']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				if(mysql_num_rows($query)) {
					$data2 = mysql_fetch_assoc($query);
					$aggr = $data2['playerID'];
					$aggr_ally = $data2['player_allianzenID'];
				}
				
			}
		}
	}
	
	// läuft eine andere Aktion als eingetragen?
	$other = (isset($_POST['inva'])
			AND $inva
			AND $inva['invasionenTyp'] != $_POST['inva']) ? true : false;
	
	// Invasion, Resonation, Genesis, Besatzung, Kolo löschen
	// (wenn keine AutoInva)
	if($other OR (!isset($_POST['inva']) AND $inva AND ($inva['invasionenEnde'] OR $inva['invasionenTyp'] == 4))) {
		if($inva['invasionenTyp'] != 5) {
			// falls keine Kolo, Aktion ins Archiv verschieben
			inva_archiv($inva['invasionenID'], 'löscht die Aktion durch Einscannen des Orbits');
		}
		else {
			// ansonsten nur Eintrag aus den Invasionen löschen
			query("
				DELETE FROM ".PREFIX."invasionen
				WHERE
					invasionenID = ".$inva['invasionenID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	if(isset($_POST['inva'])) {
		// fremde Aktion?
		$fremd = $registered ? 0 : 1;
		// offen, wenn Opfer registriert und keine Kolo
		$open = ($registered AND $_POST['inva'] != 5) ? 1 : 0;
		// freundlich, wenn Aggressor und Opfer in derselben Ally
		$freundlich = 0;
		if($aggr_ally AND $aggr_ally == $opfer_ally) {
			$freundlich = 1;
			$open = 0;
		}
	}
	
	// Invasion, Resonation, Genesis, Besatzung, Kolo eintragen
	if($other OR (isset($_POST['inva']) AND !$inva)) {
		// bei Kolo immer frei
		if($_POST['inva'] == 5) {
			$opfer = 0;
		}
		
		// Aktion eintragen
		query("
			INSERT INTO ".PREFIX."invasionen
			SET
				invasionenTime = ".time().",
				invasionen_playerID = ".$opfer.",
				invasionen_planetenID = ".$_POST['id'].",
				invasionen_systemeID = ".$data['planeten_systemeID'].",
				invasionenTyp = ".$_POST['inva'].",
				invasionenFremd = ".$fremd.",
				invasionenOpen = ".$open.",
				invasionenFreundlich = ".$freundlich.",
				invasionenAggressor = ".$aggr.",
				invasionenEnde = ".$ende.",
				invasionenSchiffe = ".$_POST['frs']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// falls keine Kolo, InvaLog-Eintrag
		if($_POST['inva'] != 5) {
			$id = mysql_insert_id();
			
			query("
				INSERT INTO ".PREFIX."invasionen_log
				SET
					invalog_invasionenID = ".$id.",
					invalogTime = ".time().",
					invalog_playerID = ".$user->id.",
					invalogText = 'erfasst die Aktion durch Einscannen des Orbits'
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		// Aggressor-Aktivität aktualisieren
		if($aggr) {
			query("
				UPDATE ".GLOBPREFIX."player
				SET
					playerActivity = ".time()."
				WHERE
					playerID = ".$aggr."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		// offene Invasionen aus dem Cache löschen
		$cache->remove('openinvas');
		
		if(!isset($_GET['plugin']) AND $user->rechte['invasionen']) {
			$tmpl->script = 'openinvas();';
		}
	}
	
	// Invasion aktualisieren
	if(!$other AND isset($_POST['inva']) AND $inva) {
		// manuell gesetzte Werte nicht überschreiben
		if(!$freundlich) {
			$freundlich = $inva['invasionenFreundlich'];
		}
		if($open) {
			$open = $inva['invasionenOpen'];
		}
		
		query("
			UPDATE ".PREFIX."invasionen
			SET
				invasionenTime = ".time().",
				invasionenAggressor = ".$aggr.",
				invasionenEnde = ".$ende.",
				invasionenSchiffe = ".$_POST['frs'].",
				invasionenOpen = ".$open.",
				invasionenFreundlich = ".$freundlich."
			WHERE
				invasionenID = ".$inva['invasionenID']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	
	// Sprunggenerator
	$sprung = isset($_POST['sprung']);
	// nur anwenden, wenn kein richtiges Myrigate eingetragen ist
	if($data['planetenMyrigate'] <= 2) {
		// Sprunggenerator eintragen
		if($sprung AND !$data['planetenMyrigate']) {
			query("
				UPDATE ".PREFIX."planeten
				SET
					planetenMyrigate = 2
				WHERE
					planetenID = ".$_POST['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			query("
				INSERT INTO ".PREFIX."myrigates
				SET
					myrigates_planetenID = ".$_POST['id'].",
					myrigates_galaxienID = ".$data['systeme_galaxienID'].",
					myrigatesSprung = ".time()."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		// Sprunggenerator entfernen
		else if(!$sprung AND $data['planetenMyrigate'] == 2) {
			query("
				UPDATE ".PREFIX."planeten
				SET
					planetenMyrigate = 0
				WHERE
					planetenID = ".$_POST['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			query("
				DELETE FROM ".PREFIX."myrigates
				WHERE
					myrigates_planetenID = ".$_POST['id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		// Sprunggenerator aktualisieren
		else if($sprung) {
			query("
				UPDATE ".PREFIX."myrigates
				SET
					myrigatesSprung = ".time()."
				WHERE
					myrigates_planetenID = ".$_POST['id']."
					AND myrigatesSprung > 0
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	// Bergbau eintragen/aktualisieren
	// nur aktualisieren, wenn über Orbit eingescannt
	if(isset($_POST['bb']) AND ($data['schiffeBergbau'] === NULL OR $data['schiffeBergbau'] == -1)) {
		query("
			INSERT INTO ".PREFIX."planeten_schiffe
			SET
				schiffe_planetenID = ".$_POST['id'].",
				schiffeBergbau = -1,
				schiffeBergbauUpdate = ".time()."
			ON DUPLICATE KEY UPDATE
				schiffeBergbau = -1,
				schiffeBergbauUpdate = ".time()."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	// Bergbau austragen, wenn über Orbit eingescannt
	else if(!isset($_POST['bb']) AND $data['schiffeBergbau'] == -1) {
		query("
			UPDATE ".PREFIX."planeten_schiffe
			SET
				schiffeBergbau = NULL,
				schiffeBergbauUpdate = 0
			WHERE
				schiffe_planetenID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// Terraformer eintragen
	if(isset($_POST['tf'])) {
		query("
			INSERT INTO ".PREFIX."planeten_schiffe
			SET
				schiffe_planetenID = ".$_POST['id'].",
				schiffeTerraformer = 1,
				schiffeTerraformerUpdate = ".time()."
			ON DUPLICATE KEY UPDATE
				schiffeTerraformer = 1,
				schiffeTerraformerUpdate = ".time()."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	// Terraformer austragen
	else if($data['schiffeTerraformer']) {
		query("
			UPDATE ".PREFIX."planeten_schiffe
			SET
				schiffeTerraformer = NULL,
				schiffeTerraformerUpdate = 0
			WHERE
				schiffe_planetenID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(4, 'scannt den Orbit vom Planet '.$_POST['id'].' ein');
	}
	
	// Ausgabe
	$tmpl->content = 'Orbit erfolgreich eingescannt';
}



?>