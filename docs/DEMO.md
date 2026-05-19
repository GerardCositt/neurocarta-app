# Demo en cualquier sitio (cartas + panel)

Objetivo: poder **enseñar la carta pública y el panel** desde un portátil, una tablet o un móvil, sin depender del subdominio del cliente.

Esta guía está pensada para el entorno **demo/staging** donde se comparte un único dominio para varios restaurantes.

## 1. Preparar datos (una vez por entorno)

```bash
php artisan migrate
php artisan db:seed --class=RestaurantSeeder   # si aún no hay restaurantes
php artisan demo:prepare
```

- Carga el menú de ejemplo si el primer restaurante **aún no tiene categorías** (`DemoMenuSeeder`).
- Imprime la **URL pública** con `?restaurant=ID` (imprescindible al usar `127.0.0.1`, IP local o túnel).

### Fotos más realistas para la demo comercial

Las miniaturas por defecto viven en `public/demo/`. Para **ventas y demos en vivo**, coloca fotos propias con los nombres de la lista (`local/demo-product-images/README.md`) en **una** de estas rutas (orden de prioridad):

1. `local/demo-product-images/`
2. `img/demo-product-images/` (recomendado si ya tienes muchas cosas en `img/`)
3. `img/` (raíz del proyecto)

Los ficheros con nombre aleatorio tipo `0BbN8qMdsx….jpg` **no** se enlazan solos con cada plato: copia o renombra cada foto elegida al nombre del plato (`entrecot.jpg`, etc.).

### IA ilimitada en demo

En este proyecto, en **entorno demo** (`APP_ENV=demo`) las acciones de IA se consideran **ilimitadas** por defecto (no descuentan créditos de plataforma).

Si necesitas forzarlo manualmente en un entorno que no sea demo:

```bash
php artisan demo:prepare --unlimited-ai
```

En **production**, añade `--force` junto a `--unlimited-ai` si sabes lo que haces.

## 2. Arrancar la app el día de la reunión

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

- Misma red WiFi: abre en otro dispositivo `http://IP-DEL-PORTATIL:8000/?restaurant=ID` (el `demo:prepare` te da la URL completa).
- **Internet** (cliente remoto): usa un túnel, por ejemplo [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/) o [ngrok](https://ngrok.com/), apuntando al puerto 8000, y comparte la URL añadiendo `?restaurant=ID` si hace falta.

## 3. Qué enseñar (guion rápido)

1. **Carta pública**: idiomas, alérgenos, ofertas, aspecto visual.
2. **Panel** (`/login` → productos, categorías, importación CSV / IA, traducciones, apariencia).
3. **QR** (sidebar del admin): descarga y enlace a la misma carta del restaurante seleccionado.

Notas importantes:
- **Filtro por defecto**: al abrir la carta, la vista por defecto es **“Por categorías”**.
- **Carrusel de ofertas**: el carrusel de **Ofertas** es **siempre visible** (aunque el usuario cambie a Destacados/Recomendados o Lista).
- **Multi-restaurante en un solo dominio**: en demo/staging, para asegurar que se abre el restaurante correcto, usa siempre `?restaurant=ID`.

## 4. GitHub Codespaces

Si ya usas el devcontainer del repo: migraciones + seed + `php artisan demo:prepare`, luego **Port forwarding** del puerto de la app. La URL `*.app.github.dev` ya está contemplada en el middleware de restaurante; si hace falta, usa también `?restaurant=ID`.

## 5. Checklist previo (5 minutos)

- [ ] `php artisan demo:prepare` sin errores y URL copiada.
- [ ] Usuario admin puede entrar en `/product`.
- [ ] Probada la carta pública en el dispositivo que llevarás (móvil / tablet).
- [ ] Si usas túnel: comprobar que la URL externa abre la carta con `?restaurant=ID`.
- [ ] En el admin, el QR del sidebar abre la carta del restaurante seleccionado (ver botón “QRs”).
