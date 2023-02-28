<?php


function getUserAccountMetaData(){

    $url = $domain . '/wp-json/api-test/v1/userMetaData';
    // $args = array(
    //     'method'      => 'GET',
    //     'timeout'     => 45,
    //     'sslverify'   => false,
    //     'headers'     => array(
    //         'Content-Type'  => 'application/json',
    //     )
        
    // );
    // $args = array();
    // $request = wp_remote_get( $url, $args );

    // if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
    //     error_log( print_r( $request, true ) );
    // }

    // $response = wp_remote_retrieve_body( $request );
    // $response = json_decode($response, true);

    // if( $response['status'] ){
    //     return $response;
    // } else
    //     return json_encode( array( 'status' => false, 'message' => ' '));


    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', '');
    
    echo $response->getStatusCode(); // 200
    echo $response->getHeaderLine('content-type'); // 'application/json; charset=utf8'
    echo $resp     
    

}


?>