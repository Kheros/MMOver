<?php

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################

define('THIS_SCRIPT', 'market');
define('CSRF_PROTECTION', true);
// change this depending on your filename

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
'pointmarket'
);

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array('MARKET', 'market_sidebar',
);

// pre-cache templates used by specific actions
$actiontemplates = array('market_category',
'market_itemlist',
'market_itembuy',
'market_itembuy_do',
'market_home',
'market_gift_list',
'market_gift_access_bit',
'market_gift_access',
'market_gambling',
'market_gambling_bit', 
'market_refund',
'market_donate_history',
'market_steal_history',
'market_steal_history_bit',
);

// ######################### REQUIRE BACK-END ############################
// if your page is outside of your normal vb forums directory, you should change directories by uncommenting the next line
// chdir ('/path/to/your/forums');
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$navbits = construct_navbits(array('' => 'Point Market'));
$navbar = render_navbar_template($navbits);

// ############# PAGE TITLE ##############
$pagetitle = 'Point Market';
$pointfield = $vbulletin->options['market_point_name']; // Field Name
$points_name = $vbulletin->options['market_point'];     // Regular Name
$points = $vbulletin->userinfo[$pointfield];
$userid = $vbulletin->userinfo['userid'];   
$refunds = $vbulletin->userinfo['market_refund']; 
$purchases = $vbulletin->userinfo['market_purchases'];
$discount = $permissions['market_discount'];
$discount_percentage = $discount*100;
$point_decimal = $vbulletin->options['market_point_decimal'];


// ########### Point Market Open? ########
if (!$vbulletin->options['market_active'])
{
        standard_error($vbulletin->options['market_closed']);
}
// ########### User is a Guest ########
if ($vbulletin->userinfo[usergroupid] == 1)
{
    print_no_permission();
}
// ########### No Point Field ########
if (!$vbulletin->options['market_point_name'])
{
        standard_error($vbphrase['market_no_point_field']);
}
// ########### Incorrect Point Field ########
if (!$vbulletin->userinfo[$pointfield])
{
        standard_error($vbphrase['market_bad_point_field']);
}
// ###### Grab Standard Permissions ######
if (!($permissions['market_permissions']))
{
    print_no_permission();
}


// #######################################################################
// ####################### MAIN PAGE Display #############################
// #######################################################################


if (!$_REQUEST['do']) {

require_once('./includes/functions_user.php');


// Generate Top 3 Information
    $mostpopular = $db->fetch_array($db->query_read("SELECT *, COUNT(marketid) FROM " . TABLE_PREFIX . "market_transactions
    GROUP BY marketid ORDER BY COUNT( marketid ) DESC LIMIT 0 , 1"));
    if (!$mostpopular) {
    $mostpopular[marketid] = 2; $mostpopular['COUNT(marketid)'] = 0;    
    }
    $popular_name = $db->fetch_array($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_items where marketid='$mostpopular[marketid]'"));
    $popimage = "".stripslashes($popular_name[image])."";
    $popular_desc = "".stripslashes($popular_name[description])."";

    $mostrefunded = $db->fetch_array($db->query_read("SELECT *, COUNT(marketid) FROM " . TABLE_PREFIX . "market_transactions
    where refund_date != '0' GROUP BY marketid ORDER BY COUNT( marketid ) DESC LIMIT 0 , 1"));
    if (!$mostrefunded) {
    $mostrefunded[marketid] = 2; $mostrefunded['COUNT(marketid)'] = 0;    
    }
    $refund_name = $db->fetch_array($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_items where marketid='$mostrefunded[marketid]'"));
    $refundimage = "".stripslashes($refund_name[image])."";
    $refund_desc = "".stripslashes($refund_name[description])."";

    $mostexpensive = $db->fetch_array($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_items
    where iscategory=0 AND active=1 ORDER BY amount DESC LIMIT 0 , 1"));
    $expensive = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions
    where marketid='$mostexpensive[marketid]'"));
    $expenseimage = "".stripslashes($mostexpensive[image])."";
    $expense_desc = "".stripslashes($mostexpensive[description])."";

// Generate Top 3 Users
    $user_most_purchases = $db->fetch_array($db->query_read("SELECT * from " . TABLE_PREFIX . "user order by market_purchases DESC limit 0,1"));
    $ump_name = "".stripslashes($user_most_purchases[username])."";
    $ump_avatar = fetch_avatar_url($vbulletin->userinfo[$user_most_purchases[userid]]);
	if (!is_array($ump_avatar))
	{
		$ump_avatar = "images/misc/unknown.gif";
	}
    $user_most_refunds = $db->fetch_array($db->query_read("SELECT * from " . TABLE_PREFIX . "user order by market_refund DESC limit 0,1"));
    $umr_name = "".stripslashes($user_most_refunds[username])."";
    $umr_avatar = fetch_avatar_url($vbulletin->userinfo[$user_most_refunds[userid]]);
	if (!is_array($umr_avatar))
	{
		$umr_avatar = "images/misc/unknown.gif";
	}
    $moststeals = $db->fetch_array($db->query_read("SELECT userid, COUNT(userid) FROM " . TABLE_PREFIX . "market_transactions
    where marketid >= 7 AND marketid <= 9 GROUP BY userid ORDER BY COUNT( userid ) DESC LIMIT 0 , 1"));
    if (!$moststeals) {
    $moststeals[userid] = 1;
    $moststeals['COUNT(userid)'] = 0;    
    } 
    $steal_username = $db->fetch_array($db->query_read("SELECT * FROM " . TABLE_PREFIX . "user where userid='$moststeals[userid]'"));
    $steal_username = "".stripslashes($steal_username[username])."";
    $steal_avatar = fetch_avatar_url($vbulletin->userinfo[$steal_username[userid]]);
	if (!is_array($steal_avatar))
	{
		$steal_avatar = "images/misc/unknown.gif";
	}

    $templater = vB_Template::create('market_home');

    $templater->register('popular_name', $popular_name[name]);
    $templater->register('popular_id', $popular_name[marketid]);
    $templater->register('popular_amount', $popular_name[amount]);
    $templater->register('popular_image', $popimage);
    $templater->register('popular_count', $mostpopular['COUNT(marketid)']);
    $templater->register('popular_desc', $popular_desc);
    $templater->register('refund_name', $refund_name[name]);
    $templater->register('refund_id', $refund_name[marketid]);
    $templater->register('refund_amount', $refund_name[amount]);
    $templater->register('refund_image', $refundimage);
    $templater->register('refund_count', $mostrefunded['COUNT(marketid)']);
    $templater->register('refund_desc', $refund_desc);
    $templater->register('expense_name', $mostexpensive[name]);
    $templater->register('expense_id', $mostexpensive[marketid]);
    $templater->register('expense_amount', $mostexpensive[amount]);
    $templater->register('expense_image', $expenseimage);
    $templater->register('expense_desc', $expense_desc);
    $templater->register('expense_count', $expensive);

    $templater->register('ump_name', $ump_name);
    $templater->register('ump_id', $user_most_purchases[userid]);
    $templater->register('ump_amount', $user_most_purchases[market_purchases]);
    $templater->register('ump_avatar', $ump_avatar);

    $templater->register('umr_name', $umr_name);
    $templater->register('umr_id', $user_most_refunds[userid]);
    $templater->register('umr_amount', $user_most_refunds[market_refund]);
    $templater->register('umr_avatar', $umr_avatar);

    $templater->register('steal_name', $steal_username);
    $templater->register('steal_id', $steal_username[userid]);
    $templater->register('steal_count', $moststeals['COUNT(userid)']);
    $templater->register('steal_avatar', $steal_avatar);

    $market_itemlist .= $templater->render();


}


// #######################################################################
// ##################$###### MAIN CAT SCRIPT #############################
// #######################################################################

if ($_REQUEST['do'] == "cat")
{

if ($_REQUEST['id']) {
	$id = $_REQUEST['id'];
    $catinfo = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_items where `marketid` = ".addslashes($id)."");
} else {
    $catinfo = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_items where `iscategory`='1' AND `active`='1' order by `name` ASC");
}
$catinfo = $vbulletin->db->fetch_array($catinfo);
if (!$id AND $catinfo[marketid] > 0) {
    $id = $catinfo[marketid]; // First Found Category
}
if (!$catinfo OR $catinfo[active] == 0) {
// ###### Category is Inactive or Doesn't Exist. Display No Permissions error ######
    print_no_permission();
}

$templater = vB_Template::create('market_category');
$templater->register('name', $catinfo[name]);
$templater->register('pointfield', $pointfield);
$templater->register('points', $points);
$market_category .= $templater->render();


// ###### Display Possible Items ######
$getitems = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_items where parentid= ".addslashes($id)." AND active='1' ORDER BY `order` ASC");
// Loop through all results
while ($item = $vbulletin->db->fetch_array($getitems))
{

// Build Item Image
$image = "".stripslashes($item[image])."";
$total_bought = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions where marketid='$item[marketid]'"));

// Configure Amount
    $amount = $item[amount]-($item[amount]*$discount);
    // Generate Variables to be used
    $templater = vB_Template::create('market_itemlist');
    $templater->register('item_marketid', $item[marketid]);
    $templater->register('item_type', $item[type]);
    $templater->register('item_name', $item[name]);
    $templater->register('item_parentid', $item[parentid]);
    $templater->register('item_iscategory', $item[iscategory]);
    $templater->register('item_amount', $amount);
    $templater->register('total_bought', $total_bought);
    $templater->register('image', $image);
    $templater->register('item_description', $item[description]);
    // Register market_itemlist template
    $market_itemlist .= $templater->render();
}


}


// #######################################################################
// ######################### Purchase Item ###############################
// #######################################################################

if ($_REQUEST['do'] == "item")
{

$itemid = $_REQUEST['id'];
$getitem = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_items where marketid ='".addslashes($itemid)."'");
$itembuy = $vbulletin->db->fetch_array($getitem);
$item_type = $itembuy['type'];

// ###### Grab Standard Permissions ######
if ($itembuy['active'] != 1)
{
    print_no_permission();
}

// Apply 2 Decimal Rule
$item_amount = number_format($itembuy[amount], 2, '.', ',');
$item_new_amount = $itembuy[amount]-($itembuy[amount]*$discount);
$current_points = number_format($points, 2, '.', ',');
// Calculate Theoretical Remaining Amount With 2 Decimals
$remaining = round($points-$item_new_amount, 3);
$remaining = ($points-$item_new_amount)-$remaining;
if ($remaining < 0) {
    $remaining = round($points-$item_new_amount, 3) - .01; // Corrects Remaining Bug
} else {
    $remaining = round($points-$item_new_amount, 3);
}
$remaining = number_format($remaining, 2, '.', ',');
// Disable Purchase
if ($remaining < 0) {
$disabled = "disabled";
}

// Create Max Characters
$maxchar = 25; // Default
    // Custom User Title
    if ($itembuy[marketid] == 2)  {
       $maxchar = $vbulletin->options[ctMaxChars];
    }
    if ($itembuy[marketid] == 13)  {
       $maxchar = $vbulletin->options[maxuserlength];
    }

// Create Instructions
$instructions = "".stripslashes($itembuy[instructions])."";
if ($points < $item_new_amount) {
$disabled = "disabled";
}
$max_donate = $permissions['market_maxdonate'];
if ($max_donate == 0) {
$max_donate = "Unlimited";
}
if ($max_donate < 0) {
$max_donate = "0.00";
}
// Build Item's Image
$image = "".stripslashes($itembuy[image])."";

// Create Gift Options
if ($itembuy[marketid] >= 21 AND $itembuy[marketid] <= 23) {

if ($itembuy[marketid] == 22) {
	$gift_query="SELECT * from " . TABLE_PREFIX . "market_gifts_custom where active='1' ORDER BY `name` ASC";
} else {
	$gift_query="SELECT * from " . TABLE_PREFIX . "market_gifts where active='1' AND createdby='0' ORDER BY `name` ASC";
}


$getgifts = $vbulletin->db->query_read("$gift_query");
while ($gift = $vbulletin->db->fetch_array($getgifts))
{

	if ($itembuy[marketid] == 22) {
	$customid = $gift[customid];
	} else {
	$customid = $gift[giftid];
	}
	++$loop;
	$templater = vB_Template::create('market_gift_list');
	$templater->register('gift_customid', $customid);
	$templater->register('loop', $loop);
    $templater->register('gamount', $gift[amount]);
	$templater->register('gift_name', $gift[name]);
	$templater->register('gift_description', $gift[description]);
	$templater->register('gift_iconsmall', $gift[icon_small]);
	// Register market_itemlist template
	$market_gift_list .= $templater->render();
if ($loop == 2) {
	$loop = 0;
}

}
}

$templater = vB_Template::create('market_itembuy');
$templater->register('market_gift_list', $market_gift_list);
$templater->register('name', $itembuy[name]);
$templater->register('item_id', $itembuy[marketid]);
$templater->register('item_type', $item_type);
$templater->register('amount', $item_amount);
$templater->register('new_amount', number_format($item_new_amount, 2, '.', ','));
$templater->register('current_points', $current_points);
$templater->register('discount_percentage', number_format($discount_percentage, 2, '.', ''));
$templater->register('remaining', $remaining);
$templater->register('image', $image);
$templater->register('disabled', $disabled);
$templater->register('maxchar', $maxchar);
$templater->register('instructions', $instructions);
$templater->register('gift_minimum', $permissions['market_gift_minimum']);
$templater->register('gift_maximum', $permissions['market_gift_maximum']);
if ($itembuy[type] == 4) {
$templater->register('max_donate', $max_donate);
}
$market_itembuy .= $templater->render();

}


// #######################################################################
// ########################## Do Purchase ################################
// #######################################################################

if ($_REQUEST['do'] == "purchase")
{
$error = 0;
if ($_POST) {
$item_id = $_POST['item_id'];
$affected_user = $_POST['affected_user'];
$donate_amount = $_POST['donate_amount'];
$new_username = $_POST['new_username'];
$new_feature = $_POST['new_feature'];
$reason = $_POST['reason'];
$face = $_POST['face'];
$color = $_POST['color'];
$gift_name = $_POST['gift_name'];
$gift_description = $_POST['gift_description'];
$gift_amount = $_POST['gift_amount'];
$gift_customid = $_POST['gift_customid'];

$getitem = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_items where marketid ='".addslashes($item_id)."'");
$itembuy = $vbulletin->db->fetch_array($getitem);

// Grab Gift Information
if ($gift_customid) {
	$getgift = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_gifts_custom where customid ='".addslashes($gift_customid)."'");
	$gift = $vbulletin->db->fetch_array($getgift);
}

// Check if Item Can Be Purchased
if ($itembuy[active] != 1) {
    $error = 2; // Item Isn't Active
}
if ($points < $itembuy[amount]-($itembuy[amount]*$discount)) {
    $error = 3; // Not Enough Points
}
// Check if User Exists
if ($affected_user OR $itembuy[type] == 2) {
$findother = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "user where username='".addslashes($affected_user)."'");
$findother = $vbulletin->db->fetch_array($findother);
    if (!$findother) {
        print "$affected_user and $findother[username]";
        $error = 5; // No User Found
    }
}

// Begin Script
if ($error == 0) {
$time = time();
$userid = $vbulletin->userinfo['userid'];
$amount = $itembuy[amount]-($itembuy[amount]*$discount);

// *********** Custom User Title Purchase ************
    if ($itembuy[marketid] == 2 OR $itembuy[marketid] == 5 OR $itembuy[marketid] == 6) {
        $usertitle = $_POST['usertitle'];
        if ($usertitle == "") {
        $error = 4; // Nothing Entered
        }
        // Basic or Advanced Custom Title
        if ($itembuy[marketid] == 5) {
        $customtitle = 1;
        } else { $customtitle = 2; }

        if ($error == 0) {
        // User Edits Own User Title
        if ($itembuy[marketid] != 6) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, customtitle='$customtitle', usertitle ='".addslashes($usertitle)."' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount', usertitle_type='$customtitle', usertitle ='".addslashes($usertitle)."'");
        } else {
        // User Edits Someone Else's Usertitle
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set customtitle='$customtitle', usertitle ='".addslashes($usertitle)."' where userid='$findother[userid]'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$findother[userid]', amount='$amount', usertitle_type='$customtitle', usertitle ='".addslashes($usertitle)."'");
        }
        $points = $points - $amount;
        $purchases = $purchases+1;
        }
    }
// *********** Steal Member's Amounts ************
    if ($itembuy[marketid] == 7 OR $itembuy[marketid] == 8 OR $itembuy[marketid] == 9) {
            // Calculate User's Points
            $stolen = $findother[$pointfield];
            if ($findother['market_steal_protect'] == 1) {
                $error = 14; // User is protected
            }
            // Calculate Percentage Stealing
            if ($itembuy[marketid] == 7) {
            $stealamount = $vbulletin->options['market_steal_1']*.01;
            }
            else if ($itembuy[marketid] == 8) {
            $stealamount = $vbulletin->options['market_steal_2']*.01;
            }
            else if ($itembuy[marketid] == 9) {
            $stealamount = $vbulletin->options['market_steal_3']*.01;
            }
            // Calculate Amount Stolen
            $stolen = round($stolen*$stealamount,2);
            if ($stolen <= 0) {
                $error = 6; // User has no points to steal
            }

        if ($error == 0) {
        $rand = rand(0,100);
        if ($permissions['market_steal_percent'] < $rand) {
        $stolen = 0;
        $error = 21;
        }

            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$pointfield-$stolen where userid='$findother[userid]'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount+$stolen, market_purchases=market_purchases+1 where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$findother[userid]', amount='$amount', stolenamount='$stolen'");
        $points = $points - $amount;
        $points = $points + $stolen;
        $purchases = $purchases+1;
    }
}
// *********** Change User Title Color *************
    if ($itembuy[marketid] == 3) {
        if (!$color) {
            $error = 9; // User did not select a color.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_ct_color='$color' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
// *********** Change User Title Glow *************
    if ($itembuy[marketid] == 11) {
        if (!$color) {
            $error = 9; // User did not select a color.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_ct_glow='$color' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
// *********** Donate to Another Member ************
    if ($itembuy[marketid] == 10) {

        if ($points < $donate_amount OR ($donate_amount+$amount > $points)) {
            $error = 7; // User doesn't have that much to give away.
        }
        if ($donate_amount > $permissions['market_maxdonate'] AND $permissions['market_maxdonate'] != 0)  {
            $error = 8; // User is trying to donate more then usergroup is permitted.
        }
        if ($donate_amount < 0 OR !is_numeric($donate_amount))  {
            $error = 13; // User entered invalid numbers.
        }
        if ($findother[userid] == $userid) {
            $error = 16; // User is trying to donate to self.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$pointfield+$donate_amount where userid='$findother[userid]'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount-$donate_amount, market_purchases=market_purchases+1 where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$findother[userid]', amount='$amount', donatedamount='$donate_amount', reason='".addslashes($reason)."'");
            $points = $points - $amount - $donate_amount;
            $purchases = $purchases+1;
        }
    }
// *********** Change Username ************
    if ($itembuy[marketid] == 13) {
            $findother = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "user where username='".addslashes($new_username)."'");
            $findother = $vbulletin->db->fetch_array($findother);
        if ($findother)  {
            $error = 10; // Someone has that username.
        }
        $minchar = $vbulletin->options[minuserlength];
        $maxchar = $vbulletin->options[maxuserlength];
        if ($minchar > strlen($new_username)) {
            $error = 11;
        }
        if ($maxchar < strlen($new_username)) {
            $error = 12;
        }
        if ($error == 0) {
            $oldname = $vbulletin->userinfo['username'];           
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "thread set `postusername`='".addslashes($new_username)."' where postusername='".addslashes($oldname)."'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "thread set `lastposter`='".addslashes($new_username)."' where lastposter='".addslashes($oldname)."'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "pmtext set `fromusername`='".addslashes($new_username)."' where fromusername='".addslashes($oldname)."'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `username`='".addslashes($new_username)."', `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
// *********** Change User Name Color *************
    if ($itembuy[marketid] == 14) {
        if (!$color) {
            $error = 9; // User did not select a color.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_username_color='$color' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
// *********** Change User Name Glow *************
    if ($itembuy[marketid] == 15) {
        if (!$color) {
            $error = 9; // User did not select a color.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_username_glow='$color' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
// ************ Add Steal Protection **************
    if ($itembuy[marketid] == 17) {
        if (!$new_feature or $new_feature != 1) {
            $error = 4; // User did not select the checkbox.
        }
        if ($vbulletin->userinfo['market_steal_protect'] == 1) {
            $error = 15; // User has already purchased this item.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_steal_protect='$new_feature' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
// *************** Donation History ***************
    if ($itembuy[marketid] == 18) {
        if (!$new_feature or $new_feature != 1) {
            $error = 4; // User did not select the checkbox.
        }
        if ($vbulletin->userinfo['market_donate_history'] == 1) {
            $error = 15; // User has already purchased this item.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_donate_history='$new_feature' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
// *************** Stolen Point History ***************
    if ($itembuy[marketid] == 19) {
        if (!$new_feature or $new_feature != 1) {
            $error = 4; // User did not select the checkbox.
        }
        if ($vbulletin->userinfo['market_steal_history'] == 1) {
            $error = 15; // User has already purchased this item.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_steal_history='$new_feature' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
	// *************** Purchase Gift ***************
	if ($itembuy[marketid] == 21) {
		if (!$gift_customid) {
			$error = 4; // User did not select the checkbox.
		}
        // Add Gift Price Option
            $findg = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_gifts where giftid='".addslashes($gift_customid)."'");
            $findg = $vbulletin->db->fetch_array($findg);
            $amount = $amount+$findg[amount];
        if ($points < $amount) {
            $error = 3; // Gift Money makes it too expensive.
        }
        
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_gifts='1'  where userid='$userid'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', gift_id='$gift_customid', affecteduser='$userid'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}
	// *************** Create Custom Gift ***************
	if ($itembuy[marketid] == 22) {
		if (!$gift_customid or !$gift_amount or !$gift_name or !$gift_description) {
			$error = 4; // User did not select the checkbox.
		}
		if (!is_numeric($gift_amount)) {
			$error = 13;
		}
		if ($gift_amount > $permissions['market_gift_maximum'])  {
			$error = 19; // User's price is set too high
		}
		if ($gift_amount < $permissions['market_gift_minimum'])  {
			$error = 20; // User's price is set too low
		}
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_gifts set `customid`='$gift_customid', active='1', name='$gift_name', description='".addslashes($gift_description)."', icon_small='$gift[icon_small]', icon_big='gift[icon_big]', createdby='$userid', amount='$gift_amount'");
            $getcustomid = $vbulletin->db->query_read("SELECT giftid from " . TABLE_PREFIX . "market_gifts where createdby='$userid' order by giftid DESC limit 0,1");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', gift_customid='$getcustomid'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}
	// *************** Purchase Gift Other ****************
	if ($itembuy[marketid] == 23) {
		if (!$gift_customid or !$reason) {
			$error = 4; // User did not select the checkbox.
		}
        if ($findother[userid] == $userid) {
            $error = 16; // User is trying to give to self.
        }
        // Add Gift Price Option
            $findg = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_gifts where giftid='".addslashes($gift_customid)."'");
            $findg = $vbulletin->db->fetch_array($findg);
            $amount = $amount+$findg[amount];
        if ($points < $amount) {
            $error = 3; // Gift Money makes it too expensive.
        }
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `market_gifts`=1 where userid='$findother[userid]'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', gift_id='$gift_customid', affecteduser='$findother[userid]', reason='".addslashes($reason)."'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}
	// *************** Stolen Point History ***************
	if ($itembuy[marketid] == 24) {
		if (!$new_feature or $new_feature != 1) {
			$error = 4; // User did not select the checkbox.
		}
		if ($vbulletin->userinfo['market_gift_access'] == 1) {
			$error = 15; // User has already purchased this item.
		}
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_gift_access='$new_feature' where userid='$userid'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}
	// *************** Username Subscript ***************
	if ($itembuy[marketid] == 25) {
		if (!$new_feature) {
			$error = 4; // User did not select the checkbox.
		}
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_username_subscript='1'  where userid='$userid'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', gift_id='$gift_customid', affecteduser='$userid'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}
	// *************** Usertitle Subscript ***************
	if ($itembuy[marketid] == 26) {
		if (!$new_feature) {
			$error = 4; // User did not select the checkbox.
		}
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_usertitle_subscript='1'  where userid='$userid'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', gift_id='$gift_customid', affecteduser='$userid'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}

    // *********** Post Font Face *************
    if ($itembuy[marketid] == 28) {
        if (!$face) {
            $error = 22; // User did not select a font face.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_post_fontface='$face' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
    // *********** Post Font Color *************
    if ($itembuy[marketid] == 29) {
        if (!$color) {
            $error = 9; // User did not select a color.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_post_color='$color' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
	// ***************** Post Font Italics ******************
	if ($itembuy[marketid] == 30) {
		if (!$new_feature) {
			$error = 4; // User did not select the checkbox.
		}
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_post_italics='1'  where userid='$userid'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', gift_id='$gift_customid', affecteduser='$userid'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}
	// ***************** Post Font Bold ******************
	if ($itembuy[marketid] == 31) {
		if (!$new_feature) {
			$error = 4; // User did not select the checkbox.
		}
		if ($error == 0) {
			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_post_bold='1', market_purchases=market_purchases+1 where userid='$userid'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', gift_id='$gift_customid', affecteduser='$userid'");
			$points = $points - $amount;
			$purchases = $purchases+1;
		}
	}
// *************** Gambling Access ***************
    if ($itembuy[marketid] == 38) {
        if (!$new_feature or $new_feature != 1) {
            $error = 4; // User did not select the checkbox.
        }
        if ($vbulletin->userinfo['market_gambling'] == 1) {
            $error = 15; // User has already purchased this item.
        }
        if ($error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, market_gambling='$new_feature' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', affecteduser='$userid', amount='$amount'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    }
    // ***************** Forum Access  ******************
    if ($itembuy[marketid] == 40 OR $itembuy[marketid] == 41) {
        if (!$new_feature) {
            $error = 4; // User did not select the checkbox.
        }
        if ($itembuy[marketid] == 40) {
        $forumid = $vbulletin->options['market_forum_1'];     
        } else {
        $forumid = $vbulletin->options['market_forum_2'];   
        }
        
        $findstuff = $vbulletin->db->fetch_array($vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "access where forumid='$forumid' AND userid='$userid'"));
        if ($findstuff[accessmask] != 0) {
        $error = 23;    
        }
        if ($findstuff[userid] > 0 AND $findstuff[accessmask] == 0 AND $error == 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "access set `forumid`='$forumid', userid='$userid', accessmask='1' where userid='$userid' AND forumid='$forumid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', affecteduser='$userid'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }  
        if ($error == 0 AND !$findstuff) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "access set `forumid`='$forumid', userid='$userid', accessmask='1'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', affecteduser='$userid'");
            $points = $points - $amount;
            $purchases = $purchases+1;            
        }
    }
    // ************** Primary Usergroup Access  ***************
    if ($itembuy[marketid] == 42 OR $itembuy[marketid] == 43) {
        if (!$new_feature) {
            $error = 4; // User did not select the checkbox.
        }
        if ($itembuy[marketid] == 42) {
        $usergroup = $vbulletin->options['market_primary_1'];     
        } else {
        $usergroup = $vbulletin->options['market_primary_2'];   
        }
        
        if ($usergroup == $vbulletin->userinfo['usergroupid']) {
        $error = 24;    
        }
        
        if (!$error AND $usergroup > 0) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, usergroupid='$usergroup', displaygroupid='$usergroup' where userid='$userid'");
             $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', affecteduser='$userid'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    
    }
    // ************** Secondary Usergroup Access  ***************
    if ($itembuy[marketid] == 44 OR $itembuy[marketid] == 45) {
        if (!$new_feature) {
            $error = 4; // User did not select the checkbox.
        }
        if ($itembuy[marketid] == 44) {
        $usergroup = $vbulletin->options['market_secondary_1'];     
        } else {
        $usergroup = $vbulletin->options['market_secondary_2'];   
        }
        /*
        if (in_array(',$usergroup,', $vbulletin->userinfo['membergroupids'])) {
        $error = 24;   
        }
        */
        if (!$error AND $usergroup > 0) {
            $secondary = $vbulletin->userinfo['membergroupids'];
            if (!$secondary) {
            $secondary .= "$usergroup";
            } else {
            $secondary .= ",$usergroup";   
            }
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1, membergroupids='$secondary', displaygroupid='$usergroup' where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', affecteduser='$userid'");
            $points = $points - $amount;
            $purchases = $purchases+1;
        }
    
    }
    
    // ************** Start Custom Scripted Items  ***************
    // Test Market Item #1
    if ($itembuy[marketid] == 35) {


            // Update User Info    
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            // Update Transaction Insertion
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', affecteduser='$userid'");
            $points = $points - $amount;
            $purchases = $purchases + 1;      
    }
    // Test Market Item #2
    if ($itembuy[marketid] == 36) {


            // Update User Info    
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            // Update Transaction Insertion
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', affecteduser='$userid'");
            $points = $points - $amount;
            $purchases = $purchases + 1;    
    }
    // Test Market Item #3
    if ($itembuy[marketid] == 37) {
        
        
            // Update User Info    
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$amount, market_purchases=market_purchases+1 where userid='$userid'");
            // Update Transaction Insertion
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='$itembuy[marketid]', userid='$userid', amount='$amount', affecteduser='$userid'");
            $points = $points - $amount;
            $purchases = $purchases + 1;       
    }
    // *********** End Custom Scripted Items  ************
    
    
    
}
} else {
$error = 1; // Error Finding Item
}

$templater = vB_Template::create('market_itembuy_do');
$templater->register('error', $error);
$market_itembuy_do .= $templater->render();

}

// #######################################################################
// ########################### Do Refund #################################
// #######################################################################

if ($_REQUEST['do'] == "giverefund")
{
$error = 0;
if ($_POST) {
$tran_id = $_POST['tranid'];

$get_transaction = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_transactions where tranid ='".addslashes($tran_id)."' AND refund_date=''");
$transaction = $vbulletin->db->fetch_array($get_transaction);

if (!$transaction) {
    $error = 1;
}
if ($transaction[marketid] == 3) {
    $field = "market_ct_color"; // User Title Color
}
else if ($transaction[marketid] == 11) {
    $field = "market_ct_glow"; // User Title Glow
}
else if ($transaction[marketid] == 14) {
    $field = "market_username_color"; // Username Color
}
else if ($transaction[marketid] == 15) {
    $field = "market_username_glow"; // Username Glow
}
else if ($transaction[marketid] == 25) {
    $field = "market_username_subscript"; // Username Strikethrough
}
else if ($transaction[marketid] == 26) {
    $field = "market_usertitle_subscript"; // Usertitle Strikethrough
}
else if ($transaction[marketid] == 28) {
    $field = "market_post_fontface"; // Post Font Face
}
else if ($transaction[marketid] == 29) {
    $field = "market_post_color"; // Post Color
}
else if ($transaction[marketid] == 30) {
    $field = "market_post_italics"; // Post Italics
}
else if ($transaction[marketid] == 31) {
    $field = "market_post_bold"; // Post Bold
}

// Calculate Expiration
$expires = $permissions['market_undo_time']*3600;
$expire_date = $transaction[date]+$expires;
// Calculate Refund Amount
$refund_amount = $transaction[amount]*$permissions['market_undo_penalty'];
// Calculate Remaining Time
$countdown = $expire_date-time();

if ($countdown <= 0) {
    $error = 17; // Time has Expired
}
$date = time();

// ****** Do Update Query ********
if ($error == 0) {
    $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points+$refund_amount, market_refund=market_refund+1, `$field`='' where userid='$userid'");
    $vbulletin->db->query_read("update " . TABLE_PREFIX . "market_transactions set `refund_amount`=$refund_amount, `refund_date`='$date' where tranid='".addslashes($tran_id)."'");
    $error = 18; // Refund Purchased Successfully
}

} else {
$error = 1; // Error Finding Item
}

$templater = vB_Template::create('market_itembuy_do');
$templater->register('error', $error);
$market_itembuy_do .= $templater->render();

}


// #######################################################################
// ########################## Refund Item ################################
// #######################################################################

if ($_REQUEST['do'] == "refund")
{
$itemid = $_REQUEST['id'];
$getitem = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_items where marketid ='".addslashes($itemid)."'");
$itembuy = $vbulletin->db->fetch_array($getitem);

if (!$itembuy OR ($itembuy[type] != 3 AND $itembuy[type] != 9)) {
if ($itembuy[type] == 6 AND ($itembuy[marketid] > 31 OR $itembuy[marketid] < 25)) {
    print_no_permission();
}
}

$fid = $_REQUEST['id'];
$finditem = $vbulletin->db->fetch_array($vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_transactions where marketid ='$itembuy[marketid]' AND userid='$userid' order by date DESC limit 0,1"));

// Calculate Expiration
$expires = $permissions['market_undo_time']*3600;
$expire_date = $finditem[date]+$expires;
// Calculate Refund Amount
$refund_amount = $finditem[amount]*$permissions['market_undo_penalty'];
// Calculate Remaining Time
$countdown = $expire_date-time();

$days_left = floor($countdown/86400);
$hours_left = floor(($countdown - (86400*$days_left))/3600);
$minutes_left = floor(($countdown - (86400*$days_left)-($hours_left*3600))/60);

$templater = vB_Template::create('market_refund');
    $templater->register('expire_date', $expire_date);
    $templater->register('item_date_format', 'F j, Y, g:i a');
    $templater->register('countdown', $countdown);
    $templater->register('name', $itembuy[name]);
    $templater->register('days_left', $days_left);
    $templater->register('hours_left', $hours_left);
    $templater->register('minutes_left', $minutes_left);
    $templater->register('amount', $finditem[amount]);
    $templater->register('refund_amount', $refund_amount);
    $templater->register('purchase_date', $finditem[date]);
    $templater->register('tranid', $finditem[tranid]);
    $templater->register('refund_date', $finditem[refund_date]);
$market_refund .= $templater->render();


}


// #######################################################################
// ##################### Do Custom Gift Purchase #########################
// #######################################################################

if ($_REQUEST['do'] == "giftpurchase")
{
$gift_customid = $_REQUEST['gift_customid'];
$getgift = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_gifts where giftid ='".addslashes($gift_customid)."'");
$gift = $vbulletin->db->fetch_array($getgift);

if (!$gift) {
$error = 4; // User didn't select anything
}
if ($points < $gift[amount]) {
    $error = 3; // Not Enough Points
}
if ($gift[purchasedby]) {
    $error = 2; // Item Isn't Active
}

if ($error == 0) {

			$vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$gift[amount], `market_gifts`=1 where userid='$userid'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$pointfield+$gift[amount]  where userid='$gift[createdby]'");
			$vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_transactions set `date`='$time', marketid='-1', userid='$userid', amount='$amount', gift_customid='$gift_customid', affecteduser='$userid'");
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "market_gifts set purchasedby='$userid'  where giftid='$gift[giftid]'");
            $points = $points - $amount;
			$purchases = $purchases+1;
}

$templater = vB_Template::create('market_itembuy_do');
$templater->register('error', $error);
$market_itembuy_do .= $templater->render();

}

// #######################################################################
// ######################### Steal History ###############################
// #######################################################################

if ($_REQUEST['do'] == "steal_history")
{

    if ($_REQUEST['type'] != "2") {
    $type=1;
    }
    else {
    $type=2;
    }


 // ******************* Actions Done By User *************************
    if ($type == 1) {
    $who = "userid";
    $opp = "affecteduser";
    }
  // **************** Actions Done By Someone Else *******************
    if ($type == 2) {
    $who = "affecteduser";
    $opp = "userid";
    }



    // Check if User Has Purchased Access
    if ($vbulletin->userinfo[market_steal_history] != 1){
        standard_error($vbphrase['market_error_nopurchase']);
    }
    // Pageinating for points user has stolen
    $vbulletin->input->clean_array_gpc('r', array(
    'perpage'    => TYPE_UINT,
    'pagenumber' => TYPE_UINT,
    ));
    $cel_users = $db->query_first("
    SELECT COUNT('tranid') AS users_stolen
    FROM " . TABLE_PREFIX . "market_transactions AS market_transactions
    WHERE `$who` = '$userid' AND (marketid='7' or marketid='8' or marketid='9')
");
    // Sanitize for points user has stolen
    sanitize_pageresults($cel_users['users_stolen'], $pagenumber, $perpage, 100, 20);
    if ($vbulletin->GPC['pagenumber'] < 1)
    {
        $vbulletin->GPC['pagenumber'] = 1;
    }
    else if ($vbulletin->GPC['pagenumber'] > ceil(($cel_users['users_stolen'] + 1) / $perpage))
    {
        $vbulletin->GPC['pagenumber'] = ceil(($cel_users['users_stolen'] + 1) / $perpage);
    }
    $limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
    $limitupper = ($vbulletin->GPC['pagenumber']) * $perpage;
    // Main Query to find who user has stolen from
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions WHERE `$who` = '$userid' AND (marketid='7' or marketid='8' or marketid='9') ORDER BY date DESC LIMIT $limitlower, $perpage");
    while ($shu = $vbulletin->db->fetch_array($result))
    {
    ++$loop_count;
    $other_user = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[$opp]'"));
// Configure Amount
    $amount = $item[amount]-($item[amount]*$discount);
    // Generate Variables to be used
    $templater = vB_Template::create('market_steal_history_bit');
    $templater->register('item_date', $shu[date]);
    $templater->register('item_date_format', 'F j, Y, g:i a');
    $templater->register('item_affected_name', $other_user[username]);
    $templater->register('item_affected_userid', $other_user[userid]);
    $templater->register('item_stolen_amount', $shu[stolenamount]);
    // Register market_itemlist template
    $market_steal_history_bit .= $templater->render();
    }

  $pagenav = construct_page_nav(
    $vbulletin->GPC['pagenumber'],
    $perpage,
    $cel_users['users_stolen'],
    'market.php?do=steal_history' . $vbulletin->session->vars['sessionurl'], // the pagenav-link
    '&type=' . $type, // to pass a second portion or the pagenav-link, gets directly appended to above
    '', // to pass an anchor
    '', // SEO-Link for thread, forum, member... pages - make the pagenav-links seo'ed if you use the paginator on one of those
    '', // Array to pass linkinfo for SEO-Link-Method
    ''  // Array to pass additional Info for SEO-Link-Method
);


$templater = vB_Template::create('market_steal_history');
    $templater->register('market_steal_history_bit', $market_steal_history_bit);
    $templater->register('pagenav', $pagenav);
    $templater->register('pagenumber', $pagenumber);
    $templater->register('type', $type);
    $templater->register('perpage', $perpage);
    $templater->register('loop_count', $loop_count);
    $templater->register('output', $output);
$market_steal_history .= $templater->render();


}


// #######################################################################
// ######################## Donate History ###############################
// #######################################################################

if ($_REQUEST['do'] == "donate_history")
{
    // Check if User Has Purchased Access
    if ($vbulletin->userinfo[market_donate_history] != 1){

        standard_error($vbphrase['market_error_nopurchase']);
    }
    if ($_REQUEST['type'] != "2") {
    $type=1;
    }
    else {
    $type=2;
    }

  // ******************* Actions Done By User *************************
    if ($type == 1) {
    $who = "userid";
    $opp = "affecteduser";
    }
  // **************** Actions Done By Someone Else *******************
    if ($type == 2) {
    $who = "affecteduser";
    $opp = "userid";
    }


    // Pageinating for points user has donated
    $vbulletin->input->clean_array_gpc('r', array(
    'perpage'    => TYPE_UINT,
    'pagenumber' => TYPE_UINT,
    ));
    $cel_users = $db->query_first("
    SELECT COUNT('tranid') AS users_donate
    FROM " . TABLE_PREFIX . "market_transactions AS market_transactions
    WHERE `$who` = '$userid' AND marketid='10'
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
    // Main Query to find who user has stolen from
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions WHERE `$who` = '$userid' AND marketid='10' ORDER BY date DESC LIMIT $limitlower, $perpage");
    while ($shu = $vbulletin->db->fetch_array($result))
    {
    $other_user = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[$opp]'"));
// Configure Amount
    $amount = $item[amount]-($item[amount]*$discount);
    ++$loop_count;
    // Generate Variables to be used
    $templater = vB_Template::create('market_steal_history_bit');
    $templater->register('item_date', $shu[date]);
    $templater->register('item_date_format', 'F j, Y, g:i a');
    $templater->register('item_affected_name', $other_user[username]);
    $templater->register('item_affected_userid', $other_user[userid]);
    $templater->register('item_stolen_amount', $shu[donatedamount]);
    $templater->register('reason', stripslashes($shu[reason]));
    // Register market_itemlist template
    $market_steal_history_bit .= $templater->render();
    }

  $pagenav = construct_page_nav(
    $vbulletin->GPC['pagenumber'],
    $perpage,
    $cel_users['users_donate'],
    'market.php?do=donate_history' . $vbulletin->session->vars['sessionurl'], // the pagenav-link
    '&type=' . $type, // to pass a second portion or the pagenav-link, gets directly appended to above
    '', // to pass an anchor
    '', // SEO-Link for thread, forum, member... pages - make the pagenav-links seo'ed if you use the paginator on one of those
    '', // Array to pass linkinfo for SEO-Link-Method
    ''  // Array to pass additional Info for SEO-Link-Method
);


$templater = vB_Template::create('market_donate_history');
    $templater->register('market_steal_history_bit', $market_steal_history_bit);
    $templater->register('pagenav', $pagenav);
    $templater->register('pagenumber', $pagenumber);
    $templater->register('type', $type);
    $templater->register('perpage', $perpage);
    $templater->register('loop_count', $loop_count);
    $templater->register('output', $output);
$market_donate_history .= $templater->render();


}

// #######################################################################
// ######################## Gambling Access ##############################
// #######################################################################

if ($_REQUEST['do'] == "gamble_access")
{

// No Gambling Selection
    if (!$_REQUEST['type']) {
    $type=1;
    } else {
    $type = $_REQUEST['type'];
    }
// Guess the Number
if ($type == 1) {
 $cost = $vbulletin->options['market_gamble_guess_cost'];
 $payout = $vbulletin->options['market_gamble_guess_payout'];
}
if ($type == 2) {
 $cost = $vbulletin->options['market_gamble_card_cost'];
 $payout = $vbulletin->options['market_gamble_card_payout'];
}

// **************** Do Results ****************
    $vbulletin->input->clean_array_gpc('p', array(
    'purchase'   => TYPE_STR,
    'lottoid'    => TYPE_INT,
    ));
    $lottoid = $vbulletin->GPC['lottoid'];
    
if ($vbulletin->GPC['purchase'] != "") {
    if ($points < $cost) {
    $error = 1;    
    } else {
    $error = -1;    
// **** Guess the Number ****
    if ($type == 1) {
        $urand = rand(1,10);
        $crand = rand(1,10);
        $date = time();
        if ($urand == $crand) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points+$payout-$cost where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_gamble_history set userid='$userid', date='$date', gameid='$type', cost='$cost', payout='$payout'");
            $points = $points+$payout-$cost;
            $youwon = $payout;
        } else {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$cost where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_gamble_history set userid='$userid', date='$date', gameid='$type', cost='$cost', payout='0'");
            $points = $points-$cost;
            $youwon = 0; 
        }    
        }
// **** Higher Card ****
    if ($type == 2) {
    $date = time();  
    $urand = rand(2,14);
    $crand = rand(2,14);
    // Add Computer Advantage By 1
        if ($crand != 14) {
        $lastrand = rand(1,100);
            if ($lastrand > 10) {
            $crand = $crand + 1;
            }    
            }
    // Add Computer Advantage By 2
        if ($crand < 10) {
        $lastrand = rand(1,100);
            if ($lastrand > 25) {
            $crand = $crand + 2;
            }    
            }
    // User Wins
        if ($urand > $crand) {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points+$payout-$cost where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_gamble_history set userid='$userid', date='$date', gameid='$type', cost='$cost', payout='$payout'");
            $points = $points+$payout-$cost;
            $youwon = $payout; 
        } else {
            $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$cost where userid='$userid'");
            $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_gamble_history set userid='$userid', date='$date', gameid='$type', cost='$cost', payout='0'");
            $points = $points-$cost;
            $youwon = 0; 
        }    
    
    }
 
    // Lottery Pick
    if ($type == 3 AND $lottoid < 1) {
        $error = 2;    
    } else if ($type == 3 AND $lottoid > 0) {

        $date = time();
        $lot = $db->fetch_array($db->query_read("SELECT * from ". TABLE_PREFIX . "market_lotto where lottoid='$lottoid'"));
        $vbulletin->db->query_read("insert into " . TABLE_PREFIX . "market_gamble_history set userid='$userid', date='$date', gameid='$type', cost='$lot[cost]', lottoid='$lottoid', status='1'");
        $vbulletin->db->query_read("update " . TABLE_PREFIX . "market_lotto set currentpay=currentpay+$lot[cost] where lottoid='$lottoid'");
        $vbulletin->db->query_read("update " . TABLE_PREFIX . "user set `$pointfield`=$points-$lot[cost] where userid='$userid'");
        $points = $points-$lot[cost];
        $lot[currentpay] = $lot[currentpay]+$lot[cost];
        $ticketid = $db->num_rows($db->query_read("SELECT * from ". TABLE_PREFIX . "market_gamble_history where lottoid='$lottoid'"));
    }
    }
    
}

// Bit Template
if ($type == 3) {
    $time = time();
     $find_lotto = $db->query_read("SELECT * from  " . TABLE_PREFIX . "market_lotto where winner='0' and dateend > $time");
     while ($lotto = $db->fetch_array($find_lotto)) {
    $name = "".stripslashes($lotto[name])."";
    $templater = vB_Template::create('market_gambling_bit');
    $templater->register('payout', $lotto[currentpay]);
    $templater->register('market_lottoid', $lotto[lottoid]); 
    $templater->register('market_lotto_name', $name);
    $templater->register('cost', $lotto[cost]);
    $templater->register('type', 3);
    $templater->register('expire_date', $lotto[dateend]); 
    $templater->register('item_date_format', 'F j, Y, g:i a'); 
    // Register market_itemlist template
    $market_gambling_bit .= $templater->render();  
     }
} else {
    $templater = vB_Template::create('market_gambling_bit');
    $templater->register('payout', $payout);
    $templater->register('youwon', $youwon);
    $templater->register('cost', $cost);
    $templater->register('type', $type);
    $templater->register('expire_date', $type); 
    $templater->register('item_date_format', 'F j, Y, g:i a'); 
    // Register market_itemlist template
    $market_gambling_bit .= $templater->render();
}

//Main Template
$templater = vB_Template::create('market_gambling');
$templater->register('market_gambling_bit', $market_gambling_bit);
$templater->register('type', $type);
    $templater->register('payout', $payout);
    $templater->register('youwon', $youwon);
    $templater->register('cost', $cost);
    $templater->register('lotcost', $lot[cost]);
    $templater->register('lotpay', $lot[currentpay]);
    $templater->register('ticketid', $ticketid);
    $templater->register('type', $type);
    $templater->register('error', $error); 
    $templater->register('urand', $urand);
    $templater->register('crand', $crand);
$market_gift_access .= $templater->render();    
    
}

// #######################################################################
// ########################## Gift Access ################################
// #######################################################################

if ($_REQUEST['do'] == "gift_access")
{

    if ($_REQUEST['type'] != "1") {
    $type=2;
    }
    else {
    $type=1;
    }


 // ************ Actions Done By Someone Else **************
    if ($type == 2) {
    $query = "createdby > 0 AND createdby != '$userid' AND purchasedby='0'";
    $createdby = "createdby";

    }
  // **************** Actions Done By Me *******************
    if ($type == 1) {
    $query = "createdby = '$userid'";
    $createdby = "purchasedby";
    }

    // Check if User Has Purchased Access
    if ($vbulletin->userinfo[market_gift_access] != 1){
        standard_error($vbphrase['market_error_nopurchase']);
    }
    // Pageinating for points user has stolen
    $vbulletin->input->clean_array_gpc('r', array(
    'perpage'    => TYPE_UINT,
    'pagenumber' => TYPE_UINT,
    ));
    $cel_users = $db->query_first("
    SELECT COUNT('giftid') AS gift_count
    FROM " . TABLE_PREFIX . "market_gifts AS market_gifts
    WHERE $query
");
    // Sanitize for points user has stolen
    sanitize_pageresults($cel_users['giftid'], $pagenumber, $perpage, 100, 20);
    if ($vbulletin->GPC['pagenumber'] < 1)
    {
        $vbulletin->GPC['pagenumber'] = 1;
    }
    else if ($vbulletin->GPC['pagenumber'] > ceil(($cel_users['giftid'] + 1) / $perpage))
    {
        $vbulletin->GPC['pagenumber'] = ceil(($cel_users['giftid'] + 1) / $perpage);
    }
    $limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
    $limitupper = ($vbulletin->GPC['pagenumber']) * $perpage;
    // Main Query to find who user has stolen from
    $result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_gifts AS market_gifts
    WHERE $query order by giftid DESC LIMIT $limitlower, $perpage");
    while ($shu = $vbulletin->db->fetch_array($result))
    {
    ++$loop_count;
    // Find User
    $other_user = $db->fetch_array($db->query_read("SELECT userid,username FROM " . TABLE_PREFIX . "user where userid='$shu[$createdby]'"));
    // Configure Amount
    $amount = $item[amount]-($item[amount]*$discount);
    // Generate Variables to be used
    $templater = vB_Template::create('market_gift_access_bit');
    $templater->register('gift_name', stripslashes($shu[name]));
    $templater->register('gift_customid', $shu[giftid]);
    $templater->register('gift_description', stripslashes($shu[description]));
    $templater->register('gift_icon', $shu[icon_small]);
    $templater->register('gift_purchasedby', $shu[purchasedby]);
    $templater->register('type', $type);
    $templater->register('gift_userid', $other_user[userid]);
    $templater->register('gift_username', $other_user[username]);
    $templater->register('gift_amount', $shu[amount]);
    // Register market_itemlist template
    $market_gift_access_bit .= $templater->render();
    }

  $pagenav = construct_page_nav(
    $vbulletin->GPC['pagenumber'],
    $perpage,
    $cel_users['giftid'],
    '&type=' . $type, // to pass a second portion or the pagenav-link, gets directly appended to above 
    '', // to pass a second portion or the pagenav-link, gets directly appended to above
    '', // to pass an anchor
    '', // SEO-Link for thread, forum, member... pages - make the pagenav-links seo'ed if you use the paginator on one of those
    '', // Array to pass linkinfo for SEO-Link-Method
    ''  // Array to pass additional Info for SEO-Link-Method
);


$templater = vB_Template::create('market_gift_access');
    $templater->register('market_gift_access_bit', $market_gift_access_bit);
    $templater->register('pagenav', $pagenav);
    $templater->register('pagenumber', $pagenumber);
    $templater->register('type', $type);
    $templater->register('perpage', $perpage);
    $templater->register('loop_count', $loop_count);
    $templater->register('output', $output);
$market_gift_access .= $templater->render();


}




// #######################################################################
// ######################## SIDEBAR SCRIPT ###############################
// #######################################################################

$results = $vbulletin->db->query_read("SELECT * from " . TABLE_PREFIX . "market_items where iscategory='1' AND active='1' ORDER BY `order` ASC");
    if (!$_REQUEST['do']) {
    $id = 0;
    }
while ($row = $vbulletin->db->fetch_array($results))
{
    $templater = vB_Template::create('market_sidebar');
    $templater->register('marketid', $row[marketid]);
    $templater->register('id', $id);
    $templater->register('name', $row[name]);
    $templater->register('parentid', $row[paretid]);
    $templater->register('iscategory', $row[iscategory]);
    $templater->register('type', $row[type]);
    $market_sidebar .= $templater->render();

}


// #######################################################################
// ###################### Point Market Information #######################
// #######################################################################
$market_purchases = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions"));
$market_refunds = $db->num_rows($db->query_read("SELECT * FROM " . TABLE_PREFIX . "market_transactions where refund_date != '0'"));
$market_version = "<a href='http://www.vbulletin.org/forum/showthread.php?t=232676'>2.0.1</a>";




// ###### DISPLAY MAIN TEMPLATES ######
$templater = vB_Template::create('MARKET');
	/* register variables */
// ###### PAGE VARIABLES ######
    $templater->register('pointfield', $pointfield);
    $templater->register('points', $points);
    $templater->register('points_name', $points_name);
    $templater->register('point_decimal', $point_decimal);
    $templater->register('purchases', $purchases);
    $templater->register('refunds', $refunds);
    $templater->register('navbar', $navbar);
    $templater->register('market_purchases', $market_purchases);
    $templater->register('market_refunds', $market_refunds);
    $templater->register('market_version', $market_version);
    $templater->register('discount_percentage', $discount_percentage);
    $templater->register('market_side_donate_history', $vbulletin->userinfo[market_donate_history]);
    $templater->register('market_side_steal_history', $vbulletin->userinfo[market_steal_history]);
    $templater->register('market_side_gift_access', $vbulletin->userinfo[market_gift_access]);
    $templater->register('market_side_gamble_access', $vbulletin->userinfo[market_gambling]);
	$templater->render();

$templater->register_page_templates();

$templater->register('market_sidebar', $market_sidebar);
$templater->register('market_category', $market_category);
$templater->register('market_itemlist', $market_itemlist);
$templater->register('market_itembuy', $market_itembuy);
$templater->register('market_home', $market_home);
$templater->register('market_refund', $market_refund);
$templater->register('market_itembuy_do', $market_itembuy_do);
$templater->register('market_donate_history', $market_donate_history);
$templater->register('market_steal_history', $market_steal_history);
$templater->register('market_gift_access', $market_gift_access);
$templater->register('market_gambling', $market_gambling);
$templater->register('navbar', $navbar);
$templater->register('pagetitle', $pagetitle);
print_output($templater->render());



?>