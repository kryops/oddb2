<?php

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';


// Instanz bearbeiten - absenden
else if($_GET['sp'] == 'edit_send') {
	// Vollständigkeit
	if(!isset($_GET['id']) OR !(int)$_GET['id']) {
		$tmpl->error = 'Keine oder ung&uuml;ltige ID &uuml;bergeben!';
	}
	// Vorhandensein
	if(!isset($dbs[$_GET['id']])) {
		$tmpl->error = 'Die Datenbank existiert nicht mehr!';
	}
	// Kein Name eingegeben
	if(count($dbs) > 1 AND (!isset($_POST['name']) OR trim($_POST['name']) == '')) {
		$tmpl->error = 'Kein Name eingegeben!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Daten aufbereiten
		$config = array();
		$rechte = array(
			0=>array(),
			1=>array(),
			2=>array(),
			3=>array(),
			4=>array()
		);
		
		@include('../config/config'.$_GET['id'].'.php');
		
		foreach($_POST as $key=>$val) {
			if($val === '') {
				unset($_POST[$key]);
			}
		}
		
		// Schlüssel erzeugen
		$_POST['instancekey'] = $config['instancekey'];
		
		$_POST['active'] = isset($_POST['active']);
		
		if($_POST['active']) {
			unset($_POST['active']);
		}
		
		
		$name = '';
		if(isset($_POST['name'])) {
			$name = $_POST['name'];
			$dbs[$_GET['id']] = $name;
			unset($_POST['name']);
		}
		
		if(isset($_POST['disable_freischaltung'])) {
			$_POST['disable_freischaltung'] = (bool)$_POST['disable_freischaltung'];
		}
		
		if(isset($_POST['disable_freischaltung_level'])) {
			if($_POST['disable_freischaltung_level'] === '' OR !isset($rechte[$_POST['disable_freischaltung_level']])) {
				unset($_POST['disable_freischaltung_level']);
			}
			else $_POST['disable_freischaltung_level'] = (int)$_POST['disable_freischaltung_level'];
		}
		
		if(isset($_POST['logging'])) {
			$_POST['logging'] = (int)$_POST['logging'];
			if($_POST['logging'] < 0 OR $_POST['logging'] > 3) {
				unset($_POST['logging']);
			}
		}
		
		if(isset($_POST['logging_time'])) {
			$_POST['logging_time'] = (int)$_POST['logging_time'];
			if($_POST['logging_time'] < 1) {
				unset($_POST['logging_time']);
			}
		}
		
		
		General::loadClass('config');
			
		// Instanz-Konfiguration speichern
		if(!config::save($_GET['id'], $_POST, false)) {
			$tmpl->error .= 'Konnte Konfiguration nicht speichern!';
		}
		// DB-Array speichern
		else if(!config::saveGlobal('dbs', 'dbs', $dbs)) {
			$tmpl->error .= 'Konnte Liste der Instanzen nicht speichern!';
		}
		// erfolgreich
		else {
			$tmpl->content = 'Die Konfiguration wurde erfolgreich gespeichert.';
			
			// Cache leeren
			admincache_clear();
			
			// Tabelle aktualisieren
			$c = $gconfig;
			foreach($config as $key=>$val) {
				$c[$key] = $val;
			}
			foreach($_POST as $key=>$val) {
				$c[$key] = $val;
			}
			
			$instance = $_GET['id'];
			$instance_name = $name;
			
			// Tabelle aktualisieren
			$tmpl->script = 'ajaxcall(\'index.php?p=dbs&list&ajax\', $(\'.icontentdblist\'), false, true);';
		}
	}
	
	// ausgeben
	$tmpl->output();
}



// Instanz bearbeiten
else if($_GET['sp'] == 'edit') {
	$tmpl->name = 'Datenbank bearbeiten';
	
	// Vollständigkeit
	if(!isset($_GET['id']) OR !(int)$_GET['id']) {
		$tmpl->error = 'Keine oder ung&uuml;ltige ID &uuml;bergeben!';
	}
	// Vorhandensein
	if(!isset($dbs[$_GET['id']])) {
		$tmpl->error = 'Die Datenbank existiert nicht mehr!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		$tmpl->name = 'Datenbank '.$_GET['id'].' bearbeiten';
		
		$tmpl->content = '<div class="icontent dbedit'.$_GET['id'].'">';
		
		
		// Konfiguration einbinden
		$config = array();
		
		if(!(@include('../config/config'.$_GET['id'].'.php'))) {
			$tmpl->content .= '<b>Konnte die Konfigurationsdatei nicht lesen! Beim Speichern wird eine neue Datei erstellt.</b><br /><br />';
		}
		
		$newconfig = $config;
		
		$config = array_merge($gconfig, $newconfig);
		
		// nicht besetze Werte mit leeren Strings füllen
		$config2 = $gconfig;
		foreach($config2 as $key=>$val) {
			$config2[$key] = '';
		}
		$config2 = array_merge($config2, $newconfig);
		
		
		$tmpl->content .= 'Die eingeklammerten Werte hinter den Eingabefeldern sind die Standardwerte.
		<br />
		L&auml;sst du diese Felder leer, nimmt die Konfiguration die eingeklammerten Werte an.
		<br /><br />
		<form onsubmit="return false">
		<table class="leftright" style="width:100%">
		<tr>
			<th colspan="2">Grundeinstellungen</th>
		</tr>
		<tr>
			<td>Name</td>
			<td><input type="text" class="text" name="name" value="'.htmlspecialchars($dbs[$_GET['id']], ENT_COMPAT, 'UTF-8').'" /></td>
		</tr>
		<tr>
			<td>aktiv</td>
			<td><input type="checkbox" name="active"'.($config['active'] ? ' checked="checked"' : '').' /></td>
		</tr>
		<tr>
			<td>inaktiv-Nachricht</td>
			<td><input type="text" class="text tooltip" style="width:300px" name="offlinemsg" tooltip="wird angezeigt, wenn die Datenbank auf inaktiv gesetzt ist" value="'.htmlspecialchars($config2['offlinemsg'], ENT_COMPAT, 'UTF-8').'" />
			'.($gconfig['offlinemsg'] ? '<br /><span class="small hint">('.htmlspecialchars($gconfig['offlinemsg'], ENT_COMPAT, 'UTF-8').')</span>' : '').'</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<th colspan="2">Sicherheit</th>
		</tr>
		<tr>
			<td>Spieler automatisch freischalten</td>
			<td><select name="disable_freischaltung" size="1">
			<option value=""></option>
			<option value="0"'.($config2['disable_freischaltung'] === false ? ' selected="selected"' : '').'>nein</option>
			<option value="1"'.($config2['disable_freischaltung'] ? ' selected="selected"' : '').'>ja</option>
			</select> <span class="small hint">('.($gconfig['disable_freischaltung'] ? 'ja' : 'nein').')</span></td>
		</tr>
		<tr>
			<td>Autofreischaltung Rechtelevel</td>
			<td><select name="disable_freischaltung_level" size="1">
			<option value=""></option>';
		foreach($rechte as $key=>$data) {
			$tmpl->content .= '
			<option value="'.$key.'"'.(($config2['disable_freischaltung_level'] !== '' AND $config2['disable_freischaltung_level'] == $key) ? ' selected="selected"' : '').'>'.htmlspecialchars($data['name'], ENT_COMPAT, 'UTF-8').'</option>';
		}
		$tmpl->content .= '
			</select> <span class="small hint">('.htmlspecialchars($rechte[$gconfig['disable_freischaltung_level']]['name'], ENT_COMPAT, 'UTF-8').')</span></td>
		</tr>
		<tr>
			<td>Logging-Stufe</td>
			<td><select name="logging" size="1" class="tooltip" tooltip="[vorsichtig] loggt alle Aktionen eines Spielers, [paranoid] loggt zus&auml;tzlich alle Seiten, die ein Spieler ansieht (z.B. Invasionen oder Spielerlisten)">
			<option value=""></option>
			<option value="0"'.($config2['logging'] === 0 ? ' selected="selected"' : '').'>deaktiviert</option>
			<option value="1"'.($config2['logging'] == 1 ? ' selected="selected"' : '').'>nur Verwaltung</option>
			<option value="2"'.($config2['logging'] == 2 ? ' selected="selected"' : '').'>vorsichtig</option>
			<option value="3"'.($config2['logging'] == 3 ? ' selected="selected"' : '').'>paranoid</option>
			</select> <span class="small hint">(';
		$data = array(
			0=>'deaktiviert',
			1=>'nur Verwaltung',
			2=>'vorsichtig',
			3=>'paranoid'
		);
		$tmpl->content .= $data[$gconfig['logging']].')</span></td>
		</tr>
		<tr>
			<td>Speicherdauer des Logs (Tage)</td>
			<td><input type="text" class="text tooltip" name="logging_time" value="'.htmlspecialchars($config2['logging_time'], ENT_COMPAT, 'UTF-8').'" tooltip="Zeit in Tagen, wie lange Log-Eintr&auml;ge gespeichert bleiben sollen" /> <span class="small hint">('.htmlspecialchars($gconfig['logging_time'], ENT_COMPAT, 'UTF-8').')</span></td>
		</tr>
		</table>
		
		
		<br />
		<div class="center">
			<input type="button" class="button" style="width:150px" value="Konfiguration speichern" onclick="form_send(this.parentNode.parentNode, \'index.php?p=dbs&amp;sp=edit_send&amp;id='.$_GET['id'].'&amp;ajax\', $(this.parentNode.parentNode).siblings(\'.ajax\'))" />
		</div>
		</form>
		<br />
		<div class="ajax center"></div>';
	
		$tmpl->content .= '</div>';
	}
	
	// ausgeben
	$tmpl->output();
}



?>