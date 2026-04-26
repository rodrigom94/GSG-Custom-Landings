# GSG Custom Landings

Plugin de WordPress para crear landings y quizzes reutilizables sin tener que generar un plugin por cada campaña.

## Qué incluye

- CPT `GSG Landings` para configurar cada landing.
- Template dinámico con nombre `GSG Custom | {nombre_landing}` asignable desde páginas.
- Campos seguros en admin con `nonce`, sanitización y validaciones.
- Integración por hook configurable por landing.
- Ajustes globales para OpenAI con generación real de propuestas de secciones y fallback local.
- Gestión de leads dentro de cada landing, con tabla completa por campaña y exportación REST por landing.
- Primera landing responsive basada en la referencia visual de referidos.

## Flujo recomendado

1. Activa el plugin.
2. Revisa o edita la landing de ejemplo en `GSG Landings`.
3. Crea o publica una página y asígnale el template `GSG Custom | Invita a un amigo`.
4. Ajusta textos, imagen, hook, leads/API REST y configuración OpenAI según la campaña. El modelo por defecto es `gpt-5.4`.

## Leads y endpoint REST

Cada `gsg_landing` incluye una metabox `Leads y API REST` con dos tabs:

1. `Leads`: muestra la tabla completa de envíos de esa landing.
2. `Configuración REST`: permite habilitar la exportación, definir el password y ver instrucciones de consumo.

La ruta queda en el formato:

```text
/wp-json/gsgcl/v1/landings/{landing_id}/leads
```

Autenticación soportada:

- Header `X-GSGCL-Pass: <password>`.
- Header `Authorization: Bearer <password>`.
- Query string `?pass=<password>` como fallback.

La respuesta devuelve `landing`, `total_leads` y `leads`. Cada lead incluye `payload`, `request_context`, `meta`, `all_data` y `raw_meta`.

## Hook de integración

Cada landing puede disparar un hook específico al enviar el formulario:

```php
add_action('gsgcl_referral_submission', function ($payload, $landing_id, $submission_id) {
    // Integración con CRM, webhook, automatización, etc.
}, 10, 3);
```

Además, siempre se dispara el hook global `gsgcl_submission_received`.