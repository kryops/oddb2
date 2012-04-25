<?php
/**
 * pages/allianzen.php
 * Allianzübersicht mit Statuseinstellung
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Allianzen';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'status'=>true
);

/**
 * Funktionen
 */

/**
 * gibt eine Allianz-Zeile zurück
 * @param $row Array Datensatz
 *
 * @return HTML Tabellenzeile
 */
function allianzrow($row) {
	global $user, $status, $oldtime;
	
	// Allianz möglichweise aufgelöst
	$old = false;
	if($row['allianzenUpdate'] < $oldtime) $old = true;
	
	$c = '
		<tr class="allianzrow'.$row['allianzenID'].'"'.($old ? ' style="opacity:0.4"' : '').'>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.$row['allianzenID'].'</a></td>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenName'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td>'.$row['allianzenMember'].'</td>
			<td>';
	
	// Lux-Ally
	if($row['allianzenLux']) {
		$c .= '<img src="img/layout/leer.gif" class="rasse lux" />';
	}
	else {
		$c .= '&nbsp;';
	}
	
	$c .= '</td>';
	
	// Status ändern
	if($user->rechte['verwaltung_allianzen']) {
		$c .= '
			<td class="userlistaction">&auml;ndern in 
				<select size="1">';
		foreach($status as $key=>$val) {
			$c .= '
					<option value="'.$key.'"'.($key == $row['statusStatus'] ? ' selected="selected"' : '').'>'.$val.'</option>';
		}
		$c .= '
				</select> 
				<img src="img/layout/leer.gif" class="hoverbutton" style="background-position:-1060px -91px" onclick="ajaxcall(\'index.php?p=allianzen&amp;sp=status&amp;ajax\', false, {\'id\':'.$row['allianzenID'].', \'status\':$(this).siblings(\'select\').val()}, false)" alt="[&auml;ndern]" />
			</td>';
	}
	$c .= '
		</tr>';
	
	// zurückgeben
	return $c;
}




// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Seite
 */

// Status ändern
else if($_GET['sp'] == 'status') {
	// Daten vorhanden
	if(!isset($_POST['id'], $_POST['status'], $status[$_POST['status']])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// keine Berechtigung
	else if(!$user->rechte['verwaltung_allianzen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// keine Allianz
	else if(!$user->allianz) {
		$tmpl->error = 'Du gehörst keiner Allianz an!';
	}
	// eigene Allianz
	else if((int)$_POST['id'] == $user->allianz) {
		$tmpl->content = '
		<br />
		Deine eigene Allianz hat automatisch den Status '.$status[$status_meta].'!';
	}
	// alles ok
	else {
		// Daten sichern
		$_POST['id'] = (int)$_POST['id'];
		$_POST['status'] = (int)$_POST['status'];
		
		// Allianz vorhanden?
		$query = query("
			SELECT
				allianzenTag,
				statusStatus
			FROM
				".GLOBPREFIX."allianzen
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			WHERE
				allianzenID = ".$_POST['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// nicht gefunden
		if(!mysql_num_rows($query)) {
			$tmpl->error = 'Die Allianz wurde nicht gefunden!';
		}
		// gefunden
		else {
			$data = mysql_fetch_assoc($query);
			
			// Status ändern
			if($data['statusStatus'] == NULL) $data['statusStatus'] = 0;
			if($_POST['status'] != $data['statusStatus']) {
				// evtl alten Status löschen
				query("
					DELETE FROM
						".PREFIX."allianzen_status
					WHERE
						statusDBAllianz = ".$user->allianz."
						AND status_allianzenID = ".$_POST['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				// neuen Status eintragen, wenn nicht neutral
				if($_POST['status']) {
					query("
						INSERT INTO
							".PREFIX."allianzen_status
						SET
							statusDBAllianz = ".$user->allianz.",
							status_allianzenID = ".$_POST['id'].",
							statusStatus = ".$_POST['status']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
			}
			
			// Ausgabe
			$tmpl->content = '
			<br />
			Die Allianz <a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$_POST['id'].'">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a> wurde auf '.$status[$_POST['status']].' gesetzt';
			
			// Tabellenscript
			if($_POST['status'] != $data['statusStatus']) {
				$tmpl->script = 'allianz_status('.$_POST['id'].', '.$_POST['status'].')';
			}
			
			// Log-Eintrag
			if($config['logging'] >= 1) {
				insertlog(23, 'ändert den Status der Allianz '.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').' ('.$_POST['id'].') auf '.$status[$_POST['status']]);
			}
		}
	}
	
	$tmpl->output();
}
// Übersichtsseite anzeigen
else {
	$tmpl->content = '
	<div class="icontent">
		';
	// keine Allianz
	if(!$user->allianz) {
		$tmpl->content .= '
		<br />
		<div class="center">
			Du geh&ouml;rst keiner Allianz an!
			<br /><br />
			Die Statuseinstellungen greifen nur f&uuml;r Spieler, die einer Allianz angeh&ouml;ren.
		</div>
		<br />';
	}
	// gehört Allianz an
	else {
		// aufgelöst-Zeit berechnen
		$oldtime = time()-$gconfig['odrequest']*3600-3600;
		
		
		$tmpl->content .= '
		Durch den Status der Allianzen wird festgelegt, welche Allianz als Metapartner angesehen wird.
		<br />
		Au&szlig;erdem k&ouml;nnen Allianzen in Freund (Meta/HAK) und Feind (Kriegsgegner/Piraten) unterteilt werden.
		<br />
		Die Statuseinstellungen gelten nur f&uuml;r deine Allianz, jede angemeldete Allianz kann ihre eigenen Einstellungen haben.
		<br /><br />
		Ist eine Allianz noch nicht eingetragen, musst du einen ihrer Member &uuml;ber die Spieler-ID in der Schnellzugriffsleiste aufrufen.
		<br /><br /><br />';
		
		// Berechtigung
		if($user->rechte['verwaltung_allianzen']) {
			$tmpl->content .= '
		<div class="fcbox center" style="width:85%">
			<form action="#" name="allianzen" onsubmit="return form_send(this, \'index.php?p=allianzen&amp;sp=status&amp;ajax\', $(this).siblings(\'.ajax\'))">
			<b>Schnell-Einstellung</b>: Die Allianz mit der ID 
			&nbsp;<input type="text" class="smalltext" name="id" />&nbsp; 
			auf 
			&nbsp;<select name="status" size="1">';
			foreach($status as $key=>$val) {
				$tmpl->content .= '
				<option value="'.$key.'">'.$val.'</option>';
			}
			$tmpl->content .= '
			</select>&nbsp;
			<input type="submit" class="button" style="width:80px" value="setzen" />
			</form>
			<div class="ajax center"></div>
		</div>
		<br /><br />';
		}
		
		// Filter
		$tmpl->content .= '
		<div class="center">
			Allianzen nach Tag oder Name filtern: <input type="text" class="smalltext" style="width:100px" onkeyup="allianz_filter(this.parentNode.parentNode, this.value)" />
		</div>
		<br /><br />';
		
		// Tabellenheadline
		$h = '<tr class="allianzenheadline">
			<th><a class="link" data-link="index.php?p=allianzen">ID</a></th>
			<th><a class="link" data-link="index.php?p=allianzen&amp;sort=tag">Tag</a></th>
			<th><a class="link" data-link="index.php?p=allianzen&amp;sort=name">Name</a></th>
			<th><a class="link" data-link="index.php?p=allianzen&amp;sort=member">Member</a></th>
			<th>&nbsp;</th>
			'.($user->rechte['verwaltung_allianzen'] ? '<th>&nbsp;</th>' : '').'
		</tr>';
		
		
		$s = 0;
		
		// Sortierung
		$sort = array(
			'id'=>'allianzenID ASC',
			'tag'=>'allianzenTag ASC',
			'name'=>'allianzenName ASC',
			'member'=>'allianzenMember DESC'
		);
		
		if(!isset($_GET['sort']) OR !isset($sort[$_GET['sort']])) {
			$sort = $sort['id'];
		}
		else {
			$sort = $sort[$_GET['sort']];
		}
		
		// Allianzen abfragen
		$query = query("
			SELECT
				allianzenID,
				allianzenTag,
				allianzenName,
				allianzenMember,
				allianzenUpdate,
				
				statusStatus,
				
				playerID AS allianzenLux
			FROM
				".GLOBPREFIX."allianzen
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
				LEFT JOIN ".GLOBPREFIX."player
					ON player_allianzenID = allianzenID
					AND playerRasse = 10
			WHERE
				allianzenID != ".$user->allianz."
				AND statusStatus > 0
			GROUP BY
				allianzenID
			ORDER BY 
				statusStatus ASC,
				".$sort."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			
			// neuer Status
			if($row['statusStatus'] != $s) {
				// Tabelle schließen
				if($s) {
					$tmpl->content .= '
		</table>
		<br />';
				}
				
				// Status überprungen / keine Allianzen eingeteilt
				foreach($status as $key=>$val) {
					if($key > $s AND $key < $row['statusStatus']) {
						$tmpl->content .= '
		<div class="hl2">'.$val.'</div>
		<table class="data allianzstatus'.$key.'" style="margin:auto;min-width:85%">
		'.$h.'
		<tr class="allianzenkeine">
			<td colspan="'.($user->rechte['verwaltung_allianzen'] ? '6' : '5').'" style="font-style:italic">keine</td>
		</tr>
		</table>
		<br />';
					}
				}
				
				// Headline und Tabelle auf
				$tmpl->content .= '
		<div class="hl2">'.$status[$row['statusStatus']].'</div>
		<table class="data allianzstatus'.$row['statusStatus'].'" style="margin:auto;min-width:85%">
		'.$h;
				
			}
			
			$tmpl->content .= allianzrow($row);
			
			$s = $row['statusStatus'];
		}
		
		// Tabelle schließen
		if($s) {
			$tmpl->content .= '
		</table>
		<br />';
		}
		
		// keine Einträge für weitere Stati
		foreach($status as $key=>$val) {
			if($key > $s) {
				$tmpl->content .= '
		<div class="hl2">'.$val.'</div>
		<table class="data allianzstatus'.$key.'" style="margin:auto;min-width:85%">
		'.$h.'
		<tr class="allianzenkeine">
			<td colspan="'.($user->rechte['verwaltung_allianzen'] ? '6' : '5').'" style="font-style:italic">keine</td>
		</tr>
		</table>
		<br />';
			}
		}
		
		// neutrale Allys
		// Allianzen abfragen
		$query = query("
			SELECT
				allianzenID,
				allianzenTag,
				allianzenName,
				allianzenMember,
				allianzenUpdate,
				
				statusStatus,
				
				playerID AS allianzenLux
			FROM
				".GLOBPREFIX."allianzen
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
				LEFT JOIN ".GLOBPREFIX."player
					ON player_allianzenID = allianzenID
					AND playerRasse = 10
			WHERE
				allianzenID != ".$user->allianz."
				AND (statusStatus IS NULL OR statusStatus = 0)
				AND allianzenUpdate > ".$oldtime."
			GROUP BY
				allianzenID
			ORDER BY 
				".$sort."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Headline
		$tmpl->content .= '
		<div class="hl2">'.$status[0].'</div>
		<table class="data allianzstatus0" style="margin:auto;min-width:85%">
		'.$h;
		
		// keine
		if(!mysql_num_rows($query)) {
			$tmpl->content .= '
		<tr>
			<td colspan="'.($user->rechte['verwaltung_allianzen'] ? '5' : '4').'" style="font-style:italic">keine</td>
		</tr>';
		}
		
		while($row = mysql_fetch_assoc($query)) {
			$tmpl->content .= allianzrow($row);
		}
	}
	$tmpl->content .= '
		</table>
	</div>
	';
	
	// Ausgabe
	$tmpl->output();
	
	// Log-Eintrag
	if($config['logging'] == 3) {
		insertlog(5, 'lässt sich die Allianzliste anzeigen');
	}
}

?>