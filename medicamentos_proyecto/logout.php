<?php
session_start();

// 1. Destruimos absolutamente todas las variables de la sesión en memoria
$_SESSION = array();

// 2. Forzamos la expiración de la cookie de sesión en el navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruimos la sesión físicamente en el servidor de XAMPP
session_destroy();

// 4. Iniciamos una sesión limpia nueva en blanco para eliminar rastros rebeldes
session_start();
session_regenerate_id(true);

// 5. Redirección inmediata a la pantalla de login limpia
header("Location: login.php");
exit();
?>
