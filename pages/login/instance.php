<?php

/**
 * pages/login/instance.php
 * Liste der Instanzen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// API abfangen
if(isset($_GET['p']) AND $_GET['p'] == 'api') {
	include './pages/api.php';
	ODDBApi::outputError('API-Key ungültig!');
}
// AJAX abfangen
else if(isset($_GET['ajax'])) {
	$tmpl = new template;
	$tmpl->error = '
Du bist nicht mehr eingeloggt!
<br /><br />
<a href="index.php">neu einloggen</a>
';
}
// FoW abfangen
else if(isset($_GET['p']) AND $_GET['p'] == 'fow') {
	diefow();
}
// noch keine Instanz angelegt
else if(count($dbs) == 0) {
	$tmpl = new template_login;
	$tmpl->content = '
	<div class="hl1">
		ODDB V'.VERSION.' - '.ODWORLD.'
	</div>
	<br /><br />
	<div class="center" id="instances" style="line-height:30px">
		Die ODDB wurde frisch installiert, es sind noch keine Datenbanken angelegt.
		<br>
		<a href="admin/index.php" class="bold">&raquo; zum Adminbereich</a>
	</div>
	<br /><br />
	';
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