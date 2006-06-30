function bab_findPosX(obj){
	var curleft = 0;
	if (obj.offsetParent){
		while (obj.offsetParent){
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	return curleft;
}

function bab_findPosY(obj){
	var curtop = 0;
	if (obj.offsetParent){
		while (obj.offsetParent){
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	return curtop;
}


function bab_updateTimeSelect(sClockImgFullPath) {
	var input = document.getElementsByTagName('input');
	for(var i = 0 ; i < input.length; i++) {
		if ('text' == input[i].type && -1 != input[i].className.indexOf('bab_hours_mins')) {
			if (input[i].nextSibling.className!='clock') {
				img = document.createElement('img');
				img.src = sClockImgFullPath;
				img.className = 'clock';
				img.align = 'absmiddle';
				if (input[i].nextSibling) {
					input[i].parentNode.insertBefore(img,input[i].nextSibling);
				} else {
					input[i].parentNode.appendChild(img);
				}

				img.onclick = function() {
					
					if (document.getElementById('ADDON_BAB_clockdetail')) {
						clock = document.getElementById('ADDON_BAB_clockdetail');
						clock.parentNode.removeChild(clock);
					} else {
						div = document.createElement('div');
						div.id = 'ADDON_BAB_clockdetail';
						div.style.top = bab_findPosY(this)+'px';
						div.style.left = (bab_findPosX(this)+20)+'px';
						for(h = 6; h < 24 ; h++) {
							a = document.createElement('a');
							h = h+'';
							a.innerHTML = (h.length < 2 ? '0'+h : h )+':00';
							a.clock = this.previousSibling;
							a.href='';
							a.onclick = function() {
								input = this.clock;
								while(input.nodeName != 'INPUT') {
									input = input.previousSibling;
								}
								input.value = this.innerHTML;
								
								input.onkeyup && input.onkeyup();
								input.onclick && input.onclick();

								this.parentNode.parentNode.removeChild(this.parentNode);
								return false;
							}
							div.appendChild(a);
						}
						document.getElementsByTagName('body')[0].appendChild(div);
					}
				}
			}
		}
	}
}



function bab_updateDateSelect(sNewEventImgFullPath) {
	var input = document.getElementsByTagName('input');
	for(var i = 0 ; i < input.length; i++) {
		if ('text' == input[i].type && -1 != input[i].className.indexOf('bab_date_eur')) {
			if (input[i].nextSibling.className!='date') {
				img = document.createElement('img');
				img.src = sNewEventImgFullPath;
				img.className = 'date';
				img.align = 'absmiddle';
				if (input[i].nextSibling) {
					input[i].parentNode.insertBefore(img,input[i].nextSibling);
				} else {
					input[i].parentNode.appendChild(img);
				}

				img.onclick = function() {
					
					bab_dialog.currentObject = this.previousSibling;
					
					bab_dialog.selectdate (
						function(val) {
							bab_dialog.currentObject.value = val['day']+'/'+val['month']+'/'+val['year'];
							bab_dialog.currentObject.onkeyup && bab_dialog.currentObject.onkeyup();
						}
					);
				}
			}
		}
	}
}