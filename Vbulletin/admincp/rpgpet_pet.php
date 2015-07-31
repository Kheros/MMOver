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

	print_cp_header("RPG PET SETTINGS");
	echo "<form name='settings' action='rpgpet_add.php?add=pet' method='GET'>";
	print_table_start();
	print_table_header("RPG Pet Settings", 13);
	print_cells_row(array("Id","Image","Name","Description","Class","Level","Hp","ExpNext","Attact","Deffense","Critical","Luck","EDIT"),1,0,-2);
	$query = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "rpg_pet ORDER BY id desc");
	while( $petlist = $db->fetch_array($query))
	{
		switch ($petlist['class']) {
    case 1:
        $petlist['class'] ="<b>$rpgpetlang[rpg1]</b>";
        break;
    case 2:
        $petlist['class'] ="<b>$rpgpetlang[rpg2]</b>";
        break;
    case 3:
        $petlist['class'] ="<b>$rpgpetlang[rpg3]</b>";
        break;
    case 4:
        $petlist['class'] ="<b>$rpgpetlang[rpg4]</b>";
        break;
    case 5:
        $petlist['class'] ="<b>$rpgpetlang[rpg5]</b>";
        break;
}	
$mieuta = substr($petlist[mieuta],0,15);
	echo "<tr><td class='alt1'>$petlist[id]</td><td class='alt1'><img src=$petlist[image] height='42px' width='42px'></td><td class='alt1'><b>$petlist[name]</b></td> <td class='alt1'>$mieuta</td><td class='alt1'>$petlist[class]</td><td class='alt1'>$petlist[level]</td><td class='alt1'>$petlist[hp]</td><td class='alt1'>$petlist[expnext]</td><td class='alt1'>$petlist[att]</td><td class='alt1'>$petlist[def]</td></td><td class='alt1'>$petlist[cri]</td><td class='alt1'>$petlist[luck]</td><td class='alt1'><a href='rpgpet_edit.php?edit=pet&id=$petlist[id]'><b>EDIT</b></a></td></tr>";
	}
	
	print_table_header("<input type='hidden' name='add' value='pet'><input type='submit' name='submit' value='Add New Pet'>", 13);
	echo "</form>";
   print_table_footer();  
	print_cp_footer();

		
?>