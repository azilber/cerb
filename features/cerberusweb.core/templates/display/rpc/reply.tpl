<div class="block reply_frame" style="width:98%;margin:10px;">

<form id="reply{$message->id}_part1" onsubmit="return false;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2 style="color:rgb(50,50,50);">{if $is_forward}{'display.ui.forward'|devblocks_translate|capitalize}{else}{'display.ui.reply'|devblocks_translate|capitalize}{/if}</h2></td>
	</tr>
	<tr>
		<td width="100%">
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				{if isset($groups.{$ticket->group_id})}
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><b>{'message.header.from'|devblocks_translate|capitalize}:</b>&nbsp;</td>
					<td width="99%" align="left">
						{$groups.{$ticket->group_id}->name}
					</td>
				</tr>
				{/if}
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><b>{'message.header.to'|devblocks_translate|capitalize}:</b>&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="to" value="{$to}" placeholder="{if $is_forward}These recipients will receive this forwarded message{else}These recipients will automatically be included in all future correspondence{/if}" class="required" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
						{if !$is_forward}
							{if !empty($suggested_recipients)}
								<div id="reply{$message->id}_suggested">
									<a href="javascript:;" onclick="$(this).closest('div').remove();">x</a>
									<b>Consider adding these recipients:</b>
									<ul class="bubbles">
									{foreach from=$suggested_recipients item=sug name=sugs}
										<li><a href="javascript:;" class="suggested">{$sug.full_email}</a></li>
									{/foreach}
									</ul> 
								</div>
							{/if}
						{/if}
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle">{'message.header.cc'|devblocks_translate|capitalize}:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="cc" value="{$cc}" placeholder="These recipients will publicly receive a one-time copy of this message" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle">{'message.header.bcc'|devblocks_translate|capitalize}:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="bcc" value="{$bcc}" placeholder="These recipients will secretly receive a one-time copy of this message" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">					
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b>&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="subject" value="{$subject}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;" class="required">
					</td>
				</tr>
				
			</table>
			
			<div id="divDraftStatus{$message->id}"></div>
			
			<div>
				<fieldset style="display:inline-block;margin-bottom:0;">
					<legend>Actions</legend>
					{assign var=headers value=$message->getHeaders()}
					<button name="saveDraft" type="button"><span class="glyphicons glyphicons-circle-ok"></span> Save Draft</button>
					
					{* Virtual Attendants *}
					{if !empty($macros)}
					<button type="button" title="(Ctrl+Shift+B)" class="split-left" onclick="$(this).next('button').click();"><span class="glyphicons glyphicons-remote-control"></span></button><!--  
					--><button type="button" title="(Ctrl+Shift+B)" class="split-right" id="btnReplyMacros{$message->id}"><span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:white;"></span></button>
					<ul class="cerb-popupmenu cerb-float" id="menuReplyMacros{$message->id}">
						<li style="background:none;">
							<input type="text" size="32" class="input_search filter">
						</li>
						
						{$vas = DAO_VirtualAttendant::getAll()}
						
						{foreach from=$vas item=va}
							{capture name=behaviors}
							{foreach from=$macros item=macro key=macro_id}
							{if $macro->virtual_attendant_id == $va->id}
							<li class="item item-behavior">
								<div style="margin-left:10px;">
									{if $macro->has_public_vars}
									<a href="javascript:;" onclick="genericAjaxPopup('peek','c=display&a=showMacroReplyPopup&ticket_id={$message->ticket_id}&message_id={$message->id}&macro={$macro->id}',$(this).closest('ul').get(),false,'400');$(this).closest('ul.cerb-popupmenu').hide();">
									{else}
									<a href="javascript:;" onclick="genericAjaxGet('','c=display&a=getMacroReply&ticket_id={$message->ticket_id}&message_id={$message->id}&macro={$macro->id}', function(js) { $script=$('<div></div>').html(js); $('BODY').append($script); });$(this).closest('ul.cerb-popupmenu').hide();">
									{/if}
										{if !empty($macro->title)}
											{$macro->title}
										{else}
											{$event = DevblocksPlatform::getExtension($macro->event_point, false)}
											{$event->name}
										{/if}
									</a>
								</div>
							</li>
							{/if}
							{/foreach}
							{/capture}
							
							{if strlen(trim($smarty.capture.behaviors))}
							<li class="item-va">
								<div>
									<a href="javascript:;" style="color:black;" tabindex="-1">{$va->name}</a>
								</div>
							</li>
							{$smarty.capture.behaviors nofilter}
							{/if}
						{/foreach}
						
					</ul>
					{/if}
					
					<button id="btnInsertReplySig{$message->id}" type="button" {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+G)"{/if} onclick="$('#reply_{$message->id}').insertAtCursor('#signature\n');"><span class="glyphicons glyphicons-edit"></span> {'display.reply.insert_sig'|devblocks_translate|capitalize}</button>
					
					{* Plugin Toolbar *}
					{if !empty($reply_toolbaritems)}
						{foreach from=$reply_toolbaritems item=renderer}
							{if !empty($renderer)}{$renderer->render($message)}{/if}
						{/foreach}
					{/if}
				</fieldset>
				
				<fieldset style="display:inline-block;margin-bottom:0;">
					<legend>{'common.snippets'|devblocks_translate|capitalize}</legend>
					<div>
						Insert: 
						<input type="text" size="25" class="context-snippet autocomplete" {if $pref_keyboard_shortcuts}placeholder="(Ctrl+Shift+I)"{/if}>
						<button type="button" onclick="ajax.chooserSnippet('chooser{$message->id}',$('#reply_{$message->id}'), { '{CerberusContexts::CONTEXT_TICKET}':'{$ticket->id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });"><span class="glyphicons glyphicons-search"></span></button>
						<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showSnippetsPeek&id=0&owner_context={CerberusContexts::CONTEXT_WORKER}&owner_context_id={$active_worker->id}&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}',null,false,'550');"><span class="glyphicons glyphicons-circle-plus"></span></button>
					</div>
				</fieldset>
			</div>
			
		</td>
	</tr>
</table>
</form>

<div id="replyToolbarOptions{$message->id}"></div>

{$message_content = $message->getContent()}
{$mail_reply_html = DAO_WorkerPref::get($active_worker->id, 'mail_reply_html', 0)}
{$mail_reply_textbox_size_auto = DAO_WorkerPref::get($active_worker->id, 'mail_reply_textbox_size_auto', 0)}
{$mail_reply_textbox_size_px = DAO_WorkerPref::get($active_worker->id, 'mail_reply_textbox_size_px', 300)}

<form id="reply{$message->id}_part2" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td>
<!-- {* [TODO] This is ugly but gets the job done for now, giving toolbar plugins above their own <form> scope *} -->
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="sendReply">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<input type="hidden" name="ticket_mask" value="{$ticket->mask}">
<input type="hidden" name="draft_id" value="{$draft->id}">
<input type="hidden" name="reply_mode" value="">
{if $is_forward}<input type="hidden" name="is_forward" value="1">{/if}
<input type="hidden" name="group_id" value="{$ticket->group_id}">
<input type="hidden" name="bucket_id" value="{$ticket->bucket_id}">
<input type="hidden" name="format" value="{if ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}parsedown{/if}">

<!-- {* Copy these dynamically so a plugin dev doesn't need to conflict with the reply <form> *} -->
<input type="hidden" name="to" value="{$to}">
<input type="hidden" name="cc" value="{$cc}">
<input type="hidden" name="bcc" value="{$bcc}">
<input type="hidden" name="subject" value="{$subject}">

{if $is_forward}
<textarea name="content" id="reply_{$message->id}" class="reply" style="width:98%;height:{$mail_reply_textbox_size_px|default:300}px;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($draft)}{$draft->body}{else}
{if !empty($signature)}


#signature
{/if}

{'display.reply.forward.banner'|devblocks_translate}
{if isset($headers.subject)}{'message.header.subject'|devblocks_translate|capitalize}: {$headers.subject|cat:"\n"}{/if}
{if isset($headers.from)}{'message.header.from'|devblocks_translate|capitalize}: {$headers.from|cat:"\n"}{/if}
{if isset($headers.date)}{'message.header.date'|devblocks_translate|capitalize}: {$headers.date|cat:"\n"}{/if}
{if isset($headers.to)}{'message.header.to'|devblocks_translate|capitalize}: {$headers.to|cat:"\n"}{/if}

{$message_content|trim}
{/if}
</textarea>
{else}
<textarea name="content" id="reply_{$message->id}" class="reply" style="width:98%;height:{$mail_reply_textbox_size_px|default:300}px;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($draft)}{$draft->body}{else}
{if !empty($signature) && (1==$signature_pos || 3==$signature_pos)}


#signature{if 1==$signature_pos}

#cut{/if}{if in_array($reply_mode,[0,2])}{*Sig above*}


{/if}
{/if}{if in_array($reply_mode,[0,2])}{$quote_sender=$message->getSender()}{$quote_sender_personal=$quote_sender->getName()}{if !empty($quote_sender_personal)}{$reply_personal=$quote_sender_personal}{else}{$reply_personal=$quote_sender->email}{/if}{$reply_date=$message->created_date|devblocks_date:'D, d M Y'}{'display.reply.reply_banner'|devblocks_translate:$reply_date:$reply_personal}
{/if}{if in_array($reply_mode,[0,2])}{$message_content|trim|indent:1:'> '|devblocks_email_quote}
{/if}{if !empty($signature) && 2==$signature_pos}


#signature
#cut
{/if}{*Sig below*}{/if}
</textarea>
{/if}

			<div class="cerb-form-hint" style="display:block;">(Use #commands to perform additional actions)</div>
		</td>
	</tr>
	<tr>
		<td>
			<fieldset class="peek reply-attachments">
				<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
				
				<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
				<ul class="bubbles chooser-container">
				{if $draft->params.file_ids}
					{foreach from=$draft->params.file_ids item=file_id}
						{$file = DAO_Attachment::get($file_id)}
						{if !empty($file)}
						<li><input type="hidden" name="file_ids[]" value="{$file_id}">{$file->display_name} ({$file->storage_size} bytes) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
						{/if} 
					{/foreach}
				{elseif $is_forward && !empty($forward_attachments)}
					{foreach from=$forward_attachments item=attach}
						<li><input type="hidden" name="file_ids[]" value="{$attach->id}">{$attach->display_name} ({$attach->storage_size} bytes) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
					{/foreach}
				{/if}
				</ul>
				
			</fieldset>
		</td>
	</tr>
	<tr>
		<td>
			<fieldset class="peek">
				<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
				
				<table cellpadding="2" cellspacing="0" border="0" id="replyStatus{$message->id}">
					<tr>
						<td nowrap="nowrap" valign="top">
							<div style="margin-bottom:10px;">
								<span>
									{$recommend_btn_domid = uniqid()}
									{include file="devblocks:cerberusweb.core::internal/recommendations/context_recommend_button.tpl" object_recommendations=$object_recommendations context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id full=true recommend_btn_domid=$recommend_btn_domid recommend_group_id=$ticket->group_id recommend_bucket_id=$ticket->bucket_id}
								</span>
								<span>
									{$watchers_btn_domid = uniqid()}
									{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" object_watchers=$object_watchers context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id full=true watchers_btn_domid=$watchers_btn_domid watchers_group_id=$ticket->group_id watchers_bucket_id=$ticket->bucket_id}
								</span>
							</div>
							
							<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+O)"{/if}><input type="radio" name="closed" value="0" class="status_open" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','none');" {if (empty($draft) && 'open'==$mail_status_reply) || $draft->params.closed==0}checked="checked"{/if}>{'status.open'|devblocks_translate|capitalize}</label>
							<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+W)"{/if}><input type="radio" name="closed" value="2" class="status_waiting" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','block');" {if (empty($draft) && 'waiting'==$mail_status_reply) || $draft->params.closed==2}checked="checked"{/if}>{'status.waiting'|devblocks_translate|capitalize}</label>
							{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->is_closed && !$ticket->is_deleted)}<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+C)"{/if}><input type="radio" name="closed" value="1" class="status_closed" onclick="toggleDiv('replyOpen{$message->id}','none');toggleDiv('replyClosed{$message->id}','block');" {if (empty($draft) && 'closed'==$mail_status_reply) || $draft->params.closed==1}checked="checked"{/if}>{'status.closed'|devblocks_translate|capitalize}</label>{/if}
							<br>
							
							<div id="replyClosed{$message->id}" style="display:{if (empty($draft) && 'open'==$mail_status_reply) || (!empty($draft) && $draft->params.closed==0)}none{else}block{/if};margin:10px 0px 0px 20px;">
							<b>{'display.reply.next.resume'|devblocks_translate}</b> {'display.reply.next.resume_eg'|devblocks_translate}<br> 
							<input type="text" name="ticket_reopen" size="55" value="{if !empty($draft)}{$draft->params.ticket_reopen}{elseif !empty($ticket->reopen_at)}{$ticket->reopen_at|devblocks_date}{/if}"><br>
							{'display.reply.next.resume_blank'|devblocks_translate}<br>
							</div>
							
							<div style="margin-bottom:10px;"></div>
							
							{if $active_worker->hasPriv('core.ticket.actions.move')}
							<b>{'display.reply.next.move'|devblocks_translate}</b>
							<br>
							
							<select name="group_id">
								{foreach from=$groups item=group key=group_id}
								<option value="{$group_id}" {if $active_worker->isGroupMember($group_id)}member="true"{/if} {if $ticket->group_id == $group_id}selected="selected"{/if}>{$group->name}</option>
								{/foreach}
							</select>
							<select class="ticket-reply-bucket-options" style="display:none;">
								{foreach from=$buckets item=bucket key=bucket_id}
								<option value="{$bucket_id}" group_id="{$bucket->group_id}">{$bucket->name}</option>
								{/foreach}
							</select>
							<select name="bucket_id">
								{foreach from=$buckets item=bucket key=bucket_id}
									{if $bucket->group_id == $ticket->group_id}
									<option value="{$bucket_id}" {if $ticket->bucket_id == $bucket_id}selected="selected"{/if}>{$bucket->name}</option>
									{/if}
								{/foreach}
							</select>
							<br>
							<br>
							{/if}
							
							<b>{'display.reply.next.owner'|devblocks_translate}</b><br>
							<select name="owner_id">
								<option value="">-- {'common.nobody'|devblocks_translate|lower} --</option>
								{foreach from=$workers item=owner key=owner_id}
								<option value="{$owner_id}" {if !empty($draft) && $draft->params.owner_id==$owner_id}selected="selected"{elseif $ticket->owner_id==$owner_id}selected="selected"{/if}>{$owner->getName()}</option>
								{/foreach}
							</select>
							<button type="button" onclick="$(this).prev('select[name=owner_id]').val('{$active_worker->id}');">{'common.me'|devblocks_translate|lower}</button>
							<button type="button" onclick="$(this).prevAll('select[name=owner_id]').first().val('');">{'common.nobody'|devblocks_translate|lower}</button>
							<br>
						</td>
					</tr>
				</table>
			</fieldset>
		</td>
	</tr>
	<tr>
		<td>
			<div id="replyCustomFields{$message->id}" class="reply-custom-fields">
			{if !empty($custom_fields)}
			<fieldset class="peek">
				<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>
				
				<table cellpadding="2" cellspacing="0" border="0">
					<tr>
						<td nowrap="nowrap" valign="top">
							{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
						</td>
					</tr>
				</table>
			</fieldset>
			{/if}
			
			{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id bulk=true}
			</div>
		</td>
	</tr>
	<tr>
		<td id="reply{$message->id}_buttons">
			<button type="button" class="send split-left" onclick="$(this).closest('td').find('ul li:first a').click();" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {if $is_forward}{'display.ui.forward'|devblocks_translate|capitalize}{else}{'display.ui.send_message'|devblocks_translate}{/if}</button><!--
			--><button type="button" class="split-right" onclick="$(this).next('ul').toggle();"><span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:white;"></span></button>
			<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
				<li><a href="javascript:;" class="send">{if $is_forward}{'display.ui.forward'|devblocks_translate}{else}{'display.ui.send_message'|devblocks_translate}{/if}</a></li>
				{if $active_worker->hasPriv('core.mail.save_without_sending')}<li><a href="javascript:;" class="save">{'display.ui.save_nosend'|devblocks_translate}</a></li>{/if}
				<li><a href="javascript:;" class="draft">{'display.ui.continue_later'|devblocks_translate}</a></li>
			</ul>
			<button type="button" class="discard" onclick="window.onbeforeunload=null;if(confirm('Are you sure you want to discard this reply?')) { if(null != draftAutoSaveInterval) { clearTimeout(draftAutoSaveInterval); draftAutoSaveInterval = null; } $frm = $(this).closest('form'); genericAjaxGet('', 'c=mail&a=handleSectionAction&section=drafts&action=deleteDraft&draft_id='+escape($frm.find('input:hidden[name=draft_id]').val()), function(o) { $frm = $('#reply{$message->id}_part2'); $('#draft'+escape($frm.find('input:hidden[name=draft_id]').val())).remove(); $('#reply{$message->id}').html('');  } ); }"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'display.ui.discard'|devblocks_translate|capitalize}</button>
		</td>
	</tr>
</table>
</form>

</div>

<script type="text/javascript">
	if(draftAutoSaveInterval == undefined)
		var draftAutoSaveInterval = null;
	
	$(function(e) {
		var $frm = $('#reply{$message->id}_part1');
		var $frm2 = $('#reply{$message->id}_part2');
		
		// Disable ENTER submission on the FORM text input
		$frm2
			.find('input:text')
			.keydown(function(e) {
				if(e.which == 13)
					e.preventDefault();
			})
			;
		
		// Autocompletes
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=bcc]', { multiple: true } );
		
		$frm.find('input:text').blur(function(event) {
			var name = event.target.name;
			
			if(0 == name.length)
				return;
			
			$('#reply{$message->id}_part2 input:hidden[name="'+name+'"]').val(event.target.value);
		} );
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#reply{$message->id}_suggested').appendTo($(this).closest('td'));
		});
		
		// Group and bucket
		
		$frm2.find('select[name=group_id]').on('change', function(e) {
			var $select = $(this);
			var group_id = $select.val();
			var $bucket_options = $select.siblings('select.ticket-reply-bucket-options').find('option')
			var $bucket = $select.siblings('select[name=bucket_id]');
			
			$bucket.children().remove();
			
			$bucket_options.each(function() {
				var parent_id = $(this).attr('group_id');
				if(parent_id == '*' || parent_id == group_id)
					$(this).clone().appendTo($bucket);
			});
			
			$bucket.focus();
		});
		
		var $content = $('#reply_{$message->id}');
		
		// Text editor
		
		var markitupPlaintextSettings = $.extend(true, { }, markitupPlaintextDefaults);
		var markitupParsedownSettings = $.extend(true, { }, markitupParsedownDefaults);
		
		var markitupReplyFunctions = {
			switchToMarkdown: function(markItUp) { 
				$content.markItUpRemove().markItUp(markitupParsedownSettings);
				{if !empty($mail_reply_textbox_size_auto)}
				$content.autosize();
				{/if}
				$content.closest('form').find('input:hidden[name=format]').val('parsedown');

				// Template chooser
				
				var $ul = $content.closest('.markItUpContainer').find('.markItUpHeader UL');
				var $li = $('<li style="margin-left:10px;"></li>');
				
				var $select = $('<select name="html_template_id"></select>');
				$select.append($('<option value="0"> - {'common.default'|devblocks_translate|lower|escape:'javascript'} -</option>'));
				
				{foreach from=$html_templates item=html_template}
				var $option = $('<option value="{$html_template->id}">{$html_template->name|escape:'javascript'}</option>');
				{if $draft && $draft->params.html_template_id == $html_template->id}
				$option.attr('selected', 'selected');
				{/if}
				$select.append($option);
				{/foreach}
				
				$li.append($select);
				$ul.append($li);
			},
			
			switchToPlaintext: function(markItUp) { 
				$content.markItUpRemove().markItUp(markitupPlaintextSettings);
				{if !empty($mail_reply_textbox_size_auto)}
				$content.autosize();
				{/if}
				$content.closest('form').find('input:hidden[name=format]').val('');
			}
		};
		
		markitupPlaintextSettings.markupSet.unshift(
			{ name:'Switch to Markdown', openWith: markitupReplyFunctions.switchToMarkdown, key: 'H', className:'parsedown' },
			{ separator:' ' },
			{ name:'Preview', key: 'P', call:'preview', className:"preview" }
		);
		
		markitupPlaintextSettings.previewParser = function(content) {
			genericAjaxPost(
				$frm2,
				'',
				'c=display&a=getReplyPreview',
				function(o) {
					content = o;
				},
				{
					async: false
				}
			);
			
			return content;
		};
		
		markitupPlaintextSettings.previewAutoRefresh = true;
		markitupPlaintextSettings.previewInWindow = 'width=800, height=600, titlebar=no, location=no, menubar=no, status=no, toolbar=no, resizable=yes, scrollbars=yes';
		
		markitupParsedownSettings.previewParser = function(content) {
			genericAjaxPost(
				$frm2,
				'',
				'c=display&a=getReplyMarkdownPreview',
				function(o) {
					content = o;
				},
				{
					async: false
				}
			);
			
			return content;
		};
		
		markitupParsedownSettings.markupSet.unshift(
			{ name:'Switch to Plaintext', openWith: markitupReplyFunctions.switchToPlaintext, key: 'H', className:'plaintext' },
			{ separator:' ' }
		);
		
		markitupParsedownSettings.markupSet.splice(
			6,
			0,
			{ name:'Upload an Image', openWith: 
				function(markItUp) {
					var $chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
					
					$chooser.one('chooser_save', function(event) {
						if(!event.response || 0 == event.response)
							return;
						
						$content.insertAtCursor("![inline-image](" + event.response[0].url + ")");
					});
				},
				key: 'U',
				className:'image-inline'
			}
			//{ separator:' ' }
		);
		
		try {
			$content.markItUp(markitupPlaintextSettings);
			
			{if ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}
			markitupReplyFunctions.switchToMarkdown();
			{/if}
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
		
		// @who and #command
		
		var atwho_file_bundles = {CerberusApplication::getFileBundleDictionaryJson() nofilter};
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};
		
		$content
			.atwho({
				at: '#attach ',
				{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${tag}</small></li>',{/literal}
				{literal}insertTpl: '#attach ${tag}\n',{/literal}
				suffix: '',
				data: atwho_file_bundles,
				limit: 10
			})
			.atwho({
				at: '#',
				data: [
					'attach ',
					'comment',
					'comment @',
					'cut\n',
					'delete_quote_from_here\n',
					'signature\n',
					'unwatch\n',
					'watch\n'
				],
				limit: 10,
				suffix: '',
				hide_without_suffix: true,
				callbacks: {
					before_insert: function(value, $li) {
						if(value.substr(-1) != '\n' && value.substr(-1) != '@')
							value += ' ';
						
						return value;
					}
				}
			})
			.atwho({
				at: '@',
				{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
				{literal}insertTpl: '@${at_mention}',{/literal}
				data: atwho_workers,
				searchKey: '_index',
				limit: 10
			})
			;
		
		$content.on('delete_quote_from_cursor', function(e) {
			var $this = $(this);
			var pos = $this.caret('pos');
			
			var lines = $this.val().split("\n");
			var txt = [];
			var is_removing = false;
			
			for(idx in lines) {
				var line = $.trim(lines[idx]);
				
				if(line == "#delete_quote_from_here") {
					is_removing = true;
					continue;
				}
				
				if(is_removing && !line.match(/^\>/) && !line.match(/^On .* wrote:/)) {
					is_removing = false;
				}
				
				if(!is_removing) {
					txt.push(line);
				}
			}

			$this.val(txt.join("\n"));
			$this.caret('pos', pos - "#delete quote from here\n".length);
		});
		
		$content.on('inserted.atwho', function(event, $li) {
			var txt = $.trim($li.text());
			if(txt == 'delete_quote_from_here')
				$(this).trigger('delete_quote_from_cursor');
		});
		
		// Elastic

		{if !empty($mail_reply_textbox_size_auto)}
		$content.autosize();
		{/if}
		
		$frm2.find('input[name=ticket_reopen]')
			.cerbDateInputHelper({
				submit: function(e) {
					$('#reply{$message->id}_buttons a.send').click();
				}
			})
			
		// Insert suggested on click
		
		$('#reply{$message->id}_suggested').find('a.suggested').click(function(e) {
			$this = $(this);
			$sug = $this.text();
			
			$to=$this.closest('td').find('input:text:first');
			$val=$to.val();
			$len=$val.length;
			
			$last = null;
			if($len>0)
				$last=$val.substring($len-1);
			
			if(0==$len || $last==' ')
				$to.val($val+$sug);
			else if($last==',')
				$to.val($val + ' '+$sug);
			else $to.val($val + ', '+$sug);
				$to.focus();
			
			$ul=$this.closest('ul');
			$this.closest('li').remove();
			if(0==$ul.find('li').length)
				$ul.closest('div').remove();
		});
		
		// Focus
		
		{if !$is_forward}
			$textarea = $frm2.find('textarea[name=content]');
			$textarea.focus();
			setElementSelRange($textarea.get(0), 0, 0);
		{else}
			$frm.find('input:text[name=to]').focus();
		{/if}
		
		// Reply action buttons
		
		var $buttons = $('#reply{$message->id}_buttons');
		
		$buttons.find('a.send').click(function() {
			if($('#reply{$message->id}_part1').validate().form()) {
				if(null != draftAutoSaveInterval) {
					clearTimeout(draftAutoSaveInterval);
					draftAutoSaveInterval = null;
				}
				
				var $frm = $(this).closest('form');
				$frm.find('input:hidden[name=reply_mode]').val('');
				$(this).closest('td').hide();
				showLoadingPanel();
				$frm.submit();
			}
		});
		
		$buttons.find('a.save').click(function() {
			if($('#reply{$message->id}_part1').validate().form()) {
				if(null != draftAutoSaveInterval) {
					clearTimeout(draftAutoSaveInterval);
					draftAutoSaveInterval = null;
				}
				
				var $frm = $(this).closest('form');
				$frm.find('input:hidden[name=reply_mode]').val('save');
				$(this).closest('td').hide();
				showLoadingPanel();
				$frm.submit();
			}
		});

		$buttons.find('a.draft').click(function() {
			if($('#reply{$message->id}_part1').validate().form()) {
				if(null != draftAutoSaveInterval) {
					clearTimeout(draftAutoSaveInterval);
					draftAutoSaveInterval = null;
				}
				
				var $frm = $(this).closest('form');
				$frm.find('input:hidden[name=a]').val('saveDraftReply');
				$(this).closest('td').hide();
				showLoadingPanel();
				$frm.submit();
			}
		});
		
		$frm.validate();
		
		// Draft
		
		$frm.find('button[name=saveDraft]')
			.click(function() {
				if($(this).attr('disabled'))
					return;
				
				$(this).attr('disabled','disabled');
				
				genericAjaxPost(
					'reply{$message->id}_part2',
					null,
					'c=display&a=saveDraftReply&is_ajax=1',
					function(json, ui) {
						var obj = $.parseJSON(json);
						
						if(null != obj.html && null != obj.draft_id) {
							$('#divDraftStatus{$message->id}').html(obj.html);
							$('#reply{$message->id}_part2 input[name=draft_id]').val(obj.draft_id);
						}
						
						$('#reply{$message->id}_part1 button[name=saveDraft]').removeAttr('disabled');
					}
				);
			})
			.click(); // save now
		
		$frm.find('button[name=saveDraft]').click(); // save now
		if(null != draftAutoSaveInterval) {
			clearTimeout(draftAutoSaveInterval);
			draftAutoSaveInterval = null;
		}
		draftAutoSaveInterval = setInterval("$('#reply{$message->id}_part1 button[name=saveDraft]').click();", 30000); // and every 30 sec

		$frm.find('input:text.context-snippet').autocomplete({
			source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=cerberusweb.contexts.snippet&contexts[]=cerberusweb.contexts.ticket&contexts[]=cerberusweb.contexts.worker',
			minLength: 1,
			focus:function(event, ui) {
				return false;
			},
			autoFocus:true,
			select:function(event, ui) {
				$this = $(this);
				$textarea = $('#reply_{$message->id}');
				
				var $label = ui.item.label.replace("<","&lt;").replace(">","&gt;");
				var $value = ui.item.value;
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				var url = 'c=internal&a=snippetPaste&id=' + $value;

				// Context-dependent arguments
				if('cerberusweb.contexts.ticket'==ui.item.context) {
					url += "&context_id={$ticket->id}";
				} else if ('cerberusweb.contexts.worker'==ui.item.context) {
					url += "&context_id={$active_worker->id}";
				}

				genericAjaxGet('',url,function(json) {
					// If the content has placeholders, use that popup instead
					if(json.has_custom_placeholders) {
						$textarea.focus();
						
						var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id),null,false,'600');
					
						$popup_paste.bind('snippet_paste', function(event) {
							if(null == event.text)
								return;
						
							$textarea.insertAtCursor(event.text).focus();
						});
						
					} else {
						$textarea.insertAtCursor(json.text).focus();
					}
					
				}, { async: false });

				$this.val('');
				return false;
			}
		});

		// Files
		$frm2.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// Menu
		$frm2.find('button.send')
			.siblings('ul.cerb-popupmenu')
			.hover(
				function(e) { }, 
				function(e) { $(this).hide(); }
			)
			.find('> li')
			.click(function(e) {
				$(this).closest('ul.cerb-popupmenu').hide();
				
				e.stopPropagation();
				if(!$(e.target).is('li'))
				return;
				
				$(this).find('a').trigger('click');
			})
		;
		
		// Shortcuts
		
		{if $pref_keyboard_shortcuts}
		
		// Reply textbox
		$('#reply_{$message->id}').keydown(function(event) {
			if(!$(this).is(':focus'))
				return;
			
			if(!event.shiftKey || !event.ctrlKey)
				return;
			
			if(event.which == 16 || event.which == 17)
				return;
			
			switch(event.which) {
				case 13: // (RETURN) Send message
					try {
						event.preventDefault();
						$('#reply{$message->id}_buttons a.send').click();
					} catch(ex) { } 
					break;
				case 67: // (C) Set closed + focus reopen
				case 79: // (O) Set open
				case 87: // (W) Set waiting + focus reopen
					try {
						event.preventDefault();
						
						var $reply_status = $('#replyStatus{$message->id}');
						var $radio = $reply_status.find('input:radio[name=closed]');
						
						switch(event.which) {
							case 67: // closed
								$radio.filter('.status_closed').click();
								$reply_status
									.find('input:text[name=ticket_reopen]')
										.select()
										.focus()
									;
								break;
							case 79: // open
								$radio.filter('.status_open').click();
								$reply_status
									.find('select[name=group_id]')
										.select()
										.focus()
									;
								break;
							case 87: // waiting
								$radio.filter('.status_waiting').click();
								$reply_status
									.find('input:text[name=ticket_reopen]')
										.select()
										.focus()
									;
								break;
						}
						
					} catch(ex) {}
					break;
				case 71: // (G) Insert Signature
					try {
						event.preventDefault();
						$('#btnInsertReplySig{$message->id}').click();
					} catch(ex) { } 
					break;
				case 73: // (I) Insert Snippet
					try {
						event.preventDefault();
						$('#reply{$message->id}_part1').find('.context-snippet').focus();
					} catch(ex) { } 
					break;
				case 66: // (B) Insert Behavior
					try {
						event.preventDefault();
						$('#btnReplyMacros{$message->id}').click();
					} catch(ex) { } 
					break;
				case 74: // (J) Jump to first blank line
					try {
						event.preventDefault();
						var txt = $(this).val();
						var pos = txt.indexOf("\n\n")+2;
						$(this).setCursorLocation(pos).focus();
					} catch(ex) { } 
					break;
				case 81: // (Q) Reformat quotes
					try {
						event.preventDefault();
						var txt = $(this).val();
						
						var lines = txt.split("\n");
						
						var bins = [];
						var last_prefix = null;
						var wrap_to = 76;
						
						// Sort lines into bins
						for(i in lines) {
							var line = lines[i];
							var matches = line.match(/^((\> )+)/);
							var prefix = '';
							
							if(matches)
								prefix = matches[1];
							
							if(prefix != last_prefix)
								bins.push({ prefix:prefix, lines:[] });
							
							// Strip the prefix
							line = line.substring(prefix.length);
							
							idx = Math.max(bins.length-1, 0);
							bins[idx].lines.push(line);
							
							last_prefix = prefix;
						}
						
						// Rewrap quoted blocks
						for(i in bins) {
							prefix = bins[i].prefix;
							l = 0;
							bail = 25000; // prevent infinite loops
							
							if(prefix.length == 0)
								continue;
							
							while(undefined != bins[i].lines[l] && bail > 0) {
								line = bins[i].lines[l];
								boundary = Math.max(0, wrap_to-prefix.length);
								
								if(line.length > 0 && boundary > 0 && line.length > boundary) {
									// Try to split on a space
									pos = line.lastIndexOf(' ', boundary);
									break_word = (-1 == pos);
									
									overflow = line.substring(break_word ? boundary : (pos+1));
									bins[i].lines[l] = line.substring(0, break_word ? boundary : pos);
									
									// If we don't have more lines, add a new one
									if(overflow) {
										if(undefined != bins[i].lines[l+1]) {
											if(bins[i].lines[l+1].length == 0) {
												bins[i].lines.splice(l+1,0,overflow);
											} else {
												bins[i].lines[l+1] = overflow + " " + bins[i].lines[l+1];
											}
										} else {
											bins[i].lines.push(overflow);
										}
									}
								}
								
								l++;
								bail--;
							}
						}
						
						out = "";
						
						for(i in bins) {
							for(l in bins[i].lines) {
								out += bins[i].prefix + bins[i].lines[l] + "\n";
							}
						}
						
						$(this).val($.trim(out));
						
					} catch(ex) { }
					break;
			}
		});
		
		{/if}
		
		{* Run custom jQuery scripts from VA behavior *}
		
		{if !empty($jquery_scripts)}
		$('#reply{$message->id}_part1').closest('div.reply_frame').each(function(e) {
			{foreach from=$jquery_scripts item=jquery_script}
			try {
				{$jquery_script nofilter}
			} catch(e) { }
			{/foreach}
		});
		{/if}
	});
</script>

<script type="text/javascript">
{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl" selector_menu="#menuReplyMacros{$message->id}" selector_button="#btnReplyMacros{$message->id}"}
</script>