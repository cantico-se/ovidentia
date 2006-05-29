

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


function bab_SearchContext(tree, inputField)
{
	this.tree = tree;
	this.inputField = inputField;
	this.listItems = tree.rootList.getElementsByTagName('LI');
	this.currentIndex = 0;
	this.timeoutId = null;
	this.nbItemsPerLoop = 30;
	this.nbMatches = 0;
	this.searching = false;
	this.targetString = '';
}


function bab_search()
{
	var context = window.bab_searchContext;
	if (!context.searching) {
		return;	
	}
	if (context.currentIndex == 0) {
		context.tree.collapse();
		context.tree.initSearch();
	}
	context.targetString = cleanStringDiacritics(context.inputField.value);
	var targetString = context.targetString;
	
	window.status = 'Searching [' + targetString + '] ' + context.nbMatches +  ' (' + parseInt((100.0 * context.currentIndex) / context.listItems.length) + '%)';
	context.inputField.style.backgroundPosition = '' + (100 * context.currentIndex) / context.listItems.length + '% 0'
	var highlightedDivs = Array(); 
	var nbItems = context.nbItemsPerLoop;
	while (nbItems-- > 0 && context.currentIndex < context.listItems.length) {
		var listItem = context.listItems[context.currentIndex];
		var content = listItem.getAttribute('content');
		var div = listItem.getElementsByTagName('DIV')[0];
		if (content && content.indexOf(targetString) > -1) {
			context.tree.expandCollapseList(context.tree.rootList, context.tree.NODE_OPEN, listItem.id);
			highlightedDivs.push(div);
			context.nbMatches++;
		} else {
			div.style.backgroundColor = '';
		}
		context.currentIndex++;
	}
	while (highlightedDivs.length > 0) {
		highlightedDivs.pop().style.backgroundColor = '#EEEEEE';
	}

	if (context.currentIndex < context.listItems.length) {
		context.timeoutId = window.setTimeout(window.bab_search, 20);
	} else {
		if (context.nbMatches > 1) {
			context.inputField.className = 'bab_searchField';
		} else if (context.nbMatches == 0) {
			context.inputField.className = 'bab_searchFieldNotFound';
		} else {
			context.inputField.className = 'bab_searchFieldFound';			
		}
		window.status = 'Found (' + context.nbMatches + ')';
		context.inputField.style.backgroundPosition = '1px 50%'
		window.bab_searchContext.searching = false;
	}
}





function bab_delaySearch()
{
	var context = window.bab_searchContext;

	if (context.targetString == cleanStringDiacritics(this.value)) {
		return;
	}
	if (this.value.length >= 3) {
		if (context.searching == false) {
			context.searching = true;
			context.timeoutId = window.setTimeout(bab_search, 200);
		}
		this.className = 'bab_searchFieldSearching';
		context.currentIndex = 0;
		context.nbMatches = 0;
	} else {
		if (window.bab_searchContext) {
			window.clearTimeout(window.bab_searchContext.timeoutId);
			context.inputField.style.backgroundPosition = '1px 50%'
			window.bab_searchContext.searching = false;
		}
		this.className = 'bab_searchField';
		this.parentNode.tree.expand();
		this.parentNode.tree.unhighlightAll();
		window.status = '';
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
			expand.className = 'bab_expandAll';
			expand.appendChild(txt);
			toolbar.appendChild(expand);
			
			var collapse = document.createElement('A');
			txt = document.createTextNode('Collapse');
			collapse.onclick = bab_treeCollapse;
			collapse.className = 'bab_collapseAll';
			collapse.appendChild(txt);
			toolbar.appendChild(collapse);
			
			var search = document.createElement('INPUT');
			search.type = 'text';
			search.className = 'bab_searchField';
			search.onkeyup = bab_delaySearch;
			toolbar.appendChild(search);
			window.bab_searchContext = new bab_SearchContext(tree, search);

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
	if (this.collapsing)
		return;
	this.collapsing = true;
	this.expandCollapseAll(this.rootList, this.NODE_CLOSED);
	this.collapsing = false;
}



bab_ul_tree.prototype.expand = function()
{
	if (this.expanding)
		return;
	this.expanding = true;
	this.expandCollapseAll(this.rootList, this.NODE_OPEN);
	this.expanding = false;
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
	if (this.unhighlighting)
		return;
	this.unhighlighting = true;
	var listItems = this.rootList.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length ; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0];
		div.style.backgroundColor = '';
	}
	this.unhighlighting = false;
}


function cleanStringDiacritics(text)
{
/*
	try {
		text = text.replace(/àâä/g, "a");
		text = text.replace(/éèêë/g, "e");
		text = text.replace(/ìîï/g, "i");
		text = text.replace(/òôö/g, "o");
		text = text.replace(/ùûü/g, "u");
		text = text.replace(/ç/g, "c");
	} catch (e) {
		text = '';
	}
*/
	return text.toUpperCase();
}

