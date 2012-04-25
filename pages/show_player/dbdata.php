<?php
/**
 * pages/show_player/dbdata.php
 * alle Daten eines Spielers
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



/**
 * Funktionen
 */

/**
 * ermittelt aus einem implodierten String die Sitter und gibt sie aus
 * @param $s string Sitter (id+id+id)
 * @param $data array User-Datensatz
 *
 * @return string HTML-Ausgabe
 */
function sitter($s, $data) {
	global $status_meta, $status_freund;
	
	// keine Sitter
	if($s == '') {
		return '<i>keine</i>';
	}
	// Sitter vorhanden
	else {
		$s = explode('+', $s);
		
		// Daten abfragen
		$query = query("
			SELECT
				playerID,
				playerName,
				player_allianzenID,
				
				allianzenTag,
				
				statusStatus
			FROM
				".GLOBPREFIX."player
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$data['user_allianzenID']."
					AND status_allianzenID = allianzenID
			WHERE
				playerID IN(".implode(', ', $s).")
			ORDER BY
				playerName ASC
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$c = array();
		
		while($row = mysql_fetch_assoc($query)) {
			// Farbe ermitteln
			$color = 'red';
			if($row['player_allianzenID'] == $data['user_allianzenID']) {
				$color = 'green';
			}
			else if($row['statusStatus'] == $status_meta) {
				$color = 'blue';
			}
			else if(in_array($row['statusStatus'], $status_freund)) {
				$color = 'yellow';
			}
			
			// Username ausgeben
			$content = '<a class="link winlink contextmenu '.$color.'" data-link="index.php?p=show_player&amp;id='.$row['playerID'].'">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
			
			// Allianz ausgeben
			if($row['allianzenTag'] != NULL) {
				 $content .= ' &nbsp;<a class="link winlink contextmenu small '.$color.'" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'" style="white-space:nowrap">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
			}
			
			$c[] = $content;
		}
		
		// zurückgeben
		return implode(', &nbsp;', $c);
	}
}


/**
 * Seite
 */


// Daten sichern
$_GET['id'] = (int)$_GET['id'];

// Spieler-Daten abfragen
$query = query("
	SELECT
		user_playerName,
		user_allianzenID,
		userRechtelevel,
		userRechte,
		userBanned,
		userODSettings,
		userSitterTo,
		userSitterFrom,
		userODSettingsUpdate,
		userSitterUpdate,
		userEinnahmen,
		userKonto,
		userFP,
		userGeldUpdate,
		userSchiffe,
		userFlottensteuer,
		userKop,
		userKopMax,
		userPKop,
		userPKopMax,
		userFlottenUpdate,
		userICQ,
		userSitterpflicht,
		userDBPunkte,
		userSysScanned,
		userSysUpdated,
		userPlanScanned,
		userPlanUpdated,
		userOnlineDB,
		userOnlinePlugin,
		
		r1.register_playerID,
		
		r2.register_allianzenID,
		r2.registerAllyRechte,
		r2.registerProtectedAllies,
		r2.registerProtectedGalas,
		
		statusStatus
	FROM
		".PREFIX."user
		LEFT JOIN ".GLOBPREFIX."player
			ON playerID = user_playerID
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = user_allianzenID
		LEFT JOIN ".PREFIX."register r1
			ON r1.register_playerID = user_playerID
		LEFT JOIN ".PREFIX."register r2
			ON r2.register_allianzenID = user_allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID
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


$tmpl->name = 'Datenbank-Daten von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' ('.$_GET['id'].')';
$tmpl->icon = 'player';

// Allianz gesperrt
if($_GET['id'] != $user->id AND $user->protectedAllies AND in_array($data['user_allianzenID'], $user->protectedAllies)) {
	$tmpl->error = 'Du hast keine Berechtigung, Spieler dieser Allianz anzuzeigen!';
	$tmpl->output();
	die();
}

// Berechtigung ermitteln
$r = true;
if($_GET['id'] == $user->id) {}
// Allianz gesperrt
else if(!$user->rechte['show_player_db_ally'] AND $user->allianz AND $data['user_allianzenID'] == $user->allianz) {
	$r = false;
}
// Meta gesperrt
else if(!$user->rechte['show_player_db_meta'] AND $data['user_allianzenID'] == $user->allianz AND $data['statusStatus'] == $status_meta) {
	$r = false;
}
// andere Allianz gesperrt
else if(!$user->rechte['show_player_db_other'] AND $data['statusStatus'] != $status_meta) {
	$r = false;
}
	
// keine Berechtigung
if(!$r) {
	$tmpl->error = 'Du hast keine Berechtigung, die Datenbank-Daten dieses Spielers anzuzeigen!';
	$tmpl->output();
	die();
}

// Ausgabe
if(!isset($_GET['standalone'])) {
	$tmpl->content = '
	<div class="fhl2"><a onclick="$(this.parentNode).siblings(\'table\').toggle()">Datenbank-Daten</a></div>';
}
// Standalone-Ansicht
else {
	$tmpl->content = '
	<div class="hl2">Deine Daten</div>
	<div class="small2 icontent">';
}
$tmpl->content .= '	
	<table class="tnarrow" style="width:100%">
	<tr>
		<td colspan="2" style="width:100%;line-height:15px;padding-bottom:5px">
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
if($data['registerAllyRechte'] !== NULL AND ($data['registerAllyRechte'] != '' OR $data['registerProtectedAllies'] != '' OR $data['registerProtectedGalas'] != '')) {
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
		$ar[$key] = '<span class="'.($val ? 'green' : 'red').'">'.$rechtenamen[$key].'</span>';
	}
	
	$tmpl->content .= implode(', ', $ar).'
			</div>';
}

$tmpl->content .= '
			<div class="center" style="margin-top:4px"><a class="link winlink contextmenu hint" data-link="index.php?p=show_player&amp;id='.$_GET['id'].'&sp=rechte">[komplette Berechtigungsliste von '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' anzeigen]</a></div>';


// Registriererlaubnis
if($data['register_playerID'] !== NULL) {
	$tmpl->content .= '
			<div class="center" style="padding-top:8px;font-weight:bold">
				Der Spieler darf die Datenbank benutzen, egal welcher Allianz er angeh&ouml;rt
			</div>';
}

// gebannt oder noch nicht freigeschaltet
if($data['userBanned']) {
	$tmpl->content .= '
			<div class="center" style="padding-top:8px;font-weight:bold">
				';
	if($data['userBanned'] == 1) $tmpl->content .= 'Der Spieler ist zur Zeit gesperrt!';
	else if($data['userBanned'] == 2) $tmpl->content .= 'Der Spieler wurde automatisch gesperrt, weil er die Allianz gewechselt hat!';
	else $tmpl->content .= 'Der Account ist noch nicht freigeschaltet!';
	$tmpl->content .= '
			</div>';
}

// linke Spalte
// Aktivität, ICQ, Einstellungen, Einnahmen, Flotten, wann aktualisiert
$tmpl->content .= '
		</td>
	</tr>
	<tr>
		<td style="width:50%;line-height:18px;vertical-align:top">
			<b>letzte Aktivität</b>: '.($data['userOnlineDB'] ? datum($data['userOnlineDB']) : '<i>nie</i>').' (DB), '.($data['userOnlinePlugin'] ? datum($data['userOnlinePlugin']) : '<i>nie</i>').' (Plugin)
			<br />';

if($data['userICQ']) {
	$tmpl->content .= '
			<b>ICQ</b>: '.$data['userICQ'].'
			<br />';
}

// Einstellungen
if($data['userODSettings'] != '' AND strlen($data['userODSettings']) == 6) {
	// Handel rendern
	$handel = array();
	if($data['userODSettings'][2]) $handel[] = 'Allianz';
	if($data['userODSettings'][3]) $handel[] = 'Meta';
	if($data['userODSettings'][1]) $handel[] = 'neutral';
	
	if(!count($handel)) $handel = '<i>keiner</i>';
	else if(count($handel) == 3) $handel = 'Allianz, Meta und neutral';
	else $handel = implode(' und ', $handel);
	
	
	
	$tmpl->content .= '
			<b>Einstellungen</b>:
			<div style="padding-left:12px;line-height:15px">';
	/*
				Autoangriff: '.($data['userODSettings'][0] ? 'an' : 'aus').'
				<br />
	*/
	$tmpl->content .= '
				Handel: '.$handel.'
				<br />
				Kampftaktik: ';
	if($data['userODSettings'][4] == 1) $tmpl->content .= 'Keilformation';
	else if($data['userODSettings'][4] == 2) $tmpl->content .= 'Staffelung';
	else $tmpl->content .= 'Schwarmangriff';
	$tmpl->content .= '
				<br />
				Steuern: ';
	if($data['userODSettings'][5] == 1) $tmpl->content .= 'niedrig';
	else if($data['userODSettings'][5] == 2) $tmpl->content .= 'mittel';
	else $tmpl->content .= 'hoch';
	$tmpl->content .= '
			</div>';
}

// Einnahmen
if($data['userGeldUpdate']) {
	$tmpl->content .= '
		<b>Einnahmen</b>: '.ressmenge($data['userEinnahmen']).' ('.ressmenge($data['userEinnahmen']*24).' pro Tag)
		<br />
		<b>Verm&ouml;gen</b>: '.ressmenge($data['userKonto']).'
		<br />
		<b>Forschung</b>: '.ressmenge($data['userFP']).'
		<br />';
}

// Flottenkosten
if($data['userFlottenUpdate']) {
	$tmpl->content .= '
		<b>Flottenkosten</b>: '.ressmenge($data['userFlottensteuer']).' ('.$data['userSchiffe'].' Schiffe)
		<br />
		<b>KOP privat</b>: <span class="'.($data['userPKop'] > $data['userPKopMax'] ? 'red' : 'green').'">'.$data['userPKop'].' / '.$data['userPKopMax'].'</span>
		<br />
		<b>KOP Ally</b>: <span class="'.($data['userKop'] > $data['userKopMax'] ? 'red' : 'green').'">'.$data['userKop'].' / '.$data['userKopMax'].'</span>
		<br />';
}


$tmpl->content .= '
			<b>Daten aktualisiert</b>:
			<div style="padding-left:12px;line-height:15px">
				Einstellungen: <span class="'.($data['userODSettingsUpdate'] > time()-86400*$config['scan_veraltet_einst'] ? 'green' : 'red').'">'.($data['userODSettingsUpdate'] ? datum($data['userODSettingsUpdate']) : '<i>nie</i>').'</span>
				<br />
				Sitter: <span class="'.($data['userSitterUpdate'] > time()-86400*$config['scan_veraltet_einst'] ? 'green' : 'red').'">'.($data['userSitterUpdate'] ? datum($data['userSitterUpdate']) : '<i>nie</i>').'</span>
				<br />
				Flotten: <span class="'.($data['userFlottenUpdate'] > time()-86400*$config['scan_veraltet_flotten'] ? 'green' : 'red').'">'.($data['userFlottenUpdate'] ? datum($data['userFlottenUpdate']) : '<i>nie</i>').'</span>
				<br />
				Einnahmen+Forschung: <span class="'.($data['userGeldUpdate'] > time()-86400*$config['scan_veraltet_geld'] ? 'green' : 'red').'">'.($data['userGeldUpdate'] ? datum($data['userGeldUpdate']) : '<i>nie</i>').'</span>
			</div>';

// rechte Spalte
// Sitter
$tmpl->content .= '
		</td>
		<td style="vertical-align:top">';
// nur anzeigen, wenn eingescannt
if($data['userODSettingsUpdate']) {
	$tmpl->content .= '
			<b>Diese Accounts k&ouml;nnen auf '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' zugreifen</b>:
			<br />
			'.sitter($data['userSitterTo'], $data).'
			<br /><br />
			<b>'.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' kann auf diese Accounts zugreifen</b>:
			<br />
			'.sitter($data['userSitterFrom'], $data);
}
// Sitterpflicht
if($data['userSitterpflicht']) {
	$tmpl->content .= '
			<br /><br />
			alle Mitglieder der Allianz sollen '.htmlspecialchars($data['user_playerName'], ENT_COMPAT, 'UTF-8').' Sitter einrichten.';
}
// Statistiken
$tmpl->content .= '
			<br /><br /><br />
			<b>Statistiken</b>:
			<div style="padding-left:12px;line-height:15px">
				Systeme eingescannt: '.$data['userSysScanned'].'
				<br />
				Systeme aktualisiert: '.$data['userSysUpdated'].'
				<br />
				Planeten eingescannt: '.$data['userPlanScanned'].'
				<br />
				Planeten aktualisiert: '.$data['userPlanUpdated'].'
			</div>
		</td>
	</tr>
	</table>';

// Standalone-Ansicht
if(isset($_GET['standalone'])) {
	$tmpl->content .= '
	</div>';
}

// Log-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'lässt die DB-Daten des Spielers '.$data['user_playerName'].' ('.$_GET['id'].') anzeigen');
}



?>