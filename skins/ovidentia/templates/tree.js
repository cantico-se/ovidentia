

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


function bab_delaySearch()
{
	if (window.bab_treeSearchStack == undefined)
		window.bab_treeSearchStack = Array();
	if (this.timeoutId) {
		window.clearTimeout(this.timeoutId);
		window.bab_treeSearchStack.pop();
		this.timeoutId = null;
	}
	if (this.value.length >= 3) {
		this.className = 'bab_searchFieldSearching';
		this.timeoutId = window.setTimeout(bab_treeSearch, 200, this);
		window.bab_treeSearchStack.push(this);
	} else {
		this.className = 'bab_searchField';
		this.parentNode.tree.expand();
		this.parentNode.tree.unhighlightAll();
	}
}


function bab_treeSearch()
{
	element = window.bab_treeSearchStack.pop();
	text = element.value;
	tree = element.parentNode.tree;
	nbMatches = tree.searchItem(text);
	if (nbMatches == 0) {
		element.className = 'bab_searchFieldNotFound';
	} else if (nbMatches == 1) {
		element.className = 'bab_searchFieldFound';
	} else {
		element.className = 'bab_searchField';
	}
}

function bab_treeExpand()
{
	tree = this.parentNode.tree;
	tree.expand();
}

function bab_treeCollapse()
{
	tree = this.parentNode.tree;
	tree.collapse();
}

function bab_initTrees()
{
	divs = document.getElementsByTagName('DIV');
	for (i = 0; i < divs.length; i++) {
		div = divs[i];
		if (!div.initialized && hasClass(div, 'bab_tree')) {
			tree = new bab_ul_tree(div.getElementsByTagName('UL')[0]);
			tree.collapse();
			tree.processList(tree.rootList);
//			tree.expand();
			
			var toolbar = document.createElement('DIV');
			toolbar.className = 'bab_treeToolbar BabSiteAdminTitleFontBackground';
			toolbar.tree = tree;
			div.insertBefore(toolbar, div.firstChild);

			var expand = document.createElement('A');
			txt = document.createTextNode('Expand');
			expand.onclick = bab_treeExpand;
			expand.className = "bab_expandAll";
			expand.appendChild(txt);
			toolbar.appendChild(expand);
			
			var collapse = document.createElement('A');
			txt = document.createTextNode('Collapse');
			collapse.onclick = bab_treeCollapse;
			collapse.className = "bab_collapseAll";
			collapse.appendChild(txt);
			toolbar.appendChild(collapse);
			
			var search = document.createElement('INPUT');
			search.type = 'text';
			search.className = "bab_searchField";
			search.onkeyup = bab_delaySearch;
			toolbar.appendChild(search);

			div.initialized = true;
		}
	}
}



function bab_ul_tree(rootList)
{
	this.rootList = rootList;
	
	this.NODE_CLOSED = 'bab_ul_tree_closed';
	this.NODE_OPEN = 'bab_ul_tree_open';
	this.NODE_LEAF = 'bab_ul_tree_leaf';

	this.nodeLinkClass = 'bullet';
	this.nodeLineClass = 'line';
	this.nodeLineHoverClass = 'line hover';
}


function bab_onItemMouseOver()
{
	this.className = 'line hover';
}

function bab_onItemMouseOut()
{
	this.className = 'line';
}

function bab_onNodeClick()
{
	var ul = this.parentNode.parentNode;
	ul.className = (ul.className == 'bab_ul_tree_open') ? 'bab_ul_tree_closed' : 'bab_ul_tree_open';
	return false;
}


bab_ul_tree.prototype.processList = function(ul)
{
	var listItems = ul.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length; i++) {
		var listItem = listItems[i];
		if (listItem.getElementsByTagName('UL').length > 0) {
			listItem.getElementsByTagName('IMG')[0].onclick = bab_onNodeClick;
		}
		var div = listItem.getElementsByTagName('div')[0];
		div.className = this.nodeLineClass;
		div.onmouseover = bab_onItemMouseOver;
		div.onmouseout = bab_onItemMouseOut;
	}
}


bab_ul_tree.prototype.expandCollapseList = function(ul, className, itemId)
{
	var listItems = ul.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length; i++) {
		var listItem = listItems[i];
		if (itemId != null && listItem.id == itemId)
			return true;
		var uls = listItem.getElementsByTagName('UL');
		if (uls.length > 0) {
			subLists = true;
			subUl = uls[0];
			var ret = this.expandCollapseList(subUl, className, itemId);
			if (itemId != null && ret) {
				listItem.className = className;
				return true;
			}
		}
		if (subLists && itemId == null)
			listItem.className = className;
	}
}


bab_ul_tree.prototype.expandCollapseAll = function(ul, className)
{
	var listItems = ul.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length; i++) {
		var listItem = listItems[i];
		if (listItem.getElementsByTagName('UL').length > 0) {
			listItem.className = className;
		}
	}
}


bab_ul_tree.prototype.collapse = function()
{
	this.expandCollapseAll(this.rootList, this.NODE_CLOSED);
}



bab_ul_tree.prototype.expand = function()
{
	this.expandCollapseAll(this.rootList, this.NODE_OPEN);
}


bab_ul_tree.prototype.initSearch = function()
{
	if (this.initDone)
		return;
	var listItems = this.rootList.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length ; i++) {
//		var span = document.getElementById('content' + listItems[i].id);
		var span = listItems[i].getElementsByTagName('DIV')[0].getElementsByTagName('SPAN')[0];
		var text = span.firstChild.nodeValue;
		text = cleanStringDiacritics(text);
		listItems[i].setAttribute('content', text);
	}
	this.initDone = true;
}


bab_ul_tree.prototype.unhighlightAll = function()
{
	var listItems = this.rootList.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length ; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0];
		div.style.backgroundColor = '';
	}
}


bab_ul_tree.prototype.searchItem = function(targetString)
{
	this.initSearch();
	this.collapse();
	targetString = cleanStringDiacritics(targetString);
	window.status = 'Searching...';
	var nbMatches = 0;
	var highlightedDivs = Array();
	var listItems = this.rootList.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length ; i++) {
		var content = listItems[i].getAttribute('content');
		var div = listItems[i].getElementsByTagName('DIV')[0];
		if (content && content.indexOf(targetString) > -1) {
			this.expandCollapseList(this.rootList, this.NODE_OPEN, listItems[i].id);
			highlightedDivs.push(div);
			nbMatches++;
		} else {
			div.style.backgroundColor = '';
		}
	}
	while (highlightedDivs.length > 0) {
		highlightedDivs.pop().style.backgroundColor = '#EEEEEE';
	}
	window.status = '';
	return nbMatches;
}

function cleanStringDiacritics(text)
{
/*
	try {
		text = text.replace(/אגה/g, "a");
		text = text.replace(/יטךכ/g, "e");
		text = text.replace(/למן/g, "i");
		text = text.replace(/עפצ/g, "o");
		text = text.replace(/שûü/g, "u");
		text = text.replace(/ח/g, "c");
	} catch (e) {
		text = '';
	}
*/
	return text.toUpperCase();
}

