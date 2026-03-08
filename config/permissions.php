<?php

return [
    'catalog' => [
        'categorias' => [
            'label' => 'Gestión de categorías',
            'items' => [
                'categorias.ver' => 'Ver categorías',
                'categorias.crear' => 'Crear categorías',
                'categorias.editar' => 'Editar categorías/subcategorías',
                'categorias.eliminar' => 'Eliminar categorías',
            ],
        ],
        'medicamentos' => [
            'label' => 'Medicamentos',
            'items' => [
                'medicamentos.ver' => 'Ver medicamentos',
                'medicamentos.crear' => 'Crear medicamentos',
                'medicamentos.editar' => 'Editar medicamentos',
                'medicamentos.eliminar' => 'Eliminar medicamentos',
            ],
        ],
        'inventario' => [
            'label' => 'Inventario',
            'items' => [
                'inventario.ver' => 'Ver inventario',
            ],
        ],
        'movimientos' => [
            'label' => 'Movimientos',
            'items' => [
                'movimientos.entrada' => 'Registrar entradas',
                'movimientos.distribucion' => 'Registrar distribuciones',
                'movimientos.consumo' => 'Registrar consumos',
                'movimientos.ajuste_positivo' => 'Registrar ajustes positivos',
                'movimientos.ajuste_negativo' => 'Registrar ajustes negativos',
            ],
        ],
        'reportes' => [
            'label' => 'Reportes',
            'items' => [
                'reportes.inventario' => 'Reportes de inventario',
                'reportes.salida' => 'Reportes de salidas/consumo',
            ],
        ],
    ],
];
