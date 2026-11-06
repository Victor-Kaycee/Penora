<?php

declare(strict_types=1);


function signupInput()
{

    echo '<div class="input-names">';
    if (isset($_SESSION["signup_data"]["firstname"])) {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="text" name="firstname" placeholder="Firstname" value="' . $_SESSION["signup_data"]["firstname"] . '">';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="text" name="firstname" placeholder="Firstname">';
        echo '</div>';
        echo '</div>';
    }

    if (isset($_SESSION["signup_data"]["middlename"])) {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="text" name="middlename" placeholder="Middlename" value="' . $_SESSION["signup_data"]["middlename"] . '">';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="text" name="middlename" placeholder="Middlename">';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';

    if (isset($_SESSION["signup_data"]["lastname"])) {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="text" name="lastname" placeholder="Lastname" value="' . $_SESSION["signup_data"]["lastname"] . '">';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="text" name="lastname" placeholder="Lastname">';
        echo '</div>';
        echo '</div>';
    }


    if (isset($_SESSION["signup_data"]["email"]) && !isset($_SESSION["errors_signup"]["email_used"]) && !isset($_SESSION["errors_signup"]["invalid_email"])) {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="email" name="email" placeholder="Email" value="' . $_SESSION["signup_data"]["email"] . '">';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="input-group">';
        echo '<div class="input-box">';
        echo '<input type="email" name="email" placeholder="Email">';
        echo '</div>';
        echo '</div>';
    }

    echo '<div class="input-group">';
    echo '<div class="input-box">';
    echo '<input type="password" name="pwd" placeholder="Password" id="password">';
    echo '<i class="ri-eye-fill eye" id="togglePassword"></i>';
    echo '</div>';
    echo '</div>';
}







function check_signup_errors()
{
    if (isset($_SESSION["errors_signup"])) {
        $errors = $_SESSION["errors_signup"];

        echo "<br>";

        foreach ($errors as $error) {
            echo "<p class='form-error'>" . $error . "</p>";
        }

        unset($_SESSION["errors_signup"]);
    } else if (isset($_GET["signup"]) && $_GET["signup"] === "success") {
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        Swal.fire({
        title: "Signup Successful!",
        icon: "success",
        draggable: false
        }).then(() => {
        window.location.href = "login.php";
        });
        </script>';
    }
}
