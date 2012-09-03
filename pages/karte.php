<?php
/**
 * pages/karte.php
 * Galaxienkarte
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;
$tmpl->name = isset($_GET['title']) ? htmlspecialchars($_GET['title'], ENT_COMPAT, 'UTF-8') : 'Karte';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
);

// keine Berechtigung
if(!$user->rechte['karte']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->output();
}

// 404-Error
else if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Seite
 */

// Karte anzeigen
else {
	// Zeit-ID
	$time = time();
	
	// welche Galaxie?
	$gala = 0;
	
	// �bergeben
	if(isset($_GET['gala'])) {
		$gala = (int)$_GET['gala'];
		
		if(!$gala) {
			$tmpl->error = 'Ung�ltige Galaxie eingegeben!';
		}
		
		$query = query("
			SELECT
				galaxienID
			FROM
				".PREFIX."galaxien
			WHERE
				galaxienID = ".$gala."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Die Galaxie ist nicht eingetragen!';
		}
		else if($user->protectedGalas AND in_array($gala, $user->protectedAllies)) {
			$tmpl->error = 'Du hast keine Berechtigung, die Galaxie anzuzeigen!';
		}
	}
	
	// Heimat-Galaxie
	if(!$gala AND !$tmpl->error) {
		$query = query("
			SELECT
				systeme_galaxienID,
				COUNT(*) AS planetenCount
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
			WHERE
				planeten_playerID = ".$user->id."
				".($user->protectedGalas ? "
				AND systeme_galaxienID NOT IN(".implode(',', $user->protectedGalas).")" : "")."
			GROUP BY
				systeme_galaxienID
			ORDER BY
				planetenCount DESC,
				systeme_galaxienID ASC
			LIMIT 1
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
			$gala = $data['systeme_galaxienID'];
		}
	}
	
	// erste Galaxie nehmen
	if(!$gala AND !$tmpl->error) {
		$query = query("
			SELECT
				galaxienID
			FROM
				".PREFIX."galaxien
			".($user->protectedGalas ? "
			WHERE
				galaxienID NOT IN(".implode(',', $user->protectedGalas).")" : "")."
			ORDER BY
				galaxienID ASC
			LIMIT 1
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Es sind noch keine Galaxien eingetragen!';
		}
		else {
			$data = mysql_fetch_assoc($query);
			$gala = $data['galaxienID'];
		}
	}
	
	$tmpl->content .= '
	<div class="icontent">';
	
	
	$legende = array();
	
	// Karte anzeigen
	if(!$tmpl->error) {
		$tmpl->content .= '
	<div class="karte" id="karte'.$time.'">
		<div class="kartelabel">Galaxie '.$gala.'</div>
		';
		
		// Filter
		$color = array();
		$fett = true;
		
		if(isset($_GET['filter'], $_GET['data'])) {
			// Scan-Status
			if($_GET['filter'] == 'scan') {
				// nicht fett hervorheben
				$fett = false;
				
				$days = (int)$_GET['data'];
				if($days < 1) {
					$days = $config['scan_veraltet'];
				}
				$old = time()-$days*86400;
				$old2 = round(time()-$days*70000);
				$olddiff = time()-$old;
				$old2diff = $old2-$old;
				
				$query = query("
					SELECT
						systemeID,
						systemeUpdate
					FROM
						".PREFIX."systeme
					WHERE
						systeme_galaxienID = ".$gala."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					// ganz aktuell
					if($row['systemeUpdate'] > $old2) {
						$color[$row['systemeID']] = '#00ff00';
					}
					// nicht eingescannt
					else if(!$row['systemeUpdate']) {
						$color[$row['systemeID']] = '#ff0000';
					}
					// grade noch aktuell (gr�n -> gelb)
					else if($row['systemeUpdate'] > $old) {
						$c = dechex(round(255*(1-($row['systemeUpdate']-$old)/$old2diff)));
						if(strlen($c) == 1) $c = '0'.$c;
						$color[$row['systemeID']] = '#'.$c.'ff00';
					}
					// nicht mehr aktuell (gelb -> rot)
					else {
						$c = 80+round(120*(1-($old-$row['systemeUpdate'])/$olddiff));
						if($c < 80) {
							$c = 80;
						}
						$c = dechex($c);
						if(strlen($c) == 1) $c = '0'.$c;
						$color[$row['systemeID']] = '#ff'.$c.'00';
					}
				}
				
				// Legende
				$legende = array(
					'ff0000'=>'nicht eingescannt',
					'ff6600'=>'nicht mehr aktuell',
					'bbff00'=>'gerade noch aktuell',
					'00ff00'=>'aktuell ('.$days.' Tage)'
				);
			}
			// Gates und Myrigates
			if($_GET['filter'] == 'gates') {
				// Myrigates
				if($user->rechte['show_myrigates']) {
					// Bedingungen aufstellen
					$conds = array(
						"myrigates_galaxienID = ".$gala
					);
					
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
					
					// Daten abfragen
					$query = query("
						SELECT
							myrigatesSprung,
							planeten_systemeID
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
						WHERE
							".implode(" AND ", $conds)."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Myrigates eingetragen
					if(mysql_num_rows($query)) {
						while($row = mysql_fetch_assoc($query)) {
							// Sprunggenerator
							if($row['myrigatesSprung']) {
								$color[$row['planeten_systemeID']] = '#ff0000';
								$legende['ff0000'] = 'Sprunggenerator';
							}
							// Myrigate
							else {
								$color[$row['planeten_systemeID']] = '#00ff00';
								$legende['00ff00'] = 'Myrigate';
							}
						}
					}
				}
				
				// Gate
				$query = query("
					SELECT
						galaxienGateSys
					FROM
						".PREFIX."galaxien
					WHERE
						galaxienID = ".$gala."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				$data = mysql_fetch_assoc($query);
				
				if($data['galaxienGateSys']) {
					$color[$data['galaxienGateSys']] = '#0055ff';
					$legende['0055ff'] = 'Gatesystem';
				}
				
				// weder Gate noch Mgates
				if(!count($legende)) {
					$r = ($user->rechte['show_myrigates'] AND $user->rechte['show_myrigates_ally'] AND $user->rechte['show_myrigates_meta'] AND $user->rechte['show_myrigates_register']);
					$legende['ff0000'] = ($r ? 'Keine Gates und Myrigates eingetragen!' : 'Keine Gates und Myrigates eingetragen oder keine Berechtigung!');
				}
				
				// Legende alphabetisch sortieren
				asort($legende);
			}
			// Spieler
			else if($_GET['filter'] == 'player') {
				$id = false;
				
				// Spieler-ID
				if(is_numeric(trim($_GET['data']))) {
					$id = (int)$_GET['data'];
					
					// Daten abfragen
					$query = query("
						SELECT
							playerID,
							playerName,
							player_allianzenID
						FROM
							".GLOBPREFIX."player
						WHERE
							playerID = ".$id."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Spieler mit dieser ID existiert
					if(mysql_num_rows($query)) {
						$data = mysql_fetch_assoc($query);
						// Allianz gesperrt
						if($user->protectedAllies AND in_array($data['player_allianzenID'], $user->protectedAllies)) {
							$id = false;
							$legende = array(
								'ff0000'=>'Keine Berechtigung, Systeme des Spielers anzuzeigen!'
							);
						}
					}
					else {
						$id = false;
					}
				}
				
				// Name eingegeben oder ID nicht gefunden
				if(!$id) {
					$name = str_replace('*', '%', escape($_GET['data']));
					$like = escape($name);
					
					// Daten abfragen (doppelt escapen wegen LIKE-Bug)
					$query = query("
						SELECT
							playerID,
							playerName,
							player_allianzenID
						FROM
							".GLOBPREFIX."player
						WHERE
							playerName LIKE '".$like."'
						ORDER BY
							(playerName = '".$name."') DESC,
							playerDeleted ASC,
							playerID DESC
						LIMIT 1
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					if(mysql_num_rows($query)) {
						$data = mysql_fetch_assoc($query);
						$id = $data['playerID'];
						if($user->protectedAllies AND in_array($data['player_allianzenID'], $user->protectedAllies)) {
							$id = false;
							$legende = array(
								'ff0000'=>'Keine Berechtigung, Systeme des Spielers anzuzeigen!'
							);
						}
					}
				}
				
				// Systeme ermitteln
				if($id) {
					$query = query("
						SELECT
							planeten_systemeID
						FROM
							".PREFIX."planeten
							LEFT JOIN ".PREFIX."systeme
								ON systemeID = planeten_systemeID
						WHERE
							planeten_playerID = ".$id."
							AND systeme_galaxienID = ".$gala."
						GROUP BY
							planeten_systemeID
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					while($row = mysql_fetch_assoc($query)) {
						$color[$row['planeten_systemeID']] = '#00ff00';
					}
					
					$legende = array(
						'00ff00'=>'Systeme von <a class="link winlink contextmenu" style="color:#00ff00" data-link="index.php?p=show_player&amp;id='.$id.'">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a>'
					);
				}
				else if(!count($legende)) {
					$legende = array(
						'ff0000'=>'Spieler nicht gefunden!'
					);
				}
			}
			// Allianz
			else if($_GET['filter'] == 'ally') {
				$id = false;
				
				// Ally-ID
				if(is_numeric(trim($_GET['data']))) {
					$id = (int)$_GET['data'];
					
					// Daten abfragen
					$query = query("
						SELECT
							allianzenID,
							allianzenTag
						FROM
							".GLOBPREFIX."allianzen
						WHERE
							allianzenID = ".$id."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					// Allianz mit dieser ID existiert
					if(mysql_num_rows($query)) {
						$data = mysql_fetch_assoc($query);
						// Allianz gesperrt
						if($user->protectedAllies AND in_array($id, $user->protectedAllies)) {
							$id = false;
							$legende = array(
								'ff0000'=>'Keine Berechtigung, Systeme der Allianz anzuzeigen!'
							);
						}
					}
					else {
						$id = false;
					}
				}
				
				// Tag oder Name eingegeben oder ID nicht gefunden
				if(!$id) {
					$name = str_replace('*', '%', escape($_GET['data']));
					$like = escape($name);
					// Daten abfragen (doppelt escapen wegen LIKE-Bug)
					$query = query("
						SELECT
							allianzenID,
							allianzenTag
						FROM
							".GLOBPREFIX."allianzen
						WHERE
							allianzenTag LIKE '".$like."'
							OR allianzenName LIKE '".$like."'
						ORDER BY
							(allianzenTag = '".$name."') DESC,
							(allianzenID = '".$name."') DESC,
							allianzenID DESC
						LIMIT 1
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					if(mysql_num_rows($query)) {
						$data = mysql_fetch_assoc($query);
						$id = $data['allianzenID'];
						if($user->protectedAllies AND in_array($id, $user->protectedAllies)) {
							$id = false;
							$legende = array(
								'ff0000'=>'Keine Berechtigung, Systeme der Allianz anzuzeigen!'
							);
						}
					}
				}
				
				// Systeme ermitteln
				if($id) {
					$query = query("
						SELECT
							planeten_systemeID
						FROM
							".PREFIX."planeten
							LEFT JOIN ".PREFIX."systeme
								ON systemeID = planeten_systemeID
							LEFT JOIN ".GLOBPREFIX."player
								ON playerID = planeten_playerID
						WHERE
							player_allianzenID = ".$id."
							AND systeme_galaxienID = ".$gala."
						GROUP BY
							planeten_systemeID
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
					
					while($row = mysql_fetch_assoc($query)) {
						$color[$row['planeten_systemeID']] = '#00ff00';
					}
					
					$legende = array(
						'00ff00'=>'Systeme der Allianz <a class="link winlink contextmenu" style="color:#00ff00" data-link="index.php?p=show_ally&amp;id='.$id.'">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>'
					);
				}
				else if(!count($legende)) {
					$legende = array(
						'ff0000'=>'Allianz nicht gefunden!'
					);
				}
			}
		}
		
		// einzelne Hervorhebung
		if(isset($_GET['highlight'])) {
			$color[$_GET['highlight']] = '#00ff00';
		}
		
		// mehrfach-Hervorhebung
		if(isset($_POST['highlight'])) {
			$highlight = explode('-', $_POST['highlight']);
			foreach($highlight as $id) {
				$color[$id] = '#00ff00';
			}
		}
		
		// Systeme abfragen
		$query = query("
			SELECT
				systemeID,
				systemeX,
				systemeZ
			FROM
				".PREFIX."systeme
			WHERE
				systeme_galaxienID = ".$gala."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$x = 312+round($row['systemeX']/2.05)-6;
			$z = 310+round(-1*$row['systemeZ']/2.05)-6;
			
			$tmpl->content .= '
		<div class="stern link winlink contextmenu tooltip" style="top:'.$z.'px;left:'.$x.'px';
			// hervorgehoben
			if(isset($color[$row['systemeID']])) {
				$tmpl->content .= ';background-color:'.$color[$row['systemeID']];
				// fette Hervorhebung
				if($fett) {
					$tmpl->content .= ';width:5px;height:5px';
				}
			}
			$tmpl->content .= '" data-link="index.php?p=show_system&amp;id='.$row['systemeID'].'" data-tooltip="'.$row['systemeID'].'"></div>';
		}
		
		$tmpl->content .= '
	</div>';
	}
	// Fehlermeldung ausgeben
	else {
		$tmpl->content .= '
	<br /><br />
	<div class="center error">'.$tmpl->error.'</div>
	<br />';
		$tmpl->error = '';
	}
	
	// Legende
	if(count($legende)) {
		$tmpl->content .= '
	<div class="fcbox icontent center" style="width:605px;margin-top:8px">
		<b>Legende:</b> &nbsp;';
		foreach($legende as $col=>$val) {
			$tmpl->content .= '
	<span style="white-space:nowrap;color:#'.$col.'">'.$val.'</span> &nbsp;';
		}
		$tmpl->content .= '
	</div>';
	}
	
	
	// Querystring formatieren
	$querystring = $_GET;
	$remove = array('gala', 'sp', 'ajax', 'highlight');
	foreach($remove as $r) {
		if(isset($querystring[$r])) {
			unset($querystring[$r]);
		}
	}
	
	foreach($querystring as $key=>$val) {
		$querystring[$key] = urlencode($key).'='.urlencode($val);
	}
	$querystring = implode('&amp;', $querystring);
	
	// Galaxieauswahl und Filter
	if(!isset($_POST['highlight']) AND !isset($_GET['highlight'])) {
		$tmpl->content .= '
	<br />
	<div class="fcbox icontent center" style="width:605px">
	<form action="#" name="kartegala" onsubmit="$(\'&lt;a class=&quot;link&quot; data-link=&quot;index.php?'.$querystring.'&amp;gala=\'+this.gala.value+\'&quot;&gt;&lt;/a&gt;\').appendTo(this);$(this).find(\'a\').trigger(\'click\');return false">
	Galaxie anzeigen: <input type="text" class="smalltext" style="width:30px" name="gala" value="'.(isset($_GET['gala']) ? htmlspecialchars($_GET['gala'], ENT_COMPAT, 'UTF-8') : $gala).'" /> 
	<input type="submit" class="button" value="anzeigen" />
	</form>
	</div>
	
	<div class="fcbox icontent center" style="width:605px;margin-top:10px">
		<div class="fhl2">Systeme hervorheben</div>
		<div style="padding-top:5px;line-height:40px">
			<a class="link" data-link="index.php?p=karte&amp;gala='.$gala.'&amp;filter=gates&amp;data=1">Gates und Myrigates hervorheben</a>
			<form action="#" name="kartefilterscan" onsubmit="$(\'&lt;a class=&quot;link&quot; data-link=&quot;index.php?p=karte&amp;gala=\'+$(this.parentNode.parentNode.parentNode).find(\'input[name=&quot;gala&quot;]\').val()+\'&amp;filter=scan&amp;data=\'+this.filter_scan.value+\'&quot;&gt;&lt;/a&gt;\').appendTo(this);$(this).find(\'a\').trigger(\'click\');return false">
			Scan-Status f&uuml;r 
			&nbsp;<input type="text" class="smalltext" name="filter_scan" value="'.((isset($_GET['filter'], $_GET['data']) AND $_GET['filter'] == 'scan') ? htmlspecialchars($_GET['data'], ENT_COMPAT, 'UTF-8') : $config['scan_veraltet']).'" />&nbsp;
			Tage 
			&nbsp;<input type="submit" class="button" value="hervorheben" />
			</form>
			<form action="#" name="kartefilterplayer" onsubmit="$(\'&lt;a class=&quot;link&quot; data-link=&quot;index.php?p=karte&amp;gala=\'+$(this.parentNode.parentNode.parentNode).find(\'input[name=&quot;gala&quot;]\').val()+\'&amp;filter=player&amp;data=\'+this.filter_player.value+\'&quot;&gt;&lt;/a&gt;\').appendTo(this);$(this).find(\'a\').trigger(\'click\');return false">
			Systeme des Spielers 
			&nbsp;<input type="text" class="smalltext" style="width:100px" name="filter_player" value="'.htmlspecialchars(((isset($_GET['filter'], $_GET['data']) AND $_GET['filter'] == 'player') ? $_GET['data'] : $user->name), ENT_COMPAT, 'UTF-8').'" />&nbsp;
			&nbsp;<input type="submit" class="button" value="hervorheben" />
			</form>
			<form action="#" name="kartefilterally" onsubmit="$(\'&lt;a class=&quot;link&quot; data-link=&quot;index.php?p=karte&amp;gala=\'+$(this.parentNode.parentNode.parentNode).find(\'input[name=&quot;gala&quot;]\').val()+\'&amp;filter=ally&amp;data=\'+this.filter_ally.value+\'&quot;&gt;&lt;/a&gt;\').appendTo(this);$(this).find(\'a\').trigger(\'click\');return false">
			Systeme der Allianz 
			&nbsp;<input type="text" class="smalltext" style="width:100px" name="filter_ally" value="'.htmlspecialchars(((isset($_GET['filter'], $_GET['data']) AND $_GET['filter'] == 'ally') ? $_GET['data'] : ($user->allianz ? $user->allianz : '')), ENT_COMPAT, 'UTF-8').'" />&nbsp;
			&nbsp;<input type="submit" class="button" value="hervorheben" />
			</form>
		</div>
	</div>';
	}
	
	$tmpl->content .= '
	</div>';
	
	// Ausgabe
	$tmpl->output();
}

?>