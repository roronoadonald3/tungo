<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

/**
 * Système d'authentification pour TUNGO
 * Vérifie les sessions utilisateur et redirige si nécessaire
 */

require_once 'db.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est connecté et redirige vers login si non
 * @param string $redirectUrl URL de redirection après connexion
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $redirectUrl ?? $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

/**
 * Vérifie si l'utilisateur est connecté et redirige vers dashboard si oui
 */
function requireGuest() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Obtient les informations de l'utilisateur connecté
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDB();
        $sql = "SELECT id, nom, email, telephone, profession, profil_type, created_at FROM users WHERE id = ?";
        return $db->fetchOne($sql, [$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtient l'ID de l'utilisateur connecté
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Connecte un utilisateur
 * @param int $userId ID de l'utilisateur
 * @param string $userName Nom de l'utilisateur
 */
function loginUser($userId, $userName = '') {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['login_time'] = time();
    
    // Régénérer l'ID de session pour la sécurité
    session_regenerate_id(true);
    
    // Logger la connexion
    logAction($userId, 'login', 'Connexion réussie');
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser() {
    if (isLoggedIn()) {
        logAction($_SESSION['user_id'], 'logout', 'Déconnexion');
    }
    
    // Détruire la session
    session_destroy();
    
    // Supprimer le cookie de session
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

/**
 * Vérifie si la session a expiré
 * @param int $timeoutMinutes Minutes avant expiration (défaut: 30)
 * @return bool
 */
function isSessionExpired($timeoutMinutes = 30) {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    $timeout = $timeoutMinutes * 60; // Convertir en secondes
    return (time() - $_SESSION['login_time']) > $timeout;
}

/**
 * Renouvelle la session si elle est sur le point d'expirer
 * @param int $timeoutMinutes Minutes avant expiration
 */
function refreshSession($timeoutMinutes = 30) {
    if (isLoggedIn() && !isSessionExpired($timeoutMinutes)) {
        $_SESSION['login_time'] = time();
    }
}

/**
 * Vérifie les permissions de l'utilisateur
 * @param string $permission Permission requise
 * @return bool
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Pour l'instant, tous les utilisateurs connectés ont les mêmes permissions
    // Cette fonction peut être étendue pour gérer différents rôles
    return true;
}

/**
 * Génère un token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * @param string $token Token à vérifier
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Nettoie et valide les données de connexion
 * @param array $data Données du formulaire
 * @return array Données nettoyées
 */
function sanitizeLoginData($data) {
    $clean = [];
    
    // Email ou téléphone
    $identifier = trim($data['identifier'] ?? '');
    if (validateEmail($identifier)) {
        $clean['email'] = $identifier;
    } elseif (validatePhone($identifier)) {
        $clean['telephone'] = $identifier;
    } else {
        throw new Exception('Email ou téléphone invalide');
    }
    
    // Mot de passe
    $clean['password'] = $data['password'] ?? '';
    if (empty($clean['password'])) {
        throw new Exception('Mot de passe requis');
    }
    
    return $clean;
}

/**
 * Authentifie un utilisateur
 * @param array $loginData Données de connexion
 * @return array Résultat de l'authentification
 */
function authenticateUser($loginData) {
    try {
        $db = getDB();
        
        // Déterminer le champ à utiliser pour la recherche
        $field = isset($loginData['email']) ? 'email' : 'telephone';
        $value = $loginData['email'] ?? $loginData['telephone'];
        
        $sql = "SELECT id, nom, email, password_hash FROM users WHERE $field = ?";
        $user = $db->fetchOne($sql, [$value]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Identifiants incorrects'];
        }
        
        if (!verifyPassword($loginData['password'], $user['password_hash'])) {
            return ['success' => false, 'message' => 'Identifiants incorrects'];
        }
        
        // Connexion réussie
        loginUser($user['id'], $user['nom']);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'nom' => $user['nom'],
                'email' => $user['email']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Erreur d'authentification: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la connexion'];
    }
}

/**
 * Enregistre un nouvel utilisateur
 * @param array $userData Données de l'utilisateur
 * @return array Résultat de l'enregistrement
 */
function registerUser($userData) {
    try {
        $db = getDB();
        
        // Vérifier si l'email existe déjà
        $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$userData['email']]);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
        }
        
        // Vérifier si le téléphone existe déjà (si fourni)
        if (!empty($userData['telephone'])) {
            $existingPhone = $db->fetchOne("SELECT id FROM users WHERE telephone = ?", [$userData['telephone']]);
            if ($existingPhone) {
                return ['success' => false, 'message' => 'Ce numéro de téléphone est déjà utilisé'];
            }
        }
        
        // Hasher le mot de passe
        $userData['password_hash'] = hashPassword($userData['password']);
        unset($userData['password']); // Supprimer le mot de passe en clair
        
        // Insérer l'utilisateur
        $userId = $db->insert('users', $userData);
        
        // Connecter l'utilisateur
        loginUser($userId, $userData['nom']);
        
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Compte créé avec succès'
        ];
        
    } catch (Exception $e) {
        error_log("Erreur d'enregistrement: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la création du compte'];
    }
}

/**
 * Met à jour le profil utilisateur
 * @param int $userId ID de l'utilisateur
 * @param array $userData Nouvelles données
 * @return array Résultat de la mise à jour
 */
function updateUserProfile($userId, $userData) {
    try {
        $db = getDB();
        
        // Vérifier si l'email existe déjà (sauf pour cet utilisateur)
        if (isset($userData['email'])) {
            $existingUser = $db->fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$userData['email'], $userId]
            );
            if ($existingUser) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
            }
        }
        
        // Vérifier si le téléphone existe déjà (sauf pour cet utilisateur)
        if (isset($userData['telephone']) && !empty($userData['telephone'])) {
            $existingPhone = $db->fetchOne(
                "SELECT id FROM users WHERE telephone = ? AND id != ?",
                [$userData['telephone'], $userId]
            );
            if ($existingPhone) {
                return ['success' => false, 'message' => 'Ce numéro de téléphone est déjà utilisé'];
            }
        }
        
        // Mettre à jour le profil
        $userData['updated_at'] = date('Y-m-d H:i:s');
        $affected = $db->update('users', $userData, 'id = ?', [$userId]);
        
        if ($affected > 0) {
            logAction($userId, 'profile_update', 'Profil mis à jour');
            return ['success' => true, 'message' => 'Profil mis à jour avec succès'];
        } else {
            return ['success' => false, 'message' => 'Aucune modification apportée'];
        }
        
    } catch (Exception $e) {
        error_log("Erreur de mise à jour du profil: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }
}

/**
 * Change le mot de passe de l'utilisateur
 * @param int $userId ID de l'utilisateur
 * @param string $currentPassword Mot de passe actuel
 * @param string $newPassword Nouveau mot de passe
 * @return array Résultat du changement
 */
function changePassword($userId, $currentPassword, $newPassword) {
    try {
        $db = getDB();
        
        // Récupérer le hash actuel
        $user = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        // Vérifier le mot de passe actuel
        if (!verifyPassword($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Mot de passe actuel incorrect'];
        }
        
        // Hasher le nouveau mot de passe
        $newHash = hashPassword($newPassword);
        
        // Mettre à jour le mot de passe
        $affected = $db->update('users', 
            ['password_hash' => $newHash, 'updated_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$userId]
        );
        
        if ($affected > 0) {
            logAction($userId, 'password_change', 'Mot de passe changé');
            return ['success' => true, 'message' => 'Mot de passe changé avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors du changement de mot de passe'];
        }
        
    } catch (Exception $e) {
        error_log("Erreur de changement de mot de passe: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors du changement de mot de passe'];
    }
}

/**
 * Supprime le compte utilisateur
 * @param int $userId ID de l'utilisateur
 * @param string $password Mot de passe pour confirmation
 * @return array Résultat de la suppression
 */
function deleteUserAccount($userId, $password) {
    try {
        $db = getDB();
        
        // Vérifier le mot de passe
        $user = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        if (!verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Mot de passe incorrect'];
        }
        
        // Supprimer l'utilisateur (les données associées seront supprimées par CASCADE)
        $affected = $db->delete('users', 'id = ?', [$userId]);
        
        if ($affected > 0) {
            logAction($userId, 'account_deletion', 'Compte supprimé');
            logoutUser();
            return ['success' => true, 'message' => 'Compte supprimé avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
        
    } catch (Exception $e) {
        error_log("Erreur de suppression de compte: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la suppression du compte'];
    }
}

// Vérifier l'expiration de session automatiquement
if (isLoggedIn() && isSessionExpired()) {
    logoutUser();
    header('Location: login.php?expired=1');
    exit();
}

// Renouveler la session si nécessaire
if (isLoggedIn()) {
    refreshSession();
}
?> 