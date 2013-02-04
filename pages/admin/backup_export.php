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
	$tmpl->error = 'Du kannst maximal 1x pro Minute die DB exportieren!';
	$tmpl->output();
}

// alles OK
else {
	// Cache setzen
	$cache->set('oddb_export', 1, 60);
	
	// Memory-Limit erhöhen
	@ini_set('memory_limit', '768M');
	
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
			planetenRPFluor
			
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
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
		
		// bei eingescannten Planeten Datensatz erweitern
		if($row['planetenUpdateOverview']) {
			$o = array_merge($o, array(
				(int)$row['planetenKategorie'], 	// 14
				$row['planetenGebPlanet'], 			// 15
				$row['planetenGebOrbit'], 			// 16
				(int)$row['planetenOrbiter'], 		// 17
				
				(int)$row['planetenRMErz'], 		// 18
				(int)$row['planetenRMMetall'], 		// 19
				(int)$row['planetenRMWolfram'], 	// 20
				(int)$row['planetenRMKristall'], 	// 21
				(int)$row['planetenRMFluor'] 		// 22
			));
		}
		
		if($row['planetenUpdate']) {
			$o = array_merge($o, array(
				(int)$row['planetenForschung'], 	// 23
				(int)$row['planetenIndustrie'], 	// 24
				
				(int)$row['planetenRPErz'], 		// 25
				(int)$row['planetenRPMetall'], 		// 26
				(int)$row['planetenRPWolfram'], 	// 27
				(int)$row['planetenRPKristall'], 	// 28
				(int)$row['planetenRPFluor'], 		// 29
			));
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
