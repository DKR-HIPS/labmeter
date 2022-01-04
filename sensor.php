<?php

require('settings.php');

$security = trim($_GET['s']) ;
$devnum = trim($_GET['d']) ;
$temperature = $_GET['t'] ;
$humidity = $_GET['h'] ;

$nowdate = date("Y-m-d");
$nowtime = date("H:i:s");

$ipaddress = $_SERVER['REMOTE_ADDR'];

if($security==$code && is_numeric($devnum) && isset($temperature) && isset($humidity))
{
  $datename = substr($nowdate, 0, 10) ;
  $filename = $datename.$extension ;
  
  if(!file_exists($filedir.$filename)) 
    {  
    $createfile = fopen($filedir.$filename, "w") or die("Cannot create file.");
    fclose($filedir.$filename);
    }
  
  $values = $nowdate.$delimiter.$nowtime.$delimiter.$temperature.$delimiter.$humidity.$delimiter.$devnum.$delimiter.$ipaddress."\n" ;
  $oldfile = file_get_contents($filedir.$filename) ;
  $newfile = $values.$oldfile ;
  
  file_put_contents($filedir.$filename, $newfile) ;
  
  echo("|".$nowtime."|".$nowdate."|") ;
  // the server answers back: current time/date with recoginition | characters
  // this is read by the Arduino client and used to show last saved measurement timepoint in LC-display
}
  
else
{
echo("|00:00:00|0000-00-00|") ;
}

?>