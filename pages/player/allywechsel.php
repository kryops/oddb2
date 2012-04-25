<?php
/**
 * pages/player/allywechsel.php
 * Allianzwechsel anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten unvollständig
if(!isset($_POST['days'], $_POST['ally'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// keine Berechtigung
else if(!$user->rechte['allywechsel']) {
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
			$ally = "AND (allyhistory_allianzenID ".$ally." OR allyhistoryLastAlly ".$ally.")";
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
				
				$ally = "AND (allyhistory_allianzenID = ".$ally." OR allyhistoryLastAlly = ".$ally.")";
			}
		}
	}
	
	// gesperrte Allianzen
	if($user->protectedAllies) {
		$ally .= " AND allyhistory_allianzenID NOT IN(".implode(', ', $user->protectedAllies).") AND (allyhistoryLastAlly IS NULL OR allyhistoryLastAlly NOT IN(".implode(', ', $user->protectedAllies)."))";
	}
	
	// Daten abfragen
	$query = query("
		SELECT
			allyhistoryTime,
			allyhistory_playerID,
			allyhistory_allianzenID,
			allyhistoryLastAlly,
			
			playerName,
			
			a1.allianzenTag as a1_allianzenTag,
			a2.allianzenTag as a2_allianzenTag
		FROM
			".GLOBPREFIX."player_allyhistory
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = allyhistory_playerID
			LEFT JOIN ".GLOBPREFIX."allianzen a1
				ON a1.allianzenID = allyhistoryLastAlly
			LEFT JOIN ".GLOBPREFIX."allianzen a2
				ON a2.allianzenID = allyhistory_allianzenID
		WHERE
			allyhistoryTime > ".(time()-86400*$_POST['days'])."
			AND allyhistoryLastAlly IS NOT NULL
			".$ally."
		ORDER BY
			allyhistoryTime DESC
		LIMIT 500
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$tmpl->content .= '<br />';
	
	// keine Wechsel erfasst
	if(!mysql_num_rows($query)) {
		$tmpl->content .= '
		<div class="center">Es wurden keine Allianzwechsel erfasst, die den Kriterien entsprechen.</div>';
	}
	// Wechsel erfasst
	else {
		$tmpl->content .= '
		<table class="data" style="margin:auto">
		<tr>
			<th>Datum</th>
			<th>Spieler</th>
			<th>Wechsel</th>
		</tr>';
		
		while($row = mysql_fetch_assoc($query)) {
			$tmpl->content .= '
		<tr>
			<td>'.datum($row['allyhistoryTime']).'</td>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['allyhistory_playerID'].'">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td>';
			// alte Allianz
			
			// hat Allianz
			if($row['a1_allianzenTag'] != NULL) {
				$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allyhistoryLastAlly'].'&amp;ajax">'.htmlspecialchars($row['a1_allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
			}
			// allianzlos
			else if(!$row['allyhistoryLastAlly']) {
				$tmpl->content .= '<i>keine</i>';
			}
			// unbekannte Allianz
			else {
				$tmpl->content .= '<i>unbekannt</i>';
			}
			
			$tmpl->content .= ' &nbsp;&rarr;&nbsp; ';
			
			// neue Allianz
			
			// hat Allianz
			if($row['a2_allianzenTag'] != NULL) {
				$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allyhistory_allianzenID'].'&amp;ajax">'.htmlspecialchars($row['a2_allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
			}
			// allianzlos
			else if(!$row['allyhistory_allianzenID']) {
				$tmpl->content .= '<i>keine</i>';
			}
			// unbekannte Allianz
			else {
				$tmpl->content .= '<i>unbekannt</i>';
			}
			
			$tmpl->content .= '
			</td>
		</tr>';
			
		}
		
		$tmpl->content .= '
		</table>';
	}
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(22, 'lässt sich die Allianzwechsel der letzten '.$_POST['days'].' Tage anzeigen');
	}
}

// Leerzeile vor Fehlermeldung
if($tmpl->error) {
	$tmpl->error = '<br />'.$tmpl->error;
}

// Ausgabe
$tmpl->output();



?>