{if $active_worker->hasPriv('timetracking.actions.create')}
<button type="button" onclick="timeTrackingTimer.play('cerberusweb.contexts.ticket','{$message->ticket_id}');" title="{'timetracking.ui.button.track'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-stopwatch"></span></button>
{/if}