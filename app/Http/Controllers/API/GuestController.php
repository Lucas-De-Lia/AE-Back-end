<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Question;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
//use Spatie\Image\Image;
use mikehaertl\pdftk\Pdf;

class GuestController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:api');
    }

    public function uploadPdfDocument(Request $request){
        // Validate the incoming request data
        
        $request->validate([
            'title' => 'required|string',
            'abstract' => 'required|string',
            'pdf' => 'required|mimes:pdf|max:2048', // Validate that the file is a valid PDF
            'image' => 'required|image|max:2048', // Validate that the file is a valid image
        ]);
        // Start a database transaction
        DB::beginTransaction();
        try {
            // Create a new news entry with the provided title and abstract
            $news = News::create([
                'title' => $request->title,
                'abstract' => $request->abstract,
            ]);

            $imagePath = $this->uploadFileImage( $request->file('image'));
            $news->image()->create(['url' => str_replace('public/', 'storage/', $imagePath)]);
            $pdf = $request->file('pdf')->store('public/pdfs');

            $news->pdfFile()->create([
                'title' => $request->title,
                'file_path' => str_replace('public/', 'storage/', $pdf)
            ]);
            // Commit the transaction and return a success response
            DB::commit();
            return response()->json(['message' => 'PDF uploaded successfully'], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Roll back the transaction and return an error response
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function uploadFileImage($image){
        if (!Storage::exists('public/images')) {
            Storage::makeDirectory('public/images');
        }
        $imagePath = 'public/images/' .uniqid() . '_' . time() . '.' .  $image->getClientOriginalName();
        $imageM =Image::read($image);
        $imageM->resize(1920, 1080)->toWebp(100)->save(storage_path('app/' . $imagePath));
        return $imagePath;
    }

    private function updateFileImagen($image, $news){
        $filePathImage =  $news->image->url;
        unlink($filePathImage);
        $news->image()->delete();
        $imagePath = $this->uploadFileImage($image);
        $news->image()->create(['url' => str_replace('public/', 'storage/', $imagePath)]);
        return $news;
    }

    public function removePdfDocument(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        try {
            $news = News::findOrFail($request->id);
            if(!$news){
                return response()->json(['message' => 'News not found'], Response::HTTP_NOT_FOUND);
            }
            $filePathPDF =  $news->pdfFile->file_path;
            $filePathImage =  $news->image->url;
            $pdfExist= !$news->pdfFile || !file_exists(storage_path('app/' . str_replace('storage/', 'public/', $news->pdfFile->file_path)));
            $imageExist = !$news->image || !file_exists(storage_path('app/' . str_replace('storage/', 'public/', $news->image->url)));
            if ($pdfExist || $imageExist) {
                return response()->json(['error' => 'File not found.'], 404);
            }
            unlink($filePathPDF);
            unlink($filePathImage);
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
        $sort_by = ['colum' => 'id', 'order' => 'ASC'];
        $page_size = 10;
        if(!empty($request->sort_by)){
          $sort_by = $request->sort_by;
        }
        if(!empty($request->page_size)){
            $page_size = $request->page_size;
        }
        $newsList = DB::table("news")
            ->orderBy($sort_by['colum'],$sort_by['order'])
            ->join("images", "news.id", "=", "images.news_id")
            ->join("pdf_files", "news.id", "=", "pdf_files.news_id")
            ->select('news.*', 'images.url', 'pdf_files.file_path');

        if (!empty($request->title)) {
            $newsList->whereRaw("LOWER(news.title) LIKE ?", ['%' . strtolower($request->title) . '%']);
        }
        
        if (!empty($request->abstract)) {
            $newsList->whereRaw("LOWER(news.abstract) LIKE ?", ['%' . strtolower($request->abstract) . '%']);
        }
            
        return response()->json($newsList->paginate($page_size), Response::HTTP_OK);
    }

    public function getNewsPdf(Request $request){
        $request->validate([
            'id' => 'required',
        ]);

        try {
            $news = News::findOrFail($request->id);
            $filePath = storage_path('app/public/' . $news->pdfFile->file_path);
            if (!$news->pdfFile || !file_exists(storage_path('app/' . str_replace('storage/', 'public/', $news->pdfFile->file_path)))) {
                return response()->json(['error' => 'File not found.'], 404);
            }
            return response()->json([
                'id' => $news->id,
                'title' => $news->title,
                'abstract' => $news->abstract,
                'imagen' => $news->image->url,
                'pdf' => $news->pdfFile->file_path,
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
            $news = News::findOrFail($request->id);
            if(!$news){
                responde()->json(['message' => 'News not found'], Response::HTTP_NOT_FOUND);
            }
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
}