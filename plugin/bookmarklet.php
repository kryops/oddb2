<?php
error_reporting(E_ALL);

/**
 * plugin/bookmarklet.php
 * Bookmarklet-Port des ODDB Tools
 * Standalone-Seite
 */

// Sicherheitskonstante setzen
define('ODDB', true);
define('ODDBADMIN', true);

// Konfigurationsdatei einbinden
include '../common.php';
include '../config/global.php';

define('ADDR', $config['addr']);


// dynamisches Authentifizierungs-Token
if(isset($_GET['authTokenVar'])) {
	$authTokenVar = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['authTokenVar']);
}
else {
	$authTokenVar = "ODDBAuthToken";
}


/**
 * Caching 15 Minuten
 */
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

if(DEBUG) {
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);
}
else {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+900) . ' GMT');
}

header('Content-Type: text/javascript; charset=utf-8');

?>

(function() {
	
	/*
	 * aus dem ODDB Tool übernommene Funktionen
	 */
	var oddbtool = {
		
		version: '<?php echo ODDBTOOL; ?>',
		odworld: '<?php echo ODWORLD; ?>',
		
		prefs: {
			url: '<?php echo ADDR; ?>'
		},
		
		/**
		 * Gebäude-Positionen auf dem Sprite
		 */
		geb: {
			0:0,
			'-1':16,
			'-2':32,
			'-3':48,
			'-4':64,
			1000:80,
			1001:96,
			1002:112,
			1003:128,
			1004:144,
			1005:160,
			1006:176,
			1007:192,
			1008:208,
			1009:224,
			1010:240,
			1011:256,
			1012:272,
			1013:288,
			1014:304,
			1015:320,
			1016:336,
			1017:352,
			1018:368,
			1019:384,
			1020:400,
			1021:416,
			1022:432,
			1023:448,
			1024:464,
			1025:480,
			1026:496,
			1027:512,
			1028:528,
			1029:544,
			1030:560,
			1031:576,
			1032:592,
			1033:608,
			1034:624,
			1035:640,
			1036:656,
			1037:672,
			1038:688,
			1039:704,
			1040:720,
			1041:736,
			1042:752,
			1043:992,
			1044:784,
			1045:800,
			1046:816,
			1047:832,
			1048:848,
			1049:864,
			1050:880,
			1051:944,
			1052:896,
			1053:912,
			1054:928,
			1055:976,
			1056:960,
			1057:768
		},
		
		// Rassen-Suchmuster und Klassennamen
		rassen: {
			'Mensch':'mensch',
			'Roc':'tiroc',
			'Trado':'trado',
			'Bera':'bera',
			'Myri':'myri',
			'Lux':'lux',
			'Revisker':'revisker'
		},
		
		// Sonderzeichen im FoW ersetzen
		charmap_search: ['&amp;#92', '&amp;#47', '&amp;', '', '', '', '\\\'', '', '', '', ''],
		charmap_replace: ['\\', '/', '&', '&dagger;', '&bull;', '´', '\'', '˜', '”', '™', '€'],
		
		/**
		 * = PHP-Funktion str_replace()
		 * @param search mixed suchen
		 * @param replace mixed ersetzen
		 * @param subject string, in dem ersetzt werden soll
		 * @param count int optional max. Anzahl der Ersetzungen
		 * @return string mit Ersetzungen
		 */
		str_replace: function(search, replace, subject, count) {
			// Replaces all occurrences of search in haystack with replace  
			// 
			// version: 1006.1915
			// discuss at: http://phpjs.org/functions/str_replace
			// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +   improved by: Gabriel Paderni
			// +   improved by: Philip Peterson
			// +   improved by: Simon Willison (http://simonwillison.net)
			// +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
			// +   bugfixed by: Anton Ongson
			// +      input by: Onno Marsman
			// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +    tweaked by: Onno Marsman
			// +      input by: Brett Zamir (http://brett-zamir.me)
			// +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +   input by: Oleg Eremeev
			// +   improved by: Brett Zamir (http://brett-zamir.me)
			// +   bugfixed by: Oleg Eremeev
			// %          note 1: The count parameter must be passed as a string in order    // %          note 1:  to find a global variable in which the result will be given
			// *     example 1: str_replace(' ', '.', 'Kevin van Zonneveld');
			// *     returns 1: 'Kevin.van.Zonneveld'
			// *     example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name}, lars');
			// *     returns 2: 'hemmo, mars'
			var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0,
			f = [].concat(search),
			r = [].concat(replace),
			s = subject,
			ra = r instanceof Array, sa = s instanceof Array;
			s = [].concat(s);
			
			if (count) {
				this.window[count] = 0;
			}
			for (i=0, sl=s.length; i < sl; i++) {
				if (s[i] === '') {
					continue;
				}
				for (j=0, fl=f.length; j < fl; j++) {
					temp = s[i]+'';
					repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
					s[i] = (temp).split(f[j]).join(repl);
					if (count && s[i] !== temp) {
						this.window[count] += (temp.length-s[i].length)/f[j].length;
					}
				}
			}
			return sa ? s : s[0];
		},
		
		in_array: function(item,arr) {
			for(p=0;p < arr.length;p++) if (item == arr[p]) return true;
			return false;
		},
		
		
		/**
		 * Zahl mit Tausender-Trennern formatieren
		 * @param number int Zahl
		 * @return string formatierte Zahl
		 */
		ressmenge: function(number) {
			number = '' + number;
			number = number.replace(/,/g, '');
			if (number.length > 3) {
				var mod = number.length % 3;
				var output = (mod > 0 ? (number.substring(0,mod)) : '');
				for (i=0 ; i < Math.floor(number.length / 3); i++) {
					if ((mod == 0) && (i == 0)) {
						output += number.substring(mod+ 3 * i, mod + 3 * i + 3);
					}
					else {
						output+= '.' + number.substring(mod + 3 * i, mod + 3 * i + 3);
					}
				}
				return (output);
			}
			else return number;
		},
		
		/**
		 * ist die aktuelle Seite eine OD-Seite?
		 * @param url String
		 * @return bool
		 */
		isODPage: function(url) {
			var url = url.toString();
			if(url.indexOf('omega-day.com/game/') != -1 || url.indexOf('omega-day.de/game/') != -1 || url.indexOf('omegaday.de/game/') != -1) {
				// Betaserver ausschließen
				if(!oddbtool.betaserver && (url.indexOf('beta.o') != -1 || url.indexOf('pre.o') != -1 || url.indexOf('beta2.o') != -1)) {
					return false;
				}
				// www-old ausschließen
				if(url.indexOf('www-old.o') != -1) {
					return false;
				}
				return true;
			}
			return false;
		},
		
		
		/**
		 * Adress-Muster, deren Seiten geparst werden können
		 */
		parserRegex: {
			system: /\?op=system&sys=\d+(|&galx=\d+)$/,
			poview: /\?op=planlist$/,
			planet: /\?op=planet&index=\d+/,
			orbit: /\?op=orbit&index=\d+$/,
			einst: /\?op=settings$/,
			sitter: /\?op=sitter$/,
			floview: /\?op=fleet&tab=5$/,
			floviewbbs: /\?op=fleet&tab=2$/,
			//toxx: /\?op=orbit&index=\d+&bioatack=1$/,
			forschung: /\?op=tech(&tree=(geb|raum|sys))?$/
		},
		
		/**
		 * ist die aktuelle Seite eine Seite, die geparst werden kann?
		 * @param url String
		 * @return bool
		 */
		isParsePage: function(url) {
			
			// geeignete Seite
			for(var i in oddbtool.parserRegex) {
				if(oddbtool.parserRegex[i].exec(url) != null) {
					return true;
				}
			}
			
			// ungeeignete Seite
			return false;
		}
	}
	
	/**
	 * Quelltext einer Seite parsen und abschicken
	 * @param page Seite
	 * @param pdata bereits geparste Daten @see oddbtool.parsePage()
	 * @param manual bool manuell aufgerufen
	 */
	oddbtool.parser = function(page, pdata, manual) {
		
		// Meldung anzeigen
		$('#oddbtoolwin',page).append('<span id="oddbtoolparser">Parse Quelltext... </span>');
		
		// Seite parsen
		if(!pdata) {
			var pdata = oddbtool.parsePage(page, manual);
		}
		
		// Parsen fehlgeschlagen
		if(!pdata) {
			//$('#oddbtoolparser',page).append('<span style="color:red">fehlgeschlagen! (unbekannter Quelltext)</span>');
			return false;
		}
		
		// Debug-Meldung
		console.log('ODDB Bookmarklet: Parsen abgeschlossen, beginne mit dem Senden...')
		console.log(pdata);
		
		var addr = oddbtool.prefs.url+'index.php?p=scan&sp=scan&plugin&version='+oddbtool.version+'&ajax';
		
		// die ODDB zwingen, den Scan anzunehmen
		if(manual) {
			addr += '&force';
		}
		
		// Request absenden
		jQuery.ajax({
			type: 'post',
			url: addr,
			data: pdata,
			success: function(data){
				// Fehler ausgeben
				var error = $(data).find('error').text();
				if(error) {
					
					// Fehler-Ausgaben ändern
					if(error.indexOf('mehr eingeloggt') != -1) {
						error = 'Benutzerdaten falsch. Bitte richte das Bookmarklet neu ein!';
					}
					else if(error.indexOf('ODDB Tool veraltet') != -1) {
						error = 'Eine neue Version ist verf&uuml;gbar. Leere den Cache oder warte 15 Minuten!';
					}
					
					// ausgeben
					$('#oddbtoolparser',page).append('<span style="color:red">'+error+'</span>');
					
					// "trotzdem parsen"-Link
					if(error.indexOf('schon eingescannt') != -1) {
						$('#oddbtoolparser',page).append(' <a href="javascript:void(0)" id="oddbtoolparselink">[trotzdem parsen]</a>');
					}
				}
				else {
					// Content ermitteln
					var content = $(data).find('content').text();
					
					// ausgeben
					$('#oddbtoolparser',page).append('<span style="color:#00ff00">'+content+'</span>');
				}
			},
			error: function(e, msg) {
				// Fehlermeldung
				if(e.status != 0) {
					var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
					content = content.substr(0,200);
					
					$('#oddbtoolparser',page).append('<span style="color:red">Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />Adresse: '+addr+'<br /><br />'+content+'</span>');
				}
			}
		});
	};


	/**
	 * Quelltext einer Seite ermitteln
	 * @param page Seite
	 * @return string Quelltext
	 */
	oddbtool.getQuelltext = function(page) {
		// neu: Quelltext über DOM abgreifen
		var s = $('body', page).html();
		s = s.replace(/&amp;/g, '&');
		return s;
		
	};

	/**
	* Omega-Day-Seite parsen
	* @param page Seite
	* @return object geparstes Objekt
	*/
	oddbtool.parsePage = function(page, manual) {
		// manuell geparst
		if(typeof(manual) == 'undefined') {
			manual = false;
		}
		
		var input = false;
		
		// Ausgabe-Element
		var r = $('#oddbtoolparser',page);
		
		/*
		 neue Parser-Engine
		 */
		
		// DOM-Tree aufbereiten
		var tree = $('html', page);
		var ctree = tree.find('#layout-main');
		
		// Ausgabe-Container
		var out = {};
		
		try {
			// Überprüfung auf richtige OD-Welt
			var world = tree.find('div.world');
			if(world.length && world.html().indexOf('<?php echo ODWORLD; ?>') == -1) {
				throw 'Falsche OD-Welt!';
			}
			
			//
			// Unbekanntes System
			//
			if(ctree.find('td > a[onmouseover*="setter(\'\',\'\',\' \',\' \',\'\')"]').length) {
				out['typ'] = 'sysun';
				
				// Einscanner-Ally ermitteln
				if(tree.find('.statusbar a.alliance').length) {
					out['scannerally'] = tree.find('.statusbar a.alliance').attr('href').replace(/[^\d]/g, '');
				}
				
				// ID und Name
				try {
					out['id'] = ctree.find('#sysid > input').val();
					out['name'] = ctree.find('#sysid').prev('strong').html().replace(/&nbsp;/g, '');
				}
				catch(e) {
					throw 'Konnte Name und ID nicht ermitteln!';
				}
				
				// Planetendaten
				var pl = [];
				var data;
				
				if(ctree.find('table[width="800"] td[valign="top"] > table').length != 7) {
					throw 'Konnte nicht alle Planeten ermitteln!';
				}
				
				ctree.find('table[width="800"] td[valign="top"] > table').each(function(i) {
					// Planet vorhanden
					if($(this).find('td').length) {
						pl[i] = {};
						
						try {
							// ID
							pl[i]['id'] = $(this).find('tr:first-child img').attr('id').replace(/x/, '');
							
							// Typ
							pl[i]['typ'] = $(this).find('tr:first-child > td').css('background-image').replace(/^.*planet(\d+)_s.*$/, '$1');
						}
						catch(e) {
							throw 'Konnte ID und Typ nicht ermitteln ('+i+')';
						}
						
						// Name und Werte
						try {
							data = $(this).find('tr:first-child a').attr('onmouseover');
							pl[i]['name'] = data.replace(/^.*\'<b>(.*)<\/b>:\'\);setter.*$/, '$1');
							
							// kein Genesis -> Werte
							if(data.indexOf('!&lt;\/b&gt;&lt;\/center&gt;') == -1 && data.indexOf('demateriali') == -1) {
								data = data.replace(/','.*$/, '');
								data = data.replace(/http:\/\/[a-zA-Z0-9]*\.*omega/g, '');
								data = data.split('<br>');
								
								pl[i]['erz'] = data[4].replace(/[^\d+]/g, '');
								pl[i]['wolfram'] = data[5].replace(/[^\d+]/g, '');
								pl[i]['kristall'] = data[6].replace(/[^\d+]/g, '');
								pl[i]['fluor'] = data[7].replace(/[^\d+]/g, '');
								pl[i]['bev'] = data[9].replace(/[^\d+]/g, '');
								pl[i]['groesse'] = data[11].replace(/[^\d+]/g, '');
								
								// Workaround: Unbewohnbare Planis Größe 0
								if(pl[i]['groesse'] == '' || pl[i]['groesse'] == '0000') {
									pl[i]['groesse'] = 0;
								}
							}
						}
						catch(e) {
							throw 'Konnte Planetenwerte nicht ermitteln ('+i+')';
						}
						
					}
					// Kein Planet vorhanden
					else {
						pl[i] = '';
					}
				});
				
				// ans Ausgabe-Objekt hängen
				out['pl'] = pl;
			}
			//
			// Bekanntes System
			//
			else if(ctree.find('font#sysid > input').length) {
				out['typ'] = 'system';
				
				// ID und Name
				try {
					out['id'] = ctree.find('#sysid > input').val();
					out['name'] = ctree.find('#sysid').prev('strong').html().replace(/&nbsp;/g, '');
				}
				catch(e) {
					throw 'Konnte Name und ID nicht ermitteln!';
				}
				
				// Planetendaten
				var pl = [];
				var data, p;
				
				if(ctree.find('table[width="800"] td[valign="top"] > table').length != 7) {
					throw 'Konnte nicht alle Planeten ermitteln!';
				}
				
				ctree.find('table[width="800"] td[valign="top"] > table').each(function(i) {
					// Planet vorhanden
					if($(this).find('td').length) {
						pl[i] = {};
						
						try {
							// ID
							pl[i]['id'] = $(this).find('tr:first-child img').attr('id').replace(/x/, '');
							
							// Typ
							pl[i]['typ'] = $(this).find('tr:first-child > td').css('background-image').replace(/^.*planet(\d+)_s.*$/, '$1');
						}
						catch(e) {
							throw 'Konnte ID und Typ nicht ermitteln ('+i+')';
						}
						
						// Name und Werte
						try {
							data = $(this).find('tr:first-child a').attr('onmouseover');
							pl[i]['name'] = data.replace(/^.*\'(.*):\'\);setter.*$/, '$1');
							
							data = data.replace(/','.*$/, '');
							data = data.split('<br>');
							
							pl[i]['bev'] = data[4].replace(/[^\d+]/g, '');
							pl[i]['groesse'] = data[6].replace(/[^\d+]/g, '');
							
							// Workaround: Unbewohnbare Planis Größe 0
							if(pl[i]['groesse'] == '' || pl[i]['groesse'] == '0000') {
								pl[i]['groesse'] = 0;
							}
							
							data = $(data[3]);
							pl[i]['erz'] = data.find('tr:nth-child(2) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
							pl[i]['wolfram'] = data.find('tr:nth-child(4) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
							pl[i]['kristall'] = data.find('tr:nth-child(5) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
							pl[i]['fluor'] = data.find('tr:nth-child(6) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
						}
						catch(e) {
							throw 'Konnte Planetenwerte nicht ermitteln ('+i+')';
						}
						
						// Inhaber
						p = /setter\(\'(.+)\',\'(\d+)\',\'(?:.*)\',\'(.*)\',\'(.*)\'\);kringler/;
						data = p.exec($(this).find('tr:first-child a').attr('onmouseover'));
						if(data == null) throw 'Konnte Inhaber nicht ermitteln! ('+i+')';
						else {
							pl[i]['inhaber'] = data[2];
							pl[i]['allianz'] = data[3];
							
							// nur für Tool: Rasse (zur Sys-Anzeige)
							pl[i]['rasse'] = data[4];
							
							// verschleiert
							if(data[2] == 0) {
								if(data[1].indexOf('Lux') != -1) {
									pl[i]['inhaber'] = -2;
								}
								else if(data[1].indexOf('Altrasse') != -1) {
									pl[i]['inhaber'] = -3;
								}
							}
						}
						
						// Gate
						if($(this).find('tr:last-child img[src*="tor.gif"]').length) {
							pl[i]['gate'] = 1;
						}
						// Myrigate
						p = /<b>(?:Absprungpunkt nach|Jump-off point to) .*<\/b>(?:\s*)\((\d+)\)\'/;
						data = p.exec($(this).find('tr:last-child a').attr('onmouseover'));
						if(data != null) {
							pl[i]['mgate'] = data[1];
						}
						// Riss
						p = /<b> Zielpunkt von (?:.+)<\/b>(?:\s*)\((\d+)\)\'/;
						data = p.exec($(this).find('tr:last-child a').attr('onmouseover'));
						if(data != null) {
							pl[i]['riss'] = data[1];
						}
						
						// Bergbau
						if($(this).find('tr:last-child img[src*="flotte-bergbau.gif"]').length) {
							pl[i]['bb'] = 1;
						}
						
						// Natives
						else if($(this).find('tr:last-child img[src*="flotte_natives.gif"]').length) {
							pl[i]['natives'] = 1;
						}
						
						// Kolo
						else if($(this).find('tr:last-child img[src*="flotte-kolo.gif"]').length) {
							pl[i]['kolo'] = 1;
						}
						
						// Genesis
						else if($(this).find('tr:last-child img[src*="flotte-genesis.gif"]').length) {
							pl[i]['genesis'] = 1;
						}
						
						// Terraformer
						else if($(this).find('tr:last-child img[src*="flotte_terraform.gif"]').length) {
							pl[i]['tf'] = 1;
						}
						
						// Invasion
						else if($(this).find('tr:last-child img[src*="flotte_invasion.gif"]').length) {
							pl[i]['inva'] = 1;
						}
						
						// Besatzung
						else if($(this).find('tr:last-child img[src*="flotte-besatzer.gif"]').length) {
							pl[i]['besatzung'] = 1;
						}
						
						// Orbit leer
						else if($(this).find('tr:last-child img[src*="orbit_blank.gif"]').length) {
							pl[i]['leer'] = 1;
						}
					}
					// Kein Planet vorhanden
					else {
						pl[i] = '';
					}
				});
				
				// ans Ausgabe-Objekt hängen
				out['pl'] = pl;
			}
			//
			// Planetenansicht / Scan
			//
			else if(ctree.find('tr.bewtr').length && ctree.find('a[href^="?op=orbit"]').length) {
				out['typ'] = 'planet';
				
				var data;
				var p;
				
				out['id'] = ctree.find('a[href^="?op=orbit"]').attr('href').replace(/[^\d]/g, '');
				
				// Name
				var name = ctree.find('a[href^="?op=renamer"]').html();
				if(name == null) {
					// Name im Planetenscan
					name = ctree.find('table table tr:first-child > td:last-child > b').html();
					
					if(name == null) {
						throw 'Konnte Name nicht ermitteln!';
					}
				}
				
				out['name'] = name;
				
				
				// Typ
				try {
					out['pltyp'] = ctree.find('td[width="600"][background]').attr('background').replace(/^.*planet(\d+)\..*$/, '$1');
				}
				catch(e) {
					throw 'Konnte Planetentyp nicht ermitteln!';
				}
				
				// verschleierter Inhaber
				try {
					var data = ctree.find('a[href^="?op=orbit"]').parents('tr').next().find('b');
					if(data) {
						out['inhabername'] = data.html();
					}
				}
				catch(e) {
					// nichts unternehmen
				}
				
				// Werte
				data = ctree.find('img[src*="icon_planet_info.gif"]').parent().attr('onmouseover');
				if(data == null) {
					throw 'Konnte Planetenwerte nicht ermitteln!';
				}
				
				data = data.replace(/','.*$/, '');
				data = data.split('<br>');
				
				out['erz'] = data[1].replace(/[^\d]/g, '');
				out['wolfram'] = data[3].replace(/[^\d]/g, '');
				out['kristall'] = data[4].replace(/[^\d]/g, '');
				out['fluor'] = data[5].replace(/[^\d]/g, '');
				out['bev'] = data[7].replace(/[^\d]/g, '');
				out['groesse'] = data[8].replace(/[^\d]/g, '');
				
				// aktuelle Bevölkerung
				out['bevakt'] = out['bev'] * (50 - ctree.find('.bewtr > td[bgcolor="#BBBBBB"]').length) / 50;
				
				try {
					// Ressproduktion
					if(ctree.find('#erzproduktion > font').length) {
						out['erzp'] = ctree.find('#erzproduktion > font').html().replace(/[^\-\d]/g, '');
					}
					else {
						out['erzp'] = ctree.find('#erzproduktion').html().replace(/[^\-\d]/g, '');
					}
					
					out['metallp'] = ctree.find('#metallproduktion').html().replace(/[^\d]/g, '');
					out['wolframp'] = ctree.find('#wolframproduktion').html().replace(/[^\d]/g, '');
					out['kristallp'] = ctree.find('#kristallproduktion').html().replace(/[^\d]/g, '');
					out['fluorp'] = ctree.find('#flourproduktion').html().replace(/[^\d]/g, '');
					
					// Ressvorrat
					out['erzm'] = ctree.find('#erzdais').html().replace(/[^\d]/g, '');
					out['metallm'] = ctree.find('#metallda').html().replace(/[^\d]/g, '');
					out['wolframm'] = ctree.find('#wolframda').html().replace(/[^\d]/g, '');
					out['kristallm'] = ctree.find('#kristallda').html().replace(/[^\d]/g, '');
					out['fluorm'] = ctree.find('#flourda').html().replace(/[^\d]/g, '');
				}
				catch(e) {
					throw 'Konnte Ressproduktion und -vorrat nicht ermitteln!';
				}
				// Forschung und Industrie
				try {
					out['forschung'] = ctree.find('#forprod').parent().parent().prev().find('b').html().replace(/[^\d]/g, '');
					out['industrie'] = ctree.find('#indprod').parent().parent().prev().find('b').html().replace(/[^\d]/g, '');
				}
				catch(e) {
					throw 'Konnte Forschung und Industrie nicht ermitteln!';
				}
				
				// Gebäude
				try {
					// Planetengebäude
					data = ctree.find('td[width="600"][background]');
					for(var i=1; i<=36; i++) {
						out['g'+i] = data.find('img[name="pod'+i+'"]').attr('src').replace(/^.*\/img\/(?:buildings\/|misc\/)*/, '').replace(/blank.gif/, '');
					}
					
					// Orbitgebäude
					data = data.prev();
					for(i=1; i<=12; i++) {
						out['o'+i] = data.find('img[name="wpod'+i+'"]').attr('src').replace(/^.*\/img\/(?:buildings\/|misc\/)*/, '');
					}
				}
				catch(e) {
					throw 'Konnte Gebäude nicht ermitteln!';
				}
				
				// Spieler
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Spieler nicht ermitteln!';
				}
				
				// Geld, Einnahmen und Gesamt-FP
				if(ctree.find('#creditsda').html() != '') {
					try {
						// Vermögen und Steuereinnahmen
						out['konto'] = ctree.find('#creditsda').html().replace(/[^\d\-]/g, '');
						out['steuer'] = ctree.find('img[src*="credits_small.gif"]').last().next().children('b').html().replace(/^(\S*) .*$/, '$1').replace(/[^\d]/g, '');
						
						// Gesamt-FP
						data = ctree.find('img[src*="research_small.gif"]').next().children('b').html();
						// Forscherdrang
						if(data.indexOf('+') != -1) {
							data = data.split('+');
							data[0] = data[0].replace(/[^\d]/g, '');
							data[1] = data[1].replace(/[^\d]/g, '');
							out['fpges'] = parseInt(data[0]) + parseInt(data[1]);
						}
						else {
							out['fpges'] = data.replace(/[^\d]/g, '');
						}
					}
					catch(e) {
						throw 'Konnte Einnahmen und Gesamtforschung nicht ermitteln!';
					}
				}
				
				// Schiffbau
				if(ctree.find('table[width="821"] td:last-child img[src*="ships"]').length) {
					out['schiff'] = ctree.find('input[name="bauzeit"]').val();
				}
			}
			//
			// unscannbarer Planet
			//
			else if(ctree.find("b:contains('Ihre Scanner reichen nicht aus diesen Planeten zu scannen'), b:contains('Your scanners are not strong enough to scan this planet')").length) {
				
				out['typ'] = 'planet';
				out['unscannbar'] = 1;
				
				// ID ermitteln
				out['id'] = ctree.find('a[href^="?op=orbit"]').attr('href').replace(/[^\d]/g, '');
				
			}
			//
			// Planetenübersicht
			//
			else if(ctree.find('a[href="?op=planlist&order=name"]').length) {
				out['typ'] = 'poview';
				
				// Validität überprüfen
				if(!ctree.find('tr[pname]').first().is('tr[pname][pslots][perz][pwolfram][pkristall][pflour][pverz][pvmetall][pvwolfram][pvkristall][pvflour][pgrafik]')) {
					throw 'Planetenübersicht invalid!';
				}
				
				// Inhaber
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Inhaber nicht ermitteln!';
				}
				
				// Planeten auswerten
				out['pl'] = [];
				
				var geb;
				var pl;
				var data2;
				var data3;
				
				// Planetenzeilen durchgehen
				ctree.find('tr[pname]').each(function(index) {
					pl = {};
					
					try {
						// Planeten-ID und System-ID
						pl['id'] = $(this).find('a[href^="?op=planet&index"]').attr('href').replace(/[^\d]/g, '');
						pl['sid'] = $(this).find('a[href^="?op=system&sys"]').attr('href').replace(/[^\d]/g, '');
						
						// Name und Werte
						pl['name'] = $(this).attr('pname');
						pl['gr'] = $(this).attr('pslots');
						
						pl['rw'] = [
							$(this).attr('perz'),
							$(this).attr('pwolfram'),
							$(this).attr('pkristall'),
							$(this).attr('pflour')
						].join('X');
						
						pl['rv'] = [
							$(this).attr('pverz').replace(/[^\d]/g, ''),
							$(this).attr('pvmetall').replace(/[^\d]/g, ''),
							$(this).attr('pvwolfram').replace(/[^\d]/g, ''),
							$(this).attr('pvkristall').replace(/[^\d]/g, ''),
							$(this).attr('pvflour').replace(/[^\d]/g, '')
						].join('X');
						
						// Oberfläche (trafficoptimiert)
						pl['scr'] = $(this).attr('pgrafik');
						pl['scr'] = pl['scr'].replace(/^.*planet(\d*)_s\.jpg/, 'typ=$1');
						pl['scr'] = pl['scr'].replace(/&s/g, 'X');
						pl['scr'] = pl['scr'].replace(/=/g, 'Y');
						
						// Schiff in der Werft
						if($(this).find('img[src*="ships"]').length) {
							pl['schiff'] = $(this).find('td[remtimea]').attr('remtimea');
						}
						
						// Aktion(en) am Planeten
						if($(this).children('td:last-child').html() != '') {
							pl['inva'] = $(this).children('td:last-child').html();
							pl['inva'] = pl['inva'].replace(/(<strong>|<\/strong>|<font color="#[0F]+">|<\/font>)/g, '');
						}
					}
					catch(e) {
						throw 'Fehler beim Einscannen der Planetenübersicht!';
					}
					
					// ans Planeten-Array hängen
					out['pl'].push(pl);
				});
			}
			//
			// Orbit
			//
			else if(ctree.find('#jumpGateDialog').length) {
				out['typ'] = 'orbit';
				
				var data;
				
				// ID
				try {
					out['id'] = ctree.find('a[href*="&typ=e"]').attr('href').replace(/^.*=(\d+)&typ.*$/, '$1');
				}
				catch(e) {
					throw 'Konnte ID nicht ermittel!';
				}
				
				// Inhaber
				try {
					// hat bekannten Inhaber
					if(ctree.find('form[name="gotoplan"] a[href*="op=usershow"]').length) {
						out['inhaber'] = ctree.find('form[name="gotoplan"] a[href*="op=usershow"]').attr('href').replace(/^.*welch=(\d+)$/, '$1');
					}
					else {
						data = ctree.find('form[name="gotoplan"] tr:first-child font').html();
						
						// verschleierte Altrasse
						if(data.indexOf('Altrasse') != -1) {
							out['inhaber'] = -3;
						}
						// verschleierter Lux
						else if(data.indexOf('Lux') != -1) {
							out['inhaber'] = -2;
						}
						// frei
						else {
							out['inhaber'] = 0;
						}
					}
				}
				catch(e) {
					throw 'Konnte Inhaber nicht ermitteln!';
				}
				
				// fremde Schiffe
				out['frs'] = ctree.find('a[href*="typ=f"]').parent().clone().children('a').remove().end().html();
				
				// DOM in Plaintext umwandeln leider noch nötig
				if(input == false) {
					var input = ctree.html();
				}
				
				// Umod
				if(input.indexOf('sich gerade einen Urlaub. Der Planet ist nicht einnehmbar!') != -1 || input.indexOf('treating himself a holiday') != -1) {
					out['umod'] = 1;
				}
				
				// Besatzung
				if(input.indexOf('<b>Dieser Planet ist gerade besetzt!') != -1 || input.indexOf('<b>Dieser Planet wird gerade besetzt!') != -1 || input.indexOf('<b>This planet is occupied at present!') != -1) {
					
					out['bes'] = 1;
					
					// Besatzer
					// TODO Grafikpfad wahrscheinlich falsch
					data = ctree.find('img[src*="warn.gif"]').parent().next().find('a[href*="usershow"]');
					if(data.length) {
						out['besuser'] = data.attr('href').replace(/[^\d]/g, '');
					}
				}
				
				// Ende-Pattern für Kolos und Invas
				var p = /(?:bernahmezeitpunkt|Completion date): (?:.{3}), ([^<]+)[<]br/;
				
				// Kolo
				if(input.indexOf('<b>Dieser Planet wird gerade kolonisiert.') != -1  || input.indexOf('<b>This planet is currently being colonised') != -1) {
					
					out['kolo'] = 1;
					
					// Ende
					data = p.exec(input);
					if(data == null) {
						throw 'Konnte Kolodaten nicht ermitteln!';
					}
					
					out['koloende'] = data[1];
					
					// Kolonisator
					data = ctree.find('div[name="alldiv"] a[href*="usershow"]');
					if(data.length) {
						out['kolouser'] = data.attr('href').replace(/[^\d]/g, '');
					}
				}
				
				// Inva
				if(input.indexOf('<b>Dieser Planet wird gerade invadiert!') != -1 || input.indexOf('<b>This planet is currently being invaded') != -1) {
					
					out['inv'] = 1;
					
					data = p.exec(input);
					if(data == null) {
						throw 'Konnte Invadaten nicht ermitteln!';
					}
					
					out['invende'] = data[1];
					
					// Aggressor
					data = ctree.find('div[name="alldiv"] a[href*="usershow"]');
					if(data.length) {
						out['invauser'] = data.attr('href').replace(/[^\d]/g, '');
					}
				}
				
				// Genesis
				if(input.indexOf('An diesem Planeten wird ein Genesis-Projekt gestartet.') != -1 || input.indexOf('At this planet a genesis project is initiated.') != -1) {
					
					out['gen'] = 1;
					
					p = /(?:ndung|Firing): (?:.{3}), ([^<]+)[<]br/;
					
					data = p.exec(input);
					if(data == null) {
						throw 'Konnte Genesis-Daten nicht ermitteln!';
					}
					
					out['genende'] = data[1];
					
					// Genesis-Benutzer
					var genuser = ctree.find('.box td:contains("enesis") a[href^="?op=usershow"]'); 
					if(genuser.length) {
						out['genuser'] = genuser.attr('href').replace(/[^\d]/g, '');
					}
				}
				
				// Reso / Todesstern
				if(input.indexOf('sich gerade ein Resonator auf, um diesen Planeten und alle Schiffe im Orbit zu vernichten!') != -1
					|| input.indexOf('<b>A resonator is currently charging up so that it can destroy this planet and all ships in orbit around it') != -1
					|| input.indexOf('sich gerade ein Schiff auf, um diesen Planeten zu vernichten!') != -1) {
					out['reso'] = 1;
					
					p = /(?:ndung|Firing): (?:.{3}), ([^<]+)</;
					data = p.exec(input);
					
					if(data == null) {
						throw 'Konnte Reso-Daten nicht ermitteln!';
					}
					
					out['resoende'] = data[1];
				}
				
				// Natives
				if(input.indexOf('>Achtung - In diesem Orbit stehen sehr starke Schiffe!') != -1) {
					out['natives'] = 1;
				}
				
				// Sprunggenerator
				if(input.indexOf('><b>Sprunggenerator aktiv</b></font>') != -1) {
					out['sprung'] = 1;
				}
				
				// Bergbau
				if(input.indexOf('uft ein Bergbauvorgang.<') != -1 || input.indexOf('>A mining operation is active.<') != -1) {
					out['bb'] = 1;
				}
				
				// Terraforming
				if(input.indexOf('>Dieser Planet wird mittels Terraforming verbessert.<') != -1) {
					out['tf'] = 1;
				}
			}
			//
			// Einstellungen
			//
			else if(ctree.find('form[action="?op=settings"]').length) {
				out['typ'] = 'einst';
				
				// Spieler
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Spieler nicht ermitteln!';
				}
				
				// Format: AA-HandelNeutral-HandelNAP-HandelMeta-KS[1,2,3]-Steuern[1,2,3]
				
				// AA
				out['einst'] = '0';
				// HandelNeutral
				out['einst'] += ctree.find('#pn2:checked').length;
				// HandelAlly
				out['einst'] += ctree.find('#pv2:checked').length;
				// HandelNAP
				out['einst'] += ctree.find('#nap2:checked').length;
				// Kampfsystem
				if(ctree.find('#km1:checked').length) {
					out['einst'] += '1';
				}
				else if(ctree.find('#km2:checked').length) {
					out['einst'] += '2';
				}
				else {
					out['einst'] += '3';
				}
				// Steuern
				if(ctree.find('#sm1:checked').length) {
					out['einst'] += '1';
				}
				else if(ctree.find('#sm2:checked').length) {
					out['einst'] += '2';
				}
				else {
					out['einst'] += '3';
				}
			}
			//
			// Flottenübersicht - Steuern
			//
			else if(ctree.find('a.active[href*="tab=5"]').length) {
				out['typ'] = 'floview';
				
				// Spieler
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Spieler nicht ermitteln!';
				}
				
				// zuletzt gezahlte Flottensteuer
				var data = $(ctree.children('.box')[5]).find('b');
				
				out['steuer'] = $(data[2]).html().replace(/[^\d+]/g, '');
				
				// Kommandopunkte und Schiffe
				var $kopRows = ctree.find('tr.quickjump:has(b)'),
					$kop = $kopRows.find('td:last-child'),
					$kopMax = $kopRows.next().find('td:last-child'),
					$shipCount = ctree.find('tr.tablecolor + tr:not(.tablecolor)').find('td:eq(1)');
					
				if($kopRows.length != 2) {
					throw 'Konnte Kommandopunkte nicht ermitteln!';
				}
				
				try {
					out['pkop'] = $kop.first().html().replace(/[^\d+]/g, '');
					out['kop'] = $kop.last().html().replace(/[^\d+]/g, '');
					out['pkopmax'] = $kopMax.first().html().replace(/[^\d+]/g, '');
					out['kopmax'] = $kopMax.last().html().replace(/[^\d+]/g, '');
				}
				catch(e) {
					throw 'Fehler beim Ermitteln der Kommandopunkte!';
				}
				
				try {
					out['schiffe'] = parseInt($shipCount.eq(0).html().replace(/[^\d+]/g, '')) + parseInt($shipCount.eq(1).html().replace(/[^\d+]/g, ''));
				}
				catch(e) {
					throw 'Konnte Anzahl der Schiffe nicht ermitteln!';
				}
			}
			//
			// Bergbauschiffe
			//
			else if(ctree.find('a.active[href*="tab=2"]').length) {
				out['typ'] = 'floviewbbs';
				
				// Spieler
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Spieler nicht ermitteln!';
				}
				
				
				out['bb'] = [];
				
				try {
					ctree.find('.tabletrans td:nth-child(6) > a[href*=orbit]:first-child').each(function() {
						out['bb'].push($(this).attr('href').replace(/[^\d]/g, ''));
					});
				}
				catch(e) {
					throw 'Fehler beim Erfassen der Bergbauschiffe!';
				}
				
				out['bb'] = out['bb'].join('-');
			}
			//
			// Sitterliste
			//
			else if(ctree.find('a[href="?op=sitter&was=auftraege"]').length) {
				out['typ'] = 'sitter';
				
				// Spieler
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Spieler nicht ermitteln!';
				}
				
				// Sitter von anderen
				var sfrom = [];
				ctree.find('a[href*="sitter&umloggen"]').each(function() {
					sfrom.push(jQuery.trim($(this).clone().children('font').remove().end().html()));
				});
				
				out['sitterfrom'] = sfrom;
				
				// Sitter zu anderen
				var sto = [];
				ctree.find('td:last-child > .box td[width]:first-child').each(function() {
					sto.push(jQuery.trim($(this).clone().children('font').remove().end().html()));
				});
				
				out['sitterto'] = sto;
			}
			//
			// Forschung
			//
			else if(ctree.find('a[href="?op=tech&tree=geb"]').length && ctree.find('img[src*="basiscamp.gif"], img[src*="titanid_s.gif"], img[src*="ion1.gif"]').length) {
				
				out['typ'] = 'forschung';
				
				// Spieler
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Spieler nicht ermitteln!';
				}
				
				out['f'] = [];
				out['fn'] = [];
				out['ff'] = [];
				out['kategorie'] = 0;
				
				var kategorien = {
					1: 'basiscamp.gif',
					2: 'titanid_s.gif',
					3: 'ion1.gif'
				};
				
				ctree.find('img[titel]').each(function() {
					
					var $this = $(this),
						path = $this.attr('src');
					
					// Lokale Grafikpakete abfangen
					if(path.indexOf('http://static.omega-day.com/img/') == -1) {
						throw 'Grafikpfade ungültig!';
					}
					
					if(out['kategorie'] == 0) {
						for(var i in kategorien) {
							if(path.indexOf(kategorien[i]) != -1) {
								out['kategorie'] = i;
								break;
							}
						}
					}
					
					out['f'].push(path.replace(/^.*\/img\//, ''));
					out['fn'].push($this.attr('titel'));
					out['ff'].push($this.hasClass('opacity2') ? 1 : 0);
				});
				
				if(!out['f'].length) {
					throw 'Konnte Forschungen nicht ermitteln!';
				}
				
				// laufende Forschung
				var current = ctree.find('.tabletranslight .box td:first-child img');
				
				if(current.length) {
					out['current'] = current.attr('src').replace(/^.*\/img\//, '');
					out['current_end'] = ctree.find('#returntim').siblings('b').html();
				}
			}
			//
			// Unbekannter Quelltext
			//
			else {
				r.append('<span style="color:red">fehlgeschlagen! (Quelltext unbekannt)</span>');
				
				// abbrechen
				return false;
			}
		}
		// Fehlerbehandlung
		catch(e) {
			r.append('<span style="color:red">'+e+'</span>');
			
			// abbrechen
			return false;
		}
		
		// Ausgabe-Objekt zurückgeben
		return out;
	};
	
	
	oddbtool.fow = function(page, data) {
		// System-ID ermitteln
		var url = page.location;
		var p = /system&sys=(\d+)/;
		var id = p.exec(url);
		if(id == null) {
			id = $('#sysid input[name="sys"]',page).val();
		}
		else id = id[1];
		
		
		var allyids = [];
		var uids = [];
		var plcount = 0;
		
		// Daten mit den Usernamen erweitern
		if(data && data['typ'] == 'system') {
			var input = oddbtool.getQuelltext(page);
			for(var i=0;i<=6;i++) {
				// Zähler erhöhen
				if(typeof(data['pl'][i]) == 'object') {
					plcount++;
				}
				// Planet existiert und hat Inhaber
				if(typeof(data['pl'][i]) == 'object' && data['pl'][i]['inhaber'] > 0) {
					
					p = 'setter\\(\\\'(.+)\\\',\\\''+data['pl'][i]['inhaber']+'\\\',\\\'(.*)\\\',\\\'(?:.*)\\\',\\\'(?:.*)\\\'\\);kringler\\('+data['pl'][i]['id'];
					
					p = new RegExp(p);
					var data2 = p.exec(input);
					if(data2 != null) {
						// HTML dekodieren und komische Symbole anzeigbar machen
						data['pl'][i]['username'] = oddbtool.str_replace(oddbtool.charmap_search, oddbtool.charmap_replace, data2[1]);
						data['pl'][i]['allytag'] = oddbtool.str_replace(oddbtool.charmap_search, oddbtool.charmap_replace, data2[2]);
						
						
						
						// AllyID an das Abfrage-Array anhängen
						if(data['pl'][i]['allianz'] != '' && !oddbtool.in_array(data['pl'][i]['allianz'], allyids)) {
							allyids.push(data['pl'][i]['allianz']);
						}
						
						// SpielerID an das Abfrage-Array anhängen
						if(!oddbtool.in_array(data['pl'][i]['inhaber'], uids)) {
							uids.push(data['pl'][i]['inhaber']);
						}
					}
				}
				// kein Inhaber oder verschleiert
				else if(typeof(data['pl'][i]) == 'object') {
					data['pl'][i]['allytag'] = '';
					
					// frei
					if(data['pl'][i]['inhaber'] == 0) {
						data['pl'][i]['username'] = '- keiner -';
					}
					// Lux
					else if(data['pl'][i]['inhaber'] == -2) {
						data['pl'][i]['username'] = 'Seze Lux';
					}
					// Altrasse
					else if(data['pl'][i]['inhaber'] == -3) {
						data['pl'][i]['username'] = 'Altrasse';
					}
				}
			}
		}
		// bei unbekanntem System Planetenzähler auf 1 setzen
		else if(data && data['typ'] == 'sysun') {
			plcount = 1;
		}
		
		// Adresse erzeugen
		var addr = oddbtool.prefs.url+'index.php?p=fow&oddb&version='+oddbtool.version+'&id='+id+'&plcount='+plcount;
		// AllyID-Abfrage-Array anhängen
		if(allyids.length) {
			addr += '&status='+encodeURIComponent(allyids.join('+'));
		}
		// UserID-Abfrage-Array anhängen
		if(uids.length) {
			addr += '&umod='+encodeURIComponent(uids.join('+'));
		}
		// Account-Allianz
		var accally2 = $('.statusbar .alliance',page).attr('href');
		if(accally2 != null) {
			accally2 = accally2.replace(/[^\d]/g, '');
			addr += '&ally='+accally2;
		}
		
		// Meldung anzeigen
		$('#oddbtoolwin',page).append('<span id="oddbtoolfow"><a href="'+addr+'" target="_blank">Rufe Systemdaten ab</a>... </span><br>');
		
		if(!data) {
			$('#oddbtoolfow',page).append('<span style="color:red">fehlgeschlagen! (unbekannter Quelltext)</span>');
			return false;
		}
		
		// iFrame erzeugen
		$('body',page).append('<iframe id="oddbtooliframe"></iframe>');
		
		// normale Inhaber-Anzeige ausblenden
		$('#anzeigerd',page).hide();
		
		// Request absenden
		jQuery.ajax({
			type: 'post',
			url: addr,
			
			// Bookmarklet: Authentifizierungs-Daten
			data: {
				'authToken': <?php echo $authTokenVar; ?>
			},
			
			success: function(result){
				// Fehler ausgeben
				var error = $(result).find('error').text();
				if(error) {
					// Fehler-Ausgaben ändern
					if(error.indexOf('mehr eingeloggt') != -1) {
						error = 'Benutzerdaten falsch. Bitte richte das Bookmarklet neu ein!';
					}
					else if(error.indexOf('ODDB Tool veraltet') != -1) {
						error = 'Eine neue Version ist verf&uuml;gbar. Leere den Cache oder warte 15 Minuten!';
					}
					
					// ausgeben
					$('#oddbtoolfow',page).append('<span style="color:red">'+error+'</span>');
					return false;
				}
				// Authorisierung
				var auth = $(result).find('auth').text();
				if(auth == 'false') {
					$('#oddbtoolfow',page).append('<span style="color:red">Benutzerdaten falsch. Bitte richte das Bookmarklet neu ein!</span>');
					return false;
				}
				
				// erfolgreich-Meldung
				$('#oddbtoolfow',page).append('<span style="color:#00ff00">erfolgreich!</span>');
				
				// Positionen
				var xbase = 304;
				var ybase = 199;
				var xadd = 100;
				var yadd = 17;
				
				var x = 0;
				var y = 0;
				var content = '';
				var content2 = '';
				
				// Berechtigung zum Toxxen und Raiden
				var toxxrechte = $(result).find('toxxrechte').text();
				
				// Daten abgleichen
				for(var i=0;i<=6;i++) {
					if(typeof(data['pl'][i]) == 'object') {
						var pl = $(result).find('planet[pid='+data['pl'][i]['id']+']');
						// Planet in DB -> Informationen erweitern
						if($(pl).text() != '') {
							var sc = $(pl).find('scanDate');
							data['pl'][i]['scan'] = '<span style="color:';
							if($(sc).attr('current') == 1) data['pl'][i]['scan'] += '#00aa00';
							else data['pl'][i]['scan'] += '#ff3322';
							data['pl'][i]['scan'] += '">Scan: '+$(sc).text()+'</span>';
							
							data['pl'][i]['updateoverview'] = $(pl).find('scanOview').text();
							data['pl'][i]['unscannbar'] = $(pl).find('scanFailed').text();
							data['pl'][i]['gpl'] = $(pl).find('gebplanet').text();
							data['pl'][i]['gor'] = $(pl).find('geborbit').text();
							data['pl'][i]['erzmenge'] = $(pl).find('erzmenge').text();
							data['pl'][i]['metallmenge'] = $(pl).find('metallmenge').text();
							data['pl'][i]['wolframmenge'] = $(pl).find('wolframmenge').text();
							data['pl'][i]['kristallmenge'] = $(pl).find('kristallmenge').text();
							data['pl'][i]['fluormenge'] = $(pl).find('fluormenge').text();
							data['pl'][i]['additional'] = $(pl).find('additional').text();
							data['pl'][i]['additional2'] = $(pl).find('additional2').text();
							data['pl'][i]['additional3'] = $(pl).find('additional3').text();
							data['pl'][i]['comment'] = $(pl).find('comment').text();
							
							// wenn System unsichtbar, mehr Informationen holen
							if(data['typ'] == 'sysun') {
								// Resswerte und Bevölkerung
								data['pl'][i]['erz'] = $(pl).find('erz').text();
								data['pl'][i]['wolfram'] = $(pl).find('wolfram').text();
								data['pl'][i]['kristall'] = $(pl).find('kristall').text();
								data['pl'][i]['fluor'] = $(pl).find('fluor').text();
								data['pl'][i]['bev'] = $(pl).find('population').text();
								data['pl'][i]['groesse'] = $(pl).attr('size');
								
								// Inhaber und Allianz
								data['pl'][i]['inhaber'] = $(pl).find('userid').text();
								data['pl'][i]['username'] = $(pl).find('userName').text();
								data['pl'][i]['rasse'] = $(pl).find('userRace').text();
								data['pl'][i]['allianz'] = $(pl).find('userAllianceId').text();
								data['pl'][i]['allytag'] = $(pl).find('userAllianceTag').text();
								
								// Orbit
								var orbit = $(pl).find('orbit');
								if($(orbit).text() != '') {
									var o = '';
									
									if($(orbit).attr('type') == 'G') {
										data['pl'][i]['gate'] = 1;
										o = '<img src="'+oddbtool.prefs.url+'img/orbit/gate.gif" width="100" height="90">';
									}
									else if($(orbit).attr('type') == 'M') {
										data['pl'][i]['mgate'] = 1;
										o = '<img src="'+oddbtool.prefs.url+'img/orbit/mgate.gif" width="100" height="90">';
									}
									else if($(orbit).attr('type') == 'S') {
										data['pl'][i]['mgate'] = 1;
										o = '<img src="'+oddbtool.prefs.url+'img/orbit/sprunggenerator.gif" width="100" height="90">';
									}
									else {
										data['pl'][i]['riss'] = 1;
										o = '<img src="'+oddbtool.prefs.url+'img/orbit/riss.gif" width="100" height="90">';
									}
									
									// Orbit-Overlay
									x = xbase + i*xadd;
									y = ybase + i*yadd + 100;
									
									content += '<div class="oddbtoolplanet" style="left:'+x+'px; top:'+y+'px; background-image:url('+oddbtool.prefs.url+'img/layout/bg.gif)"><a href="index.php?op=orbit&index='+data['pl'][i]['id']+'" onmouseover="dlt(\'Eigene Schiffe: 0\', \''+data['pl'][i]['name']+' Orbit:\')" onmouseout="nd()">'+o+'</a></div>';
								}
							}
							// Planet verschleiert und in der DB bekannt
							else if(data['pl'][i]['inhaber'] < 0 && $(pl).find('userid').text() > 0) {
								// Inhaber und Allianz kopieren
								data['pl'][i]['inhaber'] = $(pl).find('userid').text();
								data['pl'][i]['username'] = $(pl).find('userName').text();
								data['pl'][i]['rasse'] = $(pl).find('userRace').text();
								data['pl'][i]['allianz'] = $(pl).find('userAllianceId').text();
								data['pl'][i]['allytag'] = $(pl).find('userAllianceTag').text();
							}
							// System sichtbar
							else {
								// Urlaubsmodus übertragen
								if($(result).find('umod'+data['pl'][i]['inhaber']).length) {
									data['pl'][i]['username'] += '<span style="color:#ff3322"><sup>zzZ</sup></span>';
								}
								// Bevölkerung formatieren
								data['pl'][i]['bev'] = oddbtool.ressmenge(data['pl'][i]['bev']);
							}
							
							
							// voranstehende 0 des Typs entfernen
							data['pl'][i]['typ'] = data['pl'][i]['typ'].replace(/^0/, '');
							
							x = xbase + i*xadd;
							y = ybase + i*yadd;
							
							// Tooltip erzeugen
							var tooltip = '<br>Inhaber: <b>'+data['pl'][i]['username']+'</b><br><br>';
							
							if(data['pl'][i]['additional3'] != '') {
								tooltip += '<div class="oddbtooladd3">'+data['pl'][i]['additional3']+'</div>';
							}
							
							// ohne Scan Ressmengen zurücksetzen
							if(!(data['pl'][i]['updateoverview'] > 0)) {
								data['pl'][i]['erzmenge'] = '';
								data['pl'][i]['metallmenge'] = '';
								data['pl'][i]['wolframmenge'] = '';
								data['pl'][i]['kristallmenge'] = '';
								data['pl'][i]['fluormenge'] = '';
							}
							
							// unbewohnbar
							if(data['pl'][i]['groesse'] == 0) {
								data['pl'][i]['groesse'] = '<span style="color:red">unbewohnbar</span>';
							}
							
							tooltip += '<table style="border:0;padding:0;margin:0;width:auto"><tr><td><img src="http://static.omega-day.com/img//grafik/aufbautechniken/kultur_erz_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['erz']+' %</td><td style="padding-left:10px">'+data['pl'][i]['erzmenge']+'</td></tr><tr><td><img src="http://static.omega-day.com/img//grafik/aufbautechniken/kultur_metall_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['erz']+' %</td><td style="padding-left:10px">'+data['pl'][i]['metallmenge']+'</td></tr><tr><td><img src="http://static.omega-day.com/img//grafik/aufbautechniken/kultur_wolfram_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['wolfram']+' %</td><td style="padding-left:10px">'+data['pl'][i]['wolframmenge']+'</td></tr><tr><td><img src="http://static.omega-day.com/img//grafik/aufbautechniken/kultur_kristall_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['kristall']+' %</td><td style="padding-left:10px">'+data['pl'][i]['kristallmenge']+'</td></tr><tr><td><img src="http://static.omega-day.com/img//grafik/aufbautechniken/kultur_flour_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['fluor']+' %</td><td style="padding-left:10px">'+data['pl'][i]['fluormenge']+'</td></tr></table><br>Bev&ouml;lkerung: '+data['pl'][i]['bev']+'<br>Gr&ouml;&szlig;e: '+data['pl'][i]['groesse']+'<br><br>'+data['pl'][i]['additional'];
								
							data['pl'][i]['name'] = oddbtool.str_replace(oddbtool.charmap_search, oddbtool.charmap_replace, data['pl'][i]['name']);
							tooltip = tooltip.replace(/'/g, "\\'");
							tooltip = tooltip.replace(/"/g, '&quot;');
							
							// Kommentar?
							if(data['pl'][i]['comment'] == 1) {
								data['pl'][i]['comment'] = '<div style="width:22px;height:18px;background-image:url('+oddbtool.prefs.url+'img/layout/sprite32.png);background-position:-480px -54px;float:left;margin-top:-5px"></div>';
							}
							
							// Unscannbar
							if(data['pl'][i]['unscannbar'] > data['pl'][i]['updateoverview']) {
								data['pl'][i]['comment'] += '<div style="position:absolute;top:110px;left:15px;font-weight:bold;color:#ff3322">unscannbar!</div>';
							}
							
							// Planet-Overlay
							if(data['pl'][i]['updateoverview'] > 0) {
								// Gebäude aufbereiten
								data['pl'][i]['gpl'] = '0+'+data['pl'][i]['gpl'];
								data['pl'][i]['gor'] = '0+'+data['pl'][i]['gor'];
								var gpl = data['pl'][i]['gpl'].split('+');
								var gor = data['pl'][i]['gor'].split('+');
								
								// mit leeren Werten füllen
								for(var k=1;k<=36;k++) {
									if(typeof(gpl[k]) == 'undefined') {
										gpl[k] = 0;
									}
								}
								for(var k=1;k<=12;k++) {
									if(typeof(gor[k]) == 'undefined') {
										gor[k] = 0;
									}
								}
								
								// Ressmengen entfernen, wenn kein voller Scan
								if(data['pl'][i]['updateoverview'] == 0) {
									data['pl'][i]['erzmenge'] = '';
									data['pl'][i]['metallmenge'] = '';
									data['pl'][i]['wolframmenge'] = '';
									data['pl'][i]['kristallmenge'] = '';
									data['pl'][i]['fluormenge'] = '';
								}
								
								// Trenner bei der Bevölkerung
								if(data['pl'][i]['bev'].indexOf('.') == -1) {
									var number = '' + data['pl'][i]['bev'];
									var mod = number.length % 3;
									var output = (mod > 0 ? (number.substring(0,mod)) : '');
									for (var k=0 ; k < Math.floor(number.length / 3); k++) {
										if ((mod == 0) && (k == 0)) {
											output += number.substring(mod+ 3 * k, mod + 3 * k + 3);
										}
										else {
											output += '.' + number.substring(mod + 3 * k, mod + 3 * k + 3);
										}
									}
									data['pl'][i]['bev'] = output;
								}
								
								// Gebäude auf dem Planet
								content += '<a class="oddbtoollink" href="index.php?op=planet&index='+data['pl'][i]['id']+'" onmouseover="dlt(\''+tooltip+'\', \''+data['pl'][i]['name']+':\')" onmouseout="nd()"><table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="cursor:pointer;left:'+x+'px;top:'+y+'px"><tr><td style="background-position:-'+oddbtool.geb[gpl[36]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[35]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[29]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[23]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[30]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[34]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[32]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[24]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[18]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[10]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[19]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[25]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[28]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[14]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[6]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[2]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[7]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[15]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[22]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[13]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[5]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[1]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[3]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[11]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[31]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[17]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[9]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[4]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[8]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[16]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[33]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[27]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[21]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[12]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[20]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[26]]+'px 0px"></td></tr><tr><td colspan="6" style="background-image:none;text-align:center;font-size:11px;padding-bottom:3px;padding-top:3px">'+data['pl'][i]['scan']+data['pl'][i]['comment']+'</td></tr></table></a>';
								
								// Gebäude im Orbit
								y -= 32;
								
								content += '<table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="left:'+x+'px;top:'+y+'px"><tr><td style="background-position:-'+oddbtool.geb[gor[1]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[2]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[3]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[4]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[5]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[6]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gor[7]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[8]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[9]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[10]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[11]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[12]]+'px 0px"></td></tr></table>';
							}
							else {
								// Planet-Overlay ohne Scan
								content += '<a class="oddbtoollink" href="index.php?op=planet&index='+data['pl'][i]['id']+'" onmouseover="dlt(\''+tooltip+'\', \''+data['pl'][i]['name']+':\')" onmouseout="nd()"><table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="cursor:pointer;left:'+x+'px;top:'+y+'px"><tr><td style="height:90px;background-image:none"></td></tr><tr><td style="background-image:none;text-align:center;font-size:11px;padding-bottom:3px">'+data['pl'][i]['comment']+'</td></tr></table></a>';
							}
						}
						// Planet nicht in DB
						else {
							data['pl'][i]['updateoverview'] = 0;
						}
						
						
						
						// Orbit-Overlay (Invas)
						if(typeof(data['pl'][i]['additional2']) != 'undefined' && data['pl'][i]['additional2'] != '') {
							x = xbase + i*xadd;
							y = ybase + i*yadd + 110;
							
							$('#layout-main a[href="?op=orbit&index='+data['pl'][i]['id']+'"]',page).addClass('oddbtoolorbitoverride').append('<div class="oddbtoolplanet oddbtoolorbit" id="oddbtoolorbit'+data['pl'][i]['id']+'">'+data['pl'][i]['additional2']+'</div>');
						}
						
						// Inhaber-Overlay
						if(data['typ'] == 'system' || $(result).find('systemUpdate').text() > 0 || ($(pl).text() != '' && $(pl).find('userid').text() != -1)) {
							x = xbase + i*xadd;
							y = ybase + i*yadd - 75;
							
							content += '<div class="oddbtoolplanet" style="line-height:15px;text-align:center;left:'+x+'px;top:'+y+'px">';
							if(data['pl'][i]['inhaber'] > 0) {
								// Rasse
								if(data['pl'][i]['rasse'] != '') {
									// aufbereiten
									for(var k in oddbtool.rassen) {
										if(data['pl'][i]['rasse'].indexOf(k) != -1) {
											data['pl'][i]['rasse'] = oddbtool.rassen[k];
											break;
										}
									}
								}
								
								content += '<img src="http://static.omega-day.com/img//grafik/leer.gif" alt="" class="oddbtoolrasse oddbtoolr'+data['pl'][i]['rasse']+'"><a href="index.php?op=usershow&welch='+data['pl'][i]['inhaber']+'">'+data['pl'][i]['username']+'</a>';
								if(data['pl'][i]['allianz'] > 0) {
									content += '<br><a href="index.php?op=allyshow&welch='+data['pl'][i]['allianz']+'" style="font-size:10px">'+data['pl'][i]['allytag']+'</a>';
									var status = $(result).find('status'+data['pl'][i]['allianz']).text();
									if(status != '') {
										content += '<br><span style="font-size:10px;color:#888888">'+status+'</span>';
									}
								}
							}
							// frei
							else if(data['pl'][i]['inhaber'] == 0) {
								content += '- keiner -';
							}
							// frei
							else if(data['pl'][i]['inhaber'] == -2) {
								content += '<span style="color:#ffff88;font-weight:bold;font-style:italic">Seze Lux</span>';
							}
							// frei
							else if(data['pl'][i]['inhaber'] == -3) {
								content += '<span style="color:#ffff88;font-weight:bold;font-style:italic">Altrasse</span>';
							}
							// unbekannt
							else {
								content += '- unbekannt -';
							}
							content += '</div>';
						}
						
						// Menü DB-Aktionen
						if($(pl).text() != '' && $(pl).find('rechte').text() == 1) {
							x = xbase + i*xadd + 85;
							y = ybase + i*yadd + 110;
							
							content += '<div id="oddbtoolmenu'+data['pl'][i]['id']+'" class="oddbtoolmenu" style="top:'+(y+15)+'px;left:'+(x-130)+'px;display:none"><a href="'+oddbtool.prefs.url+'index.php?p=show_planet&id='+data['pl'][i]['id']+'" target="_blank">in der DB &ouml;ffnen</a><a href="javascript:void(0)" onclick="var kf = document.getElementById(\'oddbtoolkommentar\'); kf.style.left = \''+(x-130)+'px\'; kf.style.top = \''+(y-5)+'px\'; kf.style.display=\'block\'; document.getElementById(\'oddbtoolkiframe\').src=\''+oddbtool.prefs.url+'index.php?p=show_planet&id='+data['pl'][i]['id']+'&sp=kommentar_editgame\';">Kommentar &auml;ndern</a>';
							if(toxxrechte) {
								// raiden und toxxen
								content += '<div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=raid&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>als geraidet markiert</a>\'">als geraidet markieren</a></div><div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=toxx&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>als getoxxt markiert</a>\'">als getoxxt markieren</a></div>';
								// Bergbau und Terraformer entfernen
								content += '<div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=show_planet&amp;sp=removebbstf&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>Bergbau / Terraformer entfernt</a>\'">Bergbau / Terraformer entfernen</a></div>';
								// Orbiter löschen und Ress auf 0
								if(data['pl'][i]['updateoverview'] > 0) {
									content += '<div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=show_planet&amp;sp=orbiter_del&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>Orbiter gel&ouml;scht</a>\'">Orbiter l&ouml;schen</a></div><div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=show_planet&amp;sp=ress_del&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>Ress auf 0 gesetzt</a>\'">Ress auf 0 setzen</a></div>';
								}
							}
							content += '</div><div id="oddbtoolpfeil'+data['pl'][i]['id']+'" class="oddbtoolpfeil" style="top:'+y+'px;left:'+x+'px"></div>';
						}
					}
				}
				
				// Headline anzeigen (Gala - ID - sichtbar - Scan - DB  - Reserv)
				content += '<div id="oddbtoolheadline">Gala '+$(result).find('galaxie').text()+' &nbsp; &nbsp; Sys '+data['id']+' &nbsp; &nbsp; <span style="color:';
				if(data['typ'] == 'system') content += '#00aa00">sichtbar';
				else content += '#ff3322">nicht sichtbar';
				content += '</span> &nbsp; &nbsp; <span style="color:';
				if($(result).find('system > scanDate').attr('current') == 1) content += '#00aa00';
				else content += '#ff3322';
				content += '">Scan: '+$(result).find('system > scanDate').text()+'</span> &nbsp; &nbsp; &nbsp; <a href="'+oddbtool.prefs.url+'index.php?p=show_system&id='+data['id']+'" target="_blank">in DB &ouml;ffnen</a> &nbsp; &nbsp; &nbsp; ';
				if(data['typ'] != 'system') {
					if($(result).find('reserv').length) {
						content += '<span style="font-style:italic;font-size:11px">reserviert '+$(result).find('reserv').text()+'</span>';
					}
					else {
						content += '<span><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=reserve&amp;sys='+data['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'Scan reserviert\'">Scan reservieren</a></span>';
					}
				}
				content += '</div>';
				
				// Kommentar-Formular
				content += '<div id="oddbtoolkommentar"><a onclick="this.parentNode.style.display=\'none\';">X</a><br /><iframe name="oddbtoolkiframe" id="oddbtoolkiframe" src="about:blank"></iframe></div>';
				
				
				// Inhalt an den body anhängen
				$('body',page).append(content);
				
				// Planetengrafiken verschieben
				$('table[width="100"] tr:first-child td').css('background-position', '2px -2px');
				
				
				// Headline zentrieren
				var offset = $('#layout-main table[width=600]',page).offset();
				if(offset) {
					offset = parseInt(offset.left);
					offset -= 25;
					$('#oddbtoolheadline',page).css('left',offset+'px');
				}
				
				// systemInfo-Tabelle
				if($(result).find('systemInfo').text() != '') {
					$('body',page).append('<div id="oddbtoolfowtbl">'+$(result).find('systemInfo').text()+'</div>');
					
					$('#oddbtoolfowtbl tr:even',page).css('backgroundColor', 'rgba(255,255,255,0.05)');
				}
				
			},
			error: function(e, msg) {
				if(e.status != 0) {
					// Fehlermeldung
					var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
					content = content.substr(0,200);
					
					$('#oddbtoolfow',page).append('<span style="color:red">Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />Adresse: '+addr+'<br /><br />'+content+'</span>');
				}
			}
		});
	};
	
	
	
	
	
	
	
	
	
	
	
	
	// Console-IE-Polyfill
<?php
// Console-Replacement im IE
if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
?>
	var console = {
		log: function(msg) {}
	};
<?php
}
?>
	console.log('ODDB-Bookmarklet gestartet');
	
	var page = document,
		url = page.location.href;
	
	// kein Authentifizierungs-Token
	if(typeof(<?php echo $authTokenVar; ?>) == 'undefined') {
		alert('Abbruch: Keine Authentifizierungs-Daten übergeben!');
		return;
	}
	
	// Überprüfen, ob gültige Seite
	if(!oddbtool.isODPage(url)) {
		alert('Abbruch: Keine OD-Seite!');
		return;
	}
	
	// jQuery-Bibliothek abprüfen
	if(typeof($) == 'undefined' || typeof(jQuery) == 'undefined') {
		alert('Abbruch: Keine jQuery-Bibliothek vorhanden');
		return;
	}
	
	// ODDB Tool oder doppeltes Aufrufen erkennen

	if($('#oddbtoolwin').length) {
		console.log('ODDB Tool-Ausgabe bereits vorhanden, stiller Abbruch');
		return;
	}
	
	// CSS anhängen und Logo + Content-Bereich erzeugen
	var css = ['#oddbtoolwin {position:absolute; left:240px; top:5px; width:290px; font-family:Arial,Sans; font-size:9px; color:white; z-index:2; background-color:rgba(0,0,0,0.5)}',
		'#oddbtoolwin a {font-family:Arial,Sans; font-size:9px}',
		'#oddbtoollogo {position:absolute; top:1px; left:200px; width:32px; height:32px; background-image:url('+oddbtool.prefs.url+'img/layout/fowsprite32.png); cursor:pointer; z-index:2}',
		
		'#oddbtoolheadline{position:absolute; top:85px; left:450px; width:650px; text-align:center;}',
		
		'#oddbtoolfowtbl{position:absolute; top:525px; left:275px; width:700px; background-color:rgba(255,255,255,0.15); padding:10px; -moz-border-radius:12px; border-radius:12px; font-family:Arial,Sans; font-size:12px; color:white; z-index:1;}',
		'#oddbtoolfowtbl table{width:100%}',
		'#oddbtoolfowtbl th, #oddbtoolfowtbl td, #oddbtoolfowtbl a {font-size:9pt;padding:5px}',
		'#oddbtoolfowtbl th{background-color:rgba(255,255,255,0.1); text-align:left; font-weight:bold;}',
		'#oddbtoolfowtbl td:first-child{font-weight:bold; padding-left:8px;}',
		'#oddbtooliframe {display:none}',
		
		'.oddbtoolplanet {position:absolute; width:100px; background-position:3px -3px; background-repeat:no-repeat; font-size:11px}',
		'.oddbtoolorbit {position:relative;background-color:rgba(0,0,0,0.5);top:-72px;left:0px;text-align:center;font-size:11px !important}',
		'.oddbtoolorbitoverride {position:relative}',
		'.oddbtoolorbitoverride:hover {text-decoration:none !important}',
		'.oddbtoolplanet a {font-size:11px}',
		'.oddbtoolplanet img {border:none;}',
		'.oddbtoolplanet td {width:16px; height:14px; background-image:url('+oddbtool.prefs.url+'img/gebaeude/gebaeude_small.png);}',
		'.oddbtooladd3 {float:right; margin-right:10px; margin-top:20px; font-size:11px}',
		'.oddbtoollink:hover, .oddbtoolorbit a:hover {text-decoration:none !important;}',
		
		'.oddbtoolpfeil {position:absolute; width:15px; height:14px; background-image:url('+oddbtool.prefs.url+'img/layout/fowsprite32.png); background-position:-32px 0px; z-index:3; cursor:pointer}',
		'.oddbtoolmenu {position:absolute; width:140px; background-color:rgba(30,30,30,0.9); padding:5px; -moz-border-radius:6px; font-family:Arial,Sans; z-index:2;}',
		'.oddbtoolmenu a {display:block; color:white; font-size:11px; padding:4px;}',
		'.oddbtoolmenu a:hover {background-color:#282828; text-decoration:none;}',
		'.oddbtoolrasse {margin:8px 0px 0px -30px; float:right; background-image:url('+oddbtool.prefs.url+'img/layout/fowsprite32.png); width:30px; height:27px; background-repeat:no-repeat;}',
		'.oddbtoolr {background-position:30px 30px;}',
		'.oddbtoolrbera {background-position:-47px 0px;}',
		'.oddbtoolrlux {background-position:-77px 0px;}',
		'.oddbtoolrmensch {background-position:-107px 0px;}',
		'.oddbtoolrmyri {background-position:-137px 0px;}',
		'.oddbtoolrtrado {background-position:-197px 0px;}',
		'.oddbtoolrrevisker {background-position:-227px 0px;}',
		'.oddbtoolrtiroc {background-position:-167px 0px;}',
		'#oddbtoolkommentar {position:absolute; top:350px; left:400px; background-color:rgba(30,30,30,0.9); padding:8px; font-size:11px; -moz-border-radius:8px; border-radius:8px; text-align:right; display:none; z-index:4;}',
		'#oddbtoolkommentar iframe {width:160px; height:85px; border:none;}',
		'#oddbtoolkommentar a {font-size:13px; font-weight:bold; cursor:pointer;}'
	].join('');
		
		
	$('head',page).append('<style type="text/css">'+css+'</style>');
	$('body',page).append('<div id="oddbtoollogo" class="oddbtoolsprite" title="ODDB Tool Bookmarklet"></div><div id="oddbtoolwin">ODDB Bookmarklet<br /></div>');
	
	// Gültige Parse-Seite?
	if(!oddbtool.isParsePage(url)) {
		$('#oddbtoolwin',page).append('<span style="color:red">Dieser Seiten-Typ kann nicht geparst werden!</span>');
		return;
	}
	
	
	// jQuery-Konfiguration und Event-Handler
	jQuery.ajaxSetup({
	  cache: false,
	  dataType: 'xml'
	});
	
	$.support.cors = true;
	
	
	// Parsen-Link
	$(document).on('click', '#oddbtoolparselink', function() {
		oddbtool.parser(false, false, true);
	});
	
	// Menüs
	$(document).on('mousedown', 'div.oddbtoolpfeil', function(){
		var id = $(this).attr('id');
		id = id.replace(/oddbtoolpfeil/g, '');
		$('div.oddbtoolmenu').hide();
		$('#oddbtoolmenu'+id).show();
	});
	$(document).on('mouseleave', 'div.oddbtoolmenu', function(){
		$(this).hide();
	});
	
	
	
	// Seite parsen
	var pdata = oddbtool.parsePage(page, true);
	
	// Bookmarklet: Authentifizierungs-Daten
	pdata.authToken = <?php echo $authTokenVar; ?>;
	
	// FoW-Ausgleich
	if(oddbtool.parserRegex.system.exec(url) != null) {
		oddbtool.fow(page, oddbtool.parsePage(page, true));
	}
	
	window.setTimeout(function() {
		oddbtool.parser(page, pdata, true);
	}, 100);
	
	
	
})();