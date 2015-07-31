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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('VB_PRODUCT', 'vbblog');
define('THIS_SCRIPT', 'blog_tag');
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('CSRF_PROTECTION', true);
define('VBBLOG_SCRIPT', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'vbblogglobal',
	'vbblogcat',
);

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'cloud' => array(
		'BLOG',
		'blog_css',
		'blog_usercss',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_category_link',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_generic',
		'blog_sidebar_user',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_tag_cloud_box',
		'blog_tag_cloud_link',
		'blog_tag_cloud',
		'memberinfo_visitorbit',
		'tag_cloud_headinclude',
		'ad_blog_sidebar_start',
		'ad_blog_sidebar_middle',
		'ad_blog_sidebar_end',
	),
	/*
	'tagedit' => array(
		'BLOG',
		'blog_css',
		'blog_usercss',
		'blog_tag_cloud_link',
		'blog_tag_edit',
		'blog_tag_edit_form',
		'newpost_errormessage',
		'tag_managebit',
		'ad_blog_sidebar_start',
		'ad_blog_sidebar_middle',
		'ad_blog_sidebar_end',
		'blog_sidebar_entry_link',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
	),
	*/
);

//$actiontemplates['tagupdate'] =& $actiontemplates['tagedit'];

if (empty($_REQUEST['do']))
{
	if (empty($_REQUEST['tag']))
	{
		$_REQUEST['do'] = 'cloud';
	}
	else
	{
		$_REQUEST['do'] = 'tag';
	}
}

if ($_REQUEST['do'] == 'cloud')
{
	$specialtemplates[] = 'blogtagcloud';
}
/*
else if ($_GET['do'] == 'tagedit' OR ($_POST['do'] == 'tagupdate' AND !$_POST['ajax']))
{
	$actiontemplates['tagedit'] = array_merge($actiontemplates['tagedit'], array(
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_category_link',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_user',
		'memberinfo_visitorbit',
	));
}
*/

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions_tag.php');

if (!$vbulletin->options['vbblog_tagging'])
{
	print_no_permission();
}

($hook = vBulletinHook::fetch_hook('blog_tags_start')) ? eval($hook) : false;
/*
if ($_REQUEST['do'] == 'tagedit' OR $_POST['do'] == 'tagupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogid' => TYPE_UINT,
	));

	$bloginfo = verify_blog($blogid);

	$show['add_option'] = (
		(($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_cantagown']) AND $bloginfo['userid'] == $vbulletin->userinfo['userid'])
		OR ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_cantagothers']
		OR can_moderate_blog('caneditentries'))
	);

	$show['manage_existing_option'] = (
		$show['add_option']
		OR (($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeletetagown']) AND $bloginfo['userid'] == $vbulletin->userinfo['userid'])
		OR can_moderate_blog('caneditentries')
	);

	if (!$show['add_option'] AND !$show['manage_existing_option'])
	{
		print_no_permission();
	}
}
*/
// #######################################################################
if ($_REQUEST['do'] == 'cloud')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT,
	));

	if ($vbulletin->GPC['userid'])
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);

		if (!$userinfo['canviewmyblog'])
		{
			print_no_permission();
		}

		if ($vbulletin->userinfo['userid'] == $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			print_no_permission();
		}

		if ($vbulletin->userinfo['userid'] != $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
		{
			// Can't view other's entries so off you go to your own blog.
			exec_header_redirect("blog.php?$session[sessionurl]u=" . $vbulletin->userinfo['userid']);
		}

		$show['usercloud'] = true;
		$tag_cloud = fetch_blog_tagcloud('usage', false, $userinfo['userid']);
	}
	else
	{
		$tag_cloud = fetch_blog_tagcloud('usage');
	}

	$navbits = construct_navbits(array(
		'blog.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['blogs'],
		'' => $vbphrase['tags'],
	));
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('blog_tags_cloud_complete')) ? eval($hook) : false;

	if ($userinfo)
	{
		$sidebar =& build_user_sidebar($userinfo);
	}
	else
	{
		$sidebar =& build_overview_sidebar();
	}

	$templater = vB_Template::create('blog_tag_cloud');
		$templater->register('tag_cloud', $tag_cloud);
		$templater->register('tag_delimiters', $tag_delimiters);
		$templater->register('userinfo', $userinfo);
	$content = $templater->render();
	$headinclude .= vB_Template::create('blog_css')->render();
	$templater = vB_Template::create('BLOG');
		$templater->register_page_templates();
		$templater->register('abouturl', $abouturl);
		$templater->register('blogheader', $blogheader);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('blogrssinfo', $blogrssinfo);
		$templater->register('bloguserid', $bloguserid);
		$templater->register('content', $content);
		$templater->register('navbar', $navbar);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('pingbackurl', $pingbackurl);
		$templater->register('sidebar', $sidebar);
		$templater->register('trackbackurl', $trackbackurl);
		$templater->register('usercss_profile_preview', $usercss_profile_preview);
	print_output($templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 23:48, Wed Mar 24th 2010
|| # CVS: $RCSfile$ - $Revision: 26544 $
|| ####################################################################
\*======================================================================*/
?>
