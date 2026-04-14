# GSG Custom Landings

Plugin de WordPress para crear landings y quizzes reutilizables sin tener que generar un plugin por cada campaña.

## Qué incluye

- CPT `GSG Landings` para configurar cada landing.
- Template dinámico con nombre `GSG Custom | {nombre_landing}` asignable desde páginas.
- Campos seguros en admin con `nonce`, sanitización y validaciones.
- Integración por hook configurable por landing.
- Ajustes globales para OpenAI, listos para futura automatización.
- Captura de leads en CPT `Leads GSG`.
- Primera landing responsive basada en la referencia visual de referidos.

## Flujo recomendado

1. Activa el plugin.
2. Revisa o edita la landing de ejemplo en `GSG Landings`.
3. Crea o publica una página y asígnale el template `GSG Custom | Invita a un amigo`.
4. Ajusta textos, imagen, hook y configuración OpenAI según la campaña.

## Hook de integración

Cada landing puede disparar un hook específico al enviar el formulario:

```php
add_action('gsgcl_referral_submission', function ($payload, $landing_id, $submission_id) {
    // Integración con CRM, webhook, automatización, etc.
}, 10, 3);
```

Además, siempre se dispara el hook global `gsgcl_submission_received`.