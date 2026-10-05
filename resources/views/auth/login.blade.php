<x-layouts.publico titulo="Iniciar sesión">

    @if(session('status'))
        <x-alerta tipo="exito" class="mb-6">{{ session('status') }}</x-alerta>
    @endif

    <h1 class="font-display text-3xl font-medium text-gg-tinta">Iniciar sesión</h1>
    <p class="text-base text-gg-tinta-suave mt-1.5 mb-7">Bienvenido de nuevo. Ingresa con tu correo institucional.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-campo-texto
            nombre="email"
            etiqueta="Correo electrónico"
            tipo="email"
            :valor="old('email')"
            requerido
            autocomplete="username"
            autofocus
            :error="$errors->first('email')"
        />

        <x-campo-texto
            nombre="password"
            etiqueta="Contraseña"
            tipo="password"
            requerido
            autocomplete="current-password"
            :error="$errors->first('password')"
        />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                    type="checkbox"
                    name="remember"
                    class="w-[18px] h-[18px]"
                >
                <span class="text-sm text-gg-tinta-suave">Recordarme</span>
            </label>

            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm text-gg-primario hover:underline focus-visible:outline-none focus-visible:underline">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <x-boton variante="primario" tipo="submit" tamano="lg" class="w-full justify-center" icono-final="flecha">
            Entrar
        </x-boton>
    </form>

    <p class="mt-7 pt-6 border-t border-gg-borde text-center text-sm text-gg-tinta-suave">
        ¿No tienes cuenta?
        <a href="{{ route('register') }}" class="text-gg-primario hover:underline font-medium">
            Crear cuenta
        </a>
    </p>

</x-layouts.publico>
