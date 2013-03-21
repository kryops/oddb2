<?php
/**
 * admin/pages/oview.php
 * Übersichtsseite
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template_admin;
$tmpl->name = 'Übersicht';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
);

// MySQL-Verbindung
$mysql_conn = new mysql;

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Seite
 */

// Übersichtsseite anzeigen
else {
	$tmpl->content = '
		<br /><br />
		<div align="center">
			ODDB V'.VERSION.' - OD '.ODWORLD.'
			<br /><br />
			Willkommen im Administrationsbereich der ODDB!
		</div>
		<br /><br /><br />
	';
	
	// Cronjob-Log
	
	for($i=1;$i<=2;$i++) {
		
		$tmpl->content .= '
			<div class="hl2">Cronjob '.$i.'</div>	
		';
		
		$query = query("
			SELECT
				cronjobsTime,
				cronjobsText
			FROM
				".GLOBPREFIX."cronjobs
			WHERE
				cronjobsNumber = ".$i."
			ORDER BY
				cronjobsID DESC
			LIMIT
				15
		");
		
		if(mysql_num_rows($query)) {
			
			$tmpl->content .= '
				<table class="data" style="margin:auto">
				<tr>
					<th>Datum</th>
					<th>Nachricht</th>
				</tr>';
			
			while($row = mysql_fetch_assoc($query)) {
				
				$tmpl->content .= '
					<tr>
						<td>'.datum($row['cronjobsTime']).'</td>
						<td>'.h($row['cronjobsText']).'</td>
					</tr>';
				
			}
			
			$tmpl->content .= '
				</table>
				<br />';
			
		}
		else {
			$tmpl->content .= '<p>Keine Eintr&auml;ge vorhanden</p>';
		}
	}
	
	$tmpl->content .= '
		<br />
		<p class="small center">Hier werden maximal 15 Eintr&auml;ge pro Cronjob angezeigt. Um mehr anzuzeigen, schaue in der MySQL-Tabelle <i>[globprefix]cronjobs</i> nach.</p>
	';
	
	// Ausgabe
	$tmpl->output();
}

?>