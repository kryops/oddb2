<?php
/**
 * pages/admin/settings_insertplayer.php
 * Verwaltung -> Einstellungen -> Spielerprofile einlesen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// keine Berechtigung
if(!$user->rechte['verwaltung_settings']) $tmpl->error = 'Du hast keine Berechtigung!';
// keine Daten
else if(!isset($_POST['start'], $_POST['end'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// alles OK
else {
	// Daten sichern
	$_POST['start'] = (int)$_POST['start'];
	$_POST['end'] = (int)$_POST['end'];
	
	if(isset($_POST['state'])) {
		$_POST['state'] = (int)$_POST['state'];
		if(!$_POST['state'] OR $_POST['state'] < $_POST['start'] OR $_POST['state'] > $_POST['end']) {
			$tmpl->error = 'Fehler aufgetreten!';
		}
	}
	
	if($tmpl->error) {}
	// ID ungültig
	else if($_POST['start'] <= 2 OR $_POST['end'] <= 2 OR $_POST['end'] <= $_POST['start']) {
		$tmpl->error = 'Ung&uuml;ltigen ID-Bereich eingegeben!';
	}
	else {
		// weitermachen
		if(isset($_POST['state'])) {
			$state = $_POST['state'];
			$time = (int)$_POST['time'];
			$fail = (int)$_POST['fail'];
			
			$i = 0;
			
			while($state <= $_POST['end']) {
				$query = query("
					SELECT
						playerID
					FROM
						".GLOBPREFIX."player
					WHERE
						playerID = ".$state."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				if(!mysql_num_rows($query)) {
					$success = odrequest($state, false, 2000);
					
					// fehlgeschlagene Requests zurücksetzen
					if($success) {
						$fail = 0;
					}
					// fehlgeschlagene Requests erhöhen
					else {
						$fail++;
					}
					
					$i++;
				}
				
				$state++;
				
				// unterbrechen, Statusupdate und 2 Sekunden Wartezeit
				if($i >= $gconfig['odrequest_max']*2) {
					break;
				}
			}
			
			// Balken berechnen
			$maxwidth = 300;
			$width = round(($state-1-$_POST['start'])/($_POST['end']-$_POST['start'])*$maxwidth);
			if($width > $maxwidth) {
				$width = $maxwidth;
			}
			else if($width < 0) {
				$width = 0;
			}
			
			// OD-Lag-Schutz
			if($cache->getglobal('odrequest_lag') AND $state < $_POST['end']) {
				$tmpl->script = '
$(\'#balken'.$time.' > .balkenfill\').css(\'width\', \''.$width.'px\');
$(\'#insertplayer'.$time.'\').html(\'OD ist zurzeit nicht erreichbar oder &uuml;berlastet. Bitte versuche es sp&auml;ter erneut.<br /><a class="hint" onclick="ajaxcall(\\\'index.php?p=admin&sp=settings_insertplayer\\\', false, {\\\'start\\\':'.$_POST['start'].',\\\'end\\\':'.$_POST['end'].',\\\'state\\\':'.$state.',\\\'time\\\':'.$time.',\\\'fail\\\':'.$fail.'}, false);">[weitermachen]</a>\');';
			}
			// Außerhalb des Spieler-Bereichs
			else if($fail > 500) {
				$tmpl->script = '
$(\'#balken'.$time.' > .balkenfill\').css(\'width\', \''.$width.'px\');
$(\'#insertplayer'.$time.'\').html(\'Es konnten 500 Spielerprofile am St&uuml;ck nicht eingelesen werden. Du scheinst au&szlig;erhalb des Bereichs der angemeldeten Spieler zu sein!\');';
			}
			// weitermachen
			else if($state < $_POST['end']) {
				$tmpl->script = '
$(\'#balken'.$time.' > .balkenfill\').css(\'width\', \''.$width.'px\');
$(\'#insertplayer'.$time.'\').html(\'Spieler bis ID '.($state-1).' eingetragen <img src="img/layout/ajax.gif" alt="" style="width:16px;height:16px"/>\');
window.setTimeout(function() {
	ajaxcall(\'index.php?p=admin&sp=settings_insertplayer\', false, {\'start\':'.$_POST['start'].',\'end\':'.$_POST['end'].',\'state\':'.$state.',\'time\':'.$time.',\'fail\':'.$fail.'}, false);
}, 2000);';
			}
			// fertig
			else {
				$tmpl->script = '
$(\'#balken'.$time.' > .balkenfill\').css(\'width\', \''.$maxwidth.'px\');
$(\'#insertplayer'.$time.'\').html(\'Vorgang abgeschlossen\');';
			}
		}
		// starten
		else {
			$time = time();
			
			$tmpl->content .= '
				<br /><br />
				<div class="balken" id="balken'.$time.'" style="width:300px;height:15px;margin:auto">
				<div class="balkenfill" style="width:0px"></div>
			</div>
				<br />
				<span id="insertplayer'.$time.'">Starte Einlesen... <img src="img/layout/ajax.gif" alt="" style="width:16px;height:16px"/></span>
				<br /><br />
			';
			
			// Formular deaktivieren
			$tmpl->script = '$(\'form[name="settings_insertplayer"] input\').attr(\'disabled\', \'disabled\');
$(\'form[name="settings_insertplayer"] .button\').replaceWith(\'einlesen\');
ajaxcall(\'index.php?p=admin&sp=settings_insertplayer\', false, {\'start\':'.$_POST['start'].',\'end\':'.$_POST['end'].',\'state\':'.$_POST['start'].',\'time\':'.$time.',\'fail\':0}, false);';
			
			// Log-Eintrag
			if($config['logging'] >= 1) {
				insertlog(25, 'liest die Spielerprofile von '.$_POST['start'].' bis '.$_POST['end'].' ein');
			}
		}
	}
}

// Ausgabe
if($tmpl->error) {
	$tmpl->error = '<br />'.$tmpl->error;
}

$tmpl->output();


?>