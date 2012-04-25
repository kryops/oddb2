<?php
/**
 * pages/show_player/history.php
 * Allianz-History anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten sichern
$_GET['id'] = (int)$_GET['id'];

// Spieler-Daten abfragen
$query = query("
	SELECT
		playerName
	FROM
		".GLOBPREFIX."player
	WHERE
		playerID = ".$_GET['id']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Spieler nicht gefunden
if(!mysql_num_rows($query)) {
	$tmpl->error = 'Der Spieler wurde nicht gefunden!';
	$tmpl->output();
	die();
}

$data = mysql_fetch_assoc($query);


$tmpl->name = htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')';
$tmpl->icon = 'player';

// History abfragen
$query = query("
	SELECT
		allyhistoryTime,
		allyhistory_allianzenID,
		
		allianzenTag,
		
		statusStatus
	FROM
		".GLOBPREFIX."player_allyhistory
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = allyhistory_allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID
	WHERE
		allyhistory_playerID = ".$_GET['id']."
	ORDER BY
		allyhistoryTime ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Ausgabe
$tmpl->content = '
	<div class="fhl2"><a onclick="$(this.parentNode).siblings(\'table\').toggle()">Allianz-History</a></div>
	<table class="tnarrow">';

// Daten anzeigen
if(mysql_num_rows($query)) {
	while($row = mysql_fetch_assoc($query)) {
			$tmpl->content .= '
	<tr>
		<td>'.datum($row['allyhistoryTime']).'</td>
		<td style="padding-left:5px">';
			// hat Allianz
			if($row['allianzenTag'] != NULL) {
				if($row['statusStatus'] == NULL) $row['statusStatus'] = 0;
				$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allyhistory_allianzenID'].'&amp;ajax">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
				// Status, wenn nicht eigene Allianz
				if($row['allyhistory_allianzenID'] != $user->allianz) {
					$tmpl->content .= '&nbsp;<span class="small hint">('.$status[$row['statusStatus']].')</span>';
				}
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
}
// keine History verfügbar
else {
	$tmpl->content .= '
	<tr>
		<td align="center">keine History verfügbar</td>
	</tr>';
}

$tmpl->content .= '</table>';

// Log-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'lässt die Allianz-History des Spielers '.$data['playerName'].' ('.$_GET['id'].') anzeigen');
}



?>