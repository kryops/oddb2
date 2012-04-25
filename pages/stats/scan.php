<?php
/**
 * pages/stats/scan.php
 * Scan-Statistiken
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



$content =& $csw->data['scan']['content'];

// nach wie vielen Tagen gelten Scans als veraltet?
$days = $config['scan_veraltet'];
if(isset($_GET['days']) AND (int)$_GET['days']) $days = (int)$_GET['days'];

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
	
	<br /><br />';

// nach Galaxie gruppiert
$content2 = '
	<br /><br />
	<table class="data center" style="margin:auto">
	<tr>
		<th>Gala</th>
		<th>&nbsp;</th>
		<th>gesamt</th>
		<th>gescannt</th>
		<th>aktuell</th>
	</tr>';
	
// aktuelle Systeme ermitteln
$sys_gesamt = 0;
$sys_scanned = 0;
$sys_aktuell = 0;
$sys = array();

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
	$sys[$row['systeme_galaxienID']] = $row['systemeAnzahl'];
	$sys_aktuell += $row['systemeAnzahl'];
}

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
	$aktuell = isset($sys[$row['galaxienID']]) ? $sys[$row['galaxienID']] : 0;
	$sys_gesamt += $row['galaxienSysteme'];
	$sys_scanned += $row['galaxienSysScanned'];
	
	
	// Balken berechnen
	if($row['galaxienSysteme']) {
		$px1 = 100*($row['galaxienSysScanned']/$row['galaxienSysteme']);
		if($px1 > 100) $px1 = 100;
		$px2 = 100*($aktuell/$row['galaxienSysteme']);
		if($px2 > 100) $px2 = 100;
	}
	else {
		$px1 = 100;
		$px2 = 100;
	}
	
	$content2 .= '
	<tr>
		<td>'.$row['galaxienID'].'</td>
		<td>
			<div class="balken" style="width:100px;height:10px;">
				<div class="balkenfillhalf" style="width:'.$px1.'px"></div>
				<div class="balkenfill" style="width:'.$px2.'px"></div>
			</div>
		</td>
		<td>'.$row['galaxienSysteme'].'</td>
		<td>'.$row['galaxienSysScanned'].'</td>
		<td>'.$aktuell.'</td>
	</tr>';
}
$content2 .= '
	</table>
';

// gesamt

if($sys_gesamt) {
	$px1 = 100*($sys_scanned/$sys_gesamt);
	if($px1 > 100) $px1 = 100;
	$px2 = 100*($sys_aktuell/$sys_gesamt);
	if($px2 > 100) $px2 = 100;
}
else {
	$px1 = 0;
	$px2 = 0;
}

$content .= '
<table style="margin:auto">
	<tr>
		<td style="font-weight:bold">Systeme gescannt</td>
		<td>
			<div class="balken" style="width:100px;height:10px;">
				<div class="balkenfillhalf" style="width:'.$px1.'px"></div>
				<div class="balkenfill" style="width:'.$px2.'px"></div>
			</div>
		</td>
		<td>'.$sys_scanned.' / '.$sys_gesamt.' ('.$sys_aktuell.' aktuell)</td>
	</tr>';


// Daten für Planeten ermitteln
$query = query("
	SELECT
		(	SELECT COUNT(*)
			FROM ".PREFIX."planeten
		) AS planetenAnzahl,
		(	SELECT COUNT(*)
			FROM ".PREFIX."planeten
			WHERE planetenUpdateOverview > 0
		) AS planetenScanned,
		(	SELECT COUNT(*)
			FROM ".PREFIX."planeten
			WHERE planetenUpdateOverview > ".(time()-$days*86400)."
		) AS planetenAktuell
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$data = mysql_fetch_assoc($query);

if($data['planetenAnzahl']) {
	$px1 = 100*($data['planetenScanned']/$data['planetenAnzahl']);
	if($px1 > 100) $px1 = 100;
	$px2 = 100*($data['planetenAktuell']/$data['planetenAnzahl']);
	if($px2 > 100) $px2 = 100;
}
else {
	$px1 = 0;
	$px2 = 0;
}

$content .= '
	<tr>
		<td style="font-weight:bold">Planeten gescannt</td>
		<td>
			<div class="balken" style="width:100px;height:10px;">
				<div class="balkenfillhalf" style="width:'.$px1.'px"></div>
				<div class="balkenfill" style="width:'.$px2.'px"></div>
			</div>
		</td>
		<td>'.$data['planetenScanned'].' / '.$data['planetenAnzahl'].' ('.$data['planetenAktuell'].' aktuell)</td>
	</tr>
	</table>

'.$content2;

// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich den Scan-Status anzeigen');
}



?>