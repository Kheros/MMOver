<?php


// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('logging', 'marketadmin', 'pointmarket');
$specialtemplates = array();
$globaltemplates = array();
$actiontemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
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

// ######################## CHECK ADMIN PERMISSIONS #######################
    $userid = $vbulletin->userinfo['userid'];
    $grabadmin = $db->fetch_array($db->query_read("SELECT * FROM " . TABLE_PREFIX . "administrator WHERE `userid` = '$userid'"));
if ($grabadmin[market_custom_admin_perms] == 0)
{
    print_cp_no_permission();
}


// ###################### Category Functions #######################
if ($_GET['do'] == 'category')
{
    print_cp_header($vbphrase['market_item_header']);

    print_form_header('market', 'doorder');
    print_table_header($vbphrase['market_item_header'], 4);
    print_description_row($vbphrase['market_main_description'], 0, 4);
    echo '<tr>
        <td class="thead">' . $vbphrase['market_name'] . '</td>
        <td class="thead" align="right" style="white-space:nowrap">' . $vbphrase['display_order'] . '</td>
        <td class="thead" align="right" style="white-space:nowrap">' . $vbphrase['market_purchases'] . '</td>
        <td class="thead">' . $vbphrase['controls'] . '</td>
    </tr>';
    
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_items where `iscategory` = 1 ORDER BY `order`");
    while ($category = $db->fetch_array($result))
    {
        $class = fetch_row_bgclass();
        if ($category[active] != 1) {
        $active_0s = "<s>";
        $active_0e = "</s>";
        } else {
        $active_0s = "";
        $active_0e = "";
        }

        $site_url = $vbulletin->options['bburl'];
        
        
        echo '<tr>
            <td class="' . $class . '" width="100%">&nbsp;<b>' . $active_0s . $spacer . htmlspecialchars_uni($category['name']) . $active_0e . '</b></td>
            <td class="' . $class . '" align="center"><input type="text" class="bginput" name="displayorder[' . $category['marketid'] . ']" value="' . $category['order'] . '" tabindex="1" size="2" title="' . $vbphrase['display_order'] . '" /></td>
            <td class="' . $class . '" align="center"> </td>
            <td class="' . $class . '" align="center"><a href="market.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edititem&amp;id=' . $category['marketid'] . '"><img src="' . $site_url . '/images/pointmarket/edit.png" border="0" alt="' . $vbphrase['edit'] . '"></a></td>
            </tr>';
        $result2 = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_items where `iscategory`=0 AND `parentid`='$category[marketid]' ORDER BY `order`");
        while ($item = $db->fetch_array($result2))
            {
            $class = fetch_row_bgclass();
            if ($item[active] != 1) {
                $active_0s = "<s>";
                $active_0e = "</s>";
            } else {
                $active_0s = "";
                $active_0e = "";
            }
            $purchases = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions where marketid=$item[marketid]"));
            echo '<tr>
                <td class="' . $class . '" width="100%">&nbsp;---&nbsp;' . $active_0s . $spacer . htmlspecialchars_uni($item['name']) . $active_0e . '</td>
                <td class="' . $class . '" align="center"><input type="text" class="bginput" name="displayorder[' . $item['marketid'] . ']" value="' . $item['order'] . '" tabindex="1" size="2" title="' . $vbphrase['display_order'] . '" /></td>
                <td class="' . $class . '"><a href="market.php?' . $vbulletin->session->vars['sessionurl'] . 'do=itemhistory&amp;id=' . $item['marketid'] . '">' . $purchases . '</a></td>
                <td class="' . $class . '" align="center"><a href="market.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edititem&amp;id=' . $item['marketid'] . '"><img src="' . $site_url . '/images/pointmarket/edit.png" border="0" alt="' . $vbphrase['edit'] . '"></a></td>
                </tr>';
            }
        $db->free_result($result2);
    }
    $db->free_result($result); 

    print_submit_row($vbphrase['save_display_order'], $vbphrase['reset'], 4);



    print_cp_footer();
}

// ###################### Lottery Functions #######################
if ($_GET['do'] == 'lottery')
{
    print_cp_header($vbphrase['market_lottery_header']);

    print_form_header('market', 'addlotto');
    print_table_header($vbphrase['market_lottery_header'], 7);
    print_description_row($vbphrase['market_lottery_description'], 0, 7);
    echo '<tr>
        <td class="thead">' . $vbphrase['market_name'] . '</td>
        <td class="thead" align="center" style="white-space:nowrap">' . $vbphrase['market_start_date'] . '</td>
        <td class="thead" align="center" style="white-space:nowrap">' . $vbphrase['market_end_date'] . '</td>
        <td class="thead" align="center" style="white-space:nowrap">' . $vbphrase['market_initialpay'] . '</td>
        <td class="thead" align="center" style="white-space:nowrap">' . $vbphrase['market_currentpay'] . '</td>
        <td class="thead" align="center" style="white-space:nowrap">' . $vbphrase['market_price'] . '</td>
        <td class="thead">' . $vbphrase['controls'] . '</td>
    </tr>';
    
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_lotto where `winner` = 0 ORDER BY `dateend` ASC");
    while ($category = $db->fetch_array($result))
    {
        $class = fetch_row_bgclass();
        $site_url = $vbulletin->options['bburl'];

        echo '<tr>
            <td class="' . $class . '">&nbsp;<b>' . $spacer . htmlspecialchars_uni($category['name']) . '</b></td>
            <td class="' . $class . '" align="center">' . date('F j', $category[datestart]) . ' </td>
            <td class="' . $class . '" align="center">' . date('F j, Y, g:i a', $category[dateend]) . ' </td>
            <td class="' . $class . '" align="right">' . $category[initialpay] . ' </td>
            <td class="' . $class . '" align="right">' . $category[currentpay] . ' </td>
            <td class="' . $class . '" align="right">' . $category[cost] . ' </td> 
            <td class="' . $class . '" align="center"><a href="market.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editlotto&amp;id=' . $category['lottoid'] . '"><img src="' . $site_url . '/images/pointmarket/edit.png" border="0" alt="' . $vbphrase['edit'] . '"></a></td>
            </tr>';
            
    }

    print_table_footer(7, "<input type='submit' value='$vbphrase[market_create]' name='create' />",  0);


}

// ###################### Do Edit Lotto #########################
if ($_GET['do'] == 'editlotto')
{
    $cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    $cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "market_lotto WHERE `lottoid`='$cleancatid'");

    // Day Calculation
    $end_days = floor(($cat[dateend] - $cat[datestart]) / 86400);
    $end_hours = floor(($cat[dateend] - $cat[datestart] - (86400*$end_days))/3600);  
    
    print_cp_header($vbphrase['market_lottery_header']);
    print_form_header('market', 'dolottoitem');
    print_table_header($vbphrase['market_lottery_edit']);  
    print_input_row($vbphrase['market_name'], 'name', $cat['name']);
    print_input_row($vbphrase['market_end_days'], 'end_days', $end_days);
    print_input_row($vbphrase['market_end_hours'], 'end_hours', $end_hours);  
    print_input_row($vbphrase['market_price'], 'cost', $cat['cost']); 
    print_input_row($vbphrase['market_initialpay'], 'initialpay', $cat['initialpay']);
    print_input_row($vbphrase['market_currentpay'], 'currentpay', $cat['currentpay']); 

    construct_hidden_code('datestart', $cat['datestart']);         
    construct_hidden_code('lottoid', $cat['lottoid']);
    print_submit_row($vbphrase['save']);

    print_cp_footer();
}
// ###################### Do Edit Lotto #########################
if ($_GET['do'] == 'addlotto')
{
    $cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    $cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "market_lotto WHERE `lottoid`='$cleancatid'");

    // Day Calculation
    $end_days = floor(($cat[dateend] - $cat[datestart]) / 86400);
    $end_hours = floor(($cat[dateend] - $cat[datestart] - (86400*$end_days))/3600);  
    
    print_cp_header($vbphrase['market_item_header']);
    print_form_header('market', 'addlottoitem');
    print_table_header($vbphrase['market_lottery_add']);
    print_input_row($vbphrase['market_name'], 'name');
    print_input_row($vbphrase['market_end_days'], 'end_days');
    print_input_row($vbphrase['market_end_hours'], 'end_hours');  
    print_input_row($vbphrase['market_price'], 'cost'); 
    print_input_row($vbphrase['market_initialpay'], 'initialpay');
        
    construct_hidden_code('lottoid', $cat['lottoid']);
    print_submit_row($vbphrase['save']);

    print_cp_footer();
}

// ###################### Do Edit Item #######################
if ($_POST['do'] == 'dolottoitem')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'lottoid'          => TYPE_UINT,
        'name'             => TYPE_STR,
        'end_days'         => TYPE_NUM,
        'end_hours'        => TYPE_NUM,
        'cost'             => TYPE_INT,
        'initialpay'       => TYPE_INT, 
        'currentpay'       => TYPE_INT, 
        'datestart'        => TYPE_INT, 
    ));

    if ($vbulletin->GPC['lottoid'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['name'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['end_days']  < 1 AND $vbulletin->GPC['end_hours'])
    {
        print_stop_message('please_complete_required_fields');
    }
    else
    {
    $dateend = $vbulletin->GPC['datestart']+($vbulletin->GPC['end_days']*86400)+($vbulletin->GPC['end_hours']*3600);
        $db->query_write("
            UPDATE " . TABLE_PREFIX . "market_lotto SET
                `name` = '" . $db->escape_string($vbulletin->GPC['name']) . "',
                `dateend` = '" . $db->escape_string($dateend) . "',
                `cost` = '" . $db->escape_string($vbulletin->GPC['cost']) . "',
                `initialpay` = '" . $db->escape_string($vbulletin->GPC['initialpay']) . "',  
                `currentpay` = '" . $db->escape_string($vbulletin->GPC['currentpay']) . "'
            WHERE lottoid = " . $vbulletin->GPC['lottoid']
        );
        if ($db->affected_rows() > 0)
        {
            define('CP_REDIRECT', 'market.php?do=lottery');
            print_stop_message('market_item_saved_successfully');
        }
        else
        {
            define('CP_REDIRECT', 'market.php?do=lottery');
            print_stop_message('market_item_saved_successfully');
        }
    }
}


// ###################### Do Add Lotto #######################
if ($_POST['do'] == 'addlottoitem')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'lottoid'          => TYPE_UINT,
        'name'             => TYPE_STR,
        'end_days'         => TYPE_NUM,
        'end_hours'        => TYPE_NUM,
        'cost'             => TYPE_INT,
        'initialpay'       => TYPE_INT 
    ));


    if ($vbulletin->GPC['name'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['end_days']  < 1 AND $vbulletin->GPC['end_hours'] < 1)
    {
        print_stop_message('please_complete_required_fields');
    }
    else
    {
        $datestart = time();
        $dateend = $datestart+($vbulletin->GPC['end_days']*86400)+($vbulletin->GPC['end_hours']*3600);
    
        $db->query_write("
            INSERT INTO " . TABLE_PREFIX . "market_lotto SET
                `name` = '" . $db->escape_string($vbulletin->GPC['name']) . "',
                `datestart` = '" . $db->escape_string($datestart) . "',
                `dateend` = '" . $db->escape_string($dateend) . "',
                `cost` = '" . $db->escape_string($vbulletin->GPC['cost']) . "',
                `initialpay` = '" . $db->escape_string($vbulletin->GPC['initialpay']) . "',
                `currentpay` = '" . $db->escape_string($vbulletin->GPC['initialpay']) . "'"
        );
        if ($db->affected_rows() > 0)
        {
            define('CP_REDIRECT', 'market.php?do=lottery');
            print_stop_message('market_item_saved_successfully');
        }
        else
        {
            define('CP_REDIRECT', 'market.php?do=lottery');
            print_stop_message('market_item_saved_successfully');
        }
    }
}

// ###################### Category Functions #######################
if ($_GET['do'] == 'itemhistory' OR $_POST['do'] == 'itemhistory')
{

    $grabitem = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    // Pageinating for points user has donated
    $vbulletin->input->clean_array_gpc('p', array(
    'perpage'    => TYPE_UINT,
    'previous'   => TYPE_STR,
    'next'       => TYPE_STR,
    'marketid'   => TYPE_UINT,
    'pagenumber' => TYPE_UINT,
    ));

    if ($vbulletin->GPC['next']) {
    $pagenumber = $vbulletin->GPC['pagenumber']+1;
    $vbulletin->GPC['pagenumber'] = $pagenumber;
    }
    else if ($vbulletin->GPC['previous']) {
    $pagenumber = $vbulletin->GPC['pagenumber']-1;
    $vbulletin->GPC['pagenumber'] = $pagenumber;
    }
    if ($vbulletin->GPC['marketid'] > 0) {
    $grabitem = $vbulletin->GPC['marketid'];
    }
    $cel_users = $db->query_first("
    SELECT COUNT('tranid') AS users_donate
    FROM " . TABLE_PREFIX . "market_transactions AS market_transactions
    WHERE marketid='$grabitem'
");


    // Sanitize for points user has stolen
    sanitize_pageresults($cel_users['users_stolen'], $pagenumber, $perpage, 100, 20);
    if ($vbulletin->GPC['pagenumber'] < 1)
    {
        $vbulletin->GPC['pagenumber'] = 1;
    }
    else if ($vbulletin->GPC['pagenumber'] > ceil(($cel_users['users_donate'] + 1) / $perpage))
    {
        $vbulletin->GPC['pagenumber'] = ceil(($cel_users['users_donate'] + 1) / $perpage);
    }
    $limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
    $limitupper = ($vbulletin->GPC['pagenumber']) * $perpage;


    print_cp_header($vbphrase['market_history_header']);

    print_form_header('market', 'itemhistory');
    print_table_header($vbphrase['market_history_header'], 5);
    print_description_row($vbphrase['market_history_description'], 0, 5);

    construct_hidden_code('pagenumber', $vbulletin->GPC['pagenumber']);
    construct_hidden_code('marketid', $grabitem);

    echo '<tr>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_date'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_user'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_affected'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_status'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_amount'] . '</td>
    </tr>';


    // Main Query to find who user has stolen from
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions WHERE marketid='$grabitem' ORDER BY date DESC LIMIT $limitlower, $perpage");
    while ($shu = $vbulletin->db->fetch_array($result))
    {
    $boughtuser = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[userid]'"));
    $otheruser = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[affecteduser]'"));
// Configure Amount
    $amount = $item[amount]-($item[amount]*$discount);
    ++$loop_count;

    if ($shu[date]) {
    $date = date("F j, Y, g:i a", $shu[date]);
    }

    if ($shu[refund_date] > 0) {
    $refund_date = date("F j, Y", $shu[refund_date]);
    $status = "" . $vbphrase[market_item_refund_on] .  $refund_date ."";
    } else { $status = $vbphrase['market_item_purchased']; }

    if ($shu[stolenamount] > 0) {
    $shu[amount] = $shu[stolenamount];
    }
    if ($shu[donatedamount] > 0) {
    $shu[amount] = $shu[donatedamount];
    }

    echo '<tr>
    <td class="' . $class . '">' . $spacer . $date . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($boughtuser['username']) . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($otheruser['username']) . '</td>
    <td class="' . $class . '">' . $spacer . $status . ' </td>
    <td class="' . $class . '">' . $spacer . number_format($shu[amount], '5', '.', ',') . ' </td>
    </tr>';
    }

  $pagenumber = $vbulletin->GPC['pagenumber'];

//  $link = "<a href='market.php?do=itemhistory&id=$grabitem&pagenumber=$pagenumber&perpage=$perpage'>Test</a>";
//    echo '<tr><td colspan="5" class="' . $class . '">' . $link . '</td></tr>';
    if ($pagenumber <= 1 AND $loop_count < $perpage) {
    print_table_footer(5, "<input type='submit' value='$vbphrase[previous]' name='previous' disabled /> <input type='submit' value='$vbphrase[next]' name='next' disabled />",  0);
    }
    else if ($pagenumber <= 1) {
    print_table_footer(5, "<input type='submit' value='$vbphrase[previous]' name='previous' disabled /> <input type='submit' value='$vbphrase[next]' name='next' />",  0);
    }
    else if ($loop_count < $perpage) {
    print_table_footer(5, "<input type='submit' value='$vbphrase[previous]' name='previous' /> <input type='submit' value='$vbphrase[next]' name='next' disabled />",  0);
    }
    else {
    print_table_footer(5, "<input type='submit' value='$vbphrase[previous]' name='previous' /> <input type='submit' value='$vbphrase[next]' name='next' />",  0);
    }

    print_cp_footer();

}



// ###################### Category Functions #######################
if ($_GET['do'] == 'itemgifthistory' OR $_POST['do'] == 'itemgifthistory')
{

    $grabitem = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    // Pageinating for points user has donated
    $vbulletin->input->clean_array_gpc('p', array(
    'perpage'    => TYPE_UINT,
    'previous'   => TYPE_STR,
    'next'       => TYPE_STR,
    'delete'     => TYPE_STR,
    'marketid'   => TYPE_UINT,
    'deleteid'   => TYPE_UINT,
    'pagenumber' => TYPE_UINT,
    ));

    // Delete a Specific Row
    if ($vbulletin->GPC['deleteid'] > 0 AND $vbulletin->GPC['delete']) {
    $deleteid = $vbulletin->GPC['deleteid'];
    $db->query_write("delete
    FROM " . TABLE_PREFIX . "market_transactions
    WHERE tranid='$deleteid'
");
    }

    if ($vbulletin->GPC['next']) {
    $pagenumber = $vbulletin->GPC['pagenumber']+1;
    $vbulletin->GPC['pagenumber'] = $pagenumber;
    }
    else if ($vbulletin->GPC['previous']) {
    $pagenumber = $vbulletin->GPC['pagenumber']-1;
    $vbulletin->GPC['pagenumber'] = $pagenumber;
    }
    if ($vbulletin->GPC['marketid'] > 0) {
    $grabitem = $vbulletin->GPC['marketid'];
    }
    $cel_users = $db->query_first("
    SELECT COUNT('tranid') AS users_donate
    FROM " . TABLE_PREFIX . "market_transactions AS market_transactions
    WHERE gift_id='$grabitem'
");


    // Sanitize for points user has stolen
    sanitize_pageresults($cel_users['users_stolen'], $pagenumber, $perpage, 100, 20);
    if ($vbulletin->GPC['pagenumber'] < 1)
    {
        $vbulletin->GPC['pagenumber'] = 1;
    }
    else if ($vbulletin->GPC['pagenumber'] > ceil(($cel_users['users_donate'] + 1) / $perpage))
    {
        $vbulletin->GPC['pagenumber'] = ceil(($cel_users['users_donate'] + 1) / $perpage);
    }
    $limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
    $limitupper = ($vbulletin->GPC['pagenumber']) * $perpage;


    print_cp_header($vbphrase['market_history_header']);

    print_form_header('market', 'itemgifthistory');
    print_table_header($vbphrase['market_history_header'], 6);
    print_description_row($vbphrase['market_history_description'], 0, 6);

    construct_hidden_code('pagenumber', $vbulletin->GPC['pagenumber']);
    construct_hidden_code('marketid', $grabitem);

    echo '<tr>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_date'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_user'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_affected'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_reason'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_amount'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_delete'] . '</td>
    </tr>';


    // Main Query to find who user has stolen from
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions WHERE gift_id='$grabitem' ORDER BY date DESC LIMIT $limitlower, $perpage");
    while ($shu = $vbulletin->db->fetch_array($result))
    {
    $boughtuser = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[userid]'"));
    $otheruser = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[affecteduser]'"));
// Configure Amount
    $amount = $item[amount]-($item[amount]*$discount);
    ++$loop_count;

    if ($shu[date]) {
    $date = date("F j, Y, g:i a", $shu[date]);
    }

    if ($shu[refund_date] > 0) {
    $refund_date = date("F j, Y", $shu[refund_date]);
    $status = "" . $vbphrase[market_item_refund_on] .  $refund_date ."";
    } else { $status = $vbphrase['market_item_purchased']; }

    echo '<tr>
    <td class="' . $class . '">' . $spacer . $date . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($boughtuser['username']) . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($otheruser['username']) . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($shu['reason']) . '</td>
    <td class="' . $class . '">' . $spacer . number_format($shu[amount], '5', '.', ',') . ' </td>
    <td class="' . $class . '"><input type="radio" value="'.$shu[tranid].'" name="deleteid" /> </td>
    </tr>';
    }

  $pagenumber = $vbulletin->GPC['pagenumber'];
  print_description_row($vbphrase['market_history_delete_warning'], 0, 6);

    if ($pagenumber <= 1 AND $loop_count < $perpage) {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' disabled /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' disabled />",  0);
    }
    else if ($pagenumber <= 1) {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' disabled /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' />",  0);
    }
    else if ($loop_count < $perpage) {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' disabled />",  0);
    }
    else {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' />",  0);
    }

    print_cp_footer();

}




// ###################### Category Functions #######################
if ($_GET['do'] == 'itemcustomgifthistory' OR $_POST['do'] == 'itemcustomgifthistory')
{

    $grabitem = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    // Pageinating for points user has donated
    $vbulletin->input->clean_array_gpc('p', array(
    'perpage'    => TYPE_UINT,
    'previous'   => TYPE_STR,
    'next'       => TYPE_STR,
    'delete'     => TYPE_STR,
    'marketid'   => TYPE_UINT,
    'deleteid'   => TYPE_UINT,
    'pagenumber' => TYPE_UINT,
    ));

    // Delete a Specific Row
    if ($vbulletin->GPC['deleteid'] > 0 AND $vbulletin->GPC['delete']) {
    $deleteid = $vbulletin->GPC['deleteid'];
    $db->query_write("delete
    FROM " . TABLE_PREFIX . "market_transactions
    WHERE tranid='$deleteid'
");
    }

    if ($vbulletin->GPC['next']) {
    $pagenumber = $vbulletin->GPC['pagenumber']+1;
    $vbulletin->GPC['pagenumber'] = $pagenumber;
    }
    else if ($vbulletin->GPC['previous']) {
    $pagenumber = $vbulletin->GPC['pagenumber']-1;
    $vbulletin->GPC['pagenumber'] = $pagenumber;
    }
    if ($vbulletin->GPC['marketid'] > 0) {
    $grabitem = $vbulletin->GPC['marketid'];
    }
    $cel_users = $db->query_first("
    SELECT COUNT('tranid') AS users_donate
    FROM " . TABLE_PREFIX . "market_transactions AS market_transactions
    WHERE gift_customid='$grabitem'
");


    // Sanitize for points user has stolen
    sanitize_pageresults($cel_users['users_stolen'], $pagenumber, $perpage, 100, 20);
    if ($vbulletin->GPC['pagenumber'] < 1)
    {
        $vbulletin->GPC['pagenumber'] = 1;
    }
    else if ($vbulletin->GPC['pagenumber'] > ceil(($cel_users['users_donate'] + 1) / $perpage))
    {
        $vbulletin->GPC['pagenumber'] = ceil(($cel_users['users_donate'] + 1) / $perpage);
    }
    $limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
    $limitupper = ($vbulletin->GPC['pagenumber']) * $perpage;


    print_cp_header($vbphrase['market_history_header']);

    print_form_header('market', 'itemcustomgifthistory');
    print_table_header($vbphrase['market_history_header'], 6);
    print_description_row($vbphrase['market_history_description'], 0, 6);

    construct_hidden_code('pagenumber', $vbulletin->GPC['pagenumber']);
    construct_hidden_code('marketid', $grabitem);

    echo '<tr>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_date'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_user'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_affected'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_reason'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_amount'] . '</td>
        <td class="thead" align="left" style="white-space:nowrap">' . $vbphrase['market_item_delete'] . '</td>
    </tr>';


    // Main Query to find who user has stolen from
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions WHERE gift_customid='$grabitem' ORDER BY date DESC LIMIT $limitlower, $perpage");
    while ($shu = $vbulletin->db->fetch_array($result))
    {
    $boughtuser = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[userid]'"));
    $otheruser = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[affecteduser]'"));
// Configure Amount
    $amount = $item[amount]-($item[amount]*$discount);
    ++$loop_count;

    if ($shu[date]) {
    $date = date("F j, Y, g:i a", $shu[date]);
    }

    if ($shu[refund_date] > 0) {
    $refund_date = date("F j, Y", $shu[refund_date]);
    $status = "" . $vbphrase[market_item_refund_on] .  $refund_date ."";
    } else { $status = $vbphrase['market_item_purchased']; }

    echo '<tr>
    <td class="' . $class . '">' . $spacer . $date . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($boughtuser['username']) . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($otheruser['username']) . '</td>
    <td class="' . $class . '">' . $spacer . htmlspecialchars_uni($shu['reason']) . '</td>
    <td class="' . $class . '">' . $spacer . number_format($shu[amount], '5', '.', ',') . ' </td>
    <td class="' . $class . '"><input type="radio" value="'.$shu[tranid].'" name="deleteid" /> </td>
    </tr>';
    }

  $pagenumber = $vbulletin->GPC['pagenumber'];
  print_description_row($vbphrase['market_history_delete_warning'], 0, 6);

    if ($pagenumber <= 1 AND $loop_count < $perpage) {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' disabled /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' disabled />",  0);
    }
    else if ($pagenumber <= 1) {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' disabled /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' />",  0);
    }
    else if ($loop_count < $perpage) {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' disabled />",  0);
    }
    else {
    print_table_footer(6, "<input type='submit' value='$vbphrase[previous]' name='previous' /> <input type='submit' value='$vbphrase[market_item_delete]' name='delete' /> <input type='submit' value='$vbphrase[next]' name='next' />",  0);
    }

    print_cp_footer();

}



// ###################### Do Display Order #######################
if ($_POST['do'] == 'doorder')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'displayorder'     => TYPE_NOCLEAN
    ));

    $catids = array();

    foreach ($vbulletin->GPC['displayorder'] AS $categoryid => $displayorder)
    {
        $vbulletin->GPC['displayorder']["$categoryid"] = intval($displayorder);
        $catids[] = "'" . $db->escape_string($categoryid) . "'";
    }

    $categories = $db->query_read("
        SELECT `marketid`, `order`
        FROM " . TABLE_PREFIX . "market_items");
    while ($cat = $db->fetch_array($categories))
    {
        if ($cat['order'] != $vbulletin->GPC['displayorder']["$cat[marketid]"])
        {
            $db->query_write("
                UPDATE " . TABLE_PREFIX . "market_items
                SET `order` = " . $vbulletin->GPC['displayorder']["$cat[marketid]"] . "
                WHERE marketid = '" . $db->escape_string($cat['marketid']) . "'
            ");
        }
    }

    define('CP_REDIRECT', 'market.php?do=category');
    print_stop_message('saved_display_order_successfully');
}

// ###################### Do Edit Item #########################
if ($_GET['do'] == 'edititem')
{
    $cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    $cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "market_items WHERE `marketid` = $cleancatid");


    print_cp_header($vbphrase['market_item_header']);
    print_form_header('market', 'doedititem');
    print_table_header($vbphrase['market_item_rowhead'], 2);
    print_input_row($vbphrase['market_item_name'], 'name', $cat['name']);
    print_textarea_row($vbphrase['market_item_description_edit'], 'description', $cat['description'], 4, 40, true, false);
    print_textarea_row($vbphrase['market_item_instructions_edit'], 'instructions', $cat['instructions'], 4, 40, true, false);

    print_input_row($vbphrase['market_item_amount_info'], 'amount', $cat['amount']);
    print_input_row($vbphrase['market_item_order_info'], 'order', $cat['order']);
    print_input_row($vbphrase['market_item_image'], 'image', $cat['image']);

    if ($cat[iscategory] < 1) {
    $getcategory = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_items WHERE iscategory=1 ORDER BY name ASC");
    $cat_list = array();
    while($find = $vbulletin->db->fetch_array($getcategory))
    {
               $cat_list[$find['marketid']] = $find['name'];
    }
    print_select_row($vbphrase['market_parent_id'], 'categoryid', $cat_list, $cat['parentid']);
    }
    
    print_select_row($vbphrase['market_item_active'], 'active', array('1' => $vbphrase['enabled'], '0' => $vbphrase['disabled']), $cat['active']);
    construct_hidden_code('marketid', $cat['marketid']);
    print_submit_row($vbphrase['save']);

    print_cp_footer();
}

// ###################### Do Edit Item #######################
if ($_POST['do'] == 'doedititem')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'marketid'         => TYPE_UINT,
        'name'             => TYPE_STR,
        'description'      => TYPE_STR,
        'instructions'     => TYPE_STR,
        'amount'           => TYPE_NUM,
        'image'            => TYPE_STR,
        'categoryid'       => TYPE_INT,
        'order'            => TYPE_INT,
        'active'           => TYPE_INT,
    ));

    if ($vbulletin->GPC['marketid'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['name'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else
    {

        $db->query_write("
            UPDATE " . TABLE_PREFIX . "market_items SET
                `name` = '" . $db->escape_string($vbulletin->GPC['name']) . "',
                `description` = '" . $db->escape_string($vbulletin->GPC['description']) . "',
                `instructions` = '" . $db->escape_string($vbulletin->GPC['instructions']) . "',
                `image` = '" . $db->escape_string($vbulletin->GPC['image']) . "',  
                `order` = '" . $db->escape_string($vbulletin->GPC['order']) . "',
                `parentid` = '" . $db->escape_string($vbulletin->GPC['categoryid']) . "', 
                `amount` = '" . $db->escape_string($vbulletin->GPC['amount']) . "',
                `active` = " . $vbulletin->GPC['active'] . "
            WHERE marketid = " . $vbulletin->GPC['marketid']
        );
        if ($vbulletin->GPC['active'] != 1) {
        $db->query_write("
            UPDATE " . TABLE_PREFIX . "market_items SET 
                `active` = " . $vbulletin->GPC['active'] . "
            WHERE parentid = " . $vbulletin->GPC['marketid']
        );
        }
        if ($db->affected_rows() > 0)
        {
            define('CP_REDIRECT', 'market.php?do=category');
            print_stop_message('market_item_saved_successfully');
        }
        else
        {
            define('CP_REDIRECT', 'market.php?do=category');
            print_stop_message('market_item_saved_successfully');
//            print_stop_message('market_item_saved_failed');
        }
    }
}


// ###################### Category Functions #######################
if ($_GET['do'] == 'gifts')
{
    print_cp_header($vbphrase['market_gift_header']);
    print_form_header('market', 'addgift');
    print_table_header($vbphrase['market_gift_header'], 5);
    print_description_row($vbphrase['market_gift_description'], 0, 5);
    echo '<tr>
        <td class="thead">' . $vbphrase['market_name'] . '</td>
        <td class="thead" style="white-space:nowrap">' . $vbphrase['market_icon_small'] . '</td>
        <td class="thead" style="white-space:nowrap">' . $vbphrase['market_icon_big'] . '</td>
        <td class="thead" style="white-space:nowrap">' . $vbphrase['market_purchases_total'] . '</td>
        <td class="thead">' . $vbphrase['controls'] . '</td>
    </tr>';

    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_gifts where `createdby` = 0 ORDER BY `name` ASC");
    while ($category = $db->fetch_array($result))
    {
        $class = fetch_row_bgclass();
        if ($category[active] != 1) {
        $active_0s = "<s>";
        $active_0e = "</s>";
        } else {
        $active_0s = "";
        $active_0e = "";
        }
    $site_url = $vbulletin->options['bburl'];
    $icon_small = "$site_url/$category[icon_small]";
    $icon_big = "$site_url/$category[icon_big]";
    $purchases = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions where gift_id='$category[giftid]'"));

        echo '<tr>
            <td class="' . $class . '" width="65%">&nbsp;' . $active_0s . $spacer . htmlspecialchars_uni($category['name']) . $active_0e . '</td>
            <td class="' . $class . '" align="center" width="8%"><img src="' . $icon_small . '"></td>
            <td class="' . $class . '" align="center" width="8%"><img src="' . $icon_big . '"></td>
            <td class="' . $class . '" align="center" width="8%"><a href="market.php?do=itemgifthistory&id=' . $category['giftid'] . '">' . $purchases . '</a></td>
            <td class="' . $class . '" align="center"><a href="market.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editgift&amp;id=' . $category['giftid'] . '"><img src="' . $site_url . '/images/pointmarket/edit.png" border="0" alt="' . $vbphrase['edit'] . '"></a></td>
            </tr>';

    }
    $db->free_result($result);

    print_submit_row($vbphrase['market_create'], $vbphrase['reset'], 5);

    print_cp_footer();

}

// ###################### Category Functions #######################
if ($_GET['do'] == 'giftscustom')
{
    print_cp_header($vbphrase['market_gift_header']);
    print_form_header('market', 'addcustomgift');
    print_table_header($vbphrase['market_gifts_custom'], 5);
    print_description_row($vbphrase['market_customgift_description'], 0, 5);
    echo '<tr>
        <td class="thead">' . $vbphrase['market_name'] . '</td>
        <td class="thead" style="white-space:nowrap">' . $vbphrase['market_icon_small'] . '</td>
        <td class="thead" style="white-space:nowrap">' . $vbphrase['market_icon_big'] . '</td>
        <td class="thead" style="white-space:nowrap">' . $vbphrase['market_purchases_total'] . '</td>
        <td class="thead">' . $vbphrase['controls'] . '</td>
    </tr>';

    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_gifts_custom ORDER BY `name` ASC");
    while ($category = $db->fetch_array($result))
    {
        $class = fetch_row_bgclass();
        if ($category[active] != 1) {
        $active_0s = "<s>";
        $active_0e = "</s>";
        } else {
        $active_0s = "";
        $active_0e = "";
        }
    $site_url = $vbulletin->options['bburl'];
    $icon_small = "$site_url/$category[icon_small]";
    $icon_big = "$site_url/$category[icon_big]";
    $purchases = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions where gift_customid='$category[customid]'"));

        echo '<tr>
            <td class="' . $class . '" width="65%">&nbsp;' . $active_0s . $spacer . htmlspecialchars_uni($category['name']) . $active_0e . '</td>
            <td class="' . $class . '" align="center" width="8%"><img src="' . $icon_small . '"></td>
            <td class="' . $class . '" align="center" width="8%"><img src="' . $icon_big . '"></td>
            <td class="' . $class . '" align="center" width="8%"><a href="market.php?do=itemcustomgifthistory&id=' . $category['customid'] . '">' . $purchases . '</a></td>
            <td class="' . $class . '" align="center"><a href="market.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editcustomgift&amp;id=' . $category['customid'] . '"><img src="' . $site_url . '/images/pointmarket/edit.png" border="0" alt="' . $vbphrase['edit'] . '"></a></td>
            </tr>';

    }
    $db->free_result($result);

    print_submit_row($vbphrase['market_create'], $vbphrase['reset'], 5);

    print_cp_footer();

}

// ###################### Statistics #######################
if ($_GET['do'] == 'statistics')
{

    $text = $vbulletin->options['market_point_name'];
    $most_purchases = $db->fetch_array($db->query_read("SELECT username, market_purchases FROM " . TABLE_PREFIX . "user order by market_purchases DESC limit 0,1"));
    $most_refunds = $db->fetch_array($db->query_read("SELECT username, market_refund FROM " . TABLE_PREFIX . "user order by market_refund DESC limit 0,1"));
    $most_points = $db->fetch_array($db->query_read("SELECT username, $text FROM " . TABLE_PREFIX . "user order by $text DESC limit 0,1"));


    $mostpopular = $db->fetch_array($db->query_read("SELECT marketid, COUNT(marketid) FROM " . TABLE_PREFIX . "market_transactions
    GROUP BY marketid ORDER BY COUNT( marketid ) DESC LIMIT 0 , 1"));
    $popular_name = $db->fetch_array($db->query_read("SELECT name FROM " . TABLE_PREFIX . "market_items where marketid='$mostpopular[marketid]'"));

    $mostrefunded = $db->fetch_array($db->query_read("SELECT marketid, COUNT(marketid) FROM " . TABLE_PREFIX . "market_transactions
    where refund_date != '0' GROUP BY marketid ORDER BY COUNT( marketid ) DESC LIMIT 0 , 1"));
    $refund_name = $db->fetch_array($db->query_read("SELECT name FROM " . TABLE_PREFIX . "market_items where marketid='$mostrefunded[marketid]'"));

    $moststeals = $db->fetch_array($db->query_read("SELECT userid, COUNT(userid) FROM " . TABLE_PREFIX . "market_transactions
    where marketid >= 7 AND marketid <= 9 GROUP BY userid ORDER BY COUNT( userid ) DESC LIMIT 0 , 1"));
    $steal_username = $db->fetch_array($db->query_read("SELECT username FROM " . TABLE_PREFIX . "user where userid='$moststeals[userid]'"));


    print_cp_header($vbphrase['market_stats_header']);
    print_form_header('market', 'statuserpurchase');
    print_table_header($vbphrase['market_general_stats'], 7);
    $class = fetch_row_bgclass();
    echo '<tr>
            <td class="' . $class . '">' . $vbphrase['market_transaction_count'] . '</td>
            <td class="' . $class . '">' .  $most_purchases[market_purchases] . '</td>
            <td class="' . $class . '">' .  $most_purchases[username] . '</td>
            <td class="' . $class . '" width="10%"></td>
            <td class="' . $class . '">' . $vbphrase['market_most_purchased'] . '</td>
            <td class="' . $class . '">' .  $mostpopular['COUNT(marketid)'] . '</td>
            <td class="' . $class . '">' .  $popular_name[name] . '</td>

    </tr><tr>
            <td class="' . $class . '">' . $vbphrase['market_refund_count'] . '</td>
            <td class="' . $class . '">' .  $most_refunds[market_refund] . '</td>
            <td class="' . $class . '">' .  $most_refunds[username] . '</td>
            <td class="' . $class . '" width="10%"></td>
            <td class="' . $class . '">' . $vbphrase['market_most_refunded'] . '</td>
            <td class="' . $class . '">' .  $mostrefunded['COUNT(marketid)'] . '</td>
            <td class="' . $class . '">' .  $refund_name[name] . '</td>
    </tr>
    <tr>
            <td class="' . $class . '">' . $vbphrase['market_point_amount'] . '</td>
            <td class="' . $class . '">' .  $most_points[$text] . '</td>
            <td class="' . $class . '">' .  $most_points[username] . '</td>
            <td class="' . $class . '" width="10%"></td>
            <td class="' . $class . '">' . $vbphrase['market_most_steals'] . '</td>
            <td class="' . $class . '">' .  $moststeals['COUNT(userid)'] . '</td>
            <td class="' . $class . '">' .  $steal_username[username] . '</td>
    </tr>

    ';
    print_table_footer(7, "",  0);


    print_form_header('market', 'statuserpurchase');
    print_table_header($vbphrase['market_transaction_count'], 2);
    print_input_row($vbphrase['market_user_display'], 'user_cycle', 15);
    print_submit_row($vbphrase['search'], $vbphrase['reset'], 2);

    print_form_header('market', 'statuserrefund');
    print_table_header($vbphrase['market_refund_count'], 2);
    print_input_row($vbphrase['market_user_display'], 'user_cycle', 15);
    print_submit_row($vbphrase['search'], $vbphrase['reset'], 2);

    print_form_header('market', 'statpoints');
    print_table_header($vbphrase['market_point_amount'], 2);
    print_input_row($vbphrase['market_user_display'], 'user_cycle', 15);
    print_submit_row($vbphrase['search'], $vbphrase['reset'], 2);

    print_cp_footer();

}


// ###################### Maintenance Functions #######################
if ($_GET['do'] == 'statuserpurchase' OR $_GET['do'] == 'statuserrefund' OR $_GET['do'] == 'statpoints')
{

if ($_GET['do'] == 'statuserpurchase') {
$text = "market_purchases";
}
else if ($_GET['do'] == 'statuserrefund') {
$text = "market_refund";
}
else if ($_GET['do'] == 'statpoints') {
$text = $vbulletin->options['market_point_name'];
}

    $vbulletin->input->clean_array_gpc('p', array(
        'user_cycle'       => TYPE_INT,
    ));
    $user_cycle = $vbulletin->GPC['user_cycle'];

    if ($vbulletin->GPC['user_cycle'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }

    $pointfield = $vbulletin->options['market_point_name'];
    $point_decimal = $vbulletin->options['market_point_decimal'];

    print_cp_header($vbphrase['market_stats_header']);
    print_form_header('market', 'userpurchase');
    print_table_header($vbphrase['market_stats_header'], 5);

    echo '<tr>
        <td class="thead" width="4%">' . $vbphrase['market_rank'] . '</td>
        <td class="thead" width="60%">' . $vbphrase['market_name'] . '</td>
        <td class="thead" width="12%" align="left">' . $vbphrase['market_num_purchases'] . '</td>
        <td class="thead" width="12%" align="left">' . $vbphrase['market_points'] . '</td>
        <td class="thead" width="12%" align="left">' . $vbphrase['market_num_refunds'] . '</td>
    </tr>';

    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "user ORDER BY `$text` DESC limit 0,$user_cycle");
    while ($display = $db->fetch_array($result))
    {

    ++$loop;
    $points = number_format($display[$pointfield], $point_decimal, '.', ',');
    echo '<tr>
        <td class="thead" >' . $loop . '</td>
        <td class="thead">' . $display['username'] . '</td>
        <td class="thead"  align="right">' . $display['market_purchases'] . '</td>
        <td class="thead"  align="right">' . $points . '</td>
        <td class="thead"  align="right">' . $display['market_refund'] . '</td>
    </tr>';

    }

    print_table_footer(5, "",  0);
    print_cp_footer();

}

// ###################### Maintenance Functions #######################
if ($_GET['do'] == 'maintenance')
{

// Get Latest Version from vBulletin.org
$productVerCheckURL = "http://www.vbulletin.org/forum/misc.php?do=productcheck&pid=pointmarket";
$latestVersion = file_get_contents($productVerCheckURL);
$latestVersion = ereg_replace("[^A-Za-z0-9.]", "", $latestVersion );
$latestVersion = substr($latestVersion, 23);
$latestVersion = ereg_replace("[^0-9.]", "", $latestVersion );

// Get Current Version
$array = $db->query_first("SELECT version FROM " . TABLE_PREFIX . "product WHERE productid = 'pointmarket' LIMIT 1");
$currentVersion = $array[version];
// Site Url
    $site_url = $vbulletin->options['bburl'];
    $site_url .= "/images/pointmarket";

// Build Error Checking
if ( $currentVersion == $latestVersion OR $currentVersion > $latestVersion) {
	$text1 =  $vbphrase['market_maintenance_buildtodate'];
    $icon1 = "$site_url/checkmark.png";
	}

if ( $currentVersion < $latestVersion ) {
	$text1 = "<a href='http://www.vbulletin.org/forum/showthread.php?t=232676'> " . $vbphrase['market_maintenance_downloadup'] . "</a>";
    $icon1 = "$site_url/erase.png";
	}
// Build Point Error Checking
$pointfield = $vbulletin->options['market_point_name'];
if (!$pointfield) {
	$text2 =  $vbphrase['market_maintenance_nodata'];
    $icon2 = "$site_url/erase.png";
}
else if ($vbulletin->userinfo[$pointfield] > 0)
{
	$text2 =  $vbphrase['market_maintenance_number'];
    $icon2 = "$site_url/checkmark.png";
} else {
	$text2 =  $vbphrase['market_maintenance_badfield'];
    $icon2 = "$site_url/warning.gif";
}


// Point Market Debugger
    print_cp_header($vbphrase['market_maintenance']);
    print_form_header('market', '');
    print_table_header($vbphrase['market_error_checker'], 4);
    echo '<tr>
        <td class="thead" width="15%">' . $vbphrase['market_version'] . '</td>
        <td class="thead" width="5%" align="center"><img src="' . $icon1 . '"></td>
        <td class="thead" width="15%">' .$currentVersion . ' / ' .$latestVersion . '</td>
        <td class="thead" width="65%" align="left">' . $text1 . '</td>
    </tr>';
    echo '<tr>
        <td class="thead">' . $vbphrase['market_point_field'] . '</td>
        <td class="thead" align="center"><img src="' . $icon2 . '"></td>
        <td class="thead">' . $vbulletin->options['market_point_name'] . '</td>
        <td class="thead" align="left">' . $text2 . '</td>
    </tr>';

    print_table_footer(4, "",  0);

// Update Purchase Count
    print_form_header('market', 'updatecount');
    print_table_header($vbphrase['market_transaction_count'], 2);
    print_description_row($vbphrase['market_transaction_count_desc'], 0, 2);
    print_input_row($vbphrase['market_user_cycle'], 'user_cycle', 50);
    print_submit_row($vbphrase['update'], $vbphrase['reset'], 2);

// Update Refund Count
    print_form_header('market', 'updaterefund');
    print_table_header($vbphrase['market_refund_count'], 2);
    print_description_row($vbphrase['market_transaction_refund_desc'], 0, 2);
    print_input_row($vbphrase['market_user_cycle'], 'user_cycle', 50);
    print_submit_row($vbphrase['update'], $vbphrase['reset'], 2);

// Update Use Titles
    print_form_header('market', 'updateusertitle');
    print_table_header($vbphrase['market_usertitle_fix'], 2);
    print_description_row($vbphrase['market_usertitle_fix_desc'], 0, 2);
    print_input_row($vbphrase['market_user_cycle'], 'user_cycle', 50);
    print_submit_row($vbphrase['update'], $vbphrase['reset'], 2);

    print_cp_footer();

}

// ###################### Maintenance Functions #######################
if ($_GET['do'] == 'updatecount')
{

    print_cp_header($vbphrase['market_maintenance']);

// First Page
    $vbulletin->input->clean_array_gpc('p', array(
        'user_cycle'       => TYPE_INT,
    ));
    $user_cycle = $vbulletin->GPC['user_cycle'];
    if (!$user_cycle) {
    $user_cycle = $vbulletin->input->clean_gpc('r', 'user_cycle', TYPE_UINT);
    }

// Calculate Page Information
    $page = $vbulletin->input->clean_gpc('r', 'page', TYPE_UINT);
    // Page Must be 1
    if ($page == 0) {
    $page = 1;
    }
    $current = $page*$user_cycle;
    $last = $current-$user_cycle;

// Stop Error
    if ($vbulletin->GPC['user_cycle'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
// Page Must be 1
    if ($page == 0) {
    $page = 1;
    }

// Grab User Query
	$users = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "user
		WHERE userid >= $last AND userid <= $current
		ORDER BY userid
		LIMIT " . $vbulletin->GPC['user_cycle']
	);

// Do Loop and Update Query
    while ($user = $db->fetch_array($users))
	{

    $purchases = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions where userid='$user[userid]'"));
    $vbulletin->db->query_write("update " . TABLE_PREFIX . "user set `market_purchases`=$purchases where userid='$user[userid]'");

    $lastid = $user[userid];

	echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
	vbflush();

    }

$page = $page + 1;

// Redirection Message
	if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid > '$lastid' LIMIT 1"))
	{
	  	print_cp_redirect("market.php?" . $vbulletin->session->vars['sessionurl'] . "do=updatecount&page=$page&user_cycle=" . $vbulletin->GPC['user_cycle']);
	  	echo "<p><a href=\"market.php?" . $vbulletin->session->vars['sessionurl'] . "do=updatecount&amp;page=$page&amp;user_cycle=" . $vbulletin->GPC['user_cycle'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'market.php?do=maintenance');
		print_stop_message('market_purchase_time_success');
	}

}

// ###################### Maintenance Functions #######################
if ($_GET['do'] == 'updaterefund')
{

    print_cp_header($vbphrase['market_maintenance']);

// First Page
    $vbulletin->input->clean_array_gpc('p', array(
        'user_cycle'       => TYPE_INT,
    ));
    $user_cycle = $vbulletin->GPC['user_cycle'];
    if (!$user_cycle) {
    $user_cycle = $vbulletin->input->clean_gpc('r', 'user_cycle', TYPE_UINT);
    }

// Calculate Page Information
    $page = $vbulletin->input->clean_gpc('r', 'page', TYPE_UINT);
    // Page Must be 1
    if ($page == 0) {
    $page = 1;
    }
    $current = $page*$user_cycle;
    $last = $current-$user_cycle;

// Stop Error
    if ($vbulletin->GPC['user_cycle'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
// Page Must be 1
    if ($page == 0) {
    $page = 1;
    }

// Grab User Query
	$users = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "user
		WHERE userid >= $last AND userid <= $current
		ORDER BY userid
		LIMIT " . $vbulletin->GPC['user_cycle']
	);

// Do Loop and Update Query
    while ($user = $db->fetch_array($users))
	{

    $purchases = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions where userid='$user[userid]' AND refund_date != 0"));
    $vbulletin->db->query_write("update " . TABLE_PREFIX . "user set `market_refund`=$purchases where userid='$user[userid]'");


    $lastid = $user[userid];

	echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
	vbflush();

    }

$page = $page + 1;

// Redirection Message
	if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid > '$lastid' LIMIT 1"))
	{
	  	print_cp_redirect("market.php?" . $vbulletin->session->vars['sessionurl'] . "do=updaterefund&page=$page&user_cycle=" . $vbulletin->GPC['user_cycle']);
	  	echo "<p><a href=\"market.php?" . $vbulletin->session->vars['sessionurl'] . "do=updaterefund&amp;page=$page&amp;user_cycle=" . $vbulletin->GPC['user_cycle'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'market.php?do=maintenance');
		print_stop_message('market_refund_time_success');
	}

}

// ###################### Maintenance Functions #######################
if ($_GET['do'] == 'updateusertitle')
{

    print_cp_header($vbphrase['market_maintenance']);

// First Page
    $vbulletin->input->clean_array_gpc('p', array(
        'user_cycle'       => TYPE_INT,
    ));
    $user_cycle = $vbulletin->GPC['user_cycle'];
    if (!$user_cycle) {
    $user_cycle = $vbulletin->input->clean_gpc('r', 'user_cycle', TYPE_UINT);
    }

// Calculate Page Information
    $page = $vbulletin->input->clean_gpc('r', 'page', TYPE_UINT);
    // Page Must be 1
    if ($page == 0) {
    $page = 1;
    }
    $current = $page*$user_cycle;
    $last = $current-$user_cycle;

// Stop Error
    if ($vbulletin->GPC['user_cycle'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
// Page Must be 1
    if ($page == 0) {
    $page = 1;
    }

// Grab User Query
	$users = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "market_transactions
		WHERE (marketid = 2 OR marketid=5 OR marketid=6) AND usertitle_type > 0
		ORDER BY tranid ASC
		LIMIT " . $vbulletin->GPC['user_cycle']
	);

// Do Loop and Update Query
    while ($user = $db->fetch_array($users))
	{

    $vbulletin->db->query_write("update " . TABLE_PREFIX . "user set `customtitle`=$user[usertitle_type], usertitle='$user[usertitle]' where userid='$user[affecteduser]'");

    $lastid = $user[tranid];

	echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
	vbflush();
    }

$page = $page + 1;

// Redirection Message
	if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "market_transactions WHERE tranid > '$lastid' AND (marketid = 2 OR marketid=5 OR marketid=6) AND usertitle_type > 0 LIMIT 1"))
	{
	  	print_cp_redirect("market.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateusertitle&page=$page&user_cycle=" . $vbulletin->GPC['user_cycle']);
	  	echo "<p><a href=\"market.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateusertitle&amp;page=$page&amp;user_cycle=" . $vbulletin->GPC['user_cycle'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'market.php?do=maintenance');
		print_stop_message('market_usertitle_success');
	}

}


// ################### Do Edit Custom Gift ######################
if ($_GET['do'] == 'editcustomgift')
{
    $cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    $cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "market_gifts_custom WHERE `customid` ='$cleancatid'");

    print_cp_header($vbphrase['market_gift_header']);
    print_form_header('market', 'doeditcustomgift');
    print_table_header($vbphrase['market_giftcustom_edit'], 2);
    print_input_row($vbphrase['market_item_name'], 'name', $cat['name']);
    print_textarea_row($vbphrase['market_item_description_edit'], 'description', $cat['description'], 4, 40, true, false);

    print_input_row($vbphrase['market_icon_small'], 'icon_small', $cat['icon_small']);
    print_input_row($vbphrase['market_icon_big'], 'icon_big', $cat['icon_big']);

    print_select_row($vbphrase['market_item_active'], 'active', array('1' => $vbphrase['enabled'], '0' => $vbphrase['disabled']), $cat['active']);
    construct_hidden_code('customid', $cat['customid']);
    print_submit_row($vbphrase['save']);

    print_cp_footer();
}

// ###################### Do Edit Gift #########################
if ($_GET['do'] == 'editgift')
{
    $cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
    $cat = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "market_gifts WHERE `giftid` ='$cleancatid'");

    print_cp_header($vbphrase['market_gift_header']);
    print_form_header('market', 'doeditgift');
    print_table_header($vbphrase['market_gift_edit'], 2);
    print_input_row($vbphrase['market_item_name'], 'name', $cat['name']);
    print_textarea_row($vbphrase['market_item_description_edit'], 'description', $cat['description'], 4, 40, true, false);

    $getgift = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_gifts_custom WHERE active=1 OR customid='$cat[customid]' ORDER BY name ASC");
    $cat_list = array();
    while($find = $vbulletin->db->fetch_array($getgift))
    {
       		$cat_list[$find['customid']] = $find['name'];
    }
	print_select_row($vbphrase['market_gift_template'], 'customid', $cat_list, $cat['customid']);

    print_input_row($vbphrase['market_item_amount_info'], 'amount', $cat['amount']);
    print_select_row($vbphrase['market_item_active'], 'active', array('1' => $vbphrase['enabled'], '0' => $vbphrase['disabled']), $cat['active']);
    construct_hidden_code('giftid', $cat['giftid']);
    print_submit_row($vbphrase['save']);

    print_cp_footer();
}

// ################### Do Add Custom Gift ######################
if ($_GET['do'] == 'addcustomgift')
{

    print_cp_header($vbphrase['market_gift_header']);
    print_form_header('market', 'doaddcustomgift');
    print_table_header($vbphrase['market_giftadd_rowhead'], 2);
    print_input_row($vbphrase['market_item_name'], 'name', $cat['name']);
    print_textarea_row($vbphrase['market_item_description_edit'], 'description', $cat['description'], 4, 40, true, false);

    print_input_row($vbphrase['market_icon_small_desc'], 'icon_small', $cat['icon_small']);
    print_input_row($vbphrase['market_icon_big_desc'], 'icon_big', $cat['icon_big']);

    print_select_row($vbphrase['market_item_active'], 'active', array('1' => $vbphrase['enabled'], '0' => $vbphrase['disabled']), $cat['active']);
    construct_hidden_code('customid', $cat['customid']);
    print_submit_row($vbphrase['save']);

    print_cp_footer();
}

// ###################### Do Add Gift #########################
if ($_GET['do'] == 'addgift')
{
    $cleancatid = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);

    print_cp_header($vbphrase['market_gift_header']);
    print_form_header('market', 'doaddgift');
    print_table_header($vbphrase['market_giftadd_rowhead'], 2);
    print_input_row($vbphrase['market_item_name'], 'name', $cat['name']);
    print_textarea_row($vbphrase['market_item_description_edit'], 'description', $cat['description'], 4, 40, true, false);

    $getgift = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_gifts_custom WHERE active=1 OR customid='$cat[customid]' ORDER BY name ASC");
    $cat_list = array();
    while($CAT = $vbulletin->db->fetch_array($getgift))
    {
       		$cat_list[$CAT['customid']] = $CAT['name'];
    }
	print_select_row($vbphrase['market_gift_template'], 'customid', $cat_list, $cat['customid']);

    print_input_row($vbphrase['market_item_amount_info'], 'amount', $cat['amount']);
    print_select_row($vbphrase['market_item_active'], 'active', array('1' => $vbphrase['enabled'], '0' => $vbphrase['disabled']), $cat['active']);

    print_submit_row($vbphrase['market_create']);
    print_cp_footer();
}


// ################### Do Custom Gift Edit #####################
if ($_POST['do'] == 'doeditcustomgift')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'customid'         => TYPE_UINT,
        'name'             => TYPE_STR,
        'description'      => TYPE_STR,
        'icon_small'       => TYPE_STR,
        'icon_big'         => TYPE_STR,
        'active'           => TYPE_INT,
    ));

    if ($vbulletin->GPC['customid'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['icon_small'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['icon_big'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['name'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else
    {

        $db->query_write("
            UPDATE " . TABLE_PREFIX . "market_gifts_custom SET
                `name` = '" . $db->escape_string($vbulletin->GPC['name']) . "',
                `description` = '" . $db->escape_string($vbulletin->GPC['description']) . "',
                `customid` = '" . $db->escape_string($vbulletin->GPC['customid']) . "',
                `icon_small` = '" . $db->escape_string($vbulletin->GPC['icon_small']) . "',
                `icon_big` = '" . $db->escape_string($vbulletin->GPC['icon_big']) . "',
                `active` = " . $vbulletin->GPC['active'] . "
            WHERE customid = " . $vbulletin->GPC['customid']
        );

        $db->query_write("
            UPDATE " . TABLE_PREFIX . "market_gifts SET
                `icon_small` = '" . $db->escape_string($vbulletin->GPC['icon_small']) . "',
                `icon_big` = '" . $db->escape_string($vbulletin->GPC['icon_big']) . "',
                `active` = " . $vbulletin->GPC['active'] . "
            WHERE customid = " . $vbulletin->GPC['customid']
        );



        if ($db->affected_rows() > 0)
        {
            define('CP_REDIRECT', 'market.php?do=giftscustom');
            print_stop_message('market_item_saved_successfully');
        }
        else
        {
            define('CP_REDIRECT', 'market.php?do=giftscustom');
            print_stop_message('market_item_saved_successfully');
//            print_stop_message('market_item_saved_failed');
        }
    }
}

// ###################### Do Edit Item #######################
if ($_POST['do'] == 'doeditgift')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'giftid'         => TYPE_UINT,
        'name'             => TYPE_STR,
        'description'      => TYPE_STR,
        'customid'         => TYPE_NUM,
        'amount'           => TYPE_NUM,
        'active'           => TYPE_INT,
    ));

    if ($vbulletin->GPC['giftid'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['name'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else
    {

    $findimages = $vbulletin->db->fetch_array($db->query_read("select * from " . TABLE_PREFIX . "market_gifts_custom WHERE `customid`='" . $db->escape_string($vbulletin->GPC['customid']) . "'"));
        $db->query_write("
            UPDATE " . TABLE_PREFIX . "market_gifts SET
                `name` = '" . $db->escape_string($vbulletin->GPC['name']) . "',
                `description` = '" . $db->escape_string($vbulletin->GPC['description']) . "',
                `customid` = '" . $db->escape_string($vbulletin->GPC['customid']) . "',
                `icon_small` = '" . $findimages[icon_small] . "',
                `icon_big` = '" . $findimages[icon_big] . "',
                `amount` = '" . $db->escape_string($vbulletin->GPC['amount']) . "',
                `active` = " . $vbulletin->GPC['active'] . "
            WHERE giftid = " . $vbulletin->GPC['giftid']
        );



        if ($db->affected_rows() > 0)
        {
            define('CP_REDIRECT', 'market.php?do=gifts');
            print_stop_message('market_item_saved_successfully');
        }
        else
        {
            define('CP_REDIRECT', 'market.php?do=gifts');
            print_stop_message('market_item_saved_successfully');
//            print_stop_message('market_item_saved_failed');
        }
    }
}
// ###################### Do Add Gift ########################
if ($_POST['do'] == 'doaddgift')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'name'             => TYPE_STR,
        'description'      => TYPE_STR,
        'customid'         => TYPE_NUM,
        'amount'           => TYPE_NUM,
        'active'           => TYPE_INT,
    ));

    if ($vbulletin->GPC['customid'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    if ($vbulletin->GPC['amount'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    if ($vbulletin->GPC['description'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['name'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else
    {

    $findimages = $vbulletin->db->fetch_array($db->query_read("select * from " . TABLE_PREFIX . "market_gifts_custom WHERE `customid`='" . $db->escape_string($vbulletin->GPC['customid']) . "'"));


        $db->query_write("
        INSERT INTO " . TABLE_PREFIX . "market_gifts
            (name, description, customid, icon_small, icon_big, amount, active)
        VALUES
            ('" . $db->escape_string($vbulletin->GPC['name']) . "',
            '" . $db->escape_string($vbulletin->GPC['description']) . "',
            '" . $db->escape_string($vbulletin->GPC['customid']) . "',
            '" . $findimages[icon_small] . "',
            '" . $findimages[icon_big] . "',
            '" . $db->escape_string($vbulletin->GPC['amount']) . "',
            " . $vbulletin->GPC['active'] . ")
    ");

        if ($db->affected_rows() > 0)
        {
            define('CP_REDIRECT', 'market.php?do=gifts');
            print_stop_message('market_item_saved_successfully');
        }
        else
        {
            define('CP_REDIRECT', 'market.php?do=gifts');
            print_stop_message('market_item_saved_successfully');
//            print_stop_message('market_item_saved_failed');
        }
    }
}

// ###################### Do Add Gift ########################
if ($_POST['do'] == 'doaddcustomgift')
{
    $vbulletin->input->clean_array_gpc('p', array(
        'name'             => TYPE_STR,
        'description'      => TYPE_STR,
        'icon_small'       => TYPE_STR,
        'icon_big'         => TYPE_STR,
        'active'           => TYPE_INT,
    ));

    if ($vbulletin->GPC['icon_small'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    if ($vbulletin->GPC['icon_big'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    if ($vbulletin->GPC['description'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else if ($vbulletin->GPC['name'] == '')
    {
        print_stop_message('please_complete_required_fields');
    }
    else
    {

        $db->query_write("
        INSERT INTO " . TABLE_PREFIX . "market_gifts_custom
            (name, description, icon_small, icon_big, active)
        VALUES
            ('" . $db->escape_string($vbulletin->GPC['name']) . "',
            '" . $db->escape_string($vbulletin->GPC['description']) . "',
            '" . $db->escape_string($vbulletin->GPC['icon_small']) . "',
            '" . $db->escape_string($vbulletin->GPC['icon_big']) . "',
            " . $vbulletin->GPC['active'] . ")
    ");

        if ($db->affected_rows() > 0)
        {
            define('CP_REDIRECT', 'market.php?do=giftscustom');
            print_stop_message('market_item_saved_successfully');
        }
        else
        {
            define('CP_REDIRECT', 'market.php?do=giftscustom');
            print_stop_message('market_item_saved_successfully');
//            print_stop_message('market_item_saved_failed');
        }
    }
}




?>
