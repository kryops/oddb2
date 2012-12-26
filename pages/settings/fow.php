<?php
/**
 * pages/settings/fow.php
 * FoW-Einstellungen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


General::loadClass('route');


// FoW-Einstellungen anzeigen
if($_GET['sp'] == 'fow') {
	$content =& $csw->data['fow']['content'];
	
	$content = '
<div class="hl2">
	FoW-Einstellungen
</div>

<div class="icontent">
	Hier kannst du einstellen, welche zus&auml;tzlichen Daten beim FoW-Ausgleich &uuml;ber eines der <a class="link contextmenu" data-link="index.php?p=tools" style="font-weight:bold">Tools</a> angezeigt werden sollen.
	<br /><br />
	<form name="fow" onsubmit="return form_send(this, \'index.php?p=settings&amp;sp=save_fow&amp;ajax\', $(this).siblings(\'.ajax\'))">
		<div style="line-height:30px">
		<b>Gates und Myrigates</b>
		<br />
		<input type="checkbox" name="gate"'.(isset($fow['gate']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="gate">Gate anzeigen</span>
		<br />';
	
	// Myrigate
	if($user->rechte['show_myrigates']) {
		$content .= '
		<input type="checkbox" name="mgate"'.(isset($fow['mgate']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="mgate">n&auml;chstes befreundetes Myrigate anzeigen</span>
		<br />';
	}
	
	$content .= '
		<br />
		<b>Systeme</b>
		<br />
		<input type="checkbox" name="scan"'.(isset($fow['scan']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="scan">nicht erfasste Systeme und veraltete Allianz-Systeme anzeigen</span>
		<br />
		<input type="checkbox" name="scout"'.(isset($fow['scout']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="scout">die</span> &nbsp;<input type="text" class="smalltext" name="scoutcount" value="'.(isset($fow['scoutcount']) ? $fow['scoutcount'] : '1').'" /> <span class="togglecheckbox" data-name="scout"> &nbsp;n&auml;chsten Scout-Systeme anzeigen &auml;lter als</span> &nbsp;<input type="text" class="smalltext" name="scoutval" value="'.((isset($fow['scout']) AND $fow['scout'] > 0) ? $fow['scout'] : "").'" /> &nbsp;<span class="togglecheckbox" data-name="scout">Tage</span>
		&nbsp; <span class="small hint">(leer lassen f&uuml;r globalen Standardwert: '.$config['scan_veraltet'].')</span>
		<br />&nbsp; &nbsp; &nbsp; &nbsp;(<input type="checkbox" name="scoutfirst"'.((isset($fow['scoutfirst']) AND $fow['scoutfirst']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="scoutfirst">nicht gescannte immer zuerst</span>)
		<br />
		<input type="checkbox" name="next"'.(isset($fow['next']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="next">die n&auml;chsten</span> &nbsp;<input type="text" class="smalltext" name="nextval" value="'.(isset($fow['next']) ? $fow['next'] : '1').'" /> &nbsp;<span class="togglecheckbox" data-name="next">Systeme anzeigen</span> &nbsp;
		<span class="small hint">(max. 10)</span>
		<br /><br />';
	
	/*
	 * Routen
	 */
	if($user->rechte['routen']) {
		// berechnete Routen ermitteln
		$routes = route::getlist(0,2);
		
		
		if($routes !== false) {
			
			if(!isset($fow['routen'])) {
				$fow['routen'] = array();
			}
		
			$content .= '
		<b>Routen und Listen</b>
		<br />
		<div class="fcbox fowrouten sortable" style="padding:8px 12px;max-width:98%;line-height:35px">';
			
			foreach($fow['routen'] as $id=>$count) {
				if(isset($routes[$id])) {
					$data2 =& $routes[$id];
					
					$content .= '<div><div class="closebutton" title="Eintrag l&ouml;schen" onclick="$(this.parentNode).remove()"></div><input type="hidden" name="routen[]" value="'.$id.'" />'.htmlspecialchars($data2[0], ENT_COMPAT, 'UTF-8').($data2[2] ? ' (G'.$data2[2].')' : '').' &nbsp; &nbsp; <span class="small2">(die n&auml;chsten &nbsp;<input type="text" class="smalltext" name="routencount[]" value="'.$count.'" /> &nbsp;Ziele anzeigen)</span></div>';
				}
			}
			
			
			
			$content .= '
		</div>
		<div style="max-width:98%;margin:auto">
			<select size="1">';
			
			$own = false;
			$other = false;
			
			
			foreach($routes as $id=>$data) {
				// Gruppierung
				// eigene Route
				if($data[1] == $user->id) {
					if(!$own) {
						$content .= '
	<optgroup label="Eigene Routen/Listen">';
						$own = true;
					}
				}
				// fremde Route
				else {
					if(!$other) {
						if($own) {
							$content .= '
	</optgroup>';
						}
						$content .= '
	<optgroup label="Routen/Listen von anderen">';
						$other = true;
					}
				}
				
				$content .= '
	<option value="'.$id.'">'.htmlspecialchars($data[0], ENT_COMPAT, 'UTF-8').($data[2] ? ' (G'.$data[2].')' : '').'</option>';
				
			}
			
			$content .= '
			</optgroup>
			</select> 
			<a onclick="$(this.parentNode).siblings(\'.fowrouten\').append(\'<div><div class=&quot;closebutton&quot; title=&quot;Eintrag l&ouml;schen&quot; onclick=&quot;$(this.parentNode).remove()&quot;></div><input type=&quot;hidden&quot; name=&quot;routen[]&quot; value=&quot;\'+$(this).siblings(\'select\').val()+\'&quot; />\'+$(this).siblings(\'select\').find(\'option:selected\').html()+\' &nbsp; &nbsp; <span class=&quot;small2&quot;>(die n&auml;chsten &nbsp;<input type=&quot;text&quot; class=&quot;smalltext&quot; name=&quot;routencount[]&quot; value=&quot;1&quot; /> &nbsp;Ziele anzeigen)</span></div>\')"> hinzuf&uuml;gen</a>
		</div>
		<br />';
		}
	}
	
	
	/*
	 * Suchfilter
	 */
	$t = time();
	
	$content .= '
		<b>Planeten (Suchfilter)</b>
		<div class="fcbox fowsearch sortable" style="padding:8px 12px;max-width:98%;line-height:35px" data-timestamp="'.$t.'" data-max="'.(isset($fow['search']) ? count($fow['search']) : '1').'">
		';
	
	// Such-Einträge
	if(isset($fow['search'])) {
		
		General::loadClass('Search');
		
		$i = 1;
		
		
		foreach($fow['search'] as $val) {
			/*
			 * 0 - Name
			 * 1 - Anzahl
			 * 2 - Sortierung
			 * 3 - Wert
			 * 4 - außerhalb des Systems (optional)
			 */
			
			$target = $t.$i;
			
			$content .= '<div id="'.$target.'">
			<div class="closebutton" title="Eintrag l&ouml;schen" onclick="$(this.parentNode).remove()"></div>
			
			<div class="fowsearch1">
				Name: 
				<input type="text" class="text" name="sname[]" value="'.h($val[0]).'" />
				<br />
				die 
				<input type="text" class="text smalltext" style="width:30px" name="scount[]" value="'.h($val[1]).'" /> 
				<select name="sorder[]" size="1">
					<option value="0">n&auml;chsten</option>
					<option value="1"'.($val[2] ? ' selected="selected"' : '').'>entferntesten</option>
				</select> 
				Treffer
				<br />
				<select name="sout[]">
					<option value="1">nur au&szlig;erhalb des Systems</option>
					<option value="0"'.(isset($val[4]) ? '' : ' selected="selected"').'>alle Planeten finden</option>
				</select>
			</div>
			
			<div class="fowsearch3">
				<a onclick="page_load(5, \'FoW-Suchfilter konfigurieren\', \'index.php?p=settings&amp;sp=fow_editsearch&amp;target='.$target.'\', false, {filter : $(this.parentNode.parentNode).find(\'input[type=hidden]\').val()})">
					<img src="img/layout/leer.gif" class="icon hoverbutton configbutton" title="konfigurieren" /> 
				</a>
			</div>
			
			<div class="fowsearch2">';
			
			// Beschreibung des Suchfilters
			$filter = array();
			parse_str($val[3], $filter);
			
			$content .= Search::getSearchDescription($filter);
			
			$content .= '
			</div>
			
			<input type="hidden" name="sval[]" value="'.h($val[3]).'" />
		</div>';
			
			
			$i++;
			
		}
		
	}
	
	$content .= '
			</div>
			<div style="max-width:98%;margin:auto">
				<a onclick="settingsPage.addFoWSearch($(this.parentNode).siblings(\'.fowsearch\'))" style="font-style:italic">+ Eintrag hinzuf&uuml;gen</a>
			</div>
			
			</div>
			<br />
			<div class="center">
				<input type="submit" class="button" value="FoW-Einstellungen speichern" />
			</div>
		</form>
		<div class="ajax center"></div>
	</div>';
	
	$tmpl->script .= '
$(\'.sortable\').sortable({
	items : \'> div\',
	containment : \'parent\',
	forcePlaceholderSize : true,
	revert : 150,
	tolerance : \'pointer\',
	scroll : true,
	distance : 5,
	axis : \'y\'
});';
}


// FoW-Suchfilter bearbeiten
else if($_GET['sp'] == 'fow_editsearch') {
	
	$filter = array();
	
	if(isset($_POST['filter'])) {
		parse_str($_POST['filter'], $filter);
	}
	
	// Suchklasse laden
	General::loadClass('Search');
	
	
	$tmpl->name = 'FoW-Suchfilter konfigurieren';
	
	$tmpl->content = Search::createSearchForm(
		$filter,
		'',
		'return settingsPage.editFoWSearch(this, '.$_GET['target'].')',
		'Speichern'
	);
	
	$tmpl->output();
}

// Suchfilter-Beschreibung ausgeben
else if($_GET['sp'] == 'fow_searchdesc') {
	
	$filter = array();
	
	if(isset($_POST['filter'])) {
		parse_str($_POST['filter'], $filter);
	}
	
	// Suchklasse laden
	General::loadClass('Search');
	
	
	$tmpl->content = Search::getSearchDescription($filter);
	
	$tmpl->output();
}


// FoW-Einstellungen speichern
else if($_GET['sp'] == 'save_fow') {
	// Daten unvollständig
	if(!isset($_POST['scoutval'], $_POST['nextval'])) {
		$tmpl->error = '<br />Daten unvollst&auml;ndig!';
		$tmpl->output();
		die();
	}
	
	// Daten sichern
	$_POST['scoutval'] = (int)$_POST['scoutval'];
	if($_POST['scoutval'] < 0) $_POST['scoutval'] = 0;
	
	$_POST['scoutcount'] = (int)$_POST['scoutcount'];
	if($_POST['scoutcount'] < 1) $_POST['scoutcount'] = 1;
	else if($_POST['scoutcount'] > 10) $_POST['scoutcount'] = 10;
	
	$_POST['nextval'] = (int)$_POST['nextval'];
	if($_POST['nextval'] < 1) $_POST['nextval'] = 1;
	else if($_POST['nextval'] > 10) $_POST['nextval'] = 10;
	
	// neue Einstellungen generieren
	$fow = array();
	
	if(isset($_POST['gate'])) $fow['gate'] = true;
	if(isset($_POST['mgate'])) $fow['mgate'] = true;
	if(isset($_POST['scan'])) $fow['scan'] = true;
	if(isset($_POST['scout'])) {
		$fow['scout'] = $_POST['scoutval'];
		$fow['scoutcount'] = $_POST['scoutcount'];
		$fow['scoutfirst'] = isset($_POST['scoutfirst']);
	}
	if(isset($_POST['next'])) $fow['next'] = $_POST['nextval'];
	
	// Routen und Listen
	if(isset($_POST['routen']) AND $user->rechte['routen']) {
		$fow['routen'] = array();
		foreach($_POST['routen'] as $key=>$val) {
			$val = (int)$val;
			if($val AND !isset($fow['routen'][$val])) {
				$count = isset($_POST['routencount'][$key]) ? (int)$_POST['routencount'][$key] : 1;
				if($count < 1) $count = 1;
				else if($count > 10) $count = 10;
				
				$fow['routen'][$val] = $count;
			}
		}
		
		// alle invalid
		if(!count($fow['routen'])) {
			unset($fow['routen']);
		}
	}
	
	// Suchfilter
	if(isset($_POST['sname'])) {
		
		$fow['search'] = array();
		
		foreach($_POST['sname'] AS $key=>$val) {
			
			// Vollständigkeit
			if(!isset($_POST['scount'][$key], $_POST['sorder'][$key], $_POST['sval'][$key], $_POST['sout'][$key])) {
				continue;
			}
			
			// Daten sichern
			if($_POST['sname'][$key] == '') $_POST['sname'][$key] = '---';
			$_POST['scount'][$key] = (int)$_POST['scount'][$key];
			
			// mindestens 1, höchstens 10
			if($_POST['scount'][$key] < 1) {
				$_POST['scount'][$key] = 1;
			}
			else if($_POST['scount'][$key] > 10) {
				$_POST['scount'][$key] = 10;
			}
			
			$_POST['sorder'][$key] = (int)$_POST['sorder'][$key];
			
			// an das Array anhängen
			$arr = array(
				$_POST['sname'][$key],
				$_POST['scount'][$key],
				$_POST['sorder'][$key],
				$_POST['sval'][$key]
			);
			
			if($_POST['sout'][$key]) {
				$arr[] = 1;
			}
			
			$fow['search'][] = $arr;
			
		}
		
	}
	
	$user->settings['fow'] = json_encode($fow);
	
	$settings = escape(json_encode($user->settings));
	
	// speichern
	query("
		UPDATE
			".PREFIX."user
		SET
			userSettings = '".$settings."'
		WHERE
			user_playerID = ".$user->id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Cache löschen
	$cache->remove('user'.$user->id);
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(20, 'ändert die FoW-Einstellungen');
	}
	
	$tmpl->content = '<br />Die FoW-Einstellungen wurden erfolgreich gespeichert';
	
	// Ausgabe
	$tmpl->output();
}



?>