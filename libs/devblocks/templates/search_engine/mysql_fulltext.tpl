<div style="padding:5px 10px;">
	(Default) Records are fulltext indexed in MyISAM tables in Cerb's existing MySQL database. 
	Tables are prefixed with <tt>fulltext_*</tt>. No special configuration is required.  This option 
	provides reasonable performance in most situations, but high volume environments should 
	consider using a specialized search engine like Sphinx instead.
</div>

<div style="padding:5px 10px;">
	<b>Max results:</b> <i>(blank for unlimited)</i>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][max_results]" value="{$engine_params.max_results}" size="45" style="width:100%;" placeholder="unlimited">
	</p>
</div>