<?php
/**
 * pages/inva/archiv_details.php
 * Invasionsarchiv - Details einer Aktion anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Daten unvollständig
if(!isset($_GET['id'])) {
	$tmpl->error = 'Daten unvollständig!';
}
else {
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	
	// Datensatz abfragen
	// Bedingungen aufstellen
	$conds = array(
		"archivID = ".$_GET['id']
	);
	
	if($user->protectedAllies) {
		$conds[] = "(p1.player_allianzenID IS NULL OR p1.player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
		$conds[] = "(p2.player_allianzenID IS NULL OR p2.player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
	}
	if($user->protectedGalas) {
		$conds[] = "systeme_galaxienID NOT IN(".implode(', ', $user->protectedGalas).")";
	}
	
	// Daten abfragen
	$query = query("
		SELECT
			archivID,
			archiv_planetenID,
			archiv_systemeID,
			archivTyp,
			archiv_playerID,
			archivAggressor,
			archivEnde,
			archivFremd,
			archivKommentar,
			
			systeme_galaxienID,
			systemeX,
			systemeZ,
			
			p1.playerName,
			p1.player_allianzenID,
			p2.playerName AS a_playerName,
			p2.player_allianzenID AS a_player_allianzenID,
			
			a1.allianzenTag,
			a2.allianzenTag AS a_allianzenTag
		FROM
			".PREFIX."invasionen_archiv
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = archiv_systemeID
			LEFT JOIN ".GLOBPREFIX."player p1
				ON p1.playerID = archiv_playerID
			LEFT JOIN ".GLOBPREFIX."player p2
				ON p2.playerID = archivAggressor
			LEFT JOIN ".GLOBPREFIX."allianzen a1
				ON a1.allianzenID = p1.player_allianzenID
			LEFT JOIN ".GLOBPREFIX."allianzen a2
				ON a2.allianzenID = p2.player_allianzenID
		WHERE
			".implode(' AND ', $conds)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Aktion nicht gefunden
	if(!mysql_num_rows($query)) {
		$tmpl->error = 'Die Aktion wurde nicht gefunden oder du hast keine Berechtigung sie anzuzeigen!';
		$tmpl->output();
		die();
	}
	
	$data = mysql_fetch_assoc($query);
	
	// keine Berechtigung
	if((!$data['archivFremd'] AND $data['archivTyp'] != 5 AND !$user->rechte['invasionen']) OR (($data['archivFremd'] OR $data['archivTyp'] == 5) AND !$user->rechte['fremdinvakolos'])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
		$tmpl->output();
		die();
	}
	
	$tmpl->name = 'Archiv - Details der '.$invatyp[$data['archivTyp']].' (Planet '.$data['archiv_planetenID'].')';
	
	$tmpl->content = '
	<div class="icontent">
		<b>Galaxie</b>: 
		<span style="color:'.sektor_coord($data['systemeX'], $data['systemeZ']).'">'.$data['systeme_galaxienID'].'</span>
		&nbsp; &nbsp; 
		<b>System</b>: 
		<a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$data['archiv_systemeID'].'">'.$data['archiv_systemeID'].'</a>
		&nbsp; &nbsp; 
		<b>Planet</b>: 
		<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$data['archiv_planetenID'].'">'.$data['archiv_planetenID'].'</a>
		<br /><br />
		
		<table class="tneutral" style="width:500px">
		<tr>
		<td style="width:50%;line-height:20px;vertical-align:top">
			<b>Typ:</b> '.$invatyp[$data['archivTyp']];
	// bei Besatzungen Ende nicht anzeigen
	if($data['archivTyp'] != 4) {
		$tmpl->content .= '
		<br />
		<b>Ende:</b> '.($data['archivEnde'] ? datum($data['archivEnde']) : '<i>unbekannt</i>');
	}
	
	$tmpl->content .= '
			<br />
			<b>Opfer</b>: <a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['archiv_playerID'].'">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a>'.($data['allianzenTag'] != NULL ? '&nbsp; <a class="link winlink contextmenu small2" data-link="index.php?p=show_ally&amp;id='.$data['player_allianzenID'].'">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>' : '').'
			<br />
			<b>Aggressor</b>: '.($data['a_playerName'] != NULL ? '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['archivAggressor'].'">'.htmlspecialchars($data['a_playerName'], ENT_COMPAT, 'UTF-8').'</a>' : '<i>unbekannt</i>').($data['a_allianzenTag'] != NULL ? '&nbsp; <a class="link winlink contextmenu small2" data-link="index.php?p=show_ally&amp;id='.$data['a_player_allianzenID'].'">'.htmlspecialchars($data['a_allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>' : '').'
		</td>
		<td style="width:50%;line-height:20px;vertical-align:top">';
	// Kommentar
	if(trim($data['archivKommentar']) != '') {
		$tmpl->content .= '
			<div class="small2" style="margin-top:8px">
				<div class="kommentar" style="float:left"></div> &nbsp;
				<div class="kommentarc">'.nl2br(htmlspecialchars($data['archivKommentar'], ENT_COMPAT, 'UTF-8')).'</div>
			</div>';
	}
	$tmpl->content .= '
		</td>
		</tr>
		</table>
		<br />
		<b>InvaLog-Eintr&auml;ge</b>
		<br />
		<table class="data" style="width:100%;margin-top:8px">';
	
	// Invalog-Einträge abfragen
	$query = query("
		SELECT
			invalogTime,
			invalog_playerID,
			invalogText,
			
			playerName
		FROM
			".PREFIX."invasionen_log
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = invalog_playerID
		WHERE
			invalog_invasionenID = ".$_GET['id']."
		ORDER BY
			invalogTime DESC
		LIMIT 100
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$tmpl->content .='
		<tr>
			<td>'.datum($row['invalogTime']).'</td>
			<td>'.($row['playerName'] != NULL ? '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['invalog_playerID'].'">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a>' : '&nbsp;').'</td>
			<td>'.htmlspecialchars($row['invalogText'], ENT_COMPAT, 'UTF-8').'</td>
		</tr>';
	}
	
	$tmpl->content .= '
		</table>
	</div>';
	
	
	// Log-Eintrag
	if($config['logging'] == 3) {
		insertlog(5, 'lässt sich die Details zur Archiv-'.$invatyp[$data['archivTyp']].' '.$_GET['id'].' (Planet '.$data['archiv_planetenID'].') anzeigen');
	}
}

// Ausgabe
$tmpl->output();


?>