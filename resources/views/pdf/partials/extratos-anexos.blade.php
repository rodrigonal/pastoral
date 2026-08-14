@php
    $extratos = $extratos ?? collect();
@endphp
@if($extratos->isNotEmpty())
    <h2 style="margin-top: 32px;">EXTRATO BANCÁRIO</h2>
    <p class="anexo-pdf">O PDF do extrato correspondente ao período está incorporado ao final deste relatório (arquivo versionado no projeto).</p>
    <table>
        <thead>
            <tr>
                <th>Documento</th>
                <th>Período</th>
            </tr>
        </thead>
        <tbody>
            @foreach($extratos as $extrato)
                <tr>
                    <td>{{ $extrato['titulo'] }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($extrato['inicio'])->format('d/m/Y') }}
                        a
                        {{ \Carbon\Carbon::parse($extrato['fim'])->format('d/m/Y') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
