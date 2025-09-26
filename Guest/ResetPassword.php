<?php
$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="../assets/css/resetpassword.css">  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

  <?php if (!empty($error)): ?>
    <p style="color:red;">⚠ <?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

 <form action="../backend/resetpassword.php" method="POST" onsubmit="return validatePassword()">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
 <h2>Reset Your Password</h2>
       
    <div class="input-container">
      <label for="new_password">New Password:</label><br>
      <input type="password" id="new_password" name="new_password" required>
      <i class="fa-solid fa-eye toggle-password" data-target="new_password"></i>
    </div>
    <br>

    <div class="input-container">
      <label for="confirm_password">Confirm Password:</label><br>
      <input type="password" id="confirm_password" name="confirm_password" required>
      <i class="fa-solid fa-eye toggle-password" data-target="confirm_password"></i>
    </div>


    <div id="error-message" style="color: red; font-weight: bold;"></div>
    <br>

    <button type="submit">Reset Password</button>

</form>

<script src="../assets/js/resetpassword.js"></script>

</body>
</html>
