<?php

namespace App\Models;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;

class Lancamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'data',
        'tipo',
        'categoria',
        'valor',
        'descricao',
        'observacao',
        'user_id',
        'benfeitor_id',
        'classificado',
        'is_historico',
        'historico_bancario',
        'documento',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'tipo' => TipoLancamentoEnum::class,
            'categoria' => CategoriaLancamentoEnum::class,
            'valor' => 'decimal:2',
            'classificado' => 'boolean',
            'is_historico' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function benfeitor(): BelongsTo
    {
        return $this->belongsTo(Benfeitor::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(LancamentoAnexo::class)->orderBy('ordem')->orderBy('id');
    }

    /**
     * @param  iterable<int, mixed>  $arquivos
     */
    public function anexarArquivos(iterable $arquivos): void
    {
        $ordem = (int) $this->anexos()->max('ordem');

        foreach ($arquivos as $arquivo) {
            if (! $arquivo instanceof UploadedFile) {
                continue;
            }

            $ordem++;
            $this->anexos()->create([
                'path' => $arquivo->store('lancamentos', 'local'),
                'nome_original' => $arquivo->getClientOriginalName(),
                'ordem' => $ordem,
            ]);
        }
    }

    /**
     * Relação legado — entradas da conta antiga. Não usar em lançamentos novos.
     */
    public function segmentos(): BelongsToMany
    {
        return $this->belongsToMany(Segmento::class, 'lancamento_segmento')->withTimestamps();
    }

    public function scopeContaAtual(Builder $query): Builder
    {
        return $query->where('is_historico', 0);
    }

    public function scopeHistorico(Builder $query): Builder
    {
        return $query->where('is_historico', 1);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('classificado', false)->where('is_historico', false);
    }
}
