# Script de QA manual — NeuroCarta (pre-lanzamiento)

> **Cómo usarlo**: Ejecuta cada bloque en orden. Marca cada paso con ✅ cuando pase o ❌ + nota cuando falle.  
> Entorno objetivo: producción (`https://app.neurocarta.ai`) o staging con datos limpios.  
> Usuario de prueba base: crea una cuenta nueva en cada bloque para que el estado sea predecible.

**Antes de empezar (automático):**

```bash
# Opción A — solo tests críticos (13 tests, salida clara):
./scripts/launch-test.sh

# Opción B — tests + scheduler + avisos .env + demo:
php artisan launch:check

php artisan demo:prepare --subdomain=demo   # carta de ventas (subdominio demo)
```

**En producción (Jotelulu):**

```bash
docker exec neurocarta-app-1 bash /opt/neurocarta/scripts/launch-test.sh
docker exec neurocarta-app-1 php artisan launch:check
```

---

## Bloque 1 — Registro y trial

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 1.1 | Ir a la landing → pulsar CTA "Crear cuenta" | Redirige al selector de plan |
| 1.2 | Seleccionar plan **Trial** | Se muestra el formulario de registro |
| 1.3 | Rellenar email, nombre de restaurante y teléfono → enviar | Redirige a la pantalla "Revisa tu correo" |
| 1.4 | Abrir el email de bienvenida y pulsar el enlace de activación | Pantalla para crear contraseña |
| 1.5 | Crear contraseña y confirmarla | Login automático → accede al dashboard |
| 1.6 | Verificar que el trial caduca en 7 días | En el panel aparece aviso del trial con fecha correcta |
| 1.7 | Verificar que el restaurante se ha creado automáticamente | En el panel hay un restaurante con el nombre introducido |

---

## Bloque 2 — Gestión de carta (CRUD)

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 2.1 | Ir a **Categorías** → crear 3 categorías (ej. Entrantes, Platos, Postres) | Aparecen en la lista, orden correcto |
| 2.2 | Arrastrar para reordenar las categorías | El nuevo orden se guarda y persiste al recargar |
| 2.3 | Editar el nombre de una categoría | El nombre se actualiza sin perder el orden |
| 2.4 | Ir a **Productos** → crear un producto con nombre, precio, categoría y foto | Aparece en la lista |
| 2.5 | Subir foto en formato **JPG** y otra en **PNG** | Se aceptan y se muestran en miniatura |
| 2.6 | Intentar subir foto en formato **SVG** | Se rechaza con mensaje de error |
| 2.7 | Intentar subir foto en formato **GIF** | Se rechaza con mensaje de error |
| 2.8 | Crear producto **sin foto** | Aparece el placeholder `noimg.png` en la lista y en la carta pública |
| 2.9 | Marcar un producto como **oculto** | Desaparece de la carta pública pero sigue en el panel |
| 2.10 | Marcar producto como **destacado** | Aparece primero en la sección correspondiente de la carta |
| 2.11 | Asignar alérgenos a un producto | Los iconos de alérgenos aparecen en la carta pública |
| 2.12 | Arrastrar para reordenar productos dentro de una categoría | El orden se guarda y persiste |

---

## Bloque 3 — Carta pública

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 3.1 | Ir a la URL pública del restaurante (subdominio o preview) | La carta carga con las categorías y productos creados |
| 3.2 | Navegar entre categorías | El scroll y el ancla de categorías funcionan |
| 3.3 | Revisar la carta en **móvil** (320 px y 390 px) | Legible, sin overflow horizontal, botones pulsables |
| 3.4 | Revisar la carta con **100 productos** | Carga en < 3 s, sin lag al hacer scroll |
| 3.5 | Cambiar el idioma con el selector de idioma | Los textos del producto cambian si hay traducciones; si no, se mantiene el original |
| 3.6 | El producto sin foto muestra el placeholder correctamente | Sin imagen rota (404) |

---

## Bloque 4 — Importación CSV (plan Pro/Premium)

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 4.1 | Ir a **Ajustes → Importar productos CSV** (con plan Pro) | Se carga la página sin redirección |
| 4.2 | Descargar la plantilla | Se descarga `plantilla-productos.csv` con cabeceras en castellano |
| 4.3 | Subir el CSV de ejemplo con la plantilla | Se muestra la previsualización con las filas detectadas |
| 4.4 | Pulsar "Importar" | Los productos aparecen en la lista del panel |
| 4.5 | Subir un CSV con columna obligatoria ausente (ej. sin `nombre`) | Se muestra error de validación, no se importa nada |
| 4.6 | Con plan **Básico**, navegar a `/settings/import-products` | Redirige al dashboard con mensaje "plan no incluye esta función" |

---

## Bloque 5 — Importación por IA (plan Pro/Premium)

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 5.1 | Ir a **Ajustes → Importar por IA** (con plan Pro) | Se carga la página |
| 5.2 | Con plan **Básico**, navegar a `/settings/import-ai` | Redirige al dashboard con mensaje de plan |
| 5.3 | Con plan **Básico**, navegar a `/settings/ai-billing` | Redirige al dashboard con mensaje de plan |

---

## Bloque 6 — Traducciones (plan Pro/Premium)

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 6.1 | Ir a **Traducciones** (con plan Pro) | Se carga la página con el gestor de idiomas |
| 6.2 | Con plan **Básico**, navegar a `/translations` | Redirige al dashboard con mensaje de plan |

---

## Bloque 7 — Expiración del trial y bloqueo

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 7.1 | Forzar la caducidad del trial: `php artisan trial:expire email@ejemplo.com` (en prod: añadir `--force`) | El panel muestra la pantalla de trial expirado al recargar |
| 7.2 | Intentar acceder a `/dashboard` con el trial caducado | Redirige a `/subscription/expired` |
| 7.3 | Intentar acceder a la carta pública del restaurante con trial caducado | La URL pública muestra la pantalla de bloqueo (no la carta) |
| 7.4 | El QR del restaurante redirige a la pantalla de bloqueo (no a la carta) | Sin carta accesible mientras no hay suscripción activa |
| 7.5 | La pantalla de trial expirado tiene CTA claro para contratar | El botón de "Elegir plan" lleva al flujo de pago |

---

## Bloque 8 — Plan Básico (límites de cuota)

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 8.1 | Con plan Básico, intentar crear el producto número **101** | Se muestra mensaje de límite alcanzado (100 productos) |
| 8.2 | Con plan Básico, intentar crear la categoría número **21** | Se muestra mensaje de límite (20 categorías) |
| 8.3 | Las rutas de IA, CSV y traducciones redirigen al dashboard | Ver bloques 4.6, 5.2, 5.3, 6.2 |

---

## Bloque 9 — Aislamiento multi-tenant

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 9.1 | Crear dos cuentas distintas (usuario A y usuario B) con restaurantes diferentes | Cada cuenta ve solo su restaurante en el panel |
| 9.2 | Usuario A crea productos; iniciar sesión como usuario B | Los productos de A no aparecen en el panel de B |
| 9.3 | La carta pública de restaurante A no es accesible desde el subdominio de restaurante B | Cada URL/subdominio sirve solo su carta |
| 9.4 | Usuario B no puede acceder a `/admin` de Filament si no es el admin | Redirige a login o devuelve 403 |

---

## Bloque 10 — Correos transaccionales

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 10.1 | Registro → email de bienvenida con enlace de activación | Recibido en < 2 min, enlace funcional |
| 10.2 | Día 5 del trial → email de aviso | Recibido (verificar con scheduler activo o lanzando manualmente `php artisan schedule:run`) |
| 10.3 | Día 7 del trial → email de aviso final | Recibido |
| 10.4 | (Cuando Stripe esté activo) Pago fallido → email de aviso | Recibido |

---

## Bloque 11 — Responsividad y navegador

| # | Acción | Resultado esperado |
|---|--------|--------------------|
| 11.1 | Panel completo en Chrome desktop | Sin errores JS en consola, sin layout roto |
| 11.2 | Panel completo en Safari desktop | Igual que Chrome |
| 11.3 | Panel completo en Chrome móvil (375 px) | Tabla de productos usable, sidebar colapsado |
| 11.4 | Carta pública en Chrome móvil | Misma prueba del bloque 3 |

---

## Checklist final antes de cobrar

- [ ] Stripe en modo **live** probado con pago real (importe mínimo)
- [ ] Webhook de Stripe configurado y verificado en producción
- [ ] Emails reales llegan desde `noreply@neurocarta.ai`
- [ ] Backups automáticos activos y restauración probada
- [ ] `APP_DEBUG=false` en producción
- [ ] Sin datos demo mezclados con datos reales
- [ ] Política legal publicada (términos, privacidad, cookies)
- [ ] Al menos 2-3 restaurantes piloto probados de principio a fin
