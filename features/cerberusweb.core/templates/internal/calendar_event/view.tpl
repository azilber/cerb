{$view_context = CerberusContexts::CONTEXT_CALENDAR_EVENT}
{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$view_context}&context_id=0&view_id={$view->id}',null,false,'600');"><span class="glyphicons glyphicons-circle-plus"></span></a>
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

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="cerberusweb.contexts.calendar_event">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
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
	{*{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_CALENDAR_EVENT, array_keys($data), CerberusContexts::CONTEXT_WORKER)}*}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.c_id}" style="display:none;">
				<a href="{devblocks_url}c=profiles&a=calendar_event&id={$result.c_name|devblocks_permalink}-{$result.c_id}{/devblocks_url}" class="subject">{if !empty($result.c_name)}{$result.c_name}{else}New Event{/if}</a> 
				<button type="button" class="peek" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_CALENDAR_EVENT}&context_id={$result.c_id}&view_id={$view->id}',null,false,'600');"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="c_date_start" || $column=="c_date_end"}
				<td title="{$result.$column|devblocks_date}">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&nbsp;
					{/if}
				</td>
			{elseif $column=="c_calendar_id"}
				<td>
					{$calendar_id = $result.c_calendar_id}
					{if isset($calendars.$calendar_id)}
						<a href="{devblocks_url}c=profiles&what=calendar&id={$calendar_id}-{$calendars.$calendar_id->name|devblocks_permalink}{/devblocks_url}">{$calendars.$calendar_id->name}</a>
					{/if}
				</td>
			{elseif $column=="c_is_available"}
				<td>
					{if $result.$column}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if}
				</td>
			{elseif $column=='*_owner'}
				<td>
					{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
					{$worker_id = {$result.c_owner_context_id}}
					{if isset($workers.$worker_id)}
						{$workers.{$worker_id}->getName()}
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
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
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}