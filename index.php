<?php
require('settings.php');

echo("<HEAD><TITLE>".$projectname."</TITLE>");
echo("<META name='Author' content='Daniel Krug'><meta name='date' content='2022-01-04'>");
echo("<meta name='description' content='Sensor measurements archive'>");
echo("<meta http-equiv='Content-Type' content='text/html; charset=utf-8'><META http-equiv='expires' content='0'>");
echo("<meta name='viewport' content='width=device-width, initial-scale=0.75'>");
echo("<link rel='stylesheet' href='style.css'>");

echo("</HEAD><BODY bgcolor='white'><div align=center>");

// Demonstration website notice
echo("<div style='border:1px dashed #007a78;padding:2px;background:#f4fcf8;margin-bottom:24px;'>
<p><b>This is a demonstration website for the Lab-Meter project</b>
<p><small>It will show masked IP addresses next to the measurements received for testing purposes. Data may be deleted anytime.</small></div>");

echo("<table class='outside'><tr><td><div align=center>");

echo("<p><h1>".$projectname."</h1><h2>Saved measurements</h2>");

$thisdate = date("Y-m-d");
$rnum = 1;

$dirlist = glob($filedir."*".$extension) ;
$filelist = str_replace($filedir,'',$dirlist) ;
rsort($filelist) ;
$numfiles = count($filelist) ;

if( ($_GET['action'] == 'show') && (strlen($_GET['date']) >= 10) )
{
  $showdate = substr($_GET['date'], 0, 10) ;
  $filename = $showdate.$extension ;
}
else
{  
  $filename = $filelist[0] ;
  $showdate = substr($filename, 0, 10) ;
}

if( $_GET['action'] != 'files')
{

if (($file = fopen($filedir.$filename, "r")) !== FALSE) 
 {
 $firstrow = fgetcsv($file, 100, $delimiter) ;

 if($showdate == $thisdate)
 {
  echo ("<table><tr><td><b>Last measurement:</b></td><td>".$firstrow[0]." ".$firstrow[1]."</td></tr>");
  echo ("<tr><td><b>Temperature:</b></td><td>".$firstrow[2]." &deg;C</td></tr>");
  echo ("<tr><td><b>Humidity:</b></td><td>".$firstrow[3]." %</td></tr></table>");
  $showlink = "&nbsp; <a href='index.php?action=show'>&rarr; Refresh</a>";
 }
 else
 {
  echo ("<table><tr><td><b>Data from:</b></td><td>".$firstrow[0]."</td></tr>");
  echo ("<tr><td><b>Last timepoint:</b></td><td>".$firstrow[1]."</td></tr>");
  echo ("<tr><td><b>Filename:</b></td><td><a href='".$filedir.$filename."' target='_blank'>".$filename."</a></td></tr></table>");
  $showlink = "&nbsp; <a href='index.php?action=show'>&rarr; Current values</a>";
 }
 
 echo("<hr><p>");
 // echo("<a href='chart.php?date=".$showdate."' target='_blank'>&rarr; Chart</a>&nbsp; ");
 echo("<a href='index.php?action=files'>&rarr; Archive</a>".$showlink);
 
 echo ("<div class='values'> <table class='values'>");
 echo ("<thead><tr bgcolor='#e0e4ea'><th>Date</th><th>Time</th><th>Temperature</th><th>Humidity</th><th>Device</th><th>Sent by</th></tr></thead><tbody>");

 echo ("<tr><td>".$firstrow[0]."</td><td>".$firstrow[1]."</td><td>".$firstrow[2]." &deg;C");
 // if($firstrow[2] < 18) 
 //  { echo (" <var class='c'>&#9660;</var>");}
 // if($firstrow[2] > 28)
 //  { echo (" <var class='h'>&#9650;</var>");}
 $showip = substr_replace($firstrow[5],'x',strpos($firstrow[5],'.')+1,1);
 echo ("</td><td>".$firstrow[3]." %</td><td>".$firstrow[4]."</td><td style='color:silver;'>".$showip."</td></tr>");

 while (($data = fgetcsv($file, 100, $delimiter)) !== FALSE)
  {
   $rnum = 1 - $rnum ;
   echo ("<tr bgcolor='".$rowcolor[$rnum]."'><td>".$data[0]."</td><td>".$data[1]."</td><td>".$data[2]." &deg;C");   
   // if($data[2] < 18)
   // {echo(" <var class='c'>&#9660;</var>");}
   // if($data[2] > 28)
   // {echo(" <var class='h'>&#9650;</var>");}
   $showip = substr_replace($data[5],'x',strpos($data[5],'.')+1,1);
   echo ("</td><td>".$data[3]." %</td><td>".$data[4]."</td><td  style='color:silver;'>".$showip."</td></tr>");
  }
 fclose($file);
 echo ("</tbody></table></div>");    
 }
else
 {
  echo ("<p style='color:red;'>Error: file ".$filename." not readable.");
 }
}

if( $_GET['action'] == 'files')
{
 if( isset($_GET['delete']) )
 {
  $deletename = trim($_GET['delete']) ;
  if( copy($filedir.$deletename, "deleted/".$deletename) ) 
    {
     unlink($filedir.$deletename);
     echo ("<p style='color:red;'>File ".$deletename." deleted.");
     $dirlist = glob($filedir."*".$extension) ;
     $filelist = str_replace($filedir,'',$dirlist) ;
     rsort($filelist) ;
     $numfiles = count($filelist) ;
    }
   else
    {
     echo ("<p style='color:red;'>Error deleting file.");
    }
 }

 echo ("<table><tr><td><b>Newest file:</b></td><td>".str_replace($extension,'',$filelist[0])."</td></tr>");
 echo ("<tr><td><b>Oldest file:</b></td><td>".str_replace($extension,'',$filelist[$numfiles-1])."</td></tr>");
 echo ("<tr><td><b>Number of files:</b></td><td>".$numfiles."</td></tr></table>");

 echo("<hr><p><a href='index.php?action=show'>&rarr; Current values</a>");

 echo ("<div class='values'> <table class='values'>");
 echo ("<thead><tr bgcolor='#e0e4ea'><th>File</th><th>Action</th></tr></thead><tbody>");

 foreach ($filelist as $listfile)
  {
   $rnum = 1 - $rnum ;
   echo "<tr bgcolor='".$rowcolor[$rnum]."'><td>".$listfile."</td><td><a href='index.php?action=show&date=".str_replace($extension,'',$listfile)."'>Show</a> | <a href='".$filedir.$listfile."' target='_blank'>Download</a> | <a href='index.php?action=files&delete=".$listfile."'>Delete</a></td></tr>";
  }

echo ("</tbody></table>"); 
}

echo("</div></td><tr></table><p><small>".$copyright."</small></div></BODY>");
?>