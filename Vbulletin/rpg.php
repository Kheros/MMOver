<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 4.0.0                                                  # ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2009 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'rpg');
define('CSRF_PROTECTION', true);
define('CSRF_SKIP_LIST', '');
define('VB_ENTRY', 'rpg.php');
// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('user');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array('RPG');

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_user.php');
require_once("RPG/function_setting.php");
include("RPG/language/language.php");
$setting4 = setting(4);
$setting5 = setting(5);
$setting6 = setting(6);
date_default_timezone_set($setting4);
	if ($vbulletin->userinfo['userid'] == 0)
	{
eval('standard_error($rpgpetlang[rpg34]);');			
	}
 	$query = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "rpg_pet");   
    $checkinstall = $vbulletin->db->fetch_array($query);
      if(!$checkinstall['id'])
      {
    eval('standard_error($rpgpetlang[rpg34b]);');	    
      }

	$query = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "rpg_setting WHERE id=1");
	$setting = $db->fetch_array($query);
	if($setting['setting']==0)
	{
		$query = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "rpg_setting WHERE id=2");
    	$setting = $db->fetch_array($query);
	eval('standard_error($setting[setting]);');
    }
	$userid = $vbulletin->userinfo[userid];
   	$query = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "user WHERE userid =  '$userid'");   
     $userinfo = $vbulletin->db->fetch_array($query);
   $userid = $vbulletin->userinfo[userid]; 
$check = "SELECT * FROM " . TABLE_PREFIX . "rpg_user WHERE userid LIKE  '$userid'";   
$check = $db->query_read($check);
    if (!(list($userid) = $db->fetch_array($check)))
    {
  $pathfile = substr($_SERVER["REQUEST_URI"],strrpos($_SERVER["REQUEST_URI"],"/")+1);
 
 if($pathfile !="rpg.php" && $pathfile !="rpg.php?do=create" && $pathfile !="rpg.php?do=pick")
  {
 header('Location: rpg.php');
 break;
   }
    }   
    $userid = $vbulletin->userinfo[userid]; 
$getrpg = $_GET['do'];
 if ($getrpg=='create')
  {
    require_once("RPG/function_userpet.php");
    $userid = $vbulletin->userinfo['userid'];
    $userpetid = getinfopet($userid);
    if($userpetid['id'])
      {
      eval('standard_error($rpgpetlang[rpg41n]);');	
      }
        $result = "SELECT * FROM " . TABLE_PREFIX . "rpg_pet WHERE class=1"; 
    $gettotalclass1 = $vbulletin->db->num_rows($vbulletin->db->query_read($result));
    
        $result = "SELECT * FROM " . TABLE_PREFIX . "rpg_pet WHERE class=2"; 
    $gettotalclass2 = $vbulletin->db->num_rows($vbulletin->db->query_read($result)); 
    
        $result = "SELECT * FROM " . TABLE_PREFIX . "rpg_pet WHERE class=3"; 
    $gettotalclass3 = $vbulletin->db->num_rows($vbulletin->db->query_read($result)); 
    
        $result = "SELECT * FROM " . TABLE_PREFIX . "rpg_pet WHERE class=4"; 
    $gettotalclass4 = $vbulletin->db->num_rows($vbulletin->db->query_read($result)); 
    
        $result = "SELECT * FROM " . TABLE_PREFIX . "rpg_pet WHERE class=5"; 
    $gettotalclass5 = $vbulletin->db->num_rows($vbulletin->db->query_read($result));
      
   	$templater = vB_Template::create('rpgpet_create');
    $templater->register('setting5', $setting5);
    $templater->register('gettotalclass1', $gettotalclass1);
    $templater->register('gettotalclass2', $gettotalclass2);
    $templater->register('gettotalclass3', $gettotalclass3);
    $templater->register('gettotalclass4', $gettotalclass4);
    $templater->register('gettotalclass5', $gettotalclass5);
    $templater->register('rpgpetlang', $rpgpetlang);
	$rpgcreate = $templater->render();	
  }
  elseif ($getrpg=='mypet')
  {
  	$showmypet ='1';
  require_once('./RPG/mypet.php');
  }
   elseif ($getrpg=='editpet')
  {
  require_once('./RPG/editpet.php');
  }
   elseif ($getrpg=='train')
  {
  require_once('./RPG/train.php');
  }
   elseif ($getrpg=='buy')
  {
  require_once('./RPG/buy.php');
   }
   elseif ($getrpg=='equip')
  {
  require_once('./RPG/equip.php');
  }
    elseif ($getrpg=='sell')
  {
  require_once('./RPG/sell.php');
  }
    elseif ($getrpg=='battle')
  {
  require_once('./RPG/battle.php');
  }
  elseif ($getrpg=='battlepet')
  {
  require_once('./RPG/battlepet.php');
  }
  elseif ($getrpg=='boss')
  {
  require_once('./RPG/boss.php');
  }
  elseif ($getrpg=='battlemonster')
  {
  require_once('./RPG/infobattlemonster.php');
  }
   elseif ($getrpg=='battleboss')
  {
  require_once('./RPG/infobattleboss.php');
  }
   elseif ($getrpg=='battleversussolo')
  {
  require_once('./RPG/versus/infoversussolo.php');
  }
  elseif ($getrpg=='battleversusfreeteam')
  {
  require_once('./RPG/versus/infoversusfreeteam.php');
  }
    elseif ($getrpg=='versus')
  {
  require_once('./RPG/versus.php');
  }
     elseif ($getrpg=='rank')
  {
  require_once('./RPG/rank/rank.php');
    }
     elseif ($getrpg=='clan')
  {
  require_once('./RPG/clan/clan.php');
    }
     elseif ($getrpg=='pet')
  {
  require_once('./RPG/pet.php');
   }
  elseif ($getrpg=='request')
  {
  require_once('./RPG/request.php');
  }
  elseif ($getrpg=='pick')
  {
  	$check = "SELECT * FROM " . TABLE_PREFIX . "rpg_user WHERE userid LIKE  '$userid'";   
     $check = $vbulletin->db->query_read($check);
    if (!(list($userid) = $vbulletin->db->fetch_array($check)))
    {
  	$petid = $_POST['petidset'];
     	$vbulletin->input->clean_array_gpc('p', array(
		'namepet'        => TYPE_NOHTML,
	    'titlepet'        => TYPE_NOHTML
	));
  	$petname= substr($db->escape_string($_POST['namepet']),0,general(1));
  	$pettitle= substr($db->escape_string($_POST['titlepet']),0,general(2));  
    if($petname=="" || $petid==0 || $petid=="" )
     {
        eval('standard_error($rpgpetlang[rpg41n]);');	
     }
$getpets = "SELECT * FROM " . TABLE_PREFIX . "rpg_pet WHERE id ='$petid'";
$getpets = $db->query_read($getpets);
$getpet= $db->fetch_array($getpets);
 	$db->query_write("INSERT INTO
			" . TABLE_PREFIX . "rpg_user
				(`userid`,
				`pet`)
			VALUES
				('" . addslashes($vbulletin->userinfo[userid]) . "',
				'" . addslashes($vbulletin->userinfo[userid]) . "')
		");
  $userid = $vbulletin->userinfo['userid'];
   $checkitemrows = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "rpg_itemuser WHERE userid ='$userid'");
   $checkitemrow= $db->fetch_array($checkitemrows);    
   if(!$checkitemrow) 
   {       
	$db->query_write("INSERT INTO
			" . TABLE_PREFIX . "rpg_itemuser
				(`userid`		)
			VALUES
				('" . addslashes($vbulletin->userinfo[userid]) . "')
		");	
   }
		$db->query_write("INSERT INTO
			" . TABLE_PREFIX . "rpg_petuser
				(`petid`,
				`name`,
				`title`,
				`level`,
				`hp`,
				`hpdef`,
				`exp`,
				`expnext`,
				`att`,
				`def`,
                `cri`,
				`luck`,
				`class`,
				`image`
				)
			VALUES
				('" . addslashes($vbulletin->userinfo[userid]) . "',
				'$petname',
				'$pettitle',
				'$getpet[level]',
				'$getpet[hp]',
				'$getpet[hp]',
				 '0',
                '$getpet[expnext]',
				'$getpet[att]',
				'$getpet[def]',
                '$getpet[cri]',
				'$getpet[luck]',
				'$getpet[class]',
				'$getpet[image]')
		");	
  }
  header('Location: rpg.php?do=mypet');
  }
  else
  {$userid = $vbulletin->userinfo[userid]; 
$check = "SELECT * FROM " . TABLE_PREFIX . "rpg_user WHERE userid LIKE  '$userid'";   
$check = $db->query_read($check);
    if (!(list($userid) = $db->fetch_array($check)))
    {
    $started =0	;
    $query = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "rpg_broadcast WHERE id=1");
    $broadcast = $db->fetch_array($query);
    $broadcast = $broadcast['text'];
    }
    else{  $started =1;	
	$query = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "rpg_broadcast WHERE id=2");
    $broadcast = $db->fetch_array($query);
    $broadcast = $broadcast['text'];}
    
 	$templater = vB_Template::create('rpgpet_started');
    $templater->register('broadcast', $broadcast);
    $templater->register('setting5', $setting5);
	$rpgstart = $templater->render();		
  }

$userid = $vbulletin->userinfo[userid];
$check = "SELECT * FROM " . TABLE_PREFIX . "rpg_user WHERE userid LIKE  '$userid'";   
 $check = $vbulletin->db->query_read($check);
if (!(list($userid) = $vbulletin->db->fetch_array($check)))
  {
  $createdone =1;
  }


$navbits = construct_navbits(array('' => 'RPG PET'));
$navbar = render_navbar_template($navbits);

$templater = vB_Template::create('rpgpet_board');
$templater->register('rpgstart', $rpgstart);
$templater->register('rpgcreate', $rpgcreate);
$templater->register('rpgmypet', $rpgmypet);
$templater->register('rpgeditpet', $rpgeditpet);
$templater->register('rpgbattlepet', $rpgbattlepet);
$templater->register('rpgtrain', $rpgtrain);
$templater->register('rpgbattlemonter', $rpgbattlemonter);
$templater->register('rpginfobattlemonter', $rpginfobattlemonter);
$templater->register('rpgboss', $rpgboss);
$templater->register('rpgbattleboss', $rpgbattleboss);
$templater->register('rpginfobattleboss', $rpginfobattleboss);
$templater->register('rpgversus', $rpgversus);
$templater->register('rpgequip', $rpgequip);
$templater->register('rpgbuy', $rpgbuy);
$templater->register('rpgsell', $rpgsell);
$templater->register('rpgrequest', $rpgrequest);
$HTML = $templater->render();


$templater = vB_Template::create('rpgpet_rpg');
$templater->register_page_templates();
$templater->register('navbar', $navbar);
$templater->register('HTML', $HTML);
$templater->register('createdone', $createdone);
$templater->register('setting5', $setting5);
$templater->register('setting6', $setting6);
$templater->register('rpgpetlang', $rpgpetlang);
print_output($templater->render());





/*======================================================================*\
|| ####################################################################
|| # CVS: $RCSfile$ - $Revision: 29207 $
|| ####################################################################
\*======================================================================*/
?>
