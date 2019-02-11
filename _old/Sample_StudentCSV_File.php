<?php
session_start();
session_regenerate_id();  // prevention of session hijacking
require "staffauth.php";


//headers so file is downloaded, not displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Sample_Student_CSV.csv');
//create output variable
$output = fopen('php://output', 'w');
//column headings
fputcsv($output, array('Student ID','FirstName','LastName','Campus (1=Burwood. 2=Geelong. 3=Cloud)', 'Student Email' ));
//DEBUG
//fputcsv($output, array('123456781','Jack','McDorkman','3','JackMcDorkman@deakin.edu.au'));


?>
