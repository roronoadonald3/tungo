<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

require_once 'includes/auth.php';
requireGuest();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $loginData = [
            'identifier' => sanitizeInput($_POST['identifier'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];

        if (empty($loginData['identifier'])) {
            $errors[] = 'Email ou téléphone requis';
        }

        if (empty($loginData['password'])) {
            $errors[] = 'Mot de passe requis';
        }

        if (empty($errors)) {
            $result = authenticateUser($loginData);
            if ($result['success']) {
                $success = true;
                $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
                exit();
            } else {
                $errors[] = $result['message'];
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Erreur lors de la connexion: ' . $e->getMessage();
    }
}

$sessionExpired = isset($_GET['expired']) && $_GET['expired'] == '1';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Connexion à TUNGO</h1>
                <p>Accédez à votre tableau de bord et gérez vos finances</p>
            </div>

            <?php if ($sessionExpired): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Votre session a expiré. Veuillez vous reconnecter.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="login-form">
                <div class="form-group">
                    <label for="identifier">Email ou téléphone *</label>
                    <input type="text" id="identifier" name="identifier" required 
                           value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                           placeholder="votre@email.com ou +225 0700000000">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required 
                               placeholder="Votre mot de passe">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="forgot-password">
                        <a href="reset-password.php">Mot de passe oublié ?</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkmark"></span>
                        Se souvenir de moi
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <div class="auth-footer">
                <p>Pas encore de compte ? <a href="signup.php">S'inscrire</a></p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
