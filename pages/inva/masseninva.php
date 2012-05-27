<?php
/**
 * pages/inva/masseninva.php
 * Masseninva-Koordinator (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


$content =& $csw->data['masseninva']['content'];

if(!isset($_GET['update']) OR !$user->rechte['masseninva_admin']) {
	$content = '
	<div class="hl2">Masseninva-Koordinator</div>
	<div class="icontent">';
}

// Ziel-Allianzen abfragen
if(isset($config['masseninva'])) {
	$query = query("
		SELECT
			allianzenID,
			allianzenTag,
			allianzenName
		FROM
			".GLOBPREFIX."allianzen
		WHERE
			allianzenID IN(".implode(", ", $config['masseninva']).")
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}


// Masseninvas verwalten -> Box anzeigen
if($user->rechte['masseninva_admin']) {
	if(!isset($_GET['update'])) {
		$content .= '
<div class="fcbox formbox center masseninva_ziele" style="line-height:1.1em">';
	}
	$content .= '
<div class="fhl2" style="font-weight:bold">Ziele</div>
<br />';
	
	// keine Ziele angegeben
	if(!isset($config['masseninva'])) {
		$content .= '
	<div class="center">Es wurden noch keine Ziele festgelegt.</div>';
	}
	// Tabelle mit Zielen anzeigen
	else {
		$content .= '
	<table class="data" style="margin:auto">
	<tr>
	<th>ID</td>
	<th>Tag</td>
	<th>Name</td>
	'.($user->rechte['masseninva_admin'] ? '<th>&nbsp;</th>' : '').'
	</tr>';
	
		while($row = mysql_fetch_assoc($query)) {
			$content .= '
	<tr>
	<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.$row['allianzenID'].'</a></td>
	<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a></td>
	<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenName'], ENT_COMPAT, 'UTF-8').'</a></td>';
			if($user->rechte['masseninva_admin']) {
				$content .= '
	<td class="buttons"><img title="Allianz entfernen" onclick="if(window.confirm(\'Soll die Allianz wirklich entfernt werden?\')){ajaxcall(\'index.php?p=inva&amp;sp=masseninva_del&amp;id='.$row['allianzenID'].'\', this.parentNode, false, false);}" class="hoverbutton" style="background-position:-1040px -91px;cursor:pointer" src="img/layout/leer.gif" /></td>';
			}
			$content .= '
	</tr>';
		}
		
		$content .= '
	</table>';
	}
	
	$content .= '
	<br />
	<form name="masseninva_add" action="#" onsubmit="return form_send(this, \'index.php?p=inva&amp;sp=masseninva_add\', $(this).siblings(\'.ajax\'))">
	<b>Allianz(en) hinzuf&uuml;gen</b>: 
	<input type="text" class="smalltext tooltip" name="id" style="width:100px" data-tooltip="IDs mit Komma getrennt" /> 
	<input type="submit" class="button" value="hinzuf&uuml;gen" />
	</form>
	<div class="ajax center"></div>';
	if(isset($_GET['update'])) {
		$content .= '
<div class="center">
	<br />
	<a class="link hint small2" data-link="index.php?p=inva&amp;sp=masseninva">[Planetenliste aktualisieren]</a>
</div>';
	}
	
	// nur Ziele aktualisieren
	if(isset($_GET['update'])) {
		$tmpl->content = $content;
		$tmpl->output();
		die();
	}
	
	$content .= '
</div>';
}
// Ziele für normale Benutzer anzeigen
else {
	$content .= ' &nbsp;<b>Ziel-Allianzen:</b> &nbsp;';
	
	// keine Ziele angegeben
	if(!isset($config['masseninva'])) {
		$content .= '<span style="font-style:italic">noch keine festgelegt</span>';
	}
	// Ziele anzeigen
	else {
		while($row = mysql_fetch_assoc($query)) {
			$content .= '
<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a> &nbsp;';
		}
	}
	$content .= '<br />';
}



// Ziele angegeben
if(isset($config['masseninva'])) {
	// Tabellenklasse laden
	if(!class_exists('datatable')) {
		include './common/datatable.php';
	}
	
	// Bedingungen aufstellen
	$conds = array(
		"player_allianzenID IN(".implode(", ", $config['masseninva']).")"
	);
	
	if($user->protectedAllies) {
		$conds[] = "player_allianzenID NOT IN(".implode(", ", $user->protectedAllies).")";
	}
	if($user->protectedGalas) {
		$conds[] = "systeme_galaxienID NOT IN(".implode(", ", $user->protectedGalas).")";
	}
	
	// Sortierung
	$sort = array(
		'id'=>'planetenID ASC',
		'name'=>'planetenName ASC',
		'player'=>'playerName ASC',
		'ally'=>'player_allianzenID ASC',
		'groesse'=>'planetenGroesse DESC',
		'scan'=>'planetenUpdateOverview ASC',
		'bev'=>'planetenBevoelkerung DESC, planetenID ASC',
		'gate'=>'planetenGateEntf ASC'
	);
	
	if(!isset($_GET['sort']) OR !isset($sort[$_GET['sort']])) {
		$sort = $sort['id'];
	}
	else {
		$sort = $sort[$_GET['sort']];
	}

	
	
	// Eigene Ziele
	$content .= '
<br />
<div class="hl2">Eigene Ziele</div>';
	
	$conds1 = $conds;
	$conds1[] = "planetenMasseninva = ".$user->id;
	
	// Eigene Ziele abfragen
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			planeten_systemeID,
			planetenName,
			planetenGroesse,
			planetenBevoelkerung,
			planetenTyp,
			planetenKategorie,
			planetenUpdateOverview,
			planetenUpdate,
			planetenUnscannbar,
			planetenGebPlanet,
			planetenGebOrbit,
			planetenKommentar,
			planetenGateEntf,
			
			systemeX,
			systemeZ,
			systeme_galaxienID,
			
			playerName,
			player_allianzenID,
			playerRasse,
			playerUmod,
			
			allianzenTag,
			
			invasionenID
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."invasionen
				ON invasionen_planetenID = planetenID
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = allianzenID
		WHERE
			".implode(" AND ", $conds1)."
		ORDER BY
			planetenID ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$own = mysql_num_rows($query);
	
	// keine eigenen Ziele ausgewählt
	if(!$own) {
		$content .= '
<div class="center masseninva_own_empty"><br />Du hast noch keine eigenen Ziele ausgew&auml;hlt.<br /><br /></div>';
	}
	
	$content .= '
<table class="data searchtbl thighlight masseninva_own" style="width:100%;margin:auto;'.(!$own ? 'display:none' : '').'">
<tr>
	<th>G</th>
	<th>System</th>
	<th>ID</th>
	<th>Name</th>
	<th>Inhaber</th>
	<th>Allianz</th>
	<th>Gr&ouml;&szlig;e</th>
	<th>&nbsp;</th>
	<th>Bev&ouml;lkerung</th>
	<th>Gate<span class="small" style="font-weight:normal"> (A'.$user->settings['antrieb'].')</span></th>
	<th>Scan</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th>l&auml;uft</th>
	<th>&nbsp;</th>
</tr>';
	
	while($row = mysql_fetch_assoc($query)) {
		$content .= '
<tr class="masseninva'.$row['planetenID'].'">
	<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
	<td>'.datatable::system($row['planeten_systemeID']).'</td>
	<td>'.datatable::planet($row['planetenID']).'</a></td>
	<td>'.datatable::planet($row['planetenID'], $row['planetenName']).'</td>
	<td>'.datatable::inhaber($row['planeten_playerID'], $row['playerName'], $row['playerUmod'], $row['playerRasse']).'</td>
	<td>'.datatable::allianz($row['player_allianzenID'], $row['allianzenTag']).'</td>
	<td>'.$row['planetenGroesse'].'</td>
	<td>'.datatable::typ($row['planetenTyp']).'</td>
	<td>'.datatable::bevoelkerung($row['planetenBevoelkerung']).'</td>
	<td>'.datatable::gate($row['planetenGateEntf'], $user->settings['antrieb']).'</td>
	<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet'], $row['planetenUnscannbar']).'</td>
	<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
	<td>'.datatable::kategorie($row['planetenKategorie']).'</td>
	<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>
	<td>'.($row['invasionenID'] != NULL ? '<span class="green">ja</span>' : '&nbsp;').'</td>
	<td class="buttons">&rarr; <a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$user->id.'">'.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'</a> <img title="Reservierung aufheben" onclick="ajaxcall(\'index.php?p=inva&amp;sp=masseninva_unset&amp;id='.$row['planetenID'].'\', this.parentNode, false, false)" class="hoverbutton delbutton" src="img/layout/leer.gif" /></td>
</tr>';
	}
	
	$content .= '
</table>';
	
	// Alle Ziele
	$content .= '
<br />
<div class="hl2 masseninva_allhead">Alle Ziele</div>
<form name="masseninva_filter" action="#" onsubmit="return form_sendget(this, \'index.php?p=inva&amp;sp=masseninva&amp;s=1\')">
<div class="fcbox center formbox">
Galaxie 
&nbsp;<input type="text" class="smalltext" name="g" value="'.(isset($_GET['g']) ? htmlspecialchars($_GET['g'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
Mindestgr&ouml;&szlig;e 
&nbsp;<input type="text" class="smalltext" name="gr" value="'.(isset($_GET['gr']) ? htmlspecialchars($_GET['gr'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
Bev&ouml;lkerung 
&nbsp;<input type="text" class="smalltext" name="bev" value="'.(isset($_GET['bev']) ? htmlspecialchars($_GET['bev'], ENT_COMPAT, 'UTF-8') : '').'" />
<br />
<input type="submit" class="button" style="width:120px" value="Ziele filtern" /> 
<input type="button" class="button link" style="width:120px" value="Filter aufheben" data-link="index.php?p=inva&amp;sp=masseninva" />
</div>
</form>
<br />';
	
	// Suche
	// Gala
	if(isset($_GET['g'])) {
		$conds[] = "systeme_galaxienID = ".(int)$_GET['g'];
	}
	// Größe
	if(isset($_GET['gr'])) {
		$conds[] = "planetenGroesse >= ".(int)$_GET['gr'];
	}
	// Bevölkerung
	if(isset($_GET['bev'])) {
		$conds[] = "planetenBevoelkerung >= ".(int)$_GET['bev'];
	}
	
	// Treffer-Anzahl
	if(!isset($_GET['tcount']) OR !(int)$_GET['tcount']) {
		// Eigene Ziele abfragen
		$query = query("
			SELECT
				COUNT(*) AS planetenAnzahl
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			WHERE
				".implode(" AND ", $conds)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_assoc($query);
		$tcount = $data['planetenAnzahl'];
	}
	else {
		$tcount = (int)$_GET['tcount'];
	}
	
	// keine Treffer
	if(!$tcount) {
		$content .= '
<br />
<div class="center" style="font-weight:bold">Es wurden keine Ziele gefunden!</div>
<br />';
	}
	// Treffer gefunden
	else {
		// Pagebar erzeugen
		$limit = 100;
		
		$querystring = $_SERVER['QUERY_STRING'];
		if(!isset($_GET['tcount'])) {
			$querystring .= '&tcount='.$tcount;
		}
		
		if(!class_exists('pagebar')) {
			include './common/pagebar.php';
		}
		$pagebar = pagebar::generate($tcount, $limit, $querystring);
		$offset = pagebar::offset($tcount, $limit);
		
		$content .= $pagebar;
		
		$querystring = $_SERVER['QUERY_STRING'];
		$querystring = preg_replace('/&sort=([a-z]+)/', '', $querystring);
		$querystring = preg_replace('/&page=(\d+)/', '', $querystring);
		$querystring = htmlspecialchars($querystring, ENT_COMPAT, 'UTF-8');
		
		$content .= '
<table class="data searchtbl thighlight masseninva_all" style="width:100%;margin:auto">
<tr>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=id">G</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=id">System</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=id">ID</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=name">Name</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=player">Inhaber</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=ally">Allianz</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=groesse">Gr&ouml;&szlig;e</a></th>
	<th>&nbsp;</th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=bev">Bev&ouml;lkerung</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=gate">Gate<span class="small" style="font-weight:normal"> (A'.$user->settings['antrieb'].')</span></a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=scan">Scan</a></th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th>l&auml;uft</th>
	<th>&nbsp;</th>
</tr>';
		
		
		// Alle Ziele abfragen
		$query = query("
			SELECT
				planetenID,
				planeten_playerID,
				planeten_systemeID,
				planetenName,
				planetenGroesse,
				planetenBevoelkerung,
				planetenTyp,
				planetenKategorie,
				planetenUpdateOverview,
				planetenUpdate,
				planetenUnscannbar,
				planetenGebPlanet,
				planetenGebOrbit,
				planetenKommentar,
				planetenGateEntf,
				planetenMasseninva,
				
				systemeX,
				systemeZ,
				systeme_galaxienID,
				
				playerName,
				player_allianzenID,
				playerRasse,
				playerUmod,
				
				user_playerName,
				
				allianzenTag,
				
				invasionenID
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."invasionen
					ON invasionen_planetenID = planetenID
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".PREFIX."user
					ON user_playerID = planetenMasseninva
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			WHERE
				".implode(" AND ", $conds)."
			ORDER BY
				".$sort."
			LIMIT ".$offset.",".$limit."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$content .= '
<tr class="masseninva'.$row['planetenID'].'">
	<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
	<td>'.datatable::system($row['planeten_systemeID']).'</td>
	<td>'.datatable::planet($row['planetenID']).'</a></td>
	<td>'.datatable::planet($row['planetenID'], $row['planetenName']).'</td>
	<td>'.datatable::inhaber($row['planeten_playerID'], $row['playerName'], $row['playerUmod'], $row['playerRasse']).'</td>
	<td>'.datatable::allianz($row['player_allianzenID'], $row['allianzenTag']).'</td>
	<td>'.$row['planetenGroesse'].'</td>
	<td>'.datatable::typ($row['planetenTyp']).'</td>
	<td>'.datatable::bevoelkerung($row['planetenBevoelkerung']).'</td>
	<td>'.datatable::gate($row['planetenGateEntf'], $user->settings['antrieb']).'</td>
	<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet'], $row['planetenUnscannbar']).'</td>
	<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
	<td>'.datatable::kategorie($row['planetenKategorie']).'</td>
	<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>
	<td>'.($row['invasionenID'] != NULL ? '<span class="green">ja</span>' : '&nbsp;').'</td>
	<td class="buttons masseninvao'.$row['planetenID'].'">';
			// niemand
			if(!$row['planetenMasseninva']) {
				$content .= '<img title="als Ziel reservieren" onclick="ajaxcall(\'index.php?p=inva&amp;sp=masseninva_set&amp;id='.$row['planetenID'].'\', this.parentNode, false, false)" class="hoverbutton arrowbutton" src="img/layout/leer.gif" />';
			}
			// eigenes Ziel
			else if($row['planetenMasseninva'] == $user->id) {
				$content .= '&rarr; <a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$user->id.'">'.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'</a> <img title="Reservierung aufheben" onclick="ajaxcall(\'index.php?p=inva&amp;sp=masseninva_unset&amp;id='.$row['planetenID'].'\', this.parentNode, false, false)" class="hoverbutton delbutton" src="img/layout/leer.gif" />';
			}
			// reserviert
			else {
				$content .= '&rarr; '.datatable::inhaber($row['planetenMasseninva'], $row['user_playerName']);
			}
			
			$content .= '</td>
</tr>';
		}
		
		$content .= '
</table>';
		
		$content .= $pagebar;
	}
	
	// scrollen
	if(isset($_GET['s']) OR isset($_GET['page']) OR isset($_GET['sort'])) {
		$tmpl->script .= 'if($(\'#contentc .masseninva_allhead\').length){$(\'html,body\').scrollTop($(\'#contentc .masseninva_allhead\').offset().top-10);}';
	}
}

$content .= '
</div>';

// Log-Eintrag
if($config['logging'] == 3 AND !isset($_GET['page']) AND !isset($_GET['s']) AND !isset($_GET['sort'])) {
	insertlog(5, 'zeigt den Masseninva-Koordinator an');
}


?>