<?php

namespace App\Http\Controllers\Licenciado;

use App\Http\Controllers\Controller;
use App\Models\Comissao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function datatable(Request $request): JsonResponse
    {
        $query = Comissao::query()
            ->with('cliente:id,nome')
            ->where('licensed_by_user_id', Auth::id());

        if ($s = $request->query('status')) {
            $query->where('status', $s);
        }
        if ($t = $request->query('tipo')) {
            $query->where('tipo', $t);
        }
        if ($de = $request->query('data_de')) {
            $query->whereDate('data_referencia', '>=', $de);
        }
        if ($ate = $request->query('data_ate')) {
            $query->whereDate('data_referencia', '<=', $ate);
        }

        return DataTables::eloquent($query)
            ->addColumn('cliente_nome', fn (Comissao $c) => $c->cliente?->nome ?? '—')
            ->addColumn('valor_formatado', fn (Comissao $c) => 'R$ ' . number_format((float) $c->valor, 2, ',', '.'))
            ->addColumn('data_formatada', fn (Comissao $c) => $c->data_referencia->format('d/m/Y'))
            ->addColumn('tipo_badge', fn (Comissao $c) =>
                '<span class="badge bg-label-' . $c->tipoColor() . '">' . $c->tipoLabel() . '</span>')
            ->addColumn('status_badge', function (Comissao $c) {
                $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$c->status] ?? 'secondary') . '">' . ucfirst($c->status) . '</span>';
            })
            ->rawColumns(['status_badge', 'tipo_badge'])
            ->toJson();
    }
}
