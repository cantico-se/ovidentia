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

function Start(page, title, param)
{
var r;
r = this.open(page, title, param);
}

function bab_selectFirstInputField(filterclass)
	{
	if (!document.getElementsByTagName)
		{
		return;
		}
	function in_array(val,arr)
		{
		for (var i in arr )
			{
			if ( arr[i] == val)
				return true;
			}
		return false;
		}

	if (typeof filterclass != 'undefined')
		var filter = filterclass.split(',');
	else
		var filter = false;

	var el = document.getElementsByTagName('input');
	for (var i =0 ; i < el.length ; i++)
		{
		
		if (((el[i].type == 'text' || el[i].type == 'password') && el[i].value == '') || el[i].type == 'submit' )
			{
			if (filter && typeof el[i].className != 'undefined' && el[i].className != '')
				{
				if (!in_array(el[i].className,filter))
					{
					el[i].focus();
					el[i].select();
					return;
					}
				}
			else
				{
				el[i].focus();
				el[i].select();
				return;
				}
			}
		}
	}