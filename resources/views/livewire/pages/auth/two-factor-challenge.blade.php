<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Hemos enviado un código de verificación a tu dirección de correo electrónico. Por favor, introduce el código para continuar.') }}
    </div>

    <form wire:submit="verifyCode">
        <!-- Verification Code -->
        <div>
            <x-input-label for="code" :value="__('Código de Verificación')" />
            <x-text-input wire:model="code" id="code" class="block mt-1 w-full" type="text" name="code" required autofocus inputmode="numeric" pattern="[0-9]*" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verificar e Iniciar Sesión') }}
            </x-primary-button>
        </div>
    </form>
</div>