<?php

/**
 * admin/common.php
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');


// Seiten im Adminbereich
$pages = array(
	'oview'=>true,
	'dbs'=>true,
	'settings'=>true,
	'mysql'=>true,
	'stats'=>true
);



/**
 * Admin-Template
 */
class template_admin {
	// Template-Variablen, alle string
	// Icon eines Fensters - [leer], system, planet, player, ally, meta
	public $icon = '';
	// Name der Seite
	public $name = '';
	// Inhalt der Seite
	public $content = '';
	// JavaScript, das ausgeführt werden soll
	public $script = '';
	// Fehlermeldung
	public $error = '';
	// soll dem Tab eine Überschrift hinzugefügt werden?
	public $headline = true;
	
	/**
	 * Template als XML oder HTML ausgaben
	 */
	public function output() {
		global $user, $time, $queries, $cache, $config;
		
		// normale Seite
		// Ausgabe als HTML
		if(!isset($_GET['ajax'])) {
			// Content-Type-Header HTML
			header('Content-Type: text/html; charset=utf-8');
			
			
			// nicht eingeloggte User abfangen
			if(!$user->login) {
				$tmpl = new template_login_admin;
				$tmpl->content = $this->content;
				$tmpl->error = $this->error;
				$tmpl->output();
				die();
			}
			
			// Fehler
			if($this->error) {
				// Seitentitel ändern
				$this->name = 'Fehler!';
				
				// Fehlermeldung rendern
				$this->content = '
					<div class="icontent" style="text-align:center;margin:20px;font-size:16px;font-weight:bold">
						<img src="../img/layout/error.png" width="150" height="137" alt="Fehler" />
						<br /><br />
						'.$this->error.'
					</div>';
				
				// Scripts entfernen
				$this->script = '';
			}
			
			// Template ausgeben
			echo '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" type="text/css" href="../css/main.css'.FILESTAMP.'" />
<link rel="stylesheet" type="text/css" href="css/admin.css'.FILESTAMP.'" />
<link rel="shortcut icon" href="../favicon.ico" />
<title>'.$this->name.' - ODDB Admin</title>
</head>
<body>

<div id="logo"></div>

<div id="admin-headline">
	ODDB Administrationsbereich
</div>

<div id="menu">
	<a data-link="index.php?p=dbs"'.($_GET['p'] == 'dbs' ? ' class="active"' : '').'>DBs VERWALTEN</a>
	<a data-link="index.php?p=settings"'.($_GET['p'] == 'settings' ? ' class="active"' : '').'>EINSTELLUNGEN</a>
	<a data-link="index.php?p=mysql"'.($_GET['p'] == 'mysql' ? ' class="active"' : '').'>MySQL-BEFEHLE</a>
	<a data-link="index.php?p=stats"'.($_GET['p'] == 'stats' ? ' class="active"' : '').'>STATISTIKEN</a>
</div>

<div id="contentc">
	<div id="contenthl"></div>
	
	<div id="contentbody">
		<div class="content" id="content1" data-link="index.php?'.$_SERVER['QUERY_STRING'].'">
			<div class="hl1">
				<div class="hl1buttons">
					<div style="background-position:-215px -44px" title="Seite zu den Favoriten hinzuf&uuml;gen" onclick="fav_add(this, 1)"></div>
					<div style="background-position:-240px -44px" class="favbutton" title="Seite in einem Fenster &ouml;ffnen" onclick="win_fromcontent2(this, \''.addslashes($this->name).'\', false)"></div>
					<div class="ctabclose" title="Tab schlie&szlig;en" onclick="tab_close2(this)"></div>
				</div>
				'.$this->name.'
			</div>
			
			'.$this->content.'
		</div>
	</div>
	
	<div id="contentbl"></div>
</div>

<div id="tabbar"></div>

<div id="windowbar">
	<div id="startbutton"></div>
</div>

<div id="startmenu">
	<div class="cmborder" style="background-position:-528px -59px"></div>
	<div id="startmenuc">
		<a href="index.php?p=logout">Logout</a>
	</div>
	<div class="cmborder" style="background-position:-528px -65px"></div>
</div>

<div id="contextmenu">
	<div class="cmborder" style="background-position:-528px -59px"></div>
	<div id="contextmenuc"></div>
	<div class="cmborder" style="background-position:-528px -65px"></div>
</div>

<div id="tooltip"></div>

<div id="ajax">
	<img src="../img/layout/ajax.gif" width="24" height="24" alt="laden" />
</div>

<noscript>
	<div class="error noscript">Die Datenbank funktioniert nur mit aktiviertem JavaScript!</div>
</noscript>


<script type="text/javascript" src="../js/jquery.js'.FILESTAMP.'"></script>
<script type="text/javascript" src="../js/general.js'.FILESTAMP.'"></script>
<script type="text/javascript">
var tabs = [[1, \''.addslashes($this->name).'\']];

var settings = {
	\'wminoncontent\' : true,
	\'newtabswitch\' : true,
	\'winlinknew\' : true,
	\'winlink2tab\' : false,
	\'closeontransfer\' : true,
	\'szgrtype\' : 5,
	\'effects\' : 200
};';
			echo '
	'.$this->script.'
</script>

<!-- Rendertime '.number_format(microtime(true)-$time, 6).'s, '.$queries.' Queries -->

</body>
</html>';
		}
		// AJAX-Request
		// Ausgabe als XML
		else {
			// bei Fehlern Inhalt und Scripts zurücksetzen
			if($this->error) {
				$this->content = '';
				$this->script = '';
			}
			
			// Content-Type-Header XML
			header('Content-Type:text/xml; charset=utf-8');
			
			// Rendertime berechnen
			$time = number_format(microtime(true)-$time, 6);
			
			// Template ausgeben
			echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
		<page>
			<error><![CDATA['.$this->error.']]></error>
			<time><![CDATA['.$time.']]></time>
			<queries><![CDATA['.$queries.']]></queries>
			<icon><![CDATA['.$this->icon.']]></icon>
			<name><![CDATA['.$this->name.']]></name>
			<content><![CDATA['.$this->content.']]></content>
			<script><![CDATA['.$this->script.']]></script>
		</page>';
		}
	}
}


/**
 * Funktionen
 */

/**
 * File-Cache leeren
 */
function admincache_clear() {
	if(CACHING) {
		global $cache;
		
		// Werte, die gelöscht werden sollen
		$keys = array(
			'admin_dbs'
		);
		
		foreach($keys as $key) {
			$cache->removeglobal($key);
		}
		
		return true;
	}
	else {
		$dir = './cache/';
		if($handle = @opendir($dir)) {
			while (($file = readdir($handle)) !== false) {
				if(is_file($dir.$file) AND $file != '.' AND $file != '..' AND $file != 'index.html') {
					@unlink($dir.$file);
				}
			}
			@closedir($handle);
			
			return true;
		}
		else return false;
	}
}



?>