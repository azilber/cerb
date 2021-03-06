<div id="draft{$draft->id}">
	<div class="block">
		{$draft_worker = $workers.{$draft->worker_id}}
		
		{if $draft->is_queued}
			{if !empty($draft->queue_delivery_date) && $draft->queue_delivery_date > time()}
				<span class="tag" style="color:rgb(120,120,120);">{'message.queued.deliver_in'|devblocks_translate:{$draft->queue_delivery_date|devblocks_prettytime}|lower}</span>
			{else}
				<span class="tag" style="color:rgb(120,120,120);">{'message.queued.delivery_immediate'|devblocks_translate|lower}</span>
			{/if}
		{else}
			<span class="tag" style="color:rgb(120,120,120);">{'draft'|devblocks_translate|lower}</span>
		{/if}
		
		<h3 style="display:inline;">
			{if !empty($draft_worker)}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&email={$draft_worker->email|escape:'url'}', null, false, '500');" title="{$worker->email}">{$draft_worker->getName()}</a>{else}{/if}
		</h3> &nbsp;
		
		{if !$draft->is_queued}
			{if $draft->worker_id==$active_worker->id && isset($draft->params.in_reply_message_id)}<a href="javascript:;" onclick="displayReply('{$draft->params.in_reply_message_id}',{if $draft->type=='ticket.forward'}1{else}0{/if},{$draft_id});">{'Resume'|devblocks_translate|lower}</a>&nbsp;{/if}
		{/if}
		{if $draft->worker_id==$active_worker->id || $active_worker->hasPriv('core.mail.draft.delete_all')}<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this draft?')) { genericAjaxGet('', 'c=mail&a=handleSectionAction&section=drafts&action=deleteDraft&draft_id={$draft_id}', function(o) { $('#draft{$draft_id}').remove(); } ); } ">{'common.delete'|devblocks_translate|lower}</a>&nbsp;{/if}		
		<br>
		
		{if isset($draft->hint_to)}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$draft->hint_to}<br>{/if}
		{if isset($draft->params.cc)}<b>{'message.header.cc'|devblocks_translate|capitalize}:</b> {$draft->params.cc}<br>{/if}
		{if isset($draft->params.bcc)}<b>{'message.header.bcc'|devblocks_translate|capitalize}:</b> {$draft->params.bcc}<br>{/if}
		{if isset($draft->subject)}<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> {$draft->subject}<br>{/if}
		{if !empty($draft->queue_delivery_date)}
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->queue_delivery_date|devblocks_date}<br>
		{elseif !empty($draft->updated)}
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->updated|devblocks_date}<br>
		{/if}
		<pre class="emailbody" style="padding-top:10px;">{$draft->body|trim|escape|devblocks_hyperlinks nofilter}</pre>
	</div>
	<br>
</div>

