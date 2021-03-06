/**
 * CK editor specific insertion
 * @param html_id
 * @param instance_id
 * @param tpl
 */
function bab_fillEditorTemplate(html_id, instance_id, tpl, confirm_message)
{
	if (CKEDITOR.instances[instance_id] && tpl)
	{
		jQuery('#'+html_id+' .widget-section-content').show('fast', function() {
		
			var edframe = CKEDITOR.instances[instance_id];
			var data = edframe.getData();
			if ('' == data)
			{
				edframe.insertHtml(tpl);
				return;
			}
			
			if (confirm(confirm_message))
			{
				edframe.setData(tpl);
				return;
			}
		});
	}
}



/**
 * 
 * @param int editor_delay Delay before access to the editor
 */
function bab_setTopicSettings(editor_delay){
	jQuery('[name="restriction"]').attr('disabled','disabled');
	jQuery('[name="restriction"]').closest('.bab-article-restriction').hide();
	
	jQuery('[name="notify_members"]').attr('disabled','disabled');
	jQuery('[name="notify_members"]').closest('.widget-layout-vbox-item').hide();
	
	jQuery('[name="tags"]').attr('disabled','disabled');
	jQuery('[name="tags"]').closest('.bab-labelStr').hide();
	
	jQuery('[name="page_title"]').attr('disabled','disabled');
	jQuery('[name="page_description"]').attr('disabled','disabled');
	jQuery('[name="page_keywords"]').attr('disabled','disabled');
	jQuery('[name="rewritename"]').attr('disabled','disabled');
	jQuery('[name="page_title"]').closest('.widget-section').hide();
	
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
	
	if (jQuery('#bab-article-topic').length == 0) {
		id_topic = jQuery('[name="id_topic_db"]').val();
	} else {
		id_topic = jQuery('#bab-article-topic').val();
	}
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
			
			if(settings.allow_meta == 1){
				jQuery('[name="page_title"]').removeAttr('disabled');
				jQuery('[name="page_description"]').removeAttr('disabled');
				jQuery('[name="page_keywords"]').removeAttr('disabled');
				jQuery('[name="rewritename"]').removeAttr('disabled');
				jQuery('[name="page_title"]').closest('.widget-section').show();
			}
			
			if(settings.idsaart != '0'){
				jQuery('.bab-article-approbation').show();
			}
			
			window.babArticle.filesAttachments();
			
			
			// dynamic template work only with ckeditor
			if (id_topic !== jQuery('[name="id_topic_db"]').val())
			{
				setTimeout(function() {
				
					if (null != CKEDITOR && null != settings.template)
					{
						bab_fillEditorTemplate('bab_articlehead_section', 'bab_article_head', settings.template['head'], settings.confirm_message['head']);
						bab_fillEditorTemplate('bab_articlebody_section', 'bab_article_body', settings.template['body'], settings.confirm_message['body']);
					}
				
				}, editor_delay);
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
						jQuery('[name="ajaxpath"]').val()+'?tg=artedit&idx=ajaxRemoveAttachment&filename='+encodeURIComponent(filename),
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
	
	var inittopic = false;
	var initselect = false;
	
	var timer = setInterval(function(){
		
		var id_topic = 0;
		if (jQuery('#bab-article-topic').length == 0) {
			id_topic = parseInt(jQuery('[name="id_topic_db"]').val());
		} 
		
		if (jQuery('#bab-article-topic').length == 0 && id_topic == 0)
		{
			return;
		}
		
		if (false === inittopic)
		{
			inittopic = true;
			// console.debug('init Topic');
			bab_setTopicSettings(1000);
		}
		
		if (false === initselect && jQuery('#bab-article-topic').length > 0)
		{
			initselect = true;
			// console.debug('init Select');
			
			jQuery('#bab-article-topic').change(function(){
				bab_setTopicSettings(0);
			});
			
			// clearInterval(timer);
		}

		
	},500);

	
	
	
	
	jQuery('#dialog').dialog({width: '90%', height: 600, resizable: false, title: 'Previsualisation', draggable: false, modal: true, buttons: { "Ok": function() { jQuery(this).dialog("close"); } } });
	
	try{
		if(window.opener != null && window.opener.bab_popup_obj != null && window.opener.bab_popup_obj == window){
			jQuery('[name="babpopup"]').val(1);
		}
	}catch(err){}
});