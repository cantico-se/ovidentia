HTMLArea.init();

var global_editor = null;

  HTMLArea.onload = function() {
	
	config = new HTMLArea.Config();

	
	config.toolbar = [
	[ "fontname", "space", "fontsize", "space", "formatblock", "space" <!--#if arr_classname -->,"cssstyles" <!--#endif arr_classname -->],

	[ "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator",
	  "orderedlist", "unorderedlist", "outdent", "indent", "separator",
	  "forecolor", "hilitecolor", "separator",
	  "inserthorizontalrule", "createlink", "inserttable",  "killword", "htmlmode", "popupeditor" ],
	  
	 ["copy", "cut", "paste", "space", "undo", "redo", "separator",
	  "bold", "italic", "underline", "separator",
	  "strikethrough", "subscript", "superscript", "separator",
		 <!--#if mode "== 1" -->
	     "bab_image", "bab_file", "bab_article", "bab_faq", "bab_ovml", "bab_contdir"]
		 <!--#endif mode -->

		<!--#if mode "== 3" -->
	      "bab_file", "bab_article", "bab_faq", "bab_ovml", "bab_contdir"]
		 <!--#endif mode -->
	];

	 

	

	config.pageStyle = '{ css_styles }';

	config.registerButton({
	  id        : "bab_image",
	  tooltip   : "{ t_bab_image }",
	  image     : "{ babScriptPath }htmlarea/images/ed_bab_image.gif",
	  textMode  : false,
	  action    : function(editor, id) {
					//}
					global_editor = editor;
					window.open('{ babUrlScript }?tg=images&editor=0&callback=EditorOnCreateImage','bab_image','toolbar=no,menubar=no,personalbar=no,width=500,height=480,scrollbars=yes,resizable=yes');
				  }
	});


	config.registerButton({
	  id        : "bab_file",
	  tooltip   : "{ t_bab_file }",
	  image     : "{ babScriptPath }htmlarea/images/ed_bab_file.gif",
	  textMode  : false,
	  action    : function(editor, id) {
					//}
					global_editor = editor;
					window.open('{ babUrlScript }?tg=fileman&idx=brow&callback=EditorOnCreateFile&editor=1','bab_file','toolbar=no,menubar=no,personalbar=no,width=400,height=470,scrollbars=yes,resizable=yes');
				  }
	});


	config.registerButton({
	  id        : "bab_article",
	  tooltip   : "{ t_bab_article }",
	  image     : "{ babScriptPath }htmlarea/images/ed_bab_articleid.gif",
	  textMode  : false,
	  action    : function(editor, id) {
					//}
					global_editor = editor;
					window.open('{ babUrlScript }?tg=editorarticle&idx=brow&cb=EditorOnInsertArticle','bab_article','toolbar=no,menubar=no,personalbar=no,width=400,height=470,scrollbars=yes,resizable=yes');
				  }
	});


	config.registerButton({
	  id        : "bab_faq",
	  tooltip   : "{ t_bab_faq }",
	  image     : "{ babScriptPath }htmlarea/images/ed_bab_faqid.gif",
	  textMode  : false,
	  action    : function(editor, id) {
					//}
					global_editor = editor;
					window.open('{ babUrlScript }?tg=editorfaq','bab_faq','toolbar=no,menubar=no,personalbar=no,width=350,height=470,scrollbars=yes,resizable=yes');
				  }
	});


	config.registerButton({
	  id        : "bab_ovml",
	  tooltip   : "{ t_bab_ovml }",
	  image     : "{ babScriptPath }htmlarea/images/ed_bab_ovml.gif",
	  textMode  : false,
	  action    : function(editor, id) {
					//}
					global_editor = editor;
					window.open('{ babUrlScript }?tg=editorovml','bab_ovml','toolbar=no,menubar=no,personalbar=no,width=350,height=470,scrollbars=yes,resizable=yes');
				  }
	});

	config.registerButton({
	  id        : "bab_contdir",
	  tooltip   : "{ t_bab_contdir }",
	  image     : "{ babScriptPath }htmlarea/images/ed_bab_contdir.gif",
	  textMode  : false,
	  action    : function(editor, id) {
					//}
					global_editor = editor;
					window.open('{ babUrlScript }?tg=editorcontdir','bab_contdir','toolbar=no,menubar=no,personalbar=no,width=450,height=470,scrollbars=yes,resizable=yes');
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


	for(var i in textarea_id)
	  {
		var editor = new HTMLArea(textarea_id[i], config);
		editor.generate();
	  }
	return false;
};

	


function print_r(obj)
{
	var str = '';
	for (var i in obj )
		{
		str += i+' '+obj[i]+'\t';
		}
	alert(obj+' '+str);
}


function getSelection()
{
var html = global_editor.getSelectedHTML();
	html = html.replace(/ \w+=[^\s|>]*/gi,'');
	html = html.replace(/\&nbsp\;/, '');
	html = html.replace(/<\w+><\/\w+>/i, '');
	html = html.replace(/<\w+>\s+<\/\w+>/i, '');
	html = html.replace(/<\w+\s+\/>/i, '');
	html = html.replace(/<\w+\/>/i, '');
	html = html.replace(/^\s+/, '');
	html = html.replace(/\s+$/, '');

return html;
}


function EditorOnCreateImage(param)
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
var editor = global_editor;
var html = getSelection();
if (html != '')
	{
	txt = html;
	}

editor.insertHTML('$FILE('+idf+','+txt+')');
}


function EditorOnInsertArticle(id, txt, target)
{
var html = getSelection();
if (html != '')
	{
	txt = html;
	}

global_editor.insertHTML('$ARTICLEID('+id+','+txt+','+target+')');
}


function EditorOnInsertFaq(id, txt, target)
{
var html = getSelection();
if (html != '')
	{
	txt = html;
	}

global_editor.insertHTML('$FAQID('+id+','+txt+','+target+')');
}

function EditorOnInsertOvml(txt)
{
global_editor.insertHTML('$OVML('+txt+')');
}

function EditorOnInsertCont(id,txt)
{
global_editor.insertHTML('$CONTACTID('+id+','+txt+')');
}

function EditorOnInsertDir(id,txt)
{
global_editor.insertHTML('$DIRECTORYID('+id+','+txt+')');
}

function EditorOnInsertFolder(id,path,txt)
{
global_editor.insertHTML('$FOLDER('+id+','+path+','+txt+')');
}