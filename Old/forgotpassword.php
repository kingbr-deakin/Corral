<?php
require "connectdb.php";
$PageTitle = "Login Page";
require "header_public.php";

// Define variables
$email_Error = FALSE;
$email_Sent = FALSE;
$email_NotFound = FALSE;

// If form has been submitted, sanitise and process inputs
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['forgotemail']) && preg_match('/[A-Za-z0-9]*@deakin.edu.au/', $_POST['forgotemail'])){

    // Lookup email in student/staff tables to find a match.
    $email = mysqli_real_escape_string($CON, $_POST['forgotemail']);
    // Query looks at emails in both Staff and Student tables, and returns how many matches it found. (Expected 1 or 0)
    $query = "SELECT SUM(usercount) AS usercount
                FROM (
                  SELECT COUNT(*) AS usercount FROM staff WHERE sta_Email = '".$email."'
                  UNION ALL
                  SELECT count(*) AS usercount FROM student WHERE stu_Email = '".$email."'
                ) as usercounts";
    $result = mysqli_query($CON, $query) or die(mysqli_error($CON));
    $row = mysqli_fetch_row($result);
    if ($row[0] == 1){
      // Match Found
      //Configure PHPMailer
      require "PHPMailer/PHPMailerAutoload.php";
      $mail = new PHPMailer;
      $mail->SMTPOptions = array(
        'ssl' => array(
          'verify_peer' => false,
          'verify_peer_name' => false,
          'allow_self_signed' => true
        )
      );
      $mail->isSMTP();                                   // Set mailer to use SMTP
      $mail->Host = 'smtp.gmail.com';                    // Specify main and backup SMTP servers
      $mail->SMTPAuth = true;                            // Enable SMTP authentication
      $mail->Username = 'thecorralproject@gmail.com';    // SMTP username
      $mail->Password = 'corral374'; 						         // SMTP password
      $mail->SMTPSecure = 'tls';                         // Enable TLS encryption
      $mail->Port = 587;                                 // TCP port to connect to
      $mail->setFrom('thecorralproject@gmail.com','Corral Project');
      //$mail->addReplyTo($email);
      $mail->addAddress($email);   // Add a recipient
      $mail->isHTML(true);                               // Set email format to HTML

      // Create password reset URL
      $selector = bin2hex(openssl_random_pseudo_bytes(8));
      $token = openssl_random_pseudo_bytes(32);
      $resetvar = http_build_query(['selector'=>$selector, 'validator'=>bin2hex($token)]);
      $thisuri = $_SERVER['HTTP_HOST'].pathinfo($_SERVER['REQUEST_URI'])['dirname'];
      $reseturl = "http://".$thisuri."/resetpassword.php?".$resetvar;
      $expires = strtotime("+1 hour");


      // Delete any previous tokens for this user
      $query = "DELETE FROM passwordreset WHERE email='".$email."'";
      if (!mysqli_query($CON,$query)) {
        echo "Error deleting previous record: " . mysqli_error($CON);
      }
      // Insert this password reset information into database
      $query = "INSERT INTO passwordreset (email,selector,token,expires) VALUES ('".$email."', '".$selector."', '".hash('sha256',$token)."', '".$expires."')";

      if (!mysqli_query($CON,$query)) {
        echo "Error inserting new reset record: " . mysqli_error($CON);
      }

      // Set email content
      $bodyContent = '<h2>Password Reset Request</h2>
        <p>Hello, you are receiving this email because somebody attempted to reset your password.</p>
        <p>If this was not you, please ignore this email. Otherwise, click the following link:</p>
        <p><a href="'.$reseturl.'">Reset your password</a></p>
      '; // TEAM NOTE: this exact link may not work for you depending on how you have set up your XAMPP!
      $mail->Subject = 'Password Reset';
      $mail->Body=$bodyContent;

      // Send or error
      if(!$mail->send()) {
          echo 'Message could not be sent.';
          echo 'Mailer Error: ' . $mail->ErrorInfo;
      }

      $email_Sent = TRUE;
    } else {
      // No Match Found
      $email_NotFound = TRUE;
    }
  } else {
    // Invalid Deakin email
    $email_Error = TRUE;
  }
}
?>

<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
  <h2>Forgot Password</h2>
  <?php
    if($email_Error) echo "<p>Invalid Deakin email address.</p>";
    if($email_Sent) echo "<p>Password reset email sent.</p>";
    if($email_NotFound) echo "<p>You are not a registered user.</p>";
  ?>
  <input type="email" name="forgotemail" placeholder="Deakin Email Address" class="inputBox" required><br><br>
  <button type="submit" class="inputButton">Password Reset</button><br><br>
</form>

<?php require "footer.php"; ?>
