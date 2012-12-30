<?php

/**
 * pages/scan/forschung.php
 * Forschungs-Übersicht einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



General::loadClass('Forschung');


class ScanForschung {
	
	/**
	 * Ermittelt, ob ein Benutzer mit einer bestimmten ID registriert ist
	 * und liest Name und bisherige Forschung aus
	 * @param int $id
	 * @return array|false
	 */
	public static function userExists($id) {
		
		$query = query("
			SELECT
				user_playerName,
				userForschung
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".(int)$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(!mysql_num_rows($query)) {
			return false;
		}
		
		return mysql_fetch_assoc($query);
	}
	
	/**
	 * Daten des Parsers validieren
	 */
	public static function validate() {
		
		global $tmpl;
		
		if(!isset($_POST['f'], $_POST['fn'], $_POST['ff'], $_POST['kategorie'], $_POST['uid'])) {
			$tmpl->abort('Daten unvollständig!');
		}
		
		if(count($_POST['f']) != count($_POST['fn']) OR count($_POST['fn']) != count($_POST['ff'])) {
			$tmpl->abort('Daten invalid!');
		}
		
		
		$_POST['uid'] = (int)$_POST['uid'];
		$_POST['kategorie'] = (int)$_POST['kategorie'];
		
		if(!isset(Forschung::$kategorien[$_POST['kategorie']])) {
			$tmpl->abort('Ungültige Forschungs-Kategorie!');
		}
		
	}
	
	
	/**
	 * Forschungen einscannen
	 */
	public static function scan() {
		
		global $tmpl, $cache, $user, $config;
		
		
		// Übergebene Daten validieren
		self::validate();
		
		$kat = $_POST['kategorie'];
		$uid = $_POST['uid'];
		
		// Flooding-Schutz 10 Minuten
		if($cache->get('scanforsch'.$kat.'_'.$uid) AND !isset($_GET['force'])) {
			$tmpl->abort('Die Forschung wurde in den letzten 10 Minuten schon eingescannt!');
		}
		
		$cache->set('scanforsch'.$kat.'_'.$uid, 1, 600);
		
		
		// Sicherstellen, dass alle Forschungen eingetragen sind
		$fcount = count($_POST['f']);
		
		for($i=0; $i<$fcount; $i++) {
			Forschung::add($_POST['f'][$i], $_POST['fn'][$i], $kat);
		}
		

		if($u = self::userExists($uid)) {
			
			// Forschung auswerten
			$forschung = Forschung::getUserArray($u['userForschung']);
			
			$forschung['update'][$kat] = time();
			
			$fids = array();
			
			for($i=0; $i<$fcount; $i++) {
				if($_POST['ff'][$i] AND $fid = Forschung::getId($_POST['f'][$i])) {
					$fids[] = $fid;
				}
			}
			
			// aktuelle Forschung
			if(isset($_POST['current']) AND $fid = Forschung::getId($_POST['current'])) {
				$forschung['current'] = $fid;
			}
			else {
				$forschung['current'] = 0;
			}
			
			if(isset($_POST['current_end']) AND $current_end = @strtotime($_POST['current_end'])) {
				$forschung['current_end'] = $current_end;
			}
			else {
				$forschung['current_end'] = 0;
			}
			
			$forschung[$kat] = $fids;
			
			// speichern
			query("
				UPDATE
					".PREFIX."user
				SET
					userForschung = '".escape(json_encode($forschung))."'
				WHERE
					user_playerID = ".$uid."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				if($_POST['uid'] == $user->id) {
					insertlog(4, 'scannt die eigene '.Forschung::$kategorien[$_POST['kategorie']].'-Forschung ein');
				}
				else {
					insertlog(4, 'scannt die '.Forschung::$kategorien[$_POST['kategorie']].'-Forschung von '.$u['user_playerName'].' ('.$_POST['uid'].') ein');
				}
			}
		}
		
		// Ausgabe
		$tmpl->content = Forschung::$kategorien[$_POST['kategorie']].'-Forschung erfolgreich eingescannt';
	}
	
}

?>