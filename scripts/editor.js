function initEditor(id,config) {
  editor = new HTMLArea(id,config);
  editor.registerPlugin(ovtable);
  editor.generate();
  return false;
}


var config = new HTMLArea.Config();

config.toolbar = [
[ "formatblock", "space", "cssstyles" ,"space",
  "bold", "italic", "underline", "separator",
  "copy", "cut", "paste", "space", "undo", "redo" ],
		
[ "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator",
  "insertorderedlist", "insertunorderedlist", "outdent", "indent", "separator",
  "createlink", "inserttable", "htmlmode", "killword"]
];




function print_r(obj)
{
	var str = '';
for (var i in obj )
	{
	str += i+' '+obj[i]+'\t';
	}
alert(obj+' '+str);
}


var global_editor = '';

config.registerButton({
  id        : "ref-fman",
  tooltip   : "{ t_file_manager }",
  image     : "{ babInstallPath }skins/ovidentia/images/addons/referentiel/editor/ed_folder.gif",
  textMode  : false,
  action    : function(editor, id) {
				//}
				global_editor = editor;
                window.open('{ babAddonUrl }explorer&ida={ filespaces }&callback=fss_callback','explorer','status=yes,menubar=no,personalbar=no,width=600,height=400,top=50,left=50,scrollbars=yes,resizable=yes');
              }
});

config.registerButton({
  id        : "ref-link",
  tooltip   : "{ t_link }",
  image     : "{ babInstallPath }skins/ovidentia/images/addons/referentiel/editor/ed_link.gif",
  textMode  : false,
  action    : function(editor, id) {
				//}
				global_editor = editor;
                window.open('{ babAddonUrl }referential_idx&idx=set_link&be_rn_id={ be_rn_id }&callback=link_callback','referential','status=yes,menubar=no,personalbar=no,width=600,height=400,top=50,left=50,scrollbars=yes,resizable=yes');
              }
});


