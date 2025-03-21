<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = ""; // Initialize variable for login error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $conn->real_escape_string($_POST['username']);
    $inputPassword = md5($conn->real_escape_string($_POST['password'])); // Ensure this matches the hashing method used

    // Check in professors table (admin)
    $sqlProf = "SELECT * FROM professors WHERE username='$inputUsername' AND password='$inputPassword'";
    $resultProf = $conn->query($sqlProf);

    if ($resultProf->num_rows == 1) {
        $row = $resultProf->fetch_assoc();
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = 'professor'; // Set role as professor

        // Set the session to indicate successful login
        $_SESSION['loggedIn'] = true;
        echo json_encode(['status' => 'success', 'role' => 'professor']);
        exit;
    } else {
        // Check in students table
        $sqlStud = "SELECT * FROM students WHERE username='$inputUsername' AND password='$inputPassword'";
        $resultStud = $conn->query($sqlStud);

        if ($resultStud->num_rows == 1) {
            $row = $resultStud->fetch_assoc();
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = 'student'; // Set role as student
            $_SESSION['id'] = $row['id'];

            // Set the session to indicate successful login
            $_SESSION['loggedIn'] = true;
            echo json_encode(['status' => 'success', 'role' => 'student']);
            exit;
        } else {
            // If no match found, return error message
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
            exit;
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SkoolTech</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link rel="icon" href="./dist/img/skooltech-icon.png">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.1/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.1/ScrollTrigger.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            width: 17rem;
            transform: translateX(-50%);
            background-color: red;
            border: 2px solid red;
            color: white;
            padding: 10px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            text-align: center;
        }

        #loadingScreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 10);
            color: white;
            margin: 0 auto;
            display: none;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            z-index: 9999;
            place-items: center; 
            padding: 20rem;
        }

                .pencil {
        display: block;
        width: 10em;
        height: 10em;
        }

.pencil__body1,
.pencil__body2,
.pencil__body3,
.pencil__eraser,
.pencil__eraser-skew,
.pencil__point,
.pencil__rotate,
.pencil__stroke {
  animation-duration: 3s;
  animation-timing-function: linear;
  animation-iteration-count: infinite;
}

.pencil__body1,
.pencil__body2,
.pencil__body3 {
  transform: rotate(-90deg);
}

.pencil__body1 {
  animation-name: pencilBody1;
}

.pencil__body2 {
  animation-name: pencilBody2;
}

.pencil__body3 {
  animation-name: pencilBody3;
}

.pencil__eraser {
  animation-name: pencilEraser;
  transform: rotate(-90deg) translate(49px,0);
}

.pencil__eraser-skew {
  animation-name: pencilEraserSkew;
  animation-timing-function: ease-in-out;
}

.pencil__point {
  animation-name: pencilPoint;
  transform: rotate(-90deg) translate(49px,-30px);
}

.pencil__rotate {
  animation-name: pencilRotate;
}

.pencil__stroke {
  animation-name: pencilStroke;
  transform: translate(100px,100px) rotate(-113deg);
}

/* Animations */
@keyframes pencilBody1 {
  from,
	to {
    stroke-dashoffset: 351.86;
    transform: rotate(-90deg);
  }

  50% {
    stroke-dashoffset: 150.8;
 /* 3/8 of diameter */
    transform: rotate(-225deg);
  }
}

@keyframes pencilBody2 {
  from,
	to {
    stroke-dashoffset: 406.84;
    transform: rotate(-90deg);
  }

  50% {
    stroke-dashoffset: 174.36;
    transform: rotate(-225deg);
  }
}

@keyframes pencilBody3 {
  from,
	to {
    stroke-dashoffset: 296.88;
    transform: rotate(-90deg);
  }

  50% {
    stroke-dashoffset: 127.23;
    transform: rotate(-225deg);
  }
}

@keyframes pencilEraser {
  from,
	to {
    transform: rotate(-45deg) translate(49px,0);
  }

  50% {
    transform: rotate(0deg) translate(49px,0);
  }
}

@keyframes pencilEraserSkew {
  from,
	32.5%,
	67.5%,
	to {
    transform: skewX(0);
  }

  35%,
	65% {
    transform: skewX(-4deg);
  }

  37.5%, 
	62.5% {
    transform: skewX(8deg);
  }

  40%,
	45%,
	50%,
	55%,
	60% {
    transform: skewX(-15deg);
  }

  42.5%,
	47.5%,
	52.5%,
	57.5% {
    transform: skewX(15deg);
  }
}

@keyframes pencilPoint {
  from,
	to {
    transform: rotate(-90deg) translate(49px,-30px);
  }

  50% {
    transform: rotate(-225deg) translate(49px,-30px);
  }
}

@keyframes pencilRotate {
  from {
    transform: translate(100px,100px) rotate(0);
  }

  to {
    transform: translate(100px,100px) rotate(720deg);
  }
}

@keyframes pencilStroke {
  from {
    stroke-dashoffset: 439.82;
    transform: translate(100px,100px) rotate(-113deg);
  }

  50% {
    stroke-dashoffset: 164.93;
    transform: translate(100px,100px) rotate(-113deg);
  }

  75%,
	to {
    stroke-dashoffset: 439.82;
    transform: translate(100px,100px) rotate(112deg);
  }
}
    </style>
</head>
<body>
    <!-- Notification for invalid login -->
    <?php if ($loginError): ?>
        <div class="notification" id="loginError"><?php echo $loginError; ?></div>
    <?php endif; ?>

    <section class="login">
        <div class="login__wrapper">
            <div class="login__left">
                <div class="login__left__wrapper">
                    <div class="login__left--header">
                        <div class="login__left__img">
                            <img src="./dist/img/academix white logo.png" alt="">
                        </div>
                        <div class="login__left__title">
                            <h2>LOG IN</h2>
                        </div>
                    </div>
                    <div class="login__left__form">
                        <form id="loginForm">
                            <label for="username">Username:</label><br>
                            <input type="text" id="username" name="username" required><br>

                            <label for="password">Password:</label><br>
                            <div class="password-container">
                                <input type="password" id="password" name="password" class="password-input" required>
                                <label class="checkbox-container">
                                </label>
                            </div>

                            <div id="errorMessage" style="color: red; font-size: 14px; margin-top: 10px; display: none;"></div>

                            <br>
                            <br>
                            <input type="submit" value="Login" class="btn__login">
                        </form>
                        <div class="login__left__signup">
                            <h3>Don't have an account?</h3>
                            <a href="./signup.php">Sign Up</a>
                        </div>
                    </div>

                    <!-- Loading Screen -->
                    <div id="loadingScreen">
                    <svg xmlns="http://www.w3.org/2000/svg" height="200px" width="200px" viewBox="0 0 200 200" class="pencil">
	<defs>
		<clipPath id="pencil-eraser">
			<rect height="30" width="30" ry="5" rx="5"></rect>
		</clipPath>
	</defs>
	<circle transform="rotate(-113,100,100)" stroke-linecap="round" stroke-dashoffset="439.82" stroke-dasharray="439.82 439.82" stroke-width="2" stroke="black" fill="none" r="70" class="pencil__stroke"></circle>
	<g transform="translate(100,100)" class="pencil__rotate">
		<g fill="none">
			<circle transform="rotate(-90)" stroke-dashoffset="402" stroke-dasharray="402.12 402.12" stroke-width="30" stroke="hsl(223,90%,50%)" r="64" class="pencil__body1"></circle>
			<circle transform="rotate(-90)" stroke-dashoffset="465" stroke-dasharray="464.96 464.96" stroke-width="10" stroke="hsl(223,90%,60%)" r="74" class="pencil__body2"></circle>
			<circle transform="rotate(-90)" stroke-dashoffset="339" stroke-dasharray="339.29 339.29" stroke-width="10" stroke="hsl(223,90%,40%)" r="54" class="pencil__body3"></circle>
		</g>
		<g transform="rotate(-90) translate(49,0)" class="pencil__eraser">
			<g class="pencil__eraser-skew">
				<rect height="30" width="30" ry="5" rx="5" fill="hsl(223,90%,70%)"></rect>
				<rect clip-path="url(#pencil-eraser)" height="30" width="5" fill="hsl(223,90%,60%)"></rect>
				<rect height="20" width="30" fill="hsl(223,10%,90%)"></rect>
				<rect height="20" width="15" fill="hsl(223,10%,70%)"></rect>
				<rect height="20" width="5" fill="hsl(223,10%,80%)"></rect>
				<rect height="2" width="30" y="6" fill="hsla(223,10%,10%,0.2)"></rect>
				<rect height="2" width="30" y="13" fill="hsla(223,10%,10%,0.2)"></rect>
			</g>
		</g>
		<g transform="rotate(-90) translate(49,-30)" class="pencil__point">
			<polygon points="15 0,30 30,0 30" fill="hsl(33,90%,70%)"></polygon>
			<polygon points="15 0,6 30,0 30" fill="hsl(33,90%,50%)"></polygon>
			<polygon points="15 0,20 10,10 10" fill="hsl(223,10%,10%)"></polygon>
		</g>
	</g>
</svg>
                    </div>
                </div>
            </div>
            <div class="login__desc">
                <div class="login__desc__wrapper">
                    <div class="login__desc__info">
                        <div class="login__desc__title">
                            <h1 class="animated-text">Unlock your potential with Academix</h1>
                        </div>
                        <div class="login__desc__text">
                            <p>Join a community of learners and innovators. Academix provides the tools, resources, and support you need to excel in your studies and beyond.</p>
                        </div>
                    </div>
                    <div class="login__desc__img">
                        <img src="./dist/img/login-pic.png" alt="">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
    $(document).ready(function () {
    // Handle form submission using AJAX
    $('#loginForm').on('submit', function (event) {
        event.preventDefault(); // Prevent normal form submission

        // Clear previous error styles and message
        $('#username, #password').css('border', '');
        $('#username, #password').removeClass('shake');
        $('#errorMessage').hide().text('');

        // Send the login data to the server
        $.ajax({
            type: 'POST',
            url: '', // Current file
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    // Show the loading screen only after successful login
                    $('#loadingScreen').show();

                    // Delay redirection
                    setTimeout(function () {
                        if (response.role === 'professor') {
                            window.location.href = 'admin_dashboard.php'; // Redirect to admin dashboard
                        } else {
                            window.location.href = 'user_dashboard.php'; // Redirect to user dashboard
                        }
                    }, 3000); // 3-second delay
                } else {
                    // If login failed, apply error styles to inputs
                    if (response.message.includes("username") || response.message.includes("password")) {
                        $('#username, #password').css('border', '2px solid red'); // Red border
                        $('#username, #password').addClass('shake'); // Shake effect
                        $('#errorMessage').text('Incorrect username or password.').fadeIn(); // Show error message
                    }
                }
            },
            error: function () {
                // Handle errors
                $('#loadingScreen').hide();
                alert('An error occurred. Please try again.');
            }
        });
    });
});

</script>

<style>
    /* Shake animation */
    @keyframes shake {
        0% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        50% { transform: translateX(5px); }
        75% { transform: translateX(-5px); }
        100% { transform: translateX(0); }
    }

    /* Optional: Customize the shake effect */
    .shake {
        animation: shake 0.5s;
    }
</style>

</body>
</html>
