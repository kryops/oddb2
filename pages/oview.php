<?php
/**
 * pages/oview.php
 * Übersichtsseite
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Übersicht';

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

// Übersichtsseite anzeigen
else {
	$tmpl->content = '
<div class="icontent">
	<div class="small2" style="float:right">
		ODDB V'.VERSION.' - OD '.ODWORLD.' &nbsp;
		<a href="changelog.txt" target="_blank" class="hint">[Changelog]</a>
	</div>
	<br />
	<span class="big">Willkommen '.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'!</span>
	<br /><br />';
	
	// globale Nachricht anzeigen
	if(isset($config['oviewmsg'])) {
		$tmpl->content .= '
	<div style="margin:5px 15px 15px 15px">
	'.nl2br(htmlspecialchars($config['oviewmsg'], ENT_COMPAT, 'UTF-8')).'
	</div>';
	}
	
	//
	// laufende Aktionen
	//
	
	if($user->rechte['invasionen'] AND $cache->get('invas') !== 0) {
		// Bedingungen aufstellen
		$conds = array(
			"(invasionenEnde = 0 OR invasionenEnde > ".time().")",
			"invasionenFremd = 0",
			"invasionenTyp != 5"
		);
		
		if($user->protectedAllies) {
			$conds[] = "(p1.player_allianzenID IS NULL OR p1.player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
			$conds[] = "(p2.player_allianzenID IS NULL OR p2.player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
		}
		if($user->protectedGalas) {
			$conds[] = "systeme_galaxienID NOT IN(".implode(', ', $user->protectedGalas).")";
		}
		
		// Daten abfragen
		$query = query("
			SELECT
				invasionenOpen,
				invasionenAbbrecher,
				invasionenFreundlich
			FROM
				".PREFIX."invasionen
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = invasionen_systemeID
				LEFT JOIN ".GLOBPREFIX."player p1
					ON p1.playerID = invasionen_playerID
				LEFT JOIN ".GLOBPREFIX."player p2
					ON p2.playerID = invasionenAggressor
			WHERE
				".implode(' AND ', $conds)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$count = mysql_num_rows($query);
		
		// Aktionen vorhanden
		if($count) {
			$invas = array(0, 0, 0);
			
			while($row = mysql_fetch_assoc($query)) {
				if($row['invasionenOpen']) {
					$invas[0]++;
				}
				else if($row['invasionenAbbrecher']) {
					$invas[1]++;
				}
				else if($row['invasionenFreundlich']) {
					$invas[2]++;
				}
			}
			
			// offene Invasionen aktualisieren
			if(isset($_GET['ajax'])) {
				$tmpl->script = 'openinvas_update('.$invas[0].')';
			}
			
			$invac = array();
			if($invas[0]) {
				$invac[] = '<span class="red">'.$invas[0].' offen</span>';
			}
			if($invas[1]) {
				$invac[] = '<span class="yellow">'.$invas[1].' in Bearbeitung</span>';
			}
			if($invas[2]) {
				$invac[] = '<span class="green">'.$invas[2].' als freundlich markiert</span>';
			}
			
			if(count($invac) == 3) {
				$invac = $invac[0].', '.$invac[1].' und '.$invac[2];
			}
			else {
				$invac = implode(' und ', $invac);
			}
			
			$tmpl->content .= '
	<table class="oviewtbl">
	<tr>
	<td><div class="oviewicon link" data-link="index.php?p=inva" title="zum Invasionsbereich"></div></td>
	<td>
		<b>Es '.($count == 1 ? 'ist 1 Aktion' : 'sind '.$count.' Aktionen').' gegen angemeldete Spieler eingetragen!</b>';
		
		if($invac != '') {
			$tmpl->content .= '
		<br />davon '.$invac;
		}
		
		$tmpl->content .= '
		<br />
		<a class="link contextmenu" data-link="index.php?p=inva">&raquo; zum Invasionsbereich</a>
	</td>
	</tr>
	</table>';
		}
	}
	
	//
	// noch nicht freigeschaltet
	//
	
	if($user->rechte['verwaltung_user_register'] OR ($user->rechte['verwaltung_userally'] AND $user->allianz) AND $cache->get('userbanned') !== 0) {
		// Bedingungen aufstellen
		$conds = array(
			"userBanned = 3"
		);
		
		if(!$user->rechte['verwaltung_user_register']) {
			$conds[] = "user_allianzenID = ".$user->allianz;
		}
		
		if($user->protectedAllies) {
			$conds[] = "user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
		}
		
		// Daten abfragen
		$query = query("
			SELECT
				COUNT(*) AS userCount
			FROM
				".PREFIX."user
			WHERE
				".implode(' AND ', $conds)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_assoc($query);
		
		$count = $data['userCount'];
		
		if($count) {
			$tmpl->content .= '
	<table class="oviewtbl">
	<tr>
	<td><div class="oviewicon link" data-link="index.php?p=player" style="background-position:-120px 0px"></div></td>
	<td>
		<b>'.$count.' Spieler wurde'.($count == 1 ? '' : 'n').' noch nicht freigeschaltet!</b>
		<br />
		<a class="link contextmenu" data-link="index.php?p=player">&raquo; zur Spielerliste</a>
	</td>
	</tr>
	</table>';
		}
	
		//
		// automatisch gesperrte Spieler
		//
		
		// Bedingungen aufstellen
		$conds = array(
			"userBanned = 2"
		);
		
		if(!$user->rechte['verwaltung_user_register']) {
			$conds[] = "user_allianzenID = ".$user->allianz;
		}
		
		if($user->protectedAllies) {
			$conds[] = "user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
		}
		
		// Daten abfragen
		$query = query("
			SELECT
				COUNT(*) AS userCount
			FROM
				".PREFIX."user
			WHERE
				".implode(' AND ', $conds)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_assoc($query);
		
		$count = $data['userCount'];
		
		if($count) {
			$tmpl->content .= '
	<table class="oviewtbl">
	<tr>
	<td><div class="oviewicon link" data-link="index.php?p=player" style="background-position:-240px 0px"></div></td>
	<td>
		<b>'.$count.' Spieler wurde'.($count == 1 ? '' : 'n').' automatisch gesperrt!</b>
		<br />
		<a class="link contextmenu" data-link="index.php?p=player">&raquo; zur Spielerliste</a>
	</td>
	</tr>
	</table>';
		}
	}
	
	//
	// veraltete Scans und fehlende Sitter
	//
	
	// Daten aus dem Cache lesen
	$c = array(
		$cache->get('scans'.$user->id),
		($user->allianz ? $cache->get('fow_ally'.$user->allianz) : 1),
		($user->allianz ? $cache->get('sitterpflicht'.$user->id) : 1),
	);
	
	if($user->rechte['scan'] AND ($c[0] === false OR $c[1] === false OR $c[2] === false)) {
		
		$query = query("
			SELECT
				userOverviewUpdate,
				userFlottenUpdate,
				userODSettingsUpdate,
				userSitterUpdate,
				userSitterTo
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$user->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
			
			//
			// veraltete Scans
			//
			
			$scans = array();
			$pscans = array();
			$ascans = 0;
			
			// User-Scans
			if($c[0] === false) {
				// Planetenübersicht
				if(time()-$data['userOverviewUpdate'] > $config['scan_veraltet_oview']*3600) {
					$scans[] = '<a href="http://www.omega-day.com/game/?op=planlist" target="_blank">Planeten&uuml;bersicht</a>';
				}
				// Flottenübersicht
				if(time()-$data['userFlottenUpdate'] > $config['scan_veraltet_flotten']*86400) {
					$scans[] = '<a href="http://www.omega-day.com/game/?op=fleet" target="_blank">Flotten&uuml;bersicht</a>';
				}
				// Sitter
				if(time()-$data['userSitterUpdate'] > $config['scan_veraltet_einst']*86400) {
					$scans[] = '<a href="http://www.omega-day.com/game/?op=sitter" target="_blank">Sitter</a>';
				}
				// Einstellungen
				if(time()-$data['userODSettingsUpdate'] > $config['scan_veraltet_einst']*86400) {
					$scans[] = '<a href="http://www.omega-day.com/game/?op=settings" target="_blank">Einstellungen</a>';
				}
				
				// Planeten
				$query = query("
					SELECT
						planetenID
					FROM
						".PREFIX."planeten
					WHERE
						planeten_playerID = ".$user->id."
						AND planetenUpdate < ".(time()-$config['scan_veraltet_ally']*86400)."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					$pscans[] = '<a href="http://www.omega-day.com/game/?op=planet&amp;index='.$row['planetenID'].'" target="_blank">'.$row['planetenID'].'</a>';
				}
				
				// Cache setzen
				if(!count($scans) AND !count($pscans)) {
					$cache->set('scans'.$user->id, 1, 3600);
				}
			}
			
			// Ally-Systeme
			if($c[1] === false) {
				// Systeme abfragen
				$query = query("
					SELECT
						COUNT(*) AS systemeCount
					FROM
						".PREFIX."systeme
					WHERE
						systemeUpdate < ".(time()-$config['scan_veraltet_ally']*86400)."
						AND systemeAllianzen LIKE '%+".$user->allianz."+%'
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$data2 = mysql_fetch_assoc($query);
				
				$ascans = $data2['systemeCount'];
				
				if(!$ascans) {
					$cache->set('fow_ally'.$user->allianz, 1, 3600);
				}
			}
			
			// anzeigen
			if(count($scans) OR count($pscans) OR $ascans) {
				$tmpl->content .= '
	<table class="oviewtbl">
	<tr>
	<td><div class="oviewicon" style="background-position:-360px 0px"></div></td>
	<td>
		<b>Folgende Scans fehlen oder sind veraltet:</b>';
				if(count($scans)) {
					$tmpl->content .= '
		<br />
		'.implode(', ', $scans);
				}
				if(count($pscans)) {
					$tmpl->content .= '
		<br />Planet 
		'.implode(', ', $pscans);
				}
				if($ascans) {
					$tmpl->content .= '
		<br /><a class="link contextmenu" data-link="index.php?p=scout&amp;sp=intern">'.$ascans.' System'.($ascans == 1 ? ' deiner Allianz ist' : 'e deiner Allianz sind').' veraltet</a>';
				}
				
				$tmpl->content .= '
	</td>
	</tr>
	</table>';
			}
			
			//
			// Fehlende Sitter
			//
			if($c[2] === false AND $user->allianz AND $data['userSitterUpdate']) {
				$conds = array(
					'user_allianzenID = '.$user->allianz,
					'userSitterpflicht = 1',
					'user_playerID != '.$user->id
				);
				// schon vorhandene Sitter ausfiltern
				if($data['userSitterTo'] != '') {
					$data['userSitterTo'] = explode('+', $data['userSitterTo']);
					$conds[] = 'user_playerID NOT IN('.implode(', ', $data['userSitterTo']).')';
				}
				
				$query = query("
					SELECT
						user_playerID,
						user_playerName
					FROM
						".PREFIX."user
					WHERE
						".implode(' AND ', $conds)."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// nicht alle Sitter eingerichtet
				if($count = mysql_num_rows($query)) {
					$sitter = array();
					while($row = mysql_fetch_assoc($query)) {
						$sitter[] = '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['user_playerID'].'">'.htmlspecialchars($row['user_playerName'], ENT_COMPAT, 'UTF-8').'</a>';
					}
					
					$tmpl->content .= '
	<table class="oviewtbl">
	<tr>
	<td><div class="oviewicon" style="background-position:-720px 0px"></div></td>
	<td>
		<b>Du musst noch '.$count.' Sitter einrichten:</b>
		<br />
		'.implode(', ', $sitter).'
	</td>
	</tr>
	</table>';
				}
				// Cache setzen
				else {
					$cache->set('sitterpflicht'.$user->id, 1, 3600);
				}
				
			}
		}
	}
	
	
	//
	// User online	
	//
	
	$uonline = array();
	
	// aus der DB laden
	
	// Bedingungen aufstellen
	$conds = array(
		"userOnlineDB > ".(time()-300)
	);
	
	if($user->protectedAllies) {
		$conds[] = "user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
	}
	
	if(!$user->rechte['show_player_db_meta']) {
		$conds[] = "(user_allianzenID = ".$user->allianz." OR statusStatus IS NULL OR statusStatus != ".$status_meta.")";
	}
	if(!$user->rechte['show_player_db_other']) {
		$conds[] = "statusStatus = ".$status_meta;
	}
	
	$query = query("
		SELECT
			user_playerID,
			user_playerName
		FROM
			".PREFIX."user
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = user_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = allianzenID
		WHERE
			".implode(' AND ', $conds)."
		ORDER BY
			user_playerName ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$uonline[$row['user_playerID']] = $row['user_playerName'];
	}
	
	// eigenen Account hinzufügen
	if(!isset($uonline[$user->id])) {
		$uonline[$user->id] = $user->name;
		asort($uonline);
	}
	
	$count = count($uonline);
	
	$tmpl->content .= '
	<table class="oviewtbl">
	<tr>
	<td><div class="oviewicon" style="background-position:-600px 0px"></div></td>
	<td>
		<b>Es '.($count == 1 ? 'ist' : 'sind').' '.$count.' User online:</b>
		<br />';
	
	$i = 1;
	foreach($uonline as $id=>$name) {
		$tmpl->content .= '
		<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$id.'">'.htmlspecialchars($name, ENT_COMPAT, 'UTF-8').'</a>';
		
		if($i != $count) {
			$tmpl->content .= ', ';
		}
		$i++;
	}
	
	$tmpl->content .= '
	</td>
	</tr>
	</table>
	<br /><br />
</div>';
	
	// Ausgabe
	$tmpl->output();
}

?>