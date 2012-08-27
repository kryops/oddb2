/**
 * Quelltext einer Seite parsen und abschicken
 * @param page Seite
 * @param pdata bereits geparste Daten @see oddbtool.parsePage()
 * @param manual bool manuell aufgerufen
 */
oddbtool.parser = function(page, pdata, manual) {
	
	// automatisch aufgerufen
	if(typeof(manual) == 'undefined') {
		manual = false;
	}
	
	// über das Menü aufgerufen
	if(!page) {
		page = document;
		var url = page.location;
		
		if(!oddbtool.isParsePage(url)) return false;
	}
	
	// Parser-Link verstecken
	$('#oddbtoolparselink',page).hide();
	
	// vorherigen Parser-Container entfernen
	$('#oddbtoolparser',page).remove();
	
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
	
	// Debug-Ausgabe /////////////////////////////////////////////////////////////////////////
	//$('#oddbtoolwin',page).append('<br><br>'+decodeURIComponent(jQuery.param(pdata)));
	
	var content = decodeURIComponent(jQuery.param(pdata));
	content = content.replace(/&/gi, " ");
	content = content.replace(/</gi, "[");
	content = content.replace(/>/gi, "]");
	$('body',page).append('<div style="display:none">'+content+'</div>');
	
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
				// Loginfehler ändern
				if(error.indexOf('mehr eingeloggt') != -1) error = 'nicht eingeloggt!';
				
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
							data = data.replace(/http:\/\/[a-zA-Z0-9]*\.*omega/g, '');
							data = data.split('<br>');
							
							pl[i]['erz'] = data[4].replace(/[^\d+]/g, '');
							pl[i]['wolfram'] = data[5].replace(/[^\d+]/g, '');
							pl[i]['kristall'] = data[6].replace(/[^\d+]/g, '');
							pl[i]['fluor'] = data[7].replace(/[^\d+]/g, '');
							pl[i]['bev'] = data[9].replace(/[^\d+]/g, '');
							pl[i]['groesse'] = data[11].replace(/[^\d+]/g, '');
							
							// Workaround: Unbewohnbare Planis Größe 2
							if(pl[i]['groesse'] == '') {
								pl[i]['groesse'] = 2;
								pl[i]['bev'] = 0;
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
							pl[i]['bev'] = 0;
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
					
					// Kolo
					if($(this).find('tr:last-child img[src*="flotte-kolo.gif"]').length) {
						pl[i]['kolo'] = 1;
					}
					
					// Genesis
					if($(this).find('tr:last-child img[src*="flotte-genesis.gif"]').length) {
						pl[i]['genesis'] = 1;
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
			
			// Schiffbau
			if(ctree.find('table[width="821"] td:last-child img[src*="schiffe"]').length) {
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
			if(input.indexOf('An diesem Planeten wird ein Genesis-Projekt gestartet.') != -1 || input.indexOf('At this planet a genesis project is initiated.') != -1) {
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
				
				// Genesis-Benutzer
				var genuser = ctree.find('.box td:contains("enesis") a[href^="?op=usershow"]'); 
				if(genuser.length) {
					out['genuser'] = genuser.attr('href').replace(/[^\d]/g, '');
					out['user'] = out['genuser'];
				}
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
			p = /nicht unterstellbar\) aus <b>([\d\.]*)<\/b> Schiff\(en\) ben\S*tigt <b>([\d\.]*)<\/b> KoP \(<b>([\d\.]*)<\/b> KoP/;
			
			data = p.exec(input);
			if(data == null) {
				
				p = /non-assignable\) of <b>([\d\.]*)<\/b> ship\(s\) use <b>([\d\.]*)<\/b> CoP \(<b>([\d\.]*)<\/b> CoP/;
				data = p.exec(input);
				
				if(data == null) {
					throw 'Konnte Privat-Kommandopunkte nicht ermitteln!';
				}
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
			p = /Orbiter kosten zus\S*tzlich <b>([\d\.]+)<\/b> private/;
			data = p.exec(input);
			
			if(data == null) {
				p = /orbiter\(s\) cost you additional  <b>([\d\.]+)<\/b> private/;
				data = p.exec(input);
			}
			
			if(data != null) {
				out['pkop'] = parseInt(out['pkop']) + parseInt(data[1].replace(/\./g, ''));
			}
			
			// AF-KoP
			p = /\(unterstellbar\) aus <b>([\d\.]*)<\/b> Schiff\(en\) ben\S*tigt <b>([\d\.]*)<\/b> KoP \(<b>([\d\.]*)<\/b> KoP/;
			data = p.exec(input);
			if(data == null) {
				p = /\(assignable\) of <b>([\d\.]*)<\/b> ship\(s\) use <b>([\d\.]*)<\/b> CoP \(<b>([\d\.]*)<\/b> CoP/;
				data = p.exec(input);
				
				if(data == null) {
					throw 'Konnte AF-Kommandopunkte nicht ermitteln!';
				}
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
			p = /sich <b>([\d\.]+)<\/b> Privat-KoP und <b>([\d\.]+)<\/b> normale KoP ergeben./;
			data = p.exec(input);
			
			if(data == null) {
				p = /resulting in <b>([\d\.]+)<\/b> private CoP and <b>([\d\.]+)<\/b> normal CoP/;
				data = p.exec(input);
			}
			
			if(data != null) {
				out['pkop'] = data[1].replace(/\./g, '');
				out['kop'] = data[2].replace(/\./g, '');
			}
			
			// Bergbauschiffe
			out['bb'] = [];
			
			// CHROME nth-Child 1 weniger als Firefox!
			try {
				ctree.find('#div9 .tabletrans td:nth-child(4) > a[href*="orbit"]:first-child').each(function() {
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