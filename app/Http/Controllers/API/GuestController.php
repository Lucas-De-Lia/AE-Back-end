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

    /**
     * Uploads a PDF document along with its title and abstract, and an image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPdfDocument(Request $request)
    {
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

            // Store the uploaded image and associate it with the news entry

            $image = $request->file('image');

            if (!Storage::exists('public/images')) {
                Storage::makeDirectory('public/images');
            }

            $imagePath = 'public/images/' . $image->getClientOriginalName();
            
            $imageM =Image::read($image);
            $imageM->resize(1920, 1080)->toWebp(100)->save(storage_path('app/' . $imagePath));

            $news->image()->create(['url' => str_replace('public/', 'storage/', $imagePath)]);
            // Store the uploaded PDF and associate it with the news entry
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



    public function getQuestionList()
    {
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

    /**
     * Get the list of news articles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNewsList()
    {
        // Retrieve all news articles and map them to a new format
        $newsList = News::all()->map(function ($news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'image' => "/" . $news->image->url,
                'abstract' => $news->abstract
            ];
        })->all();

        // Return the news list as a JSON response
        return response()->json($newsList, Response::HTTP_OK);
    }
    public function getNewsPdf(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        try {
            $news = News::findOrFail($request->id);
            $filePath = storage_path('app/public/' . $news->pdfFile->file_path);

            if (!$news->pdfFile || !file_exists(storage_path('app/' . str_replace('storage/', 'public/', $news->pdfFile->file_path)))) {
                return response()->json(['error' => 'File not found.'], 404);
            }
            //$filePath = storage_path('app/' . str_replace('storage/', 'public/', $news->pdfFile->file_path));
            //$fileContent = file_get_contents($filePath);
            //$base64File = base64_encode($fileContent);
            return response()->json([
                'id' => $news->id,
                'title' => $news->title,
                'pdf' => $news->pdfFile->file_path,
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'PDF not found',
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