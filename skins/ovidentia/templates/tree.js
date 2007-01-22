

/**
 * Checks wether the array contains the value 'val'.
 * @param {Object} val
 */
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
	var totalListItems = context.listItems.length;
	while (nbItems-- > 0 && currentIndex < totalListItems) {
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
		context.timeoutId = window.setTimeout(window.bab_search, 0);
		return;
	}

	window.status = '[' + context.nbMatches + '] ' + context.currentIndex + ' / ' + context.listItems.length
	context.inputField.style.backgroundPosition = '' + (100 * currentIndex) / context.listItems.length + '% 0'

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
		context.tree.saveState();
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
		if (context.searching) {
			this.className = 'bab_searchField';
			this.parentNode.tree.unhighlightAll();
		}
		context.searching = false;
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


function bab_showActions()
{
	this.actions.style.display = '';
}

function bab_hideActions()
{
	this.actions.style.display = 'none';
}


function bab_initTrees()
{
	var divs = document.getElementsByTagName('DIV');
	var nbDivs = divs.length
	for (var i = 0; i < nbDivs; i++) {
		var div = divs[i];
		if (!div.initialized && hasClass(div, 'bab_tree')) {
			var tree = new bab_Tree(div);
			tree.loadState();
//			tree.initSearch();		
			window.setTimeout('document.getElementById("' + div.id + '").tree.initSearch();', 100);
			
			var toolbar = document.createElement('DIV');
			toolbar.className = 'bab_treeToolbar BabSiteAdminTitleFontBackground';
			toolbar.tree = tree;
			div.insertBefore(toolbar, div.firstChild);

			var expand = document.createElement('A');
			txt = document.createTextNode(bab_translate('Expand'));
			expand.onclick = bab_treeExpand;
			expand.className = 'bab_expandAll';
			expand.appendChild(txt);
			toolbar.appendChild(expand);
			
			var collapse = document.createElement('A');
			txt = document.createTextNode(bab_translate('Collapse'));
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

/*
			var actionsList = div.getElementsByTagName('SPAN');
			var nbActions = actionsList.length
			for (var i = 0; i < nbActions; i++) {
				var actions = actionsList[i];
				if (hasClass(actions, 'actions')) {
					var node = actions.parentNode.parentNode.parentNode;
					var d = actions.parentNode.parentNode;
					var rightElements = actions.parentNode;
					var menu = document.createElement('SPAN');
					menu.appendChild(document.createTextNode('Menu'));
					rightElements.appendChild(menu);
					menu.actions = actions;
					menu.onclick = bab_showActions;
//					menu.onmouseout = bab_hideActions;
					actions.style.position = 'absolute';
					actions.style.left = '0px';
					actions.style.top = '0px';
					actions.style.display = 'none';
					actions.controlledElement = node;
					node.appendChild(actions);
				}
			}
*/
			div.initialized = true;
		}
	}
}



function bab_Tree(div)
{
	this.rootList = div.getElementsByTagName('UL')[0];
	this.processList();
	this.id = div.id;
	div.tree = this;
}


bab_Tree.prototype.BTN_EXPAND_ALL = 1;
bab_Tree.prototype.BTN_COLLAPSE_ALL = 2;
bab_Tree.prototype.BTN_SEARCH = 3;

bab_Tree.prototype.NODE_CLOSED = 'bab_ul_tree_closed';
bab_Tree.prototype.NODE_OPEN = 'bab_ul_tree_open';
bab_Tree.prototype.NODE_LEAF = 'bab_ul_tree_leaf';
bab_Tree.prototype.nodeLinkClass = 'bullet';
bab_Tree.prototype.nodeLineClass = 'line';
bab_Tree.prototype.nodeLineHoverClass = 'line hover';


function bab_onItemMouseOver()
{
	try {
		this.className = bab_Tree.prototype.nodeLineHoverClass;
	} catch(e) {}
}

function bab_onItemMouseOut()
{
	try {
		this.className = bab_Tree.prototype.nodeLineClass;
	} catch(e) {}
}

function bab_onNodeClick()
{
	var li = this.parentNode.parentNode;
	if (li.className == bab_Tree.prototype.NODE_OPEN) {
		li.className = bab_Tree.prototype.NODE_CLOSED;
	} else {
		li.className = bab_Tree.prototype.NODE_OPEN;
	}
	this.tree.saveState();
	return false;
}

function bab_onElementClick()
{
	var parent = this.parentNode;
	while (parent && !hasClass(parent, 'bab_tree')) {
		parent = parent.parentNode;
	}
	if (parent && typeof parent.onElementClick == 'function') {
		parent.onElementClick(this);
	}

}

bab_Tree.prototype.processList = function()
{
	window.console && console.time('processList');

	var uls = this.rootList.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		var li = uls[i].parentNode;
		li.className = this.NODE_CLOSED;
		var div = li.getElementsByTagName('DIV')[0];
		var img = div.getElementsByTagName('IMG')[0];
		img.tree = this;
		img.onclick = bab_onNodeClick;
	}

	window.console && console.timeEnd('processList');
}


bab_Tree.prototype.expandCollapseListItem = function(listItem, className)
{
	listItem = listItem.parentNode.parentNode;
	while (listItem.tagName == 'LI') {
		if (listItem.className != className) {
			listItem.className = className;
		}
		listItem = listItem.parentNode.parentNode;
	}
}


bab_Tree.prototype.expandCollapseAll = function(ul, className)
{
	var uls = ul.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		if (uls[i].parentNode.className != className) {
			uls[i].parentNode.className = className;
		}
	}
	this.saveState();
}


bab_Tree.prototype.collapse = function()
{
	this.expandCollapseAll(this.rootList, this.NODE_CLOSED);
}



bab_Tree.prototype.expand = function()
{
	this.expandCollapseAll(this.rootList, this.NODE_OPEN);
}


bab_Tree.prototype.initSearch = function()
{
	window.console && console.time('initSearch');
	if (this.initDone)
		return;
	var listItems = this.rootList.getElementsByTagName('LI');
	var nbListItems = listItems.length
	for (var i = 0; i < nbListItems; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0]
		var span = div.getElementsByTagName('SPAN')[0];
		var text = span.firstChild.nodeValue;
		text = cleanStringDiacritics(text);
		listItems[i].setAttribute('content', text);
		
		div.onmouseover = bab_onItemMouseOver;
		div.onmouseout = bab_onItemMouseOut;
		
		if (hasClass(span, 'clickable')) {
			span.onclick = bab_onElementClick;
		}
	}
	this.initDone = true;
	window.console && console.timeEnd('initSearch');
}


bab_Tree.prototype.unhighlightAll = function()
{
	var listItems = this.rootList.getElementsByTagName('LI');
	var nbListItems = listItems.length
	for (var i = 0; i < nbListItems; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0];
		if (div.style.backgroundColor != '') {
			div.style.backgroundColor = '';
		}
	}
}

bab_Tree.prototype.saveState = function()
{
	var expiryDate = new Date;
	expiryDate.setMonth(expiryDate.getMonth() + 6);
//	var cookiePath = document.location.href.replace(new RegExp('^[a-z]+://' + document.location.host), '');
	var cookiePath = '/';
	var listItems = this.rootList.getElementsByTagName('LI');
	var nbListItems = listItems.length
	var nodes = new Array();
	for (var i = 0; i < nbListItems; i++) {
		var listItem = listItems[i];
		if (listItem.className == this.NODE_OPEN) {
			nodes.push(listItem.id);
		}
	}
	
	document.cookie = 'bab_Tree.' + this.id + '=' + escape(nodes.join('/'))
						+ '; expires=' + expiryDate.toGMTString()
						+ '; path=' + cookiePath;
}


bab_Tree.prototype.loadState = function()
{
	var pairs = document.cookie.split('; ');
	for (var i = 0; i < pairs.length; i++) {
		var keyValue = pairs[i].split('=');
		if (keyValue[0] == 'bab_Tree.' + this.id) {
			if (keyValue[1] != '') {
				var nodes = unescape(keyValue[1]).split('/');
				for (var j = 0; j < nodes.length; j++) {
					try {
					document.getElementById(nodes[j]).className = this.NODE_OPEN;
					} catch(e) {
					}
				}
			}
		}
	}
}


function cleanStringDiacritics(text)
{
	try {
		text = text.replace(/�|�|�/g, "a");
		text = text.replace(/�|�|�|�/g, "e");
		text = text.replace(/�|�|�/g, "i");
		text = text.replace(/�|�|�/g, "o");
		text = text.replace(/�|�|�/g, "u");
		text = text.replace(/�/g, "c");
	} catch (e) {
		text = '';
	}

	return text.toUpperCase();
}

