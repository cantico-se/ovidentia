

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
}


function bab_showActions()
{
	this.actions.style.display = '';
}

function bab_hideActions()
{
	this.actions.style.display = 'none';
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
	//console && console.timeEnd('bab_setLevel');
}


function bab_zoomIn()
{
	var orgChartDiv = this.controlledElement;
	var tables = orgChartDiv.getElementsByTagName('TABLE');
	orgChartDiv.zoomFactor *= 1.125;
	this.zoomFactor.firstChild.nodeValue = parseInt(orgChartDiv.zoomFactor * 100 + 0.5) + "%";
	tables[0].style.fontSize = orgChartDiv.zoomFactor + 'em';
}

function bab_zoomOut()
{
	var orgChartDiv = this.controlledElement;
	var tables = orgChartDiv.getElementsByTagName('TABLE');
	orgChartDiv.zoomFactor /= 1.125;
	this.zoomFactor.firstChild.nodeValue = parseInt(orgChartDiv.zoomFactor * 100 + 0.5) + "%";
	tables[0].style.fontSize = orgChartDiv.zoomFactor + 'em';
}


function bab_zoomFit()
{
	var orgChartDiv = this.controlledElement;
	var tables = orgChartDiv.getElementsByTagName('TABLE');
	var factor = (orgChartDiv.offsetWidth - 20) / (tables[0].offsetWidth);
	orgChartDiv.zoomFactor *= factor;
	this.zoomFactor.firstChild.nodeValue = parseInt(orgChartDiv.zoomFactor * 100 + 0.5) + "%";
	tables[0].style.fontSize = orgChartDiv.zoomFactor + 'em';
}


function bab_toggleMembers(action) {
	var entity = action.parentNode.parentNode;
	var members = entity.members;
	members.style.display = (members.style.display == 'none' ? '' : 'none');
	return false;
}

function bab_initOrgChart(orgChartDiv)
{
	// Toolbar creation.
	
	
	var orgChartContainer = orgChartDiv.parentNode;
	var divs = orgChartContainer.getElementsByTagName('DIV');
	var nbDivs = divs.length;

	//console && console.time('toolbar');
	for (var i = 0; i < nbDivs; i++) {
		var toolbar = divs[i];
		if (Element.hasClassName(toolbar, 'bab_treeToolbar')) {

			toolbar.controlledElement = orgChartDiv;

			var zoomWidget = document.createElement('SPAN');
			zoomWidget.className = 'bab_toolbarGroup';
			zoomWidget.appendChild(document.createTextNode('Zoom'));

			var zoomFactor = document.createElement('SPAN');
			zoomFactor.appendChild(document.createTextNode('100%'));
		
			var zoomFit = document.createElement('A');
			zoomFit.appendChild(document.createTextNode(' '));
			zoomFit.onclick = bab_zoomFit;
			zoomFit.className = 'bab_zoomFit';
			zoomFit.controlledElement = orgChartDiv;
			zoomFit.zoomFactor = zoomFactor;
			
			var zoomIn = document.createElement('A');
			zoomIn.appendChild(document.createTextNode(' '));
			zoomIn.onclick = bab_zoomIn;
			zoomIn.className = 'bab_zoomIn';
			zoomIn.controlledElement = orgChartDiv;
			zoomIn.zoomFactor = zoomFactor;
					
			var zoomOut = document.createElement('A');
			zoomOut.appendChild(document.createTextNode(' '));
			zoomOut.onclick = bab_zoomOut;
			zoomOut.className = 'bab_zoomOut';
			zoomOut.controlledElement = orgChartDiv;
			zoomOut.zoomFactor = zoomFactor;
		
			zoomWidget.appendChild(zoomFit);
			zoomWidget.appendChild(zoomOut);
			zoomWidget.appendChild(zoomFactor);
			zoomWidget.appendChild(zoomIn);
		
			toolbar.appendChild(zoomWidget);

			var levelWidget = document.createElement('SPAN');
			levelWidget.appendChild(document.createTextNode('Niveaux visibles '));
			levelWidget.className = 'bab_toolbarGroup';

			var levelSelect = document.createElement('SELECT');
			levelSelect.controlledElement = orgChartDiv;
			for (var i = 1; i <= 7; i++) {
				var option = document.createElement('OPTION');
				option.value = i;
				option.appendChild(document.createTextNode(i));
				levelSelect.appendChild(option);
			}
			levelSelect.onchange = bab_setLevel;
			levelWidget.appendChild(levelSelect);
			
			toolbar.appendChild(levelWidget);
			
			orgChartDiv.levelSelect = levelSelect;

			break;
		}
	}
	//console && console.timeEnd('toolbar');


	//console && console.time('switches');
	
	var horizontalList = document.getElementsByClassName('horizontal', orgChartDiv);
	var verticalList = document.getElementsByClassName('vertical', orgChartDiv);
	var levelList = horizontalList.concat(verticalList);

	levelList.each(function(level) {
		level.parentEntity = level.parentNode.getElementsByTagName('DIV')[0];
		level.parentEntity.childEntities = level;
		var switchDiv = document.createElement('DIV');
		switchDiv.className = 'switch_open';
		switchDiv.controlledElement = level;
		switchDiv.onclick = bab_toggleCollapsed;
		level.parentNode.insertBefore(switchDiv, level);		
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
		var actionsList = document.getElementsByClassName('actions', entity);
		if (actionsList.length > 0) {
			actions = actionsList[0];
			entity.actions = actions;
			actions.style.display = 'none';
			actions.controlledElement = entity;
		};
	});
	//console && console.timeEnd('entities');


	orgChartDiv.updateDisplay = bab_updateDisplay;
	orgChartDiv.zoomFactor = 1.0;

	orgChartDiv.levelSelect.value = '4';
	orgChartDiv.levelSelect.onchange();

	// Force redisplay for ie...
//	window.orgchart = orgChartDiv;
//	window.setTimeout('window.orgchart.updateDisplay();', 200);

}

// This function forces the redisplay of the orgchart.
function bab_updateDisplay()
{
	var tables = this.getElementsByTagName('TABLE');
	tables[0].style.fontSize = this.zoomFactor + 'em';
	window.status = "updateDisplay(" + this + ")";
}

//window.onresize = function() {window.status="a"; window.orgchart.zoomFactor = 1.2; window.setTimeout('window.orgchart.updateDisplay();', 1000);};
