<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

require_once 'includes/auth.php';
require_once 'includes/db.php';

$db = getDB();
$userId = $_SESSION['user_id'];

// Récupérer les données de l'utilisateur
$userData = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Traitement du formulaire de mise à jour
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $nom = sanitizeInput($_POST['nom']);
                $email = sanitizeInput($_POST['email']);
                $telephone = sanitizeInput($_POST['telephone']);
                $profession = sanitizeInput($_POST['profession']);
                $profil = sanitizeInput($_POST['profil']);
                
                // Validation
                $errors = [];
                
                if (empty($nom)) {
                    $errors[] = 'Le nom est requis';
                }
                
                if (!empty($email) && !validateEmail($email)) {
                    $errors[] = 'L\'email n\'est pas valide';
                }
                
                if (!empty($telephone) && !validatePhone($telephone)) {
                    $errors[] = 'Le numéro de téléphone n\'est pas valide';
                }
                
                // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
                if (!empty($email)) {
                    $existingUser = $db->fetchOne(
                        "SELECT id FROM users WHERE email = ? AND id != ?",
                        [$email, $userId]
                    );
                    if ($existingUser) {
                        $errors[] = 'Cet email est déjà utilisé';
                    }
                }
                
                if (empty($errors)) {
                    try {
                        $db->update('users', [
                            'nom' => $nom,
                            'email' => $email,
                            'telephone' => $telephone,
                            'profession' => $profession,
                            'profil' => $profil,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$userId]);
                        
                        $message = 'Profil mis à jour avec succès !';
                        $messageType = 'success';
                        
                        // Mettre à jour les données affichées
                        $userData = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
                        
                    } catch (Exception $e) {
                        $message = 'Erreur lors de la mise à jour du profil';
                        $messageType = 'error';
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                // Vérifier l'ancien mot de passe
                if (!verifyPassword($currentPassword, $userData['password'])) {
                    $message = 'L\'ancien mot de passe est incorrect';
                    $messageType = 'error';
                } elseif (strlen($newPassword) < 6) {
                    $message = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
                    $messageType = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'Les mots de passe ne correspondent pas';
                    $messageType = 'error';
                } else {
                    try {
                        $hashedPassword = hashPassword($newPassword);
                        $db->update('users', [
                            'password' => $hashedPassword,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$userId]);
                        
                        $message = 'Mot de passe modifié avec succès !';
                        $messageType = 'success';
                        
                    } catch (Exception $e) {
                        $message = 'Erreur lors du changement de mot de passe';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete_account':
                $confirmPassword = $_POST['confirm_password'];
                
                if (!verifyPassword($confirmPassword, $userData['password'])) {
                    $message = 'Mot de passe incorrect';
                    $messageType = 'error';
                } else {
                    try {
                        // Supprimer toutes les données de l'utilisateur
                        $db->delete('repartitions', 'user_id = ?', [$userId]);
                        $db->delete('revenus', 'user_id = ?', [$userId]);
                        $db->delete('objectifs', 'user_id = ?', [$userId]);
                        $db->delete('simulations', 'user_id = ?', [$userId]);
                        $db->delete('users', 'id = ?', [$userId]);
                        
                        // Détruire la session et rediriger
                        session_destroy();
                        header('Location: index.php?message=account_deleted');
                        exit;
                        
                    } catch (Exception $e) {
                        $message = 'Erreur lors de la suppression du compte';
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Récupérer les statistiques de l'utilisateur
$stats = [
    'repartitions' => $db->fetchOne("SELECT COUNT(*) as count FROM repartitions WHERE user_id = ?", [$userId])['count'],
    'revenus' => $db->fetchOne("SELECT COUNT(*) as count FROM revenus WHERE user_id = ?", [$userId])['count'],
    'objectifs' => $db->fetchOne("SELECT COUNT(*) as count FROM objectifs WHERE user_id = ?", [$userId])['count'],
    'simulations' => $db->fetchOne("SELECT COUNT(*) as count FROM simulations WHERE user_id = ?", [$userId])['count']
];

$totalRevenus = $db->fetchOne("SELECT SUM(montant) as total FROM revenus WHERE user_id = ?", [$userId])['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profil.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user"></i> Mon profil</h1>
                <p>Gérez vos informations personnelles et vos paramètres</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>

            <div class="profil-container">
                <!-- Informations du profil -->
                <div class="profil-section">
                    <h2>Informations personnelles</h2>
                    
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $stats['repartitions'] ?></h3>
                                <p>Répartitions</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $stats['revenus'] ?></h3>
                                <p>Revenus enregistrés</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-target"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $stats['objectifs'] ?></h3>
                                <p>Objectifs définis</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $stats['simulations'] ?></h3>
                                <p>Simulations</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="profil-form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="nom">Nom complet *</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($userData['nom']) ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($userData['telephone'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="profession">Profession</label>
                                <select id="profession" name="profession">
                                    <option value="">Sélectionner une profession</option>
                                    <option value="salarie" <?= ($userData['profession'] === 'salarie') ? 'selected' : '' ?>>Salarié</option>
                                    <option value="independant" <?= ($userData['profession'] === 'independant') ? 'selected' : '' ?>>Indépendant</option>
                                    <option value="etudiant" <?= ($userData['profession'] === 'etudiant') ? 'selected' : '' ?>>Étudiant</option>
                                    <option value="retraite" <?= ($userData['profession'] === 'retraite') ? 'selected' : '' ?>>Retraité</option>
                                    <option value="chomeur" <?= ($userData['profession'] === 'chomeur') ? 'selected' : '' ?>>Chômeur</option>
                                    <option value="autre" <?= ($userData['profession'] === 'autre') ? 'selected' : '' ?>>Autre</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="profil">Profil financier</label>
                                <select id="profil" name="profil">
                                    <option value="regulier" <?= ($userData['profil'] === 'regulier') ? 'selected' : '' ?>>Revenus réguliers</option>
                                    <option value="irregulier" <?= ($userData['profil'] === 'irregulier') ? 'selected' : '' ?>>Revenus irréguliers</option>
                                    <option value="mixte" <?= ($userData['profil'] === 'mixte') ? 'selected' : '' ?>>Revenus mixtes</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Sauvegarder les modifications
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Changement de mot de passe -->
                <div class="profil-section">
                    <h2>Changer le mot de passe</h2>
                    
                    <form method="POST" class="password-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe *</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <small>Minimum 6 caractères</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirmer le mot de passe *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-key"></i> Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Suppression du compte -->
                <div class="profil-section danger-zone">
                    <h2>Zone dangereuse</h2>
                    <div class="danger-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>La suppression de votre compte est irréversible. Toutes vos données seront définitivement supprimées.</p>
                    </div>
                    
                    <form method="POST" class="delete-form" onsubmit="return confirmDelete()">
                        <input type="hidden" name="action" value="delete_account">
                        
                        <div class="form-group">
                            <label for="delete_confirm_password">Confirmer avec votre mot de passe *</label>
                            <input type="password" id="delete_confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Supprimer mon compte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include_once 'includes/footer.php'; ?>

    <script src="assets/js/profil.js"></script>
</body>
</html> 