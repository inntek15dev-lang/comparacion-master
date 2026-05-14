<div id="cookie-banner" class="fixed bottom-6 right-6 left-6 md:left-auto md:w-96 z-[9999] transform translate-y-20 opacity-0 transition-all duration-500 ease-out hidden">
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200 dark:border-gray-700 shadow-2xl rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/50 p-2 rounded-xl">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Consentimiento de cookies</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Este sitio web utiliza cookies para mejorar su experiencia de navegación. No compartimos ninguna información personal con ningún tercero.
                </p>
                <div class="flex items-center gap-3">
                    <button id="accept-cookies" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition-colors shadow-lg shadow-indigo-200 dark:shadow-none">
                        Aceptar
                    </button>
                    <button id="close-cookie-banner" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cookie-banner');
        const acceptBtn = document.getElementById('accept-cookies');
        const closeBtn = document.getElementById('close-cookie-banner');

        // Verificar si ya se aceptaron
        if (!localStorage.getItem('oval_cookies_accepted')) {
            // Pequeño delay para impacto visual
            setTimeout(() => {
                banner.classList.remove('hidden');
                // Forzar reflow
                banner.offsetHeight;
                banner.classList.remove('translate-y-20', 'opacity-0');
            }, 1000);
        }

        const hideBanner = () => {
            banner.classList.add('translate-y-20', 'opacity-0');
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 500);
        };

        acceptBtn.addEventListener('click', function() {
            localStorage.setItem('oval_cookies_accepted', 'true');
            hideBanner();
        });

        closeBtn.addEventListener('click', hideBanner);
    });
</script>
