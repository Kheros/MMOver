<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 4.0.3 - Licence Number VBF1Y7SPM3
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2010 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array();
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_sitemap.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('cansitemap'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['xml_sitemap_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'menu';
}

// ########################################################################
if ($_REQUEST['do'] == 'menu')
{
	$options = array('forum' => $vbphrase['forum']);

	print_form_header('sitemap');
	print_table_header($vbphrase['sitemap_priority_manager']);
	print_select_row($vbphrase['manage_priority_for_content_type'], 'do', $options);
	print_submit_row($vbphrase['manage'], null);
}

// ########################################################################
if ($_POST['do'] == 'saveforum')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'f' => TYPE_ARRAY_STR
	));

	// Custom values to remove
	$update_values = array();

	foreach ($vbulletin->GPC['f'] AS $forumid => $priority)
	{
		if ($priority == 'default')
		{
			$vbulletin->db->query("
				DELETE FROM " . TABLE_PREFIX . "contentpriority
				WHERE contenttypeid = 'forum' AND sourceid = " . intval($forumid)
			);
		}
		else
		{
			$update_values[] = "('forum', " . intval($forumid) . "," . floatval($priority) . ")";
		}
	}

	// If there are any with custom values, set them
	if (count($update_values))
	{
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "contentpriority
				(contenttypeid, sourceid, prioritylevel)
			VALUES
				" . implode(',', $update_values)
		);
	}

	define('CP_REDIRECT', 'sitemap.php?do=forum');
	print_stop_message('saved_content_priority_successfully');
}

// ########################################################################
if ($_REQUEST['do'] == 'forum')
{
	// Default priority settings, with clear
	$default_settings = array(
		'default' => $vbphrase['default'],
		'0.0' => vb_number_format('0.0', 1),
		'0.1' => vb_number_format('0.1', 1),
		'0.2' => vb_number_format('0.2', 1),
		'0.3' => vb_number_format('0.3', 1),
		'0.4' => vb_number_format('0.4', 1),
		'0.5' => vb_number_format('0.5', 1),
		'0.6' => vb_number_format('0.6', 1),
		'0.7' => vb_number_format('0.7', 1),
		'0.8' => vb_number_format('0.8', 1),
		'0.9' => vb_number_format('0.9', 1),
		'1.0' => vb_number_format('1.0', 1),
	);

	// Get the custom forum priorities
	$sitemap = new vB_SiteMap_Forum($vbulletin);

	print_form_header('sitemap', 'saveforum');
	print_table_header($vbphrase['forum_priority_manager']);
	print_description_row($vbphrase['sitemap_forum_priority_desc']);

	if (is_array($vbulletin->forumcache))
	{
		foreach($vbulletin->forumcache AS $key => $forum)
		{
			$priority = $sitemap->get_forum_custom_priority($forum['forumid']);
			if ($priority === false)
			{
				$priority = 'default';
			}

			$cell = array();

			$cell[] = "<b>" . construct_depth_mark($forum['depth'], '- - ')
				. "<a href=\"forum.php?do=edit&amp;f=$forum[forumid]\">$forum[title]</a></b>";

			$cell[] = "\n\t<select name=\"f[$forum[forumid]]\" class=\"bginput\">\n"
				. construct_select_options($default_settings, $priority)
				. " />\n\t";

			if ($forum['parentid'] == -1)
			{
				print_cells_row(array(
					$vbphrase['forum'],
					construct_phrase($vbphrase['priority_default_x'], vb_number_format($vbulletin->options['sitemap_priority'], 1))
				), 1, 'tcat');
			}

			print_cells_row($cell);
		}
	}

	print_submit_row($vbphrase['save_priority']);
}

// ########################################################################
if ($_REQUEST['do'] == 'removesession')
{
	print_form_header('sitemap', 'doremovesession');
	print_table_header($vbphrase['remove_sitemap_session']);
	print_description_row($vbphrase['are_you_sure_remove_sitemap_session']);
	print_submit_row($vbphrase['remove_sitemap_session'], null);
}

// ########################################################################
if ($_POST['do'] == 'doremovesession')
{
	// reset the build time to be the next time the cron is supposed to run based on schedule (in case we're in the middle of running it)
	require_once(DIR . '/includes/functions_cron.php');
	$cron = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "cron WHERE filename = './includes/cron/sitemap.php'");
	if ($cron)
	{
		build_cron_item($cron['cronid'], $cron);
	}

	$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "adminutil WHERE title = 'sitemapsession'");

	$_REQUEST['do'] = 'buildsitemap';
}

// ########################################################################
if ($_REQUEST['do'] == 'buildsitemap')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'success' => TYPE_BOOL
	));

	if ($vbulletin->GPC['success'])
	{
		print_table_start();
		print_description_row($vbphrase['sitemap_built_successfully_view_here'], false, 2, '', 'center');
		print_table_footer();
	}

	$runner = new vB_SiteMapRunner_Admin($vbulletin);

	$status = $runner->check_environment();
	if ($status['error'])
	{
		$sitemap_session = $runner->fetch_session();
		if ($sitemap_session['state'] != 'start')
		{
			print_table_start();
			print_description_row('<a href="sitemap.php?do=removesession">' . $vbphrase['remove_sitemap_session'] . '</a>', false, 2, '', 'center');
			print_table_footer();
		}

		print_stop_message($status['error']);
	}

	// Manual Sitemap Build
	print_form_header('sitemap', 'dobuildsitemap');
	print_table_header($vbphrase['build_sitemap']);
	print_description_row($vbphrase['use_to_build_sitemap']);
	print_submit_row($vbphrase['build_sitemap'], null);
}

// ########################################################################
if ($_POST['do'] == 'dobuildsitemap')
{
	$runner = new vB_SiteMapRunner_Admin($vbulletin);

	$status = $runner->check_environment();
	if ($status['error'])
	{
		print_stop_message($status['error']);
	}

	echo '<div>' . construct_phrase($vbphrase['processing_x'], '...') . '</div>';
	vbflush();

	$runner->generate();

	if ($runner->is_finished)
	{
		print_cp_redirect('sitemap.php?do=buildsitemap&success=1');
	}
	else
	{
		echo '<div>' . construct_phrase($vbphrase['processing_x'], $runner->written_filename) . '</div>';

		print_form_header('sitemap', 'dobuildsitemap', false, true, 'cpform_dobuildsitemap');
		print_submit_row($vbphrase['next_page'], 0);
		print_form_auto_submit('cpform_dobuildsitemap');
	}
}

// ########################################################################

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 23:48, Wed Mar 24th 2010
|| # CVS: $RCSfile$ - $Revision:  $
|| ####################################################################
\*======================================================================*/