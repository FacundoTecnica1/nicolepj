<?php
session_start(); // Inicia la sesión
include 'conexion.php'; // Asegúrate que esta ruta es correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // NOTA IMPORTANTE: Tu formulario de login pide 'Email', por lo que el campo que envías
    // DEBERÍA llamarse 'correo' o 'email'. Para que este código funcione, debes 
    // asegurarte que tu campo HTML de 'Email' tenga el atributo name="correo".
    // Si tu campo se llama name="nombre_usuario" (aunque pida Email), 
    // usa $_POST['nombre_usuario'] y se buscará en correo_electronico.
    
    // Usaremos $_POST['nombre_usuario'] según tu código anterior,
    // pero lo buscaremos en la columna de correo_electronico (para coincidir con el formulario).
    $identificador_ingresado = $conexion->real_escape_string($_POST['correo']);
    $contrasena_ingresada = $_POST['contrasena'];

    // MODIFICACIÓN 1: Seleccionar nombre_completo y buscar por correo_electronico (que es el campo de login visible)
    $sql = "SELECT id, nombre_completo, contrasena FROM usuarios WHERE correo_electronico = ?";
    $stmt = $conexion->prepare($sql);
    // Usamos el identificador_ingresado (que es el Email) para la búsqueda
    $stmt->bind_param("s", $identificador_ingresado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        $contrasena_hasheada = $usuario['contrasena'];

        // Verificar la contraseña ingresada con la hasheada
        if (password_verify($contrasena_ingresada, $contrasena_hasheada)) {
            // Contraseña correcta: iniciar sesión
            $_SESSION['id_usuario'] = $usuario['id'];
            // Guardamos el nombre completo del usuario en la sesión para el saludo en index.php
            $_SESSION['nombre_usuario'] = $usuario['nombre_completo']; 

            // MODIFICACIÓN 2: Corregir la ruta. Subimos un nivel (../) para ir a index.php
            header("Location: ../index.php"); 
            exit();
        } else {
            // Contraseña incorrecta
            echo "<script>alert('Contraseña o Email incorrectos.'); window.history.back();</script>";
        }
    } else {
        // Usuario no encontrado o Email incorrecto
        echo "<script>alert('Contraseña o Email incorrectos.'); window.history.back();</script>";
    }

    $stmt->close();
    $conexion->close();
} else {
    // MODIFICACIÓN 3: Redirigir al formulario de registro/login, que está un nivel arriba
    header("Location: ../registro.html"); 
    exit();
}
?>