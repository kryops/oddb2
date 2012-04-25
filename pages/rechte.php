<?php
/**
 * pages/rechte.php
 * Anzeige aller Rechtelevel
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Rechtelevel';

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

// Rechteseite anzeigen
else {
	// Rechte bereinigen
	unset($rechtenamen['override_allies']);
	unset($rechtenamen['override_galas']);
	
	$tmpl->content .= '
	<div class="icontent">';
	
	foreach($rechte as $r) {
		$tmpl->content .= '
		<div class="hl2">'.htmlspecialchars($r['name'], ENT_COMPAT, 'UTF-8').'</div>
		<div class="icontent">
		'.htmlspecialchars($r['desc'], ENT_COMPAT, 'UTF-8').'
		<br /><br />
		<table class="tnarrow tsmall" style="width:90%;margin:auto">
		<tr>
			<td style="width:50%;vertical-align:top">
				<b>nutzbare Funktionen</b>:
				<br />
				<div style="padding-left:8px;padding-top:5px" class="green">';
		foreach($rechtenamen as $key=>$val) {
			if($r[$key]) $tmpl->content .= '- '.$val.'<br />';
			// höchstes zu vergebendes Rechtelevel
			if($key == 'verwaltung_user_register' AND ($r['verwaltung_userally'] OR $r['verwaltung_user_register'])) {
				$rc = $r['verwaltung_user_maxlevel'];
				// Rechtelevel nicht vorhanden
				if(!isset($rechte[$rc])) {
					// größtes Rechtelevel
					$lnr = array_keys($rechte);
					sort($lnr);
					$lnr = array_pop($lnr);
					if($rc > $lnr) $rc = $rechte[$lnr]['name'];
					else $rc = 'unbekannt';
				}
				else $rc = $rechte[$rc]['name'];
				
				$tmpl->content .= '- h&ouml;chstes zu vergebendes Rechtelevel: '.htmlspecialchars($rc, ENT_COMPAT, 'UTF-8').'<br />';
			}
		}
		$tmpl->content .= '
				</div>
			</td>
			<td style="vertical-align:top">
				<b>gesperrte Funktionen</b>:
				<br />
				<div style="padding-left:8px;padding-top:5px" class="red">';
		foreach($rechtenamen as $key=>$val) {
			if(!$r[$key]) $tmpl->content .= '- '.$val.'<br />';
		}
		$tmpl->content .= '
				</div>
			</td>
		</tr>
		</table>
		</div>
		<br />';
	}
	
	$tmpl->content .= '
	</div>';
	
	// Log-Eintrag
	if($config['logging'] >= 3) {
		insertlog(5, 'lässt sich die Liste der Rechtelevel anzeigen');
	}
	
	// Ausgabe
	$tmpl->output();
}

?>