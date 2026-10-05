<x-layouts.publico titulo="Crear cuenta">

    <h1 class="font-display text-3xl font-medium text-gg-tinta">Crear cuenta</h1>
    <p class="text-base text-gg-tinta-suave mt-1.5 mb-7">Toma menos de dos minutos. Usaremos estos datos solo con fines académicos.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        {{-- Datos de acceso --}}
        <x-campo-texto
            nombre="name"
            etiqueta="Nombre completo"
            :valor="old('name')"
            requerido
            autocomplete="name"
            autofocus
            :error="$errors->first('name')"
        />

        <x-campo-texto
            nombre="email"
            etiqueta="Correo institucional"
            tipo="email"
            :valor="old('email')"
            requerido
            autocomplete="username"
            ayuda="Usa tu correo @umariana.edu.co"
            :error="$errors->first('email')"
        />

        <x-campo-texto
            nombre="codigo_participante"
            etiqueta="Código de participante"
            :valor="old('codigo_participante')"
            requerido
            ayuda="Código asignado en el estudio (ej. EST-001)"
            :error="$errors->first('codigo_participante')"
        />

        <x-campo-texto
            nombre="password"
            etiqueta="Contraseña"
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

        {{-- Perfil sociodemográfico --}}
        <div class="pt-6 border-t border-gg-borde">
            <p class="gg-rotulo mb-1">Datos sociodemográficos</p>
            <p class="text-sm text-gg-tinta-suave mb-5">Los usaremos para prellenar tu primera encuesta.</p>

            <div class="space-y-5">

                {{-- Género --}}
                <div class="w-full space-y-1.5">
                    <label for="genero" class="block text-sm font-medium text-gg-tinta">
                        Género
                        <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="genero"
                        name="genero"
                        required
                        class="w-full rounded-control border border-gg-borde text-gg-tinta bg-gg-superficie
                               {{ $errors->first('genero') ? '!border-gg-riesgo-alto bg-[#FDF6F3]' : '' }}"
                    >
                        <option value="">Selecciona una opción</option>
                        <option value="masculino"          {{ old('genero') === 'masculino'          ? 'selected' : '' }}>Masculino</option>
                        <option value="femenino"           {{ old('genero') === 'femenino'           ? 'selected' : '' }}>Femenino</option>
                        <option value="otro"               {{ old('genero') === 'otro'               ? 'selected' : '' }}>Otro</option>
                        <option value="prefiero_no_decir"  {{ old('genero') === 'prefiero_no_decir'  ? 'selected' : '' }}>Prefiero no decir</option>
                    </select>
                    @error('genero')
                        <p class="text-sm text-gg-riesgo-alto" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <x-campo-texto
                    nombre="edad"
                    etiqueta="Edad"
                    tipo="number"
                    :valor="old('edad')"
                    requerido
                    placeholder="Ej. 20"
                    :error="$errors->first('edad')"
                />

                {{-- Programa --}}
                <div class="w-full space-y-1.5">
                    <label for="programa_id" class="block text-sm font-medium text-gg-tinta">
                        Programa académico
                        <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="programa_id"
                        name="programa_id"
                        required
                        class="w-full rounded-control border border-gg-borde text-gg-tinta bg-gg-superficie
                               {{ $errors->first('programa_id') ? '!border-gg-riesgo-alto bg-[#FDF6F3]' : '' }}"
                    >
                        <option value="">Selecciona un programa</option>
                        @foreach($programas as $programa)
                            <option value="{{ $programa->id }}" {{ old('programa_id') == $programa->id ? 'selected' : '' }}>
                                {{ $programa->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('programa_id')
                        <p class="text-sm text-gg-riesgo-alto" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Semestre --}}
                <div class="w-full space-y-1.5">
                    <label for="semestre" class="block text-sm font-medium text-gg-tinta">
                        Semestre
                        <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="semestre"
                        name="semestre"
                        required
                        class="w-full rounded-control border border-gg-borde text-gg-tinta bg-gg-superficie
                               {{ $errors->first('semestre') ? '!border-gg-riesgo-alto bg-[#FDF6F3]' : '' }}"
                    >
                        <option value="">Selecciona un semestre</option>
                        @foreach(range(1, 10) as $sem)
                            <option value="{{ $sem }}" {{ old('semestre') == $sem ? 'selected' : '' }}>
                                Semestre {{ $sem }}
                            </option>
                        @endforeach
                    </select>
                    @error('semestre')
                        <p class="text-sm text-gg-riesgo-alto" role="alert">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <x-boton variante="primario" tipo="submit" tamano="lg" class="w-full justify-center" icono-final="flecha">
            Crear cuenta
        </x-boton>
    </form>

    <p class="mt-7 pt-6 border-t border-gg-borde text-center text-sm text-gg-tinta-suave">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" class="text-gg-primario hover:underline font-medium">
            Iniciar sesión
        </a>
    </p>

</x-layouts.publico>
