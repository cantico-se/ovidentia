function onCheckAll(ctrlPrefix, state)
{
	var i;
	var objCtrl;

	for (i = 0; i < document.forms.listform.elements.length; i++)
		{
		objCtrl = document.forms.listform.elements[i];
		if (objCtrl.name.substring(0,ctrlPrefix.length) == ctrlPrefix)
			objCtrl.checked = state;
		}
}
function submitForm(cmd, fmname) 
{ 
if( typeof document.forms[fmname] != "object" ) return;
document.forms[fmname].idx.value = cmd;
document.forms[fmname].submit();
}
