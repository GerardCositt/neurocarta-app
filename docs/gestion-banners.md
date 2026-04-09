# Banners y avisos del panel de gestión

Colores de referencia para mensajes en el admin (información, éxito, advertencia, peligro). Las clases CSS vivas están en `resources/views/layouts/app.blade.php` (prefijo `admin-banner`, `admin-inset`, `admin-bulk-panel` para el bloque de selección masiva en productos).

## Resumen

| Situación | Icono | Uso típico |
|-----------|-------|------------|
| **Información** | 💡 | Ayudas, explicaciones, consejos sin error |
| **Éxito** | ✅ | Operación correcta, `session('message')` positivo |
| **Advertencia** | ⚠️ | Acciones que requieren atención (p. ej. selección masiva) |
| **Peligro / error** | ❌ | Errores de validación, fallos, eliminaciones |

## Colores (tema claro)

| Variante | Fondo | Texto | Borde |
|----------|--------|--------|--------|
| Información | `#e8f4fc` | `#0c4a6e` | `#7eb8d6` |
| Éxito | `#ecf8f0` | `#14532d` | `#7cc9a0` |
| Advertencia | `#fffbeb` | `#78350f` | `#e6c35c` |
| Peligro | `#fef2f2` | `#7f1d1d` | `#f0a8a8` |

Cabecera del bloque de **selección masiva** (advertencia, más énfasis): fondo `#fde68a`, borde inferior `#d97706`.

## Uso en Blade

**Componente (mensaje en una sola frase o párrafo):**

```blade
<x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
<x-admin.banner variant="info">Texto de ayuda.</x-admin.banner>
<x-admin.banner variant="warning">Revisa antes de continuar.</x-admin.banner>
<x-admin.banner variant="danger">{{ $msgError }}</x-admin.banner>
```

Sin icono: `<x-admin.banner variant="info" :show-icon="false">…</x-admin.banner>`.

**Paneles compactos dentro de formularios** (misma paleta, sin icono obligatorio):

- `admin-inset admin-inset--info`
- `admin-inset admin-inset--danger` (p. ej. bloque “Oferta” en el modal de producto)
- `admin-inset admin-inset--warning` (resaltado intermedio)

**Selección masiva (productos):** contenedor `admin-bulk-panel`; sin selección, ayuda en **tooltip** neutro (`admin-bulk-tooltip`); con selección, barra `admin-bulk-panel__active`. Ítem activo del menú lateral: clase `admin-nav-active` (gris pizarra, no ámbar).

**Filas de tabla / resaltes**: `admin-row-info` (contexto informativo), `admin-row-warning` (fila vinculada o aviso).

## Tema oscuro

En `html[data-effective-theme="dark"]` los mismos roles usan fondos semitransparentes y texto claro; ver reglas bajo `.admin-main` en el layout.
