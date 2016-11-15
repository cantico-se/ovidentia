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

function bab_popup(url,divisor,menubar, toolbar, personalbar)
{
    if (!menubar) {
        menubar = 'no';
    } else {
        menubar = 'yes';
    }

    if (!toolbar) {
        toolbar = 'no';
    } else {
        toolbar = 'yes';
    }

    if (!personalbar) {
        personalbar = 'no';
    } else {
        personalbar = 'yes';
    }

    if (bab_popup_obj == null || bab_popup_obj.closed || (typeof(bab_popup_obj) != 'object' && bab_popup_obj == 0)) {
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

        if (typeof(bab_popup_obj) != 'object' && bab_popup_obj == 0) {
            window.open(url,name,'status=yes,menubar='+menubar+',toolbar='+toolbar+',personalbar='+personalbar+',width='+w+',height='+h+',top='+t+',left='+l+',scrollbars=yes,resizable=yes');
            bab_popup_obj = null;
        } else {
            bab_popup_obj = window.open(url,name,'status=yes,menubar='+menubar+',toolbar='+toolbar+',personalbar='+personalbar+',width='+w+',height='+h+',top='+t+',left='+l+',scrollbars=yes,resizable=yes');
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

/**
 * Show or Hide an html element (with bab_tooltipPosition(), the html element will be displayed in a tooltip : it follows the mouse).
 * This function can be called in this situation :
 * 									<div id="myTooltip" class="allTooltip">Text displayed in tooltip</div>
 * 									<div id="myElement" onmouseover="bab_showOnMouse("myTooltip", true);" onmouseout="bab_showOnMouse("myTooltip", false);">My datas</div>
 *
 * CSS styles for tooltip :
 * 		.allTooltip {
 * 			position: absolute;
 * 			border: 1px solid black;
 * 			padding: .2em .5em;
 * 			background-color: white;
 * 			visibility: hidden;
 * 			z-index: 100;
 * 			text-align:left;
 * 			font-size:11px;
 * 			font-family:Verdana;
 * 			color:#000;
 * 			white-space:nowrap;
 * 			top:-1000px;
 * 			left:-1000px;
 * 		}
 *
 * Don't forget to call bab_tooltipPosition() before the call of bab_showOnMouse() :
 * 				document.onmousemove = bab_tooltipPosition;
 *
 * Don't forget to import the javascript file :
 * 				$GLOBALS['babBody']->addJavascriptFile($GLOBALS['babInstallPath'].'scripts/bab_dialog.js');
 *
 * Example with jQuery framework :
 * 		jQuery(document).ready(function() {
 * 			jQuery(document).mousemove(function(e){
 * 		    	bab_tooltipPosition(e);
 * 		    });
 *
 * 		    jQuery("#myElement").hover(function(e){
 * 		    	bab_showOnMouse("myTooltip", true);
 * 		    });
 *
 * 		    jQuery("#myElement").mouseout(function(e){
 * 		    	bab_showOnMouse("myTooltip", false);
 * 		    });
 * 		});
 *
 * @param string tooltipId Identifiant of the html element (Example : myId <div id="myId"></div>)
 * @param boolean on If true the html element will be displayed
 */
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

/**
 * Set the position of the mouse cursor (with bab_showOnMouse(), an html element will be displayed in a tooltip : it follows the mouse).
 * This function can be called before the call of bab_showOnMouse() :
 * 								document.onmousemove = bab_tooltipPosition;
 *
 * Example with jQuery framework :
 * 		jQuery(document).ready(function() {
 * 			jQuery(document).mousemove(function(e){
 * 		    	bab_tooltipPosition(e);
 * 		    });
 * 		});
 *
 * Don't forget to import the javascript file :
 * 				$GLOBALS['babBody']->addJavascriptFile($GLOBALS['babInstallPath'].'scripts/bab_dialog.js');
 *
 * See bab_showOnMouse() for more information
 *
 * @param string tooltipId Identifiant of the html element (Example : myId <div id="myId"></div>)
 * @param boolean on If true the html element will be displayed
 */
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



/**
 * send the link variables with a POST form
 * if there is a title, use it as confirm
 * This function must be used with a return on a onclick attribute of a <a href=""> tag
 * @param {DOMNode} a
 * @return {boolean}
 */
function bab_postLinkConfirm(a)
{
    var title = a.getAttribute('title');
    if (title && !confirm(title)) {
        return false;
    }

    if (window.XMLHttpRequest) { // Mozilla, Safari, ...
        httpRequest = new XMLHttpRequest();
    }
    else if (window.ActiveXObject) { // IE
        httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
    }

    function createHiddenField(name, value) {
        var input=document.createElement('input');
        input.type='hidden';
        input.name=decodeURIComponent(name);
        value = value.replace(/\+/g, '%20');
        try {
            decodeURIComponent(value);
        } catch (e) {
            value = unescape(value);
        }
        input.value=decodeURIComponent(value);
        return input;
    }


    httpRequest = new XMLHttpRequest();

    httpRequest.onreadystatechange = function() {

        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                var pathItems = a.pathname.split('/');
                var f = document.createElement('form');
                f.action=pathItems[pathItems.length-1];
                f.method='POST';

                f.appendChild(createHiddenField('babCsrfProtect', httpRequest.responseText));

                var vars = a.search.substr(1).split('&');
                for (var i = 0; i < vars.length; i++) {
                    var pair = vars[i].split('=');
                    f.appendChild(createHiddenField(pair[0], pair[1]));
                }

                document.body.appendChild(f);
                f.submit();

            } else {
                alert('Failed to retreive the CSRF token');
            }
        }



    };

    httpRequest.open('GET', '?tg=csrfprotect', true);
    httpRequest.send();

    return false;
}