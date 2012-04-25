<?php

/**
 * common/pagebar.php
 * Seitennavigation
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class pagebar {
	/**
	 * Seitennavigation erzeugen
	 * @param $tcount int Gesamtzahl der Einträge
	 * @param $limit int Anzahl der Einträge pro Seite @default 100
	 * @param $querystring string GET-Parameter der Seite @default Server-Querystring
	 *
	 * @return string HTML der Seitennavigation
	 */
	public static function generate($tcount, $limit=100, $querystring=false) {
		if($querystring === false) {
			$querystring = $_SERVER['QUERY_STRING'];
		}
		
		// Gesamt-Seitenzahl berechnen
		$num_pages = ceil($tcount/$limit);
		if($num_pages == 0) {
			$num_pages = 1;
		}
		
		// aktuelle Seite ermitteln
		$cur_page = (isset($_GET['page']) AND is_numeric($_GET['page']) AND (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;
		if($cur_page > $num_pages) {
			$cur_page = $num_pages;
		}
		
		$offset = ($cur_page-1)*$limit;
		
		// Querystring modifizieren
		$querystring = preg_replace('/&page=(\d+)/', '', $querystring);
		$querystring = str_replace('&switch', '', $querystring);
		$querystring = str_replace('&', '&amp;', $querystring);
		
		// HTML erzeugen
		$pagebar = '';
		if($num_pages > 1) {
			$pagebar .= '
	<div class="pagebar">';
			if($cur_page > 1) $pagebar .= '
		<a class="link contextmenu" data-link="index.php?'.$querystring.'"><b>&laquo;&laquo;</b></a> &nbsp; 
		<a class="link contextmenu" data-link="index.php?'.$querystring.'&amp;page='.($cur_page-1).'"><b>&laquo;</b></a> &nbsp;';
			if($cur_page > 5) $pagebar .= '
		<b>...</b> &nbsp;';
			for($i=$cur_page-4;$i<=$cur_page+4;$i++) {
				if($i == $cur_page) {
					$pagebar .= '
		<a class="link contextmenu active" data-link="index.php?'.$querystring.'&amp;page='.$i.'">'.$i.'</a> &nbsp;';
				}
				else if($i > 0 AND $i <= $num_pages) {
					$pagebar .= '
		<a class="link contextmenu" data-link="index.php?'.$querystring.'&amp;page='.$i.'">'.$i.'</a> &nbsp;';
				}
			}
			if($num_pages-$cur_page >= 5) $pagebar .= '
		<b>...</b> &nbsp;';
			if($cur_page < $num_pages) {
				$pagebar .= '
		<a class="link contextmenu" data-link="index.php?'.$querystring.'&amp;page='.($cur_page+1).'">&raquo;</a> &nbsp;
		<a class="link contextmenu" data-link="index.php?'.$querystring.'&amp;page='.$num_pages.'">&raquo;&raquo;</a> &nbsp;';
			}
			$pagebar .= '
	</div>';
		}
		
		return $pagebar;
	}
	
	/**
	 * berechnet das MySQL-LIMIT-Offset bei der Seitennavigation
	 * @param $tcount int Gesamtzahl der Einträge
	 * @param $limit int Anzahl der Einträge pro Seite @default 100
	 *
	 * @return int Offset
	 */
	public static function offset($tcount, $limit=100) {
		// Gesamt-Seitenzahl berechnen
		$num_pages = ceil($tcount/$limit);
		if($num_pages == 0) {
			$num_pages = 1;
		}
		
		// aktuelle Seite ermitteln
		$cur_page = (isset($_GET['page']) AND is_numeric($_GET['page']) AND (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;
		if($cur_page > $num_pages) {
			$cur_page = $num_pages;
		}
		
		$offset = ($cur_page-1)*$limit;
		
		return $offset;
	}
}

?>