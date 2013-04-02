<?php

/**
 * install/common.php
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');


/**
 * Installations-Template
 */
class template_install {
	// Template-Variablen, alle string
	// Name der Seite
	public $name = '';
	// Inhalt der Seite
	public $content = '';
	// JavaScript, das ausgeführt werden soll
	public $script = '';
	// Fehlermeldung
	public $error = '';
	
	/**
	 * Template als XML oder HTML ausgaben
	 */
	public function output() {
		global $time, $queries;
		
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
<link rel="stylesheet" type="text/css" href="install.css" />
<link rel="shortcut icon" href="../favicon.ico" />
<title>ODDB Installation</title>
</head>
<body>

<div id="logo" style="cursor:default"></div>

<div id="install-headline">
	ODDB Installation
</div>

<div id="contentc">
	<div id="contenthl"></div>
	
	<div id="contentbody">
		<div class="content" id="content1" data-link="index.php?'.$_SERVER['QUERY_STRING'].'">
			<div class="hl1">
				'.$this->name.'
			</div>
			<div class="icontent">
			'.$this->content.'
			</div>
		</div>
	</div>
	
	<div id="contentbl"></div>
</div>

<noscript>
	<div class="error noscript">Die Datenbank funktioniert nur mit aktiviertem JavaScript!</div>
</noscript>


<script type="text/javascript" src="../js/jquery.js'.FILESTAMP.'"></script>
<script type="text/javascript">
	'.$this->script.'
</script>

<!-- Rendertime '.number_format(microtime(true)-$time, 6).'s, '.$queries.' Queries -->

</body>
</html>';
	}
	
	/**
	 * Installations-Formular erzeugen
	 */
	public function form() {
		global $bconfig;
		
		// MySQL-Server standard localhost
		// unter Windows 127.0.0.1
		$mysql_standard = isset($_SERVER['WINDIR']) ? '127.0.0.1' : 'localhost';
		
		// Cache-Engines sniffen
		$cacheselect = 0;
		
		if(isset($_POST['caching'])) {
			$cacheselect = (int)$_POST['caching'];
		}
		else if(function_exists('apc_fetch')) {
			$cacheselect = 1;
		}
		
		
		return '
<br />
<form action="index.php" method="post" onsubmit="$(this).find(\'.button\').hide()">
<table class="leftright" style="width:100%">
<tr>
<th colspan="2">Grundeinstellungen</th>
</tr>
<tr>
<td style="width:45%">vollst&auml;ndige Adresse (mit /)</td>
<td><input type="text" class="text" name="addr" value="'.h(isset($_POST['addr']) ? $_POST['addr'] : 'http://'.$_SERVER['HTTP_HOST'].str_replace('install/index.php', '', $_SERVER['SCRIPT_NAME'])).'" /></td>
</tr>
<tr>
<td>Server (ohne /)</td>
<td><input type="text" class="text" name="server" value="'.h(isset($_POST['server']) ? $_POST['server'] : $_SERVER['HTTP_HOST']).'" /></td>
</tr>
<tr>
<td>Sicherheits-Schl&uuml;ssel</td>
<td><input type="text" class="text" name="key" value="'.h(isset($_POST['key']) ? $_POST['key'] : generate_key()).'" /> <br /><span class="small hint">(f&uuml;r die Cronjobs; bitte nur Buchstaben und Zahlen)</span></td>
</tr>
<tr>
<td>Kontaktdaten des Administrators<br />(Impressum, HTML m&ouml;glich)</td>
<td><textarea name="impressum" style="width:300px;height:60px">'.h(isset($_POST['impressum']) ? $_POST['impressum'] : '').'</textarea></td>
</tr>
<tr>
<td>HTML-Code, der immer eingebunden werden soll<br />(Werbung, Counter...)</td>
<td><textarea name="adcode" style="width:300px;height:60px">'.h(isset($_POST['adcode']) ? $_POST['adcode'] : '').'</textarea></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th colspan="2">MySQL</th>
</tr>
<tr>
<td>MySQL-Server</td>
<td><input type="text" class="text" name="mysql_host" value="'.h(isset($_POST['mysql_host']) ? $_POST['mysql_host'] : $mysql_standard).'" /></td>
</tr>
<tr>
<td>MySQL-Benutzer</td>
<td><input type="text" class="text" name="mysql_user" value="'.h(isset($_POST['mysql_user']) ? $_POST['mysql_user'] : $bconfig['mysql_user']).'" /></td>
</tr>
<tr>
<td>MySQL-Passwort</td>
<td><input type="password" class="text" name="mysql_pw" value="'.h(isset($_POST['mysql_pw']) ? $_POST['mysql_pw'] : $bconfig['mysql_pw']).'" /></td>
</tr>
<tr>
<td>MySQL-Datenbank</td>
<td><input type="text" class="text" name="mysql_db" value="'.h(isset($_POST['mysql_db']) ? $_POST['mysql_db'] : $bconfig['mysql_db']).'" /></td>
</tr>
<tr>
<td>MySQL-Tabellenpr&auml;fix</td>
<td><input type="text" class="text" name="mysql_globprefix" value="'.h(isset($_POST['mysql_globprefix']) ? $_POST['mysql_globprefix'] : $bconfig['mysql_globprefix']).'" /></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th colspan="2">Caching</th>
</tr>
<tr>
<td>Cache-Typ</td>
<td><select name="caching" size="1" class="cache_option">
<option value="0">deaktiviert</option>
'.(function_exists('apc_fetch') ? '<option value="1"'.($cacheselect == 1 ? ' selected="selected"' : '').'>APC</option>' : '').'
'.(class_exists('Memcache') ? '<option value="2"'.($cacheselect == 2 ? ' selected="selected"' : '').'>memcached</option>' : '').'
</select></td>
</tr>
<tr class="show_cache">
<td>Cache-Pr&auml;fix</td>
<td><input type="text" class="text" name="caching_prefix" value="'.h(isset($_POST['caching_prefix']) ? $_POST['caching_prefix'] : $bconfig['caching_prefix']).'" /></td>
</tr>
<tr class="show_cache show_memcached">
<td>memcached-Server</td>
<td><input type="text" class="text" name="memcached_host" value="'.h(isset($_POST['memcached_host']) ? $_POST['memcached_host'] : $bconfig['memcached_host']).'" /></td>
</tr>
<tr class="show_cache show_memcached">
<td>memcached-Port</td>
<td><input type="text" class="text" name="memcached_port" value="'.h(isset($_POST['memcached_port']) ? $_POST['memcached_port'] : $bconfig['memcached_port']).'" /></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th colspan="2">Sicherheit</th>
</tr>
<tr>
<td>IP-Ban Versuche (0 = deaktiviert)</td>
<td><input type="text" class="smalltext" name="ipban" value="'.h(isset($_POST['ipban']) ? $_POST['ipban'] : $bconfig['ipban']).'" /></td>
</tr>
<tr>
<td>IP-Ban Bannzeit (Minuten)</td>
<td><input type="text" class="smalltext tooltip" name="ipban_time" value="'.h(isset($_POST['ipban_time']) ? $_POST['ipban_time'] : $bconfig['ipban_time']).'"/></td>
</tr>
<tr>
<td>Flooding-Schutz aktiv</td>
<td><select name="flooding" size="1">
<option value="0">nein</option>
<option value="1"'.((isset($_POST['flooding']) AND $_POST['flooding'] == "1") ? ' selected="selected"' : '').'>ja</option>
</select>
</td>
</tr>
<tr>
<td>Flooding-Zeit (Sekunden)</td>
<td><input type="text" class="smalltext" name="flooding_time" value="'.h(isset($_POST['flooding_time']) ? $_POST['flooding_time'] : $bconfig['flooding_time']).'" /></td>
</tr>
<tr>
<td>max. Seiten in der Flooding-Zeit</td>
<td><input type="text" class="smalltext" name="flooding_pages" value="'.h(isset($_POST['flooding_pages']) ? $_POST['flooding_pages'] : $bconfig['flooding_pages']).'" /></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th colspan="2">Einstellungen f&uuml;r 1. Instanz</th>
</tr>
<tr>
<td>Name der Instanz</td>
<td><input type="text" class="text" name="db_name" value="'.h(isset($_POST['db_name']) ? $_POST['db_name'] : '').'" /></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<td>Administrator-UserID</td>
<td><input type="text" class="text" name="admin" value="'.h(isset($_POST['admin']) ? $_POST['admin'] : '').'" /> <span class="small hint">(deine OD-Spieler-ID)</span></td>
</tr>
<tr>
<td>Passwort</td>
<td><input type="password" class="text" name="admin_passwort" value="'.h(isset($_POST['admin_passwort']) ? $_POST['admin_passwort'] : '').'" /></td>
</tr>
<tr>
<td>(wiederholen)</td>
<td><input type="password" class="text" name="admin_passwort2" value="'.h(isset($_POST['admin_passwort2']) ? $_POST['admin_passwort2'] : '').'" /></td>
</tr>
</table>

<br /><br />
<div class="center">Wenn du die ODDB installieren willst, ohne eine Instanz anzulegen (z.B. vor Rundenstart), lasse die Felder f&uuml;r den Namen der Instanz und die Administrator-ID leer.</div>

<br /><br />
<div class="center">
<input type="submit" class="button" value="Installation starten" style="width:150px" />
</div>
</form>';
	}
}


/**
 * zufälligen Key erzeugen
 * @return string Key
 */
function generate_key() {
	return substr(md5(time()), rand(0,14), rand(12,16));
}



?>