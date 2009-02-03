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
		
		if ((el[i].type == 'text' || el[i].type == 'password') && el[i].value == '' && !el[i].disabled )
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
	if (!menubar) {
		menubar = 'no';
	} else {
		menubar = 'yes';
	}
	if (bab_popup_obj == null || bab_popup_obj.closed || bab_popup_obj == 0) {
		if (typeof divisor == 'undefined') {
				divisor = 3;
		}
		var wd = Math.round(screen.width/divisor);
		var hd = Math.round(screen.height/divisor);
		var w = screen.width-wd;
		var h = menubar == 'yes' ? Math.round((screen.height - hd) / 1.5) : screen.height-hd;
		var l = Math.round(wd/2);
		var t = Math.round(hd/2);
		var name = 'bab_popup'+Math.floor(Math.random() * 99999999);
		
		if (bab_popup_obj == 0) {
			window.open(url,name,'status=yes,menubar='+menubar+',personalbar=no,width='+w+',height='+h+',top='+t+',left='+l+',scrollbars=yes,resizable=yes');
			bab_popup_obj = null;
		} else {
			bab_popup_obj = window.open(url,name,'status=yes,menubar='+menubar+',personalbar=no,width='+w+',height='+h+',top='+t+',left='+l+',scrollbars=yes,resizable=yes');
		}
	} else {
		bab_popup_obj.focus();
		bab_popup_obj.location.href = url;
	}
}


/**
 * @var Array	An array of functions that will be called when the page is loaded.
 *
 * Skins should not use the onload property of the body tag, but instead use this script
 * somewhere after including ovidentia.js :
 * bab_initFunctions.push(function() { ... });
 */
var bab_initFunctions = new Array();


window.onload = function() {
	window.isLoaded = true;
	for (var i = 0; i < bab_initFunctions.length; i++) {
		var initFunction = bab_initFunctions[i];
		if (typeof(initFunction) == 'function') {
			initFunction();
		}
	}
};


var bab_currentTooltip = null;


function bab_showOnMouse(tooltipId, on)
{
	var tooltip = document.getElementById(tooltipId);
	if (!tooltip) {
		return;
	}

	if (window.isLoaded && !tooltip.bab_initialized) {
		// Here we move the tooltip element at the end of the DOM tree to
		// avoid an IE6 bug messing-up the z-index for positioned elements
		// This way we are sure that the tooltip will appear above all other elements.
		// On IE (again!) we must wait for the page to be completely loaded before we
		// can touch the DOM.
		document.getElementsByTagName('BODY')[0].appendChild(tooltip);
		tooltip.bab_initialized = true;
	}

	if (on) {
		window.bab_currentTooltip = tooltip;
		document.onmousemove = bab_tooltipPosition;
		tooltip.style.zIndex = 127;
	} else {
		document.onmousemove = null;
		tooltip.style.visibility = 'hidden';
		window.bab_currentTooltip = null;
	}
}


function bab_tooltipPosition(e)
{
	e = e || window.event;

	var tooltip = window.bab_currentTooltip;
	
	if (!tooltip) {
		return true;
	}

	var offsetX = -tooltip.offsetWidth / 2, offsetY = 20;
	for (var offsetParent = tooltip.offsetParent; offsetParent; offsetParent = offsetParent.offsetParent) {
		offsetX -= offsetParent.offsetLeft || 0;
		offsetY -= offsetParent.offsetTop || 0;
	}

	var ie = document.all;
	var ns6 = document.getElementById && !document.all;

	var ieBody = (document.compatMode && document.compatMode != 'BackCompat') ? document.documentElement : document.body;
	var cursorX = ns6 ? e.pageX : e.clientX + ieBody.scrollLeft;
	var cursorY = ns6 ? e.pageY : e.clientY + ieBody.scrollTop;
	var rightedge = ie && !window.opera ? ieBody.clientWidth - e.clientX - offsetX : window.innerWidth - e.clientX - offsetX - 20;
	var bottomedge = ie && !window.opera ? ieBody.clientHeight - e.clientY - offsetY : window.innerHeight - e.clientY - offsetY - 20;
	var leftedge = (offsetX < 0) ? offsetX * (-1) : -1000;

	if (rightedge < tooltip.offsetWidth) {
		tooltip.style.left = ie ? ieBody.scrollLeft + e.clientX - tooltip.offsetWidth + 'px' : window.pageXOffset + e.clientX - tooltip.offsetWidth + 'px';
	} else if (cursorX < leftedge) {
		tooltip.style.left = '5px';
	} else {
		tooltip.style.left = cursorX + offsetX + 'px';
	}

	if (bottomedge < tooltip.offsetHeight) {
		tooltip.style.top = ie ? ieBody.scrollTop + e.clientY - tooltip.offsetHeight - offsetY + 'px' : window.pageYOffset + e.clientY - tooltip.offsetHeight - offsetY + 'px';
	} else {
		tooltip.style.top = cursorY + offsetY + 'px';
	}
	tooltip.style.visibility = 'visible';
	return true;
}



function bab_getInstallPath()
{
	var scripts = document.getElementsByTagName('script');
	for (var i = 0; i < scripts.length; i++) {
		if (-1 != scripts[i].src.indexOf('ovidentia.js')) {
			arr = scripts[i].src.split('/');
			for (var j in arr) {
				if ('scripts' == arr[j]) {
					return arr[j-1] + '/';
				}
			}
		}
	}
	return '';
}
