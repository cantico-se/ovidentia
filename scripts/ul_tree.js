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


bab_ul_tree.prototype.processList = function(ul) {
	if (null == ul)
		{	
		ul = this.treeId;
		}
		
	if (!ul.childNodes || ul.childNodes.length==0) { return; }
	// Iterate LIs
	for (var itemi=0;itemi<ul.childNodes.length;itemi++) {
		var item = ul.childNodes[itemi];
		if ("LI" == item.nodeName) {
			var subLists = false;
			for (var sitemi=0;sitemi<item.childNodes.length;sitemi++) {
				var sitem = item.childNodes[sitemi];
				if (sitem.nodeName=="UL") {
					subLists = true;
					this.processList(sitem);
				}
			}	

			var s= document.createElement("SPAN");
			var t= '\u00A0'; // &nbsp;
			s.className = this.nodeLinkClass;
			if (subLists) {
				if ( item.className==null || item.className=="" ) {
					item.className = this.nodeClosedClass;
				}
				// If it's just text, make the text work as the link also
				if (item.firstChild.nodeName=="#text") {
					t = t+item.firstChild.nodeValue;
					item.removeChild(item.firstChild);
				}
				s.onclick = function () {
					this.parentNode.parentNode.className = (this.parentNode.parentNode.className=='bab_ul_tree_open') ? 'bab_ul_tree_closed' : 'bab_ul_tree_open';
					return false;
				}
			}
			else {
				// No sublists, so it's just a bullet node
				item.className = this.nodeBulletClass;
				s.onclick = function () { return false; }
			}
			s.appendChild(document.createTextNode(t));
			item = item.getElementsByTagName('div')[0];
			item.className = this.nodeLineClass;
			item.onmouseover = function() {
				this.className='line hover';
				}
			item.onmouseout = function() {
				this.className='line';
				}
			item.insertBefore(s,item.firstChild);
		}
	}
}


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




bab_ul_tree.prototype.collapse = function() {
	this.expandCollapseList(this.treeId,this.nodeClosedClass);
}

bab_ul_tree.prototype.expand = function() {
	this.expandCollapseList(this.treeId,this.nodeOpenClass);
}


bab_ul_tree.prototype.expandToItem = function(itemId, focus) {
	var ret = this.expandCollapseList(this.treeId,this.nodeOpenClass,itemId);
	if (ret && null != focus) {
		var o = document.getElementById(itemId);
		if (o.scrollIntoView) {
			o.scrollIntoView(false);
		}
	}
}

bab_ul_tree.prototype.expandChecked = function() {
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