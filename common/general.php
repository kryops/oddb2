<?php
error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



class General {
	
	/**
	 * Lädt eine Klassendatei
	 * @param string $classname Klassenname mit Pfad ([Ordner/]Name)
	 * @return boolean Erfolg
	 */
	public static function loadClass($classname) {
		
		// Klasse in Unterordner
		$folder = explode('/', $classname);
		$name = array_pop($folder);
		
		$folder = implode('/', $folder);
		if($folder != '') {
			$folder .= '/';
		}
		
		$file = (ODDBADMIN ? '.' : '').'./common/'.$folder.strtolower($name).'.php';
		
		// Klasse bereits geladen
		if(class_exists($name)) {
			return false;
		}
		
		// Klasse einbinden
		include $file;
		
		return true;
	}
	
	/**
	 * Passwort verschlüsseln
	 * @param string $pass Passwort
	 * @param string $key Instanzbasierter Sicherheitsschlüssel
	 * @return string Passwort-Hash
	 */
	public static function encryptPassword($pass, $key='') {
		
		$hash = crypt($pass, '$2a$10'.$key.'$');
		
		return substr($hash, -32, 32);
		
	}
	
	
}


?>