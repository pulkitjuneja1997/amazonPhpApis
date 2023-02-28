<?php

require __DIR__ . '/../vendor/autoload.php';
use \GuzzleHttp\Client;


function getUserAccountMetaData($domain){

    $url = $domain . '/wp-json/api-test/v1/userMetaData';

    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', $url);
    
    echo $response->getStatusCode(); // 200
    echo $response->getHeaderLine('content-type'); // 'application/json; charset=utf8'
    echo $response->getBody(); // '{"id": 1420053, "name": "guzzle", ...}'
    
    if( $response->getStatusCode() == 200 ){
        $responseBody = $response->getBody();
        $data = json_decode( $responseBody, true );
        return json_encode( array( 'status' => true, 'data' => $data['user_data'] ) ); 
    } else
        return json_encode( array( 'status' => false, 'message' => ' '));

    

}


?>