<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Storage;

class MfilesController extends Controller
{
    public function get_token()
    {
        //$vaultName = env('MFILES_VAULT_URL').'/REST/'; //$request->input('vault');
        $authURL = env('MFILES_VAULT_URL').'/REST/'.'server/authenticationtokens';
        // $credential = [
        //     'Username'  => env("MFILES_ADMIN_USER"),
        //     'Password'  => env("MFILES_ADMIN_PASS"),
        //     'VaultGuid' => env('MFILES_VAULTID') // $request->input('vaultid') -- change to Param if multiple vault
        // ];
        $credential = [
            'Username'  => "rae.admin",
            'Password'  => "Jng@2024",
            'VaultGuid' => "CAD8D7CC-A4DB-4BC2-96E0-BE2B15A9CEDF" // $request->input('vaultid') -- change to Param if multiple vault
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
            return response()->json(['error' => 'Error in generatinng Token: ' . $e->getMessage()], 500);
        }
    }
    public function create_object(Request $request){
        $get_token = $this->get_token();
        $properties = $request->Properties;
        $setProperties;
        //set document properties
        if($properties){
            foreach ($properties as $property) 
            {   
                $currProperty = [
                    "PropertyDef" => $property['ID'],
                    "TypedValue" => [
                        "DataType" => 1,
                        "Value" => $property['Value']
                    ]
                ];
                $setProperties[] = $currProperty;
            }
        }
        //set if document accept multiple files
        $setProperties[] = [
            "PropertyDef" => 22,
            "TypedValue" => [
                "DataType" => 8,
                "Value" => $request->multipleFile
            ]
        ]; 
        //set document class
        $setProperties[] = [
            "PropertyDef" => 100,
            "TypedValue" => [
                "DataType" => 9,
                "Lookup"=>[
                    "Item" => $request->classId,
                    "Version" => -1
                ]
            ]
        ]; 

        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];
        
        $body = '{"PropertyValues":'.json_encode($setProperties).',"Files": []}';
        // dd($body);
        try{
            $response = $client->post(env('MFILES_VAULT_URL').'/REST/objects/'.$request->input('objectID').'?checkIn=true', [
                'json' => $body,
                'headers' => [
                    'x-authentication' => $get_token['token'],
                    'Content-Type' => 'application/json',
                    'Cookie' => $get_token['setCookie']
                ],
            ]);
            return json_decode($res->getBody()->getContents()); 
        }
        catch (\Exception $e) {
        return response()->json(['error' => 'Error creating document: ' . $e->getMessage()], 500);
        }
        // $request = new Request('POST', env('MFILES_VAULT_URL').'/REST/objects/'.$request->input('objectID').'?checkIn=true', $headers, $body);
        // $res = $client->sendAsync($request)->wait();
        return json_decode($res->getBody()->getContents()); 

    }
    public function upload_file(Request $request){
        
        $upload_url = env('MFILES_VAULT_URL').'/REST/files'; //$request->input('vault')
        $get_token = $this->get_token();
        $auth_token = $get_token['token'];
        $setCookie =  $get_token['setCookie'];
        $client = new Client();
        $headers = [
        'Content-Type' => 'application/json',
        'X-authentication' => $auth_token,
        'Cookie' => 'ASP.NET_SessionId=3vr4xytzwegax4oueruqwjgh; mfilesmsm=13ccb5b753b6fb91'
        ];
        $options = [
        'multipart' => [
            [
            'name' => $request->upload_file->getClientOriginalName(),
            'contents' => Utils::tryFopen($request->upload_file->getPathName(), 'r'),
            'filename' => $request->upload_file->getfilename(),
            'headers'  => [
                'Content-Type' => '<Content-type header>'
            ]
            ]
        ]];
        $request = new GuzzleRequest('POST', 'https://rli-storefront.cloudvault.m-files.com/REST/files', $headers);
        $res = $client->sendAsync($request, $options)->wait();//for large file/long processing callbacks
        echo $res->getBody();
      
        return $auth_token;
    }
    
    
}
