<?php
/**
 * pages/player/settings.php
 * Einstellungen und Vermögen anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// AutoAngriff-Override
$_POST['aa'] = '-1';

// Daten unvollständig
if(!isset($_POST['typ'], $_POST['sort'], $_POST['sort_'], $_POST['aa'], $_POST['kampf'], $_POST['steuern'], $_POST['handel_ally'], $_POST['handel_neutral'], $_POST['einnahmen'], $_POST['einnahmen_'], $_POST['konto'], $_POST['konto_'], $_POST['schiffe'], $_POST['schiffe_'], $_POST['flottensteuer'], $_POST['flottensteuer_'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// keine Berechtigung
else if(!$user->rechte['userlist'] OR (!$user->rechte['show_player_db_ally'] AND !$user->rechte['show_player_db_meta'] AND !$user->rechte['show_player_db_other'])) {
	$tmpl->error = 'Du hast keine Berechtigung!';
}
else {
	// Daten sichern
	$_POST['einnahmen'] = (int)$_POST['einnahmen'];
	$_POST['konto'] = (int)$_POST['konto'];
	$_POST['fp'] = (int)str_replace('.', '', $_POST['fp']);
	$_POST['schiffe'] = (int)$_POST['schiffe'];
	$_POST['flottensteuer'] = (int)$_POST['flottensteuer'];
	
	$heute = strtotime('today');
	
	$allies = array();
	
	// Query erzeugen
	$conds = array();
	
	// Anzeigetyp
	if($_POST['typ'] == 'ally') {
		$conds[] = "user_allianzenID = ".$user->allianz;
	}
	else if($_POST['typ'] == 'meta') {
		$conds[] = "statusStatus = ".$status_meta;
	}
	
	// Berechtigungen
	if($user->allianz AND !$user->rechte['show_player_db_ally']) {
		$conds[] = "user_allianzenID != ".$user->allianz;
	}
	if(!$user->rechte['show_player_db_meta']) {
		$conds[] = "(user_allianzenID = ".$user->allianz." OR statusStatus IS NULL OR statusStatus != ".$status_meta.")";
	}
	if(!$user->rechte['show_player_db_other']) {
		$conds[] = "statusStatus = ".$status_meta;
	}
	
	// Einnahmen
	if($_POST['einnahmen']) {
		$conds[] = "userEinnahmen ".($_POST['einnahmen_'] ? ">" : "<")." ".$_POST['einnahmen'];
	}
	
	// Vermögen
	if($_POST['konto']) {
		$conds[] = "userKonto ".($_POST['konto_'] ? ">" : "<")." ".$_POST['konto'];
	}
	
	// Forschungspunkte
	if($_POST['fp']) {
		$conds[] = "userFP ".($_POST['fp_'] ? ">" : "<")." ".$_POST['fp'];
	}
	
	// Schiffe
	if($_POST['schiffe']) {
		$conds[] = "userSchiffe ".($_POST['schiffe_'] ? ">" : "<")." ".$_POST['schiffe'];
	}
	
	// Flottensteuer
	if($_POST['flottensteuer']) {
		$conds[] = "userFlottensteuer ".($_POST['flottensteuer_'] ? ">" : "<")." ".$_POST['flottensteuer'];
	}
	
	// gesperrte Allianzen
	if($user->protectedAllies) {
		$conds[] = "user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
	}
	
	if(count($conds)) {
		$conds = "WHERE
			".implode(" AND ", $conds);
	}
	else $conds = '';
	
	// Sortierung
	$sort = array(
		'name'=>'user_playerName {dir}',
		'ally'=>'user_allianzenID {dir}, user_playerName {dir}',
		'einnahmen'=>'userEinnahmen {dir}',
		'konto'=>'userKonto {dir}',
		'fp'=>'userFP {dir}',
		'schiffe'=>'userSchiffe {dir}',
		'flottensteuer'=>'userFlottensteuer {dir}',
		'kop'=>'userKop {dir}'
	);
	
	if(!isset($sort[$_POST['sort']])) {
		$sort = $sort['ally'];
	}
	else {
		$sort = $sort[$_POST['sort']];
	}
	
	// Reihenfolge
	$sort = str_replace('{dir}', ($_POST['sort_'] ? 'DESC' : 'ASC'), $sort);
	
	// Daten abfragen
	$query = query("
		SELECT
			user_playerID,
			user_playerName,
			user_allianzenID,
			userODSettings,
			userEinnahmen,
			userKonto,
			userFP,
			userSchiffe,
			userFlottensteuer,
			userKop,
			userKopMax,
			userPKop,
			userPKopMax,
			userODSettingsUpdate,
			userGeldUpdate,
			userFlottenUpdate,
			
			allianzenTag
		FROM
			".PREFIX."user
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = user_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = allianzenID
		".$conds."
		ORDER BY
			".$sort."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$tmpl->content = '<br />';
	
	$count = 0;
	
	$content2 = '';
	
	while($row = mysql_fetch_assoc($query)) {
		$r = true;
		
		// wenn Einstellungen eingescannt, nur ohne Filter anzeigen
		if($row['userODSettings'] == '') {
			$row['userODSettings'] = '444444';
		}
		
		// Autoangriff
		if($_POST['aa'] != -1 AND $row['userODSettings'][0] != $_POST['aa']) {
			$r = false;
		}
		// neutraler Handel
		if($_POST['handel_neutral'] != -1 AND $row['userODSettings'][1] != $_POST['handel_neutral']) {
			$r = false;
		}
		// Ally-Handel
		if($_POST['handel_ally'] != -1 AND $row['userODSettings'][2] != $_POST['handel_ally']) {
			$r = false;
		}
		// NAP-Handel
		if($_POST['handel_nap'] != -1 AND $row['userODSettings'][3] != $_POST['handel_nap']) {
			$r = false;
		}
		// Kampfsystem
		if($_POST['kampf'] != -1 AND $row['userODSettings'][4] != $_POST['kampf']) {
			$r = false;
		}
		// Steuern
		if($_POST['steuern'] != -1 AND $row['userODSettings'][5] != $_POST['steuern']) {
			$r = false;
		}
		
		// Zeile wird angezeigt
		if($r) {
			// Allianz der Liste hinzufügen
			if(!isset($allies[$row['user_allianzenID']])) {
				$allies[$row['user_allianzenID']] = array($row['allianzenTag'], 1);
			}
			else {
				$allies[$row['user_allianzenID']][1]++;
			}
			
			$content2 .= '
		<tr data-ally="'.$row['user_allianzenID'].'"'.($row['user_playerID'] == $user->id ? ' class="trhighlight"' : '').'>
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
			$content2 .= '</td>';
			
			// Einstellungen eingescannt
			if($row['userODSettingsUpdate'] AND strlen($row['userODSettings']) == 6) {
				// Handel
				$handel = array();
				if($row['userODSettings'][2]) $handel[] = 'Allianz';
				if($row['userODSettings'][3]) $handel[] = 'NAP';
				if($row['userODSettings'][1]) $handel[] = 'neutral';
				
				if(!count($handel)) $handel = '<i>keiner</i>';
				else if(count($handel) == 3) $handel = 'alle';
				else $handel = implode(' und ', $handel);
				
				$content2 .= '
			<td>'.$handel.'</td>
			<td>';
				if($row['userODSettings'][4] == 1) $content2 .= 'Kapern';
				else if($row['userODSettings'][4] == 2) $content2 .= 'Fernkampf';
				else $content2 .= 'Nahkampf';
				$content2 .= '</td>
			<td>';
				if($row['userODSettings'][5] == 1) $content2 .= 'niedrig';
				else if($row['userODSettings'][5] == 2) $content2 .= 'mittel';
				else $content2 .= 'hoch';
				$content2 .= '</td>';
			}
			// Einstellungen nicht eingescannt
			else {
				$content2 .= '
			<td colspan="3" class="red" style="font-style:italic">Einstellungen nicht eingescannt</td>';
			}
			
			// Einnahmen eingescannt
			if($row['userGeldUpdate']) {
				$content2 .= '
			<td>'.ressmenge($row['userEinnahmen']).'</td>
			<td>'.ressmenge($row['userKonto']).'</td>
			<td>'.ressmenge($row['userFP']).'</td>';
			}
			// Einnahmen nicht eingescannt
			else {
				$content2 .= '
			<td colspan="3" class="red" style="font-style:italic">nicht eingescannt</td>';
			}
			
			// Flotten eingescannt
			if($row['userFlottenUpdate']) {
				$content2 .= '
			<td>'.ressmenge($row['userSchiffe']).'</td>
			<td>'.ressmenge($row['userFlottensteuer']).'</td>
			<td class="'.($row['userKop'] > $row['userKopMax'] ? 'red' : 'green').'">'.$row['userKop'].' / '.$row['userKopMax'].'</td>
			<td class="'.($row['userPKop'] > $row['userPKopMax'] ? 'red' : 'green').'">'.$row['userPKop'].' / '.$row['userPKopMax'].'</td>';
			}
			// Flotten nicht eingescannt
			else {
				$content2 .= '
			<td colspan="4" class="red" style="font-style:italic">nicht eingescannt</td>';
			}
			
			// aktualisiert
			$content2 .= '
			<td class="small" style="text-align:left">
				Einstellungen: <span class="'.(($row['userODSettingsUpdate'] > time()-86400*$config['scan_veraltet_einst']) ? 'green' : 'red').'">'.($row['userODSettingsUpdate'] ? (($row['userODSettingsUpdate'] > $heute) ? 'heute' : strftime('%d.%m.%y', $row['userODSettingsUpdate'])) : '<i>nie</i>').'</span><br />
				Verm&ouml;gen+FP: <span class="'.(($row['userGeldUpdate'] > time()-86400*$config['scan_veraltet_geld']) ? 'green' : 'red').'">'.($row['userGeldUpdate'] ? (($row['userGeldUpdate'] > $heute) ? 'heute' : strftime('%d.%m.%y', $row['userGeldUpdate'])) : '<i>nie</i>').'</span><br />
				Flotten: <span class="'.(($row['userFlottenUpdate'] > time()-86400*$config['scan_veraltet_flotten']) ? 'green' : 'red').'">'.($row['userFlottenUpdate'] ? (($row['userFlottenUpdate'] > $heute) ? 'heute' : strftime('%d.%m.%y', $row['userFlottenUpdate'])) : '<i>nie</i>').'</span>
			</td>
		</tr>';
			
			// Treffer hochzählen
			$count++;
		}
	}
	
	if(!$count) {
		$tmpl->content = '
	<br />
	<div class="center" style="font-weight:bold">Keine Treffer zu den gew&auml;hlten Kriterien gefunden!</div>';
	}
	// Tabelle erzeugen
	else {
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
	<table class="data allyfiltert small2" style="margin:auto">
	<tr>
		<th>Spieler</th>
		<th>Allianz</th>
		<th>Handel</th>
		<th>Kampf</th>
		<th>Steuern</th>
		<th>Einnahmen</th>
		<th>Verm&ouml;gen</th>
		<th>FP</th>
		<th>Schiffe</th>
		<th>Flottensteuer</th>
		<th>KOP</th>
		<th>Privat-KOP</th>
		<th>aktualisiert</th>
	</tr>
	'.$content2.'
	</table>';
	}
	
	// Log-Eintrag
	$log = 'lässt sich Einstellungen und Vermögen von ';
	if($_POST['typ'] == 'ally') $log .= 'Spielern seiner Allianz';
	else if($_POST['typ'] == 'meta') $log .= 'Spielern seiner Meta';
	else $log .= 'allen Spielern';
	$log .= ' anzeigen';
		
	if($config['logging'] >= 2) {
		insertlog(22, $log);
	}
}

// Leerzeile vor Fehlermeldung
if($tmpl->error) {
	$tmpl->error = '<br />'.$tmpl->error;
}

// Ausgabe
$tmpl->output();



?>