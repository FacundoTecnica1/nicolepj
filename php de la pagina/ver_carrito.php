<?php
session_start();
// Se asume que 'conexion.php' contiene la conexión a la base de datos ($conexion).
include 'conexion.php'; 

// Función auxiliar para enviar respuesta JSON y terminar la ejecución (usada por AJAX)
function sendJsonResponse($status, $message = null) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

// =========================================================================
// === 1. LÓGICA DE PROCESAMIENTO DE PAGO (Activada por solicitud POST de AJAX) ===
// =========================================================================

// Esta sección se ejecuta SÓLO cuando el JavaScript envía el formulario (método POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verificar sesión
    if (!isset($_SESSION['id_usuario'])) {
        sendJsonResponse('error', 'Sesión requerida para el pago. Por favor, inicia sesión.');
    }

    $id_usuario = (int)$_SESSION['id_usuario'];

    try {
        // 2. VACIAR EL CARRITO del usuario (Simulación de pago exitoso)
        $sql_vaciar = "DELETE FROM carrito WHERE id_usuario = ?";
        $stmt_vaciar = $conexion->prepare($sql_vaciar);

        if ($stmt_vaciar === false) {
            throw new Exception("Error al preparar la consulta DELETE: " . $conexion->error);
        }

        $stmt_vaciar->bind_param("i", $id_usuario);
        
        if ($stmt_vaciar->execute()) {
            $stmt_vaciar->close();
            $conexion->close();
            
            // 3. ÉXITO: Devolver JSON y terminar
            sendJsonResponse('success');
        } else {
            throw new Exception("Fallo al ejecutar la eliminación del carrito: " . $stmt_vaciar->error);
        }

    } catch (Exception $e) {
        $conexion->close();
        // 4. ERROR: Devolver JSON con mensaje de error y terminar
        sendJsonResponse('error', 'Error interno al procesar el pago: ' . $e->getMessage());
    }
}

// =========================================================================
// === 2. LÓGICA DE CARGA DE DATOS Y GENERACIÓN DE HTML (Carga inicial por GET) ===
// =========================================================================

// Esto se ejecuta cuando el usuario simplemente navega a la página (solicitud GET).

$usuario_logueado = isset($_SESSION['id_usuario']);
$nombre_usuario = $usuario_logueado ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Invitado';

$items_carrito = [];
$total_carrito = 0;
$conteo_carrito = 0;

if ($usuario_logueado) {
    $id_usuario = $_SESSION['id_usuario'];

    // Consulta de productos del carrito
    $sql_carrito = "SELECT 
                        c.nombre_producto, 
                        c.precio_unitario, 
                        c.cantidad, 
                        p.imagen AS imagen_item
                    FROM carrito c
                    LEFT JOIN productos p ON c.id_producto = p.id_producto
                    WHERE c.id_usuario = ?";

    $stmt_carrito = $conexion->prepare($sql_carrito);
    $stmt_carrito->bind_param("i", $id_usuario);
    $stmt_carrito->execute();
    $resultado_carrito = $stmt_carrito->get_result();

    while ($item = $resultado_carrito->fetch_assoc()) {
        $subtotal = $item['precio_unitario'] * $item['cantidad'];
        $total_carrito += $subtotal;
        $conteo_carrito += $item['cantidad'];
        $items_carrito[] = $item;
    }

    $stmt_carrito->close();
    
    // Cierre seguro de la conexión
    if (isset($conexion) && $conexion->ping()) {
        $conexion->close();
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Carrito - Margarita's Cerámicas</title>
    <link rel="stylesheet" href="../estilo de la pagina/carrito.css?v=20251111">
    
    <style>
        .success-message-box {
            background-color: #d1fae5;
            border-left: 8px solid #059669;
            color: #065f46;
            padding: 20px;
            margin: 20px auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-family: Inter, sans-serif;
        }
        .success-icon {
            color: #047857;
            font-size: 30px;
            margin-right: 15px;
        }
        .success-text {
            font-weight: bold;
            font-size: 1.1em;
        }
        .error-message-box {
            background-color: #fee2e2;
            border-left: 8px solid #ef4444;
            color: #991b1b;
            padding: 20px;
            margin: 20px auto;
            border-radius: 8px;
            max-width: 400px;
            text-align: center;
            font-family: Inter, sans-serif;
            display: none; /* Inicialmente oculto */
        }
        .btn-main-checkout {
            padding: 12px 25px;
            background-color: #4CAF50; 
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-main-checkout:hover {
            background-color: #45a049;
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-main-checkout:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }
        .btn-main {
            display: block;
            width: 200px;
            margin: 20px auto;
            text-align: center;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body class="carrito-page">

<header class="navbar">
    <div class="logo">
        <span class="logo-shield">🌼</span>
        <span class="logo-text">Margarita's Cerámicas</span>
    </div>

    <nav class="nav-links">
        <a href="../index.php">Inicio</a>
        <?php if ($usuario_logueado): ?>
            <span class="welcome-message">¡Hola, <?= $nombre_usuario; ?>!</span>
            <a href="logout.php" class="nav-button logout-button">Cerrar Sesión</a>
        <?php else: ?>
            <a href="../registro.html" class="nav-button active-session">Sesión/Registrar</a>
        <?php endif; ?>
    </nav>
</header>

<main class="carrito-main">
    <h1 class="section-title">🛍️ Tu Carrito de Compras</h1>

    <?php if ($usuario_logueado): ?>
        
        <div id="confirmacion-pago" class="success-message-box" style="display: none;">
            <span class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                  <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.882l-3.236 4.53L9.53 12.234a.75.75 0 0 0-1.06 1.06l2.102 2.102a.75.75 0 0 0 1.227-.128l3.968-5.56Z" clip-rule="evenodd" />
                </svg>
            </span>
            <span class="success-text">¡Pago Exitoso! Tu pedido ha sido procesado.</span>
        </div>
        <div id="mensaje-error" class="error-message-box">
             <span id="error-text">Ocurrió un error al procesar el pago.</span>
        </div>
        <?php if (empty($items_carrito)): ?>
            <p class="empty-cart-message" id="empty-message" style="display: block;">Tu carrito está vacío. ¡Añadí algunos productos!</p>
            <a href="../index.php" class="btn btn-main">Volver a la tienda</a>
        <?php else: ?>
            <p class="empty-cart-message" id="empty-message" style="display: none;">Tu carrito está vacío. ¡Añadí algunos productos!</p>
            <a href="../index.php" class="btn btn-main" id="volver-tienda-link" style="display: block;">Volver a la tienda</a>

            <div class="carrito-list" id="carrito-items-list">
                <?php foreach ($items_carrito as $item): ?>
                    <div class="carrito-item">
                        <?php if (!empty($item['imagen_item'])): ?>
                            <img src="../imagenes/<?= htmlspecialchars($item['imagen_item']); ?>" alt="Producto">
                        <?php endif; ?>
                        <div class="item-info">
                            <span class="item-name"><?= htmlspecialchars($item['nombre_producto']); ?></span>
                            <span class="item-price">$<?= number_format($item['precio_unitario'], 2, ',', '.'); ?></span>
                            <span class="item-quantity">Cantidad: <?= $item['cantidad']; ?></span>
                            <span class="item-subtotal">Subtotal: $<?= number_format($item['precio_unitario'] * $item['cantidad'], 2, ',', '.'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="carrito-resumen" id="carrito-resumen-box">
                <p class="carrito-total">Total a pagar: <span>$<?= number_format($total_carrito, 2, ',', '.'); ?></span></p>
                
                <form id="formulario-pago" class="checkout-form">
                    <button type="submit" class="btn btn-main-checkout" id="btn-pago">
                        Proceder al Pago 
                    </button>
                </form>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="login-prompt">
            <p>Por favor, <a href="../registro.html">iniciá sesión</a> para ver y gestionar tu carrito.</p>
        </div>
    <?php endif; ?>
</main>
<footer class="footer">
    <div class="footer-content">
        </div>
        <div class="footer-column">
                <h4>Nicole Roglich - 5to 3ra - Redes Informáticas</h4>
                
        </div>
</footer>
<script>
    // === CÓDIGO JAVASCRIPT/AJAX PARA EL PAGO (SIN RECARGA) ===
    const formPago = document.getElementById('formulario-pago');
    const btnPago = document.getElementById('btn-pago');
    const mensajeExito = document.getElementById('confirmacion-pago');
    const mensajeError = document.getElementById('mensaje-error');
    const errorText = document.getElementById('error-text');
    const carritoResumen = document.getElementById('carrito-resumen-box');
    const carritoLista = document.getElementById('carrito-items-list');
    const emptyMessage = document.getElementById('empty-message');
    const volverTiendaLink = document.getElementById('volver-tienda-link');


    if (formPago) {
        formPago.addEventListener('submit', function(event) {
            event.preventDefault(); // CLAVE: Detiene la recarga normal de la página

            // 1. Ocultar mensajes previos y preparar la UI
            if (mensajeExito) mensajeExito.style.display = 'none';
            if (mensajeError) mensajeError.style.display = 'none';

            // 2. Deshabilitar botón y mostrar estado de carga
            btnPago.innerText = 'Procesando...';
            btnPago.disabled = true;

            // 3. Envía la solicitud POST al mismo script PHP (ver_carrito.php)
            fetch('ver_carrito.php', {
                method: 'POST',
            })
            .then(response => {
                // El script PHP debe responder con un JSON.
                if (!response.ok) {
                    throw new Error('Error de conexión con el servidor: ' + response.statusText);
                }
                return response.json(); 
            }) 
            .then(data => {
                if (data.status === 'success') {
                    // 4. PAGO EXITOSO: Actualizar la interfaz de usuario
                    
                    // Oculta los elementos del carrito y el formulario
                    if (carritoResumen) carritoResumen.style.display = 'none';
                    if (carritoLista) carritoLista.style.display = 'none';
                    
                    // Oculta el enlace "Volver a la tienda" si estaba visible
                    if (volverTiendaLink) volverTiendaLink.style.display = 'none';
                    
                    // Muestra el recuadro verde de éxito
                    if (mensajeExito) mensajeExito.style.display = 'flex';
                    
                    // Muestra el mensaje de carrito vacío
                    if (emptyMessage) emptyMessage.style.display = 'block';

                } else {
                    // 5. ERROR EN EL PAGO (Devuelto por el servidor PHP)
                    errorText.innerText = data.message || 'Error desconocido al procesar el pago.';
                    if (mensajeError) mensajeError.style.display = 'block';
                    
                    // Habilitar botón
                    btnPago.innerText = 'Proceder al Pago ';
                    btnPago.disabled = false;
                }
            })
            .catch(error => {
                // 6. ERROR DE RED O CONEXIÓN
                console.error('Error de red o del servidor:', error);
                errorText.innerText = 'Ocurrió un error inesperado. ' + error.message;
                if (mensajeError) mensajeError.style.display = 'block';

                // Habilitar botón
                btnPago.innerText = 'Proceder al Pago ';
                btnPago.disabled = false;
            });
        });
    }
</script>

</body>
</html>