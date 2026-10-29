<?php
$money=function($value){ return '$'.number_format((float)$value,2); };
$approval=$sale['approval_status']??'PENDIENTE';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ticket <?= htmlspecialchars($sale['sale_folio']) ?></title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#eef1f4;font-family:Arial,Helvetica,sans-serif;color:#182433}
.toolbar{max-width:520px;margin:16px auto 0;display:flex;gap:8px;justify-content:flex-end}
.toolbar a,.toolbar button{border:0;border-radius:8px;padding:9px 13px;font-weight:700;text-decoration:none;cursor:pointer}
.back{background:#4b5563;color:#fff}.print{background:#17385f;color:#fff}
.ticket{width:480px;margin:10px auto 28px;background:#fff;padding:26px 30px;box-shadow:0 4px 20px rgba(0,0,0,.12);border-radius:10px}
.logo img{width:92px;max-height:86px;object-fit:contain;margin:0 auto 8px}
h1{text-align:center;font-size:19px;margin:0;color:#17385f}
.subtitle{text-align:center;color:#6b7280;font-size:10px;margin:4px 0 18px}
.row{display:flex;justify-content:space-between;gap:14px;padding:7px 0;border-bottom:1px dashed #d2d7dd;font-size:11px}
.row span:first-child{color:#667085}.row strong{text-align:right}
.service{margin:14px 0;padding:12px;background:#f4f7fa;border-radius:8px}
.service .name{font-weight:800;font-size:13px}.service .small{font-size:10px;color:#667085;margin-top:4px}
.total{font-size:16px;font-weight:800}
.methods{margin-top:14px}
.method{display:flex;justify-content:space-between;font-size:11px;padding:5px 0}
.notice{margin-top:16px;background:#edf3f6;padding:10px;border-radius:7px;font-size:9px;line-height:1.4;color:#55616e}
.status{text-align:center;margin-top:14px;padding:8px;border-radius:7px;font-size:10px;font-weight:800;background:#fff5d9;color:#805b00}
.footer{text-align:center;font-size:9px;color:#6b7280;margin-top:18px;line-height:1.45}
@media(max-width:520px){.ticket{width:calc(100% - 16px);margin:8px;padding:22px}.toolbar{padding:0 8px}}
@media print{
 @page{size:80mm auto;margin:5mm}
 body{background:#fff}
 .toolbar{display:none!important}
 .ticket{width:70mm;margin:0;padding:0;box-shadow:none;border-radius:0}
}
</style>
</head>
<body>
<div class="toolbar">
    <a class="back" href="<?= BASE_URL ?>/ingresos">Volver a Mis ventas</a>
    <button class="print" onclick="window.print()">Imprimir ticket</button>
</div>

<main class="ticket">
    <div class="logo"><img src="<?= BASE_URL ?>/logo-visas-login.png" alt="Logo"></div>
    <h1>NOTA DE REMISIÓN</h1>
    <div class="subtitle">Ticket de compra / comprobante administrativo</div>

    <div class="row"><span>Folio de venta</span><strong><?= htmlspecialchars($sale['sale_folio']) ?></strong></div>
    <div class="row"><span>Fecha</span><strong><?= date('d/m/Y',strtotime($sale['contract_date'])) ?></strong></div>
    <div class="row"><span>Cliente</span><strong><?= htmlspecialchars($sale['client_name_snapshot']) ?></strong></div>
    <div class="row"><span>Folio cliente</span><strong><?= htmlspecialchars($sale['client_folio_snapshot']?:'—') ?></strong></div>
    <div class="row"><span>Asesora</span><strong><?= htmlspecialchars($sale['advisor_name']?:'—') ?></strong></div>

    <div class="service">
        <div class="name"><?= htmlspecialchars($sale['service_name_snapshot']) ?></div>
        <div class="small">Cantidad: <?= (int)$sale['quantity'] ?> · Precio unitario: <?= $money($sale['unit_price']) ?></div>
    </div>

    <div class="row total"><span>Total de compra</span><strong><?= $money($sale['total_amount']) ?></strong></div>
    <div class="row"><span>Total reportado</span><strong><?= $money($reported) ?></strong></div>
    <div class="row"><span>Saldo pendiente</span><strong><?= $money($pending) ?></strong></div>

    <div class="methods">
        <?php if($breakdown['EFECTIVO']>0): ?><div class="method"><span>Efectivo</span><strong><?= $money($breakdown['EFECTIVO']) ?></strong></div><?php endif; ?>
        <?php if($breakdown['TERMINAL_TARJETA']>0): ?><div class="method"><span>Terminal</span><strong><?= $money($breakdown['TERMINAL_TARJETA']) ?></strong></div><?php endif; ?>
        <?php if($breakdown['TRANSFERENCIA']>0): ?><div class="method"><span>Transferencia</span><strong><?= $money($breakdown['TRANSFERENCIA']) ?></strong></div><?php endif; ?>
    </div>

    <div class="notice">
        Este ticket funciona como comprobante administrativo de la venta registrada en la intranet.
        No es una factura ni comprobante fiscal. El movimiento queda sujeto a validación de Gerencia.
    </div>

    <div class="status">ESTADO: <?= htmlspecialchars($approval) ?></div>

    <div class="footer">
        TRÁMITE DE VISA AMERICANA EN QUERÉTARO BY VANESA TORRES<br>
        Gracias por su compra.
    </div>
</main>

<?php if($autoPrint): ?>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),300));</script>
<?php endif; ?>
</body>
</html>
