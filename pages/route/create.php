<?php
/**
 * pages/route/create.php
 * Route erstellen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Route/Liste erstellen
if($_GET['sp'] == 'create') {
	$tmpl->name = 'Route / Liste erstellen';
	
	$tmpl->content = '
	<div class="icontent center">
		<br />
		<form action="#" name="routecreate" onsubmit="form_send(this, \'index.php?p=route&amp;sp=create_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
		<div class="fcbox icontent" style="width:48%;float:right">
			<div class="fhl2 center">
			Express-Erstellung <span class="small hint">(optional)</span>
			</div>
			<div class="formcontent center icontent">
			<input type="checkbox" name="express" />&nbsp;
			<span class="togglecheckbox" data-name="express">Route sofort mit Planeten bef&uuml;llen und berechnen</span>
			<br /><br />
			max.&nbsp;
			<input type="text" class="smalltext" name="express_count" onkeydown="$(this).parents(\'form\').find(\':checkbox[name=express]\').attr(\'checked\', true)" /> 
			&nbsp;Planeten von&nbsp;
			<input type="text" class="smalltext tooltip" name="express_start" data-tooltip="kann nur von Planeten-ID aus berechnet werden" onkeydown="$(this).parents(\'form\').find(\':checkbox[name=express]\').attr(\'checked\', true)" /> 
			&nbsp;aus hinzuf&uuml;gen
			<br />
			<div style="text-align:left;margin-left:20%;margin-top:10px">
			<input type="radio" name="express_krieg" value="1" checked="checked"> 
			<span onclick="$(this).siblings(\'input\').attr(\'checked\', true)" style="cursor:pointer">von Kriegsgegnern</span>
			</div>
			<div style="text-align:left;margin-left:20%">
			<input type="radio" name="express_krieg" value="0"> 
			<span onclick="$(this).siblings(\'input[type=radio]\').attr(\'checked\', true)" style="cursor:pointer">der Allianz(en)</span> 
			&nbsp;<input type="text" class="smalltext" name="express_ally" style="width:120px" onkeydown="$(this).parents(\'form\').find(\':checkbox[name=express]\').attr(\'checked\', true);$(this).siblings(\'input[type=radio]\').attr(\'checked\', true)" /> 
			<span class="small hint">(IDs)</span>
			</div>
			<div style="text-align:left;margin-left:20%;margin-top:20px">
			vor mehr als&nbsp;
			<input type="text" class="smalltext" name="express_raid" onkeydown="$(this).parents(\'form\').find(\':checkbox[name=express]\').attr(\'checked\', true)" /> 
			&nbsp;Tagen geraidet
			<br />
			<input type="checkbox" name="express_toxx" onclick="$(this).parents(\'form\').find(\':checkbox[name=express]\').attr(\'checked\', true)" /> 
			&nbsp;<span class="togglecheckbox" data-name="express_toxx" onclick="$(this).parents(\'form\').find(\':checkbox[name=express]\').attr(\'checked\', true)">nur nicht getoxxte Planeten</span>
			</div>
			</div>
		</div>
		
		<table class="leftright" style="width:48%">
		<tr>
			<td>Name</td>
			<td><input type="text" class="text" name="name" style="width:200px" /></td>
		</tr>
		<tr>
			<td>Typ</td>
			<td><select name="liste">
			<option value="0">Route</option>
			<option value="2">Toxxroute</option>
			<option value="1">Liste</option>
			</select> &nbsp;
			<img src="img/layout/leer.gif" alt="" class="icon infobutton tooltip" style="cursor:default" data-tooltip="&lt;b&gt;Route&lt;/b&gt;: normale Route in einer Galaxie&lt;br /&gt;&lt;b&gt;Toxxroute&lt;/b&gt;: Jeder Planet darf nur in einer Toxxroute vorkommen. Wird nach 7 Tagen automatisch gel&ouml;scht&lt;br /&gt;&lt;b&gt;Liste&lt;/b&gt;: Planeten nach Entfernung sortierbar, nicht auf eine Galaxie beschr&auml;nkt. Praktisch vor allem f&uuml;r den FoW-Ausgleich" /></td>
		</tr>
		<tr>
			<td>Galaxie</td>
			<td><input type="text" class="smalltext" name="gala" /> &nbsp;<span class="small hint">(f&uuml;r Routen und Toxxrouten)</span></td>
		</tr>
		<tr>
			<td>Sichtbarkeit</td>
			<td><select name="typ">
			<option value="1">privat</option>
			<option value="2">Allianz</option>
			<option value="3">Meta</option>
			<option value="4">alle</option>
			</select></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align:center"><input type="checkbox" name="edit" /> von anderen editierbar</td>
		</tr>
		<tr>
			<td>Antrieb</td>
			<td><input type="text" class="smalltext" name="antrieb" /> &nbsp;<span class="small hint">(leer lassen f&uuml;r Standardantrieb)</span></td>
		</tr>
		</table>
		<div style="clear:both"></div>
		<br /><br />
		<input type="submit" class="button" style="width:120px" value="erstellen" />
		</form>
		<div class="ajax"></div>
	</div>';
	
	$tmpl->output();
}


// Route erstellen -> absenden
else if($_GET['sp'] == 'create_send') {
	// Vollständigkeit der Daten
	if(!isset($_POST['name'], $_POST['liste'], $_POST['gala'], $_POST['typ'], $_POST['antrieb'], $_POST['express_count'], $_POST['express_start'], $_POST['express_krieg'], $_POST['express_ally'], $_POST['express_raid'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// Daten sichern
		$_POST['liste'] = (int)$_POST['liste'];
		$_POST['gala'] = (int)$_POST['gala'];
		$_POST['typ'] = (int)$_POST['typ'];
		$_POST['antrieb'] = (int)$_POST['antrieb'];
		
		if($_POST['antrieb'] < 0) {
			$_POST['antrieb'] = 0;
		}
		
		// Daten ungültig
		if($_POST['liste'] < 0 OR $_POST['liste'] > 2 OR $_POST['typ'] < 1 OR $_POST['typ'] > 4) {
			$tmpl->error = 'Daten ungültig!';
		}
		else if($_POST['liste'] != 1 AND $_POST['gala'] < 1) {
			$tmpl->error = 'Ungültige Galaxie eingegeben!';
		}
		// Route erzeugen
		else {
			$route = new route;
			$route->create();
			
			if(trim($_POST['name']) != '') {
				$route->info['routenName'] = $_POST['name'];
			}
			if($_POST['liste'] == 1) {
				$route->liste = true;
			}
			else if($_POST['liste'] == 2) {
				$route->toxx = true;
			}
			if(!$route->liste) {
				$route->gala = $_POST['gala'];
			}
			
			$route->info['routenTyp'] = $_POST['typ'];
			
			if($_POST['antrieb']) {
				$route->antrieb = $_POST['antrieb'];
				$route->info['routenAntrieb'] = $_POST['antrieb'];
			}
			
			if(isset($_POST['edit'])) {
				$route->info['routenEdit'] = 1;
			}
			
			$route->save();
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(26, 'erstellt eine neue Route ('.$route->id.', '.$route->info['routenName'].')');
			}
			
			// Weiterleitung
			$tmpl->content .= '
			Die '.$rnames[$_POST['liste']].' wurde erfolgreich erstellt.
			<a class="link" style="display:none" data-link="index.php?p=route&amp;sp=view&amp;id='.$route->id.'" id="routecreatelink'.$route->id.'"></a>';
			
			$tmpl->script = '$("#routecreatelink'.$route->id.'").trigger("click");';
			
			if(isset($_POST['express'])) {
				$querystring = '';
				
				// Limit
				$_POST['express_count'] = (int)$_POST['express_count'];
				if($_POST['express_count'] > 0 AND $_POST['express_count'] < $route->limit) {
					$querystring .= '&limit='.$_POST['express_count'];
				}
				// Startpunkt
				if($_POST['express_start'] != '') {
					$querystring .= 'sortt=1&entf='.urlencode($_POST['express_start']);
				}
				// Kriegsgegner
				if($_POST['express_krieg']) {
					$querystring .= '&as='.$status_krieg;
				}
				// Ally
				else if($_POST['express_ally'] != '') {
					$querystring .= '&aid='.urlencode($_POST['express_ally']);
				}
				// Raid/Toxx
				if($_POST['express_raid'] != '') {
					$querystring .= '&rai='.(int)$_POST['express_raid'];
				}
				if(isset($_POST['express_toxx'])) {
					$querystring .= '&tox=0';
				}
				
				$tmpl->script .= '
window.setTimeout(function() {
	ajaxcall(\'index.php?p=search&sp=planet&s=1'.$querystring.'&hide&add2route&compute\', false, \'route='.$route->id.'\', true);
}, 500);';
			}
		}
	}
	
	if($tmpl->error != '') {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	
	$tmpl->output();
}


?>