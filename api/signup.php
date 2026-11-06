<?php
require_once "includes/config_session.inc.php";
require_once "includes/signup_includes/signup_view.inc.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | Signup</title>
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/signup.css">
</head>

<body>
    <main class="main">

        <div class="container">

            <!-- LEFT -->

            <form class="left" action="includes/signup.inc.php" method="post" enctype="multipart/form-data">

                <h1>Get Started</h1>
                <p class="subtitle">
                    We're happy to see you join us. Let's stay ahead of the game.
                </p>

                <?php
                signupInput();
                ?>

                <input type="file" name="profileimg" class="input-file">

                <div class="agree">
                    <input type="checkbox">
                    <span>I have agree to the <a href="#">Terms</a> and <a href="#">Privacy Policy</a></span>
                </div>

                <button value="submit" class="login-btn">LOGIN</button>

                <div class="signup">
                    Already have an account?
                    <a href="login.php">Log in now!</a>
                </div>
                <?php
                check_signup_errors();
                ?>
            </form>


                        <!-- RIGHT -->

            <div class="right">

                <!-- Slides -->
                <div class="slide active">
                    <img src="assets/img/login-slide-1.jpg" alt="" class="right-img">

                    <div class="overlay">
                    </div>
                </div>

                <div class="slide">
                    <img src="assets/img/login-slide-2.jpg" alt="" class="right-img">

                    <div class="overlay">
                    </div>
                </div>

                <div class="slide">
                    <img src="assets/img/login-slide-3.jpg" alt="" class="right-img">

                    <div class="overlay">
                    </div>
                </div>

                <div class="slide">
                    <img src="assets/img/login-slide-4.jpg" alt="" class="right-img">

                    <div class="overlay">
                    </div>
                </div>

                <!-- Slider Navigation -->
                <div class="slider">
                    <div class="line active"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>

            </div>

            

        </div>


    </main>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="sweetalert2.min.js"></script>
    <script src="assets/js/signup.js"></script>
</body>

</html>