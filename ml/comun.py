"""Definiciones compartidas del pipeline de entrenamiento (ml/).

Centraliza los nombres de columna crudas del instrumento (deben coincidir con
InstrumentoSeeder), el orden fijo de predictores del modelo y la regla de
derivación de la variable dependiente Y.

IMPORTANTE — BLOQUEANTE DOCUMENTADO EN CLAUDE.md §7 y §11:
La función `derivar_categoria_riesgo` de este módulo es un PLACEHOLDER de
ingeniería, no un criterio clínico validado. El documento de tesis indica
explícitamente que la regla operativa para derivar Y a partir de P11-P13 y
P20 debe construirse con el equipo de Enfermería antes de considerar el
modelo entrenable para uso real. Los umbrales de abajo son arbitrarios
(elegidos solo para producir tres clases no degeneradas sobre datos
sintéticos) y DEBEN reemplazarse cuando exista el criterio clínico oficial.
Todo `modelo_v1.json` generado con esta versión debe documentarse como
provisional en la monografía (dominio "análisis estadístico" de PROBAST+AI).
"""

from __future__ import annotations

import numpy as np
import pandas as pd

SINTOMAS = ['diarrea', 'dolor_abdominal', 'vomito', 'nauseas', 'estrenimiento', 'fiebre']

ANTECEDENTES = [
    'parasitos_intestinales', 'inflamacion_intestino', 'infecciones_bacterianas',
    'ulceras_digestivas', 'estres', 'sobrepeso', 'gastroenteritis', 'hipersensibilidad_visceral',
]

MEDICAMENTOS = [
    'antidepresivos', 'antiinflamatorios', 'antibioticos', 'antiespasmodicos',
    'antidiarreicos', 'laxantes', 'corticosteroides',
]

ENFERMEDADES = [
    'reflujo_gastroesofagico', 'colitis', 'enfermedad_crohn', 'colecistitis',
    'enfermedad_celiaca', 'apendicitis', 'calculos', 'anemia',
]

# ----------------------------------------------------------------
# Orden fijo de predictores (X) del modelo — ~22 variables.
#
# P11, P12, P13 y P20 quedan FUERA de X a propósito: son la fuente de la
# etiqueta Y (ver derivar_categoria_riesgo). Incluirlas como predictores
# produciría fuga de información (el modelo "adivinaría" la regla en vez de
# generalizar a partir de hábitos/antecedentes independientes).
#
# P14/P15/P16/P17/P18 se resumen en conteos en vez de un dummy por ítem:
# reduce la dimensionalidad frente a los 347 registros disponibles
# (ver limitación de eventos-por-variable documentada en CLAUDE.md §7).
# El programa académico (SD3) se excluye por la misma razón (8 categorías
# nominales inflarían el modelo sin aporte claro al riesgo digestivo).
# ----------------------------------------------------------------
ORDEN_VARIABLES = [
    'edad', 'semestre', 'genero_femenino', 'genero_otro',
    'p01_frutas', 'p02_ensaladas', 'p03_integrales', 'p04_embutidos',
    'p05_empaquetados', 'p06_azucares', 'p07_frituras', 'p08_tiempos_comida',
    'p09_alcohol', 'p09_tabaco', 'p10_lavado_manos',
    'p14_antecedentes_personales_count', 'p15_antecedentes_familiares_count',
    'p16_medicamentos_recientes_count', 'p17_medicamentos_frecuentes_count',
    'p18_practica_deporte', 'p18_factores_riesgo_count', 'p19_frecuencia_deporte',
]

ETIQUETAS_VARIABLES = {
    'edad': 'Rango de edad',
    'semestre': 'Semestre académico',
    'genero_femenino': 'Género femenino (ref.: masculino)',
    'genero_otro': 'Género no binario / otro (ref.: masculino)',
    'p01_frutas': 'Consumo de frutas',
    'p02_ensaladas': 'Consumo de ensaladas frescas',
    'p03_integrales': 'Consumo de alimentos integrales',
    'p04_embutidos': 'Consumo de embutidos',
    'p05_empaquetados': 'Consumo de alimentos empaquetados',
    'p06_azucares': 'Consumo de azúcares refinados',
    'p07_frituras': 'Consumo de frituras',
    'p08_tiempos_comida': 'Tiempos de comida al día',
    'p09_alcohol': 'Consumo de alcohol',
    'p09_tabaco': 'Consumo de tabaco',
    'p10_lavado_manos': 'Lavado de manos antes de comer',
    'p14_antecedentes_personales_count': 'Cantidad de antecedentes personales',
    'p15_antecedentes_familiares_count': 'Cantidad de antecedentes familiares',
    'p16_medicamentos_recientes_count': 'Medicamentos consumidos recientemente',
    'p17_medicamentos_frecuentes_count': 'Medicamentos de consumo frecuente',
    'p18_practica_deporte': 'Práctica de deporte',
    'p18_factores_riesgo_count': 'Factores de estilo de vida de riesgo',
    'p19_frecuencia_deporte': 'Frecuencia de práctica deportiva',
}

CATEGORIA_BASE = 0
CATEGORIAS = [0, 1, 2]

# ----------------------------------------------------------------
# Regla PLACEHOLDER de derivación de Y — sistema de puntaje sobre P11-P13/P20.
# Ver docstring del módulo. Los cortes de puntaje se eligieron para producir
# tres clases no degeneradas sobre el dataset sintético, no por criterio
# clínico.
# ----------------------------------------------------------------
RECIENCIA_ALTA = 4  # valor_numerico de P11 (0-5) que cuenta como "reciente" (Último mes o Última semana)

UMBRAL_FRECUENCIA_MEDIA = 7   # suma P12 (0-18) por encima de la cual hay "síntomas recurrentes"
UMBRAL_FRECUENCIA_ALTA = 11   # suma P12 asociada a "sintomatología persistente"
UMBRAL_DOLOR = 4              # P13 (1-5)
UMBRAL_RECIENCIA = 2          # conteo P11 con recencia alta (0-6)
UMBRAL_ANTECEDENTES = 1       # conteo P20 (0-8)
UMBRAL_ANTECEDENTES_MULTIPLES = 3

PUNTAJE_CORTE_ALTO = 5
PUNTAJE_CORTE_MEDIO = 3


def derivar_categoria_riesgo(fila: pd.Series) -> int:
    """Deriva Y (0/1/2) desde P11, P12, P13 y P20 con un puntaje de señales de riesgo."""
    sintomas_frecuentes = sum(fila[f'p12_{s}'] for s in SINTOMAS)
    sintomas_recientes = sum(1 for s in SINTOMAS if fila[f'p11_{s}'] >= RECIENCIA_ALTA)
    dolor = fila['p13_dolor']
    enfermedades_count = sum(fila[f'p20_{e}'] for e in ENFERMEDADES)

    puntaje = 0
    puntaje += sintomas_frecuentes >= UMBRAL_FRECUENCIA_MEDIA
    puntaje += sintomas_frecuentes >= UMBRAL_FRECUENCIA_ALTA
    puntaje += sintomas_recientes >= UMBRAL_RECIENCIA
    puntaje += dolor >= UMBRAL_DOLOR
    puntaje += enfermedades_count >= UMBRAL_ANTECEDENTES
    puntaje += enfermedades_count >= UMBRAL_ANTECEDENTES_MULTIPLES

    if puntaje >= PUNTAJE_CORTE_ALTO:
        return 2
    if puntaje >= PUNTAJE_CORTE_MEDIO:
        return 1
    return 0


def construir_predictores(fila: pd.Series) -> pd.Series:
    """Transforma una fila cruda (columnas del instrumento) al vector X en ORDEN_VARIABLES."""
    valores = {
        'edad': fila['sd2_edad'],
        'semestre': fila['sd4_semestre'],
        'genero_femenino': int(fila['sd1_genero'] == 'femenino'),
        'genero_otro': int(fila['sd1_genero'] == 'otro'),
        'p01_frutas': fila['p01'],
        'p02_ensaladas': fila['p02'],
        'p03_integrales': fila['p03'],
        'p04_embutidos': fila['p04'],
        'p05_empaquetados': fila['p05'],
        'p06_azucares': fila['p06'],
        'p07_frituras': fila['p07'],
        'p08_tiempos_comida': fila['p08'],
        'p09_alcohol': fila['p09_alcohol'],
        'p09_tabaco': fila['p09_tabaco'],
        'p10_lavado_manos': fila['p10'],
        'p14_antecedentes_personales_count': sum(fila[f'p14_{a}'] for a in ANTECEDENTES),
        'p15_antecedentes_familiares_count': sum(fila[f'p15_{a}'] for a in ANTECEDENTES),
        'p16_medicamentos_recientes_count': sum(1 for m in MEDICAMENTOS if fila[f'p16_{m}'] >= RECIENCIA_ALTA),
        'p17_medicamentos_frecuentes_count': sum(1 for m in MEDICAMENTOS if fila[f'p17_{m}'] >= 2),
        'p18_practica_deporte': fila['p18_practica_deporte'],
        'p18_factores_riesgo_count': (
            fila['p18_sobrecarga_academica'] + fila['p18_estres_psicologico'] + fila['p18_sedentarismo']
        ),
        'p19_frecuencia_deporte': fila['p19'],
    }
    return pd.Series({k: valores[k] for k in ORDEN_VARIABLES})
