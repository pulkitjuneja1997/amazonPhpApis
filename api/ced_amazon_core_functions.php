<?php

require __DIR__ . '/../vendor/autoload.php';
use \GuzzleHttp\Client;

function createDirectoryRecursively($path) {
    $directory = dirname($path);
    if (!is_dir($directory)) {
        createDirectoryRecursively($directory);
        mkdir($directory, 0777);
    }
}

function wp_mkdir_p( $target ) {
    $wrapper = null;

    // Strip the protocol
    if( wp_is_stream( $target ) ) {
        list( $wrapper, $target ) = explode( '://', $target, 2 );
    }

    // From php.net/mkdir user contributed notes
    $target = str_replace( '//', '/', $target );

    // Put the wrapper back on the target
    if( $wrapper !== null ) {
        $target = $wrapper . '://' . $target;
    }

    // Safe mode fails with a trailing slash under certain PHP versions.
    $target = rtrim($target, '/'); // Use rtrim() instead of untrailingslashit to avoid formatting.php dependency.
    if ( empty($target) )
        $target = '/';

    if ( file_exists( $target ) )
        return @is_dir( $target );

    // We need to find the permissions of the parent folder that exists and inherit that.
    $target_parent = dirname( $target );
    while ( '.' != $target_parent && ! is_dir( $target_parent ) ) {
        $target_parent = dirname( $target_parent );
    }

    // Get the permission bits.
    if ( $stat = @stat( $target_parent ) ) {
        $dir_perms = $stat['mode'] & 0007777;
    } else {
        $dir_perms = 0777;
    }

    if ( @mkdir( $target, $dir_perms, true ) ) {

        // If a umask is set that modifies $dir_perms, we'll have to re-set the $dir_perms correctly with chmod()
        if ( $dir_perms != ( $dir_perms & ~umask() ) ) {
            $folder_parts = explode( '/', substr( $target, strlen( $target_parent ) + 1 ) );
            for ( $i = 1; $i <= count( $folder_parts ); $i++ ) {
                @chmod( $target_parent . '/' . implode( '/', array_slice( $folder_parts, 0, $i ) ), $dir_perms );
            }
        }

        return true;
    }

    return false;
}


function wp_is_stream( $path ) {
    $scheme_separator = strpos( $path, '://' );

    if ( false === $scheme_separator ) {
        // $path isn't a stream.
        return false;
    }

    $stream = substr( $path, 0, $scheme_separator );

    return in_array( $stream, stream_get_wrappers(), true );
}

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