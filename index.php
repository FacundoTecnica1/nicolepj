<?php
session_start();
include 'php de la pagina/conexion.php'; 

$usuario_logueado = isset($_SESSION['id_usuario']);
$nombre_usuario = $usuario_logueado ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Invitado';

$conteo_carrito = 0;
if ($usuario_logueado) {
    $id_usuario = $_SESSION['id_usuario'];
    $sql_count = "SELECT SUM(cantidad) AS total_items FROM carrito WHERE id_usuario = ?";
    $stmt_count = $conexion->prepare($sql_count);
    $stmt_count->bind_param("i", $id_usuario);
    $stmt_count->execute();
    $resultado_count = $stmt_count->get_result()->fetch_assoc();
    $conteo_carrito = $resultado_count['total_items'] ?? 0;
    $stmt_count->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Margarita`s 🌼</title>
    <link rel="stylesheet" href="estilo de la pagina/index.css">
   
</head>
<body>

<!-- HEADER -->
<header class="navbar">
    <div class="logo">
        <span class="logo-shield">🌼</span>
        <span class="logo-text">Margarita`s ceramicas</span>
    </div>

    <nav class="nav-links">
        <a href="index.php">Inicio</a>

        <?php 
        $pagina_actual = basename($_SERVER['PHP_SELF']);
        if ($pagina_actual !== 'ver_carrito.php'): 
        ?>
        
        <?php endif; ?>

        <?php if ($usuario_logueado): ?>
            <span class="welcome-message">¡Hola, <?= $nombre_usuario; ?>!</span>
            <a href="php de la pagina/logout.php" class="nav-button logout-button">Cerrar Sesión</a>
        <?php else: ?>
            <a href="registro.html" class="nav-button active-session">Sesión/Registrar</a>
        <?php endif; ?>
    </nav>

    <div class="cart-button-container">
        <a href="php de la pagina/ver_carrito.php" class="cart-button">
            🛒 Carrito <span class="cart-count"><?= $conteo_carrito; ?></span>
        </a>
    </div>
</header>

<!-- PRODUCTOS -->
<main>
    <section class="product-section">
        <h2 class="section-title">Productos Ceramicas</h2>
        <div class="product-list">

        <?php
        $sql_prod = "SELECT * FROM productos";
        $resultado = $conexion->query($sql_prod);
        while ($prod = $resultado->fetch_assoc()) {
        ?>
            <div class="product-info-card">
                <img src="imagenes/<?= htmlspecialchars($prod['imagen']); ?>" alt="<?= htmlspecialchars($prod['nombre']); ?>" class="product-img">
                <h3 class="product-name"><?= htmlspecialchars($prod['nombre']); ?></h3>
                <p class="product-description"><?= htmlspecialchars($prod['descripcion']); ?></p>
                <p class="product-price">$<?= number_format($prod['precio'], 2, ',', '.'); ?></p>
                <form action="php de la pagina/agregar_a_carrito.php" method="POST">
                    <input type="hidden" name="id_producto" value="<?= $prod['id_producto']; ?>">
                    <input type="number" name="cantidad" value="1" min="1">
                    <button type="submit" class="btn btn-add-to-cart">Agregar al Carrito</button>
                </form>
            </div>
        <?php } ?>
        </div>
    </section>

    <!-- RESEÑAS -->
    <section class="reviews-section review-form-section">
        <h2 class="section-title">Déjanos tu Reseña</h2>
        <div class="review-form-container">
            <form class="review-form" action="php de la pagina/guardar_resena.php" method="POST">
                <div class="form-layout-grid">
                    <div class="form-group-textarea">
                        <textarea id="review-text" name="review-text" rows="5" placeholder="Escribe tu experiencia con el producto..." required></textarea>
                    </div>

                    <div class="form-group-side">
                        <div class="form-group field-group">
                            <label for="rating">Calificación (1-100):</label>
                            <input type="number" id="rating" name="rating" placeholder="70" required min="1" max="100">
                        </div>
                        <div class="form-group field-group">
                            <label for="reviewer-name">Tu Nombre:</label>
                            <input type="text" id="reviewer-name" name="reviewer-name" placeholder="Juan Pérez" required value="<?= $usuario_logueado ? $nombre_usuario : ''; ?>"> 
                        </div>
                        <button type="submit" class="btn btn-submit-review">Enviar Reseña</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- MOSTRAR RESEÑAS -->
    <section class="reviews-display-section">
        <h2 class="section-title">Lo que dicen nuestros clientes</h2>
        <div class="reviews-list-container">
        <?php
        $sql_select_reviews = "SELECT nombre_autor, resena_texto, calificacion, fecha_resena FROM resenas ORDER BY fecha_resena DESC LIMIT 10";
        $resultado_resenas = $conexion->query($sql_select_reviews);

        if ($resultado_resenas && $resultado_resenas->num_rows > 0) {
            while($resena = $resultado_resenas->fetch_assoc()) {
                $fecha_formateada = date("d/m/Y", strtotime($resena['fecha_resena']));
                $calificacion_texto = $resena['calificacion'] . '/100';
        ?>
            <div class="review-card">
                <p class="review-text"><?= htmlspecialchars($resena['resena_texto']); ?></p>
                <div class="review-meta">
                    <span class="review-author">— <?= htmlspecialchars($resena['nombre_autor']); ?></span>
                    <span class="review-rating">⭐ <?= $calificacion_texto; ?></span>
                    <span class="review-date">| <?= $fecha_formateada; ?></span>
                </div>
            </div>
        <?php
            }
        } else {
            echo "<p>Aún no hay reseñas. ¡Sé el primero en dejar una!</p>";
        }
        $conexion->close(); 
        ?>
        </div>
    </section>
</main>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-content">
        </div>
        <div class="footer-column">
                <h4>Nicole Roglich - 5to 3ra - Redes Informáticas</h4>
                
        </div>
</footer>

<script src="java de la pagina/java.js"></script>
</body>
</html>
