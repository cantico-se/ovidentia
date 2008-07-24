function bab_ul_tree(id)
{
	if ('UL' != document.getElementById(id).tagName)
		{ this.treeId = document.getElementById(id).getElementsByTagName('ul')[0]; }
	else
		{ this.treeId = document.getElementById(id); }

	this.nodeClosedClass = 'bab_ul_tree_closed';
	this.nodeOpenClass = 'bab_ul_tree_open';
	this.nodeBulletClass = 'bab_ul_tree_leaf';
	this.nodeLinkClass = 'bullet';
	this.nodeLineClass = 'line';
	this.nodeLineHoverClass = 'line hover';
}


function bab_onNodeClick()
{
	var li = this.parentNode.parentNode;
	if (li.className == this.tree.nodeOpenClass) {
		li.className = this.tree.nodeClosedClass;
	} else {
		li.className = this.tree.nodeOpenClass;
	}
	return false;
}




bab_ul_tree.prototype.processList = function(rootList)
{
	if (rootList == null)
		rootList = this.treeId;

	if (!window.bab_ul_tree_lis)
		 window.bab_ul_tree_lis = rootList.getElementsByTagName('LI');

	var lis = rootList.getElementsByTagName('LI');
	var nbLis = lis.length;
	for (var i = 0; i < nbLis; i++) {
		var li = lis[i];
		li.className = this.nodeBulletClass;
		var div = li.getElementsByTagName('DIV')[0];
		div.className = this.nodeLineClass;
		div.onmouseover = function() {
				this.className='line hover';
			}
		div.onmouseout = function() {
				this.className='line';
			}
		var span = document.createElement('IMG');
		span.style.width = '20px';
		span.style.height = '16px';
		span.src = bab_getInstallPath() + 'skins/ovidentia/images/Puces/Space1PxTrans.gif';
		span.className = this.nodeLinkClass;
		span.tree = this;
		span.onclick = bab_onNodeClick;
		div.insertBefore(span, div.firstChild);		
	}

	var uls = rootList.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		var li = uls[i].parentNode;
		li.className = this.nodeClosedClass;
		var div = li.getElementsByTagName('DIV')[0];
		var img = div.getElementsByTagName('IMG')[0];
		img.tree = this;
		img.onclick = bab_onNodeClick;
		var span = div.firstChild;
		span.className = this.nodeLinkClass;
	}
}


//
bab_ul_tree.prototype.expandCollapseList = function(ul,cName,itemId) {
	if (null == ul)
		{	
		ul = this.treeId;
		}
	if (!ul.childNodes || ul.childNodes.length==0) { return false; }
	// Iterate LIs
	for (var itemi=0;itemi<ul.childNodes.length;itemi++) {
		var item = ul.childNodes[itemi];
		if (itemId!=null && item.id==itemId) { return true; }
		if (item.nodeName == "LI") {
			var subLists = false;
			for (var sitemi=0;sitemi<item.childNodes.length;sitemi++) {
				var sitem = item.childNodes[sitemi];
				if (sitem.nodeName=="UL") {
					subLists = true;
					var ret = this.expandCollapseList(sitem,cName,itemId);
					if (itemId!=null && ret) {
						item.className = cName;
						return true;
					}
				}
			}

			if (subLists && itemId==null) {
				item.className = cName;
			}
		}
	}
}



//
bab_ul_tree.prototype._collapse = function() {
	this.expandCollapseList(this.treeId,this.nodeClosedClass);
}
//
bab_ul_tree.prototype._expand = function() {
	this.expandCollapseList(this.treeId,this.nodeOpenClass);
}





bab_ul_tree.prototype.expandCollapseAll = function(ul, className)
{
	var uls = ul.getElementsByTagName('UL');
	var nbUls = uls.length;
	for (var i = 0; i < nbUls; i++) {
		if (uls[i].parentNode.className != className) {
			uls[i].parentNode.className = className;
		}
	}
}

bab_ul_tree.prototype.collapse = function()
{
	this.expandCollapseAll(this.treeId, this.nodeClosedClass);
}



bab_ul_tree.prototype.expand = function()
{
	this.expandCollapseAll(this.treeId, this.nodeOpenClass);
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


//
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
	var inputs = this.treeId.getElementsByTagName('INPUT');
	var nbInputs = inputs.length
	var jobDone = false;
	for (var i = 0; i < nbInputs; i++) {
		var input = inputs[i];
		if (input.checked && !input.disabled) {
			li = input.parentNode;
			while (li && li.nodeName != 'LI') {
				li = li.parentNode;
			}
			this.expandCollapseListItem(li, this.nodeOpenClass);
			jobDone = true;
		}
	}
	
	return jobDone;
}


bab_ul_tree.prototype.initSearch = function()
{
	if (this.initDone)
		return;
	var listItems = this.treeId.getElementsByTagName('LI');
	var nbListItems = listItems.length;
	for (var i = 0; i < nbListItems; i++) {
		var span = document.getElementById('content' + listItems[i].id);
		var text = span.firstChild.nodeValue;
		text = cleanStringDiacritics(text);
		listItems[i].setAttribute('content', text);
	}
	this.initDone = true;
}

bab_ul_tree.prototype.highlightItem = function(itemId) {
	var item = document.getElementById(itemId);
	if (!item)
		return false;
	var div = item.getElementsByTagName('div')[0];
	div.style.backgroundColor = '#EEEEEE';
	this.expandCollapseList(this.treeId, this.nodeOpenClass, itemId);
	return true;
}

bab_ul_tree.prototype.searchItem = function(targetString) {
	this.initSearch();
	if (targetString == '')
	{
		this.expand();
		var regExp = null;
	}
	else
	{
		this.collapse();
		targetString = cleanStringDiacritics(targetString);
		var regExp = new RegExp(targetString, 'i');
	}
	var nbMatches = 0;
	var listItems = this.treeId.getElementsByTagName('li');
	for (var i = 0; i < listItems.length ; i++)
	{
		var content = listItems[i].getAttribute('content');
		var div = listItems[i].getElementsByTagName('div')[0];
		if (regExp && content && content.match(regExp))
		{
			div.style.backgroundColor = '#EEEEEE';
			this.expandCollapseList(this.treeId, this.nodeOpenClass, listItems[i].id);
			nbMatches++;
		}
		else 
			div.style.backgroundColor = '';
	}
	return nbMatches;
}

function cleanStringDiacritics(text)
{
	try
	{
		text = text.replace(/[áàâä]/g, "a");
		text = text.replace(/[éèêë]/g, "e");
		text = text.replace(/[íìîï]/g, "i");
		text = text.replace(/[óòôö]/g, "o");
		text = text.replace(/[úùûü]/g, "u");
		text = text.replace(/[ç]/g, "c");
	}
	catch (e)
	{
		text = '';
	}
	return text;
}


function tree_check_childs(checkbox)
{
	li = checkbox.parentNode;
	while (li.nodeName != 'LI') {
		li = li.parentNode;
		}
	
	var tree = li.getElementsByTagName('input');
	for (var j = 0; j < tree.length ; j++) {
		tree[j].checked = checkbox.checked;
		}
}