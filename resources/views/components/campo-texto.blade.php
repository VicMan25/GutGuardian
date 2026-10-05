{{--
    Campo de texto del sistema de diseño GutGuardián.
    Cubre: text, email, password, number, date.

    Props:
      nombre       string   Atributo name e id
      etiqueta     string   Texto del label
      tipo         string   Tipo del input ('text' por defecto)
      valor        string   Valor actual
      placeholder  string
      requerido    bool
      discapacitado bool
      error        string   Mensaje de error (muestra estado de error)
      ayuda        string   Texto de ayuda debajo del campo
      autocomplete string
--}}
@props([
    'nombre',
    'etiqueta'     => '',
    'tipo'         => 'text',
    'valor'        => '',
    'placeholder'  => '',
    'requerido'    => false,
    'discapacitado'=> false,
    'error'        => null,
    'ayuda'        => null,
    'autocomplete' => null,
])

@php
$uid      = 'ct-' . $nombre . '-' . Str::random(6);
$errorId  = $uid . '-error';
$ayudaId  = $uid . '-ayuda';

$esPassword = $tipo === 'password';

$claseInput = 'w-full min-h-[46px] px-3.5 rounded-control border text-base text-gg-tinta bg-gg-superficie
               placeholder:text-[#8A948E]
               disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gg-papel'
    . ($esPassword || $error ? ' pr-11' : '')
    . ($error ? ' !border-gg-riesgo-alto bg-[#FDF6F3]' : ' border-gg-borde');
@endphp

<div class="w-full space-y-1.5">

    @if($etiqueta)
    <label for="{{ $uid }}" class="block text-sm font-medium text-gg-tinta">
        {{ $etiqueta }}
        @if($requerido)
            <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
        @endif
    </label>
    @endif

    <div class="relative" @if($esPassword) x-data="{ visible: false }" @endif>
        <input
            id="{{ $uid }}"
            @if($esPassword) :type="visible ? 'text' : 'password'" @endif
            name="{{ $nombre }}"
            value="{{ old($nombre, $valor) }}"
            placeholder="{{ $placeholder }}"
            {{ $requerido    ? 'required'  : '' }}
            {{ $discapacitado ? 'disabled' : '' }}
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($error) aria-describedby="{{ $errorId }}" aria-invalid="true" @endif
            @if($ayuda && !$error) aria-describedby="{{ $ayudaId }}" @endif
            {{ $attributes->merge(['class' => $claseInput]) }}
        />

        {{-- Mostrar / ocultar contraseña: reduce errores de tipeo sin campo de confirmación extra --}}
        @if($esPassword)
        <button type="button"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 w-9 h-9 inline-flex items-center justify-center rounded-[8px]
                       text-gg-tinta-suave hover:text-gg-tinta hover:bg-gg-papel transition-colors duration-150"
                @click="visible = !visible"
                :aria-pressed="visible"
                :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'">
            <x-icono nombre="ojo" class="w-[18px] h-[18px]" x-show="!visible" />
            <x-icono nombre="ojo-cerrado" class="w-[18px] h-[18px]" x-show="visible" x-cloak />
        </button>
        @endif

        {{-- Ícono de error --}}
        @if($error && !$esPassword)
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gg-riesgo-alto pointer-events-none" aria-hidden="true">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </span>
        @endif
    </div>

    @if($error)
    <p id="{{ $errorId }}" class="text-sm text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @elseif($ayuda)
    <p id="{{ $ayudaId }}" class="text-xs text-gg-tinta-suave">{{ $ayuda }}</p>
    @endif

</div>
