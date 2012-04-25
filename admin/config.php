<?php

/**
 * admin/config.php
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

$config['adminpw'] = 'fc16bcd5a418882563a2fc2ec532639e';	// Admin-Passwort (MD5-Hash)
// MD5-Hash z.B. hier generieren: http://www.functions-online.com/md5.html

// !!! FÜR DEN IP-BAN MUSS DAS CACHING AKTIVIERT SEIN !!!
$config['ipban'] = 3;	// nach wie vielen Fehlversuchen soll eine IP gesperrt werden? (0 für nie)
$config['ipban_time'] = 15;	// wie viele Minuten lang soll eine IP gebannt bleiben?
$config['caching'] = 0;	// false, 1 - APC, 2 - memcached


?>