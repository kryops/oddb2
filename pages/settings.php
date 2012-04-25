<?php
/**
 * pages/settings.php
 * Account-Einstellungen
 * Passwort ändern
 * FoW-Einstellungen
 * Deine Daten
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	$_GET['sp'] = '';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Einstellungen';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'save'=>true,
	'pw'=>true,
	'save_pw'=>true,
	'fow'=>true,
	'save_fow'=>true,
	'dbdata'=>true
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

// Account-Einstellungen speichern
else if($_GET['sp'] == 'save') {
	include './pages/settings/save.php';
}

// Passwort ändern
else if($_GET['sp'] == 'save_pw') {
	include './pages/settings/save_pw.php';
}

// FoW-Einstellungen speichern
else if($_GET['sp'] == 'save_fow') {
	include './pages/settings/fow.php';
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Userdaten abfragen
	$query = query("
		SELECT
			userEmail,
			userICQ
		FROM
			".PREFIX."user
		WHERE
			user_playerID = ".$user->id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$data = mysql_fetch_assoc($query);
	
	// Account-Einstellungen
	$csw->data[''] = array(
		'link'=>'index.php?p=settings',
		'bg'=>'background-image:url(img/layout/csw_settings.png)',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Account-Einstellungen
			</div>
			
			<div class="icontent">
				<form name="settings" onsubmit="return form_send(this, \'index.php?p=settings&amp;sp=save&amp;ajax\', $(this).siblings(\'.ajax\'))">
					<b>generelle Einstellungen</b>
					<br /><br />
					<table class="leftright">
					<tr>
						<td>E-Mail-Adresse</td>
						<td><input type="text" class="text" name="email" value="'.$data['userEmail'].'" /></td>
					</tr>
					<tr>
						<td>ICQ-Nummer</td>
						<td><input type="text" class="text" name="icq" value="'.($data['userICQ'] ? $data['userICQ'] : '').'" /></td>
					</tr>
					<tr>
						<td>Standard-Antrieb</td>
						<td><input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" /></td>
					</tr>
					</table>
					
					<br /><br />
					<b>Datenbank-Oberfl&auml;che</b>
					<br /><br />
						<div style="line-height:30px">
						<input type="checkbox" name="wminoncontent"'.($user->settings['wminoncontent'] ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="wminoncontent">Fenster beim &Ouml;ffnen und Wechseln von Tabs minimieren</span>
						<br />
						<input type="checkbox" name="newtabswitch"'.($user->settings['newtabswitch'] ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="newtabswitch">Beim &Ouml;ffnen eines neuen Tabs auf diesen wechseln</span>
						<br />
						<input type="checkbox" name="winlinknew"'.($user->settings['winlinknew'] ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="winlinknew">Fensterlinks (= Links zu System-, Planeten-, Spieler- und Ally-Ansichten) innerhalb von Fenstern in einem neuen Fenster &ouml;ffnen</span>
						<br />
						<input type="checkbox" name="winlink2tab"'.($user->settings['winlink2tab'] ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="winlink2tab">Fensterlinks wie normale Links behandeln</span>
						<br />
						<input type="checkbox" name="closeontransfer"'.($user->settings['closeontransfer'] ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="closeontransfer">Fenster oder Tab schlie&szlig;en, wenn Inhalt in jeweils anderes Medium transferiert wird</span>
						<br />
						<input type="checkbox" name="szgr"'.($user->settings['szgr'] ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="szgr">Schnellzugriffsleiste standardm&auml;&szlig;ig einblenden</span>
						<br />
						Verhalten der Schnellzugriffsleiste
						&nbsp;<select name="szgrtype" size="1">
							<option value="1">im aktiven Tab &ouml;ffnen</option>
							<option value="2"'.($user->settings['szgrtype'] == 2 ? ' selected="selected"' : '').'>in einem neuen DB-Tab &ouml;ffnen</option>
							<option value="3"'.($user->settings['szgrtype'] == 3 ? ' selected="selected"' : '').'>in einem neuen Browser-Tab &ouml;ffnen</option>
							<option value="5"'.($user->settings['szgrtype'] == 5 ? ' selected="selected"' : '').'>in einem neuen DB-Fenster &ouml;ffnen</option>
						</select>
						<br />
						<input type="checkbox" name="szgrwildcard"'.($user->settings['szgrwildcard'] ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="szgrwildcard">Mit der Schnellzugriffsleiste auch Namen finden, die den eingegebenen Namen enthalten (Name wird zu *Name*)</span>
					</div>
					
					<br /><br />
					<div class="center">
						<input type="submit" class="button" value="Einstellungen speichern" />
					</div>
				</form>
				<div class="ajax center"></div>
			</div>
		'
	);
	
	// Passwort ändern
	$csw->data['pw'] = array(
		'link'=>'index.php?p=settings&sp=pw',
		'bg'=>'background-image:url(img/layout/csw_settings.png);background-position:-150px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Passwort &auml;ndern
			</div>
			
			<div class="icontent">
				<form name="pw" onsubmit="return form_send(this, \'index.php?p=settings&amp;sp=save_pw&amp;ajax\', $(this).siblings(\'.ajax\'))">
					<table class="leftright" style="margin:auto">
					<tr>
						<td>altes Passwort</td>
						<td><input type="password" class="text" name="old" /></td>
					</tr>
					<tr>
						<td>neues Passwort</td>
						<td><input type="password" class="text" name="new1" /></td>
					</tr>
					<tr>
						<td>wiederholen</td>
						<td><input type="password" class="text" name="new2" /></td>
					</tr>
					</table>
					<br />
					<div class="center">
						<input type="submit" class="button" value="Passwort &auml;ndern" />
					</div>
				</form>
				<div class="ajax center"></div>
			</div>
		'
	);
	
	// FoW-Einstellungen
	if($user->rechte['fow']) {
		// Einstellungen ermitteln
		$fow = unserialize($user->settings['fow']);
		
		$csw->data['fow'] = array(
		'link'=>'index.php?p=settings&sp=fow',
		'bg'=>'background-image:url(img/layout/csw_settings.png);background-position:-300px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>''
		);
		
		// Inhalt für FoW-Einstellungen
		if($_GET['sp'] == 'fow') {
			include './pages/settings/fow.php';
		}
		
	}
	
	// Deine Daten
	$csw->data['dbdata'] = array(
		'link'=>'index.php?p=settings&sp=dbdata',
		'bg'=>'background-image:url(img/layout/csw_settings.png);background-position:-450px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>''
	);
	
	// Inhalt für Deine Daten
	if($_GET['sp'] == 'dbdata') {
		// Adressdaten manipulieren
		$_GET['id'] = $user->id;
		$_GET['standalone'] = 1;
		
		include './pages/show_player/dbdata.php';
		
		$tmpl->name = 'Einstellungen';
		$csw->data['dbdata']['content'] = $tmpl->content;
	}
	
	
	// nur Unterseite ausgeben
	if(isset($_GET['switch'])) {
		if(isset($csw->data[$_GET['sp']])) {
			$tmpl->content = $csw->data[$_GET['sp']]['content'];
		}
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
	}
	// keine Berechtigung
	else if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
	}
	$tmpl->output();
	
}
?>