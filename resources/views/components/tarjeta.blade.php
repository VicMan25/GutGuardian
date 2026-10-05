{{--
    Tarjeta — contenedor de superficie con borde hairline, esquinas de 16 px
    y elevación mínima (elev-1). Sin degradado.

    Props:
      padding     string   Clase de padding ('p-5 sm:p-6' por defecto)
      clase       string   Clases adicionales
      interactiva bool     Se eleva al pasar el cursor (tarjetas clicables)
--}}
@props([
    'padding'     => 'p-5 sm:p-6',
    'clase'       => '',
    'interactiva' => false,
])

<div {{ $attributes->merge(['class' => 'bg-gg-superficie border border-gg-borde rounded-tarjeta shadow-elev-1 '
    .$padding.' '.$clase.($interactiva ? ' gg-interactiva' : '')]) }}>
    {{ $slot }}
</div>
