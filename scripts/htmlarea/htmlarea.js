//
// htmlArea v3.0 - Copyright (c) 2002 interactivetools.com, inc.
// This copyright notice MUST stay intact for use (see license.txt).
//
// A free WYSIWYG editor replacement for <textarea> fields.
// For full source code and docs, visit http://www.interactivetools.com/
//
// Version 3.0 developed by Mihai Bazon for InteractiveTools.
//           http://students.infoiasi.ro/~mishoo
//
// $Id$

// Creates a new HTMLArea object.  Tries to replace the textarea with the given
// ID with it.

function HTMLArea(textarea,baburl,babInstallPath, babLanguage,babPhpSelf,enabled,config) {
	
	if (HTMLArea.checkSupportedBrowser()) {
		if (typeof baburl == "undefined") {
			alert('HTMLArea : baburl missing');
		} else {
			this.baburl = baburl;
		}
		if (typeof babInstallPath == "undefined") {
			alert('HTMLArea : babInstallPath missing');
		} else {
			this.babInstallPath = babInstallPath;
			HTMLArea.babInstallPath = babInstallPath;
		}
		if (typeof babPhpSelf == "undefined") {
			alert('HTMLArea : babPhpSelf missing');
		} else {
			this.babPhpSelf = babPhpSelf;
			HTMLArea.babPhpSelf = babPhpSelf;
		}
		if (typeof babLanguage == "undefined" || babLanguage == 'en') {
			this.babLanguage = '';
		} else {
			this.babLanguage = babLanguage+'/';
		}
		
		if (typeof enabled == "undefined") {
			this.enabled = true;
		} else {
			this.enabled = enabled;
		}
		if (typeof config == "undefined") {
			this.config = new HTMLArea.Config(this.babLanguage);
		} else {
			this.config = config;
		}
		if (this.config.debug) {
			alert("DEBUG ON!!");
		}
		this._htmlArea = null;
		this._textArea = textarea;
		this._editMode = "wysiwyg";

	}
};


HTMLArea.Config = function (babLanguage) {
	this.version = "3.10";

	if (screen.width > 800)
	{
		this.width = screen.width-420;
	}
	else
	{
		this.width = "auto";
	}
	this.height = "auto";

	// the next parameter specifies whether the toolbar should be included
	// in the size or not.
	this.sizeIncludesToolbar = false;

	this.bodyStyle = "background-color: #fff; font-family: Verdana, Arial, sans-serif ; font-size:11px";
	this.invisibletable = "border:dashed 1pt;border-color:#CCCCCC";
	this.editorURL = HTMLArea.babInstallPath+"scripts/htmlarea/";

	// URL-s
	this.imgURL = "images/";
	this.popupURL = "popups/";

	this.debug = 0;

	this.replaceNextLines = 0;
	this.plainTextInput = 0;


	this.toolbar = [ [ "fontname", "space" ],
			 [ "fontsize", "space" ],
			 [ "formatblock", "space"],
			 [ "babstyle", "space", "textindicator","linebreak" ],
			 [ "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator" ],
			 [ "orderedlist", "unorderedlist", "outdent", "indent", "separator" ],
			 [ "forecolor", "backcolor", "separator" ],
			 [ "horizontalrule", "inserttable", "htmlmode", "separator" ],
			 [ "popupeditor","bablink", "linebreak" ],
			 [ "copy", "cut", "paste","undo","redo", "separator" ],
			 [ "bold", "italic", "underline", "separator","strikethrough", "subscript", "superscript", "separator" ],
			 ["cleanhtml","babarticle"]
		];

	this.fontname = {
		"Arial":           'arial,helvetica,sans-serif',
		"Courier New":     'courier new,courier,monospace',
		"Georgia":         'georgia,times new roman,times,serif',
		"Tahoma":          'tahoma,arial,helvetica,sans-serif',
		"Times New Roman": 'times new roman,times,serif',
		"Verdana":         'verdana,arial,helvetica,sans-serif',
		"impact":          'impact',
		"WingDings":       'wingdings'
	};

	this.fontsize = {
		"1 (8 pt)":  "1",
		"2 (10 pt)": "2",
		"3 (12 pt)": "3",
		"4 (14 pt)": "4",
		"5 (18 pt)": "5",
		"6 (24 pt)": "6",
		"7 (36 pt)": "7"
	};

	this.formatblock = {
		"Heading 1": "h1",
		"Heading 2": "h2",
		"Heading 3": "h3",
		"Heading 4": "h4",
		"Heading 5": "h5",
		"Heading 6": "h6",
		"Normal": "p",
		"Address": "address",
		"Formatted": "pre"
	};
	

	this.babstyle = HTMLArea.babstyle;


	//      ID              CMD                      ToolTip               Icon                        Enabled in text mode?
	this.btnList = {
		bold:           ["Bold",                 "Bold",               babLanguage+"ed_format_bold.gif",		false],
		italic:         ["Italic",               "Italic",             "ed_format_italic.gif",					false],
		underline:      ["Underline",            "Underline",          babLanguage+"ed_format_underline.gif",	false],
		strikethrough:  ["StrikeThrough",        "Strikethrough",      "ed_format_strike.gif",					false],
		subscript:      ["SubScript",            "Subscript",          "ed_format_sub.gif",						false],
		superscript:    ["SuperScript",          "Superscript",        "ed_format_sup.gif",						false],
		justifyleft:    ["JustifyLeft",          "Justify Left",       "ed_align_left.gif",						false],
		justifycenter:  ["JustifyCenter",        "Justify Center",     "ed_align_center.gif",					false],
		justifyright:   ["JustifyRight",         "Justify Right",      "ed_align_right.gif",					false],
		justifyfull:    ["JustifyFull",          "Justify Full",       "ed_align_justify.gif",					false],
		orderedlist:    ["InsertOrderedList",    "Ordered List",       "ed_list_num.gif",						false],
		unorderedlist:  ["InsertUnorderedList",  "Bulleted List",      "ed_list_bullet.gif",					false],
		outdent:        ["Outdent",              "Decrease Indent",    "ed_indent_less.gif",					false],
		indent:         ["Indent",               "Increase Indent",    "ed_indent_more.gif",					false],
		forecolor:      ["ForeColor",            "Font Color",         "ed_color_fg.gif",						false],
		backcolor:      ["BackColor",            "Background Color",   "ed_color_bg.gif",						false],
		horizontalrule: ["InsertHorizontalRule", "Horizontal Rule",    "ed_hr.gif",								false],
		inserttable:    ["InsertTable",          "Insert Table",       "insert_table.gif",						false],
		htmlmode:       ["HtmlMode",             "Toggle HTML Source", "ed_html.gif",							true],
		popupeditor:    ["popupeditor",          "Enlarge Editor",     "fullscreen_maximize.gif",				true],
		babimage:		["BabImage",			 "Insert Ovidentia Image","ed_image.gif",						false],
		bablink:		["bablink",				 "Insert Web Link",    "ed_link.gif",							false],
		cleanhtml:		["cleanhtml",			 "Clean HTML",		   "ed_bab_clean.gif",						false],
		copy:			["copy",				 "Copy",			   "ed_copy.gif",							false],
		cut:			["cut",					 "Cut",				   "ed_cut.gif",							false],
		paste:			["paste",				 "Paste",			   "ed_paste.gif",							false],
		undo:			["undo",				 "undo",			   "ed_undo.gif",							false],
		redo:			["redo",				 "Redo",			   "ed_redo.gif",							false],
		babfile:		["babfile",				 "Insert Ovidentia File","ed_bab_file.gif",						false],
		babarticle:		["babarticle",			"Ovidentia Article link","ed_bab_articleid.gif",				false],	
		babfaq:			["babfaq",				"Ovidentia FAQ link",	"ed_bab_faqid.gif",						false],
		babovml:		["babovml",				"Ovidentia OVML link",	"ed_bab_ovml.gif",						false]
	};

	// initialize tooltips from the I18N module
	for (var i in this.btnList) {
		var btn = this.btnList[i];
		if (typeof HTMLArea.I18N.tooltips[i] != "undefined") {
			btn[1] = HTMLArea.I18N.tooltips[i];
		}
	}

	if (typeof HTMLArea.I18N.formatblock != "undefined")
		this.formatblock = HTMLArea.I18N.formatblock;
};

/** Helper function: replace all TEXTAREA-s in the document with HTMLArea-s. */
HTMLArea.replaceAll = function(baburl,babInstallPath, babLanguage) {	
	var tas = document.getElementsByTagName("textarea");
	for (var i = tas.length; i > 0; (editor = new HTMLArea(tas[--i],baburl,babInstallPath, babLanguage)).generate());

};

// Creates the toolbar and appends it to the _htmlarea
HTMLArea.prototype._createToolbar = function () {

	var editor = this;	// to access this in nested functions

	var toolbar = document.createElement("div");
	this._toolbar = toolbar;
	toolbar.className = "toolbar";
	toolbar.unselectable = "1";
	if (editor.config.debug) {
		toolbar.style.border = "1px solid red";
	}
	var tb_row = null;
	var tb_objects = new Object();
	this._toolbarObjects = tb_objects;


	// creates a new line in the toolbar
	function newLine() {
		var table = document.createElement("table");
		table.border = "0px";
		table.cellSpacing = "0px";
		table.cellPadding = "0px";
		
		toolbar.appendChild(table);
		// TBODY is required for IE, otherwise you don't see anything
		// in the TABLE.
		var tb_body = document.createElement("tbody");
		table.appendChild(tb_body);
		tb_row = document.createElement("tr");
		tb_body.appendChild(tb_row);
	};
	// init first line
	newLine();

	// appends a new button to toolbar
	function createButton(txt) {
		// updates the state of a toolbar element
		function setButtonStatus(id, newval) {
			var oldval = this[id];
			var el = this.element;
			if (oldval != newval) {
				switch (id) {
				    case "enabled":
					if (newval) {
						HTMLArea._removeClass(el, "buttonDisabled");
						el.disabled = false;
					} else {
						HTMLArea._addClass(el, "buttonDisabled");
						el.disabled = true;
					}
					break;
				    case "active":
					if (newval) {
						HTMLArea._addClass(el, "buttonPressed");
					} else {
						HTMLArea._removeClass(el, "buttonPressed");
					}
					break;
				}
				this[id] = newval;
			}
		};
		// this function will handle creation of combo boxes
		function createSelect() {
			var options = null;
			var el = null;
			var cmd = null;
			switch (txt) {
			    case "fontsize":
			    case "fontname":
			    case "formatblock":
				case "babstyle":
				options = editor.config[txt];
				cmd = txt;
				break;
			}
			if (options) {
				el = document.createElement("select");
				var obj = {
					name: txt,     // field name
					element: el,   // the UI element (SELECT)
					enabled: true, // is it enabled?
					text: false,   // enabled in text mode?
					cmd: cmd,      // command ID
					state: setButtonStatus // for changing state
				};
				tb_objects[txt] = obj;
				for (var i in options) {
					var op = document.createElement("option");
					op.appendChild(document.createTextNode(i));
					op.value = options[i];
					el.appendChild(op);
				}
				HTMLArea._addEvent(el, "change", function () {
					editor._comboSelected(el, txt);
				});
			}
			return el;
		};
		// the element that will be created
		var el = null;
		var btn = null;
		switch (txt) {
		    case "separator":
			el = document.createElement("div");
			el.className = "separator";
			break;
		    case "space":
			el = document.createElement("div");
			el.className = "space";
			break;
		    case "linebreak":
			newLine();
			return false;
		    case "textindicator":
			el = document.createElement("div");
			el.appendChild(document.createTextNode("A"));
			el.className = "indicator";
			el.title = HTMLArea.I18N.tooltips.textindicator;
			var obj = {
				name: txt,     // the button name (i.e. 'bold')
				element: el,   // the UI element (DIV)
				enabled: true, // is it enabled?
				active: false, // is it pressed?
				text: false,   // enabled in text mode?
				cmd: "textindicator", // the command ID
				state: setButtonStatus // for changing state
			};
			tb_objects[txt] = obj;
			break;
		    default:
			btn = editor.config.btnList[txt];
			break;
		}
		if (!el && btn) {
			el = document.createElement("div");
			el.title = btn[1];
			el.className = "button";
			// let's just pretend we have a button object, and
			// assign all the needed information to it.
			var obj = {
				name: txt,     // the button name (i.e. 'bold')
				element: el,   // the UI element (DIV)
				enabled: true, // is it enabled?
				active: false, // is it pressed?
				text: btn[3],  // enabled in text mode?
				cmd: btn[0],   // the command ID
				state: setButtonStatus // for changing state
			};
			tb_objects[txt] = obj;
			// handlers to emulate nice flat toolbar buttons
			HTMLArea._addEvent(el, "mouseover", function () {
				if (obj.enabled) {
					HTMLArea._addClass(el, "buttonHover");
				}
			});
			HTMLArea._addEvent(el, "mouseout", function () {
				if (obj.enabled) with (HTMLArea) {
					_removeClass(el, "buttonHover");
					_removeClass(el, "buttonActive");
					(obj.active) && _addClass(el, "buttonPressed");
				}
			});
			HTMLArea._addEvent(el, "mousedown", function (ev) {
				if (obj.enabled) with (HTMLArea) {
					_addClass(el, "buttonActive");
					_removeClass(el, "buttonPressed");
					_stopEvent(is_ie ? window.event : ev);
				}
			});
			// when clicked, do the following:
			HTMLArea._addEvent(el, "click", function (ev) {
				if (obj.enabled) with (HTMLArea) {
					_removeClass(el, "buttonActive");
					_removeClass(el, "buttonHover");
					editor._buttonClicked(txt);
					_stopEvent(is_ie ? window.event : ev);
				}
			});
			var img = document.createElement("img");
			img.src = editor.imgURL(btn[2]);
			el.appendChild(img);
		} else if (!el) {
			el = createSelect();
		}
		if (el) {
			var tb_cell = document.createElement("td");
			tb_row.appendChild(tb_cell);
			tb_cell.appendChild(el);
		} else {
			alert("FIXME: Unknown toolbar item: " + txt);
		}
		return el;
	};

	for (var i in this.config.toolbar) {
		var group = this.config.toolbar[i];
		for (var j in group) {
			if (document.all || (group[j] != "paste" && group[j] != "copy" && group[j] != "cut" && group[j] != "cleanhtml"))
			{
			createButton(group[j]);
			}
			
		}
	}

HTMLArea.enabled = this.enabled;

if (this.enabled == true)
	{
	this._htmlArea.appendChild(toolbar);
	}
else
	{
	function setButtonStatus(id, newval) {
			var oldval = this[id];
			var el = this.element;
			if (oldval != newval) {
				switch (id) {
				    case "enabled":
					if (newval) {
						HTMLArea._removeClass(el, "buttonDisabled");
						el.disabled = false;
					} else {
						HTMLArea._addClass(el, "buttonDisabled");
						el.disabled = true;
					}
					break;
				    case "active":
					if (newval) {
						HTMLArea._addClass(el, "buttonPressed");
					} else {
						HTMLArea._removeClass(el, "buttonPressed");
					}
					break;
				}
				this[id] = newval;
			}
		};

	var lnk = document.createElement("div");
	lnk.title = "popupeditor";
	lnk.className = "button";
	
	var obj = {
				name: "popupeditor",     // the button name (i.e. 'bold')
				element: lnk,   // the UI element (DIV)
				enabled: true, // is it enabled?
				active: false, // is it pressed?
				text: false,   // enabled in text mode?
				cmd: "popupeditor", // the command ID
				state: setButtonStatus // for changing state
			};

	HTMLArea._addEvent(lnk, "mouseover", function () {
			if (obj.enabled) {
				HTMLArea._addClass(lnk, "buttonHover");
			}
		});
		HTMLArea._addEvent(lnk, "mouseout", function () {
			
				HTMLArea._removeClass(lnk, "buttonHover");
				HTMLArea._removeClass(lnk, "buttonActive");
		});
		HTMLArea._addEvent(lnk, "mousedown", function (ev) {
				HTMLArea._addClass(lnk, "buttonActive");
				HTMLArea._stopEvent(HTMLArea.is_ie ? window.event : ev);

		});
		// when clicked, do the following:
		HTMLArea._addEvent(lnk, "click", function (ev) {
				HTMLArea._removeClass(lnk, "buttonActive");
				HTMLArea._removeClass(lnk, "buttonHover");
				editor._buttonClicked("popupeditor");
				HTMLArea._stopEvent(HTMLArea.is_ie ? window.event : ev);
		});

	
		var img = document.createElement("img");
		img.src = editor.imgURL("ed_bab_clean.gif");
		lnk.appendChild(img);
		
		var txt = document.createElement("span");
		HTMLArea._addClass(txt, "BabBodyTitleBackground");
		txt.innerHTML='<a href="#">Editeur</a>';
		lnk.appendChild(txt);

		
	
	this._htmlArea.appendChild(lnk);

	}


	
};

// Creates the HTMLArea object and replaces the textarea with it.
HTMLArea.prototype.generate = function () {
	var editor = this;	// we'll need "this" in some nested functions
	// get the textarea
	var textarea = this._textArea;
	if (typeof textarea == "string") {
		// it's not element but ID
		this._textArea = textarea = document.getElementById(textarea);
	}
	this._ta_size = {
		w: textarea.offsetWidth,
		h: textarea.offsetHeight
	};
	// hide the textarea
	textarea.style.display = "none";

	// create the editor framework
	var htmlarea = document.createElement("div");
	htmlarea.className = "htmlarea";
	this._htmlArea = htmlarea;

	// insert the editor before the textarea.
	textarea.parentNode.insertBefore(htmlarea, textarea);

	// retrieve the HTML on submit
	HTMLArea._addEvent(textarea.form, "submit", function (event) {
		editor._formSubmit(HTMLArea.is_ie ? window.event : event);
	});

	// add a handler for the "back/forward" case -- on body.unload we save
	// the HTML content into the original textarea.
	window.onunload = function() {
		editor._textArea.value = editor.getHTML();
	};

	// creates & appends the toolbar
	this._createToolbar();

	// create the IFRAME
	var iframe = document.createElement("iframe");
	htmlarea.appendChild(iframe);
	this._iframe = iframe;

	// remove the default border as it keeps us from computing correctly
	// the sizes.  (somebody tell me why doesn't this work in IE)
	// iframe.style.border = "none";
	// iframe.frameborder = "0";

	// size the IFRAME according to user's prefs or initial textarea
	var height = (this.config.height == "auto" ? (this._ta_size.h + "px") : this.config.height);
	height = parseInt(height);
	var width = (this.config.width == "auto" ? (this._ta_size.w + "px") : this.config.width);
	width = parseInt(width);

	iframe.style.width = width + "px";
	if (this.config.sizeIncludesToolbar) {
		// substract toolbar height
		height -= this._toolbar.offsetHeight;
	}
	iframe.style.height = height + "px";

	// now create a secondary textarea so that we can switch between
	// WYSIWYG & text mode.
	textarea = document.createElement("textarea");

	// hidden by default
	textarea.style.display = "none";

	// make it the same size as the editor
	textarea.style.width = iframe.style.width;
	textarea.style.height = iframe.style.height;

	// insert it after the iframe
	htmlarea.appendChild(textarea);

	// remember it for later
	//this._textArea2 = textarea;

	// IMPORTANT: we have to allow Mozilla a short time to recognize the
	// new frame.  Otherwise we get a stupid exception.
	function initIframe() {
		var doc = editor._iframe.contentWindow.document;
		if (!doc) {
			if (HTMLArea.is_gecko) {
				setTimeout(function () { editor._initIframe(); }, 10);
				return false;
			} else {
				alert("ERROR: IFRAME can't be initialized.");
			}
		}
		if (HTMLArea.is_gecko) {
			// enable editable mode for Mozilla
			doc.designMode = "on";
		}
		editor._doc = doc;
		doc.open();
		var html = "<html>\n";
		html += "<head>\n";
		html += "<link rel=stylesheet href="+editor.baburl+editor.babPhpSelf+"?tg=htmlarea&idx=css type=text/css>\n";
		html += "<style>body,tbody { " + editor.config.bodyStyle + " }</style>\n";
		html += "</head>\n";
		html += "<body>\n";
		html += editor._textArea.value;
		html += "</body>\n";
		html += "</html>";
		doc.write(html);
		doc.close();

		if (HTMLArea.is_ie) {
			// enable editable mode for IE.  For some reason this
			// doesn't work if done in the same place as for Gecko
			// (above).
			doc.body.contentEditable = true;	
		}

		editor.focusEditor();
		// intercept some events; for updating the toolbar & keyboard handlers
		HTMLArea._addEvents
			(doc, ["keydown", "keypress", "mousedown", "mouseup", "drag"],
			 function (event) {
				 return editor._editorEvent(HTMLArea.is_ie ? editor._iframe.contentWindow.event : event);
			 });
		editor.updateToolbar();
		editor.focusEditor();
	};
	setTimeout(initIframe, HTMLArea.is_gecko ? 10 : 0);

};

// Switches editor mode; parameter can be "textmode" or "wysiwyg".  If no
// parameter was passed this function toggles between modes.
HTMLArea.prototype.setMode = function(mode) {
	if (typeof mode == "undefined") {
		mode = ((this._editMode == "textmode") ? "wysiwyg" : "textmode");
	}
	switch (mode) {
	    case "textmode":
		this._textArea.value = this.getHTML();
		this._iframe.style.display = "none";
		this._textArea.style.display = "block";
		break;
	    case "wysiwyg":
		if (HTMLArea.is_gecko) {
			// disable design mode before changing innerHTML
			this._doc.designMode = "off";
		}
		this._doc.body.innerHTML = this.getHTML();
		this._iframe.style.display = "block";
		this._textArea.style.display = "none";
		if (HTMLArea.is_gecko) {
			// we need to refresh that info for Moz-1.3a
			this._doc.designMode = "on";
		}
		break;
	    default:
		alert("Mode <" + mode + "> not defined!");
		return false;
	}
	this._editMode = mode;
	this.focusEditor();
};

/***************************************************
 *  Category: EDITOR UTILITIES
 ***************************************************/

// focuses the iframe window.  returns a reference to the editor document.
HTMLArea.prototype.focusEditor = function() {
	switch (this._editMode) {
	    case "wysiwyg" : this._iframe.contentWindow.focus(); break;
	    case "textmode": this._textArea.focus(); break;
	    default	   : alert("ERROR: mode " + this._editMode + " is not defined");
	}
	return this._doc;
};

// updates enabled/disable/active state of the toolbar elements
HTMLArea.prototype.updateToolbar = function() {
	var doc = this._doc;
	var text = (this._editMode == "textmode");
	for (var i in this._toolbarObjects) {
		var btn = this._toolbarObjects[i];
		var cmd = btn.cmd;
		if (typeof cmd == "function") {
			continue;
		}
		cmd = cmd.toLowerCase();
		btn.state("enabled", !text || btn.text);
		switch (cmd) {
		    case "fontname":
		    case "fontsize":
		    case "formatblock":
			
			if (!text) {
				var value = ("" + doc.queryCommandValue(cmd)).toLowerCase();
				if (!value) {
					// FIXME: what do we do here?
					break;
				}
				var options = this.config[i]; // HACK!!
				var k = 0;
				// btn.element.selectedIndex = 0;
				for (var j in options) {
					// FIXME: the following line is scary.
					if ((j.toLowerCase() == value) ||
					    (options[j].substr(0, value.length).toLowerCase() == value)) {
						btn.element.selectedIndex = k;
						break;
					}
					++k;
				}
			}
			break;
			
		    case "textindicator":
			if (!text) {
				try {with (btn.element.style) {
					backgroundColor = HTMLArea._makeColor(doc.queryCommandValue("backcolor"));
					color = HTMLArea._makeColor(doc.queryCommandValue("forecolor"));
					fontFamily = doc.queryCommandValue("fontname");
					fontWeight = doc.queryCommandState("bold") ? "bold" : "normal";
					fontStyle = doc.queryCommandState("italic") ? "italic" : "normal";
				}} catch (e) {
					//alert(e + "\n\n" + cmd + "\n\n" + btn.element.style );
				}
			}
			break;


			// afficher le style courrant dans le selecteur
			case "babstyle":
			
			var sel = this._getSelection();
			var range = this._createRange(sel);
			if (!text) {
				var span=null;
				if (HTMLArea.is_ie) {
				
				try {with (range) {
					span = range.parentElement();
				}}
				catch (e) {
					btn.element.selectedIndex = 0;
					break;
				}
				
				if (span.tagName.toLowerCase() != "span") {
					span = span.previousSibling;
					}
				} else {
					span = range.startContainer.previousSibling;
				}
				try {with (span.attributes) {
					var attrib = span.attributes.getNamedItem('class');
					var value = attrib.value.toLowerCase();
				}}
				catch (e) {
					btn.element.selectedIndex = 0;
					break;
				}
				btn.element.selectedIndex = 0;

				// reprise du code des select
				if (!value) {
					break;
				}

				var options = this.config[i];
				var k = 0;
				for (var j in options) {
					if ((j.toLowerCase() == value) || (options[j].substr(0, value.length).toLowerCase() == value)) {
						btn.element.selectedIndex = k;
						break;
					}
					++k;
				}
			}
			
			break;
			

		    case "htmlmode":
			btn.state("active", text);
			break;
		    default:
			try {
				btn.state("active", (!text && doc.queryCommandState(cmd)));
			} catch (e) {}
			break;
		}
	}
};

/** Returns a node after which we can insert other nodes, in the current
 * selection.  The selection is removed.  It splits a text node, if needed.
 */
HTMLArea.prototype.insertNodeAtSelection = function(toBeInserted) {
	if (!HTMLArea.is_ie) {
		var sel = this._getSelection();
		var range = this._createRange(sel);
		// remove the current selection
		sel.removeAllRanges();
		range.deleteContents();
		var node = range.startContainer;
		var pos = range.startOffset;
		range = this._createRange();
		switch (node.nodeType) {
		    case 3: // Node.TEXT_NODE
			// we have to split it at the caret position.
			if (toBeInserted.nodeType == 3) {
				// do optimized insertion
				node.insertData(pos, toBeInserted.data);
				range.setEnd(node, pos + toBeInserted.length);
				range.setStart(node, pos + toBeInserted.length);
			} else {
				node = node.splitText(pos);
				node.parentNode.insertBefore(toBeInserted, node);
				range.setStart(node, 0);
				range.setEnd(node, 0);
			}
			break;
		    case 1: // Node.ELEMENT_NODE
			node = node.childNodes[pos];
			node.parentNode.insertBefore(toBeInserted, node);
			range.setStart(node, 0);
			range.setEnd(node, 0);
			break;
		}
		sel.addRange(range);
	} else {
		return null;	// this function not yet used for IE <FIXME>
	}
};

/** Call this function to insert HTML code at the current position.  It deletes
 * the selection, if any.
 */
HTMLArea.prototype.insertHTML = function(html) {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	if (HTMLArea.is_ie) {
		range.pasteHTML(html);
	} else {
		// construct a new document fragment with the given HTML
		var fragment = this._doc.createDocumentFragment();
		var div = this._doc.createElement("div");
		div.innerHTML = html;
		while (div.firstChild) {
			// the following call also removes the node from div
			fragment.appendChild(div.firstChild);
		}
		// this also removes the selection
		var node = this.insertNodeAtSelection(fragment);
	}
};


/**
 *  Call this function to surround the existing HTML code in the selection with
 *  your tags.
 */
HTMLArea.prototype.surroundHTML = function(startTag, endTag) {
	var html = this.getSelectedHTML();
	// the following also deletes the selection
	this.insertHTML(startTag + html + endTag);
};

/// Retrieve the selected block
HTMLArea.prototype.getSelectedHTML = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var existing = null;
	if (HTMLArea.is_ie) {
		existing = range.htmlText;
	} else {
		existing = HTMLArea.getHTML(range.cloneContents(), false);
	}
	return existing;
};


// Called when the user clicks on "InsertImage" button
/*
HTMLArea.prototype._insertImage = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var editor = this;	// for nested functions
	this._popupDialog("insert_image.html", function(param) {
		if (!param) {	// user must have pressed Cancel
			return false;
		}
		editor._execCommand("insertimage", false, param["f_url"]);
		var img = null;
		if (HTMLArea.is_ie) {
			img = range.parentElement();
			// wonder if this works...
			if (img.tagName.toLowerCase() != "img") {
				img = img.previousSibling;
			}
		} else {
			img = range.startContainer.previousSibling;
		}
		for (field in param) {
			var value = param[field];
			if (!value) {
				continue;
			}
			switch (field) {
			    case "f_alt":
				img.alt = value;
				break;
			    case "f_border":
				img.border = parseInt(value);
				break;
			    case "f_align":
				img.align = value;
				break;
			    case "f_vert":
				img.vspace = parseInt(value);
				break;
			    case "f_horiz":
				img.hspace = parseInt(value);
				break;
			}
		}
	}, null);
};

*/

// Called when the user clicks on "BabInsertImage" button
HTMLArea.prototype._insertBabImage = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var editor = this;	// for nested functions
	this._babDialog(this.baburl+this.babPhpSelf+"?tg=images&editor=0&callback=EditorOnCreateImage", function(param) {
		if (!param) {	// user must have pressed Cancel
			return false;
		}
		editor._execCommand("insertimage", false, param["f_url"]);
		var img = null;
		if (HTMLArea.is_ie) {
			img = range.parentElement();
			// wonder if this works...
			if (img.tagName.toLowerCase() != "img") {
				img = img.previousSibling;
			}
		} else {
			img = range.startContainer.previousSibling;
		}
		for (field in param) {
			var value = param[field];
			if (!value) {
				continue;
			}
			switch (field) {
			    case "f_alt":
				img.alt = value;
				break;
			    case "f_border":
				img.border = parseInt(value);
				break;
			    case "f_align":
				img.align = value;
				break;
			    case "f_vert":
				img.vspace = parseInt(value);
				break;
			    case "f_horiz":
				img.hspace = parseInt(value);
				break;
			}
		}
	}, null,'toolbar=no,menubar=no,personalbar=no,width=400,height=470,scrollbars=yes,resizable=yes');
};



HTMLArea.prototype._babcleanhtml = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var editor = this;
	var msg = "Warrning : cleaning HTML can remove page-setting of the document";

	if (typeof HTMLArea.messages.cleanHTML != "undefined") {
		msg = HTMLArea.messages.cleanHTML;
	}


 if (confirm(msg))
 {
	if (HTMLArea.is_ie)
	{

	var selected=null;
	if (HTMLArea.is_ie) {
	selected = range.parentElement();
	}
	else
		{selected = range.startContainer.previousSibling;} // cette ligne n'est pas encore utilis�e

	var found = true;
      while (found)
      {
        found = false;

		if (sel.type.toLowerCase() =='text')
			{
			var els = selected.all;
			}
		else
			{
			var els = editor._iframe.contentWindow.document.body.all;
			}
		
        for (i=0; i<els.length; i++)
        {
          // remove tags with urns set
          if (els[i].tagUrn != null && (typeof(els[i].tagName) != "undefined") && els[i].tagUrn != '')
          {
            els[i].removeNode(false);
            found = true;
          } 
          // remove font and span tags
          if (els[i].tagName != null && (typeof(els[i].tagName) != "undefined") &&(els[i].tagName == "FONT" || els[i].tagName == "SPAN" || els[i].tagName == "DIV" || els[i].tagName == "IMG" || els[i].tagName == "F" || els[i].tagName == "IMAGEDATA"  || els[i].tagName == "FORMULAS" || els[i].tagName == "ACRONYM"))
          {
            els[i].removeNode(false);
            found = true;
          }
        }      
      }
      
      // remove styles
      var els = selected.all;
      for (i=0; i<els.length; i++)
      {
        // remove style and class attributes from all tags
        els[i].removeAttribute("className",0);
        els[i].removeAttribute("style",0);
        
      }
	}
	 else
		{
		alert('FIXME : il manque la fonction removeNode dans mozilla!');
		}
 }
};



// Called when the user clicks on "BabInsertLink" button
HTMLArea.prototype._insertbablink = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var editor = this;	// for nested functions

	this._popupDialog("insert_bablink.html?lang="+this.babLanguage.substr(0, (this.babLanguage.length-1)), function(param) {
	if (!param) {	// user must have pressed Cancel
			return false;
		}
	
	var seltxt = ''+sel;
	//alert(typeof seltxt+' '+seltxt.length+' seltxt='+seltxt+' sel='+sel);

	if (seltxt.length>0)
		{
		editor.surroundHTML('<a target="'+param['target']+'" href="'+param['url']+'">','</a>');
		}
	else
		{
		editor.insertHTML('<a target="'+param['target']+'" href="'+param['url']+'">'+param['url']+'</a>');
		}
	
	/*
	editor._execCommand("CreateLink", false, param['url']);

	var a = null;
		if (HTMLArea.is_ie) {
			a = range.parentElement();
			if (a.tagName.toLowerCase() != "a") {
				a = a.previousSibling;
			}
		} else {
			a = range.startContainer.previousSibling;
		}
		for (field in param) {
			var value = param[field];
			if (!value) {
				continue;
			}
			switch (field) {
			    case "target":
				a.target = value;
				break;
			}
		}
	*/
	}, range);
};



// Called when the user clicks the Insert Table button
HTMLArea.prototype._insertTable = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var editor = this;	// for nested functions
	this._popupDialog("insert_table.html?lang="+this.babLanguage.substr(0, (this.babLanguage.length-1)), function(param) {
		if (!param) {	// user must have pressed Cancel
			return false;
		}
		var doc = editor._doc;
		// create the table element
		var table = doc.createElement("table");
		// assign the given arguments
		for (var field in param) {
			var value = param[field];
			if (!value) {
				continue;
			}
			switch (field) {
			    case "f_width":
				table.style.width = value + param["f_unit"];
				break;
			    case "f_align":
				table.align = value;
				break;
			    case "f_border":
				table.border = parseInt(value);
				break;
			    case "f_spacing":
				table.cellspacing = parseInt(value);
				break;
			    case "f_padding":
				table.cellpadding = parseInt(value);
				break;
			}
		}
		var tbody = doc.createElement("tbody");
		table.appendChild(tbody);
		for (var i = 0; i < param["f_rows"]; ++i) {
			var tr = doc.createElement("tr");
			tbody.appendChild(tr);
			for (var j = 0; j < param["f_cols"]; ++j) {
				var td = doc.createElement("td");
				tr.appendChild(td);
				if (HTMLArea.is_gecko) {
					// Mozilla likes to see something
					// inside the cell.
					td.appendChild(doc.createElement("br"));
				}
			}
		}
		if (HTMLArea.is_ie) { 
			range.pasteHTML(table.outerHTML);
		} else {
			editor.insertNodeAtSelection(table);
		}
		return true;
	}, null);
};

/***************************************************
 *  Category: EVENT HANDLERS
 ***************************************************/
var fullview;
// txt is the name of the button, as in config.toolbar
HTMLArea.prototype._buttonClicked = function(txt) {
	var editor = this;	// needed in nested functions
	this.focusEditor();
	var btn = this.config.btnList[txt];
	if (!btn) {
		alert("FIXME: Unconfigured button!");
		return false;
	}
	var cmd = btn[0];
	if (typeof cmd == "function") {
		return cmd(this, txt);
	}
	switch (cmd.toLowerCase()) {
	    case "htmlmode":
		this.setMode(); break;
		break;
	    case "forecolor":
	    case "backcolor":
		this._popupDialog("select_color.html", function(color) {
			editor._execCommand(cmd, false, "#" + color);
		}, HTMLArea._colorToRgb(this._doc.queryCommandValue(btn[0])));
		break;
	    case "createlink":
		this._execCommand(cmd, true);
		break;
	    case "insertimage":
		this._insertImage();
		break;
	    case "inserttable":
		this._insertTable();
		break;
	    case "popupeditor":
		if (HTMLArea.is_ie) {
			fullview = window.open(this.popupURL("fullscreen.html?baburl="+escape(this.baburl)+"&babInstallPath="+escape(this.babInstallPath)+"&babPhpSelf="+escape(this.babPhpSelf)+"&lang="+this.babLanguage.substr(0, (this.babLanguage.length-1))), "ha_fullscreen",
				    "toolbar=no,location=no,directories=no,status=yes,menubar=no," +
				    "scrollbars=no,resizable=yes,width=640,height=480");
		} else {
			window.open(this.popupURL("fullscreen.html?baburl="+escape(this.baburl)+"&babInstallPath="+escape(this.babInstallPath)+"&babPhpSelf"+escape(this.babPhpSelf)+"&lang="+this.babLanguage.substr(0, (this.babLanguage.length-1))), "ha_fullscreen",
				    "toolbar=no,menubar=no,personalbar=no,width=640,height=480," +
				    "scrollbars=no,resizable=yes");
		}
		// pass this object to the newly opened window
		HTMLArea._object = this;
		break;
	    case "about":
		this._popupDialog("about.html", null, null);
		break;
	    case "help":
		alert("Help not implemented");
		break;
		case "babimage":
		this._insertBabImage();
		break;
		case "bablink":
		this._insertbablink();
		break;
		case "babfile":
		this._babDialog(this.baburl+this.babPhpSelf+"?tg=fileman&idx=brow&callback=EditorOnCreateFile&editor=1", null, null,'toolbar=no,menubar=no,personalbar=no,width=400,height=470,scrollbars=yes,resizable=yes');
		break;
		case "babarticle":
		this._babDialog(this.baburl+this.babPhpSelf+"?tg=editorarticle&idx=brow&cb=EditorOnInsertArticle", null, null,'toolbar=no,menubar=no,personalbar=no,width=350,height=470,scrollbars=yes,resizable=yes');
		break;
		case "babfaq":
		this._babDialog(this.baburl+this.babPhpSelf+"?tg=editorfaq", null, null,'toolbar=no,menubar=no,personalbar=no,width=350,height=470,scrollbars=yes,resizable=yes');
		break;
		case "babovml":
		this._babDialog(this.baburl+this.babPhpSelf+"?tg=editorovml", null, null,'toolbar=no,menubar=no,personalbar=no,width=350,height=470,scrollbars=yes,resizable=yes');
		break;
		case "cleanhtml":
		this._babcleanhtml();
		break;
		case "paste":
		this._execCommand('paste', false,null);
		break;
	    default:
		this._execCommand(btn[0], false, "");
		break;
	}
	this.updateToolbar();
	return false;
};

// el is reference to the SELECT object
// txt is the name of the select field, as in config.toolbar
HTMLArea.prototype._comboSelected = function(el, txt) {
	this.focusEditor();
	var value = el.options[el.selectedIndex].value;
	switch (txt) {
	    case "fontname":
	    case "fontsize":
		this._execCommand(txt, false, value);
		break;
	    case "formatblock":
		if (HTMLArea.is_ie) { // sad but true
			value = "<" + value + ">";
		}
		this._execCommand(txt, false, value);
		break;
		case "babstyle":
		this.babstyle(value);
		break;
	    default:
		alert("FIXME: combo box " + txt + " not implemented");
		break;
	}
};

HTMLArea.prototype.babstyle = function(value) 
{
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var editor = this;	// for nested functions

	if (HTMLArea.is_ie) 
	{
		var span=null;
		try {with (range) {
			span = range.parentElement();
		}}
		catch (e) {
			//alert(e);
		}
		
		if (span.tagName.toLowerCase() != "span") {
			span = span.previousSibling;
			}
		try {with (span.attributes) {
			var attrib = span.attributes.getNamedItem('class');
			var currentvalue = attrib.value.toLowerCase();
		}}
		catch (e) {
			//alert(e);
		}
		if( currentvalue )
			{
			if (value == 'Normal')
				{
				span.removeNode(false);
				}
			else
				span.className = value;
			}
		else
			this.surroundHTML('<span class="'+value+'">','</span>');
	}
	else
		{
		range.execCommand('RemoveFormat'); 
		this.surroundHTML('<span class="'+value+'">','</span>');
		}


};

// the execCommand function (intercepts some commands and replaces them with
// our own implementation)
HTMLArea.prototype._execCommand = function(cmdID, UI, param) {
	switch (cmdID.toLowerCase()) {
	    case "createlink":
		if (HTMLArea.is_ie || !UI) {
			this._doc.execCommand(cmdID, UI, param);
		} else {
			// browser is Mozilla & wants UI
			if ((param = prompt("Enter URL"))) {
				this._doc.execCommand(cmdID, false, param);
			}
		}
		break;
	    case "backcolor":
		if (HTMLArea.is_ie) {
			this._doc.execCommand(cmdID, UI, param);
		} else {
			this._doc.execCommand("hilitecolor", UI, param);
		}
		break;

	    default:
		this._doc.execCommand(cmdID, UI, param);
		break;
	}
	this.focusEditor();
};

/** A generic event handler for things that happen in the IFRAME's document.
 * This function also handles key bindings. */
HTMLArea.prototype._editorEvent = function(ev) {
	var editor = this;
	var keyEvent = (HTMLArea.is_ie && ev.type == "keydown") || (ev.type == "keypress");
	if (keyEvent && ev.ctrlKey) {
		var sel = null;
		var range = null;
		var key = String.fromCharCode(HTMLArea.is_ie ? ev.keyCode : ev.charCode).toLowerCase();
		var cmd = null;
		var value = null;
		switch (key) {
		    case 'a':
			if (!HTMLArea.is_ie) {
				// KEY select all
				sel = this._getSelection();
				sel.removeAllRanges();
				range = this._createRange();
				range.selectNodeContents(this._doc.body);
				sel.addRange(range);
				HTMLArea._stopEvent(ev);
			}
			break;

			// simple key commands follow

		    case 'b': cmd = "bold"; break;
		    case 'i': cmd = "italic"; break;
		    case 'u': cmd = "underline"; break;
		    case 's': cmd = "strikethrough"; break;
		    case 'l': cmd = "justifyleft"; break;
		    case 'e': cmd = "justifycenter"; break;
		    case 'r': cmd = "justifyright"; break;
		    case 'j': cmd = "justifyfull"; break;

			// headings
		    case '1':
		    case '2':
		    case '3':
		    case '4':
		    case '5':
		    case '6':
			cmd = "formatblock";
			value = "h" + key;
			if (HTMLArea.is_ie) {
				value = "<" + value + ">";
			}
			break;
		}
		if (cmd) {
			// execute simple command
			this._execCommand(cmd, false, value);
			HTMLArea._stopEvent(ev);
		}
	}
	
	else if (keyEvent) {
	try {
		if ( (typeof fullview == 'object') && (fullview.focus()) )
			{
			fullview.focus();
			}
		} catch (e) {
			//alert(e);
		}
	
	}


	// update the toolbar state after some time
	setTimeout(function() {
		editor.updateToolbar();
	}, 50);
};

// gets called before the form is submitted
HTMLArea.prototype._formSubmit = function(ev) {
	// retrieve the HTML
	this._textArea.value = this.getHTML();
};

// retrieve the HTML
HTMLArea.prototype.getHTML = function() {
	switch (this._editMode) {
	    case "wysiwyg"  : return HTMLArea.getHTML(this._doc.body, false);
	    case "textmode" : return this._textArea.value;
	    default	    : alert("Mode <" + mode + "> not defined!");
	}
	return false;
};


// retrieve the HTML (fastest version, but uses innerHTML)
HTMLArea.prototype.getInnerHTML = function() {
	switch (this._editMode) {
	    case "wysiwyg"  : return this._doc.body.innerHTML;
	    case "textmode" : return this._textArea.value;
	    default	    : alert("Mode <" + mode + "> not defined!");
	}
	return false;
};

// completely change the HTML inside
HTMLArea.prototype.setHTML = function(html) {
	switch (this._editMode) {
	    case "wysiwyg"  : this._doc.body.innerHTML = html; break;
	    case "textmode" : this._textArea.value = html; break;
	    default	    : alert("Mode <" + mode + "> not defined!");
	}
	return false;
};

/***************************************************
 *  Category: UTILITY FUNCTIONS
 ***************************************************/

// browser identification

HTMLArea.agt = navigator.userAgent.toLowerCase();
HTMLArea.is_ie     = ((HTMLArea.agt.indexOf("msie") != -1) && (HTMLArea.agt.indexOf("opera") == -1));
HTMLArea.is_opera  = (HTMLArea.agt.indexOf("opera") != -1);
HTMLArea.is_mac    = (HTMLArea.agt.indexOf("mac") != -1);
HTMLArea.is_mac_ie = (HTMLArea.is_ie && HTMLArea.is_mac);
HTMLArea.is_win_ie = (HTMLArea.is_ie && !HTMLArea.is_mac);
HTMLArea.is_gecko  = (navigator.product == "Gecko");

// variable used to pass the object to the popup editor window.
HTMLArea._object = null;

HTMLArea.checkSupportedBrowser = function() {

	if (HTMLArea.is_ie) {
		var temp = HTMLArea.agt.split("msie");
		var version = parseFloat(temp[1]);
		if (version<5.5)
			{
			return false;
			}
		}
	if (HTMLArea.is_gecko) {
		return false;
		if (navigator.productSub < 20021201) {
			//alert("You need at least Mozilla-1.3 Alpha.\n" +
			 //     "Sorry, your Gecko is not supported.");
			return false;
		}
		if (navigator.productSub < 20030210) {
			//alert("Mozilla < 1.3 Beta is not supported!\n" +
			//      "I'll try, though, but it might not work.");

			// d�sactiv� a cause de netscape
			return false;
		}
	}
	return HTMLArea.is_gecko || HTMLArea.is_ie;
};

// selection & ranges

// returns the current selection object
HTMLArea.prototype._getSelection = function() {
	if (HTMLArea.is_ie) {
		return this._doc.selection;
	} else {
		return this._iframe.contentWindow.getSelection();
	}
};

// returns a range for the current selection
HTMLArea.prototype._createRange = function(sel) {
	if (HTMLArea.is_ie) {
		return sel.createRange();
	} else {
		this.focusEditor();
		if (typeof sel != "undefined") {
			return sel.getRangeAt(0);
		} else {
			return this._doc.createRange();
		}
	}
};

// event handling

HTMLArea._addEvent = function(el, evname, func) {
	if (HTMLArea.is_ie) {
		el.attachEvent("on" + evname, func);
	} else {
		el.addEventListener(evname, func, true);
	}
};

HTMLArea._addEvents = function(el, evs, func) {
	for (var i in evs) {
		HTMLArea._addEvent(el, evs[i], func);
	}
};

HTMLArea._removeEvent = function(el, evname, func) {
	if (HTMLArea.is_ie) {
		el.detachEvent("on" + evname, func);
	} else {
		el.removeEventListener(evname, func, true);
	}
};

HTMLArea._removeEvents = function(el, evs, func) {
	for (var i in evs) {
		HTMLArea._removeEvent(el, evs[i], func);
	}
};

HTMLArea._stopEvent = function(ev) {
	if (HTMLArea.is_ie) {
		ev.cancelBubble = true;
		ev.returnValue = false;
	} else {
		ev.preventDefault();
		ev.stopPropagation();
	}
};

HTMLArea._removeClass = function(el, className) {
	if (!(el && el.className)) {
		return;
	}
	var cls = el.className.split(" ");
	var ar = new Array();
	for (var i = cls.length; i > 0;) {
		if (cls[--i] != className) {
			ar[ar.length] = cls[i];
		}
	}
	el.className = ar.join(" ");
};

HTMLArea._addClass = function(el, className) {
	// remove the class first, if already there
	HTMLArea._removeClass(el, className);
	el.className += " " + className;
};

HTMLArea._hasClass = function(el, className) {
	if (!(el && el.className)) {
		return false;
	}
	var cls = el.className.split(" ");
	for (var i = cls.length; i > 0;) {
		if (cls[--i] == className) {
			return true;
		}
	}
	return false;
};

HTMLArea.isBlockElement = function(el) {
	var blockTags = " body form textarea fieldset ul ol dl li div " +
		"p h1 h2 h3 h4 h5 h6 quote pre table thead " +
		"tbody tfoot tr td iframe address ";
	return (blockTags.indexOf(" " + el.tagName.toLowerCase() + " ") != -1);
};

HTMLArea.needsClosingTag = function(el) {
	var closingTags = " script style div span ";
	return (closingTags.indexOf(" " + el.tagName.toLowerCase() + " ") != -1);
};

// performs HTML encoding of some given string
HTMLArea.htmlEncode = function(str) {
	// we don't need regexp for that, but.. so be it for now.
	str = str.replace(/&/ig, "&amp;");
	str = str.replace(/</ig, "&lt;");
	str = str.replace(/>/ig, "&gt;");
	str = str.replace(/\x22/ig, "&quot;");
	// \x22 means '"' -- we use hex reprezentation so that we don't disturb
	// JS compressors (well, at least mine fails.. ;)
	return str;
};

// Retrieves the HTML code from the given node.  This is a replacement for
// getting innerHTML, using standard DOM calls.
HTMLArea.getHTML = function(root, outputRoot) {
	var html = "";
	switch (root.nodeType) {
	    case 1: // Node.ELEMENT_NODE
	    case 11: // Node.DOCUMENT_FRAGMENT_NODE
		var closed;
		var i;
		if (outputRoot) {
			closed = (!(root.hasChildNodes() || HTMLArea.needsClosingTag(root)));
			html = "<" + root.tagName.toLowerCase();
			var attrs = root.attributes;
			for (i = 0; i < attrs.length; ++i) {
				var a = attrs.item(i);
				if (!a.specified) {
					continue;
				}
				var name = a.nodeName.toLowerCase();
				if (/_moz/.test(name)) {
					// Mozilla reports some special tags
					// here; we don't need them.
					continue;
				}
				var value;
				if (name != "style") {
					// IE5.5 reports 25 when cellSpacing is
					// 1; other values might be doomed too.
					// For this reason we extract the
					// values directly from the root node.
					if (typeof root[a.nodeName] != "undefined") {
						value = root[a.nodeName];
					} else {
						value = a.nodeValue;
					}
				} else { // IE fails to put style in attributes list
					// FIXME: cssText reported by IE is UPPERCASE
					value = root.style.cssText;
				}
				if (/_moz/.test(value)) {
					// Mozilla reports some special tags
					// here; we don't need them.
					continue;
				}
				html += " " + name + '="' + value + '"';
			}
			html += closed ? " />" : ">";
		}
		for (i = root.firstChild; i; i = i.nextSibling) {
			html += HTMLArea.getHTML(i, true);
		}
		if (outputRoot && !closed) {
			html += "</" + root.tagName.toLowerCase() + ">";
		}
		break;
	    case 3: // Node.TEXT_NODE
		html = HTMLArea.htmlEncode(root.data);
		break;
	    case 8: // Node.COMMENT_NODE
		html = "<!--" + root.data + "-->";
		break;		// skip comments, for now.
	}
	return html;
};


// creates a rgb-style color from a number
HTMLArea._makeColor = function(v) {
	if (typeof v != "number") {
		// already in rgb (hopefully); IE doesn't get here.
		return v;
	}
	// IE sends number; convert to rgb.
	var r = v & 0xFF;
	var g = (v >> 8) & 0xFF;
	var b = (v >> 16) & 0xFF;
	return "rgb(" + r + "," + g + "," + b + ")";
};

// returns hexadecimal color representation from a number or a rgb-style color.
HTMLArea._colorToRgb = function(v) {
	// returns the hex representation of one byte (2 digits)
	function hex(d) {
		return (d < 16) ? ("0" + d.toString(16)) : d.toString(16);
	};
	
	// exeption : plusieurs coul par selection
	if (v==null)
	{
		return null;
	}

	if (typeof v == "number") {
		// we're talking to IE here
		var r = v & 0xFF;
		var g = (v >> 8) & 0xFF;
		var b = (v >> 16) & 0xFF;
		return "#" + hex(r) + hex(g) + hex(b);
	}
	
	if (v.substr(0, 3) == "rgb") {
		// in rgb(...) form -- Mozilla
		var re = /rgb\s*\(\s*([0-9]+)\s*,\s*([0-9]+)\s*,\s*([0-9]+)\s*\)/;
		if (v.match(re)) {
			var r = parseInt(RegExp.$1);
			var g = parseInt(RegExp.$2);
			var b = parseInt(RegExp.$3);
			return "#" + hex(r) + hex(g) + hex(b);
		}
		// doesn't match RE?!  maybe uses percentages or float numbers
		// -- FIXME: not yet implemented.
		return null;
	}

	if (v[0] == "#") {
		// already hex rgb (hopefully :D )
		return v;
	}

	// if everything else fails ;)
	return null;
};

// modal dialogs for Mozilla (for IE we're using the showModalDialog() call).

// receives an URL to the popup dialog and a function that receives one value;
// this function will get called after the dialog is closed, with the return
// value of the dialog.
HTMLArea.prototype._popupDialog = function(url, action, init) {
	Dialog(this.popupURL(url), action, init);
};

HTMLArea.prototype._babDialog = function(url, action, init,param) {
	if (document.all)
	{
		window.open(url, "imageloader",param);
	}
	else
	{
		Dialog(url, action, init,param);}
};

// paths

HTMLArea.prototype.imgURL = function(file) {
	return this.config.editorURL + this.config.imgURL + file;
};

HTMLArea.prototype.popupURL = function(file) {
	return this.config.editorURL + this.config.popupURL + file;
};


// fonctions dupliqu�es sur fullscreen.html


function EditorOnCreateImage(param)
	{
	var sel = editor._getSelection();
	var range = editor._createRange(sel);

		if (!param) {	// user must have pressed Cancel
			return false;
		}
		editor._execCommand("insertimage", false, param["f_url"]);
		var img = null;
		if (HTMLArea.is_ie) {
			img = range.parentElement();
			if (img.tagName.toLowerCase() != "img") {
				img = img.previousSibling;
			}
		} else {
			img = range.startContainer.previousSibling;
		}
		for (field in param) {
			var value = param[field];
			if (!value) {
				continue;
			}
			switch (field) {
			    case "f_alt":
				img.alt = value;
				break;
			    case "f_border":
				img.border = parseInt(value);
				break;
			    case "f_align":
				img.align = value;
				break;
			    case "f_vert":
				img.vspace = parseInt(value);
				break;
			    case "f_horiz":
				img.hspace = parseInt(value);
				break;
			}
		}
	}



function EditorOnInsertFile(id, idf, txt)
{
/*var html = editor.getSelectedHTML();
if (html)
	{txt = html;
	}*/
editor.insertHTML('$FILE('+idf+','+txt+')');
}

function EditorOnInsertArticle(id, txt, target)
{
editor.insertHTML('$ARTICLEID('+id+','+txt+','+target+')');
}

function EditorOnInsertFaq(id, txt, target)
{
editor.insertHTML('$FAQID('+id+','+txt+','+target+')');
}

function EditorOnInsertOvml(txt)
{
editor.insertHTML('$OVML('+txt+')');
}

// bug ie 5.0
function savef(obj) {}

// EOF
// Local variables: //
// c-basic-offset:8 //
// indent-tabs-mode:t //
// End: //

var editor = null;

var babUrl = null;
var babInstallPath = null;
var babLanguage = null;
var babPhpSelf = null;

function initEditorVars(var1, var2, var3, var4)
	{
	babUrl = var1;
	babInstallPath = var2;
	babLanguage = var3;
	if (var4.substr(0, 1) == '/')
		{
		babPhpSelf = var4.substr(1, var4.length);
		}
	else
		{
		babPhpSelf = var4;
		}
	}

function initEditor2(what,ta)
	{
	
	if (typeof babPhpSelf  == "undefined")
	{
		babPhpSelf = 'index.php';
	}


	editor = new HTMLArea(ta,babUrl,babInstallPath,babLanguage,babPhpSelf);
	if( what == 1 )
		{
	  	editor.config.toolbar = [ [ "fontname", "space" ],
			 [ "fontsize", "space" ],
			 [ "formatblock", "space"],
			 [ "babstyle", "space", "textindicator","linebreak" ],
			 [ "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator" ],
			 [ "orderedlist", "unorderedlist", "outdent", "indent", "separator" ],
			 [ "forecolor", "backcolor", "separator" ],
			 [ "horizontalrule", "inserttable", "htmlmode", "separator" ],
			 [ "popupeditor","bablink", "linebreak" ],
			 [ "copy", "cut", "paste","undo","redo", "separator" ],
			 [ "bold", "italic", "underline", "separator","strikethrough", "subscript", "superscript", "separator" ],
			 ["cleanhtml","babimage","babfile","babarticle","babfaq","babovml"]
		];
		}

	if (typeof HTMLArea.babstyle.Normal != "string")
		{
		editor.config.toolbar[3] = [ "space", "textindicator","linebreak" ];
		}

	}

function initEditor(what,ta)
	{
	if (document.readyState != 'complete')
		setTimeout(function() { initEditor(what,ta) }, 25);
	else
		{
		initEditor2(what,ta);
		editor.generate();
		}
	}