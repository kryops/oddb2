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
				$data = json_decode(file_get_contents('./cache/stats-'.KEY), true);
			}
		}
	}
	
	// neu holen
	if($data === false) {
		$heute = strtotime('today');
		
		$data = array();
		$data['time'] = time();
		
		// MySQL-Verbindung
		$mysql_conn = new mysql;
		
		// Instanzen durchgehen
		foreach($dbs as $instance=>$instance_name) {
			
			$data[$instance] = array(
				'name'=>$instance_name
			);
			
			
			$prefix = mysql::getPrefix($instance);
			
			// angemeldete User
			$query = query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."user
			");
			
			if($query) {
				$row = mysql_fetch_array($query);
				$data[$instance]['user'] = $row[0];
			}
			
			// User in 7 Tagen
			$time7days = time()-604800;
			
			$query = query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."user
				WHERE
					userOnlineDB > ".$time7days."
					OR userOnlinePlugin > ".$time7days."
			");
				
			if($query) {
				$row = mysql_fetch_array($query);
				$data[$instance]['user_aktiv'] = $row[0];
			}
			
			
			$sys = 0;
			$sys_scanned = 0;
			$sys_aktuell = 0;
			
			// Systeme
			$query = query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."systeme
			");
			
			if($query) {
				$row = mysql_fetch_array($query);
				$sys = $row[0];
			}
			
			// Systeme gescannt
			$query = query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."systeme
				WHERE
					systemeUpdate > 0
			");
			
			if($query) {
				$row = mysql_fetch_array($query);
				$sys_scanned = $row[0];
			}
			
			// Systeme aktuell
			$query = query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."systeme
				WHERE
					systemeUpdate > ".(time()-21*86400)."
			");
			
			if($query) {
				$row = mysql_fetch_array($query);
				$sys_aktuell = $row[0];
			}
			
			if($sys) {
				$data[$instance]['sys_scanned'] = round($sys_scanned/$sys*100,2).'%';
				$data[$instance]['sys_aktuell'] = round($sys_aktuell/$sys*100,2).'%';
			}
			
			// Log-Einträge
			$query = query("
				SELECT
					COUNT(*)
				FROM
					".$prefix."log
			");
				
			if($query) {
				$row = mysql_fetch_array($query);
				$data[$instance]['log'] = $row[0];
			}
		}
		
		// cachen
		if(CACHING) {
			$cache->setglobal('admin_stats', $data , 14400);
		}
		else {
			$fp = fopen('./cache/stats-'.KEY, 'w');
			@fwrite($fp, json_encode($data));
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
	
	 // Zähler
	$count_user = 0;
	$count_aktiv = 0;
	
	
	$tmpl->content .= '
			<table class="data" style="margin:auto">
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>User</th>
				<th>User aktiv <span class="small">(7 Tage)</span></th>
				<th>Systeme gescannt / aktuell <span class="small">(21 Tage)</span></th>
				<th>Log-Eintr&auml;ge</th>
			</tr>';
	foreach($data as $instance=>$stats) {
		if($instance != 'time') {
			$tmpl->content .= '
			<tr>
				<td>'.$instance.'</td>
				<td>'.(trim($stats['name']) != '' ? htmlspecialchars($stats['name'], ENT_COMPAT, 'UTF-8') : ' - ').'</td>
				<td>'.(isset($stats['user']) ? $stats['user'] : '-').'</td>
				<td>'.(isset($stats['user_aktiv']) ? $stats['user_aktiv'] : '-').'</td>
				<td>'.(isset($stats['sys_scanned']) ? $stats['sys_scanned'] : '-').' / '.(isset($stats['sys_aktuell']) ? $stats['sys_aktuell'] : '-').'</td>
				<td>'.(isset($stats['log']) ? $stats['log'] : '-').'</td>
			</tr>';
			
			if(isset($stats['user'])) {
				$count_user += $stats['user'];
			}
			if(isset($stats['user_aktiv'])) {
				$count_aktiv += $stats['user_aktiv'];
			}
		}
	}
	$tmpl->content .= '
				<tr>
					<th colspan="2">gesamt</th>
					<th>'.$count_user.'</th>
					<th>'.$count_aktiv.'</th>
					<th colspan="2">&nbsp;</th>
				</tr>
			</table>
		</div>
	';
	
	// Ausgabe
	$tmpl->output();
}

?>