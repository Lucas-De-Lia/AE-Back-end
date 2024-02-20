<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PdfFile;
use App\Models\Image;
use App\Models\News;
use App\Models\Question;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image as ImageIntervention;
use Intervention\Image\Decoders\FilePathImageDecoder;
use Intervention\Image\Decoders\Base64ImageDecoder;

class GuestController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:api');
    }
    /*
    public function getPdf(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        try {
            $pdfDocument = PdfDocument::findOrFail($request->id);
            return response()->json($pdfDocument, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'PDF not found',
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPdfList()
    {
        $pdfDocuments = PdfDocument::all();

        if ($pdfDocuments->isEmpty()) {
            return response()->json([
                'message' => 'No PDFs found',
            ], Response::HTTP_NOT_FOUND);
        }

        $pdfList = $pdfDocuments->map(function ($pdfDocument) {
            $imgPath = 'public/images/'. str_replace(' ', '', $pdfDocument->title). '.wepb';
            $thumbnailPath = 'public/images/'. str_replace(' ', '', $pdfDocument->title). '-thubpmbnail.webp';

            if (!Storage::exists($imgPath)) {
                $imageData = base64_decode($pdfDocument->img);
                Storage::put($imgPath, $imageData);
                GuestController::createLowResImage($pdfDocument->img, $thumbnailPath, 50, 400);
            }
            $imgUrl = Storage::url($imgPath);
            $thumbnailUrl = Storage::url($thumbnailPath);
            return [
                'id' => $pdfDocument->id,
                'title' => $pdfDocument->title,
                'abstract' => $pdfDocument->abstract,
                'img' => $imgUrl,
                'thumbnail'=> $thumbnailUrl
            ];
        })->all();

        return response()->json($pdfList, Response::HTTP_OK);
    }

    public function createLowResImage($sourceImagePath, $destinationImagePath, $quality = 50, $width = 100, $height = null)
    {
        $image = Image::read($sourceImagePath,[Base64ImageDecoder::class, FilePathImageDecoder::class]);
        $image->scaleDown(width: $width);
        //$image->save($destinationImagePath, $quality);
        Storage::put($destinationImagePath, $image->encode());
    }
    */
    public function uploadPdfDocument(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'abstract' => 'required|string',
            'pdf' => 'required|mimes:pdf|max:2048', // Validar que el archivo sea un PDF válido
            'image' => 'required|image|mimes:webp|max:2048', // Validar que el archivo sea una imagen válida
        ]);
        //return response()->json(['message' => 'PDF uploaded successfully'], Response::HTTP_OK);

        DB::beginTransaction();
        try{
            // Create a new News
            $news = new News();
            $news->title = $request->title;
            $news->abstract = $request->abstract;
            $news->save();

            $image= $request->file('image');
            $imagenPath = $image->store('images');
            $imageModel = Image::create(['url' => $imagenPath]);
            $news->imagen()->save($imageModel);

            $pdfFile = $request->file('pdf');
            $pdfPath= $pdfFile->store('pdfs');
            $pdfModel = PdfFile::create(['file_path' => $pdfPath]);
            $pdf->pdf()->save($pdfModel);

            $news->save();
            DB::commit();
            return response()->json(['message' => 'PDF uploaded successfully'], Response::HTTP_OK);

        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
    public function getQuestionList()
    {
        $questions = Question::all();

        return $questions->isEmpty()
            ? response()->json(['message' => 'No questions found'], Response::HTTP_NOT_FOUND)
            : response()->json($questions, Response::HTTP_OK);
    }
}
