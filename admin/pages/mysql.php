<?php
/**
 * admin/pages/mysql.php
 * MySQL-Befehle
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template_admin;
$tmpl->name = 'MySQL-Befehle ausführen';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'send'=>true
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX
 */

else if($_GET['sp'] == 'send') {
	// Daten unvollständig
	if(!isset($_POST['query'], $_POST['mode'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// globalen Präfix ersetzen
		$_POST['query'] = str_replace('[globprefix]', GLOBPREFIX, $_POST['query']);
		
		
		// normale MySQL-Klasse umgehen
		$mysql_conn = new mysql;
		$mysql_conn->connected = true;
		
		$mysqlconns = array();
		
		// Instanzen durchgehen
		foreach($dbs as $instance=>$instance_name) {
			
			// globales Query
			if($_POST['mode']) {
				$tmpl->content .= '<div class="hl2">globale Abfrage - alle Datenbanken</div>
			<div class="icontent">';
			}
			// Instanz-Query
			else {
				$tmpl->content .= '<div class="hl2">Datenbank '.$instance.' '.htmlspecialchars($instance_name, ENT_COMPAT, 'UTF-8').'</div>
			<div class="icontent">';
			}
			
			// Konfigurationsdatei einbinden
			$config = $bconfig;
			if(!(@include('../config/config'.$instance.'.php'))) {
				$tmpl->content .= '<b>Konnte Konfigurationsdatei nicht einbinden!</b>
				</div>';
				continue;
			}
			
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
					$tmpl->content .= '<b>MySQL-Verbindung fehlgeschlagen: '.$mysqlhash.'</b>
					</div>';
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
				$tmpl->content .= '<b>Datenbank konnte nicht ausgewählt werden: '.$mysqlhash.'-'.$config['mysql_db'].'</b>
					</div>';
				continue;
			}
			
			$prefix = $config['mysql_prefix'];
			
			
			// Präfix-Platzhalter ersetzen
			$q = trim(str_replace('[prefix]', $prefix, $_POST['query']));
			
			// Query absetzen
			$query = mysql_query($q, $conn);
			
			// Query fehlgeschlagen
			if(!$query) {
				$tmpl->content .= '<b>Query fehlgeschlagen: '.mysql_error().'</b>
				</div>';
				continue;
			}
			
			// Query-Ausgabe
			// Update
			if(strtoupper(substr($q, 0, 6)) == 'UPDATE') {
				$tmpl->content .= 'Zeilen aktualisiert: '.mysql_affected_rows($conn);
			}
			// Delete
			else if(strtoupper(substr($q, 0, 6)) == 'DELETE') {
				$tmpl->content .= 'Zeilen gel&ouml;scht: '.mysql_affected_rows($conn);
			}
			// Insert
			else if(strtoupper(substr($q, 0, 6)) == 'INSERT') {
				$tmpl->content .= 'Zeilen eingef&uuml;gt: '.mysql_affected_rows($conn);
			}
			// Select
			else if(strtoupper(substr($q, 0, 6)) == 'SELECT' OR strtoupper(substr($q, 0, 7)) == 'EXPLAIN') {
				$count = mysql_num_rows($query);
				$tmpl->content .= $count.' Ergebnis'.($count != 1 ? 'se' : '');
				if($count > 100) {
					$tmpl->content .= ' <span class="small2">(limitiert auf die ersten 100)</span>';
				}
				// Ergebnisse anzeigen
				if($count) {
					$tmpl->content .= '<br /><br />
					<table class="data searchtbl" style="background-image:url(../img/layout/contentbg.gif)">';
					
					$i = 1;
					
					while($row = mysql_fetch_assoc($query)) {
						// Tabellen-Headline
						if($i == 1) {
							$tmpl->content .= '<tr>';
							foreach($row as $key=>$val) {
								$tmpl->content .= '<th>'.htmlspecialchars($key, ENT_COMPAT, 'UTF-8').'</th>';
							}
							$tmpl->content .= '</tr>';
						}
						
						// Zeile
						$tmpl->content .= '<tr>';
						foreach($row as $val) {
							if($val === NULL) $tmpl->content .= '<td style="font-style:italic">NULL</td>';
							else $tmpl->content .= '<td>'.htmlspecialchars($val, ENT_COMPAT, 'UTF-8').'</td>';
						}
						$tmpl->content .= '</tr>';
						
						// Zähler erhöhen
						$i++;
						
						// bei 100 Ergebnissen abbrechen
						if($i > 100) {
							break;
						}
					}
					
					$tmpl->content .= '</table>';
				}
			}
			// unbekanntes Query
			else {
				$tmpl->content .= 'Query erfolgreich';
			}
			
			$tmpl->content .= '</div>';
			
			
			// bei globalem Query abbrechen
			if($_POST['mode']) {
				break;
			}
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

/**
 * Seite
 */

else {
	$tmpl->content = '
		<div class="icontent">
			Mit dieser Funktion kannst du MySQL-Befehle in allen DB-Instanzen ausf&uuml;hren.
			<br />
			Du kannst immer nur einen Befehl auf einmal abschicken.
			<br />
			Als Platzhalter f&uuml;r den Instanz-Pr&auml;fix musst du <b>[prefix]</b> benutzen, f&uuml;r den globalen Pr&auml;fix <b>[globprefix]</b>.
			<br /><br />
			<b>Benutze diese Funktion nur, wenn du dir absolut sicher bist, was du tust!</b>
			<br /><br />
			<form action="#" onsubmit="return form_send(this, \'index.php?p=mysql&amp;sp=send&amp;ajax\', $(this).siblings(\'.ajax\'))">
				&nbsp; Modus: <select name="mode" size="1">
				<option value="0">f&uuml;r jede Instanz einzeln ausf&uuml;hren</option>
				<option value="1">global - nur einmal ausf&uuml;hren</option>
				</select>
				<br /><br />
				<div class="center">
					<textarea name="query" style="width:98%;height:300px"></textarea>
					<br /><br />
					<input type="submit" class="button" value="MySQL-Befehle abschicken" />
				</div>
			</form>
			<br />
			<div class="ajax"></div>
		</div>
	';
	
	// Ausgabe
	$tmpl->output();
}

?>