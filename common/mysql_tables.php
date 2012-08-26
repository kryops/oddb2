<?php
/**
 * admin/mysql_tables.php
 * MySQL-Tabellen
 */

// Sicherheitsabfrage
if(!defined('ODDBADMIN')) die('unerlaubter Zugriff!');

// Variablen notfalls setzen
if(!isset($prefix)) {
	$prefix = '';
}
if(!isset($globprefix)) {
	$globprefix = '';
}


// Array mit allen Instanztabellen
$instancetables = array(
	'allianzen_status',
	'favoriten',
	'galaxien',
	'invasionen',
	'invasionen_archiv',
	'invasionen_log',
	'log',
	'myrigates',
	'planeten',
	'planeten_history',
	'planeten_schiffe',
	'register',
	'routen',
	'systeme',
	'user'
);



// globale Tabellen
$globtables_add = array(
"CREATE TABLE IF NOT EXISTS `".$globprefix."allianzen` (
  `allianzenID` int(10) unsigned NOT NULL,
  `allianzenTag` varchar(50) NOT NULL,
  `allianzenName` varchar(255) NOT NULL,
  `allianzenMember` smallint(5) unsigned NOT NULL,
  `allianzenUpdate` int(10) unsigned NOT NULL,
  PRIMARY KEY (`allianzenID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",
	
"CREATE TABLE IF NOT EXISTS `".$globprefix."ban` (
  `banIP` int(10) unsigned NOT NULL,
  `banTries` smallint(5) unsigned NOT NULL,
  `banTime` int(10) unsigned NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$globprefix."flooding` (
  `flooding_playerID` int(10) unsigned NOT NULL,
  `floodingTime` int(10) unsigned NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$globprefix."player` (
  `playerID` int(10) unsigned NOT NULL,
  `playerName` varchar(50) NOT NULL,
  `player_allianzenID` int(10) unsigned NOT NULL,
  `playerRasse` tinyint(3) unsigned NOT NULL,
  `playerPlaneten` tinyint(3) unsigned NOT NULL,
  `playerImppunkte` mediumint(8) unsigned NOT NULL,
  `playerUmod` tinyint(1) unsigned NOT NULL,
  `playerFA` tinyint(1) unsigned NOT NULL,
  `playerDeleted` tinyint(1) unsigned NOT NULL,
  `playerGesinnung` mediumint(9) DEFAULT NULL,
  `playerActivity` int(10) unsigned NOT NULL,
  `playerUpdate` int(10) unsigned NOT NULL,
  PRIMARY KEY (`playerID`),
  KEY `player_allianzenID` (`player_allianzenID`),
  KEY `playerName` (`playerName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$globprefix."player_allyhistory` (
  `allyhistory_playerID` int(10) unsigned NOT NULL,
  `allyhistory_allianzenID` int(10) unsigned NOT NULL,
  `allyhistoryTime` int(10) unsigned NOT NULL,
  `allyhistoryLastAlly` int(10) unsigned DEFAULT NULL,
  `allyhistoryFinal` tinyint(1) unsigned NOT NULL,
  KEY `allyhistory_playerID` (`allyhistory_playerID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"INSERT INTO
	".$globprefix."player
SET
	playerID = 1,
	playerName = 'X',
	playerRasse = 10",

"INSERT INTO
	".$globprefix."player
SET
	playerID = 2,
	playerName = 'Rebellion',
	playerRasse = 10"

);


// Instanz-Tabellen

$tables_add = array(
"CREATE TABLE IF NOT EXISTS `".$prefix."allianzen_status` (
  `statusDBAllianz` int(10) unsigned NOT NULL,
  `status_allianzenID` int(10) unsigned NOT NULL,
  `statusStatus` tinyint(3) unsigned NOT NULL,
  KEY `statusDBAllianz` (`statusDBAllianz`,`status_allianzenID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."favoriten` (
  `favoritenID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `favoriten_playerID` int(10) unsigned NOT NULL,
  `favoritenLink` text NOT NULL,
  `favoritenName` varchar(255) NOT NULL,
  `favoritenTyp` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`favoritenID`),
  KEY `favoriten_playerID` (`favoriten_playerID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."galaxien` (
  `galaxienID` smallint(5) unsigned NOT NULL,
  `galaxienSysteme` smallint(5) unsigned NOT NULL,
  `galaxienSysScanned` smallint(5) unsigned NOT NULL,
  `galaxienGate` int(10) unsigned NOT NULL,
  `galaxienGateSys` int(10) unsigned NOT NULL,
  `galaxienGateX` smallint(6) NOT NULL,
  `galaxienGateY` smallint(6) NOT NULL,
  `galaxienGateZ` smallint(6) NOT NULL,
  `galaxienGatePos` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`galaxienID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."invasionen` (
  `invasionenID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invasionenTime` int(10) unsigned NOT NULL,
  `invasionen_planetenID` int(10) unsigned NOT NULL,
  `invasionen_systemeID` int(10) unsigned NOT NULL,
  `invasionen_playerID` int(10) NOT NULL,
  `invasionenTyp` tinyint(3) unsigned NOT NULL,
  `invasionenFremd` tinyint(1) unsigned NOT NULL,
  `invasionenAggressor` int(11) NOT NULL,
  `invasionenEnde` int(10) unsigned NOT NULL,
  `invasionenSchiffe` smallint(6) NOT NULL,
  `invasionenAbbrecher` int(10) unsigned NOT NULL,
  `invasionenFreundlich` tinyint(1) NOT NULL,
  `invasionenKommentar` text NOT NULL,
  `invasionenOpen` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`invasionenID`),
  KEY `invasionenOpen` (`invasionenOpen`),
  KEY `invasionen_planetenID` (`invasionen_planetenID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."invasionen_archiv` (
  `archivID` int(10) unsigned NOT NULL,
  `archivTime` int(10) unsigned NOT NULL,
  `archiv_planetenID` int(10) unsigned NOT NULL,
  `archiv_systemeID` int(10) unsigned NOT NULL,
  `archiv_playerID` int(10) NOT NULL,
  `archivTyp` tinyint(3) unsigned NOT NULL,
  `archivFremd` tinyint(1) unsigned NOT NULL,
  `archivAggressor` int(11) NOT NULL,
  `archivEnde` int(10) unsigned NOT NULL,
  `archivSchiffe` smallint(6) NOT NULL,
  `archivKommentar` text NOT NULL,
  PRIMARY KEY (`archivID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."invasionen_log` (
  `invalog_invasionenID` int(10) unsigned NOT NULL,
  `invalogTime` int(10) unsigned NOT NULL,
  `invalog_playerID` int(10) unsigned NOT NULL,
  `invalogText` text NOT NULL,
  KEY `invalog_invasionenID` (`invalog_invasionenID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."log` (
  `logID` int(11) NOT NULL AUTO_INCREMENT,
  `logTime` int(10) unsigned NOT NULL,
  `log_playerID` int(10) unsigned NOT NULL,
  `logType` smallint(5) unsigned NOT NULL,
  `logText` text NOT NULL,
  `logIP` int(10) unsigned NOT NULL,
  PRIMARY KEY (`logID`),
  KEY `log_playerID` (`log_playerID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."myrigates` (
  `myrigates_planetenID` int(10) unsigned NOT NULL,
  `myrigates_galaxienID` smallint(5) unsigned NOT NULL,
  `myrigatesSprung` int(10) unsigned NOT NULL,
  `myrigatesSprungFeind` tinyint(1) NOT NULL,
  PRIMARY KEY (`myrigates_planetenID`),
  KEY `myrigates_galaxienID` (`myrigates_galaxienID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."planeten` (
  `planetenID` int(10) unsigned NOT NULL,
  `planeten_systemeID` int(10) unsigned NOT NULL,
  `planetenPosition` tinyint(4) NOT NULL,
  `planeten_playerID` int(11) NOT NULL,
  `planetenName` varchar(100) NOT NULL,
  `planetenUpdateOverview` int(10) unsigned NOT NULL,
  `planetenUpdate` int(10) unsigned NOT NULL,
  `planetenUnscannbar` int(10) unsigned NOT NULL,
  `planetenTyp` smallint(5) unsigned NOT NULL,
  `planetenGroesse` smallint(5) unsigned NOT NULL,
  `planetenKategorie` smallint(5) unsigned NOT NULL,
  `planetenGebPlanet` varchar(255) NOT NULL,
  `planetenGebOrbit` varchar(100) NOT NULL,
  `planetenOrbiter` tinyint(3) unsigned NOT NULL,
  `planetenMyrigate` int(10) unsigned NOT NULL,
  `planetenRiss` int(10) unsigned NOT NULL,
  `planetenGateEntf` mediumint(8) unsigned DEFAULT NULL,
  `planetenRWErz` smallint(5) unsigned NOT NULL,
  `planetenRWWolfram` smallint(5) unsigned NOT NULL,
  `planetenRWKristall` smallint(5) unsigned NOT NULL,
  `planetenRWFluor` smallint(5) unsigned NOT NULL,
  `planetenRPErz` mediumint(8) unsigned NOT NULL,
  `planetenRPMetall` mediumint(8) unsigned NOT NULL,
  `planetenRPWolfram` mediumint(8) unsigned NOT NULL,
  `planetenRPKristall` mediumint(8) unsigned NOT NULL,
  `planetenRPFluor` mediumint(8) unsigned NOT NULL,
  `planetenRMErz` int(10) unsigned NOT NULL,
  `planetenRMMetall` int(10) unsigned NOT NULL,
  `planetenRMWolfram` int(10) unsigned NOT NULL,
  `planetenRMKristall` int(10) unsigned NOT NULL,
  `planetenRMFluor` int(10) unsigned NOT NULL,
  `planetenRPGesamt` int(10) unsigned NOT NULL,
  `planetenRMGesamt` bigint(20) unsigned NOT NULL,
  `planetenForschung` mediumint(8) unsigned NOT NULL,
  `planetenIndustrie` mediumint(8) unsigned NOT NULL,
  `planetenBevoelkerung` mediumint(8) unsigned NOT NULL,
  `planetenRessplani` tinyint(1) unsigned NOT NULL,
  `planetenWerft` tinyint(1) unsigned NOT NULL,
  `planetenBunker` tinyint(1) unsigned NOT NULL,
  `planetenWerftBedarf` varchar(100) NOT NULL,
  `planetenWerftFinish` int(10) unsigned NOT NULL,
  `planetenGeraidet` int(10) unsigned NOT NULL,
  `planetenGetoxxt` int(10) unsigned NOT NULL,
  `planetenKommentar` varchar(255) NOT NULL,
  `planetenKommentarUser` int(10) unsigned NOT NULL,
  `planetenKommentarUpdate` int(10) unsigned NOT NULL,
  `planetenHistory` smallint(5) unsigned NOT NULL,
  `planetenMasseninva` int(10) unsigned NOT NULL,
  `planetenNatives` tinyint(3) unsigned NOT NULL,
  `planetenReserv` int(10) unsigned NOT NULL,
  PRIMARY KEY (`planetenID`),
  KEY `planeten_systemeID` (`planeten_systemeID`),
  KEY `planeten_playerID` (`planeten_playerID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."planeten_schiffe` (
  `schiffe_planetenID` int(10) unsigned NOT NULL,
  `schiffeBergbau` int(11) DEFAULT NULL,
  `schiffeBergbauUpdate` int(10) unsigned NOT NULL,
  `schiffeTerraformer` tinyint(4) DEFAULT NULL,
  `schiffeTerraformerUpdate` int(10) unsigned NOT NULL,
  PRIMARY KEY (`schiffe_planetenID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."planeten_history` (
  `history_planetenID` int(10) unsigned NOT NULL,
  `history_playerID` int(11) NOT NULL,
  `historyLast` int(11) NOT NULL,
  `historyTime` int(10) unsigned NOT NULL,
  KEY `history_planetenID` (`history_planetenID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."register` (
  `registerID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `register_playerID` int(10) unsigned NOT NULL,
  `register_allianzenID` int(10) unsigned NOT NULL,
  `registerProtectedAllies` text NOT NULL,
  `registerProtectedGalas` text NOT NULL,
  `registerAllyRechte` text NOT NULL,
  PRIMARY KEY (`registerID`),
  KEY `register_allianzenID` (`register_allianzenID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;",

"CREATE TABLE IF NOT EXISTS `".$prefix."routen` (
  `routenID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `routenDate` int(10) unsigned NOT NULL,
  `routen_playerID` int(10) unsigned NOT NULL,
  `routen_galaxienID` smallint(5) unsigned NOT NULL,
  `routenName` varchar(100) NOT NULL,
  `routenListe` tinyint(1) unsigned NOT NULL,
  `routenTyp` tinyint(3) unsigned NOT NULL,
  `routenEdit` tinyint(1) unsigned NOT NULL,
  `routenFinished` tinyint(1) unsigned NOT NULL,
  `routenData` text NOT NULL,
  `routenCount` smallint(5) unsigned NOT NULL,
  `routenMarker` int(10) unsigned NOT NULL,
  `routenAntrieb` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`routenID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",

"CREATE TABLE IF NOT EXISTS `".$prefix."systeme` (
  `systemeID` int(10) unsigned NOT NULL,
  `systeme_galaxienID` smallint(5) unsigned NOT NULL,
  `systemeName` varchar(100) NOT NULL,
  `systemeX` smallint(6) NOT NULL,
  `systemeY` smallint(6) NOT NULL,
  `systemeZ` smallint(6) NOT NULL,
  `systemeUpdateHidden` int(10) unsigned NOT NULL,
  `systemeUpdate` int(10) unsigned NOT NULL,
  `systemeAllianzen` text NOT NULL,
  `systemeGateEntf` mediumint(8) unsigned DEFAULT NULL,
  `systemeScanReserv` int(10) unsigned NOT NULL,
  `systemeReservUser` varchar(50) NOT NULL,
  PRIMARY KEY (`systemeID`),
  KEY `systeme_galaxienID` (`systeme_galaxienID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."user` (
  `user_playerID` int(10) unsigned NOT NULL,
  `user_playerName` varchar(50) NOT NULL,
  `userPassword` char(32) NOT NULL,
  `userPwSend` int(10) unsigned NOT NULL,
  `userEmail` varchar(255) NOT NULL,
  `user_allianzenID` int(10) unsigned NOT NULL,
  `userRechtelevel` smallint(5) unsigned NOT NULL,
  `userRechte` text NOT NULL,
  `userBanned` tinyint(3) unsigned NOT NULL,
  `userSettings` text NOT NULL,
  `userODSettings` varchar(6) NOT NULL,
  `userSitterTo` text NOT NULL,
  `userSitterFrom` text NOT NULL,
  `userODSettingsUpdate` int(10) unsigned NOT NULL,
  `userSitterUpdate` int(10) unsigned NOT NULL,
  `userEinnahmen` mediumint(8) unsigned NOT NULL,
  `userKonto` int(11) NOT NULL,
  `userFP` int(10) unsigned NOT NULL,
  `userGeldUpdate` int(10) unsigned NOT NULL,
  `userSchiffe` smallint(5) unsigned NOT NULL,
  `userFlottensteuer` mediumint(8) unsigned NOT NULL,
  `userKop` smallint(5) unsigned NOT NULL,
  `userKopMax` smallint(5) unsigned NOT NULL,
  `userPKop` smallint(5) unsigned NOT NULL,
  `userPKopMax` smallint(5) unsigned NOT NULL,
  `userFlottenUpdate` int(10) unsigned NOT NULL,
  `userICQ` int(10) unsigned NOT NULL,
  `userSitterpflicht` tinyint(1) unsigned NOT NULL,
  `userOverviewUpdate` int(10) unsigned NOT NULL,
  `userDBPunkte` mediumint(8) unsigned NOT NULL,
  `userSysScanned` mediumint(8) unsigned NOT NULL,
  `userSysUpdated` mediumint(8) unsigned NOT NULL,
  `userPlanScanned` mediumint(8) unsigned NOT NULL,
  `userPlanUpdated` mediumint(8) unsigned NOT NULL,
  `userOnlineDB` int(10) unsigned NOT NULL,
  `userOnlinePlugin` int(10) unsigned NOT NULL,
  `userApiKey` char(32) NOT NULL,
  PRIMARY KEY (`user_playerID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8"
);


// Instanz löschen
$tables_del = array(
	"DROP TABLE IF EXISTS `".$prefix."allianzen_status`, `".$prefix."favoriten`, `".$prefix."galaxien`, `".$prefix."invasionen`, `".$prefix."invasionen_archiv`, `".$prefix."invasionen_log`, `".$prefix."log`, `".$prefix."myrigates`, `".$prefix."planeten`, `".$prefix."planeten_schiffe`, `".$prefix."planeten_history`, `".$prefix."register`, `".$prefix."routen`, `".$prefix."systeme`, `".$prefix."user`"
);
