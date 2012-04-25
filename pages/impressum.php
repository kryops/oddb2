<?php

/**
 * pages/impressum.php
 * Impressum
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

$tmpl = new template_login;
$tmpl->content = '
		<div class="hl1">
			Impressum
		</div>
		<br />
		'.IMPRESSUM.'
		<br />
		<a href="index.php" class="small hint">zur&uuml;ck</a>
		<br /><br />';
$tmpl->output();
die();

?>