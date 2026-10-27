# Consulta a Enfermería: cortes del puntaje de nivel de riesgo

> **Para:** equipo de Enfermería (con copia a Nutrición y Dietética)
> **Fecha:** 2026-10-15
> **Contiene solo cifras agregadas** de los 347 registros reales; ningún dato individual.

## Contexto

La regla que asigna el nivel de riesgo (0 bajo, 1 medio, 2 alto) a partir de P11, P12, P13 y
P20 fue **aprobada tal cual** por Enfermería y Nutrición. No se propone cambiar las seis
señales, solo **revisar los dos cortes del puntaje**: ahora conocemos cómo se reparte en la
población real, cosa que no se sabía cuando se definieron.

Regla aprobada: cada señal suma 1 punto (puntaje de 0 a 6).

| Señal | Condición | Participantes que la cumplen |
|---|---|---:|
| Síntomas recurrentes | suma de frecuencias de P12 ≥ 7 | 62 |
| Síntomas persistentes | suma de frecuencias de P12 ≥ 11 | 8 |
| Síntomas recientes | ≥ 2 síntomas de P11 en el último mes o la última semana | 138 |
| Dolor intenso | P13 ≥ 4 | 44 |
| Algún antecedente | ≥ 1 enfermedad en P20 | 132 |
| Antecedentes múltiples | ≥ 3 enfermedades en P20 | 5 |

Cortes aprobados: **medio si puntaje ≥ 3, alto si puntaje ≥ 5.**

## Qué encontramos

| Puntaje | 0 | 1 | 2 | 3 | 4 | 5 | 6 |
|---|---:|---:|---:|---:|---:|---:|---:|
| Participantes | 127 | 113 | 58 | 38 | 9 | 2 | 0 |

Con los cortes aprobados quedan **298 en riesgo bajo, 47 en medio y 2 en alto**.

Con solo 2 casos, el modelo estadístico no puede aprender qué distingue el riesgo alto. Se
entrenó y ya está en uso (versión 2.0) con técnicas que compensan el desbalance, pero la parte
del modelo que corresponde al riesgo alto no es confiable. No se generaron datos artificiales
para compensar, porque solo copiarían a esos 2 participantes.

## Alternativas de corte (mismas señales)

| Opción | Medio si… | Alto si… | Bajo | Medio | Alto |
|---|---|---|---:|---:|---:|
| **Aprobada (actual)** | ≥ 3 | ≥ 5 | 298 | 47 | 2 |
| A | ≥ 3 | ≥ 4 | 298 | 38 | 11 |
| B | ≥ 2 | ≥ 4 | 240 | 96 | 11 |
| C | ≥ 2 | ≥ 3 | 240 | 58 | 49 |

Ninguna se recomienda desde Ingeniería: decidir qué combinación de señales justifica «riesgo
medio» o «riesgo alto» es un criterio clínico. Como referencia técnica, cualquier opción con al
menos 10 casos en riesgo alto ya permite estimar esa parte del modelo con regularización.

## Preguntas para el equipo

1. ¿Se mantienen los cortes aprobados (≥ 3 y ≥ 5), aceptando que el riesgo alto quede
   como limitación declarada en la monografía?
2. Si no, ¿qué cortes considera clínicamente adecuados? (A, B, C u otros.)
3. ¿Pueden usarse los registros de la prueba piloto para reentrenar el modelo más adelante?
   Requiere que el consentimiento lo cubra.

Cualquier cambio de cortes se aplica en `ml/comun.py`, se reentrena y se publica como una
versión nueva del modelo (v2.1), sin afectar los resultados ya entregados.
