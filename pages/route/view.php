<?php
/**
 * pages/route/view.php
 * Routenansicht
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// Tabellenklasse laden
if(!class_exists('datatable')) {
	include './common/datatable.php';
}


$route = new route;

if(!isset($_GET['id'])) {
	$tmpl->error = 'Keine ID übergeben';
}
// Laden der Route fehlgeschlagen
else if(($return = $route->load($_GET['id'])) !== true) {
	$tmpl->error = $return;
}
// keine Berechtigung
else if(!$route->rechte_view()) {
	$tmpl->error = 'Du hast keine Berechtigung, die Route/Liste anzuzeigen!';
}
// Route anzeigen
else {
	$tmpl->name = $rnames[$route->info['routenListe']].' '.htmlspecialchars($route->info['routenName'], ENT_COMPAT, 'UTF-8');
	
	$_GET['id'] = (int)$_GET['id'];
	$heute = strtotime('today');
	$r = $route->rechte_edit();
	
	if(!isset($_GET['update'])) {
		$tmpl->content .= '
<div class="icontent route'.$_GET['id'].'">';
	}
	$tmpl->content .= '
	<table class="tneutral" style="width:100%">
	<tr>
	<td style="width:50%;line-height:22px;vertical-align:top">
	erstellt von: '.($route->info['user_playerName'] != NULL ? '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$route->info['routen_playerID'].'">'.htmlspecialchars($route->info['user_playerName'], ENT_COMPAT, 'UTF-8').'</a>' : '<i>unbekannt</i>').'<br />
	'.($route->gala ? 'Galaxie: '.$route->gala.'<br />' : '').'
	Planeten: '.$route->count.'<br />
	Sichtbarkeit: '.$rtypes[$route->info['routenTyp']].(($route->info['routenEdit'] AND $route->info['routenTyp'] > 1) ? ' (auch bearbeiten)' : '').'<br />
	'.(!$route->liste ? 'Status: '.($route->finished ? 'berechnet' : '<i>in Bearbeitung</i>').'<br />' : '').'
	</td>
	<td style="width:50%;line-height:22px;vertical-align:top">';
	// Rechte zum Bearbeiten
	if($route->rechte_edit()) {
		$tmpl->content .= '<b>Optionen</b>
	<br />
	
	<a class="dellink link" data-link="index.php?p=route&amp;del='.$_GET['id'].'" style="display:none"></a>';
		
		// berechnet -> in den Bearbeitungsmodus zurücksetzen
		if($route->finished) {
			$tmpl->content .= '
	<a onclick="if(window.confirm(\'Soll die '.$rnames[$route->info['routenListe']].' wirklich in den Bearbeitungsmodus zurückgesetzt werden?\')){ajaxcall(\'index.php?p=route&amp;sp=reset&amp;id='.$_GET['id'].'\', false, false, false)}">in den Bearbeitungsmodus zur&uuml;cksetzen</a>
	<br />';
		}
		// nicht berechnet
		else {
			// Planeten hinzufügen
			$tmpl->content .= '
	<a class="link winlink contextmenu" data-link="index.php?p=route&amp;sp=addoptions&amp;id='.$_GET['id'].'">Planeten hinzuf&uuml;gen / entfernen</a>
	<br />';
			// berechnen
			if(!$route->liste AND $route->count) {
				$tmpl->content .= '
	<a onclick="$(this).parents(\'div.icontent\').find(\'div.routeberechnen\').slideToggle(200).find(\':text\').focus()">Route berechnen</a>
	<br />';
			}
		}
		
		// auf der Karte anzeigen
		if($user->rechte['karte'] AND $route->count) {
			$tmpl->content .= '
	<div>
	<form action="#" name="route_karte" onsubmit="$(\'&lt;a class=&quot;link winlink&quot; data-link=&quot;index.php?p=route&amp;sp=karte&amp;id='.$route->id.'&amp;gala=\'+this.gala.value+\'&quot;&gt;&lt;/a&gt;\').appendTo(this);$(this).find(\'a.link\').trigger(\'click\').remove();return false">
	<a onclick="$(this.parentNode).trigger(\'onsubmit\')">Systeme auf der Karte anzeigen'.($route->gala ? '</a>
	<input type="hidden" name="gala" value="'.$route->gala.'" />' : ' - Galaxie</a>  
	&nbsp;<input type="text" class="smalltext" style="width:30px" name="gala" value="1" onclick="this.select()" />
	<input type="submit" style="display:none" />').'
	</form>
	</div>';
		}
		
		// bearbeiten und löschen
		$tmpl->content .= '
	<a class="link contextmenu" data-link="index.php?p=route&amp;sp=edit&amp;id='.$_GET['id'].'">Details der '.$rnames[$route->info['routenListe']].' &auml;ndern</a>
	<br />
	<a onclick="if(window.confirm(\'Soll die '.$rnames[$route->info['routenListe']].' wirklich gelöscht werden?\')){$(this).siblings(\'.dellink\').trigger(\'click\');}">'.$rnames[$route->info['routenListe']].' l&ouml;schen</a>
	<br />';
	}
	$tmpl->content .= '
	</td>
	</tr>
	</table>';
	
	// Liste -> nach Entfernung sortieren
	if($route->liste AND $route->count) {
		$tmpl->content .= '
	<div class="center">
	<br /><br />
	<form action="#" name="listesortieren" onsubmit="form_send(this, \'index.php?p=route&amp;sp=view&amp;id='.$_GET['id'].'&amp;update&amp;sort=\'+this.start.value+\'&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
	Liste nach Entfernung zu 
	&nbsp;<input type="text" class="smalltext tooltip" style="width:80px" name="start" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)"'.(isset($_GET['sort']) ? ' value="'.htmlspecialchars($_GET['sort'], ENT_COMPAT, 'UTF-8').'"' : '').' />
	&nbsp;<input type="submit" class="button" value="sortieren" />
	&nbsp;<span class="small hint">(Planet, System oder Koordinaten)</span>
	</form>
	<div class="ajax"></div>
	</div>';
	}
	// Maske zum Berechnen der Route
	if(!$route->finished AND !$route->liste AND $route->count) {
		$tmpl->content .= '
	<div class="routeberechnen center" style="display:none">
	<br />
	<form action="#" name="routeberechnen" onsubmit="form_send(this, \'index.php?p=route&amp;sp=compute&amp;id='.$_GET['id'].'&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
	Route vom Planeten 
	&nbsp;<input type="text" class="smalltext" style="width:80px" name="start" />&nbsp;
	aus 
	&nbsp;<input type="submit" class="button" value="berechnen" />
	</form>
	<div class="ajax"></div>
	</div>';
	}
	
	// Sortierung nach Entfernung
	$sort = false;
	if(isset($_GET['sort']) AND $_GET['sort'] != '' AND $route->liste) {
		$sort = flug_point($_GET['sort']);
		
		// Fehler
		if(!is_array($sort)) {
			if($sort == 'coords') {
				$tmpl->error = '<br />Ungültige Koordinaten eingegeben!';
			}
			else if($sort == 'data') {
				$tmpl->error = '<br />Ungültige Daten eingegeben!';
			}
			else {
				$tmpl->error = '<br />Der Ausgangspunkt wurde nicht gefunden!';
			}
			
			$tmpl->output();
			die();
		}
	}
			
	// Route / Liste anzeigen
	$tmpl->content .= '
	<br /><br />
	<form name="routecbs" action="#">
	<table class="data searchtbl" name="'.$route->id.'" style="width:100%;margin-bottom:5px">
	<tr>';
		if($route->finished) {
			$tmpl->content .= '
		<th>&nbsp;</th>';
		}
		else if($r) {
			$tmpl->content .= '
		<th><input type="checkbox" onclick="$(this).parents(\'table\').find(\'input\').attr(\'checked\', this.checked)" /></th>';
		}
		$tmpl->content .= ($route->liste ? '<th>G</th>' : '').'
		<th>Sys</th>
		<th>ID</th>
		<th>Name</th>
		<th>Inhaber</th>
		<th>Allianz</th>
		<th>Status</th>
		<th>Gr&ouml;&szlig;e</th>
		<th>&nbsp;</th>
		'.(($route->finished OR $sort) ? '<th>Entf A'.$route->antrieb.'</th>' : '').'
		<th>Scan</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		'.($user->rechte['toxxraid'] ? '<th>geraidet</th>
		<th>Toxx bis</th>' : '').'
		'.((!$route->finished AND $r) ? '<th>&nbsp;</th>' : '').'
	</tr>';
	
	if($route->count) {
		// Bedingungen aufstellen
		$conds = array(
			"planetenID IN(".implode(",",array_keys($route->data)).")"
		);
		
		// Sortierung -> Gala
		if($sort) {
			$conds[] = 'systeme_galaxienID = '.$sort[0];
		}
		
		// eingeschränkte Berechtigungen
		if($user->protectedGalas) {
			$conds[] = "systeme_galaxienID NOT IN(".implode(",", $user->protectedGalas).")";
		}
		if($user->protectedAllies) {
			$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(",", $user->protectedAllies)."))";
		}
		
		$data = array();
		
		// Planeten abfragen
		$query = query("
			SELECT
				planetenID,
				planetenName,
				planeten_playerID,
				planeten_systemeID,
				planetenGroesse,
				planetenTyp,
				planetenRMErz,
				planetenRMMetall,
				planetenRMWolfram,
				planetenRMKristall,
				planetenRMFluor,
				planetenUpdateOverview,
				planetenUnscannbar,
				planetenGebPlanet,
				planetenGebOrbit,
				planetenKategorie,
				planetenGeraidet,
				planetenGetoxxt,
				planetenKommentar,
				
				systeme_galaxienID,
				systemeX,
				systemeZ,
				".($sort ? entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $sort[1], $sort[2], $sort[3], $sort[4])." AS planetenEntfernung," : "")."
				
				playerName,
				player_allianzenID,
				playerUmod,
				playerRasse,
				
				allianzenTag,
				
				register_allianzenID,
				
				statusStatus
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON planeten_playerID = playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			WHERE
				".implode(' AND ', $conds)."
			ORDER BY
				NULL
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$data[$row['planetenID']] = $row;
			// Sortierung
			if($sort) {
				$route->data[$row['planetenID']] = $row['planetenEntfernung'];
			}
		}
		
		// sortieren
		if($sort) {
			asort($route->data);
		}
		
		foreach($route->data as $id=>$entf) {
			if(isset($data[$id])) {
				$row =& $data[$id];
				
				$tmpl->content .= '
	<tr'.($route->info['routenMarker'] == $row['planetenID'] ? ' class="trhighlight"' : '').'>';
				// Marker
				if($route->finished) {
					$tmpl->content .= '
		<td class="routemarker userlistaction" name="'.$row['planetenID'].'">&nbsp;</td>';
				}
				else if($r) {
					$tmpl->content .= '<td><input type="checkbox" name="'.$id.'" /></td>';
				}
				$tmpl->content .=  ($route->liste ? '<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>' : '').'
		<td>'.($route->liste ? datatable::system($row['planeten_systemeID']) : datatable::systemsektor($row['planeten_systemeID'], $row['systemeX'], $row['systemeZ'])).'</td>
		<td>'.datatable::planet($row['planetenID']).'</a></td>
		<td>'.datatable::planet($row['planetenID'], $row['planetenName']).'</td>
		<td>'.datatable::inhaber($row['planeten_playerID'], $row['playerName'], $row['playerUmod'], $row['playerRasse']).'</td>
		<td>'.datatable::allianz($row['player_allianzenID'], $row['allianzenTag']).'</td>
		<td>'.datatable::status($row['statusStatus'], $row['player_allianzenID']).'</td>
		<td>'.$row['planetenGroesse'].'</td>
		<td>'.datatable::typ($row['planetenTyp']).'</td>';
				// Entfernung
				if($route->finished) {
					$tmpl->content .= '<td>'.flugdauer($entf, $route->antrieb).'</td>';
				}
				else if($sort) {
					$tmpl->content .= '<td>'.flugdauer($row['planetenEntfernung'], $route->antrieb).'</td>';
				}
				$tmpl->content .= '
		<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet'], $row['planetenUnscannbar']).'</td>
		<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
		<td>'.datatable::kategorie($row['planetenKategorie'], $row['planetenUpdateOverview'], $row).'</td>
		<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>';
				
				// geraidet und getoxxt
				if($user->rechte['toxxraid']) {
					$tmpl->content .= '
		<td>'.datatable::geraidet($row['planetenGeraidet'], $row['planetenID']).'</td>
		<td>'.datatable::getoxxt($row['planetenGetoxxt'], $row['planetenID']).'</td>';
				}
				
				// Planet löschen
				if(!$route->finished AND $r) {
					$tmpl->content .= '
		<td class="userlistaction"><img src="img/layout/leer.gif" style="background-position:-1040px -91px;cursor:pointer" class="hoverbutton" onclick="if(window.confirm(\'Soll der Planet wirklich entfernt werden?\')){ajaxcall(\'index.php?p=route&amp;sp=remove&amp;id='.$_GET['id'].'&amp;remove='.$id.'&amp;list&amp;ajax\', false, false, false)}" title="Planet entfernen" /></td>';
				}
			}
			// keine Daten verfügbar oder keine Berechtigung
			else if(!$sort) {
				$tmpl->content .= '
	<tr>
		<td>&nbsp;</td>
		'.($route->liste ? '<td>&nbsp;</td>' : '').'
		<td>&nbsp;</td>
		<td><a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$id.'&ajax">'.$id.'</a></td>
		<td colspan="5" style="font-style:italic">keine Daten verf&uuml;gbar oder keine Berechtigung</td>
		'.($route->finished ? '<td>&nbsp;</td>' : '').'
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		'.((!$route->finished AND $r) ? '<td class="userlistaction"><img src="img/layout/leer.gif" style="background-position:-1040px -91px;cursor:pointer" class="hoverbutton" onclick="if(window.confirm(\'Soll der Planet wirklich entfernt werden?\')){ajaxcall(\'index.php?p=route&amp;sp=remove&amp;id='.$_GET['id'].'&remove='.$id.'&ajax\', false, false, false)}" title="Planet entfernen" /></td>' : '').'
	</tr>';
			}
		}
	}
	// Route leer
	else {
		$c = 14;
		if($r) {
			$c += 2;
		}
		if($route->liste) {
			$c++;
		}
		$tmpl->content .= '
	<tr>
		<td colspan="'.$c.'" class="center" style="font-style:italic">Die '.($route->liste ? 'Liste' : 'Route').' enth&auml;lt noch keine Planeten</td>
	</tr>
		';
	}
	
	$tmpl->content .= '
	</table>';
	// markierte Planeten entfernen
	if(!$route->finished AND $r AND $route->count) {
		$tmpl->content .= '
		<a onclick="if(window.confirm(\'Sollen die markierten Planeten wirklich entfernt werden?\')){ajaxcall(\'index.php?p=route&amp;sp=remove&amp;id='.$_GET['id'].'&amp;ajax\', false, $(this.parentNode).serialize(), false)}" style="font-style:italic">markierte entfernen</a>';
	}
	$tmpl->content .= '
	</form>';
	if(!isset($_GET['update'])) {
		$tmpl->content .= '
</div>';
	}
}

// Update -> Content in Script laden
if(isset($_GET['update']) AND $tmpl->error == '') {
	$tmpl->content = addslashes(str_replace(array("\r\n", "\n"), "", $tmpl->content));
	$tmpl->script = '$(\'.route'.$_GET['id'].'\').html(\''.$tmpl->content.'\');';
	$tmpl->content = '';
	
	if($sort) {
		$tmpl->script .= '
$(\'.route'.$_GET['id'].' :text\').select();';
	}
}
else {
	// Log-Eintrag
	if($config['logging'] == 3) {
		insertlog(5, 'lässt sich die Route/Liste '.$route->info['routenName'].' ('.$_GET['id'].') anzeigen');
	}
}

// Ausgabe
$tmpl->output();



?>