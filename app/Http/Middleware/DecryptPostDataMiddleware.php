<?php

namespace App\Http\Middleware;

use Closure;
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
        $encryptedDataString = substr($data,16);
        $decryptedData = openssl_decrypt($encryptedDataString, $chiper, $key, OPENSSL_RAW_DATA, $iv);
        if($decryptedData === false){
            throw new \Exception('Decryption failed');
        }
        return $decryptedData;
    }

    private function encrypt($data, $key, $chiper)
    {
        $iv = random_bytes(16);
        $encryptedData = openssl_encrypt($data, $chiper, $key, OPENSSL_RAW_DATA, $iv);
        if ($encryptedData === false){
            throw new \Exception('Encryption failed');
        }
        return base64_encode($iv . $encryptedData);
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si la solicitud es un POST
        if ($request->isMethod('post') && $request->has('data')) {
            // Obtener el dato encriptado desde la solicitud
            $encryptedData = $request->input('data'); // Ajusta 'data' según el nombre de tu campo POST
            try {
                // Desencriptar los datos usando AES
                Log::info($encryptedData);
                $decryptedData = $this->decrypt($encryptedData, $this->key, $this->chiper);
                $data = json_decode($decryptedData, true);
                Log::info($data);
                $request->merge($data);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Manejar el error si la desencriptación falla
                Log::error('Error al desencriptar los datos: ' . $e->getMessage());
                return response()->json(['error' => 'Error al desencriptar los datos'], 500);
            }
        }
        Log::info("RESPONSE");
        $response = $next($request);
        Log::info($response);
        if( $response instanceof \Illuminate\Http\JsonResponse){
            $originalData = $response->getData(true);
            Log::info($originalData);
            try {
                $encryptedResponseData = $this->encrypt(json_encode($originalData), $this->key, $this->chiper);
                Log::info($encryptedResponseData);
                $response->setData(['data' => $encryptedResponseData]);
            } catch (\Exception $e){
                Log::error('Error encrypting response data: ' . $e->getMessage());
                return response()->json(['error' => 'Error encrypting response']);
            }
        }
        return $response;
    }

}