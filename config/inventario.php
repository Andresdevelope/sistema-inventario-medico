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

    'bloquear_vencidos_distribucion' => false,
    'bloquear_vencidos_ajuste_neg' => false,
];
