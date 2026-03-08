<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Política de lotes vencidos
    |--------------------------------------------------------------------------
    |
    | consumo (paciente) se bloquea siempre desde servicio por regla sanitaria.
    | Para distribución y ajuste negativo puedes activar/desactivar bloqueo aquí.
    |
    */

    'bloquear_vencidos_distribucion' => true,
    'bloquear_vencidos_ajuste_neg' => false,

    /*
    |--------------------------------------------------------------------------
    | Seguridad de roles
    |--------------------------------------------------------------------------
    |
    | Cantidad máxima de usuarios con rol administrador permitidos en el
    | sistema. Si se alcanza este valor, no se podrán crear/promover más admins.
    |
    */
    'max_admins' => 2,
];
