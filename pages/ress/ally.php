<?php
/**
 * pages/ress/ally.php
 * Verbündete Ressplanis
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Tabellenklasse laden
if(!class_exists('datatable')) {
	include './common/datatable.php';
}



$content =& $csw->data['ally']['content'];

$content = '
<div class="hl2 ress_allyhead">Verb&uuml;ndete Ressplanis</div>
<div class="icontent">
<form name="ress_ally" action="#" onsubmit="return form_sendget(this, \'index.php?p=ress&amp;sp=ally&amp;s=1\')">
<div class="fcbox center formbox">
beschr&auml;nken auf eingetragene Ressplaneten deiner 
&nbsp;<select name="meta">
'.($user->rechte['ressplani_ally'] ? '<option value="">Allianz</option>' : '').'
'.($user->rechte['ressplani_meta'] ? '<option value="1"'.(isset($_GET['meta']) ? ' selected="selected"' : '').'>Meta</option>' : '').'
</select>&nbsp; 
in Galaxie 
&nbsp;<input type="text" class="smalltext" name="g" value="'.(isset($_GET['g']) ? htmlspecialchars($_GET['g'], ENT_COMPAT, 'UTF-8') : '').'" />
<br />
Mindestmengen: 
<img src="img/layout/leer.gif" class="ress ress_form erz" /> 
<input type="text" class="smalltext" name="erz" value="'.(isset($_GET['erz']) ? htmlspecialchars($_GET['erz'], ENT_COMPAT, 'UTF-8') : '').'" /> 
<img src="img/layout/leer.gif" class="ress ress_form metall" /> 
<input type="text" class="smalltext" name="metall" value="'.(isset($_GET['metall']) ? htmlspecialchars($_GET['metall'], ENT_COMPAT, 'UTF-8') : '').'" /> 
<img src="img/layout/leer.gif" class="ress ress_form wolfram" /> 
<input type="text" class="smalltext" name="wolfram" value="'.(isset($_GET['wolfram']) ? htmlspecialchars($_GET['wolfram'], ENT_COMPAT, 'UTF-8') : '').'" /> 
<img src="img/layout/leer.gif" class="ress ress_form kristall" /> 
<input type="text" class="smalltext" name="kristall" value="'.(isset($_GET['kristall']) ? htmlspecialchars($_GET['kristall'], ENT_COMPAT, 'UTF-8') : '').'" /> 
<img src="img/layout/leer.gif" class="ress ress_form fluor" /> 
<input type="text" class="smalltext" name="fluor" value="'.(isset($_GET['fluor']) ? htmlspecialchars($_GET['fluor'], ENT_COMPAT, 'UTF-8') : '').'" />
<br />
gesamter Ressvorrat: 
&nbsp;<input type="text" class="smalltext" name="ress" value="'.(isset($_GET['ress']) ? htmlspecialchars($_GET['ress'], ENT_COMPAT, 'UTF-8') : '').'" />
<br />
<input type="submit" class="button" style="width:120px" value="Ressplanis filtern" /> 
<input type="button" class="button link" style="width:120px" value="Filter aufheben" data-link="index.php?p=ress&amp;sp=ally" />
<div class="small hint" style="line-height:1.5em">(f&uuml;r weitere Filter benutze bitte die Suchfunktion)</div>
</div>
</form>

<br /><br />';

// Bedingungen
$conds = array(
	"planetenRessplani = 1"
);

// eingeschränkte Berechtigungen
if(!$user->rechte['ressplani_ally'] AND $user->allianz) {
	$conds[] = "player_allianzenID != ".$user->allianz;
}
if(!$user->rechte['ressplani_meta'] AND $user->allianz) {
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

// Ressmengen
if(isset($_GET['erz'])) {
	$conds[] = "planetenRMErz >= ".(int)$_GET['erz'];
}
if(isset($_GET['metall'])) {
	$conds[] = "planetenRMMetall >= ".(int)$_GET['metall'];
}
if(isset($_GET['wolfram'])) {
	$conds[] = "planetenRMWolfram >= ".(int)$_GET['wolfram'];
}
if(isset($_GET['kristall'])) {
	$conds[] = "planetenRMKristall >= ".(int)$_GET['kristall'];
}
if(isset($_GET['fluor'])) {
	$conds[] = "planetenRMFluor >= ".(int)$_GET['fluor'];
}

if(isset($_GET['ress'])) {
	$conds[] = "planetenRMGesamt >= ".(int)$_GET['ress'];
}

// Sortierung
$sort = array(
	'id'=>'planetenID ASC',
	'name'=>'planetenName ASC',
	'player'=>'playerName ASC',
	'ally'=>'player_allianzenID ASC',
	'groesse'=>'planetenGroesse ASC',
	'scan'=>'planetenUpdateOverview ASC',
	'erz'=>'planetenRMErz DESC',
	'metall'=>'planetenRMMetall DESC',
	'wolfram'=>'planetenRMWolfram DESC',
	'kristall'=>'planetenRMKristall DESC',
	'fluor'=>'planetenRMFluor DESC'
);

if(!isset($_GET['sort']) OR !isset($sort[$_GET['sort']])) {
	$sort = $sort['id'];
}
else {
	$sort = $sort[$_GET['sort']];
}

$querystring = $_SERVER['QUERY_STRING'];
$querystring = preg_replace('/&sort=([a-z]+)/', '', $querystring);
$querystring = str_replace('&switch', '', $querystring);
$querystring = htmlspecialchars($querystring, ENT_COMPAT, 'UTF-8');

$t = time();
$ids = array();


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
	<form name="ress_allyroutenform" onsubmit="return false">';
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
	<th><div class="link ress erz" data-link="index.php?'.$querystring.'&amp;sort=erz"></div></th>
	<th><div class="link ress metall" data-link="index.php?'.$querystring.'&amp;sort=metall"></div></th>
	<th><div class="link ress wolfram" data-link="index.php?'.$querystring.'&amp;sort=wolfram"></div></th>
	<th><div class="link ress kristall" data-link="index.php?'.$querystring.'&amp;sort=kristall"></div></th>
	<th><div class="link ress fluor" data-link="index.php?'.$querystring.'&amp;sort=fluor"></div></th>
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
		$content .= '
	<tr>
	<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
	<td>'.datatable::system($row['planeten_systemeID']).'</td>
	<td>'.datatable::planet($row['planetenID'], false, $t).'</a></td>
	<td>'.datatable::planet($row['planetenID'], $row['planetenName'], $t).'</td>
	<td>'.datatable::inhaber($row['planeten_playerID'], $row['playerName'], $row['playerUmod'], $row['playerRasse']).'</td>
	<td>'.datatable::allianz($row['player_allianzenID'], $row['allianzenTag']).'</td>
	<td>'.$row['planetenGroesse'].'</td>
	<td>'.datatable::typ($row['planetenTyp']).'</td>
	<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet']).'</td>
	<td>'.($row['planetenUpdateOverview'] ? ressmenge2($row['planetenRMErz']) : '?').'</td>
	<td>'.($row['planetenUpdateOverview'] ? ressmenge2($row['planetenRMMetall']) : '?').'</td>
	<td>'.($row['planetenUpdateOverview'] ? ressmenge2($row['planetenRMWolfram']) : '?').'</td>
	<td>'.($row['planetenUpdateOverview'] ? ressmenge2($row['planetenRMKristall']) : '?').'</td>
	<td>'.($row['planetenUpdateOverview'] ? ressmenge2($row['planetenRMFluor']) : '?').'</td>
	<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
	<td>'.datatable::kategorie($row['planetenKategorie']).'</td>
	<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>';
		if($user->rechte['routen']) {
			$content .= '<td><input type="checkbox" name="'.$row['planetenID'].'" /></td>';
		}
		$content .= '
	</tr>';
		
		$ids[] = $row['planetenID'];
	}
	
	$content .= '
	</table>';
	
	// hidden-Feld für die Suchnavigation
	$content .= '
		<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />';
	
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
	<div class="center" style="font-weight:bold">Keine Ressplanis gefunden, die den Kriterien entsprechen!</div>
	<br />';
}

$content .= '
</div>';

// scrollen
if(isset($_GET['s']) OR isset($_GET['sort'])) {
	$tmpl->script .= 'if($(\'#contentc .content:visible .ress_allyhead\').length){$(\'html,body\').scrollTop($(\'#contentc .content:visible .ress_allyhead\').offset().top-10);}';
}

// Logfile-Eintrag
if($config['logging'] >= 3 AND !isset($_GET['s']) AND !isset($_GET['sort'])) {
	insertlog(5, 'zeigt die Liste der verbündeten Ressplaneten an');
}



?>