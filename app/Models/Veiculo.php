<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Veiculo extends Model
{
    use HasFactory;

    protected $table = 'veiculos';

    public const COMBUSTIVEIS = [
        'gasolina' => 'Gasolina',
        'alcool' => 'Álcool',
        'flex' => 'Flex',
        'diesel' => 'Diesel',
        'eletrico' => 'Elétrico',
        'hibrido' => 'Híbrido',
        'gnv' => 'GNV',
    ];

    protected $fillable = [
        'processo_id',
        'placa',
        'marca',
        'modelo',
        'ano_fabricacao',
        'ano_modelo',
        'cor',
        'chassi',
        'renavam',
        'combustivel',
        'quilometragem',
        'valor_fipe',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'ano_fabricacao' => 'integer',
            'ano_modelo' => 'integer',
            'quilometragem' => 'integer',
            'valor_fipe' => 'decimal:2',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function combustivelLabel(): string
    {
        return self::COMBUSTIVEIS[$this->combustivel] ?? '—';
    }

    public function descricaoCurta(): string
    {
        $parts = array_filter([
            $this->marca,
            $this->modelo,
            $this->ano_modelo ? '(' . $this->ano_modelo . ')' : null,
        ]);
        return trim(implode(' ', $parts)) ?: '—';
    }
}
