# Changelog

## v1.1.17 - 2026-04-25

- Los leads se gestionan desde cada `gsg_landing` en una metabox con tabs para tabla completa y configuración REST.
- Nueva ruta `gsgcl/v1/landings/{landing_id}/leads` protegida por password configurable por landing.
- Cada lead guarda y exporta además del payload el contexto técnico del envío para consumo externo.

## v1.1.16 - 2026-04-22

- Reemplazo de la opción `Exam Prep` por `Curso de preparación` en el select de interés del formulario.

## v1.1.15 - 2026-04-21

- Actualización del asset mobile del hero principal al banner `Banner-800x1000px-02-1.jpg`.
- Ajuste de opacidad a `0.8` para el aro decorativo mobile de la sección de beneficio.

## v1.1.14 - 2026-04-21

- El mensaje de éxito del formulario se normaliza a `Gracias. Tu solicitud ha sido registrada con éxito.` incluso en landings con el texto anterior guardado.

## v1.1.13 - 2026-04-21

- El campo `Hook de envío` en Integraciones ahora acepta URLs webhook HTTPS además de hooks internos de WordPress.
- Los envíos pueden despacharse por `POST` JSON a servicios externos como Make.

## v1.1.12 - 2026-04-21

- Ajuste visual del prefijo no editable del campo de WhatsApp para alinear el texto a la izquierda y compactar su padding.

## v1.1.11 - 2026-04-21

- Envío del formulario sin recarga con mensajes de éxito y error en el mismo bloque visual.
- Logging de envíos y errores del formulario en `uploads/gsgcl-logs/gsgcl-submissions.log`.
- Ajuste del campo de WhatsApp con prefijo no editable dependiente del país y corrección del layout compacto.

## v1.1.10 - 2026-04-21

- Ajuste del hero demo en mobile para que la imagen real ocupe el 100% del ancho visible y se desplace `-30px` hacia arriba.

## v1.1.9 - 2026-04-21

- Ajuste del aro decorativo de la sección de beneficio a `right: -14%`.
- El banner demo del hero ahora usa una imagen real en mobile en lugar de `background-image`.

## v1.1.8 - 2026-04-21

- Generación de propuestas de secciones conectada a OpenAI con fallback local seguro.
- Selector de modelo IA en ajustes con `gpt-5.4` como valor por defecto.
- Soporte de referencia visual por imagen en el contexto enviado al generador de propuestas.

## v1.1.7 - 2026-04-18

- Eliminación de la sombra en la imagen lateral de la sección `¿Cómo funciona?`.

## v1.1.6 - 2026-04-18

- Ajuste de color para mantener en blanco la descripción del hero, el panel de razones y el bloque de ayuda en la landing de referidos.

## v1.1.5 - 2026-04-14

- Ajuste del hero principal para usar caja útil de 1200px, sin aro decorativo y con padding horizontal responsivo en el bloque de contenido.

## v1.1.4 - 2026-04-14

- Corrección del desbordamiento horizontal en producción para landings renderizadas dentro del theme.

## v1.1.3 - 2026-04-14

- Corrección de layout para que las landings con header/footer del theme puedan renderizarse full width fuera del contenedor del tema.
- Reversión del cambio de fallback que forzaba ocultar header/footer por defecto.

## v1.1.2 - 2026-04-14

- Ajuste de proporción en el hero principal para usar `grid-template-columns: 0.7fr 1fr`.
- Refinamiento visual del fondo de la sección de razones y sus elementos decorativos.

## v1.1.1 - 2026-04-14

- Corrección del desbordamiento horizontal en frontend manteniendo visible el aro decorativo entre secciones.
- Ajuste del panel principal hero al color sólido `#006bcb`.
- Mejora del formulario de referidos con select de países ampliado y prefijo automático para WhatsApp.

## v1.1.0 - 2026-04-14

- Release inicial del plugin GSG Custom Landings.
- Registro de landings dinámicas con template `GSG Custom | {nombre}`.
- Primera landing responsive de referidos lista para demo.
- Integraciones por hook y ajustes globales OpenAI.
- Biblioteca de secciones versionadas con preview HTML editable.
- Generación de 3 propuestas por sección.
- Revisiones, rollback, fork y versionado de secciones.
- Seed automático de landing demo, página publicada y secciones base.
- Inicio del refactor para renderizar el frontend desde la biblioteca de secciones.