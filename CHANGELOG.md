# Changelog

## Unreleased

- Generación de propuestas de secciones conectada a OpenAI con fallback local seguro.
- Selector de modelo IA en ajustes con `gpt-5.4` como valor por defecto.
- Soporte de referencia visual por imagen en el contexto enviado al generador de propuestas.

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