/**
 * need ovidentia.js
 */



bab_initFunctions.push(function() {

	var bloc 		= document.getElementById('bab_search_secondary_bloc');
	var primary		= document.getElementById('bab_search_primary');
	var secondary 	= document.getElementById('bab_search_secondary');

	if (bloc && !secondary.value) {
		bloc.style.display = 'none';
	}

	img	= document.createElement('img');
	img.src = bab_getInstallPath()+'skins/ovidentia/images/Puces/add_emblem.png';
	img.style.cursor = 'pointer';

	primary.parentNode.appendChild(img);

	img.onclick = function() {
		if ('none' == bloc.style.display) {
			bloc.style.display = '';
		} else {
			bloc.style.display = 'none';
		}
	};
});




function bab_compareOptionText(a,b) {
	return a.text!=b.text ? a.text<b.text ? -1 : 1 : 0;
}



function bab_searchUpdateSelect(id, fields, full, currentselect, selectedvalue)
{
	var j = 0;
	
	for (j=currentselect.options.length-1; j>=0; j--) {
		currentselect.options[j]=null;
	}

	for (var k in fields[-1]) {
		currentselect.options[currentselect.options.length] = new Option( full[fields[-1][k]],fields[-1][k]);
	}

	if (id > 0) {
		for (var h in fields[id]) {
			currentselect.options[currentselect.options.length] = new Option( full[fields[id][h]],fields[id][h]);
		}
	}

	for (j=currentselect.options.length-1; j>=0; j--) {
		if (currentselect.options[j].value == selectedvalue) {
			currentselect.options[j].selected = true;
		}
	}
	
	
	
	var items = currentselect.options.length;
	
	// create array and make copies of options in list
	
	var tmpArray = new Array(items);
	
	for ( i=0; i<items; i++ )
		tmpArray[i] = new Option(currentselect.options[i].text, currentselect.options[i].value);

	// sort options using given function
	tmpArray.sort(bab_compareOptionText);
	
	// make copies of sorted options back to list
	for ( i=0; i<items; i++ )
		currentselect.options[i] = new Option(tmpArray[i].text,tmpArray[i].value);
}
