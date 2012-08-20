<?php
/**
 * admin/pages/stats.php
 * Statistiken
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template_admin;
$tmpl->name = 'Statistiken';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Seite
 */

else {
	// Statistiken abfragen
	$data = false;
	$cache_set = false;
	
	// gecached?
	if(!isset($_GET['refresh'])) {
		if(CACHING) {
			$data = $cache->getglobal('admin_stats');
		}
		else {
			if(file_exists('./cache/stats-'.KEY) AND filemtime('./cache/stats-'.KEY) > time()-14400) {
				$data = unserialize(file_get_contents('./cache/stats-'.KEY));
			}
		}
	}
	
	// neu holen
	if($data === false) {
		$heute = strtotime('today');
		
		$data = array();
		$data['time'] = time();
		
		// normale MySQL-Klasse umgehen
		$mysql_conn = new mysql;
		$mysql_conn->connected = true;

		$mysqlconns = array();
		
		// Instanzen durchgehen
		foreach($dbs as $instance=>$instance_name) {
			// Konfigurationsdatei einbinden
			$config = $gconfig;
			
			if(!(@include('../config/config'.$instance.'.php'))) {
				continue;
			}
			
			$data[$instance] = array(
				'name'=>$instance_name
			);
			
			// MySQL-Verbindung
			$mysqlhash = $config['mysql_host'].'-'.$config['mysql_user'];
			
			// Verbindung besteht schon
			if(in_array($mysqlhash, $mysqlconns)) {
				$conn =& ${'mysqlconn'.array_search($mysqlhash, $mysqlconns)};
			}
			// Verbindung aufbauen
			else {
				${'mysqlconn'.$instance} = @mysql_connect(
					$config['mysql_host'],
					$config['mysql_user'],
					$config['mysql_pw']
				);
				
				// Verbindung fehlgeschlagen
				if(!${'mysqlconn'.$instance}) {
					continue;
				}
				
				$conn =& ${'mysqlconn'.$instance};
				$mysqlconns[$instance] = $mysqlhash;
				
				// MySQL auf UTF-8 stellen
				if(function_exists('mysql_set_charset')) {
					mysql_set_charset('utf8', $conn);
				}
				else {
					mysql_query("
						SET NAMES 'UTF8'
					", $conn) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
			}
			
			// Datenbank auswählen
			if(!mysql_select_db($config['mysql_db'], $conn)) {
				continue;
			}
			
			$prefix = $config['mysql_globprefix'].$instance.'_';
			
			// angemeldete User
			$query = mysql_query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."user
			", $conn);
			
			if($query) {
				$row = mysql_fetch_array($query);
				$data[$instance]['user'] = $row[0];
			}
			
			// User heute online
			$query = mysql_query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."user
				WHERE
					userOnlineDB > ".$heute."
			", $conn);
			
			if($query) {
				$row = mysql_fetch_array($query);
				$data[$instance]['user_heute'] = $row[0];
			}
			
			// User heute Plugin
			$query = mysql_query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."user
				WHERE
					userOnlinePlugin > ".$heute."
			", $conn);
			
			if($query) {
				$row = mysql_fetch_array($query);
				$data[$instance]['user_heute_plugin'] = $row[0];
			}
			
			$sys = 0;
			$sys_scanned = 0;
			$sys_aktuell = 0;
			
			// Systeme
			$query = mysql_query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."systeme
			", $conn);
			
			if($query) {
				$row = mysql_fetch_array($query);
				$sys = $row[0];
			}
			
			// Systeme gescannt
			$query = mysql_query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."systeme
				WHERE
					systemeUpdate > 0
			", $conn);
			
			if($query) {
				$row = mysql_fetch_array($query);
				$sys_scanned = $row[0];
			}
			
			// Systeme
			$query = mysql_query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."systeme
				WHERE
					systemeUpdate > ".(time()-21*86400)."
			", $conn);
			
			if($query) {
				$row = mysql_fetch_array($query);
				$sys_aktuell = $row[0];
			}
			
			if($sys) {
				$data[$instance]['sys_scanned'] = $sys_scanned.'/'.$sys.' = '.round($sys_scanned/$sys*100,2).'%';
				
				$data[$instance]['sys_aktuell'] = $sys_aktuell.'/'.$sys.' = '.round($sys_aktuell/$sys*100,2).'%';
			}
		}
		
		// cachen
		if(CACHING) {
			$cache->setglobal('admin_stats', $data , 14400);
		}
		else {
			$fp = fopen('./cache/stats-'.KEY, 'w');
			@fwrite($fp, serialize($data));
			@fclose($fp);
		}
	}
	
	
	$tmpl->content = '
		<div class="icontent">
			<div style="float:right">
				<a class="link" data-link="index.php?p=stats&amp;refresh" style="font-style:italic">Statistiken aktualisieren</a>
			</div>
			aktuell '.datum($data['time'], true).'
			<br /><br />';
	
	// odrequests
	if(CACHING AND ($count = $cache->getglobal('odrequest')) !== false) {
		$tmpl->content .= 'Seit der letzten Downtime wurden '.$count.' Anfragen an die OD-Shoutbox gesendet.
			<br /><br />';
	}
	
	 // Z�hler
	$count_user = 0;
	$count_online = 0;
	$count_plugin = 0;
	
	
	$tmpl->content .= '
			<table class="data" style="margin:auto">
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>User</th>
				<th>heute online (DB / Plugin)</th>
				<th>Systeme gescannt</th>
				<th>Systeme aktuell (21 Tage)</th>
			</tr>';
	foreach($data as $instance=>$stats) {
		if($instance != 'time') {
			$tmpl->content .= '
			<tr>
				<td>'.$instance.'</td>
				<td>'.(trim($stats['name']) != '' ? htmlspecialchars($stats['name'], ENT_COMPAT, 'UTF-8') : ' - ').'</td>
				<td>'.(isset($stats['user']) ? $stats['user'] : '-').'</td>
				<td>'.(isset($stats['user_heute'], $stats['user_heute_plugin']) ? $stats['user_heute'].' / '.$stats['user_heute_plugin'] : '-').'</td>
				<td>'.(isset($stats['sys_scanned']) ? $stats['sys_scanned'] : '-').'</td>
				<td>'.(isset($stats['sys_aktuell']) ? $stats['sys_aktuell'] : '-').'</td>
			</tr>';
			
			if(isset($stats['user'])) {
				$count_user += $stats['user'];
			}
			if(isset($stats['user_heute'])) {
				$count_online += $stats['user_heute'];
			}
			if(isset($stats['user_heute_plugin'])) {
				$count_plugin += $stats['user_heute_plugin'];
			}
		}
	}
	$tmpl->content .= '
				<tr>
					<th colspan="2">gesamt</th>
					<th>'.$count_user.'</th>
					<th>'.$count_online.' / '.$count_plugin.'</th>
					<th colspan="2">&nbsp;</th>
				</tr>
			</table>
		</div>
	';
	
	// Ausgabe
	$tmpl->output();
}

?>