jQuery(document).ready(function() {
	setTopicSettings();
	jQuery('#bab-article-topic').change(function(){
		setTopicSettings();
	});
	
	jQuery('#dialog').dialog({width: '90%', height: 600, resizable: false, title: 'Previsualisation', draggable: false, modal: true, buttons: { "Ok": function() { $(this).dialog("close"); } } });
	
});

function setTopicSettings(){
	jQuery('[name="restriction"]').attr('disabled','disabled');
	jQuery('[name="restriction"]').parent().parent().parent().hide();
	jQuery('[name="operator"]').attr('disabled','disabled');
	jQuery('[name="operator"]').parent().parent().parent().hide();
	jQuery('[name="notify_members"]').attr('disabled','disabled');
	jQuery('[name="notify_members"]').parent().parent().parent().parent().parent().hide();
	jQuery('[name="tags"]').attr('disabled','disabled');
	jQuery('[name="tags"]').parent().parent().parent().hide();
	
	jQuery('.widget-filepicker').first().parent().hide();
	jQuery('.widget-filepicker').last().parent().hide();

	jQuery('[name="hpage_public"]').attr('disabled','disabled');
	jQuery('[name="hpage_public"]').parent().parent().parent().parent().parent().hide();
	jQuery('[name="hpage_private"]').attr('disabled','disabled');
	jQuery('[name="hpage_private"]').parent().parent().parent().parent().parent().hide();
	jQuery('[name="date_publication"]').attr('disabled','disabled');
	jQuery('[name="date_publication"]').parent().parent().parent().parent().parent().parent().parent().hide();
	jQuery('[name="date_archiving"]').attr('disabled','disabled');
	jQuery('[name="date_archiving"]').parent().parent().parent().parent().parent().parent().parent().hide();
	

	valueSelected = jQuery('#bab-article-topic').val();
	jQuery.ajax({
		url: jQuery('[name="ajaxpath"]').val()+'?tg=artedit&idx=ajaxTopicRow&id='+valueSelected,
		dataType: 'json',
		success: function(settings){
			if(settings.restriction == 'Y'){
				jQuery('[name="restriction"]').removeAttr('disabled');
				jQuery('[name="restriction"]').parent().parent().parent().show();
				jQuery('[name="operator"]').removeAttr('disabled');
				jQuery('[name="operator"]').parent().parent().parent().show();
			}
			if(settings.notify == 'Y'){
				jQuery('[name="notify_members"]').removeAttr('disabled');
				jQuery('[name="notify_members"]').parent().parent().parent().parent().parent().show();
			}
			
			//what to do for filepicker????
			if(settings.allow_addImg == 'Y'){
				jQuery('.widget-filepicker').first().parent().show();
			}
			if(settings.allow_attachments == 'Y'){
				jQuery('.widget-filepicker').last().parent().show();
			}
			if(settings.allow_hpages == 'Y'){
				jQuery('[name="hpage_public"]').removeAttr('disabled');
				jQuery('[name="hpage_public"]').parent().parent().parent().parent().parent().show();
				jQuery('[name="hpage_private"]').removeAttr('disabled');
				jQuery('[name="hpage_private"]').parent().parent().parent().parent().parent().show();
			}
			
			if(settings.allow_pubdates == 'Y'){
				jQuery('[name="date_publication"]').removeAttr('disabled');
				jQuery('[name="date_publication"]').parent().parent().parent().parent().parent().parent().parent().show();
				jQuery('[name="date_archiving"]').removeAttr('disabled');
				jQuery('[name="date_archiving"]').parent().parent().parent().parent().parent().parent().parent().show();
			}

			if(settings.allow_manupdate == 'Y'){
				//jQuery('[name="notify_members"]').removeAttr('disabled');
			}
			if(settings.allow_update == 'Y'){
				//jQuery('[name="notify_members"]').removeAttr('disabled');
			}
			if(settings.busetags == 'Y'){
				jQuery('[name="tags"]').removeAttr('disabled');
				jQuery('[name="tags"]').parent().parent().parent().show();
			}
			
			
		}
	});
}