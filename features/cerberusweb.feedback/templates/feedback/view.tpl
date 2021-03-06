{$view_context = CerberusContexts::CONTEXT_FEEDBACK}
{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$view_context}&context_id=0&view_id={$view->id}',null,false,'500');"><span class="glyphicons glyphicons-circle-plus"></span></a>
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=viewShowQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			<a href="javascript:;" title="{'common.export'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>
			<a href="javascript:;" title="{'common.copy'|devblocks_translate|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="#">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="feedback">
<input type="hidden" name="a" value="">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{if !$view->options.disable_watchers}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.watchers'|devblocks_translate|capitalize}">
			<span class="glyphicons glyphicons-eye-open" style="color:rgb(80,80,80);"></span>
		</th>
		{/if}

		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if $view->options.disable_sorting}no-sort{/if}">
			{if !$view->options.disable_sorting && !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	
	{assign var=worker_id value=$result.f_worker_id}
	{assign var=mood value=$result.f_quote_mood}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center" valign="top" rowspan="2" nowrap="nowrap" style="padding:5px;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.f_id}
			</td>
			{foreach from=$view->view_columns item=column name=columns}
				{if substr($column,0,3)=="cf_"}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
				{elseif $column=="f_id"}
					<td>{$result.f_id}&nbsp;</td>
				{elseif $column=="a_email"}
					<td>
						{if !empty($result.a_email)}
							<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$result.f_quote_address_id}&view_id={$view->id}',null,false,'500');">{$result.a_email}</a>
						{else}
							<i>{'common.anonymous'|devblocks_translate|lower}</i>
						{/if}
					</td>
				{elseif $column=="f_log_date"}
					<td title="{$result.f_log_date|devblocks_date}">{$result.f_log_date|devblocks_prettytime}&nbsp;</td>
				{elseif $column=="f_worker_id"}
					<td>{if isset($workers.$worker_id)}{$workers.$worker_id->getName()}{/if}&nbsp;</td>
				{elseif $column=="f_quote_mood"}
					<td>
						{if 1==$result.$column}
							<span style="background-color:rgb(235, 255, 235);color:rgb(0, 180, 0);font-weight:bold;">{'feedback.mood.praise'|devblocks_translate}</span>
						{elseif 2==$result.$column}
							<span style="background-color: rgb(255, 235, 235);color: rgb(180, 0, 0);font-weight:bold;">{'feedback.mood.criticism'|devblocks_translate}</span>
						{else}
							{'feedback.mood.neutral'|devblocks_translate}
						{/if}
					</td>
				{elseif $column=="f_source_url"}
					<td><a href="{$result.f_source_url}" target="_blank" title="{$result.f_source_url}">{$result.f_source_url|truncate:35:'...':true:false}</a>&nbsp;</td>
				{else}
					<td>{$result.$column}</td>
				{/if}
			{/foreach}
		</tr>
		<tr class="{$tableRowClass}">
			<td colspan="{$smarty.foreach.headers.total}">
				<div id="subject_{$result.f_id}_{$view->id}" style="margin:5px;margin-left:10px;font-size:12px;">
					<input type="checkbox" name="row_id[]" value="{$result.f_id}" style="display:none;">
					<img src="{devblocks_url}c=resource&p=cerberusweb.feedback&f=images/{if 1==$mood}bullet_ball_glass_green.png{elseif 2==$mood}bullet_ball_glass_red.png{else}bullet_ball_glass_grey.png{/if}{/devblocks_url}" align="top" title="{if 1==$mood}Praise{elseif 2==$mood}Criticism{else}Neutral{/if}"> 
					{$result.f_quote_text} 
					{if ($active_worker->hasPriv('feedback.actions.create') && $result.f_worker_id==$active_worker->id) || $active_worker->hasPriv('feedback.actions.update_all')}
						<button type="button" class="peek" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_FEEDBACK}&context_id={$result.f_id}&view_id={$view->id}',null,false,'550');"><span class="glyphicons glyphicons-new-window-alt"></span></button>
					{/if}
				</div>
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

<div style="padding-top:5px;">
	<div style="float:right;">
		{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
		{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
		{math assign=nextPage equation="x+1" x=$view->renderPage}
		{math assign=prevPage equation="x-1" x=$view->renderPage}
		{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
		
		{* Sanity checks *}
		{if $toRow > $total}{assign var=toRow value=$total}{/if}
		{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
		
		{if $view->renderPage > 0}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{'common.next'|devblocks_translate|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
		{if $active_worker->hasPriv('feedback.actions.update_all')}<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=feedback&a=showBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="glyphicons glyphicons-folder-closed"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$frm = $('#viewForm{$view->id}');

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);
	
	$view_actions = $('#{$view->id}_actions');
	
	hotkey_activated = true;

	switch(event.keypress_event.which) {
		case 98: // (b) bulk update
			$btn = $view_actions.find('button.action-bulkupdate');
		
			if(event.indirect) {
				$btn.select().focus();
				
			} else {
				$btn.click();
			}
			break;
		
		default:
			hotkey_activated = false;
			break;
	}

	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>