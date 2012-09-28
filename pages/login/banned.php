<?php

/**
 * pages/login/banned.php
 * Account gesperrt oder noch nicht freigeschaltet
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// FoW abfangen
if(isset($_GET['p']) AND $_GET['p'] == 'fow') {
	diefow();
}

// Login-Template
$tmpl = new template_login;
$tmpl->content = '<br /><br />';

// Meldung
/*
	1 - manuell gebannt
	2 - automatisch gebannt
	3 - noch nicht freigeschaltet
*/
if($user->banned == 1) $tmpl->content .= 'Dein Account wurde gesperrt!';
else if($user->banned == 2) $tmpl->content .= 'Du wurdest automatisch gesperrt, weil du oder deine Allianz<br />keine Registrier-Erlaubnis mehr haben!<br /><br />Wenn du wieder in eine zugelassene Allianz gewechselt bist, kann es<br />mehrere Stunden dauern, bis die Datenbank den Wechsel erkennt.';
else $tmpl->content .= 'Dein Account ist noch nicht freigeschaltet.';

// User mit Freischaltrechten ermitteln, zuerst im Cache nachschauen
if(!($fruser = $cache->get('fruser'.$user->allianz))) {
	$frally = array();
	$frall = array();
	// geeignete Rechtelevel suchen
	foreach($rechte as $key=>$data) {
		if($data['verwaltung_user_register']) $frall[] = $key;
		// Allianzfreischaltrecht nicht bei Allianzwechsel-Sperrungen
		else if($user->banned != 2 AND $data['verwaltung_userally']) $frally[] = $key;
	}
	
	// User-Freischaltrechte (gesamt / Ally)
	$cond = array(
		"userRechte LIKE '%s:24:\"verwaltung_user_register\";b:1;%'",
		"(userRechte LIKE '%s:19:\"verwaltung_userally\";b:1;%' AND user_allianzenID = ".$user->allianz.")",
	);
	// Rechtelevel -> gesamt-Freischaltrechte
	if(count($frall)) {
		$cond[] = "userRechtelevel IN (".implode(', ', $frall).")";
	}
	// Rechtelevel -> Ally-Freischaltrechte
	if(count($frally)) {
		$cond[] = "(userRechtelevel IN (".implode(', ', $frally).") AND user_allianzenID = ".$user->allianz.")";
	}
	// Query generieren
	$sql = "
		SELECT
			user_playerName,
			user_allianzenID,
			userRechte,
			userRechtelevel,
			registerAllyRechte
		FROM
			".PREFIX."user
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON user_allianzenID = allianzenID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = allianzenID
		WHERE
			userBanned = 0
			AND (".implode(' OR ', $cond).")";
	
	$query = query($sql) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$fruser = array();
	
	// Datensätze vorhanden
	if(mysql_num_rows($query)) {
		while($row = mysql_fetch_assoc($query)) {
			$add2user = false;
			//
			$urechte = json_decode($row['userRechte'], true);
			// Gesamt-Freischaltrechte
			if(isset($urechte['verwaltung_user_register']) AND $urechte['verwaltung_user_register']) {
				$fruser[] = $row['user_playerName'];
			}
			// Ally-Freischaltrechte
			else if($row['user_allianzenID'] = $user-> allianz AND isset($urechte['verwaltung_userally']) AND $urechte['verwaltung_userally']) $fruser[] = $row['user_playerName'];
			else {
				$urechte = isset($rechte[$row['userRechtelevel']]) ? $rechte[$row['userRechtelevel']] : $rechte[0];
				$arechte = explode('+', $row['registerAllyRechte']);
				// Rechtelevel: Gesamt-Freischaltrechte
				if($urechte['verwaltung_user_register'] AND !in_array('verwaltung_user_register', $arechte)) {
					$fruser[] = $row['user_playerName'];
				}
				// Rechtelevel: Ally-Freischaltrechte
				else if($urechte['verwaltung_userally'] AND !in_array('verwaltung_userally', $arechte)) {
					$fruser[] = $row['user_playerName'];
				}
			}
		}
	}
	
	// Freischalt-User in den Cache laden (10min)
	$cache->set('fruser'.$user->allianz, $fruser, 600);
}

// User mit Freischaltrechten vorhanden
if(count($fruser)) {
	$tmpl->content .= '<br /><br />';
	// nur 1 User kann freischalten
	if(count($fruser) == 1) {
		$tmpl->content .= ($user->banned == 2 ? 'Wenn nicht, schicke' : 'Schicke').' bitte zur '.($user->banned != 3 ? 'erneuten ' : '').'Freischaltung eine Comm an <br />
<span style="font-weight:bold">'.htmlspecialchars($fruser[0]).'</span>
<br />';
	}
	// mehrere User können freischalten
	else {
		$tmpl->content .= 'Schicke bitte zur '.($user->banned != 3 ? 'erneuten ' : '').'Freischaltung eine Comm an einen der folgenden User:
<br /><br />
<div style="font-weight:bold;line-height:18pt;padding:0px 15px">';
		foreach($fruser as $name) {
			$tmpl->content .= '&nbsp; '.htmlspecialchars($name, ENT_COMPAT, 'UTF-8').' &nbsp;';
		}
		$tmpl->content .= '</div>';
	}
}
// niemand kann freischalten
else $tmpl->content .= '<br />';

$tmpl->content .= '<br /><br />
<a href="index.php?p=logout" class="small hint">[Logout]</a>
<br /><br />';

// ausgeben
$tmpl->output();



?>