<?php
// generate_hash.php
$new_password = "admin123"; // <--- CHANGE THIS to your desired new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
echo "New hashed password: <br>";
echo $hashed_password;
echo "<br><br>Keep this file secure or delete it after use.";
?>
