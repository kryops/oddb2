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
		
		global $gconfig;
		
		$config = $gconfig;
		
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
			$config = self::getcustom($instance);
		}
		if($config === false) {
			$config = array();
		}
		
		if($rechte === false) {
			$rechte = self::getcustom_rechte($instance);
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
						$val[$key2] = 'json_decode(\''.str_replace('\\"', '"', addslashes(json_encode($val2))).'\', true)';
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
	
	/**
	 * Inkrementelle globale Konfiguration auslesen
	 * @param string $filename Name der Datei (ohne .php-Endung)
	 * @param string $varname Name der Konfigurationsvariable
	 * @return array/false Konfiguration
	 */
	public static function getcustomGlobal($filename, $varname) {
		
		// Datei- und Variablennamen validieren
		if(!self::validateString($filename) OR !self::validateString($varname)) {
			return false;
		}
		
		
		${$varname} = array();
		if(@include((ODDBADMIN ? '.' : '').'./config/'.$filename.'.php')) {
			return ${$varname};
		}
		else {
			return false;
		}
		
	}
	
	
	/**
	 * globale Konfiguration speichern
	 * @param string $filename Name der Datei (ohne .php-Endung)
	 * @param string $varname Name der Konfigurationsvariable
	 * @param array $data zu speicherndes Konfigurations-Array
	 * @param bool $merge Konfiguration vorher auslesen und verschmelzen
	 * @return bool Erfolg
	 */
	public static function saveGlobal($filename, $varname, $data, $merge=false) {
		
		// Datei- und Variablennamen validieren
		if(!self::validateString($filename) OR !self::validateString($varname)) {
			return false;
		}
		
		
		$path = (ODDBADMIN ? '.' : '').'./config/'.$filename.'.php';
		
		if(file_exists($path)) {
			// restliche Konfiguration auslesen
			if($merge) {
				$data = array_merge(self::getcustomGlobal($filename, $varname), $data);
			}
			
			// benutzerdefinierte Einstellungen beachten
			$content = file_get_contents($path);
			preg_match("#//BEGIN:USERDEFINED(.*)//END:USERDEFINED#Uis", $content, $udef);
			
			if($udef) {
				$udef = $udef[1];
			}
			else {
				$udef = '';
			}
		}
		else {
			$udef = '';
		}
		
		// Dateiinhalt erzeugen
		$content = "<"."?php
error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

";
		
		foreach($data as $key=>$val) {
			$content .= '
$'.$varname.'['.self::transformArrayVal($key, true).'] = '.self::transformArrayVal($val).';';
		}
		
		$content .= '

//BEGIN:USERDEFINED
'.trim($udef).'
//END:USERDEFINED

?'.'>';
		
		// speichern
		$fp = @fopen($path, 'w');
		if(!$fp) {
			return false;
		}
		fwrite($fp, $content);
		fclose($fp);
		
		// Erfolg
		return true;
		
	}
	
	
	/**
	 * Überprüft, ob ein String nur Buchstaben, Zahlen, Bindetriche und Unterstriche enthält
	 * @param string $str
	 * @return boolean
	 */
	public static function validateString($str) {
		return (preg_replace("/[a-zA-Z0-9_\-]/Uis", "", $str) == "");
	}
	
	
	/**
	 * Array in String umwandeln
	 * @param Array $arr
	 * @return string
	 */
	public static function transformArray($arr) {
		
		$out = array();
		
		foreach($arr as $key=>$val) {
			$out[] = self::transformArrayVal($key, true).' => '.self::transformArrayVal($val);
		}
		
		return implode(",\n	", $out);
		
	}
	
	/**
	 * Array-Schlüssel oder -Wert zur Speicherung umwandeln
	 * Strings werden mit Anführungszeichen versehen
	 * Arrays 
	 * @param mixed $var
	 * @param boolean $key Handelt es sich um einen Array-Schl�ssel? @default false
	 * @return string
	 */
	public static function transformArrayVal($var, $key=false) {
		
		// boolesche Variable
		if(is_bool($var)) {
			
			// in Schlüsseln nicht erlaubt; als String zurückgeben
			if($key) {
				return $var ? "'true'" : "'false'";
			}
			
			return $var ? "true" : "false";
		}
		
		// Zahl
		if(is_int($var)) {
			return (string)$var;
		}
		
		// Array: rekursiv behandeln
		if(is_array($var)) {
			
			// Schlüssel können keine Arrays sein
			if($key) {
				return "array";
			}
			
			return "array(".self::transformArray($var).")";
		}
		
		// den Rest als String behandeln
		$var = addslashes($var);
		
		// doppelte Anführungszeichen wieder unescapen
		$var = str_replace('\\"', '"', $var);
		
		return "'".$var."'";
		
	}
	
}

?>