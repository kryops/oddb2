<?php
/**
 * pages/stats/scan.php
 * Scan-Statistiken
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class StatsScan {
	
	/**
	 * Sytem- und Planetenstatistik abfragen
	 * @param int $days Tage, nach denen ein Scan nicht mehr als aktuell gilt
	 * @return array Statistik
	 */
	public static function fetchStats($days) {
		
		global $cache;
		
		// Daten für Planeten ermitteln
		if(($stats = $cache->get('stats'.$days)) === false) {
			
			$stats = array(
				'gesamt' => array(
					'systemeGesamt' => 0,
					'systemeGescannt' => 0,
					'systemeAktuell' => 0,
					'planetenGesamt' => 0,
					'planetenGescannt' => 0,
					'planetenAktuell' => 0
				)
			);
			
			/*
			 * Systeme
			 */
			
			// Gesamtzahl und gescannte Systeme ermitteln
			$query = query("
				SELECT
					galaxienID,
					galaxienSysteme,
					galaxienSysScanned
				FROM
					".PREFIX."galaxien
				ORDER BY
					galaxienID ASC
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$stats[$row['galaxienID']] = array(
					'systemeGesamt' => $row['galaxienSysteme'],
					'systemeGescannt' => $row['galaxienSysScanned'],
					'systemeAktuell' => 0,
					'planetenGesamt' => 0,
					'planetenGescannt' => 0,
					'planetenAktuell' => 0
				);
				
				$stats['gesamt']['systemeGesamt'] += $row['galaxienSysteme'];
				$stats['gesamt']['systemeGescannt'] += $row['galaxienSysScanned'];
			}
			
			// aktuelle Systeme
			$query = query("
				SELECT
					systeme_galaxienID,
					COUNT(*) as systemeAnzahl
				FROM
					".PREFIX."systeme
				WHERE
					systemeUpdate > ".(time()-$days*86400)."
				GROUP BY
					systeme_galaxienID
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				if(isset($stats[$row['systeme_galaxienID']])) {
					$stats[$row['systeme_galaxienID']]['systemeAktuell'] = $row['systemeAnzahl'];
					$stats['gesamt']['systemeAktuell'] += $row['systemeAnzahl'];
				}
			}
			
			
			/*
			 * Planeten
			 */
			
			// gesamte Anzahl
			$query = query("
				SELECT
					systeme_galaxienID,
					COUNT(*) AS planetenAnzahl
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
				WHERE
					planeten_playerID NOT IN(0,-1)
				GROUP BY
					systeme_galaxienID
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				if(isset($stats[$row['systeme_galaxienID']])) {
					$stats[$row['systeme_galaxienID']]['planetenGesamt'] = $row['planetenAnzahl'];
					$stats['gesamt']['planetenGesamt'] += $row['planetenAnzahl'];
				}
			}
			
			// gescannte Planeten
			$query = query("
				SELECT
					systeme_galaxienID,
					COUNT(*) AS planetenAnzahl
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
				WHERE
					planeten_playerID NOT IN(0,-1)
					AND planetenUpdateOverview > 0
				GROUP BY
					systeme_galaxienID
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				if(isset($stats[$row['systeme_galaxienID']])) {
					$stats[$row['systeme_galaxienID']]['planetenGescannt'] = $row['planetenAnzahl'];
					$stats['gesamt']['planetenGescannt'] += $row['planetenAnzahl'];
				}
			}
			
			// aktuelle Planeten
			$query = query("
				SELECT
					systeme_galaxienID,
					COUNT(*) AS planetenAnzahl
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
				WHERE
					planeten_playerID NOT IN(0,-1)
					AND planetenUpdateOverview > ".(time()-$days*86400)."
				GROUP BY
					systeme_galaxienID
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				if(isset($stats[$row['systeme_galaxienID']])) {
					$stats[$row['systeme_galaxienID']]['planetenAktuell'] = $row['planetenAnzahl'];
					$stats['gesamt']['planetenAktuell'] += $row['planetenAnzahl'];
				}
			}
			
			// 15 Minuten cachen
			$cache->set('stats'.$days, $stats, 900);
			
		}
		
		return $stats;
	}
	
	
	/**
	 * Statistik-Balken mit zwei Füllwerten erzeugen
	 * @param int $total Gesamtanzahl
	 * @param int $full Anzahl, die der volle Balken anzeigen soll
	 * @param int $half Anzahl, die der halbtransparente Balken anzeigen soll
	 * @param int $width Breite des Balkens @default 100
	 * @param int $height Höhe des Balkens @default 10
	 * @return HTML des Balkens
	 */
	public static function createBalken($total, $full=0, $half=0, $width=100, $height=10) {
		
		if($total > 0) {
			$px1 = round($width*($half/$total));
			if($px1 > $width) {
				$px1 = $width;
			}
			
			$px2 = round($width*($full/$total));
			if($px2 > $width) {
				$px2 = $width;
			}
		}
		else {
			$px1 = $width;
			$px2 = $width;
		}
		
		return '<div class="balken" style="width:'.$width.'px;height:'.$height.'px;">
			<div class="balkenfillhalf" style="width:'.$px1.'px"></div>
			<div class="balkenfill" style="width:'.$px2.'px"></div>
		</div>';
		
	}
	
}




$content =& $csw->data['scan']['content'];

// nach wie vielen Tagen gelten Scans als veraltet?
$days = $config['scan_veraltet'];
if(isset($_GET['days']) AND (int)$_GET['days']) $days = (int)$_GET['days'];



$stats = StatsScan::fetchStats($days);



$content = '
	<div class="hl2">Scan-Status</div>
	
	<form action="#" name="stats_scan" onsubmit="return form_send(this, \'index.php?p=stats&amp;sp=scan&amp;days=\'+this.days.value+\'&amp;ajax&amp;switch\', this.parentNode)">
	<div class="fcbox formcontent center" style="padding:10px;width:500px">
		Statistiken f&uuml;r
		&nbsp;<input type="text" class="smalltext" name="days" value="'.$days.'" />&nbsp;
		Tage 
		<input type="submit" class="button" value="anzeigen" />
	</div>
	</form>
	
	<br /><br />

	<table style="margin:auto">
	<tr>
		<td style="font-weight:bold">Systeme gescannt</td>
		<td>
			'.StatsScan::createBalken($stats['gesamt']['systemeGesamt'], $stats['gesamt']['systemeAktuell'], $stats['gesamt']['systemeGescannt']).'
		</td>
		<td>'.$stats['gesamt']['systemeGescannt'].' / '.$stats['gesamt']['systemeGesamt'].' ('.$stats['gesamt']['systemeAktuell'].' aktuell)</td>
	</tr>
	<tr>
		<td style="font-weight:bold">
			Planeten gescannt
			<br />
			<span class="small hint" style="font-weight:normal">(nur bewohnte)</span>
		</td>
		<td>
			'.StatsScan::createBalken($stats['gesamt']['planetenGesamt'], $stats['gesamt']['planetenAktuell'], $stats['gesamt']['planetenGescannt']).'
		</td>
		<td>'.$stats['gesamt']['planetenGescannt'].' / '.$stats['gesamt']['planetenGesamt'].' ('.$stats['gesamt']['planetenAktuell'].' aktuell)</td>
	</tr>
	</table>
	
	<br /><br />
	<table class="data center" style="margin:auto">
	<tr>
		<th>Gala</th>
		<th colspan="2">Systeme</th>
		<th colspan="2">bewohnte Planeten</th>
	</tr>';

foreach($stats as $gala=>$data) {
	
	// gesperrte Galaxien ausblenden
	if($user->protectedGalas AND in_array($gala, $user->protectedGalas)) {
		continue;
	}
	
	if($gala != 'gesamt') {
		$content .= '
	<tr>
		<td>'.$gala.'</td>
		<td>
			'.StatsScan::createBalken($data['systemeGesamt'], $data['systemeAktuell'], $data['systemeGescannt']).'
		</td>
		<td>'.$data['systemeGescannt'].' / '.$data['systemeGesamt'].' ('.$data['systemeAktuell'].' aktuell)</td>
		<td>
			'.StatsScan::createBalken($data['planetenGesamt'], $data['planetenAktuell'], $data['planetenGescannt']).'
		</td>
		<td>'.$data['planetenGescannt'].' / '.$data['planetenGesamt'].' ('.$data['planetenAktuell'].' aktuell)</td>
	</tr>';
	}
}

$content .= '
	</table>
		
	<br />
	<div class="center small hint">Die Scan-Statistik wird alle 15 Minuten neu berechnet</div>
';



// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich den Scan-Status anzeigen');
}


?>