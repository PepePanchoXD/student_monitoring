# Student Monitoring (local_student_monitoring)

Author: Jose Salazar

Descripción

Este plugin registra sesiones de uso de actividades por parte de estudiantes dentro de un curso. Guarda información como userid, courseid, cmid, modname, timestart, timeend y duración. También almacena capturas opcionales del nombre del estudiante (`studentname`) y del nombre de la actividad (`activityname`) para reportes históricos.

Instalación

1. Coloca la carpeta `local/student_monitoring` dentro del directorio `local/` de tu instalación de Moodle.
2. Entra a Administración del sitio > Notificaciones o ejecuta desde CLI:

```bash
sudo -u www-data php /var/www/html/moodle/admin/cli/upgrade.php
```

3. Purga cachés si es necesario:

```bash
sudo -u www-data php /var/www/html/moodle/admin/cli/purge_caches.php
```

Uso

- El plugin añade un informe accesible a usuarios con la capacidad `local/student_monitoring:viewreport` en cada curso en `local/student_monitoring/index.php?id=COURSEID`.
- Las sesiones se crean automáticamente desde los observadores de eventos (por ejemplo al ver un módulo) y se cierran al salir o al cerrar sesión.

Notas importantes

- `studentname` y `activityname` son capturas en el momento de la sesión. Si un usuario cambia su nombre o el título de una actividad más tarde, los registros históricos no se actualizarán automáticamente.
- La tabla de la base de datos es `local_studentmonitoring` (prefijo de tabla de Moodle puede variar, por ejemplo `mdl_local_studentmonitoring`).

Soporte

Para problemas con el plugin, revisa los logs de Moodle y los mensajes de error en `admin/cli/upgrade.php` y `debug` en la interfaz. Si necesitas que adapte el plugin (por ejemplo, sin almacenar nombres, o añadiendo más informes), contacta con el autor.

Licencia

Este plugin respeta la licencia de Moodle (GNU GPL v3 o superior). Siéntete libre de modificarlo acorde a la licencia.
