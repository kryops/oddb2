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
  `allianzenMember` smallint(5) unsigned NOT NULL DEFAULT 0,
  `allianzenUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`allianzenID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",
	
"CREATE TABLE IF NOT EXISTS `".$globprefix."ban` (
  `banIP` int(10) unsigned NOT NULL,
  `banTries` smallint(5) unsigned NOT NULL DEFAULT 0,
  `banTime` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=MEMORY DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `oddbcronjobs` (
  `cronjobsID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cronjobsTime` int(10) unsigned NOT NULL,
  `cronjobsNumber` tinyint(3) unsigned NOT NULL,
  `cronjobsText` text NOT NULL,
  PRIMARY KEY (`cronjobsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",
		
"CREATE TABLE IF NOT EXISTS `".$globprefix."flooding` (
  `flooding_playerID` int(10) unsigned NOT NULL,
  `floodingTime` int(10) unsigned NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$globprefix."player` (
  `playerID` int(10) unsigned NOT NULL,
  `playerName` varchar(50) NOT NULL,
  `player_allianzenID` int(10) unsigned NOT NULL DEFAULT 0,
  `playerRasse` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `playerPlaneten` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `playerImppunkte` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `playerGesamtpunkte` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `playerKriegspunkte` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `playerQuestpunkte` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `playerHandelspunkte` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `playerUmod` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `playerFA` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `playerDeleted` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `playerGesinnung` mediumint(9) DEFAULT NULL,
  `playerActivity` int(10) unsigned NOT NULL DEFAULT 0,
  `playerUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`playerID`),
  KEY `player_allianzenID` (`player_allianzenID`),
  KEY `playerName` (`playerName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$globprefix."player_allyhistory` (
  `allyhistory_playerID` int(10) unsigned NOT NULL DEFAULT 0,
  `allyhistory_allianzenID` int(10) unsigned NOT NULL DEFAULT 0,
  `allyhistoryTime` int(10) unsigned NOT NULL DEFAULT 0,
  `allyhistoryLastAlly` int(10) unsigned DEFAULT NULL,
  `allyhistoryFinal` tinyint(1) unsigned NOT NULL DEFAULT 0,
  KEY `allyhistory_playerID` (`allyhistory_playerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$globprefix."forschung` (
  `forschungID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forschungKategorie` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `forschungName` varchar(255) NOT NULL,
  `forschungPfad` varchar(255) NOT NULL,
  PRIMARY KEY (`forschungID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8",

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
  `statusDBAllianz` int(10) unsigned NOT NULL DEFAULT 0,
  `status_allianzenID` int(10) unsigned NOT NULL DEFAULT 0,
  `statusStatus` tinyint(3) unsigned NOT NULL DEFAULT 0,
  KEY `statusDBAllianz` (`statusDBAllianz`,`status_allianzenID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."favoriten` (
  `favoritenID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `favoriten_playerID` int(10) unsigned NOT NULL DEFAULT 0,
  `favoritenLink` text NOT NULL,
  `favoritenName` varchar(255) NOT NULL,
  `favoritenTyp` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`favoritenID`),
  KEY `favoriten_playerID` (`favoriten_playerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."galaxien` (
  `galaxienID` smallint(5) unsigned NOT NULL,
  `galaxienSysteme` smallint(5) unsigned NOT NULL DEFAULT 0,
  `galaxienSysScanned` smallint(5) unsigned NOT NULL DEFAULT 0,
  `galaxienGate` int(10) unsigned NOT NULL DEFAULT 0,
  `galaxienGateSys` int(10) unsigned NOT NULL DEFAULT 0,
  `galaxienGateX` smallint(6) NOT NULL DEFAULT 0,
  `galaxienGateY` smallint(6) NOT NULL DEFAULT 0,
  `galaxienGateZ` smallint(6) NOT NULL DEFAULT 0,
  `galaxienGatePos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`galaxienID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."invasionen` (
  `invasionenID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invasionenTime` int(10) unsigned NOT NULL DEFAULT 0,
  `invasionen_planetenID` int(10) unsigned NOT NULL DEFAULT 0,
  `invasionen_systemeID` int(10) unsigned NOT NULL DEFAULT 0,
  `invasionen_playerID` int(10) NOT NULL DEFAULT 0,
  `invasionenTyp` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `invasionenFremd` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `invasionenAggressor` int(11) NOT NULL DEFAULT 0,
  `invasionenEnde` int(10) unsigned NOT NULL DEFAULT 0,
  `invasionenSchiffe` smallint(6) NOT NULL DEFAULT 0,
  `invasionenAbbrecher` int(10) unsigned NOT NULL DEFAULT 0,
  `invasionenFreundlich` tinyint(1) NOT NULL DEFAULT 0,
  `invasionenKommentar` text NOT NULL,
  `invasionenOpen` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`invasionenID`),
  KEY `invasionenOpen` (`invasionenOpen`),
  KEY `invasionen_planetenID` (`invasionen_planetenID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."invasionen_archiv` (
  `archivID` int(10) unsigned NOT NULL,
  `archivTime` int(10) unsigned NOT NULL DEFAULT 0,
  `archiv_planetenID` int(10) unsigned NOT NULL DEFAULT 0,
  `archiv_systemeID` int(10) unsigned NOT NULL DEFAULT 0,
  `archiv_playerID` int(10) NOT NULL DEFAULT 0,
  `archivTyp` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `archivFremd` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `archivAggressor` int(11) NOT NULL DEFAULT 0,
  `archivEnde` int(10) unsigned NOT NULL DEFAULT 0,
  `archivSchiffe` smallint(6) NOT NULL DEFAULT 0,
  `archivKommentar` text NOT NULL,
  PRIMARY KEY (`archivID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."invasionen_log` (
  `invalog_invasionenID` int(10) unsigned NOT NULL DEFAULT 0,
  `invalogTime` int(10) unsigned NOT NULL DEFAULT 0,
  `invalog_playerID` int(10) unsigned NOT NULL DEFAULT 0,
  `invalogText` text NOT NULL,
  KEY `invalog_invasionenID` (`invalog_invasionenID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."log` (
  `logID` int(11) NOT NULL AUTO_INCREMENT,
  `logTime` int(10) unsigned NOT NULL DEFAULT 0,
  `log_playerID` int(10) unsigned NOT NULL DEFAULT 0,
  `logType` smallint(5) unsigned NOT NULL DEFAULT 0,
  `logText` text NOT NULL,
  `logIP` varchar(20) NOT NULL,
  PRIMARY KEY (`logID`),
  KEY `log_playerID` (`log_playerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."myrigates` (
  `myrigates_planetenID` int(10) unsigned NOT NULL DEFAULT 0,
  `myrigates_galaxienID` smallint(5) unsigned NOT NULL DEFAULT 0,
  `myrigatesSprung` int(10) unsigned NOT NULL DEFAULT 0,
  `myrigatesSprungFeind` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`myrigates_planetenID`),
  KEY `myrigates_galaxienID` (`myrigates_galaxienID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."planeten` (
  `planetenID` int(10) unsigned NOT NULL,
  `planeten_systemeID` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenPosition` tinyint(4) NOT NULL DEFAULT 0,
  `planeten_playerID` int(11) NOT NULL DEFAULT 0,
  `planetenName` varchar(100) NOT NULL,
  `planetenUpdateOverview` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenUnscannbar` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenTyp` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenGroesse` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenKategorie` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenGebPlanet` varchar(255) NOT NULL,
  `planetenGebOrbit` varchar(100) NOT NULL,
  `planetenOrbiter` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `planetenMyrigate` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenRiss` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenGateEntf` mediumint(8) unsigned DEFAULT NULL,
  `planetenRWErz` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenRWWolfram` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenRWKristall` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenRWFluor` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenRPErz` mediumint(9) NOT NULL DEFAULT 0,
  `planetenRPMetall` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `planetenRPWolfram` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `planetenRPKristall` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `planetenRPFluor` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `planetenRMErz` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenRMMetall` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenRMWolfram` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenRMKristall` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenRMFluor` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenRPGesamt` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenRMGesamt` bigint(20) unsigned NOT NULL DEFAULT 0,
  `planetenForschung` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `planetenIndustrie` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `planetenBevoelkerung` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `planetenRessplani` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `planetenWerft` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `planetenBunker` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `planetenWerftBedarf` varchar(100) NOT NULL,
  `planetenWerftFinish` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenGeraidet` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenGetoxxt` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenKommentar` varchar(255) NOT NULL,
  `planetenKommentarUser` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenKommentarUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenHistory` smallint(5) unsigned NOT NULL DEFAULT 0,
  `planetenMasseninva` int(10) unsigned NOT NULL DEFAULT 0,
  `planetenNatives` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `planetenReserv` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`planetenID`),
  KEY `planeten_systemeID` (`planeten_systemeID`),
  KEY `planeten_playerID` (`planeten_playerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."planeten_schiffe` (
  `schiffe_planetenID` int(10) unsigned NOT NULL,
  `schiffeBergbau` int(11) DEFAULT NULL,
  `schiffeBergbauUpdate` int(10) unsigned NOT NULL,
  `schiffeTerraformer` tinyint(4) DEFAULT NULL,
  `schiffeTerraformerUpdate` int(10) unsigned NOT NULL,
  PRIMARY KEY (`schiffe_planetenID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."planeten_history` (
  `history_planetenID` int(10) unsigned NOT NULL DEFAULT 0,
  `history_playerID` int(11) NOT NULL DEFAULT 0,
  `historyLast` int(11) NOT NULL DEFAULT 0,
  `historyTime` int(10) unsigned NOT NULL DEFAULT 0,
  KEY `history_planetenID` (`history_planetenID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."register` (
  `registerID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `register_playerID` int(10) unsigned NOT NULL DEFAULT 0,
  `register_allianzenID` int(10) unsigned NOT NULL DEFAULT 0,
  `registerProtectedAllies` text NOT NULL,
  `registerProtectedGalas` text NOT NULL,
  `registerAllyRechte` text NOT NULL,
  PRIMARY KEY (`registerID`),
  KEY `register_allianzenID` (`register_allianzenID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

"CREATE TABLE IF NOT EXISTS `".$prefix."routen` (
  `routenID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `routenDate` int(10) unsigned NOT NULL DEFAULT 0,
  `routen_playerID` int(10) unsigned NOT NULL DEFAULT 0,
  `routen_galaxienID` smallint(5) unsigned NOT NULL DEFAULT 0,
  `routenName` varchar(100) NOT NULL,
  `routenListe` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `routenTyp` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `routenEdit` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `routenFinished` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `routenData` text NOT NULL,
  `routenCount` smallint(5) unsigned NOT NULL DEFAULT 0,
  `routenMarker` int(10) unsigned NOT NULL DEFAULT 0,
  `routenAntrieb` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`routenID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",

"CREATE TABLE IF NOT EXISTS `".$prefix."systeme` (
  `systemeID` int(10) unsigned NOT NULL,
  `systeme_galaxienID` smallint(5) unsigned NOT NULL DEFAULT 0,
  `systemeName` varchar(100) NOT NULL,
  `systemeX` smallint(6) NOT NULL DEFAULT 0,
  `systemeY` smallint(6) NOT NULL DEFAULT 0,
  `systemeZ` smallint(6) NOT NULL DEFAULT 0,
  `systemeUpdateHidden` int(10) unsigned NOT NULL DEFAULT 0,
  `systemeUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `systemeAllianzen` text NOT NULL,
  `systemeGateEntf` mediumint(8) unsigned DEFAULT NULL,
  `systemeScanReserv` int(10) unsigned NOT NULL DEFAULT 0,
  `systemeReservUser` varchar(50) NOT NULL,
  PRIMARY KEY (`systemeID`),
  KEY `systeme_galaxienID` (`systeme_galaxienID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",

"CREATE TABLE IF NOT EXISTS `".$prefix."user` (
  `user_playerID` int(10) unsigned NOT NULL,
  `user_playerName` varchar(50) NOT NULL,
  `userPassword` char(32) NOT NULL,
  `userPwSend` int(10) unsigned NOT NULL DEFAULT 0,
  `userEmail` varchar(255) NOT NULL,
  `user_allianzenID` int(10) unsigned NOT NULL DEFAULT 0,
  `userRechtelevel` smallint(5) unsigned NOT NULL DEFAULT 0,
  `userRechte` text NOT NULL,
  `userBanned` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `userSettings` text NOT NULL,
  `userODSettings` varchar(6) NOT NULL DEFAULT 0,
  `userSitterTo` text NOT NULL,
  `userSitterFrom` text NOT NULL,
  `userODSettingsUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `userSitterUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `userEinnahmen` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `userKonto` int(11) NOT NULL DEFAULT 0,
  `userFP` int(10) unsigned NOT NULL DEFAULT 0,
  `userGeldUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `userSchiffe` smallint(5) unsigned NOT NULL DEFAULT 0,
  `userFlottensteuer` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `userKop` smallint(5) unsigned NOT NULL DEFAULT 0,
  `userKopMax` smallint(5) unsigned NOT NULL DEFAULT 0,
  `userPKop` smallint(5) unsigned NOT NULL DEFAULT 0,
  `userPKopMax` smallint(5) unsigned NOT NULL DEFAULT 0,
  `userFlottenUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `userICQ` int(10) unsigned NOT NULL DEFAULT 0,
  `userSitterpflicht` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `userOverviewUpdate` int(10) unsigned NOT NULL DEFAULT 0,
  `userDBPunkte` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `userSysScanned` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `userSysUpdated` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `userPlanScanned` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `userPlanUpdated` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `userOnlineDB` int(10) unsigned NOT NULL DEFAULT 0,
  `userOnlinePlugin` int(10) unsigned NOT NULL DEFAULT 0,
  `userApiKey` char(32) NOT NULL,
  `userForschung` TEXT NOT NULL,
  `userODServer` varchar(100) NOT NULL,
  PRIMARY KEY (`user_playerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8"
);


// Instanz löschen
$tables_del = array(
	"DROP TABLE IF EXISTS `".$prefix."allianzen_status`, `".$prefix."favoriten`, `".$prefix."galaxien`, `".$prefix."invasionen`, `".$prefix."invasionen_archiv`, `".$prefix."invasionen_log`, `".$prefix."log`, `".$prefix."myrigates`, `".$prefix."planeten`, `".$prefix."planeten_schiffe`, `".$prefix."planeten_history`, `".$prefix."register`, `".$prefix."routen`, `".$prefix."systeme`, `".$prefix."user`"
);
