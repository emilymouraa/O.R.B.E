<?php
/* Template do Espelho de Ponto — renderizado via Dompdf */
$now    = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$mesAno = $now->format('m/Y');
$ano    = (int) $now->format('Y');
$mes    = (int) $now->format('m');

$diasUteis = [];
$totalDias = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
for ($d = 1; $d <= $totalDias; $d++) {
    $data      = new DateTime("{$ano}-{$mes}-{$d}");
    $diaSemana = (int) $data->format('N');
    if ($diaSemana <= 5) {
        $diasUteis[] = [
            'data'    => $data->format('d/m/Y'),
            'semana'  => ['Seg','Ter','Qua','Qui','Sex'][$diaSemana - 1],
            'entrada' => '08:00',
            'saida1'  => '12:00',
            'entrada2'=> '13:00',
            'saida'   => '17:00',
            'total'   => '08:00',
        ];
    }
}

$totalHoras = count($diasUteis) * 8;
$totalHorasStr = $totalHoras . 'h00';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size:9pt; color:#1e293b; }

  .header { background:#1e3a5f; color:#fff; padding:16px 24px;
            display:flex; justify-content:space-between; align-items:center; }
  .header-left { display:flex; align-items:center; gap:14px; }
  .header-title { font-size:13pt; font-weight:700; }
  .header-sub   { font-size:8pt; opacity:.8; margin-top:2px; }
  .header-right { text-align:right; font-size:8pt; opacity:.85; }

  .servidor-box { background:#f1f5f9; border:1px solid #e2e8f0;
                  margin:16px 24px; border-radius:6px; padding:12px 16px; }
  .servidor-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
  .servidor-field label { font-size:7pt; color:#64748b; display:block; margin-bottom:2px; }
  .servidor-field span  { font-size:9pt; font-weight:600; color:#1e293b; }

  .section { margin:0 24px 16px; }
  .section-title { font-size:9pt; font-weight:700; color:#1e3a5f;
                   border-bottom:2px solid #1e3a5f; padding-bottom:4px; margin-bottom:8px; }

  table { width:100%; border-collapse:collapse; }
  thead th { background:#1e3a5f; color:#fff; padding:6px 10px;
             font-size:8pt; text-align:left; }
  tbody td { padding:5px 10px; font-size:8pt; border-bottom:1px solid #e2e8f0;
             text-align:center; }
  tbody td:first-child { text-align:left; }
  tbody tr:nth-child(even) td { background:#f8fafc; }

  .totais-box { margin:0 24px 16px; display:grid;
                grid-template-columns:repeat(3,1fr); gap:12px; }
  .total-card { border-radius:6px; padding:12px 16px; text-align:center; }
  .total-card label { font-size:7pt; display:block; margin-bottom:4px; font-weight:600; }
  .total-card span  { font-size:13pt; font-weight:700; }
  .total-card--azul   { background:#dbeafe; color:#1d4ed8; }
  .total-card--verde  { background:#dcfce7; color:#15803d; }
  .total-card--cinza  { background:#f1f5f9; color:#475569; }

  .aviso { margin:0 24px; background:#fef9c3; border:1px solid #fde047;
           border-radius:6px; padding:10px 14px; font-size:7.5pt; color:#854d0e; }

  .footer { position:fixed; bottom:0; left:0; right:0; padding:8px 24px;
            background:#f8fafc; border-top:1px solid #e2e8f0;
            font-size:7pt; color:#94a3b8; display:flex; justify-content:space-between; }
</style>
</head>
<body>

<div class="header">
  <div class="header-left">
    <?= $logoHtml ?>
    <div>
      <div class="header-title">Espelho de Ponto · <?= $mesAno ?></div>
      <div class="header-sub">Sistema ORBE · Polícia Rodoviária Federal</div>
    </div>
  </div>
  <div class="header-right">
    Competência<br><strong><?= $mesAno ?></strong>
  </div>
</div>

<div class="servidor-box">
  <div class="servidor-grid">
    <div class="servidor-field">
      <label>Nome</label>
      <span><?= htmlspecialchars($servidor['nome']) ?></span>
    </div>
    <div class="servidor-field">
      <label>RA</label>
      <span><?= htmlspecialchars($servidor['ra']) ?></span>
    </div>
    <div class="servidor-field">
      <label>Cargo</label>
      <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $servidor['cargo']))) ?></span>
    </div>
    <div class="servidor-field">
      <label>Unidade</label>
      <span><?= htmlspecialchars($servidor['unidade_sigla'] . ' — ' . $servidor['unidade']) ?></span>
    </div>
    <div class="servidor-field">
      <label>Mês de Referência</label>
      <span><?= $mesAno ?></span>
    </div>
    <div class="servidor-field">
      <label>Regime</label>
      <span>Jornada 40h semanais</span>
    </div>
  </div>
</div>

<div class="section">
  <div class="section-title">Registro de Frequência</div>
  <table>
    <thead>
      <tr>
        <th>Data</th>
        <th>Dia</th>
        <th>Entrada</th>
        <th>Saída Almoço</th>
        <th>Retorno</th>
        <th>Saída</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($diasUteis as $dia): ?>
      <tr>
        <td><?= $dia['data'] ?></td>
        <td><?= $dia['semana'] ?></td>
        <td><?= $dia['entrada'] ?></td>
        <td><?= $dia['saida1'] ?></td>
        <td><?= $dia['entrada2'] ?></td>
        <td><?= $dia['saida'] ?></td>
        <td><?= $dia['total'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="totais-box">
  <div class="total-card total-card--azul">
    <label>Dias Trabalhados</label>
    <span><?= count($diasUteis) ?> dias</span>
  </div>
  <div class="total-card total-card--verde">
    <label>Total de Horas</label>
    <span><?= $totalHorasStr ?></span>
  </div>
  <div class="total-card total-card--cinza">
    <label>Horas Extras / Banco</label>
    <span>00h00</span>
  </div>
</div>

<div class="aviso">
  ⚠️ <strong>Documento fictício gerado para fins de demonstração.</strong>
  Os registros de ponto apresentados são simulados e não representam frequência real.
</div>

<div class="footer">
  <span>ORBE · Sistema de Gestão de RH · PRF</span>
  <span>Gerado em <?= $now->format('d/m/Y H:i') ?></span>
</div>

</body>
</html>