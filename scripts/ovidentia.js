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
		
		if ((el[i].type == 'text' || el[i].type == 'password') && el[i].value == '' )
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

var bab_popup_obj = null;
function bab_popup(url,divisor)
	{
	if (bab_popup_obj == null || bab_popup_obj.closed)
		{
		if (typeof divisor == 'undefined')
			{
				divisor = 3;
			}
		var wd = Math.round(screen.width/divisor);
		var hd = Math.round(screen.height/divisor);
		var w = screen.width-wd;
		var h = screen.height-hd;
		var l = Math.round(wd/2);
		var t = Math.round(hd/2);
		bab_popup_obj = window.open(url,'bab_popup','status=yes,menubar=no,personalbar=no,width='+w+',height='+h+',top='+t+',left='+l+',scrollbars=yes,resizable=yes');
		}
	else
		{
		bab_popup_obj.focus();
		bab_popup_obj.location.href = url;
		}
	}