<?php
/**
 * pages/show_ally.php
 * Allianz anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;


// keine Berechtigung, Allianzen anzuzeigen
if(!$user->rechte['show_ally']) {
	$tmpl->error = 'Du hast keine Berechtigung, Allianzen anzuzeigen!';
	$tmpl->output();
	die();
}

// keine ID übergeben
if(!isset($_GET['id'])) {
	$tmpl->error = 'Keine ID übergeben!';
	$tmpl->output();
	die();
}

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
}


/**
 * Seiten
 */

// Allianz anzeigen
else if($_GET['sp'] == '') {
	// Daten sichern
	$_GET['id'] = escape($_GET['id']);
	
	// Anzahl der gefundenen Allianzen ermitteln
	$count = '';
	if(strpos($_GET['id'], '*') !== false OR (!is_numeric(trim($_GET['id'])) AND $user->settings['szgrwildcard'])) {
		//$count = 'COUNT(allianzenID) AS allianzenAnzahl,';
		
		// * als Wildcard benutzen
		$_GET['id'] = str_replace('*', '%', $_GET['id']);
		
		// AutoWildcard-Einstellung
		if($user->settings['szgrwildcard']) {
			$_GET['id'] = '%'.$_GET['id'].'%';
		}
		
		$count = "(
			SELECT
				COUNT(*)
			FROM
				".GLOBPREFIX."allianzen
			WHERE
				allianzenTag LIKE '".escape($_GET['id'])."'
				OR allianzenName LIKE '".escape($_GET['id'])."'
			) AS allianzenAnzahl,";
		
	}
	
	// Query erzeugen
	$sql = "
		SELECT
			".$count."
			
			allianzenID,
			allianzenTag,
			allianzenName,
			allianzenMember,
			allianzenUpdate,
			
			register_allianzenID,
			registerProtectedAllies,
			registerProtectedGalas,
			registerAllyRechte,
			
			statusStatus
		FROM
			".GLOBPREFIX."allianzen
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = allianzenID";
	
	$data = false;
	
	// Allianz-ID
	if(is_numeric(trim($_GET['id']))) {
		// Daten abfragen
		$query = query("
			".$sql."
			WHERE
				allianzenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Allianz mit dieser ID existiert
		if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
	}
	
	// Name eingegeben oder ID nicht gefunden
	if(!$data) {
		$input = str_replace('%', '', $_GET['id']);
		
		// Daten abfragen (doppelt escapen wegen LIKE-Bug)
		$query = query("
			".$sql."
			WHERE
				allianzenTag LIKE '".escape($_GET['id'])."'
				OR allianzenName LIKE '".escape($_GET['id'])."'
			ORDER BY
				(allianzenTag = '".$input."' OR allianzenName = '".$input."') DESC,
				allianzenID DESC
			LIMIT 1
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Allianz mit diesem Namen
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
			
			// mehr als eine Allianz gefunden
			if(isset($data['allianzenAnzahl']) AND $data['allianzenAnzahl'] > 1) {
				// Bedingungen aufstellen
				$conds = array(
					"(allianzenTag LIKE '".escape($_GET['id'])."'
				OR allianzenName LIKE '".escape($_GET['id'])."')",
					"allianzenID != ".$data['allianzenID']
				);
				
				if($user->protectedAllies) {
					$conds[] = "allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
				}
				
				$conds = implode(" AND ", $conds);
				
				$query = query("
					SELECT
						allianzenID,
						allianzenTag,
						allianzenName,
						allianzenUpdate
					FROM
						".PREFIX."allianzen
					WHERE
						".$conds."
					ORDER BY
						allianzenID ASC
					LIMIT 20
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				if(mysql_num_rows($query)) {
					$tmpl->content .= '
					<div class="fcbox small2" style="line-height:24px">
						<b>meintest du:</b>
						<br />';
					while($row = mysql_fetch_assoc($query)) {
						// hat sich die Allianz aufgelöst?
						$old = ($row['allianzenUpdate'] < time()-$gconfig['odrequest']*3600-3600) ? 'style="opacity:0.4"' : '';
						
						$tmpl->content .= '
						<span style="white-space:nowrap">
						<a class="link contextmenu" '.$old.' data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'&amp;ajax">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').' 
						/ 
						'.htmlspecialchars($row['allianzenName'], ENT_COMPAT, 'UTF-8').' 
						('.$row['allianzenID'].')</a>
						</span> &nbsp; &nbsp; &nbsp; ';
					}
					$tmpl->content .= '
					</div>
					<br />';
				}
			}
			// keine Allianz gefunden
			else if(isset($data['allianzenAnzahl']) AND $data['allianzenAnzahl'] == 0) {
				$data = false;
			}
		}
	}
	
	// Allianz nicht gefunden
	if(!$data) {
		$tmpl->error = 'Die Allianz wurde nicht gefunden!';
		$tmpl->output();
		die();
	}
	
	// Allianz gesperrt
	if($user->protectedAllies AND in_array($data['allianzenID'], $user->protectedAllies)) {
		$tmpl->content .= '<div class="icontent" style="text-align:center;margin:20px;font-size:16px;font-weight:bold"><img src="img/layout/error.png" width="150" height="137" alt="Fehler" /><br /><br />Du hast keine Berechtigung, diese Allianz anzuzeigen!</div>';
		$tmpl->name = 'Fehler!';
		$tmpl->output();
		die();
	}
	
	// Planetendaten ermitteln
	$query = query("
		SELECT
		COUNT(*) AS planetenTotal,
		AVG(planetenGroesse) AS planetenAvgGroesse,
		(
		SELECT SUM(playerPlaneten)
		FROM
			".GLOBPREFIX."player
		WHERE
			player_allianzenID = ".$data['allianzenID']."
		) AS playerPlaneten,
		(
		SELECT COUNT(*)
		FROM
			".PREFIX."planeten
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
		WHERE
			player_allianzenID = ".$data['allianzenID']."
			AND planetenUpdateOverview > 0
		) AS planetenScanned,
		(
		SELECT COUNT(*)
		FROM
			".PREFIX."planeten
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
		WHERE
			player_allianzenID = ".$data['allianzenID']."
			AND planetenUpdateOverview > ".(time()-86400*$config['scan_veraltet'])."
		) AS planetenAktuell
	FROM
		".PREFIX."planeten
		LEFT JOIN ".GLOBPREFIX."player
			ON playerID = planeten_playerID
	WHERE
		player_allianzenID = ".$data['allianzenID']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$pldata = mysql_fetch_assoc($query);
	
	// Heimgalaxien ermitteln
	$gdata = array();
	
	$query = query("
		SELECT
			systeme_galaxienID,
			COUNT(*) AS planetenCount
		FROM
			".PREFIX."planeten
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
		WHERE
			player_allianzenID = ".$data['allianzenID']."
		GROUP BY
			systeme_galaxienID
		ORDER BY
			planetenCount DESC
		LIMIT 3
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$gdata[] = ($user->rechte['karte'] ? '<a class="link winlink contextmenu" data-link="index.php?p=karte&amp;gala='.$row['systeme_galaxienID'].'&amp;filter=ally&amp;data='.$data['allianzenID'].'&amp;title=Systeme%20der%20Allianz%20'.urlencode($data['allianzenTag']).'">' : '').$row['systeme_galaxienID'].' ('.$row['planetenCount'].' Planet'.($row['planetenCount'] > 1 ? 'en' : '').')'.($user->rechte['karte'] ? '</a>' : '');
	}
	
	// Ausgabe
	$tmpl->name = 'Allianz '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' ('.$data['allianzenID'].')';
	$tmpl->icon = 'ally';
	
	
	$tmpl->content .= '
		<table class="tsmall tdata" style="width:100%;min-width:600px;margin-top:5px">
		<tr>
			<td style="width:40%">
				<b>Tag</b>: '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'
				<br />
				<b>Name</b>: '.htmlspecialchars($data['allianzenName'], ENT_COMPAT, 'UTF-8').'
				<br />';
	// Status, wenn nicht eigene Allianz
	if($data['allianzenID'] != $user->allianz) {
		if($data['statusStatus'] == NULL) $data['statusStatus'] = 0;
		$tmpl->content .= '
				<b>Status</b>: <span '.$status_color[$data['statusStatus']].'>'.$status[$data['statusStatus']].'</span>
				<br />';
	}
	
	// Mitglieder
	$tmpl->content .= '
				<b>Mitglieder</b>: '.$data['allianzenMember'];
	
	
	// Planetendaten
	if(!$pldata['playerPlaneten']) {
		$pl1 = 0;
		$pl2 = 0;
		$pl3 = 0;
	}
	else {
		$pl1 = round(100*$pldata['planetenTotal']/$pldata['playerPlaneten']);
		$pl2 = round(100*$pldata['planetenScanned']/$pldata['playerPlaneten']);
		$pl3 = round(100*$pldata['planetenAktuell']/$pldata['playerPlaneten']);
		if($pl1 > 100) $pl1 = 100;
		if($pl2 > 100) $pl2 = 100;
		if($pl3 > 100) $pl3 = 100;
	}
	
	$tmpl->content .= '
			</td>
			<td>
				<div class="balken" style="width:100px;height:10px;float:right;margin-top:18px;margin-right:20px">
					<div class="balkenfillhalf" style="width:'.$pl1.'px"></div>
					<div class="balkenfillhalf" style="width:'.$pl2.'px"></div>
					<div class="balkenfill" style="width:'.$pl3.'px"></div>
				</div>
				'.$pldata['planetenTotal'].' von ~'.$pldata['playerPlaneten'].' Planet'.($pldata['playerPlaneten'] != 1 ? 'en' : '').' erfasst &nbsp;';
	// alle Planeten anzeigen
	if($user->rechte['search']) {
		$tmpl->content .= '
				<a class="link winlink contextmenu hint" data-link="index.php?p=search&amp;s=1&amp;aid='.$data['allianzenID'].'&amp;hide&amp;title='.urlencode('Planeten der Allianz '.$data['allianzenTag']).'&amp;ajax">[anzeigen]</a>';
	}
	$tmpl->content .= '
				<br />
				davon '.$pldata['planetenScanned'].' gescannt ('.$pldata['planetenAktuell'].' aktuell)
				<br />';
	// durchschnittliche Planetengröße
	if($pldata['planetenTotal']) {
		$tmpl->content .= '
				<b>&#0216; Planetengröße</b>: '.round($pldata['planetenAvgGroesse'], 1).'
				<br />';
	}
	// Heimatgalaxien
	if(count($gdata)) {
		$tmpl->content .= '
				<b>Heimatgalaxien</b>: '.implode(', ', $gdata);
	}
	$tmpl->content .= '
			</td>
		</tr>
		</table>';
	
	// erfasste Mitglieder auflisten
	$tmpl->content .= '
		<br />
		<div class="icontent small2" style="width:600px;line-height:15px">
			<b>erfasste Mitglieder</b>:
			<br />';
	
	$query = query("
		SELECT
			playerID,
			playerName
		FROM
			".GLOBPREFIX."player
		WHERE
			player_allianzenID = ".$data['allianzenID']."
		ORDER BY
			playerName ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['playerID'].'&amp;ajax">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a> &nbsp; ';
	}
	
	$tmpl->content .= '
		</div>';
	
	// wahrscheinlich aufgelöst
	if($data['allianzenUpdate'] < time()-$gconfig['odrequest']*3600-3600) {
		$tmpl->content .= '
		<br />
		<div class="center" style="font-weight:bold">Die Allianz hat sich wahrscheinlich aufgel&ouml;st!</div>
		<br />';
	}
	
	// Planeten zu einer Route/Liste hinzufügen
	if($user->rechte['routen'] AND $pldata['planetenTotal']) {
		$tmpl->content .= '
		<br /><div class="fcbox small2">
			<div class="center">
				<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=add2route&amp;typ=ally&amp;id='.$data['allianzenID'].'&amp;ajax\', this.parentNode, false, true)">Planeten der Allianz zu einer Route / Liste hinzuf&uuml;gen</a>
			</div>
		</div>';
	}
	
	
	// Allianz darf die DB benutzen
	if($data['register_allianzenID'] !== NULL) {
		$tmpl->content .= '
		<br />
		<div class="fcbox small2" style="padding:8px">
			<div class="center" style="padding:3px;font-weight:bold">
				Diese Allianz darf die Datenbank benutzen
			</div>';
		
		// gesperrte Funktionen
		if($data['registerProtectedAllies'] != '' OR $data['registerProtectedGalas'] != '' OR $data['registerAllyRechte'] != '') {
			$tmpl->content .= '
			<br />
			<b>gesperrte Funktionen f&uuml;r Mitglieder dieser Allianz</b>:
			<div class="red" style="padding-left:12px;padding-top:3px">';
			
			// gesperrte Berechtigungen
			if($data['registerAllyRechte'] != '') {
				$ar = explode('+', $data['registerAllyRechte']);
				foreach($ar as $key=>$val) {
					$ar[$key] = $rechtenamen[$val];
				}
			}
			else {
				$ar = array();
			}
			// gesperrte Allianzen
			if($data['registerProtectedAllies'] != '') {
				$data['registerProtectedAllies'] = explode('+', $data['registerProtectedAllies']);
				
				$query = query("
					SELECT
						allianzenID,
						allianzenTag
					FROM
						".GLOBPREFIX."allianzen
					WHERE
						allianzenID IN (".implode(', ', $data['registerProtectedAllies']).")
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				if(mysql_num_rows($query)) {
					$pa = array();
					while($row = mysql_fetch_assoc($query)) {
						$pa[] = '<a class="link red" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
					}
					$ar[] = 'Zugriff auf Allianz'.(count($data['registerProtectedAllies']) != 1 ? 'en' : '').' '.implode(', ', $pa).' gesperrt';
				}
			}
			
			// gesperrte Galaxien
			if($data['registerProtectedGalas'] != '') {
				$data['registerProtectedGalas'] = explode('+', $data['registerProtectedGalas']);
				
				$ar[] = 'Zugriff auf Galaxie'.(count($data['registerProtectedGalas']) != 1 ? 'n' : '').' '.implode(', ', $data['registerProtectedGalas']).' gesperrt';
			}
			
			$tmpl->content .= implode(',<br />', $ar).'</span>
			</div>';
		}		
		
		$tmpl->content .= '
		</div>';
	}
	
	// Log-Eintrag
	if($config['logging'] >= 3) {
		insertlog(5, 'lässt die Allianz '.$data['allianzenTag'].' ('.$data['allianzenID'].') anzeigen');
	}
}

// Ausgabe
$tmpl->output();

?>