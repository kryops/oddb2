<?php
/**
 * pages/gates.php
 * Gateliste
 * Myrigateliste
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	if($user->rechte['gates']) $_GET['sp'] = 'gates';
	else $_GET['sp'] = 'mgates';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Gates & Myrigates';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'gates'=>true,
	'mgates'=>true,
	'sprung_change'=>true
);


/**
 * Funktionen
 */

/**
 * Bedingungen für Myrigateansicht-Berechtigungen erzeugen
 * return array MySQL-Bedingungen
 */
function mgate_getconds() {
	global $user, $status_meta;
	
	// Bedingungen aufstellen
	$conds = array();
	
	// Berechtigungen
	if(!$user->rechte['show_myrigates_ally'] AND $user->allianz) {
		$conds[] = "player_allianzenID != ".$user->allianz;
	}
	if(!$user->rechte['show_myrigates_meta']) {
		$conds[] = "(statusStatus IS NULL OR statusStatus != ".$status_meta." OR player_allianzenID = ".$user->allianz.")";
	}
	if(!$user->rechte['show_myrigates_register']) {
		$conds[] = "(statusStatus = ".$status_meta." OR register_allianzenID IS NULL)";
	}
	
	// gesperrte Allianzen und Galaxien
	if($user->protectedAllies) {
		$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(", ", $user->protectedAllies)."))";
	}
	
	if($user->protectedGalas) {
		$conds[] = "myrigates_galaxienID NOT IN(".implode(", ", $user->protectedGalas).")";
	}
	
	// zurückgeben
	return $conds;
}


// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

// Sprunggenerator als (un)benutzbar markieren
else if($_GET['sp'] == 'sprung_change') {
	
	// Daten vorhanden
	if(!isset($_GET['id'], $_GET['set'])) {
		$tmpl->error = 'Daten unvollständig!';
		$tmpl->output();
	}
	
	// Berechtigungen
	if(!$user->rechte['show_myrigates']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
		$tmpl->output();
	}
	
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	$_GET['set'] = (int)$_GET['set'];
	
	// speichern
	query("
		UPDATE
			".PREFIX."myrigates
		SET
			myrigatesSprungFeind = ".$_GET['set']."
		WHERE
			myrigates_planetenID = ".$_GET['id']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	// Anzeige ändern
	$tmpl->content = '<a class="italic" onclick="ajaxcall(\'index.php?p=gates&amp;sp=sprung_change&amp;id='.$_GET['id'].'&amp;set='.($_GET['set'] ? 0 : 1).'\', this.parentNode, false, false)">';
	
	if($_GET['set']) {
		$tmpl->content .= '<span class="bold red">unbenutzbar</span>';
	}
	else {
		$tmpl->content .= '<span class="green">benutzbar</span>';
	}
	$tmpl->content .= '</a>';
	
	$tmpl->output();
}


/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Gateliste
	if($user->rechte['gates']) {
		$csw->data['gates'] = array(
			'link'=>'index.php?p=gates&sp=gates',
			'bg'=>'background-image:url(img/layout/csw_gates.png)',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
	}
	
	// Myrigateliste
	if($user->rechte['show_myrigates']) {
		$csw->data['mgates'] = array(
			'link'=>'index.php?p=gates&sp=mgates',
			'bg'=>'background-image:url(img/layout/csw_gates.png);background-position:-150px 0px',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
	}
	
	// Inhalt für die Gateliste
	if($_GET['sp'] == 'gates' AND $user->rechte['gates']) {
		$content =& $csw->data['gates']['content'];
		
		$content = '
			<div class="hl2">Gates</div>';
		
		$t = time();
		$sids = array();
		
		$query = query("
			SELECT
				galaxienID,
				galaxienGate,
				galaxienGateSys
			FROM
				".PREFIX."galaxien
			WHERE
				galaxienGate > 0
			ORDER BY
				galaxienID ASC
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// keine Gates eingetragen
		if(!mysql_num_rows($query)) {
			$content .= '
			<br /><br />
			<div class="center">Es wurden noch keine Gates eingetragen</div>
			<br /><br />';
		}
		// Gates vorhanden
		else {
			$content .= '
			<table class="data" style="margin:auto">
			<tr>
				<th>Gala</th>
				<th>System</th>
				<th>Planet</th>
				<th>&nbsp;</th>
			</tr>';
			while($row = mysql_fetch_assoc($query)) {
				$content .= '
			<tr>
				<td>'.$row['galaxienID'].'</td>
				<td><a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$row['galaxienGateSys'].'&amp;nav='.$t.'">'.$row['galaxienGateSys'].'</a></td>
				<td>'.$row['galaxienGate'].'</td>
				<td><a href="'.($user->odServer != '' ? $user->odServer : 'http://www.omega-day.com').'/game/index.php?op=orbit&amp;index='.$row['galaxienGate'].'" target="_blank">[in OD &ouml;ffnen]</a></td>
			</tr>';
				
				$sids[] = $row['galaxienGateSys'];
			}
			$content .= '
			</table>';
			
			// System-Navigation
			$content .= '<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
		}
		
		// Log-Eintrag
		if($config['logging'] == 3) {
			insertlog(5, 'lässt sich die Gateliste anzeigen');
		}
	}
	
	// Inhalt für die Myrigateliste
	if($_GET['sp'] == 'mgates' AND $user->rechte['show_myrigates']) {
		$content =& $csw->data['mgates']['content'];
		
		if(!class_exists('datatable')) {
			include './common/datatable.php';
		}
		
		$content = '
			<div class="hl2">Myrigates</div>';
		
		// Bedingungen aufstellen
		$conds = mgate_getconds();
		
		if(count($conds)) {
			$conds = "WHERE ".implode(" AND ", $conds);
		}
		else {
			$conds = "";
		}
		
		$t = time();
		$ids = array();
		$sids = array();
		
		// Daten abfragen
		$query = query("
			SELECT
				myrigates_galaxienID,
				myrigates_planetenID,
				myrigatesSprung,
				myrigatesSprungFeind,
				
				planetenMyrigate,
				planeten_systemeID,
				planeten_playerID,
				planetenKommentar,
				
				systemeX,
				systemeZ,
				systemeUpdate,
				
				playerName,
				player_allianzenID,
				
				allianzenTag,
				
				statusStatus
			FROM
				".PREFIX."myrigates
				LEFT JOIN ".PREFIX."planeten
					ON planetenID = myrigates_planetenID
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON playerID = planeten_playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			".$conds."
			ORDER BY
				myrigates_galaxienID ASC,
				myrigates_planetenID ASC
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// keine Gates eingetragen
		if(!mysql_num_rows($query)) {
			$content .= '
			<br /><br />
			<div class="center">Es wurden noch keine Myrigates und Sprunggeneratoren eingetragen</div>
			<br /><br />';
		}
		// Gates vorhanden
		else {
			$heute = strtotime('today');
			
			$content .= '
			<div class="icontent">
				Um die Myrigates anders zu sortieren oder weiter zu filtern, benutze bitte die Suchfunktion.
			</div>
			<br /><br />
			
			<div class="center">
			nur in Galaxie <input type="text" class="smalltext" onkeyup="mgate_filter(this.parentNode.parentNode, this.value)" /> anzeigen
			</div>
			<br /><br />
			<table class="data" style="margin:auto">
			<tr>
				<th>Gala</th>
				<th>System</th>
				<th>Planet</th>
				<th>Inhaber</th>
				<th>Allianz</th>
				<th>Status</th>
				<th>Sys-Scan</th>
				<th>Typ</th>
				<th>Ziel</th>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>';
			while($row = mysql_fetch_assoc($query)) {
				// Zeile ausgrauen, wenn kein Freund und kein Sprunggenerator
				$dunkel = false;
				if(!in_array($row['statusStatus'], $status_freund) AND !$row['myrigatesSprung']) {
					$dunkel = true;
				}
				
				$content .= '
			<tr class="filter" name="gala'.$row['myrigates_galaxienID'].'"'.($dunkel ? ' style="opacity:0.6"' : '').'>
				<td><span style="color:'.sektor_coord($row['systemeX'], $row['systemeZ']).'">'.$row['myrigates_galaxienID'].'</span></td>
				<td><a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$row['planeten_systemeID'].'&amp;nav='.$t.'&amp;ajax">'.$row['planeten_systemeID'].'</a></td>
				<td><a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$row['myrigates_planetenID'].'&amp;nav='.$t.'&amp;ajax">'.$row['myrigates_planetenID'].'</a></td>
				<td>';
				// Inhaber
				if($row['playerName'] != NULL) {
					$content .= '
					<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['planeten_playerID'].'&amp;ajax">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
				}
				else if($row['planeten_playerID'] == 0) {
					$content .= '<i>keiner</i>';
				}
				else if($row['planeten_playerID'] == -2) {
					$content .= '<i>Lux</i>';
				}
				else if($row['planeten_playerID'] == -3) {
					$content .= '<i>Altrasse</i>';
				}
				else {
					$content .= '<i>unbekannt</i>';
				}
				
				$content .= '
				</td>
				<td>';
				
				// Allianz
				if($row['allianzenTag'] != NULL) {
					$content .= '
					<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
				}
				else {
					$content .= '&nbsp;';
				}
				
				$content .= '
				</td>
				<td>';
				
				// Status
				if($row['allianzenTag'] != NULL AND $row['player_allianzenID'] != $user->allianz) {
					if($row['statusStatus'] == NULL) $row['statusStatus'] = 0;
					
					$content .= '
					<span '.$status_color[$row['statusStatus']].'>'.$status[$row['statusStatus']].'</span>';
				}
				else {
					$content .= '&nbsp;';
				}
				
				$content .= '
				</td>';
				
				// Scan
				$color = (time()-($config['scan_veraltet']*86400) > $row['systemeUpdate']) ? 'red' : 'green';
				if($row['systemeUpdate'] > $heute) $scan = 'heute';
				else if($row['systemeUpdate']) $scan = strftime('%d.%m.%y', $row['systemeUpdate']);
				else $scan = 'nie';
				
				
				$content .= '
				<td class="'.$color.'">'.$scan.'</td>
				<td>'.($row['myrigatesSprung'] ? 'Sprunggenerator' : 'Myrigate').'</td>
				<td class="mgateziel'.$row['myrigates_planetenID'].'">';
				// Sprunggenerator
				if($row['myrigatesSprung']) {
					$content .= '-';
				}
				// Myrigate-Ziel anzeigen
				else {
					$content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$row['planetenMyrigate'].'">'.$row['planetenMyrigate'].'</a>';
				}
				// Kommentar
				$content .= '
				<td>'.datatable::kommentar($row['planetenKommentar'], $row['myrigates_planetenID']).'</td>
				<td class="sprungfeind'.$row['myrigates_planetenID'].'">';
				
				// feindlicher Sprunggenerator
				if($row['myrigatesSprung']) {
					
					$content .= '<a class="italic" onclick="ajaxcall(\'index.php?p=gates&amp;sp=sprung_change&amp;id='.$row['myrigates_planetenID'].'&amp;set='.($row['myrigatesSprungFeind'] ? 0 : 1).'\', this.parentNode, false, false)">';
					
					if($row['myrigatesSprungFeind']) {
						$content .= '<span class="bold red">unbenutzbar</span>';
					}
					else {
						$content .= '<span class="green">benutzbar</span>';
					}
					$content .= '</a>';
				
				}
				else {
					$content .= '&nbsp;';
				}
				
				$content .= '</td>
			</tr>';
				
				$ids[] = $row['myrigates_planetenID'];
				
				if(!in_array($row['planeten_systemeID'], $sids)) {
					$sids[] = $row['planeten_systemeID'];
				}
			}
			$content .= '
			</table>';
			
			// System- und Planeten-Navigation
			$content .= '
				<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />
				<input type="hidden" id="sysnav'.$t.'" value="'.implode('-', $sids).'" />';
		}
		
		// Log-Eintrag
		if($config['logging'] == 3) {
			insertlog(5, 'lässt sich die Myrigateliste anzeigen');
		}
	}
	
	
	
	// nur Unterseite ausgeben
	if(isset($_GET['switch'])) {
		if(isset($csw->data[$_GET['sp']])) {
			$tmpl->content = $csw->data[$_GET['sp']]['content'];
		}
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
	}
	// keine Berechtigung
	else if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
	}
	$tmpl->output();
	
}
?>