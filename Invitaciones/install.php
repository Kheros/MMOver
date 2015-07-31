<?php
// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
	
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

global $smcFunc;

$invite = array(
	'table' => '{db_prefix}invites',
	'columns' => array(
		0 => array(
				'name' => 'invite_id',
				'type' => 'int',
				'size' => 4,
				'unsigned' => true,
				'null' => false,
				'auto' => true,
		),
		1 => array(
				'name' => 'key',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
				'default' => '',
		),
		2 => array(
				'name' => 'active',
				'type' => 'tinyint',
				'size' => 1,
				'unsigned' => true,
				'null' => false,
		),
		3 => array(
				'name' => 'member_id',
				'type' => 'tinyint',
				'size' => 3,
				'unsigned' => true,
				'null' => false,
		),
		4 => array(
				'name' => 'member_name',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
				'default' => '',
		),
		5 => array(
				'name' => 'time',
				'type' => 'int',
				'size' => 10,
				'unsigned' => true,
				'null' => false,
				'default' => 0,
		),
	),
	'indexes' => array(
		0 => array(
				'type' => 'primary',
				'columns' => array('invite_id')
		),
	)
);

db_extend('packages');

$smcFunc['db_create_table']($invite['table'], $invite['columns'], $invite['indexes'], array(), 'ignore');

$request = $smcFunc['db_query']('', '
	DESCRIBE {db_prefix}members invite_count',
	array()
);

if($smcFunc['db_num_rows']($request) == 0)
{
	$smcFunc['db_free_result']($request);
		
	$table = '{db_prefix}members';
	$invites_column = array(
		1 => array(
			'name' => 'invite_count',
			'type' => 'int',
			'size' => 5,
			'null' => false,
			'default' => 0,
			'unsigned' => true,
		),
		2 => array(
			'name' => 'invite_max',
			'type' => 'int',
			'size' => 5,
			'null' => false,
			'default' => 5,
			'unsigned' => false,
		),
		3 => array(
			'name' => 'invite_roll_max',
			'type' => 'int',
			'size' => 5,
			'null' => false,
			'default' => 5,
			'unsigned' => false,
		),
	);
	
	foreach ($invites_column as $column)
		$smcFunc['db_add_column']($table, $column);

	$smcFunc['db_query']('',"
		ALTER IGNORE TABLE {db_prefix}members
		CHANGE invite_max invite_max int(5) NOT NULL default '5'");
	$smcFunc['db_query']('',"
		ALTER IGNORE TABLE {db_prefix}members
		CHANGE invite_roll_max invite_roll_max int(5) NOT NULL default '5'");
}else
	$smcFunc['db_free_result']($request);

$request = $smcFunc['db_query']('', '
	DESCRIBE {db_prefix}membergroups max_invites',
	array()
);

if($smcFunc['db_num_rows']($request) == 0)
{
	$smcFunc['db_free_result']($request);
		
	$table = '{db_prefix}membergroups';
	$invites_column = array(
		'name' => 'max_invites',
		'type' => 'int',
		'size' => 5,
		'null' => false,
		'default' => 5,
		'unsigned' => false,
	);
	
	$smcFunc['db_add_column']($table, $invites_column);
	$smcFunc['db_query']('',"
		ALTER IGNORE TABLE {db_prefix}membergroups
		CHANGE max_invites max_invites int(5) NOT NULL default '5'");
}else
	$smcFunc['db_free_result']($request);
		
$mod_settings = array(
	'invite_enabled' => '1',
	'key_expire' => '15',
	'roll_over' => '1',
	'key_renew' => '30',
	'invite_email' => '1',
	'invite_email_subject' => 'You have received and invite to join {forum}!',
	'invite_email_message' => 'Hello {invitee},
		
	You have received an invitation to join {forum} from {inviter}. Here is what he or she had to say:
		
	{message}
		
	Use this key
	{key}
	to register at
	{link}
		
	Regards,
	{forum} staff.',
);

foreach ($mod_settings as $new_setting => $new_value)
	if (empty($modSettings[$new_setting]))
		updateSettings(array($new_setting => $new_value));
		
$mod_settings_up = array(
	'invite_enabled' => '0',
	'roll_over' => '0',
	'invite_email' => '0',
);

	foreach ($mod_settings_up as $new_setting => $new_value)
		updateSettings(array($new_setting => $new_value));

$request = $smcFunc['db_query']('', '
	SELECT task
	FROM {db_prefix}scheduled_tasks
	WHERE task = {string:tsk}
	LIMIT 1',
	array(
		'tsk' => 'invitePrune'
	)
);

if(!$smcFunc['db_num_rows']($request))
{
	$smcFunc['db_free_result']($request);
		
	$smcFunc['db_insert']('insert',
		'{db_prefix}scheduled_tasks',
		array('next_time' => 'int', 'time_offset' => 'int', 'time_regularity' => 'int', 'time_unit' => 'string', 'disabled' => 'int', 'task' => 'string'),
		array(
			array(0, 0, 7, 'd', 0, 'invitePrune'),
			array(0, 0, 15, 'd', 0, 'keyExpire'),
			array(0, 0, 30, 'd', 0, 'keyRenew'),
		),
		array('id_task')
	);
}else
	$smcFunc['db_free_result']($request);
?>