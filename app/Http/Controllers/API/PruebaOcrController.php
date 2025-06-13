<?php 
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

//! CONTROLLER DE PRUEBA NO DEFINITIVO
class PruebaOcrController extends Controller{
    public function checkLegibility(Request $request){
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]); 

        $image = $request->file('image');
        $path = $image->store('temp');
        $fullPath = storage_path('app/' . $path);
        try {
            $ocr = new TesseractOCR($fullPath);
            $text = $ocr->run();
            Log::info($text);
            // Retornamos siempre el texto que encontró
            return response()->json([
                'success' => strlen(trim($text)) >= 20, // true si encontró texto "suficiente"
                'message' => strlen(trim($text)) >= 20 
                    ? 'Imagen legible' 
                    : 'Texto insuficiente, la imagen podría ser borrosa o con poca luz.',
                'text_detected' => $text,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la imagen.',
                'error' => $e->getMessage(),
                'text_detected' => '',
            ], 500);
        } finally {
            Storage::delete($path);
        }
    }
} 

// PRUBA IMAGEN pruebaDNI.jpg (imagen de la mejor calidad) -> Characters with spaces 237
//RECUPERA NOMBRE, APELLIDO, TEXTO ARRIBA DEL DNI, NACIONALIDAD, FIRMA, MITAD DEL DNI

//pruebaDNI2 -> no recupera practicamente nada y se ve bien.

//pruebaDNI3 -> 

//pruebaDNIBorrosa -> No info

//pruebaDNIintermedia -> recupera Characters with spaces 16

//pruebaDNIintermedia2 -> recupera Characters with spaces 201

//! TESSERACT ES MUY IRREGULAR
//? Intentar con python y herramientas para verificación de calidad como varianza,brillo, buscar texto con expresiones regulares y generar un sistema de scoring