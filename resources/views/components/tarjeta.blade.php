{{--
    Tarjeta — contenedor de superficie con borde y esquinas de 12 px.
    Sin sombra. Sin degradado.

    Props:
      padding  string   Clase de padding ('p-5' por defecto)
      clase    string   Clases adicionales
--}}
@props([
    'padding' => 'p-5',
    'clase'   => '',
])

<div {{ $attributes->merge(['class' => "bg-gg-superficie border border-gg-borde rounded-tarjeta $padding $clase"]) }}>
    {{ $slot }}
</div>
