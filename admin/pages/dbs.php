<?php
/**
 * admin/pages/dbs.php
 * Datenbanken verwalten
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template_admin;
$tmpl->name = 'Datenbanken verwalten';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'add'=>true,
	'add_send'=>true,
	'edit'=>true,
	'edit_send'=>true,
	'del'=>true
);


/**
 * Funktionen
 */

/**
 * Instanz-Key erzeugen
 * @return string Key
 */
function generate_key() {
	return substr(md5(time()), rand(0,14), rand(12,16));
}



// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX
 */

// Instanz erstellen - absenden
else if($_GET['sp'] == 'add_send') {
	include './pages/dbs/add.php';
}

// Instanz bearbeiten - absenden
else if($_GET['sp'] == 'edit_send') {
	include './pages/dbs/edit.php';
}

// Instanz löschen
else if($_GET['sp'] == 'del') {
	// Vollständigkeit
	if(!isset($_GET['id']) OR !(int)$_GET['id']) {
		$tmpl->error = 'Keine oder ung&uuml;ltige ID &uuml;bergeben!';
	}
	// Vorhandensein
	else if(!isset($dbs[$_GET['id']])) {
		$tmpl->error = 'Die Datenbank existiert nicht mehr!';
	}
	else if(count($dbs) == 1) {
		$tmpl->error = 'Die letzte Instanz kann nicht gelöscht werden! Du musst zuerst eine neue Instanz erstellen, bevor du diese Instanz löschen kannst!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// MySQL-Tabellen löschen
		$prefix = $config['mysql_globprefix'].$_GET['id'].'_';
		
		// MySQL-Verbindung
		$mysql_conn = new mysql;
		
		
		include '../common/mysql_tables.php';
	
		foreach($tables_del as $sql) {
			$query = query($sql);
			if(!$query) {
				$tmpl->error = 'Löschen der MySQL-Tabellen fehlgeschlagen: '.mysql_error().'<br /><br />(Query '.htmlspecialchars($sql, ENT_COMPAT, 'UTF-8').')';
				break;
			}
		}
		
		// Instanz-Konfiguration löschen
		@unlink('../config/config'.$_GET['id'].'.php');
		
		// globale Konfiguration speichern
		unset($dbs[$_GET['id']]);
		
		General::loadClass('config');
		
		if(!config::saveGlobal('dbs', 'dbs', $dbs)) {
			$tmpl->error .= 'Konnte Liste der Instanzen nicht speichern!';
		}
		
		// Cache leeren
		admincache_clear();
		
		$tmpl->content = 'gel&ouml;scht';
		
		// Tabelle aktualisieren
		$tmpl->script = 'ajaxcall(\'index.php?p=dbs&list&ajax\', $(\'.icontentdblist\'), false, true);';
	}
	
	// ausgeben
	$tmpl->output();
}


/**
 * Seite
 */

// neue Instanz erstellen
else if($_GET['sp'] == 'add') {
	include './pages/dbs/add.php';
}

// Instanz bearbeiten
else if($_GET['sp'] == 'edit') {
	include './pages/dbs/edit.php';
}

// Übersicht
else {
	// MySQL-Verbindungsdaten abfragen
	$data = false;
	$cache_set = false;
	
	// gecached?
	if(!isset($_GET['refresh'])) {
		if(CACHING) {
			$data = $cache->getglobal('admin_dbs');
		}
		else {
			if(file_exists('./cache/dbs-'.KEY) AND filemtime('./cache/dbs-'.KEY) > time()-86400) {
				$data = unserialize(file_get_contents('./cache/dbs-'.KEY));
			}
		}
	}
	
	// neu holen
	if($data === false) {
		$data = array();
		
		// Instanzen durchgehen
		foreach($dbs as $instance=>$instance_name) {
			// Konfigurationsdatei einbinden
			$config = $gconfig;
			
			if(!(@include('../config/config'.$instance.'.php'))) {
				continue;
			}
			
			// MySQL-Verbindung
			$data[$instance] = $config;
		}
		
		// cachen
		if(CACHING) {
			$cache->setglobal('admin_dbs', $data , 86400);
		}
		else {
			$fp = fopen('./cache/dbs-'.KEY, 'w');
			@fwrite($fp, serialize($data));
			@fclose($fp);
		}
	}
	
	if(!isset($_GET['list'])) {
		$tmpl->content = '
			<div class="icontent icontentdblist">';
	}
	
	// Tabelle anzeigen
	$tmpl->content .= '
		<div style="float:right">
			<a class="link" data-link="index.php?p=dbs&amp;refresh" style="font-style:italic">Anzeigen aktualisieren</a>
		</div>
		<a class="link winlink contextmenu" data-link="index.php?p=dbs&amp;sp=add">+ neue DB-Instanz erstellen</a>
		<br /><br />
		<table class="data" style="margin:auto">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>MySQL-Verbindung</th>
			<th>aktiv</th>
			<th>Optionen</th>
		</tr>';
	foreach($dbs as $instance=>$instance_name) {
		if(isset($data[$instance])) {
			$c =& $data[$instance];
		}
		else {
			$c = false;
		}
		
		$tmpl->content .= '
		<tr class="dbrow'.$instance.'">
			<td>'.$instance.'</td>
			<td>'.(trim($instance_name) != '' ? htmlspecialchars($instance_name, ENT_COMPAT, 'UTF-8') : ' - ').'</td>
			<td>'.($c ? 'Tabellen-Pr&auml;fix '.mysql::getPrefix($instance) : '<i>unbekannt</i>').'</td>
			<td>'.($c ? ($c['active'] ? 'ja' : '<span class="tooltip" tooltip="'.htmlspecialchars($c['offlinemsg'], ENT_COMPAT, 'UTF-8').'">nein</span>') : '<i>unbekannt</i>').'</td>
			<td class="userlistaction">
				<img src="../img/layout/leer.gif" style="background-position:-1020px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=dbs&amp;sp=edit&amp;id='.$instance.'" title="Datenbank bearbeiten" />
				'.($instance != 1 ? '<img src="../img/layout/leer.gif" style="background-position:-1040px -91px;cursor:pointer" class="hoverbutton" onclick="if(window.confirm(\'Soll die Datenbank wirklich unwiderruflich gelöscht werden?\\nDU KANNST DIESE AKTION NICHT RÜCKGÄNGIG MACHEN!\')){ajaxcall(\'index.php?p=dbs&amp;sp=del&amp;id='.$instance.'&amp;ajax\', this.parentNode, false, false)}" title="Datenbank l&ouml;schen" />' : '').'
			</td>
		</tr>';
	}
	$tmpl->content .= '	
		</table>
	';
	
	// importieren
	if(!isset($_GET['list'])) {
		$tmpl->content .= '</div>
		';
	}
	
	// Ausgabe
	$tmpl->output();
}

?>