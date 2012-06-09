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


/**
 * Konfiguration einer Instanz zurückgeben
 * @param $instance int Instanz-ID
 * @return array/false Konfiguration
 */
function getconfig($instance) {
	global $bconfig;
	
	$config = $bconfig;
	if(@include('../config/config'.$instance.'.php')) {
		return $config;
	}
	else {
		return false;
	}
}


/**
 * Instanz-Konfiguration speichern
 * @param $instance int DB-Instanz
 * @param $config array Konfiguration
 *
 * @return bool Erfolg
 */
function config_save($instance, $config, $rechte) {
	// valide ID übergeben?
	if(!is_int($instance)) {
		return false;
	}
	
	// Dateininhalt erzeugen
	$content = '<'.'?php

/**
 * config.php
 * manipuliert die Instanz-spezifischen Einstellungen
 */

// Sicherheitsabfrage
if(!defined(\'ODDB\')) die(\'unerlaubter Zugriff!\');

';
	
	// Einstellungen
	foreach($config as $key=>$val) {
		$content .= '$config[\''.addslashes($key).'\'] = ';
		if(is_bool($val)) {
			$content .= $val ? 'true' : 'false';
		}
		else if(is_int($val)) {
			$content .= $val;
		}
		else {
			$content .= '\''.addslashes($val).'\'';
		}
		$content .= ';
';
	}
	
	$content .= '
';
	
	// Rechte
	if($rechte !== false) {
		// Einstellungen
		foreach($rechte as $key=>$val) {
			if(count($val)) {
				foreach($val as $key2=>$val2) {
					$content .= '$rechte['.$key.'][\''.addslashes($key2).'\'] = ';
					if(is_bool($val2)) {
						$content .= $val2 ? 'true' : 'false';
					}
					else if(is_int($val2)) {
						$content .= $val2;
					}
					else {
						$content .= '\''.addslashes($val2).'\'';
					}
					$content .= ';
';
				}
			}
		}
	}

	$content .= '

?'.'>';
	
	$fp = @fopen('../config/config'.$instance.'.php', 'w');
	if(!$fp) {
		return false;
	}
	fwrite($fp, $content);
	fclose($fp);
	
	// Erfolg
	return true;
}

/**
 * globale Konfiguration speichern (DB-Array)
 * @return bool Erfolg
 */
function globalconfig_save() {
	// Inhalt einlesen
	if(!($content = file_get_contents('../globalconfig.php'))) {
		return false;
	}
	
	// neues Array erzeugen
	global $dbs;
	
	$count = count($dbs);
	if($count <= 1) {
		$new = '/***_dbs_***/

$dbs = false;

/***_/dbs_***/';
	}
	else {
		$i = 1;
		$new = '/***_dbs_***/

$dbs = array(';
		foreach($dbs as $key=>$val) {
			if($val == '') {
				$val = '[unbenannt]';
			}
			$new .= '
	'.$key.'=>\''.addslashes($val).'\'';
			if($i < $count) {
				$new .= ',';
			}
			$i++;
		}
		$new .= '
);

/***_/dbs_***/';
	}
	
	// Inhalt ersetzen
	$content = preg_replace('#/\*\*\*_dbs_\*\*\*/(?:.|\s)*/\*\*\*_/dbs_\*\*\*/#Uis', '{***_dbs_***}', $content);
	$content = str_replace('{***_dbs_***}', $new, $content);
	
	$fp = @fopen('../globalconfig.php', 'w');
	if(!$fp) {
		return false;
	}
	fwrite($fp, $content);
	fclose($fp);
	
	return true;
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
	// Kein Name eingegeben
	if(!isset($_POST['name']) OR trim($_POST['name']) == '') {
		$tmpl->error = 'Kein Name eingegeben!';
	}
	// Keine User-ID eingegeben
	else if(!isset($_POST['admin']) OR (int)$_POST['admin'] == 0) {
		$tmpl->error = 'Keine oder ung&uuml;ltige User-ID eingegeben!';
	}
	else {
		// Daten sichern
		$time = 0;
		if(isset($_GET['time'])) {
			$time = (int)$_GET['time'];
		}
		
		// Daten aufbereiten
		foreach($_POST as $key=>$val) {
			if($val === '') {
				unset($_POST[$key]);
			}
		}
		
		$_POST['active'] = isset($_POST['active']);
		
		$name = $_POST['name'];
		unset($_POST['name']);
		
		$admin = (int)$_POST['admin'];
		unset($_POST['admin']);
		
		if(isset($_POST['data_copy'])) {
			$data_copy = (int)$_POST['data_copy'];
			unset($_POST['data_copy']);
		}
		else {
			$data_copy = 0;
		}
		
		$mysql_use = false;
		if(isset($_POST['mysql_use'])) {
			$mysql_use = true;
			unset($_POST['mysql_use']);
		}
		
		if(isset($_POST['disable_freischaltung'])) {
			$_POST['disable_freischaltung'] = (bool)$_POST['disable_freischaltung'];
		}
		
		if(isset($_POST['disable_freischaltung_level'])) {
			if($_POST['disable_freischaltung_level'] === '' OR !isset($rechte[$_POST['disable_freischaltung_level']])) {
				unset($_POST['disable_freischaltung_level']);
			}
			else $_POST['disable_freischaltung_level'] = (int)$_POST['disable_freischaltung_level'];
		}
		
		if(isset($_POST['logging'])) {
			$_POST['logging'] = (int)$_POST['logging'];
			if($_POST['logging'] < 0 OR $_POST['logging'] > 3) {
				unset($_POST['logging']);
			}
		}
		
		if(isset($_POST['logging_time'])) {
			$_POST['logging_time'] = (int)$_POST['logging_time'];
			if($_POST['logging_time'] < 1) {
				unset($_POST['logging_time']);
			}
		}
		
		// Schlüssel erzeugen
		$_POST['key'] = generate_key();
		
		// ID ermitteln
		$dbkeys = array_keys($dbs);
		sort($dbkeys);
		$instance = array_pop($dbkeys)+1;
		
		$dbs[$instance] = $name;
		
		// fertige Konfiguration erzeugen
		$config = $bconfig;
		foreach($_POST as $key=>$val) {
			$config[$key] = $val;
		}
		
		$prefix = $config['mysql_prefix'];
		
		// normale MySQL-Klasse umgehen
		$mysql_conn = new mysql;
		$mysql_conn->connected = true;
		
		// MySQL-Verbindung testen
		if(!($conn = @mysql_connect($config['mysql_host'], $config['mysql_user'], $config['mysql_pw']))) {
			$tmpl->error = 'MySQL-Verbindung fehlgeschlagen: '.mysql_error();
		}
		else {
			// MySQL auf UTF-8 stellen
			if(function_exists('mysql_set_charset')) {
				mysql_set_charset('utf8');
			}
			else {
				mysql_query("
					SET NAMES 'UTF8'
				");
			}
			
			if(!mysql_select_db($config['mysql_db'])) {
				$tmpl->error = 'Datenbank konnte nicht ausgewählt werden: '.mysql_error();
			}
			else {
				// Tabellen schon vorhanden?
				$query = mysql_query("
					SHOW TABLES LIKE '".$prefix."planeten'
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// vorhanden
				if(mysql_num_rows($query)) {
					// nicht ignoriert
					if(!$mysql_use) {
						$tmpl->error = 'Es sind bereits MySQL-Tabellen mit diesem Pr&auml;fix vorhanden!<br />&Auml;ndere es oder aktiviere die Option zum Benutzen vorhandener Installationen!';
					}
				}
				else {
					$mysql_use = false;
				}
			}
		}
		
		// Vorhandensein des Administrators
		$file = 'http://www.omega-day.com/game/states/live_state.php?userid='.$admin.'&world='.ODWORLD;
		$connection = @fopen($file,'r');
		if(!$connection) {
			$tmpl->error = 'Konnte keine Verbindung zum OD-Server aufbauen!';
		}
		else {
			$buffer = fgets($connection, 4096);
			fclose($connection);
			
			// als Array parsen
			parse_str($buffer, $oddata);
			
			// User nicht vorhanden oder gelöscht
			if(!isset($oddata['name']) OR $oddata['name'] == '') {
				$tmpl->error = 'Der Account mit der User-ID '.$admin.' ist nicht vorhanden oder hat sich gel&ouml;scht!';
			}
		}
		
		// Tabellen erzeugen
		if(!$mysql_use AND !$tmpl->error) {
			include '../common/mysql_tables.php';
			
			foreach($tables_add as $sql) {
				$query = query($sql);
				if(!$query) {
					$tmpl->error = 'Anlegen der MySQL-Tabellen fehlgeschlagen: '.mysql_error().'<br /><br />(Query '.htmlspecialchars($sql, ENT_COMPAT, 'UTF-8').')';
					break;
				}
			}
			
			
			// Grunddaten aus anderer Instanz übernehmen
			$copyerror = '';
			
			if(!$tmpl->error AND $data_copy AND isset($dbs[$data_copy])) {
				// Zeitlimit erhöhen
				@set_time_limit(300);
				
				// Konfiguration einbinden
				if($dconfig = getconfig($data_copy)) {
					$dprefix = $dconfig['mysql_prefix'];
					
					if($dataconn = @mysql_connect($dconfig['mysql_host'], $dconfig['mysql_user'], $dconfig['mysql_pw'])) {
						// MySQL auf UTF-8 stellen
						if(function_exists('mysql_set_charset')) {
							mysql_set_charset('utf8', $dataconn);
						}
						else {
							mysql_query("
								SET NAMES 'UTF8'
							", $dataconn);
						}
						
						// DB auswählen
						if(mysql_select_db($dconfig['mysql_db'], $dataconn)) {
							
							// Galaxien übertragen
							$query = mysql_query("
								SELECT
									galaxienID,
									galaxienSysteme
								FROM
									".$dprefix."galaxien
							", $dataconn);
							
							if($query) {
								$copyarray = array();
								
								while($row = mysql_fetch_assoc($query)) {
									$copyarray[] = "(".$row['galaxienID'].",".$row['galaxienSysteme'].")";
								}
								
								if(count($copyarray)) {
									$copyarray = "INSERT INTO ".$prefix."galaxien
											(galaxienID, galaxienSysteme)
											VALUES
											".implode(', ', $copyarray);
									
									if(!mysql_query($copyarray, $conn)) {
										$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Galaxien2]! (Fehler: '.mysql_error().')';
									}
								}
								
								mysql_free_result($query);
							}
							else {
								$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Galaxien]! (Fehler: '.mysql_error().')';
							}
							
							// Systeme übertragen
							$query = mysql_query("
								SELECT
									systemeID,
									systeme_galaxienID,
									systemeName,
									systemeX,
									systemeY,
									systemeZ,
									systemeUpdateHidden
								FROM
									".$dprefix."systeme
							", $dataconn);
							
							$i = 1;
							
							if($query) {
								$copyarray = array();
								
								while($row = mysql_fetch_assoc($query)) {
									$copyarray[] = "(".$row['systemeID'].",".$row['systeme_galaxienID'].",'".mysql_real_escape_string($row['systemeName'])."',".$row['systemeX'].",".$row['systemeY'].",".$row['systemeZ'].",".($row['systemeUpdateHidden'] ? 1 : 0).")";
									
									if($i == 5000) {
										$copyarray = "INSERT INTO ".$prefix."systeme
											(systemeID, systeme_galaxienID, systemeName, systemeX, systemeY, systemeZ, systemeUpdateHidden)
											VALUES
											".implode(', ', $copyarray);
									
										if(!mysql_query($copyarray, $conn)) {
											$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Systeme3]! (Fehler: '.mysql_error().')';
										}
										
										$copyarray = array();
										$i = 0;
									}
									
									$i++;
								}
								
								if(count($copyarray)) {
									$copyarray = "INSERT INTO ".$prefix."systeme
											(systemeID, systeme_galaxienID, systemeName, systemeX, systemeY, systemeZ, systemeUpdateHidden)
											VALUES
											".implode(', ', $copyarray);
									
									if(!mysql_query($copyarray, $conn)) {
										$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Systeme2]! (Fehler: '.mysql_error().')';
									}
								}
								
								mysql_free_result($query);
							}
							else {
								$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Systeme]! (Fehler: '.mysql_error().')';
							}
							
							echo $copyerror;
							
							// Planeten übertragen
							$query = mysql_query("
								SELECT
									planetenID,
									planeten_systemeID,
									planetenPosition,
									planetenName,
									planetenTyp,
									planetenGroesse,
									planetenRWErz,
									planetenRWWolfram,
									planetenRWKristall,
									planetenRWFluor,
									planetenBevoelkerung
								FROM
									".$dprefix."planeten
							", $dataconn);
							
							if($query) {
								$copyarray = array();
								
								$i = 1;
								
								while($row = mysql_fetch_assoc($query)) {
									$copyarray[] = "(".$row['planetenID'].",".$row['planeten_systemeID'].",".$row['planetenPosition'].",'".mysql_real_escape_string($row['planetenName'])."',".$row['planetenTyp'].",".$row['planetenGroesse'].",".$row['planetenRWErz'].",".$row['planetenRWWolfram'].",".$row['planetenRWKristall'].",".$row['planetenRWFluor'].",".$row['planetenBevoelkerung'].",-1)";
									
									// alle 5000 Planeten Query absetzen
									if($i == 5000) {
										
										$copyarray = "INSERT INTO ".$prefix."planeten
											(planetenID, planeten_systemeID, planetenPosition, planetenName, planetenTyp, planetenGroesse, planetenRWErz, planetenRWWolfram, planetenRWKristall, planetenRWFluor, planetenBevoelkerung, planeten_playerID)
											VALUES
											".implode(', ', $copyarray);
									
											if(!mysql_query($copyarray, $conn)) {
												$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Planeten3]! (Fehler: '.mysql_error().')';
											}
										
										$i = 0;
										$copyarray = array();
									}
									
									$i++;
								}
								
								if(count($copyarray)) {
									$copyarray = "INSERT INTO ".$prefix."planeten
											(planetenID, planeten_systemeID, planetenPosition, planetenName, planetenTyp, planetenGroesse, planetenRWErz, planetenRWWolfram, planetenRWKristall, planetenRWFluor, planetenBevoelkerung, planeten_playerID)
											VALUES
											".implode(', ', $copyarray);
									
									if(!mysql_query($copyarray, $conn)) {
										$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Planeten2]! (Fehler: '.mysql_error().')';
									}
								}
								
								mysql_free_result($query);
							}
							else {
								$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Planeten]! (Fehler: '.mysql_error().')';
							}
							
						}
						else {
							$copyerror = 'Kopieren der Grunddaten fehlgeschlagen! (Konnte Datenbank nicht ausw&auml;hlen: '.mysql_error().')';
						}
					}
					else {
						$copyerror = 'Kopieren der Grunddaten fehlgeschlagen! (MySQL-Verbindung fehlgeschlagen: '.mysql_error().')';
					}
				}
				else {
					$copyerror = 'Kopieren der Grunddaten fehlgeschlagen! (Konnte Konfigurationsdatei nicht &ouml;ffnen)';
				}
			}
		}
		
		if(!$tmpl->error) {
			// Administrator-Account anlegen
			odrequest($admin, true);
			
			// Passwort erzeugen
			$v = array("a", "e", "i", "o", "u");
			$c = array("b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v", "w", "x", "z");
			$vc = count($v)-1;
			$cc = count($c)-1;
			
			$pw = '';
			for($i=1;$i<=3;$i++) {
				$pw .= $c[rand(0, $cc)].$v[rand(0, $vc)];
			}
			$pw .= rand(10,99);
			
			$query = query("
				INSERT INTO
					".$prefix."register
				SET
					register_playerID = ".$admin."
			");
			if(!$query) {
				$tmpl->error = 'Fehler beim Anlegen des Administrator-Accounts! '.mysql_error().'<br />';
			}
			
			$query = query("
				SELECT
					playerName,
					player_allianzenID
				FROM
					".GLOBPREFIX."player
				WHERE
					playerID = ".$admin."
			");
			if(!$query OR !mysql_num_rows($query)) {
				$tmpl->error = 'Fehler beim Anlegen des Administrator-Accounts!<br />';
			}
			else {
				$data = mysql_fetch_assoc($query);
				
				$settings = $bsettings;
				$settings['scout'] = $config['scan_veraltet'];
				$settings['fow'] = serialize($bfowsettings);
				$settings = serialize($settings);
				
				$query = query("
					INSERT INTO
						".$prefix."user
					SET
						user_playerID = ".$admin.",
						user_playerName = '".escape($data['playerName'])."',
						user_allianzenID = ".$data['player_allianzenID'].",
						userRechtelevel = 4,
						userPassword = '".md5($pw)."',
						userSettings = '".escape($settings)."'
				");
				if(!$query) {
					$tmpl->error = 'Fehler beim Anlegen des Administrator-Accounts! '.mysql_error().'<br />';
				}
				else {
					// Fenster mit Passwort öffnen
					$content = '<div class="center icontent"><br />Die Instanz wurde erfolgreich erstellt.<br /><br />Der Account '.addslashes(htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8')).' wurde mit dem Passwort <b>'.$pw.'</b> angelegt.</div><br /><br />';
					$tmpl->script = 'win_open(\'\', \'Instanz angelegt\', \''.$content.'\', 450, \'\');
';
				}
			}
			
			// Instanz-Konfiguration speichern
			if(!config_save($instance, $_POST, false)) {
				$tmpl->error .= 'Konnte Konfiguration nicht speichern!';
			}
			// DB-Array in der globalconfig.php speichern
			else if(!globalconfig_save()) {
				$tmpl->error .= 'Konnte globale Konfiguration nicht speichern!';
			}
			// erfolgreich
			else {
				$tmpl->content = 'Die Instanz wurde erfolgreich erstellt.';
				
				// Fehler beim Übertragen der Daten anhängen
				if($copyerror) {
					$tmpl->error .= '<br />'.$copyerror;
				}
				
				// Ohne Fehler Fenster schließen
				if(!$tmpl->error) {
					$tmpl->script .= 'parentwin_close(\'.dbadd'.$time.'\');
';
				}
				// Fehler in Content umwandeln
				else {
					$tmpl->content = '<div class="error">'.$tmpl->error.'</div>';
					$tmpl->error = '';
					$tmpl->script .= '$(\'.dbadd'.$time.' .button\').attr(\'disabled\', \'disabled\').val(\'Instanz fehlerhaft erstellt\');
';
				}
				
				// Cache leeren
				admincache_clear();
				
				// Tabelle aktualisieren
				$tmpl->script .= 'ajaxcall(\'index.php?p=dbs&list&ajax\', $(\'.icontentdblist\'), false, true);';
			}
		}
	}
	
	// ausgeben
	$tmpl->output();
}

// Instanz bearbeiten - absenden
else if($_GET['sp'] == 'edit_send') {
	// Vollständigkeit
	if(!isset($_GET['id']) OR !(int)$_GET['id']) {
		$tmpl->error = 'Keine oder ung&uuml;ltige ID &uuml;bergeben!';
	}
	// Vorhandensein
	if(!isset($dbs[$_GET['id']])) {
		$tmpl->error = 'Die Datenbank existiert nicht mehr!';
	}
	// Kein Name eingegeben
	if(count($dbs) > 1 AND (!isset($_POST['name']) OR trim($_POST['name']) == '')) {
		$tmpl->error = 'Kein Name eingegeben!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Daten aufbereiten
		$config = array();
		$rechte = array(
			0=>array(),
			1=>array(),
			2=>array(),
			3=>array(),
			4=>array()
		);
		
		@include('../config/config'.$_GET['id'].'.php');
		
		foreach($_POST as $key=>$val) {
			if($val === '') {
				unset($_POST[$key]);
			}
		}
		
		// Schlüssel erzeugen
		$_POST['key'] = isset($config['key']) ? $config['key'] : generate_key();
		
		$_POST['active'] = isset($_POST['active']);
		
		$name = '';
		if(isset($_POST['name'])) {
			$name = $_POST['name'];
			$dbs[$_GET['id']] = $name;
			unset($_POST['name']);
		}
		
		if(isset($_POST['disable_freischaltung'])) {
			$_POST['disable_freischaltung'] = (bool)$_POST['disable_freischaltung'];
		}
		
		if(isset($_POST['disable_freischaltung_level'])) {
			if($_POST['disable_freischaltung_level'] === '' OR !isset($rechte[$_POST['disable_freischaltung_level']])) {
				unset($_POST['disable_freischaltung_level']);
			}
			else $_POST['disable_freischaltung_level'] = (int)$_POST['disable_freischaltung_level'];
		}
		
		if(isset($_POST['logging'])) {
			$_POST['logging'] = (int)$_POST['logging'];
			if($_POST['logging'] < 0 OR $_POST['logging'] > 3) {
				unset($_POST['logging']);
			}
		}
		
		if(isset($_POST['logging_time'])) {
			$_POST['logging_time'] = (int)$_POST['logging_time'];
			if($_POST['logging_time'] < 1) {
				unset($_POST['logging_time']);
			}
		}
		
		
		// Instanz-Konfiguration speichern
		if(!config_save($_GET['id'], $_POST, $rechte)) {
			$tmpl->error = 'Konnte Konfiguration nicht speichern!';
		}
		// DB-Array in der globalconfig.php speichern
		else if(!globalconfig_save()) {
			$tmpl->error = 'Konnte globale Konfiguration nicht speichern!';
		}
		// erfolgreich
		else {
			$tmpl->content = 'Die Konfiguration wurde erfolgreich gespeichert.';
			
			// Cache leeren
			admincache_clear();
			
			// Tabelle aktualisieren
			$c = $bconfig;
			foreach($config as $key=>$val) {
				$c[$key] = $val;
			}
			foreach($_POST as $key=>$val) {
				$c[$key] = $val;
			}
			
			$instance = $_GET['id'];
			$instance_name = $name;
			
			$tmpl->script = '$(\'.dbrow'.$instance.'\').html(\''.addslashes('<td>'.$instance.'</td><td>'.(trim($instance_name) != '' ? htmlspecialchars($instance_name, ENT_COMPAT, 'UTF-8') : ' - ').'</td><td>'.($c ? htmlspecialchars($c['mysql_user'].'@'.$c['mysql_host'].' - [DB] '.$c['mysql_db'].' [Prefix] '.$c['mysql_prefix'], ENT_COMPAT, 'UTF-8') : '<i>unbekannt</i>').'</td><td>'.($c ? ($c['active'] ? 'ja' : '<span class="tooltip" tooltip="'.htmlspecialchars($c['offlinemsg'], ENT_COMPAT, 'UTF-8').'">nein</span>') : '<i>unbekannt</i>').'</td><td class="userlistaction"><img src="../img/layout/leer.gif" style="background-position:-1020px -91px" class="link winlink contextmenu hoverbutton" link="index.php?p=dbs&sp=edit&id='.$instance.'" title="Datenbank bearbeiten" />'.($instance != 1 ? ' <img src="../img/layout/leer.gif" style="background-position:-1040px -91px;cursor:pointer" class="hoverbutton" onclick="if(window.confirm(\'Soll die Datenbank wirklich unwiderruflich gelöscht werden?\')){ajaxcall(\'index.php?p=dbs&sp=del&id='.$instance.'&ajax\', this.parentNode, false, false)}" title="Datenbank l&ouml;schen" />' : '').'</td>').'\');
parentwin_close(\'.dbedit'.$instance.'\');';
		}
	}
	
	// ausgeben
	$tmpl->output();
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
		
		// Daten aufbereiten
		$config = $bconfig;
		
		@include('../config/config'.$_GET['id'].'.php');
		
		
		// MySQL-Tabellen löschen
		$prefix = $config['mysql_prefix'];
		
		// normale MySQL-Klasse umgehen
		$mysql_conn = new mysql;
		$mysql_conn->connected = true;
		
		// MySQL-Verbindung testen
		if(!(@mysql_connect($config['mysql_host'], $config['mysql_user'], $config['mysql_pw']))) {
			$tmpl->error = 'MySQL-Verbindung fehlgeschlagen: '.mysql_error();
		}
		else {
			// MySQL auf UTF-8 stellen
			if(function_exists('mysql_set_charset')) {
				mysql_set_charset('utf8');
			}
			else {
				mysql_query("
					SET NAMES 'UTF8'
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
			
			if(!mysql_select_db($config['mysql_db'])) {
				$tmpl->error = 'Datenbank konnte nicht ausgewählt werden: '.mysql_error();
			}
			else {
				include '../common/mysql_tables.php';
			
				foreach($tables_del as $sql) {
					$query = query($sql);
					if(!$query) {
						$tmpl->error = 'Löschen der MySQL-Tabellen fehlgeschlagen: '.mysql_error().'<br /><br />(Query '.htmlspecialchars($sql, ENT_COMPAT, 'UTF-8').')';
						break;
					}
				}
			}
		}
		
		// Instanz-Konfiguration löschen
		@unlink('../config/config'.$_GET['id'].'.php');
		
		// globale Konfiguration speichern
		unset($dbs[$_GET['id']]);
		if(!globalconfig_save()) {
			$tmpl->error .= '
Konnte globale Konfiguration nicht speichern!';
		}
		
		// gerenderte Bilder dieser Instanz löschen
		$dir = '../render/';
		if($handle = @opendir($dir)) {
			while (($file = readdir($handle)) !== false) {
				if(is_file($dir.$file) AND strpos($file, $config['key']) !== false) {
					@unlink($dir.$file);
				}
			}
			closedir($handle);
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
	$tmpl->name = 'neue DB-Instanz erstellen';
	
	$time = time();
	
	// nächsten MySQL-Prefix berechnen
	$dbkeys = array_keys($dbs);
	sort($dbkeys);
	$nextid = array_pop($dbkeys)+1;
	
	$tmpl->content = '<div class="icontent dbadd'.$time.'">
	Die eingeklammerten Werte hinter den Eingabefeldern sind die Standardwerte.
	<br />
	L&auml;sst du diese Felder leer, nimmt die Konfiguration die eingeklammerten Werte an (k&ouml;nnen in der globalconfig.php ge&auml;ndert werden).
	<br /><br />
	<form onsubmit="return false">
	<table class="leftright" style="width:100%">
	<tr>
		<th colspan="2">Grundeinstellungen</th>
	</tr>
	<tr>
		<td>Name</td>
		<td><input type="text" class="text" name="name" /></td>
	</tr>
	<tr>
		<td>aktiv</td>
		<td><input type="checkbox" name="active" checked="checked" /></td>
	</tr>
	<tr>
		<td>inaktiv-Nachricht</td>
		<td><input type="text" class="text tooltip" style="width:300px" name="offlinemsg" tooltip="wird angezeigt, wenn die Datenbank auf inaktiv gesetzt ist" />
		'.($bconfig['offlinemsg'] ? '<br /><span class="small hint">('.htmlspecialchars($bconfig['offlinemsg'], ENT_COMPAT, 'UTF-8').')</span>' : '').'</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<th colspan="2">MySQL</th>
	</tr>
	<tr>
		<td>Tabellen-Pr&auml;fix</td>
		<td>
			<input type="text" class="text" name="mysql_prefix" value="oddb'.$nextid.'_" />
			&nbsp;
			<input type="checkbox" name="mysql_use" /> <span class="togglecheckbox tooltip" data-name="mysql_use" tooltip="Falls schon MySQL-Tabellen mit diesem Pr&auml;fix vorhanden sind, wird keine Warnung ausgegeben">vorhandene Installation benutzen</span>
			</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<th colspan="2">Sicherheit</th>
	</tr>
	<tr>
		<td>Spieler automatisch freischalten</td>
		<td><select name="disable_freischaltung" size="1">
		<option value=""></option>
		<option value="0">nein</option>
		<option value="1">ja</option>
		</select> <span class="small hint">('.($bconfig['disable_freischaltung'] ? 'ja' : 'nein').')</span></td>
	</tr>
	<tr>
		<td>Autofreischaltung Rechtelevel</td>
		<td><select name="disable_freischaltung_level" size="1">
		<option value=""></option>';
	foreach($rechte as $key=>$data) {
		$tmpl->content .= '
		<option value="'.$key.'">'.htmlspecialchars($data['name'], ENT_COMPAT, 'UTF-8').'</option>';
	}
	$tmpl->content .= '
		</select> <span class="small hint">('.htmlspecialchars($rechte[$bconfig['disable_freischaltung_level']]['name'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Logging-Stufe</td>
		<td><select name="logging" size="1" class="tooltip" tooltip="[vorsichtig] loggt alle Aktionen eines Spielers, [paranoid] loggt zus&auml;tzlich alle Seiten, die ein Spieler ansieht (z.B. Invasionen oder Spielerlisten)">
		<option value=""></option>
		<option value="0">deaktiviert</option>
		<option value="1">nur Verwaltung</option>
		<option value="2">vorsichtig</option>
		<option value="3">paranoid</option>
		</select> <span class="small hint">(';
	$data = array(
		0=>'deaktiviert',
		1=>'nur Verwaltung',
		2=>'vorsichtig',
		3=>'paranoid'
	);
	$tmpl->content .= $data[$bconfig['logging']].')</span></td>
	</tr>
	<tr>
		<td>Speicherdauer des Logs (Tage)</td>
		<td><input type="text" class="text tooltip" name="logging_time" tooltip="Zeit in Tagen, wie lange Log-Eintr&auml;ge gespeichert bleiben sollen" /> <span class="small hint">('.htmlspecialchars($bconfig['logging_time'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<th colspan="2">Grunddaten aus anderer Instanz &uuml;bernehmen</th>
	</tr>
	<tr>
		<td>Grunddaten &uuml;bernehmen von</td>
		<td><select name="data_copy" size="1" class="tooltip" tooltip="Es werden die Galaxien und die verdeckten Systeme &uuml;bertragen. Die Option ist nur aktiv, wenn keine vorhandene Installation benutzt wird">
		<option value="0">keine Daten übernehmen</option>';
	if(count($dbs) == 1) {
		$tmpl->content .= '
		<option value="1">bestehender Instanz</option>';
	}
	else {
		foreach($dbs as $inst=>$instname) {
			$tmpl->content .= '
			<option value="'.$inst.'">'.$inst.' - '.htmlspecialchars($instname, ENT_COMPAT, 'UTF-8').'</option>';
		}
	}
	$tmpl->content .= '
		</select>
	<tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<th colspan="2">Administrator</th>
	</tr>
	<tr>
		<td>User-ID</td>
		<td><input type="text" class="text tooltip" name="admin" tooltip="Die OD-User-ID des Spielers, der die Instanz administrieren soll" /></td>
	</tr>
	</table>
	
	
	<br /><br />
	<div class="center">
		<input type="button" class="button dbaddbutton" style="width:150px" value="Instanz erstellen" onclick="form_send(this.parentNode.parentNode, \'index.php?p=dbs&amp;sp=add_send&amp;time='.$time.'&amp;ajax\', $(this.parentNode.parentNode).siblings(\'.ajax\'))" />
	</div>
	</form>
	<br />
	<div class="ajax center">&nbsp;</div>';

	$tmpl->content .= '</div>';
	
	// ausgeben
	$tmpl->output();
}

// Instanz bearbeiten
else if($_GET['sp'] == 'edit') {
	$tmpl->name = 'Datenbank bearbeiten';
	
	// Vollständigkeit
	if(!isset($_GET['id']) OR !(int)$_GET['id']) {
		$tmpl->error = 'Keine oder ung&uuml;ltige ID &uuml;bergeben!';
	}
	// Vorhandensein
	if(!isset($dbs[$_GET['id']])) {
		$tmpl->error = 'Die Datenbank existiert nicht mehr!';
	}
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		$tmpl->name = 'Datenbank '.$_GET['id'].' bearbeiten';
		
		$tmpl->content = '<div class="icontent dbedit'.$_GET['id'].'">';
		
		
		// Konfiguration einbinden
		$config = array();
		
		if(!(@include('../config/config'.$_GET['id'].'.php'))) {
			$tmpl->content .= '<b>Konnte die Konfigurationsdatei nicht lesen! Beim Speichern wird eine neue Datei erstellt.</b><br /><br />';
		}
		
		$newconfig = $config;
		
		$config = array_merge($bconfig, $newconfig);
		
		// nicht besetze Werte mit leeren Strings füllen
		$config2 = $bconfig;
		foreach($config2 as $key=>$val) {
			$config2[$key] = '';
		}
		$config2 = array_merge($config2, $newconfig);
		
		
		$tmpl->content .= 'Die eingeklammerten Werte hinter den Eingabefeldern sind die Standardwerte.
		<br />
		L&auml;sst du diese Felder leer, nimmt die Konfiguration die eingeklammerten Werte an (k&ouml;nnen in der globalconfig.php ge&auml;ndert werden).
		<br /><br />
		<form onsubmit="return false">
		<table class="leftright" style="width:100%">
		<tr>
			<th colspan="2">Grundeinstellungen</th>
		</tr>
		<tr>
			<td>Name</td>
			<td>';
		if(count($dbs) == 1) {
			$tmpl->content .= '<i>einzige Instanz - kann keinen Namen haben!</i>';
		}
		else {
			$tmpl->content .= '<input type="text" class="text" name="name" value="'.htmlspecialchars($dbs[$_GET['id']], ENT_COMPAT, 'UTF-8').'" />';
		}
		$tmpl->content .= '</td>
		</tr>
		<tr>
			<td>aktiv</td>
			<td><input type="checkbox" name="active"'.($config['active'] ? ' checked="checked"' : '').' /></td>
		</tr>
		<tr>
			<td>inaktiv-Nachricht</td>
			<td><input type="text" class="text tooltip" style="width:300px" name="offlinemsg" tooltip="wird angezeigt, wenn die Datenbank auf inaktiv gesetzt ist" value="'.htmlspecialchars($config2['offlinemsg'], ENT_COMPAT, 'UTF-8').'" />
			'.($bconfig['offlinemsg'] ? '<br /><span class="small hint">('.htmlspecialchars($bconfig['offlinemsg'], ENT_COMPAT, 'UTF-8').')</span>' : '').'</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<th colspan="2">MySQL</th>
		</tr>
		<tr>
			<td>Tabellen-Pr&auml;fix</td>
			<td><input type="text" class="text" name="mysql_prefix" value="'.htmlspecialchars($config2['mysql_prefix'], ENT_COMPAT, 'UTF-8').'" /></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align:center;font-style:italic">Achtung: Die Tabellen werden nicht automatisch umbenannt!</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<th colspan="2">Sicherheit</th>
		</tr>
		<tr>
			<td>Spieler automatisch freischalten</td>
			<td><select name="disable_freischaltung" size="1">
			<option value=""></option>
			<option value="0"'.($config2['disable_freischaltung'] === false ? ' selected="selected"' : '').'>nein</option>
			<option value="1"'.($config2['disable_freischaltung'] ? ' selected="selected"' : '').'>ja</option>
			</select> <span class="small hint">('.($bconfig['disable_freischaltung'] ? 'ja' : 'nein').')</span></td>
		</tr>
		<tr>
			<td>Autofreischaltung Rechtelevel</td>
			<td><select name="disable_freischaltung_level" size="1">
			<option value=""></option>';
		foreach($rechte as $key=>$data) {
			$tmpl->content .= '
			<option value="'.$key.'"'.(($config2['disable_freischaltung_level'] !== '' AND $config2['disable_freischaltung_level'] == $key) ? ' selected="selected"' : '').'>'.htmlspecialchars($data['name'], ENT_COMPAT, 'UTF-8').'</option>';
		}
		$tmpl->content .= '
			</select> <span class="small hint">('.htmlspecialchars($rechte[$bconfig['disable_freischaltung_level']]['name'], ENT_COMPAT, 'UTF-8').')</span></td>
		</tr>
		<tr>
			<td>Logging-Stufe</td>
			<td><select name="logging" size="1" class="tooltip" tooltip="[vorsichtig] loggt alle Aktionen eines Spielers, [paranoid] loggt zus&auml;tzlich alle Seiten, die ein Spieler ansieht (z.B. Invasionen oder Spielerlisten)">
			<option value=""></option>
			<option value="0"'.($config2['logging'] === 0 ? ' selected="selected"' : '').'>deaktiviert</option>
			<option value="1"'.($config2['logging'] == 1 ? ' selected="selected"' : '').'>nur Verwaltung</option>
			<option value="2"'.($config2['logging'] == 2 ? ' selected="selected"' : '').'>vorsichtig</option>
			<option value="3"'.($config2['logging'] == 3 ? ' selected="selected"' : '').'>paranoid</option>
			</select> <span class="small hint">(';
		$data = array(
			0=>'deaktiviert',
			1=>'nur Verwaltung',
			2=>'vorsichtig',
			3=>'paranoid'
		);
		$tmpl->content .= $data[$bconfig['logging']].')</span></td>
		</tr>
		<tr>
			<td>Speicherdauer des Logs (Tage)</td>
			<td><input type="text" class="text tooltip" name="logging_time" value="'.htmlspecialchars($config2['logging_time'], ENT_COMPAT, 'UTF-8').'" tooltip="Zeit in Tagen, wie lange Log-Eintr&auml;ge gespeichert bleiben sollen" /> <span class="small hint">('.htmlspecialchars($bconfig['logging_time'], ENT_COMPAT, 'UTF-8').')</span></td>
		</tr>
		</table>
		
		
		<br />
		<div class="center">
			<input type="button" class="button" style="width:150px" value="Konfiguration speichern" onclick="form_send(this.parentNode.parentNode, \'index.php?p=dbs&amp;sp=edit_send&amp;id='.$_GET['id'].'&amp;ajax\', $(this.parentNode.parentNode).siblings(\'.ajax\'))" />
		</div>
		</form>
		<br />
		<div class="ajax center"></div>';
	
		$tmpl->content .= '</div>';
	}
	
	// ausgeben
	$tmpl->output();
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
			$config = $bconfig;
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
			<td>'.($c ? htmlspecialchars($c['mysql_user'].'@'.$c['mysql_host'].' - [DB] '.$c['mysql_db'].' [Prefix] '.$c['mysql_prefix'], ENT_COMPAT, 'UTF-8') : '<i>unbekannt</i>').'</td>
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
	
	if(!isset($_GET['list'])) {
		$tmpl->content .= '</div>';
	}
	
	// Ausgabe
	$tmpl->output();
}

?>