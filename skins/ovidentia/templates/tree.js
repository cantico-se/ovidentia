

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
	this.nbItemsPerLoop = 40;
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
	if (context.targetString != cleanStringDiacritics(context.inputField.value)) {
		context.currentIndex = 0;
		context.nbMatches = 0;
		context.tree.collapse();
		context.tree.initSearch();
		context.targetString = cleanStringDiacritics(context.inputField.value);
	}
	var targetString = context.targetString;
	
	var nbItems = context.nbItemsPerLoop;

	var currentIndex = context.currentIndex;
	while (nbItems-- > 0 && currentIndex < context.listItems.length) {
		var listItem = context.listItems[currentIndex];
		var content = listItem.getAttribute('content');
		var div = listItem.getElementsByTagName('DIV')[0];
		if (content && content.indexOf(targetString) > -1) {
			context.tree.expandCollapseListItem(listItem, context.tree.NODE_OPEN);
			div.style.backgroundColor = '#EEEEEE';
			context.nbMatches++;
		} else {
			div.style.backgroundColor = '';
		}
		currentIndex++;
	}
	
	if (context.targetString == cleanStringDiacritics(context.inputField.value)) {
		context.currentIndex = currentIndex;
	} else {
		context.timeoutId = window.setTimeout(window.bab_search, 10);
		return;
	}

	window.status = '' + context.currentIndex + ' / ' + context.listItems.length
	context.inputField.style.backgroundPosition = '' + (100 * currentIndex) / context.listItems.length + '% 0'

	if (currentIndex < context.listItems.length) {
		context.timeoutId = window.setTimeout(window.bab_search, 10);
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

	if (this.value.length >= 2) {
		if (context.searching == false) {
			context.searching = true;
			context.timeoutId = window.setTimeout(bab_search, 300);
		}
		this.className = 'bab_searchFieldSearching';
		context.targetString = '';
	} else {
		window.clearTimeout(context.timeoutId);
		context.inputField.style.backgroundPosition = '1px 50%'
		context.searching = false;
		if (this.className != 'bab_searchField') {
			this.className = 'bab_searchField';
			this.parentNode.tree.expand();
			this.parentNode.tree.unhighlightAll();
		}
	}
}


function bab_treeExpand()
{
	this.parentNode.tree.expand();
}

function bab_treeCollapse()
{
	this.parentNode.tree.collapse();
}

function bab_initTrees()
{
	divs = document.getElementsByTagName('DIV');
	for (i = 0; i < divs.length; i++) {
		div = divs[i];
		if (!div.initialized && hasClass(div, 'bab_tree')) {
			tree = new bab_ul_tree(div.getElementsByTagName('UL')[0]);
			tree.processList(tree.rootList);
			tree.initSearch();
			
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
	window.console && console.time('processList');

	var uls = ul.getElementsByTagName('UL');
	for (var i = 0; i < uls.length; i++) {
		var li = uls[i].parentNode;
		li.className = this.NODE_CLOSED;
		var div = li.getElementsByTagName('DIV')[0];
		var img = div.getElementsByTagName('IMG')[0];
		img.onclick = bab_onNodeClick;
	}

	window.console && console.timeEnd('processList');
}


bab_ul_tree.prototype.expandCollapseListItem = function(listItem, className)
{
	listItem = listItem.parentNode.parentNode;
	while (listItem.tagName == 'LI') {
		if (listItem.className != className) {
			listItem.className = className;
		}
		listItem = listItem.parentNode.parentNode;
	}
}


bab_ul_tree.prototype.expandCollapseAll = function(ul, className)
{
	var uls = ul.getElementsByTagName('UL');
	for (var i = 0; i < uls.length; i++) {
		if (uls[i].parentNode.className != className) {
			uls[i].parentNode.className = className;
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
	window.console && console.time('processList');
	if (this.initDone)
		return;
	var listItems = this.rootList.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length ; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0]
		var span = div.getElementsByTagName('SPAN')[0];
		var text = span.firstChild.nodeValue;
		text = cleanStringDiacritics(text);
		listItems[i].setAttribute('content', text);
		
		div.onmouseover = bab_onItemMouseOver;
		div.onmouseout = bab_onItemMouseOut;		
	}
	this.initDone = true;
	window.console && console.timeEnd('processList');
}


bab_ul_tree.prototype.unhighlightAll = function()
{
	var listItems = this.rootList.getElementsByTagName('LI');
	for (var i = 0; i < listItems.length ; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0];
		if (div.style.backgroundColor != '') {
			div.style.backgroundColor = '';
		}
	}
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

