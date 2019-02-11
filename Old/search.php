<?php
$PageTitle = "Search";
require "header_staff.php";
require_once "connectdb.php";
require_once "getfunctions.php";

if (isset($_GET['search'])) {
  // Filter with Regex, Search term should only include uppercase/lowercase/numbers/.@ (to allow email search)
  if(!filter_var($_GET['search'], FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[a-zA-Z0-9.@]+$/")))) echo "<p>Invalid search term entered.</p>";
  else { // Passed regex, execute search with supplied term
    $search = $_GET['search'];
    $results = 0;
    // Search student table, print link to result
    $query = "SELECT stu_ID, stu_FirstName, stu_LastName, stu_Campus, stu_Email FROM student WHERE (
                stu_ID        LIKE '%".$search."%' OR
                stu_FirstName LIKE '%".$search."%' OR
                stu_LastName  LIKE '%".$search."%' OR
                stu_Email     LIKE '%".$search."%')";
    $res = mysqli_query($CON, $query);
    if (mysqli_num_rows($res) > 0) {
      echo "<p>Student results found: ".mysqli_num_rows($res)."</p>";
      echo "<form name action='studentuser.php' method='get'>
        <table class='listTable' align='center'>
          <tr>
              <th>ID</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Campus</th>
              <th>Email</th>
              <th>Update</th>
          </tr>";
      while ($row=mysqli_fetch_assoc($res)){
        echo
        "<tr>
            <td>".$row['stu_ID']."</td>
            <td>".$row['stu_FirstName']."</td>
            <td>".$row['stu_LastName']."</td>
            <td>".getcampus($row["stu_Campus"])."</td>
            <td>".$row['stu_Email']."</td>
            <td><button value ='".$row['stu_ID']."' name='studentid' class='updateButton'>Update</button></td>
        </tr>";
      }
      echo "</table><br />";
      $results += mysqli_num_rows($res);
      mysqli_free_result($res);
    }
    // Search staff table, print link to result
    $query = "SELECT sta_ID, sta_FirstName, sta_LastName, sta_Campus, sta_Email FROM staff WHERE (
                sta_FirstName LIKE '%".$search."%' OR
                sta_LastName  LIKE '%".$search."%' OR
                sta_Email     LIKE '%".$search."%')";
    $res = mysqli_query($CON, $query);
    if (mysqli_num_rows($res) > 0) {
      echo "<p>Staff results found: ".mysqli_num_rows($res)."</p>";
      echo
      "<form name='staffListForm' action='staffuser.php' method='get'>
        <table class='listTable' align='center'>
          <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Campus</th>
            <th>Email</th>
            <th>Update</th>
          </tr>";
      while ($row=mysqli_fetch_assoc($res)){
        echo
        "<tr>
          <td>".$row['sta_FirstName']."</td>
          <td>".$row['sta_LastName']."</td>
          <td>".getcampus($row["sta_Campus"])."</td>
          <td>".$row['sta_Email']."</td>
          <td><button value ='".$row['sta_ID']."' name='staffid' class='updateButton'>Update</button></td>
        </tr>";
      }
      echo "</table><br />";
      $results += mysqli_num_rows($res);
      mysqli_free_result($res);
    }
    // Search project table, prink link to result
    $query = "SELECT pro_ID, pro_title, pro_leader, pro_email,pro_brief,pro_status FROM project WHERE (
                pro_title   LIKE '%".$search."%' OR
                pro_leader  LIKE '%".$search."%' OR
                pro_email   LIKE '%".$search."%' OR
				        pro_brief   LIKE '%".$search."%' OR
                pro_status  LIKE '%".$search."%')";
    $res = mysqli_query($CON, $query);
    if (mysqli_num_rows($res) > 0) {
      echo "<p>Project results found: ".mysqli_num_rows($res)."</p>";
      echo
      "<form name='projectListForm' action='project.php' method='get'>
        <table class='listTable' align='center'>
          <tr>
              <th>Project Title</th>
              <th>Supervisor</th>
              <th>Supervisor Email</th>
              <th>Project Brief</th>
              <th>Project Status</th>
              <th>Update</th>
          </tr>";
      while ($row=mysqli_fetch_assoc($res)){
        echo
        "<tr>
          <td>".$row['pro_title']."</td>
          <td>".$row['pro_leader']."</td>
          <td>".$row['pro_email']."</td>
		      <td>".$row['pro_brief']."</td>
          <td>".$row['pro_status']."</td>
          <td><button value='".$row['pro_ID']."' name='number' class='updateButton'>Update</button></td>
        </tr>";
      }
      echo "</table><br />";
      $results += mysqli_num_rows($res);
      mysqli_free_result($res);
    }

    if ($results == 0) {
      echo '<h2>No results found for "'.$search.'"</h2>';
    }
  }
}

if (!isset($_GET['search'])) {
  echo "
  <form action='".htmlspecialchars($_SERVER['PHP_SELF'])."' method='get'>
  <input type='text' placeholder='Search..' name='search' class='inputBox'>
  <button type='submit'><i class='fa fa-search'></i></button>
  </form>";
}


?>
<?php require "footer.php"; ?>
