

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
	var display = this.controlledElement.style.display;
	if (display == 'none') {
		this.controlledElement.style.display = '';
		this.className = 'switch_open';
	} else {
		this.controlledElement.style.display = 'none';
		this.className = 'switch_closed';
	}
	this.orgChart.saveStateInCookie();
	bab_refresh(this.orgChart);
}


function bab_showActions()
{
	this.actions.style.display = '';
}

function bab_hideActions()
{
	this.actions.style.display = 'none';
}



function bab_getOpenNodes()
{
	var openSwitchList = document.getElementsByClassName('switch_open', this);
	
	var entityIds = new Array();
	openSwitchList.each(function(openSwitch) {
		var entity = openSwitch.controlledElement.parentEntity;
		entityIds.push(entity.id);
	});
	return entityIds;
}

function bab_getOpenMembers()
{
	var membersList = document.getElementsByClassName('members', this);
	
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
	var switchDivs = document.getElementsByClassName('switch_open');
	switchDivs.each(function(switchDiv) {
		if (!entityIds.contains(switchDiv.controlledElement.parentEntity.id)) {
			switchDiv.className = 'switch_closed';
			switchDiv.controlledElement.style.display = 'none';
		}
	});

	switchDivs = document.getElementsByClassName('switch_closed');
	switchDivs.each(function(switchDiv) {
		if (entityIds.contains(switchDiv.controlledElement.parentEntity.id)) {
			switchDiv.className = 'switch_open';
			switchDiv.controlledElement.style.display = '';
		}
	});

	/*
	for (var i = 0; i < entityIds.length; i++) {
		var entity = document.getElementById(entityIds[i]);
		if (entity) {
			var switchDiv = entity.switchDiv;
			switchDiv.className = 'switch_open';
			switchDiv.controlledElement.style.display = '';
		}
	}
	*/
}

function bab_setOpenMembers(memberIds)
{
	var membersList = document.getElementsByClassName('members');
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
	for (var i = orgChartDiv.currentLevel + 1; i <= 7; i++) {
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



function bab_setZoom(zoomFactor)
{
	this.zoomFactor = zoomFactor;
	this.zoomWidget.zoomText.firstChild.nodeValue = parseInt(this.zoomFactor * 100 + 0.5) + "%";
	bab_refresh(this);
}

function bab_zoomIn()
{

	var orgChartDiv = this.getControlledElement();
	orgChartDiv.setZoom(orgChartDiv.zoomFactor * 1.125);
}

function bab_zoomOut()
{
	var orgChartDiv = this.getControlledElement();
	orgChartDiv.setZoom(orgChartDiv.zoomFactor / 1.125);
}


function bab_zoomFit()
{
	var orgChartDiv = this.getControlledElement();
	var tables = orgChartDiv.getElementsByTagName('TABLE');
	var factor = (orgChartDiv.offsetWidth - 20) / (tables[0].offsetWidth);
	orgChartDiv.style.display = 'none';
	orgChartDiv.zoomFactor *= factor;
	this.zoomText.firstChild.nodeValue = parseInt(orgChartDiv.zoomFactor * 100 + 0.5) + "%";
	tables[0].style.fontSize = orgChartDiv.zoomFactor + 'em';
	orgChartDiv.style.display = '';
}


function bab_toggleMembers(action) {
	var entity = action.parentNode.parentNode;
	var members = entity.members;
	if( typeof(members) != 'undefined' ) {
		members.style.display = (members.style.display == 'none' ? '' : 'none');
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
	var toolbar = document.getElementsByClassName('bab_treeToolbar', orgChartContainer)[0];
	
	var toolbarDimensions = Element.getDimensions(toolbar);
	
	var ieBody = (document.documentElement ? document.documentElement : document.body)
	
		
	var bodyDimensions = Element.getDimensions(ieBody);
	
	orgChartContainer.style.width = (bodyDimensions.width - 4) + "px";
	orgChartContainer.style.height = (bodyDimensions.height - 4) + "px";
	orgChartDiv.style.top = toolbarDimensions.height + "px";
	orgChartDiv.style.left = "0px";
	orgChartDiv.style.width = (bodyDimensions.width - 4) + "px";
	orgChartDiv.style.height = (bodyDimensions.height - toolbarDimensions.height - 4) + "px";
	
	bab_refresh(orgChartDiv);
}

function bab_saveState()
{
	var orgChartDiv = this.getControlledElement();
	window.location = '?tg=frchart&idx=save_state&disp=disp3&ocid=' + orgChartDiv.orgChartId + '&oeid=' + orgChartDiv.entityId + '&iduser=' + orgChartDiv.userId + '&open_nodes=' + orgChartDiv.getOpenNodes().join(',') + '&open_members=' + orgChartDiv.getOpenMembers().join(',') + '&zoom_factor=' + orgChartDiv.zoomFactor;
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
	window.onunload = function() {	};
	window.location = '?tg=frchart&idx=list&disp=disp3&ocid=' + orgChartDiv.orgChartId + '&oeid=' + orgChartDiv.entityId + '&iduser=' + orgChartDiv.userId;
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
	return link;
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

	var toolbar = document.getElementsByClassName('bab_treeToolbar', orgChartContainer)[0];
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
		zoomIn.onclick = bab_zoomIn;
		zoomIn.zoomText = zoomText;
		zoomIn.title = window.bab_Translations['zoom_in'];
				
		var zoomOut = createButton('', 'bab_zoomOut');
		zoomOut.onclick = bab_zoomOut;
		zoomOut.zoomText = zoomText;
		zoomOut.title = window.bab_Translations['zoom_out'];
	
		zoomWidget.appendChild(zoomFit);
		zoomWidget.appendChild(zoomOut);
		zoomWidget.appendChild(zoomText);
		zoomWidget.appendChild(zoomIn);
	
		toolbar.appendChild(zoomWidget);
		
//		zoomWidget.setZoom(orgChartDiv.zoomFactor);
		orgChartDiv.zoomWidget = zoomWidget;
		orgChartDiv.setZoom = bab_setZoom;
		orgChartDiv.setZoom(orgChartDiv.zoomFactor);

		var levelWidget = document.createElement('SPAN');
		levelWidget.appendChild(document.createTextNode(window.bab_Translations['visible_levels'] + ' '));
		levelWidget.className = 'bab_toolbarGroup';
		levelWidget.title = window.bab_Translations['visible_levels_tip'];

		var levelSelect = document.createElement('SELECT');
		levelSelect.controlledElement = orgChartDiv;

		var option = document.createElement('OPTION');
		option.appendChild(document.createTextNode('-'));
		levelSelect.appendChild(option);

		for (var i = 1; i <= 7; i++) {
			option = document.createElement('OPTION');
			option.value = i;
			option.appendChild(document.createTextNode(i));
			levelSelect.appendChild(option);
		}
		levelSelect.onchange = bab_setLevel;
		levelWidget.appendChild(levelSelect);
		
		toolbar.appendChild(levelWidget);
		
		orgChartDiv.levelSelect = levelSelect;
		
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
	}

	//console && console.timeEnd('toolbar');


	//console && console.time('switches');
	
	var horizontalList = document.getElementsByClassName('horizontal', orgChartDiv);
	var verticalList = document.getElementsByClassName('vertical', orgChartDiv);
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
	var membersList = document.getElementsByClassName('members', orgChartDiv);
	membersList.each(function(members) {
		members.parentNode.members = members;
		members.style.display = 'none';
	});
	//console && console.timeEnd('members');

	//console && console.time('entities');
	var entityList = document.getElementsByClassName('entity', orgChartDiv);
	entityList.each(function(entity) {
		entity.onmouseover = bab_showActions;
		entity.onmouseout = bab_hideActions;
		entity.orgChart = orgChartDiv;
		var actionsList = document.getElementsByClassName('actions', entity);
		if (actionsList.length > 0) {
			actions = actionsList[0];
			entity.actions = actions;
			actions.style.display = 'none';
			actions.controlledElement = entity;
		};
	});
	//console && console.timeEnd('entities');



	orgChartDiv.levelSelect.value = '-';

	window.setTimeout('bab_setOpenNodes(window.bab_orgChart.bab_openNodes);'
						+ 'bab_setOpenMembers(window.bab_orgChart.bab_openMembers);'
						+ 'window.bab_orgChart.loadStateFromCookie()',
						10);

	bab_resizeOrgChartContainer();
	
	window.onresize = bab_resizeOrgChartContainer;

	document.getElementById("bab_loading").style.display = 'none';
}
