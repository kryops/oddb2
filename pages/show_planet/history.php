<?php
/**
 * pages/show_planet/history.php
 * Eigentümer-History anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten sichern
$_GET['id'] = (int)$_GET['id'];

$data = false;

// Existenz und Berechtigung ermitteln
$query = query("
	SELECT
		planetenID,
		planeten_playerID,
		
		systeme_galaxienID,
		
		player_allianzenID,
		
		register_allianzenID,
		
		statusStatus
	FROM
		".PREFIX."planeten
		LEFT JOIN ".PREFIX."systeme
			ON systemeID = planeten_systemeID
		LEFT JOIN ".GLOBPREFIX."player
			ON playerID = planeten_playerID
		LEFT JOIN ".PREFIX."register
			ON register_allianzenID = player_allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = player_allianzenID
	WHERE
		planetenID = ".$_GET['id']."
	ORDER BY planetenID ASC
	LIMIT 1
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Planet mit dieser ID existiert
if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);

show_planet_rechte($data);

// Log-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'lässt die Inhaber-History vom Planet '.$data['planetenID'].' anzeigen');
}

// History ermitteln
$query = query("
	SELECT
		history_playerID,
		historyTime,
		
		playerName,
		player_allianzenID,
		
		allianzenTag,
		
		statusStatus
	FROM
		".PREFIX."planeten_history
		LEFT JOIN ".GLOBPREFIX."player
			ON playerID = history_playerID
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = player_allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID
	WHERE
		history_planetenID = ".$_GET['id']."
	ORDER BY historyTime ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Ausgabe
$tmpl->content = '
	<div class="fhl2"><a onclick="$(this.parentNode).siblings(\'table\').toggle()">Eigentümer-History</a></div>
	<table class="tnarrow">';

while($row = mysql_fetch_assoc($query)) {
	$tmpl->content .= '<tr>
		<td>'.datum($row['historyTime']).'</td>
		<td style="padding-left:5px">';
	// Inhaber bekannt
	if($row['playerName'] != NULL) {
		$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['history_playerID'].'&amp;ajax">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a> &nbsp;';
		// Allianz
		if($row['allianzenTag'] != NULL) {
			$tmpl->content .= '
			<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
		}
		// allianzlos
		else if($row['player_allianzenID'] == 0) {
			$tmpl->content .= '<span class="small" style="font-style:italic">allianzlos</span>';
		}
		else {
			$tmpl->content .= '<span class="small" style="font-style:italic">unbekiannt</span>';
		}
	}
	// frei
	else if($row['history_playerID'] == 0) {
		$tmpl->content .= '<i>frei</i>';
	}
	// unbekannt
	else {
		$tmpl->content .= '<i>unbekannt</i>';
	}
	$tmpl->content .= '</td>
	</tr>';
}
$tmpl->content .= '</table>';



?>