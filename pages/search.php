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


General::loadClass('Search');



/**
 * Umgebungsdaten
 */

// Suchspalten
$spalten = array(
	54=>'Treffer-Position',
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
	53=>'Punkte',
	55=>'Orbiter-Angriff'
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
$sorto = Search::$sorto;

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
	37=>'Punkte',
	38=>'Orbiter-Angriff'
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
	53=>37,
	55=>38
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
					userSettings = '".escape(json_encode($user->settings))."'
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
		
		General::loadClass('route');
		
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
	
	General::loadClass('route');
	
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
			
			$form_additional = '
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
				$form_additional .= '
				<option value="'.$key.'"'.((isset($_GET['sort']) AND $_GET['sort'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
			}
			$form_additional .= '
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
				$form_additional .= '
				<option value="'.$key.'"'.((isset($_GET['sort2']) AND $_GET['sort2'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
			}
			$form_additional .= '
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
					$form_additional .= '
			<div class="filter_'.$key.'" style="opacity:1;filter:alpha(opacity=100)"><input type="checkbox" class="ssp'.$key.'" checked="checked" /> <span>'.$spalten[$key].'</span></div>';
					// aus den verbleibenden Spalten löschen
					if(isset($spalten2[$key])) {
						unset($spalten2[$key]);
					}
				}
			}
			// nicht angekreuzte Spalten
			foreach($spalten2 as $key=>$val) {
				$form_additional .= '
		<div class="filter_'.$key.'" style="opacity:0.5;filter:alpha(opacity=50)"><input type="checkbox" class="ssp'.$key.'" /> <span>'.$val.'</span></div>';
			}
			$form_additional .= '
	</div>
	</div>';
			
			$content .= Search::createSearchForm($_GET, $form_additional);
			
		}
		
		// Suche gesartet
		if(isset($_GET['s'])) {
			
			// alle Suchergebnisse zu einer Route hinzufügen
			$route = false;
			if(isset($_GET['add2route'], $_POST['route'])) {
				
				General::loadClass('route');
				
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
				53=>'Punkte',
				54=>'Pos',
				55=>'Orb-Angriff'
			);
			
			$heute = strtotime('today');
			
			// Bedingungen generieren
			$conds = Search::buildConditions($_GET);
			
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
			$entf = Search::getEntf($_GET);
			
			// Entfernung-Spalte ausblenden, wenn keine Entfernung berechnet
			if(!$entf AND isset($sp2[10])) {
				unset($sp2[10]);
				unset($sp[array_search(10, $sp)]);
			}
			
			// Sortierung
			$sort = Search::getSort($_GET, $entf);
			
			
			$t = time();
			
			
			// Anzahl der Ergebnisse ermitteln
			// bei Route weglassen
			if($route) {
				$_GET['tcount'] = 1;
			}
			if(!isset($_GET['tcount']) OR (int)$_GET['tcount'] < 1 OR (isset($_GET['time']) AND $_GET['time'] < $t-3600)) {
				
				$tcount = Search::getCount($conds);
				
				$_GET['tcount'] = $tcount;
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
				
				
				// Ergebnisse abfragen
				$results = Search::getSearchAsMySQL($conds, $entf, $sort, $offset, $limit);
				
				// Invasionen abfragen
				Search::getInvasionen();
				
				// ID-Liste für Ergebnis-Navigation
				$ids = array();
				$sids = array();
				
				// erste Galaxie
				$firstgala = false;
				
				// Positions-Zähler
				$pos = $offset;
				
				while($row = mysql_fetch_assoc($results)) {
					// Position hochzählen
					$pos++;
					
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
					
					if(!in_array($row['systemeID'], $sids)) {
						$sids[] = $row['systemeID'];
					}
					
					// Invasionen übernehmen
					$row['invasionen'] = isset(Search::$invasionen[$row['planetenID']]) ? Search::$invasionen[$row['planetenID']] : false;
					
					// Werte erzeugen
					$spv = array();
					
					// Galaxie
					if(isset($sp2[1])) {
						$spv[1] = '<span style="color:'.sektor_coord($row['systemeX'], $row['systemeZ']).'">'.$row['systeme_galaxienID'].'</span>';
					}
					// System
					if(isset($sp2[2])) {
						$spv[2] = '<a class="link winlink contextmenu link_system" data-id="'.$row['systemeID'].'" data-link="index.php?p=show_system&amp;id='.$row['systemeID'].'&amp;nav='.$t.'&amp;ajax">'.$row['systemeID'].'</a>';
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
						
						// Natives und Invasionen zum Inhaber
						if($row['planetenNatives'] > 0) {
							$spv[5] .= ' <span class="red">(Natives)</span>';
						}
						
						if($row['invasionen'] !== false) {
							foreach($row['invasionen'] as $inv) {
								$spv[5] .= ' <span class="red">('.$inv['invasionenTypName'].')</span>';
							}
						}
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
						General::loadClass('Rechte');
						
						if($row['planetenUpdateOverview'] AND Rechte::getRechteShowPlanet($row)) {
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
							
							$spv[13] = '<div class="searchicon tooltip plscreen" style="width:18px;height:18px;background-position:-336px -54px" data-plscreen="'.$row['planetenTyp'].'_0+'.$row['planetenGebPlanet'].'_0+'.$row['planetenGebOrbit'].'_0+'.$row['planetenGebSpezial'].'_&lt;div class=&quot;'.$color.' center&quot;&gt;Scan: '.$scan.'&lt;/div&gt;'.$unscannbar.'"></div>';
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
						
						General::loadClass('Rechte');
						
						$r = Rechte::getRechteShowMyrigate($row);
						
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
						
						General::loadClass('Rechte');
						
						$r = Rechte::getRechteRessplanet($row);
						
						// Berechtigung
						if($r) {
							$spv[26] = $row['planetenRessplani'] ? 'ja' : '<span class="hint">nein</span>';
						}
						else $spv[26] = '&nbsp;';
					}
					// Bunker
					if(isset($sp2[27])) {
						
						General::loadClass('Rechte');
						
						$r = Rechte::getRechteBunker($row);
						
						// Berechtigung
						if($r) {
							$spv[27] = $row['planetenBunker'] ? 'ja' : '<span class="hint">nein</span>';
						}
						else $spv[27] = '&nbsp;';
					}
					// Werft
					if(isset($sp2[28])) {
						
						General::loadClass('Rechte');
						
						$r = Rechte::getRechteWerft($row);
						
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
					// Treffer-Position
					if(isset($sp2[54])) {
						$spv[54] = $pos;
					}
					// Orbiter-Angriff
					if(isset($sp2[55])) {
						$spv[55] = ressmenge($row['planetenOrbiter']);
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
					<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />
					<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
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
					insertlog(16, 'benutzt die Suchfunktion ('.Search::getSearchDescription($_GET).')');
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