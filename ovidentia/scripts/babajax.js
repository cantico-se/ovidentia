
function bab_ajaxRequest(url, action, output, indicator, params)
{
	
	var property = 'innerHTML';
	var id = output;

	if( output != '' )
	{
		ot = output.split(':');
		id = ot[0];
		property = ot[1];
		if( typeof(property)== 'undefined' )
			property = 'innerHTML';
	}

	if( action == '' )
	{
		action = 'get';
	}

	Element.show(indicator);
	var ajax = new Ajax.Request(url, {method: action, parameters: params, onComplete: onUpdateResponse});

	function onUpdateResponse(or) 
	{
		Element.hide(indicator);
		elem = $(id);
		eval('elem.'+property + '= or.responseText');
	}

}
