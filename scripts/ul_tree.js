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
}


bab_ul_tree.prototype.nextTagSibbling = function(obj) {
	obj = obj.nextSibling
	while(obj && obj.nodeType != 1){
		obj = obj.nextSibling
		}
	return obj;
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
		if (item.nodeName == "LI") {
			var next = this.nextTagSibbling(item);
			var subLists = false;
			if (next && next.nodeName=="UL") {
				subLists = true;
				this.processList(next);
				}	

			var s= document.createElement("SPAN");
			var t= '\u00A0'; // &nbsp;
			s.className = this.nodeLinkClass;
			if (subLists) {
				// This LI has UL's in it, so it's a +/- node
				if ( item.className==null || item.className=="" ) {
					item.className = this.nodeClosedClass;
					next.className = this.nodeClosedClass;
				}
				// If it's just text, make the text work as the link also
				if (item.firstChild.nodeName=="#text") {
					t = t+item.firstChild.nodeValue;
					item.removeChild(item.firstChild);
				}
				s.onclick = function () {
					var newclass = (this.parentNode.className == 'bab_ul_tree_open') ? 'bab_ul_tree_closed' : 'bab_ul_tree_open';
					this.parentNode.className = newclass;
					obj = this.parentNode;
					obj = obj.nextSibling;
					while(obj && obj.nodeType != 1){
						obj = obj.nextSibling;
						}
					
					if (obj.nodeName=="UL")
					{
						obj.className = newclass;
					}
					return false;
				}
			}
			else {
				// No sublists, so it's just a bullet node
				item.className = this.nodeBulletClass;
				s.onclick = function () { return false; }
			}
			s.appendChild(document.createTextNode(t));
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
			var next = this.nextTagSibbling(item);
			var subLists = false;
			if (next && next.nodeName=="UL") {
				subLists = true;
				var ret = this.expandCollapseList(next,cName,itemId);
					if (itemId!=null && ret) {
						item.className = cName;
						return true;
					}
				}	
			
			if (subLists && itemId==null) {
				item.className = cName;
				next.className = cName;
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