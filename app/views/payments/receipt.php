<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago - <?php echo htmlspecialchars($payment['payment_reference']); ?></title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .receipt-header h1 {
            font-size: 28px;
            color: #3b82f6;
            margin-bottom: 5px;
        }
        
        .receipt-header .subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .receipt-info div {
            flex: 1;
        }
        
        .receipt-info h3 {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .receipt-info p {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        .payment-details {
            margin-bottom: 30px;
        }
        
        .payment-details h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .amount-box {
            background: #3b82f6;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        
        .amount-box .label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .amount-box .amount {
            font-size: 36px;
            font-weight: bold;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-refunded {
            background: #e9d5ff;
            color: #6b21a8;
        }
        
        .notes-section {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        
        .notes-section h3 {
            font-size: 14px;
            color: #92400e;
            margin-bottom: 8px;
        }
        
        .notes-section p {
            font-size: 13px;
            color: #78350f;
        }
        
        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .no-print {
            margin: 20px 0;
            text-align: center;
        }
        
        .no-print button {
            padding: 12px 24px;
            margin: 0 5px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-print {
            background: #3b82f6;
            color: white;
        }
        
        .btn-print:hover {
            background: #2563eb;
        }
        
        .btn-back {
            background: #6b7280;
            color: white;
        }
        
        .btn-back:hover {
            background: #4b5563;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .receipt-container {
                padding: 20px;
            }
            
            body {
                background: white;
            }
            
            @page {
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>🧾 RECIBO DE PAGO</h1>
            <p class="subtitle">CRM Visas - Sistema de Gestión de Trámites</p>
        </div>
        
        <div class="receipt-info">
            <div>
                <h3>Número de Recibo</h3>
                <p><?php echo htmlspecialchars($payment['payment_reference']); ?></p>
            </div>
            <div>
                <h3>Fecha de Emisión</h3>
                <p><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></p>
            </div>
            <div>
                <h3>Estado</h3>
                <p>
                    <?php
                    $statusClass = 'status-pending';
                    $statusText = 'Pendiente';
                    
                    switch ($payment['payment_status']) {
                        case 'completed':
                            $statusClass = 'status-completed';
                            $statusText = 'Completado';
                            break;
                        case 'failed':
                            $statusClass = 'status-failed';
                            $statusText = 'Fallido';
                            break;
                        case 'refunded':
                            $statusClass = 'status-refunded';
                            $statusText = 'Reembolsado';
                            break;
                    }
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </p>
            </div>
        </div>
        
        <div class="amount-box">
            <div class="label">MONTO TOTAL</div>
            <div class="amount">$<?php echo number_format($payment['amount'], 2); ?> <?php echo htmlspecialchars($payment['currency']); ?></div>
        </div>
        
        <div class="payment-details">
            <h2>Información del Cliente</h2>
            <div class="detail-row">
                <span class="detail-label">Nombre:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Correo Electrónico:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['email']); ?></span>
            </div>
        </div>
        
        <div class="payment-details">
            <h2>Detalles del Servicio</h2>
            <div class="detail-row">
                <span class="detail-label">Número de Solicitud:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['request_number']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Servicio:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['service_name']); ?></span>
            </div>
        </div>
        
        <div class="payment-details">
            <h2>Detalles del Pago</h2>
            <div class="detail-row">
                <span class="detail-label">Método de Pago:</span>
                <span class="detail-value">
                    <?php 
                    $methods = [
                        'cash' => 'Efectivo',
                        'credit_card' => 'Tarjeta de Crédito',
                        'debit_card' => 'Tarjeta de Débito',
                        'bank_transfer' => 'Transferencia Bancaria',
                        'paypal' => 'PayPal',
                        'other' => 'Otro'
                    ];
                    echo $methods[$payment['payment_method']] ?? ucfirst($payment['payment_method']);
                    ?>
                </span>
            </div>
            <?php if ($payment['transaction_id']): ?>
            <div class="detail-row">
                <span class="detail-label">ID de Transacción:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($payment['payment_date']): ?>
            <div class="detail-row">
                <span class="detail-label">Fecha de Pago:</span>
                <span class="detail-value"><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($payment['due_date']): ?>
            <div class="detail-row">
                <span class="detail-label">Fecha de Vencimiento:</span>
                <span class="detail-value"><?php echo date('d/m/Y', strtotime($payment['due_date'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($payment['notes']): ?>
        <div class="notes-section">
            <h3>Notas Adicionales</h3>
            <p><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="receipt-footer">
            <p>Este es un recibo generado automáticamente por el sistema CRM Visas.</p>
            <p>Para cualquier consulta o aclaración, por favor contacte a nuestro equipo de soporte.</p>
            <p style="margin-top: 10px; font-weight: 600;">Gracias por confiar en nosotros.</p>
        </div>
        
        <div class="no-print">
            <button class="btn-print" id="printBtn">
                <i class="fas fa-print"></i> Imprimir Recibo
            </button>
            <button class="btn-back" id="backBtn">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
        </div>
    </div>
    
    <script>
        // Print button handler
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });
        
        // Back button handler
        document.getElementById('backBtn').addEventListener('click', function() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?php echo BASE_URL; ?>/public/index.php?page=payments';
            }
        });
    </script>
</body>
</html>
