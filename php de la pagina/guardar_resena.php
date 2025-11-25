<?php
// Asegúrate de incluir tu conexión a la DB
include 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Obtener los datos sin sanear todavía
    $resena_texto_raw = $_POST['review-text'] ?? '';
    $calificacion_raw = $_POST['rating'] ?? '';
    $nombre_autor_raw = $_POST['reviewer-name'] ?? ''; // Usa el operador ?? para evitar errores si no se envía el campo
    
    // -------------------------------------------------------------
    // PASO CRÍTICO: VALIDACIÓN DE DATOS ANTES DE SANEAR
    // -------------------------------------------------------------

    // 1. Verificar que los campos no estén vacíos
    if (empty($resena_texto_raw) || empty($nombre_autor_raw) || empty($calificacion_raw)) {
        echo "<script>alert('Error: Por favor, completa todos los campos correctamente (incluyendo el nombre y la reseña).'); window.history.back();</script>";
        exit();
    }
    
    // 2. Verificar que la calificación sea un número dentro del rango
    $calificacion = (int)$calificacion_raw; // Intentar convertir a entero
    
    if (!is_numeric($calificacion_raw) || $calificacion < 1 || $calificacion > 100) {
        echo "<script>alert('Error: La calificación debe ser un número entre 1 y 100.'); window.history.back();</script>";
        exit();
    }
    
    // -------------------------------------------------------------
    // PASO 3: SANEAR DATOS
    // -------------------------------------------------------------
    $resena_texto = $conexion->real_escape_string($resena_texto_raw);
    $nombre_autor = $conexion->real_escape_string($nombre_autor_raw);

    // -------------------------------------------------------------
    // PASO 4: INSERTAR EN LA BASE DE DATOS
    // -------------------------------------------------------------
    
    // Preparar la sentencia SQL para la inserción
    $sql = "INSERT INTO resenas (nombre_autor, resena_texto, calificacion) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    
    $stmt->bind_param("ssi", $nombre_autor, $resena_texto, $calificacion);
    
    if ($stmt->execute()) {
        header("Location: ../index.php?resena=success");
        exit();
    } else {
        header("Location: ../index.php?resena=error");
        exit();
    }
    
    $stmt->close();
    $conexion->close();
} else {
    header("Location: ../index.php");
    exit();
}
?>