## Carta online · Bar Jaén

Aplicación Laravel 8 (Jetstream/Livewire) para la carta pública y el panel de administración.

### Desarrollo local o Codespace

- PHP 8.x, Composer, Node (ver `composer.json` / `package.json`).
- `cp .env.example .env`, `php artisan key:generate`, base de datos y `php artisan migrate`.
- Front: `npm run dev` o `npm run watch`; assets de producción: `npm run production`.
- Arranque: `php artisan serve` (en Codespaces usar `--host=0.0.0.0`).

Tras `migrate` + `db:seed`, la **carta pública** puede salir vacía: no hay platos hasta que los cargues en el **panel** (`/register` → categorías/productos) o importes CSV en ajustes. Para **datos de prueba** rápidos: `php artisan db:seed --class=DemoMenuSeeder` (solo si aún no hay categorías para el primer restaurante).

Para **presentar la app en cualquier sitio** (URLs listas, túnel, guion): `php artisan demo:prepare` y la guía en **`docs/DEMO.md`**.

Para **publicar en hosting** (Docker + compose o servidor clásico): **`docs/DEPLOY.md`** y `docker-compose.prod.yml` en la raíz.

### Demo/staging (multi-restaurante en un solo dominio)

- **URL pública por restaurante**: usa `/?restaurant=ID` (en demo/staging el dominio puede ser compartido).
- **Carta pública**:
  - vista por defecto: **Por categorías**
  - carrusel de **Ofertas**: **siempre visible**
- **Admin**:
  - botón **“Ver carta”** y panel **“QRs”** apuntan al restaurante seleccionado (incluyendo `?restaurant=ID` cuando aplica).

Hay configuración en **`.devcontainer/`** para **GitHub Codespaces**: **un solo contenedor** con PHP 8.3 (**GD + zip + pdo_mysql**) y **MariaDB** escuchando en **`127.0.0.1:3306`** (usuario `carta`, contraseña `carta`, base `carta_v2`). Los datos de MariaDB van a un **volumen** (`carta-barjaen-mysql-data`) para que no se pierdan al apagar el Codespace.

**Comprobar que estás en el Dev Container:** debe existir `mysqladmin` y `docker-php-ext-install`:
```bash
command -v mysqladmin && command -v docker-php-ext-install && echo OK
```
Si falta alguno, **no** has hecho Rebuild con `.devcontainer/`: `git pull` → **Rebuild Container** o crea un **Codespace nuevo**.

**Importar un volcado `.sql` desde el Codespace (sin instalar MySQL en el Mac):**

1. Sube el archivo al repo o arrástralo a `database/backups/` (por ejemplo `carta_v2_staging_….sql`).
2. Asegura MariaDB: `bash .devcontainer/start-db.sh` (si hace falta).
3. En la terminal:
   ```bash
   mysql -h 127.0.0.1 -u carta -pcarta carta_v2 < database/backups/carta_v2_staging_2026-04-03_14-29-14.sql
   ```
   Si el dump exige **root**: `sudo mysql -u root carta_v2 < …` (root suele ser por socket en esta imagen).
3. Si el volcado es **solo datos** y la base está vacía, antes: `php artisan migrate`. Si el `.sql` ya incluye tablas completas, **no** ejecutes `migrate` después.

### Codespace: PHP en `/usr/local/php/…` y error `ext-gd`

Eso es el Codespace **por defecto** (sin Dev Container): ese PHP **no trae GD** y `sudo apt install php-gd` no lo enlaza bien. **Solución:** en VS Code / web, paleta de comandos (`Ctrl+Shift+P` / `F1`) → **`Codespaces: Rebuild Container`** o **`Dev Containers: Rebuild Container`**, para que use `.devcontainer/Dockerfile`. Tras el rebuild, `php -m | grep gd` debe mostrar `gd` y `composer install` funcionará.

**Si ahora mismo no puedes hacer Rebuild** (solo para desbloquear `vendor/`; **los QR pueden fallar** hasta que uses el contenedor):

```bash
git fetch origin && git checkout origin/main -- composer.json composer.lock
chmod +x scripts/codespace-install-vendor.sh
./scripts/codespace-install-vendor.sh --no-scripts
php artisan key:generate --force
./scripts/codespace-install-vendor.sh
```

O a mano: `composer install --ignore-platform-req=ext-gd` (mismo efecto).

### Codespace: aviso «lock file is not up to date with composer.json»

Tu `composer.json` local no coincide con el lock del repo. Alinea con GitHub:

```bash
git fetch origin
git checkout origin/main -- composer.json composer.lock
```

### Codespace: `git pull` bloqueado por `package-lock.json`

Has modificado el lock local (p. ej. con `npm install`). Para alinear con GitHub **sin perder el remoto**:

```bash
git fetch origin
git checkout origin/main -- package-lock.json
git pull
```

(Si quieres descartar cualquier cambio local en ese archivo: `git checkout -- package-lock.json && git pull`.)

### Codespace: error `vendor/autoload.php` no existe

Significa que **Composer no ha podido instalar `vendor/`** (o no se ha ejecutado). Orden recomendado:

1. **Rebuild Container** con `.devcontainer/` (ver sección `ext-gd` arriba) para tener **GD** de verdad.
2. **`composer.json` / `composer.lock` alineados** con `origin/main` (ver aviso «lock file is not up to date» arriba si aplica).

Secuencia típica tras un `git pull` limpio:

```bash
export XDEBUG_MODE=off
[ ! -f .env ] && cp .env.example .env
mkdir -p database && touch database/database.sqlite
composer install --no-interaction --no-scripts
php artisan key:generate --force
composer install --no-interaction
npm install
```

Si `composer` sigue quejándose solo de **ext-gd** y no puedes instalarla en esa imagen, **solo como último recurso** (los QR pueden fallar):

`composer install --no-interaction --ignore-platform-req=ext-gd`

Para silenciar Xdebug: `export XDEBUG_MODE=off` (opcional en `~/.bashrc`).

**Recomendado:** crear el Codespace con el **Dev Container** del repo (`.devcontainer/`) y ejecutar `chmod +x .devcontainer/setup.sh && .devcontainer/setup.sh`.

### Avisos `npm audit` (webpack, tmp, yaml, etc.)

Vienen sobre todo de **Laravel Mix 6 / webpack**. Para desarrollo no bloquean `php artisan serve`. Ver mensajes anteriores: `npm audit fix` sin `--force` puede ayudar; **`npm audit fix --force`** puede romper el build.
