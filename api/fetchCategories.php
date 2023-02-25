<?php

$request_body = $_POST;

function ced_amazon_fetch_next_level_category( $request_body ) {

        $template_id = isset($_POST['template_id']) ? $_POST['template_id'] : '';

        $select_html = '';
        global $wpdb;
       
        $amazon_category_data = isset( $_POST['category_data'] ) ? $_POST['category_data'] : array();
        $level                = isset( $_POST['level'] ) ? $_POST['level'] : '';
        $shop_id              = isset( $_POST['shop_id'] ) ? $_POST['shop_id'] : '';
        $display_saved_values = isset( $_POST['display_saved_values'] ) ? $_POST['display_saved_values'] : '';
        $domain               = isset( $_POST['domain'] ) ? $_POST['domain'] : '';

        $next_level           = intval( $level ) + 1;

        $amzonCurlRequest = __DIR__ . '/amazon/lib/ced-amazon-curl-request.php';

        if ( file_exists( $amzonCurlRequest ) ) {
            require_once $amzonCurlRequest;
            $amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
        } else {
            return;
        }

        if ( ! empty( $template_id ) ) {

           $url = $domain . '/wp-json/api-test/v1/getProfileDetails';
           $args = array(
            'method'      => 'POST',
            'timeout'     => 45,
            'sslverify'   => false,
            'headers'     => array(
                'Content-Type'  => 'application/json',
            ),
            'body'        => json_encode( array('itemIDs' => $itemId) ),
        );
           $args = array();
           wp_remote_post( $url, $args );
        }

        if ( 'no' == $display_saved_values ) {
            $current_amazon_profile = array();
        }

        if ( is_array( $amazon_category_data ) && ! empty( $amazon_category_data ) ) {

            $category_id     = isset( $amazon_category_data['primary_category'] ) ? $amazon_category_data['primary_category'] : '';
            $sub_category_id = isset( $amazon_category_data['secondary_category'] ) ? $amazon_category_data['secondary_category'] : '';
            $browse_nodes    = isset( $amazon_category_data['browse_nodes'] ) ? $amazon_category_data['browse_nodes'] : '';
        }

        $url_array = array(
            1 => array(
                'url' => 'webapi/rest/v1/category/?shop_id=' . $shop_id,
                'key' => 'primary_category',
            ),
            2 => array(
                'url' => 'webapi/rest/v1/sub-category/?shop_id=' . $shop_id . '&selected=' . $category_id,
                'key' => 'secondary_category',
            ),
            3 => array(
                'url' => 'webapi/rest/v1/browse-node/?shop_id=' . $shop_id . '&selected=' . $category_id,
                'key' => 'browse_nodes',
            ),
            4 => array(
                'url' => 'webapi/rest/v1/category-attribute/?shop_id=' . $shop_id . '&category_id=' . $category_id . '&sub_category_id=' . $sub_category_id . '&browse_node_id=' . $browse_nodes . '&barcode_exemption=false',
                'key' => 'category_attributes',
            ),
        );

        $modified_key = explode( '_', $url_array[ $next_level ]['key'] );
        $modified_key = ucfirst( $modified_key[0] ) . ' ' . ucfirst( $modified_key[1] );

        $ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

        $shop_loc            = get_option( 'ced_umb_amazon_bulk_profile_loc' );
        $location_for_seller = get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );

        $userData    = $ced_amzon_configuration_validated[ $location_for_seller ];
        $userCountry = $userData['ced_mp_name'];

        if ( 4 > $next_level ) {
            $amazonCategoryList = $amzonCurlRequestInstance->ced_amazon_get_category( $url_array[ $next_level ]['url'] );

            if ( is_array( $amazonCategoryList ) && ! empty( $amazonCategoryList ) ) {
                $select_html  = '<tr class="" id="ced_amazon_categories">';
                $select_html .= '<td>
                <label for="" class="tooltip">Amazon ' . $modified_key . '
                <span class="ced_amazon_wal_required">[Required]</span>
                </label>
                </td>';
                $select_html .= '<td >';
                $select_html .= '<select id="ced_amazon_' . $url_array[ $next_level ]['key'] . '_selection" name="ced_amazon_profile_data[' . $url_array[ $next_level ]['key'] . ']" class="select short ced_amazon_select_category" data-level="' . $next_level . '">';
                $select_html .= '<option value="">--Select--</option>';

                if ( is_array( $amazonCategoryList['response'] ) ) {
                    foreach ( $amazonCategoryList['response'] as $key => $value ) {
                        $selected = '';
                        if ( ! empty( $current_amazon_profile ) && $current_amazon_profile[ $url_array[ $next_level ]['key'] ] == $key ) {
                            $selected = 'selected';
                        }
                        $select_html .= '<option value="' . $key . '" ' . $selected . '>' . ucfirst( $value ) . '</option>';
                    }
                }

                $select_html .= '</select>';
                $select_html .= '</td>';
                $select_html .= '</tr>';

                echo esc_attr( wp_send_json_success( $select_html ) );
                die;

            }
        }

        if ( 4 == $next_level ) {

            die('okkk');
            $upload_dir = wp_upload_dir();

            // fetch product template
            $product_template = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/products_template_fields.json';
            if ( 'no' == $display_saved_values || ! file_exists( $product_template ) ) {
                $amzonCurlRequestInstance->fetchProductTemplate( $category_id, $userCountry );
            }

            // fetch product template

            // save profile
            $dirname           = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/' . $sub_category_id;
            $fileName          = $dirname . '/products.json';
            $valid_values_file = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/valid_values.json';
            if ( ! file_exists( $fileName ) || ! file_exists( $valid_values_file ) ) {

                if ( ! is_dir( $dirname ) ) {
                    wp_mkdir_p( $dirname );
                }

                wp_mkdir_p( $dirname );

                $amazon_profile_data = $amzonCurlRequestInstance->ced_amazon_get_category( $url_array[ $next_level ]['url'] );

                $amazon_profile_template      = $amazon_profile_data['response'];
                $amazon_profile_template_data = json_encode( $amazon_profile_template );

                $amazon_profile_valid_values      = $amazon_profile_data['valid_values'];
                $amazon_profile_valid_values_data = json_encode( $amazon_profile_valid_values );

                if ( ! file_exists( $fileName ) ) {
                    $jsonFile = fopen( $fileName, 'w' );
                    fwrite( $jsonFile, $amazon_profile_template_data );
                    fclose( $jsonFile );
                    chmod( $fileName, 0777 );
                }

                if ( ! file_exists( $valid_values_file ) ) {
                    $jsonFile = fopen( $valid_values_file, 'w' );
                    fwrite( $jsonFile, $amazon_profile_valid_values_data );
                    fclose( $jsonFile );
                    chmod( $valid_values_file, 0777 );
                }
            } else {
                $amazon_profile_template_data     = file_get_contents( $fileName );
                $amazon_profile_valid_values_data = file_get_contents( $valid_values_file );
            }

            $amazonCategoryList = json_decode( $amazon_profile_template_data, true );
            $valid_values       = json_decode( $amazon_profile_valid_values_data, true );

            if ( ! empty( $amazonCategoryList ) ) {

                global $wpdb;

                // $results        = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
                // $query          = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
                // $addedMetaKeys  = get_option( 'CedUmbProfileSelectedMetaKeys', false );
                
                $optionalFields = array();
                $html           = '';

                foreach ( $amazonCategoryList as $fieldsKey => $fieldsArray ) {

                    $select_html2 = $this->prepareProfileFieldsSection( $fieldsKey, $fieldsArray, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id );

                    if ( $select_html2['display_heading'] ) {
                        $select_html .= '<tr class="categoryAttributes" ><td colspan="3"></td></tr><tr class="categoryAttributes" ><td colspan="3"></td></tr>
                        <tr class="categoryAttributes "><th colspan="3" class="profileSectionHeading">
                        <label style="font-size: 1.25rem;color: #6574cd;" >';

                        $select_html .= $fieldsKey;
                        $select_html .= ' Fields </label></th></tr><tr class="categoryAttributes" ><td colspan="3"></td></tr>';

                    }

                    $select_html     .= $select_html2['html'];
                    $optionalFields[] = $select_html2['optionsFields'];

                }

                if ( 'no' == $display_saved_values ) {

                    if ( ! empty( $optionalFields ) ) {

                        $html .= '<tr class="categoryAttributes"><th colspan="3" class="px-4 mt-4 py-6 sm:p-6 border-t-2 border-green-500" style="text-align:left;margin:0;">
                        <label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

                        $html .= '<tr class="categoryAttributes" ><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';

                        foreach ( $optionalFields as $optionalField ) {
                            foreach ( $optionalField as $fieldsKey1 => $fieldsValue1 ) {
                                $html .= '<optgroup label="' . $fieldsKey1 . '">';
                                foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

                                    $html .= '<option value="';
                                    $html .= htmlspecialchars( json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) );
                                    $html .= '" >';
                                    $html .= $fieldsValue[0]['label'];
                                    $html .= ' (';
                                    $html .= $fieldsKey2;
                                    $html .= ') </option>';

                                }

                                $html .= '</optgroup>';
                            }
                        }

                        $html .= '</select></td>';
                        $html .= '<td><button class="ced_amazon_add_rows_button" id="';
                        $html .= $fieldsKey;
                        $html .= '">Add Row</button></td></tr>';
                    }

                    $select_html .= $html;

                } else {

                    if ( ! empty( $optionalFields ) ) {
                        $optional_fields = array_values( $optionalFields );

                        $select_html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
                        <label style="font-size: 1.25rem;color: #6574cd;" > Optional Fields </label></th></tr>';

                        $optionalFieldsHtml = '';
                        $saved_value        = json_decode( $current_amazon_profile['category_attributes_data'], true );

                        $html .= '<tr class="categoryAttributes"><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';
                        foreach ( $optionalFields as $optionalField ) {
                            foreach ( $optionalField as $fieldsKey1 => $fieldsValue1 ) {
                                $html .= '<optgroup label="' . $fieldsKey1 . '">';
                                foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

                                    if ( ! array_key_exists( $fieldsKey2, $saved_value ) ) {
                                        $html .= '<option  value="' . htmlspecialchars( json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) ) . '" >' . $fieldsValue[0]['label'] . ' (' . $fieldsKey2 . ') </option>';

                                    } else {

                                        $prodileRowHTml      = $this->prepareProfileRows( $current_amazon_profile, 'yes', $valid_values, $sub_category_id, '', '', $fieldsKey2, $fieldsValue[0], 'yes', '', '','' );
                                        $optionalFieldsHtml .= $prodileRowHTml;
                                    }
                                }
                                $html .= '</optgroup>';
                            }
                        }

                        $html .= '</select></td>';
                        $html .= '<td><button class="ced_amazon_add_rows_button" id="' . $fieldsKey . '">Add Row</button></td></tr>';

                        $select_html .= $optionalFieldsHtml;
                        $select_html .= $html;


                    }
                }


                /*// test
                    
                // ----------------------------------------- Display Missing Fields Starts ---------------------------------------------------	

                $select_html .= '<tr class="categoryAttributes ced_amazon_add_missing_fields_heading" data-attr="" ><th colspan="3" class="profileSectionHeading">
                <label style="font-size: 1.25rem;color: #6574cd;">Missing Fields</label></th></tr>';


                $ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

                $shop_loc            = get_option( 'ced_umb_amazon_bulk_profile_loc' );
                $location_for_seller = get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );

                $userData    = $ced_amzon_configuration_validated[ $location_for_seller ];
                $userCountry = $userData['ced_mp_name'];

                $upload_dir           = wp_upload_dir();
                $missing_fields_json_path  = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/' . $sub_category_id . '/missingFields.json';

                if( file_exists( $missing_fields_json_path ) ){

                    $missing_fields_encoded = file_get_contents( $missing_fields_json_path );
                    $missing_fields_decoded = json_decode( $missing_fields_encoded, true ); 

                    $missing_fields_html = '';
                    foreach( $missing_fields_decoded['Custom'] as $field_id => $missing_field_array ){
                        
                        $custom_field = Array(
                            'Custom Fields' => array( $field_id .'_custom_field' => $missing_field_array)
                        );
                        
                          
                        // if( !empty($template_id ) ){
                        // 	$view = 'yes';
                        // }
                        //                                                $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $req, $required, $fieldsKey2, $fieldsValue, $globalValue, $globalValueDefault, $globalValueMetakey 							
                        $select_html     .= $this->prepareProfileRows(  $current_amazon_profile, $display_saved_values , $valid_values, $sub_category_id, '', '', $field_id .'_custom_field', $missing_field_array, '', '', '', 'yes' );
                            
                        // $encoded_response = $this->ced_amazon_profile_dropdown( $field_id, $required = '', array(), $custom_field, $category_id, $sub_category_id, 'no' );
                        // $decoded_response = json_decode( $encoded_response, true );
                        // $missing_fields_html     .= $decoded_response['data'];

                        
                    }

                   // $select_html .= $missing_fields_html;
                    
                }  


                // ----------------------------------------- Display Missing Fields Ends ---------------------------------------------------	


                $select_html .= '<tr class="categoryAttributes ced_amazon_add_missing_field_row" >
                        <td> <label> Add Missing Field Title </label></td>
                        <td> <p>Title: </p> <input type="text" class="short ced_amazon_add_missing_field_title custom_category_attributes_input" /></td>
                        <td><p>Slug</p> <input type="text" class="short ced_amazon_add_missing_field_slug custom_category_attributes_input" onkeypress="return event.charCode != 32" />
                        <button class="ced_amazon_add_missing_fields ced-amazon-v2-btn">Add Row</button></td>
                    </tr>';

                // test*/

            }

            echo esc_attr( wp_send_json_success( $select_html ) );
            wp_die();

        }
    
}


?>