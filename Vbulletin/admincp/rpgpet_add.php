<?php
error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);
 
// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('style');
$specialtemplates = array('products');
 
// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_template.php');
require_once(DIR . '/RPG/language/language.php');
if (!can_administer('canadminlanguages'))
{
	print_cp_no_permission();
}
if ($_GET['add']=="pet")
  {
 
 if($_GET['submit']=="DONE")
{
    $petname = $_GET['petname'];
    $petimg = $_GET['petimg'];
    $petdes = $db->escape_string($_GET['petdes']);
    $petclass = $_GET['petclass'];
    $petlevel = $_GET['petlevel'];
    $pethp = $_GET['pethp'];
    $petexpnext = $_GET['petexpnext'];
    $petatt = $_GET['petatt'];
    $petdef = $_GET['petdef'];
    $petcri = $_GET['petcri'];
    $petluck = $_GET['petluck'];
    
$vbulletin->db->query_write("INSERT INTO
			" . TABLE_PREFIX . "rpg_pet
				(`image`,
				`name`,
				`mieuta`,
				`class`,
				`level`,
				`hp`,
				`expnext`,
				`att`,
				`def`,
				`cri`,
				`luck`)
			VALUES
				('$petimg',
				'$petname',
				'$petdes',
				'$petclass',
				'$petlevel',
				'$pethp',
				'$petexpnext',
				'$petatt',
				'$petdef',
				'$petcri',
				'$petluck')
		");  


print_cp_message('<b><center>DONE<br> <br>REFRESHING ...</center></b>', 'rpgpet_pet.php',2);  
}
    print_cp_header("RPG ADD PET");
	echo "<form name='settings' action='rpgpet_add.php?add=pet' method='GET'>";
	print_table_start();
	print_table_header("ADD NEW PET");
	
print_input_row('Name', 'petname');  
print_input_row('Image','petimg','',0,60);
print_textarea_row('Description', 'petdes');  
print_select_row('Class', 'petclass', array(
 '1' => $rpgpetlang['rpg1'], '2' => $rpgpetlang['rpg2'], '3' => $rpgpetlang['rpg3'], '4' => $rpgpetlang['rpg4'], '5' => $rpgpetlang['rpg5']));  
print_input_row('Level', 'petlevel','',0,5);  
print_input_row('HP', 'pethp','',0,5);  
print_input_row('EXP Next', 'petexpnext','',0,5);  
print_input_row('Attack', 'petatt','',0,5);  
print_input_row('Defense', 'petdef','',0,5);  
print_input_row('Critical', 'petcri','',0,5);  
print_input_row('Luck', 'petluck','',0,5);  

	print_table_header("<input type='hidden' name='add' value='pet'>");	 
		print_table_header("<input type='submit' name='submit' value='DONE'>");
	echo "</form>";
   print_table_footer();  
	print_cp_footer();
  }
  
  elseif ($_GET['add']=="monster")
  {
  if($_GET['submit']=="DONE")
{
    $monstername = $_GET['monstername'];
    $monsterimg = $_GET['monsterimg'];
    $monsterdes = $db->escape_string($_GET['monsterdes']);
    $monsterclass = $_GET['monsterclass'];
    $monsterlevel = $_GET['monsterlevel'];
    $monsterhp = $_GET['monsterhp'];
    $monsteratt = $_GET['monsteratt'];
    $monsterdef = $_GET['monsterdef'];
    $monstercri = $_GET['monstercri'];
    $monsterluck = $_GET['monsterluck'];
    $monstergp = $_GET['monstergp'];
    $monsterdropgp = $_GET['monsterdropgp'];
    $monsterexp = $_GET['monsterexp'];
    $monstertime = $_GET['monstertime'];
    $monsteractive = $_GET['monsteractive'];
    
$vbulletin->db->query_write("INSERT INTO
			" . TABLE_PREFIX . "rpg_monster
				(`image`,
				`name`,
				`mieuta`,
				`class`,
				`level`,
				`hp`,
				`att`,
				`def`,
				`cri`,
				`luck`,
				`gp`,
				`dropgp`,
				`exp`,
				`time`,
				`active`)
			VALUES
				('$monsterimg',
				'$monstername',
			\"$monsterdes\",
				'$monsterclass',
				'$monsterlevel',
				'$monsterhp',
				'$monsteratt',
				'$monsterdef',
				'$monstercri',
				'$monsterluck',
				'$monstergp',
				'$monsterdropgp',
				'$monsterexp',
				'$monstertime',
				'$monsteractive')
		");  


print_cp_message('<b><center>DONE<br> <br>REFRESHING ...</center></b>', 'rpgpet_monster.php',2);  
}
    print_cp_header("RPG ADD MONSTER");
	echo "<form name='settings' action='rpgpet_add.php?add=monster' method='GET'>";
	print_table_start();
	print_table_header("ADD NEW MONSTER");
	
print_input_row('Name', 'monstername');  
print_input_row('Image', 'monsterimg','',0,60);
print_textarea_row('Description', 'monsterdes');  
print_select_row('Class', 'monsterclass', array(
 '1' => $rpgpetlang['rpg1'], '2' => $rpgpetlang['rpg2'], '3' => $rpgpetlang['rpg3'], '4' => $rpgpetlang['rpg4'], '5' => $rpgpetlang['rpg5'])); 
print_input_row('Level', 'monsterlevel',0,0,5);  
print_input_row('HP', 'monsterhp',0,0,5);  
print_input_row('Attack', 'monsteratt',0,0,5);  
print_input_row('Defense', 'monsterdef',0,0,5);  
print_input_row('Critical', 'monstercri',0,0,5);  
print_input_row('Luck', 'monsterluck',0,0,5);  
print_input_row($rpgpetlang[rpggold], 'monstergp',0,0,5);  
print_input_row('Drop '.$rpgpetlang[rpggold], 'monsterdropgp','0,0',0,0,10);  
print_input_row('EXP', 'monsterexp',0,0,5);  
print_input_row('Time', 'monstertime',0,0,5);  
print_yes_no_row('Active', 'monsteractive');  

	print_table_header("<input type='hidden' name='add' value='monster'>");	 
		print_table_header("<input type='submit' name='submit' value='DONE'>");
	echo "</form>";
   print_table_footer();  
	print_cp_footer();
  }
  
   elseif ($_GET['add']=="boss")
  {
  if($_GET['submit']=="DONE")
{
   $bossname = $_GET['bossname'];
    $bossimg = $_GET['bossimg'];
    $bossdes = $db->escape_string($_GET['bossdes']);
    $bossclass = $_GET['bossclass'];
    $bosslevel = $_GET['bosslevel'];
    $bosshp = $_GET['bosshp'];
    $bossatt = $_GET['bossatt'];
    $bossdef = $_GET['bossdef'];
    $bosscri = $_GET['bosscri'];
    $bossluck = $_GET['bossluck'];
    $bossgp = $_GET['bossgp'];
    $bossdropgp = $_GET['bossdropgp'];
    $bossexp = $_GET['bossexp'];
    $bosstime = $_GET['bosstime'];
    $bossskill = $_GET['bossskill'];
    $bossplayers = $_GET['bossplayers'];
    $bossminlevel = $_GET['bossminlevel'];
    $bossitem = $_GET['bossitem'];
    $bosstime = $_GET['bosstime'];
    $bossactive = $_GET['bossactive'];
    
$vbulletin->db->query_write("INSERT INTO
			" . TABLE_PREFIX . "rpg_boss
				(`image`,
				`name`,
				`mieuta`,
				`class`,
				`level`,
				`hp`,
				`att`,
				`def`,
				`cri`,
				`luck`,
				`gp`,
				`dropgp`,
				`exp`,
				`time`,
				`skill`,
				`players`,
				`minlevel`,
				`item`,
				`active`)
			VALUES
				('$bossimg',
				'$bossname',
				\"$bossdes\",
				'$bossclass',
				'$bosslevel',
				'$bosshp',
				'$bossatt',
				'$bossdef',
				'$bosscri',
				'$bossluck',
				'$bossgp',
				'$bossdropgp',
				'$bossexp',
				'$bosstime',
				'$bossskill',
				'$bossplayers',
				'$bossminlevel',
				'$bossitem',
				'$bossactive')
		");  


print_cp_message('<b><center>DONE<br> <br>REFRESHING ...</center></b>', 'rpgpet_boss.php',2);  
}
    print_cp_header("RPG ADD BOSS");
	echo "<form name='settings' action='rpgpet_add.php?add=boss' method='GET'>";
	print_table_start();
	print_table_header("ADD NEW BOSS");
	
print_input_row('Name', 'bossname');  
print_input_row('Image', 'bossimg','',0,60);
print_textarea_row('Description', 'bossdes','',7,60);  
print_select_row('Class', 'bossclass', array(
 '1' => $rpgpetlang['rpg1'], '2' => $rpgpetlang['rpg2'], '3' => $rpgpetlang['rpg3'], '4' => $rpgpetlang['rpg4'], '5' => $rpgpetlang['rpg5'])); 
print_input_row('Level', 'bosslevel',0,0,5);  
print_input_row('HP', 'bosshp',0,0,5);  
print_input_row('Attack', 'bossatt',0,0,5);  
print_input_row('Defense', 'bossdef',0,0,5);  
print_input_row('Critical', 'bosscri',0,0,5);  
print_input_row('Luck', 'bossluck',0,0,5);  
print_select_row('Skill', 'bossskill',array()); 
print_input_row('Players', 'bossplayers','0,0',0,5);  
print_input_row('Min Level', 'bossminlevel',0,0,5);   
print_input_row('Time', 'bosstime',0,0,5);   
print_input_row($rpgpetlang[rpggold], 'bossgp',0,0,5);  
print_input_row('Drop '. $rpgpetlang[rpggold], 'bossdropgp','0,0',0,0,15);  
print_input_row('EXP', 'bossexp',0,0,5);  
print_input_row('Item', 'bossitem',0,0,5);  
print_yes_no_row('Active', 'bossactive'); 

	print_table_header("<input type='hidden' name='add' value='boss'>");	 
		print_table_header("<input type='submit' name='submit' value='DONE'>");
	echo "</form>";
   print_table_footer();  
	print_cp_footer();
  } 
  
   elseif ($_GET['add']=="item")
  {
  if($_GET['submit']=="DONE")
{
    $itemname = $_GET['itemname'];
    $itemimg = $_GET['itemimg'];
    $itemdes = $db->escape_string($_GET['itemdes']);
    $itemclass = $_GET['itemclass'];
    $itemlevel = $_GET['itemlevel'];
    $itemhp = $_GET['itemhp'];
    $itematt = $_GET['itematt'];
    $itemdef = $_GET['itemdef'];
    $itemcri = $_GET['itemcri'];
    $itemluck = $_GET['itemluck'];
    $itemgp = $_GET['itemgp'];
    $itemspecial = $_GET['itemspecial'];
    $itemtype = $_GET['itemtype'];
    $itemshop = $_GET['itemshop'];
    
$vbulletin->db->query_write("INSERT INTO
			" . TABLE_PREFIX . "rpg_item
				(`image`,
				`name`,
				`mieuta`,
				`class`,
				`level`,
				`hp`,
				`att`,
				`def`,
				`cri`,
				`luck`,
				`gold`,
				`special`,
				`type`,
				`active`)
			VALUES
				('$itemimg',
				'$itemname',
				\"$itemdes\",
				'$itemclass',
				'$itemlevel',
				'$itemhp',
				'$itematt',
				'$itemdef',
				'$itemcri',
				'$itemluck',
				'$itemgp',
				'$itemspecial',
				'$itemtype',
				'$itemshop')
		");  


print_cp_message('<b><center>DONE<br> <br>REFRESHING ...</center></b>', 'rpgpet_item.php',2);  
}
    print_cp_header("RPG ADD ITEM");
	echo "<form name='settings' action='rpgpet_add.php?add=item' method='GET'>";
	print_table_start();
	print_table_header("ADD NEW item");
	
print_input_row('Name', 'itemname');  
print_input_row('Image', 'itemimg','',0,60);
print_textarea_row('Description', 'itemdes','',3,60);  
print_select_row('Class', 'itemclass', array(
 '0' => 'NOT', '1' => $rpgpetlang['rpg1'], '2' => $rpgpetlang['rpg2'], '3' => $rpgpetlang['rpg3'], '4' => $rpgpetlang['rpg4'], '5' => $rpgpetlang['rpg5'])); 
print_input_row('Level', 'itemlevel',0,0,5);  
print_input_row('HP', 'itemhp',0,0,5);  
print_input_row('Attack', 'itematt',0,0,5);  
print_input_row('Defense', 'itemdef',0,0,5);  
print_input_row('Critical', 'itemcri',0,0,5);  
print_input_row('Luck', 'itemluck',0,0,5);  
print_input_row('Special', 'itemspecial','type:0;hp:0',0,15);  
print_select_row('Type', 'itemtype', array(
 '1' => $rpgpetlang['rpg13'], '2' => $rpgpetlang['rpg14'], '3' => $rpgpetlang['rpg15'], '4' => $rpgpetlang['rpg16'], '5' => $rpgpetlang['rpg17']));  
print_input_row($rpgpetlang[rpggold], 'itemgp',0,0,5);  
print_yes_no_row('Shop', 'itemshop'); 
	print_table_header("<input type='hidden' name='add' value='item'>");	 
		print_table_header("<input type='submit' name='submit' value='DONE'>");
	echo "</form>";
   print_table_footer();  
	print_cp_footer();
  }
  
   
?>