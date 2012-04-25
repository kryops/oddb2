<?php

/**
 * common/config.php
 * Konfigurationsklasse
 * Instanz-Konfiguration abrufen und speichern
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class config {
	/**
	 * Konfiguration einer Instanz zurückgeben
	 * @param $instance int Instanz-ID
	 * @return array/false Konfiguration
	 */
	public static function get($instance) {
		// Validierung
		if(!is_numeric($instance)) {
			return false;
		}
		
		global $bconfig;
		
		$config = $bconfig;
		if(@include((ODDBADMIN ? '.' : '').'./config/config'.$instance.'.php')) {
			return $config;
		}
		else {
			return false;
		}
	}

	/**
	 * inkrementell geänderte Konfiguration einer Instanz zurückgeben
	 * @param $instance int Instanz-ID
	 * @return array/false Konfiguration
	 */
	public static function getcustom($instance) {
		// Validierung
		if(!is_numeric($instance)) {
			return false;
		}
		
		$config = array();
		if(@include((ODDBADMIN ? '.' : '').'./config/config'.$instance.'.php')) {
			return $config;
		}
		else {
			return false;
		}
	}

	/**
	 * Änderungen an den Rechteleveln einer Instanz zurückgeben
	 * @param $instance int Instanz-ID
	 * @return array/false Rechte
	 */
	public static function getcustom_rechte($instance) {
		// Validierung
		if(!is_numeric($instance)) {
			return false;
		}
		
		$rechte = array(
			0=>array(),
			1=>array(),
			2=>array(),
			3=>array(),
			4=>array()
		);
		
		if(@include((ODDBADMIN ? '.' : '').'./config/config'.$instance.'.php')) {
			return $rechte;
		}
		else {
			return false;
		}
	}

	/**
	 * Instanz-Konfiguration speichern
	 * @param $instance int DB-Instanz
	 * @param $config array Instanz-Konfiguration
	 * @param $rechte array Instanz-Rechte
	 *
	 * @return bool Erfolg
	 */
	public static function save($instance, $config=false, $rechte=false) {
		// valide ID übergeben?
		if(!is_numeric($instance)) {
			return false;
		}
		
		// Konfiguration und Rechte laden
		if($config === false) {
			$config = config::getcustom($instance);
		}
		if($config === false) {
			$config = array();
		}
		
		if($rechte === false) {
			$rechte = config::getcustom_rechte($instance);
		}
		
		// Dateiinhalt erzeugen
		$content = '<'.'?php

/**
 * config.php
 * manipuliert die Instanz-spezifischen Einstellungen
 */

// Sicherheitsabfrage
if(!defined(\'ODDB\')) die(\'unerlaubter Zugriff!\');

';
		
		// Einstellungen
		foreach($config as $key=>$val) {
			$content .= '$config[\''.str_replace('\\"', '"', addslashes($key)).'\'] = ';
			if(is_bool($val)) {
				$content .= $val ? 'true' : 'false';
			}
			else if(is_int($val)) {
				$content .= $val;
			}
			else if(is_array($val)) {
				// Array-Werte anpassen
				foreach($val as $key2=>$val2) {
					if(is_bool($val2)) {
						$val[$key2] = $val2 ? 'true' : 'false';
					}
					else if(is_string($val2)) {
						$val[$key2] = '\''.str_replace('\\"', '"', addslashes($val2)).'\'';
					}
					else if(is_array($val2)) {
						$val[$key2] = 'unserialize(\''.str_replace('\\"', '"', addslashes(serialize($val2))).'\')';
					}
				}
				$content .= 'array('.implode(', ', $val).')';
			}
			else {
				$content .= '\''.str_replace('\\"', '"', addslashes($val)).'\'';
			}
			$content .= ';
';
		}
		
		$content .= '
';
		
		// Rechte
		if($rechte !== false) {
			// Einstellungen
			foreach($rechte as $key=>$val) {
				if(count($val)) {
					foreach($val as $key2=>$val2) {
						$content .= '$rechte['.$key.'][\''.str_replace('\\"', '"', addslashes($key2)).'\'] = ';
						if(is_bool($val2)) {
							$content .= $val2 ? 'true' : 'false';
						}
						else if(is_int($val2)) {
							$content .= $val2;
						}
						else {
							$content .= '\''.str_replace('\\"', '"', addslashes($val2)).'\'';
						}
						$content .= ';
';
					}
				}
			}
		}

		$content .= '

?'.'>';
		
		$fp = @fopen((ODDBADMIN ? '.' : '').'./config/config'.$instance.'.php', 'w');
		if(!$fp) {
			return false;
		}
		fwrite($fp, $content);
		fclose($fp);
		
		// Erfolg
		return true;
	}
}

?>