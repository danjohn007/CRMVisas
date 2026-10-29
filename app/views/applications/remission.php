<?php
$phone = trim((string)($clientData['telefono'] ?? $clientData['telefono_personal'] ?? $clientData['campo_6'] ?? ''));
$clientName = trim((string)($application['client_name'] ?? ''));
$issueDate = $infoSheet['entry_date'] ?: date('Y-m-d');
$observations = trim((string)($infoSheet['observations'] ?? ''));
$approval = $sale['approval_status'] ?? 'PENDIENTE';

$money = function($value) {
    return '$' . number_format((float)$value, 2);
};
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nota de Remisión <?= htmlspecialchars($application['folio']) ?></title>
<style>
    *{box-sizing:border-box}
    :root{--navy:#17385f;--ink:#172235;--muted:#5d6978;--line:#cfd6df;--paper:#fff}
    html,body{margin:0;padding:0}
    body{background:#dfe4ea;font-family:Arial,Helvetica,sans-serif;color:var(--ink)}
    .toolbar{max-width:8.5in;margin:16px auto 8px;display:flex;gap:10px;justify-content:flex-end;padding:0 4px}
    .toolbar a,.toolbar button{border:0;border-radius:8px;padding:10px 16px;font-weight:700;cursor:pointer;text-decoration:none;font-size:14px}
    .back{background:#4b5563;color:#fff}.print{background:var(--navy);color:#fff}

    /* Vista previa y papel real tamaño CARTA: 8.5 x 11 pulgadas. */
    .sheet{
        width:8.5in;
        min-height:11in;
        margin:10px auto 24px;
        background:var(--paper);
        padding:.48in .55in .45in;
        box-shadow:0 5px 24px rgba(0,0,0,.18);
        position:relative;
        overflow:hidden;
    }
    .header{display:grid;grid-template-columns:1.18in 1fr 1.82in;align-items:start;gap:.16in}
    .logo img{width:.88in;max-height:.82in;object-fit:contain;display:block;margin:auto}
    .brand-mini{text-align:center;font-size:6.7pt;color:#465468;font-weight:700;margin-top:3px;line-height:1.25}
    .title{text-align:center;padding-top:4px}
    .title h1{margin:0;font-size:19pt;color:var(--navy);letter-spacing:.4px}
    .title p{margin:5px 0 0;font-size:8pt;color:#536173}
    .meta{font-size:8.4pt;line-height:1.65;padding-top:3px}
    .meta strong{display:inline-block;min-width:.78in}
    .agency{margin-top:.15in;font-size:7.7pt;line-height:1.4;color:#485667}
    .agency strong{font-size:8.3pt;color:#1b304b}
    .section-title{margin-top:.12in;background:var(--navy);color:#fff;padding:6px 9px;font-size:8.7pt;font-weight:800;letter-spacing:.3px}
    .fields{padding:.10in .08in .02in;display:grid;grid-template-columns:1fr 1fr;gap:.11in .28in;font-size:8.4pt}
    .field{display:flex;gap:6px;align-items:flex-end}
    .field label{white-space:nowrap;font-weight:700;color:#506073}
    .line{flex:1;border-bottom:1px solid #7d8792;min-height:.18in;padding:0 3px 2px;color:var(--ink);font-weight:600}
    table{width:100%;border-collapse:collapse}
    .services th{background:var(--navy);color:#fff;padding:6px 8px;font-size:7.7pt;text-align:left}
    .services th:nth-child(2){width:.55in;text-align:center}
    .services th:nth-child(3),.services th:nth-child(4){width:1.08in;text-align:right}
    .services td{padding:7px 8px;border-bottom:1px solid #d7dde5;font-size:8.4pt}
    .services td:nth-child(2){text-align:center}.services td:nth-child(3),.services td:nth-child(4){text-align:right}
    .summary{padding:.10in .08in .02in;display:grid;grid-template-columns:1fr 1fr;gap:.08in .34in;font-size:8.4pt}
    .summary-row{display:flex;align-items:flex-end;gap:6px}
    .summary-row b{white-space:nowrap}.summary-value{flex:1;border-bottom:1px solid #7d8792;padding:0 3px 2px;font-weight:700;min-height:.18in}
    .methods{grid-column:1/-1;display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-top:2px}
    .method{border:1px solid var(--line);border-radius:5px;padding:6px 7px;display:flex;justify-content:space-between;gap:7px}
    .method .check{font-size:10pt;font-weight:800;color:var(--navy)}
    .observations{min-height:.55in;border-bottom:1px solid #aab3bf;padding:8px 9px;font-size:8.4pt;white-space:pre-wrap;line-height:1.4}
    .notice{margin-top:.11in;background:#edf3f6;padding:8px 9px;font-size:6.8pt;line-height:1.38;color:#4d5a68}
    .signatures{display:grid;grid-template-columns:1fr;max-width:2.55in;margin:.36in auto 0;text-align:center;font-size:7pt;color:#596675}
    .signature-line{border-top:1px solid #65717e;padding-top:5px}
    .status{margin-top:.08in;font-size:6.8pt;text-align:right;color:#6b7280}

    .terms-sheet{padding:.48in .55in .45in}
    .terms-sheet h1{text-align:center;margin:0 0 .17in;color:var(--navy);font-size:17pt;letter-spacing:.25px}
    .terms-intro{font-size:8pt;color:#5b6775;margin-bottom:.13in;line-height:1.4}
    .terms-list{margin:0;padding:0;list-style:none;counter-reset:term}
    .terms-list li{counter-increment:term;font-size:7.65pt;line-height:1.38;margin:0 0 .065in;text-align:justify}
    .terms-list li::before{content:counter(term) ". ";font-weight:800;color:var(--navy)}
    .terms-list strong{color:var(--ink)}
    .client-acceptance{margin-top:.18in;border-top:1px solid #d7dde5;padding-top:.14in}
    .client-acceptance p{font-size:8pt;font-weight:700;margin-bottom:.34in}
    .client-sign-grid{display:grid;grid-template-columns:1.25fr 1fr;gap:.48in}
    .sign-line{border-top:1px solid #586675;padding-top:5px;text-align:center;font-size:7pt;color:#596675}
    .terms-footer{position:absolute;left:.55in;right:.55in;bottom:.30in;text-align:center;font-size:6.8pt;color:#697585}

    @media(max-width:900px){
        body{background:#eef1f4}
        .toolbar{padding:0 10px}
        .sheet{width:calc(100% - 18px);min-height:auto;margin:8px auto 18px;padding:24px 20px;overflow:visible}
        .header{grid-template-columns:100px 1fr}
        .meta{grid-column:1/-1}
        .fields,.summary{grid-template-columns:1fr}
        .methods{grid-template-columns:1fr}
        .client-sign-grid{grid-template-columns:1fr;gap:34px}
        .terms-footer{position:static;margin-top:24px}
    }
    @media print{
        @page{size:Letter portrait;margin:0}
        html,body{width:8.5in;background:#fff}
        .toolbar{display:none!important}
        .sheet{
            width:8.5in;
            height:11in;
            min-height:11in;
            margin:0;
            padding:.48in .55in .45in;
            box-shadow:none;
            overflow:hidden;
            page-break-after:always;
            break-after:page;
        }
        .sheet:last-of-type{page-break-after:auto;break-after:auto}
    }
</style>
</head>
<body>
<div class="toolbar">
    <a class="back" href="<?= BASE_URL ?>/solicitudes/ver/<?= (int)$application['id'] ?>">Volver al expediente</a>
    <button class="print" onclick="window.print()">Imprimir Nota de Remisión</button>
</div>

<main class="sheet">
    <div class="header">
        <div class="logo">
            <img src="<?= BASE_URL ?>/logo-visas-login.png" alt="Trámite de Visa Americana en Querétaro">
            <div class="brand-mini">TRÁMITE VISA AMERICANA<br>QUERÉTARO<br>BY VANESA TORRES</div>
        </div>
        <div class="title">
            <h1>NOTA DE REMISIÓN</h1>
            <p>Documento de control de servicios y pagos</p>
        </div>
        <div class="meta">
            <div><strong>Folio:</strong> <?= htmlspecialchars($application['folio']) ?></div>
            <div><strong>Fecha de emisión:</strong> <?= date('d/m/Y',strtotime($issueDate)) ?></div>
        </div>
    </div>

    <div class="agency">
        <strong>TRÁMITE DE VISA AMERICANA EN QUERÉTARO BY VANESA TORRES</strong><br>
        Blvd. Bernardo Quintana 124, Int. 111. Col. Carretas, dentro de Plaza Lúbana. Querétaro, Qro.<br>
        WhatsApp: 442 204 54 48
    </div>

    <div class="section-title">DATOS DEL CLIENTE</div>
    <div class="fields">
        <div class="field"><label>Nombre completo:</label><div class="line"><?= htmlspecialchars($clientName) ?></div></div>
        <div class="field"><label>Teléfono:</label><div class="line"><?= htmlspecialchars($phone ?: '—') ?></div></div>
        <div class="field"><label>N.º de expediente:</label><div class="line"><?= htmlspecialchars($application['folio']) ?></div></div>
        <div class="field"><label>Asesora:</label><div class="line"><?= htmlspecialchars($application['advisor_name'] ?: '—') ?></div></div>
    </div>

    <div class="section-title">SERVICIOS CONTRATADOS</div>
    <table class="services">
        <thead>
            <tr>
                <th>SERVICIO / CONCEPTO</th>
                <th>CANT.</th>
                <th>PRECIO UNIT.</th>
                <th>IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($sale['service_name_snapshot']) ?></td>
                <td><?= (int)$sale['quantity'] ?></td>
                <td><?= $money($sale['unit_price']) ?></td>
                <td><?= $money($sale['total_amount']) ?></td>
            </tr>
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
        </tbody>
    </table>

    <div class="section-title">RESUMEN DE COBRO</div>
    <div class="summary">
        <div class="summary-row"><b>Importe total:</b><div class="summary-value"><?= $money($sale['total_amount']) ?></div></div>
        <div class="summary-row"><b>Total recibido:</b><div class="summary-value"><?= $money($received) ?></div></div>
        <div class="summary-row"><b>Saldo pendiente:</b><div class="summary-value"><?= $money($pending) ?></div></div>
        <div class="summary-row"><b>Fecha de pago:</b><div class="summary-value"><?= date('d/m/Y',strtotime($issueDate)) ?></div></div>

        <div class="methods">
            <div class="method"><span><span class="check"><?= $paymentBreakdown['EFECTIVO']>0 ? '☑' : '☐' ?></span> Efectivo</span><strong><?= $money($paymentBreakdown['EFECTIVO']) ?></strong></div>
            <div class="method"><span><span class="check"><?= $paymentBreakdown['TERMINAL_TARJETA']>0 ? '☑' : '☐' ?></span> Terminal</span><strong><?= $money($paymentBreakdown['TERMINAL_TARJETA']) ?></strong></div>
            <div class="method"><span><span class="check"><?= $paymentBreakdown['TRANSFERENCIA']>0 ? '☑' : '☐' ?></span> Transferencia</span><strong><?= $money($paymentBreakdown['TRANSFERENCIA']) ?></strong></div>
        </div>
    </div>

    <div class="section-title">OBSERVACIONES</div>
    <div class="observations"><?= htmlspecialchars($observations ?: ' ') ?></div>

    <div class="notice">
        <strong>Información importante:</strong> Esta nota es un comprobante administrativo de los servicios y pagos
        registrados por la agencia; no es una factura ni un comprobante fiscal. Los pagos capturados quedan sujetos
        a verificación administrativa. La aprobación de visas o documentos oficiales corresponde exclusivamente a
        las autoridades competentes.
    </div>

    <div class="signatures">
        <div class="signature-line">Elaboró / responsable de la agencia<br><?= htmlspecialchars($application['advisor_name'] ?: '') ?></div>
    </div>

    <div class="status">Estado administrativo de la venta: <?= htmlspecialchars($approval) ?></div>
</main>

<main class="sheet terms-sheet">
    <h1>TÉRMINOS Y CONDICIONES DEL SERVICIO</h1>
    <p class="terms-intro">
        El cliente declara haber recibido información sobre el servicio contratado y acepta las siguientes condiciones:
    </p>

    <ol class="terms-list">
        <li><strong>PAGOS Y REEMBOLSOS.</strong> No se realizan reembolsos por concepto de honorarios ni por servicios contratados, una vez iniciado el trámite.</li>
        <li><strong>ACCESOS AL SISTEMA.</strong> Por motivos de seguridad, confidencialidad y control administrativo, los accesos a las plataformas de visas son administrados exclusivamente por la agencia y no se proporcionan al cliente durante la gestión del trámite.</li>
        <li><strong>APROBACIÓN DE VISAS.</strong> La agencia no garantiza la aprobación de ninguna visa. La decisión corresponde exclusivamente a la Embajada o Consulado.</li>
        <li><strong>DISPONIBILIDAD DE CITAS.</strong> Las fechas de citas están sujetas a disponibilidad y pueden ser modificadas, canceladas o reprogramadas por las autoridades correspondientes, sin intervención de la agencia.</li>
        <li><strong>RESPONSABILIDAD DEL CLIENTE.</strong> El cliente deberá proporcionar información verdadera, completa y correcta, así como entregar toda la documentación solicitada dentro de los plazos establecidos por la agencia. Cualquier retraso, omisión o incumplimiento en la entrega de información o documentación podrá ocasionar la cancelación definitiva de su trámite, sin derecho a devolución de los pagos realizados.</li>
        <li><strong>CAMBIOS Y REPROGRAMACIONES.</strong> Cualquier modificación solicitada por el cliente después de la programación de sus citas generará honorarios adicionales, los cuales deberán cubrirse antes de realizar cualquier cambio.</li>
        <li><strong>INASISTENCIAS.</strong> La agencia no se responsabiliza por citas perdidas debido a retrasos, inasistencias o incumplimiento de indicaciones por parte del cliente.</li>
        <li><strong>VERIFICACIÓN DE CITAS.</strong> El cliente deberá comunicarse vía WhatsApp al 442 449 56 75, un día antes de sus citas, para confirmar la información vigente.</li>
        <li><strong>TIEMPOS DE PROCESAMIENTO.</strong> Los plazos de atención, resolución y entrega de documentos dependen de las autoridades correspondientes y no pueden garantizarse.</li>
        <li><strong>CANCELACIÓN DEL SERVICIO.</strong> Si el cliente decide cancelar, suspender o abandonar voluntariamente su trámite, no se realizará ninguna devolución de los pagos efectuados.</li>
        <li><strong>GASTOS ADICIONALES.</strong> Los pagos gubernamentales, traslados, mensajería y demás servicios adicionales se cobrarán por separado, salvo que estén expresamente incluidos en el recibo.</li>
        <li><strong>ACEPTACIÓN.</strong> Al firmar este recibo, el cliente manifiesta haber leído, comprendido y aceptado los términos y condiciones del servicio contratado.</li>
    </ol>

    <div class="client-acceptance">
        <p>NOMBRE Y FIRMA DEL CLIENTE:</p>
        <div class="client-sign-grid">
            <div class="sign-line">Nombre completo del cliente</div>
            <div class="sign-line">Firma del cliente</div>
        </div>
    </div>

    <div class="terms-footer">
        Folio de expediente: <?= htmlspecialchars($application['folio']) ?> · <?= htmlspecialchars($clientName) ?>
    </div>
</main>

<?php if($autoPrint): ?>
<script>
window.addEventListener('load',()=>setTimeout(()=>window.print(),350));
</script>
<?php endif; ?>
</body>
</html>
