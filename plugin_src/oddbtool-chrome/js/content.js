var oddbtool = {
	
	/**
	 * Version
	 */
	version: '2.4',
	odworld: 'int12',
	
	
	/**
	 * Gebäude-Positionen auf dem Sprite
	 */
	geb: {
		0:0,
		'-1':16,
		'-2':32,
		'-3':48,
		'-5':64,
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
		1057:768,
		
		1200:992,
		1201:1008,
		1202:1024,
		1203:1040,
		1205:1056,
		1206:1072,
		1207:1088,
		1208:1104,
		1210:1120,
		1211:1136,
		1216:1152,
		1217:1168,
		1218:1184,
		1220:1200,
		1221:1216,
		1223:1232,
		1224:1248,
		1225:1264,
		1230:1280,
		1234:1296,
		
		1500:1312,
		1501:1328,
		1502:1344,
		
		'-4':1360
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
	 * Seite laden 
	 */
	loadPage: function(page) {
		
		var url = page.location;
		
		// zum Parsen geeignete Seite?
		if(!oddbtool.isParsePage(url)) {
			return false;
		}
		
		// CSS mit Pfad einbinden
		var css = [
			'#oddbtoollogo {position:absolute; top:1px; left:200px; width:32px; height:32px; background-image:url('+chrome.extension.getURL('img/fowsprite32.png')+'); cursor:pointer; z-index:2}',
			'.oddbtoolplanet td {width:16px; height:14px; background-image:url('+chrome.extension.getURL('img/gebaeude_small.png')+')}',
			'.oddbtoolpfeil {position:absolute; width:15px; height:14px; background-image:url('+chrome.extension.getURL('img/fowsprite32.png')+'); background-position:-32px 0px; z-index:3; cursor:pointer}',
			'.oddbtoolrasse {margin:8px 0px 0px -30px; float:right; background-image:url('+chrome.extension.getURL('img/fowsprite32.png')+'); width:30px; height:27px; background-repeat:no-repeat;}'
		].join("\n");
		
		$('head',page).append('<style type="text/css">'+css+'</style>');
		
		// jQuery-Konfiguration und Event-Handler
		jQuery.ajaxSetup({
		  cache: false,
		  dataType: 'xml'
		});
		
		// Logo klickbar machen
		$(document).on('click', '#oddbtoollogo', function() {
			window.open(chrome.extension.getURL('options.html'));
		});
		
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
		
		// Logo und log-div erzeugen
		$('body',page).append('<div id="oddbtoollogo" class="oddbtoolsprite" title="Einstellungen f&uuml;r das ODDB Tool &auml;ndern"></div><div id="oddbtoolwin"></div>');
		
		// Planet getoxxt
		if(oddbtool.prefs.auto_toxx && oddbtool.isToxxPage(url)) {
			
			oddbtool.toxxen(page);
			
			// Parsen verhindern
			return false;
		}
		
		var data = false;
		
		// FoW-Ausgleich in Systemen
		if(oddbtool.prefs.fow && oddbtool.parserRegex.system.exec(url) != null) {
			// Seite parsen
			var data2 = oddbtool.parsePage(page);
			
			// Parser-Daten kopieren
			data = $.extend(true, {}, data2);
			
			oddbtool.fow(page, data2);
		}
		
		// Autoparser
		for(var i in oddbtool.parserRegex) {
			
			if(oddbtool.parserRegex[i].exec(url) != null) {
				
				// Bergbauschiffe auf Flottenübersich mappen
				if(i == 'floviewbbs') {
					i = 'floview';
				}
				
				if(typeof(oddbtool.prefs["auto_"+i]) != 'undefined' && oddbtool.prefs["auto_"+i]) {
					window.setTimeout(
						function() {
							oddbtool.parser(page, data);
						},
						200
					);
				}
				else {
					$('#oddbtoolwin',page).append('<a href="javascript:void(0)" id="oddbtoolparselink">[Seite parsen]</a>');
				}
				
				
				return true;
			}
		}
		
		
		
		
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
		toxx: /\?op=orbit&index=\d+&bioatack=1$/,
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
	},
	
	/**
	 * ist die aktuelle Seite eine Toxx-Seite?
	 * @param url String
	 * @return bool
	 */
	isToxxPage: function(url) {
		
		// Toxx-Seite
		if(oddbtool.parserRegex.toxx.exec(url) != null) {
			return true;
		}
		
		// andere Seite
		return false;
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
					$('#oddbtoolwin',page).append('<span style="color:red">Konnte Getoxxt-Summe nicht ermitteln!</span><br>');
					return false;
				}
				
				// abschicken
				var addr = oddbtool.prefs.url+'index.php?p=ajax_general&sp=toxx&id='+id+'&bev='+getoxxt+'&plugin&ajax';
				jQuery.get(addr);
				
				$('#oddbtoolwin',page).append('Planet als getoxxt markiert<br>');
			}
		}
	}
}