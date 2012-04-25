<?php
/**
 * pages/show_player/rechte.php
 * Berechtigungen eines Spielers
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten sichern
$_GET['id'] = (int)$_GET['id'];

// Spieler-Daten abfragen
$query = query("
	SELECT
		user_playerName,
		user_allianzenID,
		userRechtelevel,
		userRechte,
		
		register_allianzenID,
		registerProtectedAllies,
		registerProtectedGalas,
		registerAllyRechte,
		
		statusStatus
	FROM
		".PREFIX."user
		LEFT JOIN ".PREFIX."register
			ON register_allianzenID = user_allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = user_allianzenID
	WHERE
		user_playerID = ".$_GET['id']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Spieler nicht gefunden
if(!mysql_num_rows($query)) {
	$tmpl->error = 'Der Spieler wurde nicht gefunden!';
	$tmpl->output();
	die();
}

$data = mysql_fetch_assoc($query);


$tmpl->name = 'Berechtigungen von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')';
$tmpl->icon = 'player';

// Allianz gesperrt
if($user->protectedAllies AND in_array($data['user_allianzenID'], $user->protectedAllies)) {
	$tmpl->error = 'Du hast keine Berechtigung, Spieler dieser Allianz anzuzeigen!';
	$tmpl->output();
	die();
}

// Ausgabe
$tmpl->content = '
	<div class="icontent small2" style="width:700px;line-height:15px">
		<b>Rechtelevel</b>: ';
			
// Berechtigungen
$r = getrechte(
	$data['userRechtelevel'],
	$data['registerProtectedAllies'],
	$data['registerProtectedGalas'],
	$data['registerAllyRechte'],
	$data['userRechte']
);

// Rechtelevel-Anzeige
// normal
if(!$r[0]) {
	$tmpl->content .= $rechte[$r[4]]['name'];
}
// eingeschränkt
else if($r[0] == 1) {
	$tmpl->content .= '<span class="red"><i>eingeschränkt:</i> '.$rechte[$r[4]]['name'].'</span>';
}
// modifiziert
else {
	$tmpl->content .= '<span class="yellow"><i>modifiziert:</i> '.$rechte[$r[4]]['name'].'</span>';
}

$tmpl->content .= '
			<br />';

// Allianz-Einschränkungen
if($data['registerAllyRechte'] != '' OR $data['registerProtectedAllies'] != '' OR $data['registerProtectedGalas'] != '') {
	$tmpl->content .= '
		<b>durch Allianz eingeschr&auml;nkt</b>: 
		<div class="red" style="padding-left:12px">';
	// gesperrte Berechtigungen
	if($data['registerAllyRechte'] != '') {
		$ar = explode('+', $data['registerAllyRechte']);
		foreach($ar as $key=>$val) {
			$ar[$key] = $rechtenamen[$val];
			// durch User-Berechtigungen aufgehoben
			if($r[1][$val]) {
				$ar[$key] = '<strike>'.$ar[$key].'</strike>';
			}
		}
	}
	else {
		$ar = array();
	}
	// gesperrte Allianzen
	if($data['registerProtectedAllies'] != '') {
		$data['registerProtectedAllies'] = explode('+', $data['registerProtectedAllies']);
		
		$query = query("
			SELECT
				allianzenID,
				allianzenTag
			FROM
				".GLOBPREFIX."allianzen
			WHERE
				allianzenID IN (".implode(', ', $data['registerProtectedAllies']).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$pa = array();
			while($row = mysql_fetch_assoc($query)) {
				$pa[] = '<a class="link red" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
			}
			if($r[2]) {
				$ar[] = 'Zugriff auf Allianz'.(count($data['registerProtectedAllies']) != 1 ? 'en' : '').' '.implode(', ', $pa).' gesperrt';
			}
			// durch User-Berechtigungen aufgehoben
			else {
				$ar[] = '<strike>Zugriff auf Allianz'.(count($data['registerProtectedAllies']) != 1 ? 'en' : '').' '.implode(', ', $pa).' gesperrt</strike>';
			}
		}
	}
	
	// gesperrte Galaxien
	if($data['registerProtectedGalas'] != '') {
		$data['registerProtectedGalas'] = explode('+', $data['registerProtectedGalas']);
		
		if($r[3]) {
			$ar[] = 'Zugriff auf Galaxie'.(count($data['registerProtectedGalas']) != 1 ? 'n' : '').' '.implode(', ', $data['registerProtectedGalas']).' gesperrt';
		}
		// durch User-Berechtigungen aufgehoben
		else {
			$ar[] = '<strike>Zugriff auf Galaxie'.(count($data['registerProtectedGalas']) != 1 ? 'n' : '').' '.implode(', ', $data['registerProtectedGalas']).' gesperrt</strike>';
		}
	}
	$tmpl->content .= implode(',<br />', $ar).'</span>
		</div>';
}

// Usermodifikationen
if($data['userRechte'] != '') {
	$tmpl->content .= '
		<b>durch User-Berechtigungen modifiziert</b>: 
		<div style="padding-left:12px">';
	
	$ar = unserialize($data['userRechte']);
	foreach($ar as $key=>$val) {
		if(isset($rechtenamen[$key])) $ar[$key] = '<span class="'.($val ? 'green' : 'red').'">'.$rechtenamen[$key].'</span>';
	}
	// höchstes zu vergebendes Rechtelevel
	if(isset($ar['verwaltung_user_maxlevel'])) {
		$ar['verwaltung_user_maxlevel'] = '<span>h&ouml;chstes zu vergebendes Rechtelevel: '.(isset($rechte[$ar['verwaltung_user_maxlevel']]) ? $rechte[$ar['verwaltung_user_maxlevel']]['name'] : $ar['verwaltung_user_maxlevel']).'</span>';
	}
	
	$tmpl->content .= implode(', ', $ar).'
		</div>';
}

// Rechte bereinigen
unset($rechtenamen['override_allies']);
unset($rechtenamen['override_galas']);

$tmpl->content .= '
		<br />
		<table class="tnarrow" style="width:690px">
		<tr>
			<td style="width:50%;vertical-align:top">
				<b>nutzbare Funktionen</b>:
				<br />
				<div style="padding-left:8px" class="green">';
foreach($rechtenamen as $key=>$val) {
	if($r[1][$key]) $tmpl->content .= '- '.$val.'<br />';
	// höchstes zu vergebendes Rechtelevel
	if($key == 'verwaltung_user_register' AND ($r[1]['verwaltung_userally'] OR $r[1]['verwaltung_user_register'])) {
		$rc = $r[1]['verwaltung_user_maxlevel'];
		// Rechtelevel nicht vorhanden
		if(!isset($rechte[$rc])) {
			// größtes Rechtelevel
			$lnr = array_keys($rechte);
			sort($lnr);
			$lnr = array_pop($lnr);
			if($rc > $lnr) $rc = $rechte[$lnr]['name'];
			else $rc = 'unbekannt';
		}
		else $rc = $rechte[$rc]['name'];
		
		$tmpl->content .= '- h&ouml;chstes zu vergebendes Rechtelevel: '.htmlspecialchars($rc, ENT_COMPAT, 'UTF-8').'<br />';
	}
}
$tmpl->content .= '
				</div>
			</td>
			<td style="vertical-align:top">
				<b>gesperrte Funktionen</b>:
				<br />
				<div style="padding-left:8px" class="red">';
foreach($rechtenamen as $key=>$val) {
	if(!$r[1][$key]) $tmpl->content .= '- '.$val.'<br />';
}
$tmpl->content .= '
				</div>
			</td>
		</tr>
		</table>
	</div>';

// Log-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'lässt die Berechtigungen des Spielers '.$data['user_playerName'].' ('.$_GET['id'].') anzeigen');
}



?>