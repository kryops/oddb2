<?php
/**
 * pages/stats/gesinnung.php
 * Gesinnungs-Highscore
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



/**
 * nettesten bzw. bösesten Spieler ermitteln und ausgeben
 * @param $typ int
 *		1: nettester Spieler
 *		0: bösester Spieler
 * @return HTML
 */
function gesinnungminmax($typ) {
	$content = '';
	
	$query = query("
		SELECT
			playerID,
			playerName,
			player_allianzenID,
			playerGesinnung,
			
			allianzenTag
		FROM
			".GLOBPREFIX."player
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = player_allianzenID
		WHERE
			playerGesinnung IS NOT NULL
		ORDER BY
			playerGesinnung ".($typ ? "DESC" : "ASC")."
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	if(mysql_num_rows($query)) {
		$data = mysql_fetch_assoc($query);
		
		$content .= '
			<b>'.($typ ? 'nettester' : 'b&ouml;sester').' Spieler</b>: 
			&nbsp;
			<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['playerID'].'&amp;ajax">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a> &nbsp;';
		// Allianz
		if($data['player_allianzenID']) {
			$content .= '
			<a class="link winlink contextmenu small2" data-link="index.php?p=show_ally&amp;id='.$data['player_allianzenID'].'&amp;ajax">'.(
				($data['allianzenTag'] != NULL) 
				? htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8')
				: '<i>unbekannt</i>'
			).'</a>';
		}
		// allianzlos
		else $content .= '<span class="small2" style="font-style:italic">allianzlos</span>';
		$content .= '
			&nbsp;
			<span style="color:#'.gesinnung($data['playerGesinnung']).'">('.$data['playerGesinnung'].')</span>';
		if($typ) {
			$content .= '
		<br />';
		}
	}
	
	// Inhalt zurückgeben
	return $content;
}



/**
 * Inhalt 
 */

$content =& $csw->data['gesinnung']['content'];

$content = '
	<div class="hl2">Gesinnungs-Highscore</div>
	<br />
	<div class="formcontent center">
';

// nettester und bösester Spieler
$content .= gesinnungminmax(1).gesinnungminmax(0);

$content .= '
	</div>
	<br /><br />';


// Daten abfragen
$query = query("
	SELECT
		user_playerID,
		user_playerName,
		user_allianzenID,
		
		playerGesinnung,
		
		allianzenTag
	FROM
		".PREFIX."user
		LEFT JOIN ".GLOBPREFIX."player
			ON playerID = user_playerID
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = user_allianzenID
	ORDER BY
		playerGesinnung DESC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$content2 = '
		<table class="data allyfiltert" style="margin:auto">
		<tr>
			<th>Rang</th>
			<th>Spieler</th>
			<th>Allianz</th>
			<th>Gesinnung</th>
		</tr>';

$allies = array();
$i = 1;
$lastrang = 1;
$lastrangpoints = 0;

while($row = mysql_fetch_assoc($query)) {
	// Allianz der Liste hinzufügen
	if(!isset($allies[$row['user_allianzenID']])) {
		$allies[$row['user_allianzenID']] = array($row['allianzenTag'], 1);
	}
	else {
		$allies[$row['user_allianzenID']][1]++;
	}
	
	// gleiche Punktzahl
	if($lastrangpoints == $row['playerGesinnung']) {
		$rangpos = $lastrang;
	}
	// unterschiedliche Punktzahl
	else {
		$rangpos = $i;
		$lastrang = $i;
	}
	$lastrangpoints = $row['playerGesinnung'];
	
	$content2 .= '<tr data-ally="'.$row['user_allianzenID'].'"'.($row['user_playerID'] == $user->id ? ' class="trhighlight"' : '').'>
		<td>'.$rangpos.'</td>
		<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['user_playerID'].'&amp;ajax">'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').'</a></td>
		<td style="white-space:nowrap">';
	// Allianz
	if($row['user_allianzenID']) {
		$content2 .= '
		<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['user_allianzenID'].'&amp;ajax">'.(
			($row['allianzenTag'] != NULL) 
			? htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8')
			: '<i>unbekannt</i>'
		).'</a>';
	}
	// allianzlos
	else $content2 .= '&nbsp;';
	$content2 .= '</td>
			<td>'.(($row['playerGesinnung'] == NULL) ? '<i>unbekannt</i>' : '<span style="color:#'.gesinnung($row['playerGesinnung']).'">'.$row['playerGesinnung'].'</span>').'</td>
		</tr>';
	
	// Rang hochzählen
	$i++;
}

$content2 .= '
		</table>';


// Allianzen-Auswahl anzeigen
asort($allies);

if(count($allies) > 1) {
	$content .= '
		<div class="allyfilter center small2">';
	foreach($allies as $key=>$data) {
		// allianzlos
		if(!$key) $data[0] = '<i>allianzlos</i>';
		// unbekannte Allianz
		else if($data[0] == NULL) $data[0] = '<i>unbekannt</i>';
		else $data[0] = htmlspecialchars($data[0], ENT_COMPAT, 'UTF-8');
		
		$content .= '&nbsp; 
		<span style="white-space:nowrap">
		<input type="checkbox" name="'.$key.'" checked="checked" /> <a name="'.$key.'">'.$data[0].' ('.$data[1].')</a>
		</span>&nbsp; ';
	}
	$content .= '
		</div>
		<br />';
}


$content .= $content2;

// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich die Gesinnungs-Highscore anzeigen');
}



?>