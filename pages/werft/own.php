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


$content =& $csw->data['own']['content'];

if(!isset($_GET['update'])) {
	$content = '
<div class="hl2">Eigene Werften</div>
<div class="icontent">
<div class="werften_own">';
}


$bedarf_list = array();


// eigene Werften abfragen
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
		systeme_galaxienID
	FROM
		".PREFIX."planeten
		LEFT JOIN ".PREFIX."systeme
			ON systemeID = planeten_systemeID
	WHERE
		planeten_playerID = ".$user->id."
		AND planetenWerft = 1
	ORDER BY
		planetenID ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$content .= '
	<table class="data searchtbl werfttbl thighlight" style="width:100%;margin:auto">
	<tr>
	<th>G</th>
	<th>System</th>
	<th>ID</th>
	<th>Name</th>
	<th>Gr&ouml;&szlig;e</th>
	<th>&nbsp;</th>
	<th>Scan</th>
	<th>Bedarf</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	</tr>';

// Planeten gefunden
if(mysql_num_rows($query)) {
	
	$heute = strtotime('today');
	
	while($row = mysql_fetch_assoc($query)) {
		$content .= '
	<tr class="werft'.$row['planetenID'].'">
	<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
	<td>'.datatable::system($row['planeten_systemeID']).'</td>
		<td>'.datatable::planet($row['planetenID']).'</a></td>
		<td>'.datatable::planet($row['planetenID'], $row['planetenName']).'</td>
	<td>'.$row['planetenGroesse'].'</td>
	<td>'.datatable::typ($row['planetenTyp']).'</td>
	<td>'.datatable::scan($row['planetenUpdateOverview'], $config['scan_veraltet']).'</td>
	<td class="center">';
		// Bedarf
		// unbekannt
		if($row['planetenWerftBedarf'] == '') {
			$content .= '<a style="font-style:italic" class="link winlink contextmenu" data-link="index.php?p=werft&amp;sp=edit&amp;id='.$row['planetenID'].'">nicht eingetragen</a>';
		}
		else {
			// Bedarf ausrechnen
			$b = json_decode($row['planetenWerftBedarf'], true);
			
			foreach($b as $key=>$val) {
				$content .= $ress[$key].' '.ressmenge2($val, true).' &nbsp; ';
			}
			
			
			
			
			// zur Schnellauswahl hinzufügen
			$b2 = $b;
			foreach($b2 as $key=>$val) {
				$b2[$key] = ressmenge2($val);
			}
			
			$bedarf_list[implode('-', $b)] = implode(' - ', $b2);
		}
		
		$content .= '</td>
	<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
	<td>'.datatable::kategorie($row['planetenKategorie'], $row['planetenUpdateOverview'], $row).'</td>
	<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>
	<td class="buttons"><img title="Bedarf bearbeiten" data-link="index.php?p=werft&amp;sp=edit&amp;id='.$row['planetenID'].'" class="link winlink contextmenu hoverbutton" style="background-position:-1020px -91px" src="img/layout/leer.gif" /> <img title="entfernen" onclick="ajaxcall(\'index.php?p=werft&amp;sp=del&amp;id='.$row['planetenID'].'\', this.parentNode, false, false)" class="hoverbutton" style="background-position:-1040px -91px;cursor:pointer" src="img/layout/leer.gif" /></td>
	</tr>';
	}
	
}
// keine Werften eingetragen
else {
	$content .= '
	<tr class="empty">
	<td colspan="12" class="center" style="font-style:italic">keine eigenen Werften eingetragen</td>
	</tr>';
}

$content .= '
	</table>';

// Werften hinzufügen
$query = query("
	SELECT
		planetenID,
		planetenName
	FROM
		".PREFIX."planeten
	WHERE
		planeten_playerID = ".$user->id."
		AND planetenWerft = 0
	ORDER BY
		planetenName ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

if(mysql_num_rows($query)) {
	$content .= '
	<br />
	<form name="werft_add" action="#" onsubmit="return form_send(this, \'index.php?p=werft&amp;sp=add\', false)">
	<select name="id">';
	while($row = mysql_fetch_assoc($query)) {
		$content .= '
		<option value="'.$row['planetenID'].'">'.htmlspecialchars($row['planetenName'], ENT_COMPAT, 'UTF-8').' ('.$row['planetenID'].')</option>';
	}
	$content .= '
	</select> 
	<input type="submit" class="button" value="hinzuf&uuml;gen" />
	</form>';
}

// Bedarf-Schnellauswahlliste
$bedarf_select = '<option></option>';

foreach($bedarf_list as $value=>$label) {
	$bedarf_select .= '<option value="'.$value.'">'.$label.'</option>';
}


// reine Aktualisierung der eigenen Werften
if(isset($_GET['update'])) {
	$tmpl->content = $content;
	$tmpl->script = "$('.werft_bedarfselect').html('".$bedarf_select."');";
	$tmpl->output();
	die();
}


$content .= '
	</div>
	
	<br /><br />
	<div class="hl2">Bedarf aller eigener Werften &auml;ndern</div>
	<br />
		
	<form name="werft_editall" action="#" onsubmit="return form_send(this, \'index.php?p=werft&amp;sp=edit_all\', $(this).siblings(\'.ajax\'))">
	<br />
	<div class="center">
		<img src="img/layout/leer.gif" class="ress ress_form erz" /> 
		<input type="text" class="smalltext" name="erz" /> 
		<img src="img/layout/leer.gif" class="ress ress_form metall" /> 
		<input type="text" class="smalltext" name="metall" /> 
		<img src="img/layout/leer.gif" class="ress ress_form wolfram" /> 
		<input type="text" class="smalltext" name="wolfram" /> 
		<img src="img/layout/leer.gif" class="ress ress_form kristall" /> 
		<input type="text" class="smalltext" name="kristall" /> 
		<img src="img/layout/leer.gif" class="ress ress_form fluor" /> 
		<input type="text" class="smalltext" name="fluor" />
		&nbsp;
		<input type="submit" class="button" style="width:100px" value="speichern" />
		<br /><br />
	</div>
	</form>
	<div class="ajax center"></div>
	
	<br />
	Schnellauswahl: <select size="1" class="werft_bedarfselect" onchange="werftPage.setBedarf(this)">
		'.$bedarf_select.'
	</select>
</div>';

// Logfile-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'zeigt die Liste der eigenen Werften an');
}



?>