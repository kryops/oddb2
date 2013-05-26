<?php
/**
 * pages/inva.php
 * Invasionsübersicht
 * Fremdinvas / Kolos
 * Invasionsarchiv
 * veraltete Planetenübersichten
 * Masseninva-Koordinator
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = 'inva';

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Invasionen';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'inva'=>true,
	'inva_details'=>true,
	'inva_freundlich'=>true,
	'inva_abbruch'=>true,
	'inva_kommentar'=>true,
	'masseninva_add'=>true,
	'masseninva_del'=>true,
	'masseninva_set'=>true,
	'masseninva_unset'=>true,
	
	'fremd'=>true,
	'archiv'=>true,
	'archiv_details'=>true,
	'oview'=>true,
	'masseninva'=>true
);


/**
 * Namen der Aktionstypen
 */
$invatyp = array(
	1=>'Invasion',
	2=>'Resonation',
	3=>'Genesis',
	4=>'Besatzung',
	5=>'Kolonisation'
);

/**
 * Funktionen
 */

/**
 * Invasions-Zeile erzeugen
 * @param $row array Datensatz
 * @param $fremd bool Fremdinvasion/Kolonisation default false
 * @return HTML Tabellenzeile
 */
function invarow($row, $fremd=false, $nav=false) {
	global $invatyp, $user;
	
	if($row['player_allianzenID'] == NULL) {
		$row['player_allianzenID'] = -1;
	}
	
	if($row['invasionenTyp'] == 5) {
		$row['player_allianzenID'] = $row['a_player_allianzenID'];
	}
	
	// Tabellen-Klasse einbinden
	if(!class_exists('datatable')) {
		include './common/datatable.php';
	}
	
	$content = '
	<tr class="invarow'.$row['invasionenID'].'" data-ally="'.$row['player_allianzenID'].'">
		<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
		<td>'.datatable::system($row['invasionen_systemeID']).'</td>
				<td>'.datatable::planet($row['invasionen_planetenID'], false, $nav).'</td>
		<td>'.datatable::gate($row['systemeGateEntf'], $user->settings['antrieb']).'</td>
		<td>'.(isset($invatyp[$row['invasionenTyp']]) ? $invatyp[$row['invasionenTyp']] : '-').'</td>
		<td>'.datatable::playerallianz($row['invasionen_playerID'], $row['playerName'], $row['player_allianzenID'], $row['allianzenTag']).'</td>
		<td>'.($row['a_playerName'] != NULL ? datatable::playerallianz($row['invasionenAggressor'], $row['a_playerName'], $row['a_player_allianzenID'], $row['a_allianzenTag']) : '').'</td>
		<td>';
	// Ende
	if($row['invasionenTyp'] != 4) {
		$content .= $row['invasionenEnde'] ? datum($row['invasionenEnde']) : '<i>unbekannt</i>';
	}
	else {
		$content .= '&nbsp;';
	}
	
	$content .= '</td>';
	
	if(!$fremd) {
		$content .= '
			<td class="invastatus'.$row['invasionenID'].'">';
		// Status
		if($row['invasionenOpen']) {
			$content .= '<span class="red" style="font-weight:bold">offen</span>';
		}
		else if($row['invasionenFreundlich']) {
			$content .= '<span class="green">freundlich</span>';
		}
		else if($row['invasionenAbbrecher']) {
			$content .= '<span style="color:#ffff00">Abbruch durch</span> <a class="link winlink contextmenu" style="color:#ffff00" data-link="index.php?p=show_player&amp;id='.$row['invasionenAbbrecher'].'">'.htmlspecialchars($row['abbr_playerName'], ENT_COMPAT, 'UTF-8').'</a>';
		}
		else {
			$content .= '<i>unbekannt</i>';
		}
		$content .= '</td>';
	}
	// Entfernung
	if(isset($row['planetenEntfernung'])) {
		$content .= '
		<td>'.flugdauer($row['planetenEntfernung'], $user->settings['antrieb']).'</td>';
	}
	$content .= '
		<td class="invakommentar'.$row['invasionenID'].'">'.(trim($row['invasionenKommentar']) != '' ? '<div class="kommentar searchicon tooltip" data-tooltip="'.htmlspecialchars(htmlspecialchars($row['invasionenKommentar'], ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8').'"></div>' : '&nbsp;').'</td>
		<td class="userlistaction"><img src="img/layout/leer.gif" style="background-position:-1000px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=inva&amp;sp=inva_details&amp;id='.$row['invasionenID'].'" alt="Details" title="Details" /></td>';
	if($user->rechte['routen']) {
		$content .= '<td><input type="checkbox" name="'.$row['invasionen_planetenID'].'" /></td>';
	}
	$content .= '
	</tr>';
	
	// zurückgeben
	return $content;
}


/**
 * Datensatz einer Aktion abfragen
 * @param $id int Datensatz-ID
 * @param $extended alle Daten abfragen (inkl. Abbrecher-Name und Allytags)
 */
function invadata($id, $extended) {
	global $user, $tmpl;
	
	// Bedingungen aufstellen
	$conds = array(
		"invasionenID = ".$id
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
			invasionenID,
			invasionen_planetenID,
			invasionen_systemeID,
			invasionenTyp,
			invasionen_playerID,
			invasionenAggressor,
			invasionenEnde,
			invasionenOpen,
			invasionenFremd,
			invasionenAbbrecher,
			invasionenFreundlich,
			invasionenKommentar,
			
			systeme_galaxienID,
			systemeX,
			systemeZ,
			systemeGateEntf,
			
			p1.playerName,
			p1.player_allianzenID,
			p2.playerName AS a_playerName,
			p2.player_allianzenID AS a_player_allianzenID
			
			".($extended ? ",
			p3.playerName AS abbr_playerName,
			
			a1.allianzenTag,
			a2.allianzenTag AS a_allianzenTag" : "")."
		FROM
			".PREFIX."invasionen
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = invasionen_systemeID
			LEFT JOIN ".GLOBPREFIX."player p1
				ON p1.playerID = invasionen_playerID
			LEFT JOIN ".GLOBPREFIX."player p2
				ON p2.playerID = invasionenAggressor
			".($extended ? "
			LEFT JOIN ".GLOBPREFIX."player p3
				ON p3.playerID = invasionenAbbrecher
			LEFT JOIN ".GLOBPREFIX."allianzen a1
				ON a1.allianzenID = p1.player_allianzenID
			LEFT JOIN ".GLOBPREFIX."allianzen a2
				ON a2.allianzenID = p2.player_allianzenID" : "")."
		WHERE
			".implode(' AND ', $conds)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Aktion nicht gefunden
	if(!mysql_num_rows($query)) {
		$tmpl->error = 'Die Aktion wurde nicht gefunden oder du hast keine Berechtigung sie anzuzeigen!';
		$tmpl->output();
		die();
	}
	
	// bei Erfolg Datensatz zurückgeben
	return mysql_fetch_assoc($query);
}



// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}


// Aktionsdetails anzeigen
else if($_GET['sp'] == 'inva_details') {
	include './pages/inva/inva_details.php';
}


// Invasion auf freundlich setzen
else if($_GET['sp'] == 'inva_freundlich') {
	include './pages/inva/inva_actions.php';
}

// Invasion abbrechen / zurückziehen
else if($_GET['sp'] == 'inva_abbruch') {
	include './pages/inva/inva_actions.php';
}

// Kommentar ändern
else if($_GET['sp'] == 'inva_kommentar') {
	include './pages/inva/inva_actions.php';
}

// Aktionsdetails anzeigen (Archiv)
else if($_GET['sp'] == 'archiv_details') {
	include './pages/inva/archiv_details.php';
}

// Masseninva: Allianzen hinzufügen und entfernen
else if($_GET['sp'] == 'masseninva_add' OR $_GET['sp'] == 'masseninva_del') {
	include './pages/inva/masseninva_actions.php';
}

// Masseninva: Planet reservieren und Reservierung aufheben
else if($_GET['sp'] == 'masseninva_set' OR $_GET['sp'] == 'masseninva_unset') {
	include './pages/inva/masseninva_actions.php';
}


/**
 * Seite
 */

else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Aktionen bei angemeldeten Spielern
	if($user->rechte['invasionen']) {
		$csw->data['inva'] = array(
			'link'=>'index.php?p=inva&sp=inva',
			'bg'=>'background-image:url(img/layout/csw_inva.png)',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für die Aktionen bei angemeldeten Spielern
		if($_GET['sp'] == 'inva') {
			include './pages/inva/inva.php';
		}
	}
	
	// Aktionen bei fremden Spielern und Kolonisationen
	if($user->rechte['fremdinvakolos']) {
		$csw->data['fremd'] = array(
			'link'=>'index.php?p=inva&sp=fremd',
			'bg'=>'background-image:url(img/layout/csw_inva.png);background-position:-150px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für Fremdaktionen und Kolonisationen
		if($_GET['sp'] == 'fremd') {
			include './pages/inva/fremd.php';
		}
	}
	
	// Invasionsarchiv
	if($user->rechte['invasionen'] OR $user->rechte['fremdinvakolos']) {
		$csw->data['archiv'] = array(
			'link'=>'index.php?p=inva&sp=archiv',
			'bg'=>'background-image:url(img/layout/csw_inva.png);background-position:-300px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für Invasionsarchiv
		if($_GET['sp'] == 'archiv') {
			include './pages/inva/archiv.php';
		}
	}
	
	// veraltete Planetenübersichten
	if($user->rechte['show_player_db_ally'] OR $user->rechte['show_player_db_meta'] OR $user->rechte['show_player_db_other']) {
		$csw->data['oview'] = array(
			'link'=>'index.php?p=inva&sp=oview',
			'bg'=>'background-image:url(img/layout/csw_inva.png);background-position:-450px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für veraltete Planetenübersichten
		if($_GET['sp'] == 'oview') {
			include './pages/inva/oview.php';
		}
	}
	
	// Masseninva-Koordinator
	if($user->rechte['masseninva']) {
		$csw->data['masseninva'] = array(
			'link'=>'index.php?p=inva&sp=masseninva',
			'bg'=>'background-image:url(img/layout/csw_inva.png);background-position:-600px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für Masseninva-Koordinator
		if($_GET['sp'] == 'masseninva') {
			include './pages/inva/masseninva.php';
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