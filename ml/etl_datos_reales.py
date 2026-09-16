"""ETL del dataset REAL de 347 participantes (docs/ENCUESTAS ANALISIS TABLAS .xlsx,
hoja "Respuestas de formulario 1") hacia el esquema crudo que consume
preparar_datos.py — el mismo que produce generar_dataset_sintetico.py
(codigo_participante, sd1_genero, sd2_edad, sd3_programa, sd4_semestre,
p01..p20_*).

El archivo de origen NUNCA se sube al repositorio (ver .gitignore y
CLAUDE.md §10) y este script tampoco imprime respuestas individuales; solo
resúmenes agregados. La salida (ml/datos/dataset_real.csv) cae dentro del
patrón `dataset_real.*` ya ignorado por git.

Resuelve tres problemas de codificación identificados en el archivo real
frente al esquema documentado en CLAUDE.md §6 y al InstrumentoSeeder actual:

1. "Semestre y programa" es texto libre (319 valores distintos para 347
   filas): se parte en sd4_semestre (entero) y sd3_programa (catálogo de 8
   categorías del InstrumentoSeeder, con "Otro"=8 para programas fuera del
   catálogo, p.ej. Trabajo Social, Mercadeo, Ingeniería Mecatrónica).
2. El texto completo de las opciones de escala cambió de redacción durante
   la recolección (p.ej. "3 - Dolor moderado" / "3 - Mediano dolor" para el
   mismo código). La codificación se hace por palabra clave o por el dígito
   incrustado en el texto, nunca por comparación de texto completo.
3. Las preguntas de selección múltiple (P14, P15, P18, P20) llegan como una
   sola celda con las opciones separadas por coma: se separan y cada opción
   se compara contra un diccionario de palabras clave por ítem.

Salida: ml/datos/dataset_real.csv (mismas columnas que dataset_sintetico.csv)
        ml/datos/reporte_etl_datos_reales.csv (filas con parseo ambiguo de
        semestre/programa, para revisión manual — también real, también
        ignorado por git)
"""

from __future__ import annotations

import re
import unicodedata
from pathlib import Path

import pandas as pd

from comun import ANTECEDENTES, ENFERMEDADES, MEDICAMENTOS, SINTOMAS

ENTRADA = Path(__file__).parent.parent / 'docs' / 'ENCUESTAS ANALISIS TABLAS .xlsx'
HOJA = 'Respuestas de formulario 1'

SALIDA = Path(__file__).parent / 'datos' / 'dataset_real.csv'
REPORTE = Path(__file__).parent / 'datos' / 'reporte_etl_datos_reales.csv'

# Nombres de columna crudas del archivo real, en el mismo orden del
# instrumento (ver docs/ notas de extracción). Los ítems de matriz repiten
# el enunciado de la pregunta; se referencian por posición.
COL_CODIGO = 'Código asignado'
COL_GENERO = 'Género'
COL_EDAD = 'Edad'
COL_SEMESTRE_PROGRAMA = 'Semestre y programa'


# ----------------------------------------------------------------
# Normalización y parsers por tipo de pregunta (punto 2)
# ----------------------------------------------------------------

def normalizar(texto: str) -> str:
    """minúsculas, sin tildes, sin espacios sobrantes — para comparar por palabra clave."""
    texto = unicodedata.normalize('NFKD', str(texto)).encode('ascii', 'ignore').decode('ascii')
    return texto.lower().strip()


# Siempre=3 … Nunca=0 (InstrumentoSeeder::opcionesEscalaFrecuencia). La
# redacción entre paréntesis varió entre encuestados; la palabra clave no.
_ESCALA_FRECUENCIA = [
    ('algunas veces', 2),
    ('ocasionalmente', 1),
    ('siempre', 3),
    ('nunca', 0),
]


def parsear_escala_frecuencia(texto: str, contexto: str) -> int:
    n = normalizar(texto)
    for palabra, valor in _ESCALA_FRECUENCIA:
        if palabra in n:
            return valor
    raise ValueError(f'{contexto}: no se reconoce la escala de frecuencia en {texto!r}')


# Última semana=5 … Nunca=0 (InstrumentoSeeder::opcionesTemporalidad). Estas
# columnas llegan sin prefijo de letra en el archivo real.
_TEMPORALIDAD = [
    ('ultima semana', 5),
    ('ultimo mes', 4),
    ('ultimos 2 meses', 3),
    ('ultimos 6 meses', 2),
    ('ultimo ano', 1),
    ('nunca', 0),
]


def parsear_temporalidad(texto: str, contexto: str) -> int:
    n = normalizar(texto)
    for palabra, valor in _TEMPORALIDAD:
        if palabra in n:
            return valor
    raise ValueError(f'{contexto}: no se reconoce la temporalidad en {texto!r}')


def parsear_tiempos_comida(texto: str, contexto: str) -> int:
    """P08: Ninguna=0 … Más de 5=6. Comprobar 'ninguna' y 'mas de 5' ANTES de
    extraer dígitos: "F. Mas de 5 veces" contiene un '5' que codificaría mal."""
    n = normalizar(texto)
    if 'ninguna' in n:
        return 0
    if 'mas de 5' in n:
        return 6
    match = re.search(r'(\d)', texto)
    if match and 1 <= int(match.group(1)) <= 5:
        return int(match.group(1))
    raise ValueError(f'{contexto}: no se reconoce el número de tiempos de comida en {texto!r}')


def parsear_dolor(texto: str, contexto: str) -> int:
    """P13: el dígito 1-5 va siempre incrustado en el texto ("C. 3 - Dolor
    moderado" / "C. 3 - Mediano dolor"); el descriptor cambió de redacción
    pero el número no, así que se ignora el descriptor por completo."""
    match = re.search(r'(\d)', texto)
    if match and 1 <= int(match.group(1)) <= 5:
        return int(match.group(1))
    raise ValueError(f'{contexto}: no se reconoce la escala de dolor en {texto!r}')


def parsear_multiple(texto: str, mapa_palabras_clave: dict[str, list[str]]) -> dict[str, int]:
    """P14/P15/P18/P20: separa la celda por comas y compara cada opción
    marcada contra las palabras clave de cada ítem canónico. "Ninguna (de las
    anteriores)" no necesita mapeo propio: si ninguna palabra clave coincide,
    todos los indicadores quedan en 0, que es exactamente su semántica."""
    banderas = {clave: 0 for clave in mapa_palabras_clave}
    if pd.isna(texto):
        return banderas
    for opcion in str(texto).split(','):
        n = normalizar(opcion)
        for clave, palabras in mapa_palabras_clave.items():
            if any(palabra in n for palabra in palabras):
                banderas[clave] = 1
    return banderas


# Palabras clave (ya normalizadas, sin tildes) por ítem canónico de comun.py.
# Tolerantes a los errores de tipeo observados en el archivo real (p.ej.
# "hipersensibilidad viceral" en vez de "visceral", "Chron" en vez de "Crohn").
_ANTECEDENTES_PALABRAS = {
    'parasitos_intestinales': ['parasito'],
    'inflamacion_intestino': ['inflamacion'],
    'infecciones_bacterianas': ['infeccion'],
    'ulceras_digestivas': ['ulcera'],
    'estres': ['estres'],
    'sobrepeso': ['sobrepeso'],
    'gastroenteritis': ['gastroenteritis'],
    'hipersensibilidad_visceral': ['hipersensibilidad'],
}
assert set(_ANTECEDENTES_PALABRAS) == set(ANTECEDENTES)

_ENFERMEDADES_PALABRAS = {
    'reflujo_gastroesofagico': ['reflujo'],
    'colitis': ['colitis'],
    'enfermedad_crohn': ['crohn', 'chron'],
    'colecistitis': ['colecistitis'],
    'enfermedad_celiaca': ['celiaca'],
    'apendicitis': ['apendicitis'],
    'calculos': ['calculo'],
    'anemia': ['anemia'],
}
assert set(_ENFERMEDADES_PALABRAS) == set(ENFERMEDADES)

_ESTILO_VIDA_PALABRAS = {
    'sobrecarga_academica': ['sobrecarga'],
    'estres_psicologico': ['estres psicologico'],
    'sedentarismo': ['sedentarismo'],
    'practica_deporte': ['practica deporte'],
}


# ----------------------------------------------------------------
# Punto 1 — "Semestre y programa" en texto libre -> sd4_semestre + sd3_programa
# ----------------------------------------------------------------

_NUMERO_PALABRA = {
    # Ordinales
    'primer': 1, 'primero': 1, 'segundo': 2, 'tercer': 3, 'tercero': 3,
    'cuarto': 4, 'quinto': 5, 'sexto': 6, 'septimo': 7, 'setimo': 7,
    'octavo': 8, 'noveno': 9, 'decimo': 10,
    # Cardinales ("Seis - ingeniería de sistemas")
    'uno': 1, 'dos': 2, 'tres': 3, 'cuatro': 4, 'cinco': 5,
    'seis': 6, 'siete': 7, 'ocho': 8, 'nueve': 9, 'diez': 10,
}

# Numerales romanos ("V semestre", "Fisioterapia IV semestre", "VII FISIOTERAPIA").
# Se buscan como palabra completa para no capturar la "I" o "X" de otras palabras.
_ROMANO_A_NUMERO = {
    'x': 10, 'ix': 9, 'viii': 8, 'vii': 7, 'vi': 6,
    'v': 5, 'iv': 4, 'iii': 3, 'ii': 2, 'i': 1,
}
_ROMANO_RE = re.compile(r'\b(x|ix|viii|vii|vi|v|iv|iii|ii|i)\b')

# Catálogo SD3 de InstrumentoSeeder::sembrarSociodemografico. Programas reales
# fuera de este catálogo (Trabajo Social, Mercadeo, Contaduría, Regencia de
# Farmacia, Terapia Ocupacional, Ingeniería Mecatrónica, preuniversitario)
# no tienen código propio en el instrumento actual y caen en "Otro"=8.
_PROGRAMA_PALABRAS = [
    ('enfermeria', 1),
    ('nutricion', 2),
    ('medicina', 3),
    ('ingenieria de sistemas', 4),
    ('psicologia', 5),
    ('bacteriologia', 6),
    ('fisioterapia', 7),
]
PROGRAMA_OTRO = 8


def parsear_semestre(texto_normalizado: str) -> int | None:
    # Un dígito pegado a una letra ("5to", "8vo", "7mo", "9no", "2semestre")
    # rompe el \b de cierre porque dígito y letra son ambos \w: se separa
    # siempre, sea sufijo ordinal o la palabra "semestre" sin espacio.
    sin_sufijos = re.sub(r'(\d)(?=[a-z])', r'\1 ', texto_normalizado)

    match = re.search(r'\b(10|[1-9])\b', sin_sufijos)
    if match:
        return int(match.group(1))
    for palabra, numero in _NUMERO_PALABRA.items():
        if palabra in texto_normalizado:
            return numero
    match = _ROMANO_RE.search(texto_normalizado)
    if match:
        return _ROMANO_A_NUMERO[match.group(1)]
    return None


def parsear_programa(texto_normalizado: str) -> int:
    for palabra, codigo in _PROGRAMA_PALABRAS:
        if palabra in texto_normalizado:
            return codigo
    return PROGRAMA_OTRO


def parsear_semestre_programa(texto: str) -> tuple[int, int, bool]:
    """Devuelve (semestre, programa_id, ambiguo). ambiguo=True cuando no se
    pudo extraer semestre (p.ej. "Preuniversitario", sin semestre aplicable)
    o el programa no coincidió con el catálogo (cayó en "Otro") — estas filas
    se listan en el reporte para revisión manual, no se descartan."""
    n = normalizar(texto)
    semestre = parsear_semestre(n)
    programa = parsear_programa(n)
    ambiguo = semestre is None or programa == PROGRAMA_OTRO
    return (semestre or 0), programa, ambiguo


# ----------------------------------------------------------------
# Construcción del DataFrame en el esquema crudo del pipeline
# ----------------------------------------------------------------

_SD2_RANGOS = [(18, 1), (20, 2), (22, 3), (25, 4)]  # límite superior -> código; >25 -> 5


def parsear_edad(edad: int) -> int:
    """SD2 del instrumento pide un RANGO (17-18 / 19-20 / 21-22 / 23-25 / 26+),
    no la edad exacta: se bucketiza la edad real al mismo código 1-5 que usa
    InstrumentoSeeder, para que sea compatible con construir_predictores."""
    for limite, codigo in _SD2_RANGOS:
        if edad <= limite:
            return codigo
    return 5


_GENERO = {'masculino': 1, 'femenino': 2}


def construir_fila(fila: pd.Series, cols: list[str], errores: list[str]) -> dict | None:
    codigo = f'REAL-{int(fila[COL_CODIGO]):04d}'
    contexto_base = f'{codigo}'
    try:
        genero_normalizado = normalizar(fila[COL_GENERO])
        if genero_normalizado not in _GENERO:
            raise ValueError(f'género no reconocido: {fila[COL_GENERO]!r}')

        datos: dict[str, object] = {
            'codigo_participante': codigo,
            'sd1_genero': 'masculino' if _GENERO[genero_normalizado] == 1 else 'femenino',
            'sd2_edad': parsear_edad(int(fila[COL_EDAD])),
        }

        semestre, programa, _ambiguo = parsear_semestre_programa(fila[COL_SEMESTRE_PROGRAMA])
        datos['sd3_programa'] = programa
        datos['sd4_semestre'] = semestre

        # P01-P07: escala de frecuencia estándar
        for i, p in enumerate(['p01', 'p02', 'p03', 'p04', 'p05', 'p06', 'p07']):
            datos[p] = parsear_escala_frecuencia(fila[cols[5 + i]], f'{contexto_base} {p}')

        # P08: tiempos de comida
        datos['p08'] = parsear_tiempos_comida(fila[cols[12]], f'{contexto_base} p08')

        # P09: alcohol / tabaco (matriz de 2 ítems, escala de frecuencia)
        datos['p09_alcohol'] = parsear_escala_frecuencia(fila[cols[13]], f'{contexto_base} p09_alcohol')
        datos['p09_tabaco'] = parsear_escala_frecuencia(fila[cols[14]], f'{contexto_base} p09_tabaco')

        # P10: lavado de manos
        datos['p10'] = parsear_escala_frecuencia(fila[cols[15]], f'{contexto_base} p10')

        # P11: temporalidad de síntomas (matriz de 6, sin prefijo de letra)
        for i, s in enumerate(SINTOMAS):
            datos[f'p11_{s}'] = parsear_temporalidad(fila[cols[16 + i]], f'{contexto_base} p11_{s}')

        # P12: frecuencia de síntomas último mes (matriz de 6, escala estándar)
        for i, s in enumerate(SINTOMAS):
            datos[f'p12_{s}'] = parsear_escala_frecuencia(fila[cols[22 + i]], f'{contexto_base} p12_{s}')

        # P13: escala de dolor 1-5
        datos['p13_dolor'] = parsear_dolor(fila[cols[28]], f'{contexto_base} p13')

        # P14/P15: antecedentes personales/familiares (selección múltiple)
        p14 = parsear_multiple(fila[cols[29]], _ANTECEDENTES_PALABRAS)
        for a in ANTECEDENTES:
            datos[f'p14_{a}'] = p14[a]
        p15 = parsear_multiple(fila[cols[30]], _ANTECEDENTES_PALABRAS)
        for a in ANTECEDENTES:
            datos[f'p15_{a}'] = p15[a]

        # P16: temporalidad de medicamentos (matriz de 7, sin prefijo de letra)
        for i, m in enumerate(MEDICAMENTOS):
            datos[f'p16_{m}'] = parsear_temporalidad(fila[cols[31 + i]], f'{contexto_base} p16_{m}')

        # P17: frecuencia de medicamentos último mes (matriz de 7, escala estándar)
        for i, m in enumerate(MEDICAMENTOS):
            datos[f'p17_{m}'] = parsear_escala_frecuencia(fila[cols[38 + i]], f'{contexto_base} p17_{m}')

        # P18: estilo de vida (selección múltiple)
        p18 = parsear_multiple(fila[cols[45]], _ESTILO_VIDA_PALABRAS)
        datos['p18_sobrecarga_academica'] = p18['sobrecarga_academica']
        datos['p18_estres_psicologico'] = p18['estres_psicologico']
        datos['p18_sedentarismo'] = p18['sedentarismo']
        datos['p18_practica_deporte'] = p18['practica_deporte']

        # P19: frecuencia de práctica deportiva
        datos['p19'] = parsear_escala_frecuencia(fila[cols[46]], f'{contexto_base} p19')

        # P20: enfermedades diagnosticadas (selección múltiple)
        p20 = parsear_multiple(fila[cols[47]], _ENFERMEDADES_PALABRAS)
        for e in ENFERMEDADES:
            datos[f'p20_{e}'] = p20[e]

        return datos
    except ValueError as exc:
        errores.append(str(exc))
        return None


def construir_dataset() -> tuple[pd.DataFrame, pd.DataFrame]:
    crudo = pd.read_excel(ENTRADA, sheet_name=HOJA)
    cols = crudo.columns.tolist()

    filas: list[dict] = []
    errores: list[str] = []
    ambiguos: list[dict] = []

    for _, fila in crudo.iterrows():
        registro = construir_fila(fila, cols, errores)
        if registro is None:
            continue
        filas.append(registro)

        semestre, programa, ambiguo = parsear_semestre_programa(fila[COL_SEMESTRE_PROGRAMA])
        if ambiguo:
            ambiguos.append({
                'codigo_participante': registro['codigo_participante'],
                'texto_original': fila[COL_SEMESTRE_PROGRAMA],
                'semestre_extraido': semestre,
                'programa_id_extraido': programa,
            })

    dataset = pd.DataFrame(filas)
    reporte = pd.DataFrame(ambiguos)

    print(f'Filas leídas del archivo real: {len(crudo)}')
    print(f'Filas convertidas correctamente: {len(dataset)}')
    if errores:
        print(f'Filas excluidas por error de codificación ({len(errores)}):')
        for e in errores:
            print(f'  - {e}')
    print(f'Filas con semestre/programa ambiguo (van al reporte, NO se excluyen): {len(reporte)}')

    return dataset, reporte


def main() -> None:
    if not ENTRADA.exists():
        raise SystemExit(
            f'No se encontró {ENTRADA}. Este archivo contiene datos reales de '
            'participantes y nunca se sube al repositorio: pídelo directamente '
            'y colócalo en docs/ con ese nombre exacto antes de correr este script.'
        )

    dataset, reporte = construir_dataset()

    SALIDA.parent.mkdir(parents=True, exist_ok=True)
    dataset.to_csv(SALIDA, index=False)
    print(f'Dataset real exportado: {SALIDA} ({len(dataset)} filas, {len(dataset.columns)} columnas)')

    if not reporte.empty:
        reporte.to_csv(REPORTE, index=False)
        print(f'Reporte de semestre/programa ambiguo: {REPORTE} ({len(reporte)} filas para revisión manual)')


if __name__ == '__main__':
    main()
