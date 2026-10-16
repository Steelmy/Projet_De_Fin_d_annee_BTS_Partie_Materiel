<?php
session_start();
require_once __DIR__ . '/php/core/EnvLoader.php';
require_once __DIR__ . '/php/core/Database.php';

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    EnvLoader::load($envPath);
}

$error = "";

// Juste après le début du PHP dans admin_login.php
// Heures de remise à zéro de la base (service « reset » de docker-compose), affichées aux visiteurs.
$resetTimes = preg_split('/\s+/', trim(EnvLoader::get('RESET_TIMES')), -1, PREG_SPLIT_NO_EMPTY);

$message = "";
if (isset($_GET['msg']) && $_GET['msg'] == 'deconnecte') {
    $message = "Vous avez été déconnecté avec succès.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getConnection();

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // On cherche l'admin par son nom
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe haché
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // ✅ CONNEXION RÉUSSIE
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];
            $_SESSION['last_activity'] = time();
            
            header("Location: index.php");
            exit;
        } else {
            // ❌ ÉCHEC
            $error = "Identifiants incorrects.";
        }
    } catch (Exception $e) {
        $error = "Erreur de connexion à la base de données.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icone.png">
    <title>Connexion Administration</title>
    <style>
        body { 
            background: #0f172a; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0;
            padding: 16px;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        .login-card { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); 
            width: 100%;
            max-width: 380px; 
        }
        @media (max-width: 480px) { .login-card { padding: 28px 22px; } }
        h2 { color: #b6a071; text-align: center; margin-top: 0; }
        input { 
            width: 100%; 
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid #e2e8f0; 
            border-radius: 10px; 
            box-sizing: border-box; 
            font-size: 16px;
        }
        button { 
            width: 100%; 
            padding: 14px; 
            background: #b6a071; 
            border: none; 
            color: white; 
            border-radius: 10px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 16px;
            transition: background 0.3s;
        }
        button:hover { background: #9d8a5f; }
        .hint { color: #64748b; font-size: 14px; margin: 10px 0 0; }
        .hint + input { margin-top: 4px; }
        .reset-notice { text-align: center; margin-top: 18px; font-size: 13px; }
        .error { 
            color: #ef4444; 
            background: #fee2e2;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px; 
            text-align: center; 
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>🔒 Administration</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($message): ?>
    <div style="color: #10b981; background: #ecfdf5; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

        <form method="POST">
            <p class="hint">Identifiant : admin</p>
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <p class="hint">Mot de passe : base105</p>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>

        <?php if ($resetTimes): ?>
            <p class="hint reset-notice">
                Base de démonstration : toutes les modifications sont effacées chaque jour à
                <?php echo htmlspecialchars(implode(' et ', $resetTimes)); ?>.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
