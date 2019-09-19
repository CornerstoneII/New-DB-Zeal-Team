<?php



session_start();





require 'config/db.php';
require_once 'emailController.php';




$errors = array();
$username = "";
$email = "";




//if user clicks the sign up button
if (isset($_POST['signup-btn'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $passwordConfirm = $_POST['passwordConfirm'];




//validation
if (empty($username)) {
    $errors['username'] = "Username is required";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "E-mail address entered is invalid";
}
if (empty($email)) {
    $errors['email'] = "E-mail is required";
}
if (empty($password)) {
    $errors['password'] = "Password is required";
}
if ($password != $password) {
    $errors['password'] = "The password entered do not match!";
}



$emailQuery = "SELECT * FROM users WHERE email=? LIMIT 1";
$stmt = $conn->prepare($emailQuery);
$stmt->bind_param('s', $email);
$stmt->execute();
$result= $stmt->get_result();
$userCount = $result->num_rows;
$stmt->close();

if($userCount > 0){
    $errors['email'] = "E-mail address entered already exists!";
}


if (count($errors) === 0) {
    $password = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(50));
    $verified = false;

    $sql = "INSERT INTO users (username, email, verified, token, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssbss', $username, $email, $verified, $token, $password);

    if ($stmt->execute()) {
        //login user
        $user_id = $conn->insert_id;
        $_SESSION['id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['verified'] = $verified;






// send verification message
sendVerificationEmail($email, $token);










        //flash message
        $_SESSION['message'] = "You are now logged in!";
        $_SESSION['alert-class'] = "alert-success";
        header('location: index.php');
        exit();
    }else{

        $errors['db error'] = "Database error: failed to register";
        }
    }


}


// if user clicks on the login button
if (isset($_POST['login-btn'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];





//validation
if (empty($username)) {
    $errors['username'] = "Username is required";
}
if (empty($password)) {
    $errors['password'] = "Password is required";
}


if (count($errors) === 0) {
    $sql = "SELECT * FROM users WHERE email=? OR username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();


    if (password_verify($password, $user['password'])) {
        //login success
        //login user
        $user_id = $conn->insert_id;
        $_SESSION['id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['verified'] = $user['verified'];
        //flash message
        $_SESSION['message'] = "You are now logged in!";
        $_SESSION['alert-class'] = "alert-success";
        header('location: index.php');
    }else{
        $errors['login_fail'] = "Wrong credentials";
        }
    }
}


//logout user
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    unset($_SESSION['verified']);
    header('location: login.php');
    exit();
}
?>