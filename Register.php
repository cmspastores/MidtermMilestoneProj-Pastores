<?php
$errorMessages = [
    'invalid_input' => 'Enter a valid email and username, and confirm a matching password between 8 and 72 bytes.',
    'account_exists' => 'That email address or username is already registered.',
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
    <title>Create an account | RecipeBook</title>
    <style>
        :root {
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

        .card {
            width: min(100%, 440px);
            padding: 44px 48px;
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
            margin: 30px 0 8px;
            font-size: clamp(28px, 6vw, 34px);
            letter-spacing: -0.045em;
        }

        .intro {
            margin: 0 0 25px;
            color: #6c7169;
            font-size: 15px;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 17px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 650;
        }

        input {
            width: 100%;
            min-height: 47px;
            padding: 11px 14px;
            border: 1px solid #dcded6;
            border-radius: 9px;
            outline: none;
            background: #fff;
            color: #20251f;
            font: inherit;
        }

        input:focus {
            border-color: #527d4e;
            box-shadow: 0 0 0 3px rgba(82, 125, 78, 0.15);
        }

        .message {
            margin: 0 0 19px;
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
        }

        button:hover {
            background: #274631;
        }

        button:focus-visible,
        .brand:focus-visible,
        .signin a:focus-visible {
            outline: 3px solid #9ab27f;
            outline-offset: 3px;
        }

        .signin {
            margin: 23px 0 0;
            color: #6c7169;
            font-size: 14px;
            text-align: center;
        }

        .signin a {
            color: #31533b;
            font-weight: 700;
            text-decoration: none;
        }

        .signin a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card {
                padding: 34px 25px;
            }
        }
    </style>
</head>
<body>
    <main class="card">
        <a class="brand" href="index.php" aria-label="RecipeBook home">
            <span class="brand-mark" aria-hidden="true">🥬</span>
            <span>RecipeBook</span>
        </a>

        <h1>Create your account</h1>
        <p class="intro">Join RecipeBook to keep all your favorite recipes in one place.</p>
        <?php if ($errorMessage !== null): ?>
            <p class="message" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="UserController.php" method="post">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" autocomplete="username" required>
            </div>

            <div class="field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    minlength="8"
                    maxlength="72"
                    required
                >
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    minlength="8"
                    maxlength="72"
                    required
                >
            </div>

            <button type="submit" name="action" value="Register">Create account</button>
        </form>

        <p class="signin">Already have an account? <a href="login.php">Sign in</a></p>
    </main>
</body>
</html>
