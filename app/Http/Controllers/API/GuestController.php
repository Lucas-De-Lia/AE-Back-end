<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PdfDocument;
use App\Models\Question;

class GuestController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:api');
    }
    public function getPdf(Request $request)
    {
        $request->validate([
            'pdfId' => 'required|integer',
        ]);
        $pdfDocument = PdfDocument::find($request->pdfId);

        // Verificar si se encontró el documento
        if ($pdfDocument) {
            return  response()->json([
                'title' => $pdfDocument->title,
                'abstract' => $pdfDocument->abstract,
                'img' => $pdfDocument->img,
                'content' => $pdfDocument->content,
            ]);
        } else {
            return response()->json([
                'message' => 'PDF not found',
            ], 404);
        }
    }

    public function getPdfList(Request $request)
    {
        $pdfDocuments = PdfDocument::all();

        // Verificar si se encontraron documentos
        if ($pdfDocuments->count() > 0) {
            $pdfList = [];

            foreach ($pdfDocuments as $pdfDocument) {
                $pdfList[] = [
                    'id' => $pdfDocument->id,
                    'title' => $pdfDocument->title,
                    'abstract' => $pdfDocument->abstract,
                    'img' => $pdfDocument->img
                ];
            }

            return response()->json($pdfList);
        } else {
            return response()->json([
                'message' => 'No PDFs found',
            ], 404);
        }
    }
    public function publishPDF(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'abstract' => 'required|string',
            'img' => 'required', // Crear regla personalizada para validar imágenes en base64
            'content' => 'required',
        ]);
        $pdf = PdfDocument::create([
            'title' => $request->input('title'),
            'abstract' => $request->input('abstract'),
            'img' => $request->input('img'),
            'content' => $request->input('content'),
        ]);
        return response()->json([
            'id' => $pdf->id,
            'msg' => 'Pdf saved',
        ]);
    }

    function getQuestions(Request $request)
    {
        return response()->json(['questions'  => Question::all()]);
    }
}
