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
	// Runde
	$out = 'C1='.ODWORLD.'""""C2='.ABGLEICH_VERSION.'""""';
	
	// System-Scandaten abfragen
	$query = query("
		SELECT
			systemeID,
			systemeUpdate,
			
			planetenID,
			planetenName,
			planeten_playerID,
			planetenGroesse,
			planetenBevoelkerung,
			planetenNatives,
			planetenRWErz,
			planetenRWWolfram,
			planetenRWKristall,
			planetenRWFluor,
			planetenMyrigate,
			planetenRiss
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
		WHERE
			systemeUpdate > 0
		ORDER BY
			planetenID ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$current_sys = 0;
	
	while($row = mysql_fetch_assoc($query)) {
		// System anhängen
		if($current_sys != $row['systemeID']) {
			
			// in den Ausgabe-String
			if($current_sys != 0) {
				$out .= 'S'.$current_sys.'='.json_encode($o).'""""';
			}
			
			$o = array((int)$row['systemeUpdate']);
			$current_sys = $row['systemeID'];
		}
		
		// Planet anhängen
		$o[$row['planetenID']] = array(
			$row['planetenName'],
			(int)$row['planeten_playerID'],
			(int)$row['planetenGroesse'],
			(int)$row['planetenBevoelkerung'],
			(int)$row['planetenNatives'],
			(int)$row['planetenRWErz'],
			(int)$row['planetenRWWolfram'],
			(int)$row['planetenRWKristall'],
			(int)$row['planetenRWFluor'],
			(int)$row['planetenMyrigate'],
			(int)$row['planetenRiss']
		);
	}
	
	// in den Ausgabe-String
	if($current_sys != 0) {
		$out .= 'S'.$current_sys.'='.json_encode($o).'""""';
	}
	
	// Speicher wieder freigeben
	mysql_free_result($query);
	
	// Planeten-Scandaten abfragen
	$query = query("
		SELECT
			planetenID,
			planetenUpdateOverview,
			planetenUpdate,
			planetenKategorie,
			planetenGebPlanet,
			planetenGebOrbit,
			planetenOrbiter,
			planetenRPErz,
			planetenRPMetall,
			planetenRPWolfram,
			planetenRPKristall,
			planetenRPFluor,
			planetenRMErz,
			planetenRMMetall,
			planetenRMWolfram,
			planetenRMKristall,
			planetenRMFluor,
			planetenForschung,
			planetenIndustrie
		FROM
			".PREFIX."planeten
		WHERE
			planetenUpdateOverview > 0
		ORDER BY
			planetenID ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$out .= 'P'.$row['planetenID'].'='.json_encode(array(
			(int)$row['planetenUpdateOverview'],
			(int)$row['planetenUpdate'],
			(int)$row['planetenKategorie'],
			$row['planetenGebPlanet'],
			$row['planetenGebOrbit'],
			(int)$row['planetenOrbiter'],
			(int)$row['planetenRPErz'],
			(int)$row['planetenRPMetall'],
			(int)$row['planetenRPWolfram'],
			(int)$row['planetenRPKristall'],
			(int)$row['planetenRPFluor'],
			(int)$row['planetenRMErz'],
			(int)$row['planetenRMMetall'],
			(int)$row['planetenRMWolfram'],
			(int)$row['planetenRMKristall'],
			(int)$row['planetenRMFluor'],
			(int)$row['planetenForschung'],
			(int)$row['planetenIndustrie']
		)).'""""';
	}
	
	// Speicher wieder freigeben
	mysql_free_result($query);
	
	// max_input_vars-Problem
	@ini_set('max_input_vars', 65536);
	
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
