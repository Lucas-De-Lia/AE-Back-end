<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
//use Spatie\Image\Image;
use Illuminate\Support\Facades\Http;
use mikehaertl\pdftk\Pdf;

class GuestController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:api');
    }
    // Sube una noticia nueva
    public function uploadPdfDocument(Request $request){
        // Valido los datos entrantes
        $request->validate([
            'title' => 'required|string',
            'abstract' => 'required|string',
            'pdf' => 'required|mimes:pdf|max:2048',
            'image' => 'required|image|max:2048',
        ]);
        DB::beginTransaction();
        try {
            $news = News::create([
                'title' => $request->title,
                'abstract' => $request->abstract,
            ]);
            $imagePath = $this->uploadFileImage( $request->file('image')); //suvolafoto
            $news->image()->create(['url' => str_replace('public/', 'storage/', $imagePath)]); // creo el "objeto" imagen
            $pdf = $request->file('pdf')->store('public/pdfs'); //guardo el pdf
            $news->pdfFile()->create([ //creo el objeto pdf
                'title' => $request->title,
                'file_path' => str_replace('public/', 'storage/', $pdf)
            ]);
            DB::commit();
            return response()->json([
            'id' => $news->id,
            'title' => $news->title,
            'abstract' => $news->abstract,
            'imagen' => str_replace('public/', 'storage/', $imagePath),  // URL accesible desde el navegador
            'pdf' => str_replace('public/', 'storage/', $pdf),
        ], 200);
        } catch (\Exception $e) {
            // Roll back the transaction and return an error response
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // Sube la imagen 
    private function uploadFileImage($image){
        if (!Storage::exists('public/images')) {
            Storage::makeDirectory('public/images'); // verifica la exitencia de la carpeta
        }
        $imagePath = 'public/images/' .uniqid() . '_' . time() . '.' .  $image->getClientOriginalName(); // Genera un nombre unico
        $imageM =Image::read($image); // carga la imagen
        $imageM->resize(1920, 1080)->toWebp(100)->save(storage_path('app/' . $imagePath)); // modifica la imagen 
        return $imagePath;
    }
    // sube laimagen
    private function updateFileImagen($image, $news){
        if (!Storage::exists('public/pdfs')) {
            Storage::makeDirectory('public/pdfs'); // verifica la exitencia de la carpeta
        }
        $filePathImage =  $news->image->url;
        unlink($filePathImage);
        $news->image()->delete();
        $imagePath = $this->uploadFileImage($image);
        $news->image()->create(['url' => str_replace('public/', 'storage/', $imagePath)]);
        return $news;
    }
    // remove una noticia
    public function removePdfDocument(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        try {
            //Busco la noticia
            $news = News::findOrFail($request->id);
            if(!$news){
                // No existe
                return response()->json(['message' => 'News not found'], Response::HTTP_NOT_FOUND);
            }
            // Los paths
            $filePathPDF =  $news->pdfFile->file_path;
            $filePathImage =  $news->image->url;
            $pdfExist= !$news->pdfFile || !file_exists(storage_path('app/' . str_replace('storage/', 'public/', $news->pdfFile->file_path)));
            $imageExist = !$news->image || !file_exists(storage_path('app/' . str_replace('storage/', 'public/', $news->image->url)));
            if ($pdfExist || $imageExist) {
                return response()->json(['error' => 'File not found.'], 404);
            }
            // Borra los archivos
            unlink($filePathPDF);
            unlink($filePathImage);
            // Borra los objetos de los archivos
            $news->pdfFile()->delete();
            $news->image()->delete();
            $news->delete();     
            return response()->json([ 'message' => 'Remove success'], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'PDF not found',
            ], Response::HTTP_NOT_FOUND);
        }
    }
    
    public function getQuestionList(){
        $questions = Question::all();
        if($questions->isEmpty()){
            return response()->json(['message' => 'No questions found']);
        }
        $questions->transform(function ($question) {
            $question->answers = json_decode($question->answers);
            return $question;
        });
        return response()->json($questions, Response::HTTP_OK);
    }

    public function getNewsList(Request $request){
        // Retrieve all news articles and map them to a new format
        // Valores de orden y paginado defaults
        $sort_by = ['colum' => 'id', 'order' => 'ASC'];
        $page_size = 10;
        //Si envie datos concretos de paginado los seteo
        if(!empty($request->sort_by)){
          $sort_by = $request->sort_by;
        }
        if(!empty($request->page_size)){
            $page_size = $request->page_size;
        }
        $reglas = Array();
        $filters = [
            'title' => 'news.title',
            'abstract' => 'news.abstract',
            'start_date' => 'news.created_at',
            'end_date' => 'news.created_at',
          ];
          //echo asi por las dudas si necesito agregar mas tipos de filtros y evitar tanto los if agrupando diferentes tipos de filtros en uno
        foreach($filters as $key => $column){
            if (!empty($request->$key)) {
                switch($key){
                    case 'title':
                    case 'abstract':
                        $searchTerm = strtolower($request->$key);
                        $reglas[] = [DB::raw("LOWER($column)"), 'LIKE', "%$searchTerm%"];
                        break;
                    case 'start_date':
                        $reglas[] = [$column, '>=', $request->$key];
                        break;
                    case 'end_date':
                        $reglas[] = [$column, '<=', $request->$key];
                        break;
                }
            }
        }
        // Realizo la busqueda
        $newsList = DB::table("news") // De las noticias
            ->orderBy($sort_by['colum'],$sort_by['order']) // con el orden indicado
            ->join("images", "news.id", "=", "images.news_id") // quiero vincularla con su imagen
            ->join("pdf_files", "news.id", "=", "pdf_files.news_id") //quieor vincularla con su pdf
            ->select('news.*', 'images.url', 'pdf_files.file_path')
            ->where($reglas);
        return response()->json($newsList->paginate($page_size), Response::HTTP_OK); //pagino y luego retorno el valor.
    }

    public function getNewsPdf(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        try {
            // Busco la noticia
            $news = News::findOrFail($request->id);
            $filePath = storage_path('app/public/' . $news->pdfFile->file_path);
            if (!$news->pdfFile || !file_exists(storage_path('app/' . str_replace('storage/', 'public/', $news->pdfFile->file_path)))) {
                // Si no existe el archivo
                return response()->json(['error' => 'File not found.'], 404);
            }
            return response()->json([
                'id' => $news->id,
                'title' => $news->title,
                'abstract' => $news->abstract,
                'imagen' => asset($news->image->url),
                'pdf' => asset($news->pdfFile->file_path),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'PDF not found',
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function editNews(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        try {
            // Obtiene la noticia
            $news = News::findOrFail($request->id);
            if(!$news){
                response()->json(['message' => 'News not found'], Response::HTTP_NOT_FOUND);
            }
            //Actualizo los campos si son enviados en la request
            if($request->title){
                $news->title = $request->title;
            }
            if($request->abstract){
                $news->abstract = $request->abstract;
            }
            if($request->image){
                $news = $this->updateFileImagen($request->file('image'), $news);
            }
            if($request->pdf){
                $filePathPDF =  $news->pdfFile->file_path;
                unlink($filePathPDF);
                $news->pdfFile()->delete();
                $pdf = $request->file('pdf')->store('public/pdfs');
                $news->pdfFile()->create([
                    'title' => $request->file('pdf')->getClientOriginalName(),
                    'file_path' => str_replace('public/', 'storage/', $pdf)
                ]);
            }
            // Guardo
            $news->save();
            return response()->json(['message' => 'News updated'], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'News not found',
            ], Response::HTTP_NOT_FOUND);
        }
    }
    
    public function uploadQuestions(Request $request){
        $request->validate(["title" => "required|string", "bodyQ"=> "required|string"]);
        $newBody = explode("\n", $request->bodyQ);
        $newBody = array_map('trim', $newBody);
        $newBody = array_values(array_filter($newBody));
        $asList = json_encode($ewBody);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json('Error decoding JSON response: ' . json_last_error_msg(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $question = Question::create([
            'question'=> $request->title, 'answers' => $asList
        ]);
    }
    // Verifica que el token del captcha es correcto
    public function verifyCaptcha(Request $request){
        $token = $request->token;
        $response = Http::withUrlParameters([
            'SITE_SECRET' => env("CAPTCHA_SECRET_KEY"),
            'captchaValue' =>  $token,
        ])->post('https://www.google.com/recaptcha/api/siteverify?secret={SITE_SECRET}&response={captchaValue}');
        if ($response->successful()) {
            $data = $response->json();
        } else {
            Log::error("Error al hacer la solicitud HTTP: " . $response);
            throw new Exception("Error al obtener los datos: " . $response->status());
        }
        return response()->json($data, Response::HTTP_OK);
    }
}