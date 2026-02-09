<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Destino;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $q = Bitacora::with('user')
            ->when($request->filled('user'), fn($qb)=>$qb->where('user_id', $request->input('user')))
            ->when($request->filled('accion'), fn($qb)=>$qb->where('accion', 'like', '%'.$request->input('accion').'%'))
            ->when($request->filled('desde'), fn($qb)=>$qb->where('fecha_hora', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn($qb)=>$qb->where('fecha_hora', '<=', $request->input('hasta')))
            ->orderBy('fecha_hora', 'desc');

        $bitacora = $q->paginate(25)->appends($request->query());
        $this->hydrateEntriesForUi($bitacora);
        $usuarios = \App\Models\User::select('id','name')->orderBy('name')->get();
        return view('bitacora.index', compact('bitacora','usuarios'));
    }

    protected function hydrateEntriesForUi(LengthAwarePaginator $bitacora): void
    {
        $collection = $bitacora->getCollection();
        $productoIds = [];
        $destinoIds = [];

        foreach ($collection as $entry) {
            $parsed = $this->decodeDetalles($entry->detalles);
            $entry->setAttribute('parsed_detalles', $parsed);
            if (is_array($parsed)) {
                if (!empty($parsed['producto_id'])) {
                    $productoIds[] = (int) $parsed['producto_id'];
                }
                if (!empty($parsed['destino_id'])) {
                    $destinoIds[] = (int) $parsed['destino_id'];
                }
            }
        }

        $productos = !empty($productoIds)
            ? Producto::whereIn('id', array_unique($productoIds))->get(['id','nombre','codigo','tipo_producto'])->keyBy('id')
            : collect();
        $destinos = !empty($destinoIds)
            ? Destino::whereIn('id', array_unique($destinoIds))->get(['id','nombre','codigo'])->keyBy('id')
            : collect();

        $collection->transform(function (Bitacora $entry) use ($productos, $destinos) {
            $meta = $this->resolveAccionMeta($entry->accion ?? '');
            $parsed = $entry->getAttribute('parsed_detalles');

            if ($parsed && Str::startsWith($entry->accion, 'movimiento.')) {
                $movementUi = $this->buildMovementUi($parsed, $productos, $destinos);
                $ui = array_merge($meta, $movementUi);
                $ui['chips'] = array_values(array_filter(array_merge($meta['chips'] ?? [], $movementUi['chips'] ?? [])));
                $entry->setAttribute('ui', $ui);
            } elseif ($parsed) {
                $entry->setAttribute('ui', array_merge($meta, [
                    'summary' => $this->arrayToSentence($parsed) ?? 'Detalle disponible',
                    'description' => null,
                    'sections' => $this->buildGenericSections($parsed),
                ]));
            } else {
                $entry->setAttribute('ui', array_merge($meta, [
                    'summary' => $entry->detalles ?? 'Sin detalles registrados',
                    'description' => null,
                    'sections' => [],
                ]));
            }

            return $entry;
        });
    }

    protected function decodeDetalles(?string $detalles): ?array
    {
        if (!$detalles) {
            return null;
        }

        try {
            $decoded = json_decode($detalles, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveAccionMeta(?string $accion): array
    {
        $accion = $accion ?? '';
        $defaults = [
            'badge_text' => 'Sistema',
            'badge_class' => 'secondary',
            'badge_icon' => 'fa-clipboard-list',
            'action_context' => Str::headline(str_replace(['.', '_'], ' ', $accion)) ?: 'Acción registrada',
            'action_description' => null,
            'chips' => [],
        ];

        $groups = [
            'movimiento.' => ['label' => 'Movimiento', 'class' => 'warning', 'icon' => 'fa-arrows-rotate', 'description' => 'Registro de movimientos físico-químicos'],
            'inventario.' => ['label' => 'Inventario', 'class' => 'info', 'icon' => 'fa-warehouse', 'description' => 'Cambios sobre inventario central'],
            'usuario.' => ['label' => 'Usuarios', 'class' => 'primary', 'icon' => 'fa-user-gear'],
            'auth.' => ['label' => 'Autenticación', 'class' => 'dark', 'icon' => 'fa-right-to-bracket'],
            'producto.' => ['label' => 'Productos', 'class' => 'success', 'icon' => 'fa-capsules'],
            'categoria.' => ['label' => 'Catálogos', 'class' => 'teal', 'icon' => 'fa-layer-group'],
            'proveedor.' => ['label' => 'Proveedores', 'class' => 'teal', 'icon' => 'fa-handshake'],
            'reportes.' => ['label' => 'Reportes', 'class' => 'purple', 'icon' => 'fa-chart-simple'],
        ];

        foreach ($groups as $prefix => $meta) {
            if (Str::startsWith($accion, $prefix)) {
                $actionName = Str::of($accion)->after($prefix)->replace(['.', '_'], ' ')->headline();
                return array_merge($defaults, [
                    'badge_text' => $meta['label'],
                    'badge_class' => $meta['class'],
                    'badge_icon' => $meta['icon'],
                    'action_context' => $actionName ?: $defaults['action_context'],
                    'action_description' => $meta['description'] ?? null,
                ]);
            }
        }

        return $defaults;
    }

    protected function buildMovementUi(array $parsed, Collection $productos, Collection $destinos): array
    {
        $productoId = isset($parsed['producto_id']) ? (int) $parsed['producto_id'] : null;
        $producto = $productoId ? $productos->get($productoId) : null;
        $destinoId = isset($parsed['destino_id']) ? (int) $parsed['destino_id'] : null;
        $destino = $destinoId ? $destinos->get($destinoId) : null;
        $tipo = $parsed['tipo'] ?? null;
        $modalidad = $parsed['modalidad'] ?? null;
        $cantidad = isset($parsed['cantidad']) ? (int) $parsed['cantidad'] : null;

        $tipoLabel = match ($tipo) {
            'ingreso' => ($producto && strtolower($producto->tipo_producto ?? '') === 'medicamento') ? 'Entrada por blíster' : 'Entrada',
            'egreso' => match ($modalidad) {
                'distribucion' => 'Distribución',
                'consumo' => 'Consumo',
                default => 'Salida',
            },
            'ajuste_pos' => 'Ajuste positivo',
            'ajuste_neg' => 'Ajuste negativo',
            default => 'Movimiento',
        };

        $unidad = ($producto && strtolower($producto->tipo_producto ?? '') === 'medicamento') ? 'blísteres' : 'unidades';
        $chips = [];
        if ($modalidad) {
            $chips[] = Str::headline($modalidad);
        }
        if (in_array($tipo, ['ajuste_pos', 'ajuste_neg'])) {
            $chips[] = 'Ajuste';
        }

        $parts = [];
        if ($cantidad) {
            $parts[] = number_format($cantidad, 0, ',', '.') . ' ' . $unidad;
        }
        if ($producto) {
            $parts[] = $producto->nombre;
        }

        $summary = $tipoLabel;
        if ($parts) {
            $summary .= ': ' . implode(' · ', $parts);
        }
        if ($destino && $modalidad === 'distribucion') {
            $summary .= ' → ' . $destino->nombre;
        } elseif ($modalidad === 'consumo' && !empty($parsed['area'])) {
            $summary .= ' en ' . $parsed['area'];
        }

        $sections = [];
        $movItems = array_filter([
            ['label' => 'Tipo de movimiento', 'value' => $tipoLabel],
            $modalidad ? ['label' => 'Modalidad', 'value' => Str::headline($modalidad)] : null,
            $cantidad ? ['label' => 'Cantidad', 'value' => number_format($cantidad, 0, ',', '.') . ' ' . $unidad] : null,
            !empty($parsed['fecha']) ? ['label' => 'Fecha operativa', 'value' => $this->formatDate($parsed['fecha'], 'd/m/Y')] : null,
            !empty($parsed['area']) ? ['label' => 'Área / salida', 'value' => $parsed['area']] : null,
            $destino ? ['label' => 'Destino', 'value' => trim($destino->nombre . ($destino->codigo ? ' · ' . $destino->codigo : ''))] : null,
            !empty($parsed['motivo']) ? ['label' => 'Motivo', 'value' => $parsed['motivo']] : null,
        ]);
        if ($movItems) {
            $sections[] = ['title' => 'Movimiento', 'items' => array_values($movItems)];
        }

        if ($producto) {
            $sections[] = [
                'title' => 'Producto',
                'items' => array_values(array_filter([
                    ['label' => 'Nombre', 'value' => $producto->nombre],
                    $producto->codigo ? ['label' => 'Código', 'value' => $producto->codigo] : null,
                    !empty($producto->tipo_producto) ? ['label' => 'Tipo', 'value' => Str::headline($producto->tipo_producto)] : null,
                    !empty($parsed['lote']) ? ['label' => 'Lote', 'value' => $parsed['lote']] : null,
                    !empty($parsed['fecha_vencimiento']) ? ['label' => 'Vence', 'value' => $this->formatDate($parsed['fecha_vencimiento'], 'd/m/Y')] : null,
                ])),
            ];
        }

        if ($modalidad === 'consumo') {
            $beneficiario = array_values(array_filter([
                !empty($parsed['tipo_identificacion']) ? ['label' => 'Tipo de identificación', 'value' => Str::headline($parsed['tipo_identificacion'])] : null,
                !empty($parsed['sexo']) ? ['label' => 'Sexo', 'value' => Str::headline($parsed['sexo'])] : null,
            ]));
            if ($beneficiario) {
                $sections[] = ['title' => 'Beneficiario', 'items' => $beneficiario];
            }
        }

        return [
            'summary' => $summary,
            'description' => $destino && $modalidad === 'distribucion'
                ? 'Distribución registrada hacia ' . $destino->nombre
                : null,
            'sections' => $sections,
            'chips' => array_values(array_unique($chips)),
        ];
    }

    protected function buildGenericSections(array $parsed): array
    {
        $items = [];
        foreach ($parsed as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $items[] = [
                    'label' => Str::headline(str_replace('_', ' ', (string) $key)),
                    'value' => $value === null ? '—' : (string) $value,
                ];
            } else {
                $items[] = [
                    'label' => Str::headline(str_replace('_', ' ', (string) $key)),
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }
        }

        return $items ? [['title' => 'Detalle', 'items' => $items]] : [];
    }

    protected function arrayToSentence(array $parsed): ?string
    {
        $parts = [];
        foreach ($parsed as $key => $value) {
            if (is_scalar($value) && $value !== '') {
                $parts[] = Str::headline(str_replace('_', ' ', (string) $key)) . ': ' . $value;
            }
            if (count($parts) >= 4) {
                break;
            }
        }

        return $parts ? implode(' · ', $parts) : null;
    }

    protected function formatDate(string $value, string $format): ?string
    {
        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
