<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Utils;
use Carbon\Carbon;
use App\Models\UploadAndConvert;

class DocumentController extends Controller
{
    private function baseURL(){
        return env("MFILES_BASEURL","https://raemulanlands.cloudvault.m-files.com");
    }

    public function getToken(){
        $authURL = $this->baseURL().'/REST/server/authenticationtokens';
        $credential = [
            'Username'  => config('mfiles.mfiles_credentials.username'),
            'Password'  => config('mfiles.mfiles_credentials.password'),
            'VaultGuid' => env('MFILES_VAULTID',"DF00F177-C725-4EB7-AC98-A557D1245361")
        ];
        $client = new Client();
        try {
            $response = $client->post($authURL,[
                'json' => $credential ,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $setCookies = null;
            $setCookie = $response->getHeader('Set-Cookie');
            if($setCookie){
                foreach ($setCookie as $cookie) {
                    $array = explode(';',$cookie);
                    $setCookies = $setCookies == NULL ? $array[0] : $setCookies.";".$array[0];
                }
            }
            $response_message = json_decode($response->getBody()->getContents());
            return ["token"=>$response_message->Value, "setCookie" => $setCookies];

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error in generating Token: ' . $e->getMessage()], 500);
        }
    }

    public function get_token(Request $request)
    {
        $authURL = $this->baseURL().'/REST/server/authenticationtokens';
        $username = !$request->Credentials?env("MFILES_ADMIN_USER"):$request->Credentials['Username'];
        $password = !$request->Credentials?env("MFILES_ADMIN_PASS"):$request->Credentials['Password'];
        $credential = [
            'Username'  => $username,
            'Password'  => $password,
            'VaultGuid' => env('MFILES_VAULTID',"DF00F177-C725-4EB7-AC98-A557D1245361")
        ];

        $client = new Client();
        try {
            $response = $client->post($authURL,[
                'json' => $credential ,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $setCookies = null;
            $setCookie = $response->getHeader('Set-Cookie');
            if($setCookie){
                foreach ($setCookie as $cookie) {
                    $array = explode(';',$cookie);
                    $setCookies = $setCookies == NULL ? $array[0] : $setCookies.";".$array[0];
                }
            }
            $response_message = json_decode($response->getBody()->getContents());
            return ["token"=>$response_message->Value, "setCookie" => $setCookies];

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error in generating Token: ' . $e->getMessage()], 500);
        }
    }

    public function upload_storefront_file(Request $request)
    {
        $get_token = $this->get_token($request);
        $client = new Client();

        $headers = [
            'x-authentication' => $get_token['token'],
            'Cookie' => $get_token['setCookie'],
        ];
        $objectId = env("MFILES_STOREFRONT_OBJECT_ID",723368);

        $uploadFile = $request->file('uploadFile');
        $fileName = $uploadFile->getClientOriginalName();
        $objectURL = $this->baseURL() . "/REST/objects/0/{$objectId}/files";

        try {
            $multipart = [
                [
                    'name'     => 'file',
                    'contents' => Utils::tryFopen($uploadFile->getPathname(), 'r'),
                    'filename' => Carbon::now()->timestamp."_".$fileName,
                ],
            ];
            $request = new GuzzleRequest('POST', $objectURL, $headers);
            $res = $client->sendAsync($request, ['multipart' => $multipart])->wait();

            $responseBody = $res->getBody()->getContents();
            $resBodyJson = json_decode($responseBody, true);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'fileId' => $resBodyJson['AddedFiles'][0]['ID']
            ], 200);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $errorMessage = $e->getResponse()->getBody()->getContents();
            }

            return response()->json([
                'error' => 'Upload failed',
                'details' => $errorMessage
            ], 500);
        }
    }

    public function view_storefront_document(Request $request, $fileId){
        $get_token = $this->get_token($request);
        $objectId = env("MFILES_STOREFRONT_OBJECT_ID",723368);
        $client = new Client();
        $headers = [
            'x-authentication' => $get_token['token'],
            'Content-Type' => 'application/json',
            'Cookie' => $get_token['setCookie']
        ];
        try{
            // $objectURL =$this->baseURL()."/REST/objects/0/723368/files/".$fileId."/content.aspx?filePreview=true&format=pdf" ;
            $objectURL =$this->baseURL()."/REST/objects/0/".$objectId."/files/".$fileId."/content.aspx?format=pdf" ;
            $request = new GuzzleRequest('GET', $objectURL, $headers);
            $res = $client->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();

            return response($responseBody, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="document.pdf"'); // inline = view in
            // dd($responseBody);
        } catch (\Exception $e) {
            // dd($e->getResponse()->getBody()->getContents());
            return response()->json(['error' => 'Document not found'], 500);
        }

    }

    public function upload_and_view_storefront_file(Request $request)
    {
        $get_token = $this->getToken();

        $client = new \GuzzleHttp\Client();

        $headers = [
            'x-authentication' => $get_token['token'],
            'Cookie'           => $get_token['setCookie'],
        ];

        $objectId = env('MFILES_STOREFRONT_OBJECT_ID', 723368);

        try {
            // Step 1: Upload file
            $uploadFile = $request->file('uploadFile');
            $fileName   = $uploadFile->getClientOriginalName();
            $uploadUrl  = $this->baseURL() . "/REST/objects/0/{$objectId}/files";

            $multipart = [[
                'name'     => 'file',
                'contents' => \GuzzleHttp\Psr7\Utils::tryFopen($uploadFile->getPathname(), 'r'),
                'filename' => now()->timestamp . '_' . $fileName,
            ]];

            $uploadReq  = new \GuzzleHttp\Psr7\Request('POST', $uploadUrl, $headers);
            $uploadRes  = $client->sendAsync($uploadReq, ['multipart' => $multipart])->wait();
            $uploadBody = json_decode($uploadRes->getBody()->getContents(), true);
            $fileId     = $uploadBody['AddedFiles'][0]['ID'] ?? null;
            if (!$fileId) {
                return response()->json([
                    'error'   => 'Upload failed — no file ID returned',
                    'details' => $uploadBody,
                ], 500);
            }

            // Step 2: Get file metadata for file size
            $fileInfoUrl = $this->baseURL() . "/REST/objects/0/{$objectId}/files/{$fileId}";
            $fileInfoReq = new \GuzzleHttp\Psr7\Request('GET', $fileInfoUrl, $headers);
            $fileInfoRes = $client->sendAsync($fileInfoReq)->wait();
            $fileInfoBody = json_decode($fileInfoRes->getBody()->getContents(), true);

            $fileSize = $fileInfoBody['Size'] ?? $uploadFile->getSize(); // fallback to local size

            // Step 3: View uploaded PDF
            $headers = [
                'x-authentication' => $get_token['token'],
                'Content-Type' => 'application/json',
                'Cookie' => $get_token['setCookie']
            ];
//            $viewUrl     = $this->baseURL() . "/REST/objects/0/{$objectId}/files/{$fileId}/content.aspx?format=pdf";
//
//            $viewReq  = new \GuzzleHttp\Psr7\Request('GET', $viewUrl, $headers);
//            $viewRes  = $client->sendAsync($viewReq)->wait();
//            $viewBody = $viewRes->getBody()->getContents();

            // Step 4: Log upload
           $uac= \App\Models\UploadAndConvert::create([
                'request_from_ip'      => $request->ip(),
                'request_from_website' => $request->from??'',
                'user_id'              => $request->from_user_id??'',
                'user_name'            => $request->from_user_name??'',
                'object_id'            => $fileId,
                'data'                 => $request->data??'',
                'file_name'            => $fileName??'',
                'link'                 => $viewUrl??'',
                'file_size'            => $fileSize ? number_format($fileSize / 1024, 2) . ' KB' : null,
                'expires_at'           => now()->addDays(7),
            ]);

           $uac->link= config('app.url') . "/api/mfiles/storefront/{$uac->id}/view/{$fileName}";
           $uac->save();
//           return response($viewUrl,200);

//             Step 5: Return inline PDF response
            return $uac->link;

        } catch (\Exception $e) {
            $error = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $error = $e->getResponse()->getBody()->getContents();
            }

            return response()->json([
                'error'   => 'Upload and view failed',
                'details' => $error,
            ], 500);
        }
    }

    public function view(String $id,String $filename){
        $get_token = $this->getToken();
        $objectId = env("MFILES_STOREFRONT_OBJECT_ID",723368);
        $client = new Client();
        $headers = [
            'x-authentication' => $get_token['token'],
            'Content-Type' => 'application/json',
            'Cookie' => $get_token['setCookie']
        ];
        $uac = UploadAndConvert::findOrFail($id);
        try{
            // $objectURL =$this->baseURL()."/REST/objects/0/723368/files/".$fileId."/content.aspx?filePreview=true&format=pdf" ;
            $objectURL =$this->baseURL()."/REST/objects/0/".$objectId."/files/".$uac->object_id."/content.aspx?format=pdf" ;
            $request = new GuzzleRequest('GET', $objectURL, $headers);
            $res = $client->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();

            return response($responseBody, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="document.pdf"'); // inline = view in
            // dd($responseBody);
        } catch (\Exception $e) {
            // dd($e->getResponse()->getBody()->getContents());
            return response()->json(['error' => 'Document not found'], 500);
        }
    }

    public function convert(Request $request){

        $get_token = $this->get_token($request);
        $client = new Client();

        $headers = [
            'x-authentication' => $get_token['token'],
            'Cookie' => $get_token['setCookie'],
        ];
//        $objectId = env("MFILES_STOREFRONT_OBJECT_ID",723368);
        $objectId=config('mfiles.mfile_sf_obj_id');

        $uploadFile = $request->file('uploadFile');
        $fileName = $uploadFile->getClientOriginalName();
        $objectURL = $this->baseURL() . "/REST/objects/0/{$objectId}/files";
        $fileId = '';
        try {
            $multipart = [
                [
                    'name'     => 'file',
                    'contents' => Utils::tryFopen($uploadFile->getPathname(), 'r'),
                    'filename' => Carbon::now()->timestamp."_".$fileName,
                ],
            ];
            $request = new GuzzleRequest('POST', $objectURL, $headers);
            $res = $client->sendAsync($request, ['multipart' => $multipart])->wait();

            $responseBody = $res->getBody()->getContents();
            $resBodyJson = json_decode($responseBody, true);

            $fileId = $resBodyJson['AddedFiles'][0]['ID'];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $errorMessage = $e->getResponse()->getBody()->getContents();
            }

            return response()->json([
                'error' => 'Upload failed',
                'details' => $errorMessage
            ], 500);
        }

        try{
            $objectURL =$this->baseURL()."/REST/objects/0/".$objectId."/files/".$fileId."/content.aspx?format=pdf" ;
            $request = new GuzzleRequest('GET', $objectURL, $headers);
            $res = $client->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();

            return response($responseBody, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="document.pdf"'); // inline = view in
            // dd($responseBody);
        } catch (\Exception $e) {
            // dd($e->getResponse()->getBody()->getContents());
            return response()->json(['error' => 'Document not found'], 500);
        }


    }

}
