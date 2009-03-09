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





