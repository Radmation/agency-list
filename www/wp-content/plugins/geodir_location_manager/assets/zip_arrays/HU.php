<?php
/**
 * Fix Hungary regions
 *
 * @source https://en.wikipedia.org/wiki/List_of_regions_of_Hungary
 * @source https://en.wikipedia.org/wiki/List_of_postal_codes_in_Hungary
 */

if(isset($_POST['ZIP']) && $_POST['ZIP']){

$zip_code = (int)str_replace(' ', '', $_POST['ZIP']);
	$rg = substr($zip_code, 0, 2);


	// specifics
	if((1000 <= $zip_code) && ($zip_code <= 1901)){echo "Közép-Magyarország";} //1000 - 1901
	elseif((2000 <= $zip_code) && ($zip_code <= 2391)){echo "Közép-Magyarország";} //2000 - 2391
	elseif((2400 <= $zip_code) && ($zip_code <= 2491)){echo "Közép-Dunántúl";} //2400 - 2491
	elseif((2500 <= $zip_code) && ($zip_code <= 2591)){echo "Közép-Dunántúl";} //2500 - 2591
	elseif((2600 <= $zip_code) && ($zip_code <= 2791)){echo "Közép-Magyarország";} //2600 - 2791
	elseif((2800 <= $zip_code) && ($zip_code <= 2991)){echo "Közép-Dunántúl";} // 2800 - 2991
	elseif((3000 <= $zip_code) && ($zip_code <= 3050)){echo "Észak-Magyaroszág";} // 3000 - 3050
	elseif((3051  <= $zip_code) && ($zip_code <= 3191)){echo "Észak-Magyaroszág";} // 3051 - 3191
	elseif((3200 <= $zip_code) && ($zip_code <= 3391)){echo "Észak-Magyaroszág";} // 3200 - 3391
	elseif((3400 <= $zip_code) && ($zip_code <= 3991)){echo "Észak-Magyaroszág";} //3400 - 3991
	elseif((4000 <= $zip_code) && ($zip_code <= 4291)){echo "Észak-Alföld";} //4000 - 4291
	elseif((4300 <= $zip_code) && ($zip_code <= 4991)){echo "Észak-Alföld";} //4300 - 4991
	elseif((5000 <= $zip_code) && ($zip_code <= 5491)){echo "Észak-Alföld";} //5000 - 5491
	elseif((5500 <= $zip_code) && ($zip_code <= 5991)){echo "Dél-Alföld";} //5500 - 5991
	elseif((6000 <= $zip_code) && ($zip_code <= 6591)){echo "Dél-Alföld";} //6000 - 6591
	elseif((6600  <= $zip_code) && ($zip_code <= 6991)){echo "Dél-Alföld";} //6600 - 6991
	elseif((7000  <= $zip_code) && ($zip_code <= 7059)){echo "Közép-Dunántúl";} //7000 - 7059
	elseif((7060 <= $zip_code) && ($zip_code <= 7254)){echo "Dél-Dunántúl";} //7060 - 7254

	// two digit codes
	elseif(in_array($rg,array(31,32,33,34,35,36,37,38,39))){echo "Észak-Magyaroszág";}
	elseif(in_array($rg,array(40,41,42,43,44,45,46,47,48,49,50,51,52,53,54))){echo "Észak-Alföld";}
	elseif(in_array($rg,array(55,56,57,58,59,60,61,62,63,64,65))){echo "Dél-Alföld";}
	elseif(in_array($rg,array(10,11,12,20,21,22,23,26,27))){echo "Közép-Magyarország";}
	elseif(in_array($rg,array(24,25,28,29,80,81,82,83,84,85))){echo "Közép-Dunántúl";}
	elseif(in_array($rg,array(70,71,72,73,74,75,76,77,78,79,86,87))){echo "Dél-Dunántúl";}
	elseif(in_array($rg,array(90,91,92,93,94,95,96,97,98,99,88,89))){echo "Nyugat-Dunántúl";}




}

//Northern Hungary (Észak-Magyaroszág)	Miskolc	13,428	1,153,714	86
//Northern Great Plain (Észak-Alföld)	Debrecen	17,749	1,474,383	83
//Southern Great Plain (Dél-Alföld)	Szeged	18,339	1,262,936	69
//Central Hungary (Közép-Magyarország)	Budapest	6,919	2,993,948	433
//Central Transdanubia (Közép-Dunántúl)	Székesfehérvár	11,237	1,060,703	94
//Western Transdanubia (Nyugat-Dunántúl)	Győr	11,209	983,933	88
//Southern Transdanubia (Dél-Dunántúl)	Pécs	14,169	900,868	64

