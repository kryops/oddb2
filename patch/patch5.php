<?php

/*
 * Patch Version 2.3.3 - 2.3.4 (Patch-Nummer 5)
 */


if($config['patchversion'] == 4) {
	
	/*
	 * Neue Spalten in der Planeten-Tabelle für den Werft-Belieferer
	 */
	
	global $dbs;
	
	foreach($dbs as $instance=>$name) {
		$prefix = mysql::getPrefix($instance);
		
		query("
			ALTER TABLE ".$prefix."planeten
			ADD `planetenBelieferer` int(10) unsigned NOT NULL DEFAULT 0,
  			ADD `planetenBeliefererTime` int(10) unsigned NOT NULL DEFAULT 0
		");
	}
	
	$config['patchversion'] = 5;
	
}

?>