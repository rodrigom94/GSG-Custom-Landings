# Propuestas de Escalabilidad

Este documento resume caminos posibles para evolucionar GSG Custom Landings sin perder control sobre seguridad, mantenibilidad y rendimiento.

Objetivo editorial deseado:

- Toda landing se compone por secciones.
- Cada landing funciona como un rompecabezas de piezas reutilizables.
- Cada sección sigue siendo editable dentro de la landing concreta.
- Algunas secciones pueden tener variantes radicalmente distintas entre campañas.
- La IA puede asistir en generar o adaptar variantes, pero no debe romper el contrato interno del plugin.

## Criterios de evaluación

| Criterio | Qué significa en este plugin |
| --- | --- |
| Velocidad editorial | Qué tan rápido se puede crear una nueva landing o quiz |
| Reutilización | Cuánto se reaprovechan módulos, lógica y componentes |
| Rendimiento | Costo de render en frontend y en admin |
| Trazabilidad | Qué tan fácil es depurar, versionar y auditar cambios |
| Riesgo IA | Qué tanto dependemos de respuestas no determinísticas |
| Escalabilidad | Qué tan bien soporta muchas campañas, variantes y equipos |

## Tabla de propuestas

| Propuesta | Descripción | Pros | Contras | Cuándo conviene |
| --- | --- | --- | --- | --- |
| 1. Módulos fijos sin IA en runtime | El plugin crece con variantes y módulos controlados en PHP. La edición se hace con campos y bloques conocidos, sin depender de HTML generado por IA para el frontend ni para el admin | Máximo rendimiento, seguridad más fuerte, fácil de probar, mantenimiento simple, muy buena compatibilidad con WordPress | Menos flexibilidad creativa, creación de nuevas variantes más manual, el admin puede crecer bastante | Cuando se prioriza estabilidad operativa y performance desde el inicio |
| 2. IA para HTML preview y generación asistida | La IA genera previews HTML o propuestas visuales, pero el resultado final se traduce manualmente o semiautomáticamente a módulos internos del plugin | Acelera ideación, mejora velocidad editorial, útil para campañas nuevas, mantiene el runtime controlado | Puede haber diferencia entre preview y resultado final, requiere validación humana, el preview no debe ser la fuente de verdad | Cuando se quiere usar IA sin comprometer el rendimiento productivo |
| 3. Schema versionado + secciones ensamblables + IA asistente | Cada landing vive en un schema interno versionado compuesto por secciones. Cada sección tiene tipo, variante, configuración y versión. La IA propone estructura, copys o nuevas variantes, pero el admin y el frontend se construyen desde ese schema y componentes propios | Mejor equilibrio entre escalabilidad, trazabilidad, rendimiento, reuso y automatización futura; base sólida para quizzes y landings complejas; permite que una sección cambie mucho sin romper el sistema | Mayor costo inicial de arquitectura, requiere diseñar schema, registro de secciones, migraciones y catálogo de variantes | Recomendación principal si el plugin va a crecer de verdad |

## Análisis específico: HTML preview con IA

### Opción A. HTML preview como inspiración visual

Flujo:

1. El editor completa un brief.
2. La IA genera uno o varios previews HTML.
3. El editor elige una propuesta.
4. La propuesta se traduce manual o semiautomáticamente a módulos del plugin.

Pros:

- Bajo impacto en la arquitectura existente.
- Buen punto de entrada para explorar IA sin comprometer el runtime.
- El frontend real sigue siendo rápido y seguro.

Contras:

- Puede haber diferencia entre el preview y el resultado final.
- Sigue existiendo trabajo humano de traducción.

### Opción B. HTML preview como entrada a un schema estructural

Flujo:

1. La IA genera HTML preview.
2. Un parser detecta secciones y elementos semánticos.
3. El plugin convierte eso a un schema interno.
4. El admin se construye desde ese schema.
5. El frontend real renderiza módulos propios del plugin.

Pros:

- Mucho más escalable que usar el HTML directamente.
- El HTML funciona como puente creativo, no como contrato final.
- Permite iterar con IA conservando una base mantenible.

Contras:

- Requiere inversión técnica relevante.
- Necesita reglas fuertes para que la IA produzca HTML parseable.

### Opción C. HTML preview como fuente directa del admin dinámico

Flujo:

1. La IA genera HTML preview.
2. El plugin analiza nodos, textos, inputs y CTAs.
3. Genera metaboxes o pantallas admin específicas para esa landing.
4. Las ediciones posteriores intentan sincronizarse con el HTML original.

Pros:

- Muy vistoso a nivel producto.
- Puede reducir la fricción inicial para campañas atípicas.

Contras:

- Alto riesgo de fragilidad.
- La sincronización entre HTML, campos, estado y render final se vuelve compleja.
- Es el camino menos eficiente si el objetivo principal es rendimiento y escalado estable.

## Alternativas que priorizan rendimiento

| Enfoque | Cómo funcionaría | Ventaja principal | Riesgo principal |
| --- | --- | --- | --- |
| Módulos fijos en PHP | Cada layout usa vistas y campos controlados | Muy rápido y simple de mantener | Escala peor en variedad visual |
| Preview IA desacoplado | El preview sirve para ideación, no para runtime | Buen balance entre UX y performance | Puede haber drift entre preview y versión final |
| Schema versionado | El plugin usa una estructura interna para admin y frontend | Mejor base para escalar con orden | Mayor complejidad inicial |

## Recomendación técnica

### Recomendación 1. Corto plazo

Usar un core modular sin IA en el runtime y permitir IA solo para generar previews y propuestas de contenido.

Esto implica:

1. Mantener plantillas controladas por variantes.
2. Definir un catálogo de módulos reutilizables.
3. Guardar configuración por landing en metadatos estructurados.
4. Mantener hooks de integración y settings globales como ya está planteado.

### Recomendación 2. Mediano plazo

Migrar el modelo a un schema versionado por landing y usar la IA para sugerir ese schema, no para controlar directamente el HTML productivo.

Esto habilita:

1. Generación de previews más confiables.
2. Render dinámico más rápido y predecible.
3. Mejor trazabilidad para QA y soporte.
4. Compatibilidad con quizzes, múltiples pasos, scoring e integraciones futuras.

## Opción 3 aterrizada a tu caso

La opción 3 debe implementarse con esta idea base:

Una landing no es una plantilla rígida completa. Una landing es una lista ordenada de secciones. Cada sección es una pieza del rompecabezas con identidad propia.

Cada sección debería tener al menos estos atributos:

| Campo | Propósito |
| --- | --- |
| `section_type` | Tipo funcional: hero, counter, benefits, form, testimonials, quiz_step, FAQ, CTA, footer |
| `variant` | Variante visual o estructural dentro del tipo |
| `version` | Versión técnica de esa variante |
| `settings` | Configuración editable de la sección |
| `ai_metadata` | Instrucciones, prompts, restricciones o historial de ayuda IA |
| `visibility_rules` | Reglas opcionales para mostrar, ocultar o personalizar |
| `order` | Posición dentro de la landing |

Ejemplo conceptual:

```json
{
	"landing": {
		"title": "Invita a un amigo",
		"sections": [
			{
				"section_type": "hero",
				"variant": "referral-split-v1",
				"version": 1,
				"settings": {}
			},
			{
				"section_type": "counter",
				"variant": "circular-highlight-v3",
				"version": 3,
				"settings": {}
			},
			{
				"section_type": "form",
				"variant": "two-column-referral-v1",
				"version": 1,
				"settings": {}
			}
		]
	}
}
```

## Variaciones recomendadas de la opción 3

### Variante 3A. Secciones fijas con variantes registradas

Cada tipo de sección tiene un catálogo cerrado de variantes registradas en código.

Ejemplo:

- `counter / minimal-v1`
- `counter / badge-ribbon-v1`
- `counter / circular-highlight-v3`
- `counter / split-stat-v2`

Pros:

- Muy buena performance.
- Muy fácil de testear.
- Buen control visual y técnico.
- La edición por secciones es clara para el equipo.

Contras:

- Cada nueva variante requiere desarrollo.
- Menos libertad para cambios extremos de diseño.

Cuándo conviene:

- Cuando el equipo quiere orden y estabilidad primero.

### Variante 3B. Secciones registradas + IA para mutaciones controladas

Cada sección parte de una variante base registrada, pero la IA puede proponer una mutación controlada de contenido, layout o micro-estructura dentro de límites definidos.

Ejemplo para un contador:

- Base: `counter / circular-highlight-v3`
- Mutación IA permitida: cambiar jerarquía textual, estilo del badge, cantidad de métricas, microcopy, disposición de apoyo, sin cambiar contratos del bloque.

Pros:

- Mantiene el rendimiento del sistema base.
- Permite variaciones fuertes entre landings.
- La IA opera dentro de un perímetro controlado.
- Es una buena respuesta a bloques que cambian mucho entre campañas.

Contras:

- Requiere definir qué partes de la sección son mutables y cuáles no.
- La capa de prompts y validación debe estar bien diseñada.

Cuándo conviene:

- Cuando hay secciones con alta variación de diseño, pero no quieres caer en HTML libre.

### Variante 3C. Secciones versionadas con fork administrable

Una sección puede nacer de una variante base y luego crear un fork versionado para una campaña específica. Ese fork queda guardado como nueva variante reutilizable o como variante privada de una landing.

Ejemplo:

1. Se toma `counter / split-stat-v2`.
2. La IA propone una adaptación para una landing de scholarships.
3. El editor aprueba la propuesta.
4. El sistema guarda `counter / split-stat-scholarships-v1`.

Pros:

- Muy potente para construir biblioteca real de bloques.
- Convierte trabajo puntual en activos reutilizables.
- Mantiene trazabilidad por versión.

Contras:

- Necesita gobierno de variantes para evitar explosión de versiones.
- Requiere una UX de admin más madura.

Cuándo conviene:

- Cuando el plugin va a tener muchas campañas y vale la pena invertir en una librería viva de secciones.

## Recomendación concreta para secciones radicalmente distintas

Para casos como el contador, donde una misma intención funcional puede verse muy distinta entre landings, la mejor estrategia no es tratarlo como un solo bloque rígido.

La recomendación es esta:

1. Mantener un `section_type` estable: por ejemplo `counter`.
2. Permitir múltiples `variant` muy diferentes entre sí.
3. Hacer que cada variante exponga sus propios `settings` editables.
4. Permitir que la IA ayude a generar una nueva variante o a mutar una existente.
5. Guardar toda nueva mutación aprobada como una variante versionada, no como HTML suelto.

Eso te da exactamente el modelo rompecabezas que buscas:

- La landing se arma por piezas.
- Cada pieza puede ser muy distinta según la campaña.
- Igual puedes editar la pieza dentro de la landing.
- La IA ayuda a evolucionar piezas sin romper la arquitectura.

## Modelo administrativo recomendado

Si vas por la opción 3, el admin debería separarse en dos niveles:

| Nivel | Qué edita el usuario |
| --- | --- |
| Landing | Orden de secciones, visibilidad, asignación de variantes, reglas, integraciones, preview general |
| Sección | Contenido, estilo parametrizable, comportamiento, variante, prompt IA asociado, versión |

Flujo recomendado:

1. Crear landing.
2. Agregar secciones desde una biblioteca.
3. Elegir variante por sección.
4. Editar settings de cada sección dentro de la landing.
5. Si una sección no alcanza, pedir a la IA una propuesta de mutación o nueva variante.
6. Aprobar esa mutación como variante temporal o reusable.

## Decisión sugerida ajustada a tu necesidad

Si ya vamos por la opción 3, la mejor bajada práctica no es una sola, sino esta combinación:

1. Base del sistema: secciones ensamblables con schema versionado.
2. Para la mayoría de bloques: variantes registradas y editables.
3. Para bloques altamente variables como counters, hero especiales o comparadores: mutaciones controladas con IA.
4. Para resultados valiosos repetibles: fork versionado de la sección como nueva variante.

Ese enfoque conserva rendimiento y orden, pero te permite que una sección cambie radicalmente entre landings sin obligarte a crear un plugin nuevo ni caer en HTML libre descontrolado.

### Recomendación 3. Evitar como core

No usar HTML libre generado por IA como fuente directa del frontend final ni del admin definitivo, salvo como experimento interno.

Razón:

- Penaliza rendimiento.
- Aumenta superficie de ataque.
- Vuelve frágiles las migraciones.
- Complica mucho el versionado y la depuración.

## Arquitectura sugerida para escalar bien

| Capa | Recomendación |
| --- | --- |
| Definición de landing | JSON schema versionado |
| Edición | Admin por módulos y formularios controlados |
| IA | Asistente para brief, copys, estructura y preview |
| Preview | HTML estático o render aislado, no productivo |
| Runtime frontend | Render server-side desde módulos propios |
| Integraciones | Hooks por landing + capa de adaptadores |
| OpenAI | Servicio desacoplado, sin lógica mezclada con templates |
| Caché | Transients o page cache según landing y preview |

## Decisión sugerida

Si el objetivo es crecer con estabilidad, la mejor línea es esta:

1. Runtime real con secciones y schema controlado.
2. IA solo como asistente de generación y evolución de variantes, no como motor de render final.
3. Preview HTML desacoplado del frontend productivo.
4. Admin generado desde schema interno por landing y por sección, no desde HTML crudo.

Esa combinación da el mejor equilibrio entre velocidad editorial, seguridad, performance y capacidad de escalar el plugin a muchas campañas.