<?php
/*======================================================================*\
|| #################################################################### ||
|| # DownloadsII 6.0.2 : A downloads mod for vBulletin                # ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2010 by (RS_)Jelle (http://www.minatica.be/)          # ||
|| # All Rights Reserved.                                             # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # http://www.vbulletin.org/forum/showthread.php?t=231427           # ||
|| #################################################################### ||
\*======================================================================*/

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'downloads2');
define('CSRF_PROTECTION', true);
define('GET_EDIT_TEMPLATES', 'add,edit');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'downloads2',
	'posting'
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'DOWNLOADS2',
	'downloads2_panel_bit',
	'downloads2_panel_side',
	'downloads2_wrapper_top',
	'downloads2_wrapper_side',
	'downloads2_wrapper_none',
	'downloads2_warning'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'add' => array(
		'downloads2_file_addit'
	),
	'cat' => array(
		'downloads2_cat',
		'downloads2_cat_filebit',
		'downloads2_cat_subbit',
		'downloads2_cat_subs',
		'forumdisplay_sortarrow'
	),
	'edit' => array(
		'downloads2_file_addit'
	),
	'file' => array(
		'downloads2_file',
		'downloads2_file_commentbit',
		'downloads2_file_imagebit',
		'editor_clientscript',
		'editor_jsoptions_font',
		'editor_jsoptions_size',
		'editor_toolbar_colors',
		'editor_toolbar_fontname',
		'editor_toolbar_fontsize',
		'showthread_quickreply',
		'newpost_errormessage'
	),
	'manfiles' => array(
		'downloads2_man',
		'downloads2_man_bit'
	),
	'none' => array(
		'downloads2_main',
		'downloads2_main_catbit'
	),
	'report' => array(
		'downloads2_report'
	),
	'search' => array(
		'downloads2_search',
		'downloads2_search_result',
		'downloads2_search_result_bit'
	)
);

// allows proper template caching for the default action (none) if no valid action is specified
if (!empty($_REQUEST['do']) AND !isset($actiontemplates["$_REQUEST[do]"]))
{
	$actiontemplates["$_REQUEST[do]"] =& $actiontemplates['none'];
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_downloads2.php');

$dl = new vB_Downloads();
$navbits = array('downloads.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['dl2_downloads']);
$forceredirect = false;
$vbulletin->url = 'downloads.php' . $vbulletin->session->vars['sessionurl_q'];

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (!$vbulletin->options['dl2active'])
{
	if (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canviewdisabled']))
	{
		$dwarning = '<strong>'.$vbphrase['dl2_disabled'].':</strong> '.$vbulletin->options['dl2closedreason'];

		$templater = vB_Template::create('downloads2_warning');
			$templater->register('dwarning', $dwarning);
		$dwarning = $templater->render();
	}
	else
	{
		standard_error($vbulletin->options['dl2closedreason']);
	}
}

if (!is_dir($vbulletin->options['dl2folderpath']))
{
	$dwarning = $vbphrase['dl2_dir_doesnt_exist'];

	$templater = vB_Template::create('downloads2_warning');
		$templater->register('dwarning', $dwarning);
	$dwarning = $templater->render();
}
else if (!is_writable($vbulletin->options['dl2folderpath']))
{
	$dwarning = $vbphrase['dl2_dir_not_writable'];

	$templater = vB_Template::create('downloads2_warning');
		$templater->register('dwarning', $dwarning);
	$dwarning = $templater->render();
}
else if (!file_exists($vbulletin->options['dl2folderpath']."/index.html") AND !file_exists($vbulletin->options['dl2folderpath']."/index.php"))
{
	$dwarning = $vbphrase['dl2_no_index_in_dir'];

	$templater = vB_Template::create('downloads2_warning');
		$templater->register('dwarning', $dwarning);
	$dwarning = $templater->render();
}

// Check for safe mode
if (ini_get('safe_mode') AND !is_dir($vbulletin->options['dl2folderpath'] . '/dl_tmp/'))
{
	standard_error($vbphrase['dl2_safe_mode']);
}

if (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canaccessdownloads2']))
{
	print_no_permission();
}

$dlstats['latestall'] = $dl->stats['latestall'];
$dlstats['popularall'] = $dl->stats['popularall'];
$dlstats['contriball'] = $dl->stats['contriball'];

($hook = vBulletinHook::fetch_hook('dl2_start')) ? eval($hook) : false;

if ($_GET['do'] == 'cat')
{
	$cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
	$catexclude = $dl->exclude_cat();

	$cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_categories WHERE $catexclude `id` = $cleancatid");
	if ($cat['id'] == 0)
	{
		eval(print_standard_redirect('dl2_msg_invalid_cat', true, true));
	}

	$cat['name'] = htmlspecialchars_uni($cat['name']);
	$dlcustomtitle = $cat['name'];

	$navbits += $dl->build_cat_nav($cat['id']);

	$show['addnewfile'] = false;
	if ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadfiles']
	    OR $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canlinktofiles'])
	{
		$show['addnewfile'] = true;
	}

	$show['dlsearch'] = $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['cansearchfiles'];

	$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_categories WHERE $catexclude `parent` = $cat[id] ORDER BY `weight`");
	if ($db->num_rows($result) > 0)
	{
		while ($sub = $db->fetch_array($result))
		{
			$sub['name'] = htmlspecialchars_uni($sub['name']);
			$sub['description'] = htmlspecialchars_uni($sub['description']);
			$sub['files'] = vb_number_format($sub['files']);

			if ($vbulletin->options['dl2subcatsdepth'] > 0)
			{
				$subcats = $dl->grab_subcats_by_name($sub['id']);
			}
			else
			{
				$subcats = '';
			}

			$templater = vB_Template::create('downloads2_cat_subbit');
				$templater->register('sub', $sub);
				$templater->register('subcats', $subcats);
			$dsubbits .= $templater->render();
		}

		$templater = vB_Template::create('downloads2_cat_subs');
			$templater->register('dsubbits', $dsubbits);
		$dsubcats .= $templater->render();
	}
	$db->free_result($result);

	$filesexclude = $dl->exclude_files();
	$temp = $db->query_first("SELECT COUNT(*) AS files FROM " . TABLE_PREFIX . "dl2_files WHERE $filesexclude `category` = $cat[id]");

	$sortfield = $vbulletin->input->clean_gpc('r', 'sortfield', TYPE_STR);
	$sort_fields = array(
		'title',
		'author',
		'uploader',
		'dateadded',
		'totaldownloads',
		'lastdownload',
		'totalcomments',
		'rating'
	);
	if (!in_array($sortfield, $sort_fields))
	{
		$sortfield = $cat['defaultsortfield'];
	}
	$sortorder = $vbulletin->input->clean_gpc('r', 'sortorder', TYPE_STR);
	if ($sortorder != 'asc' AND $sortorder != 'desc')
	{
		$sortorder = $cat['defaultsortorder'];
	}
	$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);

	sanitize_pageresults($temp['files'], $pagenumber, $vbulletin->options['dl2perpage'], $vbulletin->options['dl2perpage'], $vbulletin->options['dl2perpage']);

	$limit = ($pagenumber -1) * $vbulletin->options['dl2perpage'];
	$pagenav = construct_page_nav($pagenumber, $vbulletin->options['dl2perpage'], $temp['files'], 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=cat&amp;id=$cat[id]", ""
		. (($sortfield != $cat['defaultsortfield']) ? "&amp;sort=$sortfield" : "")
		. (($sortorder != $cat['defaultsortorder']) ? "&amp;order=$sortorder" : "")
	);

	// Show sort arrow
	$oppositesort = ($sortorder == 'asc' ? 'desc' : 'asc');
	$templater = vB_Template::create('forumdisplay_sortarrow');
		$templater->register('oppositesort', $oppositesort);
	$sortarrow["$sortfield"] = $templater->render();

	// Create sort links
	$sorturl['title'] = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=cat&amp;id=$cat[id]&amp;sort=title&amp;order=" . ($sortfield == 'title' ? $oppositesort : 'asc');
	$sorturl['dateadded'] = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=cat&amp;id=$cat[id]&amp;sort=dateadded&amp;order=" . ($sortfield == 'dateadded' ? $oppositesort : 'desc');
	$sorturl['totaldownloads'] = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=cat&amp;id=$cat[id]&amp;sort=totaldownloads&amp;order=" . ($sortfield == 'totaldownloads' ? $oppositesort : 'desc');
	$sorturl['totalcomments'] = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=cat&amp;id=$cat[id]&amp;sort=totalcomments&amp;order=" . ($sortfield == 'totalcomments' ? $oppositesort : 'desc');

	// Select some stuff in the sort form
	$sortformfield["$sortfield"] = ' selected="selected"';
	$sortformorder["$sortorder"] = ' selected="selected"';

	$result = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "dl2_files
		WHERE category = $cat[id]
			" . (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canmanagemodqueue']) ? '' : 'AND modqueue = 0') . "
		ORDER BY pin DESC, $sortfield $sortorder
		LIMIT $limit, " . $vbulletin->options['dl2perpage']
	);
	if ($db->num_rows($result) > 0)
	{
		while ($file = $db->fetch_array($result))
		{
			$file['title'] = htmlspecialchars_uni($file['title']);
			$file['dateadded'] = vbdate($vbulletin->options['dateformat'], $file['dateadded'], true);
			$file['totaldownloads'] = vb_number_format($file['totaldownloads']);

			if ($vbulletin->options['dl2smalldesc'] > 0)
			{
				$smalldesc = strip_bbcode($file['description'], $stripquotes = false, $fast_and_dirty = false, $showlinks = false);
				$smalldesc = substr($smalldesc, 0, $vbulletin->options['dl2smalldesc']);
				$smalldesc = $vbulletin->input->clean($smalldesc, TYPE_NOHTML);
				$smalldesc = ': ' . $smalldesc;
			}

			if ($vbulletin->options['dl2allowimages'] AND $vbulletin->options['dl2showthumbs'])
			{
				$thumb_q = $db->query_first("SELECT thumb FROM " . TABLE_PREFIX . "dl2_images WHERE file = $file[id] ORDER BY `id` ASC LIMIT 1");
				if ($thumb_q['thumb'] != '')
				{
					$show['filethumb'] = true;
					$file['thumb'] = $dl->url . $thumb_q['thumb'];
				}
				else
				{
					$show['filethumb'] = false;
				}
			}
			else
			{
				$show['filethumb'] = false;
			}

			if ($file['size'] == 0)
			{
				$file['size'] = $vbphrase['dl2_unknown_size'];
			}
			else
			{
				$file['size'] = vb_number_format($file['size'], 0, true);
			}

			if (strlen($file['description']) > $vbulletin->options['dl2smalldesc'] AND $vbulletin->options['dl2smalldesc'] > 0)
			{
				$smalldesc .= '&nbsp;... [<a href="downloads.php?' . $vbulletin->session->vars['sessionurl'] . 'do=file&amp;id='.$file['id'].'">'.$vbphrase['dl2_more'].'</a>]';
			}

			$templater = vB_Template::create('downloads2_cat_filebit');
				$templater->register('file', $file);
				$templater->register('smalldesc', $smalldesc);
			$dfilebits .= $templater->render();
		}
	}
	$db->free_result($result);

	$category_array = $dl->construct_select_array(0, array('#' => $vbphrase['dl2_category_jump']), '');
	foreach ($category_array AS $cat_key => $cat_value)
	{
		$category_jump .= '<option value="'.$cat_key.'">'.$cat_value.'</option>';
	}

	$templater = vB_Template::create('downloads2_cat');
		$templater->register('dsubcats', $dsubcats);
		$templater->register('cat', $cat);
		$templater->register('pagenav', $pagenav);
		$templater->register('dfilebits', $dfilebits);
		$templater->register('sorturl', $sorturl);
		$templater->register('sortarrow', $sortarrow);
		$templater->register('sortformfield', $sortformfield);
		$templater->register('sortformorder', $sortformorder);
		$templater->register('category_jump', $category_jump);
		$templater->register('gobutton', $gobutton);
	$dmain_jr = $templater->render();

	if ($vbulletin->options['dl2showtops'] & 2)
	{
		$templater = vB_Template::create('downloads2_panel_side');
			$templater->register('dlstats', $dlstats);
		$dpanel = $templater->render();

		$templater = vB_Template::create('downloads2_wrapper_side');
			$templater->register('dlcustomtitle', $dlcustomtitle);
			$templater->register('dmain_jr', $dmain_jr);
			$templater->register('dpanel', $dpanel);
		$dmain = $templater->render();
	}
	else
	{
		$templater = vB_Template::create('downloads2_wrapper_none');
			$templater->register('dmain_jr', $dmain_jr);
		$dmain = $templater->render();
	}
}
else if ($_GET['do'] == 'file')
{
	if (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canviewfiles']))
	{
		print_no_permission();
	}

	$filesexclude = $dl->exclude_files(1);

	$cleanfileid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);

	$file = $db->query_first("
		SELECT " . TABLE_PREFIX . "dl2_files.*, " . TABLE_PREFIX . "dl2_extensions.mimetype, " . TABLE_PREFIX . "dl2_extensions.newwindow
		FROM " . TABLE_PREFIX . "dl2_files
		LEFT JOIN " . TABLE_PREFIX . "dl2_extensions ON (" . TABLE_PREFIX . "dl2_extensions.extension=" . TABLE_PREFIX . "dl2_files.extension)
		WHERE $filesexclude
		" . TABLE_PREFIX . "dl2_files.id = $cleanfileid
	");
	if ($file['id'] == 0)
	{
		eval(print_standard_redirect('dl2_msg_invalid_file', true, true));
	}

	$dlcustomtitle = htmlspecialchars_uni($file['title']);

	$vbulletin->url = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$file[id]";

	$navbits += $dl->build_cat_nav($file['category']);
	$navbits['downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$file[id]"] = htmlspecialchars_uni($file['title']);

	$show['canedit'] = false;
	if (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditallfiles']) OR
	   (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditownfiles']) AND
	   ($file['uploaderid'] == $vbulletin->userinfo['userid'])))
	{
		$show['canedit'] = true;
	}

	$show['canapprove'] = $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canmanagemodqueue'];

	if (($_POST['vote'] > 0) AND ($vbulletin->userinfo['userid'] > 0) AND $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canratefiles'])
	{
		$result = $db->query_first("SELECT COUNT(*) AS voteamount FROM " . TABLE_PREFIX . "dl2_votes WHERE `file` = $file[id] AND `user` = " . $vbulletin->userinfo['userid']);
		if ($result['voteamount'] == 0)
		{
			$cleanvote = $vbulletin->input->clean_gpc('r', 'vote', TYPE_UINT);

			$valid_votes = array(1, 2, 3, 4, 5);
			if (!in_array($cleanvote, $valid_votes))
			{
				eval(print_standard_redirect('dl2_msg_failure', true, true));
			}

			$result = $db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dl2_votes
					(user, file, value)
				VALUES
					(" . $vbulletin->userinfo['userid'] . ", $file[id], $cleanvote)
			");
			if ($result)
			{
				$voteinfo = $db->query_first("SELECT COUNT(*) AS votes, SUM(`value`) AS total FROM " . TABLE_PREFIX . "dl2_votes WHERE `file` = $file[id]");
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `rating` = " . ($voteinfo['total'] / $voteinfo['votes']) . " WHERE `id` = $file[id]");
				eval(print_standard_redirect('dl2_msg_vote_success', true, true));
			}
			else
			{
				eval(print_standard_redirect('dl2_msg_failure', true, true));
			}
		}
		else
		{
			eval(print_standard_redirect('dl2_msg_already_voted', true, true));
		}
		$db->free_result($result);
	}

	if ($_GET['act'] == 'down')
	{
		if (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['candownloadfiles']) OR
		   (($file['modqueue'] == 1) AND !($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canmanagemodqueue'])))
		{
			print_no_permission();
		}
		else
		{
			// Check if strict daily limits are in effect, then set conditional for testing limits
			if (!($permissions['downloads2extrapermissions'] & $vbulletin->bf_ugp['downloads2extrapermissions']['dailylimits']))
			{
				// strict limits is set to NO (use AND)
				// check if the users has exceeded the max daily download amount
				if (($permissions['downloadsmaxdailydl'] >= 0) AND ($permissions['downloadsmaxdailyfiles'] >= 0))
				{
					// check if max is set to zero
					if (($permissions['downloadsmaxdailydl'] == 0) AND ($permissions['downloadsmaxdailyfiles'] == 0))
					{
						eval(print_standard_redirect('dl2_daily_download_amount_exceeded', true, true));
					}

					// check amount downloaded against maxdaily
					if ($permissions['downloads2extrapermissions'] & $vbulletin->bf_ugp['downloads2extrapermissions']['downloadsmaxdailydl'] > 0)
					{
						$tempmax1 = $db->query_first("
							SELECT SUM(filesize) AS dlamount
							FROM " . TABLE_PREFIX . "dl2_downloads
							WHERE (userid = " . $vbulletin->userinfo['userid'] . "
								OR ipaddress = '" . $db->escape_string(IPADDRESS) . "')
								AND time >= " . (TIMENOW - 86400)
						);

						$tempnew = $db->query_first("SELECT size FROM " . TABLE_PREFIX . "dl2_files WHERE id = $file[id]");

						$dlremaining = ($permissions['downloadsmaxdailydl'] * 1048576) - ($tempmax1['dlamount'] + $tempnew['size']);

						if ($dlremaining < 0)
						{
							$maxdailydl = true;
						}
					}
					// check amount downloaded against max daily number of files
					if ($permissions['downloads2extrapermissions'] & $vbulletin->bf_ugp['downloads2extrapermissions']['downloadsmaxdailyfiles'] > 0)
					{
						$tempmax1 = $db->query_first("
							SELECT COUNT(*) AS dlcount
							FROM " . TABLE_PREFIX . "dl2_downloads
							WHERE (userid = " . $vbulletin->userinfo['userid'] . "
								OR ipaddress = '" . $db->escape_string(IPADDRESS) . "')
								AND time >= " . (TIMENOW - 86400)
						);

						$dlremaining = ($permissions['downloadsmaxdailyfiles']) - ($tempmax1['dlcount']);

						if ($dlremaining <= 0)
						{
							$maxdailyfiles = true;
						}
					}
					// combine the two checks
					if ($maxdailydl AND $maxdailyfiles)
					{
						eval(print_standard_redirect('dl2_daily_download_amount_exceeded', true, true));
					}
				}
			}
			else
			{
				// strict limits is set to YES (use OR)
				// check if the users has exceeded the max daily download amount
				if (($permissions['downloadsmaxdailydl'] >= 0) OR ($permissions['downloadsmaxdailyfiles'] >= 0))
				{
					// check if max is set to zero
					if (($permissions['downloadsmaxdailydl'] == 0) OR ($permissions['downloadsmaxdailyfiles'] == 0))
					{
						eval(print_standard_redirect('dl2_daily_download_amount_exceeded', true, true));
					}

					// check amount downloaded against maxdaily
					if ($permissions['downloadsmaxdailydl'] > 0)
					{
						$tempmax1 = $db->query_first("
							SELECT SUM(filesize) AS dlamount
							FROM " . TABLE_PREFIX . "dl2_downloads
							WHERE (userid = " . $vbulletin->userinfo['userid'] . "
								OR ipaddress = '" . $db->escape_string(IPADDRESS) . "')
								AND time >= " . (TIMENOW - 86400)
						);

						$tempnew = $db->query_first("SELECT size FROM " . TABLE_PREFIX . "dl2_files WHERE id = $file[id]");

						$dlremaining = ($permissions['downloadsmaxdailydl'] * 1048576) - ($tempmax1['dlamount'] + $tempnew['size']);

						if ($dlremaining < 0)
						{
							eval(print_standard_redirect('dl2_daily_download_amount_exceeded', true, true));
						}
					}
					// check amount downloaded against max daily number of files
					if ($permissions['downloadsmaxdailyfiles'] > 0)
					{
						$tempmax1 = $db->query_first("
							SELECT COUNT(*) AS dlcount
							FROM " . TABLE_PREFIX . "dl2_downloads
							WHERE (userid = " . $vbulletin->userinfo['userid'] . "
								OR ipaddress = '" . $db->escape_string(IPADDRESS) . "')
								AND time >= " . (TIMENOW - 86400)
						);

						$dlremaining = ($permissions['downloadsmaxdailyfiles']) - ($tempmax1['dlcount']);

						if ($dlremaining <= 0)
						{
							eval(print_standard_redirect('dl2_daily_download_amount_exceeded', true, true));
						}
					}
				}
			}

			if ($permissions['downloaddelaygrp'] > 0)
			{
				// check for possible Denial of service attack
				$temptime = $db->query_first("
					SELECT time
					FROM " . TABLE_PREFIX . "dl2_downloads 
					WHERE `userid` = " . $vbulletin->userinfo['userid'] . "
						OR `ipaddress` = '" . $db->escape_string(IPADDRESS) . "'
					ORDER BY `time` DESC LIMIT 0, 1
				");

				$timedelay = $permissions['downloaddelaygrp'];

				if (TIMENOW - $temptime['time'] < $timedelay)
				{
					$timedelay = round($temptime['time'] + $timedelay - TIMENOW);

					eval(standard_error(fetch_error('dl2_download_too_quickly', $timedelay)));
				}

				$db->free_result($temptime);
			}

			// hook for pre-download checks
			($hook = vBulletinHook::fetch_hook('dl2_pre_download')) ? eval($hook) : false;

			$db->query_write("INSERT INTO " . TABLE_PREFIX . "dl2_downloads (userid, fileid, user, file, time, filesize, ipaddress)
							VALUES(" . $vbulletin->userinfo['userid'] . ", $file[id], '" . $db->escape_string($vbulletin->userinfo['username']) . "', '" . $db->escape_string(htmlspecialchars_uni($file['title'])) . "', " . TIMENOW . ", $file[size], '" . $db->escape_string(IPADDRESS) . "')");
			$db->query_write("UPDATE " . TABLE_PREFIX . "user SET `dl2_downloads`=`dl2_downloads`+1 WHERE `userid` = " . $vbulletin->userinfo['userid']);
			$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `totaldownloads`=`totaldownloads`+1, `lastdownload`=".TIMENOW." WHERE `id` = $file[id]");
			$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `downloads`=`downloads`+1");
			$dl->update_popular_files();
			$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_stats SET `downloads`=`downloads`+1, `bandwidth`=`bandwidth`+".$file['size']." WHERE `day`=".$db->sql_prepare((int) (TIMENOW/86400)));
			if ($db->affected_rows() == 0)
			{
				$db->query_write("INSERT INTO " . TABLE_PREFIX . "dl2_stats (day, downloads, bandwidth) VALUES (" . $db->sql_prepare((int) (TIMENOW/86400)) . ", 1, $file[size])");
			}


			if ($file['link'] == 0)
			{
				// hook for post-download checks
				($hook = vBulletinHook::fetch_hook('dl2_post_download')) ? eval($hook) : false;

				$ext = strtolower(file_extension($dl->url . $file['url']));
				if ($vbulletin->options['ecrenamefiles'])
				{
					$newfilename = preg_replace('/[^\sA-Za-z0-9\&\(\)\-\_\+\{\[\}\]\,\.]+/', '', $file['title']).'.'.$ext;
				}
				else
				{
					$newfilename = preg_replace('/[0-9]+-/', '', $file['url']);
				}

				$filename = $dl->url . $file['url'];
				$dlfilename = $dl->url . $newfilename;

				// required for IE, otherwise Content-disposition is ignored
				if (ini_get('zlib.output_compression'))
				{
					@ini_set('zlib.output_compression', 'Off');
				}

				if ($filename == '')
				{
					echo "<html><head><title>DownloadsII Error</title></head><body>ERROR: download file NOT SPECIFIED.</body></html>";
					exit;
				}
				else if (!file_exists($filename))
				{
					echo "<html><head><title>DownloadsII Error</title></head><body>ERROR: File not found.</body></html>";
					exit;
				}

				if ($file['mimetype'] != '')
				{
					$ctype = $file['mimetype'];
				}
				else
				{
					$ctype = "Content-Type: application/force-download";
				}

				if ($file['inline'] == 1)
				{
					$cdisposition = 'inline';
				}
				else
				{
					$cdisposition = 'attachment';
				}




/*

// Testing stuff, don't use this yet!

    //  Begin writing headers
    header("Cache-Control:");
    header("Cache-Control: public");
    header("Content-Type: $ctype");
 
    $filespaces = str_replace("_", " ", $filename);
    // if your filename contains underscores, replace them with spaces
 
    $header='Content-Disposition: attachment; filename='.$filespaces;
    header($header);
    header("Accept-Ranges: bytes");
   
    $size = filesize($file); 
    //  check if http_range is sent by browser (or download manager) 
    if (isset($_SERVER['HTTP_RANGE']))
    {
        // if yes, download missing part    
 
        $seek_range = substr($_SERVER['HTTP_RANGE'] , 6);
        $range = explode('-', $seek_range);
        if($range[0] > 0) { $seek_start = intval($range[0]); }
        if($range[1] > 0) { $seek_end  =  intval($range[1]); }
          
        header("HTTP/1.1 206 Partial Content");
        header("Content-Length: " . ($seek_end - $seek_start + 1));
        header("Content-Range: bytes $seek_start-$seek_end/$size");
    }
    else
    {
        header("Content-Range: bytes 0-$seek_end/$size");
        header("Content-Length: $size");
    } 
    //open the file
    $fp = fopen("$file", "rb");
   
    //seek to start of missing part 
    fseek($fp, $seek_start);
 
    //start buffered download
    while (!feof($fp))
    {  
        //reset time limit for big files
        set_time_limit(0);    
        print(fread($fp, 1024*$speed));
        flush();
        sleep(1);
    }

*/




				header("Pragma: public"); // required
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private",false); // required for certain browsers 
				header("$ctype; name=\"".basename($dlfilename)."\";");
				// change, added quotes to allow spaces in filenames, by Rajkumar Singh
				header("Content-Disposition: $cdisposition; filename=\"".basename($dlfilename)."\";" );
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".filesize($filename));
				readfile("$filename");
				exit();
			}
			else
			{
				header("Location: $file[url]");
				exit();
			}
		}
	}

	if ($vbulletin->options['dl2allowcomments'])
	{
		if ($_POST['wysiwyg'] == 1) // do this first to prevent empty messages
		{
			require_once(DIR . '/includes/functions_wysiwyg.php');
			$_POST['message'] = convert_wysiwyg_html_to_bbcode($_POST['message'], 0);
		}

		if (($_POST['message'] != '') AND ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['cancomment']))
		{
			require_once(DIR . '/includes/functions_newpost.php');
			$_POST['message'] = convert_url_to_bbcode($_POST['message']);

			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dl2_comments
					(`fileid`, `author`, `authorid`, `date`, `message`)
				VALUES
					($file[id], '" . $db->escape_string($vbulletin->userinfo['username']) . "'," . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ", '" . $db->escape_string($_POST['message']) . "')
			");
			$db->query_write("UPDATE " . TABLE_PREFIX . "user SET `dl2_comments`=`dl2_comments`+1 WHERE `userid` = " . $vbulletin->userinfo['userid']);
			$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `comments`=`comments`+1");
			$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `totalcomments`=`totalcomments`+1 WHERE `id` = $file[id]");

			eval(print_standard_redirect('dl2_msg_comment_added', true, true));
		}

		if ($_GET['act'] == 'delcomment')
		{
			$comment = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_comments WHERE `id` = " . $db->sql_prepare($_GET['com']));

			if (($comment['fileid'] == $file['id']) AND
				(($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['candeleteallcomments']) OR 
				(($comment['authorid'] == $userinfo['userid']) AND ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['candeleteowncomments']))))
			{
				$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_comments WHERE `id` = $comment[id]");
				$db->query_write("UPDATE " . TABLE_PREFIX . "user SET `dl2_comments`=`dl2_comments`-1 WHERE `userid` = " . $vbulletin->userinfo['userid']);
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `comments`=`comments`-1");
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `totalcomments`=`totalcomments`-1 WHERE `id` = $file[id]");
				eval(print_standard_redirect('dl2_msg_comment_deleted', true, true));
			}
		}
	}

	if ($vbulletin->options['dl2allowimages'])
	{
		if
		(
			($_FILES['image']['name'] != '')
			AND
			(
				($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadimagestoownfiles'] AND ($file['uploaderid'] == $vbulletin->userinfo['userid']))
				OR
				($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadimagestoallfiles'])
			)
		)
		{
			$vbulletin->input->clean_gpc('f', 'image', TYPE_FILE);

			for ($i = 1; $i <= 3; $i++)
			{
				switch(rand(1,3))
				{
					case 1: $random.=chr(mt_rand(48,57)); break;  // 0-9
					case 2: $random.=chr(mt_rand(65,90)); break;  // A-Z
					case 3: $random.=chr(mt_rand(97,122)); break; // a-z
				}
			}
			$dot = strrpos($vbulletin->GPC['image']['name'], '.');
			$name = strtolower(substr($vbulletin->GPC['image']['name'], 0, $dot));
			$ext = strtolower(substr($vbulletin->GPC['image']['name'], $dot+1));

			// Empty errors array
			$errors = array();

			$extension = $db->query_first("
				SELECT *
				FROM " . TABLE_PREFIX . "dl2_extensions
				WHERE `extension` = '" . $db->escape_string($ext) . "'
					AND `mode` <> 1
					AND `enabled` = 1
				LIMIT 1
			");

			if ($extension['extension'] === $ext)
			{
				if (($extension['size'] > 0) AND ($extension['size'] < @filesize($vbulletin->GPC['image']['tmp_name'])))
				{
					$errors['message'][] = construct_phrase($vbphrase['dl2_error_too_big'], vb_number_format($extension['size'], 0, true));
				}

				if (($extension['width'] > 0) OR ($extension['height'] > 0))
				{
					list($width, $height, $type, $attr) = @getimagesize($vbulletin->GPC['image']['tmp_name']);

					if (($extension['width'] > 0) AND ($extension['width'] < $width))
					{
						$errors['message'][] = construct_phrase($vbphrase['dl2_error_too_broad'], $extension['width']);
					}

					if (($extension['height'] > 0) AND ($extension['height'] < $height))
					{
						$errors['message'][] = construct_phrase($vbphrase['dl2_error_too_high'], $extension['height']);
					}
				}
			}
			else
			{
				$query = $db->query_read("
					SELECT *
					FROM " . TABLE_PREFIX . "dl2_extensions
					WHERE `mode` <> 1
						AND `enabled` = 1
				");
				while ($extensioning = $vbulletin->db->fetch_array($query))
				{
					$extensionlist .= '.' . $extensioning['extension'] . ' ';
				}

				$errors['message'][] = $vbphrase['dl2_error_invalid_extension'] . ': ' . $extensionlist;
			}

			if (empty($errors))
			{
				$forceredirect = true;
				$newfilename = $name . '_' . $random . '.' . $ext;
				move_uploaded_file($vbulletin->GPC['image']['tmp_name'], $dl->url.$newfilename);
				chmod($dl->url.$newfilename, 0666);
				$thumb = $name.'_'.$random.'_thumb.'.$ext;
				if (($ext == 'jpg') OR ($ext == 'jpeg'))
				{
					$orig_image = imagecreatefromjpeg($dl->url.$newfilename);
				}
				else if ($ext == 'png')
				{
					$orig_image = imagecreatefrompng($dl->url.$newfilename);
				}
				else if ($ext == 'gif')
				{
					$orig_image = imagecreatefromgif($dl->url.$newfilename);
				}

				list($width, $height, $type, $attr) = @getimagesize($dl->url.$newfilename);
				if ($width > 100)
				{
					$ratio = 100 / $width;
					$newheight = $ratio * $height;
				}
				else
				{
					$newheight = $height;
				}
				$destimg = @imagecreatetruecolor(100, $newheight);
				imagecopyresampled($destimg, $orig_image, 0, 0, 0, 0, 100, $newheight, imagesx($orig_image), imagesy($orig_image));

				if (($ext == 'jpg') OR ($ext == 'jpeg'))
				{
					@imagejpeg($destimg, $dl->url.$thumb);
				}
				else if ($ext == 'png')
				{
					@imagepng($destimg, $dl->url.$thumb);
				}
				else if ($ext == 'gif')
				{
					@imagegif($destimg, $dl->url.$thumb);
				}
				@imagedestroy($destimg);

				$db->query_write("
					INSERT INTO " . TABLE_PREFIX . "dl2_images
						(`file`, `name`, `thumb`, `uploader`, `uploaderid`, `date`)
					VALUES
						($file[id],
						'" . $db->escape_string($newfilename) . "',
						'" . $db->escape_string($thumb) . "',
						'" . $db->escape_string($vbulletin->userinfo['username']) . "',
						" . $vbulletin->userinfo['userid'] . ",
						" . TIMENOW . ")
				");
				eval(print_standard_redirect('dl2_msg_image_added', true, true));
			}
			else
			{
				// Error handling
				$show['errors'] = true;
				foreach ($errors['message'] AS $errormessage)
				{
					$templater = vB_Template::create('newpost_errormessage');
						$templater->register('errormessage', $errormessage);
					$errorlist .= $templater->render();
				}
			}
		}

		if ($_GET['act'] == 'delimg')
		{
			$image = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_images WHERE `id` = " . $db->sql_prepare($_GET['img']));
			if (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['candeleteallimages']) OR
			   (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['candeleteownimages']) AND
				 (($image['uploaderid'] == $vbulletin->userinfo['userid']) AND ($file['uploaderid'] == $vbulletin->userinfo['userid']))))
			{
				$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_images WHERE `id` = $image[id]");

				@unlink($dl->url.$image['name']);
				@unlink($dl->url.$image['thumb']);

				eval(print_standard_redirect('dl2_msg_image_deleted', true, true));
			}
		}

		$show['uploadimage'] = false;
		if
		(
			($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadimagestoownfiles'] AND ($file['uploaderid'] == $vbulletin->userinfo['userid']))
			OR
			($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadimagestoallfiles'])
		)
		{
			$show['uploadimage'] = true;

			require_once(DIR . '/includes/functions_file.php');
			$inimaxattach = fetch_max_upload_size();
		}
	}

	$file['title'] = htmlspecialchars_uni($file['title']);

	$show['reportfile'] = $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canreportfiles'];

	$file['dateadded'] = vbdate($vbulletin->options['dateformat'], $file['dateadded'], true);

	if ($file['rating'] >= 1)
	{
		$show['filerating'] = true;
		$file['rating'] = round($file['rating']);
	}
	else
	{
		$show['filerating'] = false;
	}

	$urating = $db->query_first("SELECT `value` FROM " . TABLE_PREFIX . "dl2_votes WHERE `file` = $file[id] AND `user` = " . $vbulletin->userinfo['userid']);
	if ($urating['value'] > 0)
	{
		// You already rated the file
		$show['ratefile'] = false;

		// $userscore = $vbphrase['dl2_your_grade'].': '.$urating['value'].' '.(($urating['value'] == 1) ? $vbphrase['dl2_star'] : $vbphrase['dl2_stars']);
	}
	else
	{
		$show['ratefile'] = true;

		$show['allowratefile'] = false;
		if ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canratefiles'])
		{
			$show['allowratefile'] = true;
		}
	}

	if ($file['size'] == 0)
	{
		$file['size'] = $vbphrase['dl2_unknown_size'];
	}
	else
	{
		$file['size'] = vb_number_format($file['size'], 0, true);
	}

	$file['totaldownloads'] = vb_number_format($file['totaldownloads']);

	if ($file['link'])
	{
		$show['newwindow'] = true;
	}
	else
	{
		$show['newwindow'] = ($file['newwindow'] ? true : false);
	}

	require_once(DIR . '/includes/class_bbcode.php');
	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	$file['description'] = $bbcode_parser->do_parse($file['description'], false, true, true, true, true, $cachable);

	if ($vbulletin->options['dl2allowimages'])
	{
		$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_images WHERE `file` = $file[id]");
		while ($image = $db->fetch_array($result))
		{
			$show['controls'] = false;
			if ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditallfiles'] OR
			   ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditownfiles'] AND
				(($image['uploaderid'] == $vbulletin->userinfo['userid']) AND ($file['uploaderid'] == $vbulletin->userinfo['userid']))
			   ))
			{
				$show['controls'] = true;
			}

			$image['name'] = $dl->url . $image['name'];

			if (file_exists($dl->url . $image['thumb']))
			{
				$image['thumb'] = $dl->url . $image['thumb'];
			}
			else
			{
				$image['thumb'] = false;
			}

			$templater = vB_Template::create('downloads2_file_imagebit');
				$templater->register('image', $image);
				$templater->register('file', $file);
			$dimages .= $templater->render();
		}
	}

	// $show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups'] AND $vbulletin->options['dl2allowimages'] AND $dimages);

	if ($file['_author'] != '')
	{
		$_author = $file['_author'];
	}
	else if (($file['_author'] == '') AND ($file['author'] != ''))
	{
		$_author = htmlspecialchars_uni($file['author']);
	}
	else
	{
		$_author = $vbphrase['dl2_unknown'];
	}

	$category_array = $dl->construct_select_array(0, array('#' => $vbphrase['dl2_category_jump']), '');
	foreach ($category_array AS $cat_key => $cat_value)
	{
		$category_jump .= '<option value="'.$cat_key.'">'.$cat_value.'</option>';
	}

	if ($vbulletin->options['dl2allowcomments'])
	{
		$show['cancomment'] = false;
		if ($permissions[downloads2permissions] & $vbulletin->bf_ugp[downloads2permissions][cancomment])
		{
			$show['cancomment'] = true;

			require_once(DIR . '/includes/functions_editor.php');
			$editorid = construct_edit_toolbar('', false, 'nonforum', $vbulletin->options['allowsmilies'], true, false, 'qr'); // todo: recheck $editorid
		}

		if ($file['totalcomments'] > 0)
		{
			$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);

			sanitize_pageresults($file['totalcomments'], $pagenumber, $vbulletin->options['dl2perpage'], $vbulletin->options['dl2perpage'], $vbulletin->options['dl2perpage']);

			$limit = ($pagenumber -1) * $vbulletin->options['dl2perpage'];
			$pagenav = construct_page_nav($pagenumber, $vbulletin->options['dl2perpage'], $file['totalcomments'], "downloads.php?" . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$file[id]");

			$result = $db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "dl2_comments
				WHERE `fileid` = $file[id]
				LIMIT $limit, " . $vbulletin->options['dl2perpage']
			);
			while ($comment = $db->fetch_array($result))
			{
				$comment['date'] = vbdate($vbulletin->options['dateformat'], $comment['date'], true)." at ".vbdate($vbulletin->options['timeformat'], $comment['date'], true);
				$comment['message'] = $bbcode_parser->do_parse($comment['message'], false, true, true, true, true, $cachable);
				if ((($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['candeleteowncomments']) AND ($comment['authorid'] == $vbulletin->userinfo['userid'])) OR
					($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['candeleteallcomments']))
				{
					$show['caneditcomment'] = true;
				}
				else
				{
					$show['caneditcomment'] = false;
				}

				$templater = vB_Template::create('downloads2_file_commentbit');
					$templater->register('comment', $comment);
					$templater->register('file', $file);
				$comments .= $templater->render();
			}
			$db->free_result($result);
		}
	}

	$templater = vB_Template::create('downloads2_file');
		$templater->register('file', $file);
		$templater->register('_author', $_author);
		$templater->register('errorlist', $errorlist);
		$templater->register('dimages', $dimages);
		$templater->register('inimaxattach', $inimaxattach);
		$templater->register('comments', $comments);
		$templater->register('pagenav', $pagenav);
		$templater->register('vBeditTemplate', $vBeditTemplate);
		$templater->register('editorid', $editorid);
		$templater->register('messagearea', $messagearea);
		$templater->register('category_jump', $category_jump);
		$templater->register('gobutton', $gobutton);
	$dmain_jr = $templater->render();

	if ($vbulletin->options['dl2showtops'] & 4)
	{
		$templater = vB_Template::create('downloads2_panel_side');
			$templater->register('dlstats', $dlstats);
		$dpanel = $templater->render();

		$templater = vB_Template::create('downloads2_wrapper_side');
			$templater->register('dlcustomtitle', $dlcustomtitle);
			$templater->register('dmain_jr', $dmain_jr);
			$templater->register('dpanel', $dpanel);
		$dmain = $templater->render();
	}
	else
	{
		$templater = vB_Template::create('downloads2_wrapper_none');
			$templater->register('dmain_jr', $dmain_jr);
		$dmain = $templater->render();
	}
}
else if ($_GET['do'] == 'report')
{
	if (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canviewfiles'])
	 OR !($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canreportfiles']))
	{
		print_no_permission();
	}

	$filesexclude = $dl->exclude_files();

	$vbulletin->input->clean_array_gpc('r', array(
		'fileid'         => TYPE_UINT,
		'reason'         => TYPE_NOHTML
	));
	$cleanfileid = $vbulletin->GPC['fileid'];

	$file = $db->query_first("
		SELECT id, category, title
		FROM " . TABLE_PREFIX . "dl2_files
		WHERE $filesexclude
		id = $cleanfileid
	");

	if ($file['id'] == 0)
	{
		eval(print_standard_redirect('dl2_msg_invalid_file', true, true));
	}

	$dlcustomtitle = $vbphrase['dl2_report_file'];
	$file['title'] = htmlspecialchars_uni($file['title']);

	$vbulletin->url = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$file[id]";

	$navbits += $dl->build_cat_nav($file['category']);
	$navbits['downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$file[id]"] = $file['title'];
	$navbits['downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=report&amp;id=$file[id]"] = $vbphrase['dl2_report_file'];

	if ($vbulletin->GPC['reason'] != '')
	{
		// Make it possible to override the default report method
		$overridereport = false;

		($hook = vBulletinHook::fetch_hook('dl2_alt_report')) ? eval($hook) : false;

		if ($overridereport == false)
		{
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dl2_reports
					(fileid, username, userid, date, reason, ipaddress)
				VALUES
					($file[id], '" . $db->escape_string($vbulletin->userinfo['username']) . "', " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ", '" . $db->escape_string($vbulletin->GPC['reason']) . "', '" . $db->escape_string(IPADDRESS) . "')
			");
		}

		eval(print_standard_redirect('dl2_msg_file_reported', true, true));
	}

	$templater = vB_Template::create('downloads2_report');
		$templater->register('file', $file);
	$dmain_jr = $templater->render();

	if ($vbulletin->options['dl2showtops'] & 16)
	{
		$templater = vB_Template::create('downloads2_panel_side');
			$templater->register('dlstats', $dlstats);
		$dpanel = $templater->render();

		$templater = vB_Template::create('downloads2_wrapper_side');
			$templater->register('dlcustomtitle', $dlcustomtitle);
			$templater->register('dmain_jr', $dmain_jr);
			$templater->register('dpanel', $dpanel);
		$dmain = $templater->render();
	}
	else
	{
		$templater = vB_Template::create('downloads2_wrapper_none');
			$templater->register('dmain_jr', $dmain_jr);
		$dmain = $templater->render();
	}
}
else if ($_GET['do'] == 'add' OR $_GET['do'] == 'edit')
{
	if ($_GET['do'] == 'add')
	{
		$navbits['downloads.php?' . $vbulletin->session->vars['sessionurl'] . 'do=add'] = $vbphrase['dl2_add_file'];
		$dlcustomtitle = $vbphrase['dl2_add_file'];
		if (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadfiles']) AND
			!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canlinktofiles']))
		{
			print_no_permission();
		}
	}
	else if ($_GET['do'] == 'edit')
	{
		$cleanfileid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
		$file = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_files WHERE `id` = $cleanfileid");
		if ($file['id'] == 0)
		{
			eval(print_standard_redirect('dl2_msg_invalid_file', true, true));
		}

		$navbits['downloads.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit'] = $vbphrase['dl2_edit_file'];
		$dlcustomtitle = $vbphrase['dl2_edit_file'];
		if (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditallfiles']) AND
		   (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditownfiles']) OR
		   ($file['uploaderid'] != $vbulletin->userinfo['userid'])))
		{
			print_no_permission();
		}
	}

	require_once(DIR . '/includes/functions_editor.php');
	// $textareacols = fetch_textarea_width(); // deprecated (gives white page)

	if ($_POST['submit'] != '')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'title'          => TYPE_STR,
			'author'         => TYPE_STR,
			'message'        => TYPE_STR,
			'wysiwyg'        => TYPE_BOOL,
			'category'       => TYPE_UINT,
			'pin'            => TYPE_BOOL,
			'uploader'       => TYPE_NOHTML,
			'link'           => TYPE_STR,
			'size'           => TYPE_STR // leave as STR
		));

		$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

		// Hook for pre-upload checks
		($hook = vBulletinHook::fetch_hook('dl2_pre_upload')) ? eval($hook) : false;

		// Check if the user has exceeded the max upload amount
		if ($permissions['downloadsmaxuploadtotal'] >= 0 AND @filesize($vbulletin->GPC['upload']['tmp_name']) > 0)
		{
			$vbulletin->url = 'downloads.php' . $vbulletin->session->vars['sessionurl_q'];

			// Max is set to zero so ... no downloads unless override is set for usergroup
			if (($permissions['downloadsmaxuploadtotal'] == 0))
			{
				eval(print_standard_redirect('dl2_upload_amount_exceeded', true, true));
			}

			// Check amount downloaded against maxdaily
			if ($permissions['downloadsmaxuploadtotal'] > 0)
			{
				$tempnew = $db->query_first("SELECT SUM(size) AS uploadedsize FROM " . TABLE_PREFIX . "dl2_files WHERE `uploaderid` = " . $vbulletin->userinfo['userid']);

				$size = @filesize($vbulletin->GPC['upload']['tmp_name']);
				$dlremaining = $permissions['downloadsmaxuploadtotal'] * 1048576 - ($tempnew['uploadedsize'] + $size);
				$db->free_result($tempnew);

				if ($dlremaining < 0)
				{
					eval(print_standard_redirect('dl2_upload_amount_will_be_exceeded', true, true));
				}
			}
		}

		// Empty errors array
		$errors = array();

		if ($vbulletin->GPC['title'] == '')
		{
			$errors['message'][] = $vbphrase['dl2_fill_in_title'];
		}

		if ($vbulletin->GPC['author'] != '')
		{
			if ($vbulletin->options['dl2namesugg'] != 'disabled')
			{
				$authors = explode(';', $vbulletin->GPC['author']);
				foreach ($authors AS $key => $value)
				{
					$value = htmlspecialchars_uni(trim($value));
					$author = $db->query_first("SELECT userid, username FROM " . TABLE_PREFIX . "user WHERE `username` = '" . $db->escape_string($value) . "'");
					if ($author['userid'] > 0)
					{
						$authors[$key] = '<a href="member.php?u=' . $author['userid'] . '">' . $author['username'] . '</a>';
					}
					else
					{
						$authors[$key] = $value;
						if ($authors[$key] == '')
						{
							unset($authors[$key]);
						}
					}
				}
				$_author = implode(', ', $authors);
			}
			else
			{
				$_author = htmlspecialchars_uni($vbulletin->GPC['author']);
			}
		}

		if ($vbulletin->GPC['wysiwyg'])
		{
			require_once(DIR . '/includes/functions_wysiwyg.php');
			$vbulletin->GPC['message'] = convert_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], 0);
		}

		require_once(DIR . '/includes/functions_newpost.php');
		$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);

		if (empty($vbulletin->GPC['category']))
		{
			$errors['message'][] = $vbphrase['dl2_fill_in_category'];
		}

		// Assign new uploader
		if (($_GET['do'] == 'edit') AND ($vbulletin->GPC['uploader'] != ''))
		{
			$temp = $db->query_first("
				SELECT username, userid
				FROM " . TABLE_PREFIX . "user
				WHERE username = '" . $db->escape_string($vbulletin->GPC['uploader']) . "'
			");

			if ($temp['username'] == '')
			{
				$errors['message'][] = $vbphrase['dl2_no_such_user'];
			}
		}

		if ($vbulletin->GPC['upload']['name'] != '' AND ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadfiles']))
		{
			$link = 0;
			$upload = true;
			$ext = strtolower(file_extension($vbulletin->GPC['upload']['name']));
		}
		else if ($vbulletin->GPC['link'] != '' AND ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canlinktofiles']))
		{
			$link = 1;
			$upload = false;
			$ext = strtolower(file_extension($vbulletin->GPC['link']));
		}
		else if ($_GET['do'] != 'edit')
		{
			$errors['message'][] = $vbphrase['dl2_must_submit_file'];
		}

		if ($upload == true)
		{
			if ($vbulletin->GPC['upload']['error'] != 0)
			{
				// Fatal upload error (error type 1-8)
				// http://www.php.net/manual/en/features.file-upload.errors.php
				eval(standard_error(fetch_error('dl2_upload_error', $vbulletin->GPC['upload']['error'])));
			}

			if (!is_uploaded_file($vbulletin->GPC['upload']['tmp_name']))
			{
				// Fatal upload error (error type "file")
				// Possible hack attempt
				eval(standard_error(fetch_error('dl2_upload_error', 'file')));
			}

			$extension = $db->query_first("
				SELECT *
				FROM " . TABLE_PREFIX . "dl2_extensions
				WHERE `extension` = '" . $db->escape_string($ext) . "'
					AND `mode` <> 2
					AND `enabled` = 1
				LIMIT 1
			");
			if ($extension['extension'] === $ext)
			{
				if (($extension['size'] > 0) AND ($extension['size'] < @filesize($vbulletin->GPC['upload']['tmp_name'])))
				{
					$errors['message'][] = construct_phrase($vbphrase['dl2_error_too_big'], vb_number_format($extension['size'], 0, true));
				}

				if (($extension['width'] > 0) OR ($extension['height'] > 0))
				{
					list($width, $height, $type, $attr) = @getimagesize($vbulletin->GPC['upload']['tmp_name']);

					if (($extension['width'] > 0) AND ($extension['width'] < $width))
					{
						$errors['message'][] = construct_phrase($vbphrase['dl2_error_too_broad'], $extension['width']);
					}

					if (($extension['height'] > 0) AND ($extension['height'] < $height))
					{
						$errors['message'][] = construct_phrase($vbphrase['dl2_error_too_high'], $extension['height']);
					}
				}
			}
			else
			{
				$query = $db->query_read("
					SELECT *
					FROM " . TABLE_PREFIX . "dl2_extensions
					WHERE `mode` <> 2
						AND `enabled` = 1
				");
				while ($extensioning = $vbulletin->db->fetch_array($query))
				{
					$extensionlist .= '.' . $extensioning['extension'] . ' ';
				}

				$errors['message'][] = $vbphrase['dl2_error_invalid_extension'] . ': ' . $extensionlist;
			}
		}

		if (empty($errors))
		{
			if ($upload)
			{
				$newfilename = (TIMENOW%100000) . '-' . $vbulletin->GPC['upload']['name'];
				if (move_uploaded_file($vbulletin->GPC['upload']['tmp_name'], $dl->url.$newfilename))
				{
					chmod($dl->url.$newfilename, 0666);
					$size = @filesize($dl->url.$newfilename);
				}
				else
				{
					// Fatal upload error (error type "move")
					eval(standard_error(fetch_error('dl2_upload_error', 'move')));
				}
			}
			else if ($link)
			{
				$newfilename = $vbulletin->GPC['link'];

				if ($vbulletin->GPC['size'] == '')
				{
					$size = @filesize($newfilename);
					if ($size == false)
					{
						$size = 0;
					}
				}
				else
				{
					if (is_numeric($vbulletin->GPC['size']))
					{
						$size = $vbulletin->GPC['size'];
					}
					else
					{
						$size = 0;
					}
				}

				// check for http on beginning of link or d/l won't work
				if (strpos($newfilename, 'http://') === false AND strpos($newfilename, 'https://') === false AND strpos($newfilename, 'ftp://') === false)
				{
					$newfilename = 'http://' . $newfilename;
				}
			}
			else if ($_GET['do'] == 'edit')
			{
				$newfilename = $file['url'];
				$size = $file['size'];
				$link = $file['link'];
				$ext = $file['extension'];
			}

			if ($_GET['do'] == 'add')
			{
				$modqueue = ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canavoidmodqueue']) ? 0 : 1;

				$db->query_write("
					INSERT INTO " . TABLE_PREFIX . "dl2_files
						(`title`, `description`, `author`, `_author`, `uploader`, `uploaderid`, `url`, `extension`, `dateadded`, `category`, `size`, `pin`, `modqueue`, `link`)
					VALUES
						(
							'" . $db->escape_string($vbulletin->GPC['title']) . "',
							'" . $db->escape_string($vbulletin->GPC['message']) . "',
							'" . $db->escape_string($vbulletin->GPC['author']) . "',
							'" . $db->escape_string($_author) . "',
							'" . $db->escape_string($vbulletin->userinfo['username']) . "',
							" . $vbulletin->userinfo['userid'] . ",
							'" . $db->escape_string($newfilename) . "',
							'" . $db->escape_string($ext) . "',
							" . TIMENOW . ",
							" . $vbulletin->GPC['category'] . ",
							" . $size . ",
							" . $vbulletin->GPC['pin'] . ",
							" . $modqueue . ",
							" . $link . "
						)
				");
			}
			else if ($_GET['do'] == 'edit')
			{
				$modqueue = $file['modqueue'];

				if ($temp['username'] != '')
				{
					$updatequery = ", `uploader` = '" . $db->escape_string($temp['username']) . "', `uploaderid` = " . $temp['userid'];
				}

				$db->query_write("
					UPDATE " . TABLE_PREFIX . "dl2_files SET
						`title` = '" . $db->escape_string($vbulletin->GPC['title']) . "',
						`description` = '" . $db->escape_string($vbulletin->GPC['message']) . "',
						`author` = '" . $db->escape_string($vbulletin->GPC['author']) . "',
						`_author` = '" . $db->escape_string($_author) . "',
						`url` = '" . $db->escape_string($newfilename) . "',
						`extension` = '" . $db->escape_string($ext) . "',
						`category` = " . $vbulletin->GPC['category'] . ",
						`size` = " . $size . ",
						`pin` = " . $vbulletin->GPC['pin'] . ",
						`modqueue` = " . $modqueue . ",
						`link` = " . $link . ",
						`lastedit` = " . TIMENOW . ",
						`lasteditor` = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
						`lasteditorid` = " . $vbulletin->userinfo['userid'] .
						$updatequery . "
					WHERE id = $file[id]
				");
			}

			if ($_GET['do'] == 'add')
			{
				$id = $db->insert_id();

				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `files` = `files` + 1");

				$dl->modify_filecount($vbulletin->GPC['category'], 1);
				$dl->modify_filecount_user($vbulletin->userinfo['userid']);
				$dl->update_counters();

				$vbulletin->url = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$id";

				// hook for post-upload checks
				($hook = vBulletinHook::fetch_hook('dl2_post_upload_add')) ? eval($hook) : false;

				eval(print_standard_redirect('dl2_msg_file_added', true, true));
			}
			else if ($_GET['do'] == 'edit')
			{
				if ($upload)
				{
					// New upload was successfull, so remove the old file
					@unlink($dl->url . $file['url']);
				}
				if ($file['category'] != $vbulletin->GPC['category'])
				{
					$dl->modify_filecount($vbulletin->GPC['category'], 1);
					$dl->modify_filecount_delete($file['category'], -1);
				}
				// Asign new uploader
				if ($temp['username'] != '')
				{
					$dl->modify_filecount_user($temp['userid']);
					$dl->modify_filecount_user($file['uploaderid']);
				}
				$dl->update_counters();

				$vbulletin->url = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$file[id]";

				// hook for post-upload checks
				($hook = vBulletinHook::fetch_hook('dl2_post_upload_edit')) ? eval($hook) : false;

				eval(print_standard_redirect('dl2_msg_file_edited', true, true));
			}

		}
		else
		{
			// Error handling
			$show['errors'] = true;
			foreach ($errors['message'] AS $errormessage)
			{
				$templater = vB_Template::create('newpost_errormessage');
					$templater->register('errormessage', $errormessage);
				$errorlist .= $templater->render();
			}

			// Reprinting the add or edit screen
			$newfile['title'] = htmlspecialchars_uni($vbulletin->GPC['title']);
			$newfile['author'] = htmlspecialchars_uni($vbulletin->GPC['author']);
			$newfile['message'] = htmlspecialchars_uni($vbulletin->GPC['message']);
			$newfile['category'] =& $vbulletin->GPC['category'];
			$newfile['uploader'] = htmlspecialchars_uni($vbulletin->GPC['uploader']);
			$newfile['link'] =& $link;
			$newfile['url'] = htmlspecialchars_uni($vbulletin->GPC['link']); // Strange, I know, but it's like this (Jelle)
			$newfile['size'] = htmlspecialchars_uni($vbulletin->GPC['size']); // Because it's STR

			if ($vbulletin->GPC['pin'])
			{
				$pinned = 'checked="checked"';
			}
			else
			{
				$pinned = '';
			}

			if ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadfiles'])
			{
				require_once(DIR . '/includes/functions_file.php');
				$inimaxattach = fetch_max_upload_size();
			}
		}
	}
	else
	{
		// Nothing submitted, printing a clean new add or edit screen
		if ($_GET['do'] == 'edit')
		{
			$newfile['title'] = htmlspecialchars_uni($file['title']);
			$newfile['author'] = htmlspecialchars_uni($file['author']);
			$newfile['message'] = htmlspecialchars_uni($file['description']);
			$newfile['category'] =& $file['category'];
			$newfile['link'] =& $file['link'];
			$newfile['url'] = htmlspecialchars_uni($file['url']);
			$newfile['size'] =& $file['size'];

			if ($file['pin'])
			{
				$pinned = 'checked="checked"';
			}
			else
			{
				$pinned = '';
			}
		}
		else
		{
			$newfile['category'] = $vbulletin->input->clean_gpc('r', 'cat', TYPE_UINT);
			$pinned = '';
		}

		if ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadfiles'])
		{
			require_once(DIR . '/includes/functions_file.php');
			$inimaxattach = fetch_max_upload_size();
		}
	}

	// Get the message editor for the description
	$editorid = construct_edit_toolbar($newfile['message'], false, 'nonforum', $vbulletin->options['allowsmilies']);

	$category_array = $dl->construct_select_array(0, array('' => '----------'), '');
	foreach ($category_array AS $cat_key => $cat_value)
	{
		if ($newfile['category'] == $cat_key)
		{
			$selected = 'selected="selected"';
		}
		else
		{
			$selected = '';
		}
		$category_select .= '<option value="'.$cat_key.'" '.$selected.'>'.$cat_value.'</option>';
	}

	$show['uploadfiles'] = $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadfiles'];
	$show['linktofiles'] = $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canlinktofiles'];

	$templater = vB_Template::create('downloads2_file_addit');
		$templater->register('file', $file);
		$templater->register('newfile', $newfile);
		$templater->register('dlcustomtitle', $dlcustomtitle);
		$templater->register('errorlist', $errorlist);
		$templater->register('editorid', $editorid);
		$templater->register('messagearea', $messagearea);
		$templater->register('category_select', $category_select);
		$templater->register('pinned', $pinned);
		$templater->register('inimaxattach', $inimaxattach);
	$dmain_jr = $templater->render();

	if ($vbulletin->options['dl2showtops'] & 8)
	{
		$templater = vB_Template::create('downloads2_panel_side');
			$templater->register('dlstats', $dlstats);
		$dpanel = $templater->render();

		$templater = vB_Template::create('downloads2_wrapper_side');
			$templater->register('dlcustomtitle', $dlcustomtitle);
			$templater->register('dmain_jr', $dmain_jr);
			$templater->register('dpanel', $dpanel);
		$dmain = $templater->render();
	}
	else
	{
		$templater = vB_Template::create('downloads2_wrapper_top');
			$templater->register('dmain_jr', $dmain_jr);
		$dmain = $templater->render();
	}
}
else if ($_GET['do'] == 'manfiles')
{
	$navbits['downloads.php?' . $vbulletin->session->vars['sessionurl'] . 'do=manfiles'] = $vbphrase['dl2_manage_files'];
	$dlcustomtitle = $vbphrase['dl2_manage_files'];

	if ($_GET['act'] == 'updatecounters')
	{
		$dl->update_counters_all();
		$vbulletin->url = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . 'do=manfiles';
		eval(print_standard_redirect('dl2_msg_counters_updated', true, true));
	}

	// check for category permissions
	$filesexclude = $dl->exclude_files();

	$file = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_files WHERE $filesexclude `id` = " . $db->sql_prepare($_GET['id']));
	if (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditallfiles']) OR
	   (($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditownfiles']) AND
	   ($file['uploaderid'] == $vbulletin->userinfo['userid'])))
	{
		$showedit = true;
	}
	if ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canmanagemodqueue'])
	{
		$showapprove = true;
	}
	if (!$showedit AND !$showapprove)
	{
		print_no_permission();
	}

	if ($_GET['redir'] == 'manfiles')
	{
		if ($_GET['category'] != '')
		{
			$_GET['category'] = $vbulletin->input->clean_gpc('r', 'category', TYPE_UINT);
		}
		if ($_GET['pin'] != '')
		{
			$_GET['pin'] = $vbulletin->input->clean_gpc('r', 'pin', TYPE_UINT);
		}
		if ($_GET['approval'] != '')
		{
			$_GET['approval'] = $vbulletin->input->clean_gpc('r', 'approval', TYPE_UINT);
		}
		$_GET['page'] = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);
		$vbulletin->url = 'downloads.php?do=manfiles&category='.$_GET['category'].'&pin='.$_GET['pin'].'&approval='.$_GET['approval'].'&page='.$_GET['page'];
	}
	else if ($_GET['redir'] == 'file')
	{
		$cleanfileid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
		$vbulletin->url = 'downloads.php?' . $vbulletin->session->vars['sessionurl'] . "do=file&amp;id=$cleanfileid";
	}
	else
	{
		$vbulletin->url = 'downloads.php' . $vbulletin->session->vars['sessionurl_q'];
	}

	if ($_GET['act'] == '')
	{
	}
	else if ($_GET['act'] == 'approve' AND $showapprove)
	{
		$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `modqueue`='0' WHERE `id` = ".$db->sql_prepare($_GET['id']));
		$dl->update_counters();
		eval(print_standard_redirect('dl2_msg_file_approved', true, true));
	}
	else if ($_GET['act'] == 'unapprove' AND $showapprove)
	{
		$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `modqueue`='1' WHERE `id` = ".$db->sql_prepare($_GET['id']));
		$dl->update_counters();
		eval(print_standard_redirect('dl2_msg_file_unapproved', true, true));
	}
	else if ($_GET['act'] == 'pin' AND $showapprove)
	{
		$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `pin`='1' WHERE `id` = ".$db->sql_prepare($_GET['id']));
		eval(print_standard_redirect('dl2_msg_file_pinned', true, true));
	}
	else if ($_GET['act'] == 'unpin' AND $showapprove)
	{
		$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `pin`='0' WHERE `id` = ".$db->sql_prepare($_GET['id']));
		eval(print_standard_redirect('dl2_msg_file_unpinned', true, true));
	}
	else if ($_GET['act'] == 'delete' AND $showedit)
	{
		$cleanfileid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
		$file = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_files WHERE `id` = $cleanfileid");

		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_files WHERE `id` = $file[id]");
		if (!$file['link'])
		{
			@unlink($dl->url . $file['url']);
		}

		$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_images WHERE `file` = $file[id]");
		while ($image = $db->fetch_array($result))
		{
			@unlink($dl->url . $image['name']);
			@unlink($dl->url . $image['thumb']);
		}
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_images WHERE `file` = $file[id]");
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_comments WHERE `fileid` = $file[id]");
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_votes WHERE `file` = $file[id]");
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_reports WHERE `fileid` = $file[id]");

		$dl->modify_filecount_user($file['uploaderid']);
		$dl->update_counters();
		$dl->modify_filecount_delete($file['category'], -1);

		$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `files`=`files`-1");

		eval(print_standard_redirect('dl2_msg_file_deleted', true, true));
	}
	else if ($_GET['act'] == 'mass' AND ($showedit OR $showapprove) AND $_POST['id'])
	{
		if ($_POST['task'] == 'approve' AND $showapprove)
		{
			foreach ($_POST['id'] AS $id => $value)
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `modqueue`='0' WHERE `id` = " . $db->sql_prepare($id));
			}
			$dl->update_counters();
			eval(print_standard_redirect('dl2_msg_file_approved', true, true));
		}
		else if ($_POST['task'] == 'unapprove' AND $showapprove)
		{
			foreach ($_POST['id'] AS $id => $value)
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `modqueue`='1' WHERE `id` = " . $db->sql_prepare($id));
			}
			$dl->update_counters();
			eval(print_standard_redirect('dl2_msg_file_unapproved', true, true));
		}
		else if ($_POST['task'] == 'pin' AND $showapprove)
		{
			foreach ($_POST['id'] AS $id => $value)
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `pin`='1' WHERE `id` = " . $db->sql_prepare($id));
			}
			eval(print_standard_redirect('dl2_msg_file_pinned', true, true));
		}
		else if ($_POST['task'] == 'unpin' AND $showapprove)
		{
			foreach ($_POST['id'] AS $id => $value)
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `pin`='0' WHERE `id` = " . $db->sql_prepare($id));
			}
			eval(print_standard_redirect('dl2_msg_file_unpinned', true, true));
		}
		else if ($_POST['task'] == 'delete' AND $showedit)
		{
			foreach ($_POST['id'] AS $id => $value)
			{
				$file = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_files WHERE `id` = " . $db->sql_prepare($id));
				if ($file['id'] > 0)
				{
					$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_files WHERE `id` = $file[id]");
					if (!$file['link'])
					{
						@unlink($dl->url . $file['url']);
					}

					$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_images WHERE `file` = $file[id]");
					while ($image = $db->fetch_array($result))
					{
						@unlink($dl->url . $image['name']);
						@unlink($dl->url . $image['thumb']);
					}
					$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_images WHERE `file` = $file[id]");
					$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_comments WHERE `fileid` = $file[id]");
					$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_votes WHERE `file` = $file[id]");
					$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_reports WHERE `fileid` = $file[id]");

					$dl->modify_filecount_delete($file['category'], -1);
				}
				else
				{
					unset($_POST[$id]);
				}
			}
			$dl->update_counters();
			$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `downloads`=`downloads`-".$db->sql_prepare(sizeof($_POST['id'])));
			eval(print_standard_redirect('dl2_msg_file_deleted', true, true));
		}
		else if ($_POST['task'] == 'move' AND $showedit)
		{
			foreach ($_POST['id'] AS $id => $value)
			{
				$file = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_files WHERE `id` = ".$db->sql_prepare($id));
				if ($file['id'] > 0)
				{
					$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET `category`=".$db->sql_prepare($_POST['category'])." WHERE `id` = ".$db->sql_prepare($id));
					$dl->modify_filecount_delete($file['category'], -1);
				}
				else
				{
					unset($_POST[$id]);
				}
			}
			$dl->modify_filecount($_POST['category'], sizeof($_POST['id']));
			eval(print_standard_redirect('dl2_msg_file_moved', true, true));
		}
	}

	$category_array = $dl->construct_select_array(0, array('' => '['.$vbphrase['dl2_category'].']'), '');
	foreach ($category_array AS $cat_key => $cat_value)
	{
		$category_select .= '<option value="'.$cat_key.'">'.$cat_value.'</option>';
	}

	$params = '&amp;redir=manfiles';

	if ($_GET['category'] != '' AND $_GET['category'] != 0)
	{
		$cleancatid = $vbulletin->input->clean_gpc('r', 'category', TYPE_UINT);
		$category = 'category = '.$cleancatid;
		$params .= '&amp;category='.$cleancatid;
	}
	else
	{
		$category = 'category != -1';
	}

	if ($_GET['pin'] == '0')
	{
		$pin = ' AND pin = 0';
		$params .= '&amp;pin=0';
	}
	else if ($_GET['pin'] == '1')
	{
		$pin = ' AND pin = 1';
		$params .= '&amp;pin=1';
	}
	else
	{
		$pin = '';
	}

	if ($_GET['approval'] == '0')
	{
		$approval = ' AND modqueue = 1';
		$params .= '&amp;approval=0';
		$cleanapprove = 0;
	}
	else if ($_GET['approval'] == '1')
	{
		$approval = ' AND modqueue = 0';
		$params .= '&amp;approval=1';
		$cleanapprove = 1;
	}
	else
	{
		$approval = '';
		$cleanapprove = 1;
	}
		
	$temp = $db->query_first("SELECT COUNT(*) AS files FROM " . TABLE_PREFIX . "dl2_files WHERE $filesexclude ".$category.$pin.$approval);

	$cleanpin = $vbulletin->input->clean_gpc('r', 'pin', TYPE_UINT);
	$cleancatid = $vbulletin->input->clean_gpc('r', 'category', TYPE_UINT);
	// $cleanapprove = $vbulletin->input->clean_gpc('r', 'approval', TYPE_UINT);
	$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);

	sanitize_pageresults($temp['files'], $pagenumber, $vbulletin->options['dl2perpage'], $vbulletin->options['dl2perpage'], $vbulletin->options['dl2perpage']);

	$limit = ($pagenumber -1) * $vbulletin->options['dl2perpage'];
	$pagenav = construct_page_nav($pagenumber, $vbulletin->options['dl2perpage'], $temp['files'], "downloads.php?" . $vbulletin->session->vars['sessionurl'] . "do=manfiles&amp;pin=$cleanpin&amp;approval=$cleanapprove", ""
		. (!empty($cleancatid) ? "&amp;category=$cleancatid" : "")
	);

	$params .= '&amp;page='.$pagenumber;
	
	$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_files WHERE $filesexclude " . $category . $pin . $approval . " ORDER BY `id` DESC LIMIT $limit, " . $vbulletin->options['dl2perpage']);
	if ($db->num_rows($result) > 0)
	{
		while ($file = $db->fetch_array($result))
		{
			exec_switch_bg();
			$file['title'] = htmlspecialchars_uni($file['title']);
			if ($file['modqueue'] == 0)
			{
				$info = ' <span style="color: blue;">' . $vbphrase['dl2_approved'] . '</span>';
			}
			else
			{
				$info = ' <span style="color: red;">' . $vbphrase['dl2_unapproved'] . '</span>';
			}
			if ($file['pin'] == 1)
			{
				$info .= ', ' . $vbphrase['dl2_pinned'];
			}

			if (($_GET['act'] != 'nolink') OR !file_exists($dl->url.$file['url']))
			{
				$templater = vB_Template::create('downloads2_man_bit');
					$templater->register('file', $file);
					$templater->register('info', $info);
					$templater->register('params', $params);
					$templater->register('showapprove', $showapprove);
					$templater->register('showedit', $showedit);
				$dfilebits .= $templater->render();
			}
		}
	}

	$db->free_result($result);

	$templater = vB_Template::create('downloads2_man');
		$templater->register('dfilebits', $dfilebits);
		$templater->register('category_select', $category_select);
		$templater->register('showapprove', $showapprove);
		$templater->register('showedit', $showedit);
		$templater->register('pagenav', $pagenav);
	$dmain_jr = $templater->render();

	if ($vbulletin->options['dl2showtops'] & 64)
	{
		$templater = vB_Template::create('downloads2_panel_side');
			$templater->register('dlstats', $dlstats);
		$dpanel = $templater->render();

		$templater = vB_Template::create('downloads2_wrapper_side');
			$templater->register('dlcustomtitle', $dlcustomtitle);
			$templater->register('dmain_jr', $dmain_jr);
			$templater->register('dpanel', $dpanel);
		$dmain = $templater->render();
	}
	else
	{
		$templater = vB_Template::create('downloads2_wrapper_none');
			$templater->register('dmain_jr', $dmain_jr);
		$dmain = $templater->render();
	}
}
else if ($_GET['do'] == 'search')
{
	if (!($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['cansearchfiles']))
	{
		print_no_permission();
	}

	$navbits['downloads.php?' . $vbulletin->session->vars['sessionurl'] . 'do=search'] = $vbphrase['dl2_search'];
	$dlcustomtitle = $vbphrase['dl2_search'];

	if ($_REQUEST['query'] != '' OR $_REQUEST['author'] != '' OR $_REQUEST['uploader'] != '')
	{
		$vbulletin->input->clean_array_gpc('r', array(
			'query'          => TYPE_STR,
			'titleonly'      => TYPE_BOOL,
			'cat'            => TYPE_UINT,
			'downloadsless'  => TYPE_BOOL,
			'downloadslimit' => TYPE_UINT,
			'commentsless'   => TYPE_BOOL,
			'commentslimit'  => TYPE_UINT,
			'author'         => TYPE_STR,
			'uploader'       => TYPE_NOHTML
		));

		// Check for category permissions
		$filesexclude = $dl->exclude_files();

		$keyword = explode(',', $vbulletin->GPC['query']);
		foreach ($keyword AS $text)
		{
			$text = trim($text);
			if (strlen($text) >= 4)
			{
				$query .= " OR " . TABLE_PREFIX . "dl2_files.title LIKE '%" . $db->escape_string_like($text) . "%'";

				if ($vbulletin->GPC['titleonly'] == 0)
				{
					$query .= " OR " . TABLE_PREFIX . "dl2_files.description LIKE '%" . $db->escape_string_like($text) . "%'";
				}
			}
		}

		// Remove the first " OR " part if necessary
		if (strlen($query) > 0)
		{
			$query = "AND (" . substr($query, 4) . ")";
		}

		if ($vbulletin->GPC['cat'] > 0)
		{
			$searchcat = "AND " . TABLE_PREFIX . "dl2_files.category = " . $vbulletin->GPC['cat'];
		}

		if ($vbulletin->GPC['downloadslimit'] > 0)
		{
			$downloadsless = ($vbulletin->GPC['downloadsless']) ? '<=' : '>=';
			$downloadslimit = "AND " . TABLE_PREFIX . "dl2_files.totaldownloads $downloadsless " . $vbulletin->GPC['downloadslimit'];
		}
		if ($vbulletin->GPC['commentslimit'] > 0)
		{
			$commentsless = ($vbulletin->GPC['commentsless']) ? '<=' : '>=';
			$commentslimit = "AND " . TABLE_PREFIX . "dl2_files.totalcomments $commentsless " . $vbulletin->GPC['commentslimit'];
		}

		if ($vbulletin->GPC['author'] != '')
		{
			$author = "AND " . TABLE_PREFIX . "dl2_files.author LIKE '%" . $db->escape_string_like($vbulletin->GPC['author']) . "%'";
		}
		if ($vbulletin->GPC['uploader'] != '')
		{
			$uploader = "AND " . TABLE_PREFIX . "dl2_files.uploader LIKE '%" . $db->escape_string_like($vbulletin->GPC['uploader']) . "%'";
		}

		$result = $db->query_read("
			SELECT " . TABLE_PREFIX . "dl2_files.*,  " . TABLE_PREFIX . "dl2_categories.name AS catname
			FROM " . TABLE_PREFIX . "dl2_files, " . TABLE_PREFIX . "dl2_categories
			WHERE $filesexclude
				" . TABLE_PREFIX . "dl2_files.category = " . TABLE_PREFIX . "dl2_categories.id
				$query
				$searchcat
				$downloadslimit
				$commentslimit
				$author
				$uploader
		");
		if ($db->num_rows($result) > 0)
		{
			while ($file = $db->fetch_array($result))
			{
				$file['title'] = htmlspecialchars_uni($file['title']);
				$file['catname'] = htmlspecialchars_uni($file['catname']);
				$file['dateadded'] = vbdate($vbulletin->options['dateformat'], $file['dateadded'], true);
				$file['author'] = htmlspecialchars_uni($file['author']);

				if ($vbulletin->options['dl2smalldesc'] > 0)
				{
					$smalldesc = strip_bbcode($file['description'], $stripquotes = false, $fast_and_dirty = false, $showlinks = false);
					$smalldesc = substr($smalldesc, 0, $vbulletin->options['dl2smalldesc']);
					$smalldesc = $vbulletin->input->clean($smalldesc, TYPE_NOHTML);
					$smalldesc = ': ' . $smalldesc;

					if (strlen($file['description']) > $vbulletin->options['dl2smalldesc'])
					{
						$smalldesc .= ' ...';
					}
				}

				// exec_switch_bg(); // deprecated

				$templater = vB_Template::create('downloads2_search_result_bit');
					$templater->register('file', $file);
					$templater->register('smalldesc', $smalldesc);
				$dresultbits .= $templater->render();
			}
		}

		$filters = array();

		$filters['query'] = htmlspecialchars_uni($vbulletin->GPC['query']);
		$filters['author'] = htmlspecialchars_uni($vbulletin->GPC['author']);
		$filters['uploader'] =& $vbulletin->GPC['uploader'];

		$titleonlyselected[$vbulletin->GPC['titleonly']] = ' selected="selected"';
		$downloadslessselected[$vbulletin->GPC['downloadsless']] = ' selected="selected"';
		$commentslessselected[$vbulletin->GPC['commentsless']] = ' selected="selected"';

		$filters['downloadslimit'] =& $vbulletin->GPC['downloadslimit'];
		$filters['commentslimit'] =& $vbulletin->GPC['commentslimit'];

		$templater = vB_Template::create('downloads2_search_result');
			$templater->register('dresultbits', $dresultbits);
		$dresult .= $templater->render();
	}

	$category_array = $dl->construct_select_array(0, array('' => '----------'), '');
	foreach ($category_array AS $cat_key => $cat_value)
	{
		if ($_REQUEST['cat'] == $cat_key)
		{
			$selected = ' selected="selected"';
		}
		else
		{
			$selected = '';
		}
		$category_select .= '<option value="'.$cat_key.'"'.$selected.'>'.$cat_value.'</option>';
	}

	$templater = vB_Template::create('downloads2_search');
		$templater->register('filters', $filters);
		$templater->register('titleonlyselected', $titleonlyselected);
		$templater->register('downloadslessselected', $downloadslessselected);
		$templater->register('commentslessselected', $commentslessselected);
		$templater->register('dresult', $dresult);
		$templater->register('category_select', $category_select);
	$dmain_jr = $templater->render();

	if ($vbulletin->options['dl2showtops'] & 32)
	{
		$templater = vB_Template::create('downloads2_panel_side');
			$templater->register('dlstats', $dlstats);
		$dpanel = $templater->render();

		$templater = vB_Template::create('downloads2_wrapper_side');
			$templater->register('dlcustomtitle', $dlcustomtitle);
			$templater->register('dmain_jr', $dmain_jr);
			$templater->register('dpanel', $dpanel);
		$dmain = $templater->render();
	}
	else
	{
		$templater = vB_Template::create('downloads2_wrapper_none');
			$templater->register('dmain_jr', $dmain_jr);
		$dmain = $templater->render();
	}
}
else
{
	// check for category permissions
	$catexclude = $dl->exclude_cat();

	$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_categories WHERE $catexclude parent = 0 ORDER BY `weight`");
	while ($cat = $db->fetch_array($result))
	{
		$cat['name'] = htmlspecialchars_uni($cat['name']);
		$cat['description'] = htmlspecialchars_uni($cat['description']);
		$cat['files'] = vb_number_format($cat['files']);

		if ($vbulletin->options['dl2subcatsdepth'] > 0)
		{
			$subcats = $dl->grab_subcats_by_name($cat['id']);
		}
		else
		{
			$subcats = '';
		}

		$templater = vB_Template::create('downloads2_main_catbit');
			$templater->register('cat', $cat);
			$templater->register('subcats', $subcats);
		$dcatbits .= $templater->render();
	}
	$db->free_result($result);

	$show['addnewfile'] = ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canuploadfiles'] OR $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canlinktofiles']);
	$show['dlsearch'] = $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['cansearchfiles'];
	$show['manfiles'] = ($permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['caneditallfiles'] OR $permissions['downloads2permissions'] & $vbulletin->bf_ugp['downloads2permissions']['canmanagemodqueue']);

	$dl2_stats_files = vb_number_format($dl->stats['files']);
	$dl2_stats_categories = vb_number_format($dl->stats['categories']);
	$dl2_stats_downloads = vb_number_format($dl->stats['downloads']);

	$templater = vB_Template::create('downloads2_main');
		$templater->register('dcatbits', $dcatbits);
		$templater->register('dl2_stats_files', $dl2_stats_files);
		$templater->register('dl2_stats_categories', $dl2_stats_categories);
		$templater->register('dl2_stats_downloads', $dl2_stats_downloads);
	$dmain_jr = $templater->render();

	if ($vbulletin->options['dl2showtops'] & 1)
	{
		$templater = vB_Template::create('downloads2_panel_side');
			$templater->register('dlstats', $dlstats);
		$dpanel = $templater->render();

		$templater = vB_Template::create('downloads2_wrapper_side');
			$templater->register('dlcustomtitle', $dlcustomtitle);
			$templater->register('dmain_jr', $dmain_jr);
			$templater->register('dpanel', $dpanel);
		$dmain = $templater->render();
	}
	else
	{
		$templater = vB_Template::create('downloads2_wrapper_none');
			$templater->register('dmain_jr', $dmain_jr);
		$dmain = $templater->render();
	}
}

$navbits = construct_navbits($navbits);
$navbar = render_navbar_template($navbits);

($hook = vBulletinHook::fetch_hook('dl2_complete')) ? eval($hook) : false;

$templater = vB_Template::create('DOWNLOADS2');
	$templater->register_page_templates();
	$templater->register('navbar', $navbar);
	$templater->register('dlcustomtitle', $dlcustomtitle);
	$templater->register('dwarning', $dwarning);
	$templater->register('dmain', $dmain);
print_output($templater->render());
?>