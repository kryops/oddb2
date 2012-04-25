<?php
/**
 * pages/route/edit.php
 * Route bearbeiten
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Route/Liste bearbeiten
if($_GET['sp'] == 'edit') {
	$tmpl->name = 'Route / Liste bearbeiten';
	
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Keine ID &uuml;bergeben!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		$route = new route;
		// Route laden
		if(($error = $route->load($_GET['id'])) !== true) {
			$tmpl->error = $error;
		}
		// keine Berechtigung
		if(!$route->rechte_edit()) {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
		else {
			$tmpl->name = $rnames[$route->info['routenListe']].' '.htmlspecialchars($route->info['routenName'], ENT_COMPAT, 'UTF-8').' - Details ändern';
			
			$tmpl->content = '
	<div class="icontent center">
		<br />
		<form action="#" name="routeedit" onsubmit="form_send(this, \'index.php?p=route&amp;sp=edit_send&amp;id='.$_GET['id'].'&amp;win=\'+win_getid(this)+\'&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
		<table class="leftright" style="margin:auto">
		<tr>
			<td>Name</td>
			<td><input type="text" class="text" name="name" style="width:200px" value="'.htmlspecialchars($route->info['routenName'], ENT_COMPAT, 'UTF-8').'" /></td>
		</tr>';
			if($route->info['routen_playerID'] == $user->id) {
				$tmpl->content .= '
		<tr>
			<td>Sichtbarkeit</td>
			<td><select name="typ">
			<option value="1">privat</option>
			<option value="2"'.($route->info['routenTyp'] == 2 ? ' selected="selected"' : '').'>Allianz</option>
			<option value="3"'.($route->info['routenTyp'] == 3 ? ' selected="selected"' : '').'>Meta</option>
			<option value="4"'.($route->info['routenTyp'] == 4 ? ' selected="selected"' : '').'>alle</option>
			</select></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align:center"><input type="checkbox" name="edit"'.($route->info['routenEdit'] ? ' checked="checked"' : '').' /> von anderen editierbar</td>
		</tr>';
			}
			$tmpl->content .= '
		<tr>
			<td>Antrieb</td>
			<td><input type="text" class="smalltext" name="antrieb" value="'.($route->info['routenAntrieb'] ? $route->info['routenAntrieb'] : '').'" /> &nbsp;<span class="small hint">(leer lassen f&uuml;r Standardantrieb)</span></td>
		</tr>
		</table>
		<br />
		<input type="submit" class="button" style="width:100px" value="speichern" />
		</form>
		<div class="ajax"></div>
	</div>';
		}
	}
	
	$tmpl->output();
}


// Route bearbeiten -> abesenden
else if($_GET['sp'] == 'edit_send') {
	// Vollständigkeit der Daten
	if(!isset($_GET['id'], $_GET['win'], $_POST['name'], $_POST['antrieb'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		$_GET['win'] = (int)$_GET['win'];
		if(isset($_POST['typ'])) {
			$_POST['typ'] = (int)$_POST['typ'];
		}
		$_POST['antrieb'] = (int)$_POST['antrieb'];
		
		if($_POST['antrieb'] < 0) {
			$_POST['antrieb'] = 0;
		}
		
		$route = new route;
		// Route laden
		if(($error = $route->load($_GET['id'])) !== true) {
			$tmpl->error = $error;
		}
		// keine Berechtigung
		else if(!$route->rechte_edit()) {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
		// Änderungen speichern
		else {
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(26, 'bearbeitet die Route '.$route->info['routenName'].' ('.$route->id.')');
			}
			
			if(trim($_POST['name']) != '') {
				$route->info['routenName'] = $_POST['name'];
			}
			else {
				$route->info['routenName'] = 'Unbenannt';
			}
			
			if($route->info['routen_playerID'] == $user->id AND isset($_POST['typ'])) {
				$route->info['routenTyp'] = $_POST['typ'];
				$route->info['routenEdit'] = isset($_POST['edit']);
			}
			
			$route->antrieb = $_POST['antrieb'];
			$route->info['routenAntrieb'] = $_POST['antrieb'];
			
			$route->save();
			
			// Weiterleitung
			$tmpl->content .= '
			<br />
			Die '.$rnames[$route->info['routenListe']].' wurde erfolgreich gespeichert.
			<a class="link" style="display:none" data-link="index.php?p=route&amp;sp=view&amp;id='.$route->id.'" id="routeeditlink'.$route->id.'"></a>';
			
			$tmpl->script = '$("#routeeditlink'.$route->id.'").trigger("click");';
		}
	}
	
	if($tmpl->error != '') {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	
	$tmpl->output();
}



?>