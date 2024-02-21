<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Proyecto de Auto gestión para apostadores

Este es un proyecto de auto gestión para apostadores, donde pueden gestionar su autoexclusión. Está desarrollado en Laravel 10 y está configurado para utilizar una base de datos PostgreSQL. La estructura de la base de datos está definida tanto en los migraciones (migrates) como en archivos SQL que se encuentran en la carpeta DB.

## Comandos típicos de Laravel

### Lanzar el servicio

Para lanzar el servicio, asegúrate de tener PHP y Composer instalados y luego ejecuta los siguientes comandos:

```bash
composer install
php artisan serve
```

Esto iniciará el servidor de desarrollo de Laravel.

### Migrar la base de datos

Para migrar la base de datos, utiliza el siguiente comando:

```bash
php artisan migrate
```

Este comando ejecutará todas las migraciones pendientes.

### Uso de archivos .sql para migrar datos

Si deseas utilizar los archivos .sql para migrar los datos, puedes usar el siguiente comando para importarlos en tu base de datos PostgreSQL:

```bash
psql -U username -d database_name -f path_to_sql_file.sql
```

Reemplaza "username" con tu nombre de usuario de PostgreSQL, "database_name" con el nombre de tu base de datos, y "path_to_sql_file.sql" con la ruta al archivo .sql que deseas importar.

## Contribuciones

-
