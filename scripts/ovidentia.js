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

	var filter = false;
	if (typeof filterclass != 'undefined')
		filter = filterclass.split(',');
		

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
function bab_popup(url,divisor,menubar)
	{
	if (!menubar)
		{
		menubar = 'no';
		}
	else
		{
		menubar = 'yes';
		}
	if (bab_popup_obj == null || bab_popup_obj.closed)
		{
		if (typeof divisor == 'undefined')
			{
				divisor = 3;
			}
		var wd = Math.round(screen.width/divisor);
		var hd = Math.round(screen.height/divisor);
		var w = screen.width-wd;
		var h = menubar == 'yes' ? Math.round((screen.height-hd)/1.5) : screen.height-hd;
		var l = Math.round(wd/2);
		var t = Math.round(hd/2);
		var name = 'bab_popup'+Math.floor(Math.random() * 99999999);
		bab_popup_obj = window.open(url,name,'status=yes,menubar='+menubar+',personalbar=no,width='+w+',height='+h+',top='+t+',left='+l+',scrollbars=yes,resizable=yes');
		}
	else
		{
		bab_popup_obj.focus();
		bab_popup_obj.location.href = url;
		}
	}


var bab_tooltip_obj = false;

function bab_showOnMouse(id,on)
	{
	var ie=document.all;
	var ns6=document.getElementById && !document.all;
	if (ie||ns6)
		bab_tooltip_obj = document.all? document.all[id] : document.getElementById? document.getElementById(id) : "";
	else
		return false;

	if (!on)
		{
		bab_tooltip_obj.style.visibility = 'hidden';
		bab_tooltip_obj = false;
		return true;
		}

	}


function bab_tooltipPosition(e)
	{
	if (!bab_tooltip_obj)
		return false;
	var obj = bab_tooltip_obj;
	var offsetxpoint=-60;
	var offsetypoint=20;
	var ie=document.all;
	var ns6=document.getElementById && !document.all;

	var ietypebody = (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
	var curX=(ns6)? e.pageX : event.x + ietypebody.scrollLeft;
	var curY=(ns6)? e.pageY : event.y + ietypebody.scrollTop;
	var rightedge=ie&&!window.opera ? ietypebody.clientWidth-event.clientX-offsetxpoint : window.innerWidth-e.clientX-offsetxpoint-20
	var bottomedge=ie&&!window.opera ? ietypebody.clientHeight-event.clientY-offsetypoint : window.innerHeight-e.clientY-offsetypoint-20
	var leftedge=(offsetxpoint<0)? offsetxpoint*(-1) : -1000;

	if (rightedge<obj.offsetWidth)
		{
		obj.style.left=ie? ietypebody.scrollLeft + event.clientX-obj.offsetWidth+'px' : window.pageXOffset+e.clientX-obj.offsetWidth+'px';
		}
	else if (curX<leftedge)
		{
		obj.style.left='5px';
		}
	else
		{
		obj.style.left=curX+offsetxpoint+'px';
		}

	if (bottomedge < obj.offsetHeight)
		obj.style.top=ie? ietypebody.scrollTop+event.clientY-obj.offsetHeight-offsetypoint+"px" : window.pageYOffset+e.clientY-obj.offsetHeight-offsetypoint+"px";
	else
		{
		obj.style.top=curY+offsetypoint+"px"
		}
	obj.style.visibility="visible";
	return true;
	}
