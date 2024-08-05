<?php
// fix regions for Canarias islands
if(isset($_POST['ZIP']) && $_POST['ZIP']>=35000 && $_POST['ZIP']<=35999){echo "Canarias";}