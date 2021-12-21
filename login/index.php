<?php

include '../includes/connection.php';
include '../includes/functions.php';
session_start();

if (isset($_SESSION['username']))
{
    header("Location: ../dashboard/");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>KeyAuth - Login</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="https://cdn.keyauth.com/assets/img/favicon.png" type="image/x-icon">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.keyauth.com/auth/css/util.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.keyauth.com/auth/css/main.css">
</head>
<body>
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100 p-t-50 p-b-90">
				<form class="login100-form validate-form flex-sb flex-w" method="post">
					<span class="login100-form-title p-b-51">
						Login
					</span>

					
					<div class="wrap-input100 validate-input m-b-16">
						<input class="input100" type="text" name="keyauthusername" placeholder="Username" required>
						<span class="focus-input100"></span>
					</div>
					
					
					<div class="wrap-input100 validate-input m-b-16">
						<input class="input100" type="password" name="keyauthpassword" placeholder="Password" required>
						<span class="focus-input100"></span>
					</div>

                    <div class="wrap-input100 validate-input m-b-16">
						<input class="input100" name="keyauthtwofactor" placeholder="Two Factor Code (if applicable)">
						<span class="focus-input100"></span>
					</div>
					
					<div class="flex-sb-m w-full p-t-3 p-b-24">
						<div>
							<a href="../register/" class="txt1">
								Register
							</a>
						</div>

						<div>
							<a href="../forgot/" class="txt1">
								Forgot?
							</a>
						</div>
					</div>

					<div class="container-login100-form-btn m-t-17">
						<button name="login" class="login100-form-btn">
							Login
						</button>
					</div>

				</form>
			</div>
		</div>
	</div>
	
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

    <?php
if (isset($_POST['login']))
{
    $username = sanitize($_POST['keyauthusername']);
    $password = sanitize($_POST['keyauthpassword']);

    ($result = mysqli_query($link, "SELECT * FROM `accounts` WHERE `username` = '$username'")) or die(mysqli_error($link));

    if (mysqli_num_rows($result) == 0)
    {
        error("Account doesn\'t exist!");
        return;
    }
    while ($row = mysqli_fetch_array($result))
    {
        $user = $row['username'];
        $pass = $row['password'];
        $id = $row['ownerid'];
        $email = $row['email'];
        $role = $row['role'];
        $app = $row['app'];
        $banned = $row['banned'];
        $img = $row['img'];

        $owner = $row['owner'];
        $twofactor_optional = $row['twofactor'];
        $acclogs = $row['acclogs'];
        $google_Code = $row['googleAuthCode'];
    }

    if (!is_null($banned))
    {
        error("Banned: Reason: " . sanitize($banned));
        return;
    }

    if (!password_verify($password, $pass))
    {
        error("Password is invalid!");
        return;
    }

    if ($twofactor_optional)
    {
        // keyauthtwofactor
        $twofactor = sanitize($_POST['keyauthtwofactor']);
        if (empty($twofactor))
        {
            error("Two factor field needed for this acccount!");
            return;
        }

        require_once '../auth/GoogleAuthenticator.php';
        $gauth = new GoogleAuthenticator();
        $checkResult = $gauth->verifyCode($google_Code, $twofactor, 2);

        if (!$checkResult)
        {
            error("2FA code Invalid!");
            return;
        }
    }

    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['ownerid'] = $id;
    $_SESSION['owner'] = $owner;
    $_SESSION['role'] = $role;
	$_SESSION['logindate'] = time();

    if ($role == "Reseller" || $role == "Manager")
    {
        ($result = mysqli_query($link, "SELECT `secret` FROM `apps` WHERE `name` = '$app' AND `owner` = '$owner'")) or die(mysqli_error($link));
        if (mysqli_num_rows($result) < 1)
        {
            error("Application you\'re assigned to no longer exists!");
            return;
        }
        while ($row = mysqli_fetch_array($result))
        {
            $app = $row["secret"];
        }
        $_SESSION['app'] = $app;
    }

    $_SESSION['img'] = $img;

    if ($acclogs) // check if account logs enabled
    
    {
		$ua = sanitize($_SERVER['HTTP_USER_AGENT']);
        mysqli_query($link, "INSERT INTO `acclogs`(`username`, `date`, `ip`, `useragent`) VALUES ('$username','" . time() . "','$ip','$ua')"); // insert ip log
        $ts = time() - 604800;
        mysqli_query($link, "DELETE FROM `acclogs` WHERE `username` = '$username' AND `date` < '$ts'"); // delete any account logs more than a week old
        
    }
	wh_log($logwebhook, "{$username} has logged into KeyAuth with IP `{$ip}`", $webhookun);
	
    mysqli_query($link, "UPDATE `accounts` SET `lastip` = '$ip' WHERE `username` = '$username'");

    header("location: ../dashboard/");
}
?>
</body>
</html>