HTMLArea.init();

var global_editor = null;

  HTMLArea.onload = function() {
	
	config = new HTMLArea.Config();

	config.statusBar = false;
	config.sizeIncludesToolbar = false;

	config.debug = false;

	config.toolbar = [
	[ "fontname", "space", "fontsize", "space", "formatblock", "space" <!--#if arr_classname -->,"cssstyles" <!--#endif arr_classname -->],

	[ "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator",
	  "orderedlist", "unorderedlist", "outdent", "indent", "separator",
	  "forecolor", "hilitecolor", "separator",
	  "createlink", "bab_unlink", "inserttable", "removeformat", "killword", "htmlmode", "popupeditor" ],
	  
	 ["copy", "cut", "paste", "space", "undo", "redo", "separator",
	  "bold", "italic", "underline", "separator",
	  "strikethrough", "subscript", "superscript", "separator", "bab_functions"
		]
	];



	config.btnList.removeformat[0] = '{ t_removeformat }';
	config.btnList.killword[0] = '{ t_killword }';
	<!--#if babLanguage "!= en" -->
	config.btnList.bold[1] = _editor_url + 'images/{ babLanguage }/ed_format_bold.gif';
	config.btnList.underline[1] = _editor_url + 'images/{ babLanguage }/ed_format_underline.gif';
	<!--#endif babLanguage -->

	config.fontname = { //}
		"&mdash; { t_font } &mdash;":         '',
		"Arial":	   'arial,helvetica,sans-serif',
		"Courier New":	   'courier new,courier,monospace',
		"Georgia":	   'georgia,times new roman,times,serif',
		"Tahoma":	   'tahoma,arial,helvetica,sans-serif',
		"Times New Roman": 'times new roman,times,serif',
		"Verdana":	   'verdana,arial,helvetica,sans-serif',
		"impact":	   'impact',
		"WingDings":	   'wingdings'
	};

	config.fontsize = {//}
		"&mdash; { t_size } &mdash;"  : "",
		"1 (8 pt)" : "1",
		"2 (10 pt)": "2",
		"3 (12 pt)": "3",
		"4 (14 pt)": "4",
		"5 (18 pt)": "5",
		"6 (24 pt)": "6",
		"7 (36 pt)": "7"
	};


	config.formatblock = {//}
		"&mdash; { t_format } &mdash;"  : "",
		"{ t_heading } 1": "h1",
		"{ t_heading } 2": "h2",
		"{ t_heading } 3": "h3",
		"{ t_heading } 4": "h4",
		"{ t_heading } 5": "h5",
		"{ t_heading } 6": "h6",
		"{ t_paragraph }": "p",
		"{ t_address }"  : "address",
		"{ t_formated }": "pre"
	};

	//alert(typeof config.fontsize["&mdash; size &mdash;"]);
	

	config.pageStyle = '{ css_styles }';

	config.registerButton({
	  id        : "bab_functions",
	  tooltip   : "{ t_bab_functions }",
	  image     : _editor_url + 'images/ovidentia.gif',
	  textMode  : false,
	  action    : function(editor, id) {
					//}
					global_editor = editor;
					global_uid = '{ uid }';
					insert_ovidentia();
				}
	});

	config.registerButton({
	  id        : "bab_unlink",
	  tooltip   : "{ t_bab_unlink }",
	  image     : _editor_url + 'images/ed_unlink.gif',
	  textMode  : false,
	  action    : function(editor, id) {
					editor.execCommand("unlink");
				  }
	});


	config.registerDropdown({
	  id        : "cssstyles",
	  options	: {
					//}
					"&mdash; { t_css } &mdash;":"normal",
					<!--#in getnextbodyclass -->
					"{ text }":"{ classname }",{ linebreak } 
					<!--#endin getnextbodyclass -->
					"Normal":"normal"
				  },
	  context	: "",
	  textMode  : false,
	  refresh   : function(editor) {
					var sel = editor._getSelection();
					var range = editor._createRange(sel);
					var btn = editor._toolbarObjects["cssstyles"];

					
					var span = editor.getParentElement();
					
					while (span && !span.className) { span = span.parentElement; }
					var currentvalue = span ? span.className.toLowerCase() : "";

					if( !currentvalue )
						{
						currentvalue = 'normal';
						}
					var options = editor.config.customSelects["cssstyles"].options;
					var k = 0;

					for (var j in options) {
						if ((j.toLowerCase() == currentvalue) || (options[j].substr(0, currentvalue.length).toLowerCase() == currentvalue)) 
							{
							btn.element.selectedIndex = k;
							break;
							}
						++k;
					}


				  },

	  action    : function(editor) {
					var sel = editor._getSelection();
					var range = editor._createRange(sel);
					var btn = editor.config.customSelects["cssstyles"];
					var options = editor.config.customSelects["cssstyles"].options;

					var k = 0;
					for (var j in options) {
						if (k == editor._toolbarObjects["cssstyles"].element.selectedIndex) 
							{
							var value = editor.config.customSelects["cssstyles"].options[j];
							break;
							}
						++k;
						}
					var span = editor.getParentElement();

					while (span && !span.className) { span = span.parentElement; }
					var currentvalue = span ? span.className.toLowerCase() : "";

					if( currentvalue )
						{
						
						if (HTMLArea.is_ie && value == 'normal' )
							{
							range.execCommand('RemoveFormat');
							span.removeNode(false);
							}
						
						else if (HTMLArea.is_gecko && value == 'normal' )
							{
							editor.execCommand("RemoveFormat");
							}
						else
							span.className = value;
						
						}
					else if (value != 'removeformat')
					{
						editor.surroundHTML('<span class="'+value+'">','</span>');
					}
						
				
				editor.updateToolbar();
			  }
	});

	setTimeout ( function() { 
		for(var i in textarea_id)
		  {
			var editor = new HTMLArea(textarea_id[i], config);

			if (editor.config)
				{
				document.getElementById(textarea_id[i]+'_text_toolbar').style.display = 'none';
				document.getElementById(textarea_id[i]+'_textmode').value = '';
				
				editor.generate(); 
				editor._toolbar.style.height = '67px'; 
				
				}

		  }
		}, 10 );

	return false;
};

	
	
function EditorOnCreateImage_WYSIWYG(param)
{
	var editor = global_editor;
	editor.focusEditor();

	var sel = editor._getSelection();
	var range = editor._createRange(sel);

	if (!param) {
		return false;
	}
	editor._doc.execCommand("insertimage", false, param["f_url"]);
	var img = null;
	if (HTMLArea.is_ie) {
		img = range.parentElement();
		if (img.tagName.toLowerCase() != "img") {
			img = img.previousSibling;
		}
	} else {
		img = range.startContainer.previousSibling;
		if (!img) {
			img = range.startContainer.nextSibling;
		}
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
	



