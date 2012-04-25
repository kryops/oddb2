<?php
/**
 * pages/admin/rechte_edit.php
 * Verwaltung -> Berechtigungen -> Rechtelevel bearbeiten
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');




// keine Berechtigung
if(!$user->rechte['verwaltung_rechte']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
}

// Vorhandensein der Daten
else if(!isset($_GET['id'], $rechte[$_GET['id']]) OR $_GET['id'] == 4) {
	$tmpl->error = 'Ung&uuml;ltiges Rechtelevel &uuml;bergeben!';
}

// alles OK
else {
	// Daten sichern
	$id = (int)$_GET['id'];
	
	// bisherige Konfiguration laden
	if(!class_exists('config')) {
		include './common/config.php';
	}
	
	$r = config::getcustom_rechte(INSTANCE);
	$r = $r[$_GET['id']];
	$br = $brechte[$_GET['id']];
	$gr = $rechte[$_GET['id']];
	
	$tmpl->name = 'Rechtelevel '.h($rechte[$id]['name']).' bearbeiten';
	
	// Rechte bereinigen
	unset($rechtenamen['override_allies']);
	unset($rechtenamen['override_galas']);
	
	$tmpl->content = '
	<div class="icontent rechteedit'.$id.'">
		<form name="rechte">
		
		<table class="tnarrow small2">
		<tr>
			<td>Name</td>
			<td><input type="text" class="text" name="name" value="'.h($gr['name']).'" /></td>
		</tr>
		<tr>
			<td>Beschreibung</td>
			<td><input type="text" class="text" name="desc" style="width:500px" value="'.h($gr['desc']).'" /></td>
		</tr>
		</table>
		
		<br />
		Graue Berechtigungen sind in der Standardeinstellung f&uuml;r dieses Rechtelevel gesperrt.
		
		<br /><br />
		<span class="small2">
		<span style="font-weight:bold">normal</span>: Standardeinstellung &uuml;bernehmen
		<br />
		<span style="font-weight:bold;color:#00aa00">gr&uuml;n</span>: Funktion nutzbar
		<br />
		<span style="font-weight:bold;color:#ff3322">rot</span>: Funktion gesperrt
		</span>
		
		<br /><br />
		<table class="tsmall tnarrow trechte">';
	
	// Berechtigungen durchgehen
	foreach($rechtenamen as $key=>$name) {
		$tmpl->content .= '
		<tr>
			<td><input type="radio" name="'.$key.'" value="-1"'.(!isset($r[$key]) ? ' checked="checked" ' : '').' /></td>
			<td style="background-color:#005500"><input type="radio" name="'.$key.'" value="1"'.((isset($r[$key]) AND $r[$key]) ? ' checked="checked" ' : '').' /></td>
			<td style="background-color:#aa0000"><input type="radio" name="'.$key.'" value="0"'.((isset($r[$key]) AND !$r[$key]) ? ' checked="checked" ' : '').' /></td>
			<td';
		// standardmäßig deaktiviert
		if(!$br[$key]) {
			$tmpl->content .= ' class="rechtedisabled"';
		}
		// tatsächlich deaktiviert
		if(!$br[$key] AND !isset($r[$key])) {
			$tmpl->content .= ' style="opacity:0.4;filter:alpha(opacity=40)"';
		}
		// erlaubt -> grün
		else if(isset($r[$key]) AND $r[$key]) {
			$tmpl->content .= ' style="color:#00aa00"';
		}
		// gesperrt -> rot
		else if(isset($r[$key]) AND !$r[$key]) {
			$tmpl->content .= ' style="color:#ff3322"';
		}
		$tmpl->content .= '> &nbsp;'.h($name).'</td>
		</tr>';
	}
	$tmpl->content .= '
		</table>
		<br />
		<div class="center">
			<div class="center ajax"></div>
			<input type="button" class="button" value="Rechtelevel speichern" onclick="form_send(this.parentNode.parentNode, \'index.php?p=admin&amp;sp=rechte_send&amp;id='.$id.'&amp;ajax\', $(this).siblings(\'.ajax\'))" />
		</div>
		</form>
	</div>';
	
	// Script zum Ändern der Farbe
	$tmpl->script = '$(\'.trechte input\').click(function(){rechte_click(this)});';
}

// Ausgabe
$tmpl->output();


?>