<?php
/**
 * pages/scan.php
 * einscannen (über DB und Plugin)
 * - Systemansicht
 * - Planetenansicht/scan
 * - Orbit
 * - Planetenübersicht
 * - Einstellungen
 * - Flottenübersichten 
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

/**
 * Funktionen
 */

/**
 * Planeten anhand der Bebauung kategorisieren
 * @param $gpl string/array Gebäude auf dem Planet
 * @param $gor string/array Gebäude im Orbit
 * @param $gr int Planetengröße
 *
 * @return int Kategorie des Planeten
 */
function categorize($gpl, $gor, $gr) {
	
	/*
	 * Kategorien
	 * 1 Erz
	 * 2 Metall
	 * 3 Wolfram
	 * 4 Kristall
	 * 5 Fluor
	 * 6 Forschungseinrichtungen
	 * 7 UNI-Forschung
	 * 8 Forschungszentren
	 * 9 Myri-Forschungszentren
	 * 10 Orbitale Forschung
	 * 11 Gedankenkonzentratoren
	 * 12 Umsatzfabriken
	 * 13 Werft
	 * --- 14-16 reserviert durch Suchfunktion
	 * 17 Crediterzeugung
	 */
	
	
	// bei Größe 0 abbrechen
	if(!$gr) return 0;
	
	// IDs der verschiedenen Gebäudetypen
	// Forschungsgebäude
	$fgeb = array(1001, 1011, 1015, 1038);
	// orbitale Forschungsgebäude
	$ofgeb = array(1032, 1034);
	// Werten
	$werft = array(1004, 1052);
	// Rohstoff-Gebäude
	$erz = array(1003, 1016, 1017, 1058, 1062, 1067, 1072);
	$metall = array(1005, 1006, 1025, 1059, 1063, 1068, 1070);
	$wolfram = array(1008, 1018, 1019, 1055, 1061, 1064, 1069);
	$kristall = array(1007, 1031, 1060, 1065, 1073);
	$fluor = array(1042, 1044, 1057, 1066, 1071, 1074);
	// Umsatzfabrik
	$umsf = 1045;
	// Fabrikgebäude
	$fabgeb = array(1002, 1009, 1010, 1022, 1023, 1035, 1037, 1051, 1053, 1054);
	// orbitale Fabrikgebäude
	$ofabgeb = array(1027, 1028, 1049);
	// Credit-Gebäude
	$creditgeb = array(1014, 1043);
	
	// Gebäude-Zähler initialisieren
	$countfg = 0;
	$countf = array(
		1=>0,
		2=>0,
		3=>0,
		4=>0
	);
	$countofg = 0;
	$countof = array(
		1=>0,
		2=>0
	);
	$counterz = 0;
	$countmetall = 0;
	$countwolfram = 0;
	$countkristall = 0;
	$countfluor = 0;
	$countums = 0;
	$countfab = 0;
	$countwerft = 0;
	$countcredit = 0;
	
	// Gebäude auf dem Planet auswerten
	foreach($gpl as $geb) {
		if(in_array($geb, $fgeb)) {
			$countfg++;
			$countf[array_search($geb, $fgeb)+1]++;
		}
		else if(in_array($geb, $fabgeb)) $countfab++;
		else if(in_array($geb, $erz)) $counterz++;
		else if(in_array($geb, $metall)) {
			if($geb != 1005) {
				$countmetall += 3;
			}
			else {
				$countmetall++;
			}
		}
		else if(in_array($geb, $wolfram)) $countwolfram++;
		else if(in_array($geb, $kristall)) $countkristall++;
		else if(in_array($geb, $fluor)) $countfluor++;
		else if(in_array($geb, $creditgeb)) $countcredit++;
	}
	// Gebäude im Orbit auswerten
	foreach($gor as $geb) {
		if(in_array($geb, $ofgeb)) {
			$countofg++;
			$countof[array_search($geb, $ofgeb)+1]++;
		}
		else if(in_array($geb, $ofabgeb)) $countfab++;
		else if(in_array($geb, $werft)) $countwerft++;
		else if($geb == $umsf) $countums++;
		else if(in_array($geb, $creditgeb)) $countcredit++;
	}
	
	// Forschungsgebäude-Array sortieren
	arsort($countf);
	reset($countf);
	
	// über die Kategorie entscheiden
	
	// Stufe 1
	
	// Werft bei 90% Fabriken
	if($countfab/$gr >= 0.9 AND $countwerft) return 13;
	// orbitale Forschung bei 8 Gebäuden und 50% Bodenforschung
	if($countofg >= 8 AND $countfg/$gr >= 0.5) {
		// orbitale Forschung
		if($countof[1] > $countof[2]) return 10;
		// GDKZ
		else return 11;
	}
	
	// Credits bei mindestens 20 Gebäuden
	if($countcredit >= 20) return 17;
	
	// Werft bei 60% Fabriken
	if($countfab/$gr >= 0.6 AND $countwerft) return 13;
	// Umsatzfabrik bei 8
	// if($countums >= 8) return 12;	--- ausgesetzt, damit Kategorisierung eher dem Rohstofftyp entspricht
	// Forschungsgebäude bei 80%
	if($countfg/$gr >= 0.8) return (key($countf)+5);
	// Erz bei 60% und 3x so viel Erz wie Metall
	if($counterz/$gr >= 0.6 AND $counterz/3 >= $countmetall) return 1;
	// Metall bei 40%
	if($countmetall/$gr >= 0.4) return 2;
	// andere Ressplaneten bei 70%
	if($countwolfram/$gr >= 0.7) return 3;
	if($countkristall/$gr >= 0.7) return 4;
	if($countfluor/$gr >= 0.7) return 5;
	
	// Stufe 2
	
	// Credits bei mindestens 15 Gebäuden
	if($countcredit >= 15) return 17;
	
	// orbitale Forschung bei 6 Gebäuden und 50% Bodenforschung
	if($countofg >= 6 AND $countfg/$gr >= 0.5) {
		// orbitale Forschung
		if($countof[1] > $countof[2]) return 10;
		// GDKZ
		else return 11;
	}
	
	// Werft bei 40% Fabriken
	if($countfab/$gr >= 0.4 AND $countwerft) return 13;
	// Forschung bei 60%
	if($countfg/$gr >= 0.6) return (key($countf)+5);
	// Erz bei 40% und 3x so viel wie Metall
	if($counterz/$gr >= 0.4 AND $counterz/3 > $countmetall) return 1;
	// Metall bei 25%
	if($countmetall/$gr >= 0.25) return 2;
	// andere Ressplanis bei 40%
	if($countwolfram/$gr >= 0.4) return 3;
	if($countkristall/$gr >= 0.4) return 4;
	if($countfluor/$gr >= 0.4) return 5;
	// Umsatzfabrik bei 8
	if($countums >= 8) return 12;
	
	// Stufe 3
	
	// Credits bei mindestens 10 Gebäuden
	if($countcredit >= 10) return 17;
	
	// Werft bei 35% Fabriken
	if($countfab/$gr >= 0.35 AND $countwerft) return 13;
	// orbitale Forschung bei 4 Gebäuden
	if($countofg >= 4) {
		// orbitale Forschung
		if($countof[1] > $countof[2]) return 10;
		// GDKZ
		else return 11;
	}
	// Umsatzfabriken bei 5
	if($countums >= 5) return 12;
	// Forschung bei 40%
	if($countfg/$gr >= 0.4) return (key($countf)+5);
	// Erz bei 25% und 3x so viel wie Metall
	if($counterz/$gr >= 0.25 AND $counterz/3 > $countmetall) return 1;
	// andere Ressplanis bei 25%
	if($countwolfram/$gr >= 0.25) return 3;
	if($countkristall/$gr >= 0.25) return 4;
	if($countfluor/$gr >= 0.25) return 5;
	// Metall bei 20%
	if($countmetall/$gr >= 0.2) return 2;
	
	// Credits bei mindestens 8 Gebäuden
	if($countcredit >= 8) return 17;
	
	// Werft bei 25% Fabriken
	if($countfab/$gr >= 0.25 AND $countwerft) return 13;
	
	return 0;
}

/**
 * Invasion ins Archiv verschieben
 * @param $id int Invasions-ID
 * @param $log string log-Eintrag
 */
function inva_archiv($id, $log) {
	global $user, $cache;
	
	// Daten sichern
	$id = (int)$id;
	
	// ins Archiv kopieren
	query("
		INSERT IGNORE INTO ".PREFIX."invasionen_archiv
		SELECT
			invasionenID,
			invasionenTime,
			invasionen_planetenID,
			invasionen_systemeID,
			invasionen_playerID,
			invasionenTyp,
			invasionenFremd,
			invasionenAggressor,
			invasionenEnde,
			invasionenSchiffe,
			invasionenKommentar
		FROM ".PREFIX."invasionen
		WHERE
			invasionenID = ".$id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Eintrag aus den Invasionen löschen
	query("
		DELETE FROM ".PREFIX."invasionen
		WHERE
			invasionenID = ".$id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// InvaLog-Eintrag
	query("
		INSERT INTO ".PREFIX."invasionen_log
		SET
			invalog_invasionenID = ".$id.",
			invalogTime = ".time().",
			invalog_playerID = ".$user->id.",
			invalogText = '".mysql_real_escape_string($log)."'
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// offene Invasionen aus dem Cache löschen
	$cache->remove('openinvas');
	
	// Anzeige aktualisieren
	if(!isset($_GET['plugin']) AND $user->rechte['invasionen']) {
		global $tmpl;
		$tmpl->script = 'openinvas();';
	}
}



/**
 * Workaround für das AUTO_INCREMENT-Verhalten von InnoDB beim Hinzufügen von Invasionen
 * @param $id int ID der hinzugefügten Invasion
 * @return int geänderte ID der Invasion
 */
function inva_autoIncrement($id) {

	$query = query("
		SELECT
			 MAX(invalog_invasionenID)
		FROM
			".PREFIX."invasionen_log
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$data = mysql_fetch_array($query);
	$maxId = $data[0];
	
	if($maxId !== null AND $maxId >= $id) {
		
		// ID der Aktion ändern
		query("
			UPDATE
				".PREFIX."invasionen
			SET
				invasionenID = ".($maxId+1)."
			WHERE
				invasionenID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$id = $maxId+1;
		
		// AUTO_INCREMENT-Wert erhöhen
		query("
			ALTER TABLE
				".PREFIX."invasionen
			AUTO_INCREMENT = ".($maxId+2)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	return $id;
}


// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Einscannen';


// keine Berechtigung
if(!$user->rechte['scan']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->output();
	die();
}

// ODDB Tool veraltet
if(isset($_GET['plugin'], $_GET['version']) AND $_GET['version'] != ODDBTOOL) {
	
	$path = strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') ? ODDBTOOLPATH_CHROME : ODDBTOOLPATH;
	
	$tmpl->error = 'ODDB Tool veraltet!<br />
	<a href="'.ADDR.'plugin/'.$path.'" style="color:#ffff00">[neue Version installieren]</a>';
	$tmpl->output();
	die();
}


// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'scan'=>true
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}


/**
 * AJAX-Funktionen
 */
 
// Quelltext abschicken (AJAX)
else if($_GET['sp'] == 'scan') {
	if(!isset($_POST['typ'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// unsichtbares System
		if($_POST['typ'] == 'sysun') {
			include './pages/scan/sysun.php';
		}
		// sichtbares System
		else if($_POST['typ'] == 'system') {
			include './pages/scan/system.php';
		}
		// Planetenansicht/scan
		else if($_POST['typ'] == 'planet') {
			include './pages/scan/planet.php';
		}
		// Planetenübersicht
		else if($_POST['typ'] == 'poview') {
			include './pages/scan/poview.php';
		}
		// Orbit
		else if($_POST['typ'] == 'orbit') {
			include './pages/scan/orbit.php';
		}
		// Einstellungen
		else if($_POST['typ'] == 'einst') {
			include './pages/scan/einst.php';
		}
		// Flottenübersicht
		else if($_POST['typ'] == 'floview') {
			include './pages/scan/floview.php';
		}
		// Flottenübersicht - Bergbauschiffe
		else if($_POST['typ'] == 'floviewbbs') {
			include './pages/scan/floview_bbs.php';
		}
		// Sitterliste
		else if($_POST['typ'] == 'sitter') {
			include './pages/scan/sitter.php';
		}
		// Forschung
		else if($_POST['typ'] == 'forschung') {
			include './pages/scan/forschung.php';
			ScanForschung::scan();
		}
		// unbekannter Typ
		else {
			$tmpl->error = 'Unbekannter Scan-Typ!';
		}
	}
	$tmpl->output();
}

/**
 * normale Seiten
 */
else {
	$tmpl->content = '
		<br />
		<div class="fcbox" style="width:92%;line-height:20px;padding:10px">
			<b>gescannt werden k&ouml;nnen:</b>
			<br />
			Systemansicht, Planetenansicht/scan, Orbit, Planeten&uuml;bersicht, Sitter, Einstellungen, Flotten&uuml;bersicht (Bergbauschiffe, Steuern) und Forschung (Geb&auml;ude, Schiffe, Systeme)
		</div>
		<div class="icontent" style="text-align:center;min-width:500px">
			<form action="#" name="qtform">
				<textarea name="input" style="width:95%;height:300px;margin:auto"></textarea>
				<br /><br />
				<input type="button" class="button" value="Quelltext einscannen" style="width:140px" onclick="quelltext(this.parentNode, $(this.parentNode).siblings(\'.ajax\'));this.disabled=true;window.setTimeout(\'$(\\\'.button\\\').removeAttr(\\\'disabled\\\')\', 2000)" />
			</form>
			<br />
			<div class="ajax"></div>
		</div>';
	
	$tmpl->output();
}
?>