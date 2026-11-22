<?php
// Esta pasta guarda apenas as fotos dos registros.
// O acesso é feito por index.php?page=foto&id=… (com verificação de dono/admin).
http_response_code(403);
exit;