#!/usr/bin/env bash
# MariaDB dentro del mismo contenedor (127.0.0.1). Evita Docker Compose en Codespaces.
set -euo pipefail

# El usuario «vscode» en Debian suele tener un PATH sin /usr/sbin; ahí están mariadbd y mysqld.
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:${PATH}"

# Algunas imágenes (o sesiones sin el Dockerfile del repo) no tienen el usuario unix "mysql";
# sin él, chown y mysqld_safe fallan. Usamos UID/GID numéricos para evitar rarezas de resolución.
ensure_mysql_unix_user() {
  if id mysql >/dev/null 2>&1; then
    return 0
  fi
  echo "Creando usuario/grupo de sistema mysql (faltaba en la imagen)…"
  sudo mkdir -p /var/lib/mysql
  if getent group mysql >/dev/null 2>&1; then
    :
  else
    sudo groupadd -r mysql
  fi
  # -M: no exige crear home; evita fallos si la ruta aún no es válida.
  if sudo useradd -r -g mysql -M -d /var/lib/mysql -s /usr/sbin/nologin mysql; then
    return 0
  fi
  echo "(primer useradd falló, probando home /nonexistent y shell /bin/false)"
  sudo useradd -r -g mysql -M -d /nonexistent -s /bin/false mysql
}

ensure_mysql_unix_user

if ! id mysql >/dev/null 2>&1; then
  echo "ERROR: No existe el usuario unix «mysql» y no se pudo crear."
  echo "Comprueba que estás en el devcontainer construido con .devcontainer/Dockerfile (apt: mariadb-server)."
  echo "Si el script es antiguo: git pull y Rebuild Container."
  exit 1
fi

MYSQL_UID="$(id -u mysql)"
MYSQL_GID="$(id -g mysql)"

sudo mkdir -p /run/mysqld
sudo chown "${MYSQL_UID}:${MYSQL_GID}" /run/mysqld

if [[ ! -d /var/lib/mysql/mysql ]]; then
  echo "Inicializando directorio de datos MariaDB (primera vez, puede tardar)…"
  sudo chown -R "${MYSQL_UID}:${MYSQL_GID}" /var/lib/mysql || true
  install_db_bin="$(command -v mariadb-install-db 2>/dev/null || true)"
  [[ -z "${install_db_bin}" && -x /usr/bin/mariadb-install-db ]] && install_db_bin=/usr/bin/mariadb-install-db
  [[ -z "${install_db_bin}" && -x /usr/sbin/mariadb-install-db ]] && install_db_bin=/usr/sbin/mariadb-install-db
  mariadbd_bin="$(command -v mariadbd 2>/dev/null || true)"
  [[ -z "${mariadbd_bin}" && -x /usr/sbin/mariadbd ]] && mariadbd_bin=/usr/sbin/mariadbd

  if [[ -n "${install_db_bin}" ]]; then
    if ! sudo "${install_db_bin}" --user=mysql --datadir=/var/lib/mysql --skip-test-db; then
      echo "Reintentando mariadb-install-db sin --skip-test-db (opción no soportada en esta versión)…"
      sudo "${install_db_bin}" --user=mysql --datadir=/var/lib/mysql
    fi
  elif [[ -n "${mariadbd_bin}" ]]; then
    sudo "${mariadbd_bin}" --no-defaults --initialize-insecure --user=mysql --datadir=/var/lib/mysql
  else
    echo "ERROR: no hay mariadb-install-db ni mariadbd en el contenedor."
    echo "  → Haz «Rebuild Container» para que se aplique .devcontainer/Dockerfile (apt: mariadb-server)."
    echo "  → Si abriste el repo sin devcontainer, no tendrás MariaDB en la imagen."
    exit 1
  fi
fi

if ! pgrep -x mysqld >/dev/null 2>&1 && ! pgrep mariadbd >/dev/null 2>&1; then
  echo "Arrancando servidor MariaDB…"
  sudo mysqld_safe --user=mysql --datadir=/var/lib/mysql &
  sleep 5
fi

for _ in $(seq 1 90); do
  if sudo mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

sudo mysql -u root <<-SQL
CREATE DATABASE IF NOT EXISTS carta_v2;
-- TCP usa host distinto a «localhost» (p. ej. 127.0.0.1); en Docker/Codespaces
-- el servidor puede ver el cliente como IP del puente (p. ej. 172.17.0.1). Sólo entorno dev.
CREATE USER IF NOT EXISTS 'carta'@'localhost' IDENTIFIED BY 'carta';
CREATE USER IF NOT EXISTS 'carta'@'127.0.0.1' IDENTIFIED BY 'carta';
CREATE USER IF NOT EXISTS 'carta'@'%' IDENTIFIED BY 'carta';
GRANT ALL PRIVILEGES ON carta_v2.* TO 'carta'@'localhost';
GRANT ALL PRIVILEGES ON carta_v2.* TO 'carta'@'127.0.0.1';
GRANT ALL PRIVILEGES ON carta_v2.* TO 'carta'@'%';
FLUSH PRIVILEGES;
SQL

echo "MariaDB listo en 127.0.0.1 (usuario carta / base carta_v2)."
