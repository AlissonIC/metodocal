<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxy pra APIs públicas gratuitas — usada no cadastro de processos
 * para autocompletar marca/modelo de veículo e endereço.
 *
 * FIPE: Parallelum (https://deividfortuna.github.io/fipe) — o proxy da BrasilAPI
 *       tem apresentado 500 upstream, então bate direto no Parallelum.
 * CEP:  BrasilAPI (https://brasilapi.com.br/docs) — funciona bem.
 *
 * O proxy existe pra: evitar CORS no frontend, permitir cache agressivo e
 * concentrar timeouts/tratamento de erro num só lugar.
 */
class BrasilApiController extends Controller
{
    private const BRASIL_API = 'https://brasilapi.com.br/api';
    private const FIPE_API = 'https://parallelum.com.br/fipe/api/v1';
    private const TIMEOUT = 6; // segundos

    /**
     * Lista marcas FIPE para um tipo de veículo.
     * Tipo: carros | motos | caminhoes
     */
    public function marcasFipe(string $tipo): JsonResponse
    {
        if (! in_array($tipo, ['carros', 'motos', 'caminhoes'], true)) {
            return response()->json(['error' => 'tipo inválido'], 400);
        }

        $data = Cache::remember("fipe:marcas:{$tipo}", now()->addDays(7), function () use ($tipo) {
            try {
                $r = Http::timeout(self::TIMEOUT)->get(self::FIPE_API . "/{$tipo}/marcas");
                return $r->successful() ? $r->json() : [];
            } catch (\Throwable) {
                return [];
            }
        });

        return response()->json($data);
    }

    /**
     * Lista modelos FIPE de uma marca dentro de um tipo.
     * O Parallelum retorna { modelos: [...], anos: [...] } — normalizamos pra array simples.
     */
    public function modelosFipe(string $tipo, string $codigoMarca): JsonResponse
    {
        if (! in_array($tipo, ['carros', 'motos', 'caminhoes'], true)) {
            return response()->json(['error' => 'tipo inválido'], 400);
        }

        $data = Cache::remember("fipe:modelos:{$tipo}:{$codigoMarca}", now()->addDays(7), function () use ($tipo, $codigoMarca) {
            try {
                $r = Http::timeout(self::TIMEOUT)->get(self::FIPE_API . "/{$tipo}/marcas/{$codigoMarca}/modelos");
                if (! $r->successful()) return [];
                $body = $r->json();
                return $body['modelos'] ?? [];
            } catch (\Throwable) {
                return [];
            }
        });

        return response()->json($data);
    }

    /**
     * Consulta CEP (v2 tem cobertura melhor).
     * Retorna: { cep, state, city, neighborhood, street, service }
     */
    public function cep(string $cep): JsonResponse
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) {
            return response()->json(['error' => 'CEP inválido'], 400);
        }

        $data = Cache::remember("cep:{$cep}", now()->addDays(30), function () use ($cep) {
            try {
                $r = Http::timeout(self::TIMEOUT)->get(self::BRASIL_API . "/cep/v2/{$cep}");
                return $r->successful() ? $r->json() : null;
            } catch (\Throwable) {
                return null;
            }
        });

        if (! $data) {
            return response()->json(['error' => 'CEP não encontrado'], 404);
        }

        return response()->json($data);
    }
}
