<?php
 	$PageTitle = "Student Survey";
	require "header_student.php";
  require_once "connectdb.php";
  require "getactivesurveys.php";

  function skillOptions($title, $key) {
    echo "<tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr><tr>
            <th>$title</th>
            <td><label for='4$key'><div class='radioCell'>
                <input type='radio' id='4$key' name='$key' value='4' class='radioCell'>&nbsp;
            </div></label></td>
            <td><label for='3$key'><div class='radioCell'>
                <input type='radio' id='3$key' name='$key' value='3' class='radioCell'>&nbsp;
            </div></label></td>
            <td><label for='2$key'><div class='radioCell'>
                <input type='radio' id='2$key' name='$key' value='2' class='radioCell'>&nbsp;
            </div></label></td>
            <td><label for='1$key'><div class='radioCell'>
                <input type='radio' id='1$key' name='$key' value='1' class='radioCell'>&nbsp;
            </div></label></td>
            <td><label for='0$key'><div class='radioCell'>
                <input type='radio' id='0$key' name='$key' value='0' checked class='radioCell'>&nbsp;
            </div></label></td>
        </tr>";
  }

  // check active surveys for student
  $surveys = getActiveSurveys($id, $CON);
  $units = [];
  $i=0;
  while (isset($surveys[$i][0])) {
    array_push($units, $surveys[$i][0]);
    $i++;
  }
  $error = FALSE;
  // If form has been submitted, sanitise and process inputs
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $valid = TRUE;
    // Check if unit is a valid unit for this student, with survey open for submission
    if(!in_array($_POST['unit_ID'], $units)) {
      $valid = FALSE;
    } else {
      $unit_ID = $_POST['unit_ID'];
    }

    $i = 0;
    $survey_results = [];
    for ($i = 0; $i < 20; $i += 1)
    {
        if (isset($_POST[$i]))
        {
            $value = $_POST[$i];
            if (!is_null($value))
            {
              if(preg_match('/^[0-4]$/', $value)) {
                $survey_results['stu_skill_'.sprintf("%02d", $i)] = $value;
              } else {
                $valid = FALSE;
              }
            }
        }
    }
    if ($valid) {
      // Students will already be in survey table. No insert required, only update
      $count = sizeof($survey_results);
      $keys = array_keys($survey_results);
      $values = array_values($survey_results);
      $sql = "UPDATE surveyanswer SET submitted = 1, ";
      for ($j=0; $j<$count; $j++) {
        $sql .= $keys[$j] . " = " . $values[$j] . ",";
      }
      $sql = substr($sql, 0, -1); // Remove last comma
      $sql .= " WHERE stu_ID='$id' AND unit_ID='$unit_ID'";
      if (!mysqli_query($CON,$sql)) {
        $error = TRUE;
      }
    } else {
      // Invalid data, injection attempt?
      echo "Invalid data. Please reload and try again.";
      $error = TRUE;
    }
    if (!$error) {
      echo "<h2>Survey Complete</h2>";
      echo "<p>Thank you for completing the skills survey.</p>";
    }
  } elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['u'])) {
    // If not submitted, print form for GET unit_ID assuming unit_ID has an open survey for this student
    if (in_array($_GET['u'], $units)) {
    $unit_ID = $_GET['u'];
?>

<style>
    table {
        border-collapse: collapse;
    }
    td, th {
        text-align: center;
        width: 96px;
        border: 0;
        padding: 0;
    }
    td div {
        width: 100%;
        height: 100%;
    }
    .radioCell {
        background: #e0e0e0;
        cursor: pointer;
    }
    .radioCell:hover {
        background: #e0f0ff;
    }
</style>

<div style="text-align: center;">
    <?php if($error) echo "There was an error saving your survey. Please try again."; ?>
    <h1>Skills Survey</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <table align="center"><tr>
    <th>&nbsp;</th>
    <th>Expert</th>
    <th>High</th>
    <th>Intermediate</th>
    <th>Novice</th>
    <th>None</th>
    </tr>

<?php
  // Get Skill Names from Database
  $sql = "SELECT * FROM unit WHERE unit_ID = '" . $unit_ID . "'";
  $res = mysqli_query($CON, $sql);
  $row = mysqli_fetch_assoc($res);

  // Call skillOptions() to print a row for skill_## if that skill has a name value
    for ($i=0; $i<20; $i++) {
        $name = $row['skill_'.sprintf('%02d',$i)];
        if (!is_null($name)) {
        skillOptions($name, $i);
    }
  }
?>
    </table>
    <br>
    <input type="hidden" name="unit_ID" id="unit_ID" value="<?php echo $unit_ID;?>" />
    <input type="submit" value="Submit Responses" style="font-size: 1.5em" class="inputButton">
    </form>
</div> <?php } else echo "Invalid Unit Selected";
} else echo "Invalid Unit Selected";?>
<?php require "footer.php"; ?>
