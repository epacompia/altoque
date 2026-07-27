<?php

namespace App\Services;

use App\Models\CommissionRule;
use App\Models\OrderCommission;
use App\Models\CommissionAuditLog;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommissionService
{
    /**
     * Calcular comisión para un pedido
     * Aplica las reglas en este orden de prioridad:
     * 1. Rango de precio (price_range)
     * 2. Categoría de producto (category)
     * 3. Tipo de vendedor (vendor_type)
     * 4. Comisión base (base)
     * 
     * @param Order $order
     * @return OrderCommission
     */
    public function calculateCommissionForOrder(Order $order)
    {
        try {
            // Obtener todas las reglas activas
            $activeRules = CommissionRule::getActiveRules();
            
            if ($activeRules->isEmpty()) {
                $rule = CommissionRule::getBaseRule();
            } else {
                $rule = $this->selectBestRule($order, $activeRules);
            }

            // Validar que el porcentaje esté en rango
            if (!$rule->isValidPercentage()) {
                throw new \Exception("Porcentaje de comisión inválido: {$rule->commission_percentage}%. Debe estar entre 10% y 25%");
            }

            // Obtener el total del pedido (sin comisión)
            $orderTotal = $order->total;

            // Calcular monto de comisión
            $commissionAmount = $this->calculateAmount($orderTotal, $rule->commission_percentage);
            
            // Calcular monto neto para el vendedor
            $netAmount = $orderTotal - $commissionAmount;

            // Crear registro de comisión
            $orderCommission = OrderCommission::create([
                'order_id' => $order->id,
                'commission_rule_id' => $rule->id,
                'order_total' => $orderTotal,
                'commission_percentage' => $rule->commission_percentage,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'status' => 'pending',
                'rule_applied' => $this->getRuleDescription($rule),
                'calculated_at' => now()
            ]);

            // Registrar en auditoría
            CommissionAuditLog::log(
                'calculated',
                $rule->id,
                null,
                [
                    'order_id' => $order->id,
                    'order_total' => $orderTotal,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $netAmount,
                    'percentage' => $rule->commission_percentage
                ],
                'ORDER_PAYMENT',
                request()->ip(),
                "Comisión calculada para pedido #{$order->id}"
            );

            return $orderCommission;

        } catch (\Exception $e) {
            \Log::error("Error calculando comisión para pedido {$order->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Seleccionar la regla más específica/aplicable
     * Orden de prioridad:
     * 1. Rango de precio (más específico)
     * 2. Categoría de producto
     * 3. Tipo de vendedor
     * 4. Base (menos específico)
     */
    private function selectBestRule(Order $order, $activeRules)
    {
        // 1. Buscar por rango de precio
        foreach ($activeRules as $rule) {
            if ($rule->type === 'price_range') {
                if ($order->total >= $rule->min_amount && 
                    $order->total <= $rule->max_amount) {
                    return $rule;
                }
            }
        }

        // 2. Buscar por categoría (usar la primera categoría del pedido)
        if ($order->orderItems && $order->orderItems->count() > 0) {
            $firstProduct = $order->orderItems->first()->menuItem;
            if ($firstProduct && $firstProduct->category_id) {
                foreach ($activeRules as $rule) {
                    if ($rule->type === 'category' && 
                        $rule->category_id == $firstProduct->category_id) {
                        return $rule;
                    }
                }
            }
        }

        // 3. Buscar por tipo de vendedor (si existe)
        if ($order->foodStall) {
            $vendorType = $order->foodStall->vendor_type ?? 'normal';
            foreach ($activeRules as $rule) {
                if ($rule->type === 'vendor_type' && 
                    $rule->vendor_type === $vendorType) {
                    return $rule;
                }
            }
        }

        // 4. Usar la regla base
        return CommissionRule::getBaseRule();
    }

    /**
     * Calcular el monto de comisión
     */
    private function calculateAmount($total, $percentage)
    {
        return round(($total * $percentage) / 100, 2);
    }

    /**
     * Obtener descripción de la regla aplicada
     */
    private function getRuleDescription(CommissionRule $rule)
    {
        switch ($rule->type) {
            case 'base':
                return "Comisión base: {$rule->commission_percentage}%";
            case 'category':
                return "Comisión por categoría: {$rule->commission_percentage}%";
            case 'price_range':
                return "Comisión por rango de precio (S/{$rule->min_amount} - S/{$rule->max_amount}): {$rule->commission_percentage}%";
            case 'vendor_type':
                return "Comisión por tipo de vendedor ({$rule->vendor_type}): {$rule->commission_percentage}%";
            default:
                return "Comisión aplicada: {$rule->commission_percentage}%";
        }
    }

    /**
     * Obtener reporte de comisiones por rango de fechas
     */
    public function getCommissionReport($from, $to)
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        // Totales agregados en SQL
        $totals = OrderCommission::whereBetween('calculated_at', [$fromDate, $toDate])
            ->selectRaw('COUNT(*) as total_pedidos')
            ->selectRaw('COALESCE(SUM(order_total), 0) as monto_total_pedidos')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as comisiones_totales')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as monto_neto_vendedores')
            ->first();

        // Conteo por estado en SQL
        $porEstado = OrderCommission::whereBetween('calculated_at', [$fromDate, $toDate])
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END), 0) as refunded")
            ->first();

        // Agrupado por regla en SQL
        $porRegla = OrderCommission::whereBetween('calculated_at', [$fromDate, $toDate])
            ->selectRaw('commission_rule_id')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as comision_total')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as monto_neto')
            ->groupBy('commission_rule_id')
            ->get()
            ->keyBy('commission_rule_id');

        // Enriquecer con el porcentaje de cada regla
        $reglas = CommissionRule::pluck('commission_percentage', 'id');
        $porReglaFormato = $porRegla->map(function ($item) use ($reglas) {
            return [
                'cantidad' => (int) $item->cantidad,
                'comision_total' => (float) $item->comision_total,
                'porcentaje' => $reglas->get($item->commission_rule_id, 0),
                'monto_neto' => (float) $item->monto_neto,
            ];
        });

        return [
            'periodo' => [
                'desde' => $from,
                'hasta' => $to
            ],
            'total_pedidos' => (int) $totals->total_pedidos,
            'monto_total_pedidos' => (float) $totals->monto_total_pedidos,
            'comisiones_totales' => (float) $totals->comisiones_totales,
            'monto_neto_vendedores' => (float) $totals->monto_neto_vendedores,
            'por_estado' => [
                'pending' => (int) $porEstado->pending,
                'completed' => (int) $porEstado->completed,
                'refunded' => (int) $porEstado->refunded,
            ],
            'por_regla' => $porReglaFormato,
        ];
    }

    /**
     * Marcar comisión como completada
     */
    public function markAsCompleted(OrderCommission $commission, array $transferData = [])
    {
        $update = [
            'status' => 'completed',
            'transferred_at' => now()
        ];

        if (!empty($transferData['method'])) {
            $update['transfer_method'] = $transferData['method'];
        }
        if (!empty($transferData['transaction_id'])) {
            $update['transfer_transaction_id'] = $transferData['transaction_id'];
        }

        $commission->update($update);

        CommissionAuditLog::log(
            'transferred',
            $commission->commission_rule_id,
            ['status' => 'pending'],
            ['status' => 'completed'],
            'PAYMENT_SYSTEM',
            request()->ip() ?? 'SYSTEM',
            "Comisión transferida al vendedor - Pedido #{$commission->order_id}"
        );

        return $commission;
    }

    /**
     * Revertir comisión (devolución de pedido)
     */
    public function refundCommission(OrderCommission $commission)
    {
        $oldStatus = $commission->status;
        $commission->update([
            'status' => 'refunded',
            'transferred_at' => null
        ]);

        // Registrar en auditoría
        CommissionAuditLog::log(
            'refunded',
            $commission->commission_rule_id,
            ['status' => $oldStatus],
            ['status' => 'refunded'],
            'PAYMENT_SYSTEM',
            request()->ip() ?? 'SYSTEM',
            "Comisión reembolsada - Pedido #{$commission->order_id}"
        );

        // Registro contable: devolver monto neto al cliente -> débito en cuenta de la empresa
        try {
            \App\Models\CompanyAccountEntry::create([
                'type' => 'debit',
                'amount' => $commission->order_total,
                'description' => 'Refund for order #' . $commission->order_id,
                'order_id' => $commission->order_id,
                'reference' => 'REFUND-COMM-' . $commission->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create company account entry for refund: ' . $e->getMessage());
        }

        return $commission;
    }

    /**
     * Actualizar regla de comisión
     */
    public function updateRule(CommissionRule $rule, array $data, $changedBy)
    {
        $oldValues = $rule->toArray();

        $rule->update($data);

        CommissionAuditLog::log(
            'updated',
            $rule->id,
            $oldValues,
            $rule->toArray(),
            $changedBy,
            request()->ip(),
            "Regla de comisión actualizada"
        );

        return $rule;
    }

    /**
     * Crear nueva regla de comisión
     */
    public function createRule(array $data, $createdBy)
    {
        // Validar porcentaje
        if ($data['commission_percentage'] < 10 || $data['commission_percentage'] > 25) {
            throw new \Exception("El porcentaje debe estar entre 10% y 25%");
        }

        $rule = CommissionRule::create(array_merge($data, [
            'created_by' => $createdBy
        ]));

        CommissionAuditLog::log(
            'created',
            $rule->id,
            null,
            $rule->toArray(),
            $createdBy,
            request()->ip(),
            "Nueva regla de comisión creada"
        );

        return $rule;
    }
}
