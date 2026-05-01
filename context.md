# Laravel Project Context

## Architecture

-   Framework: Laravel
-   Pattern: MVC + Service Layer

## Folder Structure

-   Controllers: app/Http/Controllers/{module}
-   Models: app/Models/{module}
-   Services: app/Services
-   Views: resources/views/pages/{module}
-   Partials: resources/views/partials/{module}
-   layouts: resources/views/components/{module}

## Modules

-   Administrasi
-   Finance
-   Inventory
-   Notification
-   Report
-   Sdm

## Coding Rules

-   Gunakan Service untuk business logic
-   Controller hanya handle request/response
-   Gunakan Eloquent ORM
-   Gunakan blade template Laravel
-   Hindari duplicate code

## Naming Convention

-   Controller: PascalCase (UserController)
-   Model: Singular (User)
-   View: snake_case

## Notes

-   Semua fitur berbasis module
-   Gunakan struktur yang sudah ada, jangan buat baru
