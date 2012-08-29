<?php

error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



class Search {
	
	/**
	 * Wie soll das Suchformular standardmäßig abgeschickt werden? (onsubmit-Attribut)
	 * @var string
	 */
	const DEFAULT_ACTION = 'return form_sendget(this, \'index.php?p=search&amp;s=1\')';
	
	
	public static $sorto = array(
		1=>'planetenID',
		2=>'playerName',
		3=>'player_allianzenID',
		4=>'planetenGroesse',
		5=>'planetenBevoelkerung',
		6=>'planetenRPErz',
		7=>'planetenRPMetall',
		8=>'planetenRPWolfram',
		9=>'planetenRPKristall',
		10=>'planetenRPFluor',
		11=>'planetenRMErz',
		12=>'planetenRMMetall',
		13=>'planetenRMWolfram',
		14=>'planetenRMKristall',
		15=>'planetenRMFluor',
		16=>'planetenForschung',
		17=>'planetenIndustrie',
		18=>'planetenRWErz',
		20=>'planetenRWWolfram',
		21=>'planetenRWKristall',
		22=>'planetenRWFluor',
		23=>'planetenName',
		24=>'planetenRPGesamt',
		25=>'planetenRMGesamt',
		26=>'planetenGateEntf',
		27=>'planetenNatives',
		28=>'planetenTyp',
		29=>'planetenUpdateOverview',
		30=>'planetenGetoxxt',
		31=>'planetenGeraidet',
		32=>'statusStatus',
		33=>'playerPlaneten',
		34=>'playerImppunkte',
		35=>'playerActivity',
		36=>'systemeUpdate',
		37=>'FLOOR(
				(planetenBevoelkerung/100000) * planetenGroesse
				+ GREATEST(planetenRWErz, planetenRWWolfram, planetenRWKristall, planetenRWFluor)
				+ planetenRWFluor*3
			)'
	);
	
	
	/**
	 * Suchformular generieren und mit Werten füllen
	 * @param array $filter
	 * 		Bereits eingetragene Werte
	 * @param string $additional
	 * 		Content, der ans Ende des Formulars angehängt wird
	 * 		(z.B. Sortierung und Spaltenauswahl)
	 * @param string $action
	 * 		onsubmit-Eintrag des Suchformulars
	 * @param string $button
	 * 		Label des Abschicken-Buttons
	 * @return string HTML
	 */
	public static function createSearchForm($filter, $additional='', $action=self::DEFAULT_ACTION, $button='Suche starten') {
		
		global $user, $rassen, $gebaeude, $status;
		
		
		// Planeten-Typen
		$pltypen = 62;
		$pltypnot = array(46,48);
		
		// Planetentyp validieren
		if(isset($filter['t']) AND ((int)$filter['t'] < 1 OR (int)$filter['t'] > $pltypen OR in_array((int)$filter['t'], $pltypnot))) {
			unset($filter['t']);
		}
		
		// Gebäude-Filter
		$searchgeb = self::getGebaeudeFilter($filter);
		
		
		$content = '
<form action="#" onsubmit="'.$action.'" class="searchform">
<table>
<tr>
	<td style="width:80px;font-weight:bold">
		System
	</td>
	<td style="vertical-align:top">
		Galaxie <input type="text" class="smalltext" style="width:40px" name="g" value="'.(isset($filter['g']) ? htmlspecialchars($filter['g'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Sektor 
		<select name="sek" size="1">
			<option value="">alle</option>
			<option value="1"'.((isset($filter['sek']) AND $filter['sek'] == 1) ? ' selected="selected"' : '').'>rot</option>
			<option value="2"'.((isset($filter['sek']) AND $filter['sek'] == 2) ? ' selected="selected"' : '').'>gr&uuml;n</option>
			<option value="3"'.((isset($filter['sek']) AND $filter['sek'] == 3) ? ' selected="selected"' : '').'>blau</option>
			<option value="4"'.((isset($filter['sek']) AND $filter['sek'] == 4) ? ' selected="selected"' : '').'>gelb</option>
		</select> &nbsp; &nbsp;
		System-ID <input type="text" class="smalltext" name="sid" value="'.(isset($filter['sid']) ? htmlspecialchars($filter['sid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		System-Name <input type="text" class="text" style="width:80px" name="sn" value="'.(isset($filter['sn']) ? htmlspecialchars($filter['sn'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		<span style="white-space:nowrap">Scan 
		<select name="ssc" size="1" onchange="if(this.value>=3){$(this).siblings(\'.ssct\').show().select();}else{$(this).siblings(\'.ssct\').hide();}">
			<option value="">egal</option>
			<option value="1"'.((isset($filter['ssc']) AND $filter['ssc'] == 1) ? ' selected="selected"' : '').'>nicht gescannt</option>
			<option value="2"'.((isset($filter['ssc']) AND $filter['ssc'] == 2) ? ' selected="selected"' : '').'>irgendwann</option>
			<option value="3"'.((isset($filter['ssc']) AND $filter['ssc'] == 3) ? ' selected="selected"' : '').'>neuer als (Tage)</option>
			<option value="4"'.((isset($filter['ssc']) AND $filter['ssc'] == 4) ? ' selected="selected"' : '').'>&auml;lter als (Tage)</option>
		</select> 
		<input type="text" class="smalltext ssct" name="ssct" value="'.(isset($filter['ssct']) ? htmlspecialchars($filter['ssct'], ENT_COMPAT, 'UTF-8') : '').'" style="'.((!isset($filter['ssc']) OR $filter['ssc'] < 3) ? 'display:none;' : '').'width:40px" /></span>
	</td>
</tr>
</table>
<hr />
<table>
<tr>
	<td style="width:80px;font-weight:bold">
		Planet
	</td>
	<td style="vertical-align:top">
		Planeten-ID <input type="text" class="smalltext" name="pid" value="'.(isset($filter['pid']) ? htmlspecialchars($filter['pid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Planeten-Name <input type="text" class="text" name="pn" value="'.(isset($filter['pn']) ? htmlspecialchars($filter['pn'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		<input type="checkbox" name="pon"'.(isset($filter['pon']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="pon">Original-Name</span> &nbsp; &nbsp;
		Gr&ouml;&szlig;e 
		<select name="gr_" size="1">
			<option value="">=</option>
			<option value="1"'.((isset($filter['gr_']) AND $filter['gr_'] == 1) ? ' selected="selected"' : '').'>&gt;</option>
			<option value="2"'.((isset($filter['gr_']) AND $filter['gr_'] == 2) ? ' selected="selected"' : '').'>&lt;</option>
		</select> 
		<input type="text" class="smalltext" name="gr" value="'.(isset($filter['gr']) ? htmlspecialchars($filter['gr'], ENT_COMPAT, 'UTF-8') : '').'" />
		<br />
		<span style="cursor:pointer" onclick="$(this).siblings(\'.searchpltyplist\').slideToggle(250)">Typ</span> &nbsp;
		<input type="hidden" name="t" value="'.(isset($filter['t']) ? h($filter['t']) : '').'" />
		<span class="searchpltyp" onclick="$(this).siblings(\'.searchpltyplist\').slideToggle(250)">'.(isset($filter['t']) ? '<img src="img/planeten/'.(int)$filter['t'].'.jpg" alt="" />' : '<i>alle</i>').'</span> &nbsp; &nbsp;
		Scan 
		<select name="sc" size="1" onchange="if(this.value>=3){$(this).siblings(\'.sct\').show().select();}else{$(this).siblings(\'.sct\').hide();}">
			<option value="">egal</option>
			<option value="1"'.((isset($filter['sc']) AND $filter['sc'] == 1) ? ' selected="selected"' : '').'>nicht gescannt</option>
			<option value="2"'.((isset($filter['sc']) AND $filter['sc'] == 2) ? ' selected="selected"' : '').'>irgendwann</option>
			<option value="3"'.((isset($filter['sc']) AND $filter['sc'] == 3) ? ' selected="selected"' : '').'>neuer als (Tage)</option>
			<option value="4"'.((isset($filter['sc']) AND $filter['sc'] == 4) ? ' selected="selected"' : '').'>&auml;lter als (Tage)</option>
		</select> 
		<input type="text" class="smalltext sct" name="sct" value="'.(isset($filter['sct']) ? htmlspecialchars($filter['sct'], ENT_COMPAT, 'UTF-8') : '').'" style="'.((!isset($filter['sc']) OR $filter['sc'] < 3) ? 'display:none;' : '').'width:40px" /> &nbsp; &nbsp;
		<input type="checkbox" name="usc"'.(isset($filter['usc']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="usc">unscannbar</span> &nbsp; &nbsp;
		kategorisiert 
		<select name="k">
			<option value=""></option>
			<option value="0"'.((isset($filter['k']) AND $filter['k'] == 0) ? ' selected="selected"' : '').'>nicht kategorisiert</option>
			<option value="13"'.((isset($filter['k']) AND $filter['k'] == 13) ? ' selected="selected"' : '').'>Werft</option>
			<option value="15"'.((isset($filter['k']) AND $filter['k'] == 15) ? ' selected="selected"' : '').'>- Ressplanis und Werften -</option>
			<option value="14"'.((isset($filter['k']) AND $filter['k'] == 14) ? ' selected="selected"' : '').'>- alle Ressplaneten -</option>
			<option value="1"'.((isset($filter['k']) AND $filter['k'] == 1) ? ' selected="selected"' : '').'>Erz</option>
			<option value="2"'.((isset($filter['k']) AND $filter['k'] == 2) ? ' selected="selected"' : '').'>Metall</option>
			<option value="3"'.((isset($filter['k']) AND $filter['k'] == 3) ? ' selected="selected"' : '').'>Wolfram</option>
			<option value="4"'.((isset($filter['k']) AND $filter['k'] == 4) ? ' selected="selected"' : '').'>Kristall</option>
			<option value="5"'.((isset($filter['k']) AND $filter['k'] == 5) ? ' selected="selected"' : '').'>Fluor</option>
			<option value="12"'.((isset($filter['k']) AND $filter['k'] == 12) ? ' selected="selected"' : '').'>Umsatzfabriken</option>
			<option value="16"'.((isset($filter['k']) AND $filter['k'] == 16) ? ' selected="selected"' : '').'>- alle  Forschungsplaneten -</option>
			<option value="6"'.((isset($filter['k']) AND $filter['k'] == 6) ? ' selected="selected"' : '').'>Forschungseinrichtungen</option>
			<option value="7"'.((isset($filter['k']) AND $filter['k'] == 7) ? ' selected="selected"' : '').'>UNI-Labore</option>
			<option value="8"'.((isset($filter['k']) AND $filter['k'] == 8) ? ' selected="selected"' : '').'>Forschungszentren</option>
			<option value="9"'.((isset($filter['k']) AND $filter['k'] == 9) ? ' selected="selected"' : '').'>Myriforschung</option>
			<option value="10"'.((isset($filter['k']) AND $filter['k'] == 10) ? ' selected="selected"' : '').'>orbitale Forschung</option>
			<option value="11"'.((isset($filter['k']) AND $filter['k'] == 11) ? ' selected="selected"' : '').'>Gedankenkonzentratoren</option>
		</select>
		<br />
		<div class="searchpltyplist fcbox" style="display:none">';
		
		// Planetentypen ausgeben
		for($i=1; $i <= 62; $i++) {
			if(!in_array($i, $pltypnot)) {
				$content .= '<img src="img/planeten/'.$i.'.jpg" alt="" /> ';
			}
		}
		
		$content .= ' <a onclick="$(this).parents(\'form\').find(\'input[name=t]\').val(\'\').siblings(\'.searchpltyp\').html(\'&lt;i&gt;alle&lt;/i&gt;\');$(this.parentNode).slideUp(250);" style="font-style:italic"> [alle]</a>
		</div>
		
		<span style="cursor:pointer" onclick="$(this).siblings(\'.searchgeblist\').slideToggle(250)">Geb&auml;ude-Filter</span> &nbsp;
		<input type="hidden" name="geb" value="'.(isset($filter['geb']) ? h($filter['geb']) : '').'" />
		<span class="searchgeb" onclick="$(this).siblings(\'.searchgeblist\').slideToggle(250)">';
		
		if(!count($searchgeb)) {
			$content .= '<i>alle</i>';
		}
		else {
			// ausgewählte Gebäude anzeigen
			foreach($searchgeb as $geb) {
				$content .= '<img src="img/gebaeude/'.$gebaeude[$geb].'" alt="" /> ';
			}
		}
		
		$content .= '</span> &nbsp; &nbsp;
		<input type="checkbox" name="mg"'.(isset($filter['mg']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="mg">Myrigate</span> &nbsp; &nbsp;';
		
		$content .= '
		Orbiter 
		<select name="o" size="1">
			<option value="">egal</option>
			<option value="1"'.((isset($filter['o']) AND $filter['o'] == 1) ? ' selected="selected"' : '').'>keine</option>
			<option value="6"'.((isset($filter['o']) AND $filter['o'] == 6) ? ' selected="selected"' : '').'>ja</option>
			<option value="2"'.((isset($filter['o']) AND $filter['o'] == 2) ? ' selected="selected"' : '').'>max. Stufe 1</option>
			<option value="3"'.((isset($filter['o']) AND $filter['o'] == 3) ? ' selected="selected"' : '').'>max. Stufe 2</option>
			<option value="4"'.((isset($filter['o']) AND $filter['o'] == 4) ? ' selected="selected"' : '').'>mind. Stufe 2</option>
			<option value="5"'.((isset($filter['o']) AND $filter['o'] == 5) ? ' selected="selected"' : '').'>mind. Stufe 3</option>
		</select>
		&nbsp; &nbsp;
		Natives 
		<select name="na_" size="1">
			<option value="">=</option>
			<option value="1"'.((isset($filter['na_']) AND $filter['na_'] == 1) ? ' selected="selected"' : '').'>&gt;</option>
			<option value="2"'.((isset($filter['na_']) AND $filter['na_'] == 2) ? ' selected="selected"' : '').'>&lt;</option>
		</select> 
		<input type="text" class="smalltext" name="na" value="'.(isset($filter['na']) ? htmlspecialchars($filter['na'], ENT_COMPAT, 'UTF-8') : '').'" />
		<br />
		
		<div class="searchgeblist fcbox" style="display:none">';
		
		foreach($gebaeude as $key=>$val) {
			if($key > 0) {
				$content .= '<img src="img/gebaeude/'.$val.'" alt="" data-id="'.$key.'"'.(in_array($key, $searchgeb) ? ' class="active"' : '').' /> ';
			}
		}
		
		$content .= ' <a onclick="$(this).parents(\'form\').find(\'input[name=geb]\').val(\'\').siblings(\'.searchgeb\').html(\'&lt;i&gt;alle&lt;/i&gt;\');$(this).siblings(\'.active\').removeClass(\'active\');" style="font-style:italic"> [alle]</a>
		</div>
		
		Bev&ouml;lkerung <input type="text" class="text" style="width:80px" name="bev" value="'.(isset($filter['bev']) ? htmlspecialchars($filter['bev'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Forschung <input type="text" class="text" style="width:80px" name="f" value="'.(isset($filter['f']) ? htmlspecialchars($filter['f'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Industrie <input type="text" class="text" style="width:80px" name="i" value="'.(isset($filter['i']) ? htmlspecialchars($filter['i'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Punkte <input type="text" class="text" style="width:80px" name="pu" value="'.(isset($filter['pu']) ? htmlspecialchars($filter['pu'], ENT_COMPAT, 'UTF-8') : '').'" />
		<br />
		eingetragen als &nbsp;
		<input type="checkbox" name="rpl"'.(isset($filter['rpl']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="rpl">Ressplanet</span> &nbsp; &nbsp;
		<input type="checkbox" name="we"'.(isset($filter['we']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="we">Werft</span> &nbsp; &nbsp;
		<input type="checkbox" name="bu"'.(isset($filter['bu']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="bu">Bunker</span> 
		&nbsp; &nbsp; 
		Bergbau / Terraformer
		<select name="bb" size="1">
			<option value="">egal</option>
			<option value="1"'.((isset($filter['bb']) AND $filter['bb'] == 1) ? ' selected="selected"' : '').'>eins von beiden</option>
			<option value="2"'.((isset($filter['bb']) AND $filter['bb'] == 2) ? ' selected="selected"' : '').'>keins von beiden</option>
			<option value="3"'.((isset($filter['bb']) AND $filter['bb'] == 3) ? ' selected="selected"' : '').'>Bergbau</option>
			<option value="4"'.((isset($filter['bb']) AND $filter['bb'] == 4) ? ' selected="selected"' : '').'>Terraformer</option>
		</select>
		
		<br />
		<table class="searchress">
		<tr class="searchresstr">
			<td></td>
			<td><div class="ress erz"></div></td>
			<td><div class="ress metall"></div></td>
			<td><div class="ress wolfram"></div></td>
			<td><div class="ress kristall"></div></td>
			<td><div class="ress fluor"></div></td>
			<td rowspan="4" style="width:260px;padding-left:25px">
				Summe der Resswerte <input type="text" class="smalltext" name="rw" value="'.(isset($filter['rw']) ? htmlspecialchars($filter['rw'], ENT_COMPAT, 'UTF-8') : '').'" />
				<br />
				gesamte Ressproduktion <input type="text" class="smalltext" name="rp" value="'.(isset($filter['rp']) ? htmlspecialchars($filter['rp'], ENT_COMPAT, 'UTF-8') : '').'" />
				<br />
				gesamter Ressvorrat <input type="text" class="smalltext" name="rv" value="'.(isset($filter['rv']) ? htmlspecialchars($filter['rv'], ENT_COMPAT, 'UTF-8') : '').'" />
			</td>
		</tr>
		<tr>
			<td>Werte</td>
			<td><input type="text" class="smalltext" name="rwe" value="'.(isset($filter['rwe']) ? htmlspecialchars($filter['rwe'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td class="small hint center">(= Erz-Wert)</td>
			<td><input type="text" class="smalltext" name="rww" value="'.(isset($filter['rww']) ? htmlspecialchars($filter['rww'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rwk" value="'.(isset($filter['rwk']) ? htmlspecialchars($filter['rwk'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rwf" value="'.(isset($filter['rwf']) ? htmlspecialchars($filter['rwf'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
		</tr>
		<tr>
			<td>Produktion</td>
			<td><input type="text" class="smalltext" name="rpe" value="'.(isset($filter['rpe']) ? htmlspecialchars($filter['rpe'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rpm" value="'.(isset($filter['rpm']) ? htmlspecialchars($filter['rpm'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rpw" value="'.(isset($filter['rpw']) ? htmlspecialchars($filter['rpw'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rpk" value="'.(isset($filter['rpk']) ? htmlspecialchars($filter['rpk'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rpf" value="'.(isset($filter['rpf']) ? htmlspecialchars($filter['rpf'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
		</tr>
		<tr>
			<td>Vorrat</td>
			<td><input type="text" class="smalltext" name="rve" value="'.(isset($filter['rve']) ? htmlspecialchars($filter['rve'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rvm" value="'.(isset($filter['rvm']) ? htmlspecialchars($filter['rvm'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rvw" value="'.(isset($filter['rvw']) ? htmlspecialchars($filter['rvw'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rvk" value="'.(isset($filter['rvk']) ? htmlspecialchars($filter['rvk'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
			<td><input type="text" class="smalltext" name="rvf" value="'.(isset($filter['rvf']) ? htmlspecialchars($filter['rvf'], ENT_COMPAT, 'UTF-8') : '').'" /></td>
		</tr>
		</table>
		'.($user->rechte['toxxraid'] ? '
		geraidet (Tage) <input type="text" class="smalltext" name="rai" value="'.(isset($filter['rai']) ? htmlspecialchars($filter['rai'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		getoxxt <select name="tox" size="1">
			<option value="">egal</option>
			<option value="0"'.((isset($filter['tox']) AND $filter['tox'] == 0) ? ' selected="selected"' : '').'>nein</option>
			<option value="1"'.((isset($filter['tox']) AND $filter['tox'] == 1) ? ' selected="selected"' : '').'>ja</option>
		</select> &nbsp; &nbsp; ' : '').'
		Kommentar enth&auml;lt <input type="text" class="text" style="width:160px" name="ko" value="'.(isset($filter['ko']) ? htmlspecialchars($filter['ko'], ENT_COMPAT, 'UTF-8') : '').'" />
	</td>
</tr>
</table>
<hr />
<table>
<tr>
	<td style="width:80px;font-weight:bold">
		Inhaber
	</td>
	<td style="vertical-align:top">
		User-ID <input type="text" class="text" style="width:80px" name="uid" value="'.(isset($filter['uid']) ? htmlspecialchars($filter['uid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Username <input type="text" class="text" name="un" value="'.(isset($filter['un']) ? htmlspecialchars($filter['un'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Rasse <select name="ra" size="1">
			<option value="">egal</option>';
		
		foreach($rassen as $key=>$val) {
			$content .= '
			<option value="'.$key.'"'.((isset($filter['ra']) AND $filter['ra'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
		}
		
		$content .= '
		</select> &nbsp; &nbsp;
		frei 
		<select name="fr" size="1">
			<option value="">egal</option>
			<option value="1"'.((isset($filter['fr']) AND $filter['fr'] == 1) ? ' selected="selected"' : '').'>ja</option>
			<option value="2"'.((isset($filter['fr']) AND $filter['fr'] == 2) ? ' selected="selected"' : '').'>nein</option>
		</select>
		&nbsp; &nbsp;
		<input type="checkbox" name="kbar" value="1"'.(isset($filter['kbar']) ? ' checked="checked"' : '').' /> <span class="togglecheckbox" data-name="kbar">kolonisierbar</span>
		<br />
		Urlaubsmodus 
		<select name="umod" size="1">
			<option value="">egal</option>
			<option value="1"'.((isset($filter['umod']) AND $filter['umod'] == 1) ? ' selected="selected"' : '').'>ja</option>
			<option value="2"'.((isset($filter['umod']) AND $filter['umod'] == 2) ? ' selected="selected"' : '').'>nein</option>
		</select> &nbsp; &nbsp;
		Planeten
		<select name="pl_" size="1">
			<option value="">h&ouml;chstens</option>
			<option value="1"'.((isset($filter['pl_']) AND $filter['pl_'] == 1) ? ' selected="selected"' : '').'>mindestens</option>
		</select> 
		<input type="text" class="smalltext" name="pl" value="'.(isset($filter['pl']) ? htmlspecialchars($filter['pl'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		inaktiv seit mindestens
		&nbsp;<input type="text" class="smalltext" name="ina" value="'.(isset($filter['ina']) ? htmlspecialchars($filter['ina'], ENT_COMPAT, 'UTF-8') : '').'" />&nbsp;
		Tagen
		<br />
		Allianz-ID <input type="text" class="text" style="width:80px" name="aid" value="'.(isset($filter['aid']) ? htmlspecialchars($filter['aid'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Allianz-Tag <input type="text" class="text" style="width:80px" name="at" value="'.(isset($filter['at']) ? htmlspecialchars($filter['at'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		Allianz-Name <input type="text" class="text" name="an" value="'.(isset($filter['an']) ? htmlspecialchars($filter['an'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp; &nbsp;
		<span class="as_simple"'.(isset($filter['as2']) ? ' style="display:none"' : '').'>
		Status
		<select name="as" size="1">
			<option value="">egal</option>
			<option value="-1"'.((isset($filter['as']) AND $filter['as'] == -1) ? ' selected="selected"' : '').'>- Freunde -</option>
			<option value="-2"'.((isset($filter['as']) AND $filter['as'] == -2) ? ' selected="selected"' : '').'>- Feinde -</option>';
		
		// Allianz-Status-Select erzeugen
		foreach($status as $key=>$val) {
			$content .= '
			<option value="'.$key.'"'.((isset($filter['as']) AND $filter['as'] == $key) ? ' selected="selected"' : '').'>'.$val.'</option>';
		}
		
		$content .= '
		</select>
		&nbsp; <a class="small2" style="font-style:italic" onclick="$(this.parentNode).siblings(\'.as_advanced\').show().find(\'input\').val(\'1\');$(this.parentNode).hide().find(\'select\').val(\'\');">(erweitert)</a>
		</span>
		<br />
		<div class="as_advanced" '.(!isset($filter['as2']) ? ' style="display:none"' : '').'>
		Status &nbsp;
		<span class="small2">';
		
		// Allianz-Status-Checkboxen erzeugen
		foreach($status as $key=>$val) {
			$checked = true;
			if(isset($filter['as2']) AND !isset($filter['as2'][$key])) {
				$checked = false;
			}
			else if(isset($filter['as'])) {
				if($filter['as'] == -1) {
					if(!in_array($key, $status_freund)) $checked = false;
				}
				else if($filter['as'] == -2) {
					if(!in_array($key, $status_feind))  $checked = false;
				}
				else if($key != $filter['as']) {
					$checked = false;
				}
			}
			
			$content .= '
			<input type="checkbox" name="as2['.$key.']" value="'.(isset($filter['as2']) ? '1' : '').'"'.($checked ? ' checked="checked"' : '').'> <span class="togglecheckbox" data-name="as2['.$key.']">'.$val.'</span> &nbsp;';
		}
		
		$content .= '
		&nbsp; <a class="small2" style="font-style:italic" onclick="$(this.parentNode.parentNode).siblings(\'.as_simple\').show();$(this.parentNode.parentNode).hide().find(\'input\').val(\'\');">(einfach)</a>
		</span>
		</div>
	</td>
</tr>
</table>
<hr />
<table>
<tr>
	<td style="width:80px;font-weight:bold">
		History
	</td>
	<td style="vertical-align:top">
		der Planet hat einmal &nbsp;
		<select name="his_" size="1">
			<option value="">Username</option>
			<option value="1"'.((isset($filter['his_']) AND $filter['his_'] == 1) ? ' selected="selected"' : '').'>User-ID</option>
		</select> 
		<input type="text" class="text" name="his" value="'.(isset($filter['his']) ? htmlspecialchars($filter['his'], ENT_COMPAT, 'UTF-8') : '').'" /> &nbsp;
		geh&ouml;rt
	</td>
</tr>
</table>

'.$additional.'

<br /><br />
<div class="center">
	<input type="submit" class="button" style="width:120px" value="'.$button.'" />
</div>
</form>';
		
		
		// Suchformular zurückgeben
		return $content;
		
	}
	
	
	
	
	
	/**
	 * MySQL-Bedingungen für die Suche anhand eines Filter-Arrays aufstellen
	 * @param array $filter
	 * @return array MySQL-Bedingungen
	 */
	public static function buildConditions($filter) {
		
		
		global $user;
		
		
		// heutigen Timestamp ermitteln
		$heute = strtotime('today');
		
		// Gebäude-Filter
		$searchgeb = self::getGebaeudeFilter($filter);
		
		
		
		// Bedingungen aufstellen
		$conds = array();
		
		// Einschränkungen und Sperrungen der Rechte
		if($user->protectedAllies) {
			$conds[] = '(player_allianzenID IS NULL OR player_allianzenID NOT IN ('.implode(', ', $user->protectedAllies).'))';
		}
		if($user->protectedGalas) {
			$conds[] = 'systeme_galaxienID NOT IN ('.implode(', ', $user->protectedGalas).')';
		}
		if(!$user->rechte['search_ally'] AND $user->allianz) {
			$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID IS NULL OR player_allianzenID != '.$user->allianz.')';
		}
		if(!$user->rechte['search_meta'] AND $user->allianz) {
			$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
		}
		if(!$user->rechte['search_register']) {
			$conds[] = '(statusStatus = '.$status_meta.' OR allianzenID IS NULL OR register_allianzenID IS NULL)';
		}
		
		// eingegebene Bedingungen
		
		// Galaxie
		if(isset($filter['g'])) {
			$conds[] = 'systeme_galaxienID'.db_multiple($filter['g']);
		}
		
		// Sektor
		if(isset($filter['sek'])) {
			if($filter['sek'] == 1) $conds[] = 'systemeX > 0 AND systemeZ > 0';
			else if($filter['sek'] == 2) $conds[] = 'systemeX < 0 AND systemeZ > 0';
			else if($filter['sek'] == 3) $conds[] = 'systemeX < 0 AND systemeZ < 0';
			else $conds[] = 'systemeX > 0 AND systemeZ < 0';
		}
		// System-ID
		if(isset($filter['sid'])) {
			$conds[] = 'planeten_systemeID'.db_multiple($filter['sid']);
		}
		// System-Name
		if(isset($filter['sn'])) {
			$conds[] = "systemeName LIKE '".escape(escape(str_replace('*', '%', $filter['sn'])))."'";
		}
		// System-Scan
		if(isset($filter['ssc'])) {
			if(isset($filter['ssct'])) {
				$filter['ssct'] = (int)$filter['ssct'];
			}
			else $filter['ssct'] = 0;
			
			// nicht gescannt
			if($filter['ssc'] == 1) {
				$conds[] = 'systemeUpdate = 0';
			}
			// irgendwann
			else if($filter['ssc'] == 2) {
				$conds[] = 'systemeUpdate != 0';
			}
			// älter / neuer als
			else if($filter['ssct'] > 0) {
				$conds[] = 'systemeUpdate '.(($filter['ssc'] == 3) ? '>' : '<').' '.(time()-$filter['ssct']*86400);
			}
		}
		
		// Planeten-ID
		if(isset($filter['pid'])) {
			$conds[] = 'planetenID'.db_multiple($filter['pid']);
		}
		// Planeten-Name
		if(isset($filter['pn'])) {
			$conds[] = "planetenName LIKE '".escape(escape(str_replace('*', '%', $filter['pn'])))."'";
		}
		// Original-Name
		if(isset($filter['pon'])) {
			$conds[] = "planetenName = CONCAT('P',systeme_galaxienID,'_',planeten_systemeID,planetenPosition)";
		}
		// Größe
		if(isset($filter['gr'])) {
			$val = '=';
			if(isset($filter['gr_'])) {
				if($filter['gr_'] == 1) $val = '>';
				else $val = '<';
			}
			$filter['gr'] = (int)$filter['gr'];
			$conds[] = 'planetenGroesse '.$val.' '.$filter['gr'];
		}
		// Planeten-Typ
		if(isset($filter['t'])) {
			$filter['t'] = (int)$filter['t'];
			$conds[] = 'planetenTyp = '.$filter['t'];
		}
		// Planeten-Scan (Oberfläche)
		if(isset($filter['sc'])) {
			if(isset($filter['sct'])) {
				$filter['sct'] = (int)$filter['sct'];
			}
			else $filter['sct'] = 0;
			
			// nicht gescannt
			if($filter['sc'] == 1) {
				$conds[] = 'planetenUpdateOverview = 0';
			}
			// irgendwann
			else if($filter['sc'] == 2) {
				$conds[] = 'planetenUpdateOverview != 0';
			}
			// älter / neuer als
			else if($filter['sct'] > 0) {
				$conds[] = 'planetenUpdateOverview '.(($filter['sc'] == 3) ? '>' : '<').' '.(time()-$filter['sct']*86400);
			}
		}
		// unscannbar
		if(isset($filter['usc'])) {
			$conds[] = 'planetenUnscannbar > planetenUpdateOverview';
		}
		// Kategorie
		if(isset($filter['k'])) {
			$filter['k'] = (int)$filter['k'];
			// normale Kategorie
			if($filter['k'] >= 0 AND $filter['k'] <= 13) {
				$conds[] = 'planetenKategorie = '.$filter['k'];
			}
			// Sammelkategorien
			// alle Ressplaneten
			else if($filter['k'] == 14) {
				$conds[] = 'planetenKategorie IN(1,2,3,4,5,12)';
			}
			// Ressplaneten und Werften
			else if($filter['k'] == 15) {
				$conds[] = 'planetenKategorie IN(1,2,3,4,5,12,13)';
			}
			// alle Forschungsplaneten
			else {
				$conds[] = 'planetenKategorie >= 6 AND planetenKategorie <= 11';
			}
		}
		// Gebäude
		if(count($searchgeb)) {
			foreach($searchgeb as $geb) {
				$conds[] = "(planetenGebPlanet LIKE '%".$geb."%' OR planetenGebOrbit LIKE '%".$geb."%')";
			}
		}
		// Myrigate
		if(isset($filter['mg'])) {
			$conds[] = 'planetenMyrigate > 0';
			// Berechtigungs-Einschränkungen
			if(!$user->rechte['show_myrigates_ally'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
			}
			if(!$user->rechte['show_myrigates_meta'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
			}
			if(!$user->rechte['show_myrigates_register']) {
				$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
			}
		}
		
		// Orbiter
		if(isset($filter['o'])) {
			if($filter['o'] == 1) {
				$conds[] = 'planetenOrbiter = 0 AND planetenUpdateOverview > 0';
			}
			else if($filter['o'] == 2) {
				$conds[] = 'planetenOrbiter <= 1 AND planetenUpdateOverview > 0';
			}
			else if($filter['o'] == 3) {
				$conds[] = 'planetenOrbiter <= 2 AND planetenUpdateOverview > 0';
			}
			else if($filter['o'] == 4) {
				$conds[] = 'planetenOrbiter >= 2';
			}
			else if($filter['o'] == 5) {
				$conds[] = 'planetenOrbiter >= 3';
			}
			else {
				$conds[] = 'planetenOrbiter >= 1';
			}
		}
		// Natives
		if(isset($filter['na'])) {
			$val = '=';
			if(isset($filter['na_'])) {
				if($filter['na_'] == 1) $val = '>';
				else $val = '<';
			}
			$filter['na'] = (int)$filter['na'];
			$conds[] = 'planetenNatives '.$val.' '.$filter['na'];
			if($filter['na']) {
				// nur freie Planeten -> Performance
				$conds[] = 'planeten_playerID = 0';
				// Planeten mit 0 Natives ausblenden
				$conds[] = 'planetenNatives > 0';
			}
		}
		// Bevölkerung
		if(isset($filter['bev'])) {
			$filter['bev'] = (int)$filter['bev'];
			$conds[] = 'planetenBevoelkerung >= '.$filter['bev'];
		}
		// Forschung
		if(isset($filter['f'])) {
			$filter['f'] = (int)$filter['f'];
			$conds[] = 'planetenForschung >= '.$filter['f'];
		}
		// Industrie
		if(isset($filter['i'])) {
			$filter['i'] = (int)$filter['i'];
			$conds[] = 'planetenIndustrie >= '.$filter['i'];
		}
		// Punkte
		if(isset($filter['pu'])) {
			$filter['pu'] = (int)$filter['pu'];
			$conds[] = imppunkte_mysql().' >= '.$filter['pu'];
		}
		// Ressplanet
		if(isset($filter['rpl'])) {
			$conds[] = 'planetenRessplani = 1';
			// Berechtigungs-Einschränkungen
			if(!$user->rechte['ressplani_ally'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
			}
			if(!$user->rechte['ressplani_meta'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
			}
			if(!$user->rechte['ressplani_register']) {
				$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
			}
		}
		// Werft
		if(isset($filter['we'])) {
			$conds[] = 'planetenWerft = 1';
			// Berechtigungs-Einschränkungen
			if(!$user->rechte['werft_ally'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
			}
			if(!$user->rechte['werft_meta'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
			}
			if(!$user->rechte['werft_register']) {
				$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
			}
		}
		// Bunker
		if(isset($filter['bu'])) {
			$conds[] = 'planetenBunker = 1';
			// Berechtigungs-Einschränkungen
			if(!$user->rechte['bunker_ally'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR player_allianzenID != '.$user->allianz.')';
			}
			if(!$user->rechte['bunker_meta'] AND $user->allianz) {
				$conds[] = '(planeten_playerID = '.$user->id.' OR statusStatus IS NULL OR statusStatus != '.$status_meta.')';
			}
			if(!$user->rechte['bunker_register']) {
				$conds[] = '(register_allianzenID IS NULL OR statusStatus = '.$status_meta.')';
			}
		}
		// Bergbau
		if(isset($filter['bb']) AND $user->rechte['fremdinvakolos']) {
			// BBS oder TF
			if($filter['bb'] == 1) {
				$conds[] = "(schiffeBergbau IS NOT NULL OR schiffeTerraformer IS NOT NULL)";
			}
			// keins
			else if($filter['bb'] == 2) {
				$conds[] = "schiffeBergbau IS NULL";
				$conds[] = "schiffeTerraformer IS NULL";
			}
			// BBS
			else if($filter['bb'] == 3) {
				$conds[] = "schiffeBergbau IS NOT NULL";
			}
			// TF
			else {
				$conds[] = "schiffeTerraformer IS NOT NULL";
			}
		}
		// Summe aller Resswerte
		if(isset($filter['rw'])) {
			$filter['rw'] = (int)$filter['rw'];
			$conds[] = 'planetenRWErz+planetenRWWolfram+planetenRWKristall+planetenRWFluor >= '.$filter['rw'];
		}
		// gesamte Ressproduktion
		if(isset($filter['rp'])) {
			$filter['rp'] = (int)$filter['rp'];
			$conds[] = 'planetenRPGesamt >= '.$filter['rp'];
		}
		// gesamter Ressvorrat
		if(isset($filter['rv'])) {
			$filter['rv'] = (int)$filter['rv'];
			$conds[] = 'planetenRMGesamt >= '.$filter['rv'];
		}
		// Resswerte
		if(isset($filter['rwe'])) {
			$filter['rwe'] = (int)$filter['rwe'];
			$conds[] = 'planetenRWErz >= '.$filter['rwe'];
		}
		if(isset($filter['rww'])) {
			$filter['rww'] = (int)$filter['rww'];
			$conds[] = 'planetenRWWolfram >= '.$filter['rww'];
		}
		if(isset($filter['rwk'])) {
			$filter['rwk'] = (int)$filter['rwk'];
			$conds[] = 'planetenRWKristall >= '.$filter['rwk'];
		}
		if(isset($filter['rwf'])) {
			$filter['rwf'] = (int)$filter['rwf'];
			$conds[] = 'planetenRWFluor >= '.$filter['rwf'];
		}
		// Ressproduktion
		if(isset($filter['rpe'])) {
			$filter['rpe'] = (int)$filter['rpe'];
			$conds[] = 'planetenRPErz >= '.$filter['rpe'];
		}
		if(isset($filter['rpm'])) {
			$filter['rpm'] = (int)$filter['rpm'];
			$conds[] = 'planetenRPMetall >= '.$filter['rpm'];
		}
		if(isset($filter['rpw'])) {
			$filter['rpw'] = (int)$filter['rpw'];
			$conds[] = 'planetenRPWolfram >= '.$filter['rpw'];
		}
		if(isset($filter['rpk'])) {
			$filter['rpk'] = (int)$filter['rpk'];
			$conds[] = 'planetenRPKristall >= '.$filter['rpk'];
		}
		if(isset($filter['rpf'])) {
			$filter['rpf'] = (int)$filter['rpf'];
			$conds[] = 'planetenRPFluor >= '.$filter['rpf'];
		}
		// Ressvorrat
		if(isset($filter['rve'])) {
			$filter['rve'] = (int)$filter['rve'];
			$conds[] = 'planetenRMErz >= '.$filter['rve'];
		}
		if(isset($filter['rvm'])) {
			$filter['rvm'] = (int)$filter['rvm'];
			$conds[] = 'planetenRMMetall >= '.$filter['rvm'];
		}
		if(isset($filter['rvw'])) {
			$filter['rvw'] = (int)$filter['rvw'];
			$conds[] = 'planetenRMWolfram >= '.$filter['rvw'];
		}
		if(isset($filter['rvk'])) {
			$filter['rvk'] = (int)$filter['rvk'];
			$conds[] = 'planetenRMKristall >= '.$filter['rvk'];
		}
		if(isset($filter['rvf'])) {
			$filter['rvf'] = (int)$filter['rvf'];
			$conds[] = 'planetenRMFluor >= '.$filter['rvf'];
		}
		// geraidet
		if(isset($filter['rai']) AND $user->rechte['toxxraid']) {
			$filter['rai'] = (int)$filter['rai'];
			$conds[] = 'planetenGeraidet < '.(time()-86400*$filter['rai']);
		}
		// getoxxt
		if(isset($filter['tox']) AND $user->rechte['toxxraid']) {
			if($filter['tox']) {
				$conds[] = 'planetenGetoxxt > '.time();
			}
			else {
				$conds[] = 'planetenGetoxxt < '.time();
			}
		}
		// Kommentar
		if(isset($filter['ko'])) {
			$conds[] = "planetenKommentar LIKE '%".escape(escape(str_replace('*', '%', $filter['ko'])))."%'";
		}
		// User-ID
		if(isset($filter['uid'])) {
			$conds[] = 'planeten_playerID'.db_multiple($filter['uid']);
		}
		// User-Name
		if(isset($filter['un'])) {
			$conds[] = "playerName LIKE '".escape(escape(str_replace('*', '%', $filter['un'])))."'";
		}
		// Rasse
		if(isset($filter['ra'])) {
			$conds[] = 'playerRasse = '.(int)$filter['ra'];
		}
		// frei
		if(isset($filter['fr'])) {
			// frei
			if($filter['fr'] == 1) $conds[] = 'planeten_playerID = 0';
			// nicht frei
			else $conds[] = '(planeten_playerID > 0 OR planeten_playerID < -1)';
		}
		// kolonisierbar
		if(isset($filter['kbar'])) {
			$conds[] = 'planeten_playerID = 0';
			$conds[] = 'planetenNatives = 0';
			$conds[] = 'planetenGroesse > 3';
			
			// laufende Aktionen abfragen
			$q = query("
				SELECT
					invasionen_planetenID
				FROM
					".PREFIX."invasionen
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$ids = array();
			
			while($d = mysql_fetch_assoc($q)) {
				$ids[] = $d['invasionen_planetenID'];
			}
			
			if(count($ids)) {
				$conds[] = "planetenID NOT IN(".implode(',', $ids).")";
			}
		}
		// Urlaubsmodus
		if(isset($filter['umod'])) {
			// ja
			if($filter['umod'] == 1) $conds[] = 'playerUmod = 1';
			// nein
			else $conds[] = 'playerUmod = 0';
		}
		// Planeten
		if(isset($filter['pl'])) {
			$filter['pl'] = (int)$filter['pl'];
			$conds[] = 'playerPlaneten '.(isset($filter['pl_']) ? '>' : '<').'= '.$filter['pl'];
			$conds[] = 'planeten_playerID > 2';
		}
		// Inaktiv
		if(isset($filter['ina'])) {
			$filter['ina'] = (int)$filter['ina'];
			$filter['ina'] = time()-$filter['ina']*86400;
			$conds[] = 'playerActivity < '.$filter['ina'];
			$conds[] = 'playerActivity > 0';
			$conds[] = 'planeten_playerID > 2';
		}
		// Allianz-ID
		if(isset($filter['aid'])) {
			$conds[] = 'player_allianzenID'.db_multiple($filter['aid']);
		}
		// Allianz-Tag
		if(isset($filter['at'])) {
			$conds[] = "allianzenTag LIKE '".escape(escape(str_replace('*', '%', $filter['at'])))."'";
		}
		// Allianz-Name
		if(isset($filter['an'])) {
			$conds[] = "allianzenName LIKE '".escape(escape(str_replace('*', '%', $filter['an'])))."'";
		}
		// Allianz-Status
		if(isset($filter['as'])) {
			$filter['as'] = (int)$filter['as'];
			// Freunde
			if($filter['as'] == -1) {
				$conds[] = 'statusStatus IN('.implode(',', $status_freund).')';
			}
			// Feinde
			else if($filter['as'] == -2) {
				$conds[] = 'statusStatus IN('.implode(',', $status_feind).')';
			}
			// neutral
			else if($filter['as'] == 0) {
				$conds[] = '(statusStatus = 0 OR statusStatus IS NULL)';
			}
			// normaler Status
			else if(isset($status[$filter['as']])) {
				$conds[] = 'statusStatus = '.$filter['as'];
			}
		}
		// Allianz-Status (erweitert)
		if(isset($filter['as2'])) {
			foreach($filter['as2'] as $key=>$val) {
				if(!isset($status[$key])) {
					unset($filter['as2'][$key]);
				}
			}
			
			if(count($filter['as2']) AND count($filter['as2']) < count($status)) {
				$as2 = array_keys($filter['as2']);
				
				// neutral dabei -> NULL
				if(isset($filter['as2'][0])) {
					$conds[] = '(statusStatus IN('.implode(',', $as2).') OR statusStatus IS NULL)';
				}
				else {
					$conds[] = 'statusStatus IN('.implode(',', $as2).')';
				}
			}
		}
		// History
		if(isset($filter['his'])) {
			// User-ID
			if(isset($filter['his_'])) {
				$filter['his'] = (int)$filter['his'];
				// jeder Planet war mal frei
				if($filter['his'] == 0) $filter['his'] = -2;
				
				$query = query("
					SELECT DISTINCT
						history_planetenID
					FROM
						".PREFIX."planeten_history
					WHERE
						history_playerID = ".$filter['his']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
			// Username
			else {
				$query = query("
					SELECT
						playerID
					FROM
						".GLOBPREFIX."player
					WHERE
						playerName LIKE '".escape(escape(str_replace('*', '%', $filter['his'])))."'
					ORDER BY playerID ASC
					LIMIT 1
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				if(mysql_num_rows($query)) {
					$data = mysql_fetch_assoc($query);
					
					$query = query("
						SELECT DISTINCT
							history_planetenID
						FROM
							".PREFIX."planeten_history
						WHERE
							history_playerID = ".$data['playerID']."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
			}
			// auswerten und Bedingung hinzufügen
			if(mysql_num_rows($query)) {
				$val = array();
				while($row = mysql_fetch_assoc($query)) {
					$val[] = $row['history_planetenID'];
				}
				$conds[] = 'planetenID IN('.implode(',', $val).')';
			}
			// keine Planeten gefunden
			else {
				$conds[] = 'planetenID = 0';
			}
		}
		
		// Entfernung und Sortierung
		$entf = false;
		
		if(isset($filter['entf']) OR isset($filter['entf2'])) {
			if(isset($filter['entf'])) {
				$entf1 = flug_point($filter['entf']);
				if(is_array($entf1)) {
					$entf = entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $entf1[1], $entf1[2], $entf1[3], $entf1[4]);
					// Galaxie filtern
					$conds[] = 'systeme_galaxienID = '.$entf1[0];
				}
			}
			if(isset($filter['entf2'])) {
				$entf2 = flug_point($filter['entf2']);
				if(is_array($entf2)) {
					// Galaxie filtern
					if(!$entf) {
						$conds[] = 'systeme_galaxienID = '.$entf2[0];
					}
					$entf2 = entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $entf2[1], $entf2[2], $entf2[3], $entf2[4]);
					// zwei Entfernungen koppeln
					if($entf) {
						$entf .= " + ".$entf2;
					}
					else $entf = $entf2;
				}
			}
		}
		
		// Entfernungsfilter
		if($entf AND (isset($filter['ef1']) OR isset($filter['ef2']))) {
			$ef1 = isset($filter['ef1']) ? (int)$filter['ef1'] : 0;
			$ef2 = isset($filter['ef2']) ? (int)$filter['ef2'] : 0;
			$ef3 = isset($filter['ef3']) ? (int)$filter['ef3'] : 2; // default +- 2 min
			
			$ef = (3600*$ef1)+(60*$ef2);
			
			$ef3 *= 60;
			
			$conds[] = $entf.' > '.entffdauer($ef-$ef3, $antr);
			$conds[] = $entf.' < '.entffdauer($ef+$ef3, $antr);
		}
		
		
		return $conds;
		
	}
	
	/**
	 * Gebäude-Filter erzeugen
	 * @param array $filter
	 * @returns array Gebäude, nach denen gesucht werden soll
	 */
	public static function getGebaeudeFilter($filter) {
		
		global $gebaeude;
		
		if(isset($filter['geb'])) {
			$searchgeb = explode('-', $filter['geb']);
			// validieren
			foreach($searchgeb as $key=>$val) {
				if(!isset($gebaeude[$val]) OR $val <= 0) {
					unset($searchgeb[$key]);
				}
			}
		}
		else {
			$searchgeb = array();
		}
		
		return $searchgeb;
		
	}
	
	
	/**
	 * Sortierung der Suchergebnisse ermitteln
	 * @param array $filter
	 * @param string|false $entf
	 * 		Entfernungspunkt
	 * 		wird benötigt, wenn nach Entfernung sortiert werden soll
	 * @return string Sortierung für MySQL
	 */
	public static function getSort($filter, $entf=false) {
		// nach Entfernung sortieren
		if(isset($filter['sortt']) AND $entf) {
			$sort = 'planetenEntfernung '.(isset($filter['sorto3']) ? 'DESC' : 'ASC');
		}
		// nach Spalte sortieren
		else {
			if(!isset($filter['sort']) OR !isset(self::$sorto[$filter['sort']])) $filter['sort'] = 1;
			if(!isset($filter['sort2']) OR !isset(self::$sorto[$filter['sort2']])) $filter['sort2'] = 1;
			
			$sort = self::$sorto[$filter['sort']].' '.(isset($filter['sorto']) ? 'DESC' : 'ASC');
			// 2. Stufe, wenn nicht gleich wie 1. Stufe
			if($filter['sort2'] != $filter['sort']) {
				$sort .= ', '.self::$sorto[$filter['sort2']].' '.(isset($filter['sorto2']) ? 'DESC' : 'ASC');
			}
		}
		
		return $sort;
	}
	
	
	/**
	 * Berechnung der Entfernungs-Spalte ermitteln
	 * @param array $filter
	 * @return string|false Entfernungs-Spalte (MySQL)
	 */
	public static function getEntf($filter) {
		$entf = false;
		
		if(isset($filter['entf']) OR isset($filter['entf2'])) {
			if(isset($filter['entf'])) {
				$entf1 = flug_point($filter['entf']);
				if(is_array($entf1)) {
					$entf = entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $entf1[1], $entf1[2], $entf1[3], $entf1[4]);
				}
			}
			if(isset($filter['entf2'])) {
				$entf2 = flug_point($filter['entf2']);
				if(is_array($entf2)) {
					$entf2 = entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $entf2[1], $entf2[2], $entf2[3], $entf2[4]);
					// zwei Entfernungen koppeln
					if($entf) {
						$entf .= " + ".$entf2;
					}
					else $entf = $entf2;
				}
			}
		}
		
		return $entf;
	}
	
	
	/**
	 * MySQL-Tabellen zurückgeben, die für die Suche benötigt werden
	 */
	public static function getTables() {
		
		global $user;
		
		
		return "
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = allianzenID
			LEFT JOIN ".PREFIX."galaxien
				ON galaxienID = systeme_galaxienID
				AND galaxienGate = planetenID
			LEFT JOIN ".PREFIX."planeten_schiffe
				ON schiffe_planetenID = planetenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = allianzenID";
	}
	
	
	/**
	 * Trefferanzahl der Suchfunktion ermitteln
	 * @param array $conds MySQL-Bedingungen
	 * @returns array Trefferanzahl
	 */
	public static function getCount($conds) {
		
		$conds = implode(' AND ', $conds);
		if($conds == '') $conds = '1';
		
		
		$query = query("
			SELECT
				COUNT(*)
			FROM
				".self::getTables()."
			WHERE
				".$conds."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_array($query);
		
		return $data[0];
	}
	
	
	/**
	 * Suche durchführen und Ergebnisse als MySQL-Ressource zurückgeben
	 * @param array $conds MySQL-Bedingungen
	 * @param string|false $entf Entfernung abfragen
	 * @param string $sort Sortierung
	 * @param int $offset
	 * @param int $limit
	 */
	public static function getSearchAsMySQL($conds, $entf=false, $sort="NULL", $offset=0, $limit=100) {
		
		$conds = implode(' AND ', $conds);
		if($conds == '') $conds = '1';
		
		// Daten abfragen
		$query = query("
			SELECT
				planetenID,
				planetenName,
				planetenUpdateOverview,
				planetenUpdate,
				planetenUnscannbar,
				planetenTyp,
				planetenGroesse,
				planetenKategorie,
				planetenGebPlanet,
				planetenGebOrbit,
				planetenMyrigate,
				planetenRiss,
				planetenGateEntf,
				planetenRWErz,
				planetenRWWolfram,
				planetenRWKristall,
				planetenRWFluor,
				planetenRPErz,
				planetenRPMetall,
				planetenRPWolfram,
				planetenRPKristall,
				planetenRPFluor,
				planetenRMErz,
				planetenRMMetall,
				planetenRMWolfram,
				planetenRMKristall,
				planetenRMFluor,
				planetenRPGesamt,
				planetenRMGesamt,
				planetenForschung,
				planetenIndustrie,
				planetenBevoelkerung,
				planetenRessplani,
				planetenWerft,
				planetenBunker,
				planetenGeraidet,
				planetenGetoxxt,
				planetenKommentar,
				planeten_playerID,
				planetenNatives,
				
				".($entf ? $entf." AS planetenEntfernung," : '')."
				
				systemeID,
				systeme_galaxienID,
				systemeX,
				systemeY,
				systemeZ,
				systemeUpdate,
				systemeAllianzen,
				
				galaxienGate,
				
				playerName,
				playerPlaneten,
				playerRasse,
				playerImppunkte,
				playerUmod,
				playerDeleted,
				playerActivity,
				player_allianzenID,
				
				allianzenTag,
				allianzenName,
				
				register_allianzenID,
				
				schiffeBergbau,
				schiffeTerraformer,
				
				statusStatus
			FROM
				".self::getTables()."
			WHERE
				".$conds."
			ORDER BY
				".$sort."
			LIMIT
				".$offset.",".$limit."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		return $query;
	}
	
	
	/**
	 * Suche durchführen und Ergebnisse als Array zurückgeben
	 * @param array $conds MySQL-Bedingungen
	 * @param string|false $entf Entfernung auslesen
	 * @param int $offset
	 * @param int $limit
	 */
	public static function getSearchAsArray($conds, $entf=false, $sort="NULL", $offset=0, $limit=100) {
		
		$query = self::getSearchAsMySQL($conds, $entf, $sort, $offset, $limit);
		
		$array = array();
		
		while($row = mysql_fetch_assoc($query)) {
			$array[] = $row;
		}
		
		return $array;
		
	}
	
	
	/**
	 * Beschreibung eines Suchfilters generieren
	 * @param array $filter
	 * @return string Beschreibung
	 */
	public static function getSearchDescription($filter) {
		
		global $gebaeude;
		
		// Filter sichern
		foreach($filter as $key=>$val) {
			if(!is_array($val)) $filter[$key] = h($val);
		}
		
		// Gebäude-Filter
		$searchgeb = self::getGebaeudeFilter($filter);
		
		
		
		
		$desc = array();
		
		
		// Galaxie
		if(isset($filter['g'])) {
			$desc[] = 'Galaxie '.$filter['g'];
		}
		
		// Sektor
		if(isset($filter['sek']) AND $filter['sek'] >= 1 AND $filter['sek'] <= 4) {

			$sek = array(
				1=>'rot',
				2=>'grün',
				3=>'blau',
				4=>'gelb'
			);
			
			$desc[] = 'Sektor '.$sek[$filter['sek']];
		}
		// System-ID
		if(isset($filter['sid'])) {
			$desc[] = 'System '.$filter['sid'];
		}
		// System-Name
		if(isset($filter['sn'])) {
			$desc[] = 'Systemname '.$filter['sn'];
		}
		// System-Scan
		if(isset($filter['ssc'])) {
			if(isset($filter['ssct'])) {
				$filter['ssct'] = (int)$filter['ssct'];
			}
			else $filter['ssct'] = 0;
			
			// nicht gescannt
			if($filter['ssc'] == 1) {
				$desc[] = 'System nie gescannt';
			}
			// irgendwann
			else if($filter['ssc'] == 2) {
				$desc[] = 'System irgendwann gescannt';
			}
			// älter / neuer als
			else if($filter['ssct'] > 0) {
				$desc[] = 'System-Scan '.(($filter['ssc'] == 3) ? 'neuer als' : 'älter als').' '.$filter['ssct'].' Tage';
			}
		}
		
		// Planeten-ID
		if(isset($filter['pid'])) {
			$desc[] = 'Planet '.$filter['pid'];
		}
		// Planeten-Name
		if(isset($filter['pn'])) {
			$desc[] = 'Planetenname '.$filter['pn'];
		}
		// Original-Name
		if(isset($filter['pon'])) {
			$desc[] = 'Planet hat Originalnamen';
		}
		// Größe
		if(isset($filter['gr'])) {
			$val = '=';
			if(isset($filter['gr_'])) {
				if($filter['gr_'] == 1) $val = '&gt;';
				else $val = '&lt;';
			}
			$filter['gr'] = (int)$filter['gr'];
			$desc[] = 'Größe '.$val.' '.$filter['gr'];
		}
		// Planeten-Typ
		if(isset($filter['t'])) {
			$filter['t'] = (int)$filter['t'];
			$desc[] = 'Planetentyp <img src="img/planeten/'.$filter['t'].'.jpg" alt="" class="icon" />';
		}
		// Planeten-Scan (Oberfläche)
		if(isset($filter['sc'])) {
			if(isset($filter['sct'])) {
				$filter['sct'] = (int)$filter['sct'];
			}
			else $filter['sct'] = 0;
			
			// nicht gescannt
			if($filter['sc'] == 1) {
				$desc[] = 'Planet nie gescannt';
			}
			// irgendwann
			else if($filter['sc'] == 2) {
				$desc[] = 'Planet irgendwann gescannt';
			}
			// älter / neuer als
			else if($filter['sct'] > 0) {
				$desc[] = 'Planeten-Scan '.(($filter['sc'] == 3) ? 'neuer als' : 'älter als').' '.$filter['sct'].' Tage';
			}
		}
		// unscannbar
		if(isset($filter['usc'])) {
			$desc[] = 'Planet unscannbar';
		}
		// Kategorie
		if(isset($filter['k'])) {
			
			$kat = array(
				0=>'nicht kategorisiert',
				1=>'Erz',
				2=>'Metall',
				3=>'Wolfram',
				4=>'Kristall',
				5=>'Fluor',
				6=>'Forschungseinrichtungen',
				7=>'UNI-Labore',
				8=>'Forschungszentren',
				9=>'Myriforschung',
				10=>'orbitale Forschung',
				11=>'Gedankenkonzentratoren',
				12=>'Umsatzfabriken',
				13=>'Werft',
				14=>'alle Ressplaneten',
				15=>'alle Ressplaneten und Werften',
				16=>'alle Forschungsplaneten'
			);
			
			if(isset($kat[$filter['k']])) {
				$desc[] = 'Kategorie '.$kat[$filter['k']];
			}
		}
		// Gebäude
		if(count($searchgeb)) {
			
			$c = 'Gebäude ';
			
			foreach($searchgeb as $geb) {
				$c .= '<img src="img/gebaeude/'.$gebaeude[$geb].'" class="icon" alt="" />';
			}
			
			$desc[] = $c;
		}
		// Myrigate
		if(isset($filter['mg'])) {
			$desc[] = 'Myrigate';
		}
		
		// Orbiter
		if(isset($filter['o'])) {
			if($filter['o'] == 1) {
				$desc[] = 'keine Orbiter';
			}
			else if($filter['o'] == 2) {
				$desc[] = 'Orbiter max. Stufe 1';
			}
			else if($filter['o'] == 3) {
				$desc[] = 'Orbiter max. Stufe 2';
			}
			else if($filter['o'] == 4) {
				$desc[] = 'Orbiter mind. Stufe 2';
			}
			else if($filter['o'] == 5) {
				$desc[] = 'Orbiter mind. Stufe 3';
			}
			else {
				$desc[] = 'Orbiter vorhanden';
			}
		}
		// Natives
		if(isset($filter['na'])) {
			$val = '=';
			if(isset($filter['na_'])) {
				if($filter['na_'] == 1) $val = '&gt;';
				else $val = '&lt;';
			}
			$filter['na'] = (int)$filter['na'];
			$desc[] = 'Natives '.$val.' '.$filter['na'];
		}
		// Bevölkerung
		if(isset($filter['bev'])) {
			$filter['bev'] = (int)$filter['bev'];
			$desc[] = 'Bevölkerung &gt;= '.ressmenge2($filter['bev']);
		}
		// Forschung
		if(isset($filter['f'])) {
			$filter['f'] = (int)$filter['f'];
			$desc[] = 'Forschung &gt;= '.ressmenge2($filter['f']);
		}
		// Industrie
		if(isset($filter['i'])) {
			$filter['i'] = (int)$filter['i'];
			$desc[] = 'Industrie &gt;= '.ressmenge2($filter['i']);
		}
		// Punkte
		if(isset($filter['pu'])) {
			$filter['pu'] = (int)$filter['pu'];
			$desc[] = 'Planetenpunkte &gt;= '.ressmenge2($filter['pu']);
		}
		// Ressplanet
		if(isset($filter['rpl'])) {
			$desc[] = 'als Ressplanet markiert';
		}
		// Werft
		if(isset($filter['we'])) {
			$desc[] = 'als Werft markiert';
		}
		// Bunker
		if(isset($filter['bu'])) {
			$desc[] = 'als Bunker markiert';
		}
		// Bergbau
		if(isset($filter['bb'])) {
			// BBS oder TF
			if($filter['bb'] == 1) {
				$desc[] = 'Bergbau oder Terraformer';
			}
			// keins
			else if($filter['bb'] == 2) {
				$desc[] = 'weder Bergbau noch Terraformer';
			}
			// BBS
			else if($filter['bb'] == 3) {
				$desc[] = 'Bergbau';
			}
			// TF
			else {
				$desc[] = 'Terraformer';
			}
		}
		// Summe aller Resswerte
		if(isset($filter['rw'])) {
			$filter['rw'] = (int)$filter['rw'];
			$desc[] = 'Summe der Resswerte &gt;= '.$filter['rw'];
		}
		// gesamte Ressproduktion
		if(isset($filter['rp'])) {
			$filter['rp'] = (int)$filter['rp'];
			$desc[] = 'gesamte Ressproduktion &gt;= '.ressmenge2($filter['rp']);
		}
		// gesamter Ressvorrat
		if(isset($filter['rv'])) {
			$filter['rv'] = (int)$filter['rv'];
			$desc[] = 'gesamter Ressvorrat &gt;= '.ressmenge2($filter['rv']);
		}
		// Resswerte
		if(isset($filter['rwe'])) {
			$filter['rwe'] = (int)$filter['rwe'];
			$desc[] = 'Erz-Wert &gt;= '.$filter['rwe'];
		}
		if(isset($filter['rww'])) {
			$filter['rww'] = (int)$filter['rww'];
			$desc[] = 'Wolfram-Wert &gt;= '.$filter['rww'];
		}
		if(isset($filter['rwk'])) {
			$filter['rwk'] = (int)$filter['rwk'];
			$desc[] = 'Kristall-Wert &gt;= '.$filter['rwk'];
		}
		if(isset($filter['rwf'])) {
			$filter['rwf'] = (int)$filter['rwf'];
			$desc[] = 'Fluor-Wert &gt;= '.$filter['rwf'];
		}
		// Ressproduktion
		if(isset($filter['rpe'])) {
			$filter['rpe'] = (int)$filter['rpe'];
			$desc[] = 'Erz-Produktion &gt;= '.ressmenge2($filter['rpe']);
		}
		if(isset($filter['rpm'])) {
			$filter['rpm'] = (int)$filter['rpm'];
			$desc[] = 'Metall-Produktion &gt;= '.ressmenge2($filter['rpm']);
		}
		if(isset($filter['rpw'])) {
			$filter['rpw'] = (int)$filter['rpw'];
			$desc[] = 'Wolfram-Produktion &gt;= '.ressmenge2($filter['rpw']);
		}
		if(isset($filter['rpk'])) {
			$filter['rpk'] = (int)$filter['rpk'];
			$desc[] = 'Kristall-Produktion &gt;= '.ressmenge2($filter['rpk']);
		}
		if(isset($filter['rpf'])) {
			$filter['rpf'] = (int)$filter['rpf'];
			$desc[] = 'Fluor-Produktion &gt;= '.ressmenge2($filter['rpf']);
		}
		// Ressvorrat
		if(isset($filter['rve'])) {
			$filter['rve'] = (int)$filter['rve'];
			$desc[] = 'Erz-Vorrat &gt;= '.ressmenge2($filter['rve']);
		}
		if(isset($filter['rvm'])) {
			$filter['rvm'] = (int)$filter['rvm'];
			$desc[] = 'Metall-Vorrat &gt;= '.ressmenge2($filter['rvm']);
		}
		if(isset($filter['rvw'])) {
			$filter['rvw'] = (int)$filter['rvw'];
			$desc[] = 'Wolfram-Vorrat &gt;= '.ressmenge2($filter['rvw']);
		}
		if(isset($filter['rvk'])) {
			$filter['rvk'] = (int)$filter['rvk'];
			$desc[] = 'Kristall-Vorrat &gt;= '.ressmenge2($filter['rvk']);
		}
		if(isset($filter['rvf'])) {
			$filter['rvf'] = (int)$filter['rvf'];
			$desc[] = 'Fluor-Vorrat &gt;= '.ressmenge2($filter['rvf']);
		}
		// geraidet
		if(isset($filter['rai'])) {
			$filter['rai'] = (int)$filter['rai'];
			$desc[] = 'geraidet vor mehr als '.$filter['rai'].' Tagen';
		}
		// getoxxt
		if(isset($filter['tox'])) {
			$desc[] = 'Planet '.($filter['tox'] ? '' : 'nicht ').'getoxxt';
		}
		// Kommentar
		if(isset($filter['ko'])) {
			$desc[] = 'Kommentar enthält &quot;<i>'.$filter['ko'].'</i>&quot;';
		}
		// User-ID
		if(isset($filter['uid'])) {
			$desc[] = 'Inhaber-ID '.$filter['uid'];
		}
		// User-Name
		if(isset($filter['un'])) {
			$desc[] = 'Inhaber '.$filter['un'];
		}
		// Rasse
		if(isset($filter['ra'])) {
			
			global $rassen;
			
			$filter['ra'] = (int)$filter['ra'];
			
			if(isset($rassen[$filter['ra']])) {
				$desc[] = 'Rasse '.$rassen[$filter['ra']];
			}
		}
		// frei
		if(isset($filter['fr'])) {
			// frei
			if($filter['fr'] == 1) $desc[] = 'Planet frei';
			// nicht frei
			else $desc[] = 'Planet besetzt';
		}
		// kolonisierbar
		if(isset($filter['kbar'])) {
			$desc[] = 'Planet kolonisierbar';
		}
		// Urlaubsmodus
		if(isset($filter['umod'])) {
			// ja
			if($filter['umod'] == 1) $desc[] = 'Urlaubsmodus';
			// nein
			else $desc[] = 'kein Urlaubsmodus';
		}
		// Planeten
		if(isset($filter['pl'])) {
			$filter['pl'] = (int)$filter['pl'];
			$desc[] = 'Spieler hat '.(isset($filter['pl_']) ? 'mindestens' : 'höchstens').' '.$filter['pl'].' Planeten';
		}
		// Inaktiv
		if(isset($filter['ina'])) {
			$filter['ina'] = (int)$filter['ina'];
			$desc[] = 'Inhaber mehr als '.$filter['ina'].' Tage inaktiv';
		}
		// Allianz-ID
		if(isset($filter['aid'])) {
			$desc[] = 'Allianz-ID '.$filter['aid'];
		}
		// Allianz-Tag
		if(isset($filter['at'])) {
			$desc[] = 'Allianz-Tag '.$filter['at'];
		}
		// Allianz-Name
		if(isset($filter['an'])) {
			$desc[] = 'Allianz-Name '.$filter['an'];
		}
		// Allianz-Status
		if(isset($filter['as'])) {
			
			global $status;
			
			$filter['as'] = (int)$filter['as'];
			// Freunde
			if($filter['as'] == -1) {
				$desc[] = 'eingestuft als Freund';
			}
			// Feinde
			else if($filter['as'] == -2) {
				$desc[] = 'eingestuft als Feind';
			}
			// neutral
			else if($filter['as'] == 0) {
				$desc[] = 'neutral';
			}
			// normaler Status
			else if(isset($status[$filter['as']])) {
				$desc[] = 'Status '.$status[$filter['as']];
			}
		}
		// Allianz-Status (erweitert)
		if(isset($filter['as2'])) {
			
			global $status;
			
			foreach($filter['as2'] as $key=>$val) {
				if(!isset($status[$key])) {
					unset($filter['as2'][$key]);
				}
			}
			
			if(count($filter['as2']) AND count($filter['as2']) < count($status)) {
				$as2 = array_keys($filter['as2']);
				
				foreach($as2 as $key=>$as) {
					$as2[$key] = $status[$as];
				}
				
				$desc[] = 'Status '.implode(' / ', $as2);
			}
		}
		// History
		if(isset($filter['his'])) {
			// User-ID
			if(isset($filter['his_'])) {
				$desc[] = 'Planet hat dem Spieler '.$filter['his'].' (ID) gehört';
			}
			// Username
			else {
				$desc[] = 'Planet hat dem Spieler '.$filter['his'].' gehört';
			}
		}
		
		
		// kein Filter
		if(!count($desc)) {
			return 'alle Planeten';
		}
		
		
		return implode(', ', $desc);
	}
	
	
}
