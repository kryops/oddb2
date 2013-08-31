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
		
		if(strlen($key) < 22) {
			for($i=strlen($key); $i<22; $i++) {
				$key .= 'a';
			}
		}
		
		$hash = crypt($pass, '$2a$11$'.$key.'$');
		
		return substr($hash, -32, 32);
		
	}
	
	
	/**
	 * Patches installieren
	 */
	public static function patchApplication() {
		
		global $config, $cache;
		
		// Anwendung nicht aktuell
		if(PATCH_VERSION > $config['patchversion']) {
			
			// Doppeltes Patchen verhindern
			if($cache->getglobal('patch')) {
				$tmpl = new template;
				$tmpl->error = 'Die ODDB wird derzeit aktualisiert. Bitte warte einen Moment!';
				$tmpl->output();
				die();
			}
			
			$cache->setglobal('patch', true, 60);
			
			// Datenbank sperren
			General::loadClass('config');
			config::saveGlobal('global', 'config', array(
				'active'=>false,
				'offlinemsg'=>'Die ODDB wird derzeit aktualisiert. Bitte warte einen Moment!'
			), true);
			
			// 5 Sekunden pausieren, wenn die Patch-Dateien noch nicht hochgeladen wurden
			for($i = $config['patchversion']+1; $i <= PATCH_VERSION; $i++) {
				if(!file_exists('./patch/patch'.$i.'.php')) {
					sleep(5);
					break;
				}
			}
			
			
			// Patches einbinden
			for($i = $config['patchversion']+1; $i <= PATCH_VERSION; $i++) {
				@include './patch/patch'.$i.'.php';
			}
			
			
			// Konfiguration speichern und Datenbank wieder entsperren
			$c = config::getcustomGlobal('global', 'config');
			unset($c['active']);
			unset($c['offlinemsg']);
			$c['patchversion'] = $config['patchversion'];
			
			config::saveGlobal('global', 'config', $c);
			
			// aus dem Cache löschen
			$cache->removeglobal('patch');
		}
		
	}
	
	/**
	 * zufälligen API-Key generieren
	 * @return API-Key
	 */
	public static function generateApiKey() {
		
		return md5(microtime(true));
		
	}
		
}


?>