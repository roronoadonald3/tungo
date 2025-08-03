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
        $userData = [
            'nom' => sanitizeInput($_POST['nom'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'telephone' => sanitizeInput($_POST['telephone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'profession' => sanitizeInput($_POST['profession'] ?? ''),
            'profil_type' => sanitizeInput($_POST['profil_type'] ?? 'regulier')
        ];

        if (empty($userData['nom'])) {
            $errors[] = 'Le nom est requis';
        }

        if (empty($userData['email']) || !validateEmail($userData['email'])) {
            $errors[] = 'Email invalide';
        }

        if (empty($userData['telephone']) || !validatePhone($userData['telephone'])) {
            $errors[] = 'Numéro de téléphone invalide';
        }

        if (strlen($userData['password']) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        if ($userData['password'] !== $userData['password_confirm']) {
            $errors[] = 'Les mots de passe ne correspondent pas';
        }

        if (empty($userData['profession'])) {
            $errors[] = 'Veuillez sélectionner votre profession';
        }

        if (!isset($_POST['terms'])) {
            $errors[] = 'Vous devez accepter les conditions d\'utilisation.';
        }

        if (empty($errors)) {
            $result = registerUser($userData);

            if ($result['success']) {
                $success = true;
                $redirect = $_GET['redirect'] ?? 'dashboard.php';
                header("Location: $redirect");
                exit();
            } else {
                $errors[] = $result['message'];
            }
        }

    } catch (Exception $e) {
        $errors[] = 'Erreur lors de l\'inscription: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Inscription - TUNGO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 250px;
        }

        .password-input {
            position: relative;
        }

        .password-input input[type="password"] {
            width: 100%;
            padding-right: 40px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }

        /* Case à cocher stylisée */
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            cursor: pointer;
            position: relative;
        }

        .checkbox-label input[type="checkbox"] {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .checkbox-label .checkmark {
            width: 20px;
            height: 20px;
            background-color: #fff;
            border: 2px solid #007bff;
            border-radius: 4px;
            display: inline-block;
            position: relative;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: "";
            position: absolute;
            left: 5px;
            top: 1px;
            width: 6px;
            height: 12px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background-color: #007bff;
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Créer votre compte TUNGO</h1>
            <p>Rejoignez des milliers d'utilisateurs qui gèrent intelligemment leurs finances</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" id="signup-form">
            <div class="form-group">
                <label for="nom">Nom complet *</label>
                <input type="text" id="nom" name="nom" required 
                       value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                       placeholder="Votre nom complet">
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="votre@email.com">
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone *</label>
                <input type="tel" id="telephone" name="telephone" required 
                       value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>"
                       placeholder="+225 0700000000">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required 
                               placeholder="Minimum 8 caractères">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe *</label>
                    <div class="password-input">
                        <input type="password" id="password_confirm" name="password_confirm" required 
                               placeholder="Répétez votre mot de passe">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="profession">Profession *</label>
                <select id="profession" name="profession" required>
                    <option value="">Sélectionnez votre profession</option>
                    <option value="salarie" <?php echo ($_POST['profession'] ?? '') === 'salarie' ? 'selected' : ''; ?>>Salarié</option>
                    <option value="freelance" <?php echo ($_POST['profession'] ?? '') === 'freelance' ? 'selected' : ''; ?>>Freelance</option>
                    <option value="commercant" <?php echo ($_POST['profession'] ?? '') === 'commercant' ? 'selected' : ''; ?>>Commerçant</option>
                    <option value="entrepreneur" <?php echo ($_POST['profession'] ?? '') === 'entrepreneur' ? 'selected' : ''; ?>>Entrepreneur</option>
                    <option value="etudiant" <?php echo ($_POST['profession'] ?? '') === 'etudiant' ? 'selected' : ''; ?>>Étudiant</option>
                    <option value="retraite" <?php echo ($_POST['profession'] ?? '') === 'retraite' ? 'selected' : ''; ?>>Retraité</option>
                    <option value="autre" <?php echo ($_POST['profession'] ?? '') === 'autre' ? 'selected' : ''; ?>>Autre</option>
                </select>
            </div>

            <div class="form-group">
                <label>Profil financier *</label>
                <div class="profile-options">
                    <div class="profile-option">
                        <input type="radio" id="profil_regulier" name="profil_type" value="regulier" 
                               <?php echo ($_POST['profil_type'] ?? 'regulier') === 'regulier' ? 'checked' : ''; ?>>
                        <label for="profil_regulier">
                            <i class="fas fa-calendar-check"></i>
                            <span>Régulier</span>
                            <small>Revenus fixes et prévisibles</small>
                        </label>
                    </div>

                    <div class="profile-option">
                        <input type="radio" id="profil_irregulier" name="profil_type" value="irregulier"
                               <?php echo ($_POST['profil_type'] ?? '') === 'irregulier' ? 'checked' : ''; ?>>
                        <label for="profil_irregulier">
                            <i class="fas fa-chart-line"></i>
                            <span>Irrégulier</span>
                            <small>Revenus variables</small>
                        </label>
                    </div>

                    <div class="profile-option">
                        <input type="radio" id="profil_mixte" name="profil_type" value="mixte"
                               <?php echo ($_POST['profil_type'] ?? '') === 'mixte' ? 'checked' : ''; ?>>
                        <label for="profil_mixte">
                            <i class="fas fa-balance-scale"></i>
                            <span>Mixte</span>
                            <small>Combinaison fixe/variable</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="terms" name="terms" required>
                    <span class="checkmark"></span>
                    J'accepte les <a href="mentions-legales.php" target="_blank">conditions d'utilisation</a>
                    et la <a href="politique-confidentialite.php" target="_blank">politique de confidentialité</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus"></i> Créer mon compte
            </button>
        </form>

        <div class="auth-footer">
            <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script src="assets/js/signup.js"></script>
</body>
</html>
