<?php
/**
 * pages/route/oview.php
 * Routenübersicht
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Routen abfragen
$query = query("
	SELECT
		routenID,
		routen_playerID,
		routen_galaxienID,
		routenName,
		routenListe,
		routenTyp,
		routenEdit,
		routenFinished,
		routenData,
		routenCount,
		routenMarker,
		
		user_playerName,
		user_allianzenID,
		
		statusStatus
	FROM
		".PREFIX."routen
		LEFT JOIN ".PREFIX."user
			ON user_playerID = routen_playerID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = user_allianzenID
			AND status_allianzenID = ".$user->allianz."
	WHERE
		".route::rechte_view_mysql()."
	ORDER BY
		(routen_playerID = ".$user->id.") DESC,
		routen_galaxienID ASC,
		routenName ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$tmpl->content .= '
<div class="icontent">
	<div class="hl2">Eigene Routen und Listen</div>
	&nbsp; <a class="link contextmenu" data-link="index.php?p=route&amp;sp=create">+ neue Route / Liste erstellen</a>
	<br /><br />';

$own = false;
$other = false;

// Routen anzeigen
while($row = mysql_fetch_assoc($query)) {
	// eigene Route
	if($row['routen_playerID'] == $user->id) {
		// Tabelle aufmachen
		if(!$own) {
			$tmpl->content .= '
	<table class="data" style="margin:auto">
	<tr>
		<th>Name</th>
		<th>Typ</th>
		<th>Gala</th>
		<th>Planeten</th>
		<th>Status</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
	</tr>';
		}
		$own = true;
	}
	else if(!$other) {
		$other = true;
		// eigene Tabelle schließen
		if($own) {
			$tmpl->content .= '
	</table>';
		}
		// keine eigenen Routen und Listen
		else {
			$tmpl->content .= '
	<br />
	<div class="center">Du hast noch keine Routen und Listen angelegt</div>';
		}
		$tmpl->content .= '
	<br /><br />
	<div class="hl2">Routen und Listen von anderen</div>
	<table class="data" style="margin:auto">
	<tr>
		<th>Name</th>
		<th>Typ</th>
		<th>Spieler</th>
		<th>Gala</th>
		<th>Planeten</th>
		<th>Status</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
	</tr>';
	}
	
	$n = ($row['routenListe'] == 1 ? 'Liste' : 'Route');
	
	$tmpl->content .= '
	<tr>
		<td><a class="link contextmenu" data-link="index.php?p=route&amp;sp=view&amp;id='.$row['routenID'].'">'.htmlspecialchars($row['routenName'], ENT_COMPAT, 'UTF-8').'</a></td>
		<td>'.$rnames[$row['routenListe']].($row['routenTyp'] < 4 ? ' ('.$rtypes[$row['routenTyp']].')' : '').'</td>
		'.($row['routen_playerID'] != $user->id ? '<td>'.($row['user_playerName'] != NULL ? '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['routen_playerID'].'">'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').'</a>' : '<i>unbekannt</i>').'</td>' : '').'
		<td>'.($row['routen_galaxienID'] ? $row['routen_galaxienID'] : '-').'</td>
		<td>'.$row['routenCount'].'</td>
		<td>'.($row['routenListe'] == 1 ? '-' : ($row['routenFinished'] ? 'berechnet' : '<i>in Bearbeitung</i>')).'</td>
		<td class="userlistaction"><img src="img/layout/leer.gif" class="link contextmenu hoverbutton" style="background-position:-1060px -91px" title="'.$n.' aufrufen" data-link="index.php?p=route&amp;sp=view&amp;id='.$row['routenID'].'" /></td>
		'.(($row['routen_playerID'] == $user->id OR $row['routenEdit']) ? '<td class="userlistaction"><img src="img/layout/leer.gif" style="background-position:-1040px -91px;cursor:pointer" class="hoverbutton" onclick="if(window.confirm(\'Soll die '.$n.' wirklich gelöscht werden?\')){$(\'<a class=&quot;link&quot; link=&quot;index.php?p=route&amp;del='.$row['routenID'].'&quot;></a>\').appendTo(this.parentNode).trigger(\'click\')}" title="'.$n.' l&ouml;schen" /></td>' : '<td>&nbsp;</td>').'
	</tr>';
	
}

// andere Tabelle schließen
if($other) {
	$tmpl->content .= '
	</table>';
}
// keine anderen Routen und Listen
else {
	if(!$own) {
		$tmpl->content .= '
	<br />
	<div class="center">Du hast noch keine Routen und Listen angelegt</div>';
	}
	else {
		$tmpl->content .= '
	</table>';
	}
	
	$tmpl->content .= '
	<br /><br />
	<div class="hl2">Routen und Listen von anderen</div>
	<br />
	<div class="center">Es sind keine Routen/Listen von anderen vorhanden oder du hast keine Berechtigung, sie anzuzeigen.</div>
	<br /><br />';
}

$tmpl->content .= '
</div>';

// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich die Routenübersicht anzeigen');
}


// Ausgabe
$tmpl->output();



?>