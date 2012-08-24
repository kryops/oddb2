<?php

/**
 * pages/login/instance.php
 * Liste der Instanzen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// AJAX abfangen
if(isset($_GET['ajax'])) {
	$tmpl = new template;
	$tmpl->error = '
Du bist nicht mehr eingeloggt!
<br /><br />
<a href="index.php">neu einloggen</a>
';
}
// Ungültiger API-Key
else if(isset($_GET['p']) AND $_GET['p'] == 'api') {
	$tmpl = new template;
	$tmpl->error = 'API-Key ungültig';
}
// FoW abfangen
else if(isset($_GET['p']) AND $_GET['p'] == 'fow') {
	diefow();
}
// Instanz-Auswahl anzeigen
else {
	$tmpl = new template_login;
	$tmpl->content = '
	<div class="hl1">
		Datenbank ausw&auml;hlen
	</div>
	<br /><br />';
	// bei mehr als 5 Instanzen Suchfeld anzeigen
	if(count($dbs) > 5) {
		$tmpl->content .= '
	Datenbank suchen: 
	<input type="text" class="text" onkeyup="instfilter(this.value)" />
	<br /><br /><br />';
	}
	$tmpl->content .= '
	<div class="center" id="instances" style="line-height:30px">';
	foreach($dbs as $key=>$name) {
		$tmpl->content .= '
		<a href="index.php?inst='.$key.'" class="big">'.htmlspecialchars($name, ENT_COMPAT, 'UTF-8').'</a>';
	}
	$tmpl->content .= '
	</div>
	<br /><br />
	';
}
$tmpl->output();
die();


?>