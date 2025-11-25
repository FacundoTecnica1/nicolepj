<?php
// inicia la sesión para poder acceder a los datos de sesión y destruirlos
session_start();

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// destruye la sesión en el servidor
session_destroy();

header("Location: ../index.php"); 
exit();
?>