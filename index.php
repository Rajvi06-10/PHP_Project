<?php
session_start();

// If user is already authenticated, skip splash and go directly to home
if (isset($_SESSION['user_id'])) {
    header("Location: pages/home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swipe Nest</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/globals.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            background-color: var(--color-bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .splash-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0.95);
            animation: splashAnimation 2s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }

        .splash-logo {
            width: 80px;
            height: 80px;
            margin-bottom: var(--spacing-4);
        }

        .splash-title {
            font-family: var(--font-family-heading);
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-text-primary);
            letter-spacing: -0.02em;
        }

        @keyframes splashAnimation {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }
            20% {
                opacity: 1;
                transform: scale(1);
            }
            80% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body>
    <div class="splash-container">
        <img src="assets/images/logo.svg" alt="Swipe Nest Logo" class="splash-logo">
        <div class="splash-title">Swipe Nest</div>
    </div>

    <script>
        // Exactly 2 seconds (2000ms) before redirecting to Login
        setTimeout(() => {
            window.location.href = 'pages/auth.php';
        }, 2000);
    </script>
</body>
</html>
