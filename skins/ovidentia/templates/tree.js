

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
		this.parentNode.tree.unhighlight();
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
	divs = document.getElementsByTagName('div');
	for (i = 0; i < divs.length; i++) {
		div = divs[i];
		if (!div.initialized && hasClass(div, 'bab_tree')) {
			tree = new bab_ul_tree(div);
			tree.processList();
			tree.expand();
			
			var toolbar = document.createElement('div');
			toolbar.className = 'bab_treeToolbar BabSiteAdminTitleFontBackground';
			toolbar.tree = tree;
			div.insertBefore(toolbar, div.firstChild);

			var expand = document.createElement('a');
			txt = document.createTextNode('Expand');
			expand.onclick = bab_treeExpand;
			expand.className = "bab_expandAll";
			expand.appendChild(txt);
			toolbar.appendChild(expand);
			
			var collapse = document.createElement('a');
			txt = document.createTextNode('Collapse');
			collapse.onclick = bab_treeCollapse;
			collapse.className = "bab_collapseAll";
			collapse.appendChild(txt);
			toolbar.appendChild(collapse);
			
			var search = document.createElement('input');
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

	this.highlightedItems = Array();

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


bab_ul_tree.prototype.appendElement = function(element, parentId)
{
	
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
		var img = document.createElement('img');
		img.src = 'ovidentia/skins/ovidentia/images/Puces/Space1PxTrans.gif';
		img.width = '16';
		img.height = '16';
		s.appendChild(img);
		item = item.getElementsByTagName('div')[0];
		item.className = this.nodeLineClass;
		
		item.onmouseover = bab_onItemMouseOver;
		item.onmouseout = bab_onItemMouseOut;
		item.insertBefore(s, item.firstChild);
	}
}


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
			var img = document.createElement('img');
			img.src = 'ovidentia/skins/ovidentia/images/Puces/Space1PxTrans.gif';
			img.width = '16';
			img.height = '16';
			s.appendChild(img);
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

/*
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
			var img = document.createElement('img');
			img.src = 'ovidentia/skins/ovidentia/images/Puces/Space1PxTrans.gif';
			img.width = '16';
			img.height = '16';
			s.appendChild(img);
			item = item.getElementsByTagName('div')[0];
			item.className = this.nodeLineClass;
			item.onmouseover = bab_onItemMouseOver;
			item.onmouseout = bab_onItemMouseOut;
			item.insertBefore(s, item.firstChild);
		}
	}
}
*/



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
			var uls = item.getElementsByTagName('UL');
			if (uls.length > 0) {
				subLists = true;
				sitem = uls[0];
				var ret = this.expandCollapseList(sitem, cName, itemId);
				if (itemId != null && ret) {
					item.className = cName;
					return true;
				}
			}

			if (subLists && itemId == null)
				item.className = cName;
		}
	}
}

/*
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
*/



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


bab_ul_tree.prototype.unhighlight = function()
{
	while (this.highlightedItems.length > 0) {
		var div = this.highlightedItems.pop();
		div.style.backgroundColor = '';
	}
}


bab_ul_tree.prototype.searchItem = function(targetString)
{
	window.status = 'initSearch';
	this.initSearch();
	this.collapse();
	targetString = cleanStringDiacritics(targetString);
	window.status = 'Searching...';
	var nbMatches = 0;
	var highlightedDivs = Array();
	var listItems = this.treeId.getElementsByTagName('li');
	window.status = 0;
	for (var i = 0; i < listItems.length ; i++) {
		var content = listItems[i].getAttribute('content');
		var div = listItems[i].getElementsByTagName('div')[0];
		if (content && content.indexOf(targetString) > -1) {
			this.expandCollapseList(this.treeId, this.nodeOpenClass, listItems[i].id);
			this.highlightedItems.push(div);
			highlightedDivs.push(div);
			nbMatches++;
		} else {
			div.style.backgroundColor = '';
		}
//		(i % 20) == 0 && (window.status = i);
	}
	while (highlightedDivs.length > 0) {
		highlightedDivs.pop().style.backgroundColor = '#EEEEEE';
	}
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
	return text;
}

