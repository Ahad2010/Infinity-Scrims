#!/bin/bash
set -e

PORT="${PORT:-80}"

# Point Apache at whatever port Railway assigns this deploy
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

exec "$@"