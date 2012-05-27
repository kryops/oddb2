<?php
/**
 * pages/scan/planet.php
 * Planet einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten sichern
$_POST['id'] = (int)$_POST['id'];


// Planet unscannbar
if(isset($_POST['unscannbar'])) {
	
	// aktualisieren
	query("
		UPDATE
			".PREFIX."planeten
		SET
			planetenUnscannbar = ".time()."
		WHERE
			planetenID = ".$_POST['id']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(4, 'scannt den Planet '.$_POST['id'].' ein (fehlgeschlagen: Scanprotektoren)');
	}
	
	// Ausgabe
	$tmpl->content = 'Planet '.$_POST['id'].' als unscannbar markiert';
	$tmpl->output();
	
	die();
}








// Daten sichern
$_POST['name'] = escape(html_entity_decode($_POST['name'], ENT_QUOTES, 'utf-8'));
if(isset($_POST['inhabername'])) {
	$_POST['inhabername'] = escape(html_entity_decode($_POST['inhabername'], ENT_QUOTES, 'utf-8'));
}
$_POST['pltyp'] = (int)$_POST['pltyp'];
$_POST['bev'] = (int)$_POST['bev'];
$_POST['bevakt'] = (int)$_POST['bevakt'];
$_POST['groesse'] = (int)$_POST['groesse'];
$_POST['erz'] = (int)$_POST['erz'];
$_POST['wolfram'] = (int)$_POST['wolfram'];
$_POST['kristall'] = (int)$_POST['kristall'];
$_POST['fluor'] = (int)$_POST['fluor'];
$_POST['erzp'] = (int)$_POST['erzp'];
$_POST['metallp'] = (int)$_POST['metallp'];
$_POST['wolframp'] = (int)$_POST['wolframp'];
$_POST['kristallp'] = (int)$_POST['kristallp'];
$_POST['fluorp'] = (int)$_POST['fluorp'];
$_POST['erzm'] = (int)$_POST['erzm'];
$_POST['metallm'] = (int)$_POST['metallm'];
$_POST['wolframm'] = (int)$_POST['wolframm'];
$_POST['kristallm'] = (int)$_POST['kristallm'];
$_POST['fluorm'] = (int)$_POST['fluorm'];
$_POST['forschung'] = (int)$_POST['forschung'];
$_POST['industrie'] = (int)$_POST['industrie'];

// Gebäude auf dem Planet
$gpl = array();
for($i=1;$i<=36;$i++) {
	if($_POST['g'.$i] != '') {
		$geb = array_search($_POST['g'.$i], $gebaeude);
		if($geb !== false) $gpl[] = $geb;
		else $gpl[] = '-4';
	}
	//else $gpl[] = '';  --> Platz sparen
}

// Orbiter
$orb = 0;

// Gebäude im Orbit
$gor = array();
for($i=1;$i<=12;$i++) {
	if($_POST['o'.$i] != '') {
		$geb = array_search($_POST['o'.$i], $gebaeude);
		if($geb !== false) {
			$gor[] = $geb;
			// Orbiter-Stufe
			if(isset($orbiter[$geb]) AND $orbiter[$geb] > $orb) {
				$orb = $orbiter[$geb];
			}
		}
		else $gor[] = '-4';
	}
	//else $gor[] = '';  --> Platz sparen
}

// Existenz überprüfen
$query = query("
	SELECT
		planetenUpdate,
		planeten_playerID
	FROM ".PREFIX."planeten
	WHERE
		planetenID = ".$_POST['id']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Planet existiert nicht
if(!mysql_num_rows($query)) {
	$tmpl->error = 'Der Planet ist nicht erfasst. Zuerst muss das System eingescannt werden!';
	// im Cache ändern
	$cache->set('scanplanet'.$_POST['id'], 1, 10);
}
// Planet existiert
else {
	$data = mysql_fetch_assoc($query);
	
	// Flooding-Schutz 15 Sekunden
	if(time()-$data['planetenUpdate'] < 15) {
		$tmpl->error = 'Der Planet wurde in den letzten 15 Sekunden schon eingescannt!';
		$tmpl->output();
		die();
	}
	
	// Kategorie ermitteln
	$cat = categorize($gpl, $gor, $_POST['groesse']);
	
	// gesamte Ressproduktion und Ressmenge berechnen
	$rp = $_POST['erzp']-$_POST['metallp'];
	if($rp < 0) $rp = 0;
	$rp += $_POST['metallp']+$_POST['wolframp']+$_POST['kristallp']+$_POST['fluorp'];
	
	$rm = $_POST['erzm']+$_POST['metallm']+$_POST['wolframm']+$_POST['kristallm']+$_POST['fluorm'];
	
	// Planet getoxxt
	$getoxxt = "";
	if($_POST['bev'] AND $_POST['bevakt']) {
		if($_POST['bevakt'] < $_POST['bev']) {
			$getoxxt = "planetenGetoxxt = ".getoxxt($_POST['bev'], $_POST['bevakt']).",";
		}
		else {
			$getoxxt = "planetenGetoxxt = 0,";
		}
	}
	
	// verschleierter Inhaber
	$inhaber = '';
	
	if(isset($_POST['inhabername']) AND !isset($_POST['steuer'])) {
		// Name doppelt escapen
		$conds = array(
			"playerName LIKE '".escape($_POST['inhabername'])."'"
		);
		
		// ID ermitteln
		$query = query("
			SELECT
				playerID
			FROM
				".GLOBPREFIX."player
			WHERE
				".implode(" AND ", $conds)."
			ORDER BY
				playerDeleted ASC,
				playerID DESC
			LIMIT 1
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler gefunden
		if(mysql_num_rows($query)) {
			$inhid = mysql_fetch_assoc($query);
			$inhid = $inhid['playerID'];
			
			// Inhaber geändert
			if($inhid != $data['planeten_playerID']) {
				
				// in Update-Abfrage einbauen
				$inhaber = 'planeten_playerID = '.$inhid.',
							planetenHistory = planetenHistory+1,';
				
				// History-Eintrag
				query("
					INSERT INTO ".PREFIX."planeten_history
					SET
						history_planetenID = ".$_POST['id'].",
						history_playerID = ".$inhid.",
						historyLast = ".$data['planeten_playerID'].",
						historyTime = ".time()."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
		}
	}
	
	// Planet aktualisieren
	query("
		UPDATE ".PREFIX."planeten
		SET
			planetenUpdateOverview = ".time().",
			planetenUpdate = ".time().",
			planetenName = '".$_POST['name']."',
			planetenTyp = ".$_POST['pltyp'].",
			planetenGroesse = ".$_POST['groesse'].",
			planetenBevoelkerung = ".$_POST['bev'].",
			planetenForschung = ".$_POST['forschung'].",
			planetenIndustrie = ".$_POST['industrie'].",
			planetenRWErz = ".$_POST['erz'].",
			planetenRWWolfram = ".$_POST['wolfram'].",
			planetenRWKristall = ".$_POST['kristall'].",
			planetenRWFluor = ".$_POST['fluor'].",
			planetenRPErz = ".$_POST['erzp'].",
			planetenRPMetall = ".$_POST['metallp'].",
			planetenRPWolfram = ".$_POST['wolframp'].",
			planetenRPKristall = ".$_POST['kristallp'].",
			planetenRPFluor = ".$_POST['fluorp'].",
			planetenRMErz = ".$_POST['erzm'].",
			planetenRMMetall = ".$_POST['metallm'].",
			planetenRMWolfram = ".$_POST['wolframm'].",
			planetenRMKristall = ".$_POST['kristallm'].",
			planetenRMFluor = ".$_POST['fluorm'].",
			planetenRPGesamt = ".$rp.",
			planetenRMGesamt = ".$rm.",
			planetenGebPlanet = '".implode('+', $gpl)."',
			planetenGebOrbit = '".implode('+', $gor)."',
			planetenOrbiter = ".$orb.",
			".$getoxxt."
			".$inhaber."
			planetenKategorie = ".$cat."
		WHERE
			planetenID = ".$_POST['id']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Steuereinnahmen und Vermögen
	
	$qadd = '';
	
	if(isset($_POST['steuer'], $_POST['konto'], $_POST['fpges'], $_POST['uid'])) {
		// Daten sichern
		$_POST['steuer'] = (int)$_POST['steuer'];
		$_POST['konto'] = (int)$_POST['konto'];
		$_POST['fpges'] = (int)$_POST['fpges'];
		$_POST['uid'] = (int)$_POST['uid'];
		
		// wenn es der eigene Planet ist, Steuer-Update an Statistik-Update hängen
		if($_POST['uid'] == $user->id) {
			$qadd = "
				,
				userEinnahmen = ".$_POST['steuer'].",
				userKonto = ".$_POST['konto'].",
				userFP = ".$_POST['fpges'].",
				userGeldUpdate = ".time()."
			";
		}
		// wenn nicht, Steuern so aktualisieren
		else {
			query("
				UPDATE ".PREFIX."user
				SET
					userEinnahmen = ".$_POST['steuer'].",
					userKonto = ".$_POST['konto'].",
					userFP = ".$_POST['fpges'].",
					userGeldUpdate = ".time()."
				WHERE
					user_playerID = ".$_POST['uid']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
	
	// User-Statistik
	// Planet gilt als aktualisiert, wenn er älter als 1 Tag war
	if($data['planetenUpdate'] AND time()-$data['planetenUpdate'] > 86400) {
		query("
			UPDATE ".PREFIX."user
			SET
				userPlanUpdated = userPlanUpdated+1,
				userDBPunkte = userDBPunkte+1
				".$qadd."
			WHERE
				user_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	// Planet eingetragen -> 2 Punkte
	else if(!$data['planetenUpdate']) {
		query("
			UPDATE ".PREFIX."user
			SET
				userPlanScanned = userPlanScanned+1,
				userDBPunkte = userDBPunkte+2
				".$qadd."
			WHERE
				user_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	// Planet schnell aktualisiert --> nur Einnahmen und Vermögen aktualisieren
	else if($qadd != '') {
		query("
			UPDATE ".PREFIX."user
			SET
				userPlanScanned = userPlanScanned
				".$qadd."
			WHERE
				user_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(4, 'scannt den Planet '.$_POST['id'].' ein');
	}
	
	// Ausgabe
	$tmpl->content = 'Planet '.(isset($_GET['plugin']) ? $_POST['id'] : '<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$_POST['id'].'">'.$_POST['id'].'</a>').' erfolgreich ';
	if($data['planetenUpdate']) $tmpl->content .= 'aktualisiert';
	else $tmpl->content .= 'eigescannt';
}



?>