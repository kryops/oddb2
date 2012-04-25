<?php
/**
 * pages/player/sitter.php
 * Sitterliste anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// Daten unvollständig
if(!isset($_POST['typ'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// keine Berechtigung
else if(!$user->rechte['userlist'] OR (!$user->rechte['show_player_db_ally'] AND !$user->rechte['show_player_db_meta'] AND !$user->rechte['show_player_db_other'])) {
	$tmpl->error = 'Du hast keine Berechtigung!';
}
else {
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
	
	// gesperrte Allianzen
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
			userSitterUpdate,
			
			userSitterTo,
			userSitterFrom,
			
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
			user_allianzenID ASC,
			user_playerName ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// nichts gefunden -> keine Berechtigung
	if(!mysql_num_rows($query)) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		$tmpl->content = '<br />';
		
		$allies = array();
		
		$content2 = '
			<table class="tneutral allyfiltert" style="width:100%">
			<tr><td>&nbsp;</td></tr>';
		
		while($row = mysql_fetch_assoc($query)) {
			// Allianz der Liste hinzufügen
			if(!isset($allies[$row['user_allianzenID']])) {
				$allies[$row['user_allianzenID']] = array($row['allianzenTag'], 1);
			}
			else {
				$allies[$row['user_allianzenID']][1]++;
			}
			
			$content2 .= '
			<tr data-ally="'.$row['user_allianzenID'].'">
			<td>
			<div class="fhl2" style="text-align:left">
				<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['user_playerID'].'">'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').'</a>
				&nbsp;';
			// Allianz
			if($row['user_allianzenID']) {
				$content2 .= '
				<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$row['user_allianzenID'].'&amp;ajax">'.(
					($row['allianzenTag'] != NULL) 
					? htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8')
					: '<i>unbekannt</i>'
				).'</a>';
			}
			// allianzlos
			else $content2 .= '<span class="small" style="font-style:italic">allianzlos</span>';
			$content2 .= '
			&nbsp; &nbsp;
			<span class="small '.($row['userSitterUpdate'] > time()-86400*$config['scan_veraltet_einst'] ? 'green' : 'red').'">(aktualisiert: '.($row['userSitterUpdate'] ? datum($row['userSitterUpdate']) : '<i>nie</i>').')</span>
			</div>';
			// Sitter eingescannt
			if($row['userSitterUpdate']) {
				$content2 .= '
				<table style="width:95%">
				<tr>
				<td style="width:50%;padding:15px" class="small2">
					<b>Diese Accounts k&ouml;nnen auf '.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').' zugreifen:</b>
					<br />
					'.sitter($row['userSitterTo'], $row).'
				</td>
				<td style="width:50%" class="small2">
					<b>'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').' kann auf diese Accounts zugreifen:</b>
					<br />
					'.sitter($row['userSitterFrom'], $row).'
				</td>
				</tr>
				</table>';
			}
			// Sitter noch nicht eingescannt
			else {
				$content2 .= '
			<br />
			<span class="small2" style="font-style:italic">Sitter noch nicht eingescannt</span>
			<br />';
			}
			$content2 .= '
			<br /><br />
			</td>
			</tr>';
		}
		$content2 .= '
		</table>';
		
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
				<input type="checkbox" name="'.$key.'" checked="checked" /> <a name="'.$key.'">'.$data[0].' ('.$data[1].')</a> </span>&nbsp; ';
			}
			$tmpl->content .= '
				</div>
				<br />';
		}
		
		$tmpl->content .= $content2;
		
		// Log-Eintrag
		$log = 'lässt sich die Sitterliste ';
		if($_POST['typ'] == 'ally') $log .= 'seiner Allianz';
		else if($_POST['typ'] == 'meta') $log .= 'seiner Meta';
		$log .= ' anzeigen';
			
		if($config['logging'] >= 2) {
			insertlog(22, $log);
		}
	}
}

// Leerzeile vor Fehlermeldung
if($tmpl->error) {
	$tmpl->error = '<br />'.$tmpl->error;
}

// Ausgabe
$tmpl->output();




?>