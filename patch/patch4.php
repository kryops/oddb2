<?php

/*
 * Patch Version 2.3.1 - 2.3.1.1 (Patch-Nummer 4)
 */


if($config['patchversion'] == 3) {
	
	/*
	 * Grafiken auf static.omega-day.com haben andere Pfade
	 * Forschungs-Tabelle leeren
	 * Forschungs-Spalte in der User-Tabelle leeren
	 */
	
	query("
		DELETE FROM `".GLOBPREFIX."forschung`
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	global $dbs;
	
	foreach($dbs as $instance=>$name) {
		$prefix = mysql::getPrefix($instance);
		
		query("
			UPDATE `".$prefix."user` SET  userForschung = ''
		");
	}
	
	$config['patchversion'] = 4;
	
}

?>