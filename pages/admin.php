<?php
/**
 * pages/admin.php
 * Userverwaltung
 * Registrierungseinstellungen
 * Rechte
 * Galaxien
 * Logfile
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	if($user->rechte['verwaltung_galaxien'] OR $user->rechte['verwaltung_galaxien2']) $_GET['sp'] = 'galaxien';
	else if($user->rechte['verwaltung_user_register']) $_GET['sp'] = 'register';
	else if($user->rechte['verwaltung_settings']) $_GET['sp'] = 'settings';
	else $_GET['sp'] = '';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Verwaltung';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'galaxien'=>true,
	'galaxien_parse'=>true,
	'galaxien_merge'=>true,
	'galaxien_del'=>true,
	
	'register'=>true,
	'register_addally'=>true,
	'register_addplayer'=>true,
	'register_delally'=>true,
	'register_delally2'=>true,
	'register_delplayer'=>true,
	'register_delplayer2'=>true,
	'register_allyconfig'=>true,
	'register_allyconfig2'=>true,
	
	
	'rechte'=>true,
	'rechte_edit'=>true,
	'rechte_send'=>true,
	
	'logfile'=>true,
	
	'settings'=>true,
	'settings_send'=>true,
	'settings_insertplayer'=>true,
	
	'backup'=>true,
	'backup_export'=>true,
	'backup_import'=>true
);


/**
 * Funktionen
 */

/**
 * Allianz-Zeile für die Registrierungstabelle erzeugen
 * @param $row array Datensatz
 * @param $br bool Zeilenumbrüche entfernen
 * @return HTML Zeile
 */
function allianzrow($row, $br=false) {
	global $rechtenamen;
	
	$c = '
		<tr class="registerallianzrow'.$row['allianzenID'].'">
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.$row['allianzenID'].'</a></td>
			<td style="white-space:nowrap"><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['allianzenID'].'">'.htmlspecialchars($row['allianzenName'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td class="small2">';
	
	// Einschränkungen
	if($row['registerProtectedAllies'] != '' OR $row['registerProtectedGalas'] != '' OR $row['registerAllyRechte'] != '') {
		$c .= '
		<span class="red">';
		
		// gesperrte Berechtigungen
		if($row['registerAllyRechte'] != '') {
			$ar = explode('+', $row['registerAllyRechte']);
			foreach($ar as $key=>$val) {
				$ar[$key] = $rechtenamen[$val];
			}
		}
		else {
			$ar = array();
		}
		// gesperrte Allianzen
		if($row['registerProtectedAllies'] != '') {
			$row['registerProtectedAllies'] = explode('+', $row['registerProtectedAllies']);
			
			$query2 = query("
				SELECT
					allianzenID,
					allianzenTag
				FROM
					".GLOBPREFIX."allianzen
				WHERE
					allianzenID IN (".implode(', ', $row['registerProtectedAllies']).")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query2)) {
				$pa = array();
				while($row2 = mysql_fetch_assoc($query2)) {
					$pa[] = '<a class="link winlink contextmenu red" data-link="index.php?p=show_ally&amp;id='.$row2['allianzenID'].'">'.htmlspecialchars($row2['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
				}
				$ar[] = 'Zugriff auf Allianz'.(count($row['registerProtectedAllies']) != 1 ? 'en' : '').' '.implode(', ', $pa).' gesperrt';
			}
		}
		
		// gesperrte Galaxien
		if($row['registerProtectedGalas'] != '') {
			$row['registerProtectedGalas'] = explode('+', $row['registerProtectedGalas']);
			
			$ar[] = 'Zugriff auf Galaxie'.(count($row['registerProtectedGalas']) != 1 ? 'n' : '').' '.implode(', ', $row['registerProtectedGalas']).' gesperrt';
		}
		
		$c .= implode(', ', $ar).'</span>';
	}
	// keine Einschränkungen
	else {
		$c .= '<i>keine</i>';
	}
	
	$c .= '
			</td>
			<td class="userlistaction">
				<img src="img/layout/leer.gif" style="background-position:-1020px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=admin&amp;sp=register_allyconfig&amp;id='.$row['allianzenID'].'" title="Einschr&auml;nkungen bearbeiten" />
				<img src="img/layout/leer.gif" style="background-position:-1040px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=admin&amp;sp=register_delally&amp;id='.$row['allianzenID'].'" title="Registrierungserlaubnis entziehen" />
			</td>
		</tr>';
	
	// Zeilenumbrüche entfernen
	if($br) {
		$c = str_replace(
			array("\r\n", "\n"),
			array('', ''),
			$c
		);
	}
	
	// zurückgeben
	return $c;
}


/**
 * Spieler-Zeile für die Registrierungstabelle erzeugen
 * @param $row array Datensatz
 * @param $br bool Zeilenumbrüche entfernen
 * @return HTML Zeile
 */
function userrow($row, $br=false) {
	$c = '
		<tr class="registeruserrow'.$row['playerID'].'">
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['playerID'].'">'.$row['playerID'].'</a></td>
			<td><a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$row['playerID'].'">'.htmlspecialchars($row['playerName'], ENT_COMPAT, 'UTF-8').'</a></td>
			<td style="white-space:nowrap">'.($row['allianzenTag'] != NULL ? '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$row['player_allianzenID'].'">'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>' : '&nbsp;').'</td>
			<td>'.($row['user_playerID'] != NULL ? 'ja' : 'nein').'</td>
			<td class="userlistaction">
				<img src="img/layout/leer.gif" style="background-position:-1040px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=admin&amp;sp=register_delplayer&amp;id='.$row['playerID'].'" title="Registrierungserlaubnis entziehen" />
			</td>
		</tr>';
	
	// Zeilenumbrüche entfernen
	if($br) {
		$c = str_replace(
			array("\r\n", "\n"),
			array('', ''),
			$c
		);
	}
	
	// zurückgeben
	return $c;
}




// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

/**
 * Galaxieverwaltung
 */
// Galaxie einparsen (AJAX)
else if($_GET['sp'] == 'galaxien_parse') {
	include './pages/admin/galaxien_parse.php';
}
// Galaxien verschmelzen (AJAX)
else if($_GET['sp'] == 'galaxien_merge') {
	include './pages/admin/galaxien_merge.php';
}
// Galaxie löschen (AJAX)
else if($_GET['sp'] == 'galaxien_del') {
	include './pages/admin/galaxien_del.php';
}

/**
 * Registrierung
 */
// Registriererlaubnis für Allianz hinzufügen
else if($_GET['sp'] == 'register_addally') {
	include './pages/admin/register_ally.php';
}
// Registriererlaubnis für Spieler hinzufügen
else if($_GET['sp'] == 'register_addplayer') {
	include './pages/admin/register_player.php';
}
// Registriererlaubnis für Allianz entziehen -> Dialog
else if($_GET['sp'] == 'register_delally') {
	include './pages/admin/register_ally.php';
}
// Registriererlaubnis für Allianz entziehen -> ausführen
else if($_GET['sp'] == 'register_delally2') {
	include './pages/admin/register_ally.php';
}
// Registriererlaubnis für Spieler entziehen -> Dialog
else if($_GET['sp'] == 'register_delplayer') {
	include './pages/admin/register_player.php';
}
// Registriererlaubnis für Spieler entziehen -> ausführen
else if($_GET['sp'] == 'register_delplayer2') {
	include './pages/admin/register_player.php';
}
// Allianz bearbeiten
else if($_GET['sp'] == 'register_allyconfig') {
	include './pages/admin/register_ally.php';
}
// Allianz bearbeiten -> ausführen
else if($_GET['sp'] == 'register_allyconfig2') {
	include './pages/admin/register_ally.php';
}

/**
 * Berechtigungen
 */
// Rechtelevel bearbeiten
else if($_GET['sp'] == 'rechte_edit') {
	include './pages/admin/rechte_edit.php';
}
// Rechtelevel speichern
else if($_GET['sp'] == 'rechte_send') {
	include './pages/admin/rechte_send.php';
}

/**
 * Einstellungen
 */
// Einstellungen speichern
else if($_GET['sp'] == 'settings_send') {
	include './pages/admin/settings_send.php';
}
// Spielerprofile einlesen
else if($_GET['sp'] == 'settings_insertplayer') {
	include './pages/admin/settings_insertplayer.php';
}

/**
 * Import/Export
 */
// Daten exportieren
else if($_GET['sp'] == 'backup_export') {
	include './pages/admin/backup_export.php';
}
// Daten importieren
else if($_GET['sp'] == 'backup_import') {
	include './pages/admin/backup_import.php';
}






/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Galaxienverwaltung
	if($user->rechte['verwaltung_galaxien'] OR $user->rechte['verwaltung_galaxien2']) {
		$csw->data['galaxien'] = array(
			'link'=>'index.php?p=admin&sp=galaxien',
			'bg'=>'background-image:url(img/layout/csw_admin.png)',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt
		if($_GET['sp'] == 'galaxien') {
			include './pages/admin/galaxien.php';
		}
	}
	
	// Registrierung
	if($user->rechte['verwaltung_user_register']) {
		$csw->data['register'] = array(
			'link'=>'index.php?p=admin&sp=register',
			'bg'=>'background-image:url(img/layout/csw_admin.png);background-position:-150px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt
		if($_GET['sp'] == 'register') {
			include './pages/admin/register.php';
		}
	}
	
	// Berechtigungen
	if($user->rechte['verwaltung_rechte']) {
		$csw->data['rechte'] = array(
			'link'=>'index.php?p=admin&sp=rechte',
			'bg'=>'background-image:url(img/layout/csw_admin.png);background-position:-300px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt
		if($_GET['sp'] == 'rechte') {
			include './pages/admin/rechte.php';
		}
	}
	
	// Logfile
	if($user->rechte['verwaltung_logfile']) {
		$csw->data['logfile'] = array(
			'link'=>'index.php?p=admin&sp=logfile',
			'bg'=>'background-image:url(img/layout/csw_admin.png);background-position:-450px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt
		if($_GET['sp'] == 'logfile') {
			include './pages/admin/logfile.php';
		}
	}
	
	// Einstellungen
	if($user->rechte['verwaltung_settings']) {
		$csw->data['settings'] = array(
			'link'=>'index.php?p=admin&sp=settings',
			'bg'=>'background-image:url(img/layout/csw_admin.png);background-position:-600px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt
		if($_GET['sp'] == 'settings') {
			include './pages/admin/settings.php';
		}
	}
	
	// Backups, Import/Export
	if($user->rechte['verwaltung_backup']) {
		$csw->data['backup'] = array(
			'link'=>'index.php?p=admin&sp=backup',
			'bg'=>'background-image:url(img/layout/csw_admin.png);background-position:-750px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt
		if($_GET['sp'] == 'backup') {
			include './pages/admin/backup.php';
		}
	}
	
	
	// nur Unterseite ausgeben
	if(isset($_GET['switch'])) {
		if(isset($csw->data[$_GET['sp']])) {
			$tmpl->content = $csw->data[$_GET['sp']]['content'];
			$tmpl->output();
		}
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
			$tmpl->output();
		}
	}
	// keine Berechtigung
	else if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
		$tmpl->output();
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
		$tmpl->output();
	}
}
?>