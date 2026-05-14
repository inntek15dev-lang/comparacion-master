<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentosFisicosController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max por archivo
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            
            // Guardar en la carpeta de ingesta
            $path = $file->storeAs('importar_documentos_fisicos', $originalName, 'public');
            
            return response()->json(['success' => $path]);
        }

        return response()->json(['error' => 'No se subió ningún archivo'], 400);
    }
}
