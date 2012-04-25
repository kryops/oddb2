<?php
/**
 * pages/strecken/matrix.php
 * Flugzeiten-Matrix
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// keine Berechtigung
if(!$user->rechte['strecken_flug']) $tmpl->error = 'Du hast keine Berechtigung!';
// Daten unvollständig
else if(!isset($_POST['list1'], $_POST['list2'], $_POST['antrieb'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// Listen unvollständig
else if(trim($_POST['list1']) == '' OR trim($_POST['list2']) == '') {
	$tmpl->error = 'Listen unvollst&auml;ndig!';
}
// Antrieb ungültig
else if((int)$_POST['antrieb'] < 1) {
	$tmpl->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
}
// Berechtigung
else {
	// Titel
	$tmpl->name = 'Flugzeiten-Matrix';
	
	
	// Daten sichern
	$_POST['antrieb'] = (int)$_POST['antrieb'];
	
	$list1 = preg_replace('#\s#Uis', '', $_POST['list1']);
	$list2 = preg_replace('#\s#Uis', '', $_POST['list2']);
	$list1 = explode(',', $list1);
	$list2 = explode(',', $list2);
	
	$tmpl->content = '<table class="data" style="margin:auto">
	<tr>
		<th>&nbsp;</th>';
	// Headline
	foreach($list2 as $key=>$val) {
		$tmpl->content .= '
		<th>'.htmlspecialchars($val, ENT_COMPAT, 'UTF-8').'</th>';
		
		$list2[$key] = flug_point($val);
	}
	$tmpl->content .= '
	</tr>';
	
	// Liste 1 durchgehen
	
	foreach($list1 as $key1=>$val1) {
		// Entfernungen berechnen
		$minentf = false;
		$minentfkey = array();
		$out = array();
		
		$val1 = flug_point($val1);
		
		foreach($list2 as $key2=>$val2) {
			if(!is_array($val1) OR !is_array($val2)) {
				$out[$key2] = '<span class="tooltip" data-tooltip="Daten ung&uuml;ltig!">-</span>';
			}
			else if($val1[0] != $val2[0]) {
				$out[$key2] = '<span class="tooltip" data-tooltip="Galaxien unterschiedlich!">-</span>';
			}
			else if($user->protectedGalas AND in_array($points[0][0], $user->protectedGalas)) {
				$out[$key2] = '<span class="tooltip" data-tooltip="Deine Allianz hat keinen Zugriff auf diese Galaxie!">-</span>';
			}
			else {
				$e = entf(
					$val1[1],
					$val1[2],
					$val1[3],
					$val1[4],
					$val2[1],
					$val2[2],
					$val2[3],
					$val2[4]
				);
				
				// schnellste Flugzeit ermitteln
				if($e > 0 AND ($minentf === false OR $minentf >= $e)) {
					if($minentf === $e) {
						$minentfkey[] = $key2;
					}
					else {
						$minentf = $e;
						$minentfkey = array($key2);
					}
				}
				
				if($e == 0) {
					$out[$key2] = '<span class="yellow">'.flugdauer($e, $_POST['antrieb']).'</span>';
				}
				else {
					$out[$key2] = flugdauer($e, $_POST['antrieb']);
				}
			}
		}
		
		// Ausgabe
		$tmpl->content .= '
	<tr>
		<th>'.htmlspecialchars($list1[$key1], ENT_COMPAT, 'UTF-8').'</th>';
		
		foreach($list2 as $key2=>$val2) {
			$tmpl->content .= '<td';
			if(in_array($key2, $minentfkey)) {
				$tmpl->content .= ' style="background-color:#227711"';
			}
			$tmpl->content .= '>'.$out[$key2].'</td>';
		}
		
		$tmpl->content .= '
	</tr>';
	}
	
	$tmpl->content .= '</table>';
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(6, 'lässt eine Flugzeiten-Matrix erzeugen');
	}
}
// Ausgabe
$tmpl->output();



?>