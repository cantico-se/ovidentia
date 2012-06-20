

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
	if (element.className == undefined) {
		return false;
	}
	var classes = element.className.split(' ');
	return classes.contains(className);
}



function bab_getXhr()
{
	var xhr = null; 
	 
	if (window.XMLHttpRequest) { // Firefox et autres
		xhr = new XMLHttpRequest(); 
	} else if(window.ActiveXObject) { // Internet Explorer 
		try {
			xhr = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	return xhr;
}


function bab_loadSubTree(li, url)
{
	var xhr = bab_getXhr();
	xhr.onreadystatechange = function() {
 		if (xhr.readyState == 4 && xhr.status == 200) {
			var fragment = document.createDocumentFragment();
			var d = document.createElement('DIV');
			fragment.appendChild(d);
			d.innerHTML = xhr.responseText;
			var ul = d.getElementsByTagName('UL')[0];
			if (ul) {
				li.appendChild(ul);
				li.className = bab_Tree.prototype.NODE_CLOSED;
				bab_processSubTreeList(li);
				bab_initSubTreeSearch(li);
	
				var div = li.getElementsByTagName('DIV')[0];
				div.onmouseover = bab_onItemMouseOver;
				div.onmouseout = bab_onItemMouseOut;
	
				li.className = bab_Tree.prototype.NODE_OPEN;
			} else {
				li.className = bab_Tree.prototype.NODE_LEAF;
			}
			bab_refreshAllTreeNodes(li.parentNode);
		} else if (xhr.readyState == 4 && xhr.status == 404) {
			li.className = bab_Tree.prototype.NODE_LEAF;
		}
	}
	li.className = bab_Tree.prototype.NODE_LOADING;
	xhr.open('GET', url, true);
	xhr.send(null);
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


function bab_highlightDiv(div)
{
	div.style.backgroundColor = '#DDDDDD';
}

function bab_unhighlightDiv(div)
{
	div.style.backgroundColor = '';
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
		if (context.tree.hideNonMatchingItems) {
			context.tree.hideSubtree(context.tree.rootList);
		}
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
			if (context.tree.hideNonMatchingItems) {
				listItem.style.display = '';
				context.tree.showSubtree(listItem);
			}
			if (context.tree.expandMatchingItemsSubTree) {
				context.tree.expandSubTree(listItem);
			}
			bab_highlightDiv(div);
			context.nbMatches++;
		} else {
			bab_unhighlightDiv(div);
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
//		if (context.searching) {
			this.className = 'bab_searchField';
			this.parentNode.tree.unhighlightAll();
			this.parentNode.tree.expand();
			this.parentNode.tree.showSubtree(this.parentNode.tree.rootList);
//		}
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


function bab_initTree(div)
{
	if (div.getElementsByTagName('UL').length == 0) {
		return null;
	}
	
	var tree = new bab_Tree(div);
	
	tree.hideNonMatchingItems = hasClass(div, 'bab-hide-non-matching-items');
	tree.expandMatchingItemsSubTree = hasClass(div, 'bab-expand-matching-items-sub-tree');

	window.setTimeout('document.getElementById("' + div.id + '").tree.initSearch();', 800);

	var showToolbar = hasClass(div, 'bab-show-toolbar');
	if (showToolbar) {
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
	}

	div.initialized = true;
	
	return tree;
}




function bab_Tree(div)
{
	this.rootList = div.getElementsByTagName('UL')[0];
	this.processList();
	this.id = div.id;
	this.memorizeOpenNodes = false;
	div.tree = this;
}


bab_Tree.prototype.BTN_EXPAND_ALL = 1;
bab_Tree.prototype.BTN_COLLAPSE_ALL = 2;
bab_Tree.prototype.BTN_SEARCH = 3;

bab_Tree.prototype.NODE_INVISIBLE = 'bab_ul_tree_invisible';
bab_Tree.prototype.NODE_CLOSED = 'bab_ul_tree_closed';
bab_Tree.prototype.NODE_OPEN = 'bab_ul_tree_open';
bab_Tree.prototype.NODE_LEAF = 'bab_ul_tree_leaf';
bab_Tree.prototype.NODE_LOADING = 'bab_ul_tree_loading';
bab_Tree.prototype.nodeLinkClass = 'bullet';
bab_Tree.prototype.nodeLineClass = 'line';
bab_Tree.prototype.nodeLineHoverClass = 'line hover';


function bab_onItemMouseOver()
{
/*
	try {
		this.className = bab_Tree.prototype.nodeLineHoverClass;
	} catch(e) {}
*/
}

function bab_onItemMouseOut()
{
/*
	try {
		this.className = bab_Tree.prototype.nodeLineClass;
	} catch(e) {}
*/
}


function bab_refreshAllTreeNodes(ul)
{
	var lis = ul.getElementsByTagName('LI');
	var nbLis = lis.length;
	for (var i = 0; i < nbLis; i++)
	{
		var li = lis[i];
		if (li.className == bab_Tree.prototype.NODE_OPEN) {
			li.className = li.className;
		}
	}
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
	bab_refreshAllTreeNodes(this.tree.rootList);

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
	var uls = this.rootList.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		var li = uls[i].parentNode;
		li.className = bab_Tree.prototype.NODE_CLOSED;
		var div = li.getElementsByTagName('DIV')[0];
		var img = div.getElementsByTagName('IMG')[0];
		img.tree = this;
		img.onclick = bab_onNodeClick;
	}
};


function bab_processSubTreeList(rootList)
{
	var treeDiv = rootList.parentNode;
	while (treeDiv && !hasClass(treeDiv, 'bab_tree')) {
		treeDiv = treeDiv.parentNode;
	}
	var uls = rootList.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		var li = uls[i].parentNode;
		li.className = bab_Tree.prototype.NODE_CLOSED;
		var div = li.getElementsByTagName('DIV')[0];
		var img = div.getElementsByTagName('IMG')[0];
		img.tree = treeDiv.tree;
		img.onclick = bab_onNodeClick;
	}
}


bab_Tree.prototype.expandCollapseListItem = function(listItem, className)
{
	listItem = listItem.parentNode.parentNode;
	while (listItem.tagName == 'LI') {
		if (listItem.className != className) {
			listItem.className = className;
		}
		listItem.style.display = '';
		listItem = listItem.parentNode.parentNode;
	}
};


bab_Tree.prototype.showSubtree = function(ul)
{
	var lis = ul.getElementsByTagName('LI');
	var nbLis = lis.length;
	for (var i = 0; i < nbLis; i++) {
		lis[i].style.display = '';
	}
};

bab_Tree.prototype.hideSubtree = function(ul)
{
	var lis = ul.getElementsByTagName('LI');
	var nbLis = lis.length;
	for (var i = 0; i < nbLis; i++) {
		lis[i].style.display = 'none';
	}
};


bab_Tree.prototype.expandSubTree = function(node)
{
	var uls = node.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		if (uls[i].parentNode.className != bab_Tree.prototype.NODE_OPEN) {
			uls[i].parentNode.className = bab_Tree.prototype.NODE_OPEN;
		}
	}
	this.saveState();
};


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
};


bab_Tree.prototype.collapse = function()
{
	this.expandCollapseAll(this.rootList, this.NODE_CLOSED);
};



bab_Tree.prototype.expand = function()
{
	this.expandCollapseAll(this.rootList, this.NODE_OPEN);
};



bab_Tree.prototype.initSearch = function()
{
	if (this.initDone)
		return;
	var listItems = this.rootList.getElementsByTagName('LI');
	var nbListItems = listItems.length;
	for (var i = 0; i < nbListItems; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0];
		var span = div.getElementsByTagName('SPAN')[1];
		
		try {
			var text = span.firstChild.nodeValue;
			text = cleanStringDiacritics(text);
			listItems[i].setAttribute('content', text);
		} catch (e) {};
		
		div.onmouseover = bab_onItemMouseOver;
		div.onmouseout = bab_onItemMouseOut;

		if (hasClass(span, 'clickable')) {
			span.onclick = bab_onElementClick;
		}
	}
	this.initDone = true;
};

function bab_initSubTreeSearch(rootList)
{
	var listItems = rootList.getElementsByTagName('LI');
	var nbListItems = listItems.length;
	for (var i = 0; i < nbListItems; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0];
		var span = div.getElementsByTagName('SPAN')[0];
		try {
			var text = span.firstChild.nodeValue;
			text = cleanStringDiacritics(text);
			listItems[i].setAttribute('content', text);
		} catch(e) {};
		
		div.onmouseover = bab_onItemMouseOver;
		div.onmouseout = bab_onItemMouseOut;
		
		if (hasClass(span, 'clickable')) {
			span.onclick = bab_onElementClick;
		}
	}
}



bab_Tree.prototype.unhighlightAll = function()
{
	var listItems = this.rootList.getElementsByTagName('LI');
	var nbListItems = listItems.length;
	for (var i = 0; i < nbListItems; i++) {
		var div = listItems[i].getElementsByTagName('DIV')[0];
		if (div.style.backgroundColor != '') {
			bab_unhighlightDiv(div);
		}
	}
};


bab_Tree.prototype.saveState = function()
{
	if (!this.memorizeOpenNodes) {
		return;
	}
	var MAX_COOKIE_SIZE = 2 * 1024;
	var expiryDate = new Date;
	expiryDate.setMonth(expiryDate.getMonth() + 6);

	var cookiePath = '/';
	var listItems = this.rootList.getElementsByTagName('LI');
	var nbListItems = listItems.length;
	var nodes = new Array();
	for (var i = 0; i < nbListItems; i++) {
		var listItem = listItems[i];
		if (listItem.className == this.NODE_OPEN) {
			nodes.push(listItem.id);
		}
	}
	
	var cookieString = nodes.join('/');
	if (cookieString.length > MAX_COOKIE_SIZE) {
		cookieString = cookieString.substr(0, MAX_COOKIE_SIZE);	
	}
	document.cookie = 'bab_Tree.' + this.id + '=' + escape(cookieString)
		+ '; expires=' + expiryDate.toGMTString()
		+ '; path=' + cookiePath;
};


bab_Tree.prototype.loadState = function()
{
	if (!this.memorizeOpenNodes) {
		return;
	}
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
};


function cleanStringDiacritics(text)
{

	var defaultDiacriticsRemovalMap = [
       {'base':'A', 'letters':/[\u0041\u24B6\uFF21\u00C0\u00C1\u00C2\u1EA6\u1EA4\u1EAA\u1EA8\u00C3\u0100\u0102\u1EB0\u1EAE\u1EB4\u1EB2\u0226\u01E0\u00C4\u01DE\u1EA2\u00C5\u01FA\u01CD\u0200\u0202\u1EA0\u1EAC\u1EB6\u1E00\u0104\u023A\u2C6F]/g},
       {'base':'AA','letters':/[\uA732]/g},
       {'base':'AE','letters':/[\u00C6\u01FC\u01E2]/g},
       {'base':'AO','letters':/[\uA734]/g},
       {'base':'AU','letters':/[\uA736]/g},
       {'base':'AV','letters':/[\uA738\uA73A]/g},
       {'base':'AY','letters':/[\uA73C]/g},
       {'base':'B', 'letters':/[\u0042\u24B7\uFF22\u1E02\u1E04\u1E06\u0243\u0182\u0181]/g},
       {'base':'C', 'letters':/[\u0043\u24B8\uFF23\u0106\u0108\u010A\u010C\u00C7\u1E08\u0187\u023B\uA73E]/g},
       {'base':'D', 'letters':/[\u0044\u24B9\uFF24\u1E0A\u010E\u1E0C\u1E10\u1E12\u1E0E\u0110\u018B\u018A\u0189\uA779]/g},
       {'base':'DZ','letters':/[\u01F1\u01C4]/g},
       {'base':'Dz','letters':/[\u01F2\u01C5]/g},
       {'base':'E', 'letters':/[\u0045\u24BA\uFF25\u00C8\u00C9\u00CA\u1EC0\u1EBE\u1EC4\u1EC2\u1EBC\u0112\u1E14\u1E16\u0114\u0116\u00CB\u1EBA\u011A\u0204\u0206\u1EB8\u1EC6\u0228\u1E1C\u0118\u1E18\u1E1A\u0190\u018E]/g},
       {'base':'F', 'letters':/[\u0046\u24BB\uFF26\u1E1E\u0191\uA77B]/g},
       {'base':'G', 'letters':/[\u0047\u24BC\uFF27\u01F4\u011C\u1E20\u011E\u0120\u01E6\u0122\u01E4\u0193\uA7A0\uA77D\uA77E]/g},
       {'base':'H', 'letters':/[\u0048\u24BD\uFF28\u0124\u1E22\u1E26\u021E\u1E24\u1E28\u1E2A\u0126\u2C67\u2C75\uA78D]/g},
       {'base':'I', 'letters':/[\u0049\u24BE\uFF29\u00CC\u00CD\u00CE\u0128\u012A\u012C\u0130\u00CF\u1E2E\u1EC8\u01CF\u0208\u020A\u1ECA\u012E\u1E2C\u0197]/g},
       {'base':'J', 'letters':/[\u004A\u24BF\uFF2A\u0134\u0248]/g},
       {'base':'K', 'letters':/[\u004B\u24C0\uFF2B\u1E30\u01E8\u1E32\u0136\u1E34\u0198\u2C69\uA740\uA742\uA744\uA7A2]/g},
       {'base':'L', 'letters':/[\u004C\u24C1\uFF2C\u013F\u0139\u013D\u1E36\u1E38\u013B\u1E3C\u1E3A\u0141\u023D\u2C62\u2C60\uA748\uA746\uA780]/g},
       {'base':'LJ','letters':/[\u01C7]/g},
       {'base':'Lj','letters':/[\u01C8]/g},
       {'base':'M', 'letters':/[\u004D\u24C2\uFF2D\u1E3E\u1E40\u1E42\u2C6E\u019C]/g},
       {'base':'N', 'letters':/[\u004E\u24C3\uFF2E\u01F8\u0143\u00D1\u1E44\u0147\u1E46\u0145\u1E4A\u1E48\u0220\u019D\uA790\uA7A4]/g},
       {'base':'NJ','letters':/[\u01CA]/g},
       {'base':'Nj','letters':/[\u01CB]/g},
       {'base':'O', 'letters':/[\u004F\u24C4\uFF2F\u00D2\u00D3\u00D4\u1ED2\u1ED0\u1ED6\u1ED4\u00D5\u1E4C\u022C\u1E4E\u014C\u1E50\u1E52\u014E\u022E\u0230\u00D6\u022A\u1ECE\u0150\u01D1\u020C\u020E\u01A0\u1EDC\u1EDA\u1EE0\u1EDE\u1EE2\u1ECC\u1ED8\u01EA\u01EC\u00D8\u01FE\u0186\u019F\uA74A\uA74C]/g},
       {'base':'OI','letters':/[\u01A2]/g},
       {'base':'OO','letters':/[\uA74E]/g},
       {'base':'OU','letters':/[\u0222]/g},
       {'base':'P', 'letters':/[\u0050\u24C5\uFF30\u1E54\u1E56\u01A4\u2C63\uA750\uA752\uA754]/g},
       {'base':'Q', 'letters':/[\u0051\u24C6\uFF31\uA756\uA758\u024A]/g},
       {'base':'R', 'letters':/[\u0052\u24C7\uFF32\u0154\u1E58\u0158\u0210\u0212\u1E5A\u1E5C\u0156\u1E5E\u024C\u2C64\uA75A\uA7A6\uA782]/g},
       {'base':'S', 'letters':/[\u0053\u24C8\uFF33\u1E9E\u015A\u1E64\u015C\u1E60\u0160\u1E66\u1E62\u1E68\u0218\u015E\u2C7E\uA7A8\uA784]/g},
       {'base':'T', 'letters':/[\u0054\u24C9\uFF34\u1E6A\u0164\u1E6C\u021A\u0162\u1E70\u1E6E\u0166\u01AC\u01AE\u023E\uA786]/g},
       {'base':'TZ','letters':/[\uA728]/g},
       {'base':'U', 'letters':/[\u0055\u24CA\uFF35\u00D9\u00DA\u00DB\u0168\u1E78\u016A\u1E7A\u016C\u00DC\u01DB\u01D7\u01D5\u01D9\u1EE6\u016E\u0170\u01D3\u0214\u0216\u01AF\u1EEA\u1EE8\u1EEE\u1EEC\u1EF0\u1EE4\u1E72\u0172\u1E76\u1E74\u0244]/g},
       {'base':'V', 'letters':/[\u0056\u24CB\uFF36\u1E7C\u1E7E\u01B2\uA75E\u0245]/g},
       {'base':'VY','letters':/[\uA760]/g},
       {'base':'W', 'letters':/[\u0057\u24CC\uFF37\u1E80\u1E82\u0174\u1E86\u1E84\u1E88\u2C72]/g},
       {'base':'X', 'letters':/[\u0058\u24CD\uFF38\u1E8A\u1E8C]/g},
       {'base':'Y', 'letters':/[\u0059\u24CE\uFF39\u1EF2\u00DD\u0176\u1EF8\u0232\u1E8E\u0178\u1EF6\u1EF4\u01B3\u024E\u1EFE]/g},
       {'base':'Z', 'letters':/[\u005A\u24CF\uFF3A\u0179\u1E90\u017B\u017D\u1E92\u1E94\u01B5\u0224\u2C7F\u2C6B\uA762]/g},
       {'base':'a', 'letters':/[\u0061\u24D0\uFF41\u1E9A\u00E0\u00E1\u00E2\u1EA7\u1EA5\u1EAB\u1EA9\u00E3\u0101\u0103\u1EB1\u1EAF\u1EB5\u1EB3\u0227\u01E1\u00E4\u01DF\u1EA3\u00E5\u01FB\u01CE\u0201\u0203\u1EA1\u1EAD\u1EB7\u1E01\u0105\u2C65\u0250]/g},
       {'base':'aa','letters':/[\uA733]/g},
       {'base':'ae','letters':/[\u00E6\u01FD\u01E3]/g},
       {'base':'ao','letters':/[\uA735]/g},
       {'base':'au','letters':/[\uA737]/g},
       {'base':'av','letters':/[\uA739\uA73B]/g},
       {'base':'ay','letters':/[\uA73D]/g},
       {'base':'b', 'letters':/[\u0062\u24D1\uFF42\u1E03\u1E05\u1E07\u0180\u0183\u0253]/g},
       {'base':'c', 'letters':/[\u0063\u24D2\uFF43\u0107\u0109\u010B\u010D\u00E7\u1E09\u0188\u023C\uA73F\u2184]/g},
       {'base':'d', 'letters':/[\u0064\u24D3\uFF44\u1E0B\u010F\u1E0D\u1E11\u1E13\u1E0F\u0111\u018C\u0256\u0257\uA77A]/g},
       {'base':'dz','letters':/[\u01F3\u01C6]/g},
       {'base':'e', 'letters':/[\u0065\u24D4\uFF45\u00E8\u00E9\u00EA\u1EC1\u1EBF\u1EC5\u1EC3\u1EBD\u0113\u1E15\u1E17\u0115\u0117\u00EB\u1EBB\u011B\u0205\u0207\u1EB9\u1EC7\u0229\u1E1D\u0119\u1E19\u1E1B\u0247\u025B\u01DD]/g},
       {'base':'f', 'letters':/[\u0066\u24D5\uFF46\u1E1F\u0192\uA77C]/g},
       {'base':'g', 'letters':/[\u0067\u24D6\uFF47\u01F5\u011D\u1E21\u011F\u0121\u01E7\u0123\u01E5\u0260\uA7A1\u1D79\uA77F]/g},
       {'base':'h', 'letters':/[\u0068\u24D7\uFF48\u0125\u1E23\u1E27\u021F\u1E25\u1E29\u1E2B\u1E96\u0127\u2C68\u2C76\u0265]/g},
       {'base':'hv','letters':/[\u0195]/g},
       {'base':'i', 'letters':/[\u0069\u24D8\uFF49\u00EC\u00ED\u00EE\u0129\u012B\u012D\u00EF\u1E2F\u1EC9\u01D0\u0209\u020B\u1ECB\u012F\u1E2D\u0268\u0131]/g},
       {'base':'j', 'letters':/[\u006A\u24D9\uFF4A\u0135\u01F0\u0249]/g},
       {'base':'k', 'letters':/[\u006B\u24DA\uFF4B\u1E31\u01E9\u1E33\u0137\u1E35\u0199\u2C6A\uA741\uA743\uA745\uA7A3]/g},
       {'base':'l', 'letters':/[\u006C\u24DB\uFF4C\u0140\u013A\u013E\u1E37\u1E39\u013C\u1E3D\u1E3B\u017F\u0142\u019A\u026B\u2C61\uA749\uA781\uA747]/g},
       {'base':'lj','letters':/[\u01C9]/g},
       {'base':'m', 'letters':/[\u006D\u24DC\uFF4D\u1E3F\u1E41\u1E43\u0271\u026F]/g},
       {'base':'n', 'letters':/[\u006E\u24DD\uFF4E\u01F9\u0144\u00F1\u1E45\u0148\u1E47\u0146\u1E4B\u1E49\u019E\u0272\u0149\uA791\uA7A5]/g},
       {'base':'nj','letters':/[\u01CC]/g},
       {'base':'o', 'letters':/[\u006F\u24DE\uFF4F\u00F2\u00F3\u00F4\u1ED3\u1ED1\u1ED7\u1ED5\u00F5\u1E4D\u022D\u1E4F\u014D\u1E51\u1E53\u014F\u022F\u0231\u00F6\u022B\u1ECF\u0151\u01D2\u020D\u020F\u01A1\u1EDD\u1EDB\u1EE1\u1EDF\u1EE3\u1ECD\u1ED9\u01EB\u01ED\u00F8\u01FF\u0254\uA74B\uA74D\u0275]/g},
       {'base':'oi','letters':/[\u01A3]/g},
       {'base':'ou','letters':/[\u0223]/g},
       {'base':'oo','letters':/[\uA74F]/g},
       {'base':'p','letters':/[\u0070\u24DF\uFF50\u1E55\u1E57\u01A5\u1D7D\uA751\uA753\uA755]/g},
       {'base':'q','letters':/[\u0071\u24E0\uFF51\u024B\uA757\uA759]/g},
       {'base':'r','letters':/[\u0072\u24E1\uFF52\u0155\u1E59\u0159\u0211\u0213\u1E5B\u1E5D\u0157\u1E5F\u024D\u027D\uA75B\uA7A7\uA783]/g},
       {'base':'s','letters':/[\u0073\u24E2\uFF53\u00DF\u015B\u1E65\u015D\u1E61\u0161\u1E67\u1E63\u1E69\u0219\u015F\u023F\uA7A9\uA785\u1E9B]/g},
       {'base':'t','letters':/[\u0074\u24E3\uFF54\u1E6B\u1E97\u0165\u1E6D\u021B\u0163\u1E71\u1E6F\u0167\u01AD\u0288\u2C66\uA787]/g},
       {'base':'tz','letters':/[\uA729]/g},
       {'base':'u','letters':/[\u0075\u24E4\uFF55\u00F9\u00FA\u00FB\u0169\u1E79\u016B\u1E7B\u016D\u00FC\u01DC\u01D8\u01D6\u01DA\u1EE7\u016F\u0171\u01D4\u0215\u0217\u01B0\u1EEB\u1EE9\u1EEF\u1EED\u1EF1\u1EE5\u1E73\u0173\u1E77\u1E75\u0289]/g},
       {'base':'v','letters':/[\u0076\u24E5\uFF56\u1E7D\u1E7F\u028B\uA75F\u028C]/g},
       {'base':'vy','letters':/[\uA761]/g},
       {'base':'w','letters':/[\u0077\u24E6\uFF57\u1E81\u1E83\u0175\u1E87\u1E85\u1E98\u1E89\u2C73]/g},
       {'base':'x','letters':/[\u0078\u24E7\uFF58\u1E8B\u1E8D]/g},
       {'base':'y','letters':/[\u0079\u24E8\uFF59\u1EF3\u00FD\u0177\u1EF9\u0233\u1E8F\u00FF\u1EF7\u1E99\u1EF5\u01B4\u024F\u1EFF]/g},
       {'base':'z','letters':/[\u007A\u24E9\uFF5A\u017A\u1E91\u017C\u017E\u1E93\u1E95\u01B6\u0225\u0240\u2C6C\uA763]/g}
   ];

   try {
	   for (var i = 0; i < defaultDiacriticsRemovalMap.length; i++) {
		   text = text.replace(defaultDiacriticsRemovalMap[i].letters, defaultDiacriticsRemovalMap[i].base);
	   }
	} catch (e) {
		text = '';
	}

	return text.toUpperCase();
}

