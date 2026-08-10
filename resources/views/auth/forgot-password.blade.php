<x-layouts.publico titulo="Recuperar contraseña">

    @if(session('status'))
        <x-alerta tipo="exito" class="mb-6">{{ session('status') }}</x-alerta>
    @endif

    <h1 class="font-display text-xl font-medium text-gg-tinta mb-2">Recuperar contraseña</h1>
    <p class="text-sm text-gg-tinta-suave mb-6">
        Escribe tu correo y te enviaremos un enlace para restablecer tu contraseña.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-campo-texto
            nombre="email"
            etiqueta="Correo electrónico"
            tipo="email"
            :valor="old('email')"
            requerido
            autocomplete="email"
            autofocus
            :error="$errors->first('email')"
        />

        <x-boton variante="primario" type="submit" class="w-full justify-center">
            Enviar enlace
        </x-boton>
    </form>

    <p class="mt-6 text-center text-sm text-gg-tinta-suave">
        <a href="{{ route('login') }}" class="text-gg-primario hover:underline">
            Volver al inicio de sesión
        </a>
    </p>

</x-layouts.publico>
