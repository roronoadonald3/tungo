# TUNGO - Application de gestion financière

## Installation et configuration

### 1. Prérequis

- **PHP 8.0 ou supérieur**: Assurez-vous que PHP est installé sur votre système et que la commande `php` est accessible depuis votre terminal.
- **PostgreSQL**: Vous devez avoir une instance de PostgreSQL en cours d'exécution.

### 2. Configuration de PHP

1.  **Télécharger PHP**: Si vous n'avez pas PHP, téléchargez-le depuis le site officiel : [https://www.php.net/downloads.php](https://www.php.net/downloads.php). Pour Windows, il est recommandé de télécharger la version "Thread Safe".

2.  **Extraire PHP**: Extrayez les fichiers dans un dossier, par exemple `C:\php`.

3.  **Configurer `php.ini`**:
    - Dans le dossier `C:\php`, trouvez le fichier `php.ini-development` et renommez-le en `php.ini`.
    - Ouvrez `php.ini` et décommentez la ligne suivante en supprimant le point-virgule (`;`) au début :
      ```ini
      ;extension=pdo_pgsql
      ```
      devient :
      ```ini
      extension=pdo_pgsql
      ```
    - Décommentez également la ligne `extension_dir` et assurez-vous qu'elle pointe vers le dossier `ext` de votre installation PHP :
        ```ini
        extension_dir = "ext"
        ```

4.  **Ajouter PHP à votre PATH**: Pour pouvoir exécuter PHP depuis n'importe quel emplacement, ajoutez `C:\php` à votre variable d'environnement PATH.

### 3. Configuration de la base de données

1.  **Créer la base de données**: Créez une base de données PostgreSQL nommée `tungo_db` et un utilisateur `tungo_user` avec un mot de passe.

2.  **Mettre à jour les identifiants**: Ouvrez le fichier `includes/db.php` et mettez à jour les informations de connexion à la base de données :
    ```php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'tungo_db');
    define('DB_USER', 'tungo_user');
    define('DB_PASS', 'votre_mot_de_passe_securise'); // Remplacez par votre mot de passe
    ```

### 4. Initialisation de la base de données

1.  **Ouvrir un terminal**: Ouvrez un terminal dans le répertoire de votre projet.

2.  **Exécuter le script d'initialisation**: Exécutez la commande suivante pour créer les tables de la base de données :
    ```bash
    php init_db.php
    ```

### 5. Lancer l'application

Pour lancer l'application, vous pouvez utiliser le serveur web intégré de PHP. Exécutez la commande suivante à la racine de votre projet :

```bash
php -S localhost:8000
```

Ouvrez ensuite votre navigateur et accédez à `http://localhost:8000`.
