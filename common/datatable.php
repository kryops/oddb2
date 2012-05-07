<?php

/**
 * common/datatable.php
 * Planetentabellen-Zellen erzeugen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class datatable {
	/**
	 * Galaxie
	 * @param $g int Galaxie
	 * @param $x int X-Koordinate @default false
	 * @param $z int Z-Koordinate @default false
	 * @return HTML
	 */
	public static function galaxie($g, $x=false, $z=false) {
		if($x === false OR $z === false) {
			return $g;
		}
		else {
			return '<span style="color:'.sektor_coord($x, $z).'">'.$g.'</span>';
		}
	}
	
	/**
	 * System
	 * @param $id int System-ID
	 * @return HTML
	 */
	public static function system($id) {
		return '<a class="link winlink contextmenu link_system" data-id="'.$id.'" data-link="index.php?p=show_system&amp;id='.$id.'&amp;ajax">'.$id.'</a>';
	}
	
	/**
	 * System mit Sektorfarbe
	 * @param $id int System-ID
	 * @param $x X-Koordinate
	 * @param $z Z-Koordinate
	 * @return HTML
	 */
	public static function systemsektor($id, $x, $z) {
		return '<a class="link winlink contextmenu link_system" data-id="'.$id.'" data-link="index.php?p=show_system&amp;id='.$id.'&amp;ajax" style="color:'.sektor_coord($x, $z).'">'.$id.'</a>';
	}
	
	/**
	 * Planet
	 * @param $id int Planeten-ID
	 * @param $name string Planeten-Name @default false
	 * @return HTML
	 */
	public static function planet($id, $name=false) {
		return '<a class="link winlink contextmenu link_planet" data-id="'.$id.'" data-link="index.php?p=show_planet&amp;id='.$id.'&amp;ajax">'.($name === false ? $id : htmlspecialchars($name, ENT_COMPAT, 'UTF-8')).'</a>';
	}
	
	/**
	 * Inhaber
	 * @param $id int Player-ID
	 * @param $name string Name @default false
	 * @param $umod bool/int Urlaubsmodus @default false
	 * @param $rasse int Rassen-ID @default false
	 * @return HTML
	 */
	public static function inhaber($id, $name=false, $umod=false, $rasse=false) {
		global $rassen2;
		
		if($name !== NULL AND $id) {
			$c = '
	<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$id.'">'.htmlspecialchars($name, ENT_COMPAT, 'UTF-8').'</a>';
			// Urlaubsmodus
			if($umod) {
				$c .= '<sup class="small red">zzZ</sup>';
			}
			// Rasse
			if($rasse AND isset($rassen2[$rasse])) {
				$c .= ' <img src="img/layout/leer.gif" class="rasse searchrasse '.$rassen2[$rasse].'" alt="" />';
			}
		}
		else if($id == 0) {
			$c = '<i>keiner</i>';
		}
		else if($id == -2) {
			$c = '<span style="color:#ffff88;font-weight:bold;font-style:italic">Seze Lux</span>';
		}
		else if($id == -3) {
			$c = '<span style="color:#ffff88;font-weight:bold;font-style:italic">Altrasse</span>';
		}
		else if($name === false) {
			$c = '
	<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$id.'">'.$id.'</a>';
		}
		else {
			$c = '<i>unbekannt</i>';
		}
		
		return $c;
	}
	
	/**
	 * Spieler
	 * Alias zu inhaber()
	 */
	public static function player($id, $name=false, $umod=false, $rasse=false) {
		return datatable::inhaber($id, $name, $umod, $rasse);
	}
	
	/**
	 * Allianz
	 * @param $id int Allianz-ID
	 * @param $tag string Allianz-Tag @default false
	 * @param $status int/false Allianz-Status für Einfärbung @default false
	 * @param $statusadd bool Status nicht einfärben, sondern anhängen @default false
	 * @return HTML
	 */
	public static function allianz($id, $tag=false, $st=false, $statusadd=false) {
		global $status, $status_color, $user;
		
		if($st === NULL) {
			$st = 0;
		}
		
		if($id > 0) {
			$c = '<span style="white-space:nowrap"><a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$id.'" '.(
				($st !== false AND !$statusadd AND isset($status_color[$st]) AND $id != $user->allianz)
				? $status_color[$st]
				: ''
			).'>'.(
				($tag !== NULL) 
				? htmlspecialchars($tag, ENT_COMPAT, 'UTF-8')
				: '<i>unbekannt</i>'
			).'</a></span>';
			
			// Status anhängen
			if($st !== false AND $statusadd AND isset($status[$st], $status_color[$st]) AND $id != $user->allianz) {
				$c .= ' <span class="small" '.$status_color[$st].'>'.$status[$st].'</span>';
			}
		}
		else {
			$c = '&nbsp;';
		}
		
		return $c;
	}
	
	/**
	 * Spieler und Allianz
	 * @param $id int Player-ID
	 * @param $name string Name @default false
	 * @param $aid int Allianz-ID @default false
	 * @param $atag string Allianz-Tag @default false
	 * @param $umod bool/int Urlaubsmodus @default false
	 * @param $rasse int Rassen-ID @default false
	 * @return HTML
	 */
	public static function playerallianz($id, $name=false, $aid=false, $atag=false, $umod=false, $rasse=false) {
		global $rassen2;
		
		// Spieler-Link
		$c = datatable::inhaber($id, $name, $umod, $rasse);
		
		// Ally-Link
		if($aid > 0 AND $id > 2) {
			$c .= '&nbsp; <a class="link winlink contextmenu small2" data-link="index.php?p=show_ally&amp;id='.$aid.'">'.(
				($atag !== false AND $atag !== NULL)
				? htmlspecialchars($atag, ENT_COMPAT, 'UTF-8')
				: $aid
			).'</a>';
		}
		
		return $c;
	}
	
	/**
	 * Allianz-Status
	 * @param $st int/null Allianz-Status
	 * @return HTML
	 */
	public static function status($st, $aid=false) {
		global $status, $status_color, $user;
		
		if($aid !== false AND !$aid) {
			return '&nbsp;';
		}
		
		// eigene Allianz
		if($aid == $user->allianz) {
			return '&nbsp;';
		}
		
		// neutral
		if($status === NULL) {
			$status = 0;
		}
		
		// nicht vorhanden
		if(!isset($status[$st], $status_color[$st])) {
			return '&nbsp;';
		}
		
		// Status zurückgeben
		return '<span '.$status_color[$st].'>'.$status[$st].'</span>';
	}
	
	/**
	 * Planetentyp-Icon
	 * @param $typ int Planetentyp
	 * @return HTML
	 */
	public static function typ($typ) {
		return '<img src="img/planeten/'.$typ.'.jpg" alt="" class="searchicon" style="width:20px;height:20px" />';
	}
	
	/**
	 * Bevölkerung
	 * @param $bev int Bevölkerung
	 * @return formatierte Zahl
	 */
	public static function bevoelkerung($bev) {
		return ressmenge($bev);
	}
	
	/**
	 * Gate-Entfernung
	 * @param $entf int/null Entfernung in Zentimetron
	 * @param $antr int Antrieb
	 * @return HTML
	 */
	public static function gate($entf, $antr) {
		return ($entf !== NULL ? flugdauer($entf, $antr) : '-');
	}
	
	/**
	 * Scan-Datum
	 * @param $sc int Timestamp
	 * @param $days int Tage, ab denen der Scan veraltet ist
	 * @return HTML
	 */
	public static function scan($sc, $days) {
		global $datum_heute;
		if($datum_heute === NULL) {
			$datum_heute = strtotime('today');
		}
		
		$color = (time()-($days*86400) > $sc) ? 'red' : 'green';
		
		if($sc >= $datum_heute) $scan = 'heute';
		else if($sc) $scan = strftime('%d.%m.%y', $sc);
		else $scan = 'nie';
		
		return '<span class="'.$color.'">'.$scan.'</span>';
	}
	
	/**
	 * Miniaturansicht der Planeten-Bebauung
	 * @param $row array Planeten-Datensatz
	 * @param $days int Tage, ab denen der Scan veraltet ist
	 * @return HTML
	 */
	public static function screenshot($row, $days) {
		global $user, $datum_heute;
		if($datum_heute === NULL) {
			$datum_heute = strtotime('today');
		}
		
		// Vorhandensein der Spalten
		$sp = array(
			'planeten_playerID',
			'player_allianzenID',
			'statusStatus',
			'allianzenRegister',
			'planetenUpdateOverview',
			'planetenTyp',
			'planetenGebPlanet',
			'planetenGebOrbit'
		);
		
		foreach($sp as $val) {
			if(!isset($row[$val])) {
				$row[$val] = 0;
			}
		}
		
		// Berechtigung überprüfen, den Scan zu sehen
		$r_show = $user->rechte['show_planet'];
		
		// bei eigenen Planeten immer Berechtigung, falls globale Berechtigung
		if($r_show AND $row['planeten_playerID'] != $user->id) {
			// keine Berechtigung (Ally)
			if(!$user->rechte['show_planet_ally'] AND $user->allianz AND $row['player_allianzenID'] == $user->allianz) {
				$r_show = false;
			}
			// keine Berechtigung (Meta)
			else if($user->allianz AND !$user->rechte['show_planet_meta'] AND $row['statusStatus'] == $status_meta AND $row['player_allianzenID'] != $user->allianz) {
				$r_show = false;
			}
			// keine Berechtigung (registrierte Allianzen)
			else if(!$user->rechte['show_planet_register'] AND $row['allianzenRegister'] AND $row['statusStatus'] != $status_meta) {
				$r_show = false;
			}
		}
		
		if($row['planetenUpdateOverview'] AND $r_show) {
			$color = (time()-($days*86400) > $row['planetenUpdateOverview']) ? 'red' : 'green';
			
			$scan = strftime('%d.%m.%y', $row['planetenUpdateOverview']);
			if($row['planetenUpdateOverview'] >= $datum_heute) $scan = 'heute';
			else $scan = strftime('%d.%m.%y', $row['planetenUpdateOverview']);
			
			$c = '<div class="searchicon tooltip plscreen" style="width:18px;height:18px;background-position:-336px -54px" data-plscreen="'.$row['planetenTyp'].'_0+'.$row['planetenGebPlanet'].'_0+'.$row['planetenGebOrbit'].'_&lt;div class=&quot;'.$color.' center&quot;&gt;Scan: '.$scan.'&lt;/div&gt;"></div>';
		}
		else $c = '&nbsp;';
		
		return $c;
	}
	
	/**
	 * Planeten-Kategorieibild
	 * @param $k int Kategorie-ID
	 * @param $row array Datensatz @default false (für den Ressvorrat-Tooltip
	 * @return HTML
	 */
	public static function kategorie($k, $scan=false, $row=false) {
		// ohne Ressvorrat
		if($row === false OR !$scan) {
			return '<div class="katicon tooltip" style="'.($k ? 'background-position:-'.(20*($k-1)).'px 0px' : 'background-image:none').'"></div>';
		}
		// mit Ressvorrat
		else {
			return '<div class="katicon tooltip" style="'.($k ? 'background-position:-'.(20*($k-1)).'px 0px' : 'background-image:none').'" data-tooltip="&lt;table class=&quot;showsysresst&quot;&gt;&lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress erz&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMErz']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress metall&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMMetall']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress wolfram&quot;&gt;&lt;/div&gt;&lt;/td&gt; &lt;td&gt;'.ressmenge($row['planetenRMWolfram']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress kristall&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMKristall']).'&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td&gt;&lt;div class=&quot;ress fluor&quot;&gt;&lt;/div&gt;&lt;/td&gt;&lt;td&gt;'.ressmenge($row['planetenRMFluor']).'&lt;/td&gt; &lt;/tr&gt; &lt;/table&gt;"></div>';
		}
	}
	
	/**
	 * Planetenkommentar
	 * @param $k string Kommentar
	 * @param $id int Planeten-ID
	 * @return HTML
	 */
	public static function kommentar($k, $id) {
		// Kommentar
		if(trim($k) != '') {
			return '<div class="plkommentar'.$id.' kommentar searchicon tooltip" data-tooltip="'.htmlspecialchars(nl2br(htmlspecialchars($k, ENT_COMPAT, 'UTF-8')), ENT_COMPAT, 'UTF-8').'"></div>';
		}
		else {
			return '<div class="plkommentar'.$id.' kommentar searchicon tooltip" data-tooltip="" style="display:none"></div>';
		}
	}
	
	/**
	 * Planet geraidet / raiden-Link
	 * @param $raid int Timestamp des letzten Raids
	 * @param $id int Planeten-ID
	 * @return HTML
	 */
	public static function geraidet($raid, $id) {
		return '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=raid&amp;id='.$id.'&amp;typ=search&amp;ajax\', this.parentNode, false, false)">'.($raid ? '<span'.((time()-$raid > 604800) ? ' class="hint"' : '').'>'.strftime('%d.%m.%y', $raid).'</span>' : '<span class="hint" style="font-style:italic">nie</span>').'</a>';
	}
	
	/**
	 * Planet getoxxt bis / toxxen-Link
	 * @param $toxx int Timestamp, wann der Planet wieder voll ist
	 * @param $id int Planeten-ID
	 * @return HTML
	 */
	public static function getoxxt($toxx, $id) {
		return ($toxx > time() ? strftime('%d.%m.%y', $toxx) : '<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=toxx&amp;id='.$id.'&amp;typ=search&amp;ajax\', this.parentNode, false, false)" class="hint">[getoxxt]</a>');
	}
	
}

?>