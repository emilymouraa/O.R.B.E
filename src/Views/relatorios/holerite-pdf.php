<?php
/* Template do Holerite — renderizado via Dompdf */
$now    = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$mesAno = $now->format('m/Y');
$mes    = (int) $now->format('m');
$ano    = (int) $now->format('Y');
$meses  = ['janeiro','fevereiro','março','abril','maio','junho',
           'julho','agosto','setembro','outubro','novembro','dezembro'];
$mesExtenso = $meses[$mes - 1] . ' de ' . $ano;

$vencimentos = [
    ['descricao' => 'Vencimento Básico',              'valor' => 4500.00],
    ['descricao' => 'Gratificação de Atividade',      'valor' => 1200.00],
    ['descricao' => 'Adicional de Tempo de Serviço',  'valor' =>  380.00],
    ['descricao' => 'Auxílio Alimentação',             'valor' =>  458.00],
    ['descricao' => 'Auxílio Transporte',              'valor' =>  200.00],
];

$descontos = [
    ['descricao' => 'INSS / RPPS',                   'valor' =>  720.00],
    ['descricao' => 'IRRF',                           'valor' =>  412.00],
    ['descricao' => 'Plano de Saúde',                 'valor' =>  198.00],
    ['descricao' => 'Contribuição Sindical',          'valor' =>   45.00],
];

$totalVencimentos = array_sum(array_column($vencimentos, 'valor'));
$totalDescontos   = array_sum(array_column($descontos,   'valor'));
$totalLiquido     = $totalVencimentos - $totalDescontos;
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
  tbody td { padding:6px 10px; font-size:8pt; border-bottom:1px solid #e2e8f0; }
  tbody tr:nth-child(even) td { background:#f8fafc; }
  .td-valor { text-align:right; }

  .totais-box { margin:0 24px 16px; display:grid;
                grid-template-columns:repeat(3,1fr); gap:12px; }
  .total-card { border-radius:6px; padding:12px 16px; text-align:center; }
  .total-card label { font-size:7pt; display:block; margin-bottom:4px; font-weight:600; }
  .total-card span  { font-size:13pt; font-weight:700; }
  .total-card--verde  { background:#dcfce7; color:#15803d; }
  .total-card--vermelho { background:#fee2e2; color:#dc2626; }
  .total-card--azul   { background:#dbeafe; color:#1d4ed8; }

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
      <div class="header-title">Contracheque · <?= $mesAno ?></div>
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
      <label>Situação</label>
      <span><?= ucfirst(htmlspecialchars($servidor['situacao'])) ?></span>
    </div>
  </div>
</div>

<div class="section">
  <div class="section-title">Vencimentos</div>
  <table>
    <thead><tr><th>Descrição</th><th style="text-align:right">Valor (R$)</th></tr></thead>
    <tbody>
      <?php foreach ($vencimentos as $v): ?>
      <tr>
        <td><?= $v['descricao'] ?></td>
        <td class="td-valor"><?= number_format($v['valor'], 2, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="section">
  <div class="section-title">Descontos</div>
  <table>
    <thead><tr><th>Descrição</th><th style="text-align:right">Valor (R$)</th></tr></thead>
    <tbody>
      <?php foreach ($descontos as $d): ?>
      <tr>
        <td><?= $d['descricao'] ?></td>
        <td class="td-valor"><?= number_format($d['valor'], 2, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="totais-box">
  <div class="total-card total-card--verde">
    <label>Total de Vencimentos</label>
    <span>R$ <?= number_format($totalVencimentos, 2, ',', '.') ?></span>
  </div>
  <div class="total-card total-card--vermelho">
    <label>Total de Descontos</label>
    <span>R$ <?= number_format($totalDescontos, 2, ',', '.') ?></span>
  </div>
  <div class="total-card total-card--azul">
    <label>Valor Líquido</label>
    <span>R$ <?= number_format($totalLiquido, 2, ',', '.') ?></span>
  </div>
</div>

<div class="aviso">
  ⚠️ <strong>Documento fictício gerado para fins de demonstração.</strong>
  Os valores apresentados são simulados e não representam remuneração real.
</div>

<div class="footer">
  <span>ORBE · Sistema de Gestão de RH · PRF</span>
  <span>Gerado em <?= (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('d/m/Y H:i') ?></span>
</div>

</body>
</html>