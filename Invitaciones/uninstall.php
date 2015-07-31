<?php
// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
	
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

global $smcFunc;

$columns = array(
	'invitePrune',
	'keyExpire',
	'keyRenew',
);

foreach ($columns as $col)
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}scheduled_tasks
		WHERE task = {string:col}',
		array(
			'col' => $col
		)
	);
?>