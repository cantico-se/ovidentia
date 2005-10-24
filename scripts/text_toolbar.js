var global_textarea_id = null;
if (typeof global_editor == 'undefined')
	{
		global_editor = null;
	}

function getSelection()
{
if (global_editor != null)
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
	}
else
	{
	var html = '';
	}


return html;
}



function bab_tt_popup(textarea_id, url)
{
	global_textarea_id = textarea_id;
	window.open(url,'text_toolbar','toolbar=no,menubar=no,personalbar=no,width=500,height=480,scrollbars=yes,resizable=yes');
}


function editorInsertText(text)
{
	if (global_editor != null)
	{
		global_editor.insertHTML(text);
	}
	else
	{
		var ta = document.getElementById(global_textarea_id);

		text = ' ' + text + ' ';
		if (ta.caretPos) {
			ta.caretPos.text += text;
			} 
		else {
			ta.value  += text;
			}

	}
}


function EditorOnCreateImage(param)
{
	if (global_editor != null)
	{
		EditorOnCreateImage_WYSIWYG(param)
	}
	else
	{
	editorInsertText('<img src="'+param['f_url']+'" alt="'+param['f_alt']+'" border="'+param['f_border']+'" align="'+param['f_align']+'" />');
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

editorInsertText('$FILE('+idf+','+txt+')');
}


function EditorOnInsertArticle(id, txt, target)
{
var html = getSelection();
if (html != '')
	{
	txt = html;
	}

editorInsertText('$ARTICLEID('+id+','+txt+','+target+')');
}


function EditorOnInsertFaq(id, txt, target)
{
var html = getSelection();
if (html != '')
	{
	txt = html;
	}

editorInsertText('$FAQID('+id+','+txt+','+target+')');
}

function EditorOnInsertOvml(txt)
{
editorInsertText('$OVML('+txt+')');
}

function EditorOnInsertCont(id,txt)
{
editorInsertText('$CONTACTID('+id+','+txt+')');
}

function EditorOnInsertDir(id,txt,iddir)
{
editorInsertText('$DIRECTORYID('+id+','+txt+','+iddir+')');
}

function EditorOnInsertFolder(id,path,txt)
{
editorInsertText('$FOLDER('+id+','+path+','+txt+')');
}