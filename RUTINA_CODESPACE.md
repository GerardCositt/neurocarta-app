# Rutina Codespace (carta-barjaen-v2)

Guía rápida para trabajar siempre desde GitHub Codespaces (sin depender del ordenador local).

## 1) Arranque tras reinicio de Codespace

```bash
bash .devcontainer/start-db.sh
php artisan optimize:clear
php artisan serve --host=0.0.0.0 --port=8001
```

Luego abre el puerto `8001` en el navegador desde la pestaña **Ports**.

## 2) Primera vez (o entorno nuevo)

```bash
bash .devcontainer/first-run.sh
```

Esto prepara MariaDB, Composer, npm y `.env`.

## 3) Si faltan imágenes

Las imágenes deben existir en:

`storage/app/public/img`

Si tienes carpeta o zip con imágenes:

```bash
bash scripts/restore-media.sh <ruta-a-carpeta-o-zip>
```

Ejemplos:

```bash
bash scripts/restore-media.sh ~/Downloads/img
bash scripts/restore-media.sh ~/Downloads/img.zip
```

## 4) Importar base de datos (.sql)

Pon el `.sql` en `database/backups/` y ejecuta:

```bash
mysql -h 127.0.0.1 -u carta -pcarta carta_v2 < database/backups/archivo.sql
```

Verificar tablas:

```bash
mysql -h 127.0.0.1 -u carta -pcarta -e "USE carta_v2; SHOW TABLES;"
```

## 5) Comprobaciones rápidas

MariaDB:

```bash
mysqladmin -h 127.0.0.1 -u carta -pcarta ping
```

Storage link:

```bash
php artisan storage:link
ls -la public | grep storage
```

## 6) Problemas típicos

- `Address already in use` en puerto 8000: usa `--port=8001`.
- Xdebug warning (`Could not connect to debugging client`): no bloquea la app.
- Si el contenedor entra en recovery mode: `Rebuild Container`.

### Anti-bloqueos rápidos (cuando algo "se queda raro")

Si `git pull` falla por cambios locales:

```bash
git stash push -u -m "tmp-codespace"
git pull
```

Si un puerto está ocupado:

```bash
pkill -f "php artisan serve" || true
php artisan serve --host=0.0.0.0 --port=8002
```

Si el login redirige mal o no mantiene sesión:

```bash
php artisan optimize:clear
rm -f storage/framework/sessions/*
```

## 7) Flujo normal de trabajo

```bash
git pull
# trabajar...
git add .
git commit -m "mensaje"
git push
```
