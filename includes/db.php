<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Configuration de la base de données PostgreSQL
 * TUNGO - Application de gestion financière
 */

// Essayer de charger dynamiquement l'extension pdo_pgsql
if (!extension_loaded('pdo_pgsql')) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        //@dl('pdo_pgsql.dll');
    } else {
        @dl('pdo_pgsql.so');
    }
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'tungo_db');
define('DB_USER', 'tungo_user');
define('DB_PASS', 'votre_mot_de_passe_securise');
define('DB_PORT', '5432');
define('DB_CHARSET', 'utf8');

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";options='--client_encoding=" . DB_CHARSET . "'";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            throw new Exception("Impossible de se connecter à la base de données");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur de requête SQL: " . $e->getMessage());
            throw new Exception("Erreur lors de l'exécution de la requête");
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders) RETURNING id";
        
        $stmt = $this->query($sql, $data);
        return $stmt->fetchColumn();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "$column = :$column";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Fonction utilitaire pour obtenir une instance de la base de données
function getDB() {
    return Database::getInstance();
}

// Script de création des tables (à exécuter une seule fois)
function createTables() {
    $db = getDB();
    
    $tables = [
        // Table des utilisateurs
        "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            telephone VARCHAR(20),
            password_hash VARCHAR(255) NOT NULL,
            profession VARCHAR(100),
            profil_type VARCHAR(20) DEFAULT 'regulier',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Table des revenus
        "CREATE TABLE IF NOT EXISTS revenus (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            montant DECIMAL(12,2) NOT NULL,
            type_revenu VARCHAR(50) NOT NULL,
            date_revenu DATE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Table des objectifs
        "CREATE TABLE IF NOT EXISTS objectifs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            nom VARCHAR(100) NOT NULL,
            montant_cible DECIMAL(12,2),
            pourcentage_cible DECIMAL(5,2),
            montant_actuel DECIMAL(12,2) DEFAULT 0,
            type_objectif VARCHAR(50) NOT NULL,
            date_limite DATE,
            statut VARCHAR(20) DEFAULT 'actif',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Table des répartitions
        "CREATE TABLE IF NOT EXISTS repartitions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            logement DECIMAL(5,2) DEFAULT 30,
            alimentation DECIMAL(5,2) DEFAULT 25,
            transport DECIMAL(5,2) DEFAULT 15,
            sante DECIMAL(5,2) DEFAULT 10,
            loisirs DECIMAL(5,2) DEFAULT 10,
            epargne DECIMAL(5,2) DEFAULT 10,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Table des simulations
        "CREATE TABLE IF NOT EXISTS simulations (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            revenu DECIMAL(12,2) NOT NULL,
            profil_type VARCHAR(20) NOT NULL,
            mode_repartition VARCHAR(20) NOT NULL,
            repartition_data JSONB NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Table des conseils
        "CREATE TABLE IF NOT EXISTS conseils (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            type_conseil VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            priorite INTEGER DEFAULT 1,
            lu BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // Table des logs
        "CREATE TABLE IF NOT EXISTS logs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    try {
        $db->beginTransaction();
        
        foreach ($tables as $sql) {
            $db->query($sql);
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Erreur lors de la création des tables: " . $e->getMessage());
        return false;
    }
}

// Fonction pour nettoyer les données
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction pour valider l'email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fonction pour valider le téléphone
function validatePhone($phone) {
    // Format international pour l'Afrique de l'Ouest
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return preg_match('/^(\+225|225)?[0-9]{8,10}$/', $phone);
}

// Fonction pour hasher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Fonction pour vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour générer un token sécurisé
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Fonction pour formater les montants
function formatCurrency($amount, $currency = 'XOF') {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

// Fonction pour calculer l'âge
function calculateAge($birthDate) {
    $date = new DateTime($birthDate);
    $now = new DateTime();
    $interval = $now->diff($date);
    return $interval->y;
}

// Fonction pour obtenir le nom du mois en français
function getMonthName($month) {
    $months = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];
    return $months[$month] ?? '';
}

// Fonction pour logger les actions
function logAction($userId, $action, $details = '') {
    $db = getDB();
    $sql = "INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $db->query($sql, [$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}
?>
