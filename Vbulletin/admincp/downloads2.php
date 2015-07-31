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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('logging', 'dl2admin', 'downloads2');
$specialtemplates = array();
$globaltemplates = array();
$actiontemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_downloads2.php');
require_once(DIR . '/includes/functions_newpost.php');

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (empty($_GET['do']))
{
	$_GET['do'] = 'category';
}

$dl = new vB_Downloads();
$categories = $dl->construct_select_array(0, array(0 => $vbphrase['none']), false);

function downloads_categories_admin($id = 0, $categories = array(), $spacer = '')
{
	global $db, $dl, $vbphrase;

	$catexclude = $dl->exclude_cat();

	$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_categories WHERE $catexclude `parent` = $id ORDER BY `weight`");
	while ($category = $db->fetch_array($result))
	{
		$class = fetch_row_bgclass();
		echo '<tr>
			<td class="' . $class . '" width="100%">&nbsp;' . $spacer . htmlspecialchars_uni($category['name']) . '</td>
			<td class="' . $class . '" align="center"><input type="text" class="bginput" name="displayorder[' . $category['id'] . ']" value="' . $category['weight'] . '" tabindex="1" size="2" title="' . $vbphrase['display_order'] . '" /></td>
			<td class="' . $class . '"><a href="downloads2.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editcat&amp;id=' . $category['id'] . '">' . $vbphrase['edit'] . '</a></td>
			<td class="' . $class . '"><a href="downloads2.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delcat&amp;id=' . $category['id'] . '">' . $vbphrase['delete'] . '</a></td>
		</tr>';

		if ($category['subs'] > 0)
		{
			$categories += downloads_categories_admin($category['id'], $categories, $spacer . '- - ');
		}
	}
	$db->free_result($result);

	return $categories;
}

// ###################### Category Functions #######################
if ($_GET['do'] == 'category')
{
	print_cp_header($vbphrase['dl2_download_categories']);

	print_form_header('downloads2', 'doorder');
	print_table_header($vbphrase['dl2_manage_download_categories'], 4);
	print_description_row($vbphrase['dl2_manage_download_categories_desc'], 0, 4);
	echo '<tr>
		<td class="thead">' . $vbphrase['dl2_category_name'] . '</td>
		<td class="thead" align="right" style="white-space:nowrap">' . $vbphrase['display_order'] . '</td>
		<td class="thead" colspan="2">' . $vbphrase['controls'] . '</td>
	</tr>';
	downloads_categories_admin();
	print_submit_row($vbphrase['save_display_order'], $vbphrase['reset'], 4);

	print_form_header('downloads2', 'addcategory', 0, 1, 'cpform2');
	print_submit_row($vbphrase['dl2_add_new_download_category'], '');

	print_cp_footer();
}

// ###################### Do Display Order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'displayorder' 	=> TYPE_NOCLEAN
	));

	$catids = array();

	foreach ($vbulletin->GPC['displayorder'] AS $categoryid => $displayorder)
	{
		$vbulletin->GPC['displayorder']["$categoryid"] = intval($displayorder);
		$catids[] = "'" . $db->escape_string($categoryid) . "'";
	}

	$categories = $db->query_read("
		SELECT id, weight
		FROM " . TABLE_PREFIX . "dl2_categories AS cat
		WHERE id IN (" . implode(', ', $catids) . ")
	");
	while ($cat = $db->fetch_array($categories))
	{
		if ($cat['weight'] != $vbulletin->GPC['displayorder']["$cat[id]"])
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "dl2_categories
				SET weight = " . $vbulletin->GPC['displayorder']["$cat[id]"] . "
				WHERE id = '" . $db->escape_string($cat['id']) . "'
			");
		}
	}

	define('CP_REDIRECT', 'downloads2.php?do=category');
	print_stop_message('saved_display_order_successfully');
}

// ###################### Add Category #######################
if ($_GET['do'] == 'addcategory')
{
	print_cp_header($vbphrase['dl2_download_categories']);
	print_form_header('downloads2', 'doaddcat');
	print_table_header($vbphrase['dl2_add_new_download_category']);
	print_input_row($vbphrase['dl2_category_edit_name_dfn'], 'name', '');
	print_textarea_row($vbphrase['dl2_category_edit_description_dfn'], 'desc', '', 4, 40, true, false);
	print_select_row($vbphrase['dl2_category_edit_parent_dfn'], 'parent', $categories);
	print_input_row($vbphrase['dl2_category_edit_weight_dfn'], 'weight', '');
	print_input_row($vbphrase['dl2_category_edit_catimage_dfn'], 'catimage', '');
	$sort_fields = array(
		'title'      	 => $vbphrase['dl2_file_name'],
		'author'      	 => $vbphrase['dl2_author'],
		'uploader'     	 => $vbphrase['dl2_uploader'],
		'dateadded'      => $vbphrase['dl2_date_added'],
		'totaldownloads' => $vbphrase['dl2_total_downloads'],
		'lastdownload'   => $vbphrase['dl2_last_download'],
		'totalcomments'  => $vbphrase['dl2_total_comments'],
		'rating'         => $vbphrase['dl2_rating']
	);
	print_select_row($vbphrase['dl2_default_sort_field'], 'defaultsortfield', $sort_fields, 'dateadded');
	print_select_row($vbphrase['dl2_default_sort_order'], 'defaultsortorder', array('asc' => $vbphrase['ascending'], 'desc' => $vbphrase['descending']), 'desc');
	print_submit_row($vbphrase['save']);
	print_cp_footer();
}

// ###################### Do Add Category #######################
if ($_POST['do'] == 'doaddcat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'name'             => TYPE_STR,
		'desc'             => TYPE_STR,
		'parent'           => TYPE_UINT,
		'catimage'         => TYPE_STR,
		'weight'           => TYPE_UINT,
		'defaultsortfield' => TYPE_STR,
		'defaultsortorder' => TYPE_STR
	));

	if ($vbulletin->GPC['name'] == '')
	{
		print_stop_message('please_complete_required_fields');
	}
	else
	{
		if ($vbulletin->GPC['parent'] > 0)
		{
			$dl->modify_subcount($vbulletin->GPC['parent'], 1);
			$isSubcat = true;
		}
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "dl2_categories
				(name, description, parent, weight, catimage, defaultsortfield, defaultsortorder)
			VALUES
				(
					'" . $db->escape_string($vbulletin->GPC['name']) . "',
					'" . $db->escape_string($vbulletin->GPC['desc']) . "',
					" . $vbulletin->GPC['parent'] . ",
					" . $vbulletin->GPC['weight'] . ",
					'" . $db->escape_string($vbulletin->GPC['catimage']) . "',
					'" . $db->escape_string($vbulletin->GPC['defaultsortfield']) . "',
					'" . $db->escape_string($vbulletin->GPC['defaultsortorder']) . "'
				)
		");
		if ($db->insert_id() > 0)
		{
			$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `categories` = `categories`+1");

			define('CP_REDIRECT', 'downloads2.php?do=category');
			print_stop_message('dl2_saved_download_category_successfully');
		}
		else
		{
			if ($isSubcat)
			{
				$dl->modify_subcount($vbulletin->GPC['parent'], -1);
			}
			print_stop_message('dl2_error_category_add_failed');
		}
	}
}

// ###################### Do Edit Cat Form #######################
if ($_GET['do'] == 'editcat')
{
	$cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
	$cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_categories WHERE `id` = $cleancatid");
	$dl->unset_subcats($cat['id']);

	print_cp_header($vbphrase['dl2_download_categories']);
	print_form_header('downloads2', 'doeditcat');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['dl2_download_category'], htmlspecialchars_uni($cat['name']), $cat['id']));
	print_input_row($vbphrase['dl2_category_edit_name_dfn'], 'name', $cat['name']);
	print_textarea_row($vbphrase['dl2_category_edit_description_dfn'], 'desc', $cat['description'], 4, 40, true, false);
	print_select_row($vbphrase['dl2_category_edit_parent_dfn'], 'parent', $categories, $cat['parent']);

	$subcats = $dl->grab_subcats_by_name($cat['id']);
	if ($subcats != '')
	{
		$subcats = '<ul>' . $subcats . '</ul>';
	}
	else
	{
		$subcats = $vbphrase['none'];
	}

	construct_hidden_code('cid', $cat['id']);
	construct_hidden_code('pid', $cat['parent']);
	print_label_row($vbphrase['dl2_category_edit_subcats_dfn'], $subcats);

	print_input_row($vbphrase['dl2_category_edit_weight_dfn'], 'weight', $cat['weight']);
	print_input_row($vbphrase['dl2_category_edit_catimage_dfn'], 'catimage', $cat['catimage']);

	$sort_fields = array(
		'title'      	 => $vbphrase['dl2_file_name'],
		'author'      	 => $vbphrase['dl2_author'],
		'uploader'     	 => $vbphrase['dl2_uploader'],
		'dateadded'      => $vbphrase['dl2_date_added'],
		'totaldownloads' => $vbphrase['dl2_total_downloads'],
		'lastdownload'   => $vbphrase['dl2_last_download'],
		'totalcomments'  => $vbphrase['dl2_total_comments'],
		'rating'         => $vbphrase['dl2_rating']
	);
	print_select_row($vbphrase['dl2_default_sort_field'], 'defaultsortfield', $sort_fields, $cat['defaultsortfield']);
	print_select_row($vbphrase['dl2_default_sort_order'], 'defaultsortorder', array('asc' => $vbphrase['ascending'], 'desc' => $vbphrase['descending']), $cat['defaultsortorder']);

	print_submit_row($vbphrase['save']);

	print_cp_footer();
}

// ###################### Do Edit Cat #######################
if ($_POST['do'] == 'doeditcat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'cid'              => TYPE_UINT,
		'pid'              => TYPE_UINT,
		'name'             => TYPE_STR,
		'desc'             => TYPE_STR,
		'parent'           => TYPE_UINT,
		'catimage'         => TYPE_STR,
		'weight'           => TYPE_UINT,
		'defaultsortfield' => TYPE_STR,
		'defaultsortorder' => TYPE_STR
	));

	if ($vbulletin->GPC['cid'] == '')
	{
		print_stop_message('please_complete_required_fields');
	}
	else
	{
		if ($vbulletin->GPC['parent'] != $vbulletin->GPC['pid'])
		{
			$isDifferent = true;
			if ($vbulletin->GPC['pid'] > 0)
			{
				$dl->modify_subcount($vbulletin->GPC['pid'], -1);
			}
			if ($vbulletin->GPC['parent'] > 0)
			{
				$dl->modify_subcount($vbulletin->GPC['parent'], 1);
			}
		}
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "dl2_categories SET 
				name = '" . $db->escape_string($vbulletin->GPC['name']) . "',
				description = '" . $db->escape_string($vbulletin->GPC['desc']) . "',
				parent = " . $vbulletin->GPC['parent'] . ",
				catimage = '" . $db->escape_string($vbulletin->GPC['catimage']) . "',
				weight = " . $vbulletin->GPC['weight'] . ",
				defaultsortfield = '" . $db->escape_string($vbulletin->GPC['defaultsortfield']) . "',
				defaultsortorder = '" . $db->escape_string($vbulletin->GPC['defaultsortorder']) . "'
			WHERE id = " . $vbulletin->GPC['cid']
		);
		if ($db->affected_rows() > 0)
		{
			define('CP_REDIRECT', 'downloads2.php?do=category');
			print_stop_message('dl2_saved_download_category_successfully');
		}
		else
		{
			print_stop_message('dl2_error_category_edit_failed');
		}
	}
}

// ###################### Delete Cat #######################
if ($_GET['do'] == "delcat")
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id' => TYPE_UINT
	));

	$cat = $db->query_first("SELECT id, name FROM " . TABLE_PREFIX . "dl2_categories WHERE `id` = " . $vbulletin->GPC['id']);

	print_cp_header($vbphrase['dl2_download_categories']);
	echo '<p>&nbsp;</p><p>&nbsp;</p>';
	print_form_header('downloads2', 'dodelcat', 0, 1, 'cpform', '75%');
	construct_hidden_code('delete', $cat['id']);
	print_table_header(construct_phrase($vbphrase['dl2_category_removal'], htmlspecialchars_uni($cat['name'])));
	print_description_row(construct_phrase($vbphrase['dl2_category_removal_confirmation'], htmlspecialchars_uni($cat['name'])));
	print_select_row($vbphrase['dl2_category_removal_delete_or_move'], 'destination', $categories);
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
	print_cp_footer();
}

// ###################### Do Delete Cat #######################
if ($_GET['do'] == "dodelcat")
{
	$vbulletin->input->clean_array_gpc('p', array(
		'delete'        => TYPE_UINT,
		'destination'   => TYPE_UINT
	));

	if (empty($vbulletin->GPC['delete']))
	{
		print_stop_message('please_complete_required_fields');
	}
	else if ($vbulletin->GPC['delete'] == $vbulletin->GPC['destination'])
	{
		print_stop_message('dl2_error_cant_move_into_self');
	}
	else if (!$dl->validate_move($vbulletin->GPC['delete'], $vbulletin->GPC['destination']))
	{
		print_stop_message('dl2_error_cannot_move_into_subcat');
	}
	else
	{
		$cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_categories WHERE id = " . $vbulletin->GPC['delete']);
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_categories WHERE id = " . $vbulletin->GPC['delete']);
		if ($db->affected_rows() > 0)
		{
			if ($vbulletin->GPC['destination'] == 0)
			{
				$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_categories WHERE parent = " . $vbulletin->GPC['delete']);
			}
			else
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_categories SET parent = " . $vbulletin->GPC['destination'] . " WHERE parent = " . $vbulletin->GPC['delete']);
			}

			$dl->modify_subcount($cat['parent'], -$db->affected_rows()+$cat['subs']);
			$dl->modify_subcount($vbulletin->GPC['destination'], $db->affected_rows()+$cat['subs']);

			if ($vbulletin->GPC['destination'] == 0)
			{
				$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_files WHERE category = " .$vbulletin->GPC['delete']);
			}
			else
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_files SET category = " . $vbulletin->GPC['destination'] . " WHERE category = " . $vbulletin->GPC['delete']);
			}

			$dl->modify_filecount($cat['parent'], -$db->affected_rows()+$cat['files']);
			$dl->modify_filecount($vbulletin->GPC['destination'], $db->affected_rows()+$cat['files']);

			$dl->update_counters();

			if ($vbulletin->GPC['destination'] == 0)
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `files` = `files`-".$db->sql_prepare($cat['files']));
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `categories` = `categories`-".$db->sql_prepare($cat['subs']+1));

				define('CP_REDIRECT', 'downloads2.php?do=category');
				print_stop_message('dl2_deleted_category_successfully');
			}
			else
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "dl2_main SET `categories` = `categories`-1");

				define('CP_REDIRECT', 'downloads2.php?do=category');
				print_stop_message('dl2_deleted_category_successfully');
			}
		}
		else
		{
			print_stop_message('dl2_error_nothing_to_delete');
		}
	}
}

// ###################### Extensions #######################
if ($_GET['do'] == 'extensions')
{
	print_cp_header($vbphrase['dl2_extensions']);

	print_form_header('downloads2', 'addext');
	print_table_header($vbphrase['dl2_manage_extensions'], 10);
	echo '<tr>
		<td class="thead">' . $vbphrase['dl2_extension'] . '</td>
		<td class="thead">' . $vbphrase['dl2_usage'] . '</td>
		<td class="thead">' . $vbphrase['dl2_maximum_filesize'] . '</td>
		<td class="thead">' . $vbphrase['dl2_max_width'] . '</td>
		<td class="thead">' . $vbphrase['dl2_max_height'] . '</td>
		<td class="thead">' . $vbphrase['enabled'] . '</td>
		<td class="thead">' . $vbphrase['dl2_new_win'] . '</td>
		<td class="thead">' . $vbphrase['dl2_inline'] . '</td>
		<td class="thead" colspan="2">' . $vbphrase['controls'] . '</td>
	</tr>';

	$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_extensions ORDER BY extension");
	while ($extension = $db->fetch_array($result))
	{
		$class = fetch_row_bgclass();

		$extension['size'] = ($extension['size']) ? $extension['size'] : $vbphrase['none'];
		$extension['width'] = ($extension['width']) ? $extension['width'] : $vbphrase['none'];
		$extension['height'] = ($extension['height']) ? $extension['height'] : $vbphrase['none'];
		$extension['enabled'] = ($extension['enabled']) ? $vbphrase['yes'] : $vbphrase['no'];
		$extension['newwindow'] = ($extension['newwindow']) ? $vbphrase['yes'] : $vbphrase['no'];
		$extension['inline'] = ($extension['inline']) ? $vbphrase['yes'] : $vbphrase['no'];

		switch ($extension['mode'])
		{
			case '1':
				$extension['mode'] = $vbphrase['dl2_mode_file'];
				break;
			case '2':
				$extension['mode'] = $vbphrase['dl2_mode_image'];
				break;
			case '3':
				$extension['mode'] = $vbphrase['dl2_mode_both'];
				break;
		}

		echo '<tr>
			<td class="' . $class . '"><strong>' . $extension['extension'] . '</strong></td>
			<td class="' . $class . '">' . $extension['mode'] . '</td>
			<td class="' . $class . '">' . $extension['size'] . '</td>
			<td class="' . $class . '">' . $extension['width'] . '</td>
			<td class="' . $class . '">' . $extension['height'] . '</td>
			<td class="' . $class . '">' . $extension['enabled'] . '</td>
			<td class="' . $class . '">' . $extension['newwindow'] . '</td>
			<td class="' . $class . '">' . $extension['inline'] . '</td>
			<td class="' . $class . '"><a href="downloads2.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editext&amp;extension=' . $extension['extension'] . '">' . $vbphrase['edit'] . '</a></td>
			<td class="' . $class . '"><a href="downloads2.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delext&amp;extension=' . $extension['extension'] . '">' . $vbphrase['delete'] . '</a></td>
		</tr>';
	}

	print_submit_row($vbphrase['dl2_add_new_extension'], 0, 10);

	print_cp_footer();
}

// ###################### Add Extension #######################
if ($_GET['do'] == 'addext')
{
	print_cp_header($vbphrase['dl2_extensions']);
	print_form_header('downloads2', 'doaddext');
	print_table_header($vbphrase['dl2_add_new_extension']);

	print_input_row($vbphrase['dl2_extension'] . '<dfn>(' . $vbphrase['dl2_in_lowercase'] . ')</dfn>', 'extension', '');
	print_input_row($vbphrase['dl2_maximum_filesize'] . '<dfn>(' . $vbphrase['dl2_in_bytes'] . ')</dfn>', 'size', '');
	print_input_row($vbphrase['dl2_maximum_width'] . '<dfn>(' . $vbphrase['dl2_in_pixels'] . ')</dfn>', 'width', '');
	print_input_row($vbphrase['dl2_maximum_height'] . '<dfn>(' . $vbphrase['dl2_in_pixels'] . ')</dfn>', 'height', '');
	print_textarea_row($vbphrase['dl2_mimetype_and_headers_dfn'], 'mimetype', '', 4, 40, true, false);
	print_select_row($vbphrase['dl2_usage_of_this_extension'], 'mode', array('1' => $vbphrase['dl2_mode_ext_file'], '2' => $vbphrase['dl2_mode_ext_image'], '3' => $vbphrase['dl2_mode_ext_both']));
	print_yes_no_row($vbphrase['dl2_open_in_new_window'], 'newwindow', '');
	print_yes_no_row($vbphrase['dl2_open_directly_in_browser_dfn'], 'inline', '');
	print_yes_no_row($vbphrase['enabled'], 'enabled', 1);

	print_submit_row($vbphrase['save']);
	print_cp_footer();
}

// ###################### Do Add Extension #######################
if ($_GET['do'] == 'doaddext')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'extension'     => TYPE_STR,
		'size'          => TYPE_UINT,
		'width'         => TYPE_UINT,
		'height'        => TYPE_UINT,
		'mimetype'      => TYPE_STR,
		'mode'          => TYPE_UINT,
		'newwindow'     => TYPE_UINT,
		'inline'        => TYPE_UINT,
		'enabled'       => TYPE_UINT
	));

	if ($vbulletin->GPC['extension'] == '')
	{
		print_stop_message('please_complete_required_fields');
	} 
	else
	{
		if ($extension = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_extensions WHERE extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "'"))
		{
			print_stop_message('dl2_there_is_already_extension_named_x', htmlspecialchars($vbulletin->GPC['extension']));
		}
		else
		{
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dl2_extensions
					(extension, size, width, height, mimetype, mode, newwindow, inline, enabled)
				VALUES
					(
						'" . $db->escape_string($vbulletin->GPC['extension']) . "',
						" . $vbulletin->GPC['size'] . ",
						" . $vbulletin->GPC['width'] . ",
						" . $vbulletin->GPC['height'] . ",
						'" . $db->escape_string($vbulletin->GPC['mimetype']) . "',
						" . $vbulletin->GPC['mode'] . ",
						" . $vbulletin->GPC['newwindow'] . ",
						" . $vbulletin->GPC['inline'] . ",
						" . $vbulletin->GPC['enabled'] . "
					)
			");

			define('CP_REDIRECT', 'downloads2.php?do=extensions');
			print_stop_message('dl2_saved_extension_successfully');
		}

	}
}

// ###################### Edit Extension #######################
if ($_GET['do'] == 'editext')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'extension' => TYPE_STR
	));

	$extension = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_extensions WHERE extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "'");

	print_cp_header($vbphrase['dl2_extensions']);
	print_form_header('downloads2', 'doeditext');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['dl2_extension'], $extension['extension'], $extension['extension']));

	print_input_row($vbphrase['dl2_extension'] . '<dfn>(' . $vbphrase['dl2_in_lowercase'] . ')</dfn>', 'extension', $extension['extension']);
	construct_hidden_code('oldname', $extension['extension']);
	print_input_row($vbphrase['dl2_maximum_filesize'] . '<dfn>(' . $vbphrase['dl2_in_bytes'] . ')</dfn>', 'size', $extension['size']);
	print_input_row($vbphrase['dl2_maximum_width'] . '<dfn>(' . $vbphrase['dl2_in_pixels'] . ')</dfn>', 'width', $extension['width']);
	print_input_row($vbphrase['dl2_maximum_height'] . '<dfn>(' . $vbphrase['dl2_in_pixels'] . ')</dfn>', 'height', $extension['height']);
	print_textarea_row($vbphrase['dl2_mimetype_and_headers_dfn'], 'mimetype', $extension['mimetype'], 4, 40, true, false);
	print_select_row($vbphrase['dl2_usage_of_this_extension'], 'mode', array('1' => $vbphrase['dl2_mode_ext_file'], '2' => $vbphrase['dl2_mode_ext_image'], '3' => $vbphrase['dl2_mode_ext_both']), $extension['mode']);
	print_yes_no_row($vbphrase['dl2_open_in_new_window'], 'newwindow', $extension['newwindow']);
	print_yes_no_row($vbphrase['dl2_open_directly_in_browser_dfn'], 'inline', $extension['inline']);
	print_yes_no_row($vbphrase['enabled'], 'enabled', $extension['enabled']);

	print_submit_row($vbphrase['save']);
	print_cp_footer();
}

// ###################### Do Edit Extension #######################
if ($_POST['do'] == 'doeditext')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'extension'     => TYPE_STR,
		'oldname'       => TYPE_STR,
		'size'          => TYPE_UINT,
		'width'         => TYPE_UINT,
		'height'        => TYPE_UINT,
		'mimetype'      => TYPE_STR,
		'mode'          => TYPE_UINT,
		'newwindow'     => TYPE_UINT,
		'inline'        => TYPE_UINT,
		'enabled'       => TYPE_UINT
	));

	$vbulletin->GPC['extension'] = preg_replace('#[^a-z0-9_]#i', '', $vbulletin->GPC['extension']);
	$vbulletin->GPC['extension'] = strtolower($vbulletin->GPC['extension']);

	if ($vbulletin->GPC['extension'] == '' OR $vbulletin->GPC['oldname'] == '')
	{
		print_stop_message('please_complete_required_fields');
	}
	else
	{
		if (($vbulletin->GPC['extension'] != $vbulletin->GPC['oldname'])
		   AND ($extension = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "dl2_extensions WHERE extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "'")))
		{
			print_stop_message('dl2_there_is_already_extension_named_x', htmlspecialchars($vbulletin->GPC['extension']));
		}
		else
		{

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "dl2_extensions SET 
					extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "', 
					size = " . $vbulletin->GPC['size'] . ", 
					width = " . $vbulletin->GPC['width'] . ",
					height = " . $vbulletin->GPC['height'] . ",
					mimetype = '" . $db->escape_string($vbulletin->GPC['mimetype']) . "',
					mode = " . $vbulletin->GPC['mode'] . ",
					newwindow = " . $vbulletin->GPC['newwindow'] . ",
					inline = " . $vbulletin->GPC['inline'] . ",
					enabled = " . $vbulletin->GPC['enabled'] . " 
				WHERE extension = '" . $db->escape_string($vbulletin->GPC['oldname']) . "'
			");

			define('CP_REDIRECT', 'downloads2.php?do=extensions');
			print_stop_message('dl2_saved_extension_successfully');
		}
	}
}

// ###################### Delete Extension ####################
if ($_GET['do'] == 'delext')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'extension' => TYPE_STR
	));

	print_cp_header($vbphrase['dl2_extensions']);
	echo '<p>&nbsp;</p><p>&nbsp;</p>';
	print_form_header('downloads2', 'dodelext', 0, 1, 'cpform', '75%');
	construct_hidden_code('extension', $vbulletin->GPC['extension']);
	print_table_header(construct_phrase($vbphrase['dl2_extension_removal'], $vbulletin->GPC['extension']));
	print_description_row("
		<blockquote>".
		construct_phrase($vbphrase['dl2_extension_removal_confirmation'], $vbulletin->GPC['extension'])."
		</blockquote>\n\t");
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
	print_cp_footer();
}

// ###################### Do Delete Extension ####################
if ($_GET['do'] == 'dodelext')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'extension' => TYPE_STR
	));

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_extensions WHERE extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "'");

	define('CP_REDIRECT', 'downloads2.php?do=extensions');
	print_stop_message('dl2_deleted_extension_successfully');
}

// ###################### Start Import #######################
if ($_REQUEST['do'] == 'import')
{
	print_cp_header('Importar archivos');
	print_form_header('downloads2', 'preimport');
	print_table_header('Importar archivos');
	print_input_row('Directorio<dfn>Ruta absoluta del directorio de descargas.<br />La ruta absoluta de este script es ' . substr($_SERVER['SCRIPT_FILENAME'], 0, 1+strrpos($_SERVER['SCRIPT_FILENAME'], "/")) . '<br />Incluye la barra lateral (slash) al final.</dfn>', 'dir', '');
	print_submit_row('Importar archivos', 0);
	print_cp_footer();
}

// ###################### Prepare Import #######################
if ($_POST['do'] == 'preimport')
{
	print_cp_header('Importar archivos');
	print_form_header('downloads2', 'doimport');
	print_table_header('Importar archivos', 7);

	construct_hidden_code('dir', $_POST['dir']);

	$categories[0] = '-----------';
	foreach ($categories AS $cat_key => $cat_value)
	{
		$category_select .= '<option value="' . $cat_key . '">' . $cat_value . '</option>';
	}

	$class = fetch_row_bgclass();
	echo '<tr><td class="'.$class.'" rowspan="2"><strong>Todos los archivos</strong><br /><em>(no rellenar)</em></td><td class="'.$class.'"><strong>Nombre</strong></td><td class="'.$class.'"><strong>Autor</strong></td><td class="'.$class.'"><strong>Descripción</strong></td><td class="'.$class.'"><strong>Categoría</strong></td><td class="'.$class.'"><strong>Adherido</strong></td><td class="'.$class.'"><strong>Importar</strong></td></tr>';
	echo '<tr><td class="'.$class.'"><input type="text" size="20" name="dname[0]" /></td><td class="'.$class.'"><input type="text" size="20" name="author[0]" /></td><td class="'.$class.'"><input type="text" size="20" name="desc[0]" /></td><td class="'.$class.'"><select name="category[0]">'.$category_select.'</select></td><td class="'.$class.'"><select name="pinned[0]"><option value="-1">-----</option><option value="0">No</option><option value="1">Yes</option></select></td><td class="'.$class.'"><input type="checkbox" name="allbox" title="Check / Uncheck All" onclick="js_check_all(this.form)" /></td></tr>';

	if ($handle = opendir($_POST['dir']))
	{
		$files = array();
		$extension = array();

		$query = $db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "dl2_extensions
			WHERE `mode` <> 2
				AND `enabled` = 1
		");
		while ($extitem = $db->fetch_array($query))
		{
			$extension[$extitem['extension']] = $extitem;
		}

		while (($file = readdir($handle)) !== false)
		{
			if (!(is_file($_POST['dir'] . $file)))
			{
				continue;
			}

			$ext = strtolower(file_extension($file));
			if ($extension[$ext]['extension'] !== $ext)
			{
				continue;
			}

			if (($extension[$ext]['size'] > 0) AND ($extension[$ext]['size'] < @filesize($_POST['dir'] . $file)))
			{
				continue;
			}

			if (($extension[$ext]['width'] > 0) OR ($extension[$ext]['height'] > 0))
			{
				list($width, $height, $type, $attr) = @getimagesize($_POST['dir'] . $file);

				if (($extension[$ext]['width'] > 0) AND ($extension[$ext]['width'] < $width))
				{
					continue;
				}

				if (($extension[$ext]['height'] > 0) AND ($extension[$ext]['height'] < $height))
				{
					continue;
				}
			}

			// Survived all checks
			array_push($files, $file);
		}
		closedir($handle);
		sort($files);

		foreach ($files AS $file)
		{
			$file = str_replace(array("[","]"),array("(openbracket)","(closebracket)"),$file);
			$class = fetch_row_bgclass();
			echo '<tr><td class="'.$class.'">'.$file.'</td><td class="'.$class.'"><input type="text" size="20" name="dname['.$file.']" /></td><td class="'.$class.'"><input type="text" size="20" name="author['.$file.']" /></td><td class="'.$class.'"><input type="text" size="20" name="desc['.$file.']" /></td><td class="'.$class.'"><select name="category['.$file.']">'.$category_select.'</select></td><td class="'.$class.'"><select name="pinned['.$file.']"><option value="-1">-----</option><option value="0">No</option><option value="1">Yes</option></select></td><td class="'.$class.'"><input type="checkbox" name="import['.$file.']" value="1" /></td></tr>';
		}
	}

	print_submit_row('Importar archivos', 0, 7);
	print_cp_footer();
}

// ###################### Do Import #######################
if ($_POST['do'] == 'doimport')
{
	$success = array();
	$category_errors = array();
	$dname_errors = array();
	$file_errors = array();

	if ($_POST['author'][0] != '')
	{
		$authors = explode(";", $_POST['author'][0]);
		foreach ($authors AS $key => $value)
		{
			$author = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE `username`= '" . $db->escape_string(trim($value)) . "'");
			if ($author['userid'] > 0)
			{
				$authors[$key] = '<a href="member.php?u=' . $author['userid'] . '">' . $author['username'] . '</a>';
			}
			else
			{
				$authors[$key] = trim($value);
			}
			$_POST['_author'][0] = implode(", ", $authors);
		}
	}
	$_POST['desc'][0] = convert_url_to_bbcode($_POST['desc'][0]);

	foreach ($_POST['import'] AS $file => $null)
	{
		$_file = str_replace(array("(openbracket)","(closebracket)"),array("[","]"),stripslashes($file));
		
		if ($_POST['category'][$file] == 0)
		{
			if ($_POST['category'][0] == 0)
			{
				array_push($category_errors, $file);
				continue;
			}
			else
			{
				$_POST['category'][$file] = $_POST['category'][0];
			}
		}
		if ($_POST['dname'][$file] == '')
		{
			if ($_POST['dname'][0] == '')
			{
				$_POST['dname'][$file] = substr($_file, 0, strrpos(stripslashes($_file), '.'));
			}
			else
			{
				$_POST['dname'][$file] = $_POST['dname'][0];
			}
		}
		if ($_POST['author'][$file] == '')
		{
			if ($_POST['author'][0] != '')
			{
				$_POST['author'][$file] = $_POST['author'][0];
				$_POST['_author'][$file] = $_POST['_author'][0];
			}
		}
		else
		{
			$authors = explode(";",$_POST['author'][$file]);
			foreach ($authors AS $key => $value)
			{
				$author = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE `username`=".$db->sql_prepare(trim($value)));
				if ($author['userid'] > 0)
				{
					$authors[$key] = '<a href="member.php?u='.$author['userid'].'">'.trim($value).'</a>';
				}
				else
				{
					$authors[$key] = trim($value);
				}
				$_POST['_author'][$file] = implode(", ",$authors);
			}
		}
		if ($_POST['desc'][$file] == '')
		{
			$_POST['desc'][$file] = $_POST['desc'][0];
		}
		else
		{
			$_POST['desc'][$file] = convert_url_to_bbcode($_POST['desc'][$file]);
		}
		if ($_POST['pinned'][$file] == -1)
		{
			if ($_POST['pinned'][0] != -1)
			{
				$_POST['pinned'][$file] = $_POST['pinned'][0];	
			}
		}

		$_POST['size'][$file] = filesize($_POST['dir'].stripslashes($_file));
		$_POST['newfilename'][$file] = (TIMENOW%100000).'-'.stripslashes($_file);

		if (is_readable($_POST['dir'].stripslashes($_file)))
		{
			@copy($_POST['dir'].stripslashes($_file), $dl->url.$_POST['newfilename'][$file]);
			
			if (file_exists($dl->url.$_POST['newfilename'][$file]))
			{
				$db->query_write("
					INSERT INTO " . TABLE_PREFIX . "dl2_files
						(`title`, `description`, `author`, `_author`, `uploader`, `uploaderid`, `url`, `dateadded`, `category`, `size`, `pin`)
					VALUES
						(".
							$db->sql_prepare($_POST['dname'][$file]).", ".
							$db->sql_prepare($_POST['desc'][$file]).", ".
							$db->sql_prepare($_POST['author'][$file]).", ".
							$db->sql_prepare($_POST['_author'][$file]).", ".
							$db->sql_prepare($vbulletin->userinfo['username']).", ".
							$db->sql_prepare($vbulletin->userinfo['userid']).", ".
							$db->sql_prepare($_POST['newfilename'][$file]).", ".
							TIMENOW.", ".
							$db->sql_prepare($_POST['category'][$file]).", ".
							$db->sql_prepare($_POST['size'][$file]).", ".
							$db->sql_prepare($_POST['pinned'][$file]).
						")
				");			
				array_push($success, '<a href="../downloads.php?do=file&amp;id='.$db->insert_id().'">'.stripslashes($file).'</a>');
			}
			else
			{
				array_push($file_errors, $file);
			}
		}
		else
		{
			array_push($file_errors, $file);
		}
	}

	// Intermezzo: initialize template system for use by update_counters_all()
	$styleid = intval($styleid);
	$style = NULL;

	if (!is_array($style))
	{
		$style = $db->query_first_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "style
			WHERE (styleid = $styleid" . iif(!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) AND !$userselect, ' AND userselect = 1') . ")
				OR styleid = " . $vbulletin->options['styleid'] . "
			ORDER BY styleid " . iif($styleid > $vbulletin->options['styleid'], 'DESC', 'ASC') . "
			LIMIT 1
		");
	}
	define('STYLEID', $style['styleid']);

	cache_templates($globaltemplates, $style['templatelist']);
	// End of the intermezzo

	$dl->update_counters_all();

	print_cp_header('Importar archivos');
	print_form_header('downloads2', 'import');
	print_table_header('Importar archivos', 1);

	$class = fetch_row_bgclass();
	echo '<tr><td class="'.$class.'">Los siguientes archivos fueron importados con éxito: '.implode(", ",$success).'</td></tr>';

	if (sizeof($category_errors) > 0)
	{
		$class = fetch_row_bgclass();
		echo '<tr><td class="'.$class.'">No se pudieron importar los siguientes archivos porque no se indicó ninguna categoría: '.implode(", ",$category_errors).'</td></tr>';
	}
	if (sizeof($dname_errors) > 0)
	{
		$class = fetch_row_bgclass();
		echo '<tr><td class="'.$class.'">No se pudieron importar los siguientes archivos porque no se indicó ningún nombre de archivo: '.implode(", ",$dname_errors).'</td></tr>';
	}
	if (sizeof($file_errors) > 0)
	{
		$class = fetch_row_bgclass();
		echo '<tr><td class="'.$class.'">No se pudieron importar los siguientes archivos porque el archivo a importar no podía ser leído o el archivo de exportación no podía ser escrito: '.implode(", ",$file_errors).'</td></tr>';
	}

	print_submit_row('Importar más archivos', 0, 1);
	print_cp_footer();
}

// ###################### View Reported Files #######################
if ($_GET['do'] == 'reports')
{
	print_cp_header($vbphrase['dl2_reported_files']);
	print_form_header('downloads2', 'reports');
	print_table_header($vbphrase['dl2_view_reported_files'], 7);

	// Make it possible to override the default report method
	$overridereport = false;

	($hook = vBulletinHook::fetch_hook('dl2_admin_alt_report')) ? eval($hook) : false;

	if ($overridereport == false)
	{
		$result = $db->query_read("
			SELECT reports.*, files.title, files.id AS fileid
			FROM " . TABLE_PREFIX . "dl2_reports AS reports
			LEFT JOIN " . TABLE_PREFIX . "dl2_files AS files ON (files.id=reports.fileid)
			ORDER BY id DESC
		");

		if ($db->num_rows($result) > 0)
		{
			while ($report = $db->fetch_array($result))
			{
				print_description_row('# ' . $report['id'], 0, 2, 'thead');
				print_label_row($vbphrase['dl2_reported_file'], '<a href="../downloads.php?' . $vbulletin->session->vars['sessionurl'] . 'do=file&amp;id=' . $report['fileid'] . '">' . htmlspecialchars_uni($report['title']) . '</a>', '', 'top', '', 20);
				print_label_row($vbphrase['dl2_reported_by'], '<a href="user.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit&amp;u='.$report['userid'].'">'.$report['username'].'</a>');
				print_label_row($vbphrase['ip_address'], '<a href="usertools.php?' . $vbulletin->session->vars['sessionurl'] . 'do=gethost&amp;ip=' . $report['ipaddress'] . '">' . $report['ipaddress'] . '</a>');
				print_label_row($vbphrase['date'], vbdate($vbulletin->options['logdateformat'], $report['date']));
				print_label_row($vbphrase['reason'], nl2br($report['reason']));
				print_label_row($vbphrase['controls'], '<input type="button" class="button" value="' . $vbphrase['dl2_delete_report'] . '" tabindex="1" onclick="window.location=\'downloads2.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delreport&amp;id=' . $report['id'] . '\'" />');
			}
		}
		else
		{
			print_description_row($vbphrase['dl2_no_reports_awaiting_moderation']);
		}
	}

	print_table_footer();
	print_cp_footer();
}

// ###################### Delete Report ####################
if ($_GET['do'] == 'delreport')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id' => TYPE_UINT
	));

	print_cp_header($vbphrase['dl2_reported_files']);
	echo '<p>&nbsp;</p><p>&nbsp;</p>';
	print_form_header('downloads2', 'dodelreport', 0, 1, 'cpform', '75%');
	construct_hidden_code('id', $vbulletin->GPC['id']);
	print_table_header(construct_phrase($vbphrase['dl2_report_removal'], $vbulletin->GPC['id']));
	print_description_row("
		<blockquote>".
		construct_phrase($vbphrase['dl2_report_removal_confirmation'], $vbulletin->GPC['id'])."
		</blockquote>\n\t");
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
	print_cp_footer();
}

// ###################### Do Delete Report ####################
if ($_GET['do'] == 'dodelreport')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id' => TYPE_UINT
	));

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "dl2_reports WHERE id = " . $vbulletin->GPC['id']);

	define('CP_REDIRECT', 'downloads2.php?do=reports');
	print_stop_message('dl2_deleted_report_successfully');
}

// ###################### Downloads log #######################
if ($_GET['do'] == 'downloads')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
	));

	$vbulletin->GPC['perpage'] = 25;

	$counter = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "dl2_downloads");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_downloads ORDER BY `id` DESC LIMIT $startat, " . $vbulletin->GPC['perpage']);
	if ($db->num_rows($result))
	{
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=downloads&amp;page=1'\" />";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=downloads&amp;page=$prv'\" />";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=downloads&amp;page=$nxt'\" />";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=downloads&amp;page=$totalpages'\" />";
		}
	}

	print_cp_header($vbphrase['dl2_downloads_log']);
	print_table_start();
	print_table_header(construct_phrase($vbphrase['dl2_downloads_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 6);

	echo '<tr>
		<td class="thead">' . $vbphrase['id'] . '</td>
		<td class="thead">' . $vbphrase['user'] . '</td>
		<td class="thead">' . $vbphrase['dl2_file'] . '</td>
		<td class="thead">' . $vbphrase['date'] . '</td>
		<td class="thead">' . $vbphrase['dl2_file_size'] . '</td>
		<td class="thead">' . $vbphrase['ip_address'] . '</td>
	</tr>';

	while ($download = $db->fetch_array($result))
	{
		$class = fetch_row_bgclass();
		echo '<tr>
			<td class="' . $class . '">' . $download['id'] . '</td>
			<td class="' . $class . '"><a href="user.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit&amp;u=' . $download['userid'] . '">' . $download['user'] . '</a></td>
			<td class="' . $class . '"><a href="../downloads.php?do=file&amp;id=' . $download['fileid'] . '">' . $download['file'] . '</a></td>
			<td class="' . $class . '"><span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $download['time']) . '</span></td>
			<td class="' . $class . '">' . vb_number_format($download['filesize'], 0, true) . '</td>
			<td class="' . $class . '"><span class="smallfont"><a href="usertools.php?' . $vbulletin->session->vars['sessionurl'] . 'do=gethost&amp;ip=' . $download['ipaddress'] . '">' . $download['ipaddress'] . '</a></span></td>
		</tr>';
	}

	print_table_footer(6, "$firstpage $prevpage &nbsp; $nextpage $lastpage", '', 0);

	print_form_header('downloads2', 'prunelog');
	print_table_header($vbphrase['dl2_prune_downloads_log']);
	print_input_row($vbphrase['remove_entries_older_than_days'], 'daysprune', 30);
	print_submit_row($vbphrase['prune'], 0);

	print_cp_footer();
}

// ###################### Confirm prune log #######################
if ($_POST['do'] == 'prunelog')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'daysprune' => TYPE_INT
	));

	$datecut = TIMENOW - (86400 * $vbulletin->GPC['daysprune']);

	$logs = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "dl2_downloads
		WHERE time < $datecut
	");

	if ($logs['total'])
	{
		print_cp_header($vbphrase['dl2_downloads_log']);
		echo '<p>&nbsp;</p><p>&nbsp;</p>';
		print_form_header('downloads2', 'doprunelog', 0, 1, 'cpform', '75%');
		construct_hidden_code('datecut', $datecut);
		print_table_header($vbphrase['dl2_prune_downloads_log']);
		print_description_row("
			<blockquote>".
			construct_phrase($vbphrase['dl2_prune_confirmation'], vb_number_format($logs['total']))."
			</blockquote>\n\t");
		print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
		print_cp_footer();
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

// ###################### Do prune log #######################
if ($_POST['do'] == 'doprunelog')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'datecut' => TYPE_INT
	));

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "dl2_downloads
		WHERE time < " . $vbulletin->GPC['datecut'] . "
	");

	define('CP_REDIRECT', 'downloads2.php?do=downloads');
	print_stop_message('dl2_pruned_downloads_log_successfully');
}

// ###################### Download Stats #######################
if ($_GET['do'] == 'stats')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
	));

	$vbulletin->GPC['perpage'] = 25;

	$counter = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "dl2_stats");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "dl2_stats ORDER BY `day` DESC LIMIT $startat, " . $vbulletin->GPC['perpage']);
	if ($db->num_rows($result))
	{
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=stats&amp;page=1'\" />";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=stats&amp;page=$prv'\" />";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=stats&amp;page=$nxt'\" />";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='downloads2.php?do=stats&amp;page=$totalpages'\" />";
		}
	}

	print_cp_header($vbphrase['dl2_download_stats']);
	print_table_start();
	print_table_header(construct_phrase($vbphrase['dl2_download_stats_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 6);

	echo '<tr>
		<td class="thead">' . $vbphrase['date'] . '</td>
		<td class="thead">' . $vbphrase['dl2_downloads'] . '</td>
		<td class="thead">' . $vbphrase['dl2_bandwidth'] . '<a href="#bandwidth">*</a></td>
	</tr>';

	while ($stat = $db->fetch_array($result))
	{
		$date = vbdate($vbulletin->options['dateformat'], $stat['day']*86400, true);
		$bandwidth = (int) ($stat['bandwidth']/1000);
		if ($bandwidth == 0)
		{
			$bandwidth = $vbphrase['dl2_unknown'];
		}
		else
		{
			$bandwidth .= ' KB';
		}

		$class = fetch_row_bgclass();
		echo '<tr>
			<td class="' . $class . '">' . $date . '</td>
			<td class="' . $class . '">' . $stat['downloads'] . '</td>
			<td class="' . $class . '">' . $bandwidth . '</td>
		</tr>';
	}

	print_description_row('* <a name="bandwidth"></a>' . $vbphrase['dl2_bandwidth_note'], 0, 3);
	print_table_footer(6, "$firstpage $prevpage &nbsp; $nextpage $lastpage", '', 0);

	print_cp_footer();
}

?>