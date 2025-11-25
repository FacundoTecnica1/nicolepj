<?php
session_start();
include 'conexion.php';

$usuario_logueado = isset($_SESSION['id_usuario']);
$nombre_usuario = $usuario_logueado ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Invitado';

// Consultar los servicios disponibles
$sql = "SELECT * FROM servicios";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios - SecurityShop</title>
    <link rel="stylesheet" href="../estilo de la pagina/index.css?v=20251031">
</head>
<body>

<!-- 🔥 HEADER ACTUALIZADO -->
<header class="navbar"> 
  <div class="logo">
    <span class="logo-shield">🛡️</span>
    <span class="logo-text">SecurityShop</span>
  </div>

  <nav class="nav-links">
    <a href="../index.php">Inicio</a>
    <a href="../index.php">Servicios</a> <!-- 🔁 Ahora lleva al inicio -->
    <?php if ($usuario_logueado): ?>
      <span class="welcome-message">¡Hola, <?= $nombre_usuario; ?>!</span>
      <a href="logout.php" class="nav-button logout-button">Cerrar Sesión</a>
    <?php else: ?>
      <a href="../registro.html" class="nav-button active-session">Sesión/Registrar</a>
    <?php endif; ?>
  </nav>
</header>

<!-- CONTENIDO PRINCIPAL -->
<main>
    <section class="product-section">
        <h1 class="section-title">Servicios Disponibles</h1>

        <?php if ($resultado->num_rows > 0): ?>
            <div class="product-list"> <!-- Contenedor de todas las tarjetas -->
                <?php while ($servicio = $resultado->fetch_assoc()) { ?>
                    <div class="product-info-card">
                        <div class="product-visual-area">
                            <?php if (!empty($servicio['imagen'])): ?>
                                <img src="../imagenes/<?= htmlspecialchars($servicio['imagen']); ?>" width="250" alt="<?= htmlspecialchars($servicio['nombre']); ?>">
                            <?php endif; ?>
                        </div>

                        <h3 class="product-name"><?= htmlspecialchars($servicio['nombre']); ?></h3>
                        <p class="product-description"><?= htmlspecialchars($servicio['descripcion']); ?></p>
                        <p class="product-price">$<?= number_format($servicio['precio'], 2, ',', '.'); ?></p>

                        <?php if ($usuario_logueado): ?>
                            <form action="agregar_a_carrito.php" method="POST">
                                <input type="hidden" name="tipo" value="servicio">
                                <input type="hidden" name="id_item" value="<?= $servicio['id_servicio']; ?>">
                                <input type="number" name="cantidad" value="1" min="1">
                                <button type="submit" class="btn btn-add-to-cart">Contratar Servicio</button>
                            </form>
                        <?php else: ?>
                            <p><a href="../registro.html" class="btn btn-main">Inicia sesión para contratar</a></p>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div> <!-- Fin del contenedor .product-list -->
        <?php else: ?>
            <p style="text-align:center;">No hay servicios disponibles por el momento.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>

<?php $conexion->close(); ?>
