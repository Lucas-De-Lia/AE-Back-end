<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class DecryptPostDataMiddleware
{
    private $key;
    private $chiper;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function __construct()
    {
        $this->chiper = env('APP_CIPHER');
        $keyTest = env('APP_CRYPT');
        if (substr($keyTest, 0, 7) === 'base64:') {
            $keyTest = substr($keyTest, 7);
            $this->key = base64_decode($keyTest);
        } else {
            $this->key = hex2bin($keyTest);
        }

        if ($this->key === false) {
            throw new \Exception('Invalid encryption key');
        }
    }

    private function decrypt($encryptedData, $key, $chiper)
    {
        $data = base64_decode($encryptedData);
        $iv = substr($data,0,16);
        $hash = substr($data, -32);
        $encryptedDataString = substr($data,16, strlen($data)- 32 - 16); // no es start y end sino start, length
        $decryptedData = openssl_decrypt($encryptedDataString, $chiper, $key, OPENSSL_RAW_DATA, $iv);
        if($decryptedData === false){
            throw new \Exception('Decryption failed');
        }
        $recalculatedHash = hash('sha256', $decryptedData, true);    
        if (!hash_equals($hash, $recalculatedHash)) {
            throw new Exception('Hash verification failed');
        }
        return $decryptedData;
    }

    private function encrypt($data, $key, $chiper)
    {
        $iv = random_bytes(16);
        $encryptedData = openssl_encrypt($data, $chiper, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash('sha256', $data, true);

        if ($encryptedData === false){
            throw new \Exception('Encryption failed');
        }
        return base64_encode($iv . $encryptedData. $hash);
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si la solicitud es un POST
        if ($request->isMethod('post') && $request->has('data')) {
            // Obtener el dato encriptado desde la solicitud
            $encryptedData = $request->input('data');
            try {
                // Desencriptar los datos usando AES
                $decryptedData = $this->decrypt($encryptedData, $this->key, $this->chiper);

                $data = json_decode($decryptedData, true);
                $request->merge($data);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Manejar el error si la desencriptación falla
                Log::error('Error al desencriptar los datos: ' . $e->getMessage());
                return response()->json(['error' => 'Error al desencriptar los datos'], 500);
            }
        }
        $response = $next($request);
        $excludedRoutes = [
        'api/resources/get-news-pdf', // agregá las que necesites
        'api/resources/update-news',
    ];
        if( $response instanceof \Illuminate\Http\JsonResponse &&
        !in_array($request->path(), $excludedRoutes)){
            $originalData = $response->getData(true);
            try {
                $encryptedResponseData = $this->encrypt(json_encode($originalData), $this->key, $this->chiper);
                $response->setData(['data' => $encryptedResponseData]);
            } catch (\Exception $e){
                Log::error('Error encrypting response data: ' . $e->getMessage());
                return response()->json(['error' => 'Error encrypting response'], 500);
            }
        }
        return $response;
    }

}