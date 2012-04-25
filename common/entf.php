<?php

/**
 * common/entf.php
 * Entfernungs- und Flug-Funktionen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


/**
 * Entfernung in ZentiMetron aus Koordinaten und Planetenposition berechnen
 * @param $x1 int X-Koordinate Planet 1
 * @param $y1 int Y-Koordinate Planet 1
 * @param $z1 int Z-Koordinate Planet 1
 * @param $pl1 int Position Planet 1
 * @param $x2 int X-Koordinate Planet 2
 * @param $y2 int Y-Koordinate Planet 2
 * @param $z2 int Z-Koordinate Planet 2
 * @param $pl2 int Position Planet 2
 *
 * @return float Entfernung
 */
function entf($x1, $y1, $z1, $pl1, $x2, $y2, $z2, $pl2) {
	return round(
		(
			sqrt(
				pow($x2-$x1,2)
				+
				pow($y2-$y1,2)
				+
				pow($z2-$z1,2)
			) / 2 
			+
			abs($pl2-$pl1)*0.3
		) * 100
	);
}

/**
 * Entfernung in einer MySQL-Abfrage berechnen
 * @param $x1 int X-Koordinate Planet 1
 * @param $y1 int Y-Koordinate Planet 1
 * @param $z1 int Z-Koordinate Planet 1
 * @param $pl1 int Position Planet 1
 * @param $x2 int X-Koordinate Planet 2
 * @param $y2 int Y-Koordinate Planet 2
 * @param $z2 int Z-Koordinate Planet 2
 * @param $pl2 int Position Planet 2
 *
 * @return string MySQL-Berechnung
 */
function entf_mysql($x1, $y1, $z1, $pl1, $x2, $y2, $z2, $pl2) {
	return "ROUND(
				(
					SQRT(
						POW(".$x1." - ".$x2.", 2) +
						POW(".$y1." - ".$y2.", 2) +
						POW(".$z1." - ".$z2.", 2)
					) / 2 
					+ ABS(".$pl1." - ".$pl2.") * 0.3
				) * 100
			)";
}

/**
 * Flugdauer aus Entfernung und Antrieb berechnen
 * @param $entf float Entfernung in ZentiMetron
 * @param $antr int Antrieb
 * @param $long bool Anzeige mit Tagen und Sekunden (default false)
 *
 * @return string Flugdauer als Stunden:Minuten oder Tage:Stunden:Minuten:Sekunden
 */
function flugdauer($entf, $antr, $long=false) {
	// Dauer in Sekunden
	$dauer = 12*$entf/$antr;
	
	// lange Version
	if($long) {
		// Tage
		$tage = floor($dauer/86400);
		$dauer -= $tage*86400;
		// Stunden
		$stunden = floor($dauer/3600);
		$dauer -= $stunden*3600;
		// Minuten
		$minuten = floor($dauer/60);
		$dauer -= $minuten*60;
		if($minuten < 10) $minuten = '0'.$minuten;
		// Sekunden
		$sekunden = floor($dauer);
		if($sekunden < 10) $sekunden = '0'.$sekunden;
		
		return $tage.':'.$stunden.':'.$minuten.':'.$sekunden;
	}
	// kurze Version
	else {
		// Stunden
		$stunden = floor($dauer/3600);
		$dauer -= $stunden*3600;
		// Minuten
		$minuten = round($dauer/60);
		if($minuten > 59) {
			$stunden++;
			$minuten = 0;
		}
		if($minuten < 10) $minuten = '0'.$minuten;
		
		return $stunden.':'.$minuten;
	}
}

/**
 * berechnet die Entfernung aus der Flugzeit
 * @param $dauer int Flugzeit in Sekunden
 * @param $antr int Antrieb
 * @return Entfernung in ZentiMetron
 */
function entffdauer($dauer, $antr) {
	
	$entf = ($dauer*$antr)/12;
	
	return $entf;
}

/**
 * System-ID, Planeten-ID oder Koordinaten-String in Koordinaten-Array umwandeln
 * @param $val int/string Input
 * @return	coords - ungültige Koordinaten
 *			data - ungültige Daten eingegeben
 *			found - Punkt nicht gefunden
 *			array -> Koordinaten-Array
 */
function flug_point($val) {
	$val = trim($val);
	
	// Koordinaten
	if(strpos($val, '|') !== false) {
		$val = explode('|', $val);
		// ungültig
		if(count($val) != 4) {
			return 'coords';
		}
		// sichern
		foreach($val as $key=>$data) {
			$val[$key] = (int)$data;
		}
		// Platzhalter für planetenPosition und systemeID
		$val[] = 1;
		$val[] = 0;
		return $val;
	}
	// Planet oder System
	else if(is_numeric($val) OR strpos(strtolower($val), 'sys') !== false) {
		// System
		if(strpos(strtolower($val), 'sys') !== false) {
			$val = str_replace('sys', '', strtolower($val));
			$val = (int)$val;
			
			// System-ID suchen
			$query = query("
				SELECT
					systeme_galaxienID,
					systemeX,
					systemeY,
					systemeZ
				FROM
					".PREFIX."systeme
				WHERE
					systemeID = ".$val."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// System gefunden
			if(mysql_num_rows($query)) {
				$val2 = mysql_fetch_assoc($query);
				return array(
					$val2['systeme_galaxienID'],
					$val2['systemeX'],
					$val2['systemeY'],
					$val2['systemeZ'],
					1,
					$val
				);
			}
			// kein System gefunden
			else {
				return 'found';
			}
		}
		else {
			$val = (int)$val;
			
			// Planet suchen
			$query = query("
				SELECT
					systeme_galaxienID,
					systemeX,
					systemeY,
					systemeZ,
					planetenPosition,
					systemeID
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON planeten_systemeID = systemeID
				WHERE
					planetenID = ".$val."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Planet gefunden
			if(mysql_num_rows($query)) {
				$val = mysql_fetch_assoc($query);
				return array(
					$val['systeme_galaxienID'],
					$val['systemeX'],
					$val['systemeY'],
					$val['systemeZ'],
					$val['planetenPosition'],
					$val['systemeID']
				);
			}
			// kein Planet gefunden -> System suchen
			else {
				$query = query("
					SELECT
						systeme_galaxienID,
						systemeX,
						systemeY,
						systemeZ
					FROM
						".PREFIX."systeme
					WHERE
						systemeID = ".$val."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// System gefunden
				if(mysql_num_rows($query)) {
					$val2 = mysql_fetch_assoc($query);
					return array(
						$val2['systeme_galaxienID'],
						$val2['systemeX'],
						$val2['systemeY'],
						$val2['systemeZ'],
						1,
						$val
					);
				}
				// nichts gefunden
				else {
					return 'found';
				}
			}
		}
	}
	// invalide Daten
	else {
		return 'data';
	}
}


?>