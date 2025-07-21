# api_Carrion

Repositorio backend oficial del sistema **Carrion-Web**. Este proyecto proporciona una robusta API desarrollada en **PHP**, pensada para gestionar de forma eficiente la información académica y administrativa del sistema Carrion, y sirve como núcleo de integración para el frontend [Carrion-Web](https://github.com/JFVela/Carrion-Web).

## Descripción profesional

api_Carrion implementa una arquitectura de servicios RESTful para servir datos y operaciones a clientes web y móviles. El backend está diseñado para ser escalable, modular y seguro, facilitando la integración con otras aplicaciones y permitiendo una experiencia fluida al usuario final.

### Características destacadas

- **Conexión eficiente a base de datos**: Implementación optimizada en PHP para acceso y manipulación de datos.
- **APIs documentadas**: Exposición de endpoints RESTful para funcionalidades académicas y administrativas.
- **Separación de roles**: Soporte explícito para estudiantes, profesores y administradores, alineado con las necesidades del frontend.
- **Notificaciones por correo**: Integración con [PHPMailer](https://github.com/PHPMailer/PHPMailer) para el envío de notificaciones y reportes automáticos.
- **Pruebas y despliegue profesional**: Compatible con entornos de desarrollo local y despliegue profesional en servidores productivos.
- **Gestión mediante Composer**: Administración de dependencias moderna y eficiente a través de [Composer](https://getcomposer.org/).

## Tecnologías y dependencias

- **PHP**: Lenguaje principal del backend.
- **Composer**: Gestor de dependencias PHP.
- **PHPMailer**: Para envío de correos electrónicos automáticos (`phpmailer/phpmailer`).
- **Servidores locales y soporte para Docker**: Facilita el desarrollo, pruebas y despliegue profesional.

## Instalación y ejecución local

1. Clona el repositorio:
   ```bash
   git clone https://github.com/JFVela/api_Carrion.git
   cd api_Carrion
   ```

2. Instala las dependencias con Composer:
   ```bash
   composer install
   ```

3. Configura el acceso a la base de datos en el archivo de configuración correspondiente (revisa la documentación interna o `.env` si existe).

4. Levanta el servidor local:
   ```bash
   php -S localhost:8000 -t public/
   ```
   O configura tu entorno local con Apache/Nginx apuntando al directorio público.

5. (Opcional) Para pruebas con Docker, crea tu propio archivo `docker-compose.yml` adaptado a tus necesidades.

## Uso de la API

- Los endpoints principales están orientados a la gestión de usuarios, notas, reportes y operaciones administrativas.
- El sistema está preparado para integrarse directamente con el frontend Carrion-Web y soportar autenticación y autorización por roles.
- Ejemplo de consumo de API:
   ```
   GET /api/estudiantes
   POST /api/docentes
   ```

## Pruebas y despliegue

- Se recomienda realizar pruebas locales en servidores Apache/Nginx o mediante el servidor embebido de PHP.
- Preparado para despliegue profesional en infraestructuras estándar de hosting PHP o contenedores Docker.

## Contribución

1. Haz un fork del repositorio.
2. Crea una rama para tu funcionalidad o corrección (`git checkout -b feature/nueva-funcionalidad`).
3. Sube tus cambios (`git commit -am 'Agrega nueva funcionalidad'`).
4. Haz push a tu rama (`git push origin feature/nueva-funcionalidad`).
5. Abre un Pull Request.

## Autor

- [JFVela](https://github.com/JFVela)

---

Repositorio oficial: [api_Carrion](https://github.com/JFVela/api_Carrion)
Frontend relacionado: [Carrion-Web](https://github.com/JFVela/Carrion-Web)
