<!DOCTYPE html>
<html lang='pt-BR'>
<head>
<meta charset='UTF-8'>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; background: #fff; }

  /* ── Cabeçalho ── */
  .header {
    display:flex; align-items:center; justify-content:space-between;
    padding: 18px 24px; background: #1e3a5f; color: #fff;
  }
  .header-left        { display:flex; align-items:center; gap:14px; }
  .header-title       { font-size:14pt; font-weight:700; margin-bottom:2px; }
  .header-sub         { font-size:8pt; opacity:.8; }
  .header-right       { text-align:right; font-size:8pt; opacity:.85; }

  /* ── Meta (total de registros) ── */
  .meta {
    padding: 14px 24px; border-bottom: 2px solid #e2e8f0;
    display:flex; justify-content:space-between; align-items:center;
  }
  .meta-total         { font-size:11pt; font-weight:700; color:#1e3a5f; }
  .meta-total span    { font-size:8pt; font-weight:400; color:#64748b; margin-left:6px; }

  /* ── Resumo de filtros ── */
  .resumo-filtros {
    padding: 10px 24px; background:#f1f5f9;
    border-bottom:1px solid #e2e8f0; font-size:8pt; color:#475569;
  }

  /* ── Tabela ── */
  table               { width:100%; border-collapse:collapse; }
  thead th {
    background:#1e3a5f; color:#fff; padding:8px 10px;
    text-align:left; font-size:8pt; font-weight:600;
  }
  tbody td {
    padding:7px 10px; font-size:8pt;
    border-bottom:1px solid #e2e8f0; vertical-align:top;
  }
  tbody tr:last-child td { border-bottom:none; }

  /* ── Badges de situação ── */
  .badge {
    display:inline-block; padding:2px 7px; border-radius:999px;
    font-size:7pt; font-weight:600;
  }
  .badge-ativo        { background:#dcfce7; color:#15803d; }
  .badge-afastado     { background:#fef9c3; color:#a16207; }
  .badge-aposentado   { background:#e0e7ff; color:#4338ca; }
  .badge-licenca      { background:#fce7f3; color:#be185d; }
  .badge-desligado    { background:#fee2e2; color:#dc2626; }
  .badge-outros       { background:#f1f5f9; color:#475569; }

  /* ── Competências ── */
  .comp-lista         { list-style:none; padding:0; margin:0; }
  .comp-lista li      { margin-bottom:2px; }
  .comp-tag {
    display:inline-block; background:#eff6ff; color:#1d4ed8;
    border-radius:4px; padding:1px 5px; font-size:7pt;
  }

  /* ── Rodapé fixo ── */
  .footer {
    position:fixed; bottom:0; left:0; right:0;
    padding:8px 24px; background:#f8fafc;
    border-top:1px solid #e2e8f0; font-size:7pt; color:#94a3b8;
    display:flex; justify-content:space-between;
  }
</style>
</head>
<body>

  <div class="header">
    <div class="header-left">
      <?= $logoHtml ?>
      <div>
        <div class="header-title">Relatório de Colaboradores</div>
        <div class="header-sub">Sistema ORBE · Polícia Rodoviária Federal</div>
      </div>
    </div>
    <div class="header-right">
      Gerado em<br><strong><?= $dataGeracao ?></strong>
    </div>
  </div>

  <div class="meta">
    <div class="meta-total">
      <?= $totalRegistros ?> colaborador(es) encontrado(s)
      <span>com base nos filtros selecionados</span>
    </div>
  </div>

  <?= $resumoHtml ?>

  <table>
    <thead><tr><?= $thCols ?></tr></thead>
    <tbody><?= $trRows ?></tbody>
  </table>

  <div class="footer">
    <span>ORBE · Sistema de Gestão de RH · PRF</span>
    <span>Documento gerado em <?= $dataGeracao ?></span>
  </div>

</body>
</html>