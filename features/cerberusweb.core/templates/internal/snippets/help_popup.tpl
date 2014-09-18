<h1 style="margin-bottom:10px;color:inherit;">Placeholders</h1>

<fieldset class="peek">
	<legend>Placeholders</legend>
	
	{literal}
	<p>
		A <b><tt>{{placeholder}}</tt></b> will be automatically replaced with a fragment of text from a record. 
		Placeholders are surrounded by two pairs of curly braces.
	</p>
	{/literal}

	<p>
		For example, the placeholder text "Hi {literal}<b><tt>{{first_name}}</tt></b>{/literal}" would become "Hi {$active_worker->first_name}".
	</p>
</fieldset>

<fieldset class="peek">
	<legend>Filters</legend>
	
	{literal}
	<p>
		The value of a placeholder may be modified using <i>filters</i>. 
		Some filters also have parameters that modify their behavior, which are provided in parentheses.
		The possible filters will be automatically suggested when you append the pipe character (<b><tt>|</tt></b>) to a placeholder name. 
	</p>
	{/literal}
	
	<p>
		For example, "Hi {literal}<b><tt>{{first_name|upper}}</tt></b>{/literal}" would become "Hi {$active_worker->first_name|upper}".
	</p>
</fieldset>

<fieldset class="peek">
	<legend>Default values</legend>
	
	<p>
		You can use the <b><tt>|default</tt></b> filter to give a default value to empty placeholders.
	</p>
	
	{literal}
	<pre style="margin:0.5em 1em;">
{% set name = '' %}
Hi {{name|default('there')}}
</pre>
{/literal}
</fieldset>

<h1 style="margin-bottom:10px;color:inherit;">Scripting</h1>

<fieldset class="peek">
	<legend>Variables</legend>
	
	You can set temporary variables and use them as placeholders:
	
	<pre style="margin:0.5em 1em;">
{literal}{%{/literal} set name = '{$active_worker->first_name}' {literal}%}{/literal}
{literal}{{name}}{/literal}</pre>
</fieldset>

<fieldset class="peek">
	<legend>Conditional Logic</legend>
	
	Conditional logic can display different content based on the value of a placeholder:
	
	{literal}
	<pre style="margin:0.5em 1em;">
{% set sla_expiration = '+2 weeks'|date('U') %}
{% if sla_expiration >= 'now'|date('U') %}
Your SLA coverage is active.
{% else %}
Your SLA coverage has expired.
{% endif %}
</pre>
	{/literal}
</fieldset>

<fieldset class="peek">
	<legend>Loops</legend>
	
	If a placeholder value is a list (array), then it can be iterated in a loop:
	
	{literal}
	<pre style="margin:0.5em 1em;">
{% set list_of_names = ["Jeff", "Dan", "Darren"] %}
{% for name in list_of_names %}
{{name}}
{% endfor %}
</pre>
	{/literal}
	
</fieldset>

{literal}
<fieldset class="peek">
	<legend>Operators</legend>
	
	<pre style="margin:0.5em 1em;">
{% set this = 0 %}
{% set that = 1 %}
{% set those = [1,2,3] %}

{% if this == that %}
{{this}} equals {{that}}
{% endif -%}

{% if this != that %}
{{this}} doesn't equal {{that}}
{% endif -%}

{% if this < that %}
{{this}} is less than {{that}}
{% endif -%}

{% if this > that %}
{{this}} is greater than {{that}}
{% endif -%}

{% if that in those %}
{{that}} is in {{those|join(',')}}
{% endif -%}

{% if this not in those %}
{{this}} is not in {{those|join(',')}}
{% endif -%}</pre>

</fieldset>
{/literal}

<fieldset class="peek">
	<legend>Concatenation</legend>
	
	<pre style="margin:0.5em 1em;">
{literal}{%{/literal} set first_name = "{$active_worker->first_name}" {literal}%}{/literal}
{literal}{%{/literal} set last_name = "{$active_worker->last_name}" {literal}%}{/literal}
{literal}{% set full_name = first_name ~ ' ' ~ last_name %}{/literal}
{literal}{{full_name}}{/literal}
</pre>

</fieldset>

{literal}
<fieldset class="peek">
	<legend>Whitespace</legend>

	You can ignore whitespace at the beginning or end of a tag with a dash (<tt><b>-</b></tt>):
	
	<pre style="margin:0.5em 1em;">
This text

{{-" has no leading or trailing whitespace "-}}

in it.
</pre>
{/literal}

</fieldset>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('help');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Help: Placeholders \x26 Scripting");
	});
</script>
