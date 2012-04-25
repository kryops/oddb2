/**
 * ODDB Tool by Kryops
 * http://www.kryops.de/
 */

var oddbtool = {
	/**
	 * Version
	 */
	version: '1.1.3',
	jqueryload: false,
	jQuery: false,
	
	betaserver: false,
	
	/**
	 * Startfunktion
	 */
	onLoad: function() {
		// Einstellungen laden
		oddbtool.getPrefs();
		
		// Seite laden -> Autoparser
		oddbtool.tabbrowser = window.getBrowser();
		oddbtool.tabbrowser.addEventListener('DOMContentLoaded', oddbtool.loadPage, true);
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
		1043:768,
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
		1056:960 /*,
		1057:768,
		1058:336,
		1059:176,
		1060:304,
		1061:368,
		1062:336,
		1063:176,
		1064:368,
		1065:304,
		1066:768,
		1067:336,
		1068:176,
		1069:368,
		1070:176,
		1071:768,
		1072:336,
		1073:304,
		1074:768*/
	},
	
	// Rassen-Suchmuster und Klassennamen
	rassen: {
		'Mensch':'mensch',
		'Roc':'tiroc',
		'Trado':'trado',
		'Bera':'bera',
		'Myri':'myri',
		'Lux':'lux'
	},
	
	// Sonderzeichen im FoW ersetzen
	charmap_search: ['&amp;#92', '&amp;#47', '&amp;', '', '', '', '\\\'', '', '', '', ''],
	charmap_replace: ['\\', '/', '&', '&dagger;', '&bull;', '´', '\'', '˜', '”', '™', '€'],
	
	/**
	 * Einstellungen laden
	 */
	getPrefs: function() {
		var prefManager = Components.classes["@mozilla.org/preferences-service;1"].getService(Components.interfaces.nsIPrefBranch);
		oddbtool.prefs = {
			url: prefManager.getCharPref('extensions.oddbtool.url'),
			fow: prefManager.getBoolPref('extensions.oddbtool.fow'),
			auto: prefManager.getBoolPref('extensions.oddbtool.auto'),
			settings: prefManager.getBoolPref('extensions.oddbtool.settings')
		}
	},
	
	/**
	 * Einstellungen aus dem Menü heraus öffnen
	 */
	preferences_open: function() {
		try {
			return window.openDialog(
				"chrome://oddbtool/content/options.xul", 
				"Einstellungen",
				"top=200,left=200"
			);
		}catch(e){}
	},
	
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
		for(p=0;p<arr.length;p++) if (item == arr[p]) return true;
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
	 * ist die aktuelle Seite eine Seite, die geparst werden kann?
	 * @param url String
	 * @return bool
	 */
	isParsePage: function(url) {
		var regex_sys = /\?op=system&sys=\d+(|&galx=\d+)$/;
		var regex_planov = /\?op=planlist$/;
		var regex_plani = /\?op=planet&index=\d+/;
		var regex_orbit = /\?op=orbit&index=\d+$/;
		var regex_einstellungen = /\?op=settings$/;
		var regex_sitter = /\?op=sitter$/;
		var regex_flov = /\?op=fleet$/;
		var regex_toxx = /\?op=orbit&index=\d+&bioatack=1$/;
		
		// geeignete Seite
		if(regex_sys.exec(url) != null || regex_planov.exec(url) != null || regex_plani.exec(url) != null || regex_orbit.exec(url) != null || regex_einstellungen.exec(url) != null || regex_sitter.exec(url) != null || regex_flov.exec(url) != null || regex_toxx.exec(url) != null) {
			return true;
		}
		
		// ungeeignete Seite
		return false;
	},
	
	/**
	 * ist die aktuelle Seite die Einstellungs- oder Sitterseite?
	 * @param url String
	 * @return bool
	 */
	isSettingsPage: function(url) {
		var regex_einstellungen = /\?op=settings$/;
		var regex_sitter = /\?op=sitter$/;
		
		// Einstellungen oder Sitter
		if(regex_einstellungen.exec(url) != null || regex_sitter.exec(url) != null) {
			return true;
		}
		
		// andere Seite
		return false;
	},
	
	/**
	 * ist die aktuelle Seite eine Toxx-Seite?
	 * @param url String
	 * @return bool
	 */
	isToxxPage: function(url) {
		var regex_toxx = /\?op=orbit&index=(\d+)&bioatack=1$/;
		
		// Einstellungen oder Sitter
		if(regex_toxx.exec(url) != null) {
			return true;
		}
		
		// andere Seite
		return false;
	},
	
	/**
	 * Seite wird geladen
	 * -> FoW-Ausgleich und Autoparser starten
	 */
	loadPage: function(e) {
		var page = e.target;
		var url = page.location;
		
		// handelt es sich um eine OD-Seite?
		if(!oddbtool.isODPage(url)) return false;
		
		// jQuery laden
		if(!oddbtool.jqueryload) {
			var subscriptLoader = Components.classes["@mozilla.org/moz/jssubscript-loader;1"]
						  .getService(Components.interfaces.mozIJSSubScriptLoader);
			subscriptLoader.loadSubScript("chrome://oddbtool/content/jquery1.7.1.js");
			
			jQuery.noConflict();
			
			$ = function(selector,context){ return new jQuery.fn.init(selector,context||gBrowser.contentDocument); };
			$.fn = $.prototype = jQuery.fn;
			
			oddbtool.jQuery = jQuery;
			
			jQuery.ajaxSetup({
			  cache: false,
			  dataType: 'xml'
			});
			
			// Logo klickbar machen
			$('#oddbtoollogo',document).live('click', function() {
				oddbtool.preferences_open();
			});
			
			// Parsen-Link
			$('#oddbtoolparselink',document).live('click', function() {
				oddbtool.parser(false, false, true);
			});
			
			// Menüs
			$('div.oddbtoolpfeil',document).live('mousedown', function(){
				var id = $(this).attr('id');
				id = id.replace(/oddbtoolpfeil/g, '');
				$('div.oddbtoolmenu').hide();
				$('#oddbtoolmenu'+id).show();
			});
			$('div.oddbtoolmenu',document).live('mouseleave', function(){
				$(this).hide();
			});
			
			oddbtool.jqueryload = true;
		}
		// handelt es sich um eine Seite, die geparst werden kann?
		if(!oddbtool.isParsePage(url)) return false;
		
		
		// Einstellungen neu laden
		oddbtool.getPrefs();
		
		// CSS einbinden
		var css = ['#oddbtoolwin {position:absolute; left:240px; top:5px; width:290px; font-family:Arial,Sans; font-size:9px; color:white; z-index:2; background-color:rgba(0,0,0,0.5)}',
		'#oddbtoolwin a {font-family:Arial,Sans; font-size:9px}',
		'#oddbtoollogo {position:absolute; top:1px; left:200px; width:32px; height:32px; background-image:url('+oddbtool.prefs.url+'img/layout/fowsprite32.png); cursor:pointer; z-index:2}',
		
		'#oddbtoolheadline{position:absolute; top:85px; left:450px; width:650px; text-align:center;}',
		
		'#oddbtoolfowtbl{position:absolute; top:525px; left:300px; width:700px; background-color:rgba(255,255,255,0.15); padding:10px; -moz-border-radius:12px; border-radius:12px; font-family:Arial,Sans; font-size:12px; color:white; z-index:1;}',
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
		'.oddbtoolrtiroc {background-position:-167px 0px;}',
		'#oddbtoolkommentar {position:absolute; top:350px; left:400px; background-color:rgba(30,30,30,0.9); padding:8px; font-size:11px; -moz-border-radius:8px; border-radius:8px; text-align:right; display:none; z-index:4;}',
		'#oddbtoolkommentar iframe {width:160px; height:85px; border:none;}',
		'#oddbtoolkommentar a {font-size:13px; font-weight:bold; cursor:pointer;}'
		].join('');
		
		
		$('head',page).append('<style type="text/css">'+css+'</style>');
		
		// Logo und log-div erzeugen
		$('body',page).append('<div id="oddbtoollogo" class="oddbtoolsprite" title="Einstellungen f&uuml;r das ODDB Tool &auml;ndern"></div><div id="oddbtoolwin"></div>');
		
		// Planet getoxxt
		if(oddbtool.isToxxPage(url)) {
			oddbtool.toxxen(page);
			
			// Parsen verhindern
			return false;
		}
		
		var data = false;
		
		// FoW-Ausgleich in Systemen
		var regex_sys = /\?op=system&sys=(\d+)/;
		if(oddbtool.prefs.fow && regex_sys.exec(url) != null) {
			// Seite parsen
			data = oddbtool.parsePage(page);
			oddbtool.fow(page, data);
		}
		
		// Autoparser
		if(oddbtool.prefs.auto) {
			window.setTimeout(
				function() {
					oddbtool.parser(page, false);
				},
				200
			);
		}
		else {
			$('#oddbtoolwin',page).append('<a href="javascript:void(0)" id="oddbtoolparselink">[Seite parsen]</a>');
		}
	},
	
	/** 
	 * Planet getoxxt
	 * @param page Seite
	 */
	toxxen: function(page) {
		var url = page.location;
		p = /op=orbit&index=(\d+)&bioatack=1/;
		var data = p.exec(url);
		if(data != null) {
			var input = oddbtool.getQuelltext(page);
			if(input.indexOf(' Wohnungen sind unbewohnbar geworden! ') != -1) {
				var id = data[1];
				// getoxxte Summe ermitteln
				p = />([\d\.]+) Wohnungen sind unbewohnbar/;
				data = p.exec(input);
				if(data != null) {
					var getoxxt = data[1];
					getoxxt = getoxxt.replace(/\./g, '');
				}
				else {
					alert('Konnte getoxxt-Summe nicht ermitteln!');
					return false;
				}
				
				// abschicken
				var addr = oddbtool.prefs.url+'index.php?p=ajax_general&sp=toxx&id='+id+'&bev='+getoxxt+'&plugin&ajax';
				jQuery.get(addr);
				
				$('#oddbtoolwin',page).append('Planet als getoxxt markiert<br>');
			}
		}
	},
	
	/**
	 * FoW-Ausgleich in Systemen
	 * @param page Seite
	 * @param data geparste Daten @see oddbtool.parsePage()
	 */
	fow: function(page, data) {
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
		
		// Debug-Ausgabe
		//$('#oddbtoolwin',page).append('<br><br>'+decodeURIComponent(jQuery.param(data)));
		
		
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
			type: 'get',
			url: addr,
			data: false,
			success: function(result){
				// Fehler ausgeben
				var error = $(result).find('error').text();
				if(error) {
					// Loginfehler ändern
					if(error.indexOf('mehr eingeloggt') != -1) error = 'nicht eingeloggt!';
					
					// ausgeben
					$('#oddbtoolfow',page).append('<span style="color:red">'+error+'</span>');
					return false;
				}
				// Authorisierung
				var auth = $(result).find('auth').text();
				if(auth == 'false') {
					$('#oddbtoolfow',page).append('<span style="color:red">nicht eingeloggt!</span>');
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
							
							tooltip += '<table style="border:0;padding:0;margin:0;width:auto"><tr><td><img src="http://www.omega-day.com/spielgrafik//grafik/aufbautechniken/kultur_erz_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['erz']+' %</td><td style="padding-left:10px">'+data['pl'][i]['erzmenge']+'</td></tr><tr><td><img src="http://www.omega-day.com/spielgrafik//grafik/aufbautechniken/kultur_metall_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['erz']+' %</td><td style="padding-left:10px">'+data['pl'][i]['metallmenge']+'</td></tr><tr><td><img src="http://www.omega-day.com/spielgrafik//grafik/aufbautechniken/kultur_wolfram_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['wolfram']+' %</td><td style="padding-left:10px">'+data['pl'][i]['wolframmenge']+'</td></tr><tr><td><img src="http://www.omega-day.com/spielgrafik//grafik/aufbautechniken/kultur_kristall_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['kristall']+' %</td><td style="padding-left:10px">'+data['pl'][i]['kristallmenge']+'</td></tr><tr><td><img src="http://www.omega-day.com/spielgrafik//grafik/aufbautechniken/kultur_flour_us.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['fluor']+' %</td><td style="padding-left:10px">'+data['pl'][i]['fluormenge']+'</td></tr></table><br>Bev&ouml;lkerung: '+data['pl'][i]['bev']+'<br>Gr&ouml;&szlig;e: '+data['pl'][i]['groesse']+'<br><br>'+data['pl'][i]['additional'];
								
							data['pl'][i]['name'] = oddbtool.str_replace(oddbtool.charmap_search, oddbtool.charmap_replace, data['pl'][i]['name']);
							tooltip = tooltip.replace(/'/g, "\\'");
							tooltip = tooltip.replace(/"/g, '&quot;');
							
							// Kommentar?
							if(data['pl'][i]['comment'] == 1) {
								data['pl'][i]['comment'] = '<div style="width:22px;height:18px;background-image:url('+oddbtool.prefs.url+'img/layout/sprite32.png);background-position:-480px -54px;float:left;margin-top:-5px"></div>';
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
								/* ;background-color:black;background-image:url('+oddbtool.prefs.url+'img/planeten/'+data['pl'][i]['typ']+'.jpg)
								*/
								content += '<a class="oddbtoollink" href="index.php?op=planet&index='+data['pl'][i]['id']+'" onmouseover="dlt(\''+tooltip+'\', \''+data['pl'][i]['name']+':\')" onmouseout="nd()"><table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="cursor:pointer;left:'+x+'px;top:'+y+'px"><tr><td style="background-position:-'+oddbtool.geb[gpl[36]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[35]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[29]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[23]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[30]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[34]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[32]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[24]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[18]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[10]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[19]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[25]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[28]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[14]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[6]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[2]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[7]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[15]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[22]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[13]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[5]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[1]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[3]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[11]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[31]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[17]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[9]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[4]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[8]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[16]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[33]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[27]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[21]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[12]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[20]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[26]]+'px 0px"></td></tr><tr><td colspan="6" style="background-image:none;text-align:center;font-size:11px;padding-bottom:3px;padding-top:3px">'+data['pl'][i]['scan']+data['pl'][i]['comment']+'</td></tr></table></a>';
								
								//$('body',page).append(content);
								
								// Gebäude im Orbit
								y -= 32;
								
								content += '<table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="left:'+x+'px;top:'+y+'px"><tr><td style="background-position:-'+oddbtool.geb[gor[1]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[2]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[3]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[4]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[5]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[6]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gor[7]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[8]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[9]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[10]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[11]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[12]]+'px 0px"></td></tr></table>';
							}
							else {
								// Planet-Overlay ohne Scan
								/*
								;background-color:black;background-image:url('+oddbtool.prefs.url+'img/planeten/'+data['pl'][i]['typ']+'.jpg)
								*/
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
								
								content += '<img src="../spielgrafik//grafik/leer.gif" alt="" class="oddbtoolrasse oddbtoolr'+data['pl'][i]['rasse']+'"><a href="index.php?op=usershow&welch='+data['pl'][i]['inhaber']+'">'+data['pl'][i]['username']+'</a>';
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
							x = xbase + i*xadd + 80;
							y = ybase + i*yadd + 100;
							
							content += '<div id="oddbtoolmenu'+data['pl'][i]['id']+'" class="oddbtoolmenu" style="top:'+(y+15)+'px;left:'+(x-130)+'px;display:none"><a href="'+oddbtool.prefs.url+'index.php?p=show_planet&id='+data['pl'][i]['id']+'" target="_blank">in der DB &ouml;ffnen</a><a href="javascript:void(0)" onclick="var kf = document.getElementById(\'oddbtoolkommentar\'); kf.style.left = \''+(x-130)+'px\'; kf.style.top = \''+(y-5)+'px\'; kf.style.display=\'block\'; document.getElementById(\'oddbtoolkiframe\').src=\''+oddbtool.prefs.url+'index.php?p=show_planet&id='+data['pl'][i]['id']+'&sp=kommentar_editgame\';">Kommentar &auml;ndern</a>';
							if(toxxrechte) {
								// raiden und toxxen
								content += '<div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=raid&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>als geraidet markiert</a>\'">als geraidet markieren</a></div><div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=toxx&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>als getoxxt markiert</a>\'">als getoxxt markieren</a></div>';
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
				
				// Debug-Ausgabe
				//$('#oddbtoolfow',page).append('<span style="color:#00ff00">'+decodeURIComponent(jQuery.param(data))+'</span>');
				
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
	},
	
	/**
	 * Quelltext einer Seite parsen und abschicken
	 * @param page Seite
	 * @param data2 geparste Daten @see oddbtool.parsePage()
	 * @param manual bool manuell aufgerufen
	 */
	parser: function(page, data2, manual) {
		
		// automatisch aufgerufen
		if(typeof(manual) == 'undefined') {
			manual = false;
		}
		
		// über das Menü aufgerufen
		if(!page) {
			page = gBrowser.contentDocument
			var url = page.location;
			
			if(!oddbtool.isODPage(url) || !oddbtool.isParsePage(url)) return false;
		}
		
		// Einstellungen und Sitter bei gegebener Einstellung nicht scannen
		else if(oddbtool.prefs.auto && !oddbtool.prefs.settings && oddbtool.isSettingsPage(page.location) && !manual) {
			$('#oddbtoolwin',page).append('<a href="javascript:void(0)" id="oddbtoolparselink">[Seite parsen]</a>');
			return false;
		}
		
		// doppeltes Parsen verhindern
		if($('body',page).find('#oddbtoolparser').attr('id') != null) return false;
		
		// Parser-Link verstecken
		$('#oddbtoolparselink',page).hide();
		
		// Meldung anzeigen
		$('#oddbtoolwin',page).append('<span id="oddbtoolparser">Parse Quelltext... </span>');
		
		// Seite parsen
		var pdata = oddbtool.parsePage(page, manual);
		
		// Parsen fehlgeschlagen
		if(!pdata) {
			//$('#oddbtoolparser',page).append('<span style="color:red">fehlgeschlagen! (unbekannter Quelltext)</span>');
			return false;
		}
		
		// Debug-Ausgabe /////////////////////////////////////////////////////////////////////////
		//$('#oddbtoolwin',page).append('<br><br>'+decodeURIComponent(jQuery.param(pdata)));
		
		var content = decodeURIComponent(jQuery.param(pdata));
		content = content.replace(/&/gi, " ");
		content = content.replace(/</gi, "[");
		content = content.replace(/>/gi, "]");
		$('body',page).append('<div style="display:none">'+content+'</div>');
		
		var addr = oddbtool.prefs.url+'index.php?p=scan&sp=scan&plugin&version='+oddbtool.version+'&ajax';
		
		// Request absenden
		jQuery.ajax({
			type: 'post',
			url: addr,
			data: pdata,
			success: function(data){
				// Fehler ausgeben
				var error = $(data).find('error').text();
				if(error) {
					// Loginfehler ändern
					if(error.indexOf('mehr eingeloggt') != -1) error = 'nicht eingeloggt!';
					
					// ausgeben
					$('#oddbtoolparser',page).append('<span style="color:red">'+error+'</span>');
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
	},
	
	
	/**
	 * Quelltext einer Seite ermitteln
	 * @param page Seite
	 * @return string Quelltext
	 */
	getQuelltext: function(page) {
		// Benchmark:
		//var t1 = new Date().getTime() / 1000;
		
		// neu: Quelltext über DOM abgreifen
		var s = $('body', page).html();
		s = s.replace(/&amp;/g, '&');
		return s;
		
		
		/*
		// code copied and modified from odhelper extension 
		// (http://helper.omega-day.com/)
		// and db.moz.plugin
		// (http://db.wiki.seiringer.eu/plugin/start)

		// originial: doc = window._content.document;
		// but _content is deprecated
		var doc = page || window.content.document;
		
		// Sonderbehandlung Orbit
		if(page) {
			var url = ""+page.location;
			if(url.indexOf('orbit') != -1) {
				// Quelltext über page-DOM abrufen.
				var s = $('html', page).html();
				s = s.replace(/&amp;/g, '&');
				
				return s;
			}
		}
		
		// Part 1 : get the history entry (nsISHEntry) associated with the document
		var webnav;
		try {
		  // Get the DOMWindow for the requested document.  If the DOMWindow
		  // cannot be found, then just use the _content window...
		  var win = doc.defaultView;
		  if (win == window) {
			// originial: win = _content;
			// but _content is deprecated
			win = content;
		  }
		  var ifRequestor = win.QueryInterface(Components.interfaces.nsIInterfaceRequestor);
		  webNav = ifRequestor.getInterface(Components.interfaces.nsIWebNavigation);
		} catch(err) {
		  // If nsIWebNavigation cannot be found, just get the one for the whole window...
		  webNav = getWebNavigation();
		}
		try {
		  var PageLoader = webNav.QueryInterface(Components.interfaces.nsIWebPageDescriptor);
		  var pageCookie = PageLoader.currentDescriptor;
		  var shEntry = pageCookie.QueryInterface(Components.interfaces.nsISHEntry);
		} catch(err) {
		  // If no page descriptor is available, just use the URL...
		}
		//// Part 2 : open a nsIChannel to get the HTML of the doc
		var url = doc.URL;
		var urlCharset = doc.characterSet;
		var ios = Components.classes["@mozilla.org/network/io-service;1"]
							.getService(Components.interfaces.nsIIOService);
	  
		var channel = ios.newChannel( url, urlCharset, null );
		channel.loadFlags |= Components.interfaces.nsIRequest.VALIDATE_NEVER;
		channel.loadFlags |= Components.interfaces.nsIRequest.LOAD_FROM_CACHE;
	  //    channel.loadFlags |= Components.interfaces.nsICachingChannel.LOAD_ONLY_FROM_CACHE;
	  
		try {
		  // Use the cache key to distinguish POST entries in the cache (see nsDocShell.cpp)
		  var cacheChannel = channel.QueryInterface(Components.interfaces.nsICachingChannel);
		  cacheChannel.cacheKey = shEntry.cacheKey;
		} catch(e) {
		}
	  
		var stream = channel.open();
		const scriptableStream = Components.classes["@mozilla.org/scriptableinputstream;1"].createInstance(Components.interfaces.nsIScriptableInputStream);
		scriptableStream.init( stream );
	  
		var s = "";
		try {
		  while( scriptableStream.available()>0 ) {
			s += scriptableStream.read(scriptableStream.available());
		  }
		} catch(e) {
		}
		scriptableStream.close();
		stream.close();
		
		return s;
		*/
	},
	
	/**
	* Omega-Day-Seite parsen
	* @param page Seite
	* @return object geparstes Objekt
	*/
	parsePage: function(page, manual) {
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
			if(world.length && world.html().indexOf('int9') == -1) {
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
								data = data.split('<br>');
								
								pl[i]['erz'] = data[3].replace(/[^\d+]/g, '');
								pl[i]['wolfram'] = data[4].replace(/[^\d+]/g, '');
								pl[i]['kristall'] = data[5].replace(/[^\d+]/g, '');
								pl[i]['fluor'] = data[6].replace(/[^\d+]/g, '');
								pl[i]['bev'] = data[8].replace(/[^\d+]/g, '');
								pl[i]['groesse'] = data[10].replace(/[^\d+]/g, '');
								
								// Workaround: Unbewohnbare Planis Größe 2
								if(pl[i]['groesse'] == '') {
									pl[i]['groesse'] = 2;
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
							
							// Workaround: Unbewohnbare Planis Größe 2
							if(pl[i]['groesse'] == '') {
								pl[i]['groesse'] = 2;
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
				data = ctree.find('img[src*="planeteninfo"]').parent().attr('onmouseover');
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
					out['erzp'] = ctree.find('#erzproduktion').html().replace(/[^\d]/g, '');
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
						out['g'+i] = data.find('img[name="pod'+i+'"]').attr('src').replace(/^.*\/grafik\/(?:gebaude\/)*/, '').replace(/leer.gif/, '');
					}
					
					// Orbitgebäude
					data = data.prev();
					for(i=1; i<=12; i++) {
						out['o'+i] = data.find('img[name="wpod'+i+'"]').attr('src').replace(/^.*\/grafik\/(?:gebaude\/)*/, '');
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
						out['steuer'] = ctree.find('img[src*="credits_us"]').last().next().children('b').html().replace(/^(\S*) .*$/, '$1').replace(/[^\d]/g, '');
						
						// Gesamt-FP
						data = ctree.find('img[src*="forschung_forschen"]').next().children('b').html()
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
						if($(this).find('img[src*="schiffe"]').length) {
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
					// neu
					out['bes'] = 1;
					// alt
					out['inva'] = 4;
					
					// Besatzer
					data = ctree.find('img[src*="warn.gif"]').parent().next().find('a[href*="usershow"]');
					if(data.length) {
						// neu
						out['besuser'] = data.attr('href').replace(/[^\d]/g, '');
						// alt
						out['user'] = out['besuser'];
					}
				}
				
				// Ende-Pattern für Kolos und Invas
				var p = /(?:bernahmezeitpunkt|Completion date): (?:.{3}), ([^<]+)<br/;
				
				// Kolo
				if(input.indexOf('<b>Dieser Planet wird gerade kolonisiert.') != -1  || input.indexOf('<b>This planet is currently being colonised') != -1) {
					// neu
					out['kolo'] = 1;
					// alt
					out['inva'] = 5;
					
					// Ende
					data = p.exec(input);
					if(data == null) {
						throw 'Konnte Kolodaten nicht ermitteln!';
					}
					// neu
					out['koloende'] = data[1];
					// alt
					out['ende'] = data[1];
					
					// Kolonisator
					data = ctree.find('div[name="alldiv"] a[href*="usershow"]');
					if(data.length) {
						// neu
						out['kolouser'] = data.attr('href').replace(/[^\d]/g, '');
						// alt
						out['user'] = out['kolouser'];
					}
				}
				
				// Inva
				if(input.indexOf('<b>Dieser Planet wird gerade invadiert!') != -1 || input.indexOf('<b>This planet is currently being invaded') != -1) {
					// neu
					out['inv'] = 1;
					// alt
					out['inva'] = 1;
					
					data = p.exec(input);
					if(data == null) {
						throw 'Konnte Invadaten nicht ermitteln!';
					}
					// neu
					out['invende'] = data[1];
					// alt
					out['ende'] = data[1];
					
					// Aggressor
					data = ctree.find('div[name="alldiv"] a[href*="usershow"]');
					if(data.length) {
						// neu
						out['invauser'] = data.attr('href').replace(/[^\d]/g, '');
						// alt
						out['user'] = out['invauser'];
					}
				}
				
				// Genesis
				if(input.indexOf('<b>An diesem Planeten wird ein Genesis-Projekt gestartet.') != -1) {
					// neu
					out['gen'] = 1;
					// alt
					out['inva'] = 3;
					
					p = /(?:ndung|Firing): (?:.{3}), ([^<]+)<br/;
					
					data = p.exec(input);
					if(data == null) {
						throw 'Konnte Genesis-Daten nicht ermitteln!';
					}
					
					// neu
					out['genende'] = data[1];
					// alt
					out['ende'] = data[1];
				}
				
				// Reso
				if(input.indexOf('sich gerade ein Resonator auf, um diesen Planeten und alle Schiffe im Orbit zu vernichten!') != -1 || input.indexOf('<b>A resonator is currently charging up so that it can destroy this planet and all ships in orbit around it') != -1) {
					// neu
					out['reso'] = 1;
					// alt
					out['inva'] = 2;
					
					p = /(?:ndung|Firing): (?:.{3}), ([^<]+)</;
					data = p.exec(input);
					
					if(data == null) {
						throw 'Konnte Reso-Daten nicht ermitteln!';
					}
					
					// neu
					out['resoende'] = data[1];
					// alt
					out['ende'] = data[1];
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
					if(input.indexOf('>An diesem Planeten läuft ein Bergbauvorgang.<') != -1 || input.indexOf('>An diesem Planeten l&auml;uft ein Bergbauvorgang.<') != -1 || input.indexOf('>A mining operation is active.<') != -1) {
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
				
				// Format: AA-HandelNeutral-HandelAlly-HandelMeta-KS[1,2,3]-Steuern[1,2,3]
				
				// AA
				out['einst'] = '0';
				// HandelNeutral
				out['einst'] += ctree.find('#pn2:checked').length;
				// HandelAlly
				out['einst'] += ctree.find('#pv2:checked').length;
				// HandelMeta
				out['einst'] += '0';
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
			// Flottenübersicht
			//
			else if(ctree.find('a[href="#flott"]').length) {
				out['typ'] = 'floview';
				
				// Spieler
				try {
					out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
				}
				catch(e) {
					throw 'Konnte Spieler nicht ermitteln!';
				}
				
				var data = ctree.find('#div4 > .fleet-content');
				
				// Tagesabrechnung wird nicht angezeigt
				if(data.find('b').length < 12) {
					throw 'Tagesabrechnung wird nicht angezeigt!';
				}
				
				data = data.find('b');
				
				// zuletzt gezahlte Flottensteuer
				out['steuer'] = $(data[1]).html().replace(/[^\d+]/g, '');
				
				/* übernommen aus dem alten Parser */
				var input = ctree.find('#div4 > .fleet-content').html();
				var p;
				
				// privat-KoP
				p = /mit <b>([\d\.]*)<\/b> nicht unterstellbare(?:n|m) Schiff(?:en|) ben\S*tigt <b>([\d\.]*)<\/b> KoP \(<b>([\d\.]*)<\/b> KoP/;
				data = p.exec(input);
				if(data == null) {
					throw 'Konnte Privat-Kommandopunkte nicht ermitteln!';
				}
				for(var i=1; i<=3; i++) {
					data[i] = data[i].replace(/\./g, '');
					if(data[i] == '') {
						data[i] = 0;
					}
				}
				
				out['schiffe'] = parseInt(data[1]);
				
				out['pkop'] = data[2];
				out['pkopmax'] = data[3];
				
				// Orbiter-KOP dazuzählen
				p = /Orbiter kosten zus\S*tzlich <b>(\d+)<\/b> private/;
				data = p.exec(input);
				if(data != null) {
					out['pkop'] = parseInt(out['pkop']) + parseInt(data[1]);
				}
				
				// AF-KoP
				p = /mit <b>([\d\.]*)<\/b> <b>unterstellbare(?:n|m) Schiff(?:en|)<\/b> ben\S*tigt <b>([\d\.]*)<\/b> KoP \(<b>([\d\.]*)<\/b> KoP/;
				data = p.exec(input);
				if(data == null) {
					throw 'Konnte AF-Kommandopunkte nicht ermitteln!';
				}
				for(var i=1; i<=3; i++) {
					data[i] = data[i].replace(/\./g, '');
					if(data[i] == '') {
						data[i] = 0;
					}
				}
				
				out['schiffe'] += parseInt(data[1]);
				
				out['kop'] = data[2];
				out['kopmax'] = data[3];
				
				// Schiffe im Basar -> vorherige Werte überschreiben
				p = /sich <b>(\d+)<\/b> Privat-KoP und <b>(\d+)<\/b> normale KoP ergeben./;
				data = p.exec(input);
				if(data != null) {
					out['pkop'] = data[1];
					out['kop'] = data[2];
				}
				
				// Bergbauschiffe
				out['bb'] = [];
				
				try {
					ctree.find('#div9 .tabletrans td:nth-child(5) > a[href*=orbit]:first-child').each(function() {
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
					sfrom.push(oddbtool.jQuery.trim($(this).clone().children('font').remove().end().html()));
				});
				
				out['sitterfrom'] = sfrom;
				
				// Sitter zu anderen
				var sto = [];
				ctree.find('td:nth-child(2) > .box td[width]:first-child').each(function() {
					sto.push(oddbtool.jQuery.trim($(this).clone().children('font').remove().end().html()));
				});
				
				out['sitterto'] = sto;
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
	}
};

/**
 * Event-Listener
 */
// Firefox starten

window.addEventListener("load", oddbtool.onLoad, false);