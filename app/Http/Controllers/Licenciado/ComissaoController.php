<?php

namespace App\Http\Controllers\Licenciado;

use App\Http\Controllers\Controller;
use App\Models\Comissao;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ComissaoController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        return view('content.licenciado.comissoes', [
            'total_recebido' => (float) Comissao::where('licensed_by_user_id', $userId)->where('status', 'paga')->sum('valor'),
            'total_pendente' => (float) Comissao::where('licensed_by_user_id', $userId)->where('status', 'pendente')->sum('valor'),
            'qtd_total' => Comissao::where('licensed_by_user_id', $userId)->count(),
        ]);
    }

    public function datatable(): JsonResponse
    {
        $query = Comissao::query()
            ->with('cliente:id,nome')
            ->where('licensed_by_user_id', Auth::id());

        return DataTables::eloquent($query)
            ->addColumn('cliente_nome', fn (Comissao $c) => $c->cliente?->nome ?? '—')
            ->addColumn('valor_formatado', fn (Comissao $c) => 'R$ ' . number_format((float) $c->valor, 2, ',', '.'))
            ->addColumn('data_formatada', fn (Comissao $c) => $c->data_referencia->format('d/m/Y'))
            ->addColumn('status_badge', function (Comissao $c) {
                $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$c->status] ?? 'secondary') . '">' . ucfirst($c->status) . '</span>';
            })
            ->rawColumns(['status_badge'])
            ->toJson();
    }
}
