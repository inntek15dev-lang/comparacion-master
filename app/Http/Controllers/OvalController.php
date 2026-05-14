<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Mandante;

class OvalController extends Controller
{
    public function login()
    {
        $user = Auth::user();

        if (!$user || !$user->isContratista()) {
            return redirect()->back()->with('error', 'Acceso no autorizado.');
        }

        $contratista = $user->contratista;

        if (!$contratista) {
            return redirect()->back()->with('error', 'No se encontró información del contratista.');
        }

        // Buscar si el contratista tiene algún mandante aprobado con OVAL habilitado
        $mandanteOval = $contratista->mandantesAprobados()
            ->where('tiene_oval', true)
            ->whereNotNull('oval_cod')
            ->first();

        if (!$mandanteOval) {
            return redirect()->back()->with('error', 'No tiene acceso a OVAL a través de ningún mandante activo.');
        }

        try {
            $rut = $user->rut; // Asumiendo que el RUT está en el usuario
            $idPrinc = $mandanteOval->oval_cod;

            $ovalUser = DB::connection('mysql_oval')->selectOne(
                "select id_u, random from usuarios where rut = ? and id_princ = ? and activar = 1",
                [$rut, $idPrinc]
            );

            if ($ovalUser) {
                $url = "https://www.oval.cl/oval_30/bypass_30.aspx?elid={$ovalUser->id_u}&rand={$ovalUser->random}";
                return redirect()->away($url);
            } else {
                return redirect()->back()->with('error', 'Usuario no encontrado en OVAL.');
            }

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error conectando a OVAL: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error de conexión con OVAL. Por favor intente más tarde.');
        }
    }
}
