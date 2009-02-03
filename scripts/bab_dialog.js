
function bab_dialog(url, parameters, action, init, obj) {
	if (typeof init == "undefined") {
		init = window;	// pass this window object by default
	}

	var useparam = {
		'toolbar'		: 'no',
		'menubar'		: 'no',
		'personalbar'	: 'no',
		'width'			: 200,
		'height'		: 200,
		'scrollbars'	: 'yes',
		'resizable'		: 'yes',
		'modal'			: 'yes',
		'dependable'	: 'yes'
	}


	for (var p in parameters) {
		useparam[p] = parameters[p];
	}

	if (!document.all) {
		useparam['left'] = window.screenX + (window.outerWidth - useparam['width']) / 2;
		useparam['top'] = window.screenY + (window.outerHeight - useparam['height']) / 2;
	} else {
		useparam['left'] = (screen.availWidth - useparam['width']) / 2;
		useparam['top'] = (screen.availHeight - useparam['height']) / 2;
	}

	var tmp = new Array();
	for (var p in useparam) {
		tmp.push(p+'='+useparam[p]+'');
	}

	useparam = tmp.join(',');

	bab_dialog._openModal(url, useparam, action, init, obj);

};

bab_dialog._parentEvent = function(ev) {
	setTimeout( function() { if (bab_dialog._modal && !bab_dialog._modal.closed) { bab_dialog._modal.focus() } }, 50);
	if (bab_dialog._modal && !bab_dialog._modal.closed) {
		bab_dialog._stopEvent(ev);
	}
};

bab_dialog._stopEvent = function(ev) {
	if (document.all) {
		ev.cancelBubble = true;
		ev.returnValue = false;
	} else {
		ev.preventDefault();
		ev.stopPropagation();
	}
};

// should be a function, the return handler of the currently opened dialog.
bab_dialog._return = null;

// constant, the currently opened dialog
bab_dialog._modal = null;

// the dialog will read it's args from this variable
bab_dialog._arguments = null;



bab_dialog._addEvent = function(el, evname, func) {
	try {
		if (document.all) {
			el.attachEvent("on" + evname, func);
		} else {
			el.addEventListener(evname, func, true);
		}
	} catch(e)
	{
	}
};


bab_dialog._removeEvent = function(el, evname, func) {
	try {
		if (document.all) {
			el.detachEvent("on" + evname, func);
		} else {
			el.removeEventListener(evname, func, true);
		}
	} catch(e)
	{
	}
};


bab_dialog._openModal = function(url, parameters, action, init, obj) {
	var dlg = window.open(url, "bab_dialog", parameters );
	bab_dialog._modal = dlg;
	bab_dialog._arguments = init;

	// capture some window's events
	function capwin(w) {
		bab_dialog._addEvent(w, "click",		bab_dialog._parentEvent);
		bab_dialog._addEvent(w, "mousedown",	bab_dialog._parentEvent);
		bab_dialog._addEvent(w, "focus",		bab_dialog._parentEvent);
	};
	// release the captured events
	function relwin(w) {
		bab_dialog._removeEvent(w, "click",		bab_dialog._parentEvent);
		bab_dialog._removeEvent(w, "mousedown", bab_dialog._parentEvent);
		bab_dialog._removeEvent(w, "focus",		bab_dialog._parentEvent);
	};
	capwin(window);
	// capture other frames
	for (var i = 0; i < window.frames.length; capwin(window.frames[i++]));
	// make up a function to be called when the Dialog ends.
	bab_dialog._return = function(val) {
		if (val && action) {
			if (obj) {
				obj[action](val);
			} else {
				action(val);
			}
		}
		relwin(window);
		// capture other frames
		for (var i = 0; i < window.frames.length; relwin(window.frames[i++]));
		bab_dialog._modal = null;
	};
};



// specific functions

/**
 * A calendar dialog
 * @param action function, an associative array with keys "day", "month", "year" will be given to "action" as a single parameter
 */
bab_dialog.selectdate = function(action) {

	var useparam = {
		'width'		: 200,
		'height'	: 200,
		'scrollbars': 'no'
	}

	bab_dialog('?tg=month&callback=bab_dialog', useparam , action );
}

/**
 * A user list dialog.
 * @param action function receiving an associative array with keys "id_user" and "name" as parameter.
 */
bab_dialog.selectuser = function(action) {

	var useparam = {
		'width'		: 700,
		'height'	: 500
	}

	bab_dialog('?tg=lusers&idx=brow&cb=bab_dialog', useparam , action );
}

/**
 * An articles/topics/categories tree dialog.
 * Possible values for 'attributes' are:
 * <ul>
 * <li>show_categories: show article categories</li>
 * <li>show_topics: show article topics (implies show_categories)</li>
 * <li>show_articles: show articles (implies show_topics and show_categories)</li>
 * <li>selectable_categories: categories can be selected</li>
 * <li>selectable_topics: topics can be selected</li>
 * <li>selectable_articles: articles can be selected</li>
 * <li>ignored_categories: followed a by comma separated list of categories id that should not be displayed.
 * </ul>
 * @param function action	receiving an associative array with keys "id", "type" and "content" as parameter.
 * @param string attributes	list of '&' separated attributes for the articles/topics/categories tree selector.
 */
bab_dialog.selectarticle = function(action, attributes) {

	var useparam = {
		'width'		: 700,
		'height'	: 500
	};
	url = '?tg=selector&idx=articles';
	if (attributes != '')
		url += '&' + attributes;
	bab_dialog(url, useparam, action);
}

/**
 * A faq tree dialog.
 * Possible values for 'attributes' are:
 * <ul>
 * <li>show_categories: show faq categories</li>
 * <li>show_sub_categories: show faq sub-categories (implies show_categories)</li>
 * <li>show_questions: show faq questions-answers (implies show_sub_categories and show_categories)</li>
 * <li>selectable_categories: categories can be selected</li>
 * <li>selectable_sub_categories: sub-categories can be selected</li>
 * <li>selectable_questions: questions-answers can be selected</li>
 * </ul>
 * @param action  function receiving an associative array with keys "id", "type" and "content" as parameter.
 * @param string attributes	list of '&' separated attributes for the faq/sub-categories/questions-answers tree selector.
 */
bab_dialog.selectfaq = function(action, attributes) {
	var useparam = {
		'width'		: 700,
		'height'	: 500
	};
	url = '?tg=selector&idx=faqs';
	if (attributes != '')
		url += '&' + attributes;
	bab_dialog(url, useparam, action);
}

/**
 * A forum tree dialog.
 * Possible values for 'attributes' are:
 * <ul>
 * <li>show_forums: show forums</li>
 * <li>show_threads: show threads (implies show_posts)</li>
 * <li>show_posts: show posts (implies show_threads and show_posts)</li>
 * <li>selectable_forums: forums can be selected</li>
 * <li>selectable_threads: threads can be selected</li>
 * <li>selectable_posts: posts can be selected</li>
 * </ul>
 * @param action  function receiving an associative array with keys "id", "type" and "content" as parameter.
 * @param string attributes	list of '&' separated attributes for the forums/threads/posts tree selector.
 */
bab_dialog.selectforum = function(action, attributes) {
	var useparam = {
		'width'		: 700,
		'height'	: 500
	};
	url = '?tg=selector&idx=forums';
	if (attributes != '')
		url += '&' + attributes;
	bab_dialog(url, useparam, action);
}

/**
 * A file tree dialog.
 * Possible values for 'attributes' are:
 * <ul>
 * <li>show_collective_directories: show collective directories</li>
 * <li>show_personal_directories: show personal directories</li>
 * <li>show_sub_directories: show sub-directories</li>
 * <li>show_files: show files (implies show_sub_directories)</li>
 * <li>selectable_collective_directories: collective directories can be selected</li>
 * <li>selectable_sub_directories: sub-directories can be selected</li>
 * <li>selectable_files: files can be selected</li>
 * <li>multi: more than 1 item can be selected (there will be a checkboxes and a "select" button)</li>
 * </ul>
 * @param action  function receiving an associative array with keys "id", "type" and "content" as parameter.
 * @param string attributes	list of '&' separated attributes for the folders/files tree selector.
 */
bab_dialog.selectfile = function(action, attributes) {
	var useparam = {
		'width'		: 700,
		'height'	: 500
	};
	url = '?tg=selector&idx=files';
	if (attributes != '')
		url += '&' + attributes;
	bab_dialog(url, useparam, action);
}


/**
 * A groups selector dialog.
 * <ul>
 * <li>selectable_groups: groups can be selected</li>
 * <li>multi: more than 1 item can be selected (there will be a checkboxes and a "select" button)</li>
 * </ul>
 * @param action  function receiving an associative array with keys "id", "type" and "content" as parameter. 
 * @param string attributes	list of '&' separated attributes for the groups tree selector.
 */
bab_dialog.selectgroups = function(action, attributes) {
	var useparam = {
		'width'		: 700,
		'height'	: 500
	};
	url = '?tg=selector&idx=groups';
	if (attributes != '')
		url += '&' + attributes;
	bab_dialog(url, useparam, action);
}


/** 
 * Create a selector from a input field
 * icon can be set only by the function if there is no classname on the field
 * @since	6.1.1
 *
 * @param	object|string	field
 * @param	string			label
 * @param	function		onclickEvt
 * @param	string			[icon]		icon path from the "images" directory of the skin
 */
bab_dialog.field = function(field, label, onclickEvt, icon) {

	if (typeof field == 'string') {
		field = document.getElementById(field);
	}
	oldwidth = field.offsetWidth;
	oldheight = field.offsetHeight;
	field.style.display = 'none';
	
	contener = document.createElement('div');
	

	if ('' == field.className) {
	
		if (null == icon) {
			icon = 'Puces/reload.png';
		}
	
		contener.style.width 		= oldwidth+'px';
		contener.style.minHeight 	= oldheight+'px';
		contener.style.cursor 		= 'pointer';
		contener.style.border 		= '#000 1px solid';
		contener.style.background 	= '#fff url('+bab_getInstallPath()+'skins/ovidentia/images/'+icon+') no-repeat 99% 50%';
		contener.style.color 		= '#444';
		contener.style.padding 		= '.2em 1em .2em .6em';
	
		
	} else {
		contener.className = field.className;
	}

	
	field.parentNode.insertBefore(contener, field);
	while(contener.lastChild) {
		contener.lastChild.removeNode(true);
	}
	contener.appendChild(document.createTextNode(label));
	
	contener.onclick = function() {
		onclickEvt(contener);
	}
}