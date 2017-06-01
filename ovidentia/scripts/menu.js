jQuery(document).ready(function() {
	jQuery("#bab-menu-page ul li ul li ul").hide();
	
	jQuery("#bab-menu-page ul li ul li>div").each(function(){
		if (jQuery(this).find('li').get(0)) {
			jQuery(this).children('.bab-menu-item').before('<span style="cursor:pointer;" class="bab-toggle-open">&nbsp;&#9655;&nbsp;</span>');
		} else {
			jQuery(this).children('.bab-menu-item').before('<span style="visibility:hidden;" class="bab-disabled-toggler">&nbsp;&#9655;&nbsp;</span>');
		}
	});
	
	jQuery('.bab-toggle-open').click( function() {
		jQuery(this).parent().children('ul').toggle();
		if(jQuery(this).html() == '&nbsp;'+String.fromCharCode(9655)+'&nbsp;') {
			jQuery(this).html('&nbsp;&#9698;&nbsp;');
		} else {
			jQuery(this).html('&nbsp;&#9655;&nbsp;');
		}
		return false;
	});
	
	/*jQuery('#bab-menu-search').keyup(function() {
		findInPage(jQuery(this).val());
	});*/
	
});	

//RECHERCHE
/*var n = 0;
function findInPage(str) {
    var txt, i, found;
    if (str == "") {
        return false; 
    }
    // Find next occurance of the given string on the page, wrap around to the
    // start of the page if necessary.
    if (window.find) {
        // Look for match starting at the current point. If not found, rewind
        // back to the first match.
        if (!window.find(str)) {
            while (window.find(str, false, true)) {
                n++;
            }
        } else {
            n++;
        }
        // If not found in either direction, give message.
        if (n == 0) {
            //alert("Not found.");
        }
    } else if (window.document.body.createTextRange) {
        txt = window.document.body.createTextRange();
        // Find the nth match from the top of the page.
        found = true;
        i = 0;
        while (found === true && i <= n) {
            found = txt.findText(str);
            if (found) {
                txt.moveStart("character", 1);
                txt.moveEnd("textedit");
            }
            i += 1;
        }
        // If found, mark it and scroll it into view.
        if (found) {
            txt.moveStart("character", -1);
            txt.findText(str);
            txt.select();
            txt.scrollIntoView();
            n++;
        } else {
            // Otherwise, start over at the top of the page and find first match.
            if (n > 0) {
                n = 0;
                findInPage(str);
            }
            // Not found anywhere, give message. else
            //alert("Not found.");
        }
    }
    return false;
}*/