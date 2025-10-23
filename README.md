Notification Hub - challenge Backend Sirius

Se presenta mediante este proyecto la entrega de las funcionalidades solicitas para el Challenge de Backend.
Los archivos de entorno con sus credenciales serán enviados a través de otro medio.

Para generar las tablas y los datos iniciales de la base de datos se deberán de correr las migraciones y los seeders respectivamente.

Para ejecutar las pruebas unitarias se presenta el siguiente comando:
docker compose exec -u 82:82 -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=":memory:" -e LOG_CHANNEL=stderr -e CACHE_DRIVER=array -e SESSION_DRIVER=array   app php ./vendor/bin/phpunit --do-not-cache-result

ENDPOINT DISPONIBLES:

POST /api/register
    Descripción: Registro de un nuevo usuario.
    Parámetros:
        username: required, string;
        password: required, string;
        role: nullable (user como default);

POST /api/login
    Descripción: Login del usuario.
    Parámetros:
        username: required, string;
        password: required, string;

POST /api/send_message
    Descripción: Enviar un mensaje a diferentes servicios destino mediante APIs.
    Parámetros:
        destinations: ["discord","telegram","slack"];
        content: required, string;

GET /api/messages
    Descripción: Lista los mensajes enviados por el usuario. El usuario admin puede ver los mensajes de todos los usuarios.
    Parámetros:
        status: nullable, ["pending","success","failed"];
        services: nullable, ["discord","telegram","slack"];
        from: nullable, date;
        to: nullable, date;
        per_page: nullable, int;

GET /api/admin/metrics/message
    Descripción: Presenta unicamente al usuario administrador métricas de envío de mensajes totales y restantes por cada usaurio.
    Parámetros: -


DESCRIPCIÓN DE TESTS:

AuthTest: funcionamiento del endpoint /api/register y /api/login para un usuario.

SendMessageTest: funcionamiento del endpoint /api/send_message donde se comprueba el envío de mensajes por parte del usuario

DailyLimitMiddelwareTest: funcionamiento del límite diario de mensajes comprobando que no se puede superar el límite establecido según el rol de usuario.

MessagesIndexFiltersTest: funcionamiento del endpoint /api/messages donde se prueban los filtros básico establecidos para listar los mensajes del usuario.

AdminMetricsTest: funcionamiento del endpoint /api/admin/metrics/message donde se deben visualizar las métricas para el usuario admin