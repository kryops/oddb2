<?php
/**
 * pages/player/userlist.php
 Liste der angemeldeten Spieler anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// Content erzeugen
$content =& $csw->data['list']['content'];

$conds = '';

// gesperrte Allianzen
if($user->protectedAllies) {
	$conds = " AND user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
}

// gesperrte oder noch nicht freigeschaltete Spieler
$query = query("
	SELECT
		user_playerID,
		user_playerName,
		user_allianzenID,
		userBanned,
		
		allianzenTag
	FROM
		".PREFIX."user
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = user_allianzenID
	WHERE
		userBanned > 0
		".$conds."
	ORDER BY
		userBanned DESC,
		user_allianzenID ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

if(mysql_num_rows($query)) {
	// Berechtigung zum Bearbeiten
	$r = $user->rechte['verwaltung_userally'] OR $user->rechte['verwaltung_user_register'];
	
	$content .= '
	<div class="hl2">
		Gesperrte und noch nicht freigeschaltete Spieler
	</div>
	
	<div class="icontent">
		<table class="data" style="margin:auto">
		<tr>
			<th>Spieler</th>
			<th>Allianz</th>
			<th>Status</th>
			'.($r ? '<th>Aktionen</th>' : '').'
		</tr>';
	while($row = mysql_fetch_assoc($query)) {
		$content .= '
		<tr>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['user_playerID'].'">'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td>';
		
		// hat Allianz
		if($row['allianzenTag'] != NULL) {
			$content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['user_allianzenID'].'&amp;ajax">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
		}
		// allianzlos
		else if(!$row['user_allianzenID']) {
			$content .= '<i>keine</i>';
		}
		// unbekannte Allianz
		else {
			$content .= '<i>unbekannt</i>';
		}
		
		$content .= '</td>
			<td style="text-align:left">';
		// Status
		if($row['userBanned'] == 3) $content .= 'noch nicht freigeschaltet';
		else if($row['userBanned'] == 2) $content .= 'automatisch gesperrt';
		else $content .= 'manuell gesperrt';
		$content .= '</td>';
		// Aktionen
		if($r) {
			$content .= '
			<td style="text-align:left" class="userlistaction">';
			
			// Berechtigung
			if($user->rechte['verwaltung_user_register'] OR ($user->allianz AND $user->rechte['verwaltung_userally'] AND $row['user_allianzenID'] == $user->allianz)) {
				// noch nicht freigeschaltet
				if($row['userBanned'] == 3) {
					$content .= '
					<form action="#">
					mit dem Rechtelevel 
					&nbsp;<select name="rechtelevel" size="1">';
					foreach($rechte as $key=>$data) {
						// Berechtigung zum Vergeben
						if($key <= $user->rechte['verwaltung_user_maxlevel']) {
							$content .= '
						<option value="'.$key.'">'.$data['name'].'</option>';
						}
						// ausgegraut
						else {
							$content .= '
						<option value="'.$key.'" disabled="disabled">'.$data['name'].'</option>';
						}
					}
					$content .='
					</select>&nbsp;
					<a style="font-weight:bold" onclick="form_send(this.parentNode, \'index.php?p=player&amp;sp=free&amp;id='.$row['user_playerID'].'&amp;ajax\', this.parentNode.parentNode)">freischalten</a>
					&nbsp;oder&nbsp; 
					<a style="font-weight:bold" onclick="if(window.confirm(\'Soll der Spieler wirklich unwiderruflich gelöscht werden?\')){ajaxcall(\'index.php?p=player&amp;sp=del&amp;id='.$row['user_playerID'].'&amp;ajax\', this.parentNode.parentNode, false, false)}">l&ouml;schen</a>
					</form>
					';
				}
				// automatisch gesperrt
				else if($row['userBanned'] == 2) {
					$content .= '
					<a class="link" onclick="ajaxcall(\'index.php?p=player&amp;sp=autoban_status&amp;id='.$row['user_playerID'].'&amp;ajax\', this.parentNode, false, false)" style="font-weight:bold">Erneut &uuml;berpr&uuml;fen</a>';
					// allianzunabhängige Registriererlaubnis geben
					if($user->rechte['verwaltung_user_register']) {
						$content .= '
					&nbsp;oder&nbsp; 
					<a class="link" onclick="if(window.confirm(\'Dem Spieler wirklich eine allianzunabhängige Registriererlaubnis geben?\')){ajaxcall(\'index.php?p=player&amp;sp=autoban_register&amp;id='.$row['user_playerID'].'&amp;ajax\', this.parentNode, false, false)}" style="font-weight:bold">allianzunabh&auml;ngige Registriererlaubnis geben</a>';
					}
				}
				// manuell gesperrt
				else {
					$content .= '
					<a class="link" onclick="if(window.confirm(\'Sperrung wirklich aufheben?\')){ajaxcall(\'index.php?p=player&amp;sp=ban&amp;state=0&amp;list2&amp;id='.$row['user_playerID'].'&amp;ajax\', this.parentNode, false, false)}" style="font-weight:bold">Sperrung aufheben</a>
					&nbsp;oder&nbsp;
					<a style="font-weight:bold" onclick="if(window.confirm(\'Soll der Spieler wirklich unwiderruflich gelöscht werden?\')){ajaxcall(\'index.php?p=player&amp;sp=del&amp;id='.$row['user_playerID'].'&amp;ajax\', this.parentNode.parentNode, false, false)}">Spieler l&ouml;schen</a>';
				}
			}
			
			$content .= '
			</td>';
		}
		$content .= '
		</tr>';
	}
	$content .= '
		</table>
		<br />
	</div>';
}

$content .= '
	<div class="hl2">
		Angemeldete Spieler
	</div>
	
	<div class="icontent">
		<div style="text-align:right">
			<a class="link contextmenu small2" data-link="index.php?p=rechte">[Liste aller Rechtelevel anzeigen]</a>
		</div>
		<br />';

// Anzeigeberechtigungen ermitteln
$r_activity = ($user->rechte['show_player_db_ally'] OR $user->rechte['show_player_db_meta'] OR $user->rechte['show_player_db_other']);
$r_action = ($r_activity OR $user->rechte['verwaltung_userally'] OR $user->rechte['verwaltung_user_register']);

$heute = strtotime('today');

$conds = '';

// gesperrte Allianzen
if($user->protectedAllies) {
	$conds = " WHERE user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
}

// Sortierung
$sort = array(
	'name'=>'user_playerName ASC',
	'ally'=>'user_allianzenID ASC, user_playerName ASC',
	'rechte'=>'userRechtelevel DESC',
	'activity'=>'userOnlineDB DESC'
);

if(!isset($_GET['sort']) OR !isset($sort[$_GET['sort']])) {
	$sort = $sort['ally'];
}
else {
	$sort = $sort[$_GET['sort']];
}

// Daten abfragen
$query = query("
	SELECT
		user_playerID,
		user_playerName,
		user_allianzenID,
		userRechtelevel,
		userRechte,
		userBanned,
		userICQ,
		userOnlineDB,
		userOnlinePlugin,
		userSitterpflicht,
		
		allianzenTag,
		
		registerProtectedAllies,
		registerProtectedGalas,
		registerAllyRechte,
		
		statusStatus
	FROM
		".PREFIX."user
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = user_allianzenID
		LEFT JOIN ".PREFIX."register
			ON register_allianzenID = allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID
	".$conds."
	ORDER BY
		".$sort."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());


$content2 = '
		<table class="data allyfiltert" style="margin:auto">
		<tr>
			<th><a class="link" data-link="index.php?p=player&amp;sp=list&amp;sort=name">Spieler</a></th>
			<th><a class="link" data-link="index.php?p=player&amp;sp=list&amp;sort=ally">Allianz</a></th>
			<th>ICQ</th>
			<th><a class="link" data-link="index.php?p=player&amp;sp=list&amp;sort=rechte">Rechtelevel</a></th>
			<th>Sitterpflicht</th>
			'.($r_activity ? '<th colspan="2"><a class="link" data-link="index.php?p=player&amp;sp=list&amp;sort=activity">letzte Aktivit&auml;t (DB/Plugin)</a></th>' : '').'
			'.($r_action ? '<th>Aktionen</th>' : '').'
		</tr>';

$allies = array();

while($row = mysql_fetch_assoc($query)) {
	// Allianz der Liste hinzufügen
	if(!isset($allies[$row['user_allianzenID']])) {
		$allies[$row['user_allianzenID']] = array($row['allianzenTag'], 1);
	}
	else {
		$allies[$row['user_allianzenID']][1]++;
	}
	
	$content2 .= '<tr'.($row['userBanned'] ? ' style="opacity:0.4"' : '').' data-ally="'.$row['user_allianzenID'].'" class="userlist'.$row['user_playerID'].''.($row['user_playerID'] == $user->id ? ' trhighlight' : '').'">
		'.userrow($row).'
	</tr>';
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
	</div>';
		
// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich die Liste der angemeldeten Spieler anzeigen');
}




?>