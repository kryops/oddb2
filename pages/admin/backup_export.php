<?php
/**
 * pages/admin/backup_export.php
 * Verwaltung -> Import/Export -> exportieren
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// keine Berechtigung
if(!$user->rechte['verwaltung_backup']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->output();
}

// 1 Minute Mindestabstand
else if($cache->get('oddb_export') !== false) {
	$tmpl->error = 'Du kannst maximal alle 30 Sekunden die DB exportieren!';
	$tmpl->output();
}

// alles OK
else {
	// Cache setzen
	$cache->set('oddb_export', 1, 30);
	
	// Memory-Limit erhöhen
	@ini_set('memory_limit', '768M');
	
	// Daten-Filter
	$conds =  array();
	
	// Galaxie-Filter
	if(isset($_GET['mode_gala'], $_GET['gala']) AND $_GET['mode_gala'] != '0' AND trim($_GET['gala']) != '') {
		$galas = explode(",", $_GET['gala']);
		$galaArray = array();
		
		foreach($galas as $gala) {
			
			// Bereich angegeben
			if(strpos($gala, '-') !== false) {
				$gala = explode('-', $gala);
				
				if(count($gala) != 2) {
					continue;
				}
				
				$gala[0] = (int)$gala[0];
				$gala[1] = (int)$gala[1];
				
				if($gala[0] > 0 AND $gala[0] < 1000 AND $gala[1] > 0 AND $gala[1] < 1000) {
					$galaArray = array_merge($galaArray, range($gala[0], $gala[1]));
				}
			}
			// einzelne Galaxie
			else {
				$gala = (int)$gala;
				if($gala > 0 AND $gala < 1000 AND !in_array($gala, $galaArray)) {
					$galaArray[] = $gala;
				}
			}
		}
		
		if(count($galaArray)) {
			$conds[] = 'systeme_galaxienID '.($_GET['mode_gala'] == '2' ? 'NOT' : '').' IN('.implode(',', $galaArray).')';
		}
	}
	
	// Systemscan-Filter
	if(isset($_GET['mode_sys'], $_GET['sys']) AND $_GET['mode_sys'] != '0' AND trim($_GET['sys']) != '') {
		$allies = explode(',', $_GET['sys']);
		$allyArray = array();
		$sysMode = ($_GET['mode_sys'] == "1") ? "" : "NOT";
		$condsSys = array();
		
		foreach($allies as $ally) {
			$ally = (int)$ally;
			$condsSys[] = "systemeAllianzen ".$sysMode." LIKE '%+".$ally."+%'";
		}
		
		if(count($condsSys)) {
			$conds[] = "(".implode(" OR ", $condsSys).")";
		}
	}
	
	// Planetenscan-Filter
	$includePlanScans = true;
	$planScanFilterExclude = true;
	$planScanFilter = array();
	
	if(isset($_GET['mode_plani'], $_GET['plani']) AND $_GET['mode_plani'] != '0') {
		// Keine Planetenscans
		if($_GET['mode_plani'] == "1") {
			$includePlanScans = false;
		}
		else {
			// Filter-Modus: Nur Planeten bestimmter Allianzen
			if($_GET['mode_plani'] == '2') {
				$planScanFilterExclude = false;
			}
			
			$allies = explode(',', $_GET['plani']);
			$allyArray = array();
			
			foreach($allies as $ally) {
				$planScanFilter[] = (int)$ally;
			}
		}
	}
	
	
	// Ausgabe
	// Runde und Abgleich-Version
	$out = 'C1='.ODWORLD.'""""C2='.ABGLEICH_VERSION.'""""';
	
	$currentSystem = 0;
	
	$query = query("
		SELECT
			systemeID,
			systemeUpdateHidden,
			systemeUpdate,
			systemeName,
			
			planetenID,
			
			planetenTyp,
			planetenPosition,
			
			planetenGroesse,
			planetenBevoelkerung,
			planetenName,
			
			planeten_playerID,
			
			planetenUpdateOverview,
			planetenUpdate,
			
			planetenRWErz,
			planetenRWWolfram,
			planetenRWKristall,
			planetenRWFluor,
			
			planetenMyrigate,
			
			planetenNatives,
			
			planetenKategorie,
			planetenGebPlanet,
			planetenGebOrbit,
			planetenGebSpezial,
			planetenOrbiter,
			
			planetenRMErz,
			planetenRMMetall,
			planetenRMWolfram,
			planetenRMKristall,
			planetenRMFluor,
			
			planetenForschung,
			planetenIndustrie,
			
			planetenRPErz,
			planetenRPMetall,
			planetenRPWolfram,
			planetenRPKristall,
			planetenRPFluor,
			
			player_allianzenID
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
		".(count($conds) ? "WHERE ".implode(" AND ", $conds) : "")."
		ORDER BY
			planetenID ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		
		// System-Datensatz hinzufügen
		if($currentSystem != $row['systemeID']) {
			$currentSystem = $row['systemeID'];
			$out .= 'S'.$currentSystem.'='.json_encode(array(
				$row['systemeUpdateHidden'],	// 0
				$row['systemeUpdate'],			// 1
				$row['systemeName']				// 2
			)).'""""';
		}
		
		// Planeten-Datensatz
		$o = array(
			(int)$row['planetenTyp'], 			// 0
			(int)$row['planetenPosition'],		// 1
			(int)$row['planetenGroesse'], 		// 2
			(int)$row['planetenBevoelkerung'],	// 3 
			$row['planetenName'],				// 4
			
			(int)$row['planeten_playerID'],		// 5
			
			(int)$row['planetenUpdateOverview'],// 6
			(int)$row['planetenUpdate'], 		// 7
			
			(int)$row['planetenRWErz'], 		// 8
			(int)$row['planetenRWWolfram'], 	// 9
			(int)$row['planetenRWKristall'], 	// 10
			(int)$row['planetenRWFluor'], 		// 11
			
			(int)$row['planetenMyrigate'], 		// 12
			
			(int)$row['planetenNatives'] 		// 13
		);
		
		// bei eingescannten Planeten Datensatz erweitern, wenn eingestellt
		if($includePlanScans) {
			
			// Je nach Modus Einfügen von Allianz-Filter abhängig machen
			if(($planScanFilterExclude AND !in_array((int)$row['player_allianzenID'], $planScanFilter)) OR
				(!$planScanFilterExclude AND in_array((int)$row['player_allianzenID'], $planScanFilter))) {
				
				if($row['planetenUpdateOverview']) {
					$o = array_merge($o, array(
							(int)$row['planetenKategorie'], 	// 14
							$row['planetenGebPlanet'], 			// 15
							$row['planetenGebOrbit'], 			// 16
							$row['planetenGebSpezial'], 		// 17
							(int)$row['planetenOrbiter'], 		// 18
								
							(int)$row['planetenRMErz'], 		// 19
							(int)$row['planetenRMMetall'], 		// 20
							(int)$row['planetenRMWolfram'], 	// 21
							(int)$row['planetenRMKristall'], 	// 22
							(int)$row['planetenRMFluor'] 		// 23
					));
				}
					
				if($row['planetenUpdate']) {
					$o = array_merge($o, array(
							(int)$row['planetenForschung'], 	// 24
							(int)$row['planetenIndustrie'], 	// 25
								
							(int)$row['planetenRPErz'], 		// 26
							(int)$row['planetenRPMetall'], 		// 27
							(int)$row['planetenRPWolfram'], 	// 28
							(int)$row['planetenRPKristall'], 	// 29
							(int)$row['planetenRPFluor'] 		// 30
					));
				}
				
			}
			
		}
		
		$out .= 'P'.$row['planetenID'].'='.json_encode($o).'""""';
	}
	
	// Speicher wieder freigeben
	mysql_free_result($query);
	
	// Array komprimieren
	$out = gzcompress($out, 2);
	
	// Download-Header
	header('Content-Disposition: attachment; filename=oddb'.INSTANCE.'_'.date('Y-m-d').'.gz');
    header('Content-Type: application/x-gzip');
    header('Content-Transfer-Encoding: binary');
	
	// Ausgabe
	echo $out;
	
	
	// Log-Eintrag
	if($config['logging'] >= 1) {
		insertlog(25, 'exportiert die Datenbank');
	}
	
	/* DEBUG
	echo ressmenge(strlen($out)).' Bytes Datei';
	echo '<br />';
	echo ressmenge(memory_get_usage(true)).' Bytes RAM';
	echo '<br />';
	echo number_format(microtime(true)-$time_start, 6).' Sekunden';
	*/
}

?>
