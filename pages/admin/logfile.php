<?php
/**
 * pages/admin/logfile.php
 * Verwaltung -> Logfile
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// Filter für Logfile-Kategorien definieren
// @see common.php -> insertlog()
$logfilter = array(
	1=>array(14, 21, 24, 25), // Verwaltung
	2=>array(1, 2, 3), // Registrierung und Login
	3=>array(4, 15), // Scan und FoW-Ausgleich
	4=>array(16), // Suchfunktion
	5=>array(6, 7, 8, 9, 10, 17), // Strecken und Scout-Bereich
	6=>array(11), // Raiden und Toxxen
	7=>array(26), // Routen
	8=>array(27), // Invasionen
	9=>array(12), // Ressplaneten und Werften
	10=>array(22, 23, 18, 19, 20), // Spieler, Allianzen und Einstellungen
	12=>array(28),	// API
	11=>array(5) // Seiten anzeigen
);

// Content mappen
$content =& $csw->data['logfile']['content'];


$content = '
<div class="hl2">Logfile</div>
<div class="icontent">
	<form name="admin_logfile" action="#" onsubmit="return form_sendget(this, \'index.php?p=admin&amp;sp=logfile&amp;s=1\')">
	<div class="fcbox center formbox">
	Eintr&auml;ge 
	<select name="d_">
		<option value="">&auml;lter als</option>
		<option value="1"'.((isset($_GET['d_']) AND $_GET['d_'] == 1) ? ' selected="selected"' : '').'>neuer als</option>
		<option value="2"'.((isset($_GET['d_']) AND $_GET['d_'] == 2) ? ' selected="selected"' : '').'>genau</option>
	</select> &nbsp;
	<input type="text" class="smalltext" name="d" value="'.(isset($_GET['d']) ? h($_GET['d']) : '').'" /> &nbsp;
	Tage &nbsp; &nbsp;
	Kategorie: 
	<select name="typ">
		<option value="">alle</option>
		<option value="1"'.((isset($_GET['typ']) AND $_GET['typ'] == 1) ? ' selected="selected"' : '').'>Verwaltung</option>
		<option value="2"'.((isset($_GET['typ']) AND $_GET['typ'] == 2) ? ' selected="selected"' : '').'>Registrierung und Login</option>
		<option value="3"'.((isset($_GET['typ']) AND $_GET['typ'] == 3) ? ' selected="selected"' : '').'>Scan und FoW-Ausgleich</option>
		<option value="4"'.((isset($_GET['typ']) AND $_GET['typ'] == 4) ? ' selected="selected"' : '').'>Suchfunktion</option>
		<option value="5"'.((isset($_GET['typ']) AND $_GET['typ'] == 5) ? ' selected="selected"' : '').'>Strecken und Scout-Bereich</option>
		<option value="6"'.((isset($_GET['typ']) AND $_GET['typ'] == 6) ? ' selected="selected"' : '').'>Raiden und Toxxen</option>
		<option value="7"'.((isset($_GET['typ']) AND $_GET['typ'] == 7) ? ' selected="selected"' : '').'>Routen</option>
		<option value="8"'.((isset($_GET['typ']) AND $_GET['typ'] == 8) ? ' selected="selected"' : '').'>Invasionen</option>
		<option value="9"'.((isset($_GET['typ']) AND $_GET['typ'] == 9) ? ' selected="selected"' : '').'>Ressplaneten und Werften</option>
		<option value="10"'.((isset($_GET['typ']) AND $_GET['typ'] == 10) ? ' selected="selected"' : '').'>Spieler, Allianzen und Einstellungen</option>
		<option value="12"'.((isset($_GET['typ']) AND $_GET['typ'] == 12) ? ' selected="selected"' : '').'>ODDB-API</option>
		<option value="11"'.((isset($_GET['typ']) AND $_GET['typ'] == 11) ? ' selected="selected"' : '').'>Seiten anzeigen</option>
	</select>
	<br />
	Spieler-ID: 
	<input type="text" class="smalltext" name="uid" value="'.(isset($_GET['uid']) ? h($_GET['uid']) : '').'" /> &nbsp; &nbsp;
	IP: 
	<input type="text" class="smalltext" name="ip" value="'.(isset($_GET['ip']) ? h($_GET['ip']) : '').'" /> &nbsp; &nbsp;
	Eintrag enth&auml;lt: 
	<input type="text" class="text" name="c" value="'.(isset($_GET['c']) ? h($_GET['c']) : '').'" />
	<br />
	<input type="submit" class="button" style="width:120px" value="Logfile filtern" /> 
	<input type="button" class="button link" style="width:120px" value="Filter aufheben" data-link="index.php?p=admin&amp;sp=logfile" />
	</div>
	</form>
	<br />';

$conds = array();

// Lofgile filtern
if(isset($_GET['s'])) {
	// Datum
	if(isset($_GET['d'])) {
		$datum_heute = strtotime('today');
		$_GET['d'] = (int)$_GET['d'];
		
		// älter als
		if(!isset($_GET['d_'])) {
			$conds[] = 'logTime < '.($datum_heute+86400-$_GET['d']*86400);
		}
		// neuer als
		else if($_GET['d_'] == 1) {
			$conds[] = 'logTime > '.($datum_heute-$_GET['d']*86400);
		}
		// genau
		else {
			$t = $datum_heute-$_GET['d']*86400;
			$conds[] = 'logTime > '.($t);
			$conds[] = 'logTime < '.($t+86400);
		}
	}
	
	// Kategorie
	if(isset($_GET['typ'], $logfilter[$_GET['typ']])) {
		$conds[] = "logType IN(".implode(", ", $logfilter[$_GET['typ']]).")";
	}
	
	// Spieler-ID
	if(isset($_GET['uid'])) {
		$conds[] = "log_playerID = ".(int)$_GET['uid'];
	}
	
	// IP
	if(isset($_GET['ip'])) {
		$conds[] = "logIP = '".escape($_GET['ip'])."'";
	}
	
	// Eintrag enthält
	if(isset($_GET['c'])) {
		$conds[] = "logText LIKE '%".escape(escape(str_replace('*', '%', $_GET['c'])))."%'";
	}
}
// Platzhalter-Bedingung einfügen
if(!count($conds)) {
	$conds[] = 1;
}


// Trefferzahl ermitteln
$query = query("
	SELECT
		COUNT(*) AS logCount
	FROM
		".PREFIX."log
	WHERE
		".implode(" AND ", $conds)."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$tcount = mysql_fetch_assoc($query);
$tcount = $tcount['logCount'];

// keine Treffer
if(!$tcount) {
	$content .= '
	<br /><br />
	<div class="center">
	Es wurden keine Eintr&auml;ge gefunden, die den Kriterien entsprechen!
	</div>
	<br /><br />';
}
// Treffer
else {
	// Datatable-Klasse laden
	if(!class_exists('datatable')) {
		include './common/datatable.php';
	}
	
	// Pagebar erzeugen und Offset berechnen
	if(!class_exists('pagebar')) {
		include './common/pagebar.php';
	}
	
	$limit = 100;
	$pagebar = pagebar::generate($tcount, $limit);
	$offset = pagebar::offset($tcount, $limit);
	
	$content .= $pagebar;
	
	// Tabellenheader
	$content .= '
	<table class="data searchtbl" style="width:100%">
	<tr>
	<th>Datum</th>
	<th>Spieler</th>
	<th>IP</th>
	<th>Aktion</th>
	</tr>';
	
	// Einträge abfragen
	$query = query("
		SELECT
			logTime,
			log_playerID,
			logText,
			logIP,
			
			playerName
		FROM
			".PREFIX."log
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = log_playerID
		WHERE
			".implode(" AND ", $conds)."
		ORDER BY
			logID DESC
		LIMIT ".$offset.",".$limit."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$content .= '
	<tr>
	<td>'.datum($row['logTime']).'</td>
	<td>'.datatable::inhaber($row['log_playerID'], $row['playerName']).'</td>
	<td>'.$row['logIP'].'</td>
	<td style="text-align:left">'.$row['logText'].'</td>
	</tr>';
	}
	
	// Tabellenfooter
	$content .= '
	</table>';
	
	$content .= $pagebar;
}

$content .= '
</div>
';

?>