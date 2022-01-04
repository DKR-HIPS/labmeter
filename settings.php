<?php

// 8 hex digits security code, must match the code from the Arduino device (working example here)
$code = 'a4b3c2d1' ;

$projectname = 'Lab-Meter' ;
$copyright = 'Daniel Krug 2022 - <a href=https://github.com/DKR-HIPS/labmeter>Project on github</a>' ;

// directory for saved data files (ends with '/' but not prefixed)
$filedir = 'files/' ;
// file extension to filter for, typically this is '.csv'
$extension = '.csv' ;
// delimiter to separate data columns, typically this is ';' or ','
$delimiter = ';' ;

$rowcolor[0]='#f8f8f8';
$rowcolor[1]='white';

?>