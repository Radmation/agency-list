<?php
/**
 * Fix Lativan regions
 *
 * @source https://en.wikipedia.org/wiki/Postal_codes_in_Latvia#/media/File:2_digit_postcode_latvia.png
 * @source https://en.wikipedia.org/wiki/Planning_regions_of_Latvia#/media/File:Latvia_planning_regions.png
 * @source https://en.wikipedia.org/wiki/Planning_regions_of_Latvia
 */

if(isset($_POST['ZIP']) && $_POST['ZIP']){

$zip_code = (int)str_replace(' ', '', $_POST['ZIP']);
	$rg = substr($zip_code, 0, 2);

	if(in_array($rg,array(10,20,21,31,50,40))){echo "Riga";}
	elseif(in_array($rg,array(32,33,34,36,38))){echo "Kurzeme";}
	elseif(in_array($rg,array(37,30,39,51,52))){echo "Zemgale";}
	elseif(in_array($rg,array(53,54,56,57,46,45))){echo "Latgale";}
	elseif(in_array($rg,array(41,42,43,44,47,48))){echo "Vidzeme";}
}

