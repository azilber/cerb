<form action="#" method="POST" id="formAddressPeek" name="formAddressPeek" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveAddress">
<input type="hidden" name="id" value="{$address.a_id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.email'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if $id == 0}
					{if !empty($email)}
						<input type="hidden" name="email" value="{$email}">
						<b>{$email}</b>
					{else}
						<input type="text" id="formAddressPeek_email" name="email" style="width:98%;" value="{$email}" class="required email">
					{/if}
				{else}
					<b>{$address.a_email}</b>
					{$email_parts = explode('@',$address.a_email)}
					{if is_array($email_parts) && 2==count($email_parts)}
						{$domain = $email_parts.1}
						(<a href="http://www.{$domain}" target="_blank" title="www.{$email_parts.1}">{'contact_org.website'|devblocks_translate|lower}</a>) 
						(<a href="{devblocks_url}c=contacts&a=findAddresses{/devblocks_url}?email={'*@'|cat:$domain}" target="_blank">similar</a>)
					{/if}
				{/if}
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'address.first_name'|devblocks_translate|capitalize}: </td>
			<td width="100%"><input type="text" name="first_name" value="{$address.a_first_name}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'address.last_name'|devblocks_translate|capitalize}: </td>
			<td width="100%"><input type="text" name="last_name" value="{$address.a_last_name}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right" valign="top">{'contact_org.name'|devblocks_translate|capitalize}: </td>
			<td width="100%" valign="top">
				{if !empty($address.a_contact_org_id)}
					<b>{if !empty($address.o_name)}{$address.o_name}{else if !empty({$org_name})}{$org_name}{/if}</b>
					<a href="javascript:;" onclick="genericAjaxPopup('peek_org','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ORG}&context_id={if !empty($address.a_contact_org_id)}{$address.a_contact_org_id}{else}{$org_id}{/if}&view_id={$view->id}',null,false,'600');">{'views.peek'|devblocks_translate}</a>
					<a href="javascript:;" onclick="toggleDiv('divAddressOrg');">({'common.edit'|devblocks_translate|lower})</a>
					<br>
				{/if}
				<div id="divAddressOrg" style="display:{if empty($address.a_contact_org_id)}block{else}none{/if};">
					<input type="text" name="contact_org" id="contactinput" style="width:98%;" value="{if !empty($address.a_contact_org_id)}{$address.o_name}{else}{$org_name}{/if}">
				</div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.options'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<label><input type="checkbox" name="is_banned" value="1" title="Check this box if new messages from this email address should be rejected." {if $address.a_is_banned}checked="checked"{/if}> {'address.is_banned'|devblocks_translate|capitalize}</label>
				<label><input type="checkbox" name="is_defunct" value="1" title="Check this box if the email address is no longer active." {if $address.a_is_defunct}checked="checked"{/if}> {'address.is_defunct'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if empty($id)}
					<button type="button" class="chooser_watcher"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_ADDRESS, array($address.a_id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_ADDRESS context_id=$address.a_id full=true}
				{/if}
			</td>
		</tr>
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ADDRESS context_id=$address.a_id}

{* Comment *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="if($('#formAddressPeek').validate().form()) { genericAjaxPopupPostCloseReloadView(null,'formAddressPeek', '{$view_id}', false, 'address_save'); } "><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}

{if $id != 0}
	&nbsp; 
	<a href="{devblocks_url}c=contacts&a=findTickets{/devblocks_url}?email={$address.a_email}&closed=0">{'addy_book.peek.count.open_tickets'|devblocks_translate:$open_count}</a> &nbsp; 
	<a href="{devblocks_url}c=contacts&a=findTickets{/devblocks_url}?email={$address.a_email}&closed=1">{'addy_book.peek.count.closed_tickets'|devblocks_translate:$closed_count}</a> &nbsp; 
	{if $active_worker->hasPriv('core.mail.send')}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id=0&view_id=&to={$address.a_email|escape:'url'}',null,false,'650');"> {'addy_book.peek.compose'|devblocks_translate}</a> &nbsp; {/if}
	<a href="{devblocks_url}c=profiles&type=address&id={$address.a_id}-{$address.a_email|devblocks_permalink}{/devblocks_url}">full record</a> &nbsp; 
{/if}

<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formAddressPeek');
	
	$popup.one('popup_open',function(event,ui) {
		var $this = $(this);
		var $textarea = $this.find('textarea[name=comment]');
		
		// Title
		$this.dialog('option','title', '{'addy_book.peek.title'|devblocks_translate|escape:'javascript' nofilter}');
		
		// Form hints
		
		$textarea
			.focusin(function() {
				$(this).siblings('div.cerb-form-hint').fadeIn();
			})
			.focusout(function() {
				$(this).siblings('div.cerb-form-hint').fadeOut();
			})
			;
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});
		
		// Worker chooser
		$this.find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});

		// Autocomplete
		ajax.orgAutoComplete('#contactinput');
		
		// Form validation
		$("#formAddressPeek").validate();
		$('#formAddressPeek :input:text:first').focus();
		
		$this.find('button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
	});
});
</script>