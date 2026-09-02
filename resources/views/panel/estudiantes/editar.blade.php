<x-layouts.profesional :titulo="'Editar — '.$estudiante->name">

    <x-tarjeta class="max-w-lg">
        <form method="POST" action="{{ route('panel.usuarios.update', $estudiante) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-campo-texto nombre="name" etiqueta="Nombre completo" requerido :valor="old('name', $estudiante->name)" :error="$errors->first('name')" />
            <x-campo-texto nombre="email" etiqueta="Correo institucional" tipo="email" requerido :valor="old('email', $estudiante->email)" :error="$errors->first('email')" />
            <x-campo-texto nombre="codigo_participante" etiqueta="Código de participante" requerido :valor="old('codigo_participante', $estudiante->codigo_participante)" :error="$errors->first('codigo_participante')" />

            @php $perfil = $estudiante->perfil; @endphp
            <div class="pt-2 border-t border-gg-borde">
                <p class="text-xs font-medium text-gg-tinta-suave uppercase tracking-wide mb-4">Datos sociodemográficos</p>
                <div class="space-y-5">
                    <div class="w-full space-y-1.5">
                        <label class="block text-sm font-medium text-gg-tinta">Género <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span></label>
                        <select name="genero" required class="w-full rounded-control border border-gg-borde text-sm text-gg-tinta bg-gg-superficie focus:outline-none focus:border-gg-primario {{ $errors->first('genero') ? 'border-gg-riesgo-alto bg-[#FDF4F2]' : '' }}">
                            <option value="">Selecciona una opción</option>
                            <option value="masculino" {{ old('genero', $perfil?->genero) === 'masculino' ? 'selected' : '' }}>Masculino</option>
                            <option value="femenino" {{ old('genero', $perfil?->genero) === 'femenino' ? 'selected' : '' }}>Femenino</option>
                            <option value="otro" {{ old('genero', $perfil?->genero) === 'otro' ? 'selected' : '' }}>Otro</option>
                            <option value="prefiero_no_decir" {{ old('genero', $perfil?->genero) === 'prefiero_no_decir' ? 'selected' : '' }}>Prefiero no decir</option>
                        </select>
                        @error('genero') <p class="text-2xs text-gg-riesgo-alto" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <x-campo-texto nombre="edad" etiqueta="Edad" tipo="number" requerido :valor="old('edad', $perfil?->edad)" :error="$errors->first('edad')" />

                    <div class="w-full space-y-1.5">
                        <label class="block text-sm font-medium text-gg-tinta">Programa académico <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span></label>
                        <select name="programa_id" required class="w-full rounded-control border border-gg-borde text-sm text-gg-tinta bg-gg-superficie focus:outline-none focus:border-gg-primario {{ $errors->first('programa_id') ? 'border-gg-riesgo-alto bg-[#FDF4F2]' : '' }}">
                            <option value="">Selecciona un programa</option>
                            @foreach($programas as $programa)
                                <option value="{{ $programa->id }}" {{ (int) old('programa_id', $perfil?->programa_id) === $programa->id ? 'selected' : '' }}>{{ $programa->nombre }}</option>
                            @endforeach
                        </select>
                        @error('programa_id') <p class="text-2xs text-gg-riesgo-alto" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="w-full space-y-1.5">
                        <label class="block text-sm font-medium text-gg-tinta">Semestre <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span></label>
                        <select name="semestre" required class="w-full rounded-control border border-gg-borde text-sm text-gg-tinta bg-gg-superficie focus:outline-none focus:border-gg-primario {{ $errors->first('semestre') ? 'border-gg-riesgo-alto bg-[#FDF4F2]' : '' }}">
                            <option value="">Selecciona un semestre</option>
                            @foreach(range(1, 10) as $sem)
                                <option value="{{ $sem }}" {{ (int) old('semestre', $perfil?->semestre) === $sem ? 'selected' : '' }}>Semestre {{ $sem }}</option>
                            @endforeach
                        </select>
                        @error('semestre') <p class="text-2xs text-gg-riesgo-alto" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <x-boton variante="primario" tipo="submit" class="w-full justify-center">Guardar cambios</x-boton>
        </form>
    </x-tarjeta>

</x-layouts.profesional>
