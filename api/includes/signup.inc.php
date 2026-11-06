<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $pwd = $_POST["pwd"];
    $imageFile = $_FILES["profileimg"];


    try {

        require_once "dbh.inc.php";
        require_once "signup_includes/signup_model.inc.php";
        require_once "signup_includes/signup_contr.inc.php";



        // SIGNUP ERRORS

        $errors = [];

        if (is_email_invalid($email)) {
            $errors["invalid_email"] = '
         <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Invalid email used!"
            });
            </script>';
        }
        if (is_email_registered($pdo, $email)) {
            $errors["email_used"] = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Email already registered!"
            });
            </script>';
        }

        if (is_input_empty($firstname, $middlename, $lastname, $email, $pwd, $imageFile)) {
            $errors["input_empty"] = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Signup failed",
                text: "Input all fields!"
            });
            </script>';
        }


        require_once "config_session.inc.php";

        if ($errors) {
            $_SESSION["errors_signup"] = $errors;

            $signupData = [
                "firstname" => $firstname,
                "middlename" => $middlename,
                "lastname" => $lastname,
                "email" => $email
            ];

            $_SESSION["signup_data"] = $signupData;

            header("Location: ../signup.php");
            die();
        }
        create_user(
            $pdo,
            $_POST['firstname'],
            $_POST['middlename'],
            $_POST['lastname'],
            $_POST['email'],
            $_POST['pwd'],
            $_FILES['profileimg']
        );
        // create_user($pdo, $firstname, $middlename, $lastname, $email, $pwd);

        header("Location: ../signup.php?signup=success");

        $pdo = null;
        $stmt = null;



        die();
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
} else {
    header("Location: ../signup.php");
    die();
}
