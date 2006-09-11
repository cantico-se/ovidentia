

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


function hasClass(element, className) {
	if (element.className == undefined)
		return false;
	classes = element.className.split(' ');
	return classes.contains(className);
}



function toggleCollapsed()
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


function showActions()
{
	this.actions.style.display = '';
}

function hideActions()
{
	this.actions.style.display = 'none';
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

function bab_initOrgChart(orgChartDiv)
{
	// Toolbar creation.
	//------------------
	var toolbar = document.createElement('DIV');
	toolbar.className = 'bab_treeToolbar BabSiteAdminTitleFontBackground';
	toolbar.controlledElement = orgChartDiv;
	orgChartDiv.insertBefore(toolbar, orgChartDiv.firstChild);
	
	orgChartDiv.zoomFactor = 1.0;

	var zoomWidget = document.createElement('SPAN');
	var zoomFactor = document.createElement('SPAN');
	txt = document.createTextNode('100%');
	zoomFactor.appendChild(txt);

	var zoomIn = document.createElement('A');
	zoomIn.onclick = bab_zoomIn;
	zoomIn.className = 'bab_zoomIn';
	zoomIn.controlledElement = orgChartDiv;
	zoomIn.zoomFactor = zoomFactor;

	var zoomOut = document.createElement('A');
	zoomOut.onclick = bab_zoomOut;
	zoomOut.className = 'bab_zoomOut';
	zoomOut.controlledElement = orgChartDiv;
	zoomOut.zoomFactor = zoomFactor;

	zoomWidget.appendChild(zoomOut);
	zoomWidget.appendChild(zoomFactor);
	zoomWidget.appendChild(zoomIn);

	toolbar.appendChild(zoomWidget);

/*	
	var search = document.createElement('INPUT');
	search.type = 'text';
	search.className = 'bab_searchField';
	search.onkeyup = bab_delaySearch;
	toolbar.appendChild(search);
	window.bab_searchContext = new bab_SearchContext(tree, search);
*/

	var tables = orgChartDiv.getElementsByTagName('TABLE');
	var nbTables = tables.length;
	for (var i = 1; i < nbTables; i++) {
		var table = tables[i];
		if (!hasClass(table, 'horizontal'))
			continue;
		table.parentEntity = table.parentNode.getElementsByTagName('DIV')[0];
		table.parentEntity.childEntities = table;
		var switchDiv = document.createElement('DIV');
		switchDiv.className = 'switch_open';
		switchDiv.controlledElement = table;
		table.parentNode.insertBefore(switchDiv, table);
	}

	var uls = orgChartDiv.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		var ul = uls[i];
		ul.parentEntity = ul.parentNode.getElementsByTagName('DIV')[0];
		ul.parentEntity.childEntities = ul;
		var switchDiv = document.createElement('DIV');
		switchDiv.className = 'switch_open';
		switchDiv.controlledElement = ul;
		ul.parentNode.insertBefore(switchDiv, ul);
	}

	var divs = orgChartDiv.getElementsByTagName('DIV');
	var nbDivs = divs.length;
	for (var i = 0; i < nbDivs; i++) {
		var div = divs[i];
		if (hasClass(div, 'switch_open')) {
			div.onclick = toggleCollapsed;
		}

		if (hasClass(div, 'entity')) {
			div.onmouseover = showActions;
			div.onmouseout = hideActions;
			var actions = div.getElementsByTagName('DIV');
			var nbActions = actions.length;
			for (var j = 0; j < nbActions; j++) {
				var action = actions[j];
				if (hasClass(action, 'actions')) {
					div.actions = action;
					action.style.display = 'none';
					action.controlledElement = div;
					break;
				}
			}
		}
	}

}


