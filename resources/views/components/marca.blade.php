{{--
    Marca de GutGuardián: espacio reservado para el logo oficial + nombre.

    El logo NO se inventa: el componente busca el archivo oficial en
    public/img/logo.svg (o logo.png). Mientras no exista, muestra un
    recuadro vacío del mismo tamaño para que la composición no cambie
    cuando se incorpore. Para colocar el logo basta con copiar el archivo
    a esa ruta; no hay que tocar ninguna vista.

    Props:
      tamano    string   'sm' (32 px) | 'md' (40 px) | 'lg' (56 px)
      tono      string   'claro' (sobre fondo verde) | 'oscuro' (sobre papel)
      nombre    bool     Muestra el nombre del producto junto al logo
      subtitulo string   Texto opcional bajo el nombre
      href      string   Si se provee, la marca es un enlace
--}}
@props([
    'tamano'    => 'md',
    'tono'      => 'oscuro',
    'nombre'    => true,
    'subtitulo' => null,
    'href'      => null,
])

@php
    $archivoLogo = collect(['img/logo.svg', 'img/logo.png'])
        ->first(fn ($ruta) => file_exists(public_path($ruta)));

    $caja = [
        'sm' => 'w-8 h-8 rounded-[9px]',
        'md' => 'w-10 h-10 rounded-[11px]',
        'lg' => 'w-14 h-14 rounded-[14px]',
    ][$tamano];

    $texto = [
        'sm' => 'text-base',
        'md' => 'text-md',
        'lg' => 'text-xl',
    ][$tamano];

    $colorNombre = $tono === 'claro' ? 'text-white' : 'text-gg-primario';
    $colorSub    = $tono === 'claro' ? 'text-white/70' : 'text-gg-tinta-suave';
    $colorVacio  = $tono === 'claro' ? 'text-white' : 'text-gg-primario';

    $etiqueta = $href ? 'a' : 'div';
@endphp

<{{ $etiqueta }}
    @if($href) href="{{ $href }}" aria-label="GutGuardián — inicio" @endif
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 min-w-0 rounded-control']) }}
>
    <span class="gg-logo-espacio {{ $caja }} {{ $archivoLogo ? '' : 'gg-logo-espacio--vacio '.$colorVacio }}"
          data-espacio-logo
          @unless($archivoLogo) aria-hidden="true" @endunless>
        @if($archivoLogo)
            <img src="{{ asset($archivoLogo) }}" alt="{{ $nombre ? '' : 'GutGuardián' }}" class="w-full h-full object-contain">
        @endif
    </span>

    @if($nombre)
    <span class="min-w-0 leading-tight">
        <span class="block font-display font-medium tracking-tight {{ $texto }} {{ $colorNombre }}">GutGuardián</span>
        @if($subtitulo)
            <span class="block text-xs {{ $colorSub }} truncate">{{ $subtitulo }}</span>
        @endif
    </span>
    @endif
</{{ $etiqueta }}>
