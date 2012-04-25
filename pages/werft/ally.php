<?php
/**
 * pages/werft/own.php
 * eigene Werften (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Tabellenklasse laden
if(!class_exists('datatable')) {
	include './common/datatable.php';
}


$content =& $csw->data['ally']['content'];

$content = '
<div class="hl2 werft_allyhead">Verb&uuml;ndete Werften</div>
<div class="icontent">
<form name="ress_ally" action="#" onsubmit="return form_sendget(this, \'index.php?p=werft&amp;sp=ally&amp;s=1\')">
<div class="fcbox center formbox">
beschr&auml;nken auf eingetragene Werften deiner 
&nbsp;<select name="meta">
'.($user->rechte['werft_ally'] ? '<option value="">Allianz</option>' : '').'
'.($user->rechte['werft_meta'] ? '<option value="1"'.(isset($_GET['meta']) ? ' selected="selected"' : '').'>Meta</option>' : '').'
</select>&nbsp; 
in Galaxie 
&nbsp;<input type="text" class="smalltext" name="g" value="'.(isset($_GET['g']) ? htmlspecialchars($_GET['g'], ENT_COMPAT, 'UTF-8') : '').'" />
<br />
<input type="checkbox" name="leer" value="1"'.(isset($_GET['leer']) ? ' checked="checked"': '').' /> <span class="togglecheckbox" data-name="leer">nur leerstehende Werften</span> &nbsp; &nbsp; &nbsp;
<input type="checkbox" name="bed" value="1"'.(isset($_GET['bed']) ? ' checked="checked"': '').' /> <span class="togglecheckbox" data-name="bed">nur Werften mit Ressbedarf</span>
<br />
<input type="submit" class="button" style="width:120px" value="Werften filtern" /> 
<input type="button" class="button link" style="width:120px" value="Filter aufheben" data-link="index.php?p=werft&amp;sp=ally" />
</div>
</form>

<br /><br />';

// 2 Tage in der Vergangenheit
$t = time()-172800;

// Bedingungen
$conds = array(
	"planetenWerft = 1"
);

// eingeschränkte Berechtigungen
if(!$user->rechte['werft_ally'] AND $user->allianz) {
	$conds[] = "player_allianzenID != ".$user->allianz;
}
if(!$user->rechte['werft_meta'] AND $user->allianz) {
	$conds[] = "(player_allianzenID = ".$user->allianz." OR statusStatus IS NULL OR statusStatus != ".$status_meta.")";
}
if($user->protectedAllies) {
	$conds[] = "player_allianzenID NOT IN(".implode(", ", $user->protectedAllies).")";
}
if($user->protectedGalas) {
	$conds[] = "systeme_galaxienID NOT IN(".implode(", ", $user->protectedGalas).")";
}

// Suchfilter

// Meta
if(isset($_GET['meta'])) {
	$conds[] = "statusStatus = ".$status_meta;
}
// Ally
else {
	$conds[] = "player_allianzenID = ".$user->allianz;
}

// Gala
if(isset($_GET['g']) AND (int)$_GET['g']) {
	$conds[] = "systeme_galaxienID = ".(int)$_GET['g'];
}

// nur leerstehende Werften
if(isset($_GET['leer'])) {
	$conds[] = "planetenWerftFinish < ".time();
	// muss innerhalb der letzten 2 Tage eingescannt worden sein
	$conds[] = "planetenUpdateOverview > ".$t;
}

// Sortierung
$sort = array(
	'standard'=>'(planetenUpdateOverview > '.$t.') DESC, planetenWerftFinish ASC',
	'id'=>'planetenID ASC',
	'name'=>'planetenName ASC',
	'player'=>'playerName ASC',
	'ally'=>'player_allianzenID ASC',
	'groesse'=>'planetenGroesse ASC',
	'scan'=>'planetenUpdateOverview ASC'
);

if(!isset($_GET['sort']) OR !isset($sort[$_GET['sort']])) {
	$sort = $sort['standard'];
}
else {
	$sort = $sort[$_GET['sort']];
}

$querystring = $_SERVER['QUERY_STRING'];
$querystring = preg_replace('/&sort=([a-z]+)/', '', $querystring);
$querystring = str_replace('&switch', '', $querystring);
$querystring = htmlspecialchars($querystring, ENT_COMPAT, 'UTF-8');

// eigene Ressplanis abfragen
$query = query("
	SELECT
		planetenID,
		planeten_playerID,
		planeten_systemeID,
		planetenName,
		planetenGroesse,
		planetenTyp,
		planetenKategorie,
		planetenRMErz,
		planetenRMMetall,
		planetenRMWolfram,
		planetenRMKristall,
		planetenRMFluor,
		planetenUpdateOverview,
		planetenUpdate,
		planetenGebPlanet,
		planetenGebOrbit,
		planetenKommentar,
		planetenWerftFinish,
		planetenWerftBedarf,
		
		systemeX,
		systemeZ,
		systeme_galaxienID,
		
		playerName,
		player_allianzenID,
		playerRasse,
		playerUmod,
		
		allianzenTag,
		
		statusStatus
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
	ORDER BY
		".$sort."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());


// Planeten eingetragen
if(mysql_num_rows($query)) {
	if($user->rechte['routen']) {
		$content .= '
	<form name="werft_allyroutenform" onsubmit="return false">';
	}
	$content .= '
	<table class="data searchtbl thighlight" style="width:100%;margin:auto">
	<tr>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=id">G</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=id">System</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=id">ID</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=name">Name</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=player">Inhaber</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=ally">Allianz</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=groesse">Gr&ouml;&szlig;e</a></th>
	<th>&nbsp;</th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=scan">Scan</a></th>
	<th><a class="link" data-link="index.php?'.$querystring.'&amp;sort=standard">fertig</a></th>
	<th>Bedarf</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>';
	if($user->rechte['routen']) {
		$content .= '
	<th>&nbsp;</th>';
	}
	$content .= '
	</tr>';
	
	$heute = strtotime('today');
	
	while($row = mysql_fetch_assoc($query)) {
		// Bedarf ausrechnen
		$bedarf = false;
		if($row['planetenWerftBedarf'] != '' AND $row['planetenUpdateOverview'] > $t) {
			$b = unserialize($row['planetenWerftBedarf']);
			if($row['planetenRMErz'] < $b[0] OR $row['planetenRMMetall'] < $b[1] OR $row['planetenRMWolfram'] < $b[2] OR $row['planetenRMKristall'] < $b[3] OR $row['planetenRMFluor'] < $b[4]) {
				$bedarf = true;
			}
		}
		// Suchfilter Ressbedarf
		if(isset($_GET['bed']) AND !$bedarf) {
			continue;
		}
		
		$content .= '
	<tr>
	<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
	<td>'.datatable::system($row['planeten_systemeID']).'</td>
	<td>'.datatable::planet($row['planetenID']).'</a></td>
	<td>'.datatable::planet($row['planetenID'], $row['planetenName']).'</td>
	<td>'.datatable::inhaber($row['planeten_playerID'], $row['playerName'], $row['playerUmod'], $row['playerRasse']).'</td>
	<td>'.datatable::allianz($row['player_allianzenID'], $row['allianzenTag']).'</td>
	<td>'.$row['planetenGroesse'].'</td>
	<td>'.datatable::typ($row['planetenTyp']).'</td>
	<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet']).'</td>
	<td>';
		// fertig
		// unbekannt
		if($row['planetenUpdateOverview'] < $t) {
			$content .= '<span class="yellow" style="font-style:italic">unbekannt</span>';
		}
		// leerstehend
		else if($row['planetenWerftFinish'] < time()) {
			$content .= '<span class="red" style="font-weight:bold">leerstehend</span>';
		}
		// Schiff im Bau
		else if($row['planetenWerftFinish'] < time()+7200) {
			$content .= '<b>'.datum($row['planetenWerftFinish']).'</b>';
		}
		else {
			$content .= datum($row['planetenWerftFinish']);
		}
		$content .= '</td>
	<td>';
		// Bedarf
		// unbekannt
		if($row['planetenWerftBedarf'] == '' OR $row['planetenUpdateOverview'] <= $t) {
			$content .= '<span class="yellow" style="font-style:italic">unbekannt</span>';
		}
		else {
			// Bedarf ausrechnen
			$m = array(
				$row['planetenRMErz'],
				$row['planetenRMMetall'],
				$row['planetenRMWolfram'],
				$row['planetenRMKristall'],
				$row['planetenRMFluor']
			);
			
			$bm = array();
			foreach($m as $key=>$val) {
				$w = $b[$key]-$val;
				if($w > 0) {
					$bm[$key] = $w;
				}
			}
			
			// Tooltip erzeugen
			$tt = '<b>Vorrat:</b><br />';
			foreach($m as $key=>$val) {
				$tt .= $ress[$key].ressmenge2($val).' ';
			}
			$tt .= '<br /><br /><b>Bedarf:</b><br />';
			foreach($b as $key=>$val) {
				$tt .= $ress[$key].ressmenge2($val, true).' ';
			}
			if($bedarf) {
				$tt .= '<br /><br /><b>&raquo; noch zu liefern:</b><br />';
				foreach($bm as $key=>$val) {
					$tt .= $ress[$key].ressmenge2($val, true).' ';
				}
			}
			$content .= '<span class="'.($bedarf ? 'red' : 'green').' tooltip"'.($bedarf ? ' style="font-weight:bold"' : '').' data-tooltip="'.htmlspecialchars($tt, ENT_COMPAT, 'UTF-8').'">'.($bedarf ? 'ja' : 'nein').'</span>';
		}
		
		$content .= '</td>
	<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
	<td>'.datatable::kategorie($row['planetenKategorie'], $row['planetenUpdateOverview'], $row).'</td>
	<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>';
		if($user->rechte['routen']) {
			$content .= '<td><input type="checkbox" name="'.$row['planetenID'].'" /></td>';
		}
		$content .= '
	</tr>';
	}
	
	$content .= '
	</table>';
	
	if($user->rechte['routen']) {
		$content .= '
	<div style="text-align:right;margin-top:4px">
	markieren: 
	<a onclick="$(this).parents(\'form\').find(\'input\').prop(\'checked\', true);" style="font-style:italic">alle</a> /
	<a onclick="$(this).parents(\'form\').find(\'input\').prop(\'checked\', false);" style="font-style:italic">keine</a> 
	</div>
	<br />
	<div class="small2" style="text-align:right"><a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=route_addmarked&amp;ajax\', this.parentNode, false, true)">markierte Planeten zu einer Route / Liste hinzuf&uuml;gen</a></div>
	</form>';
	}
}
// keine Planeten eingetragen
else {
	$content .= '
	<br />
	<div class="center" style="font-weight:bold">Keine Werften gefunden, die den Kriterien entsprechen!</div>
	<br />';
}

$content .= '
</div>';

// scrollen
if(isset($_GET['s']) OR isset($_GET['sort'])) {
	$tmpl->script .= 'if($(\'#contentc .content:visible .werft_allyhead\').length){$(\'html,body\').scrollTop($(\'#contentc .content:visible .werft_allyhead\').offset().top-10);}';
}

// Logfile-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'zeigt die Liste der verbündeten Werften an');
}



?>