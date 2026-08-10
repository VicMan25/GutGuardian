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

$claseInput = 'w-full rounded-control border text-sm text-gg-tinta bg-gg-superficie
               placeholder:text-gg-tinta-suave
               transition-colors duration-150
               focus:outline-none focus:border-gg-primario focus:ring-0
               disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gg-papel'
    . ($error ? ' border-gg-riesgo-alto bg-[#FDF4F2]' : ' border-gg-borde hover:border-gg-tinta-suave');
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

    <div class="relative">
        <input
            id="{{ $uid }}"
            type="{{ $tipo }}"
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

        {{-- Ícono de error --}}
        @if($error)
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gg-riesgo-alto pointer-events-none" aria-hidden="true">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </span>
        @endif
    </div>

    @if($error)
    <p id="{{ $errorId }}" class="text-2xs text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @elseif($ayuda)
    <p id="{{ $ayudaId }}" class="text-2xs text-gg-tinta-suave">{{ $ayuda }}</p>
    @endif

</div>
