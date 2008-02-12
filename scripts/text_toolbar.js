var global_uid = null;
var global_editor = null;


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


/**
 * Launch ovidentia functionalities list
 * each function can return datas by the bab_dialog interface
 * the return format for the bab_dialog parameter is an object with this format
 * 	'callback' => function_name (ex : 'editorInsertText')
 *  'param'	=> callback_parameter (mixed)
 *
 * 
 */
function insert_ovidentia() {

	var path = document.location.href.split('?')[0];
	
	var useparam = {
		'width'		: 700,
		'height'	: 500
	};

	bab_dialog(path+'?tg=editorfunctions&uid='+global_uid, useparam, function(arr) {
		eval(arr['callback']+'(arr[\'param\']);');
	});
}


/**
 * Callback for external function
 * Html insertion
 * @param	string 	text
 */
function editorInsertText(text) {
	
	if (global_editor != null)
	{
		global_editor.insertHTML(text);
	}
	else if (global_uid != null)
	{
		var ta = document.getElementById(global_uid);

		text = ' ' + text + ' ';
		if (ta.caretPos) {
			ta.caretPos.text += text;
			} 
		else {
			ta.value += text;
		}
	} else {
		alert('global_editor or global_uid must be set before editorInsertText(text)');
	}
}




/**
 * Encode string for macro parameter
 * like $XXX(a,b,c)
 *
 * @param	string	text
 * @return string
 */
function bab_macroEncodeParam(text) {
	return text.replace(/(\(|\))/g, '-');
}




/**
 * Callback for external function
 * Image insertion
 * @param	array 	param
 */
function EditorOnCreateImage(param)
{
	if (global_editor != null && typeof EditorOnCreateImage_WYSIWYG == 'function')
	{
		EditorOnCreateImage_WYSIWYG(param)
	}
	else
	{
		editorInsertText('<img src="'+param['f_url']+'" alt="'+param['f_alt']+'" border="'+param['f_border']+'" align="'+param['f_align']+'" />');
	}
}



function EditorOnInsertFiles(files)
{
	var html = getSelection();
	if (html != '') {
		txt = html;
	}
	var insertedItems = new Array();
	for (var i = 0; i < files.length; i++) {
		var file = files[i];
		if (file.type != 'folder') {
			insertedItems.push('$FILE(' + file.id + ',' + bab_macroEncodeParam(file.content) + ')');
		} else {
			var path = file.id.split(':');
			var id = path[0];
			insertedItems.push('$FOLDER(' + id + ',' + path.slice(1).join('/') + ',' + bab_macroEncodeParam(file.content) + ')');
		}
	}
	if (insertedItems.length > 0) {
		editorInsertText(insertedItems.join(','));
	}
}