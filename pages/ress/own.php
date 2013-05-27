<?php
/**
 * pages/ress/own.php
 * eigene Ressplanis
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// eigene Ressplanis speichern
if($_GET['sp'] == 'own_send') {
	// Daten sichern
	$ids = array();
	
	foreach($_POST as $key=>$val) {
		$key = (int)$key;
		if($val) {
			$ids[] = $key;
		}
	}
	
	// Ressplanet bei allen eigenen Planeten entfernen
	query("
		UPDATE ".PREFIX."planeten
		SET
			planetenRessplani = 0
		WHERE
			planeten_playerID = ".$user->id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// markierte Planeten wieder eintragen
	query("
		UPDATE ".PREFIX."planeten
		SET
			planetenRessplani = 1
		WHERE
			planetenID IN(".implode(", ", $ids).")
			AND planeten_playerID = ".$user->id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Logfile-Eintrag
	if($config['logging'] >= 2) {
		insertlog(12, 'ändert die Markierung eigener Ressplaneten');
	}
	
	// Ausgabe
	$tmpl->content = '
	<br />
	Die Ressplaneten wurden gespeichert.';
	
	$tmpl->output();
}


// Inhalt für eigene Ressplanis
else if($_GET['sp'] == 'own') {
	// Tabellenklasse laden
	if(!class_exists('datatable')) {
		include './common/datatable.php';
	}
	
	
	$content =& $csw->data['own']['content'];
	
	$content = '
	<div class="hl2">Eigene Ressplanis</div>
	<div class="icontent">
	';
	
	// Sortierung
	$sort = array(
		'id'=>'planetenID ASC',
		'name'=>'planetenName ASC',
		'groesse'=>'planetenGroesse ASC',
		'scan'=>'planetenUpdate ASC',
		'prod'=>'planetenRPGesamt DESC',
		'vorrat'=>'planetenRMGesamt DESC'
	);
	
	if(!isset($_GET['sort']) OR !isset($sort[$_GET['sort']])) {
		$sort = $sort['id'];
	}
	else {
		$sort = $sort[$_GET['sort']];
	}
	
	$t = time();
	$ids = array();
	$sids = array();
	
	// eigene Ressplanis abfragen
	$query = query("
		SELECT
			planetenID,
			planeten_systemeID,
			planetenName,
			planetenGroesse,
			planetenTyp,
			planetenKategorie,
			planetenRPGesamt,
			planetenRMErz,
			planetenRMMetall,
			planetenRMWolfram,
			planetenRMKristall,
			planetenRMFluor,
			planetenRMGesamt,
			planetenUpdateOverview,
			planetenUpdate,
			planetenGebPlanet,
			planetenGebOrbit,
			planetenRessplani,
			planetenKommentar,
			
			systemeX,
			systemeZ,
			systeme_galaxienID
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
		WHERE
			planeten_playerID = ".$user->id."
		ORDER BY
			".$sort."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planeten eingetragen
	if(mysql_num_rows($query)) {
		$content .= '
		Hier kannst du einstellen, welche deiner Planeten als Ressplaneten markiert werden sollen.
		<br /><br /><br />
		
		<form name="ress_own" action="#" onsubmit="return form_send(this, \'index.php?p=ress&amp;sp=own_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
		<table class="data searchtbl" style="margin:auto">
		<tr>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=id">Gala</a></th>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=id">System</a></th>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=id">ID</a></th>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=name">Name</a></th>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=groesse">Gr&ouml;&szlig;e</a></th>
		<th>&nbsp;</th>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=scan">voller Scan</a></th>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=prod">Ressproduktion</a></th>
		<th><a class="link" data-link="index.php?p=ress&amp;sort=vorrat">Ressvorrat</a></th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th>Ressplani</th>
		</tr>';
		
		$heute = strtotime('today');
		
		while($row = mysql_fetch_assoc($query)) {
			$content .= '
		<tr'.($row['planetenRessplani'] ? ' class="trhighlight"' : '').'>
		<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
		<td>'.datatable::system($row['planeten_systemeID'], $t).'</td>
		<td>'.datatable::planet($row['planetenID'], false, $t).'</a></td>
		<td>'.datatable::planet($row['planetenID'], $row['planetenName'], $t).'</td>
		<td>'.$row['planetenGroesse'].'</td>
		<td>'.datatable::typ($row['planetenTyp']).'</td>
		<td>'.datatable::scan($row['planetenUpdate'], $config['scan_veraltet']).'</td>
		<td>'.($row['planetenUpdate'] ? ressmenge($row['planetenRPGesamt']) : '?').'</td>
		<td>'.($row['planetenUpdateOverview'] ? ressmenge($row['planetenRMGesamt']) : '?').'</td>
		<td>'.datatable::screenshot($row, $config['scan_veraltet']).'</td>
		<td>'.datatable::kategorie($row['planetenKategorie'], $row['planetenUpdateOverview'], $row).'</td>
		<td>'.datatable::kommentar($row['planetenKommentar'], $row['planetenID']).'</td>
		<td>
		<input type="checkbox" name="'.$row['planetenID'].'"'.($row['planetenRessplani'] ? ' checked="checked"' : '').' onclick="(this.checked ? $(this).parents(\'tr\').addClass(\'trhighlight\') : $(this).parents(\'tr\').removeClass(\'trhighlight\'))" />
		</td>
		</tr>';
			
			$ids[] = $row['planetenID'];
			
			if(!in_array($row['planeten_systemeID'], $sids)) {
				$sids[] = $row['planeten_systemeID'];
			}
		}
		
		$content .= '
		</table>
		<br />
		<div class="center">
			<input type="submit" class="button" value="Ressplanis speichern" />
		</div>
		</form>
		<div class="ajax center"></div>';
		
		// hidden-Feld für die Suchnavigation
		$content .= '
			<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />
			<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
	}
	// keine Planeten eingetragen
	else {
		$content .= '
		<br />
		<div class="center">Es sind noch keine Planeten von dir eingetragen!</div>
		<br />';
	}
	
	$content .= '
	</div>';
	
	// Logfile-Eintrag
	if($config['logging'] >= 3) {
		insertlog(5, 'zeigt die Liste der eigenen Ressplaneten an');
	}
}



?>