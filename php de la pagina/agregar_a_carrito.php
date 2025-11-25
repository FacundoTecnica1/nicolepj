<?php
session_start();
include 'conexion.php';

// 1) Requiere sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../registro.html?error=login_required");
    exit();
}

$id_usuario = (int)$_SESSION['id_usuario'];

// === Helper: volver a la página anterior o a la que envíe el form ===
function redirect_back($status = 'ok') {
    // 1) Si el form mandó redirect_to, usarlo
    $back = $_POST['redirect_to'] ?? '';

    // 2) Si no vino, usar el Referer
    if (!$back && !empty($_SERVER['HTTP_REFERER'])) {
        $back = $_SERVER['HTTP_REFERER'];
    }

    // 3) Último recurso: Home
    if (!$back) {
        $back = '../index.php';
    }

    // Sanitizar MUY básico: solo permitir rutas relativas del sitio
    // (si detectás "http" o dominio externo, forzar home)
    if (preg_match('/^https?:\/\//i', $back)) {
        $back = '../index.php';
    }

    // Agregar status
    $sep = (strpos($back, '?') !== false) ? '&' : '?';
    header("Location: {$back}{$sep}status={$status}");
    exit();
}

// 2) Entrada
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'producto';

// Aceptar tanto id_item (servicios) como id_producto (productos)
$id_item = isset($_POST['id_item']) ? (int)$_POST['id_item']
         : (isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0);

$cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

// Validación
if ($id_item <= 0 || $cantidad <= 0) {
    redirect_back('invalid_input');
}

// 3) Tomar nombre y precio desde la DB
if ($tipo === 'servicio') {
    $sql = "SELECT nombre, precio FROM servicios WHERE id_servicio = ?";
} else {
    $sql = "SELECT nombre, precio FROM productos WHERE id_producto = ?";
}

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_item);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    redirect_back('item_not_found');
}

$item = $result->fetch_assoc();
$nombre_item = $item['nombre'];
$precio_unitario = $item['precio'];
$stmt->close();

// 4) Ver si ya existe en el carrito
$id_field = ($tipo === 'servicio') ? 'id_servicio' : 'id_producto';
$sql_check = "SELECT id_carrito, cantidad FROM carrito WHERE id_usuario = ? AND {$id_field} = ? AND tipo_item = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("iis", $id_usuario, $id_item, $tipo);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows > 0) {
    // Actualizar cantidad
    $fila = $res_check->fetch_assoc();
    $nueva_cantidad = $fila['cantidad'] + $cantidad;

    $sql_update = "UPDATE carrito SET cantidad = ? WHERE id_carrito = ?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("ii", $nueva_cantidad, $fila['id_carrito']);
    $stmt_update->execute();
    $stmt_update->close();

    $stmt_check->close();
    $conexion->close();
    redirect_back('cart_updated');

} else {
    // Insert nuevo
    if ($tipo === 'servicio') {
        $sql_insert = "INSERT INTO carrito (id_usuario, id_servicio, nombre_producto, precio_unitario, cantidad, tipo_item)
                       VALUES (?, ?, ?, ?, ?, 'servicio')";
    } else {
        $sql_insert = "INSERT INTO carrito (id_usuario, id_producto, nombre_producto, precio_unitario, cantidad, tipo_item)
                       VALUES (?, ?, ?, ?, ?, 'producto')";
    }

    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bind_param("iisdi", $id_usuario, $id_item, $nombre_item, $precio_unitario, $cantidad);

    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        $stmt_check->close();
        $conexion->close();
        redirect_back('cart_added');
    } else {
        $err = $stmt_insert->error;
        $stmt_insert->close();
        $stmt_check->close();
        $conexion->close();
        // Si algo falla, volver atrás con error
        header("Location: ../index.php?error=db_error");
        exit();
    }
}
