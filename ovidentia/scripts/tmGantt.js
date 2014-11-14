var iDiffX = 0;
var iDiffY = 0;
var oSelectedObj;  
     
function handleMouseMove(oEvent) {
    var iWidth = oEvent.clientX - iDiffX;
    
//    if(iWidth <= parseInt(oTarget.parentNode.style.width) && iWidth > 11)
    {
    	oSelectedObj.style.width = iWidth + 'px';
    }
}
           
function handleMouseDown(oEvent) {
	
	oSelectedObj = Event.element(oEvent);
	iDiffX = oEvent.clientX - parseInt(oSelectedObj.style.width);

	Event.observe(document.body, 'mousemove', handleMouseMove, false);
	Event.observe(document.body, 'mouseup', handleMouseUp, false);
}

function handleMouseUp(oEvent) {
	Event.stopObserving(document.body, 'mousemove', handleMouseMove, false);
	Event.stopObserving(document.body, 'mouseup', handleMouseUp, false);
}

function tmInit(sClassName, sParentId)
{
	var aElementsList = document.getElementsByClassName(sClassName, sParentId);
	var iSize = aElementsList.length;
	
	for(var iIndex = 0; iIndex < iSize; iIndex++)
	{
		Event.observe(aElementsList[iIndex], 'mousedown', handleMouseDown, false);
	}
}