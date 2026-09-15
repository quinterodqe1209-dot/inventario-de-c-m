<?php
$mensajeRecuperar = $mensajeRecuperar ?? '';
$mensajeRecuperarError = $mensajeRecuperarError ?? '';
$enlaceVisible = $enlaceVisible ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C&M Soluciones Abrasivas SAS - Recuperar Contraseña</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Roboto:wght@300;400;500;700&display=swap');
        body { font-family: 'Roboto', sans-serif; }
        .font-industrial { font-family: 'Oswald', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-[#050912] text-white flex items-center justify-center relative overflow-x-hidden">
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=2200&q=90" alt="Trabajo industrial con abrasivos" class="w-full h-full object-cover opacity-80">
        <div class="absolute inset-0 bg-gradient-to-r from-[#03060c]/75 via-[#03060c]/48 to-[#02050b]/38"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_42%_48%,rgba(255,177,0,0.18),transparent_34%)]"></div>
    </div>

    <div class="relative z-10 w-full max-w-md mx-auto px-4 py-8">
        <div class="bg-[#05080e]/95 backdrop-blur-xl border border-gray-700/80 rounded-[2rem] p-8 md:p-10 shadow-2xl shadow-black/90 space-y-6">
            <div class="flex items-center gap-4 border-b border-gray-800 pb-5">
                <div class="w-12 h-12 rounded-full bg-amber-400/10 border border-amber-400 flex items-center justify-center text-amber-400">
                    <i data-lucide="key-round" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-industrial text-2xl font-bold uppercase tracking-wider text-white">Recuperar Contraseña</h3>
                    <div class="w-10 h-1 bg-amber-400 mt-1 rounded-full"></div>
                </div>
            </div>

            <?php if (!empty($mensajeRecuperarError)): ?>
                <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-3.5 flex items-center gap-3 text-red-400 text-xs">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($mensajeRecuperarError, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensajeRecuperar)): ?>
                <div class="bg-emerald-500/10 border border-emerald-500/50 rounded-xl p-3.5 flex items-start gap-3 text-emerald-400 text-xs">
                    <i data-lucide="check-circle-2" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <span><?php echo htmlspecialchars($mensajeRecuperar, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <?php if (!empty($enlaceVisible)): ?>
                    <div class="bg-[#131927] border border-amber-400/40 rounded-xl p-3.5 text-xs text-gray-200 break-all">
                        <p class="text-amber-400 font-semibold uppercase tracking-wider mb-1">Enlace directo (demo local):</p>
                        <a class="text-amber-300 underline hover:text-amber-200" href="<?php echo htmlspecialchars($enlaceVisible, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($enlaceVisible, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <a href="index.php?action=login" class="block text-center text-xs text-gray-400 hover:text-amber-300">
                    ← Volver al inicio de sesión
                </a>
            <?php endif; ?>

            <?php if (empty($mensajeRecuperar) && empty($enlaceVisible)): ?>
            <form action="index.php" method="POST" class="space-y-5">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="recuperar">

                <div class="space-y-2">
                    <label for="correo" class="font-industrial text-xs font-semibold tracking-wider uppercase text-gray-300">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-500 pointer-events-none">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </span>
                        <input type="email" id="correo" name="correo" required
                               placeholder="usuario@correo.com"
                               class="w-full pl-10 pr-4 py-3 bg-[#131927] border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-black font-industrial text-base font-bold uppercase tracking-wider rounded-lg shadow-lg shadow-amber-400/20 transition-all transform active:scale-[0.99]">
                    Enviar enlace de recuperación
                </button>
            </form>

            <div class="text-center pt-2 text-xs text-gray-400">
                <a href="index.php?action=login" class="text-amber-400 font-bold hover:underline">
                    ← Volver al inicio de sesión
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>lucide.createIcons();</script>
    <?php require __DIR__.'/partials/swal.php'; ?>
</body>
</html>