<?php
/**
 * pages/scout/kolo.php
 * Kolos finden: Systeme der Allianz durchschauen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Tabellenklasse laden
if(!class_exists('datatable')) {
	include './common/datatable.php';
}


// keine Berechtigung
if(!$user->rechte['scout']) $tmpl->error = 'Du hast keine Berechtigung!';
// Daten unvollständig
else if(!isset($_POST['typ'], $_POST['allianz'], $_POST['gr'], $_POST['stunden'], $_POST['sysally'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// keine Allianz
if(!$_POST['typ'] AND !$user->allianz) {
	$tmpl->error = 'Du gehörst keiner Allianz an!';
}
// Berechtigung
else {
	// Titel
	$tmpl->name = 'Kolos in den Systemen '.($_POST['typ'] ? 'der Allianz '.htmlspecialchars($_POST['allianz'], ENT_QUOTES, 'UTF-8') : 'deiner Allianz').' finden';
	
	// Daten sichern
	$_POST['allianz'] = escape($_POST['allianz']);
	$_POST['sysally'] = escape($_POST['sysally']);
	$_POST['stunden'] = (int)$_POST['stunden'];
	$_POST['gr'] = (int)$_POST['gr'];
	
	// eigene Allianz
	if(!$_POST['typ']) {
		$allianz = $user->allianz;
	}
	// andere Allianz
	else {
		$allianz = false;
	
		// Allianz-ID
		if(is_numeric(trim($_POST['allianz']))) {
			// Daten abfragen
			$query = query("
				SELECT
					allianzenID
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenID = ".$_POST['allianz']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz mit dieser ID existiert
			if(mysql_num_rows($query)) {
				$data = mysql_fetch_assoc($query);
				$allianz = $data['allianzenID'];
			}
		}
		
		// Name eingegeben oder ID nicht gefunden
		if(!$allianz) {
			// * als Wildcard benutzen
			$_POST['allianz'] = str_replace('*', '%', $_POST['allianz']);
			
			// Daten abfragen (doppelt escapen wegen LIKE-Bug)
			$query = query("
				SELECT
					allianzenID
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenTag LIKE '".escape($_POST['allianz'])."'
					OR allianzenName LIKE '".escape($_POST['allianz'])."'
				ORDER BY allianzenID ASC
				LIMIT 1
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz mit diesem Namen
			if(mysql_num_rows($query)) {
				$data = mysql_fetch_assoc($query);
				$allianz = $data['allianzenID'];
			}
		}
	}
	
	if(isset($_POST['findsysally']) AND trim($_POST['sysally']) != '') {
		$sysAllianz = null;
	
		// Allianz-ID
		if(is_numeric(trim($_POST['sysally']))) {
			// Daten abfragen
			$query = query("
				SELECT
					allianzenID
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenID = ".$_POST['sysally']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz mit dieser ID existiert
			if(mysql_num_rows($query)) {
				$data = mysql_fetch_assoc($query);
				$sysAllianz = $data['allianzenID'];
			}
		}
		
		// Name eingegeben oder ID nicht gefunden
		if(!$sysAllianz) {
			// * als Wildcard benutzen
			$_POST['sysally'] = str_replace('*', '%', $_POST['sysally']);
			
			// Daten abfragen (doppelt escapen wegen LIKE-Bug)
			$query = query("
				SELECT
					allianzenID
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenTag LIKE '".escape($_POST['sysally'])."'
					OR allianzenName LIKE '".escape($_POST['sysally'])."'
				ORDER BY allianzenID ASC
				LIMIT 1
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz mit diesem Namen
			if(mysql_num_rows($query)) {
				$data = mysql_fetch_assoc($query);
				$sysAllianz = $data['allianzenID'];
			}
		}
	}
	else {
		$sysAllianz = false;
	}
	
	// Allianz nicht gefunden
	if(!$allianz) {
		$tmpl->error = 'Die Allianz wurde nicht gefunden!';
	}
	// Allianz im System nicht gefunden
	if($sysAllianz === null) {
		$tmpl->error = 'Die Allianz im System wurde nicht gefunden!';
	}
	// Allianz gesperrt
	else if($user->protectedAllies AND (in_array($allianz, $user->protectedAllies) OR ($sysAllianz !== null AND in_array($sysAllianz, $user->protectedAllies)))) {
		$tmpl->error = 'Du hast keine Berechtigung, diese Allianz anzuzeigen!';
	}
	else {
		$heute = strtotime('today');
		$gestern = $heute-86400;
		
		// Query zusammenstellen
		$sql = "
			SELECT
				systemeID,
				systemeX,
				systemeZ,
				systeme_galaxienID,
				systemeUpdate
			FROM";
		// bei Mindestgröße auch Planetentabelle
		if($_POST['gr'] AND isset($_POST['free'])) {
			$sql .= "
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID";
		}
		else {
			$sql .= "
				".PREFIX."systeme";
		}
		$sql .= "
			WHERE
				systemeUpdate < ".(time()-$_POST['stunden']*3600)."
				AND systemeAllianzen LIKE '%+".$allianz."+%'";
		
		if($user->protectedGalas) {
			$sql .= "
				AND systeme_galaxienID NOT IN(".implode(', ', $user->protectedGalas).")";
		}
		
		// Allianz im System
		if($sysAllianz) {
			$sql .= "
				AND systemeAllianzen LIKE '%+".$sysAllianz."+%'";
		}
		
		// freie Planeten
		if(isset($_POST['free'])) {
			// Mindestgröße -> über Planeten abfragen und gruppieren
			if($_POST['gr']) {
				$sql .= "
				AND planeten_playerID = 0
				AND planetenGroesse >= ".$_POST['gr']."
			GROUP BY
				systemeID";
			}
			// keine Mindestgröße -> übers System abfragen
			else {
				$sql .= "
				AND systemeAllianzen LIKE '%+-1+%'";
			}
		}
		$sql .= "
			ORDER BY
				systemeID";
		
		// Systeme abfragen
		$query = query($sql) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Treffer
		if(mysql_num_rows($query)) {
			// Tabellen-Header
			$tmpl->content .= '
			<br /><br />
			<table class="data" style="margin:auto">
				<tr>
					<th>Gala</td>
					<th>System</td>
					<th>Scan</td>
					<th>&nbsp;</td>
				</tr>';
			while($row = mysql_fetch_assoc($query)) {
				$tmpl->content .= '
				<tr>
					<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
				<td>'.datatable::system($row['systemeID']).'</td>
					<td>'.datatable::scan($row['systemeUpdate'], $config['scan_veraltet']).'</td>
					<td><a href="'.($user->odServer != '' ? $user->odServer : 'http://www.omega-day.com').'/game/index.php?op=system&amp;sys='.$row['systemeID'].'" target="_blank">[in OD &ouml;ffnen]</a></td>
				</tr>';
			}
			$tmpl->content .= '
			</table>';
		}
		// alle Systeme aktuell
		else {
			$tmpl->content .= '
			<br />
			<div class="center" style="font-weight:bold">Keine Systeme mit den gew&auml;hlten Kriterien gefunden!</div>
			<br />';
		}
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(17, 'benutzt die "Kolos finden"-Funktion für die Allianz '.$allianz);
		}
	}
}

// Leerzeile vor Fehlermeldung setzen
if($tmpl->error != '') {
	$tmpl->error = '<br />'.$tmpl->error;
}

// Ausgabe
$tmpl->output();


?>