<?php
/**
 * pages/forschung.php
 * Forschungsübersicht
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Forschung';


// keine Berechtigung
if(!$user->rechte['show_player_db_ally'] AND !$user->rechte['show_player_db_meta'] AND !$user->rechte['show_player_db_other']) {
	$tmpl->abort('Du hast keine Berechtigung!');
}


// Nötige Includes und Berechnungen
General::loadClass('Forschung');

$forschungOld = time()-$config['scan_veraltet_forschung']*86400;


// Bedingungen
$conds = array();

if($user->allianz AND !$user->rechte['show_player_db_ally']) {
	$conds[] = "user_allianzenID != ".$user->allianz;
}
if(!$user->rechte['show_player_db_meta']) {
	$conds[] = "(user_allianzenID = ".$user->allianz." OR statusStatus IS NULL OR statusStatus != ".$status_meta.")";
}
if(!$user->rechte['show_player_db_other']) {
	$conds[] = "statusStatus = ".$status_meta;
}

if($user->protectedAllies) {
	$conds[] = "user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
}


if(count($conds)) {
	$conds = "WHERE
		".implode(" AND ", $conds);
}
else $conds = '';


// Daten abfragen
$query = query("
	SELECT
		user_playerID,
		user_playerName,
		user_allianzenID,
		userForschung,
		
		playerRasse,
		
		allianzenTag
	FROM
		".PREFIX."user
		LEFR JOIN ".GLOBPREFIX."player
			ON playerID = user_playerID
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = user_allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID
	".$conds."
	ORDER BY
		user_allianzenID ASC,
		user_playerName ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());


$allies = array();
$content2 = '';
$count = mysql_num_rows($query);


while($row = mysql_fetch_assoc($query)) {
	
	// Allianz der Liste hinzufügen
	if(!isset($allies[$row['user_allianzenID']])) {
		$allies[$row['user_allianzenID']] = array($row['allianzenTag'], 1);
	}
	else {
		$allies[$row['user_allianzenID']][1]++;
	}
	
	$content2 .= '
<tr data-ally="'.$row['user_allianzenID'].'"'.($row['user_playerID'] == $user->id ? ' class="trhighlight2"' : '').'>
	<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['user_playerID'].'&amp;ajax">'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').'</a><br /><br />
	';
	// Allianz
	if($row['user_allianzenID']) {
		$content2 .= '
		<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['user_allianzenID'].'&amp;ajax">'.(
			($row['allianzenTag'] != NULL) 
			? htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8')
			: '<i>unbekannt</i>'
		).'</a><br /><br />';
	}
	
	// Rasse
	if(isset($rassen2[$row['playerRasse']])) {
		$content2 .= ' &nbsp; <img src="img/layout/leer.gif" class="rasse '.$rassen2[$row['playerRasse']].'" alt="" />';
	}
	
	
	$content2 .= '</td>';
	
	// Forschungen
	$forschung = Forschung::getUserArray($row['userForschung']);
	
	$content2 .= '<td style="text-align:left">';
	
	foreach(Forschung::$kategorien as $i=>$name) {
		
		$update = $forschung['update'][$i];
		$content2 .= '';
		
		$content2 .= '
			<p>
				'.$name.'
				&nbsp; &nbsp; <span class="'.($update < $forschungOld ? 'red' : 'green').'">aktualisiert: '.($update ? datum($update) : '<i>nie</i>').'</span>
			</p>';
		
		foreach($forschung[$i] as $fid) {
			if($f = Forschung::get($fid)) {
				$content2 .= '<img src="'.Forschung::$baseUrl.$f['forschungPfad'].'" title="'.h($f['forschungName']).'" class="icon_forschung" data-forschung="'.$fid.'" />';
			}
		}
	}
	
	if($forschung['current'] AND $f = Forschung::get($forschung['current'])) {
		$content2 .= '
			<br />
			<p>aktuelle Forschung: 
			<img src="'.Forschung::$baseUrl.$f['forschungPfad'].'" title="'.h($f['forschungName']).'" class="icon_forschung icon_forschung_current" data-forschung="'.$forschung['current'].'" />';
		
		if($forschung['current_end']) {
			$content2 .= ' &nbsp; (Ende: '.datum($forschung['current_end']).')</p>';
		}
		
	}
	
	$content2 .= '</td>';
	
}



$tmpl->content = '
	<div class="forschung_container">';


if($count) {
	
	// Forschungs-Filter
	$tmpl->content .= '
	<div class="fcbox forschung_filter">
		<div class="fhl2 bold">Benutzer nach Forschungen filtern</div>
		<div class="icontent">';
	
	foreach(Forschung::$kategorien as $i=>$name) {
		
		$tmpl->content .= '
			<div class="forschung_filter_area">
				<p class="bold center">'.$name.'</p>';
		
		$forschung = Forschung::getKategorie($i);
		
		foreach($forschung as $fid=>$f) {
			$tmpl->content .= '<img src="'.Forschung::$baseUrl.$f['forschungPfad'].'" title="'.h($f['forschungName']).'" class="icon_forschung_filter" data-forschung="'.$fid.'" />';
		}
		
		$tmpl->content .= '</div>';
		
	}
	
	$tmpl->content .= '
		</div>
	</div>
	<br />';
	
	
	// Allianzen-Auswahl anzeigen
	asort($allies);
	
	if(count($allies) > 1) {
		$tmpl->content .= '
		<div class="allyfilter center small2">';
		foreach($allies as $key=>$data) {
			// allianzlos
			if(!$key) $data[0] = '<i>allianzlos</i>';
			// unbekannte Allianz
			else if($data[0] == NULL) $data[0] = '<i>unbekannt</i>';
			else $data[0] = htmlspecialchars($data[0], ENT_COMPAT, 'UTF-8');
			
			$tmpl->content .= '&nbsp; 
			<span style="white-space:nowrap">
			<input type="checkbox" name="'.$key.'" checked="checked" /> <a name="'.$key.'">'.$data[0].' ('.$data[1].')</a>
			</span>&nbsp; ';
		}
		$tmpl->content .= '
		</div>
		<br />';
	}
	
	
	$tmpl->content .= '
	<table class="data allyfiltert small2" style="width:100%">
	<tr>
		<th style="width:80px">Spieler</th>
		<th>Forschung</th>
	</tr>
	'.$content2.'
	</table>';
}

// keine User werden angezeigt
else {
	
	$tmpl->content .= '
	<br /><br />
	<div class="center bold">Du hast f&uuml;r keinen der registrierten Benutzer die Berechtigung, dessen Forschungen zu sehen!</div>
	<br /><br />';
	
}
			
			
$tmpl->content .= '
	</div>';


// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich die Forschungsübersicht anzeigen');
}


$tmpl->output();

?>