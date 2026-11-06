<?php
require_once "includes/config_session.inc.php";
require_once "includes/login_includes/login_view.inc.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>

    <main class="main">

        <!-- <section class="login">
            <div class="login-grid">
                <div class="login-background">
                </div>
                <div class="login-form container">
                    <a href="index.php" class="form-logo">
                        <img src="assets/img/logo-black.png" alt="">
                    </a>

                    <form action="includes/login.inc.php" class="input-fields" method="post">
                        <div class="input-div">
                            <input type="text" name="email" placeholder="Input Email">
                        </div>
                        <div class="input-div">
                            <input type="password" name="pwd" placeholder="Input Password">
                        </div>

                        <div class="forgot">
                            <a href="f-password.php">Forgot Password?</a>
                        </div>

                        <button value="submit" class="sign-in">SIGN IN</button>

                        <div class="sign-up">
                            <span>New to Blogar ?  <a href="signup.php">Sign Up!</a></span>
                        </div>
                    </form>

                    
                    
                </div>
                
            </div>
        </section> -->


        <div class="container">

            <!-- LEFT -->

            <form class="left" action="includes/login.inc.php" method="post">

                <h1>Hello, <span>Welcome Back!</span></h1>

                <p class="subtitle">
                    We're happy to see you again. Let's stay ahead of the game.
                </p>

                <div class="social-login">

                    <button class="social-btn">
                        <i class="ri-google-fill"></i>
                        Google
                    </button>

                    <button class="social-btn">
                        <i class="ri-apple-fill"></i>
                        Apple
                    </button>

                </div>

                <div class="divider">Or Continue With</div>

                <div class="input-group">
                    <label>Email</label>

                    <div class="input-box">
                        <input type="email" name="email" placeholder="Enter your email">
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>

                    <div class="input-box">
                        <input type="password" name="pwd" id="password" placeholder="Enter password">

                        <i class="ri-eye-fill eye" id="togglePassword"></i>
                    </div>
                </div>

                <div class="forgot">
                    <a href="#">Forgot Password?</a>
                </div>

                <button value="submit" class="login-btn">LOGIN</button>

                <div class="signup">
                    Don't have an account?
                    <a href="signup.php">Sign up for free</a>
                </div>
                <?php
                check_login_errors();
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
    <script src="assets/js/login.js"></script>
</body>

</html>