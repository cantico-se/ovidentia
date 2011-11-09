

function bab_setTopicSettings(){
	jQuery('[name="restriction"]').attr('disabled','disabled');
	jQuery('[name="restriction"]').closest('.bab-article-restriction').hide();
	
	jQuery('[name="notify_members"]').attr('disabled','disabled');
	jQuery('[name="notify_members"]').closest('.widget-layout-vbox-item').hide();
	
	jQuery('[name="tags"]').attr('disabled','disabled');
	jQuery('[name="tags"]').closest('.bab-labelStr').hide();
	
	jQuery('#bab_article_attachments').hide();
	jQuery('.bab-article-picture').hide();

	jQuery('[name="hpage_public"]').attr('disabled','disabled');
	jQuery('[name="hpage_public"]').closest('.widget-layout-vbox-item').hide();
	jQuery('[name="hpage_private"]').attr('disabled','disabled');
	jQuery('[name="hpage_private"]').closest('.widget-layout-vbox-item').hide();
	jQuery('[name="date_publication"]').attr('disabled','disabled');
	jQuery('[name="date_publication"]').closest('.bab-labelStr').hide();
	jQuery('[name="date_archiving"]').attr('disabled','disabled');
	jQuery('[name="date_archiving"]').closest('.bab-labelStr').hide();
	
	jQuery('.bab-article-approbation').hide();
	

	id_topic = jQuery('#bab-article-topic').val();
	id_draft = jQuery('[name=iddraft]').val();
	
	jQuery.ajax({
		url: jQuery('[name="ajaxpath"]').val()+'?tg=artedit&idx=ajaxTopicRow&id_topic='+id_topic+'&id_draft='+id_draft,
		dataType: 'json',
		success: function(settings){

			if(settings.restrict_access == 'Y') {
				var restriction = jQuery('[name="restriction"]');
				restriction.removeAttr('disabled');
				restriction.closest('.bab-article-restriction').show();
				
				var topic = jQuery('[name="topicid"] :selected').attr('value');
				if (!restriction.hasClass('bab-article-restriction-topic-'+topic))
				{
					jQuery('[name="groups[0]"]').closest('.widget-layout-vbox-item').nextAll('.widget-layout-vbox-item').remove();
					jQuery('[name="groups[0]"]').empty();
				}
				
				for (var i = 0; i < settings.groups.length; i++)
				{
					var opt = jQuery('<option value=""></option>');
					opt.attr('value', settings.groups[i][0]);
					opt.text(settings.groups[i][1]);
					jQuery('[name="groups[0]"]').append(opt);
				}
				
				
			}
			
			
			if(settings.notify == 'Y'){
				jQuery('[name="notify_members"]').removeAttr('disabled');
				jQuery('[name="notify_members"]').closest('.widget-layout-vbox-item').show();
			}
			
			if(settings.allow_addImg == 'Y'){
				jQuery('.bab-article-picture').show();
			}
			if(settings.allow_attachments == 'Y'){
				jQuery('#bab_article_attachments').show();
			}
			if(settings.allow_hpages == 'Y'){
				jQuery('[name="hpage_public"]').removeAttr('disabled');
				jQuery('[name="hpage_public"]').closest('.widget-layout-vbox-item').show();
				jQuery('[name="hpage_private"]').removeAttr('disabled');
				jQuery('[name="hpage_private"]').closest('.widget-layout-vbox-item').show();
			}
			
			if(settings.allow_pubdates == 'Y'){
				jQuery('[name="date_publication"]').removeAttr('disabled');
				jQuery('[name="date_publication"]').closest('.bab-labelStr').show();
				jQuery('[name="date_archiving"]').removeAttr('disabled');
				jQuery('[name="date_archiving"]').closest('.bab-labelStr').show();
			}

			
			if(settings.busetags == 'Y'){
				jQuery('[name="tags"]').removeAttr('disabled');
				jQuery('[name="tags"]').closest('.bab-labelStr').show();
			}
			
			if(settings.idsaart != '0'){
				jQuery('.bab-article-approbation').show();
			}
			
			window.babArticle.filesAttachments();
			
			
			// dynamic template work only with ckeditor
			
			if (null != CKEDITOR && null != settings.template)
			{
				if (CKEDITOR.instances['bab_article_head'] && settings.template['head'])
				{
					var head = CKEDITOR.instances['bab_article_head'];
					var data = head.getData();
					if ('' == data)
					{
						head.insertHtml(settings.template['head']);
					}
				}
				
				if (CKEDITOR.instances['bab_article_body'] && settings.template['body'])
				{
					var head = CKEDITOR.instances['bab_article_body'];
					var data = head.getData();
					if ('' == data)
					{
						head.insertHtml(settings.template['body']);
					}
				}
			}
		}
	});
}

window.babArticle = new Object();


window.babArticle.filesAttachments = function()
{
	// do noting if file attachment is hidden
	
	if (0 == jQuery('#bab_article_attachments:visible').length)
	{
		return;
	}
	
	
	
	// query server to update the attachement list
	
	jQuery.ajax({
		url: jQuery('[name="ajaxpath"]').val()+'?tg=artedit&idx=ajaxAttachments',
		dataType: 'json',
		success: function(attachments) {
			var list = jQuery('#bab_article_file_list');
			
			// add to list if in attachements
			
			for (var htmlid in attachments)
			{
				if (list.find('#'+htmlid).length > 0)
				{
					continue;
				}
				
				var file = attachments[htmlid];
				var row = jQuery('<div id="'+htmlid+'" class="bab-art-fileattachment"><a href=""><img src="'+bab_getInstallPath()+'skins/ovidentia/images/Puces/del.gif" /></a> &nbsp;<span></span><br /><label>Description : <input type="text" value="" size="50" /></label></div>');
				
				row.find('span').text(file.name);
				row.find('input').attr('value', file.description);
				row.find('input').attr('name', 'files['+file.filename+']');
				row.data('filename', file.filename);
				list.append(row);
				
				row.find('a').click(function() {
					// call server for delete and refresh list
					
					var filename = jQuery(this).closest('.bab-art-fileattachment').data('filename');
					
					jQuery.get(
						jQuery('[name="ajaxpath"]').val()+'?tg=artedit&idx=ajaxRemoveAttachment&filename='+filename,
						function() {
							window.babArticle.filesAttachments();
						}
					);
					
					
					return false;
				});
			}
			
			list.find('.bab-art-fileattachment').each(function(k, div) {
				var htmlid = jQuery(div).attr('id');
				if (null == attachments[htmlid])
				{
					jQuery(div).remove();
				}
				
			});
		}
	});
};



jQuery(document).ready(function() {
	bab_setTopicSettings();
	jQuery('#bab-article-topic').change(function(){
		bab_setTopicSettings();
	});
	
	
	
	jQuery('#dialog').dialog({width: '90%', height: 600, resizable: false, title: 'Previsualisation', draggable: false, modal: true, buttons: { "Ok": function() { jQuery(this).dialog("close"); } } });
	
	if(window.opener != null && window.opener.bab_popup_obj != null && window.opener.bab_popup_obj == window){
		jQuery('[name="babpopup"]').val(1);
	}
});