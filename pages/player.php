<?php
/**
 * pages/player.php
 * angemeldete Spieler
 * Sitterliste
 * Einstellungen und Vermögen
 * Allianzwechsel
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	if($user->rechte['userlist']) $_GET['sp'] = 'list';
	else if($user->rechte['allywechsel']) $_GET['sp'] = 'allywechsel';
	else $_GET['sp'] = 'inaktiv';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Spieler';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'list'=>true,
	
	'update'=>true,
	'free'=>true,
	'del'=>true,
	'autoban_status'=>true,
	'autoban_register'=>true,
	'ban_remove'=>true,
	'edit'=>true,
	'ban'=>true,
	'rechtelevel'=>true,
	'sitterpflicht'=>true,
	'rechte'=>true,
	'rechte_send'=>true,
	
	
	'sitter'=>true,
	'sitter_send'=>true,
	
	'settings'=>true,
	'settings_send'=>true,
	
	'allywechsel'=>true,
	'allywechsel_send'=>true,
	
	'inaktiv'=>true,
	'inaktiv_send'=>true
);


/**
 * Funktionen
 */

/**
 * Usertabellen-Zeile erzeugen
 * @param $row Array Datensatz
 *
 * @return HTML Zeile
 */
function userrow($row) {
	global $user, $rechte, $rechtenamen, $r_activity, $r_action, $heute, $status_meta;
	
	if(!$heute) {
		$r_activity = ($user->rechte['show_player_db_ally'] OR $user->rechte['show_player_db_meta'] OR $user->rechte['show_player_db_other']);
		$r_action = ($r_activity OR $user->rechte['verwaltung_userally'] OR $user->rechte['verwaltung_user_register']);
		
		$heute = strtotime('today');
	}
	
	$content2 = '
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
		<td>'.($row['userICQ'] ? $row['userICQ'] : '&nbsp;').'</td>
		<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;sp=rechte&amp;id='.$row['user_playerID'].'">';
	// Rechtelevel
	$r = getrechte(
		$row['userRechtelevel'],
		$row['registerProtectedAllies'],
		$row['registerProtectedGalas'],
		$row['registerAllyRechte'],
		$row['userRechte']
	);
	
	// Rechtelevel-Anzeige
	// normal
	if(!$r[0]) {
		$content2 .= $rechte[$r[4]]['name'];
	}
	else {
		// Tooltip erzeugen
		$tt = '<div style="line-height:15px">';
		
		// Allianz-Einschränkungen
		if($row['registerAllyRechte'] !== NULL AND ($row['registerAllyRechte'] != '' OR $row['registerProtectedAllies'] != '' OR $row['registerProtectedGalas'] != '')) {
			$tt .= '
		<b>durch Allianz eingeschr&auml;nkt</b>
		<div class="red" style="padding-left:12px">';
			// gesperrte Berechtigungen
			if($row['registerAllyRechte'] != '') {
				$ar = explode('+', $row['registerAllyRechte']);
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
			if($row['registerProtectedAllies'] != '') {
				$row['registerProtectedAllies'] = explode('+', $row['registerProtectedAllies']);
				
				$query2 = query("
					SELECT
						allianzenID,
						allianzenTag
					FROM
						".GLOBPREFIX."allianzen
					WHERE
						allianzenID IN (".implode(', ', $row['registerProtectedAllies']).")
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				if(mysql_num_rows($query2)) {
					$pa = array();
					while($row2 = mysql_fetch_assoc($query2)) {
						$pa[] = '<a class="link red" data-link="index.php?p=show_ally&amp;id='.$row2['allianzenID'].'">'.htmlspecialchars($row2['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
					}
					if($r[2]) {
						$ar[] = 'Zugriff auf Allianz'.(count($row['registerProtectedAllies']) != 1 ? 'en' : '').' '.implode(', ', $pa).' gesperrt';
					}
					// durch User-Berechtigungen aufgehoben
					else {
						$ar[] = '<strike>Zugriff auf Allianz'.(count($row['registerProtectedAllies']) != 1 ? 'en' : '').' '.implode(', ', $pa).' gesperrt</strike>';
					}
				}
			}
			
			// gesperrte Galaxien
			if($row['registerProtectedGalas'] != '') {
				$row['registerProtectedGalas'] = explode('+', $row['registerProtectedGalas']);
				
				if($r[3]) {
					$ar[] = 'Zugriff auf Galaxie'.(count($row['registerProtectedGalas']) != 1 ? 'n' : '').' '.implode(', ', $row['registerProtectedGalas']).' gesperrt';
				}
				// durch User-Berechtigungen aufgehoben
				else {
					$ar[] = '<strike>Zugriff auf Galaxie'.(count($row['registerProtectedGalas']) != 1 ? 'n' : '').' '.implode(', ', $row['registerProtectedGalas']).' gesperrt</strike>';
				}
			}
			$tt .= implode(',<br />', $ar).'</span>
		</div>';
		}
		
		// Usermodifikationen
		if($row['userRechte'] != '') {
			$tt .= '
		<b>durch User-Berechtigungen modifiziert</b>
		<div style="padding-left:12px">';
			
			$ar = unserialize($row['userRechte']);
			foreach($ar as $key=>$val) {
				if(isset($rechtenamen[$key])) $ar[$key] = '<span class="'.($val ? 'green' : 'red').'">'.$rechtenamen[$key].'</span>';
			}
			// höchstes zu vergebendes Rechtelevel
			if(isset($ar['verwaltung_user_maxlevel'])) {
				$ar['verwaltung_user_maxlevel'] = '<span>h&ouml;chstes zu vergebendes Rechtelevel: '.(isset($rechte[$ar['verwaltung_user_maxlevel']]) ? $rechte[$ar['verwaltung_user_maxlevel']]['name'] : $ar['verwaltung_user_maxlevel']).'</span>';
			}
			
			$tt .= implode(', ', $ar).'
					</div>';
		}
		
		$tt .= '</div>';
		$tt = htmlspecialchars($tt, ENT_COMPAT, 'UTF-8');
		
		// eingeschränkt
		if($r[0] == 1) {
			$content2 .= '<span class="red tooltip" data-tooltip="'.$tt.'"><i>eingeschränkt:</i> '.$rechte[$r[4]]['name'].'</span>';
		}
		// modifiziert
		else {
			$content2 .= '<span class="yellow tooltip" data-tooltip="'.$tt.'"><i>modifiziert:</i> '.$rechte[$r[4]]['name'].'</span>';
		}
	}
	
	$content2 .= '</a></td>
	<td>'.($row['userSitterpflicht'] ? 'ja' : 'nein').'</td>';
	
	// letzte Aktivität
	if($r_activity) {
		// Berechtigung ermitteln
		$r = true;
		if($user->allianz AND !$user->rechte['show_player_db_ally'] AND $row['user_allianzenID'] == $user->allianz) {
			$r = false;
		}
		else if(!$user->rechte['show_player_db_meta'] AND $row['user_allianzenID'] != $user->allianz AND $row['statusStatus'] == $status_meta) {
			$r = false;
		}
		else if(!$user->rechte['show_player_db_other'] AND $row['statusStatus'] != $status_meta) {
			$r = false;
		}
		
		// Berechtigung
		if($r) {
			$row['userOnlineDB'] = $row['userOnlineDB'] ? (($row['userOnlineDB'] > $heute) ? 'heute' : strftime('%d.%m.%y', $row['userOnlineDB'])) : '<i>nie</i>';
			$row['userOnlinePlugin'] = $row['userOnlinePlugin'] ? (($row['userOnlinePlugin'] > $heute) ? 'heute' : strftime('%d.%m.%y', $row['userOnlinePlugin'])) : '<i>nie</i>';
			
			$content2 .= '
			<td>'.$row['userOnlineDB'].'</td>
			<td>'.$row['userOnlinePlugin'].'</td>';
		}
		// keine Berechtigung
		else {
			$content2 .= '
			<td colspan="2">&nbsp;</td>';
		}
	}
	
	// Aktionen
	if($r_action) {
		$content2 .= '
		<td class="userlistaction">';
		// Informationen
		if($r) {
			$content2 .= '
			<img src="img/layout/leer.gif" style="background-position:-1000px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=show_player&amp;sp=dbdata&amp;standalone&amp;id='.$row['user_playerID'].'" title="Informationen zu '.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').'" />';
		}
		
		// User bearbeiten und löschen
		if($user->rechte['verwaltung_user_register'] OR ($user->allianz AND $user->rechte['verwaltung_userally'] AND $row['user_allianzenID'] == $user->allianz)) {
			// nur editierbar, wenn schon freigeschaltet
			if($row['userBanned'] != 3) {
				$content2 .= '
			<img src="img/layout/leer.gif" style="background-position:-1020px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=player&amp;sp=edit&amp;id='.$row['user_playerID'].'" title="'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').' bearbeiten" />';
			}
			// eigener Account nicht löschbar
			if($row['user_playerID'] != $user->id) {
				$content2 .= '
			<img src="img/layout/leer.gif" style="background-position:-1040px -91px;cursor:pointer" class="hoverbutton" onclick="if(window.confirm(\'Soll der Spieler wirklich unwiderruflich gelöscht werden?\')){ajaxcall(\'index.php?p=player&amp;sp=del&amp;id='.$row['user_playerID'].'&amp;list&amp;ajax\', this.parentNode, false, false)}" title="'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').' l&ouml;schen" />';
			}
		}
		
		
		$content2 .= '</td>';
	}
	
	// Zeile zurückgeben
	return $content2;
}

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




// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

 // Spieler-Zeile aktualisieren
else if($_GET['sp'] == 'update') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else if(!isset($user->rechte['userlist'])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Spielerdaten abfragen
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
			WHERE
				user_playerID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Spieler nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Der Account existiert nicht mehr!';
		}
		// Spieler gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			$content = userrow($data);
			
			$tmpl->script = '$(\'.userlist'.$_GET['id'].'\').html(\''.str_replace(
				array("\r\n", "\n", "\\", "'"),
				array("", "", "\\\\", "\\'"),
				$content
			).'\')';
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Spieler freischalten
else if($_GET['sp'] == 'free') {
	include './pages/player/actions.php';
}

// Spieler löschen
else if($_GET['sp'] == 'del') {
	include './pages/player/actions.php';
}

// automatische Sperrung überprüfen
else if($_GET['sp'] == 'autoban_status') {
	include './pages/player/actions.php';
}

// allianzunabhängige Registrierungserlaubnis geben
else if($_GET['sp'] == 'autoban_register') {
	include './pages/player/actions.php';
}

// Spieler bearbeiten
else if($_GET['sp'] == 'edit') {
	include './pages/player/actions.php';
}

// sperren / manuelle Sperrung aufheben
else if($_GET['sp'] == 'ban') {
	include './pages/player/actions.php';
}

// Rechtelevel ändern
else if($_GET['sp'] == 'rechtelevel') {
	include './pages/player/actions.php';
}

// Sitterpflicht ändern
else if($_GET['sp'] == 'sitterpflicht') {
	include './pages/player/actions.php';
}

// Berechtigungen einzeln ändern
else if($_GET['sp'] == 'rechte') {
	include './pages/player/actions.php';
}

// Berechtigungen einzeln ändern: abschicken
else if($_GET['sp'] == 'rechte_send') {
	include './pages/player/actions.php';
}

// Sitterliste anzeigen
else if($_GET['sp'] == 'sitter_send') {
	include './pages/player/sitter.php';
}

// Einstellungen und Vermögen anzeigen
else if($_GET['sp'] == 'settings_send') {
	include './pages/player/settings.php';
}

// Allianzwechsel anzeigen
else if($_GET['sp'] == 'allywechsel_send') {
	include './pages/player/allywechsel.php';
}

// Inaktivensuche absenden
else if($_GET['sp'] == 'inaktiv_send') {
	include './pages/player/inaktiv.php';
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Liste mit angemeldeten Usern
	if($user->rechte['userlist']) {
		$csw->data['list'] = array(
			'link'=>'index.php?p=player&sp=list',
			'bg'=>'background-image:url(img/layout/csw_player.png)',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Content für die Userliste erzeugen
		if($_GET['sp'] == 'list') {
			include './pages/player/userlist.php';
		}
	}
	
	if($user->rechte['userlist'] AND ($user->rechte['show_player_db_ally'] OR $user->rechte['show_player_db_meta'] OR $user->rechte['show_player_db_other'])) {
		// detaillierte Sitteranzeige
		$csw->data['sitter'] = array(
			'link'=>'index.php?p=player&sp=sitter',
			'bg'=>'background-image:url(img/layout/csw_player.png);background-position:-150px 0px',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
		
		$content =& $csw->data['sitter']['content'];
		$content = '
			<div class="hl2">
				Sitter
			</div>
			
			<div class="icontent">
				<form action="#" name="player_sitter" onsubmit="return form_send(this, \'index.php?p=player&amp;sp=sitter_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
				<div class="fcbox formcontent center" style="padding:10px;width:600px">
					Sitterliste f&uuml;r 
					&nbsp;<select name="typ" size="1">';
		if($user->rechte['show_player_db_ally']) {
			$content .= '
						<option value="ally">Spieler der eigenen Allianz</option>';
		}
		if($user->rechte['show_player_db_meta']) {
			$content .= '
						<option value="meta">Spieler der eigenen Meta</option>';
		}
		if($user->rechte['show_player_db_other']) {
			$content .= '
						<option value="all">alle angemeldeten Spieler</option>';
		}
		$content .= '
					</select>&nbsp;
					<input type="submit" class="button" value="anzeigen" />
				</div>
				</form>
				<div class="ajax"></div>
			</div>';
		
		
		// Einstellungen und Vermögen
		$csw->data['settings'] = array(
			'link'=>'index.php?p=player&sp=settings',
			'bg'=>'background-image:url(img/layout/csw_player.png);background-position:-300px 0px',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
		
		$content =& $csw->data['settings']['content'];
		$content = '
			<div class="hl2">
				Einstellungen und Verm&ouml;gen
			</div>
			
			<div class="icontent">
			<form action="#" name="player_settings" onsubmit="return form_send(this, \'index.php?p=player&amp;sp=settings_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
				<div class="fcbox center" style="padding:10px;width:650px;line-height:36px">
					Daten von  
					&nbsp;<select name="typ" size="1">';
		if($user->rechte['show_player_db_ally']) {
			$content .= '
						<option value="ally">Spielern der eigenen Allianz</option>';
		}
		if($user->rechte['show_player_db_meta']) {
			$content .= '
						<option value="meta">Spielern der eigenen Meta</option>';
		}
		if($user->rechte['show_player_db_other']) {
			$content .= '
						<option value="all">allen angemeldeten Spielern</option>';
		}
		$content .= '
					</select>&nbsp; anzeigen
					<br />
					sortieren nach 
					<select name="sort" size="1">
						<option value="ally">Allianz</option>
						<option value="name">Name</option>
						<option value="einnahmen">Steuereinnahmen</option>
						<option value="konto">Verm&ouml;gen</option>
						<option value="fp">Forschungspunkte</option>
						<option value="schiffe">Schiffe</option>
						<option value="flottensteuer">Flottensteuer</option>
						<option value="kop">Kommandopunkte</option>
					</select> 
					<select name="sort_" size="1">
						<option value="0">aufsteigend</option>
						<option value="1">absteigend</option>
					</select>
					<hr />
					Kampftaktik 
					<select name="kampf" size="1">
						<option value="-1">egal</option>
						<option value="1">Keilformation</option>
						<option value="2">Staffelung</option>
						<option value="3">Schwarmangriff</option>
					</select> &nbsp; &nbsp; 
					Steuern 
					<select name="steuern" size="1">
						<option value="-1">egal</option>
						<option value="1">niedrig</option>
						<option value="2">normal</option>
						<option value="3">hoch</option>
					</select>
					<br />
					Ally-Handel 
					<select name="handel_ally" size="1">
						<option value="-1">egal</option>
						<option value="1">an</option>
						<option value="0">aus</option>
					</select> &nbsp; &nbsp; 
					neutraler Handel 
					<select name="handel_neutral" size="1">
						<option value="-1">egal</option>
						<option value="1">an</option>
						<option value="0">aus</option>
					</select>
					<br />
					Einnahmen pro h
					<select name="einnahmen_" size="1">
						<option value="1">&gt;</option>
						<option value="0">&lt;</option>
					</select> 
					<input type="text" class="smalltext" name="einnahmen" /> &nbsp; &nbsp;
					Verm&ouml;gen
					<select name="konto_" size="1">
						<option value="1">&gt;</option>
						<option value="0">&lt;</option>
					</select> 
					<input type="text" class="smalltext" name="konto" style="width:100px" /> &nbsp; &nbsp;
					FP
					<select name="fp_" size="1">
						<option value="1">&gt;</option>
						<option value="0">&lt;</option>
					</select> 
					<input type="text" class="smalltext" name="fp" />
					<br />
					Anzahl Schiffe
					<select name="schiffe_" size="1">
						<option value="1">&gt;</option>
						<option value="0">&lt;</option>
					</select> 
					<input type="text" class="smalltext" name="schiffe" /> &nbsp; &nbsp;
					Flottensteuer
					<select name="flottensteuer_" size="1">
						<option value="1">&gt;</option>
						<option value="0">&lt;</option>
					</select> 
					<input type="text" class="smalltext" name="flottensteuer" style="width:100px" />
					<hr />
					<input type="submit" class="button" value="Daten anzeigen" />
				</div>
				</form>
				<div class="ajax"></div>
			</div>';
	}
	
	// kürzliche Allianzwechsel
	if($user->rechte['allywechsel']) $csw->data['allywechsel'] = array(
		'link'=>'index.php?p=player&sp=allywechsel',
		'bg'=>'background-image:url(img/layout/csw_player.png);background-position:-450px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Allianzwechsel
			</div>
			
			<div class="icontent">
				<b>Hinweis</b>: Das Erfassungsdatum kann vom wirklichen Zeitpunkt des Allianzwechsels um bis zu '.$gconfig['odrequest'].' Stunden abweichen!
				<br /><br /><br />
				
				<form action="#" name="player_allywechsel" onsubmit="return form_send(this, \'index.php?p=player&amp;sp=allywechsel_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
				<div class="fcbox formcontent center" style="padding:10px;width:600px">
					Zeige die erfassten Allianzwechsel der letzten 
					&nbsp;<input type="text" class="smalltext" name="days" value="7" />&nbsp; 
					Tage
					<br />
					optional: an denen die Allianz &nbsp;<input type="text" class="smalltext" name="ally" style="width:80px" />&nbsp; beteiligt ist
					<br />
					<input type="submit" class="button" style="margin-top:10px" value="Allianzwechsel anzeigen" />
				</div>
				</form>
				<div class="ajax"></div>
			</div>'
	);
	
	// Inaktivensuche
	if($user->rechte['inaktivensuche']) $csw->data['inaktiv'] = array(
		'link'=>'index.php?p=player&sp=inaktiv',
		'bg'=>'background-image:url(img/layout/csw_player.png);background-position:-600px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Inaktivensuche
			</div>
			
			<div class="icontent">
				Die Aktivit&auml;t eines Spielers wird durch &Auml;nderungen der Imperiumspunkte oder der Gesinnung erfasst. Diese Werte &auml;ndern sich durch Aktionen des Spielers sowie durch neue Planeten, Events und abgeschlossene Forschungen. Sie tun das jedoch nur, wenn sich der Spieler oder ein Sitter einloggt.
				<br /><br />
				Die &Auml;nderungen werden sp&auml;testens nach '.$gconfig['odrequest'].' Stunden erfasst. Die Anzeige ist aber nur nach etwa 1-2 Wochen wirklich aussagekr&auml;ftig, da es auch trotz Aktivit&auml;t eine Weile dauern kann, bis sich die Punkte &auml;ndern.
				<br /><br /><br />
				
				<form action="#" name="player_inaktiv" onsubmit="return form_send(this, \'index.php?p=player&amp;sp=inaktiv_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
				<div class="fcbox formcontent center" style="padding:10px;width:600px">
					Zeige Spieler, deren letzte Aktivit&auml;t mehr als 
					&nbsp;<input type="text" class="smalltext" name="days" value="7" />&nbsp; 
					Tage zur&uuml;ckliegt
					<br />
					optional: der Allianz &nbsp;<input type="text" class="smalltext" name="ally" style="width:80px" />
					<br />
					<input type="submit" class="button" style="margin-top:10px" value="Spieler anzeigen" />
				</div>
				</form>
				<div class="ajax"></div>
			</div>'
	);
	
	
	// nur Unterseite ausgeben
	if(isset($_GET['switch'])) {
		if(isset($csw->data[$_GET['sp']])) {
			$tmpl->content = $csw->data[$_GET['sp']]['content'];
		}
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
	}
	// keine Berechtigung
	else if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
	}
	$tmpl->output();
}

?>