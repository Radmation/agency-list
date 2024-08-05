<?php
// Fix Czechia regions
if(isset($_POST['ZIP']) && $_POST['ZIP']){

$zip_code = (int)str_replace(' ', '', $_POST['ZIP']);

	if((10000 <= $zip_code) && ($zip_code <= 19999)){echo "Prague";}
	elseif((25000 <= $zip_code) && ($zip_code <= 29599)){echo "Central Bohemian";}
	elseif((30100 <= $zip_code) && ($zip_code <= 34999)){echo "Plzeň";}
	elseif((35000 <= $zip_code) && ($zip_code <= 36499)){echo "Karlovy Vary";}
	elseif((37000 <= $zip_code) && ($zip_code <= 39999)){echo "South Bohemian";}
	elseif((40000 <= $zip_code) && ($zip_code <= 44199)){echo "Ústí nad Labem";}
	elseif((46000 <= $zip_code) && ($zip_code <= 47399)){echo "Liberec";}

	elseif((50000 <= $zip_code) && ($zip_code <= 52999)){echo "Hradec Králové";}

	elseif((53000 <= $zip_code) && ($zip_code <= 53999)){echo "Pardubice";}

	elseif((54100 <= $zip_code) && ($zip_code <= 55299)){echo "Hradec Králové";}

	elseif((56000 <= $zip_code) && ($zip_code <= 57299)){echo "Pardubice";}

	elseif((58000 <= $zip_code) && ($zip_code <= 59599)){echo "Vysočina";}
	elseif((60000 <= $zip_code) && ($zip_code <= 69899)){echo "South Moravian";}
	elseif((70000 <= $zip_code) && ($zip_code <= 74999)){echo "Moravian-Silesian";}
	elseif((75000 <= $zip_code) && ($zip_code <= 76999)){echo "Zlín";}
	elseif((77900 <= $zip_code) && ($zip_code <= 79899)){echo "Olomouc";}

	
}
