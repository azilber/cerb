<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Convert `custom_field.options` to `params_json`

if(!isset($tables['custom_field'])) {
	$logger->error("The 'custom_field' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('custom_field');

if(!isset($columns['params_json'])) {
	$db->Execute("ALTER TABLE custom_field ADD COLUMN params_json TEXT AFTER pos");
	
	$results = $db->GetArray("SELECT id, options FROM custom_field WHERE options != ''");
	
	foreach($results as $result) {
		$params = array(
			'options' => DevblocksPlatform::parseCrlfString($result['options'])
		);
		
		// Migrate the `options` field on `custom_field` to `params_json`
		$db->Execute(sprintf("UPDATE custom_field SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($params)),
			$result['id']
		));
	}
}

// Drop the `options` field on `custom_field`
if(isset($columns['options'])) {
	$db->Execute("ALTER TABLE custom_field DROP COLUMN options");
}

// ===========================================================================
// Add `attachment.storage_sha1hash`

if(!isset($tables['attachment'])) {
	$logger->error("The 'attachment' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('attachment');

if(!isset($columns['storage_sha1hash'])) {
	$db->Execute("ALTER TABLE attachment ADD COLUMN storage_sha1hash VARCHAR(40) DEFAULT '', ADD INDEX storage_sha1hash (storage_sha1hash(4))");
}

// ===========================================================================
// Fix S3 namespace prefixes in storage keys

$db->Execute("UPDATE attachment SET storage_key = REPLACE(storage_key, 'attachments/', '') WHERE storage_extension = 'devblocks.storage.engine.s3'");
$db->Execute("UPDATE message SET storage_key = REPLACE(storage_key, 'message_content/', '') WHERE storage_extension = 'devblocks.storage.engine.s3'");

// ===========================================================================
// mail_html_template

if(!isset($tables['mail_html_template'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS mail_html_template (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			owner_context varchar(128) NOT NULL DEFAULT '',
			owner_context_id int(11) NOT NULL DEFAULT '0',
			content mediumtext,
			PRIMARY KEY (id),
			INDEX owner (owner_context, owner_context_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['mail_html_template'] = 'mail_html_template';
}

// ===========================================================================
// Add HTML template support to groups

if(!isset($tables['worker_group'])) {
	$logger->error("The 'worker_group' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_group');

if(!isset($columns['reply_html_template_id'])) {
	$db->Execute("ALTER TABLE worker_group ADD COLUMN reply_html_template_id INT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Add HTML template support to buckets

if(!isset($tables['bucket'])) {
	$logger->error("The 'bucket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('bucket');

if(!isset($columns['reply_html_template_id'])) {
	$db->Execute("ALTER TABLE bucket ADD COLUMN reply_html_template_id INT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Finish up

return TRUE;