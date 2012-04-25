<?php
/**
 * pages/stats/highscore.php
 * Scan-Highscore
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



$content =& $csw->data['highscore']['content'];

$content = '
	<div class="hl2">User-Highscore</div>';

$conds = '';

// gesperrte Allianzen
if($user->protectedAllies) {
	$conds = " WHERE user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
}

// Sortierung
$sort = array(
	'points'=>'userDBPunkte DESC',
	'sysscanned'=>'userSysScanned DESC',
	'sysupdated'=>'userSysUpdated DESC',
	'planscanned'=>'userPlanScanned DESC',
	'planupdated'=>'userPlanUpdated DESC',
	'name'=>'user_playerName ASC',
	'ally'=>'user_allianzenID ASC, user_playerName ASC'
);

if(!isset($_GET['sort']) OR !isset($sort[$_GET['sort']])) {
	$sort = $sort['points'];
}
else {
	$sort = $sort[$_GET['sort']];
}

// bei anderer Sortierung Ränge abfragen
if($sort != 'userDBPunkte DESC') {
	$rang = array();
	$lastrang = 1;
	$lastrangpoints = 0;
	$i = 1;
	
	$query = query("
		SELECT
			user_playerID,
			userDBPunkte
		FROM
			".PREFIX."user
		".$conds."
		ORDER BY
			userDBPunkte DESC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		// gleiche Punktzahl
		if($lastrangpoints == $row['userDBPunkte']) {
			$rang[$row['user_playerID']] = $lastrang;
		}
		// unterschiedliche Punktzahl
		else {
			$rang[$row['user_playerID']] = $i;
			$lastrang = $i;
		}
		
		
		$lastrangpoints = $row['userDBPunkte'];
		$i++;
	}
}
else $rang = false;


// Daten abfragen
$query = query("
	SELECT
		user_playerID,
		user_playerName,
		user_allianzenID,
		userDBPunkte,
		userSysScanned,
		userSysUpdated,
		userPlanScanned,
		userPlanUpdated,
		
		allianzenTag
	FROM
		".PREFIX."user
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = user_allianzenID
	".$conds."
	ORDER BY
		".$sort."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$content2 = '
		<table class="data allyfiltert" style="margin:auto">
		<tr>
			<th><a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=points">Rang</a></th>
			<th><a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=name">Spieler</a></th>
			<th><a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=ally">Allianz</a></th>
			<th colspan="2">Systeme <a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=sysscanned">gescannt</a>/<a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=sysupdated">aktualisiert</a></th>
			<th colspan="2">Planeten <a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=planscanned">gescannt</a>/<a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=planupdated">aktualisiert</a></th>
			<th><a class="link" data-link="index.php?p=stats&amp;sp=highscore&amp;sort=points">Summe</a></th>
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
	
	if(!$rang) {
		// gleiche Punktzahl
		if($lastrangpoints == $row['userDBPunkte']) {
			$rangpos = $lastrang;
		}
		// unterschiedliche Punktzahl
		else {
			$rangpos = $i;
			$lastrang = $i;
		}
		$lastrangpoints = $row['userDBPunkte'];
	}
	else {
		$rangpos = $rang[$row['user_playerID']];
	}
	
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
			<td>'.$row['userSysScanned'].'</td>
			<td>'.$row['userSysUpdated'].'</td>
			<td>'.$row['userPlanScanned'].'</td>
			<td>'.$row['userPlanUpdated'].'</td>
			<td>'.$row['userDBPunkte'].'</td>
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


$content .= $content2.'
		<br />
		<div class="small hint center" style="line-height:18px">
			eingescannte Systeme und Planeten z&auml;hlen doppelt so viel wie aktualisierte
			<br />
			ein aktualisierter Scan wird maximal alle 24 Stunden zur Highscore dazugez&auml;hlt
		</div>';

// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich die User-Highscore anzeigen');
}



?>