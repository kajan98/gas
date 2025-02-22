<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Login | Gas By Gas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Login to Gas By Gas" name="description" />
    <meta content="Gas By Gas" name="author" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- preloader css -->
    <link rel="stylesheet" href="assets/css/preloader.min.css" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />

    <!-- SweetAlert CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

    <style>
        body {
            background-color: #f8f9fa;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            z-index: -1;
            animation: gradientAnimation 5s ease infinite;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-page {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            animation: fadeIn 1s ease-in-out;
        }

        .card {
            opacity: 0;
            transform: translateY(20px);
            animation: slideIn 0.5s forwards;
            animation-delay: 0.5s;
            width: 450px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-group .btn {
            cursor: pointer;
        }

        h5 {
            font-size: 1.5rem;
        }

        .form-label {
            font-size: 1.1rem;
        }

        .btn {
            transition: background-color 0.3s, transform 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        @media (max-width: 480px) {
            .card {
                width: 90%;
            }
        }
    </style>
</head>

<body>

<div class="auth-page">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%; margin-top: 100px;">
        <div class="text-center mb-4">
            <img src="assets/images/logo.png" alt="Gas By Gas Logo" style="width: 150px; height: 150px;">
        </div>
        <div class="auth-content ">
            <div class="text-center">
                <h5 class="mb-3">Welcome Back!</h5>
                <p class="text-muted">Sign in to continue to Gas By Gas.</p>
            </div>
            <form id="loginForm" method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group auth-pass-inputgroup">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                        <button class="btn btn-light shadow-none ms-0" type="button" id="password-addon">
                            <i class="mdi mdi-eye-outline"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary w-100" type="submit">Log In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/libs/jquery/jquery.min.js"></script>

<script>
    // Password toggle
    $('#password-addon').click(function () {
        const passInput = $('#password');
        if (passInput.attr('type') === 'password') {
            passInput.attr('type', 'text');
        } else {
            passInput.attr('type', 'password');
        }
    });
</script>

</body>
</html>
