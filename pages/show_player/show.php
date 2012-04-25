<?php
/**
 * pages/show_player/show.php
 * Spieler anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten sichern
$_GET['id'] = escape($_GET['id']);

// Anzahl der gefundenen Spieler ermitteln
$count = '';
if(strpos($_GET['id'], '*') !== false OR (!is_numeric(trim($_GET['id'])) AND $user->settings['szgrwildcard'])) {
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
			".GLOBPREFIX."player
		WHERE
			playerName LIKE '".escape($_GET['id'])."'
		) AS playerAnzahl,";
}

// Query erzeugen
$sql = "
	SELECT
		".$count."
		
		playerID,
		playerName,
		playerRasse,
		playerPlaneten,
		playerImppunkte,
		playerUmod,
		playerFA,
		playerDeleted,
		playerGesinnung,
		playerActivity,
		player_allianzenID,
		
		allianzenTag,
		
		register_allianzenID,
		
		statusStatus,
		
		user_playerID
	FROM
		".GLOBPREFIX."player
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = player_allianzenID
		LEFT JOIN ".PREFIX."register
			ON register_allianzenID = player_allianzenID
		LEFT JOIN ".PREFIX."user
			ON user_playerID = playerID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID";

$data = false;

// Spieler-ID
if(is_numeric(trim($_GET['id']))) {
	$_GET['id'] = (int)$_GET['id'];
	
	// OD-Request absetzen
	odrequest($_GET['id']);
	
	// Daten abfragen
	$query = query("
		".$sql."
		WHERE
			playerID = ".$_GET['id']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Spieler mit dieser ID existiert
	if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
}

// Name eingegeben oder ID nicht gefunden
if(!$data) {
	// Daten abfragen (doppelt escapen wegen LIKE-Bug)
	$query = query("
		".$sql."
		WHERE
			playerName LIKE '".escape($_GET['id'])."'
		ORDER BY
			(playerName = '".str_replace('%', '', $_GET['id'])."') DESC,
			playerDeleted ASC,
			playerID DESC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Spieler mit diesem Namen
	if(mysql_num_rows($query)) {
		$data = mysql_fetch_assoc($query);
		
		// mehr als einen Spieler gefunden
		if(isset($data['playerAnzahl']) AND $data['playerAnzahl'] > 1) {
			// Bedingungen aufstellen
			$conds = array(
				"playerName LIKE '".escape($_GET['id'])."'",
				"playerID != ".$data['playerID']
			);
			
			if($user->protectedAllies) {
				$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
			}
			
			$conds = implode(" AND ", $conds);
			
			$query = query("
				SELECT
					playerID,
					playerName,
					player_allianzenID,
					playerRasse,
					
					allianzenTag
				FROM
					".GLOBPREFIX."player
					LEFT JOIN ".GLOBPREFIX."allianzen
						ON allianzenID = player_allianzenID
				WHERE
					".$conds."
				ORDER BY
					playerDeleted ASC,
					playerID ASC
				LIMIT 20
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query)) {
				$tmpl->content .= '
				<div class="fcbox small2" style="line-height:24px">
					<b>meintest du:</b>
					<br />';
				while($row = mysql_fetch_assoc($query)) {
					$tmpl->content .= '
					<span style="white-space:nowrap">
					<a class="link contextmenu" data-link="index.php?p=show_player&amp;id='.$row['playerID'].'">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a> &nbsp;';
					
					// hat Allianz
					if($row['allianzenTag'] != NULL) {
						$tmpl->content .= '<a class="link winlink contextmenu small" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
					}
					// allianzlos
					else if(!$row['player_allianzenID']) {
						$tmpl->content .= '<span class="small"><i>allianzlos</i></span>';
					}
					// unbekannte Allianz
					else {
						$tmpl->content .= '<span class="small"><i>unbekannt</i></span>';
					}
					
					// Rasse
					if(isset($rassen2[$row['playerRasse']])) {
						$tmpl->content .= ' &nbsp; <img src="img/layout/leer.gif" class="rasse '.$rassen2[$row['playerRasse']].'" alt="" />';
					}
					
					$tmpl->content .= '</span> &nbsp; &nbsp; &nbsp; ';
				}
				$tmpl->content .= '
				</div>
				<br />';
			}
		}
		// kein Spieler gefunden
		else if(isset($data['playerAnzahl']) AND $data['playerAnzahl'] == 0) {
			$data = false;
		}
		
		
		// OD-Request absetzen, Daten evtl nochmal abfragen
		if($data AND odrequest($data['playerID'])) {
			// Daten abfragen
			$query = query("
				".$sql."
				WHERE
					playerID = ".$data['playerID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$data = mysql_fetch_assoc($query);
		}
	}
}

// Spieler nicht gefunden
if(!$data) {
	$tmpl->error = 'Der Spieler wurde nicht gefunden!';
	$tmpl->output();
	die();
}

// Allianz gesperrt
if($user->protectedAllies AND in_array($data['player_allianzenID'], $user->protectedAllies)) {
	/*$tmpl->error = 'Du hast keine Berechtigung, Spieler dieser Allianz anzuzeigen!';
	$tmpl->output();
	die();*/
	
	$tmpl->content .= '<div class="icontent" style="text-align:center;margin:20px;font-size:16px;font-weight:bold"><img src="img/layout/error.png" width="150" height="137" alt="Fehler" /><br /><br />Du hast keine Berechtigung, Spieler dieser Allianz anzuzeigen!</div>';
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
	SELECT COUNT(*)
	FROM ".PREFIX."planeten
	WHERE
		planeten_playerID = ".$data['playerID']."
		AND planetenUpdateOverview > 0
	) AS planetenScanned,
	(
	SELECT COUNT(*)
	FROM ".PREFIX."planeten
	WHERE
		planeten_playerID = ".$data['playerID']."
		AND planetenUpdateOverview > ".(time()-86400*$config['scan_veraltet'])."
	) AS planetenAktuell
FROM
	".PREFIX."planeten
WHERE
	planeten_playerID = ".$data['playerID']."
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
		LEFT JOIN ".PREFIX."systeme
			ON systemeID = planeten_systemeID
	WHERE
		planeten_playerID = ".$data['playerID']."
	GROUP BY
		systeme_galaxienID
	ORDER BY
		planetenCount DESC
	LIMIT 3
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

while($row = mysql_fetch_assoc($query)) {
	$gdata[] = ($user->rechte['karte'] ? '<a class="link winlink contextmenu" data-link="index.php?p=karte&amp;gala='.$row['systeme_galaxienID'].'&amp;filter=player&amp;data='.$data['playerID'].'&amp;title=Systeme%20von%20'.urlencode($data['playerName']).'">' : '').$row['systeme_galaxienID'].' ('.$row['planetenCount'].' Planet'.($row['planetenCount'] > 1 ? 'en' : '').')'.($user->rechte['karte'] ? '</a>' : '');
}

// Ausgabe
$tmpl->name = htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' ('.$data['playerID'].')';
$tmpl->icon = 'player';


$tmpl->content .= '
	<table class="tsmall tdata" style="width:100%;min-width:600px;margin-top:5px">
	<tr>
		<td style="width:40%">
			<b>Name</b>: '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8');
if($data['playerUmod']) {
	$tmpl->content .= '<sup class="small red">zzZ</sup>';
}
if(isset($rassen2[$data['playerRasse']])) {
	$tmpl->content .= ' &nbsp; <img src="img/layout/leer.gif" alt="" class="rasse '.$rassen2[$data['playerRasse']].'" />';
}
$tmpl->content .= '
			<br />
			<b>Allianz:</b> ';
// hat Allianz
if($data['allianzenTag'] != NULL) {
	if($data['statusStatus'] == NULL) $data['statusStatus'] = 0;
	$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$data['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
	// Status, wenn nicht eigene Allianz
	if($data['player_allianzenID'] != $user->allianz) {
		$tmpl->content .= '&nbsp;<span class="small hint" '.$status_color[$data['statusStatus']].'>('.$status[$data['statusStatus']].')</span>';
	}
}
// allianzlos
else if(!$data['player_allianzenID']) {
	$tmpl->content .= '<i>keine</i>';
}
// unbekannte Allianz
else {
	$tmpl->content .= '<i>unbekannt</i>';
}
$tmpl->content .= '
			<br />';
// FA
if($data['playerFA']) {
	$tmpl->content .= 'Dieser Spieler hat FA';
}
// Planetendaten
if(!$data['playerPlaneten']) {
	$pl1 = 0;
	$pl2 = 0;
	$pl3 = 0;
}
else {
	$pl1 = round(100*$pldata['planetenTotal']/$data['playerPlaneten']);
	$pl2 = round(100*$pldata['planetenScanned']/$data['playerPlaneten']);
	$pl3 = round(100*$pldata['planetenAktuell']/$data['playerPlaneten']);
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
			'.$pldata['planetenTotal'].' von '.$data['playerPlaneten'].' Planet'.($data['playerPlaneten'] != 1 ? 'en' : '').' erfasst &nbsp;';
// alle Planeten anzeigen
if($user->rechte['search']) {
	$tmpl->content .= '
			<a class="link winlink contextmenu hint" data-link="index.php?p=search&amp;s=1&amp;uid='.$data['playerID'].'&amp;hide&amp;title='.urlencode('Planeten von '.$data['playerName']).'&amp;ajax">[anzeigen]</a>';
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
	<tr>
		<td colspan="2" style="padding-top:12px">';
// letzte registrierte Aktivität
if($data['playerActivity']) {
	$tmpl->content .= '
			<b>letzte registrierte Aktivit&auml;t</b>: '.datum($data['playerActivity']).'
			<br />';
}
$tmpl->content .= '
			<b>Imperiumspunkte</b>: '.ressmenge($data['playerImppunkte']).'
			<br />';
// Gesinnung
if($data['playerGesinnung'] != NULL) {
	$tmpl->content .= '
			<b>Gesinnung</b>: <span style="color:#'.gesinnung($data['playerGesinnung']).'">'.$data['playerGesinnung'].'</span>
		</td>
	</tr>
	</table>';
}

// Spieler gelöscht
if($data['playerDeleted']) {
	$tmpl->content .= '
	<div class="center error" style="margin-top:5px">Der Spieler hat sich wahrscheinlich gel&ouml;scht!</div>';
}

$tmpl->content .= '
<br />
<div class="fcbox small2">
	<div class="center">
		<a onclick="ajaxcall(\'index.php?p=show_player&amp;id='.$data['playerID'].'&amp;sp=history&amp;ajax\', this.parentNode.parentNode, false, false)">Allianz-History des Spielers anzeigen</a>
	</div>
</div>';

// Planeten zu Route hinzufügen
if($user->rechte['routen'] AND $pldata['planetenTotal']) {
	$tmpl->content .= '
<div class="fcbox small2">
	<div class="center">
		<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=add2route&amp;typ=player&amp;id='.$data['playerID'].'&amp;ajax\', this.parentNode, false, true)">Planeten des Spielers zu einer Route / Liste hinzuf&uuml;gen</a>
	</div>
</div>';
}

// User ist in der DB angemeldet
if($data['user_playerID']) {
	$tmpl->content .= '
<div class="fcbox small2">
	<div class="center">
	Der Spieler ist in der Datenbank angemeldet.';
	// Berechtigung ermitteln
	$r = true;
	// sich selbst kann jeder sehen
	if($data['playerID'] == $user->id) {}
	// Allianz gesperrt
	else if(!$user->rechte['show_player_db_ally'] AND $user->allianz AND $data['player_allianzenID'] == $user->allianz) {
		$r = false;
	}
	// Meta gesperrt
	else if(!$user->rechte['show_player_db_meta'] AND $data['player_allianzenID'] != $user->allianz AND $data['statusStatus'] == $status_meta) {
		$r = false;
	}
	// andere Allianz gesperrt
	else if(!$user->rechte['show_player_db_other'] AND $data['statusStatus'] != $status_meta) {
		$r = false;
	}
	
	// Berechtigung
	if($r) {
		$tmpl->content .= '
		<br />
		<a onclick="ajaxcall(\'index.php?p=show_player&amp;id='.$data['playerID'].'&amp;sp=dbdata&amp;ajax\', this.parentNode.parentNode, false, false)" style="font-weight:bold">Daten anzeigen</a>';
	}
	$tmpl->content .= '
	</div>
</div>';
}

// Log-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'lässt den Spieler '.$data['playerName'].' ('.$data['playerID'].') anzeigen');
}



?>