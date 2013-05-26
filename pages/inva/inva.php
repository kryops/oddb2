<?php
/**
 * pages/inva/inva.php
 * Aktionen bei angemeldeten Spielern (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


$content =& $csw->data['inva']['content'];
		
$content = '
<div class="hl2">Aktionen bei angemeldeten Spielern</div>';

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

// Sortierung
$sort = array(
	'ende'=>'(invasionenTyp = 4) ASC, invasionenEnde ASC',
	'id'=>'invasionen_planetenID ASC',
	'typ'=>'invasionenTyp ASC',
	'status'=>'invasionenOpen DESC, (invasionenAbbrecher > 0) DESC, invasionenEnde ASC',
	'gate'=>'systemeGateEntf ASC, invasionenEnde ASC'
);
if(isset($_GET['sort'], $sort[$_GET['sort']])) {
	$sort = $sort[$_GET['sort']];
}
else {
	$sort = $sort['ende'];
}

// Sortierung nach Entfernung
$esort = false;
if(isset($_GET['esort']) AND $_GET['esort'] != '') {
	$esort = flug_point($_GET['esort']);
	
	// Fehler
	if(!is_array($esort)) {
		$esort = false;
	}
	else {
		$conds[] = 'systeme_galaxienID = '.$esort[0];
		$sort = 'planetenEntfernung ASC';
	}
}

$content .= '
<div class="center">
<br /><br />
<form action="#" name="sort_inva" onsubmit="$(this).children(\'.link\').data(\'link\', \'index.php?p=inva&amp;sp=inva&amp;esort=\'+this.start.value).trigger(\'click\');return false">
<a class="link" style="display:none" data-link=""></a>
Aktionen nach Entfernung zu 
&nbsp;<input type="text" class="smalltext tooltip" style="width:80px" name="start" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)"'.(isset($_GET['esort']) ? ' value="'.htmlspecialchars($_GET['esort'], ENT_COMPAT, 'UTF-8').'"' : '').' />
&nbsp;<input type="submit" class="button" value="sortieren" />
&nbsp;<span class="small hint">(Planet, System oder Koordinaten)</span>
</form>
'.((isset($_GET['esort']) AND $_GET['esort'] != '' AND !$esort) ? '<span class="error">Ausgangspunkt ung&uuml;ltig</span>' : '').'
</div>
<br /><br />
<form name="invaroutenform" onsubmit="return false">';

$t = time();
$ids = array();

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
		invasionenAbbrecher,
		invasionenFreundlich,
		invasionenKommentar,
		
		systeme_galaxienID,
		systemeX,
		systemeZ,
		systemeGateEntf,
		
		".($esort ? entf_mysql("systemeX", "systemeY", "systemeZ", "1", $esort[1], $esort[2], $esort[3], $esort[4])." AS planetenEntfernung," : "")."
		
		p1.playerName,
		p1.player_allianzenID,
		p2.playerName AS a_playerName,
		p2.player_allianzenID AS a_player_allianzenID,
		p3.playerName AS abbr_playerName,
		
		a1.allianzenTag,
		a2.allianzenTag AS a_allianzenTag
	FROM
		".PREFIX."invasionen
		LEFT JOIN ".PREFIX."systeme
			ON systemeID = invasionen_systemeID
		LEFT JOIN ".GLOBPREFIX."player p1
			ON p1.playerID = invasionen_playerID
		LEFT JOIN ".GLOBPREFIX."player p2
			ON p2.playerID = invasionenAggressor
		LEFT JOIN ".GLOBPREFIX."player p3
			ON p3.playerID = invasionenAbbrecher
		LEFT JOIN ".GLOBPREFIX."allianzen a1
			ON a1.allianzenID = p1.player_allianzenID
		LEFT JOIN ".GLOBPREFIX."allianzen a2
			ON a2.allianzenID = p2.player_allianzenID
	WHERE
		".implode(' AND ', $conds)."
	ORDER BY
		".$sort."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// keine Aktionen eingetragen
if(!mysql_num_rows($query)) {
	$content .= '
<br />
<div class="center">
Keine Aktionen eingetragen.
</div>
<br /><br /><br />
';
}

// Tabelle anzeigen
else {
	$content2 = '
<table class="data center allyfiltert" style="margin:auto">
<tr>
<th><a class="link" data-link="index.php?p=inva&amp;sort=id">Gala</a></th>
<th><a class="link" data-link="index.php?p=inva&amp;sort=id">System</a></th>
<th><a class="link" data-link="index.php?p=inva&amp;sort=id">Planet</a></th>
<th><a class="link" data-link="index.php?p=inva&amp;sort=gate">Gate <span class="small">(A'.$user->settings['antrieb'].')</span></a></th>
<th><a class="link" data-link="index.php?p=inva&amp;sort=typ">Typ</a></th>
<th>Opfer</th>
<th>Aggressor</th>
<th><a class="link" data-link="index.php?p=inva">Ende</a></th>
<th><a class="link" data-link="index.php?p=inva&amp;sort=status">Status</a></th>
'.($esort ? '<th>Entf <span class="small">(A'.$user->settings['antrieb'].')</span></th>' : '').'
<th>&nbsp;</th>
<th>&nbsp;</th>';
	if($user->rechte['routen']) {
		$content2 .= '
<th>&nbsp;</th>';
	}
	$content2 .= '
</tr>';
	
	$openinvas = 0;
	$allies = array();
	
	while($row = mysql_fetch_assoc($query)) {
		// Allianz der Liste hinzufügen
		if($row['player_allianzenID'] == NULL) {}
		else if(!isset($allies[$row['player_allianzenID']])) {
			$allies[$row['player_allianzenID']] = array($row['allianzenTag'], 1);
		}
		else {
			$allies[$row['player_allianzenID']][1]++;
		}
		
		$content2 .= invarow($row, false, $t);
		
		$ids[] = $row['invasionen_planetenID'];
		
		if($row['invasionenOpen']) {
			$openinvas++;
		}
	}
	
	// Anzeige der offenen Invasionen aktualisieren
	if(isset($_GET['ajax'])) {
		$tmpl->script = 'openinvas_update('.$openinvas.');';
	}
	
	// Allianzen-Auswahl anzeigen
	asort($allies);

	if(count($allies) > 1) {
		$content .= '
			<div class="allyfilter center small2">';
		foreach($allies as $key=>$data) {
			// allianzlos
			if(!$key) $data[0] = '<i>allianzlos</i>';
			// unbekannte Allianz
			else if($data[0] == NULL) $data[0] = '<i>unbekannt</i>';
			else $data[0] = htmlspecialchars($data[0], ENT_COMPAT, 'UTF-8');
			
			$content .= '&nbsp; 
			<span style="white-space:nowrap">
			<input type="checkbox" name="'.$key.'" checked="checked" /> <a name="'.$key.'">'.$data[0].' ('.$data[1].')</a>
			</span>&nbsp; ';
		}
		$content .= '
			</div>
			<br />';
	}
	
	$content .= $content2.'
	</table>';
	
	// hidden-Feld für die Suchnavigation
	$content .= '
		<input type="hidden" id="snav'.$t.'" value="'.implode('-', $ids).'" />';
	
	// Routen-Formular
	if($user->rechte['routen']) {
		$content .= '
<br />
<div style="text-align:right;margin-top:4px">
markieren: 
<a onclick="$(this).parents(\'form\').find(\'tr:visible input\').prop(\'checked\', true);" style="font-style:italic">alle</a> /
<a onclick="$(this).parents(\'form\').find(\'input\').prop(\'checked\', false);" style="font-style:italic">keine</a> 
</div>
<br />
<div style="text-align:right" class="small2">
<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=route_addmarked&amp;ajax\', this.parentNode, false, true)">markierte Planeten zu einer Route / Liste hinzuf&uuml;gen</a>
</div>
</form>';
	}
}

// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich die Invasionsliste anzeigen');
}



?>