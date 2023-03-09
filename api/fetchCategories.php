<?php

class CED_eBay_Category_Features{

    public function ced_amazon_fetch_next_level_category( $request_body ) {

        $template_id = isset($request_body['template_id']) ? $request_body['template_id'] : '';
        $select_html = '';
    
        $amazon_category_data = isset( $request_body['category_data'] ) ? $request_body['category_data'] : array();
        $level                = isset( $request_body['level'] ) ? $request_body['level'] : '';
        $shop_id              = isset( $request_body['shop_id'] ) ? $request_body['shop_id'] : '';
        $display_saved_values = isset( $request_body['display_saved_values'] ) ? $request_body['display_saved_values'] : '';
        $domain               = isset( $request_body['domain'] ) ? $request_body['domain'] : '';

        $domain = 'https://locarbu.com/';
        $next_level           = intval( $level ) + 1;

        print_r($request_body);
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
                'body'        => json_encode( array('template_id' => $template_id ) ),
            );
            $args = array();
            $request = wp_remote_post( $url, $args );

            if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
                error_log( print_r( $request, true ) );
            }
        
            $response = wp_remote_retrieve_body( $request );
            $response = json_decode($response, true);

            if( $response['status'] ){
                $current_amazon_profile = $response['profile_data'];
            } else
                return json_encode( array( 'status' => false, 'message' => ' '));
            
        }

        if ( 'no' == $display_saved_values ) {
            $current_amazon_profile = array();
        }

        if ( is_array( $amazon_category_data ) && ! empty( $amazon_category_data ) ) {

            $category_id     = isset( $amazon_category_data['primary_category'] ) ? $amazon_category_data['primary_category'] : '';
            $sub_category_id = isset( $amazon_scategory_data['secondary_category'] ) ? $amazon_category_data['secondary_category'] : '';
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

        // $ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );
        // $location_for_seller = get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );

        // $userData    = $ced_amzon_configuration_validated[ $location_for_seller ];
        // $userCountry = $userData['ced_mp_name'];

        require_once __DIR__ . '/ced_amazon_core_functions.php';
        $user_data_response = getUserAccountMetaData($domain);
        $decodedUserData  = json_decode( $user_data_response, true );

        print_r($decodedUserData);
        if( ! $decodedUserData['status'] )
            return $user_data_response;
        
        $userData = $decodedUserData['data'];    
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

            $upload_dir = __DIR__ . '/uploads';

            $amazonCategoryList =  $amzonCurlRequestInstance->amazon_profile_template_data( $userCountry , $category_id , $sub_category_id );
            $valid_values       =  $amzonCurlRequestInstance->amazon_profile_valid_values_data( $userCountry , $category_id , $sub_category_id );

            $amazonCategoryList = json_decode( $amazon_profile_template_data, true );
            $valid_values       = json_decode( $amazon_profile_valid_values_data, true );

            if ( ! empty( $amazonCategoryList ) ) {


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


    /*
	*
	* Function to prepare profile fields section
	*/
	public function prepareProfileFieldsSection( $fieldsKey, $fieldsArray, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id ) {

		if ( ! empty( $fieldsArray ) ) {
			$profileSectionHtml = '';
			$optionalFields     = array();
			$display_heading    = 0;
			$html               = '';

			// $ced_amazon_general_options = get_option( 'ced_amazon_general_options', array() );
            $ced_amazon_general_options =  array();

			foreach ( $fieldsArray as $fieldsKey2 => $fieldsValue ) {

				if ( 'Mandantory' == $fieldsKey ) {

					$required = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? ' [' . ucfirst( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) . ']' : '';
					$req      = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? 'required' : '';

				} else {
					$required = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? ' [Suggested]' : '';
					$req      = '';

				}

				$globalValue = 'no';

				if ( ' [Required]' == $required || ' [Suggested]' == $required ) {

					if ( isset( $ced_amazon_general_options[ $fieldsKey2 ] ) && is_array( $ced_amazon_general_options[ $fieldsKey2 ] ) && ( '' !== $ced_amazon_general_options[ $fieldsKey2 ]['default'] || '' !== $ced_amazon_general_options[ $fieldsKey2 ]['metakey'] ) ) {
						// $required = '';
						$req            = '';
						$globalValue    = 'yes';
						$defaultGlobal  = $ced_amazon_general_options[ $fieldsKey2 ]['default'];
						$meta_keyGlobal = $ced_amazon_general_options[ $fieldsKey2 ]['metakey'];

					} else {
						$defaultGlobal  = '';
						$meta_keyGlobal = '';
					}

					$display_heading     = 1;
					// $prodileRowHTml      = $this->prepareProfileRows( $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $req, $required, $fieldsKey2, $fieldsValue, $globalValue, $defaultGlobal, $meta_keyGlobal, '' );
                    $prodileRowHTml      = '';
                    $profileSectionHtml .= $prodileRowHTml;

				} else {
					$optionalFields[ $fieldsKey ][ $fieldsKey2 ][] = $fieldsValue;
				}

			}

			return array(
				'html'            => $profileSectionHtml,
				'display_heading' => $display_heading,
				'optionsFields'   => $optionalFields,
			);

		}

	}


	/*
	*
	* Function to prepare profile rows
	*/

	public function prepareProfileRows( $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $req, $required, $fieldsKey2, $fieldsValue, $globalValue, $globalValueDefault, $globalValueMetakey, $cross="no" ) {

		global $wpdb;
		$results        = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
		$query          = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
		$addedMetaKeys  = get_option( 'CedUmbProfileSelectedMetaKeys', false );
					
		$rowHtml  = '';
		$rowHtml .= '<tr class="categoryAttributes" id="ced_amazon_categories" data-attr="' . $req . '">';

		if ( 'yes' == $display_saved_values ) {
			$req = '';
		}

		$row_label = $fieldsValue['label'] ;

		$index =  strpos( $fieldsKey2,"_custom_field");
		if( $index  > -1 ){
			$slug  =  substr( $fieldsKey2, 0, $index );
		} else{
			$slug = $fieldsKey2;
		}

		$rowHtml .= '<td>
		<label for="" class="tooltip">' . $row_label . '<span class="ced_amazon_wal_required">' . $required . '</span></label>
		<p class="cat_attr_para"> (' . $slug . ') </p></td>';

		if ( ! empty( $current_amazon_profile ) ) {
			$saved_value = json_decode( $current_amazon_profile['category_attributes_data'], true );
			$saved_value = $saved_value[ $fieldsKey2 ];
		} else {
			$saved_value = array();
		}

		
		$default_value = isset( $saved_value['default'] ) ? $saved_value['default'] : '';
		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';

		// test
		if ( empty( $default_value ) && 'yes' == $globalValue && empty( $template_id ) ) {
			$default_value = $globalValueDefault;
		}

		$rowHtml .= '<td>';
		if( 'yes' == $cross){
            $rowHtml .= '<input type="hidden" name="ced_amazon_profile_data[' . $slug . '_custom_field][label]" value="' . $row_label . '" >';
			
		} else{
			$rowHtml .= '<input type="hidden" name="ced_amazon_profile_data[ref_attribute_list][' . $fieldsKey2 . ']" />';

		}
		
		if ( ( isset( $valid_values[ $fieldsKey2 ] ) && isset( $valid_values[ $fieldsKey2 ][ $sub_category_id ] ) )  || ( isset( $valid_values[ $row_label ] ) && isset( $valid_values[ $row_label ][ $sub_category_id ] ) ) ) {
			// $rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" ' . $req . '><option value="">--Select--</option>';
			$rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]"><option value="">--Select--</option>';

			$optionLabels = !empty( $valid_values[ $fieldsKey2 ][ $sub_category_id ] ) ? $valid_values[ $fieldsKey2 ][ $sub_category_id ] : $valid_values[ $row_label ][ $sub_category_id ];
			
			foreach ( $optionLabels as $acpt_key => $acpt_value ) {
				$selected = '';
				if ( $acpt_key == $default_value ) {
					$selected = 'selected';
				}
				$rowHtml .= '<option value="' . $acpt_key . '"' . $selected . '>' . $acpt_value . '</option>';
			}

			$rowHtml .= '</select>';
		} elseif ( isset( $valid_values[ $fieldsKey2 ]['all_cat'] ) && ! empty( $valid_values[ $fieldsKey2 ]['all_cat'] ) && is_array( $valid_values[ $fieldsKey2 ]['all_cat'] ) ) {

			// $rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" ' . $req . '><option value="">--Select--</option>';
			$rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]"><option value="">--Select--</option>';

			foreach ( $valid_values[ $fieldsKey2 ]['all_cat'] as $acpt_key => $acpt_value ) {
				$selected = '';
				if ( $acpt_key == $default_value ) {
					$selected = 'selected';
				}
				$rowHtml .= '<option value="' . $acpt_key . '"' . $selected . '>' . $acpt_value . '</option>';
			}
			$rowHtml .= '</select>';

		} else {
			// $rowHtml .= '<input class="custom_category_attributes_input" value="' . $default_value . '" id="' . $fieldsKey2 . '" type="text" name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" ' . $req . ' />';
			$rowHtml .= '<input class="custom_category_attributes_input" value="' . $default_value . '" id="' . $fieldsKey2 . '" type="text" name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]" />';
		}

		$rowHtml .= '<span class="app ">
			<i class="with-tooltip fa fa-info-circle" data-tooltip-content="' . $fieldsValue['accepted_value'] . '"></i>
			</span> </td>';

		$rowHtml        .= '<td>';
		$selected_value2 = isset( $saved_value['metakey'] ) ? $saved_value['metakey'] : '';

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
		// test
		if ( empty( $selected_value2 ) && 'yes' == $globalValue && empty( $template_id ) ) {
			$selected_value2 = $globalValueMetakey;
		}

		//$selectDropdownHTML = '<select class="select2 custom_category_attributes_select"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][metakey]"  ' . $req . ' >';
		$selectDropdownHTML = '<select class="select2 custom_category_attributes_select"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][metakey]">';

		foreach ( $results as $key2 => $meta_key ) {
			$post_meta_keys[] = $meta_key['meta_key'];
		}

		$custom_prd_attrb = array();
		$attrOptions      = array();

		if ( ! empty( $query ) ) {
			foreach ( $query as $key3 => $db_attribute_pair ) {
				foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key4 => $attribute_pair ) {
					if ( 1 != $attribute_pair['is_taxonomy'] ) {
						$custom_prd_attrb[] = $attribute_pair['name'];
					}
				}
			}
		}

		if ( $addedMetaKeys && 0 < count( $addedMetaKeys ) ) {
			foreach ( $addedMetaKeys as $metaKey ) {
				$attrOptions[ $metaKey ] = $metaKey;
			}
		}

		$attributes = wc_get_attribute_taxonomies();

		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attributesObject ) {
				$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
			}
		}

		/* select dropdown setup */
		ob_start();
		$fieldID             = '{{*fieldID}}';
		$selectId            = $fieldID . '_attibuteMeta';
		$selectDropdownHTML .= '<option value=""> -- select -- </option>';

		if ( is_array( $attrOptions ) ) {
			$selectDropdownHTML .= '<optgroup label="Global Attributes">';
			foreach ( $attrOptions as $attrKey => $attrName ) {
				$selected = '';
				if ( $selected_value2 == $attrKey ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="' . $attrKey . '">' . $attrName . '</option>';
			}
		}

		if ( ! empty( $custom_prd_attrb ) ) {
			$custom_prd_attrb    = array_unique( $custom_prd_attrb );
			$selectDropdownHTML .= '<optgroup label="Custom Attributes">';

			foreach ( $custom_prd_attrb as $key5 => $custom_attrb ) {
				$selected = '';
				if ( 'ced_cstm_attrb_' . esc_attr( $custom_attrb ) == $selected_value2 ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';

			}
		}

		if ( ! empty( $post_meta_keys ) ) {
			$post_meta_keys      = array_unique( $post_meta_keys );
			$selectDropdownHTML .= '<optgroup label="Custom Fields">';
			foreach ( $post_meta_keys as $key7 => $p_meta_key ) {
				$selected = '';
				if ( $selected_value2 == $p_meta_key ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
			}
		}

		$selectDropdownHTML .= '</select>';
		if( 'yes' == $cross){
			$selectDropdownHTML .= '<i class="fa fa-times ced_amazon_remove_custom_row" ></i>';
		}
		$rowHtml            .= $selectDropdownHTML;
		$rowHtml            .= '</td>';
		$rowHtml            .= '</tr>';

		return $rowHtml;

	}

}

print_r($_POST);
$request_body = $_POST;
// ced_amazon_fetch_next_level_category($request_body);

$request_body = $_POST;
$instance = new CED_eBay_Category_Features();
$instance->ced_amazon_fetch_next_level_category( $request_body );


?>