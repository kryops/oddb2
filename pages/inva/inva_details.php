<?php
/**
 * pages/inva/invas_details.php
 * Details einer Aktion anzeigen
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
	$data = invadata($_GET['id'], true);
	
	// keine Berechtigung
	if((!$data['invasionenFremd'] AND $data['invasionenTyp'] != 5 AND !$user->rechte['invasionen']) OR (($data['invasionenFremd'] OR $data['invasionenTyp'] == 5) AND !$user->rechte['fremdinvakolos'])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
		$tmpl->output();
		die();
	}
	
	$tmpl->name = 'Details der '.$invatyp[$data['invasionenTyp']].' (Planet '.$data['invasionen_planetenID'].')';
	
	$tmpl->content = '
	<div class="icontent">
		<b>Galaxie</b>: 
		<span style="color:'.sektor_coord($data['systemeX'], $data['systemeZ']).'">'.$data['systeme_galaxienID'].'</span>
		&nbsp; &nbsp; 
		<b>System</b>: 
		<a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$data['invasionen_systemeID'].'">'.$data['invasionen_systemeID'].'</a>
		&nbsp; &nbsp; 
		<b>Planet</b>: 
		<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$data['invasionen_planetenID'].'">'.$data['invasionen_planetenID'].'</a>
		'.($data['systemeGateEntf'] != NULL ? '&nbsp; &nbsp; 
		<b>Gate <span class="small">(A'.$user->settings['antrieb'].')</span></b>: '.flugdauer($data['systemeGateEntf'], $user->settings['antrieb']) : '').'
		<br /><br />
		
		<table class="tneutral" style="width:500px">
		<tr>
		<td style="width:50%;line-height:20px;vertical-align:top">
			<b>Typ:</b> '.$invatyp[$data['invasionenTyp']];
	// bei Besatzungen Ende nicht anzeigen
	if($data['invasionenTyp'] != 4) {
		$tmpl->content .= '
		<br />
		<b>Ende:</b> '.($data['invasionenEnde'] ? datum($data['invasionenEnde']) : '<i>unbekannt</i>');
	}
	
	$tmpl->content .= '
			<br />
			<b>Opfer</b>: '.($data['playerName'] != NULL ? '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['invasionen_playerID'].'">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a>' : '<i>unbekannt</i>').($data['allianzenTag'] != NULL ? '&nbsp; <a class="link winlink contextmenu small2" data-link="index.php?p=show_ally&amp;id='.$data['player_allianzenID'].'">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>' : '').'
			<br />
			<b>Aggressor</b>: '.($data['a_playerName'] != NULL ? '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['invasionenAggressor'].'">'.htmlspecialchars($data['a_playerName'], ENT_COMPAT, 'UTF-8').'</a>' : '<i>unbekannt</i>').($data['a_allianzenTag'] != NULL ? '&nbsp; <a class="link winlink contextmenu small2" data-link="index.php?p=show_ally&amp;id='.$data['a_player_allianzenID'].'">'.htmlspecialchars($data['a_allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>' : '').'
		</td>
		<td style="width:50%;line-height:20px;vertical-align:top">';
	// Status, Freundlich und Abbrecher bei Fremdinvas und Kolos nicht anzeigen
	if(!$data['invasionenFremd'] AND $data['invasionenTyp'] != 5) {
		$tmpl->content .= '
			<b>Status</b>: <span><span class="'.($data['invasionenFreundlich'] ? 'green' : 'red').'">'.($data['invasionenFreundlich'] ? 'freundlich' : 'feindlich').'</span>';
		if($user->rechte['invasionen_admin'] OR $data['invasionen_playerID'] == $user->id) {
			$tmpl->content .= ' &nbsp;<a onclick="ajaxcall(\'index.php?p=inva&amp;sp=inva_freundlich&amp;id='.$_GET['id'].'&amp;status='.($data['invasionenFreundlich'] ? '0' : '1').'&amp;ajax\', this.parentNode, false, false)" class="small2 hint">[auf '.($data['invasionenFreundlich'] ? 'feindlich' : 'freundlich').' setzen]</a>';
		}
		$tmpl->content .= '</span>
			<br />
			<b>Abbrecher</b>: <span>'.($data['invasionenAbbrecher'] ? '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['invasionenAbbrecher'].'">'.htmlspecialchars($data['abbr_playerName'], ENT_COMPAT, 'UTF-8').'</a>' : '<i>niemand</i>').' 
			&nbsp;<a onclick="';
		// Abbruch schon übernommen
		if($data['invasionenAbbrecher'] AND $data['invasionenAbbrecher'] != $user->id) {
			$tmpl->content .= 'if(window.confirm(\'Der Abbruch wurde schon von jemand anderem übernommen.\nTrotzdem weitermachen?\')){';
		}
		$tmpl->content .= 'ajaxcall(\'index.php?p=inva&amp;sp=inva_abbruch&amp;id='.$_GET['id'].'&amp;status='.($data['invasionenAbbrecher'] == $user->id ? '0' : '1').'&amp;ajax\', this.parentNode, false, false)';
		if($data['invasionenAbbrecher'] AND $data['invasionenAbbrecher'] != $user->id) {
			$tmpl->content .= '}';
		}
		$tmpl->content .= '" class="small2 hint">['.($data['invasionenAbbrecher'] == $user->id ? 'zur&uuml;ckziehen' : '&uuml;bernehmen').']</a></span>
			';
	}
	$tmpl->content .= '
			<div class="small2" style="margin-top:8px">
			<div class="kommentar" style="float:left"></div> &nbsp; ';
	// Kommentar
	if(trim($data['invasionenKommentar']) != '') {
		$tmpl->content .= '
			&nbsp; <a onclick="invakommentar_edit('.$_GET['id'].', this.parentNode, true)" class="hint">[&auml;ndern]</a>
			
			<div class="kommentarc">'.nl2br(htmlspecialchars($data['invasionenKommentar'], ENT_COMPAT, 'UTF-8')).'</div>';
	}
	// kein Kommentar
	else {
		$tmpl->content .= '&nbsp; <a onclick="invakommentar_edit('.$_GET['id'].', this.parentNode, false)">Kommentar hinzuf&uuml;gen</a>';
	}
	$tmpl->content .= '
			</div>
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
		insertlog(5, 'lässt sich die Details zur '.$invatyp[$data['invasionenTyp']].' '.$_GET['id'].' (Planet '.$data['invasionen_planetenID'].') anzeigen');
	}
}

// Ausgabe
$tmpl->output();


?>