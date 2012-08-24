<?php

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

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
		$_POST['instancekey'] = generate_key();
		
		// ID ermitteln
		$dbkeys = array_keys($dbs);
		sort($dbkeys);
		$instance = array_pop($dbkeys)+1;
		
		$dbs[$instance] = $name;
		
		// fertige Konfiguration erzeugen
		$config = $gconfig;
		
		foreach($_POST as $key=>$val) {
			$config[$key] = $val;
		}
		
		$prefix = mysql::getPrefix($instance);
		
		// MySQL-Verbindung
		$mysql_conn = new mysql;
		
		// Tabellen schon vorhanden?
		$query = query("
			SHOW TABLES LIKE '".$prefix."planeten'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// vorhanden
		if(mysql_num_rows($query)) {
			// nicht ignoriert
			if(!$mysql_use) {
				$tmpl->error = 'Es sind bereits MySQL-Tabellen mit diesem Pr&auml;fix vorhanden!<br />Bitte f&uuml;hre die Funktion zum Erkennen neuer Instanzen aus!';
			}
		}
		else {
			$mysql_use = false;
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
				
				
				$dprefix = mysql::getPrefix($data_copy);
				
				
				// Galaxien übertragen
				$query = query("
					SELECT
						galaxienID,
						galaxienSysteme
					FROM
						".$dprefix."galaxien
				");
				
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
						
						if(!query($copyarray)) {
							$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Galaxien2]! (Fehler: '.mysql_error().')';
						}
					}
					
					mysql_free_result($query);
				}
				else {
					$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Galaxien]! (Fehler: '.mysql_error().')';
				}
				
				// Systeme übertragen
				$query = query("
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
				");
				
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
						
							if(!query($copyarray)) {
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
						
						if(!query($copyarray)) {
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
				$query = query("
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
				");
				
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
						
								if(!query($copyarray)) {
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
						
						if(!query($copyarray)) {
							$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Planeten2]! (Fehler: '.mysql_error().')';
						}
					}
					
					mysql_free_result($query);
				}
				else {
					$copyerror = 'Kopieren der Grunddaten fehlgeschlagen [Planeten]! (Fehler: '.mysql_error().')';
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
						userPassword = '".General::encryptPassword($pw, $config['key'])."',
						userSettings = '".escape($settings)."',
						userApiKey = '".General::generateApiKey()."'
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
			
			General::loadClass('config');
			
			// Instanz-Konfiguration speichern
			if(!config::save($instance, $_POST, false)) {
				$tmpl->error .= 'Konnte Konfiguration nicht speichern!';
			}
			// DB-Array speichern
			else if(!config::saveGlobal('dbs', 'dbs', $dbs)) {
				$tmpl->error .= 'Konnte Liste der Instanzen nicht speichern!';
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
	L&auml;sst du diese Felder leer, nimmt die Konfiguration die eingeklammerten Werte an.
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
		'.($gconfig['offlinemsg'] ? '<br /><span class="small hint">('.htmlspecialchars($gconfig['offlinemsg'], ENT_COMPAT, 'UTF-8').')</span>' : '').'</td>
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
		</select> <span class="small hint">('.($gconfig['disable_freischaltung'] ? 'ja' : 'nein').')</span></td>
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
		</select> <span class="small hint">('.htmlspecialchars($rechte[$gconfig['disable_freischaltung_level']]['name'], ENT_COMPAT, 'UTF-8').')</span></td>
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
	$tmpl->content .= $data[$gconfig['logging']].')</span></td>
	</tr>
	<tr>
		<td>Speicherdauer des Logs (Tage)</td>
		<td><input type="text" class="text tooltip" name="logging_time" tooltip="Zeit in Tagen, wie lange Log-Eintr&auml;ge gespeichert bleiben sollen" /> <span class="small hint">('.htmlspecialchars($gconfig['logging_time'], ENT_COMPAT, 'UTF-8').')</span></td>
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



?>