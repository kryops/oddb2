<?php

/*
 * Patch Version 2.2.3 - 2.2.4 (Patch-Nummer 3)
 */


if($config['patchversion'] == 2) {
	
	/*
	 * Forschungs-Tabelle anlegen
	 * Forschungs-Spalte in die Usertabelle einfügen
	 */
	
	query("
		CREATE TABLE IF NOT EXISTS `".GLOBPREFIX."forschung` (
		  `forschungID` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `forschungKategorie` tinyint(3) unsigned NOT NULL DEFAULT 0,
		  `forschungName` varchar(255) NOT NULL,
		  `forschungPfad` varchar(255) NOT NULL,
		  PRIMARY KEY (`forschungID`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	global $dbs;
	
	foreach($dbs as $instance=>$name) {
		$prefix = mysql::getPrefix($instance);
		
		query("
			ALTER TABLE  `".$prefix."user` ADD  `userForschung` TEXT NOT NULL
		");
	}
	
	$config['patchversion'] = 3;
	
}

?>