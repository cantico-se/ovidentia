

function bab_translate(text)
{
	if (!window.bab_Translations) {
		return text;
	}
	if (window.bab_Translations[text]) {
		return window.bab_Translations[text];
	}
	return text;
}


function bab_SearchContext(tree, inputField)
{
	this.tree = tree;
	this.inputField = inputField;
	this.entities = $(tree).select('.entity'); //bab_getElementsByClassName(tree, 'entity');
	this.currentIndex = 0;
	this.timeoutId = null;
	this.nbItemsPerLoop = 5;
	this.nbMatches = 0;
	this.searching = false;
	this.targetString = '';
}


function bab_initSearch()
{
	window.console && window.console.time('bab_initSearch');
	if (this.initDone)
		return;
	var entities = $(this).select('.entity'); //bab_getElementsByClassName(this, 'entity');
	var nbEntities = entities.length;
	for (var i = 0; i < nbEntities; i++) {
		var a = entities.item(i).getElementsByTagName('A')[0]
		var text = a.firstChild.nodeValue;
		text = cleanStringDiacritics(text);
		entities.item(i).setAttribute('content', text);
	}
	this.initDone = true;
	window.console && window.console.time('bab_initSearch');
}


function bab_collapseOrgChart()
{
	var openSwitches = $(this).select('.switch_open'); //bab_getElementsByClassName(this, 'switch_open');
	
	var nbOpenSwitches = openSwitches.length;
	while (openSwitches.length > 0) {
		var openSwitch = openSwitches[0];
		openSwitch.controlledElement.style.display = 'none';
		openSwitch.className = 'switch_closed';
	}
}


function bab_showEntity(entity)
{
	for (var element = entity.parentNode.parentNode; element; element = element.parentNode) {
		var e = element.getElementsByTagName('DIV')[0];
		if (hasClass(e, 'entity')) {
			$(e).removeClassName('shaded');
			for (var s = e.nextSibling; s; s = s.nextSibling) {
				if (s.className == 'switch_closed') { 
					s.className = 'switch_open';
					s.controlledElement.style.display = '';
					break;
				}
			}
		}
	}
}


function bab_search()
{
	var context = window.bab_searchContext;
	if (!context.searching) {
		return;
	}
	if (context.targetString != cleanStringDiacritics(context.inputField.value)) {
		context.currentIndex = 0;
		context.nbMatches = 0;
		context.tree.collapse = bab_collapseOrgChart;
		context.tree.collapse();

		context.tree.initSearch = bab_initSearch;
		context.tree.initSearch();

		context.targetString = cleanStringDiacritics(context.inputField.value);
	}
	var targetString = context.targetString;

	var nbItems = context.nbItemsPerLoop;

	var currentIndex = context.currentIndex;
	var totalListItems = context.entities.length;
	while (nbItems-- > 0 && currentIndex < totalListItems) {
		var entity = context.entities[currentIndex];
		var content = entity.getAttribute('content');
		if (content && content.indexOf(targetString) > -1) {
			bab_showEntity(entity);
			$(entity).removeClassName('shaded');
			$(entity).addClassName('matching');
			context.nbMatches++;
		} else {
			$(entity).removeClassName('matching');
			$(entity).addClassName('shaded');
		}
		currentIndex++;
	}

	if (context.targetString == cleanStringDiacritics(context.inputField.value)) {
		context.currentIndex = currentIndex;
	} else {
		context.timeoutId = window.setTimeout(window.bab_search, 0);
		return;
	}

	window.status = '[' + context.nbMatches + '] ' + context.currentIndex + ' / ' + context.entities.length
	context.inputField.style.backgroundPosition = '' + (100 * currentIndex) / context.entities.length + '% 0'

	if (currentIndex < totalListItems) {
		context.timeoutId = window.setTimeout(window.bab_search, 0);
	} else {
		if (context.nbMatches > 1) {
			context.inputField.className = 'bab_searchField';
		} else if (context.nbMatches == 0) {
			context.inputField.className = 'bab_searchFieldNotFound';
		} else {
			context.inputField.className = 'bab_searchFieldFound';			
		}
		context.inputField.style.backgroundPosition = '1px 50%'
		context.searching = false;
	}
}


function bab_delaySearch()
{
	var context = window.bab_searchContext;

	if (this.value.length >= 1) {
		if (context.targetString != cleanStringDiacritics(context.inputField.value)) {
			if (context.searching == false) {
				context.searching = true;
				context.timeoutId = window.setTimeout(bab_search, 1000);
			}
			this.className = 'bab_searchFieldSearching';
			context.targetString = '';
		}
	} else {
		window.clearTimeout(context.timeoutId);
		context.inputField.style.backgroundPosition = '1px 50%'
//		if (context.searching) {
			this.className = 'bab_searchField';
			var entities = $(this.parentNode.controlledElement).select('.entity'); //bab_getElementsByClassName(this.parentNode.controlledElement, 'entity');
			var nbEntities = entities.length;
			for (var i = 0; i < nbEntities; i++) {
				var e = entities[i];
				$(e).removeClassName('matching');
				$(e).removeClassName('shaded');
			}
//		}
		context.searching = false;
	}
}





Array.prototype.contains = function(val) {
	for (var i in this)
		if (this[i] == val)
			return true;
	return false;
}

function bab_arrayContains(a, val)
{
	for (var i in a)
		if (a[i] == val)
			return true;
	return false;
}

function hasClass(element, className) {
	if (element.className == undefined)
		return false;
	classes = element.className.split(' ');
	for (var i in classes)
		if (classes[i] == className)
			return true;
	return false;
}

function hasOneOfClasses(element, classNames)
{
	for (var i = 0; i < classNames.length; i++) {
		if (Element.hasClassName(element, classNames[i])) {
			return true;
		}	
	}
	return false;
}

function bab_toggleCollapsed()
{
	var element = this.controlledElement;
	var display = this.controlledElement.style.display;
	if (display == 'none') {
		if (typeof($j) != 'undefined') {
			$j(element).show('fast');
		} else {
			element.style.display = '';
		}
		this.className = 'switch_open';
	} else {
		if (typeof($j) != 'undefined') {
			$j(element).hide('normal');
		} else {
			element.style.display = 'none';
		}
		this.className = 'switch_closed';
	}
	this.orgChart.saveStateInCookie();
	bab_refresh(this.orgChart);
}


function bab_getOpenNodes()
{
	var openSwitchList = $(this).select('.switch_open'); //bab_getElementsByClassName(this, 'switch_open');
	
	var entityIds = new Array();
	openSwitchList.each(function(openSwitch) {
		var entity = openSwitch.controlledElement.parentEntity;
		entityIds.push(entity.id);
	});
	return entityIds;
}

function bab_getOpenMembers()
{
	var membersList =  $(this).select('.members'); //bab_getElementsByClassName(this, 'members');
	
	var entityIds = new Array();
	membersList.each(function(members) {
		if (members.style.display != 'none') {
			var entity = members.parentNode;
			entityIds.push(entity.id);
		}
	});
	return entityIds;
}



function bab_setOpenNodes(entityIds)
{
	var body = document.getElementsByTagName('body')[0];
	var switchDivs = $(body).select('.switch_open');
	switchDivs.each(function(switchDiv) {
		if (!entityIds.contains(switchDiv.controlledElement.parentEntity.id)) {
			switchDiv.className = 'switch_closed';
			switchDiv.controlledElement.style.display = 'none';
		}
	});

	switchDivs = $(body).select('.switch_closed');
	switchDivs.each(function(switchDiv) {
		if (entityIds.contains(switchDiv.controlledElement.parentEntity.id)) {
			switchDiv.className = 'switch_open';
			switchDiv.controlledElement.style.display = '';
		}
	});
}


function bab_setOpenMembers(memberIds)
{
	var body = document.getElementsByTagName('body')[0];
	var membersList = $(body).select('.members');
	membersList.each(function(members) {
		members.style.display = 'none';
	});
	for (var i = 0; i < memberIds.length; i++) {
		var entity = document.getElementById(memberIds[i]);
		if (entity && entity.members) {
			entity.members.style.display = '';
		}
	}
}




function bab_saveStateInCookie()
{
//	console.log('bab_saveStateInCookie');
	var cookiePath = '/';
	var entityIds = this.getOpenNodes();
	var memberIds = this.getOpenMembers();
	document.cookie = this.id + 'nodes=' + escape(entityIds.join('/'))
						+ '; path=' + cookiePath;
	document.cookie = this.id + 'members=' + escape(memberIds.join('/'))
						+ '; path=' + cookiePath;
	document.cookie = this.id + 'zoom=' + escape(this.zoomFactor)
						+ '; path=' + cookiePath;
	document.cookie = this.id + 'threshold=' + escape(this.thresholdLevel)
						+ '; path=' + cookiePath;
	document.cookie = this.id + 'relative=' + escape(this.thresholdRelative)
						+ '; path=' + cookiePath;
}


function bab_loadStateFromCookie()
{
	var pairs = document.cookie.split('; ');
	for (var i = 0; i < pairs.length; i++) {
		var keyValue = pairs[i].split('=');
		if (keyValue[1] != '') {
			if (keyValue[0] == this.id + 'nodes') {
				var nodeIds = unescape(keyValue[1]).split('/');
				this.setOpenNodes(nodeIds);
			} else if (keyValue[0] == this.id + 'members') {
				var memberIds = unescape(keyValue[1]).split('/');
				this.setOpenMembers(memberIds);
			} else if (keyValue[0] == this.id + 'zoom') {
				//this.zoomFactor = parseFloat(keyValue[1]);
				this.setZoom(parseFloat(keyValue[1]));
			}  else if (keyValue[0] == this.id + 'threshold') {
				this.thresholdLevel = parseInt(keyValue[1]);
			}  else if (keyValue[0] == this.id + 'relative') {
				this.setRelative(keyValue[1]);
			}
		}
	}
}


function bab_refresh(orgChartDiv)
{
	// We force recalculation of sizes in ie.
	orgChartDiv.style.display = 'none';
	var tables = orgChartDiv.getElementsByTagName('TABLE');
	try {
		tables[0].style.fontSize = '0.1em'; 
		tables[0].style.fontSize = orgChartDiv.zoomFactor + 'em';
	} catch(e) { };
	orgChartDiv.style.display = '';
}



function bab_setThreshold()
{
	var orgChartDiv = this.controlledElement;
	orgChartDiv.thresholdLevel = parseInt(this.value);
	
	orgChartDiv.saveStateInCookie();

	document.location.reload();
}

function bab_setThresholdRelative()
{
	var orgChartDiv = this.controlledElement;
	orgChartDiv.thresholdRelative = (this.checked ? 'on' : 'off');
	
	orgChartDiv.saveStateInCookie();

	document.location.reload();
}

function bab_setLevel()
{
	//console && console.time('bab_setLevel');
	var orgChartDiv = this.controlledElement;
	orgChartDiv.currentLevel = parseInt(this.value);

	var previousLevels = Array();
	for (var i = 1; i <= orgChartDiv.currentLevel; i++) {
		previousLevels.push('level' + i);
	}
	var nextLevels = Array();
	for (var i = orgChartDiv.currentLevel + 1; i <= 9; i++) {
		nextLevels.push('level' + i);
	}

	var divs = orgChartDiv.getElementsByTagName('DIV');
	var nbDivs = divs.length;
	for (var i = 0; i < nbDivs; i++) {
		var div = divs[i];
		if (Element.hasClassName(div, 'switch_closed') && hasOneOfClasses(div.controlledElement, previousLevels)) {
			div.controlledElement.style.display = '';
			div.className = 'switch_open';
		}
		else if (Element.hasClassName(div, 'switch_open') && hasOneOfClasses(div.controlledElement, nextLevels)) {
			div.controlledElement.style.display = 'none';
			div.className = 'switch_closed';
		}
	}
	bab_refresh(orgChartDiv);

	orgChartDiv.saveStateInCookie();
	//console && console.timeEnd('bab_setLevel');
}


function bab_setRelative(relativeThreshold)
{
	this.relativeThreshold = relativeThreshold;
	this.relativeCheckbox.checked = (relativeThreshold == 'on');
	bab_refresh(this);
}


function bab_setZoom(zoomFactor)
{
	this.zoomFactor = zoomFactor;
	this.zoomWidget.zoomText.firstChild.nodeValue = parseInt(this.zoomFactor * 100 + 0.5) + "%";
	bab_refresh(this);
}

function bab_zoomIn(evt)
{
    evt = evt || window.event;
	var orgChartDiv = this.getControlledElement();
	orgChartDiv.setZoom(orgChartDiv.zoomFactor * 1.071773463);
	orgChartDiv.saveStateInCookie();
	return false;
}

function bab_zoomOut(evt)
{
    evt = evt || window.event;
	var orgChartDiv = this.getControlledElement();
	orgChartDiv.setZoom(orgChartDiv.zoomFactor / 1.071773463);
	orgChartDiv.saveStateInCookie();
	return false;
}


function bab_zoomFit()
{
	var orgChartDiv = this.getControlledElement();
	var tables = orgChartDiv.getElementsByTagName('TABLE');
	var factor = (orgChartDiv.offsetWidth - 20) / (tables[0].getElementsByTagName('TBODY')[0].offsetWidth);
	
	var n = Math.floor(10 * Math.log(factor) / Math.LN2);
	factor = Math.pow(2, 0.1 * n);
	
	orgChartDiv.style.display = 'none';
	orgChartDiv.zoomFactor *= factor;
	this.zoomText.firstChild.nodeValue = parseInt(orgChartDiv.zoomFactor * 100 + 0.5) + "%";
	tables[0].style.fontSize = orgChartDiv.zoomFactor + 'em';
	orgChartDiv.style.display = '';
	orgChartDiv.saveStateInCookie();
}


function bab_toggleMembers(action) {
	var entity = action.entity;
	var members = entity.members;
	if( typeof(members) != 'undefined' ) {
		if (members.style.display == 'none') {
			if (typeof($j) != 'undefined') {
				$j(members).slideDown('fast');
			} else {
				members.style.display = '';
			}
		} else {
			if (typeof($j) != 'undefined') {
				$j(members).slideUp('fast');
			} else {
				members.style.display = 'none';
			}		
		}
	}
	entity.orgChart.saveStateInCookie();
	return false;
}


// This function does in ie what the css engine do in css compliant browsers.
function bab_resizeOrgChartContainer() {
	// Only for ie.
	if (!document.all)
		return;
	var orgChartDiv = window.bab_orgChart;
	var orgChartContainer = orgChartDiv.parentNode;
	var toolbar = $(orgChartContainer).select('.bab_treeToolbar')[0]; //bab_getElementsByClassName(orgChartContainer, 'bab_treeToolbar')[0];
	var locationbar = $(orgChartContainer).select('.bab_treeLocationBar')[0]; //bab_getElementsByClassName(orgChartContainer, 'bab_treeLocationBar')[0];
	
	var toolbarDimensions = Element.getDimensions(toolbar);
	var locationbarDimensions = Element.getDimensions(locationbar);
	
	var ieBody = (document.documentElement ? document.documentElement : document.body)
	
		
	var bodyDimensions = Element.getDimensions(ieBody);
	
	orgChartContainer.style.width = (bodyDimensions.width - 4) + "px";
	orgChartContainer.style.height = (bodyDimensions.height - 4) + "px";
	orgChartDiv.style.top = (toolbarDimensions.height + locationbarDimensions.height) + "px";
	orgChartDiv.style.left = "0px";
	orgChartDiv.style.width = (bodyDimensions.width - 4) + "px";
	orgChartDiv.style.height = (bodyDimensions.height - (toolbarDimensions.height + locationbarDimensions.height) - 4) + "px";
	
	bab_refresh(orgChartDiv);
}

function bab_saveState()
{
	var orgChartDiv = this.getControlledElement();
	window.location = '?tg=frchart&idx=save_state&disp=disp3&ocid=' + orgChartDiv.orgChartId + '&oeid=' + orgChartDiv.entityId + '&iduser=' + orgChartDiv.userId + '&open_nodes=' + orgChartDiv.getOpenNodes().join(',') + '&open_members=' + orgChartDiv.getOpenMembers().join(',') + '&zoom_factor=' + orgChartDiv.zoomFactor + '&threshold_level=' + orgChartDiv.thresholdLevel;
}

function bab_restoreState()
{
	var orgChartDiv = this.getControlledElement();
	var cookiePath = '/';
	var expiryDate = new Date;
	expiryDate.setFullYear(expiryDate.getFullYear() - 1);
	document.cookie = orgChartDiv.id + 'nodes=' + escape(null)
						+ '; expires=' + expiryDate.toGMTString()
						+ '; path=' + cookiePath;
	document.cookie = orgChartDiv.id + 'members=' + escape(null)
						+ '; expires=' + expiryDate.toGMTString()
						+ '; path=' + cookiePath;
	document.cookie = orgChartDiv.id + 'zoom=' + escape(null)
						+ '; expires=' + expiryDate.toGMTString()
						+ '; path=' + cookiePath;
	document.cookie = orgChartDiv.id + 'threshold=' + escape(null)
						+ '; expires=' + expiryDate.toGMTString()
						+ '; path=' + cookiePath;
	document.cookie = orgChartDiv.id + 'relative=' + escape(null)
						+ '; expires=' + expiryDate.toGMTString()
						+ '; path=' + cookiePath;
	window.onunload = function() {	};
	window.location.href = '?tg=frchart&idx=list&disp=disp3&ocid=' + orgChartDiv.orgChartId + '&oeid=' + orgChartDiv.entityId + '&iduser=' + orgChartDiv.userId;
}

function bab_print()
{
	var orgChartDiv = this.getControlledElement();
	window.print();
}

function bab_help()
{
	window.open('?tg=oml&echo=1&file=help/orgchart.html');
}

function bab_more()
{
	var orgChartDiv = this.getControlledElement();
	var orgChartContainer = orgChartDiv.parentNode;

	var configbar = $(orgChartContainer).select('.bab_treeToolbar')[1];
	//configbar.style.display = 'none';
	$(configbar).toggle();
}


function getControlledElement()
{
	if (this.controlledElement) {
		return this.controlledElement;
	}
	try {
		return this.parentNode.getControlledElement();
	} catch(e) {
		return null;	
	}
}

function createToolbarGroup(label, className)
{
	var toolbarGroup = document.createElement('SPAN');
	toolbarGroup.className = 'bab_toolbarGroup ' + className;
	
	var spanLabel = document.createElement('SPAN');
	spanLabel.appendChild(document.createTextNode(label));
	spanLabel.className = 'bab_label';
	toolbarGroup.appendChild(spanLabel);
	toolbarGroup.getControlledElement = getControlledElement;
	toolbarGroup.onselectstart = function () { return false; } // ie
	toolbarGroup.onmousedown = function () { return false; } // mozilla
	return toolbarGroup;
}

function createLabelInput(label, className)
{
	var toolbarGroup = document.createElement('SPAN');
	toolbarGroup.className = 'bab_toolbarGroup ' + className;
	toolbarGroup.appendChild(document.createTextNode(label));
	toolbarGroup.getControlledElement = getControlledElement;
	return toolbarGroup;
}

function createButton(label, className)
{
	var link = document.createElement('A');
	link.appendChild(document.createTextNode(label + ' '));
	link.className = 'bab_toolbarButton ' + className;
	link.getControlledElement = getControlledElement;
	link.onselectstart = function () { return false; } // ie
	link.onmousedown = function () { return false; } // mozilla
	return link;
}


function bab_createContextMenu(actionsDiv, entity)
{
	entity.actions = actionsDiv;
	actionsDiv.style.display = 'none';
	actionsDiv.controlledElement = entity;
	var menuButton = document.createElement('DIV');
	var menuImage = document.createElement('IMG')
	menuImage.src = bab_getInstallPath() + 'skins/ovidentia/images/orgchart/menu.gif';
	menuButton.appendChild(menuImage);
//	menuButton.appendChild(document.createTextNode('Menu'));
	entity.appendChild(menuButton);
	menuButton.style.display = 'none';
	menuButton.style.position = 'absolute';
	menuButton.style.right = '0px';
	menuButton.style.top = '0px';
	menuButton.style.height = '16px';
//	menuButton.style.backgroundColor = '#ffffff';
//	menuButton.style.color = '#000000';
	menuButton.onclick = function(evt) {
	    evt = evt || window.event;
	    var cursorPosition = getCursorPosition(evt);
	    var back = document.createElement('DIV')
	    back.style.display = "block";
	    back.style.position = "absolute";
	    back.style.left = '0px';
	    back.style.top = '0px';
	    back.style.right = '0px';
	    back.style.bottom = '0px';
		if (document.all) {
		    back.style.width = window.bab_orgChart.parentNode.style.width;
		    back.style.height = window.bab_orgChart.parentNode.style.height;
		}
	    back.style.backgroundImage = 'url(' + bab_getInstallPath() + 'skins/ovidentia/images/spacer.gif)'; //'transparent';

	    back.actions = entity.actions;
	    back.onclick = function(evt) {
			this.actions.style.display = "none";
		    document.body.removeChild(this);
		}
	    document.body.appendChild(back);
		back.appendChild(entity.actions);
		entity.actions.style.position = "absolute";
		
		var style = entity.actions.style;

		style.display == '' ? style.display = 'none' : style.display = '';
		
		var windowWidth = (typeof window.innerWidth != 'undefined') ? window.innerWidth : document.body.offsetWidth;

		var menuWidth = entity.actions.offsetWidth;

		if (cursorPosition.x > windowWidth - menuWidth) {
			style.left = (windowWidth - menuWidth - 4) + 'px';
		} else {
			style.left = (cursorPosition.x - 32) + 'px';
		}
		style.top = (cursorPosition.y + 12) + 'px';
		

	};
	entity.menuButton = menuButton;
	entity.onmouseover = function() { this.menuButton.style.display = ''; };
	entity.onmouseout = function() { this.menuButton.style.display = 'none'; };

	var actionList = $(actionsDiv).select('.action'); //bab_getElementsByClassName(actionsDiv, 'action');
	actionList.each(function(action) {
		action.parentNode.appendChild(document.createTextNode(' ' + action.parentNode.title));
		action.parentNode.title = '';
		action.parentNode.entity = entity;
		action.parentNode.style.display = 'block';
	});
}

function bab_initOrgChart(orgChartDiv)
{
	window.bab_orgChart = orgChartDiv;

	orgChartDiv.getOpenNodes = bab_getOpenNodes;
	orgChartDiv.getOpenMembers = bab_getOpenMembers;
	orgChartDiv.saveStateInCookie = bab_saveStateInCookie;
	orgChartDiv.loadStateFromCookie = bab_loadStateFromCookie;
	orgChartDiv.setOpenNodes = bab_setOpenNodes;
	orgChartDiv.setOpenMembers = bab_setOpenMembers;

	// Toolbar creation.
	var orgChartContainer = orgChartDiv.parentNode;

	var toolbar = $(orgChartContainer).select('.bab_treeToolbar')[0]; //bab_getElementsByClassName(orgChartContainer, 'bab_treeToolbar')[0];
	if (toolbar) {
		toolbar.controlledElement = orgChartDiv;
		toolbar.getControlledElement = getControlledElement;

		var zoomWidget = createToolbarGroup('Zoom', '');

		var zoomText = document.createElement('SPAN');
		zoomText.appendChild(document.createTextNode('100%'));

		zoomWidget.zoomText = zoomText;
	
		var zoomFit = createButton('', 'bab_zoomFit');
		zoomFit.onclick = bab_zoomFit;
		zoomFit.zoomText = zoomText;
		zoomFit.title = window.bab_Translations['fit_width'];
		
		var zoomIn = createButton('', 'bab_zoomIn');
		zoomIn.id = 'bab_zoomInButton';
		zoomIn.onclick = bab_zoomIn;
		zoomIn.zoomText = zoomText;
		zoomIn.title = window.bab_Translations['zoom_in'];
				
		var zoomOut = createButton('', 'bab_zoomOut');
		zoomOut.id = 'bab_zoomOutButton';
		zoomOut.onclick = bab_zoomOut;
		zoomOut.zoomText = zoomText;
		zoomOut.title = window.bab_Translations['zoom_out'];
	
		zoomWidget.appendChild(zoomFit);
		zoomWidget.appendChild(zoomOut);
		zoomWidget.appendChild(zoomText);
		zoomWidget.appendChild(zoomIn);
	
		toolbar.appendChild(zoomWidget);
		
		orgChartDiv.zoomWidget = zoomWidget;
		orgChartDiv.setZoom = bab_setZoom;
		orgChartDiv.setZoom(orgChartDiv.zoomFactor);

		if (orgChartDiv.adminMode) {
			var save = createButton('', 'bab_save');
			save.onclick = bab_saveState;
			save.title = window.bab_Translations['save_default_view'];
			toolbar.appendChild(save);
		}

		var restore = createButton('', 'bab_restore');
		restore.onclick = bab_restoreState;
		restore.title = window.bab_Translations['default_view'];

		toolbar.appendChild(restore);

		var print = createButton('', 'bab_print');
		print.onclick = bab_print;
		print.title = window.bab_Translations['print'];
		toolbar.appendChild(print);

		var help = createButton('', 'bab_help');
		help.onclick = bab_help;
		help.title = window.bab_Translations['help'];
		toolbar.appendChild(help);

		var more = createButton(window.bab_Translations['parameters'], 'bab_more');
		more.onclick = bab_more;
		more.title = window.bab_Translations['parameters'];
		toolbar.appendChild(more);
/*
		var searchWidget = createLabelInput('Search' + ' ', '');
		searchWidget.title = 'Search entities';
		searchWidget.controlledElement = orgChartDiv;
		var search = document.createElement('INPUT');
		search.type = 'text';
		search.className = 'bab_searchField';
		search.onkeyup = bab_delaySearch;
		toolbar.appendChild(searchWidget);
		searchWidget.appendChild(search);
		window.bab_searchContext = new bab_SearchContext(orgChartDiv, search);
*/
	}
	var configbar = $(orgChartContainer).select('.bab_treeToolbar')[1]; //bab_getElementsByClassName(orgChartContainer, 'bab_treeToolbar')[0];
	if (configbar) {

		configbar.style.display = 'none';

		// Visible level selector.
		var levelWidget = createToolbarGroup(window.bab_Translations['visible_levels'] + ' ', '');
		levelWidget.title = window.bab_Translations['visible_levels_tip'];

		var levelSelect = document.createElement('SELECT');
		levelSelect.controlledElement = orgChartDiv;

		var option = document.createElement('OPTION');
		option.appendChild(document.createTextNode('-'));
		levelSelect.appendChild(option);

		for (var i = 1; i <= 9; i++) {
			option = document.createElement('OPTION');
			option.value = i;
			option.className = 'level' + i;
			option.appendChild(document.createTextNode(i));
			levelSelect.appendChild(option);
		}
		levelSelect.onchange = bab_setLevel;
		
		levelWidget.appendChild(levelSelect);
		
		configbar.appendChild(levelWidget);
		
		orgChartDiv.levelSelect = levelSelect;


		// Horizontal-vertical threshold selector.
		var thresholdWidget = createToolbarGroup(window.bab_Translations['threshold'] + ' ', '');
		thresholdWidget.title = window.bab_Translations['threshold_tip'];

		var thresholdSelect = document.createElement('SELECT');
		thresholdSelect.controlledElement = orgChartDiv;

		for (var i = 1; i <= 9; i++) {
			option = document.createElement('OPTION');
			option.value = i + 1;
//			option.className = 'level' + i;
			option.appendChild(document.createTextNode(i));
			thresholdSelect.appendChild(option);
		}
		thresholdSelect.onchange = bab_setThreshold;
		
		thresholdWidget.appendChild(thresholdSelect);
		
		configbar.appendChild(thresholdWidget);
		
		orgChartDiv.thresholdSelect = thresholdSelect;
		
		// Relative threshold selector.
		var relativeWidget = createToolbarGroup(window.bab_Translations['relative'] + ' ', '');
		//relativeWidget.title = window.bab_Translations['relative_tip'];

		var relativeCheckbox = document.createElement('INPUT');
		relativeCheckbox.type = 'checkbox';
		relativeCheckbox.controlledElement = orgChartDiv;
		relativeCheckbox.onclick = bab_setThresholdRelative;
		
		relativeWidget.appendChild(relativeCheckbox);
		
		configbar.appendChild(relativeWidget);
		
		orgChartDiv.relativeCheckbox = relativeCheckbox;

		orgChartDiv.setRelative = bab_setRelative;
		
	}

	//console && console.timeEnd('toolbar');


	//console && console.time('switches');
	
	var horizontalList = $(orgChartDiv).select('.horizontal'); //bab_getElementsByClassName(orgChartDiv, 'horizontal');
	var verticalList = $(orgChartDiv).select('.vertical'); //bab_getElementsByClassName(orgChartDiv, 'vertical');
	var levelList = horizontalList.concat(verticalList);

	levelList.each(function(level) {
		if (Element.hasClassName(level, 'level1'))
			return; 
		level.parentEntity = level.parentNode.getElementsByTagName('DIV')[0];
		level.parentEntity.childEntities = level;
		var switchDiv = document.createElement('DIV');
		switchDiv.className = 'switch_open';
		switchDiv.controlledElement = level;
		switchDiv.orgChart = orgChartDiv;
		switchDiv.onclick = bab_toggleCollapsed;
		level.parentNode.insertBefore(switchDiv, level);
		level.parentEntity.switchDiv = switchDiv;
	});
	//console && console.timeEnd('switches');


	//console && console.time('members');
	var membersList = $(orgChartDiv).select('.members'); //bab_getElementsByClassName(orgChartDiv, 'members');
	membersList.each(function(members) {
		members.parentNode.members = members;
		members.style.display = 'none';
	});
	//console && console.timeEnd('members');

	//console && console.time('entities');
	var entityList = $(orgChartDiv).select('.entity'); //bab_getElementsByClassName(orgChartDiv, 'entity');
	entityList.each(function(entity) {
		entity.orgChart = orgChartDiv;
		var actionsList = $(entity).select('.actions'); //bab_getElementsByClassName(entity, 'actions');
		if (actionsList.length > 0) {
			bab_createContextMenu(actionsList[0], entity);
		};
	});
	//console && console.timeEnd('entities');



	orgChartDiv.levelSelect.value = '-';
	
	orgChartDiv.thresholdSelect.value = orgchart.thresholdLevel;

	window.setTimeout('bab_setOpenNodes(window.bab_orgChart.bab_openNodes);'
						+ 'bab_setOpenMembers(window.bab_orgChart.bab_openMembers);'
						+ 'window.bab_orgChart.loadStateFromCookie()',
						10);

	bab_resizeOrgChartContainer();
	
	window.onresize = bab_resizeOrgChartContainer;

	document.getElementById("bab_loading").style.display = 'none';

	// Mouse wheel zooming
	if (orgChartDiv.addEventListener) {
		orgChartDiv.addEventListener('DOMMouseScroll', wheel, false);
	} else {
		window.onmousewheel = document.onmousewheel = wheel;
	}

//	orgChartDiv.alignLevels = bab_alignLevels;
	
//	var toto = createButton('', 'bab_toto');
//	toto.onclick = bab_alignLevels;
//	toto.title = window.bab_Translations['save_default_view'];
//	toolbar.appendChild(toto);
}




function handle(delta)
{
	if (delta < 0)
		window.bab_orgChart.setZoom(window.bab_orgChart.zoomFactor * 1.071773463);
	else
		window.bab_orgChart.setZoom(window.bab_orgChart.zoomFactor / 1.071773463);
}

function wheel(event)
{
	var delta = 0;
	if (!event) event = window.event;
	if (event.wheelDelta) {
		delta = event.wheelDelta/120; 
		if (window.opera) delta = -delta;
	} else if (event.detail) {
		delta = -event.detail/3;
	}
	if (delta)
		handle(delta);
        if (event.preventDefault)
                event.preventDefault();
        event.returnValue = false;
}

function getCursorPosition(e) {
    e = e || window.event;
    var cursor = {x:0, y:0};
    if (e.pageX || e.pageY) {
        cursor.x = e.pageX;
        cursor.y = e.pageY;
    } 
    else {
        var de = document.documentElement;
        var b = document.body;
        cursor.x = e.clientX + 
            (de.scrollLeft || b.scrollLeft) - (de.clientLeft || 0);
        cursor.y = e.clientY + 
            (de.scrollTop || b.scrollTop) - (de.clientTop || 0);
    }
    return cursor;
}



function cleanStringDiacritics(text)
{
	try {
		text = text.replace(/à|â|ä/g, "a");
		text = text.replace(/é|è|ê|ë/g, "e");
		text = text.replace(/ì|î|ï/g, "i");
		text = text.replace(/ò|ô|ö/g, "o");
		text = text.replace(/ù|û|ü/g, "u");
		text = text.replace(/ç/g, "c");
	} catch (e) {
		text = '';
	}

	return text.toUpperCase();
}


function bab_alignLevels()
{
	bab_alignLevel(1);
	bab_alignLevel(2);
	bab_alignLevel(3);
	bab_alignLevel(4);
	bab_alignLevel(5);
}

function bab_alignLevel(level)
{
	var levels = $(window.bab_orgChart).select('.level' + level); //bab_getElementsByClassName(window.bab_orgChart, 'level' + level);
	var entities = [];
	var maxHeight = 0;
	for (var i = 0; i < levels.length; i++) {
		if (hasClass(levels[i]), 'horizontal') {
			var tr = levels[i].getElementsByTagName('TR')[0];
			if (tr) {
				for (var td = tr.firstChild; td; td = td.nextSibling) {
					if (td.tagName == 'TD') {
						var entity = $(td).select('.entity')[0]; //bab_getElementsByClassName(td, 'entity')[0];
						if (entity) {
							entities.push(entity);
							if (entity.clientHeight > maxHeight) {
								maxHeight = entity.clientHeight;
							}
						}
					}
				}
			}
		}
	}
	
	for (var i = 0; i < entities.length; i++) {
		var entity = entities[i];
		entity.style.height = maxHeight + 'px';
	}
}
