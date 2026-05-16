# Design system — Carta Bar Jaén

Fuente de verdad visual: **carta pública** (`resources/views/menu.blade.php`). Los tokens CSS viven en **`resources/css/carta-design-tokens.css`** (compilado también a `public/css/carta-design-tokens.css` para la carta sin cargar todo Tailwind).

El **panel admin** importa los mismos tokens vía `resources/css/app.css`, luego Tailwind, después **`admin-shell.css`** y al final **`admin-overrides.css`** (obligatorio: si no, las utilidades `.bg-white` / `.text-gray-*` pisan los estilos del shell).

## Tipografía

| Uso | Familia | Variable CSS |
|-----|---------|----------------|
| Títulos / marca | Playfair Display | `var(--font-title)` |
| Cuerpo UI | Montserrat | `var(--font-body)` |

Google Fonts: misma URL en `menu.blade.php` y `layouts/app.blade.php`.

Tailwind (`tailwind.config.js`): `font-sans` → Montserrat; `font-display` → Playfair Display.

## Colores (tokens)

Definidos en `:root` (tema oscuro por defecto) y en `html[data-theme="light"]` / `prefers-color-scheme` para `data-theme="system"`. El admin usa además `html[data-effective-theme="light|dark"]` con los mismos valores.

| Token | Rol |
|-------|-----|
| `--bg` | Fondo página |
| `--surface` | Tarjetas, sidebar, pie |
| `--surface-el` | Superficies elevadas / inputs oscuros |
| `--gold`, `--gold-light`, `--gold-dim` | Acento marca (dorado) |
| `--text`, `--text-muted` | Texto principal y secundario |
| `--divider`, `--prod-border` | Bordes |
| `--chip-bg` | Fondos de hover suaves |
| `--radius` | Radio base (12px) |
| `--modal-shadow`, `--nav-border`, … | Resto en fichero de tokens |

## Componentes admin

- **Shell**: clases `admin-shell`, `admin-sidebar`, `admin-main`, `admin-nav-active`, `admin-banner--*`, etc., definidas en `admin-shell.css`.
- **Calendario (Flatpickr)**: estilos en `app.css` bajo `.admin-main .flatpickr-*` usando `var(--gold)`.

## Mantenimiento

1. Cambiar un color de marca: editar **`carta-design-tokens.css`** y comprobar `menu.blade.php` (ya enlaza el CSS compilado).
2. Tras cambiar tokens o `admin-shell.css`: `npm run dev` o `npm run production`.
