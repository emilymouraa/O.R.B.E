<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\RelatorioModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;

class RelatorioController extends Controller
{
    private RelatorioModel $model;

    public function __construct(PDO $conn)
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor', 'user']);
        $this->model = new RelatorioModel($conn);
    }

    public function preview(): void
    {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $filtros = $body['filtros'] ?? [];

        $total = $this->model->contar($filtros);

        $this->jsonResponse([
            'success' => true,
            'total'   => $total,
        ]);
    }

    public function exportar(): void
    {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $filtros = $body['filtros']  ?? [];
        $colunas = $body['colunas']  ?? ['nome', 'cargo', 'unidade', 'situacao', 'tempo_servico'];
        $ordenar = $body['ordenar']  ?? 'nome';

        $dados = $this->model->buscarParaRelatorio($filtros, $ordenar);

        $html = $this->renderizarTemplate($dados, $colunas, $filtros);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'relatorio_orbe_' . date('Ymd_His') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        echo $dompdf->output();
        exit;
    }

    public function exportarXlsx(): void
    {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $filtros = $body['filtros']  ?? [];
        $colunas = $body['colunas']  ?? ['nome', 'cargo', 'unidade', 'situacao', 'tempo_servico'];
        $ordenar = $body['ordenar']  ?? 'nome';

        $dados = $this->model->buscarParaRelatorio($filtros, $ordenar);

        $rotulosMap = [
            'nome'          => 'Nome',
            'cargo'         => 'Cargo',
            'unidade'       => 'Unidade',
            'situacao'      => 'Situação',
            'tempo_servico' => 'Tempo de Serviço',
            'idade'         => 'Idade',
            'competencias'  => 'Competências',
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Colaboradores');

        $logoPath = realpath(__DIR__ . '/../../public/assets/images/orbe_logo.png');
        if ($logoPath && file_exists($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo ORBE');
            $drawing->setDescription('Logo ORBE');
            $drawing->setPath($logoPath);
            $drawing->setHeight(48);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }

        $col = 'A';
        foreach ($colunas as $colKey) {
            $label = $rotulosMap[$colKey] ?? ucfirst($colKey);
            $sheet->setCellValue($col . '4', $label);
            $sheet->getStyle($col . '4')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            ]);
            $col++;
        }

        $row = 5;
        foreach ($dados as $i => $s) {
            $col     = 'A';
            $bgColor = $i % 2 === 0 ? 'FFFFFFFF' : 'FFF8FAFC';

            foreach ($colunas as $colKey) {
                $valor = $this->valorColunaTexto($colKey, $s);
                $sheet->setCellValue($col . $row, $valor);
                $sheet->getStyle($col . $row)->applyFromArray([
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => $bgColor]],
                    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                                    'wrapText'  => true],
                    'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                                'color'       => ['argb' => 'FFE2E8F0']]],
                ]);
                $col++;
            }
            $row++;
        }

        $col = 'A';
        foreach ($colunas as $colKey) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // ── Altura do cabeçalho ──
        $sheet->getRowDimension(1)->setRowHeight(22);

        foreach (range(1, count($colunas)) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(22);

        $spreadsheet->getProperties()
            ->setCreator('Sistema ORBE')
            ->setTitle('Relatório de Colaboradores')
            ->setDescription('Gerado em ' . date('d/m/Y H:i'));

        $filename = 'relatorio_orbe_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function gerarHolerite(): void
    {
        $servidorId   = isset($_GET['servidor_id']) ? (int) $_GET['servidor_id'] : 0;
        $userSession  = $_SESSION['user'];
        $role         = $userSession['role'] ?? '';
        $meuServidorId = (int) ($userSession['servidor_id'] ?? 0);
        $unidadeId     = (int) ($userSession['unidade_id']  ?? 0);

        if ($servidorId <= 0) {
            $this->jsonResponse(['error' => 'ID inválido.'], 400);
        }

        $model    = new \App\Models\ServidorPerfilModel($this->model->getDb());
        $servidor = $model->findPerfilById($servidorId);
        if (!$servidor) {
            $this->jsonResponse(['error' => 'Servidor não encontrado.'], 404);
        }

        // Usuário só pode baixar o próprio
        if ($role === 'user' && $servidorId !== $meuServidorId) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
        }

        // Gestor só pode baixar os da sua unidade
        if ($role === 'gestor' && (int)($servidor['unidade_id'] ?? 0) !== $unidadeId) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
        }

        $logoPath   = realpath(__DIR__ . '/../../public/assets/images/orbe_logo.png');
        $logoBase64 = '';
        if ($logoPath && file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        $logoHtml = $logoBase64
            ? "<img src=\"{$logoBase64}\" style=\"height:48px\">"
            : "<span style=\"font-size:1.4rem;font-weight:700;color:#fff\">ORBE</span>";

        ob_start();
        include __DIR__ . '/../Views/relatorios/holerite-pdf.php';
        $html = ob_get_clean();

        $this->gerarPdf($html, 'holerite_' . $servidorId . '_' . date('Ymd') . '.pdf', 'portrait');
    }

    public function gerarEspelho(): void
    {
        $servidorId   = isset($_GET['servidor_id']) ? (int) $_GET['servidor_id'] : 0;
        $userSession  = $_SESSION['user'];
        $role         = $userSession['role'] ?? '';
        $meuServidorId = (int) ($userSession['servidor_id'] ?? 0);
        $unidadeId     = (int) ($userSession['unidade_id']  ?? 0);

        if ($servidorId <= 0) {
            $this->jsonResponse(['error' => 'ID inválido.'], 400);
        }

        $model    = new \App\Models\ServidorPerfilModel($this->model->getDb());
        $servidor = $model->findPerfilById($servidorId);
        if (!$servidor) {
            $this->jsonResponse(['error' => 'Servidor não encontrado.'], 404);
        }

        if ($role === 'user' && $servidorId !== $meuServidorId) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
        }

        if ($role === 'gestor' && (int)($servidor['unidade_id'] ?? 0) !== $unidadeId) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
        }

        $logoPath   = realpath(__DIR__ . '/../../public/assets/images/orbe_logo.png');
        $logoBase64 = '';
        if ($logoPath && file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        $logoHtml = $logoBase64
            ? "<img src=\"{$logoBase64}\" style=\"height:48px\">"
            : "<span style=\"font-size:1.4rem;font-weight:700;color:#fff\">ORBE</span>";

        ob_start();
        include __DIR__ . '/../Views/relatorios/espelho-pdf.php';
        $html = ob_get_clean();

        $this->gerarPdf($html, 'espelho_ponto_' . $servidorId . '_' . date('Ymd') . '.pdf', 'landscape');
    }

    private function gerarPdf(string $html, string $filename, string $orientacao = 'portrait'): void
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientacao);
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        echo $dompdf->output();
        exit;
    }

    private function valorColunaTexto(string $col, array $s): string
    {
        return match($col) {
            'nome'          => $s['nome'] ?? '—',
            'cargo'         => ucwords(str_replace('_', ' ', $s['cargo'] ?? '—')),
            'unidade'       => ($s['unidade_sigla'] ?? '') . ' — ' . ($s['unidade_nome'] ?? ''),
            'situacao'      => ucfirst($s['situacao'] ?? '—'),
            'tempo_servico' => $this->formatarTempoServico((int)($s['anos_servico'] ?? 0)),
            'idade'         => ($s['idade'] ?? '—') . ' anos',
            'competencias'  => implode(', ', array_column($s['competencias'] ?? [], 'competencia_nome')) ?: '—',
            default         => $s[$col] ?? '—',
        };
    }

    private function renderizarTemplate(array $dados, array $colunas, array $filtros): string
    {
        $logoPath   = realpath(__DIR__ . '/../../public/assets/images/orbe_logo.png');
        $logoBase64 = '';
        if ($logoPath && file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $logoHtml = $logoBase64
            ? "<img src=\"{$logoBase64}\" style=\"height:48px\">"
            : "<span style=\"font-size:1.4rem;font-weight:700;color:#1e3a5f\">ORBE</span>";

        $dataGeracao    = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('d/m/Y H:i');
        $totalRegistros = count($dados);

        $resumoFiltros  = $this->resumirFiltros($filtros);
        $resumoHtml     = $resumoFiltros
            ? "<div class=\"resumo-filtros\"><strong>Filtros aplicados:</strong> {$resumoFiltros}</div>"
            : "<div class=\"resumo-filtros\"><em>Nenhum filtro aplicado — exibindo todos os colaboradores.</em></div>";

        $rotulosMap = [
            'nome'          => 'Nome',
            'cargo'         => 'Cargo',
            'unidade'       => 'Unidade',
            'situacao'      => 'Situação',
            'tempo_servico' => 'Tempo de Serviço',
            'idade'         => 'Idade',
            'competencias'  => 'Competências',
        ];

        $thCols = '';
        foreach ($colunas as $col) {
            $label   = $rotulosMap[$col] ?? ucfirst($col);
            $thCols .= "<th>{$label}</th>";
        }

        $trRows = '';
        foreach ($dados as $i => $s) {
            $zebra   = $i % 2 === 0 ? '' : 'background:#f8fafc;';
            $trRows .= "<tr style=\"{$zebra}\">";
            foreach ($colunas as $col) {
                $trRows .= '<td>' . $this->valorColuna($col, $s) . '</td>';
            }
            $trRows .= '</tr>';
        }

        if (empty($dados)) {
            $colspan = count($colunas);
            $trRows  = "<tr><td colspan=\"{$colspan}\" style=\"text-align:center;color:#94a3b8\">Nenhum resultado encontrado.</td></tr>";
        }

        ob_start();
        include __DIR__ . '/../Views/relatorios/relatorio-pdf.php';
        return ob_get_clean();
    }

    private function valorColuna(string $col, array $s): string
    {
        return match($col) {
            'nome'          => htmlspecialchars($s['nome'] ?? '—'),
            'cargo' => htmlspecialchars(ucwords(str_replace('_', ' ', $s['cargo'] ?? '—'))),
            'unidade'       => htmlspecialchars(($s['unidade_sigla'] ?? '') . ' — ' . ($s['unidade_nome'] ?? '')),
            'situacao'      => $this->badgeSituacao($s['situacao'] ?? ''),
            'tempo_servico' => $this->formatarTempoServico((int)($s['anos_servico'] ?? 0)),
            'idade'         => ($s['idade'] ?? '—') . ' anos',
            'competencias'  => $this->renderCompetencias($s['competencias'] ?? []),
            default         => htmlspecialchars($s[$col] ?? '—'),
        };
    }

    private function badgeSituacao(string $situacao): string
    {
        $map = [
            'ativo'              => ['cls' => 'ativo',      'label' => 'Ativo'],
            'afastado'           => ['cls' => 'afastado',   'label' => 'Afastado'],
            'aposentado'         => ['cls' => 'aposentado', 'label' => 'Aposentado'],
            'licenca_maternidade'=> ['cls' => 'licenca',    'label' => 'Licença'],
            'desligado'          => ['cls' => 'desligado',  'label' => 'Desligado'],
            'outros'             => ['cls' => 'outros',     'label' => 'Outros'],
        ];
        $cfg = $map[$situacao] ?? ['cls' => 'outros', 'label' => ucfirst($situacao)];
        return "<span class='badge badge-{$cfg['cls']}'>{$cfg['label']}</span>";
    }

    private function renderCompetencias(array $competencias): string
    {
        if (empty($competencias)) return '<span style="color:#94a3b8">—</span>';
        $items = '';
        foreach (array_slice($competencias, 0, 5) as $c) {
            $nome   = htmlspecialchars($c['competencia_nome']);
            $items .= "<li><span class='comp-tag'>{$nome}</span></li>";
        }
        $extra = count($competencias) > 5 ? '<li style="color:#94a3b8;font-size:7pt">+ ' . (count($competencias) - 5) . ' mais</li>' : '';
        return "<ul class='comp-lista'>{$items}{$extra}</ul>";
    }

    private function resumirFiltros(array $filtros): string
    {
        $partes = [];

        if (!empty($filtros['unidades']))
            $partes[] = 'Unidades: ' . implode(', ', (array) $filtros['unidades']);
        if (!empty($filtros['cargo']))
            $partes[] = 'Cargo: ' . $filtros['cargo'];
        if (!empty($filtros['role']))
            $partes[] = 'Perfil: ' . $filtros['role'];
        if (!empty($filtros['situacao']))
            $partes[] = 'Situação: ' . implode(', ', (array) $filtros['situacao']);
        if (!empty($filtros['idade_min']) || !empty($filtros['idade_max']))
            $partes[] = 'Idade: ' . ($filtros['idade_min'] ?? '0') . '–' . ($filtros['idade_max'] ?? '∞') . ' anos';
        if (!empty($filtros['servico_min']) || !empty($filtros['servico_max']))
            $partes[] = 'Tempo de serviço: ' . ($filtros['servico_min'] ?? '0') . '–' . ($filtros['servico_max'] ?? '∞') . ' anos';
        if (!empty($filtros['admissao_de']) || !empty($filtros['admissao_ate']))
            $partes[] = 'Admissão: ' . ($filtros['admissao_de'] ?? '—') . ' até ' . ($filtros['admissao_ate'] ?? '—');
        if (!empty($filtros['competencia_nome']))
            $partes[] = 'Competência: ' . $filtros['competencia_nome'];
        if (!empty($filtros['competencia_tipo']))
            $partes[] = 'Tipo de competência: ' . $filtros['competencia_tipo'];

        return implode(' · ', $partes);
    }

    private function formatarTempoServico(int $anos): string
    {
        if ($anos === 0) return 'Menos de 1 ano';
        return $anos . ' ' . ($anos === 1 ? 'ano' : 'anos');
    }
}