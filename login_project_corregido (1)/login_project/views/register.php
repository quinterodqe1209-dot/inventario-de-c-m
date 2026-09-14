<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C&M Soluciones Abrasivas SAS - Crear Cuenta</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Roboto:wght@300;400;500;700&display=swap');
        
        body {
            font-family: 'Roboto', sans-serif;
        }
        .font-industrial {
            font-family: 'Oswald', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-[#070a12] text-white flex items-center justify-center relative overflow-x-hidden p-4">

    <!-- FONDO PRINCIPAL CON IMAGEN Y DEGRADADO -->
    <div class="absolute inset-0 z-0">
        <img src="view/fondo.jpg.webp" alt="Fondo Abrasivos" class="w-full h-full object-cover opacity-75">
        <div class="absolute inset-0 bg-gradient-to-r from-[#050811]/95 via-[#050811]/70 to-black/40"></div>
    </div>

    <!-- TARJETA DE REGISTRO CENTRADA -->
    <div class="relative z-10 w-full max-w-md mx-auto">
        <div class="bg-[#0b0f19]/95 backdrop-blur-xl border border-gray-800 rounded-2xl p-8 shadow-2xl shadow-black/90 space-y-6">
            
            <div class="flex items-center gap-4 border-b border-gray-800 pb-5">
                <div class="w-12 h-12 rounded-full bg-amber-400/10 border border-amber-400 flex items-center justify-center text-amber-400">
                    <i data-lucide="user-plus" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-industrial text-2xl font-bold uppercase tracking-wider text-white">Crear Cuenta</h3>
                    <div class="w-10 h-1 bg-amber-400 mt-1 rounded-full"></div>
                </div>
            </div>

            <!-- ALERTA DE ERROR (SI EXISTE) -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-3.5 flex items-center gap-3 text-red-400 text-xs">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulario PHP -->
            <form action="index.php" method="POST" class="space-y-5">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="register">

                <div class="space-y-2">
                    <label for="nombre" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Nombre
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </span>
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            placeholder="Escribe tu nombre"
                            required
                            class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="apellido" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Apellido
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </span>
                        <input 
                            type="text" 
                            id="apellido" 
                            name="apellido" 
                            placeholder="Escribe tu apellido"
                            required
                            class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                        >
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label for="documento_id" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Documento de Identidad
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                            <i data-lucide="id" class="w-4 h-4"></i>
                        </span>
                        <input 
                            type="text" 
                            id="documento_id" 
                            name="documento_id" 
                            placeholder="Escribe tu documento de identidad"
                            inputmode="numeric"
                            pattern="[0-9]+"
                            title="El documento debe contener solo números."
                            required
                            class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="fecha_nacimiento" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Fecha de Nacimiento
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </span>
                        <input 
                            type="date" 
                            id="fecha_nacimiento" 
                            name="fecha_nacimiento" 
                            required
                            class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="correo" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Correo Electrónico
                    </label>
                    <div class="flex gap-2 items-start">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </span>
                            <input 
                                type="email" 
                                id="correo" 
                                name="correo" 
                                placeholder="Escribe tu correo electrónico"
                                pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                                title="Ingresa un correo electrónico válido."
                                required
                                class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                            >
                        </div>
                        <button
                            type="button"
                            id="btnEnviarCodigo"
                            class="shrink-0 px-3 py-2.5 bg-amber-400 hover:bg-amber-300 text-black text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all"
                        >
                            Enviar código
                        </button>
                    </div>
                    <div id="codigo-verificacion-wrap" class="hidden space-y-2 pt-1">
                        <label for="codigo_verificacion" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                            Código de verificación
                        </label>
                        <div class="flex gap-2 items-center">
                            <input
                                type="text"
                                id="codigo_verificacion"
                                name="codigo_verificacion"
                                inputmode="numeric"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                placeholder="123456"
                                class="w-full px-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                            >
                            <span id="codigoStatus" class="text-[10px] uppercase tracking-wider text-gray-400">Pendiente</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="username" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Usuario
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Escribe tu nuevo usuario"
                            required
                            class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="password" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Crea tu contraseña"
                            required
                            class="w-full pl-10 pr-10 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all"
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="rol" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Tipo de Cuenta
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500">
                            <i data-lucide="shield" class="w-4 h-4"></i>
                        </span>
                        <select 
                            id="rol" 
                            name="rol" 
                            required
                            class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-800 rounded-xl text-sm text-white focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all appearance-none cursor-pointer"
                        >
                            <option value="cliente">Cliente</option>
                            <option value="proveedor">Proveedor</option>
                            
                        </select>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3.5 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-black font-industrial text-base font-bold uppercase tracking-wider rounded-xl shadow-lg shadow-amber-400/20 transition-all transform active:scale-[0.99] mt-2"
                >
                    Registrarme
                </button>
            </form>

            <div class="text-center pt-4 border-t border-gray-800/80 text-xs text-gray-400">
                ¿Ya tienes una cuenta? 
                <a href="index.php?action=login" class="text-amber-400 font-bold hover:underline ml-1">
                    Volver al Login
                </a>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();

        const correoInput = document.getElementById('correo');
        const btnEnviarCodigo = document.getElementById('btnEnviarCodigo');
        const codigoWrap = document.getElementById('codigo-verificacion-wrap');
        const codigoInput = document.getElementById('codigo_verificacion');
        const codigoStatus = document.getElementById('codigoStatus');
        const form = document.querySelector('form');

        let verificacionActiva = false;
        let codigoEnviadoPara = '';

        function setCodigoStatus(message, tone = 'gray') {
            codigoStatus.textContent = message;
            codigoStatus.className = 'text-[10px] uppercase tracking-wider';
            if (tone === 'green') {
                codigoStatus.classList.add('text-emerald-400');
            } else if (tone === 'amber') {
                codigoStatus.classList.add('text-amber-400');
            } else if (tone === 'red') {
                codigoStatus.classList.add('text-red-400');
            } else {
                codigoStatus.classList.add('text-gray-400');
            }
        }

        correoInput.addEventListener('input', () => {
            verificacionActiva = false;
            codigoEnviadoPara = '';
            codigoWrap.classList.add('hidden');
            codigoInput.value = '';
            setCodigoStatus('Pendiente');
        });

        btnEnviarCodigo.addEventListener('click', async () => {
            const correo = correoInput.value.trim();
            const correoValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);

            if (!correoValido) {
                setCodigoStatus('Correo inválido', 'red');
                correoInput.focus();
                return;
            }

            btnEnviarCodigo.disabled = true;
            btnEnviarCodigo.textContent = 'Enviando...';
            setCodigoStatus('Enviando', 'amber');

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'enviar_codigo_verificacion');
                formData.append('correo', correo);
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData.toString()
                });

                const data = await response.json();

                if (data && data.success) {
                    verificacionActiva = true;
                    codigoEnviadoPara = correo;
                    codigoWrap.classList.remove('hidden');
                    codigoInput.focus();
                    if (data.debug_code) {
                        setCodigoStatus('Código de prueba: ' + data.debug_code, 'amber');
                    } else {
                        setCodigoStatus('Código enviado', 'green');
                    }
                } else {
                    setCodigoStatus(data && data.message ? data.message : 'Error', 'red');
                }
            } catch (error) {
                setCodigoStatus('Error al enviar', 'red');
            } finally {
                btnEnviarCodigo.disabled = false;
                btnEnviarCodigo.textContent = 'Enviar código';
            }
        });

        form.addEventListener('submit', (event) => {
            const correo = correoInput.value.trim();
            const codigo = codigoInput.value.trim();

            if (!verificacionActiva || codigoEnviadoPara !== correo || codigo.length !== 6) {
                event.preventDefault();
                setCodigoStatus('Primero envía y valida el código', 'red');
                codigoWrap.classList.remove('hidden');
                codigoInput.focus();
            }
        });
    </script>
</body>
</html>