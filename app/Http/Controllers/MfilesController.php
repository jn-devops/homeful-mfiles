<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Storage;

class MfilesController extends Controller
{   
    public function get_datatype($value)
    {
    $datatypes = [
            'Boolean' => 8,
            'Date' => 5,
            'FILETIME' => 12,
            'Float' => 3,
            'Integer' => 2,
            'Integer64' => 11,
            'Lookup' => 9,
            'MultiLookup' => 10,
            'MultiText' => 13,
            'Text' => 1,
            'Time' => 6,
            'Timestamp' => 7,
    ];
    return $datatypes[$value] ?? null;
    
    }
    public function process_property($array){   
            $datatypes = [
                'Boolean' => 8,
                'Date' => 5,
                'FILETIME' => 12,
                'Float' => 3,
                'Integer' => 2,
                'Integer64' => 11,
                'Lookup' => 9,
                'MultiLookup' => 10,
                'MultiText' => 13,
                'Text' => 1,
                'Time' => 6,
                'Timestamp' => 7,
            ];
            $datatype = $array['DataType'] ?? null;

            switch ($array['DataType']) {
                case 'MultiLookup':
                    $property = [
                        "PropertyDef" => $array['ID'],
                        "TypedValue" => [
                            "DataType" => $this->get_datatype($array['DataType']),
                            "Lookups" => [
                                "Item" => $array['ObjID'],
                                "Version" => -1
                            ]
                        ]
                    ];    
                    break;
                case 'Lookup':
                    $property = [
                        "PropertyDef" => $array['ID'],
                        "TypedValue" => [
                            "DataType" => $this->get_datatype($array['DataType']),
                            "Lookup" => [
                                "Item" => $array['ObjID'],
                                "Version" => -1
                            ]
                        ]
                    ];   
                    break;
                default:
                     $property = [
                    "PropertyDef" => $array['ID'],
                    "TypedValue" => [
                        "DataType" => $this->get_datatype($array['DataType']),
                        "Value" => $array['Value']
                    ]
                    ];    
                    break;
            }
      
        return $property;

    }
    private function baseURL(){
        return env("MFILES_BASEURL","https://raemulanlands.cloudvault.m-files.com");
    }
    public function user_information(){
        $get_token = $this->get_token($request);
        $sessionURL =$this->baseURL()."/REST/session" ;
        // Get the uploaded file from the request
        $headers = [
            'x-authentication' => $get_token['token'],
            'Content-Type' => 'application/json',
            'Cookie' => $get_token['setCookie']
            ];        

        // dd($response_content);
        
        $client = new Client();
        try{
            $request = new GuzzleRequest('GET', $sessionURL, $headers);
            $response = $client->sendAsync($request)->wait();
            $response_content = json_decode($response->getBody()->getContents());
        } catch (\Exception $e) {
        return response()->json(['error' => 'Error in getting user information: ' . $e->getMessage()], 500);
         }
        return $response_content;
    }
    public function get_token(Request $request)
    {
        $authURL = $this->baseURL().'/REST/server/authenticationtokens';
        $username = !$request->Credentials?env("MFILES_ADMIN_USER"):$request->Credentials['Username'];
        $password = !$request->Credentials?env("MFILES_ADMIN_PASS"):$request->Credentials['Password'];
        $credential = [
            'Username'  => $username,
            'Password'  => $password,
            'VaultGuid' => env('MFILES_VAULTID',"DF00F177-C725-4EB7-AC98-A557D1245361") // $request->input('vaultid') -- change to Param if multiple vault
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
    
    public function create_object(Request $request){
        $get_token = $this->get_token($request);
        $properties = $request->Properties;
        $setProperties;
        //set document properties
        if($properties)
        {
            foreach ($properties as $property) 
            {   
                $currProperty = $this->process_property($property);                
                $setProperties[] = $currProperty;

                
            }
        }
        //set if document accept multiple files
        $setProperties[] = [
            "PropertyDef" => 22,
            "TypedValue" => [
                "DataType" => 8,
                "Value" => false
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
        $body = '{"PropertyValues":'.json_encode($setProperties).',"Files": []}';
        $bodyJson = [
            "PropertyValues" => $setProperties,
            "Files" => [] ];

        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];

        $client = new Client();

        $objectURL =$this->baseURL()."/REST/objects/".$request->input('objectId')."?checkIn=true" ;
        // dd($objectURL);
        $request = new GuzzleRequest('POST', $objectURL, $headers, $body);
        $res = $client->sendAsync($request)->wait();
        $responseBody = json_decode($res->getBody());//->getContents();
        // dd($responseJSON);
        // return $responseJSON->DisplayID;     
        // return $res->getBody();        
        return $responseBody;

    }
    
    public function upload_file_url(Request $request){
                //Set document properties
        $properties = $request->Properties;
        if (file_exists(storage_path($request->filePath))) {
            $filePath = storage_path($request->filePath);
        }
        else
        { 
        return "File not found in ".storage_path($request->filePath);    
        }
        $classId = (int)$request->classID;
        $objId = $request->objectId;
        $client = new Client();
        $objectURL =$this->baseURL()."/REST/files" ;
        $bodyFile = Utils::streamFor(fopen($filePath, 'r'));
        // dd( $request->filename );
        $get_token = $this->get_token($request);
        $fileName = $request->filename;
        $referenceCode = $request->referenceCode;
        $ext = $request->ext;
        
        $headers = [
            'x-authentication' => $get_token['token'],
            'Content-Type' => 'application/json',
            'Cookie' => $get_token['setCookie']
            ];
        $request = new GuzzleRequest('POST', $objectURL, $headers, $bodyFile);
        $response = $client->sendAsync($request)->wait();
        $response_content = json_decode($response->getBody()->getContents());
        $upload_response = [
            "UploadID" => $response_content->UploadID,
            "Size" => $response_content->Size ,
            "Title" => $fileName,
            "Extension" => $ext
        ];
        // dd($upload_response);
        //create object 
        $setProperties;
        // dd($properties);
        if($properties)
        {
            foreach ($properties as $property) 
            {   
                $currProperty = $this->process_property($property);                
                $setProperties[] = $currProperty;        
            }
        }
        // $setProperties[] =  [
        //     "PropertyDef" => 1068,
        //     "TypedValue" => [
        //         "DataType" => 1,
        //         "Value" => $fileName
        //     ]
        // ];
        // $setProperties[] =  [
        //     "PropertyDef" => 1115,
        //     "TypedValue" => [
        //         "DataType" => 1,
        //         "Value" => $referenceCode
        //     ]
        // ];
        //Set if document accept multiple files
        $setProperties[] = [
            "PropertyDef" => 22,
            "TypedValue" => [
                "DataType" => 8,
                "Value" => false
            ]
        ]; 

        //Set document class
        $setProperties[] = [
            "PropertyDef" => 100,
            "TypedValue" => [
                "DataType" => 9,
                "Lookup"=>[
                    "Item" => $classId,
                    "Version" => -1
                ]
            ]
        ];
        // dd($setProperties);
        $body = '{"PropertyValues":'.json_encode($setProperties).',"Files": ['.json_encode($upload_response).']}';
        $bodyJson = [
            "PropertyValues" => $setProperties,
            "Files" => $upload_response ];

     
        // dd($bodyJson);
        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];
        // dd($headers);

 
        try{
            $objectURL =$this->baseURL()."/REST/objects/".$objId."?checkIn=true" ;
            $request = new GuzzleRequest('POST', $objectURL, $headers, $body);
            $res = $client->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();
            dd($responseBody);
        } catch (\Exception $e) {
        dd($e->getResponse()->getBody()->getContents());
        return response()->json(['error' => 'Error in uploading file: ' . $e->getMessage()], 500);
         }
        return $res->getBody();    
       
    }
    public function upload_file(Request $request){
        $get_token = $this->get_token($request);
        $fileName = $request->upload->getClientOriginalName();
        $ext = $request->upload->getClientOriginalExtension();
        $classId = (int)$request->classID;
        // dd( $classId);
        $client = new Client();
        $objectURL =$this->baseURL()."/REST/files" ;
        // Get the uploaded file from the request
        $uploadFile = $request->file('upload');
        $bodyFile = Utils::streamFor(fopen($uploadFile->getPathname(), 'r'));
        // dd($bodyFile);


        
        $headers = [
            'x-authentication' => $get_token['token'],
            'Content-Type' => 'application/json',
            'Cookie' => $get_token['setCookie']
            ];
        $request = new GuzzleRequest('POST', $objectURL, $headers, $bodyFile);
        $response = $client->sendAsync($request)->wait();
        $response_content = json_decode($response->getBody()->getContents());
        // dd($response_content);
        $upload_response = [
            "UploadID" => $response_content->UploadID,
            "Size" => $response_content->Size ,
            "Title" => $fileName,
            "Extension" => $ext
        ];

        //create object 
        //Set document properties
        $setProperties[] =  [
            "PropertyDef" => 1157,
            "TypedValue" => [
                "DataType" => 1,
                "Value" => $fileName
            ]
        ];
        //Set if document accept multiple files
        $setProperties[] = [
            "PropertyDef" => 22,
            "TypedValue" => [
                "DataType" => 8,
                "Value" => false
            ]
        ]; 

        //Set document class
        $setProperties[] = [
            "PropertyDef" => 100,
            "TypedValue" => [
                "DataType" => 9,
                "Lookup"=>[
                    "Item" => $classId,
                    "Version" => -1
                ]
            ]
        ];
        $body = '{"PropertyValues":'.json_encode($setProperties).',"Files": ['.json_encode($upload_response).']}';
        $bodyJson = [
            "PropertyValues" => $setProperties,
            "Files" => $upload_response ];

        // dd($body);
        // dd($get_token);
        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];
        // dd($headers);

 
        try{
            $objectURL =$this->baseURL()."/REST/objects/104?checkIn=true" ;
            $request = new GuzzleRequest('POST', $objectURL, $headers, $body);
            $res = $client->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();
            dd($responseBody);
        } catch (\Exception $e) {
        dd($e->getResponse()->getBody()->getContents());
        return response()->json(['error' => 'Error in generating Token: ' . $e->getMessage()], 500);
         }
        return $res->getBody();    
        // return $upload_response;
        // {{MFWSUrl}}/objects/{{ObjectType}}/{{ObjectID}}/{{ObjectVersion}}/properties?_method=PUT
        // dd($responseBody);
        // return $res->getBody();
       
    }
    public function get_document_property(Request $request){
    
        $get_token = $this->get_token($request);
        $objectURL =$this->baseURL()."/REST/objects/".$request->objectID."?p".$request->propertyID."=".$request->name ;
        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];
        // dd($headers);
        try{
            $requestObj = new GuzzleRequest('GET', $objectURL, $headers);
            $res = $client->sendAsync($requestObj)->wait();
            $responseBody = $res->getBody()->getContents();
            $res = json_decode($responseBody);
            $objID = $res->Items[0]->DisplayID;
            foreach ($request->property_ids as $prop_id){ 
                $prop_def = $this->get_property_definition($headers,$prop_id);
                $prop_value = $this->get_property_value($headers,$request->objectID."/".$objID."/latest/properties/".$prop_id);
                $result[$prop_def['name']] = $prop_value ;
               
            }
            return $result;
        } catch (\Exception $e) {
        return response()->json(['error' => 'Error in searching object: ' . $e->getMessage()], 500);
    }
    }
    
    public function get_value_list($ID){
        $get_token = $this->get_token($request);
        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];

        $client = new Client();

        $objectURL =$this->baseURL()."/REST/valuelists/".$ID."/items" ;
        $request = new GuzzleRequest('GET', $objectURL, $headers);
        $res = $client->sendAsync($request)->wait();
        // $responseBody = $res->getBody()->getContents();
        $responseBody = json_decode($res->getBody()->getContents());
        return $responseBody;
    }
    public function download_file(Request $request){
        $get_token = $this->get_token($request);
        $properties = $request->Properties;
        $setProperties;
        //set document properties
        if($properties)
        {
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
        
        //set true if document accept multiple files
        $setProperties[] = [
            "PropertyDef" => 22,
            "TypedValue" => [
                "DataType" => 8,
                "Value" => false
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
        $body = '{"PropertyValues":'.json_encode($setProperties).',"Files": []}';
        $bodyJson = [
            "PropertyValues" => $setProperties,
            "Files" => [] ];

        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];

        $client = new Client();

        $objectURL =$this->baseURL()."/REST/objects/".$request->input('objectId')."?checkIn=true" ;
        // dd($objectURL);
        $request = new GuzzleRequest('POST', $objectURL, $headers, $body);
        $res = $client->sendAsync($request)->wait();
        $responseBody = $res->getBody()->getContents();
        return $responseBody;

    }
    public function get_object(Request $request){

        $get_token = $this->get_token($request);
        $objectURL =$this->baseURL()."/REST/objects/".$request->objectID."?p".$request->propertyID."=".$request->name ;
        $client = new Client();
        $headers = [
        'x-authentication' => $get_token['token'],
        'Content-Type' => 'application/json',
        'Cookie' => $get_token['setCookie']
        ];
        try{
            $requestObj = new GuzzleRequest('GET', $objectURL, $headers);
            $res = $client->sendAsync($requestObj)->wait();
            $responseBody = $res->getBody()->getContents();
            $res = json_decode($responseBody);
            $objID = $res->Items[0]->DisplayID;
            $objPropertyURL =$this->baseURL()."/REST/objects/".$request->objectID."/".$objID."/latest?include=properties";
            $requestProp = new GuzzleRequest('GET', $objPropertyURL, $headers);
            $resProp = $client->sendAsync($requestProp)->wait();
            $responseBodyProp = json_decode($resProp->getBody()->getContents());
            return $responseBodyProp;
        } catch (\Exception $e) {
        return response()->json(['error' => 'Error in searching object: ' . $e->getMessage()], 500);
    }
    }
    public function get_property_definition($headers,$propId){
        $client = new Client();
        try{
            $objectURL =$this->baseURL()."/REST/structure/properties/".$propId;
            $requestProp = new GuzzleRequest('GET', $objectURL, $headers);
            $resProp = $client->sendAsync($requestProp)->wait();
            $responseBodyProp = json_decode($resProp->getBody()->getContents());
            return ["dataType" => $responseBodyProp->DataType , "name" =>$responseBodyProp->Name];
           
        } catch (\Exception $e) {
        return response()->json(['error' => 'Error in getting property name: ' . $e->getMessage()], 500);
        }
    }
    public function get_property_value($headers,$paramProperty)
    {
        $client = new Client();
        try{
        $objPropertyURL =$this->baseURL()."/REST/objects/".$paramProperty;
        $requestProp = new GuzzleRequest('GET', $objPropertyURL, $headers);
        $resProp = $client->sendAsync($requestProp)->wait();
        $responseBodyProp = json_decode($resProp->getBody()->getContents());
        return $responseBodyProp->TypedValue->DisplayValue;       
        } catch (\Exception $e) {
        return response()->json(['error' => 'Error in getting property value: ' . $e->getMessage()], 500);
        }
    }
    
}
