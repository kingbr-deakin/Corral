<?php
session_start();
require "staffauth.php";

require_once "connectdb.php";

//Get All Survey
$query = "SELECT student.stu_FirstName, student.stu_LastName, surveyanswer.* FROM surveyanswer INNER JOIN student ON surveyanswer.stu_ID = student.stu_ID";
if (!$result = mysqli_query($CON, $query)) {
    exit(mysqli_error($CON));
}
else{
  //headers so file is downloaded, not displayed
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=surveyanswers.csv');
  //create output variable
  $output = fopen('php://output', 'w');
  //column headings
  fputcsv($output, array('FirstName','LastName','Student ID','HTML/CSS','JavaScript','PHP','Java','C','C++','Obj C','Database','Unity 3','UI','Security'));
  //data rows
  while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
  }
}

?>
