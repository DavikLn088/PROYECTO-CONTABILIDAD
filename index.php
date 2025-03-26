<?php  
session_start();

// Control de intentos de login
if (!isset($_SESSION['intentos_login'])) {
    $_SESSION['intentos_login'] = 0;
}

if ($_SESSION['intentos_login'] > 3) {
    $_SESSION['error_message'] = 'Demasiados intentos. Por favor espere 15 minutos.';
    header("Location: index.php");
    exit();
}

// Redirigir al menú principal si ya está logueado
if(isset($_SESSION['usuario_id']) || isset($_SESSION['usuario']['id'])) {
    header("Location: menu_principal.php");
    exit();
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Facturación - Login</title>
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

    <main>
        <div class="contenedor__todo">
            <!-- Mostrar mensajes de error/success -->
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="error-message" style="display: block;">
                    <?= htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="success-message" style="display: block;">
                    <?= htmlspecialchars($_SESSION['success_message']); ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Mostrar solo formularios de login/registro -->
            <div class="caja__trasera">
                <div class="caja__trasera-login">
                    <h3>¿Ya tienes una cuenta?</h3>
                    <p>Inicia sesión para acceder al sistema</p>
                    <button id="btn__iniciar-sesion">Iniciar Sesión</button>
                </div>
                <div class="caja__trasera-register">
                    <h3>¿Aún no tienes una cuenta?</h3>
                    <p>Regístrate para comenzar a usar el sistema</p>
                    <button id="btn__registrarse">Registrarse</button>
                </div>
            </div>

            <div class="contenedor__login-register">
                <!-- Formulario de Login -->
                <form action="./php/login_usuario_be.php" method="POST" class="formulario__login" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <h2>Iniciar Sesión</h2>
                    <input type="email" placeholder="Correo Electrónico" name="correo" required autofocus>
                    <input type="password" placeholder="Contraseña" name="contrasena" required minlength="6">
                    <button type="submit">Entrar</button>
                    <div class="forgot-password-container">
                        <a href="recuperar_contrasena.php" class="forgot-password-btn">¿Olvidaste tu contraseña?</a>
                    </div>
                </form>
                
                <!-- Formulario de Registro -->
                <form action="./php/registro_usuario_be.php" method="POST" class="formulario__register" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <h2>Registrarse</h2>
                    <input type="text" placeholder="Nombre Completo" name="nombre_completo" required pattern="[A-Za-z áéíóúÁÉÍÓÚñÑ]+" title="Solo letras y espacios">
                    <input type="email" placeholder="Correo Electrónico" name="correo" required>
                    <input type="password" placeholder="Contraseña" name="contrasena" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Debe contener al menos un número, una mayúscula y minúscula, y 8+ caracteres">
                    <input type="password" placeholder="Confirmar Contraseña" name="confirmar_contrasena" required>
                    <div class="recaptcha-wrapper">
                        <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                    </div>
                    <button type="submit">Registrarse</button>
                </form>
            </div>
        </div>
    </main>

    <script src="./js/script.js"></script>
    <script>
        // Validación básica del formulario de registro
        document.querySelector('.formulario__register').addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="contrasena"]').value;
            const confirmPassword = this.querySelector('input[name="confirmar_contrasena"]').value;
            
            if(password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            return true;
        });
        
        // Mostrar/ocultar contraseña (mejora UX)
        document.querySelectorAll('input[type="password"]').forEach(input => {
            // Crear contenedor principal
            const passwordWrapper = document.createElement('div');
            passwordWrapper.style.position = 'relative';
            passwordWrapper.style.width = '100%';
            passwordWrapper.style.display = 'inline-block';
            
            // Mover el input al contenedor
            input.parentNode.insertBefore(passwordWrapper, input);
            passwordWrapper.appendChild(input);
            
            // Crear botón toggle (solo el tamaño del icono)
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            `;
            
            // Estilos para el botón (área justo del icono)
            toggleBtn.style.position = 'absolute';
            toggleBtn.style.right = '8px';
            toggleBtn.style.top = '66%';
            toggleBtn.style.transform = 'translateY(-50%)';
            toggleBtn.style.background = 'transparent';
            toggleBtn.style.border = 'none';
            toggleBtn.style.padding = '5px';
            toggleBtn.style.margin = '0';
            toggleBtn.style.cursor = 'pointer';
            toggleBtn.style.width = '28px';
            toggleBtn.style.height = '28px';
            toggleBtn.style.display = 'flex';
            toggleBtn.style.alignItems = 'center';
            toggleBtn.style.justifyContent = 'center';
            toggleBtn.style.color = '#666';
            toggleBtn.style.borderRadius = '3px';
            toggleBtn.style.transition = 'all 0.2s ease';
            
            // Estilos para el SVG dentro del botón
            const svg = toggleBtn.querySelector('svg');
            svg.style.pointerEvents = 'none';
            
            // Efecto hover
            toggleBtn.addEventListener('mouseenter', () => {
                toggleBtn.style.color = '#46A2FD';
                toggleBtn.style.background = 'rgba(70, 162, 253, 0.1)';
            });
            
            toggleBtn.addEventListener('mouseleave', () => {
                toggleBtn.style.color = '#666';
                toggleBtn.style.background = 'transparent';
            });
            
            // Función para cambiar el icono
            const updateToggleIcon = () => {
                if (input.type === 'password') {
                    toggleBtn.innerHTML = `
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    `;
                } else {
                    toggleBtn.innerHTML = `
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.9 4.2C10.6 4.1 11.3 4 12 4C19 4 23 12 23 12C22.7 12.7 22.2 13.5 21.7 14.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 12C14 13.106 13.106 14 12 14C10.894 14 10 13.106 10 12C10 10.894 10.894 10 12 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17.9 17.9C16.2 19.2 14.2 20 12 20C5 20 1 12 1 12C2.5 9.1 4.8 6.9 7.5 5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 2L2 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    `;
                }
            };
            
            // Evento click
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                input.type = input.type === 'password' ? 'text' : 'password';
                updateToggleIcon();
            });
            
            // Ajustar input para el icono
            input.style.paddingRight = '40px';
            input.style.width = '100%';
            input.style.boxSizing = 'border-box';
            
            // Añadir el botón al DOM
            passwordWrapper.appendChild(toggleBtn);
        });
    </script>
</body>
</html>