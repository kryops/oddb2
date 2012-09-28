<?php
/**
 * pages/fow.php
 * FoW-Ausgleich
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


General::loadClass('Rechte');


/**
 * Funktionen
 */

/**
 * generelles Output der XML-Struktur
 * @param $auth bool Berechtigung
 * @param $scandate string Datum des Scans / Berechtigungs-Meldung
 * @param $planinfo string Planeten-Informationen
 * @param $sysinfo string System-Informationen
 *
 * @return xmlstring Ausgabe
 */
function fowoutput($scandate, $planinfo, $sysinfo) {
	global $oddb, $time_start, $queries, $charset;
	
	// Verarbeitungszeit berechnen
	$time = number_format(microtime(true)-$time_start, 6);
	
	// in ISO-8859-1 kodieren, wenn nicht das ODDB Tool benutzt wird
	if(!$oddb) {
		$planinfo = utf8_decode($planinfo);
		$sysinfo = utf8_decode($sysinfo);
	}
	
	$out = '<?xml version="1.0" encoding="'.$charset.'" standalone="yes" ?>
<odh:odhelper xmlns="http://unzureichende.info/odhelp/ns/fog.of.war/2007" xmlns:odh="http://unzureichende.info/odhelp/ns/api">
  <odh:head>
    <odh:auth>true</odh:auth>
    <odh:status>200</odh:status>
    <odh:version>1.0</odh:version>
  </odh:head>
  <odh:data>
	<system sid="'.(isset($_GET['id']) ? (int)$_GET['id'] : '0').'">
	<comment></comment>
	'.$scandate.'
	'.$planinfo.'
	  <systemInfo>'.$sysinfo.'</systemInfo>
	</system>
 </odh:data>
 <time><![CDATA['.$time.']]></time>
 <queries><![CDATA['.$queries.']]></queries>
</odh:odhelper>';
	
	// Debug-Ausgabe der MySQL-Queries
	if(DEBUG) {
		global $mysql_stack;
		if($mysql_stack !== NULL) {
			$out .= '<!--
'.print_r($mysql_stack, true).'
-->';
		}
	}
	
	return $out;
}
/**
 * gibt einen Fehler aus
 * @param $err string Fehlermeldung
 */
function fowerror($err) {
	global $oddb, $user, $status, $status_color;
	
	// Kompatibilität zum ODH beibehalten
	if(!$oddb) {
		$err = '<tr>
	  <td><![CDATA['.$err.']]></td>
	</tr>';
	}
	else $err = '<![CDATA[<div align="center">'.$err.'</div>]]>';
	
	// Allianz-Status
	$additional = '';
	$fowstatus = array();
	
	// im System vertretene Allianzen abfragen
	if(isset($_GET['status'])) {
		// Daten aufbereiten
		$st = explode('+', $_GET['status']);
		foreach($st as $key=>$val) {
			$val = (int)$val;
			if($val > 0) $st[$key] = $val;
			else unset($st[$key]);
		}
		ksort($st);
		
		// Status abfragen, wenn nicht gleich wie bei der Planetenabfrage
		if(count($st)) {
			// abfragen
			$query = query("
				SELECT
					status_allianzenID,
					statusStatus
				FROM
					".PREFIX."allianzen_status
				WHERE
					statusDBAllianz = ".$user->allianz."
					AND status_allianzenID IN(".implode(', ', $st).")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$fowstatus[$row['status_allianzenID']] = $row['statusStatus'];
				
				// aus dem Array entfernen
				unset($st[array_search($row['status_allianzenID'], $st)]);
			}
			
			// nicht gefundene auf neutral setzen
			foreach($st as $val) {
				$fowstatus[$val] = 0;
			}
		}
	}

			
	foreach($fowstatus as $ally=>$allystatus) {
		// neutral
		if($allystatus == NULL) $allystatus = 0;
		
		if(isset($status[$allystatus]) AND $ally != $user->allianz) {
			$additional .= '
		<status'.$ally.'><![CDATA[<span '.$status_color[$allystatus].'>('.$status[$allystatus].')</span>]]></status'.$ally.'>';
		}
	}
	
	// Urlaubsmodus abfragen
	if(isset($_GET['umod'])) {
		// Daten aufbereiten
		$umod = explode('+', $_GET['umod']);
		
		
		foreach($umod as $key=>$val) {
			$val = (int)$val;
			if($val > 0) $umod[$key] = $val;
			else unset($umod[$key]);
		}
		
		// Status abfragen, wenn nicht gleich wie bei der Planetenabfrage
		if(count($umod)) {
			// abfragen
			$query = query("
				SELECT
					playerID
				FROM
					".GLOBPREFIX."player
				WHERE
					playerID IN(".implode(', ', $umod).")
					AND playerUmod = 1
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$additional .= '
		<umod'.$row['playerID'].'>1</umod'.$row['playerID'].'>';
			}
		}
	}
	
	
	// ausgeben und anhalten
	die(fowoutput('<scanDate current="2"></scanDate>', $additional, $err));
}

/**
 * FoW-systemInfo-Zelle generieren
 * @param $content string Inhalt der Zelle
 * @param $th bool (default false) headline-Zelle
 * @param $colspan int (default 0)
 * @param $href string Link-Adresse
 *
 * @return xml-string
 */
function fowcell($content, $th=false, $colspan=0, $href=false, $title='') {
	global $oddb, $charset;
	
	$colspan = $colspan ? ' colspan="'.$colspan.'"' : '';
	
	// beim ODDB Tool ohne CDATA
	if($oddb) {
		
		if($title != '') {
			$title = ' title="'.$title.'"';
		}
		
		// Link
		if($href !== false) {
			$content = '<a href="'.$href.'"'.$title.'>'.$content.'</a>';
		}
		return ($th ? '<th'.$colspan.$title.'>' : '<td'.$colspan.$title.'>').$content.($th ? '</th>' : '</td>');
	}
	// ansonsten mit CDATA, htmlentities rückgängig machen
	else {
		// Link
		$href = ($href !== false) ? ' href="'.htmlspecialchars($href, ENT_COMPAT, $charset, false).'"' : '';
		return ($th ? '<th'.$colspan.$href.'>' : '<td'.$colspan.$href.'>').'<![CDATA['.html_entity_decode($content, ENT_COMPAT, $charset).']]>'.($th ? '</th>' : '</td>');
	}
}

/**
 * Seiten
 */

// normales FoW-Output oder spezielles ODDB-Output
$oddb = isset($_GET['oddb']) ? true : false;

// daraus resultierender Zeichensatz
$charset = $oddb ? 'UTF-8' : 'ISO-8859-1';

// XML-Header
header('Content-Type:text/xml; charset='.$charset);

// keine Berechtigung
if(!$user->rechte['fow']) {
	fowerror('Du hast keine Berechtigung!');
}

// keine ID übergeben
if(!isset($_GET['id']) OR !is_numeric($_GET['id'])) {
	fowerror('Keine ID übergeben!');
}

// ODDB Tool veraltet
if(isset($_GET['version']) AND $_GET['version'] != ODDBTOOL) {
	
	$path = strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') ? ODDBTOOLPATH_CHROME : ODDBTOOLPATH;
	
	fowerror('ODDB Tool veraltet!<br /><a href="'.ADDR.'plugin/'.$path.'" style="color:#ffff00">[neue Version installieren]</a>');
}

// Daten sichern
$_GET['id'] = (int)$_GET['id'];


// System-Daten abfragen
$query = query("
	SELECT
		systemeName,
		systemeUpdateHidden,
		systemeUpdate,
		systemeX,
		systemeY,
		systemeZ,
		systeme_galaxienID,
		systemeGateEntf,
		systemeScanReserv,
		systemeReservUser,
		
		galaxienGate,
		galaxienGateSys
	FROM
		".PREFIX."systeme
		LEFT JOIN ".PREFIX."galaxien
			ON galaxienID = systeme_galaxienID
	WHERE
		systemeID = ".$_GET['id']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$data = false;

if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);

// das System existiert nicht
if(!$data) {
	// leeres System
	if(isset($_GET['plcount']) AND !$_GET['plcount']) {
		fowerror('Das System wurde nicht gefunden! Leere Systeme werden aus der DB gel&ouml;scht.');
	}
	else {
		fowerror('Das System wurde nicht gefunden! M&ouml;glicherweise wurde die Galaxie noch nicht eingetragen.');
	}
}

$pldata = array();
$fowstatus = array();

if($data['systemeUpdateHidden']) {
	// Planeten-Daten abfragen
	$query = query("
		SELECT
			planetenID,
			planetenName,
			planetenPosition,
			planetenUpdateOverview,
			planetenUpdate,
			planetenUnscannbar,
			planetenTyp,
			planetenGroesse,
			planetenBevoelkerung,
			planetenGebPlanet,
			planetenGebOrbit,
			planetenMyrigate,
			planetenRWErz,
			planetenRWWolfram,
			planetenRWKristall,
			planetenRWFluor,
			planetenRMErz,
			planetenRMMetall,
			planetenRMWolfram,
			planetenRMKristall,
			planetenRMFluor,
			planetenKommentar,
			planetenGeraidet,
			planetenGetoxxt,
			planetenNatives,
			planeten_playerID,
			
			playerName,
			playerRasse,
			player_allianzenID,
			playerUmod,
			
			allianzenTag,
			allianzenName,
			
			register_allianzenID,
			
			schiffeBergbau,
			schiffeTerraformer,
			
			statusStatus
		FROM
			".PREFIX."planeten
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = allianzenID
			LEFT JOIN ".PREFIX."planeten_schiffe
				ON schiffe_planetenID = planetenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = allianzenID
		WHERE
			planeten_systemeID = ".$_GET['id']."
		ORDER BY
			planetenID ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$pldata[$row['planetenPosition']] = $row;
		
		// Status an das Array hängen
		if($row['player_allianzenID'] AND !isset($fowstatus[$row['player_allianzenID']])) {
			$fowstatus[$row['player_allianzenID']] = $row['statusStatus'];
		}
	}
}

$showr = true;
$showerror = '';

// Berechtigungen ermitteln
// Galaxie gesperrt
if($user->protectedGalas AND in_array($data['systeme_galaxienID'], $user->protectedGalas)) {
	fowerror('Du hast keine Berechtigung, Systeme der Galaxie '.$data['systeme_galaxienID'].' anzeigen zu lassen!');
}

$allyplanet = false;
foreach($pldata as $row) {
	// Allianzplanet
	if($user->allianz AND $row['player_allianzenID'] == $user->allianz) {
		// Berechtigung -> System jetzt auf jeden Fall anzeigen
		if($user->rechte['show_system_ally']) {
			$allyplanet = true;
		}
		// Allianzsysteme gesperrt
		else {
			$showr = false;
			$showerror = 'Du hast keine Berechtigung, das System '.$_GET['id'].' anzeigen zu lassen!';
		}
	}
}

// Kein Allyplanet im System -> weitere Berechtigungen prüfen
if(!$allyplanet) {
	foreach($pldata as $row) {
		// keine Berechtigung (Allianz gesperrt)
		if($user->protectedAllies AND in_array($row['player_allianzenID'], $user->protectedAllies)) {
			//fowerror('Du hast keine Berechtigung, das System '.$_GET['id'].' anzeigen zu lassen!');
			$showr = false;
			$showerror = 'Du hast keine Berechtigung, das System '.$_GET['id'].' anzeigen zu lassen!';
		}
		// keine Berechtigung (Meta gesperrt)
		else if($user->allianz AND !$user->rechte['show_system_meta'] AND $row['statusStatus'] == $status_meta) {
			//fowerror('Du hast keine Berechtigung, das System '.$_GET['id'].' anzeigen zu lassen!');
			$showr = false;
			$showerror = 'Du hast keine Berechtigung, das System '.$_GET['id'].' anzeigen zu lassen!';
		}
		// keine Berechtigung (registrierte Allianzen)
		else if(!$user->rechte['show_planet_register'] AND $row['register_allianzenID'] !== NULL) {
			//fowerror('Du hast keine Berechtigung, das System '.$_GET['id'].' anzeigen zu lassen!');
			$showr = false;
			$showerror = 'Du hast keine Berechtigung, das System '.$_GET['id'].' anzeigen zu lassen!';
		}
	}
}

// Log-Eintrag
if($config['logging'] >=2 ) {
	insertlog(15, 'FoW System '.$_GET['id']);
}

// heutigen Timestamp ermitteln
$heute = strtotime('today');


// Myrigate im System
$mg = false;
$mg2 = false;



/**
 * Content erzeugen
 */

// Datum des System-Scans
if(!$showr) {
	$scan = '<scanDate current="2"><![CDATA[keine Berechtigung]]></scanDate>
	<systemUpdate>0</systemUpdate>';
}
else if(!$data['systemeUpdate']) {
	$scan = '<scanDate current="2"><![CDATA[nicht eingescannt]]></scanDate>
	<systemUpdate>0</systemUpdate>';
}
else {
	$scan = '<scanDate current="'.(($data['systemeUpdate'] > time()-$config['scan_veraltet']*86400) ? '1' : '2').'"><![CDATA['.($data['systemeUpdate'] > $heute ? 'heute' : strftime('%d.%m.%y', $data['systemeUpdate'])).']]></scanDate>
	<systemUpdate>'.$data['systemeUpdate'].'</systemUpdate>';
}

if($showr) {
	$sysallies = array_keys($fowstatus);
	ksort($sysallies);
}
// keine Berechtigung -> nur Status über GET anzeigen
else {
	$fowstatus = array();
	$sysallies = array();
}

// im System vertretene Allianzen abfragen
if(isset($_GET['status'])) {
	// Daten aufbereiten
	$st = explode('+', $_GET['status']);
	foreach($st as $key=>$val) {
		$val = (int)$val;
		if($val > 0) $st[$key] = $val;
		else unset($st[$key]);
	}
	$st = array_unique($st);
	ksort($st);
	
	// Status abfragen, wenn nicht gleich wie bei der Planetenabfrage
	if(count($st) AND $st != $sysallies) {
		// abfragen
		$query = query("
			SELECT
				status_allianzenID,
				statusStatus
			FROM
				".PREFIX."allianzen_status
			WHERE
				statusDBAllianz = ".$user->allianz."
				AND status_allianzenID IN(".implode(', ', $st).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$fowstatus[$row['status_allianzenID']] = $row['statusStatus'];
			
			// aus dem Array entfernen
			unset($st[array_search($row['status_allianzenID'], $st)]);
		}
		
		// nicht gefundene auf neutral setzen
		foreach($st as $val) {
			$fowstatus[$val] = 0;
		}
	}
}

		
foreach($fowstatus as $ally=>$allystatus) {
	// neutral
	if($allystatus == NULL) $allystatus = 0;
	
	if(isset($status[$allystatus]) AND $ally != $user->allianz) {
		$scan .= '
	<status'.$ally.'><![CDATA[<span '.$status_color[$allystatus].'>('.$status[$allystatus].')</span>]]></status'.$ally.'>';
	}
}

// Urlaubsmodus abfragen
if(isset($_GET['umod'])) {
	// Daten aufbereiten
	$umod = explode('+', $_GET['umod']);
	
	
	foreach($umod as $key=>$val) {
		$val = (int)$val;
		if($val > 0) $umod[$key] = $val;
		else unset($umod[$key]);
	}
	
	// Status abfragen, wenn nicht gleich wie bei der Planetenabfrage
	if(count($umod)) {
		// abfragen
		$query = query("
			SELECT
				playerID
			FROM
				".GLOBPREFIX."player
			WHERE
				playerID IN(".implode(', ', $umod).")
				AND playerUmod = 1
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$scan .= '
	<umod'.$row['playerID'].'>1</umod'.$row['playerID'].'>';
		}
	}
}

// Reservierung
if($data['systemeScanReserv'] > time()-86400) {
	$scan .= '
<reserv><![CDATA['.htmlspecialchars($data['systemeReservUser'], ENT_COMPAT, 'UTF-8').']]></reserv>';
}


if($showr AND ($user->rechte['invasionen'] OR $user->rechte['fremdinvakolos'])) {
	// laufende Invasionen etc ermitteln
	$ilabels = array(
		1=>'laufende Invasion',
		2=>'laufende Resonation',
		3=>'laufendes Genesis',
		4=>'laufende Besatzung',
		5=>'laufende Kolonisation'
	);

	$invasionen = array();
	
	$conds = array(
		"invasionen_systemeID = ".$_GET['id'],
		"(invasionenEnde > ".time()." OR invasionenEnde = 0)"
	);
	
	// Berechtigungen
	if(!$user->rechte['invasionen']) {
		$conds[] = "(invasionenFremd = 1 OR invasionenTyp = 5)";
	}
	if(!$user->rechte['fremdinvakolos']) {
		$conds[] = "(invasionenFremd = 0 OR invasionenTyp != 5)";
	}
	if($user->protectedAllies) {
		$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(", ", $user->protectedAllies)."))";
	}
	
	
	$query = query("
		SELECT
			invasionen_planetenID,
			invasionenTyp,
			invasionenEnde,
			
			playerName,
			allianzenTag
		FROM
			".PREFIX."invasionen
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = invasionenAggressor
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = player_allianzenID
		WHERE
			".implode(' AND ', $conds)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		if(!isset($invasionen[$row['invasionen_planetenID']])) {
			$invasionen[$row['invasionen_planetenID']] = array();
		}
		
		$invasionen[$row['invasionen_planetenID']][] = $row;
	}
}

// Planeten-Daten
$planeten = '';

// Galaxie übertragen
$planeten .= '
	<galaxie>'.$data['systeme_galaxienID'].'</galaxie>';

// globale Berechtigungen
$planeten .= '<toxxrechte>'.($user->rechte['toxxraid'] ? '1' : '0').'</toxxrechte>';


// Timestamp von letzter Woche ausrechnen
$lastweek = time()-604800;

if($showr) {
	// Planeten durchgehen
	for($i=1;$i<=7;$i++) {
		if(isset($pldata[$i])) {
			$pl =& $pldata[$i];
			
			// Berechtigung überprüfen, den Scan zu sehen
			$r = Rechte::getRechteShowPlanet($pl);
			
			// Planet bei der ODDB immer ausgeben, ansonsten nur wenn System oder Planet gescannt
			if($oddb OR $data['systemeUpdate'] OR $pl['planetenUpdate']) {
				$planeten .= '<planet pid="'.$pl['planetenID'].'" size="'.$pl['planetenGroesse'].'">';
				// keine Berechtigung
				if(!$r) {
					$planeten .= '
		<rechte>0</rechte>
		<scanDate current="2"><![CDATA[keine Berechtigung]]></scanDate>
		<scanOview>0</scanOview>
		<scanFailed>0</scanFailed>
		<scanImg></scanImg>';
				}
				// Planet noch nicht gescannt
				else if(!$pl['planetenUpdateOverview']) {
					$planeten .= '
		<rechte>1</rechte>
		<scanDate current="2"><![CDATA[noch nicht gescannt]]></scanDate>
		<scanOview>0</scanOview>
		<scanFailed>'.$pl['planetenUnscannbar'].'</scanFailed>
		<scanImg></scanImg>';
				}
				else {
					$planeten .= '
		<rechte>1</rechte>
		<scanDate current="'.(($pl['planetenUpdateOverview'] > time()-$config['scan_veraltet']*86400) ? '1' : '2').'"><![CDATA['.($pl['planetenUpdateOverview'] > $heute ? 'heute' : strftime('%d.%m.%y', $pl['planetenUpdateOverview'])).']]></scanDate>
		<scanDateFull current="'.(($pl['planetenUpdate'] > time()-$config['scan_veraltet']*86400) ? '1' : '2').'"><![CDATA['.($pl['planetenUpdate'] > $heute ? 'heute' : strftime('%d.%m.%y', $pl['planetenUpdate'])).']]></scanDateFull>
		<scanOview>'.$pl['planetenUpdateOverview'].'</scanOview>
		<scanFailed>'.$pl['planetenUnscannbar'].'</scanFailed>
		<scanImg><![CDATA['.($oddb ? '' : odscreen($pl['planetenTyp'], $pl['planetenGebPlanet'], $pl['planetenGebOrbit'])).']]></scanImg>';
				}
				
				// Orbit
				// Gate
				if($pl['planetenID'] == $data['galaxienGate']) {
					$planeten .= '
		<orbit type="G"><![CDATA[Gate G'.$data['systeme_galaxienID'].']]></orbit>';
				}
				// Myrigate
				else if($pl['planetenMyrigate']) {
					// Berechtigungen ermitteln
					$rm = Rechte::getRechteShowMyrigate($pl);
					
					// Berechtigung
					if($rm) {
						// Myrigate zwischenspeichern -> weitere Abfrage unnötig
						if($mg === false AND ($pl['player_allianzenID'] == $user->allianz OR in_array($pl['statusStatus'], $status_freund) OR ($pl['planeten_playerID'] == 0 AND $pl['planetenMyrigate'] == 2))) {
							$mg = $pl;
						}
						
						// Sprunggenerator
						if($pl['planetenMyrigate'] == 2) {
							$planeten .= '
	<orbit type="S"><![CDATA[Sprunggenerator]]></orbit>';
						}
						// Myrigate
						else {
							$planeten .= '
	<orbit type="M"><![CDATA[Myrigate nach '.$pl['planetenMyrigate'].']]></orbit>';
						}
					}
				}
				
				$planeten .= '
		<population><![CDATA['.ressmenge($pl['planetenBevoelkerung']).']]></population>
		<erz>'.$pl['planetenRWErz'].'</erz>
		<kristall>'.$pl['planetenRWKristall'].'</kristall>
		<wolfram>'.$pl['planetenRWWolfram'].'</wolfram>
		<fluor>'.$pl['planetenRWFluor'].'</fluor>
		<userid><![CDATA['.$pl['planeten_playerID'].']]></userid>
		<userName><![CDATA[';
				// Inhaber
				if($pl['playerName'] != NULL) {
					$planeten .= htmlspecialchars($pl['playerName'], ENT_COMPAT, $charset);
					if($pl['playerUmod'] AND $oddb) {
						$planeten .= '<span style="color:#ff3322"><sup>zzZ</sup></span>';
					}
				}
				// frei
				else if($pl['planeten_playerID'] === "0") {
					$planeten .= '- keiner -';
				}
				// Lux
				else if($pl['planeten_playerID'] == -2) {
					$planeten .= 'Seze Lux';
				}
				// Altrasse
				else if($pl['planeten_playerID'] == -3) {
					$planeten .= 'Altrasse';
				}
				// unbekannter Inhaber
				else {
					$planeten .= '- unbekannt -';
				}
				$planeten .= ']]></userName>';
				
				// Allianz anzeigen
				if($pl['playerName'] != NULL) {
					// hat Allianz
					if($pl['allianzenTag'] != NULL) {
						if($pl['statusStatus'] == NULL) $pl['statusStatus'] = 0;
						
						$planeten .= '
		<userAlliance><![CDATA['.htmlspecialchars($pl['allianzenName'], ENT_COMPAT, $charset).']]></userAlliance>
		<userAllianceId>'.$pl['player_allianzenID'].'</userAllianceId>
		<userAllianceTag><![CDATA['.htmlspecialchars($pl['allianzenTag'], ENT_COMPAT, $charset).']]></userAllianceTag>';
					}
					// allianzlos oder unbekannte Allianz
					else {
						$planeten .= '
		<userAlliance></userAlliance>
		<userAllianceId>0</userAllianceId>
		<userAllianceTag></userAllianceTag>';
					}
				}
				// kein Inhaber
				else {
					$planeten .= '
		<userAlliance></userAlliance>
		<userAllianceId>0</userAllianceId>
		<userAllianceTag></userAllianceTag>';
				}
				
				$planeten .= '
		<userRace><![CDATA['.($pl['playerName'] != NULL ? $rassen[$pl['playerRasse']] : '').']]></userRace>';
				if(trim($pl['planetenKommentar']) != '' AND $r) {
					$planeten .= '
		<comment><![CDATA['.($oddb ? 1 : htmlspecialchars($pl['planetenKommentar'], ENT_COMPAT, $charset)).']]></comment>';
				}
				else {
					$planeten .= '
		<comment />';
				}
				$planeten .= '
		<gebplanet><![CDATA['.($r ? $pl['planetenGebPlanet'] : '').']]></gebplanet>
		<geborbit><![CDATA['.($r ? $pl['planetenGebOrbit'] : '').']]></geborbit>
		<erzmenge><![CDATA['.ressmenge($pl['planetenRMErz']).']]></erzmenge>
		<metallmenge><![CDATA['.ressmenge($pl['planetenRMMetall']).']]></metallmenge>
		<wolframmenge><![CDATA['.ressmenge($pl['planetenRMWolfram']).']]></wolframmenge>
		<kristallmenge><![CDATA['.ressmenge($pl['planetenRMKristall']).']]></kristallmenge>
		<fluormenge><![CDATA['.ressmenge($pl['planetenRMFluor']).']]></fluormenge>
		<additional><![CDATA[';
				// Tooltip-Zusatz
				// Kommentar
				if(trim($pl['planetenKommentar']) != '' AND $r) {
					$planeten .= '<span id="kommentar'.$pl['planetenID'].'">Kommentar: '.str_replace(array("\r\n", "\n"), '', nl2br(htmlspecialchars(htmlspecialchars($pl['planetenKommentar'], ENT_COMPAT, $charset), ENT_COMPAT, $charset))).'</span>';
				}
				else if($r) {
					$planeten .= '<span id="kommentar'.$pl['planetenID'].'"></span>';
				}
				// Natives
				if($pl['planetenNatives']) {
					$planeten .= '<br /><b>'.$pl['planetenNatives'].' Natives</b>';
				}
				$planeten .= ']]></additional>
		<additional2><![CDATA[';
				// Orbit-Zusatz
				// laufende Invasion etc
				if(isset($invasionen[$pl['planetenID']])) {
					foreach($invasionen[$pl['planetenID']] as $inva) {
					
						if(!isset($ilabels[$inva['invasionenTyp']])) {
							continue;
						}
						
						$planeten .= '<div style="color:#ff3322;margin-bottom:0.5em">'.$ilabels[$inva['invasionenTyp']].'<br>';
						if($inva['playerName'] != NULL) {
							$planeten .= htmlspecialchars($inva['playerName'], ENT_COMPAT, $charset);
							if($inva['allianzenTag'] != NULL) {
								$planeten .= ' '.htmlspecialchars($inva['allianzenTag'], ENT_COMPAT, $charset);
							}
						}
						
						// Ende bei Besatzungen nicht anzeigen
						if($inva['invasionenTyp'] != 4) {
							$planeten .= '<br>Ende: '.($inva['invasionenEnde'] ? datum($inva['invasionenEnde']) : '<i>unbekannt</i>').'</span>';
						}
						
						$planeten .= '</div>';
						
					}
				}
				
				// Bergbau
				if($user->rechte['fremdinvakolos'] AND $pl['schiffeBergbau'] !== NULL) {
					$planeten .= '<span style="color:#55ff33">Bergbau</span>';
				}
				
				// Terraformer
				if($user->rechte['fremdinvakolos'] AND $pl['schiffeTerraformer']) {
					$planeten .= '<span style="color:#55ff33">Terraformer</span>';
				}
				
				$planeten .= ']]></additional2>
			<additional3><![CDATA[';
				// geraidet oder getoxxt?
				if($user->rechte['toxxraid']) {
					// geraidet
					if($pl['planetenGeraidet'] > $lastweek) {
						 $planeten .= 'geraidet: '.datum($pl['planetenGeraidet']);
					}
					// getoxxt
					if($pl['planetenGetoxxt'] > time()) {
						 $planeten .= '<br><br>Toxx: '.datum($pl['planetenGetoxxt']);
					}
				}
				$planeten .= ']]></additional3>
	</planet>
	';
			}
		}
	}
}

// systemInfo generieren
$sysinfo = '';

// Einstellungen ermitteln
$fow = json_decode($user->settings['fow'], true);

if(count($fow) OR !$showr) {
	// Headline
	if($oddb) {
		$sysinfo .= '<![CDATA[<table cellpadding="4" cellspacing="0">';
	}
	
	// keine Berechtigung zur Systemansicht
	if(!$showr) {
		$sysinfo .= '
	<tr>
		'.fowcell($showerror, false, 9).'
	</tr>
	<tr>
		'.fowcell('&nbsp;', false, 9).'
	</tr>';
	}
	
	$sysinfo .= ($oddb ? '<tr>' : '<thr>').'
		'.fowcell('', true).'
		'.fowcell('System', true).'
		'.fowcell('Planet', true).'
		'.fowcell('Inhaber', true).'
		'.fowcell('Allianz', true).'
		'.fowcell('Entf (A'.$user->settings['antrieb'].')', true).'
		'.fowcell('Sys-Scan', true).'
		'.fowcell('', true).'
		'.fowcell('', true).'
	'.($oddb ? '</tr>' : '</thr>');
	
	// Gate
	if(isset($fow['gate'])) {
		// Gate vorhanden
		if($data['galaxienGate']) {
			$sysinfo .= '
	<tr>
		'.fowcell('Gate G'.$data['systeme_galaxienID']).'
		'.fowcell($data['galaxienGateSys'], false, 0, 'index.php?op=system&amp;sys='.$data['galaxienGateSys']).'
		'.fowcell($data['galaxienGate']).'
		'.fowcell('X').'
		<td></td>
		'.fowcell(flugdauer($data['systemeGateEntf'], $user->settings['antrieb'])).'
		<td></td>
		<td></td>
		'.fowcell('[&raquo;]', false, 0, 'index.php?op=fleet&pre_pid_set='.$data['galaxienGate'], 'Schiffe zum Gate schicken').'
	</tr>';
		}
		// kein Gate
		else {
			$sysinfo .= '
	<tr>
		'.fowcell('Gate G'.$data['systeme_galaxienID']).'
		'.fowcell('kein Gate eingetragen', false, 8).'
	</tr>';
		}
	}
	
	// Myrigates anzeigen
	if(isset($fow['mgate'])) {
		// Myrigate im System -> zusätzliche Daten ermitteln
		if($mg) {
			$mg['planetenEntfernung'] = entf(0,0,0,1,0,0,0,$mg['planetenPosition']);
			$mg['planeten_systemeID'] = $_GET['id'];
			$mg['systemeUpdate'] = $data['systemeUpdate'];
		}
		// Myrigates abfragen
		else {
			$query = query("
				SELECT
					planetenID,
					planeten_systemeID,
					planeten_playerID,
					
					systemeUpdate,
					
					playerName,
					player_allianzenID,
					
					allianzenTag,
					
					register_allianzenID,
					
					statusStatus,
					
					".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $data['systemeX'], $data['systemeY'], $data['systemeZ'], "1")." AS planetenEntfernung
				FROM
					".PREFIX."myrigates
					LEFT JOIN ".PREFIX."planeten
						ON planetenID = myrigates_planetenID
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
					LEFT JOIN ".GLOBPREFIX."player
						ON playerID = planeten_playerID
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
					LEFT JOIN ".PREFIX."allianzen_status
						ON statusDBAllianz = ".$user->allianz."
						AND status_allianzenID = allianzenID
				WHERE
					myrigates_galaxienID = ".$data['systeme_galaxienID']."
					AND (statusStatus IN(".implode(", ", $status_freund).") OR (myrigatesSprung > 0 AND myrigatesSprungFeind = 0))
				ORDER BY
					planetenEntfernung ASC
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				// Berechtigung
				$r = Rechte::getRechteShowMyrigate($row);
				
				// nächstes Myrigate
				if($r AND $mg === false) {
					$mg = $row;
					break;
				}
			}
		}
		// Myrigate gefunden
		if($mg) {
			$sc = ($mg['systemeUpdate'] > $heute) ? 'heute' : strftime('%d.%m.%y', $mg['systemeUpdate']);
			$scc = ($mg['systemeUpdate'] > time()-$config['scan_veraltet']*86400) ? '#00aa00' : '#ff3322';
			
			// Inhaber verschleiert
			if($mg['playerName'] == NULL) {
				if($mg['planeten_playerID'] == 0) $mg['playerName'] = 'keiner';
				else if($mg['planeten_playerID'] == -2) $mg['playerName'] = 'Seze Lux';
				else if($mg['planeten_playerID'] == -3) $mg['playerName'] = 'Altrasse';
				else $mg['playerName'] = 'unbekannt';
			}
			
			$sysinfo .= '
	<tr>
		'.fowcell('nächstes Myrigate').'
		'.fowcell($mg['planeten_systemeID'], false, 0, 'index.php?op=system&amp;sys='.$mg['planeten_systemeID']).'
		'.fowcell($mg['planetenID']).'
		'.fowcell(htmlspecialchars($mg['playerName'], ENT_COMPAT, $charset), false, 0, 'index.php?op=usershow&amp;welch='.$mg['planeten_playerID']).'
		'.($mg['player_allianzenID'] ? fowcell(htmlspecialchars($mg['allianzenTag'], ENT_COMPAT, $charset), false, 0, 'index.php?op=allyshow&amp;welch='.$mg['player_allianzenID']) : fowcell('')).'
		'.fowcell(flugdauer($mg['planetenEntfernung'], $user->settings['antrieb'])).'
		'.fowcell(($oddb ? '<span style="color:'.$scc.'">'.$sc.'</span>' : $sc)).'
		'.fowcell('[weitere]', false, 0, ADDR.'index.php?p=search&amp;s=1&amp;g='.$data['systeme_galaxienID'].'&amp;mg=on&amp;as2[0]=1&amp;as2[1]=1&amp;as2[2]=1&amp;as2[4]=1&amp;as2[5]=1&amp;sortt=1&amp;entf=sys'.$_GET['id'].'&amp;hide&amp;title='.urlencode('Myrigates von '.$_GET['id'].' aus')).'
		'.fowcell('[&raquo;]', false, 0, 'index.php?op=fleet&pre_pid_set='.$mg['planetenID'], 'Schiffe hierher schicken').'
	</tr>';
		}
		// kein Myrigate gefunden
		else {
			$sysinfo .= '
	<tr>
		'.fowcell('nächstes Myrigate').'
		'.fowcell('keine Freund-Myrigates in G'.$data['systeme_galaxienID'].' eingetragen', false, 8).'
	</tr>';
		}
	}
	
	// nicht erfasste Systeme und veraltete Ally-Systeme
	if(isset($fow['scan'])) {
		// erst den Cache abfragen, ob es nicht erfasste Systeme gibt
		$q = true;
		if($c = $cache->get('fow_erfasst')) {
			$q = false;
		}
		
		// nicht erfasste Systeme abfragen
		if($q) {
			$query = query("
				SELECT
					systemeID
				FROM
					".PREFIX."systeme
				WHERE
					systemeUpdateHidden = 0
					AND systemeID != ".$_GET['id']."
				ORDER BY
					systemeID ASC
				LIMIT 1
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// nicht alle Systeme erfasst
			if(mysql_num_rows($query)) {
				$row = mysql_fetch_assoc($query);
				
				$sysinfo .= '
	<tr>
		'.fowcell('nicht erfasst').'
		'.fowcell($row['systemeID'], false, 0, 'index.php?op=system&amp;sys='.$row['systemeID']).'
		'.fowcell('bitte einscannen, damit die DB die Planeten erfassen kann', false, 6).'
	</tr>';
			}
			// alle Systeme erfasst
			else {
				$cache->set('fow_erfasst', 1, 3600);
			}
		}
		
		// Account-Allianz
		$ally = isset($_GET['ally']) ? (int)$_GET['ally'] : 0;
		if(!$ally) $ally = $user->allianz;
		
		// veraltete Allysysteme
		if($ally) {
			// erst den Cache abfragen
			$q = true;
			if($cache->get('fow_ally'.$ally)) {
				$q = false;
			}
			
			// veraltete Allysysteme abfragen
			if($q) {
				$query = query("
					SELECT
						systemeID,
						systemeUpdate
					FROM
						".PREFIX."systeme
					WHERE
						systemeUpdate < ".(time()-$config['scan_veraltet_ally']*86400)."
						AND systemeAllianzen LIKE '%+".$ally."+%'
						AND systemeID != ".$_GET['id']."
					LIMIT 1
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// veraltete Allysysteme vorhanden
				if(mysql_num_rows($query)) {
					$row = mysql_fetch_assoc($query);
					
					$sysinfo .= '
	<tr>
		'.fowcell('veraltetes Ally-System').'
		'.fowcell($row['systemeID'], false, 0, 'index.php?op=system&amp;sys='.$row['systemeID']).'
		'.fowcell('').'
		'.fowcell('').'
		'.fowcell('').'
		'.fowcell('').'
		'.fowcell(($oddb ? '<span style="color:#ff3322">'.strftime('%d.%m.%y', $row['systemeUpdate']).'</span>' : strftime('%d.%m.%y', $row['systemeUpdate']))).'
		'.fowcell('[weitere]', false, 0, ($ally == $user->allianz ? ADDR.'index.php?p=scout&amp;sp=intern' : ADDR.'index.php?p=scout&amp;sp=sitter&amp;ally='.$ally)).'
		'.fowcell('').'
	</tr>';
				}
				// alle Ally-Systeme aktuell
				else {
					$cache->set('fow_ally'.$ally, 1, 3600);
				}
			}
		}
	}
	
	// Scoutziel
	if(isset($fow['scout'])) {
		$count = isset($fow['scoutcount']) ? $fow['scoutcount'] : 1;
		
		$query = query("
			SELECT
				systemeID,
				systemeUpdate,
				".entf_mysql("systemeX", "systemeY", "systemeZ", "1", $data['systemeX'], $data['systemeY'], $data['systemeZ'], "1")." AS systemeEntfernung,
				
				planetenID,
				planeten_playerID,
				
				playerName,
				player_allianzenID,
				
				allianzenTag
			FROM
				".PREFIX."systeme
				USE INDEX (systeme_galaxienID)
				LEFT JOIN ".PREFIX."planeten
					ON planeten_systemeID = systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
			WHERE
				systeme_galaxienID = ".$data['systeme_galaxienID']."
				AND systemeUpdate < ".(time()-$fow['scout']*86400)."
				AND systemeScanReserv < ".(time()-86400)."
				AND systemeID != ".$_GET['id']."
				AND (playerDeleted IS NULL OR playerDeleted = 0)
			GROUP BY
				systemeID
			ORDER BY
				".((isset($fow['scoutfirst']) AND $fow['scoutfirst']) ? "(systemeUpdate = 0) DESC," : "")."
				systemeEntfernung ASC,
				planetenID ASC
			LIMIT ".$count."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// nicht alle Systeme aktuell
		if(mysql_num_rows($query)) {
			$first = true;
		
			while($row = mysql_fetch_assoc($query)) {
				
				if(!$row['systemeUpdate']) $sc = 'nie';
				else if($row['systemeUpdate'] > $heute) $sc = 'heute';
				else $sc = strftime('%d.%m.%y', $row['systemeUpdate']);
				$scc = ($row['systemeUpdate'] > time()-$config['scan_veraltet']*86400) ? '#00aa00' : '#ff3322';
				
				// Planet besetzt
				if($row['playerName'] != NULL) {
					$inhaber = fowcell(htmlspecialchars($row['playerName'], ENT_COMPAT, $charset), false, 0, 'index.php?op=usershow&amp;welch='.$row['planeten_playerID']);
				}
				// Planet frei
				else if($row['planeten_playerID'] === "0") {
					$inhaber = fowcell($oddb ? '<i>keiner</i>' : 'keiner');
				}
				// Lux
				else if($row['planeten_playerID'] == -2) {
					$inhaber = fowcell($oddb ? '<i>Seze Lux</i>' : 'Seze Lux');
				}
				// Altrasse
				else if($row['planeten_playerID'] == -3) {
					$inhaber = fowcell($oddb ? '<i>Altrasse</i>' : 'Altrasse');
				}
				// Inhaber unbekannt
				else {
					$inhaber = fowcell($oddb ? '<i>unbekannt</i>' : 'unbekannt');
				}
				
				$sysinfo .= '
	<tr>
		'.fowcell($first ? 'Scoutziel' : '').'
		'.fowcell($row['systemeID'], false, 0, 'index.php?op=system&amp;sys='.$row['systemeID']).'
		'.fowcell($row['planetenID']).'
		'.$inhaber.'
		'.($row['player_allianzenID'] ? fowcell(htmlspecialchars($row['allianzenTag'], ENT_COMPAT, $charset), false, 0, 'index.php?op=allyshow&amp;welch='.$row['player_allianzenID']) : fowcell('')).'
		'.fowcell(flugdauer($row['systemeEntfernung'], $user->settings['antrieb'])).'
		'.fowcell(($oddb ? '<span style="color:'.$scc.'">'.$sc.'</span>' : $sc)).'
		'.fowcell($oddb ? '<a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''.ADDR.'index.php?p=ajax_general&amp;sp=reserve&amp;sys='.$row['systemeID'].'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<i>reserviert</i>\'"><i>reservieren</i></a>' : '').'
		'.fowcell('[&raquo;]', false, 0, 'index.php?op=fleet&pre_pid_set='.$row['planetenID'], 'Schiffe hierher schicken').'
	</tr>';
				
				$first = false;
			}
		}
		// alle Systeme aktuell
		else {
			$sysinfo .= '
	<tr>
		'.fowcell('Scoutziel').'
		'.fowcell('alle Systeme in G'.$data['systeme_galaxienID'].' sind aktueller als '.$fow['scout'].' Tage', false, 8).'
	</tr>';
		}
	}
	
	// nächste Systeme
	if(isset($fow['next'])) {
		$query = query("
			SELECT
				systemeID,
				systemeUpdate,
				".entf_mysql("systemeX", "systemeY", "systemeZ", "1", $data['systemeX'], $data['systemeY'], $data['systemeZ'], "1")." AS systemeEntfernung,
				
				planetenID,
				planeten_playerID,
				
				playerName,
				player_allianzenID,
				
				allianzenTag
			FROM
				".PREFIX."systeme
				USE INDEX (systeme_galaxienID)
				LEFT JOIN ".PREFIX."planeten
					ON planeten_systemeID = systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
			WHERE
				systeme_galaxienID = ".$data['systeme_galaxienID']."
				AND systemeID != ".$_GET['id']."
				AND (playerDeleted IS NULL OR playerDeleted = 0)
			GROUP BY
				systemeID
			ORDER BY
				systemeEntfernung ASC,
				planetenID ASC
			LIMIT ".$fow['next']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$first = true;
		
		while($row = mysql_fetch_assoc($query)) {
			
			if(!$row['systemeUpdate']) $sc = 'nie';
			else if($row['systemeUpdate'] > $heute) $sc = 'heute';
			else $sc = strftime('%d.%m.%y', $row['systemeUpdate']);
			$scc = ($row['systemeUpdate'] > time()-$config['scan_veraltet']*86400) ? '#00aa00' : '#ff3322';
			
			// Planet besetzt
			if($row['playerName'] != NULL) {
				$inhaber = fowcell(htmlspecialchars($row['playerName'], ENT_COMPAT, $charset), false, 0, 'index.php?op=usershow&amp;welch='.$row['planeten_playerID']);
			}
			// Planet frei
			else if($row['planeten_playerID'] === "0") {
				$inhaber = fowcell($oddb ? '<i>keiner</i>' : 'keiner');
			}
			// Lux
			else if($row['planeten_playerID'] == -2) {
				$inhaber = fowcell($oddb ? '<i>Seze Lux</i>' : 'Seze Lux');
			}
			// Altrasse
			else if($row['planeten_playerID'] == -3) {
				$inhaber = fowcell($oddb ? '<i>Altrasse</i>' : 'Altrasse');
			}
			// Inhaber unbekannt
			else {
				$inhaber = fowcell($oddb ? '<i>unbekannt</i>' : 'unbekannt');
			}
			
			// weitere-Link
			if($first) {
				$link = fowcell('[weitere]', false, 0, ADDR.'index.php?p=strecken&amp;sp=flug_next&amp;start=sys'.$_GET['id'].'&amp;syscount=50&amp;antrieb='.$user->settings['antrieb']);
			}
			else $link = fowcell('');
			
			$sysinfo .= '
	<tr>
		'.fowcell($first ? 'nächstes System' : '').'
		'.fowcell($row['systemeID'], false, 0, 'index.php?op=system&amp;sys='.$row['systemeID']).'
		'.fowcell($row['planetenID']).'
		'.$inhaber.'
		'.($row['player_allianzenID'] ? fowcell(htmlspecialchars($row['allianzenTag'], ENT_COMPAT, $charset), false, 0, 'index.php?op=allyshow&amp;welch='.$row['player_allianzenID']) : fowcell('')).'
		'.fowcell(flugdauer($row['systemeEntfernung'], $user->settings['antrieb'])).'
		'.fowcell(($oddb ? '<span style="color:'.$scc.'">'.$sc.'</span>' : $sc)).'
		'.$link.'
		'.fowcell('[&raquo;]', false, 0, 'index.php?op=fleet&pre_pid_set='.$row['planetenID'], 'Schiffe hierher schicken').'
	</tr>';
			
			$first = false;
		}
	}
	
	
	/*
	 * Routen und Listen
	 */
	if(isset($fow['routen']) AND count($fow['routen']) AND $user->rechte['routen']) {
		
		General::loadClass('route');
		
		$rids = array_keys($fow['routen']);
		
		$routen = array();
		
		// Daten abfragen
		$query = query("
			SELECT
				routenID,
				routenDate,
				routen_playerID,
				routen_galaxienID,
				routenName,
				routenListe,
				routenTyp,
				routenEdit,
				routenFinished,
				routenData,
				routenCount,
				routenMarker,
				routenAntrieb,
				
				user_playerName,
				user_allianzenID,
				statusStatus
			FROM
				".PREFIX."routen
				LEFT JOIN ".PREFIX."user
					ON user_playerID = routen_playerID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = user_allianzenID
					AND status_allianzenID = ".$user->allianz."
			WHERE
				routenID IN(".implode(", ", $rids).")
				AND (routen_galaxienID = ".$data['systeme_galaxienID']." OR routen_galaxienID = 0)
				AND (routenFinished = 1 OR routenListe = 1)
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$routen[$row['routenID']] = $row;
		}
		
		$rziele = array();
		$rzieleall = array();
		
		// Routen durchgehen und Zielplaneten berechnen
		foreach($rids as $id) {
			if(isset($routen[$id])) {
				// Routen-Klasse laden
				General::loadClass('route');
				$route = new route;
				if($route->load($id, $routen[$id]) === true) {
					$rziele[$id] = $route->compute_next($_GET['id'], $fow['routen'][$id]);
					if(is_array($rziele[$id])) {
						$rzieleall = array_merge($rzieleall, $rziele[$id]);
					}
					else {
						unset($rziele[$id]);
					}
				}
			}
		}
		
		// doppelte IDs entfernen
		array_unique($rzieleall);
		
		$rdata = array();
		
		if(count($rzieleall)) {
			// Bedingungen aufstellen
			$conds = array(
				"systeme_galaxienID = ".$data['systeme_galaxienID'],
				"planetenID IN(".implode(", ", $rzieleall).")"
			);
			
			// Planetendaten abfragen
			$query = query("
				SELECT
					planetenID,
					planeten_playerID,
					
					systemeID,
					systemeUpdate,
					".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $data['systemeX'], $data['systemeY'], $data['systemeZ'], "1")." AS planetenEntfernung,
					
					playerName,
					player_allianzenID,
					
					allianzenTag
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
					LEFT JOIN ".GLOBPREFIX."player
						ON playerID = planeten_playerID
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
				WHERE
					".implode(" AND ", $conds)."
				ORDER BY
					NULL
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				// Inhaber von gesperrten Allianzen ausblenden
				if($user->protectedAllies AND $row['player_allianzenID'] AND in_array($row['player_allianzenID'], $user->protectedAllies)) {
					$row['playerName'] = ' ';
					$row['allianzenTag'] = ' ';
					$row['planeten_playerID'] = 1;
					$row['player_allianzenID'] = 1;
				}
				$rdata[$row['planetenID']] = $row;
			}
		}
		
		// Routen durchgehen und ausgeben
		foreach($rids as $id) {
			if(isset($rziele[$id])) {
				$first = true;
				
				foreach($rziele[$id] as $plid) {
					if(isset($rdata[$plid])) {
						$row = $rdata[$plid];
						
						if(!$row['systemeUpdate']) $sc = 'nie';
						else if($row['systemeUpdate'] > $heute) $sc = 'heute';
						else $sc = strftime('%d.%m.%y', $row['systemeUpdate']);
						$scc = ($row['systemeUpdate'] > time()-$config['scan_veraltet']*86400) ? '#00aa00' : '#ff3322';
						
						// Planet besetzt
						if($row['playerName'] != NULL) {
							$inhaber = fowcell(htmlspecialchars($row['playerName'], ENT_COMPAT, $charset), false, 0, 'index.php?op=usershow&amp;welch='.$row['planeten_playerID']);
						}
						// Planet frei
						else if($row['planeten_playerID'] === "0") {
							$inhaber = fowcell($oddb ? '<i>keiner</i>' : 'keiner');
						}
						// Lux
						else if($row['planeten_playerID'] == -2) {
							$inhaber = fowcell($oddb ? '<i>Seze Lux</i>' : 'Seze Lux');
						}
						// Altrasse
						else if($row['planeten_playerID'] == -3) {
							$inhaber = fowcell($oddb ? '<i>Altrasse</i>' : 'Altrasse');
						}
						// Inhaber unbekannt
						else {
							$inhaber = fowcell($oddb ? '<i>unbekannt</i>' : 'unbekannt');
						}
						
						// weitere-Link
						if($first) {
							if($routen[$id]['routenListe'] == 1) {
								$link = fowcell('[weitere]', false, 0, ADDR.'index.php?p=route&amp;sp=view&amp;id='.$id.'&amp;sort=sys'.$_GET['id']);
							}
							else {
								$link = fowcell('[&ouml;ffnen]', false, 0, ADDR.'index.php?p=route&amp;sp=view&amp;id='.$id);
							}
						}
						else $link = fowcell('');
						
						$sysinfo .= '
	<tr>
		'.fowcell($first ? htmlspecialchars($routen[$id]['routenName'], ENT_COMPAT, $charset) : '').'
		'.fowcell($row['systemeID'], false, 0, 'index.php?op=system&amp;sys='.$row['systemeID']).'
		'.fowcell($row['planetenID']).'
		'.$inhaber.'
		'.($row['player_allianzenID'] ? fowcell(htmlspecialchars($row['allianzenTag'], ENT_COMPAT, $charset), false, 0, 'index.php?op=allyshow&amp;welch='.$row['player_allianzenID']) : fowcell('')).'
		'.fowcell(flugdauer($row['planetenEntfernung'], ($routen[$id]['routenAntrieb'] ? $routen[$id]['routenAntrieb'] : $user->settings['antrieb']))).'
		'.fowcell(($oddb ? '<span style="color:'.$scc.'">'.$sc.'</span>' : $sc)).'
		'.$link.'
		'.fowcell('[&raquo;]', false, 0, 'index.php?op=fleet&pre_pid_set='.$row['planetenID'], 'Schiffe hierher schicken').'
	</tr>';
						
						$first = false;
					}
				}
			}
		}
	}
	
	/*
	 * Suchfilter
	 */
	if(isset($fow['search'])) {
		
		General::loadClass('Search');
		
		$entf = entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $data['systemeX'], $data['systemeY'], $data['systemeZ'], "1");
		
		foreach($fow['search'] as $val) {
			
			/*
			 * 0 - Name
			 * 1 - Anzahl
			 * 2 - Sortierung
			 * 3 - Wert
			 */
			
			// Filter erstellen
			$filter = array();
			parse_str($val[3], $filter);
			
			// auch nach Galaxie filtern
			$filter['g'] = $data['systeme_galaxienID'];
			
			$conds = Search::buildConditions($filter);
			
			$sort = $entf." ".($val[2] ? "DESC" : "ASC");
			
			$query = Search::getSearchAsMySQL($conds, $entf, $sort, 0, $val[1]);
			
			
			// keinen Planeten gefunden
			if(!mysql_num_rows($query)) {
				$sysinfo .= '
	<tr>
		'.fowcell(htmlspecialchars($val[0], ENT_COMPAT, $charset)).'
		'.fowcell('kein Planet gefunden', false, 8).'
	</tr>';
			}
			// Planeten gefunden
			else {
				
				$first = true;
				
				while($row = mysql_fetch_assoc($query)) {
				
					// Scan-Farbe
					if(!$row['systemeUpdate']) $sc = 'nie';
					else if($row['systemeUpdate'] > $heute) $sc = 'heute';
					else $sc = strftime('%d.%m.%y', $row['systemeUpdate']);
					$scc = ($row['systemeUpdate'] > time()-$config['scan_veraltet']*86400) ? '#00aa00' : '#ff3322';
					
					// Planet besetzt
					if($row['playerName'] != NULL) {
						$inhaber = fowcell(htmlspecialchars($row['playerName'], ENT_COMPAT, $charset), false, 0, 'index.php?op=usershow&amp;welch='.$row['planeten_playerID']);
					}
					// Planet frei
					else if($row['planeten_playerID'] === "0") {
						$inhaber = fowcell($oddb ? '<i>keiner</i>' : 'keiner');
					}
					// Lux
					else if($row['planeten_playerID'] == -2) {
						$inhaber = fowcell($oddb ? '<span style="color:#ffff88;font-weight:bold;font-style:italic">Seze Lux</span>' : 'Seze Lux');
					}
					// Altrasse
					else if($row['planeten_playerID'] == -3) {
						$inhaber = fowcell($oddb ? '<span style="color:#ffff88;font-weight:bold;font-style:italic">Altrasse</span>' : 'Altrasse');
					}
					// Inhaber unbekannt
					else {
						$inhaber = fowcell($oddb ? '<i>unbekannt</i>' : 'unbekannt');
					}
					
					$sysinfo .= '
	<tr>
		'.fowcell($first ? htmlspecialchars($val[0], ENT_COMPAT, $charset): '').'
		'.fowcell($row['systemeID'], false, 0, 'index.php?op=system&amp;sys='.$row['systemeID']).'
		'.fowcell($row['planetenID']).'
		'.$inhaber.'
		'.($row['player_allianzenID'] ? fowcell(htmlspecialchars($row['allianzenTag'], ENT_COMPAT, $charset), false, 0, 'index.php?op=allyshow&amp;welch='.$row['player_allianzenID']) : fowcell('')).'
		'.fowcell(flugdauer($row['planetenEntfernung'], $user->settings['antrieb'])).'
		'.fowcell(($oddb ? '<span style="color:'.$scc.'">'.$sc.'</span>' : $sc)).'
		'.($first ? fowcell('[weitere]', false, 0, ADDR.'index.php?p=search&amp;sp=planet&amp;s=1&amp;hide&amp;'.htmlspecialchars($val[3], ENT_COMPAT, $charset).'&amp;g='.$data['systeme_galaxienID'].'&amp;sortt=1&amp;entf=sys'.$_GET['id'].($val[2] ? '&amp;sorto3=1' : '')) : fowcell('')).'
		'.fowcell('[&raquo;]', false, 0, 'index.php?op=fleet&pre_pid_set='.$row['planetenID'], 'Schiffe hierher schicken').'
	</tr>';
					
					$first = false;
				}
			}
		}
	}
	
	// Tabellen-Footer
	if($oddb) {
		$sysinfo .= '</table>]]>';
	}
}


// Ausgabe
echo fowoutput($scan, $planeten, $sysinfo);

?>