<?php 
session_start();
include 'config/SYD_Class.php';

$coder = new sydClass();

if (isset($_POST["btnLogin"])) {

    $user = $_POST['txt_email'];
    $pass = $_POST['txt_password'];

    $qry = "SELECT * FROM users WHERE email='$user' AND password='$pass'";
    $coder->search($qry);

    if ($coder->result->num_rows == 1) {

        $row = $coder->result->fetch_assoc();

        $_SESSION['user_id']   = $row['user_id'];
        $_SESSION['email']     = $row['email'];
        $_SESSION['full_name'] = $row['full_name'];
        $_SESSION['user_type'] = $row['user_type'];
        $_SESSION['profile_pic']     = $row['profile_pic'];

        // Redirect based on user type
        if ($row['user_type'] == 'Admin') {

            header('Location: index.php');

        } else if ($row['user_type'] == 'Patient') {

            header('Location: patient_dashboard.php');

        } else if ($row['user_type'] == 'Doctor') {

            header('Location: doctor_dashboard.php');

        }

        exit();

    } else {

        header("Location: sign_in.php?message=Your Username or password is wrong");
        exit();

    }
}
?>