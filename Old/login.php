<?php
session_start();
session_regenerate_id();  // prevention of session hijacking
require "connectdb.php";
require "encryptor.php";// for aes256-cbc function
$PageTitle = "Login Page";
require "header_public.php";

$login_Error = FALSE;

// If form has been submitted, sanitise and process inputs
if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
    // If Username and Password fields have data & student ID is a valid 9 digit number
    if (isset($_POST['STUDENT_ID']) && preg_match('/^[1-9][0-9]{8}$/',$_POST['STUDENT_ID']) && isset($_POST['STUDENT_PASSWORD']))
        {
        $id = mysqli_real_escape_string($CON, $_POST['STUDENT_ID']);
        $password = mysqli_real_escape_string($CON, $_POST['STUDENT_PASSWORD']);

        // Reset variables for login
        $stu_ID = $stu_Password = "";
        $login_Error_Text="Login error";
        $login_Error = FALSE;

        $query = "SELECT * FROM student WHERE stu_ID='$id'";

        // If Student ID valid  and lockout not enabled

        $result = mysqli_query($CON, $query) or die(mysqli_error($CON));
        $user = $result->fetch_assoc();

        if ($user['stu_ID']!==$id and $login_Error==FALSE)
            {
            $login_Error = TRUE;
            $login_Error_Text="Invalid Username ( ID does not exist! )";
            }
        // Set php server  to +10UTC or use ---date_default_timezone_set('Australia/Melbourne');
        // grab lock out flag,time and attempts
        $loginlockout = $user['stu_LockedOut'];
        $logintimestamp = $user['stu_Timestamp'];
        $loginattempts = $user['stu_LoginAttempts'];
        $lockouttimer=time()-strtotime($logintimestamp);//takes difference of timestamp and now in seconds
        $lockouttimerinmins= 30-round($lockouttimer/60); // works out time remaining in mins
        // test for locked out student
        if  ($loginlockout==TRUE and $login_Error==FALSE)
            {
            $login_Error = TRUE;
            $login_Error_Text="Locked out of account for ".$lockouttimerinmins." minutes.";

            if ($lockouttimer>1800)// 1800 seconds for time of lockout
                {
                $query = "UPDATE student SET stu_LoginAttempts=5 WHERE stu_ID = $id";
                mysqli_query($CON, $query) or die(mysqli_error($CON));
                $query = "UPDATE student SET stu_LockedOut=FALSE WHERE stu_ID = $id";
                mysqli_query($CON, $query) or die(mysqli_error($CON));
                $login_Error==FALSE;
                $login_Error_Text="Locked Account reset.";
                }
            }

            //  Grab password from student table
            $storedencryptedhash = $user['stu_Password'];
            $storedhash = encrypt_decrypt('decrypt', $storedencryptedhash);
            $validpassword = password_verify($password, $storedhash);

            // Test Password hash match
        if (!$validpassword && $login_Error==FALSE)
            {
            // Invalid login. ID not in database
            $login_Error = TRUE;
            if ($loginattempts>1)
                {
                $loginattempts-=1;
                $login_Error_Text="Password incorrect ".$loginattempts." attempt";
                if ($loginattempts!==1)
                    {
                    $login_Error_Text=$login_Error_Text."s";
                    }
                $login_Error_Text=$login_Error_Text." left.";


                //update mysqli attempts and locked status

                $query = "UPDATE student SET stu_LoginAttempts=$loginattempts WHERE stu_ID = $id";
                mysqli_query($CON, $query) or die(mysqli_error($CON));

                }
            else
                {
                $login_Error_Text="Too many attempts. Account locked for 30 mins";
                $query = "UPDATE student SET stu_LockedOut=TRUE WHERE stu_ID = $id";
                mysqli_query($CON, $query) or die(mysqli_error($CON));
                $query = "UPDATE student SET stu_Timestamp=now() WHERE stu_ID = $id";
                mysqli_query($CON, $query) or die(mysqli_error($CON));


                }
            }



            // Successful login.

            if ($login_Error==FALSE)
            {
                //grab rest of student data
                $_SESSION['STUDENT_ID'] = $id;
                $_SESSION['STUDENT_FIRSTNAME'] = $user['stu_FirstName'];
                $_SESSION['STUDENT_LASTNAME'] = $user['stu_LastName'];
                $query = "UPDATE student SET stu_LoginAttempts=5 WHERE stu_ID = $id";
                mysqli_query($CON, $query) or die(mysqli_error($CON));
                header("location: studenthome");
            }

        }
    else
        {
          // Invalid Login. Regex doesn't match
        $login_Error = TRUE;
        $login_Error_Text="Invalid Username (ID is 9 digits)";
        }

}
// end database query for login
?>

<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
  <h2>Please Log In</h2>
  <?php if($login_Error) echo "<p>$login_Error_Text</p>";?>
  <input type="text" name="STUDENT_ID" placeholder="Student ID" class="inputBox" required><br><br>
  <input type="password" name="STUDENT_PASSWORD" placeholder="Password" class="inputBox" required><br><br>
  <button type="submit" class="inputButton">Login</button>
  <button type="button" onclick="location.href='forgotpassword.php';" value="Forgot Password" class="inputButton">Forgot Password</button><br><br>
</form>

<?php require "footer.php"; ?>
