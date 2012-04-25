<?php
error_reporting(E_ALL);

/**
 * configset.php
 * XML-Configset für den OD Helper und das db.moz.plugin
 */

// Sicherheitskonstante setzen
define('ODDB', true);

// Konfigurationsdatei einbinden
include './globalconfig.php';

// HTTP-Cache-Header ausgeben (Caching der Seite verhindern)
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);

// XML-Header
header('Content-Type:text/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
<odh:odhelper xmlns="http://unzureichende.info/odhelp/ns/configset/2007" xmlns:odh="http://unzureichende.info/odhelp/ns/api">
  <odh:head>
    <odh:auth>true</odh:auth>
    <odh:status>200</odh:status>
    <odh:version>1.0</odh:version>

  </odh:head>
  <odh:data>
    <configName><![CDATA[ODDB]]></configName>
	<includeBeta>false</includeBeta>
    <parser>
	   <parserUseExt>false</parserUseExt>
    </parser>
    <toolbar>
      <od>
        <tbIntToolUserNick>true</tbIntToolUserNick>
        <tbIntToolSysUri><![CDATA[http://%h/game/?op=system&sys=%s]]></tbIntToolSysUri>
        <tbIntToolPlanUri><![CDATA[http://%h/game/?op=orbit&index=%s]]></tbIntToolPlanUri>
        <tbIntToolShopUri><![CDATA[http://%h/game/?op=shop2&welch=%s]]></tbIntToolShopUri>
        <tbIntToolUserUri><![CDATA[http://%h/game/?op=usershow&welch=%s]]></tbIntToolUserUri>
        <tbIntToolUserNickUri><![CDATA[http://%h/game/?op=score&findstring=%s&sub=g]]></tbIntToolUserNickUri>
      </od>
      <external>
        <tbExtToolSysUri><![CDATA['.ADDR.'index.php?p=show_system&id=%s]]></tbExtToolSysUri>
		<tbExtToolPlanUri><![CDATA['.ADDR.'index.php?p=show_planet&id=%s]]></tbExtToolPlanUri>
		<tbExtToolPlanNameUri><![CDATA['.ADDR.'index.php?p=show_planet&id=%s]]></tbExtToolPlanNameUri>
		<tbExtToolUserUri><![CDATA['.ADDR.'index.php?p=show_player&id=%s]]></tbExtToolUserUri>
		<tbExtToolUserNickUri><![CDATA['.ADDR.'index.php?p=show_player&id=%s]]></tbExtToolUserNickUri>
		<tbExtToolAllyUri><![CDATA['.ADDR.'index.php?p=show_ally&id=%s]]></tbExtToolAllyUri>
		<tbExtToolAllyNickUri><![CDATA['.ADDR.'index.php?p=show_ally&id=%s]]></tbExtToolAllyNickUri>
      </external>
    </toolbar>
    <api>
		<extDisableFow>true</extDisableFow>
		<extFowApiUri><![CDATA['.ADDR.'index.php?p=fow&id=%s]]></extFowApiUri>
    </api>

    <irc>
      <extFixIrc>false</extFixIrc>
      <extFixIrcHost>irc.de.quakenet.org</extFixIrcHost>
      <extFixIrcPort>6667</extFixIrcPort>
      <extFixIrcChan><![CDATA[#omega-day]]></extFixIrcChan>
    </irc>
  </odh:data>

</odh:odhelper>';

?>