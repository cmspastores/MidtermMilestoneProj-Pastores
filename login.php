<?php
$errorMessages = [
    'invalid_credentials' => 'The email address or password is incorrect.',
];
$error = $_GET['error'] ?? '';
$errorMessage = is_string($error) ? ($errorMessages[$error] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f7f5ef">
    <title>Sign in | RecipeBook</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #20251f;
            background: #f7f5ef;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(ellipse at 15% 10%, rgba(230, 237, 212, 0.75), transparent 35%),
                #f7f5ef;
        }

        .login-card {
            width: min(100%, 440px);
            padding: 48px;
            border: 1px solid #e9e7df;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 20px 60px rgba(39, 48, 35, 0.08);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #31533b;
            font-size: 15px;
            font-weight: 750;
            letter-spacing: -0.02em;
            text-decoration: none;
        }

        .brand-mark {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 11px;
            background: #e9f0df;
            font-size: 18px;
        }

        h1 {
            margin: 36px 0 8px;
            font-size: clamp(28px, 6vw, 34px);
            letter-spacing: -0.045em;
        }

        .intro {
            margin: 0 0 30px;
            color: #6c7169;
            font-size: 15px;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 19px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 650;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid #dcded6;
            border-radius: 9px;
            outline: none;
            background: #fff;
            color: #20251f;
            font: inherit;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            color: #9a9e96;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #527d4e;
            box-shadow: 0 0 0 3px rgba(82, 125, 78, 0.15);
        }

        .message {
            margin: 0 0 20px;
            padding: 12px 14px;
            border: 1px solid #efcfca;
            border-radius: 9px;
            background: #fff5f3;
            color: #8c3429;
            font-size: 14px;
            line-height: 1.5;
        }

        button {
            width: 100%;
            min-height: 49px;
            border: 0;
            border-radius: 9px;
            background: #31533b;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background 150ms ease, transform 150ms ease;
        }

        button:hover {
            background: #274631;
        }

        button:active {
            transform: translateY(1px);
        }

        button:focus-visible,
        .brand:focus-visible,
        .signup a:focus-visible {
            outline: 3px solid #9ab27f;
            outline-offset: 3px;
        }

        .signup {
            margin: 25px 0 0;
            color: #6c7169;
            font-size: 14px;
            text-align: center;
        }

        .signup a {
            color: #31533b;
            font-weight: 700;
            text-decoration: none;
        }

        .signup a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 34px 25px;
            }
        }
    </style>
</head>
<body>
    <main class="login-card">
        <a class="brand" href="index.php" aria-label="RecipeBook home">
            <span class="brand-mark" aria-hidden="true">🥬</span>
            <span>RecipeBook</span>
        </a>

        <h1>Welcome back</h1>
        <p class="intro">Sign in to save your favorite recipes and pick up where you left off.</p>
        <?php if ($errorMessage !== null): ?>
            <p class="message" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="UserController.php" method="post">
            <div class="field">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" name="action" value="Login">Sign in</button>
        </form>

        <p class="signup">New to RecipeBook? <a href="Register.php">Create an account</a></p>
    </main>
</body>
</html>
