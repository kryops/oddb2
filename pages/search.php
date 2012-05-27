<?php
/**
 * pages/search.php
 * Planetensuche
 * benutzerdefinierte Suche
 * Systemsuche
 * Suchspalten als Standard speichern
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

/**
 * Umgebungsdaten
 */

// Planeten-Typen
$pltypen = 62;
$pltypnot = array(46,48);

// Planetentyp validieren
if(isset($_GET['t']) AND ((int)$_GET['t'] < 1 OR (int)$_GET['t'] > $pltypen OR in_array((int)$_GET['t'], $pltypnot))) {
	unset($_GET['t']);
}

// Suchspalten
$spalten = array(
	1=>'Galaxie',
	2=>'System',
	3=>'Plani-ID',
	4=>'Planetenname',
	5=>'Inhaber',
	6=>'Allianz-Tag',
	8=>'Gr&ouml;&szlig;e',
	9=>'Planeten-Typ',
	10=>'Entfernung',
	11=>'Gate-Entfernung',
	12=>'Datum des Oberfl&auml;chen-Scans',
	13=>'Miniaturansicht',
	14=>'Planeten-Kategorie',
	15=>'Kommentar',
	16=>'getoxxt',
	17=>'geraidet',
	18=>'K&auml;stchen &quot;zur Route hinzuf&uuml;gen&quot;',
	19=>'System-Scan',
	20=>'Datum des vollen Scans',
	21=>'Bev&ouml;lkerung',
	22=>'Gate am Planeten',
	23=>'Myrigate',
	24=>'Allianz-Name',
	25=>'Allianz-Status',
	26=>'als Ressplanet eingetragen',
	27=>'als Bunker eingetragen',
	28=>'als Werft eingetragen',
	29=>'Forschungspunkte',
	30=>'Industriepunkte',
	31=>'Erz- und Metall-Wert',
	33=>'Wolfram-Wert',
	34=>'Kristall-Wert',
	35=>'Fluor-Wert',
	36=>'Erz-Produktion',
	37=>'Metall-Produktion',
	38=>'Wolfram-Produktion',
	39=>'Kristall-Produktion',
	40=>'Fluor-Produktion',
	41=>'Erz-Vorrat',
	42=>'Metall-Vorrat',
	43=>'Wolfram-Vorrat',
	44=>'Kristall-Vorrat',
	45=>'Fluor-Vorrat',
	46=>'gesamte Ressproduktion',
	47=>'gesamter Ressvorrat',
	48=>'Planetenanzahl des Spielers',
	49=>'Imperiumspunkte',
	50=>'letzte registrierte Aktivit&auml;t',
	51=>'Allianzen im System',
	52=>'Natives',
	53=>'Punkte'
);

$spalten2 = $spalten;
// Suchspalten über das Formular geschickt
if(isset($_GET['spalten'])) {
	//$count = count($spalten);
	$max = array_keys($spalten);
	sort($max);
	$max = array_pop($max);
	$sp = explode('-', $_GET['spalten']);
	foreach($sp as $key=>$val) {
		$val = (int)$val;
		if($val < 1 OR $val > $max) {
			unset($sp[$key]);
		}
		else $sp[$key] = $val;
	}
	// Spalten ungültig
	if(!count($sp)) {
		$sp = explode('-', $user->settings['suchspalten']);
	}
}
else $sp = explode('-', $user->settings['suchspalten']);

$sp2 = array_flip($sp);

// Sortier-Felder
$sorto = array(
	1=>'planetenID',
	2=>'playerName',
	3=>'player_allianzenID',
	4=>'planetenGroesse',
	5=>'planetenBevoelkerung',
	6=>'planetenRPErz',
	7=>'planetenRPMetall',
	8=>'planetenRPWolfram',
	9=>'planetenRPKristall',
	10=>'planetenRPFluor',
	11=>'planetenRMErz',
	12=>'planetenRMMetall',
	13=>'planetenRMWolfram',
	14=>'planetenRMKristall',
	15=>'planetenRMFluor',
	16=>'planetenForschung',
	17=>'planetenIndustrie',
	18=>'planetenRWErz',
	20=>'planetenRWWolfram',
	21=>'planetenRWKristall',
	22=>'planetenRWFluor',
	23=>'planetenName',
	24=>'planetenRPGesamt',
	25=>'planetenRMGesamt',
	26=>'planetenGateEntf',
	27=>'planetenNatives',
	28=>'planetenTyp',
	29=>'planetenUpdateOverview',
	30=>'planetenGetoxxt',
	31=>'planetenGeraidet',
	32=>'statusStatus',
	33=>'playerPlaneten',
	34=>'playerImppunkte',
	35=>'playerActivity',
	36=>'systemeUpdate',
	37=>imppunkte_mysql()
);

$sortol = array(
	1=>'ID',
	23=>'Name',
	2=>'Inhaber',
	3=>'Allianz',
	4=>'Gr&ouml;&szlig;e',
	5=>'Bev&ouml;lkerung',
	26=>'Gate-Entfernung',
	16=>'Forschung',
	17=>'Industrie',
	18=>'Erz- und Metallwert',
	20=>'Wolframwert',
	21=>'Kristallwert',
	22=>'Fluorwert',
	24=>'gesamte Ressproduktion',
	6=>'Erzproduktion',
	7=>'Metallproduktion',
	8=>'Wolframproduktion',
	9=>'Kristallproduktion',
	10=>'Fluorproduktion',
	25=>'gesamter Ressvorrat',
	11=>'Erzvorrat',
	12=>'Metallvorrat',
	13=>'Wolframvorrat',
	14=>'Kristallvorrat',
	15=>'Fluorvorrat',
	29=>'Oberfl&auml;chen-Scan',
	36=>'System-Scan',
	27=>'Natives',
	37=>'Punkte'
);

// Sortier-IDs auf Spaltennummer mappen
$spalten_sort = array(
	1=>1,
	2=>1,
	3=>1,
	4=>23,
	5=>2,
	6=>3,
	8=>4,
	9=>28,
	11=>26,
	12=>29,
	16=>30,
	17=>31,
	19=>36,
	21=>5,
	24=>2,
	25=>32,
	29=>16,
	30=>17,
	31=>18,
	33=>20,
	34=>21,
	35=>22,
	36=>6,
	37=>7,
	38=>8,
	39=>9,
	40=>10,
	41=>11,
	42=>12,
	43=>13,
	44=>14,
	45=>15,
	46=>24,
	47=>25,
	48=>33,
	49=>34,
	50=>35,
	52=>27,
	53=>37
);

/**
 * Struktur
 */


// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	$_GET['sp'] = 'planet';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Suchen';

// benutzerdefinierter Titel
if(isset($_GET['title'])) {
	$tmpl->name = htmlspecialchars($_GET['title'], ENT_COMPAT, 'UTF-8');
}

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'planet'=>true,
	'custom'=>true,
	'system'=>true,
	'spalten'=>true,
	'route'=>true,
	'route_send'=>true
);


// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

// Suchspalten-Einstellung speichern (AJAX)
else if($_GET['sp'] == 'spalten') {
	// keine Berechtigung
	if(!$user->rechte['search']) $tmpl->error = 'Du hast keine Berechtigung!';
	// Daten unvollständig
	else if(!isset($_GET['spalten'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Spalten ausgewählt
	else if($_GET['spalten'] == "") {
		$tmpl->error = 'Keine Spalten ausgewählt!';
	}
	// Berechtigung
	else {
		// Titel
		$tmpl->name = 'Suchspalten-Einstellung';
		
		// Daten sichern
		//$count = count($spalten);
		$max = array_keys($spalten);
		sort($max);
		$max = array_pop($max);
		$sp = explode('-', $_GET['spalten']);
		foreach($sp as $key=>$val) {
			$val = (int)$val;
			if($val < 1 OR $val > $max) {
				unset($sp[$key]);
			}
			else $sp[$key] = $val;
		}
		// Daten ungültig
		if(!count($sp)) {
			$tmpl->error = 'Daten ungültig!';
		}
		// alles ok
		else {
			$sp = implode('-', $sp);
			
			// in den Einstellungen aktualisieren
			$user->settings['suchspalten'] = $sp;
			
			query("
				UPDATE
					".PREFIX."user
				SET
					userSettings = '".escape(serialize($user->settings))."'
				WHERE
					user_playerID = ".$user->id."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Cache löschen
			$cache->remove('user'.$user->id);
			
			// Ausgabe
			$tmpl->script = '
$(\'.spaltenbutton\').val(\'als Standard gespeichert\');
			';
		}
	}
	// Ausgabe
	$tmpl->output();
}

// Suchergebnisse zu Route hinzufügen
else if($_GET['sp'] == 'route') {
	// keine Berechtigung
	if(!$user->rechte['routen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		$select = route::getselect(0,1);
		
		if($select != '') {
			$tmpl->content .= '
Suchergebnisse hinzuf&uuml;gen zu <select name="route" size="1">'.$select.'</select> <img src="img/layout/leer.gif" class="icon arrowbutton hoverbutton" style="background-position:-1060px -91px" title="hinzuf&uuml;gen" onclick="ajaxcall((this.parentNode.parentNode.parentNode.typ.value == 1 ? \'index.php?p=search&amp;sp=route_send&amp;ajax\' : addajax(\'index.php?\'+this.parentNode.parentNode.parentNode.querystring.value+\'&hide&add2route\')), this.parentNode, $(this.parentNode.parentNode.parentNode).serialize(), true)" />';
		}
		else {
			$tmpl->content = 'Du hast keine Routen im Bearbeitungsmodus!';
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// markierte Suchergebnisse zu Route hinzufügen -> abschicken
else if($_GET['sp'] == 'route_send') {
	// keine Berechtigung
	if(!$user->rechte['routen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Daten unvollständig
	else if(!isset($_POST['route'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// Daten sichern
		$_POST['route'] = (int)$_POST['route'];
		
		// Route laden
		$route = new route;
		if(($error = $route->load($_POST['route'])) !== true) {
			$tmpl->error = $error;
		}
		// keine Berechtigung
		else if(!$route->rechte_edit()) {
			$tmpl->error = 'Du hast keine Berechtigung, die Route zu bearbeiten!';
		}
		else {
			// IDs auslesen
			$ids = array();
			foreach($_POST as $key=>$val) {
				if(is_numeric($key)) {
					$ids[] = (int)$key;
				}
			}
			
			if(count($ids)) {
				// Bedingungen aufstellen
				$conds = array(
					"planetenID IN(".implode(", ", $ids).")"
				);
				
				if($route->gala) {
					$conds[] = "systeme_galaxienID = ".$route->gala;
				}
				
				$query = query("
					SELECT
						planetenID
					FROM
						".PREFIX."planeten
						LEFT JOIN ".PREFIX."systeme
							ON systemeID = planeten_systemeID
					WHERE
						".implode(" AND ", $conds)."
					ORDER BY
						planetenID
					LIMIT 100
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					$route->add($row['planetenID'], false);
				}
				
				// speichern
				$route->save();
			}
					
			$tmpl->content = 'Ergebnisse hinzugef&uuml;gt. <a onclick="ajaxcall(\'index.php?p=search&amp;sp=route&amp;ajax\', this.parentNode, false, true)" class="hint">[zu weiterer Route / Liste hinzuf&uuml;gen]</a>';
			$tmpl->script = 'if($(\'.route'.$route->id.'\').length > 0) { ajaxcall("index.php?p=route&sp=view&id='.$route->id.'&update", false, false, false);}';
		}
		
		// Ausgabe
		$tmpl->output();
		die();
	}
	
	// Ausgabe
	$tmpl->output();
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Planeten-Suche
	if($user->rechte['search']) {
		$csw->data['planet'] = array(
			'link'=>'index.php?p=search&sp=planet',
			'bg'=>'background-image:url(img/layout/csw_search.png);background-position:0px 0px',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
		
		$content =& $csw->data['planet']['content'];
		
		
		// Gebäude-Filter aktiv
		if(isset($_GET['geb'])) {
			$searchgeb = explode('-', $_GET['geb']);
			// validieren
			foreach($searchgeb as $key=>$val) {
				if(!isset($gebaeude[$val]) OR $val <= 0) {
					unset($searchgeb[$key]);
				}
			}
		}
		else {
			$searchgeb = array();
		}
		
		
		// Suchformular nur im normalen Modus anzeigen
		if(!isset($_GET['hide'])) {
			$content = '
<form action="#" onsubmit="return form_sendget(this, \'index.php?p=search&amp;s=1\')" class="searchform">
	<table>
	<tr>
		<td style="width:80px;font-weight:bold">
			System
		</td>
		<td style="vertical-align:top">
			Galaxie <input type="text" class="smalltext" style="width:40px" name="g" value="'.(isset($_GET['g']) ? htmlspecialchars($_GET['g'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Sektor 
			<select name="sek" size="1">
				<option value="">alle</option>
				<option value="1"'.((isset($_GET['sek']) AND $_GET['sek'] == 1) ? ' selected="selected"' : '').'>rot</option>
				<option value="2"'.((isset($_GET['sek']) AND $_GET['sek'] == 2) ? ' selected="selected"' : '').'>gr&uuml;n</option>
				<option value="3"'.((isset($_GET['sek']) AND $_GET['sek'] == 3) ? ' selected="selected"' : '').'>blau</option>
				<option value="4"'.((isset($_GET['sek']) AND $_GET['sek'] == 4) ? ' selected="selected"' : '').'>gelb</option>
			</select> &nbsp; &nbsp;
			System-ID <input type="text" class="smalltext" name="sid" value="'.(isset($_GET['sid']) ? htmlspecialchars($_GET['sid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			System-Name <input type="text" class="text" style="width:80px" name="sn" value="'.(isset($_GET['sn']) ? htmlspecialchars($_GET['sn'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			<span style="white-space:nowrap">Scan 
			<select name="ssc" size="1" onchange="if(this.value>=3){$(this).siblings(\'.ssct\').show().select();}else{$(this).siblings(\'.ssct\').hide();}">
				<option value="">egal</option>
				<option value="1"'.((isset($_GET['ssc']) AND $_GET['ssc'] == 1) ? ' selected="selected"' : '').'>nicht gescannt</option>
				<option value="2"'.((isset($_GET['ssc']) AND $_GET['ssc'] == 2) ? ' selected="selected"' : '').'>irgendwann</option>
				<option value="3"'.((isset($_GET['ssc']) AND $_GET['ssc'] == 3) ? ' selected="selected"' : '').'>neuer als (Tage)</option>
				<option value="4"'.((isset($_GET['ssc']) AND $_GET['ssc'] == 4) ? ' selected="selected"' : '').'>&auml;lter als (Tage)</option>
			</select> 
			<input type="text" class="smalltext ssct" name="ssct" value="'.(isset($_GET['ssct']) ? htmlspecialchars($_GET['ssct'], ENT_COMPAT, 'UTF-8') : '').'" style="'.((!isset($_GET['ssc']) OR $_GET['ssc'] < 3) ? 'display:none;' : '').'width:40px" /></span>
		</td>
	</tr>
	</table>
	<hr />
	<table>
	<tr>
		<td style="width:80px;font-weight:bold">
			Planet
		</td>
		<td style="vertical-align:top">
			Planeten-ID <input type="text" class="smalltext" name="pid" value="'.(isset($_GET['pid']) ? htmlspecialchars($_GET['pid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Planeten-Name <input type="text" class="text" name="pn" value="'.(isset($_GET['pn']) ? htmlspecialchars($_GET['pn'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			<input type="checkbox" name="pon"'.(isset($_GET['pon']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="pon">Original-Name</span> &nbsp; &nbsp;
			Gr&ouml;&szlig;e 
			<select name="gr_" size="1">
				<option value="">=</option>
				<option value="1"'.((isset($_GET['gr_']) AND $_GET['gr_'] == 1) ? ' selected="selected"' : '').'>&gt;</option>
				<option value="2"'.((isset($_GET['gr_']) AND $_GET['gr_'] == 2) ? ' selected="selected"' : '').'>&lt;</option>
			</select> 
			<input type="text" class="smalltext" name="gr" value="'.(isset($_GET['gr']) ? htmlspecialchars($_GET['gr'], ENT_COMPAT, 'UTF-8') : '').'" />
			<br />
			<span style="cursor:pointer" onclick="$(this).siblings(\'.searchpltyplist\').slideToggle(250)">Typ</span> &nbsp;
			<input type="hidden" name="t" value="'.(isset($_GET['t']) ? h($_GET['t']) : '').'" />
			<span class="searchpltyp" onclick="$(this).siblings(\'.searchpltyplist\').slideToggle(250)">'.(isset($_GET['t']) ? '<img src="img/planeten/'.(int)$_GET['t'].'.jpg" alt="" />' : '<i>alle</i>').'</span> &nbsp; &nbsp;
			Scan 
			<select name="sc" size="1" onchange="if(this.value>=3){$(this).siblings(\'.sct\').show().select();}else{$(this).siblings(\'.sct\').hide();}">
				<option value="">egal</option>
				<option value="1"'.((isset($_GET['sc']) AND $_GET['sc'] == 1) ? ' selected="selected"' : '').'>nicht gescannt</option>
				<option value="2"'.((isset($_GET['sc']) AND $_GET['sc'] == 2) ? ' selected="selected"' : '').'>irgendwann</option>
				<option value="3"'.((isset($_GET['sc']) AND $_GET['sc'] == 3) ? ' selected="selected"' : '').'>neuer als (Tage)</option>
				<option value="4"'.((isset($_GET['sc']) AND $_GET['sc'] == 4) ? ' selected="selected"' : '').'>&auml;lter als (Tage)</option>
			</select> 
			<input type="text" class="smalltext sct" name="sct" value="'.(isset($_GET['sct']) ? htmlspecialchars($_GET['sct'], ENT_COMPAT, 'UTF-8') : '').'" style="'.((!isset($_GET['sc']) OR $_GET['sc'] < 3) ? 'display:none;' : '').'width:40px" /> &nbsp; &nbsp;
			<input type="checkbox" name="usc"'.(isset($_GET['usc']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="usc">unscannbar</span> &nbsp; &nbsp;
			kategorisiert 
			<select name="k">
				<option value=""></option>
				<option value="0"'.((isset($_GET['k']) AND $_GET['k'] == 0) ? ' selected="selected"' : '').'>nicht kategorisiert</option>
				<option value="13"'.((isset($_GET['k']) AND $_GET['k'] == 13) ? ' selected="selected"' : '').'>Werft</option>
				<option value="15"'.((isset($_GET['k']) AND $_GET['k'] == 15) ? ' selected="selected"' : '').'>- Ressplanis und Werften -</option>
				<option value="14"'.((isset($_GET['k']) AND $_GET['k'] == 14) ? ' selected="selected"' : '').'>- alle Ressplaneten -</option>
				<option value="1"'.((isset($_GET['k']) AND $_GET['k'] == 1) ? ' selected="selected"' : '').'>Erz</option>
				<option value="2"'.((isset($_GET['k']) AND $_GET['k'] == 2) ? ' selected="selected"' : '').'>Metall</option>
				<option value="3"'.((isset($_GET['k']) AND $_GET['k'] == 3) ? ' selected="selected"' : '').'>Wolfram</option>
				<option value="4"'.((isset($_GET['k']) AND $_GET['k'] == 4) ? ' selected="selected"' : '').'>Kristall</option>
				<option value="5"'.((isset($_GET['k']) AND $_GET['k'] == 5) ? ' selected="selected"' : '').'>Fluor</option>
				<option value="12"'.((isset($_GET['k']) AND $_GET['k'] == 12) ? ' selected="selected"' : '').'>Umsatzfabriken</option>
				<option value="16"'.((isset($_GET['k']) AND $_GET['k'] == 16) ? ' selected="selected"' : '').'>- alle  Forschungsplaneten -</option>
				<option value="6"'.((isset($_GET['k']) AND $_GET['k'] == 6) ? ' selected="selected"' : '').'>Forschungseinrichtungen</option>
				<option value="7"'.((isset($_GET['k']) AND $_GET['k'] == 7) ? ' selected="selected"' : '').'>UNI-Labore</option>
				<option value="8"'.((isset($_GET['k']) AND $_GET['k'] == 8) ? ' selected="selected"' : '').'>Forschungszentren</option>
				<option value="9"'.((isset($_GET['k']) AND $_GET['k'] == 9) ? ' selected="selected"' : '').'>Myriforschung</option>
				<option value="10"'.((isset($_GET['k']) AND $_GET['k'] == 10) ? ' selected="selected"' : '').'>orbitale Forschung</option>
				<option value="11"'.((isset($_GET['k']) AND $_GET['k'] == 11) ? ' selected="selected"' : '').'>Gedankenkonzentratoren</option>
			</select>
			<br />
			<div class="searchpltyplist fcbox" style="display:none">';
			
			// Planetentypen ausgeben
			for($i=1; $i <= 62; $i++) {
				if(!in_array($i, $pltypnot)) {
					$content .= '<img src="img/planeten/'.$i.'.jpg" alt="" /> ';
				}
			}
			
			$content .= ' <a onclick="$(this).parents(\'form\').find(\'input[name=t]\').val(\'\').siblings(\'.searchpltyp\').html(\'&lt;i&gt;alle&lt;/i&gt;\');$(this.parentNode).slideUp(250);" style="font-style:italic"> [alle]</a>
			</div>
			
			<span style="cursor:pointer" onclick="$(this).siblings(\'.searchgeblist\').slideToggle(250)">Geb&auml;ude-Filter</span> &nbsp;
			<input type="hidden" name="geb" value="'.(isset($_GET['geb']) ? h($_GET['geb']) : '').'" />
			<span class="searchgeb" onclick="$(this).siblings(\'.searchgeblist\').slideToggle(250)">';
			
			if(!count($searchgeb)) {
				$content .= '<i>alle</i>';
			}
			else {
				// ausgewählte Gebäude anzeigen
				foreach($searchgeb as $geb) {
					$content .= '<img src="img/gebaeude/'.$gebaeude[$geb].'" alt="" /> ';
				}
			}
			
			$content .= '</span> &nbsp; &nbsp;
			<input type="checkbox" name="mg"'.(isset($_GET['mg']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="mg">Myrigate</span> &nbsp; &nbsp;';
			
			$content .= '
			Orbiter 
			<select name="o" size="1">
				<option value="">egal</option>
				<option value="1"'.((isset($_GET['o']) AND $_GET['o'] == 1) ? ' selected="selected"' : '').'>keine</option>
				<option value="6"'.((isset($_GET['o']) AND $_GET['o'] == 6) ? ' selected="selected"' : '').'>ja</option>
				<option value="2"'.((isset($_GET['o']) AND $_GET['o'] == 2) ? ' selected="selected"' : '').'>max. Stufe 1</option>
				<option value="3"'.((isset($_GET['o']) AND $_GET['o'] == 3) ? ' selected="selected"' : '').'>max. Stufe 2</option>
				<option value="4"'.((isset($_GET['o']) AND $_GET['o'] == 4) ? ' selected="selected"' : '').'>mind. Stufe 2</option>
				<option value="5"'.((isset($_GET['o']) AND $_GET['o'] == 5) ? ' selected="selected"' : '').'>mind. Stufe 3</option>
			</select>
			&nbsp; &nbsp;
			Natives 
			<select name="na_" size="1">
				<option value="">=</option>
				<option value="1"'.((isset($_GET['na_']) AND $_GET['na_'] == 1) ? ' selected="selected"' : '').'>&gt;</option>
				<option value="2"'.((isset($_GET['na_']) AND $_GET['na_'] == 2) ? ' selected="selected"' : '').'>&lt;</option>
			</select> 
			<input type="text" class="smalltext" name="na" value="'.(isset($_GET['na']) ? htmlspecialchars($_GET['na'], ENT_COMPAT, 'UTF-8') : '').'" />
			<br />
			
			<div class="searchgeblist fcbox" style="display:none">';
			
			foreach($gebaeude as $key=>$val) {
				if($key > 0) {
					$content .= '<img src="img/gebaeude/'.$val.'" alt="" data-id="'.$key.'"'.(in_array($key, $searchgeb) ? ' class="active"' : '').' /> ';
				}
			}
			
			$content .= ' <a onclick="$(this).parents(\'form\').find(\'input[name=geb]\').val(\'\').siblings(\'.searchgeb\').html(\'&lt;i&gt;alle&lt;/i&gt;\');$(this).siblings(\'.active\').removeClass(\'active\');" style="font-style:italic"> [alle]</a>
			</div>
			
			Bev&ouml;lkerung <input type="text" class="text" style="width:80px" name="bev" value="'.(isset($_GET['bev']) ? htmlspecialchars($_GET['bev'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Forschung <input type="text" class="text" style="width:80px" name="f" value="'.(isset($_GET['f']) ? htmlspecialchars($_GET['f'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Industrie <input type="text" class="text" style="width:80px" name="i" value="'.(isset($_GET['i']) ? htmlspecialchars($_GET['i'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Punkte <input type="text" class="text" style="width:80px" name="pu" value="'.(isset($_GET['pu']) ? htmlspecialchars($_GET['pu'], ENT_COMPAT, 'UTF-8') : '').'" />
			<br />
			eingetragen als &nbsp;
			<input type="checkbox" name="rpl"'.(isset($_GET['rpl']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="rpl">Ressplanet</span> &nbsp; &nbsp;
			<input type="checkbox" name="we"'.(isset($_GET['we']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="we">Werft</span> &nbsp; &nbsp;
			<input type="checkbox" name="bu"'.(isset($_GET['bu']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="bu">Bunker</span> 
			&nbsp; &nbsp; 
			Bergbau / Terraformer
			<select name="bb" size="1">
				<option value="">egal</option>
				<option value="1"'.((isset($_GET['bb']) AND $_GET['bb'] == 1) ? ' selected="selected"' : '').'>eins von beiden</option>
				<option value="2"'.((isset($_GET['bb']) AND $_GET['bb'] == 2) ? ' selected="selected"' : '').'>keins von beiden</option>
				<option value="3"'.((isset($_GET['bb']) AND $_GET['bb'] == 3) ? ' selected="selected"' : '').'>Bergbau</option>
				<option value="4"'.((isset($_GET['bb']) AND $_GET['bb'] == 4) ? ' selected="selected"' : '').'>Terraformer</option>
			</select>
			
			<br />
			<table class="searchress">
			<tr class="searchresstr">
				<td></td>
				<td><div class="ress erz"></div></td>
				<td><div class="ress metall"></div></td>
				<td><div class="ress wolfram"></div></td>
				<td><div class="ress kristall"></div></td>
				<td><div class="ress fluor"></div></td>
				<td rowspan="4" style="width:260px;padding-left:25px">
					Summe der Resswerte <input type="text" class="smalltext" name="rw" value="'.(isset($_GET['rw']) ? htmlspecialchars($_GET['rw'], ENT_COMPAT, 'UTF-8') : '').'" />
					<br />
					gesamte Ressproduktion <input type="text" class="smalltext" name="rp" value="'.(isset($_GET['rp']) ? htmlspecialchars($_GET['rp'], ENT_COMPAT, 'UTF-8') : '').'" />
					<br />
					gesamter Ressvorrat <input type="text" class="smalltext" name="rv" value="'.(isset($_GET['rv']) ? htmlspecialchars($_GET['rv'], ENT_COMPAT, 'UTF-8') : '').'" />
				</td>
			</tr>
			<tr>
				<td>Werte</td>
				<td><input type="text" class="smalltext" name="rwe" value="'.(isset($_GET['rwe']) ? htmlspecialchars($_GET['rwe'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td class="small hint center">(= Erz-Wert)</td>
				<td><input type="text" class="smalltext" name="rww" value="'.(isset($_GET['rww']) ? htmlspecialchars($_GET['rww'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rwk" value="'.(isset($_GET['rwk']) ? htmlspecialchars($_GET['rwk'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rwf" value="'.(isset($_GET['rwf']) ? htmlspecialchars($_GET['rwf'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			</tr>
			<tr>
				<td>Produktion</td>
				<td><input type="text" class="smalltext" name="rpe" value="'.(isset($_GET['rpe']) ? htmlspecialchars($_GET['rpe'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rpm" value="'.(isset($_GET['rpm']) ? htmlspecialchars($_GET['rpm'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rpw" value="'.(isset($_GET['rpw']) ? htmlspecialchars($_GET['rpw'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rpk" value="'.(isset($_GET['rpk']) ? htmlspecialchars($_GET['rpk'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rpf" value="'.(isset($_GET['rpf']) ? htmlspecialchars($_GET['rpf'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			</tr>
			<tr>
				<td>Vorrat</td>
				<td><input type="text" class="smalltext" name="rve" value="'.(isset($_GET['rve']) ? htmlspecialchars($_GET['rve'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rvm" value="'.(isset($_GET['rvm']) ? htmlspecialchars($_GET['rvm'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rvw" value="'.(isset($_GET['rvw']) ? htmlspecialchars($_GET['rvw'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rvk" value="'.(isset($_GET['rvk']) ? htmlspecialchars($_GET['rvk'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
				<td><input type="text" class="smalltext" name="rvf" value="'.(isset($_GET['rvf']) ? htmlspecialchars($_GET['rvf'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			</tr>
			</table>
			'.($user->rechte['toxxraid'] ? '
			geraidet (Tage) <input type="text" class="smalltext" name="rai" value="'.(isset($_GET['rai']) ? htmlspecialchars($_GET['rai'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			getoxxt <select name="tox" size="1">
				<option value="">egal</option>
				<option value="0"'.((isset($_GET['tox']) AND $_GET['tox'] == 0) ? ' selected="selected"' : '').'>nein</option>
				<option value="1"'.((isset($_GET['tox']) AND $_GET['tox'] == 1) ? ' selected="selected"' : '').'>ja</option>
			</select> &nbsp; &nbsp; ' : '').'
			Kommentar enth&auml;lt <input type="text" class="text" style="width:160px" name="ko" value="'.(isset($_GET['ko']) ? htmlspecialchars($_GET['ko'], ENT_COMPAT, 'UTF-8') : '').'" />
		</td>
	</tr>
	</table>
	<hr />
	<table>
	<tr>
		<td style="width:80px;font-weight:bold">
			Inhaber
		</td>
		<td style="vertical-align:top">
			User-ID <input type="text" class="text" style="width:80px" name="uid" value="'.(isset($_GET['uid']) ? htmlspecialchars($_GET['uid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Username <input type="text" class="text" name="un" value="'.(isset($_GET['un']) ? htmlspecialchars($_GET['un'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Rasse <select name="ra" size="1">
				<option value="">egal</option>
				<option value="0"'.((isset($_GET['ra']) AND $_GET['ra'] == 0) ? ' selected="selected"' : '').'>alle Altrassen</option>';
			foreach($rassen as $key=>$val) {
				$content .= '
				<option value="'.$key.'"'.((isset($_GET['ra']) AND $_GET['ra'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
			}
			$content .= '
				<option value="11"'.((isset($_GET['ra']) AND $_GET['ra'] == 11) ? ' selected="selected"' : '').'>Lux ohne NPC</option>
			</select> &nbsp; &nbsp;
			frei 
			<select name="fr" size="1">
				<option value="">egal</option>
				<option value="1"'.((isset($_GET['fr']) AND $_GET['fr'] == 1) ? ' selected="selected"' : '').'>ja</option>
				<option value="2"'.((isset($_GET['fr']) AND $_GET['fr'] == 2) ? ' selected="selected"' : '').'>nein</option>
			</select>
			&nbsp; &nbsp;
			<input type="checkbox" name="kbar" value="1"'.(isset($_GET['kbar']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="kbar">kolonisierbar</span>
			<br />
			Urlaubsmodus 
			<select name="umod" size="1">
				<option value="">egal</option>
				<option value="1"'.((isset($_GET['umod']) AND $_GET['umod'] == 1) ? ' selected="selected"' : '').'>ja</option>
				<option value="2"'.((isset($_GET['umod']) AND $_GET['umod'] == 2) ? ' selected="selected"' : '').'>nein</option>
			</select> &nbsp; &nbsp;
			Planeten
			<select name="pl_" size="1">
				<option value="">h&ouml;chstens</option>
				<option value="1"'.((isset($_GET['pl_']) AND $_GET['pl_'] == 1) ? ' selected="selected"' : '').'>mindestens</option>
			</select> 
			<input type="text" class="smalltext" name="pl" value="'.(isset($_GET['pl']) ? htmlspecialchars($_GET['pl'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			inaktiv seit mindestens
			&nbsp;<input type="text" class="smalltext" name="ina" value="'.(isset($_GET['ina']) ? htmlspecialchars($_GET['ina'], ENT_COMPAT, 'UTF-8') : '').'" />&nbsp;
			Tagen
			<br />
			Allianz-ID <input type="text" class="text" style="width:80px" name="aid" value="'.(isset($_GET['aid']) ? htmlspecialchars($_GET['aid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Allianz-Tag <input type="text" class="text" style="width:80px" name="at" value="'.(isset($_GET['at']) ? htmlspecialchars($_GET['at'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			Allianz-Name <input type="text" class="text" name="an" value="'.(isset($_GET['an']) ? htmlspecialchars($_GET['an'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			<span class="as_simple"'.(isset($_GET['as2']) ? ' style="display:none"' : '').'>
			Status
			<select name="as" size="1">
				<option value="">egal</option>
				<option value="-1"'.((isset($_GET['as']) AND $_GET['as'] == -1) ? ' selected="selected"' : '').'>- Freunde -</option>
				<option value="-2"'.((isset($_GET['as']) AND $_GET['as'] == -2) ? ' selected="selected"' : '').'>- Feinde -</option>';
			// Allianz-Status-Select erzeugen
			foreach($status as $key=>$val) {
				$content .= '
				<option value="'.$key.'"'.((isset($_GET['as']) AND $_GET['as'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
			}
			$content .= '
			</select>
			&nbsp; <a class="small2" style="font-style:italic" onclick="$(this.parentNode).siblings(\'.as_advanced\').show().find(\'input\').val(\'1\');$(this.parentNode).hide().find(\'select\').val(\'\');">(erweitert)</a>
			</span>
			<br />
			<div class="as_advanced" '.(!isset($_GET['as2']) ? ' style="display:none"' : '').'>
			Status &nbsp;
			<span class="small2">';
			// Allianz-Status-Checkboxen erzeugen
			foreach($status as $key=>$val) {
				$checked = true;
				if(isset($_GET['as2']) AND !isset($_GET['as2'][$key])) {
					$checked = false;
				}
				else if(isset($_GET['as'])) {
					if($_GET['as'] == -1) {
						if(!in_array($key, $status_freund)) $checked = false;
					}
					else if($_GET['as'] == -2) {
						if(!in_array($key, $status_feind))  $checked = false;
					}
					else if($key != $_GET['as']) {
						$checked = false;
					}
				}
				
				$content .= '
				<input type="checkbox" name="as2['.$key.']" value="'.(isset($_GET['as2']) ? '1' : '').'"'.($checked ? ' checked="checked"' : '').'> <span class="togglecheckbox" data-name="as2['.$key.']">'.$val.'</span> &nbsp;';
			}
			$content .= '
			&nbsp; <a class="small2" style="font-style:italic" onclick="$(this.parentNode.parentNode).siblings(\'.as_simple\').show();$(this.parentNode.parentNode).hide().find(\'input\').val(\'\');">(einfach)</a>
			</span>
			</div>
		</td>
	</tr>
	</table>
	<hr />
	<table>
	<tr>
		<td style="width:80px;font-weight:bold">
			History
		</td>
		<td style="vertical-align:top">
			der Planet hat einmal &nbsp;
			<select name="his_" size="1">
				<option value="">Username</option>
				<option value="1"'.((isset($_GET['his_']) AND $_GET['his_'] == 1) ? ' selected="selected"' : '').'>User-ID</option>
			</select> 
			<input type="text" class="text" name="his" value="'.(isset($_GET['his']) ? htmlspecialchars($_GET['his'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp;
			geh&ouml;rt
		</td>
	</tr>
	</table>
	<hr />
	<table>
	<tr>
		<td style="width:80px;font-weight:bold">
			Sortierung
		</td>
		<td style="vertical-align:top">
			<input type="radio" name="sortt" class="sortt1" value=""'.(!isset($_GET['sortt']) ? ' checked="checked"' : '').' /> 
			<select name="sort" size="1" onchange="$(this).siblings(\'.sortt1\').attr(\'checked\', true)">';
			// Sortierspalten erzeugen
			foreach($sortol as $key=>$val) {
				if($key == 1) $key = '';
				$content .= '
				<option value="'.$key.'"'.((isset($_GET['sort']) AND $_GET['sort'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
			}
			$content .= '
			</select> &nbsp;
			<select name="sorto" size="1">
				<option value="">aufsteigend</option>
				<option value="1"'.((isset($_GET['sorto']) AND $_GET['sorto'] == 1) ? ' selected="selected"' : '').'>absteigend</option>
			</select> &nbsp; &nbsp;
			2. Stufe 
			<select name="sort2" size="1" onchange="$(this).siblings(\'.sortt1\').attr(\'checked\', true)">';
			// Sortierspalten erzeugen (2. Stufe)
			foreach($sortol as $key=>$val) {
				if($key == 1) $key = '';
				$content .= '
				<option value="'.$key.'"'.((isset($_GET['sort2']) AND $_GET['sort2'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
			}
			$content .= '
			</select> &nbsp;
			<select name="sorto2" size="1">
				<option value="">aufsteigend</option>
				<option value="1"'.((isset($_GET['sorto2']) AND $_GET['sorto2'] == 1) ? ' selected="selected"' : '').'>absteigend</option>
			</select>
			<br />
			<input type="radio" name="sortt" class="sortt2" value="1"'.((isset($_GET['sortt']) AND $_GET['sortt'] == 1) ? ' checked="checked"' : '').' /> 
			Entfernung zu &nbsp;
			<input type="text" class="text tooltip" style="width:100px" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" name="entf" onfocus="$(this).siblings(\'.sortt2\').attr(\'checked\', true)" value="'.(isset($_GET['entf']) ? htmlspecialchars($_GET['entf'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp;
			und &nbsp;
			<input type="text" class="text tooltip" style="width:100px" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" name="entf2" onfocus="$(this).siblings(\'.sortt2\').attr(\'checked\', true)" value="'.(isset($_GET['entf2']) ? htmlspecialchars($_GET['entf2'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp;
			<select name="sorto3" size="1">
				<option value="">aufsteigend</option>
				<option value="1"'.((isset($_GET['sorto3']) AND $_GET['sorto3'] == 1) ? ' selected="selected"' : '').'>absteigend</option>
			</select>
			&nbsp;
			filtern:
			<input type="text" class="smalltext" style="width:20px" name="ef1" value="'.(isset($_GET['ef1']) ? htmlspecialchars($_GET['ef1'], ENT_COMPAT, 'UTF-8') : '').'" />:<input type="text" class="smalltext" style="width:20px" name="ef2" value="'.(isset($_GET['ef2']) ? htmlspecialchars($_GET['ef2'], ENT_COMPAT, 'UTF-8') : '').'" /> 
			&#0177;
			<input type="text" class="smalltext" style="width:20px" name="ef3" value="'.(isset($_GET['ef3']) ? htmlspecialchars($_GET['ef3'], ENT_COMPAT, 'UTF-8') : '').'" />
			min
		</td>
	</tr>
	</table>
	<hr />
	<table style="width:100%">
	<tr>
		<td style="width:150px;font-weight:bold">
			zus&auml;tzliche Optionen
		</td>
		<td style="vertical-align:top">
			<div style="float:right;width:200px">
				<a class="link" data-link="index.php?p=search" style="text-align:right;font-style:italic">Suchformular zur&uuml;cksetzen</a>
			</div>
			Antrieb <input type="text" class="smalltext" name="antr" value="'.(isset($_GET['antr']) ? htmlspecialchars($_GET['antr'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
			<a href="javascript:void(0)" onclick="$(this).parents(\'table\').siblings(\'.searchspalten\').slideToggle(400)">anzuzeigende Spalten einstellen</a>
			<input type="hidden" name="spalten" value="'.(isset($_GET['spalten']) ? htmlspecialchars($_GET['spalten'], ENT_COMPAT, 'UTF-8') : '').'" />
		</td>
	</tr>
	</table>
	<div class="searchspalten">
	<b>anzuzeigende Spalten</b>
	<br />
	<span class="small hint">(per Drag &amp; Drop Reihenfolge ver&auml;ndern)</span>
	<br />
	<input type="button" class="button spaltenbutton" value="als Standard speichern" onclick="ajaxcall(\'index.php?p=search&amp;sp=spalten&amp;spalten=\'+$(this).parents(\'form\').find(\'[name=spalten]\').val(), false, false, false)" />
	<br />
	<div class="sortable spalten">';
			
			// Suchspalten anzeigen
			// angekreuzte Suchspalten zuerst
			foreach($sp as $key) {
				if($key != '') {
					$content .= '
			<div class="filter_'.$key.'" style="opacity:1;filter:alpha(opacity=100)"><input type="checkbox" class="ssp'.$key.'" checked="checked" /> <span>'.$spalten[$key].'</span></div>';
					// aus den verbleibenden Spalten löschen
					if(isset($spalten2[$key])) {
						unset($spalten2[$key]);
					}
				}
			}
			// nicht angekreuzte Spalten
			foreach($spalten2 as $key=>$val) {
				$content .= '
		<div class="filter_'.$key.'" style="opacity:0.5;filter:alpha(opacity=50)"><input type="checkbox" class="ssp'.$key.'" /> <span>'.$val.'</span></div>';
			}
			$content .= '
	</div>
	<br /><br />
	</div>
	
	<br />
	<div class="center">
		<input type="submit" class="button" style="width:120px" value="Suche starten" />
	</div>
</form>';
		}
		
		// Suche gesartet
		if(isset($_GET['s'])) {
			
			// alle Suchergebnisse zu einer Route hinzufügen
			$route = false;
			if(isset($_GET['add2route'], $_POST['route'])) {
				// Daten sichern
				$_POST['route'] = (int)$_POST['route'];
				
				// Route laden
				$route = new route;
				if(($error = $route->load($_POST['route'])) !== true) {
					$tmpl->error = $error;
				}
				// keine Berechtigung
				else if(!$route->rechte_edit()) {
					$tmpl->error = 'Du hast keine Berechtigung, die Route zu bearbeiten!';
				}
				
				
				// Fehler ausgeben
				if($tmpl->error != '') {
					$tmpl->output();
					die();
				}
			}
			
			// Ergebnisse auf der Karte hervorheben
			$karte = false;
			if(isset($_GET['karte'])) {
				if(!$user->rechte['karte']) {
					$tmpl->error = 'Du hast keine Berechtigung!';
					$tmpl->output();
					die();
				}
				
				$karte = true;
				if(!isset($_GET['g']) AND (int)$_GET['karte']) {
					$_GET['g'] = (int)$_GET['karte'];
				}
			}
			
			
			// Antrieb ermitteln
			$antr = $user->settings['antrieb'];
			if(isset($_GET['antr']) AND (int)$_GET['antr'] > 0) {
				$antr = (int)$_GET['antr'];
			}
			
			// Labels für die Spalten erzeugen
			$splabels = array(
				1=>'G',
				2=>'Sys',
				3=>'ID',
				4=>'Name',
				5=>'Inhaber',
				6=>'Allianz',
				8=>'Gr&ouml;&szlig;e',
				9=>'&nbsp;',
				10=>'Entf <span class="small" style="font-weight:normal">(A'.$antr.')</span>',
				11=>'Gate <span class="small" style="font-weight:normal">(A'.$antr.')</span>',
				12=>'Scan',
				13=>'&nbsp;',
				14=>'&nbsp;',
				15=>'&nbsp;',
				16=>'Toxx bis',
				17=>'geraidet',
				18=>'<input type="checkbox" onclick="$(this).parents(\'table.searchtbl\').find(\'input\').attr(\'checked\',this.checked)" />',
				19=>'System-Scan',
				20=>'voller Scan',
				21=>'Bev&ouml;lkerung',
				22=>'Gate',
				23=>'Mgate',
				24=>'Allianz-Name',
				25=>'Status',
				26=>'Resspl',
				27=>'Bunker',
				28=>'Werft',
				29=>'Forschung',
				30=>'Industrie',
				31=>'<div class="ress erz"></div>',
				33=>'<div class="ress wolfram"></div>',
				34=>'<div class="ress kristall"></div>',
				35=>'<div class="ress fluor"></div>',
				36=>'<div class="ress erzprod"></div>',
				37=>'<div class="ress metallprod"></div>',
				38=>'<div class="ress wolframprod"></div>',
				39=>'<div class="ress kristallprod"></div>',
				40=>'<div class="ress fluorprod"></div>',
				41=>'<div class="ress erz"></div>',
				42=>'<div class="ress metall"></div>',
				43=>'<div class="ress wolfram"></div>',
				44=>'<div class="ress kristall"></div>',
				45=>'<div class="ress fluor"></div>',
				46=>'Ressproduktion',
				47=>'Ressvorrat',
				48=>'Planeten',
				49=>'Imp-Punkte',
				50=>'Aktivit&auml;t',
				51=>'Allys im System',
				52=>'Natives',
				53=>'Punkte'
			);
			
			// heutigen Timestamp ermitteln
			$heute = strtotime('today');
			
			// Bedingungen aufstellen
			$conds = array();
			
			// Einschränkungen und Sperrungen der Rechte
			if($user->protectedAllies) {
				$conds[] = '(player_allianzenID IS NULL OR player_allianzenID NOT IN ('.implode(', ', $user->protectedAllies).'))';
			}
			if($user->protectedGalas) {
				$conds[] = 'systeme_galaxienID NOT IN ('.implode(', ', $user->protectedGalas).')';
			}
			if(!$user->rechte['search_ally'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID IS NULL OR player_allianzenID != '.$user->allianz.')';
			}
			if(!$user->rechte['search_meta'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
			}
			if(!$user->rechte['search_register']) {
				$conds[] = '(statusStatus = '.$status_meta.' OR allianzenID IS NULL OR register_allianzenID IS NULL)';
			}
			
			// eingegebene Bedingungen
			
			// Galaxie
			if(isset($_GET['g'])) {
				$conds[] = 'systeme_galaxienID'.db_multiple($_GET['g']);
			}
			
			// Sektor
			if(isset($_GET['sek'])) {
				if($_GET['sek'] == 1) $conds[] = 'systemeX > 0 AND systemeZ > 0';
				else if($_GET['sek'] == 2) $conds[] = 'systemeX < 0 AND systemeZ > 0';
				else if($_GET['sek'] == 3) $conds[] = 'systemeX < 0 AND systemeZ < 0';
				else $conds[] = 'systemeX > 0 AND systemeZ < 0';
			}
			// System-ID
			if(isset($_GET['sid'])) {
				$conds[] = 'planeten_systemeID'.db_multiple($_GET['sid']);
			}
			// System-Name
			if(isset($_GET['sn'])) {
				$conds[] = "systemeName LIKE '".escape(escape(str_replace('*', '%', $_GET['sn'])))."'";
			}
			// System-Scan
			if(isset($_GET['ssc'])) {
				if(isset($_GET['ssct'])) {
					$_GET['ssct'] = (int)$_GET['ssct'];
				}
				else $_GET['ssct'] = 0;
				
				// nicht gescannt
				if($_GET['ssc'] == 1) {
					$conds[] = 'systemeUpdate = 0';
				}
				// irgendwann
				else if($_GET['ssc'] == 2) {
					$conds[] = 'systemeUpdate != 0';
				}
				// älter / neuer als
				else if($_GET['ssct'] > 0) {
					$conds[] = 'systemeUpdate '.(($_GET['ssc'] == 3) ? '>' : '<').' '.(time()-$_GET['ssct']*86400);
				}
			}
			
			// Planeten-ID
			if(isset($_GET['pid'])) {
				$conds[] = 'planetenID'.db_multiple($_GET['pid']);
			}
			// Planeten-Name
			if(isset($_GET['pn'])) {
				$conds[] = "planetenName LIKE '".escape(escape(str_replace('*', '%', $_GET['pn'])))."'";
			}
			// Original-Name
			if(isset($_GET['pon'])) {
				$conds[] = "planetenName = CONCAT('P',systeme_galaxienID,'_',planeten_systemeID,planetenPosition)";
			}
			// Größe
			if(isset($_GET['gr'])) {
				$val = '=';
				if(isset($_GET['gr_'])) {
					if($_GET['gr_'] == 1) $val = '>';
					else $val = '<';
				}
				$_GET['gr'] = (int)$_GET['gr'];
				$conds[] = 'planetenGroesse '.$val.' '.$_GET['gr'];
			}
			// Planeten-Typ
			if(isset($_GET['t'])) {
				$_GET['t'] = (int)$_GET['t'];
				$conds[] = 'planetenTyp = '.$_GET['t'];
			}
			// Planeten-Scan (Oberfläche)
			if(isset($_GET['sc'])) {
				if(isset($_GET['sct'])) {
					$_GET['sct'] = (int)$_GET['sct'];
				}
				else $_GET['sct'] = 0;
				
				// nicht gescannt
				if($_GET['sc'] == 1) {
					$conds[] = 'planetenUpdateOverview = 0';
				}
				// irgendwann
				else if($_GET['sc'] == 2) {
					$conds[] = 'planetenUpdateOverview != 0';
				}
				// älter / neuer als
				else if($_GET['sct'] > 0) {
					$conds[] = 'planetenUpdateOverview '.(($_GET['sc'] == 3) ? '>' : '<').' '.(time()-$_GET['sct']*86400);
				}
			}
			// unscannbar
			if(isset($_GET['usc'])) {
				$conds[] = 'planetenUnscannbar > planetenUpdateOverview';
			}
			// Kategorie
			if(isset($_GET['k'])) {
				$_GET['k'] = (int)$_GET['k'];
				// normale Kategorie
				if($_GET['k'] >= 0 AND $_GET['k'] <= 13) {
					$conds[] = 'planetenKategorie = '.$_GET['k'];
				}
				// Sammelkategorien
				// alle Ressplaneten
				else if($_GET['k'] == 14) {
					$conds[] = 'planetenKategorie IN(1,2,3,4,5,12)';
				}
				// Ressplaneten und Werften
				else if($_GET['k'] == 15) {
					$conds[] = 'planetenKategorie IN(1,2,3,4,5,12,13)';
				}
				// alle Forschungsplaneten
				else {
					$conds[] = 'planetenKategorie >= 6 AND planetenKategorie <= 11';
				}
			}
			// Gebäude
			if(count($searchgeb)) {
				foreach($searchgeb as $geb) {
					$conds[] = "(planetenGebPlanet LIKE '%".$geb."%' OR planetenGebOrbit LIKE '%".$geb."%')";
				}
			}
			// Myrigate
			if(isset($_GET['mg'])) {
				$conds[] = 'planetenMyrigate > 0';
				// Berechtigungs-Einschränkungen
				if(!$user->rechte['show_myrigates_ally'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
				}
				if(!$user->rechte['show_myrigates_meta'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
				}
				if(!$user->rechte['show_myrigates_register']) {
					$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
				}
			}
			
			// Orbiter
			if(isset($_GET['o'])) {
				if($_GET['o'] == 1) {
					$conds[] = 'planetenOrbiter = 0 AND planetenUpdateOverview > 0';
				}
				else if($_GET['o'] == 2) {
					$conds[] = 'planetenOrbiter <= 1 AND planetenUpdateOverview > 0';
				}
				else if($_GET['o'] == 3) {
					$conds[] = 'planetenOrbiter <= 2 AND planetenUpdateOverview > 0';
				}
				else if($_GET['o'] == 4) {
					$conds[] = 'planetenOrbiter >= 2';
				}
				else if($_GET['o'] == 5) {
					$conds[] = 'planetenOrbiter >= 3';
				}
				else {
					$conds[] = 'planetenOrbiter >= 1';
				}
			}
			// Natives
			if(isset($_GET['na'])) {
				$val = '=';
				if(isset($_GET['na_'])) {
					if($_GET['na_'] == 1) $val = '>';
					else $val = '<';
				}
				$_GET['na'] = (int)$_GET['na'];
				$conds[] = 'planetenNatives '.$val.' '.$_GET['na'];
				if($_GET['na']) {
					// nur freie Planeten -> Performance
					$conds[] = 'planeten_playerID = 0';
					// Planeten mit 0 Natives ausblenden
					$conds[] = 'planetenNatives > 0';
				}
			}
			// Bevölkerung
			if(isset($_GET['bev'])) {
				$_GET['bev'] = (int)$_GET['bev'];
				$conds[] = 'planetenBevoelkerung >= '.$_GET['bev'];
			}
			// Forschung
			if(isset($_GET['f'])) {
				$_GET['f'] = (int)$_GET['f'];
				$conds[] = 'planetenForschung >= '.$_GET['f'];
			}
			// Industrie
			if(isset($_GET['i'])) {
				$_GET['i'] = (int)$_GET['i'];
				$conds[] = 'planetenIndustrie >= '.$_GET['i'];
			}
			// Punkte
			if(isset($_GET['pu'])) {
				$_GET['pu'] = (int)$_GET['pu'];
				$conds[] = imppunkte_mysql().' >= '.$_GET['pu'];
			}
			// Ressplanet
			if(isset($_GET['rpl'])) {
				$conds[] = 'planetenRessplani = 1';
				// Berechtigungs-Einschränkungen
				if(!$user->rechte['ressplani_ally'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
				}
				if(!$user->rechte['ressplani_meta'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
				}
				if(!$user->rechte['ressplani_register']) {
					$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
				}
			}
			// Werft
			if(isset($_GET['we'])) {
				$conds[] = 'planetenWerft = 1';
				// Berechtigungs-Einschränkungen
				if(!$user->rechte['werft_ally'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
				}
				if(!$user->rechte['werft_meta'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
				}
				if(!$user->rechte['werft_register']) {
					$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
				}
			}
			// Bunker
			if(isset($_GET['bu'])) {
				$conds[] = 'planetenBunker = 1';
				// Berechtigungs-Einschränkungen
				if(!$user->rechte['bunker_ally'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
				}
				if(!$user->rechte['bunker_meta'] AND $user->allianz) {
					$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
				}
				if(!$user->rechte['bunker_register']) {
					$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
				}
			}
			// Bergbau
			if(isset($_GET['bb']) AND $user->rechte['fremdinvakolos']) {
				// BBS oder TF
				if($_GET['bb'] == 1) {
					$conds[] = "(schiffeBergbau IS NOT NULL OR schiffeTerraformer IS NOT NULL)";
				}
				// keins
				else if($_GET['bb'] == 2) {
					$conds[] = "schiffeBergbau IS NULL";
					$conds[] = "schiffeTerraformer IS NULL";
				}
				// BBS
				else if($_GET['bb'] == 3) {
					$conds[] = "schiffeBergbau IS NOT NULL";
				}
				// TF
				else {
					$conds[] = "schiffeTerraformer IS NOT NULL";
				}
			}
			// Summe aller Resswerte
			if(isset($_GET['rw'])) {
				$_GET['rw'] = (int)$_GET['rw'];
				$conds[] = 'planetenRWErz+planetenRWWolfram+planetenRWKristall+planetenRWFluor >= '.$_GET['rw'];
			}
			// gesamte Ressproduktion
			if(isset($_GET['rp'])) {
				$_GET['rp'] = (int)$_GET['rp'];
				$conds[] = 'planetenRPGesamt >= '.$_GET['rp'];
			}
			// gesamter Ressvorrat
			if(isset($_GET['rv'])) {
				$_GET['rv'] = (int)$_GET['rv'];
				$conds[] = 'planetenRMGesamt >= '.$_GET['rv'];
			}
			// Resswerte
			if(isset($_GET['rwe'])) {
				$_GET['rwe'] = (int)$_GET['rwe'];
				$conds[] = 'planetenRWErz >= '.$_GET['rwe'];
			}
			if(isset($_GET['rww'])) {
				$_GET['rww'] = (int)$_GET['rww'];
				$conds[] = 'planetenRWWolfram >= '.$_GET['rww'];
			}
			if(isset($_GET['rwk'])) {
				$_GET['rwk'] = (int)$_GET['rwk'];
				$conds[] = 'planetenRWKristall >= '.$_GET['rwk'];
			}
			if(isset($_GET['rwf'])) {
				$_GET['rwf'] = (int)$_GET['rwf'];
				$conds[] = 'planetenRWFluor >= '.$_GET['rwf'];
			}
			// Ressproduktion
			if(isset($_GET['rpe'])) {
				$_GET['rpe'] = (int)$_GET['rpe'];
				$conds[] = 'planetenRPErz >= '.$_GET['rpe'];
			}
			if(isset($_GET['rpm'])) {
				$_GET['rpm'] = (int)$_GET['rpm'];
				$conds[] = 'planetenRPMetall >= '.$_GET['rpm'];
			}
			if(isset($_GET['rpw'])) {
				$_GET['rpw'] = (int)$_GET['rpw'];
				$conds[] = 'planetenRPWolfram >= '.$_GET['rpw'];
			}
			if(isset($_GET['rpk'])) {
				$_GET['rpk'] = (int)$_GET['rpk'];
				$conds[] = 'planetenRPKristall >= '.$_GET['rpk'];
			}
			if(isset($_GET['rpf'])) {
				$_GET['rpf'] = (int)$_GET['rpf'];
				$conds[] = 'planetenRPFluor >= '.$_GET['rpf'];
			}
			// Ressvorrat
			if(isset($_GET['rve'])) {
				$_GET['rve'] = (int)$_GET['rve'];
				$conds[] = 'planetenRMErz >= '.$_GET['rve'];
			}
			if(isset($_GET['rvm'])) {
				$_GET['rvm'] = (int)$_GET['rvm'];
				$conds[] = 'planetenRMMetall >= '.$_GET['rvm'];
			}
			if(isset($_GET['rvw'])) {
				$_GET['rvw'] = (int)$_GET['rvw'];
				$conds[] = 'planetenRMWolfram >= '.$_GET['rvw'];
			}
			if(isset($_GET['rvk'])) {
				$_GET['rvk'] = (int)$_GET['rvk'];
				$conds[] = 'planetenRMKristall >= '.$_GET['rvk'];
			}
			if(isset($_GET['rvf'])) {
				$_GET['rvf'] = (int)$_GET['rvf'];
				$conds[] = 'planetenRMFluor >= '.$_GET['rvf'];
			}
			// geraidet
			if(isset($_GET['rai']) AND $user->rechte['toxxraid']) {
				$_GET['rai'] = (int)$_GET['rai'];
				$conds[] = 'planetenGeraidet < '.(time()-86400*$_GET['rai']);
			}
			// getoxxt
			if(isset($_GET['tox']) AND $user->rechte['toxxraid']) {
				if($_GET['tox']) {
					$conds[] = 'planetenGetoxxt > '.time();
				}
				else {
					$conds[] = 'planetenGetoxxt < '.time();
				}
			}
			// Kommentar
			if(isset($_GET['ko'])) {
				$conds[] = "planetenKommentar LIKE '%".escape(escape(str_replace('*', '%', $_GET['ko'])))."%'";
			}
			// User-ID
			if(isset($_GET['uid'])) {
				$conds[] = 'planeten_playerID'.db_multiple($_GET['uid']);
			}
			// User-Name
			if(isset($_GET['un'])) {
				$conds[] = "playerName LIKE '".escape(escape(str_replace('*', '%', $_GET['un'])))."'";
			}
			// Rasse
			if(isset($_GET['ra'])) {
				$_GET['ra'] = (int)$_GET['ra'];
				// alle Altrassen
				if($_GET['ra'] == 0) $conds[] = '((playerRasse != 10 AND planeten_playerID != 0) OR planeten_playerID = -3)';
				// Lux
				else if($_GET['ra'] == 10) $conds[] = '(playerRasse = '.$_GET['ra'].' OR planeten_playerID = -2)';
				// Lux ohne NPC
				else if($_GET['ra'] == 11) $conds[] = '((playerRasse = 10 AND planeten_playerID > 2) OR planeten_playerID = -2)';
				// bestimmte Altrasse
				else $conds[] = 'playerRasse = '.$_GET['ra'];
			}
			// frei
			if(isset($_GET['fr'])) {
				// frei
				if($_GET['fr'] == 1) $conds[] = 'planeten_playerID = 0';
				// nicht frei
				else $conds[] = '(planeten_playerID > 0 OR planeten_playerID < -1)';
			}
			// kolonisierbar
			if(isset($_GET['kbar'])) {
				$conds[] = 'planeten_playerID = 0';
				$conds[] = 'planetenNatives = 0';
				$conds[] = 'planetenGroesse > 3';
				
				// laufende Aktionen abfragen
				$q = query("
					SELECT
						invasionen_planetenID
					FROM
						".PREFIX."invasionen
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$ids = array();
				
				while($d = mysql_fetch_assoc($q)) {
					$ids[] = $d['invasionen_planetenID'];
				}
				
				if(count($ids)) {
					$conds[] = "planetenID NOT IN(".implode(',', $ids).")";
				}
			}
			// Urlaubsmodus
			if(isset($_GET['umod'])) {
				// ja
				if($_GET['umod'] == 1) $conds[] = 'playerUmod = 1';
				// nein
				else $conds[] = 'playerUmod = 0';
			}
			// Planeten
			if(isset($_GET['pl'])) {
				$_GET['pl'] = (int)$_GET['pl'];
				$conds[] = 'playerPlaneten '.(isset($_GET['pl_']) ? '>' : '<').'= '.$_GET['pl'];
				$conds[] = 'planeten_playerID > 2';
			}
			// Inaktiv
			if(isset($_GET['ina'])) {
				$_GET['ina'] = (int)$_GET['ina'];
				$_GET['ina'] = time()-$_GET['ina']*86400;
				$conds[] = 'playerActivity < '.$_GET['ina'];
				$conds[] = 'playerActivity > 0';
				$conds[] = 'planeten_playerID > 2';
			}
			// Allianz-ID
			if(isset($_GET['aid'])) {
				$conds[] = 'player_allianzenID'.db_multiple($_GET['aid']);
			}
			// Allianz-Tag
			if(isset($_GET['at'])) {
				$conds[] = "allianzenTag LIKE '".escape(escape(str_replace('*', '%', $_GET['at'])))."'";
			}
			// Allianz-Name
			if(isset($_GET['an'])) {
				$conds[] = "allianzenName LIKE '".escape(escape(str_replace('*', '%', $_GET['an'])))."'";
			}
			// Allianz-Status
			if(isset($_GET['as'])) {
				$_GET['as'] = (int)$_GET['as'];
				// Freunde
				if($_GET['as'] == -1) {
					$conds[] = 'statusStatus IN('.implode(',', $status_freund).')';
				}
				// Feinde
				else if($_GET['as'] == -2) {
					$conds[] = 'statusStatus IN('.implode(',', $status_feind).')';
				}
				// neutral
				else if($_GET['as'] == 0) {
					$conds[] = '(statusStatus = 0 OR statusStatus IS NULL)';
				}
				// normaler Status
				else if(isset($status[$_GET['as']])) {
					$conds[] = 'statusStatus = '.$_GET['as'];
				}
			}
			// Allianz-Status (erweitert)
			if(isset($_GET['as2'])) {
				foreach($_GET['as2'] as $key=>$val) {
					if(!isset($status[$key])) {
						unset($_GET['as2'][$key]);
					}
				}
				
				if(count($_GET['as2']) AND count($_GET['as2']) < count($status)) {
					$as2 = array_keys($_GET['as2']);
					
					// neutral dabei -> NULL
					if(isset($_GET['as2'][0])) {
						$conds[] = '(statusStatus IN('.implode(',', $as2).') OR statusStatus IS NULL)';
					}
					else {
						$conds[] = 'statusStatus IN('.implode(',', $as2).')';
					}
				}
			}
			// History
			if(isset($_GET['his'])) {
				// User-ID
				if(isset($_GET['his_'])) {
					$_GET['his'] = (int)$_GET['his'];
					// jeder Planet war mal frei
					if($_GET['his'] == 0) $_GET['his'] = -2;
					
					$query = query("
						SELECT DISTINCT
							history_planetenID
						FROM
							".PREFIX."planeten_history
						WHERE
							history_playerID = ".$_GET['his']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
				// Username
				else {
					$query = query("
						SELECT
							playerID
						FROM
							".GLOBPREFIX."player
						WHERE
							playerName LIKE '".escape(escape(str_replace('*', '%', $_GET['his'])))."'
						ORDER BY playerID ASC
						LIMIT 1
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					if(mysql_num_rows($query)) {
						$data = mysql_fetch_assoc($query);
						
						$query = query("
							SELECT DISTINCT
								history_planetenID
							FROM
								".PREFIX."planeten_history
							WHERE
								history_playerID = ".$data['playerID']."
						") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					}
				}
				// auswerten und Bedingung hinzufügen
				if(mysql_num_rows($query)) {
					$val = array();
					while($row = mysql_fetch_assoc($query)) {
						$val[] = $row['history_planetenID'];
					}
					$conds[] = 'planetenID IN('.implode(',', $val).')';
				}
				// keine Planeten gefunden
				else {
					$conds[] = 'planetenID = 0';
				}
			}
			
			// Routen-Bedingungen
			if($route) {
				if($route->gala) {
					$conds[] = 'systeme_galaxienID = '.$route->gala;
				}
				
				if($route->toxx) {
					$route->in_toxxroute(0);
					if(count($toxxroute)) {
						$conds[] = 'planetenID NOT IN('.implode(',', array_keys($toxxroute)).')';
					}
				}
				else if($route->count) {
					$conds[] = 'planetenID NOT IN('.implode(',', array_keys($route->data)).')';
				}
			}
			
			// Entfernung und Sortierung
			$entf = false;
			
			if(isset($_GET['entf']) OR isset($_GET['entf2'])) {
				if(isset($_GET['entf'])) {
					$entf1 = flug_point($_GET['entf']);
					if(is_array($entf1)) {
						$entf = entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $entf1[1], $entf1[2], $entf1[3], $entf1[4]);
						// Galaxie filtern
						$conds[] = 'systeme_galaxienID = '.$entf1[0];
					}
				}
				if(isset($_GET['entf2'])) {
					$entf2 = flug_point($_GET['entf2']);
					if(is_array($entf2)) {
						// Galaxie filtern
						if(!$entf) {
							$conds[] = 'systeme_galaxienID = '.$entf2[0];
						}
						$entf2 = entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $entf2[1], $entf2[2], $entf2[3], $entf2[4]);
						// zwei Entfernungen koppeln
						if($entf) {
							$entf .= " + ".$entf2;
						}
						else $entf = $entf2;
					}
				}
			}
			
			// Entfernungsfilter
			if($entf AND (isset($_GET['ef1']) OR isset($_GET['ef2']))) {
				$ef1 = isset($_GET['ef1']) ? (int)$_GET['ef1'] : 0;
				$ef2 = isset($_GET['ef2']) ? (int)$_GET['ef2'] : 0;
				$ef3 = isset($_GET['ef3']) ? (int)$_GET['ef3'] : 2; // default +- 2 min
				
				$ef = (3600*$ef1)+(60*$ef2);
				
				$ef3 *= 60;
				
				$conds[] = $entf.' > '.entffdauer($ef-$ef3, $antr);
				$conds[] = $entf.' < '.entffdauer($ef+$ef3, $antr);
			}
			
			// Entfernung-Spalte ausblenden, wenn keine Entfernung berechnet
			if(!$entf AND isset($sp2[10])) {
				unset($sp2[10]);
				unset($sp[array_search(10, $sp)]);
			}
			
			// nach Entfernung sortieren
			if(isset($_GET['sortt']) AND $entf) {
				$sort = 'planetenEntfernung '.(isset($_GET['sorto3']) ? 'DESC' : 'ASC');
			}
			// nach Spalte sortieren
			else {
				if(!isset($_GET['sort']) OR !isset($sorto[$_GET['sort']])) $_GET['sort'] = 1;
				if(!isset($_GET['sort2']) OR !isset($sorto[$_GET['sort2']])) $_GET['sort2'] = 1;
				
				$sort = $sorto[$_GET['sort']].' '.(isset($_GET['sorto']) ? 'DESC' : 'ASC');
				// 2. Stufe, wenn nicht gleich wie 1. Stufe
				if($_GET['sort2'] != $_GET['sort']) {
					$sort .= ', '.$sorto[$_GET['sort2']].' '.(isset($_GET['sorto2']) ? 'DESC' : 'ASC');
				}
			}
			
			
			// Abfrage vorbereiten
			$sql = "
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
					LEFT JOIN ".GLOBPREFIX."player
						ON playerID = planeten_playerID
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
					LEFT JOIN ".PREFIX."galaxien
						ON galaxienID = systeme_galaxienID
						AND galaxienGate = planetenID
					LEFT JOIN ".PREFIX."planeten_schiffe
						ON schiffe_planetenID = planetenID
					LEFT JOIN ".PREFIX."allianzen_status
						ON statusDBAllianz = ".$user->allianz."
						AND status_allianzenID = allianzenID";
			
			$conds = implode(' AND ', $conds);
			if($conds == '') $conds = '1';
			
			
			$t = time();
			
			
			// Anzahl der Ergebnisse ermitteln
			// bei Route weglassen
			if($route) {
				$_GET['tcount'] = 1;
			}
			if(!isset($_GET['tcount']) OR (int)$_GET['tcount'] < 1 OR (isset($_GET['time']) AND $_GET['time'] < $t-3600)) {
				$query = query("
					SELECT
						COUNT(*)
					FROM
						".$sql."
					WHERE
						".$conds."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$data = mysql_fetch_array($query);
				
				$_GET['tcount'] = $data[0];
				$_GET['time'] = $t;
				$_SERVER['QUERY_STRING'] = preg_replace('/&time=(\d+)/', '', $_SERVER['QUERY_STRING']);
				$_SERVER['QUERY_STRING'] .= '&tcount='.$_GET['tcount'].'&time='.$t;
			}
			
			$tcount = (int)$_GET['tcount'];
			
			// bei normaler Suche Link "Suchoptionen ausblenden"
			if(!isset($_GET['hide'])) {
				$querystring = htmlspecialchars(str_replace('&edit', '', $_SERVER['QUERY_STRING']).'&hide', ENT_COMPAT, 'UTF-8');
				$content .= '
					<div style="float:left">
						<a class="link contextmenu" data-link="index.php?'.$querystring.'" style="font-style:italic">Suchoptionen ausblenden</a>
					</div>';
			}
			// bei versteckter Suche Bearbeiten-Link hinzufügen
			else {
				$querystring = htmlspecialchars(str_replace('&hide', '', $_SERVER['QUERY_STRING']).'&edit', ENT_COMPAT, 'UTF-8');
				$content .= '
					<div style="float:left">
						<a class="link contextmenu" data-link="index.php?'.$querystring.'" style="font-style:italic">Suchoptionen ändern</a>
					</div>';
				
			}
			
			// Favoriten-Button
			if(!isset($_GET['fav']) AND !isset($_GET['hide'])) {
				$querystring = htmlspecialchars(str_replace(array('&hide', '&edit', "'"), array('', '', "\\'"), $_SERVER['QUERY_STRING']).'&hide&fav', ENT_COMPAT, 'UTF-8');
				$content .= '
					<div class="favadd" title="Suchergebnis zu den Favoriten hinzuf&uuml;gen" onclick="fav_add(\'index.php?'.$querystring.'\', 3)"></div>';
			}
			
			$content .= '<br /><br />';
			
			// keine Treffer
			if(!$tcount) {
				$content .= '
					<div class="center" style="font-weight:bold" id="treffer'.$t.'">Die Suche lieferte keine Treffer!</div>
					<br />';
			}
			// Treffer
			else {
				// Entfernungsberechnung invalid
				if(!$entf AND isset($_GET['sortt']) AND (isset($_GET['entf']) OR isset($_GET['entf2']))) {
					$content .= '
					<div class="center error">Entfernungsberechnung fehlgeschlagen!</div>
					</br /><br />';
				}
				
				$content .= '
					<div class="center" id="treffer'.$t.'" style="font-weight:bold">Die Suche lieferte '.$tcount.' Treffer:</div>
					<br />';
				
				// Pagebar erzeugen
				if(!class_exists('pagebar')) {
					include './common/pagebar.php';
				}
				
				$querystring = preg_replace('/&page=(\d+)/', '', $_SERVER['QUERY_STRING']);
				$querystring = str_replace('&edit', '', $querystring);
				$querystring2 = str_replace('&', '&amp;', $querystring);
				
				$limit = 100;
				$pagebar = pagebar::generate($tcount, $limit, $querystring);
				$offset = pagebar::offset($tcount, $limit);
				
				// Pagebar ausgeben
				$content .= $pagebar;
				
				// Routen-Formular
				if($user->rechte['routen']) {
					$content .= '<form name="routenform" onsubmit="return false">';
				}
				
				// Tabellen-Header
				$content .= '
	<br />
	<table class="data searchtbl" style="width:100%;background-image:url(img/layout/contentbg.gif)">
	<tr>';
				
				$qs_sort = preg_replace('/&page=(\d+)/', '', $_SERVER['QUERY_STRING']);
				$qs_sort = preg_replace('/&sort=(\d+)/', '', $qs_sort);
				$qs_sort = preg_replace('/&sortt=(\d+)/', '', $qs_sort);
				$qs_sort = preg_replace('/&sorto=(\d+)/', '', $qs_sort);
				$qs_sort = preg_replace('/&sort2=(\d+)/', '', $qs_sort);
				$qs_sort = preg_replace('/&sorto2=(\d+)/', '', $qs_sort);
				$qs_sort = str_replace('&', '&amp;', $qs_sort);
				
				foreach($sp as $key) {
					$content .= '
		<th';
					// Sortierungüber Spalten-Klick
					if(isset($spalten_sort[$key])) {
						$content .= ' class="link" data-link="index.php?'.$qs_sort.'&amp;sort='.$spalten_sort[$key];
						// absteigend sortieren
						if(isset($_GET['sort']) AND $_GET['sort'] == $spalten_sort[$key] AND !isset($_GET['sorto'])) {
							$content .= '&amp;sorto=1';
						}
						$content .= '"';
					}
					$content .= '>'.$splabels[$key].'</th>';
				}
				$content .= '
	</tr>';
				
				// Abfragelimit bei Route festlegen
				if($route) {
					// maximale Anzahl in der Express-Erstellung
					if(isset($_GET['limit'])) {
						$_GET['limit'] = (int)$_GET['limit'];
						if($_GET['limit'] > 0 AND $_GET['limit'] <= $route->limit) {
							$route->limit = $_GET['limit'];
						}
					}
					
					$offset = 0;
					$limit = $route->limit-$route->count;
					if(!$limit) {
						$limit = 1;
					}
				}
				else if($karte) {
					$offset = 0;
					$limit = 10000;
					$sysids = array();
				}
				
				// Daten abfragen
				$query = query("
					SELECT
						planetenID,
						planetenName,
						planetenUpdateOverview,
						planetenUpdate,
						planetenUnscannbar,
						planetenTyp,
						planetenGroesse,
						planetenKategorie,
						planetenGebPlanet,
						planetenGebOrbit,
						planetenMyrigate,
						planetenGateEntf,
						planetenRWErz,
						planetenRWWolfram,
						planetenRWKristall,
						planetenRWFluor,
						planetenRPErz,
						planetenRPMetall,
						planetenRPWolfram,
						planetenRPKristall,
						planetenRPFluor,
						planetenRMErz,
						planetenRMMetall,
						planetenRMWolfram,
						planetenRMKristall,
						planetenRMFluor,
						planetenRPGesamt,
						planetenRMGesamt,
						planetenForschung,
						planetenIndustrie,
						planetenBevoelkerung,
						planetenRessplani,
						planetenWerft,
						planetenBunker,
						planetenGeraidet,
						planetenGetoxxt,
						planetenKommentar,
						planeten_playerID,
						planetenNatives,
						
						".($entf ? $entf." AS planetenEntfernung," : '')."
						
						systemeID,
						systeme_galaxienID,
						systemeX,
						systemeZ,
						systemeUpdate,
						systemeAllianzen,
						
						galaxienGate,
						
						playerName,
						playerPlaneten,
						playerRasse,
						playerImppunkte,
						playerUmod,
						playerDeleted,
						playerActivity,
						player_allianzenID,
						
						allianzenTag,
						allianzenName,
						
						register_allianzenID,
						
						schiffeBergbau,
						schiffeTerraformer,
						
						statusStatus
					FROM
						".$sql."
					WHERE
						".$conds."
					ORDER BY
						".$sort."
					LIMIT
						".$offset.",".$limit."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// ID-Liste für Ergebnis-Navigation
				$ids = array();
				
				// erste Galaxie
				$firstgala = false;
				
				while($row = mysql_fetch_assoc($query)) {
					// zu Route hinzufügen
					if($route) {
						$route->add($row['planetenID'], false);
						continue;
					}
					
					// Karte
					if($karte) {
						if(!isset($sysids[$row['systemeID']])) {
							$sysids[$row['systemeID']] = true;
						}
						continue;
					}
					
					// erste Galaxie
					if(!$firstgala) {
						$firstgala = $row['systeme_galaxienID'];
					}
					
					// ID an Liste anhängen
					$ids[] = $row['planetenID'];
					
					// Werte erzeugen
					$spv = array();
					
					// Galaxie
					if(isset($sp2[1])) {
						$spv[1] = '<span style="color:'.sektor_coord($row['systemeX'], $row['systemeZ']).'">'.$row['systeme_galaxienID'].'</span>';
					}
					// System
					if(isset($sp2[2])) {
						$spv[2] = '<a class="link winlink contextmenu link_system" data-id="'.$row['systemeID'].'" data-link="index.php?p=show_system&amp;id='.$row['systemeID'].'&amp;ajax">'.$row['systemeID'].'</a>';
					}
					// Plani-ID
					if(isset($sp2[3])) {
						$spv[3] = '<a class="link winlink contextmenu link_planet" data-id="'.$row['planetenID'].'" data-link="index.php?p=show_planet&amp;id='.$row['planetenID'].'&amp;nav='.$t.'&amp;ajax">'.$row['planetenID'].'</a>';
					}
					// Plani-Name
					if(isset($sp2[4])) {
						$spv[4] = '<a class="link winlink contextmenu link_planet" data-id="'.$row['planetenID'].'" data-link="index.php?p=show_planet&amp;id='.$row['planetenID'].'&amp;nav='.$t.'&amp;ajax">'.htmlspecialchars($row['planetenName'], ENT_COMPAT, 'UTF-8').'</a>';
						
						if($user->rechte['fremdinvakolos']) {
							// Bergbau anzeigen
							if($row['schiffeBergbau'] !== NULL) {
								$spv[4] .= ' <span class="lightgreen bold">BBS</span>';
							}
							
							// Terraformer anzeigen
							if($row['schiffeTerraformer']) {
								$spv[4] .= ' <span class="lightgreen bold">TF</span>';
							}
						}
					}
					// Inhaber
					if(isset($sp2[5])) {
						if($row['playerName'] !== NULL) {
							$spv[5] = '
								<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['planeten_playerID'].'&amp;ajax">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
							// Urlaubsmodus
							if($row['playerUmod']) {
								$spv[5] .= '<sup class="small red">zzZ</sup>';
							}
							if(isset($rassen2[$row['playerRasse']])) {
								$spv[5] .= ' <img src="img/layout/leer.gif" class="rasse searchrasse '.$rassen2[$row['playerRasse']].'" alt="" />';
							}
						}
						else if($row['planeten_playerID'] == 0) $spv[5] = '<i>keiner</i>';
						else if($row['planeten_playerID'] == -2) $spv[5] = '<span style="color:#ffff88;font-weight:bold;font-style:italic">Seze Lux</span>';
						else if($row['planeten_playerID'] == -3) $spv[5] = '<span style="color:#ffff88;font-weight:bold;font-style:italic">Altrasse</span>';
						else $spv[5] = '<i>unbekannt</i>';
					}
					// Allianz-Tag
					if(isset($sp2[6])) {
						if($row['playerName'] !== NULL) {
							if($row['player_allianzenID']) {
								$spv[6] = '
								<nobr><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.(
									($row['allianzenTag'] != NULL) 
									? htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8')
									: '<i>unbekannt</i>'
								).'</a></nobr>';
							}
							// allianzlos
							else $spv[6] = '&nbsp;';
						}
						else $spv[6] = '&nbsp;';
					}
					// Größe
					if(isset($sp2[8])) {
						$spv[8] = $row['planetenGroesse'];
					}
					// Planeten-Typ
					if(isset($sp2[9])) {
						$spv[9] = '<img src="img/planeten/'.$row['planetenTyp'].'.jpg" alt="" class="searchicon plicon" />';
					}
					// Entfernung
					if(isset($sp2[10]) AND $entf) {
						$spv[10] = flugdauer($row['planetenEntfernung'], $antr);
					}
					// Gate-Entfernung
					if(isset($sp2[11])) {
						if($row['planetenGateEntf'] != NULL) $spv[11] = flugdauer($row['planetenGateEntf'], $antr);
						else $spv[11] = '-';
					}
					// Oberflächen-Scan
					if(isset($sp2[12])) {
						$color = (time()-($config['scan_veraltet']*86400) > $row['planetenUpdateOverview']) ? 'red' : 'green';
						if($row['planetenUpdateOverview'] >= $heute) $scan = 'heute';
						else if($row['planetenUpdateOverview']) $scan = strftime('%d.%m.%y', $row['planetenUpdateOverview']);
						else $scan = 'nie';
						
						$spv[12] = '<span class="'.$color.'">'.$scan.'</span>';
						
						if($row['planetenUnscannbar'] > $row['planetenUpdateOverview']) {
							$spv[12] .= ' <span class="red bold">(!)</span>';
						}
						
					}
					// Miniaturansicht
					if(isset($sp2[13])) {
						// Berechtigung überprüfen, den Scan zu sehen
						$r = $user->rechte['show_planet'];
						
						// bei eigenen Planeten immer Berechtigung, falls globale Berechtigung
						if($r AND $row['planeten_playerID'] != $user->id) {
							// keine Berechtigung (Ally)
							if(!$user->rechte['show_planet_ally'] AND $user->allianz AND $row['player_allianzenID'] == $user->allianz) {
								$r = false;
							}
							// keine Berechtigung (Meta)
							else if($user->allianz AND !$user->rechte['show_planet_meta'] AND $row['statusStatus'] == $status_meta AND $row['player_allianzenID'] != $user->allianz) {
								$r = false;
							}
							// keine Berechtigung (registrierte Allianzen)
							else if(!$user->rechte['show_planet_register'] AND $row['register_allianzenID'] !== NULL AND $row['statusStatus'] != $status_meta) {
								$r = false;
							}
						}
						
						if($row['planetenUpdateOverview'] AND $r) {
							$color = (time()-($config['scan_veraltet']*86400) > $row['planetenUpdateOverview']) ? 'red' : 'green';
							$scan = strftime('%d.%m.%y', $row['planetenUpdateOverview']);
							if($row['planetenUpdateOverview'] >= $heute) $scan = 'heute';
							else $scan = strftime('%d.%m.%y', $row['planetenUpdateOverview']);
							
							// Unscannbar
							if($row['planetenUnscannbar'] > $row['planetenUpdateOverview']) {
								$unscannbar = '&lt;div class=&quot;red center bold&quot;&gt;unscannbar!&lt;/div&gt;';
							}
							else {
								$unscannbar = '';
							}
							
							$spv[13] = '<div class="searchicon tooltip plscreen" style="width:18px;height:18px;background-position:-336px -54px" data-plscreen="'.$row['planetenTyp'].'_0+'.$row['planetenGebPlanet'].'_0+'.$row['planetenGebOrbit'].'_&lt;div class=&quot;'.$color.' center&quot;&gt;Scan: '.$scan.'&lt;/div&gt;'.$unscannbar.'"></div>';
						}
						else $spv[13] = '&nbsp;';
					}
					// Kategorie
					if(isset($sp2[14])) {
						if($row['planetenUpdateOverview']) $spv[14] = '<div class="katicon tooltip" style="'.($row['planetenKategorie'] ? 'background-position:-'.(20*($row['planetenKategorie']-1)).'px 0px' : 'background-image:none').'" data-tooltip="&lt;table class=&quot;showsysresst&quot;&gt;&lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress erz&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMErz']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress metall&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMMetall']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress wolfram&quot;&gt;&lt;/div&gt;&lt;/td&gt; &lt;td&gt;'.ressmenge($row['planetenRMWolfram']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress kristall&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMKristall']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress fluor&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMFluor']).'&lt;/td&gt; &lt;/tr&gt; &lt;/table&gt;"></div>';
						else $spv[14] = '&nbsp;';
					}
					// Kommentar
					if(isset($sp2[15])) {
						if(trim($row['planetenKommentar']) != '') $spv[15] = '<div class="plkommentar'.$row['planetenID'].' kommentar searchicon tooltip" data-tooltip="'.htmlspecialchars(nl2br(htmlspecialchars($row['planetenKommentar'], ENT_COMPAT, 'UTF-8')), ENT_COMPAT, 'UTF-8').'"></div>';
						else $spv[15] = '<div class="plkommentar'.$row['planetenID'].' kommentar searchicon tooltip" data-tooltip="" style="display:none"></div>';
					}
					// getoxxt
					if(isset($sp2[16])) {
						// Berechtigung überprüfen
						if($user->rechte['toxxraid']) {
							$spv[16] = ($row['planetenGetoxxt'] > time() ? strftime('%d.%m.%y', $row['planetenGetoxxt']) : '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=toxx&amp;id='.$row['planetenID'].'&amp;typ=search&amp;ajax\', this.parentNode, false, false)" class="hint">[getoxxt]</a>');
						}
						// keine Berechtigung
						else $spv[16] = '&nbsp;';
					}
					// geraidet
					if(isset($sp2[17])) {
						// Berechtigung überprüfen
						if($user->rechte['toxxraid']) {
							// wenn älter als 7 Tage, ausgrauen
							$spv[17] = '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=raid&amp;id='.$row['planetenID'].'&amp;typ=search&amp;ajax\', this.parentNode, false, false)">'.($row['planetenGeraidet'] ? '<span'.((time()-$row['planetenGeraidet'] > 604800) ? ' class="hint"' : '').'>'.strftime('%d.%m.%y', $row['planetenGeraidet']).'</span>' : '<span class="hint" style="font-style:italic">nie</span>').'</a>';
						}
						// keine Berechtigung
						else $spv[17] = '&nbsp;';
					}
					// zu Route hinzufügen
					if(isset($sp2[18])) {
						// Berechtigung überprüfen
						if($user->rechte['routen']) {
							$spv[18] = '<input type="checkbox" name="'.$row['planetenID'].'" />';
						}
						// keine Berechtigung
						else $spv[18] = '&nbsp;';
					}
					// System-Scan
					if(isset($sp2[19])) {
						$color = (time()-($config['scan_veraltet']*86400) > $row['systemeUpdate']) ? 'red' : 'green';
						if($row['systemeUpdate'] >= $heute) $scan = 'heute';
						else if($row['systemeUpdate']) $scan = strftime('%d.%m.%y', $row['systemeUpdate']);
						else $scan = 'nie';
						$spv[19] = '<span class="'.$color.'">'.$scan.'</span>';
					}
					// voller Scan
					if(isset($sp2[20])) {
						$color = (time()-($config['scan_veraltet']*86400) > $row['planetenUpdate']) ? 'red' : 'green';
						if($row['planetenUpdate'] >= $heute) $scan = 'heute';
						else if($row['planetenUpdate']) $scan = strftime('%d.%m.%y', $row['planetenUpdate']);
						else $scan = 'nie';
						$spv[20] = '<span class="'.$color.'">'.$scan.'</span>';
					}
					// Bevölkerung
					if(isset($sp2[21])) {
						$spv[21] = ressmenge($row['planetenBevoelkerung']);
					}
					// Gate
					if(isset($sp2[22])) {
						$spv[22] = $row['galaxienGate'] ? 'ja' : '<span class="hint">nein</span>';
					}
					// Myrigate
					if(isset($sp2[23])) {
						$r = true;
	
						// keine Berechtigung (global)
						if(!$user->rechte['show_myrigates']) {
							$r = false;
						}
						// Myrigates eigener Planeten ansonsten immer sichtbar
						else if($user->id == $row['planeten_playerID']) {}
						// keine Berechtigung (Allianz)
						else if($user->allianz AND !$user->rechte['show_myrigates_ally'] AND $user->allianz == $row['player_allianzenID']) {
							$r = false;
						}
						// keine Berechtigung (Meta)
						else if($user->allianz AND !$user->rechte['show_myrigates_meta'] AND $row['statusStatus'] == $status_meta) {
							$r = false;
						}
						// keine Berechtigung (registrierte Allianzen)
						else if(!$user->rechte['show_myrigates_register'] AND $row['register_allianzenID'] !== NULL) {
							$r = false;
						}
						
						if($r) $spv[23] = $row['planetenMyrigate'] ? 'ja' : '<span class="hint">nein</span>';
						else $spv[23] = '&nbsp;';
					}
					// Allianz-Name
					if(isset($sp2[24])) {
						if($row['playerName'] != NULL) {
							if($row['player_allianzenID']) {
								$spv[24] = '
								<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.(
									($row['allianzenTag'] != NULL) 
									? htmlspecialchars($row['allianzenName'], ENT_COMPAT, 'UTF-8')
									: '<i>unbekannt</i>'
								).'</a>';
							}
							// allianzlos
							else $spv[24] = '&nbsp;';
						}
						else $spv[24] = '&nbsp;';
					}
					// Allianz-Status
					if(isset($sp2[25])) {
						if($row['allianzenTag'] != NULL) {
							if($row['statusStatus'] == NULL) $row['statusStatus'] = 0;
							$spv[25] = '<span '.$status_color[$row['statusStatus']].'>'.$status[$row['statusStatus']].'</span>';
						}
						else $spv[25] = '&nbsp;';
					}
					// Ressplanet
					if(isset($sp2[26])) {
						$r = true;
	
						// eigener Planeten ansonsten immer sichtbar
						if($user->id == $row['planeten_playerID']) {}
						// keine Berechtigung (Allianz)
						else if($user->allianz AND !$user->rechte['ressplani_ally'] AND $user->allianz == $row['player_allianzenID']) {
							$r = false;
						}
						// keine Berechtigung (Meta)
						else if($user->allianz AND !$user->rechte['ressplani_meta'] AND $row['statusStatus'] == $status_meta) {
							$r = false;
						}
						// keine Berechtigung (registrierte Allianzen)
						else if(!$user->rechte['ressplani_register'] AND $row['register_allianzenID'] !== NULL) {
							$r = false;
						}
						
						// Berechtigung
						if($r) {
							$spv[26] = $row['planetenRessplani'] ? 'ja' : '<span class="hint">nein</span>';
						}
						else $spv[26] = '&nbsp;';
					}
					// Bunker
					if(isset($sp2[27])) {
						$r = true;
	
						// eigener Planeten ansonsten immer sichtbar
						if($user->id == $row['planeten_playerID']) {}
						// keine Berechtigung (Allianz)
						else if($user->allianz AND !$user->rechte['bunker_ally'] AND $user->allianz == $row['player_allianzenID']) {
							$r = false;
						}
						// keine Berechtigung (Meta)
						else if($user->allianz AND !$user->rechte['bunker_meta'] AND $row['statusStatus'] == $status_meta) {
							$r = false;
						}
						// keine Berechtigung (registrierte Allianzen)
						else if(!$user->rechte['bunker_register'] AND $row['register_allianzenID'] !== NULL) {
							$r = false;
						}
						
						// Berechtigung
						if($r) {
							$spv[27] = $row['planetenBunker'] ? 'ja' : '<span class="hint">nein</span>';
						}
						else $spv[27] = '&nbsp;';
					}
					// Werft
					if(isset($sp2[28])) {
						$r = true;
	
						// eigener Planeten ansonsten immer sichtbar
						if($user->id == $row['planeten_playerID']) {}
						// keine Berechtigung (Allianz)
						else if($user->allianz AND !$user->rechte['werft_ally'] AND $user->allianz == $row['player_allianzenID']) {
							$r = false;
						}
						// keine Berechtigung (Meta)
						else if($user->allianz AND !$user->rechte['werft_meta'] AND $row['statusStatus'] == $status_meta) {
							$r = false;
						}
						// keine Berechtigung (registrierte Allianzen)
						else if(!$user->rechte['werft_register'] AND $row['register_allianzenID'] !== NULL) {
							$r = false;
						}
						
						// Berechtigung
						if($r) {
							$spv[28] = $row['planetenWerft'] ? 'ja' : '<span class="hint">nein</span>';
						}
						else $spv[28] = '&nbsp;';
					}
					// Forschung
					if(isset($sp2[29])) {
						$spv[29] = $row['planetenUpdate'] ? ressmenge($row['planetenForschung']) : '&nbsp;';
					}
					// Industrie
					if(isset($sp2[30])) {
						$spv[30] = $row['planetenUpdate'] ? ressmenge($row['planetenIndustrie']) : '&nbsp;';
					}
					// Erz-Wert
					if(isset($sp2[31])) {
						$spv[31] = ressmenge($row['planetenRWErz']).'%';
					}
					// Wolfram-Wert
					if(isset($sp2[33])) {
						$spv[33] = ressmenge($row['planetenRWWolfram']).'%';
					}
					// Kristall-Wert
					if(isset($sp2[34])) {
						$spv[34] = ressmenge($row['planetenRWKristall']).'%';
					}
					// Fluor-Wert
					if(isset($sp2[35])) {
						$spv[35] = ressmenge($row['planetenRWFluor']).'%';
					}
					// Erz-Produktion
					if(isset($sp2[36])) {
						$spv[36] = $row['planetenUpdate'] ? ressmenge($row['planetenRPErz']) : '&nbsp;';
					}
					// Metall-Produktion
					if(isset($sp2[37])) {
						$spv[37] = $row['planetenUpdate'] ? ressmenge($row['planetenRPMetall']) : '&nbsp;';
					}
					// Wolfram-Produktion
					if(isset($sp2[38])) {
						$spv[38] = $row['planetenUpdate'] ? ressmenge($row['planetenRPWolfram']) : '&nbsp;';
					}
					// Kristall-Produktion
					if(isset($sp2[39])) {
						$spv[39] = $row['planetenUpdate'] ? ressmenge($row['planetenRPKristall']) : '&nbsp;';
					}
					// Fluor-Produktion
					if(isset($sp2[40])) {
						$spv[40] = $row['planetenUpdate'] ? ressmenge($row['planetenRPFluor']) : '&nbsp;';
					}
					// Erz-Vorrat
					if(isset($sp2[41])) {
						$spv[41] = $row['planetenUpdateOverview'] ? ressmenge($row['planetenRMErz']) : '&nbsp;';
					}
					// Metall-Vorrat
					if(isset($sp2[42])) {
						$spv[42] = $row['planetenUpdateOverview'] ? ressmenge($row['planetenRMMetall']) : '&nbsp;';
					}
					// Wolfram-Vorrat
					if(isset($sp2[43])) {
						$spv[43] = $row['planetenUpdateOverview'] ? ressmenge($row['planetenRMWolfram']) : '&nbsp;';
					}
					// Kristall-Vorrat
					if(isset($sp2[44])) {
						$spv[44] = $row['planetenUpdateOverview'] ? ressmenge($row['planetenRMKristall']) : '&nbsp;';
					}
					// Fluor-Vorrat
					if(isset($sp2[45])) {
						$spv[45] = $row['planetenUpdateOverview'] ? ressmenge($row['planetenRMFluor']) : '&nbsp;';
					}
					// gesamte Ressproduktion
					if(isset($sp2[46])) {
						$spv[46] = $row['planetenUpdate'] ? ressmenge($row['planetenRPGesamt']) : '&nbsp;';
					}
					// gesamter Ressvorrat
					if(isset($sp2[47])) {
						$spv[47] = $row['planetenUpdateOverview'] ? ressmenge($row['planetenRMGesamt']) : '&nbsp;';
					}
					// Planetenanzahl
					if(isset($sp2[48])) {
						if($row['playerPlaneten'] != NULL AND $row['planeten_playerID'] > 1) {
							$spv[48] = $row['playerPlaneten'];
						}
						else $spv[48] = '&nbsp;';
					}
					// Imperiumspunkte
					if(isset($sp2[49])) {
						if($row['playerImppunkte'] != NULL AND $row['planeten_playerID'] > 1) {
							$spv[49] = ressmenge($row['playerImppunkte']);
						}
						else $spv[49] = '&nbsp;';
					}
					// letzte Aktivität des Inhabers
					if(isset($sp2[50])) {
						if($row['planeten_playerID'] > 1 AND $row['playerActivity'] != NULL) {
							if($row['playerActivity'] >= $heute) $spv[50] = 'heute';
							else if($row['playerActivity']) $spv[50] = strftime('%d.%m.%y', $row['playerActivity']);
							else $spv[50] = '<i>keine</i>';
						}
						else $spv[50] = '&nbsp;';
					}
					// Allianzen im System
					if(isset($sp2[51])) {
						// Allianztags ermitteln
						if(!isset($allianzen)) {
							$query2 = query("
								SELECT
									allianzenID,
									allianzenTag
								FROM
									".GLOBPREFIX."allianzen
							") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
							
							$allianzen = array();
							while($row2 = mysql_fetch_array($query2)) {
								$allianzen[$row2[0]] = $row2[1];
							}
						}
						
						// Daten aufbereiten
						$data = str_replace('+', '', explode('++', $row['systemeAllianzen']));
						
						// gesperrte Allianzen ausblenden
						if($user->protectedAllies) {
							foreach($data as $key=>$val) {
								if($val AND in_array($val, $user->protectedAllies)) {
									unset($data[$key]);
								}
							}
						}
						$spv[51] = '';
						foreach($data as $val) {
							// allianzlos
							if($val === '0') {
								$spv[51] .= '<span class="small" style="font-style:italic">allianzlos</span>&nbsp; ';
							}
							// frei
							else if($val === '-1') {
								$spv[51] .= '<span class="small" style="font-style:italic">frei</span>&nbsp; ';
							}
							// unbekannt
							else if($val === '-2') {
								$spv[51] .= '<span class="small" style="font-style:italic">unbekannt</span>&nbsp; ';
							}
							// Allianz
							else if(isset($allianzen[$val])) {
								$spv[51] .= '<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$val.'&amp;ajax">'.htmlspecialchars($allianzen[$val], ENT_COMPAT, 'UTF-8').'</a>&nbsp; ';
							}
						}
					}
					// Natives
					if(isset($sp2[52])) {
						$spv[52] = $row['planetenNatives'];
					}
					// Punkte
					if(isset($sp2[53])) {
						$spv[53] = imppunkte($row);
					}
					
					
					// ausgeben
					
					// wenn User gelöscht, Zeile halbtransparent
					$content .= '
	<tr'.($row['playerDeleted'] ? ' style="opacity:0.4;filter:alpha(opacity=40)"' : '').'>';
					foreach($sp as $key) {
						$content .= '
		<td>'.$spv[$key].'</td>';
					}
					$content .= '
	</tr>';
				}
				
				// Route speichern und Erfolgsmeldung ausgeben
				if($route) {
					// Route berechnen
					if(isset($_GET['compute'], $_GET['entf']) AND (int)$_GET['entf']) {
						$route->compute((int)$_GET['entf']);
					}
					
					$route->save();
					
					$tmpl->content = 'Ergebnisse zu Route hinzugef&uuml;gt. <a onclick="ajaxcall(\'index.php?p=search&amp;sp=route&amp;ajax\', this.parentNode, false, true)" class="hint">[zu weiterer Route / Liste hinzuf&uuml;gen]</a>';
					$tmpl->script = 'if($(\'.route'.$route->id.'\').length > 0) { ajaxcall("index.php?p=route&sp=view&id='.$route->id.'&update", false, false, false);}';
					
					$tmpl->output();
					die();
				}
				// Karte anzeigen
				else if($karte) {
					$t = time();
					if(count($sysids)) {
						$sysids = array_keys($sysids);
					}
					
					$tmpl->name = 'Suchergebnisse - Karte';
					$tmpl->content = '
	<div class="skarte'.$t.'" style="min-width:620px;height:640px;margin:auto">
	</div>';
					$tmpl->script = 'ajaxcall("index.php?p=karte'.((isset($_GET['karte']) AND (int)$_GET['karte']) ? '&gala='.(int)$_GET['karte'] : $firstgala).'", $(".skarte'.$t.'"), {highlight : "'.implode('-', $sysids).'"}, true);';
					
					
					
					$tmpl->output();
					die();
				}
				
				// Tabellen-Footer
				$content .= '
	</table>
	<br />';
				
				// Pagebar ausgeben
				$content .= $pagebar;
				
				// Routen-Formular
				if($user->rechte['routen']) {
					$content .= '
	<div style="text-align:right">
		<input type="hidden" name="querystring" value="'.htmlspecialchars($querystring, ENT_COMPAT, 'UTF-8').'" />';
					if(isset($sp2[18])) {
						$content .= '
		<select name="typ" size="1">
			<option value="1">markierte</option>
			<option value="0">alle</option>
		</select> ';
					}
					else {
						$content .= '
		<input type="hidden" name="typ" value="0" />';
					}
					$content .= '<span class="small2"><a onclick="ajaxcall(\'index.php?p=search&amp;sp=route&amp;ajax\', this.parentNode, false, true)">Suchergebnisse zu einer Route / Liste hinzuf&uuml;gen</a></span>
	</div>
	</form>';
				}
				
				// auf der Karte anzeigen
				if($user->rechte['karte']) {
					$content .= '
	<div class="small2" style="margin-top:8px;text-align:right">
		<form action="#" name="search_karte" onsubmit="$(\'&lt;a class=&quot;link winlink&quot; data-link=&quot;index.php?'.$querystring2.'&amp;karte=\'+this.gala.value+\'&quot;&gt;&lt;/a&gt;\').appendTo(this);$(this).find(\'a.link\').trigger(\'click\').remove();return false">
		<a onclick="$(this.parentNode).trigger(\'onsubmit\')">Ergebnisse auf der Karte anzeigen - Galaxie</a>  
		&nbsp;<input type="text" class="smalltext" style="width:30px" name="gala" value="'.$firstgala.'" onclick="this.select()" />
		<input type="submit" style="display:none" />
		</form>
	</div>';
				}
				
				// hidden-Feld für die Suchnavigation
				$content .= '
					<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />';
			}
			
			// Seite im normalen Modus zu den Ergebnissen scrollen
			if(!isset($_GET['hide']) AND !isset($_GET['edit'])) {
				$tmpl->script = '
if($(\'#treffer'.$t.'\').parents(\'.fenster\').attr(\'id\') != null) {
	$(\'#treffer'.$t.'\').parents(\'.fcc\').scrollTop($(\'#treffer'.$t.'\').position().top-60);
}
else {
	$(\'html,body\').scrollTop($(\'#treffer'.$t.'\').offset().top-10);
}';
				
				// Log-Eintrag
				if($config['logging'] >=2 ) {
					$querystring = htmlspecialchars(str_replace(array('&hide', '&edit', "'"), array('', '', "\\'"), $_SERVER['QUERY_STRING']).'&hide&fav', ENT_COMPAT, 'UTF-8');
					insertlog(16, 'benutzt die Suchfunktion <a class="link contextmenu" data-link="index.php?'.$querystring.'">(Link)</a>');
				}
			}
		}
	}
	
	// sortable-Script für die Suchspalten
	if(!isset($_GET['hide'])) {
		$tmpl->script .= '
$(\'.sortable\').sortable({
	items : \'> div\',
	containment : \'parent\',
	forcePlaceholderSize : true,
	revert : 150,
	tolerance : \'pointer\',
	scroll : true,
	distance : 5,
	axis : \'y\',
	stop : function() {
		var spalten = [];
		var f = $(this);
		$(this).find(\'div\').each(function(i, e) {
			var id = e.className.replace(/filter_/g, \'\');
			if($(this).find(\'input\').prop(\'checked\')) {
				spalten.push(id);
			}
		});
		spalten = spalten.join(\'-\');
		
		$(f).parents(\'form\').find(\'[name=spalten]\').val(spalten);
		$(f).parents(\'form\').find(\'[type=button]\').val(\'als Standard speichern\');
	}
});
$(\'.spalten [type=checkbox]\').click(function() {
	if($(this.parentNode).css(\'opacity\') == 1) {
		$(this.parentNode).css(\'opacity\', \'0.5\');
	}
	else {
		$(this.parentNode).css(\'opacity\', \'1\');
	}
	var spalten = [];
	var f = this.parentNode.parentNode;
	$(f).find(\'div\').each(function(i, e) {
		var id = e.className.replace(/filter_/g, \'\');
		if($(this).find(\'input\').prop(\'checked\')) {
			spalten.push(id);
		}
	});
	spalten = spalten.join(\'-\');
	$(f).parents(\'form\').find(\'[name=spalten]\').val(spalten);
	$(f).parents(\'form\').find(\'[type=button]\').val(\'als Standard speichern\');
});';
	}
	
	// keine Berechtigung
	if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
	}
	$tmpl->output();
	
}
?>