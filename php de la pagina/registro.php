<?php
include 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_completo = $_POST['nombre_completo']; 
    $correo_electronico = $_POST['correo'];     
    $contrasena = $_POST['contrasena'];         
    
    $nombre_usuario_unico = $correo_electronico; 
    

    $sql_check = "SELECT id FROM usuarios WHERE nombre_usuario = ? OR correo_electronico = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("ss", $nombre_usuario_unico, $correo_electronico);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        echo "<script>alert('Error: El Email ya está registrado. Por favor, inicia sesión o usa otro correo.'); window.history.back();</script>";
        exit(); 
    }
    $stmt_check->close();
    
    
    $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
    
    $sql_insert = "INSERT INTO usuarios (nombre_completo, nombre_usuario, correo_electronico, contrasena) VALUES (?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql_insert);
    $stmt->bind_param("ssss", $nombre_completo, $nombre_usuario_unico, $correo_electronico, $hashed_password);
    
    if ($stmt->execute()) {
        echo "<script>alert('Registro exitoso. ¡Inicia sesión ahora!'); window.location.href='../registro.html';</script>"; 
        exit();
    } else {
        echo "<script>alert('Error al registrar el usuario. Inténtalo de nuevo.'); window.history.back();</script>";
    }
    
    $stmt->close();
}

$conexion->close();
?>