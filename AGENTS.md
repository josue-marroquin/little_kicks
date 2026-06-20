# AGENTS.md — Kicks

## Propósito del proyecto

Kicks es una aplicación web gratuita para registrar y consultar movimientos o patadas durante el embarazo. Debe ser una alternativa simple a las aplicaciones con suscripciones, anuncios o cobros ocultos.

El producto debe mantenerse:

- Gratuito y sin funciones esenciales bloqueadas por pagos.
- Sin anuncios ni patrones de interfaz manipulativos.
- Simple, rápido y cómodo de usar con una sola mano.
- Respetuoso con la privacidad de la persona usuaria.
- Accesible desde teléfonos, tabletas y computadoras.

Kicks es una herramienta de registro personal. No debe presentarse como dispositivo médico, emitir diagnósticos ni sustituir la orientación de profesionales de salud. La interfaz debe mostrar esta limitación de forma clara y no alarmista.

## Ubicación y entorno

- Ruta prevista de instalación: `/opt/homebrew/var/www/Kicks`
- Tipo de aplicación: aplicación web SPA con backend PHP.
- Desarrollo principal: JavaScript, HTML, CSS y PHP.
- El proyecto debe poder ejecutarse en Apache con PHP sin depender de un proceso Node permanente en producción.
- Node.js puede utilizarse para instalar dependencias, ejecutar pruebas y generar recursos estáticos.

No se debe modificar la configuración global de Apache ni alterar otros proyectos bajo `/opt/homebrew/var/www` sin autorización explícita. Si se necesita un virtual host, puerto o regla de reescritura, debe aislarse para Kicks.

## Arquitectura inicial

Mantener la primera versión pequeña y fácil de desplegar:

- `public/`: punto de entrada público, recursos compilados y reglas del servidor.
- `src/`: código fuente de la SPA en JavaScript.
- `api/`: endpoints PHP de la aplicación.
- `app/`: lógica PHP reutilizable, validación y acceso a datos.
- `storage/`: datos locales, registros y archivos generados que no sean públicos.
- `tests/`: pruebas del frontend y backend.

La SPA consume una API JSON bajo `/api`. El backend debe devolver respuestas consistentes, usar códigos HTTP correctos y validar todos los datos; nunca debe confiar únicamente en validaciones del navegador.

Evitar frameworks o dependencias grandes hasta que una necesidad comprobada los justifique. Favorecer módulos ES, componentes pequeños y CSS propio. Si el proyecto crece, documentar la razón antes de introducir un framework.

## Alcance funcional inicial

La primera versión debe permitir:

1. Iniciar una sesión de conteo.
2. Registrar una patada con un toque.
3. Mostrar el número de patadas y el tiempo transcurrido.
4. Deshacer el último registro accidental.
5. Finalizar y guardar una sesión.
6. Consultar el historial por fecha.
7. Usar la aplicación sin crear una cuenta.

El flujo principal debe estar disponible inmediatamente al abrir la aplicación. No introducir onboarding obligatorio, formularios extensos ni pasos que retrasen el inicio del conteo.

## Modelo de datos mínimo

Una sesión de conteo debe contemplar, como mínimo:

- Identificador único.
- Fecha y hora de inicio.
- Fecha y hora de finalización.
- Lista o cantidad de movimientos registrados.
- Marca de tiempo de cada movimiento cuando corresponda.
- Notas opcionales escritas por la persona usuaria.

Las fechas deben almacenarse en un formato no ambiguo y convertirse a la zona horaria local para mostrarlas. No almacenar datos personales que no sean necesarios para la función principal.

## Experiencia de usuario

- Diseñar primero para pantallas móviles.
- El botón para registrar una patada debe ser grande, visible y fácil de alcanzar.
- Mostrar confirmación visual inmediata después de cada toque.
- Evitar depender únicamente del color para comunicar estado.
- Usar lenguaje claro, cálido y neutral; evitar mensajes que puedan generar culpa o ansiedad.
- Mantener contraste suficiente, foco visible y navegación completa por teclado.
- Respetar `prefers-reduced-motion`.
- Los objetivos táctiles deben medir al menos 44 × 44 px.
- La interfaz y el contenido inicial deben estar en español, con textos preparados para futura internacionalización.

## Privacidad y seguridad

- Priorizar almacenamiento local para la versión sin cuenta.
- No integrar analítica, publicidad, píxeles de seguimiento ni servicios de terceros sin autorización explícita.
- No enviar datos de salud o actividad a terceros.
- Aplicar validación, normalización y escape de datos en el servidor.
- Usar consultas preparadas para cualquier acceso a base de datos.
- Proteger operaciones que cambien estado contra CSRF cuando se usen sesiones o cookies.
- No guardar secretos, credenciales ni archivos `.env` reales en Git.
- Los errores enviados al navegador no deben exponer rutas, consultas, credenciales ni trazas internas.

## Convenciones de implementación

### JavaScript

- Usar JavaScript moderno con módulos ES.
- Mantener separadas la interfaz, el estado y el acceso a la API.
- Evitar estado global mutable.
- Nombrar funciones y variables según su intención, no según detalles visuales.
- Manejar explícitamente estados de carga, vacío, éxito y error.

### PHP

- Usar `declare(strict_types=1);` en archivos PHP nuevos cuando sea compatible.
- Seguir PSR-12.
- Mantener los controladores pequeños; mover reglas de negocio a servicios o clases específicas.
- Tipar parámetros, propiedades y valores de retorno.
- Centralizar las respuestas JSON y el manejo de errores.

### CSS

- Usar propiedades personalizadas para colores, espaciado, tipografía y radios.
- Diseñar con enfoque mobile-first.
- Evitar estilos en línea y especificidad innecesaria.
- Mantener una jerarquía visual sobria y consistente.

## Calidad y verificación

Antes de entregar cambios:

- Ejecutar las pruebas relacionadas con el cambio.
- Comprobar el flujo principal en un viewport móvil.
- Verificar que registrar rápidamente varios movimientos no pierda ni duplique eventos.
- Comprobar recarga, navegación hacia atrás y recuperación tras errores de red.
- Revisar errores de JavaScript y respuestas fallidas en la consola del navegador.
- Ejecutar el linter y el formateador configurados por el proyecto.
- Probar sintaxis PHP con `php -l` en los archivos modificados.

Cada corrección de un defecto debe incluir una prueba de regresión cuando sea razonable. No considerar completo un cambio que solo funciona en el caso ideal.

## Criterios para cambios futuros

- Mantener el registro de patadas como función central y siempre gratuita.
- No añadir autenticación hasta que exista una función que realmente la necesite.
- La sincronización entre dispositivos debe ser opcional y explicar claramente qué datos salen del dispositivo.
- Cualquier recordatorio debe ser opcional, configurable y no alarmista.
- Las recomendaciones relacionadas con salud deben proceder de fuentes confiables, estar claramente atribuidas y revisarse antes de publicarse.
- Favorecer decisiones que reduzcan mantenimiento, superficie de ataque y dependencia de servicios externos.

## Forma de trabajo para agentes

1. Leer este archivo y revisar el estado actual del repositorio antes de modificarlo.
2. Preservar cambios existentes que no formen parte de la tarea.
3. Implementar el cambio más pequeño que resuelva completamente el objetivo.
4. No cambiar infraestructura compartida ni instalar servicios globales sin permiso.
5. Documentar decisiones no obvias en el código o en el README, según corresponda.
6. Verificar el resultado con comandos y pruebas reales antes de reportarlo como terminado.
7. Indicar con precisión qué se cambió, qué se comprobó y cualquier limitación pendiente.

