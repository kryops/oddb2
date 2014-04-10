<?php
/**
 * pages/scout/sitter.php
 * über Sitter die veralteten Systeme einer Allianz scouten
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
else if(!isset($_POST['allianz'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// Berechtigung
else {
	// Titel
	$tmpl->name = 'Allianz '.htmlspecialchars($_POST['allianz'], ENT_QUOTES, 'UTF-8').' &uuml;ber Sitter scouten';
	
	// Daten sichern
	$_POST['allianz'] = escape($_POST['allianz']);
	
	// Query erzeugen
	$data = false;
	
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
		if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
	}
	
	// Name eingegeben oder ID nicht gefunden
	if(!$data) {
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
		}
	}
	
	// Allianz nicht gefunden
	if(!$data) {
		$tmpl->error = 'Die Allianz wurde nicht gefunden!';
	}
	// Allianz gesperrt
	if($user->protectedAllies AND in_array($data['allianzenID'], $user->protectedAllies)) {
		$tmpl->error = 'Du hast keine Berechtigung, diese Allianz anzuzeigen!';
	}
	// keine veralteten Systeme
	else if($cache->get('fow_ally'.$data['allianzenID'])) {
		$tmpl->content .= '
			<br />
			<div class="center" style="font-weight:bold">Alle Systeme der Allianz sind aktuell.</div>
			<br />';
	}
	else {
		$heute = strtotime('today');
		
		$t = time();
		$sids = array();
		
		// Systeme abfragen
		$query = query("
			SELECT
				systemeID,
				systemeX,
				systemeZ,
				systeme_galaxienID,
				systemeUpdate
			FROM
				".PREFIX."systeme
			WHERE
				systemeUpdate < ".(time()-$config['scan_veraltet_ally']*86400)."
				AND systemeAllianzen LIKE '%+".$data['allianzenID']."+%'
				".($user->protectedGalas ? "AND systeme_galaxienID NOT IN(".implode(', ', $user->protectedGalas).")" : '')."
			ORDER BY
				systemeID
			LIMIT 500
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// veraltete Allysysteme vorhanden
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
				if($row['systemeUpdate'] > $heute) $scan = 'heute';
				else if($row['systemeUpdate']) $scan = strftime('%d.%m.%y', $row['systemeUpdate']);
				else $scan = 'nie';
				
				$tmpl->content .= '
				<tr>
				<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
				<td>'.datatable::system($row['systemeID'], $t).'</td>
				<td class="red">'.$scan.'</td>
				<td><a href="'.($user->odServer != '' ? $user->odServer : 'http://www.omega-day.com').'/game/?op=system&amp;sys='.$row['systemeID'].'" target="_blank" data-sys="'.$row['systemeID'].'">[in OD &ouml;ffnen]</a></td>
				</tr>';
				
				$sids[] = $row['systemeID'];
			}
			$tmpl->content .= '
				</table>';
			
			// Ergebnis-Navigation
			$tmpl->content .= '<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
			
			// Sammel-Öffnen-Link
			$tmpl->content .= '
			<div class="openinod-container">
				<a class="openinod-link">[alle in OD &ouml;ffnen]</a>
			</div>';
		}
		// alle Allysysteme aktuell
		else {
			$cache->set('fow_ally'.$data['allianzenID'], 1, 3600);
			
			$tmpl->content .= '
			<br />
			<div class="center" style="font-weight:bold">Alle Systeme der Allianz sind aktuell.</div>
			<br />';
		}
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(17, 'scoutet die Allianz '.$_POST['allianz'].' über Sitter');
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