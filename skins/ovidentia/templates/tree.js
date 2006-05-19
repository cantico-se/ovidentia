

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


/*
function bab_includeOnce(scriptSrc)
{
	var scripts = document.getElementsByTagName('scripts');
	for (var i = 0; i < scripts.length; i++) {
		var script = scripts[i];
		if (script.src && script.src == scriptSrc) {
			return false;
		}
	}
	var head = document.getElementsByTagName('head');
	script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = scriptSrc;
	head.appendChild(script);
	return true;
}
*/


function bab_delaySearch()
{
	if (!window.bab_treeSearchStack)
		window.bab_treeSearchStack = Array();
	if (this.timeoutId) {
		window.clearTimeout(this.timeoutId);
		window.bab_treeSearchStack.pop();
		this.timeoutId = null;
	}
	if (this.value.length >= 2) {
		this.timeoutId = window.setTimeout(bab_treeSearch, 200, this);
		window.bab_treeSearchStack.push(this);
	} else {
		this.className = 'bab_searchField';
		this.parentNode.tree.searchItem('');
	}
}


function bab_treeSearch()
{
	element = window.bab_treeSearchStack.pop();
	text = element.value;
	tree = element.parentNode.tree;
	if (text.length >= 2)
		nbMatches = tree.searchItem(text);
	else
		nbMatches = tree.searchItem('');
	if (nbMatches == 0 && text.length >= 2) {
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
	divs = document.getElementsByTagName('div');
	for (i = 0; i < divs.length; i++) {
		div = divs[i];
		if (!div.initialized && hasClass(div, 'bab_tree')) {
			tree = new bab_ul_tree(div);
			tree.processList();
			tree.expand();
			
			toolbar = document.createElement('div');
			toolbar.className = 'bab_treeToolbar BabSiteAdminTitleFontBackground';
			toolbar.tree = tree;
			div.insertBefore(toolbar, div.firstChild);

			expand = document.createElement('a');
			txt = document.createTextNode('Expand');
			expand.onclick = bab_treeExpand;
			expand.className = "bab_expandAll";
			expand.appendChild(txt);
			toolbar.appendChild(expand);
			
			collapse = document.createElement('a');
			txt = document.createTextNode('Collapse');
			collapse.onclick = bab_treeCollapse;
			collapse.className = "bab_collapseAll";
			collapse.appendChild(txt);
			toolbar.appendChild(collapse);
			
			search = document.createElement('input');
			search.type = 'text';
			search.className = "bab_searchField";
			search.onkeyup = bab_delaySearch;
			toolbar.appendChild(search);

			div.initialized = true;
		}
	}
}



function bab_ul_tree(id)
{
	if (typeof id == 'object')
		this.treeId = id;
	else
		this.treeId = document.getElementById(id);
	if ('UL' != this.treeId.tagName)
		this.treeId = this.treeId.getElementsByTagName('ul')[0];

	this.nodeClosedClass = 'bab_ul_tree_closed';
	this.nodeOpenClass = 'bab_ul_tree_open';
	this.nodeBulletClass = 'bab_ul_tree_leaf';
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


bab_ul_tree.prototype.getOpenItems = function()
{
	var ul = this.treeId;
	var ids = Array();
	listItems = ul.getElementsByTagName('li');
	for (var i = 0; i < listItems.length; i++) {
		var item = listItems[i];
		if (item.className == this.nodeOpenClass) {
			ids.push(item.id);
		}
	}
	return ids;
}

/*
bab_ul_tree.prototype.processList = function(ul)
{
	if (null == ul)
		ul = this.treeId;

	listItems = ul.getElementsByTagName('li');
	for (var i = 0; i < listItems.length; i++) {
		var item = listItems[i];
		var hasSubItems = (item.getElementsByTagName('ul').length > 0);

		var s = document.createElement('span');
		var t = '\u00A0'; // &nbsp;
		s.className = this.nodeLinkClass;
		if (hasSubItems) {
			item.className = this.nodeClosedClass;
			s.onclick = bab_onNodeClick;
		} else {
			item.className = this.nodeBulletClass;
		}
		s.appendChild(document.createTextNode(t));
		item = item.getElementsByTagName('div')[0];
		item.className = this.nodeLineClass;
		item.onmouseover = bab_onItemMouseOver;
		item.onmouseout = bab_onItemMouseOut;
		item.insertBefore(s, item.firstChild);
	}
}
*/

/*
bab_ul_tree.prototype.processList = function(ul)
{
	if (null == ul)
		ul = this.treeId;
		
	if (!ul.childNodes || ul.childNodes.length==0)
		return;
	// Iterate LIs
	for (var itemi = 0; itemi < ul.childNodes.length; itemi++) {
		var item = ul.childNodes[itemi];
		if ("LI" == item.nodeName) {
			var subLists = false;
			for (var sitemi = 0; sitemi < item.childNodes.length; sitemi++) {
				var sitem = item.childNodes[sitemi];
				if (sitem.nodeName == "UL") {
					subLists = true;
					this.processList(sitem);
				}
			}	

			var s = document.createElement("span");
			var t = '\u00A0'; // &nbsp;
			s.className = this.nodeLinkClass;
			if (subLists) {
				if (item.className == null || item.className == "" )
					item.className = this.nodeClosedClass;
				// If it's just text, make the text work as the link also
				if (item.firstChild.nodeName == "#text") {
					t += item.firstChild.nodeValue;
					item.removeChild(item.firstChild);
				}
				s.onclick = function () {
					var ul = this.parentNode.parentNode;
					ul.className = (ul.className == 'bab_ul_tree_open') ? 'bab_ul_tree_closed' : 'bab_ul_tree_open';
					return false;
				}
			} else {
				// No sublists, so it's just a bullet node
				item.className = this.nodeBulletClass;
				s.onclick = function () { return false; }
			}
			s.appendChild(document.createTextNode(t));
			item = item.getElementsByTagName('div')[0];
			item.className = this.nodeLineClass;
			item.onmouseover = function() {
				this.className = 'line hover';
			}
			item.onmouseout = function() {
				this.className = 'line';
			}
			item.insertBefore(s, item.firstChild);
		}
	}
}
*/


bab_ul_tree.prototype.processList = function(ul)
{
	if (null == ul)
		ul = this.treeId;
		
	if (!ul.childNodes || ul.childNodes.length == 0)
		return;
	for (var itemi = 0; itemi < ul.childNodes.length; itemi++) {
		var item = ul.childNodes[itemi];
		if ("LI" == item.nodeName) {
			var subLists = false;
			for (var sitemi = 0; sitemi < item.childNodes.length; sitemi++) {
				var sitem = item.childNodes[sitemi];
				if (sitem.nodeName == "UL") {
					subLists = true;
					this.processList(sitem);
				}
			}	
			var s = document.createElement("span");
			s.className = this.nodeLinkClass;
			if (subLists) {
				item.className = this.nodeClosedClass;
				s.onclick = bab_onNodeClick;
			} else {
				item.className = this.nodeBulletClass;
			}
			s.appendChild(document.createTextNode('\u00A0'));
			item = item.getElementsByTagName('div')[0];
			item.className = this.nodeLineClass;
			item.onmouseover = bab_onItemMouseOver;
			item.onmouseout = bab_onItemMouseOut;
			item.insertBefore(s, item.firstChild);
		}
	}
}





bab_ul_tree.prototype.expandCollapseList = function(ul, cName, itemId)
{
	if (null == ul)
		ul = this.treeId;
	if (!ul.childNodes || ul.childNodes.length == 0)
		return false;
	// Iterate LIs
	for (var itemi = 0; itemi < ul.childNodes.length; itemi++) {
		var item = ul.childNodes[itemi];
		if (itemId != null && item.id == itemId)
			return true;
		if (item.nodeName == "LI") {
			var subLists = false;
			for (var sitemi = 0; sitemi < item.childNodes.length; sitemi++) {
				var sitem = item.childNodes[sitemi];
				if (sitem.nodeName == "UL") {
					subLists = true;
					var ret = this.expandCollapseList(sitem, cName, itemId);
					if (itemId != null && ret) {
						item.className = cName;
						return true;
					}
				}
			}

			if (subLists && itemId == null)
				item.className = cName;
		}
	}
}




bab_ul_tree.prototype.collapse = function()
{
	this.expandCollapseList(this.treeId,this.nodeClosedClass);
}


bab_ul_tree.prototype.expand = function()
{
	this.expandCollapseList(this.treeId,this.nodeOpenClass);
}


bab_ul_tree.prototype.expandToItem = function(itemId, focus)
{
	var ret = this.expandCollapseList(this.treeId,this.nodeOpenClass,itemId);
	if (ret && null != focus) {
		var o = document.getElementById(itemId);
		if (o.scrollIntoView) {
			o.scrollIntoView(false);
		}
	}
}

bab_ul_tree.prototype.expandChecked = function()
{
	this.collapse();
	var input = this.treeId.getElementsByTagName('input');
	for (var i =0; i < input.length ; i++ )
	{
		if ('checkbox' == input[i].type && input[i].checked && !input[i].disabled) {
		li = input[i];
		while (li.parentNode && li.parentNode.nodeName != 'LI') {
			li = li.parentNode;
			}
		this.expandToItem(li.parentNode.id);
		}
	}
}


bab_ul_tree.prototype.initSearch = function()
{
	if (this.initDone)
		return;
	var listItems = this.treeId.getElementsByTagName('li');
	for (var i = 0; i < listItems.length ; i++) {
		var span = document.getElementById('content' + listItems[i].id);
		if (span && span.firstChild) {
			var text = span.firstChild.nodeValue;
			text = cleanStringDiacritics(text);
			listItems[i].setAttribute('content', text);
		}
	}
	this.initDone = true;
}


bab_ul_tree.prototype.highlightItem = function(itemId)
{
	var item = document.getElementById(itemId);
	if (!item)
		return false;
	var div = item.getElementsByTagName('div')[0];
	div.style.backgroundColor = '#EEEEEE';
	this.expandCollapseList(this.treeId, this.nodeOpenClass, itemId);
	return true;
}


bab_ul_tree.prototype.searchItem = function(targetString)
{
	this.initSearch();
	if (targetString == '') {
		this.expand();
		var regExp = null;
	} else {
		this.collapse();
		targetString = cleanStringDiacritics(targetString);
		var regExp = new RegExp(targetString, 'i');
	}
	window.status = 'Searching...';
	var nbMatches = 0;
	var highlightedDivs = Array();
	var notHighlightedDivs = Array();
	var listItems = this.treeId.getElementsByTagName('li');
	for (var i = 0; i < listItems.length ; i++) {
		var content = listItems[i].getAttribute('content');
		var div = listItems[i].getElementsByTagName('div')[0];
		if (regExp && content && content.match(regExp)) {
			this.expandCollapseList(this.treeId, this.nodeOpenClass, listItems[i].id);
			highlightedDivs.push(div);
			nbMatches++;
		} else {
			notHighlightedDivs.push(div);
		}
	}
	while (highlightedDivs.length > 0) {
		highlightedDivs.pop().style.backgroundColor = '#EEEEEE';
	}
	while (notHighlightedDivs.length > 0) {
		notHighlightedDivs.pop().style.backgroundColor = '';
	}

	window.status = '';
	return nbMatches;
}


function cleanStringDiacritics(text)
{
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
	return text;
}

