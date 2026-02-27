<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 24px; }
        h2 { font-size: 12px; margin-top: 16px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .total { font-weight: bold; text-align: right; }
        .valor-entrada { color: #166534; }
        .valor-saida { color: #991b1b; }
        .anexo-item { margin-bottom: 20px; page-break-inside: avoid; }
        .anexo-item img { max-width: 100%; max-height: 200px; display: block; margin-top: 4px; }
        .anexo-pdf { font-style: italic; color: #666; }
    </style>
</head>
<body>
    @if(!empty($logoBase64))
    <div style="text-align: center; margin-bottom: 16px;">
        <img src="{{ $logoBase64 }}" alt="Fraternidade O Caminho" style="max-height: 100px; max-width: 100%;" />
    </div>
    @endif
    <h1>{{ $titulo }}</h1>

    <h2>ENTRADAS</h2>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Segmento</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entradas as $lancamento)
                <tr>
                    <td>{{ $lancamento->data->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($lancamento->categoria->value) }}</td>
                    <td>{{ $lancamento->descricao }}</td>
                    <td>{{ $lancamento->segmentos->pluck('nome')->implode(', ') ?: '-' }}</td>
                    <td style="text-align: right;" class="valor-entrada">R$ {{ number_format($lancamento->valor, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhuma entrada no período.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td colspan="4">Total de Entradas</td>
                <td style="text-align: right;">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>SAÍDAS</h2>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($saidasAfetamSaldo as $lancamento)
                <tr>
                    <td>{{ $lancamento->data->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($lancamento->categoria->value) }}</td>
                    <td>{{ $lancamento->descricao }}</td>
                    <td style="text-align: right;" class="valor-saida">R$ {{ number_format($lancamento->valor, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Nenhuma saída no período.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td colspan="3">Total de Saídas</td>
                <td style="text-align: right;">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if($reembolsos->isNotEmpty())
    <h2>REEMBOLSOS (para fins de controle)</h2>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reembolsos as $lancamento)
                <tr>
                    <td>{{ $lancamento->data->format('d/m/Y') }}</td>
                    <td>{{ $lancamento->descricao }}</td>
                    <td style="text-align: right;" class="valor-saida">R$ {{ number_format($lancamento->valor, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="2">Total de Reembolsos</td>
                <td style="text-align: right;">R$ {{ number_format($totalReembolsos, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <table style="width: 300px; margin-left: auto;">
        <tr>
            <td>Saldo Anterior:</td>
            <td style="text-align: right;">R$ {{ number_format($saldoAnterior, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Saldo Final:</strong></td>
            <td style="text-align: right; font-weight: bold;">R$ {{ number_format($saldoFinal, 2, ',', '.') }}</td>
        </tr>
    </table>

    @php
        $lancamentosComAnexo = $entradas->concat($saidasAfetamSaldo)->concat($reembolsos)->filter(fn ($l) => $l->anexo_path)->sortBy('data');
    @endphp
    @if($lancamentosComAnexo->isNotEmpty())
        <h2 style="margin-top: 32px;">COMPROVANTES / ANEXOS</h2>
        @foreach($lancamentosComAnexo as $lancamento)
            @php
                $caminhoCompleto = \Illuminate\Support\Facades\Storage::disk('local')->path($lancamento->anexo_path);
                $ext = strtolower(pathinfo($lancamento->anexo_path, PATHINFO_EXTENSION));
                $ehImagem = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                $imgBase64 = null;
                if ($ehImagem && file_exists($caminhoCompleto)) {
                    $mime = match($ext) { 'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', default => 'image/jpeg' };
                    $imgBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($caminhoCompleto));
                }
            @endphp
            <div class="anexo-item">
                <strong>{{ $lancamento->data->format('d/m/Y') }}</strong> – {{ $lancamento->descricao }}
                ({{ ucfirst($lancamento->categoria->value) }}) – R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                @if($imgBase64)
                    <img src="{{ $imgBase64 }}" alt="Comprovante {{ $lancamento->descricao }}">
                @elseif($ext === 'pdf')
                    <div class="anexo-pdf">Documento PDF incorporado ao final deste relatório.</div>
                @endif
            </div>
        @endforeach
    @endif
</body>
</html>
