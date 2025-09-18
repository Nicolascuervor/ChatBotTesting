# Paso 1: Usar una imagen oficial de PHP 8.2 con el servidor Apache ya configurado.
FROM php:8.2-apache

# Paso 2: Copiar todo el contenido de tu carpeta 'src'
# al directorio raíz del servidor web dentro del contenedor (/var/www/html).
COPY src/ .