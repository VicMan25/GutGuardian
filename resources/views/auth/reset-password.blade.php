<x-layouts.publico titulo="Restablecer contraseña">

    <h1 class="font-display text-xl font-medium text-gg-tinta mb-6">Restablecer contraseña</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-campo-texto
            nombre="email"
            etiqueta="Correo electrónico"
            tipo="email"
            :valor="old('email', $request->email)"
            requerido
            autocomplete="username"
            autofocus
            :error="$errors->first('email')"
        />

        <x-campo-texto
            nombre="password"
            etiqueta="Nueva contraseña"
            tipo="password"
            requerido
            autocomplete="new-password"
            :error="$errors->first('password')"
        />

        <x-campo-texto
            nombre="password_confirmation"
            etiqueta="Confirmar contraseña"
            tipo="password"
            requerido
            autocomplete="new-password"
            :error="$errors->first('password_confirmation')"
        />

        <x-boton variante="primario" tipo="submit" class="w-full justify-center">
            Guardar contraseña
        </x-boton>
    </form>

</x-layouts.publico>
