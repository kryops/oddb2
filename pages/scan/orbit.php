<?php
/**
 * pages/scan/orbit.php
 * Orbit einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class ScanOrbit {
	
	/**
	 * Aktionstypen auf die Werte des Parsers mappen
	 * @var array
	 */
	public static $inva_map = array(
		1=>array('inv', 'invende', 'invauser'),
		2=>array('reso', 'resoende', false),
		3=>array('gen', 'genende', 'genuser'),
		4=>array('bes', false, 'besuser'),
		5=>array('kolo', 'koloende', 'kolouser')
	);
	
}



// Flooding-Schutz 2 Minuten
if($c = $cache->get('scanorbit'.$_POST['id']) AND !isset($_GET['force'])) {
	if($c == 2) $tmpl->error = 'Der Orbit wurde in den letzten 5 Minuten schon eingescannt!';
	else $tmpl->error = 'Der Planet ist nicht eingetragen. Zuerst muss das System gescannt werden!';
	$tmpl->output();
	die();
}
// Flooding-Schutz in den Cache laden
$cache->set('scanorbit'.$_POST['id'], 2, 300);

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
	
	// Inhaber-Verschleierung umgehen
	$inhaber = ($_POST['inhaber'] >= 0) ? $_POST['inhaber'] : $data['planeten_playerID'];
	
	
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
				planetenHistory = planetenHistory+1,
				planetenGebSpezial = '".GEBAEUDE_EMPTYSPEZIAL."'
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
	if($inhaber > 3) {
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
	if($inhaber > 3) {
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
	
	
	/*
	 * laufende Aktionen
	 */
	
	$openinvas_reset = false;
	
	// eingetragene Aktionen ermitteln (Inva, Reso, Genesis, Besatzung, Kolo)
	$inva = array();
	
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
	
	while($row = mysql_fetch_assoc($query)) {
		$inva[$row['invasionenTyp']] = $row;
	}
	
	// Daten aufbereiten
	$opfer = $inhaber;
	
	
	// laufende Aktionen durchgehen
	foreach(ScanOrbit::$inva_map as $ityp=>$ifields) {
		
		if(isset($_POST[$ifields[0]])) {
			
			// Ende
			if($ifields[1] AND isset($_POST[$ifields[1]])) {
				$ende = (int)strtotime($_POST[$ifields[1]]);
			}
			else {
				$ende = 0;
			}
				
			// Aggressor
			$aggr = 0;
			$aggr_ally = 0;
			
			if($ifields[2] AND isset($_POST[$ifields[2]])) {
				$aggr = (int)$_POST[$ifields[2]];
				
				if($aggr) {
					odrequest($aggr);
					
					$query = query("
						SELECT
							playerID,
							player_allianzenID
						FROM ".GLOBPREFIX."player
						WHERE
							playerID = ".$aggr."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					if(mysql_num_rows($query)) {
						$data2 = mysql_fetch_assoc($query);
						$aggr_ally = $data2['player_allianzenID'];
					}
					
				}
			}
			
			// fremde Aktion?
			$fremd = $registered ? 0 : 1;
			
			// offen, wenn Opfer registriert und keine Kolo
			$open = ($registered AND $ityp != 5) ? 1 : 0;
			
			// freundlich, wenn Aggressor und Opfer in derselben Ally
			$freundlich = 0;
			if($aggr_ally AND $aggr_ally == $opfer_ally) {
				$freundlich = 1;
				$open = 0;
			}
			
			
			// neu eintragen (noch nicht eingetragen oder anderer Aggressor)
			if(!isset($inva[$ityp]) OR ($inva[$ityp]['invasionenAggressor'] AND $inva[$ityp]['invasionenAggressor'] != $aggr)) {
				
				query("
					INSERT INTO ".PREFIX."invasionen
					SET
						invasionenTime = ".time().",
						invasionen_playerID = ".$opfer.",
						invasionen_planetenID = ".$_POST['id'].",
						invasionen_systemeID = ".$data['planeten_systemeID'].",
						invasionenTyp = ".$ityp.",
						invasionenFremd = ".$fremd.",
						invasionenOpen = ".$open.",
						invasionenFreundlich = ".$freundlich.",
						invasionenAggressor = ".$aggr.",
						invasionenEnde = ".$ende.",
						invasionenSchiffe = ".$_POST['frs']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$id = mysql_insert_id();
				$id = inva_autoIncrement($id);
				
				// InvaLog-Eintrag
				query("
					INSERT INTO ".PREFIX."invasionen_log
					SET
						invalog_invasionenID = ".$id.",
						invalogTime = ".time().",
						invalog_playerID = ".$user->id.",
						invalogText = 'erfasst die Aktion durch Einscannen des Orbits'
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
			}
			// Aktion aktualisieren
			else {
				
				$oldinva = $inva[$ityp];
				
				// manuell gesetzte Werte nicht überschreiben
				if(!$freundlich) {
					$freundlich = $oldinva['invasionenFreundlich'];
				}
				if($open) {
					$open = $oldinva['invasionenOpen'];
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
						invasionenID = ".$oldinva['invasionenID']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// aus dem Array der zu archivierenden Aktionen löschen
				unset($inva[$ityp]);
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
			
			$openinvas_reset = true;
			
		}
		
	}
	
	
	// nicht mehr laufende Aktionen archivieren
	foreach($inva as $ityp=>$row) {
		
		// Kolos nur löschen
		if($ityp == 5) {
			query("
				DELETE FROM ".PREFIX."invasionen
				WHERE
					invasionenID = ".$row['invasionenID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			query("
				DELETE FROM ".PREFIX."invasionen_log
				WHERE
					invalog_invasionenID = ".$row['invasionenID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		else {
			inva_archiv($row['invasionenID'], 'löscht die Aktion durch Einscannen des Orbits');
		}
		
		$openinvas_reset = true;
		
	}
	
	// Anzeige offener Invasionen zurücksetzen
	if($openinvas_reset) {
		// offene Invasionen aus dem Cache löschen
		$cache->remove('openinvas');
		
		if(!isset($_GET['plugin']) AND $user->rechte['invasionen']) {
			$tmpl->script = 'openinvas();';
		}
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