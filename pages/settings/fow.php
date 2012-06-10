<?php
/**
 * pages/settings/fow.php
 * FoW-Einstellungen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


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
		<input type="checkbox" name="mgate"'.(isset($fow['mgate']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="mgate">n&auml;chstes Myrigate anzeigen</span>
		<br />';
	}
	
	$content .= '
		<br />
		<b>Systeme</b>
		<br />
		<input type="checkbox" name="scan"'.(isset($fow['scan']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="scan">nicht erfasste Systeme und veraltete Allianz-Systeme anzeigen</span>
		<br />
		<input type="checkbox" name="scout"'.(isset($fow['scout']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="scout">die</span> &nbsp;<input type="text" class="smalltext" name="scoutcount" value="'.(isset($fow['scoutcount']) ? $fow['scoutcount'] : '1').'" /> <span class="togglecheckbox" data-name="scout"> &nbsp;n&auml;chsten Scout-Systeme anzeigen &auml;lter als</span> &nbsp;<input type="text" class="smalltext" name="scoutval" value="'.(isset($fow['scout']) ? $fow['scout'] : $config['scan_veraltet']).'" /> &nbsp;<span class="togglecheckbox" data-name="scout">Tage</span>
		&nbsp; &nbsp;(<input type="checkbox" name="scoutfirst"'.((isset($fow['scoutfirst']) AND $fow['scoutfirst']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="scoutfirst">nicht gescannte immer zuerst</span>)
		<br />
		<input type="checkbox" name="next"'.(isset($fow['next']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="next">die n&auml;chsten</span> &nbsp;<input type="text" class="smalltext" name="nextval" value="'.(isset($fow['next']) ? $fow['next'] : '1').'" /> &nbsp;<span class="togglecheckbox" data-name="next">Systeme anzeigen</span> &nbsp;
		<span class="small hint">(max. 10)</span>
		<br /><br />';
	
	// Ressplaneten
	$content .= '
		<b>Eingetragene Ressplaneten</b>
		<br />
		<div class="fcbox fowress sortable" style="padding:8px 12px;max-width:98%;line-height:35px">';
	
	if(!isset($fow['ress'])) {
		$fow['ress'] = array();
	}
	
	foreach($fow['ress'] as $val) {
		$content .= '<div><div onclick="$(this.parentNode).remove()" title="Eintrag l&ouml;schen" class="closebutton"></div>Name: <input type="text" name="ressname[]" class="text" value="'.h($val[0]).'"> &nbsp; - &nbsp; n&auml;chster Ressplanet &nbsp;<select size="1" name="resstyp[]"><option value="1">von dir selbst</option>'.($user->rechte['ressplani_ally'] ? '<option value="2"'.($val[1] == 2 ? ' selected="selected"' : '').'>deiner Allianz</option>' : '').($user->rechte['ressplani_meta'] ? '<option value="3"'.($val[1] == 3 ? ' selected="selected"' : '').'>deiner Meta</option>' : '').'</select> &nbsp; Mindestmenge <select name="ressfilter[]" size="1"><option value="0">gesamt</option><option value="1"'.($val[2] == 1 ? ' selected="selected"' : '').'>Erz</option><option value="2"'.($val[2] == 2 ? ' selected="selected"' : '').'>Metall</option><option value="3"'.($val[2] == 3 ? ' selected="selected"' : '').'>Wolfram</option><option value="4"'.($val[2] == 4 ? ' selected="selected"' : '').'>Kristall</option><option value="5"'.($val[2] == 5 ? ' selected="selected"' : '').'>Fluor</option></select> &nbsp;<input type="text" style="width:80px" name="ressmenge[]" class="smalltext" value="'.($val[3] ? h($val[3]) : '').'" /></div>';
	}
	
	$content .= '
		</div>
		<div style="max-width:98%;margin:auto">
			<a onclick="$(this.parentNode).siblings(\'.fowress\').append(\'<div><div onclick=&quot;$(this.parentNode).remove()&quot; title=&quot;Eintrag l&ouml;schen&quot; class=&quot;closebutton&quot;></div>Name: <input type=&quot;text&quot; value=&quot;&quot; name=&quot;ressname[]&quot; class=&quot;text&quot;> &nbsp; - &nbsp; n&auml;chster Ressplanet &nbsp;<select size=&quot;1&quot; name=&quot;resstyp[]&quot;><option value=&quot;1&quot;>von dir selbst</option>'.($user->rechte['ressplani_ally'] ? '<option value=&quot;2&quot;>deiner Allianz</option>' : '').($user->rechte['ressplani_meta'] ? '<option value=&quot;3&quot;>deiner Meta</option>' : '').'</select> &nbsp; Mindestmenge <select name=&quot;ressfilter[]&quot; size=&quot;1&quot;><option value=&quot;0&quot;>gesamt</option><option value=&quot;1&quot;>Erz</option><option value=&quot;2&quot;>Metall</option><option value=&quot;3&quot;>Wolfram</option><option value=&quot;4&quot;>Kristall</option><option value=&quot;5&quot;>Fluor</option></select> &nbsp;<input type=&quot;text&quot; value=&quot;&quot; style=&quot;width:80px&quot; name=&quot;ressmenge[]&quot; class=&quot;smalltext&quot; /></div>\')" style="font-style:italic">+ Eintrag hinzuf&uuml;gen</a>
		</div>
		<br />';
	
	// Routenbereich
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
	
	$content .= '
		<b>benutzerdefinierter Bereich - Planeten von Spielern/Allianzen</b>
		<div class="fcbox fowudef sortable" style="padding:8px 12px;max-width:98%;line-height:35px">';
	// benutzerdefinierte Einträge vorhanden
	if(isset($fow['udef'])) {
		foreach($fow['udef'] as $val) {
			/*
			0 - searchname
			1 - search
			2 - searchtyp
			3 - searchid
			4 - outersys
			*/
			$content .= '
		<div>
			<div class="closebutton" title="Eintrag l&ouml;schen" onclick="$(this.parentNode).remove()"></div>
			Name:
			<input type="text" class="text" name="searchname[]" value="'.htmlspecialchars($val[0], ENT_COMPAT, 'UTF-8').'" />
			&nbsp; - &nbsp;
			<select name="search[]" size="1">
				<option value="0">n&auml;chster</option>
				<option value="1"'.($val[1] == 1 ? ' selected="selected"' : '').'>entferntester</option>
			</select>&nbsp;
			Planet von&nbsp;
			<select name="searchtyp[]" size="1" onchange="if(this.value > 4 && this.value < 7){$(this).siblings(\'.searchid\').css(\'display\', \'inline\').focus()}else{$(this).siblings(\'.searchid\').css(\'display\', \'none\')}">
				<option value="1">dir selbst</option>
				<option value="2"'.($val[2] == 2 ? ' selected="selected"' : '').'>deiner Allianz</option>
				<option value="3"'.($val[2] == 3 ? ' selected="selected"' : '').'>deiner Meta</option>
				<option value="4"'.($val[2] == 4 ? ' selected="selected"' : '').'>feindlichen Allianzen</option>
				<option value="5"'.($val[2] == 5 ? ' selected="selected"' : '').'>dem Spieler</option>
				<option value="6"'.($val[2] == 6 ? ' selected="selected"' : '').'>der Allianz</option>
				<option value="7"'.($val[2] == 7 ? ' selected="selected"' : '').'>einem Seze Lux</option>
				<option value="8"'.($val[2] == 8 ? ' selected="selected"' : '').'>einem Altrassen-Spieler</option>
			</select>&nbsp;
			<input type="text" class="smalltext searchid" name="searchid[]" style="width:120px'.($val[2] >= 5 ? '' : ';display:none').'" value="'.htmlspecialchars($val[3], ENT_COMPAT, 'UTF-8').'" />
		</div>';
		}
	}
	$content .= '</div>
				<div style="max-width:98%;margin:auto">
					<div style="float:right" class="small hint">(Tags und Namen werden beim Speichern in IDs umgewandelt)</div>
					<a onclick="$(this.parentNode).siblings(\'.fowudef\').append(\'<div><div class=&quot;closebutton&quot; title=&quot;Eintrag l&ouml;schen&quot; onclick=&quot;$(this.parentNode).remove()&quot;></div>Name: <input type=&quot;text&quot; class=&quot;text&quot; name=&quot;searchname[]&quot; value=&quot;&quot; /> &nbsp; - &nbsp; <select name=&quot;search[]&quot; size=&quot;1&quot;><option value=&quot;0&quot;>n&auml;chster</option><option value=&quot;1&quot;>entferntester</option></select>&nbsp; Planet von &nbsp;<select name=&quot;searchtyp[]&quot; size=&quot;1&quot; onchange=&quot;if(this.value > 4 && this.value < 7){$(this).siblings(\\\'.searchid\\\').css(\\\'display\\\', \\\'inline\\\').focus()}else{$(this).siblings(\\\'.searchid\\\').css(\\\'display\\\', \\\'none\\\')}&quot;><option value=&quot;1&quot;>dir selbst</option><option value=&quot;2&quot;>deiner Allianz</option><option value=&quot;3&quot;>deiner Meta</option><option value=&quot;4&quot;>feindlichen Allianzen</option><option value=&quot;5&quot;>dem Spieler</option><option value=&quot;6&quot;>der Allianz</option><option value=&quot;7&quot;>einem Seze Lux</option><option value=&quot;8&quot;>einem Altrassen-Spieler</option></select> &nbsp;<input type=&quot;text&quot; class=&quot;smalltext searchid&quot; name=&quot;searchid[]&quot; style=&quot;width:120px;display:none&quot; value=&quot;&quot; /></div>\')" style="font-style:italic">+ Eintrag hinzuf&uuml;gen</a>
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
	if($_POST['scoutval'] < 1) $_POST['scoutval'] = 1;
	
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
	
	// Ressplaneten
	if(isset($_POST['ressname'])) {
		$fow['ress'] = array();
		
		
		
		foreach($_POST['ressname'] as $key=>$val) {
			// Existenz
			if(!isset($_POST['resstyp'][$key], $_POST['ressfilter'][$key], $_POST['ressmenge'][$key])) {
				continue;
			}
			
			// Sicherung
			$_POST['resstyp'][$key] = (int)$_POST['resstyp'][$key];
			$_POST['ressfilter'][$key] = (int)$_POST['ressfilter'][$key];
			$_POST['ressmenge'][$key] = (int)$_POST['ressmenge'][$key];
			
			// Validierung
			if($_POST['resstyp'][$key] < 1 OR $_POST['resstyp'][$key] > 3) {
				continue;
			}
			if($_POST['ressfilter'][$key] < 0 OR $_POST['ressfilter'][$key] > 5) {
				continue;
			}
			
			if($_POST['ressmenge'][$key] < 0) {
				$_POST['ressmenge'][$key] = 0;
			}
			
			if(trim($val) == '') {
				$_POST['ressname'][$key] = '---';
			}
			
			// an das Array hängen
			$fow['ress'][] = array(
				$_POST['ressname'][$key],
				$_POST['resstyp'][$key],
				$_POST['ressfilter'][$key],
				$_POST['ressmenge'][$key]
			);
		}
		
		// alle invalid
		if(!count($fow['ress'])) {
			unset($fow['ress']);
		}
	}
	
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
	
	// benutzerdefinierte Einträge
	if(isset($_POST['search'])) {
		$fow['udef'] = array();
		
		foreach($_POST['search'] AS $key=>$val) {
			// Vollständigkeit
			if(!isset($_POST['searchname'][$key], $_POST['searchtyp'][$key], $_POST['searchid'][$key])) {
				continue;
			}
			// Daten sichern
			if($_POST['searchname'][$key] == '') $_POST['searchname'][$key] = '---';
			$_POST['search'][$key] = (int)$_POST['search'][$key];
			$_POST['searchtyp'][$key] = (int)$_POST['searchtyp'][$key];	
			if(!$_POST['searchtyp'][$key] OR $_POST['searchtyp'][$key] > 8) {
				continue;
			}
			if(is_numeric(trim($_POST['searchid'][$key]))) {
				$_POST['searchid'][$key] = (int)$_POST['searchid'][$key];
			}
			
			// Namen in IDs umwandeln
			
			// bei Typen ohne Eingabe und numerischer Eingabe nichts ändern
			if($_POST['searchtyp'][$key] < 5 OR $_POST['searchtyp'][$key] > 6 OR is_numeric($_POST['searchid'][$key])) {}
			// IDs mit Komma getrennt
			else if(preg_replace('/[\d,\s]/Uis', '', $_POST['searchid'][$key]) == '') {
				$sid = explode(',', $_POST['searchid'][$key]);
				$_POST['searchid'][$key] = array();
				foreach($sid as $val) {
					$val = (int)$val;
					if($val > 0) $_POST['searchid'][$key][] = $val;
				}
				// nur Kommas
				if(!count($_POST['searchid'][$key])) {
					continue;
				}
				$_POST['searchid'][$key] = implode(',', $_POST['searchid'][$key]);
			}
			// Spieler -> Username
			else if($_POST['searchtyp'][$key] == 5) {
				$query = query("
					SELECT
						playerID
					FROM
						".GLOBPREFIX."player
					WHERE
						playerName LIKE '".escape($_POST['searchid'][$key])."'
					ORDER BY
						playerID ASC
					LIMIT 1
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Spieler nicht gefunden
				if(!mysql_num_rows($query)) {
					continue;
				}
				
				$data = mysql_fetch_assoc($query);
				$_POST['searchid'][$key] = $data['playerID'];
			}
			// Allianz -> Tag / Name
			else if($_POST['searchtyp'][$key] == 6) {
				$_POST['searchid'][$key] = str_replace('*', '%', escape($_POST['searchid'][$key]));
				$query = query("
					SELECT
						allianzenID
					FROM
						".GLOBPREFIX."allianzen
					WHERE
						allianzenTag LIKE '".$_POST['searchid'][$key]."'
						OR allianzenName LIKE '".$_POST['searchid'][$key]."'
					ORDER BY
						allianzenID ASC
					LIMIT 1
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// Allianz nicht gefunden
				if(!mysql_num_rows($query)) {
					continue;
				}
				
				$data = mysql_fetch_assoc($query);
				$_POST['searchid'][$key] = $data['allianzenID'];
			}
			
			// an das Array anhängen
			$fow['udef'][] = array(
				$_POST['searchname'][$key],
				$_POST['search'][$key],
				$_POST['searchtyp'][$key],
				$_POST['searchid'][$key]
			);
		}
		
		$udefc = '';
		
		// alle invalid
		if(!count($fow['udef'])) {
			unset($fow['udef']);
			
		}
		// Inhalt der benutzerdefiniert-Box ändern
		else {
			foreach($fow['udef'] as $val) {
				$udefc .= '<div><div class="closebutton" title="Eintrag l&ouml;schen" onclick="$(this.parentNode).remove()"></div>Name: <input type="text" class="text" name="searchname[]" value="'.htmlspecialchars($val[0], ENT_COMPAT, 'UTF-8').'" /> &nbsp; - &nbsp; <select name="search[]" size="1"><option value="0">n&auml;chster</option><option value="1"'.($val[1] == 1 ? ' selected="selected"' : '').'>entferntester</option></select>&nbsp; Planet von &nbsp;<select name="searchtyp[]" size="1" onchange="if(this.value > 4){$(this).siblings(\'.searchid\').css(\'display\', \'inline\').focus()}else{$(this).siblings(\'.searchid\').css(\'display\', \'none\')}"><option value="1">dir selbst</option><option value="2"'.($val[2] == 2 ? ' selected="selected"' : '').'>deiner Allianz</option><option value="3"'.($val[2] == 3 ? ' selected="selected"' : '').'>deiner Meta</option><option value="4"'.($val[2] == 4 ? ' selected="selected"' : '').'>feindlichen Allianzen</option><option value="5"'.($val[2] == 5 ? ' selected="selected"' : '').'>dem Spieler</option><option value="6"'.($val[2] == 6 ? ' selected="selected"' : '').'>der Allianz</option><option value="7"'.($val[2] == 7 ? ' selected="selected"' : '').'>einem Seze Lux</option><option value="8"'.($val[2] == 8 ? ' selected="selected"' : '').'>einem Altrassen-Spieler</option></select>&nbsp; <input type="text" class="smalltext searchid" name="searchid[]" style="width:120px'.(($val[2] >= 5 AND $val[2] <= 6) ? '' : ';display:none').'" value="'.htmlspecialchars($val[3], ENT_COMPAT, 'UTF-8').'" /></div>';
			}
		}
		
		$tmpl->script = '$(\'.fowudef\').html(\''.str_replace("'", "\\'", $udefc).'\')';
	}
	
	$user->settings['fow'] = serialize($fow);
	
	$settings = escape(serialize($user->settings));
	
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