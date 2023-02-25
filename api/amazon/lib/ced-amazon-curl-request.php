<?php

class Ced_Amazon_Curl_Request {

	public function ced_amazon_get_category( $url ) {

		$access_token = get_option( 'ced_amazon_sellernext_access_token', true );
		$args         = array(
			'headers'     => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout'     => 1000,
			'httpversion' => '1.0',
			'sslverify'   => false,
		);

		$response   = wp_remote_get( 'https://remote.connector.sellernext.com/' . $url, $args );
		$categories = array();

		if ( is_array( $response ) && isset( $response['body'] ) ) {
			$categories = json_decode( $response['body'], true );
			return $categories;

		} elseif ( is_object( $response ) ) {
				echo json_encode(
					array(
						'success' => false,
						'message' => $response->errors['http_request_failed'][0],
						'status'  => 'error',
					)
				);
				die;
		} else {
			return $categories;
		}

	}

	public function fetchProductTemplate( $category_id, $userCountry ) {

		// Product flat file template structure json file
		$file_location = 'lib/' . $userCountry . '/' . $category_id . '/json/products_template_fields.json';  // products_all_fields.json'
		$endpoint      = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/get_template';
		$body          = array(
			'location' => $file_location,
		);
		$body          = wp_json_encode( $body );

		$options = array(
			'body'        => $body,
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'timeout'     => 200,
			'httpversion' => '1.0',
			'sslverify'   => false,
		);

		$data_response      = wp_remote_post( $endpoint, $options );
		$data_response      = json_decode( $data_response['body'], true );
		$json_url           = $data_response['url'];
		$json_url           = stripslashes( $json_url );
		$json_template_data = file_get_contents( $json_url );

		$upload_dir     = wp_upload_dir();
		$dirname        = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id;
		$json_file_name = 'products_template_fields.json';

		if ( ! file_exists( $dirname . '/' . $json_file_name ) ) {
			if ( ! is_dir( $dirname ) ) {
				wp_mkdir_p( $dirname );
			}
			$templateFile = fopen( $dirname . '/' . $json_file_name, 'w' );
			fwrite( $templateFile, $json_template_data );

		} else {
			$templateFile = fopen( $dirname . '/' . $json_file_name, 'w' );
			fwrite( $templateFile, $json_template_data );
		}

		fclose( $templateFile );
		chmod( $dirname . '/' . $json_file_name, 0777 );

	}

	public function getMarketplaceParticipations( $refresh_token, $marketplace_id, $seller_id ) {

		$args = array(

			'timeout'     => 1000,
			'httpversion' => '1.0',
			'sslverify'   => false,
			'body'        => json_encode(
				array(
					'marketplace_id' => $marketplace_id,
					'seller_id'      => $seller_id,
					'token'          => $refresh_token,
				)
			),
		);

		$response = wp_remote_post( 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/get_marketplace_participations', $args );

		if ( is_array( $response ) && isset( $response['body'] ) ) {
			return json_decode( $response['body'], true );
		} else {
			return array(
				'status'  => 'error',
				'message' => 'Unable to fetch your details and verify you',
			);
		}

	}


}




