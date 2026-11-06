<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST["email"];
    $pwd = $_POST["pwd"];


    try {

        require_once "dbh.inc.php";
        require_once "login_includes/login_model.inc.php";
        require_once "login_includes/login_contr.inc.php";


        // ERRORS

        $errors = [];

        if (is_input_empty($email, $pwd)) {
            $errors["empty_input"] = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Login Failed",
                text: "Input all fields!"
            });
            </script>';
        }

        $result = get_user($pdo, $email);

        if (is_email_wrong($result)) {
            $errors["login_incorrect"] = '
         <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Incorrect Email!"
            });
            </script>';
        }
        if (!is_email_wrong($result) && is_password_wrong($pwd, $result["pwd"])) {
            $errors["login_incorrect"] = '
         <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Incorrect Password!"
            });
            </script>';
        }

        require_once "config_session.inc.php";

        if ($errors) {
            $_SESSION["errors_login"] = $errors;

            header("Location: ../login.php");
        }


        $newSessionId = session_create_id();
        $sessionId = $newSessionId . "_" . $result["id"];
        session_id($sessionId);

        $_SESSION["user_id"] = $result["id"];
        $_SESSION["user_firstname"] = htmlspecialchars($result["firstname"]);
        $_SESSION["user_middlename"] = htmlspecialchars($result["middlename"]);
        $_SESSION["user_lastname"] = htmlspecialchars($result["lastname"]);
        $_SESSION["user_email"] = htmlspecialchars($result["email"]);
        $_SESSION["user_file_name"] = htmlspecialchars($result["file_name"]);

        $_SESSION["last_regeneration"] = time();

        header("Location: ../login.php?login=success");

        $pdo = null;
        $stmt = null;

        die();
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
} else {
    header("Location: ../login.php");
    die();
}
