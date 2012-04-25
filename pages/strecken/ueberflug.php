<?php
/**
 * pages/strecken/ueberflug.php
 * System-Überflug
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


/**
 * Lotfußpunkt und Entfernung für den System-Überflug berechnen
 * Parameter jeweils array(x, y, z)
 * @param $a Startpunkt
 * @param $b vorübergehender Zielpunkt vor dem Umlenken
 * @param $c finaler Zielpunkt
 *
 * @return array(Lotfußpunkt, Entfernung f-c)
 */
function ueberflug($a, $b, $c) {
	// gesucht: Abstand und Lotfußpunkt von c auf ab
	// Hilfsebene H: (x-c)*ab = 0 schneidet Gerade g: x = a + t*ab im Punkt f
	$ab = array(
		$b[0]-$a[0],
		$b[1]-$a[1],
		$b[2]-$a[2]
	);
	$ac = array(
		$c[0]-$a[0],
		$c[1]-$a[1],
		$c[2]-$a[2]
	);
	
	// |AB|^2 bestimmen
	$x = (pow($ab[0],2)+pow($ab[1],2)+pow($ab[2],2));
	
	// Division durch 0 verhindern
	if($x == 0) {
		return false;
	}
	
	// Längenfaktor berechnen
	$t = ($ac[0]*$ab[0]+$ac[1]*$ab[1]+$ac[2]*$ab[2])/$x;
	
	// wenn Längenfaktor nicht zwischen 0 und 1 liegt, ist der Lotfußpunkt außerhalb
	if($t <= 0 OR $t >= 1) {
		return false;
	}
	
	// Lotfußpunkt berechnen
	$f = array(
		$a[0]+$t*$ab[0],
		$a[1]+$t*$ab[1],
		$a[2]+$t*$ab[2]
	);
	
	// Entfernung nach dem Umlenken berechnen
	$entf = entf($f[0], $f[1], $f[2], 1, $c[0], $c[1], $c[2], 1);
	
	// zurückgeben
	return array(
		$f,
		$entf
	);
}



// keine Berechtigung
if(!$user->rechte['strecken_ueberflug']) $tmpl->error = 'Du hast keine Berechtigung!';
// Daten unvollständig
else if(!isset($_POST['start'], $_POST['dest'], $_POST['antrieb'], $_POST['count'], $_POST['range'])) {
	$tmpl->error = 'Daten unvollständig!';
}
// Antrieb ungültig
else if((int)$_POST['antrieb'] < 1) {
	$tmpl->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
}
// Anzahl ungültig
else if((int)$_POST['count'] < 1) {
	$tmpl->error = 'Ung&uuml;ltige Anzahl eingegeben!';
}
// Bereich ungültig
else if((int)$_POST['range'] < 10) {
	$tmpl->error = 'Ung&uuml;ltiger Bereich eingegeben!';
}
// Berechtigung
else {
	// Daten sichern
	$_POST['count'] = (int)$_POST['count'];
	$_POST['range'] = (int)$_POST['range'];
	
	$points = array();
	
	// Zielpunkt bestimmen
	if(strpos($_POST['dest'], 'sys') === false) {
		$_POST['dest'] = 'sys'.$_POST['dest'];
	}
	$points[1] = flug_point($_POST['dest']);
	
	// Startpunkt eingegeben
	if(trim($_POST['start']) != '') {
		$start = true;
		$points[0] = flug_point($_POST['start']);
	}
	else $start = false;
	
	foreach($points as $key=>$val) {
		$name = $key ? 'Zielpunkt' : 'Startpunkt';
		
		// Fehler
		if(!is_array($val) AND !$tmpl->error) {
			if($val == 'coords') $tmpl->error = 'Es k&ouml;nnen nur Planeten- und System-IDs eingegeben werden!';
			else if($val == 'data') $tmpl->error = 'Ung&uuml;ltige Daten beim '.$name.' eingegeben!';
			else $tmpl->error = $name.' nicht gefunden!';
		}
		// Koordinaten eingegeben
		else if(!$val[5] AND !$tmpl->error) {
			$tmpl->error = 'Es k&ouml;nnen nur Planeten- und System-IDs eingegeben werden!';
		}
		// Start- und Zielpunkt in unterschiedlichen Galaxien
		else if($start AND $points[0][0] != $points[1][0]) {
			$tmpl->error = 'Start und Ziel m&uuml;ssen in derselben Galaxie liegen!';
		}
		// kein Zugriff auf die Galaxie
		else if($user->protectedGalas AND in_array($points[1][0], $user->protectedGalas)) {
			$tmpl->error = 'Deine Allianz hat keinen Zugriff auf diese Galaxie!';
		}
	}
	
	// keine Fehler
	if(!$tmpl->error) {
		$entf = array();
		$fp = array();
		$sys = array();
		
		// Zielpunkt immer gleich
		$c = array(
			$points[1][1],
			$points[1][2],
			$points[1][3]
		);
		
		// Berechnung mit statischem Startpunkt
		if($start) {
			$a = array(
				$points[0][1],
				$points[0][2],
				$points[0][3]
			);
			
			$sys[$points[0][5]] = $a;
			
			// Systeme abfragen
			$query = query("
				SELECT
					systemeID,
					systemeX,
					systemeY,
					systemeZ
				FROM
					".PREFIX."systeme
				WHERE
					systeme_galaxienID = ".$points[1][0]."
					AND systemeID NOT IN (".$points[0][5].",".$points[1][5].")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				// Entfernungen berechnen
				$b = array(
					$row['systemeX'],
					$row['systemeY'],
					$row['systemeZ']
				);
				$data = ueberflug($a, $b, $c);
				
				if($data) {
					$entf[$points[0][5].'-'.$row['systemeID']] = $data[1];
					$fp[$points[0][5].'-'.$row['systemeID']] = $data[0];
					
					$sys[$row['systemeID']] = array(
						$row['systemeX'],
						$row['systemeY'],
						$row['systemeZ']
					);
				}
			}
		}
		// Berechnung ohne Startpunkt
		else {
			// Systeme abfragen
			$query = query("
				SELECT
					systemeID,
					systemeX,
					systemeY,
					systemeZ,
					".entf_mysql("systemeX", "systemeY", "systemeZ", "1", $points[1][1], $points[1][2], $points[1][3], "1")." AS systemeEntfernung
				FROM
					".PREFIX."systeme
				WHERE
					systeme_galaxienID = ".$points[1][0]."
					AND systemeID != ".$points[1][5]."
				ORDER BY systemeEntfernung ASC
				LIMIT ".$_POST['range']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$sys[$row['systemeID']] = array(
					$row['systemeX'],
					$row['systemeY'],
					$row['systemeZ']
				);
			}
			
			// alle Systeme durchgehen und Entfernungen berechnen
			foreach($sys as $key=>$a) {
				foreach($sys as $key2=>$b) {
					if(!isset($entf[$key2.'-'.$key]) AND $key != $key2) {
						$data = ueberflug($a, $b, $c);
						if($data) {
							$entf[$key.'-'.$key2] = $data[1];
							$fp[$key.'-'.$key2] = $data[0];
						}
					}
				}
			}
		}
		
		// keine geeignete Überflug-Route gefunden
		if(!count($entf)) {
			$tmpl->content = '
			<br />
			<div align="center">
				Es wurde keine geeignete &Uuml;berflug-Route gefunden!
			</div>';
		}
		// Routen gefunden
		else {
			asort($entf);
			
			// Array evtl kürzen
			if(count($entf) > $_POST['count']) {
				$entf = array_slice($entf, 0, $_POST['count'], true);
			}
			
			// Inhalt
			$tmpl->content = '<br />';
			
			foreach($entf as $key=>$val) {
				// Fußpunkt abrufen
				$f =& $fp[$key];
				
				$key = explode('-', $key);
				$a =& $sys[$key[0]];
				$e = entf($a[0], $a[1], $a[2], 1, $f[0], $f[1], $f[2], 1);
				
				$tmpl->content .= '
					<div class="fcbox icontent" style="width:600px">
						System <a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$key[0].'&amp;ajax" style="font-weight:bold">'.$key[0].'</a> 
						- <a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$key[1].'&amp;ajax" style="font-weight:bold">'.$key[1].'</a> &nbsp;&rarr; Flugdauer '.flugdauer($val, $_POST['antrieb'], true).'
						<br />
						umlenken nach <b>'.flugdauer($e, $_POST['antrieb'], true).'</b> ';
				// ohne Startpunkt beide Umlenkzeiten angeben
				if(!$start) {
					$b =& $sys[$key[1]];
					$e2 = entf($b[0], $b[1], $b[2], 1, $f[0], $f[1], $f[2], 1);
					
					$tmpl->content .= '
						<span class="small hint">(beim Flug '.$key[0].' &rarr; '.$key[1].')</span> 
						bzw. <b>'.flugdauer($e2, $_POST['antrieb'], true).'</b> 
						<span class="small hint">(beim Flug '.$key[1].' &rarr; '.$key[0].')</span>';
				}
				$tmpl->content .= '
					</div>';
			}
		}
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(9, 'berechnet einen System-&Uuml;berflug nach '.$_POST['dest']);
		}
	}
}
// Ausgabe
if($tmpl->error) $tmpl->error = '<br />'.$tmpl->error;
$tmpl->output();



?>