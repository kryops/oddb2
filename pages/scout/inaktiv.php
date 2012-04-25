<?php
/**
 * pages/scout/inaktiv.php
 * die Systeme von inaktiven Spielern anzeigen
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
else if(!isset($_POST['player'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// kein User eingegeben
else if(trim($_POST['player']) == '') {
	$tmpl->error = 'Kein Spieler eingegeben!';
}
// Berechtigung
else {
	// Titel
	$tmpl->name = 'Systeme des Spielers '.htmlspecialchars($_POST['player'], ENT_QUOTES, 'UTF-8');
	
	// Daten sichern
	$_POST['player'] = escape($_POST['player']);
	
	$data = false;

	// Spieler-ID
	if(is_numeric(trim($_POST['player']))) {
		// Daten abfragen
		$query = query("
			SELECT
				playerID,
				playerName,
				player_allianzenID
			FROM
				".GLOBPREFIX."player
			WHERE
				playerID = ".$_POST['player']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler mit dieser ID existiert
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
		}
	}
	
	// Name eingegeben oder ID nicht gefunden
	if(!$data) {
		// * als Wildcard benutzen
		$_POST['player'] = str_replace('*', '%', $_POST['player']);
		
		// Daten abfragen (doppelt escapen wegen LIKE-Bug)
		$query = query("
			SELECT
				playerID,
				playerName,
				player_allianzenID
			FROM
				".GLOBPREFIX."player
			WHERE
				playerName LIKE '".escape($_POST['player'])."'
			ORDER BY playerID ASC
			LIMIT 1
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Allianz mit diesem Namen
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
		}
	}
	
	// Allianz nicht gefunden
	if(!$data) {
		$tmpl->error = 'Der Spieler wurde nicht gefunden!';
	}
	// Allianz gesperrt
	else if($user->protectedAllies AND in_array($data['player_allianzenID'], $user->protectedAllies)) {
		$tmpl->error = 'Du hast keine Berechtigung, diese Allianz anzuzeigen!';
	}
	else {
		$heute = strtotime('today');
		$gestern = $heute-86400;
		
		// Systeme abfragen
		$query = query("
			SELECT
				systemeID,
				systemeX,
				systemeZ,
				systeme_galaxienID,
				systemeUpdate
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
			WHERE
				planeten_playerID = ".$data['playerID']."
				".($user->protectedGalas ? "AND systeme_galaxienID NOT IN(".implode(', ', $user->protectedGalas).")" : '')."
			GROUP BY
				systemeID
			ORDER BY
				systemeID
			LIMIT 200
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
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
					<td><a href="http://www.omega-day.com/game/index.php?op=system&amp;sys='.$row['systemeID'].'" target="_blank">[in OD &ouml;ffnen]</a></td>
				</tr>';
			}
			$tmpl->content .= '
			</table>';
		}
		// keine Systeme gefunden
		else {
			$tmpl->content .= '
			<br />
			<div class="center" style="font-weight:bold">Keine Systeme gefunden!</div>
			<br />';
		}
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(17, 'lässt sich die Systeme des Spielers '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' anzeigen');
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