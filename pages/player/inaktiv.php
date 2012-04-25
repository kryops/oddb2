<?php
/**
 * pages/player/inaktiv.php
 * Inaktivensuche durchführen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten unvollständig
if(!isset($_POST['days'], $_POST['ally'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// keine Berechtigung
else if(!$user->rechte['inaktivensuche']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
}
else {
	// Daten sichern
	$_POST['days'] = (int)$_POST['days'];
	if(!$_POST['days']) $_POST['days'] = 7;
	
	$ally = '';
	
	// Allianz-IDs
	if($_POST['ally'] != '') {
		$ally = db_multiple($_POST['ally'], true);
		
		if($ally) {
			$ally = "AND player_allianzenID ".$ally;
		}
		// Tag oder Name eingegeben
		else {
			$_POST['ally'] = escape(escape(str_replace('*', '%', $_POST['ally'])));
			
			$query = query("
				SELECT
					allianzenID
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenTag LIKE '".$_POST['ally']."'
					OR allianzenName LIKE '".$_POST['ally']."'
				ORDER BY
					allianzenID DESC
				LIMIT 1
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz nicht gefunden
			if(!mysql_num_rows($query)) {
				$tmpl->content = '
					<br />
					<div class="center" style="font-weight:bold">Allianz nicht gefunden!</div>
					<br />';
				$ally = '';
			}
			else {
				$ally = mysql_fetch_assoc($query);
				$ally = $ally['allianzenID'];
				
				$ally = "AND player_allianzenID = ".$ally;
			}
		}
	}
	
	// gesperrte Allianzen
	if($user->protectedAllies) {
		$ally .= " AND (player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
	}
	
	// Daten abfragen
	$query = query("
		SELECT
			playerID,
			playerName,
			playerActivity,
			player_allianzenID,
			
			allianzenTag,
			
			statusStatus
		FROM
			".GLOBPREFIX."player
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = allianzenID
		WHERE
			playerDeleted = 0
			AND playerUmod = 0
			AND playerActivity > 0
			AND playerActivity < ".(time()-86400*$_POST['days'])."
			".$ally."
		ORDER BY
			playerActivity DESC
		LIMIT 500
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$tmpl->content .= '<br />';
	
	// keine Spieler gefunden
	if(!mysql_num_rows($query)) {
		$tmpl->content .= '
		<div class="center">Es wurden keine Spieler gefunden, die den Kriterien entsprechen.</div>';
	}
	// Spieler gefunden
	else {
		$tmpl->content .= '
		<table class="data" style="margin:auto">
		<tr>
			<th>Spieler</th>
			<th>Allianz</th>
			<th>Status</th>
			<th>letzte registrierte Aktivit&auml;t</th>
		</tr>';
		
		while($row = mysql_fetch_assoc($query)) {
			$tmpl->content .= '
		<tr>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['playerID'].'">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td>';
			
			// hat Allianz
			if($row['allianzenTag'] != NULL) {
				$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
			}
			// allianzlos
			else if(!$row['player_allianzenID']) {
				$tmpl->content .= '<i>keine</i>';
			}
			// unbekannte Allianz
			else {
				$tmpl->content .= '<i>unbekannt</i>';
			}
			
			$tmpl->content .= '</td>
			<td>';
			if($row['allianzenTag'] != NULL) {
				if($row['statusStatus'] == NULL) $row['statusStatus'] = 0;
				$tmpl->content .= '<span '.$status_color[$row['statusStatus']].'>'.$status[$row['statusStatus']].'</span>';
			}
			$tmpl->content .= '</td>
			<td>'.datum($row['playerActivity']).'</td>
		</tr>';
			
		}
		
		$tmpl->content .= '
		</table>';
	}
	
	
	// Log-Eintrag
	$log = 'lässt sich inaktive Spieler ';
	if($_POST['ally']) {
		$log .= 'der Allianz '.htmlspecialchars($_POST['ally'], ENT_COMPAT, 'UTF-8').' ';
	}
	$log .= 'anzeigen';
	if($config['logging'] >= 2) {
		insertlog(22, $log);
	}
}

// Leerzeile vor Fehlermeldung
if($tmpl->error) {
	$tmpl->error = '<br />'.$tmpl->error;
}

// Ausgabe
$tmpl->output();




?>