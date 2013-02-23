<?php
/**
 * pages/inva/archiv.php
 * Invasionsarchiv
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



$content =& $csw->data['archiv']['content'];

$content = '
	<div class="hl2">Invasionsarchiv</div>
	
	<div class="fcbox center" style="width:600px;line-height:35px">
		<div class="fhl2" style="font-weight:bold;line-height:20px;margin-bottom:5px">Archiv durchsuchen</div>
		<form action="#" onsubmit="return form_sendget(this, \'index.php?p=inva&amp;sp=archiv&amp;s=1\')" class="searchform">
		
		Galaxie <input type="text" class="smalltext" name="g" value="'.(isset($_GET['g']) ? htmlspecialchars($_GET['g'], ENT_COMPAT, 'UTF-8') : '').'" />
		&nbsp; &nbsp;
		Typ 
		<select name="typ" size="1">
			<option value="">alle</option>';
foreach($invatyp as $key=>$val) {
	$content .= '
			<option value="'.$key.'"'.((isset($_GET['typ']) AND $_GET['typ'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
}
$content .= '
		</select>
		<br />
		Opfer <input type="text" class="text" name="o" value="'.(isset($_GET['o']) ? htmlspecialchars($_GET['o'], ENT_COMPAT, 'UTF-8') : '').'" />
		&nbsp; &nbsp;
		Opfer-Allianz <input type="text" class="smalltext" name="oa" value="'.(isset($_GET['oa']) ? htmlspecialchars($_GET['oa'], ENT_COMPAT, 'UTF-8') : '').'" />
		<br />
		Aggressor <input type="text" class="text" name="a" value="'.(isset($_GET['a']) ? htmlspecialchars($_GET['a'], ENT_COMPAT, 'UTF-8') : '').'" />
		&nbsp; &nbsp;
		Aggressor-Allianz <input type="text" class="smalltext" name="aa" value="'.(isset($_GET['aa']) ? htmlspecialchars($_GET['aa'], ENT_COMPAT, 'UTF-8') : '').'" />
		<br />
		<input type="submit" class="button" value="Suche starten" style="margin:10px;width:100px" />
		</form>
	</div>
	<br />';




// Bedingungen aufstellen
$conds = array(
);

if(!$user->rechte['invasionen']) {
	$conds[] = "(archivFremd = 1 OR archivTyp = 5)";
}
if(!$user->rechte['fremdinvakolos']) {
	$conds[] = "archivFremd = 0";
	$conds[] = "archivTyp != 5";
}

if($user->protectedAllies) {
	$conds[] = "(p1.player_allianzenID IS NULL OR p1.player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
	$conds[] = "(p2.player_allianzenID IS NULL OR p2.player_allianzenID NOT IN(".implode(', ', $user->protectedAllies)."))";
}
if($user->protectedGalas) {
	$conds[] = "systeme_galaxienID NOT IN(".implode(', ', $user->protectedGalas).")";
}


// Suchfilter anwenden
if(isset($_GET['s'])) {
	// Galaxie
	if(isset($_GET['g'])) {
		$conds[] = "systeme_galaxienID ".db_multiple($_GET['g']);
	}
	// Typ
	if(isset($_GET['typ'])) {
		$conds[] = "archivTyp = ".(int)$_GET['typ'];
	}
	// Opfer
	if(isset($_GET['o'])) {
		$val = escape(str_replace('*', '%', escape($_GET['o'])));
		if(is_numeric($val)) {
			$conds[] = "(archiv_playerID = ".(int)$val." OR p1.playerName LIKE '".$val."')";
		}
		else if($o = db_multiple($val, true)) {
			$conds[] = "(archiv_playerID ".$o." OR p1.playerName LIKE '".$val."')";
		}
		else {
			$conds[] = "p1.playerName LIKE '".$val."'";
		}
	}
	// Opfer-Ally
	if(isset($_GET['oa'])) {
		$val = escape(str_replace('*', '%', escape($_GET['oa'])));
		if(is_numeric($val)) {
			$conds[] = "(p1.player_allianzenID = ".(int)$val." OR a1.allianzenTag LIKE '".$val."' OR a1.allianzenName LIKE '".$val."')";
		}
		else if($oa = db_multiple($val, true)) {
			$conds[] = "(p1.player_allianzenID ".$oa." OR a1.allianzenTag LIKE '".$val."' OR a1.allianzenName LIKE '".$val."')";
		}
		else {
			$conds[] = "(a1.allianzenTag LIKE '".$val."' OR a1.allianzenName LIKE '".$val."')";
		}
	}
	// Aggressor
	if(isset($_GET['a'])) {
		$val = escape(str_replace('*', '%', escape($_GET['a'])));
		if(is_numeric($val)) {
			$conds[] = "(archivAggressor = ".(int)$val." OR p2.playerName LIKE '".$val."')";
		}
		else if($a = db_multiple($val, true)) {
			$conds[] = "(archivAggressor ".$a." OR p2.playerName LIKE '".$val."')";
		}
		else {
			$conds[] = "p2.playerName LIKE '".$val."'";
		}
	}
	// Aggressor-Ally
	if(isset($_GET['aa'])) {
		$val = escape(str_replace('*', '%', escape($_GET['aa'])));
		if(is_numeric($val)) {
			$conds[] = "(p2.player_allianzenID = ".(int)$val." OR a2.allianzenTag LIKE '".$val."' OR a2.allianzenName LIKE '".$val."')";
		}
		else if($aa = db_multiple($val, true)) {
			$conds[] = "(p2.player_allianzenID ".$aa." OR a2.allianzenTag LIKE '".$val."' OR a2.allianzenName LIKE '".$val."')";
		}
		else {
			$conds[] = "(a2.allianzenTag LIKE '".$val."' OR a2.allianzenName LIKE '".$val."')";
		}
	}
}

// Trefferzahl schon übergeben
if(isset($_GET['tcount']) AND $tcount = (int)$_GET['tcount']) {}
// Trefferzahl abfragen
else if(count($conds)) {
	$query = query("
		SELECT
			COUNT(*) AS Anzahl
		FROM
			".PREFIX."invasionen_archiv
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = archiv_systemeID
			LEFT JOIN ".GLOBPREFIX."player p1
				ON p1.playerID = archiv_playerID
			LEFT JOIN ".GLOBPREFIX."player p2
				ON p2.playerID = archivAggressor
			LEFT JOIN ".GLOBPREFIX."allianzen a1
				ON a1.allianzenID = p1.player_allianzenID
			LEFT JOIN ".GLOBPREFIX."allianzen a2
				ON a2.allianzenID = p2.player_allianzenID
		WHERE
			".implode(' AND ', $conds)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$tcount = mysql_fetch_assoc($query);
	$tcount = $tcount['Anzahl'];
}
// keine Bedingungen -> alle abfragen
else {
	$query = query("
		SELECT
			COUNT(*) AS Anzahl
		FROM
			".PREFIX."invasionen_archiv
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$tcount = mysql_fetch_assoc($query);
	$tcount = $tcount['Anzahl'];
}

$querystring = preg_replace('/&page=(\d+)/', '', $_SERVER['QUERY_STRING']);
// Trefferzahl an die Adresse hängen
if(!isset($_GET['tcount'])) {
	$querystring .= '&tcount='.$tcount;
}

$querystring = htmlspecialchars($querystring, ENT_COMPAT, 'UTF-8');


// keine Treffer
if(!$tcount) {
	$content .= '
	<br />
	<div class="center">
		Keine Eintr&auml;ge gefunden.
	</div>
	<br /><br /><br />
	';
}

// Treffer gefunden
else {
	// Pagebar erzeugen und Offset berechnen
	if(!class_exists('pagebar')) {
		include './common/pagebar.php';
	}
	
	$limit = 100;
	$pagebar = pagebar::generate($tcount, $limit);
	$offset = pagebar::offset($tcount, $limit);
	
	// Pagebar ausgeben
	$content .= $pagebar;
	
	
	// Daten abfragen
	$query = query("
		SELECT
			archivID,
			archiv_planetenID,
			archiv_systemeID,
			archivTyp,
			archiv_playerID,
			archivAggressor,
			archivEnde,
			archivKommentar,
			
			systeme_galaxienID,
			systemeX,
			systemeZ,
			
			p1.playerName,
			p1.player_allianzenID,
			p2.playerName AS a_playerName,
			p2.player_allianzenID AS a_player_allianzenID,
			
			a1.allianzenTag,
			a2.allianzenTag AS a_allianzenTag
		FROM
			".PREFIX."invasionen_archiv
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = archiv_systemeID
			LEFT JOIN ".GLOBPREFIX."player p1
				ON p1.playerID = archiv_playerID
			LEFT JOIN ".GLOBPREFIX."player p2
				ON p2.playerID = archivAggressor
			LEFT JOIN ".GLOBPREFIX."allianzen a1
				ON a1.allianzenID = p1.player_allianzenID
			LEFT JOIN ".GLOBPREFIX."allianzen a2
				ON a2.allianzenID = p2.player_allianzenID
		".(count($conds) ? "WHERE
			".implode(' AND ', $conds) : "")."
		ORDER BY
			archivID DESC
		LIMIT
			".$offset.",".$limit."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$content .= '
	<table class="data center" style="margin:auto">
	<tr>
		<th>Gala</th>
		<th>System</th>
		<th>Planet</th>
		<th>Typ</th>
		<th>Opfer</th>
		<th>Aggressor</th>
		<th>Ende</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
	</tr>';
	
	// Tabellen-Klasse einbinden
	if(!class_exists('datatable')) {
		include './common/datatable.php';
	}
	
	while($row = mysql_fetch_assoc($query)) {
		if($row['player_allianzenID'] == NULL) {
			$row['player_allianzenID'] = -1;
		}
		
		$content .= '
	<tr class="invarow'.$row['archivID'].'" data-ally="'.$row['player_allianzenID'].'">
		<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
		<td>'.datatable::system($row['archiv_systemeID']).'</td>
		<td>'.datatable::planet($row['archiv_planetenID']).'</td>
		<td>'.(isset($invatyp[$row['archivTyp']]) ? $invatyp[$row['archivTyp']] : '-').'</td>
		<td>'.datatable::playerallianz($row['archiv_playerID'], $row['playerName'], $row['player_allianzenID'], $row['allianzenTag']).'</td>
		<td>'.($row['a_playerName'] != NULL ? datatable::playerallianz($row['archivAggressor'], $row['a_playerName'], $row['a_player_allianzenID'], $row['a_allianzenTag']) : '').'</td>
		<td>';
		// Ende
		if($row['archivTyp'] != 4) {
			$content .= $row['archivEnde'] ? datum($row['archivEnde']) : '<i>unbekannt</i>';
		}
		else {
			$content .= '&nbsp;';
		}
		
		$content .= '</td>
		<td class="invakommentar'.$row['archivID'].'">'.(trim($row['archivKommentar']) != '' ? '<div class="kommentar searchicon tooltip" data-tooltip="'.htmlspecialchars(htmlspecialchars($row['archivKommentar'], ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8').'"></div>' : '&nbsp;').'</td>
		<td class="userlistaction"><img src="img/layout/leer.gif" style="background-position:-1000px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=inva&amp;sp=archiv_details&amp;id='.$row['archivID'].'" alt="Details" title="Details" /></td>
	</tr>';
	}
	
	$content .= '
	</table>
	
	<br />'.$pagebar;
}

// Log-Eintrag
if($config['logging'] == 3 AND !isset($_GET['s'], $_GET['page'])) {
	insertlog(5, 'lässt sich das Invasionsarchiv anzeigen');
}


?>