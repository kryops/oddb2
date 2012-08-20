<?php
error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template_admin;
$tmpl->name = 'Einstellungen';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'save'=>true
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Funktionen
 */

else if($_GET['sp'] == 'save') {
	
	$errors = array();
	
	// MySQL-Fehler
	if(!@mysql_connect($_POST['mysql_host'], $_POST['mysql_user'], $_POST['mysql_pw'])) {
		$errors[] = 'Verbindung zum MySQL-Server konnte nicht hergestellt werden: '.mysql_error();
	}
	else if(!@mysql_select_db($_POST['mysql_db'])) {
		$errors[] = 'MySQL-Datenbank kann nicht ausgew&auml;hlt werden: '.mysql_error();
	}

	// Präfix ungültig
	if(preg_replace('/[a-zA-Z0-9\-_]/', '', $_POST['mysql_globprefix']) != '') {
		$errors[] = 'Ung&uuml;ltiger globaler Tabellenpr&auml;fix!';
	}
	
	

	// Kein Key
	if($_POST['key'] == '') {
		$errors[] = 'Kein Sicherheitsschl&uuml;ssel eingegeben!';
	}
	else if(preg_replace('/[a-zA-Z0-9\-_]/', '', $_POST['key']) != '') {
		$errors[] = 'Der Sicherheitsschl&uuml;ssel darf nur Buchstaben und Zahlen beinhalten!';
	}
	
	
	// unterschiedliche Passwörter
	if($_POST['passwort'] != $_POST['passwort2']) {
		$errors[] = 'Die Passwörter sind unterschiedlich!';
	}
	// Key-Änderung, aber kein Passwort
	else if($_POST['key'] != $config['key'] AND trim($_POST['passwort']) == '') {
		$errors[] = 'Bei &Auml;nderung des Keys muss das Passwort ebenfalls ge&auml;ndert werden!';
	}
	
	
	// Fehler aufgetreten -> abbrechen
	if(count($errors)) {
		
		$tmpl->content = '<div class="error center">'.implode('<br /><br />', $errors).'</div>';
		
	}
	// speichern
	else {
		
		$c = $_POST;
		
		// Passwort ändern
		if(trim($c['passwort']) == '') {
			unset($c['passwort']);
		}
		else {
			$c['passwort'] = General::encryptPassword($c['passwort'], $c['key']);
		}
		
		
		General::loadClass('config');
		
		config::saveGlobal('global', 'config', $c, true);
		
		$tmpl->content = 'Die Einstellungen wurden gespeichert.';
		
	}
	
	
	// ausgeben
	$tmpl->output();
	
}

/**
 * Seite
 */

else {
	$tmpl->content = '

<div class="icontent">

<form onsubmit="form_send(this, \'index.php?p=settings&amp;sp=save\', $(this).siblings(\'.ajax\'));return false">
<table class="leftright" style="width:100%">
<tr>
<th colspan="2">Grundeinstellungen</th>
</tr>
<tr>
<td style="width:45%">vollst&auml;ndige Adresse (mit /)</td>
<td><input type="text" class="text" name="addr" value="'.h($config['addr']).'" /></td>
</tr>
<tr>
<td>Server (ohne /)</td>
<td><input type="text" class="text" name="server" value="'.h($config['server']).'" /></td>
</tr>
<tr>
<td>Sicherheits-Schl&uuml;ssel</td>
<td><input type="text" class="text" name="key" value="'.h($config['key']).'" /> <br /><span class="small hint">(f&uuml;r die Cronjobs; bitte nur Buchstaben und Zahlen.<br />Bei &Auml;nderung muss auch das Passwort ge&auml;ndert werden!)</span></td>
</tr>
<tr>
<td>Passwort &auml;ndern</td>
<td><input type="password" class="text" name="passwort" value="" /></td>
</tr>
<tr>
<td class="italic">(wiederholen)</td>
<td><input type="password" class="text" name="passwort2" value="" /></td>
</tr>
<tr>
<td>Kontaktdaten des Administrators<br />(Impressum, HTML m&ouml;glich)</td>
<td><textarea name="impressum" style="width:300px;height:60px">'.h($config['impressum']).'</textarea></td>
</tr>
<tr>
<td>HTML-Code, der immer eingebunden werden soll<br />(Werbung, Counter...)</td>
<td><textarea name="adcode" style="width:300px;height:60px">'.h($config['adcode']).'</textarea></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th colspan="2">MySQL</th>
</tr>
<tr>
<td>MySQL-Server</td>
<td><input type="text" class="text" name="mysql_host" value="'.h($config['mysql_host']).'" /></td>
</tr>
<tr>
<td>MySQL-Benutzer</td>
<td><input type="text" class="text" name="mysql_user" value="'.h($config['mysql_user']).'" /></td>
</tr>
<tr>
<td>MySQL-Passwort</td>
<td><input type="password" class="text" name="mysql_pw" value="'.h($config['mysql_pw']).'" /></td>
</tr>
<tr>
<td>MySQL-Datenbank</td>
<td><input type="text" class="text" name="mysql_db" value="'.h($config['mysql_db']).'" /></td>
</tr>
<tr>
<td>MySQL-Tabellenpr&auml;fix</td>
<td><input type="text" class="text" name="mysql_globprefix" value="'.h($config['mysql_globprefix']).'" /></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th colspan="2">Caching</th>
</tr>
<tr>
<td>Cache-Typ</td>
<td><select name="caching" size="1" class="cache_option">
<option value="0">deaktiviert</option>
'.(function_exists('apc_fetch') ? '<option value="1"'.($config['caching'] == 1 ? ' selected="selected"' : '').'>APC</option>' : '').'
'.(class_exists('Memcache') ? '<option value="2"'.($config['caching'] == 2 ? ' selected="selected"' : '').'>memcached</option>' : '').'
</select></td>
</tr>
<tr class="show_cache">
<td>Cache-Pr&auml;fix</td>
<td><input type="text" class="text" name="caching_prefix" value="'.h($config['caching_prefix']).'" /></td>
</tr>
<tr class="show_cache show_memcached">
<td>memcached-Server</td>
<td><input type="text" class="text" name="memcached_host" value="'.h($config['memcached_host']).'" /></td>
</tr>
<tr class="show_cache show_memcached">
<td>memcached-Port</td>
<td><input type="text" class="text" name="memcached_port" value="'.h($config['memcached_port']).'" /></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th colspan="2">Sicherheit</th>
</tr>
<tr>
<td>IP-Ban Versuche (0 = deaktiviert)</td>
<td><input type="text" class="smalltext" name="ipban" value="'.h($config['ipban']).'" /></td>
</tr>
<tr>
<td>IP-Ban Bannzeit (Minuten)</td>
<td><input type="text" class="smalltext tooltip" name="ipban_time" value="'.h($config['ipban_time']).'"/></td>
</tr>
<tr>
<td>Flooding-Schutz aktiv</td>
<td><select name="flooding" size="1">
<option value="0">nein</option>
<option value="1"'.($config['flooding'] ? ' selected="selected"' : '').'>ja</option>
</select>
</td>
</tr>
<tr>
<td>Flooding-Zeit (Sekunden)</td>
<td><input type="text" class="smalltext" name="flooding_time" value="'.h($config['flooding_time']).'" /></td>
</tr>
<tr>
<td>max. Seiten in der Flooding-Zeit</td>
<td><input type="text" class="smalltext" name="flooding_pages" value="'.h($config['flooding_pages']).'" /></td>
</tr>
</table>

<br /><br />
<div class="center">
<input type="submit" class="button" value="Einstellungen speichern" style="width:150px" />
</div>
</form>

<br />

<div class="ajax center"></div>

</div>
	
	';
	
	// Ausgabe
	$tmpl->output();
}

?>