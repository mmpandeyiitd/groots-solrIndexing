<?php

require_once('Apache/Solr/Document.php');
require_once('Apache/Solr/Service.php');

class SOLR {

    public function saveEntityIndexes($entityIndexes) {
        $solrObj = new Apache_Solr_Service(SOLRHOST, SOLRPORT, SOLRNAME);

        if (!$solrObj->ping()) {
            echo 'Solr service not responding for user Detail.';
            exit;
        }

        $documents = array();
        $arrCategories = array();
        $arrBaseProductids = array();

        foreach ($entityIndexes as $key => $index) {

            $index['description'] = (isset($index['description'])) ? $index['description'] : '';
            $index['store_price'] = (isset($index['store_price'])) ? $index['store_price'] : 0;
			
            $document = new Apache_Solr_Document();

            $document->subscribed_product_id = (int) $index['subscribed_product_id'];
            try {
                $solrObj->deleteById($index['subscribed_product_id']);
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
            $document->id = $index['subscribed_product_id'];
            $document->base_product_id = $index['base_product_id'];
            $document->store_id = $index['store_id'];
            $document->store_price = $index['store_price'];
            $document->store_offer_price = (isset($index['store_offer_price'])) ? round($index['store_offer_price']) : 0;
			$document->subscribed_shipping_charges = (isset($index['subscribe_shipping_charge'])) ? $index['subscribe_shipping_charge'] : 0;
            $document->is_cod = (isset($index['is_cod'])) ? $index['is_cod'] : 0;
            $document->weight = (isset($index['weight'])) ? $index['weight'] : 0;
            $document->weight_unit = (isset($index['weight_unit'])) ? $index['weight_unit'] : '';
            $document->length = (isset($index['length'])) ? $index['length'] : 0;
            $document->length_unit = (isset($index['length_unit'])) ? $index['length_unit'] : '';
            $document->width = (isset($index['width'])) ? $index['width'] : 0;
            $document->height = (isset($index['height'])) ? $index['height'] : 0;
            $document->diameter = (isset($index['diameter'])) ? $index['diameter'] : 0;
            $document->grade = (isset($index['grade'])) ? $index['grade'] : '';
            $document->pack_size = (isset($index['pack_size'])) ? $index['pack_size'] : 0;
            $document->pack_unit = (isset($index['pack_unit'])) ? $index['pack_unit'] : '';
            $document->min_order_qunatity = (isset($index['min_order_qunatity'])) ? (int)$index['min_order_qunatity'] : 0;
            $document->tags = (isset($index['tags'])) ? $index['tags'] : '';
            $document->specific_key = (isset($index['specific_key'])) ? $index['specific_key'] : '';
            $document->sku = (isset($index['sku'])) ? $index['sku'] : '';
            $document->created_at = gmdate('Y-m-d\TH:i:s\Z', strtotime($index['created_date']));
            $document->modified_at = gmdate('Y-m-d\TH:i:s\Z', strtotime($index['modified_date']));
            $document->quantity = $index['quantity'];
            $document->store_create_date = gmdate('Y-m-d\TH:i:s\Z', strtotime($index['store_create_date']));
            $document->title = (isset($index['title'])) ? utf8_encode(trim($index['title'])) : '';
            
            /* fix for json blank value: encoding for description */
            $description = utf8_encode($index['description']);
            $document->description = (isset($index['description'])) ? $description : '';
            $document->color = (isset($index['color'])) ? $index['color'] : '';
            $document->size = (isset($index['size'])) ? $index['size'] : '';
            $document->is_configurable = $index['is_configurable'] ? true : false;
            $document->configurable_with = (isset($index['configurable_with'])) ? $index['configurable_with'] : '';
            $document->store_name = (isset($index['store_name'])) ? $index['store_name'] : '';
            $document->email = (isset($index['email'])) ? $index['email'] : '';
            $document->store_details = (isset($index['store_details'])) ? $index['store_details'] : '';
            $document->store_logo = !empty($index['store_logo']) ? BASEMEDIAURL . $index['store_logo'] : '';
            $document->seller_name = (isset($index['seller_name'])) ? $index['seller_name'] : '';
            $document->business_address = (isset($index['business_address'])) ? $index['business_address'] : '';

            /* fix for separate fields for business address country,state,city and pincode */
            $document->business_address_country = (isset($index['business_address_country'])) ? $index['business_address_country'] : '';
            $document->business_address_state = (isset($index['business_address_state'])) ? $index['business_address_state'] : '';
            $document->business_address_city = (isset($index['business_address_city'])) ? $index['business_address_city'] : '';
            $document->business_address_pincode = (isset($index['business_address_pincode'])) ? $index['business_address_pincode'] : '';
            $document->mobile_numbers = (isset($index['mobile_numbers'])) ? $index['mobile_numbers'] : '';
            $document->telephone_numbers = (isset($index['telephone_numbers'])) ? $index['telephone_numbers'] : '';
            $document->category_id = (isset($index['categories'])) ? $index['categories'] : 0;
            $document->category_name = (isset($index['categories_name'])) ? $index['categories_name'] : 0;
            $document->category_paths = (isset($index['category_paths'])) ? $index['category_paths'] : 0;
            $document->store_front_id = (isset($index['store_front_id'])) ? $index['store_front_id'] : 0;
            $document->media_url = (isset($index['media_url'])) ? $index['media_url'] : array();
            $document->thumb_url = (isset($index['thumb_url'])) ? $index['thumb_url'] : array();
            $document->default_thumb_url = (isset($index['default_thumb_url'])) ? $index['default_thumb_url'] : '';
            $document->checkout_url = (isset($index['checkout_url'])) ? $index['checkout_url'] : '';
            $documents[] = $document;
        }
        try {
            $solrObj->addDocuments($documents);
            $solrObj->commit();
            $solrObj->optimize();
        } catch (Exception $ex) {
            $url = SOLRURL . "admin/cores?action=RELOAD&core=collection_groots_prod";
            //echo $url;
            $this->httpGet($url);
            if ($ex->getMessage() != '"400" Status: Bad Request') {
                echo $ex->getMessage();
            }
        }
    }

    /**
     * Rollbacks all add/deletes made to the index since the last commit
     *
     * @return object
     */
    public function rollback() {
        $service = new Apache_Solr_Service();
        return $service->rollback();
    }

    /**
     * Remove documents from Solr index
     *
     * @param int|string|array $docIDs
     * @param string|array $queries if "all" specified and $docIDs are empty, then all documents will be removed
     * @return unknown
     */
    public function deleteDocs($docIDs = array(), $queries = null) {
        $_deleteBySuffix = 'MultipleIds';
        $params = array();
        $solrObj = new Apache_Solr_Service(SOLRHOST, SOLRPORT, SOLRNAME);
        if (!empty($docIDs)) {
            if (!is_array($docIDs)) {
                $docIDs = array($docIDs);
            }
            $params = $docIDs;
        } elseif (!empty($queries)) {
            if ($queries == 'all') {
                $queries = array('*:*');
            }
            if (!is_array($queries)) {
                $queries = array($queries);
            }
            $_deleteBySuffix = 'Queries';
            $params = $queries;
        }
        if ($params) {
            $deleteMethod = sprintf('deleteBy%s', $_deleteBySuffix);
            try {
                $response = $solrObj->$deleteMethod($params);
                $solrObj->commit();
                $solrObj->optimize();
            } catch (Exception $ex) {
                $url = SOLRURL . "admin/cores?action=RELOAD&core=collection_groots_prod";
                $this->httpGet($url);
                if ($ex->getMessage() != '"400" Status: Bad Request') {
                    echo $ex->getMessage();
                }
                //echo $ex->getMessage();
            }
//            try {
//                $service = new Apache_Solr_Service();
//                $response = $service->$deleteMethod($params);
//                $service->commit();
//            } catch (Exception $e) {
//                $this->rollback();
//            }
        }

        return $this;
    }

    public function httpGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function saveEntityIndexes_sp($entityIndexes) {
        $solrObj = new Apache_Solr_Service(SOLRHOST, SOLRPORT, SOLRNAMESP);

        if (!$solrObj->ping()) {
            echo 'Solr service not responding for user Detail.';
            exit;
        }

        $documents = array();
        $arrCategories = array();
        $arrBaseProductids = array();

        foreach ($entityIndexes as $key => $index) {

            $document = new Apache_Solr_Document();

            $document->uniq_id = (int) $index['uniq_id'];
            try {
                $solrObj->deleteById($index['uniq_id']);
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
            $document->id = $index['uniq_id'];
            $document->subscribed_product_id = (int)$index['subscribed_product_id'];
            $document->retailer_id = (int)$index['retailer_id']; 
            $document->store_offer_price = (isset($index['store_offer_price'])) ? $index['store_offer_price'] : 0;
            $document->created_at = gmdate('Y-m-d\TH:i:s\Z', strtotime($index['created_date']));
            $documents[] = $document;
        }
        try {
            $solrObj->addDocuments($documents);
            $solrObj->commit();
            $solrObj->optimize();
        } catch (Exception $ex) {
            $url = SOLRURL . "admin/cores?action=RELOAD&core=collection_groots_special_price";
            //echo $url;
            $this->httpGet($url);
            if ($ex->getMessage() != '"400" Status: Bad Request') {
                echo $ex->getMessage();
            }
        }
    }

    /**
     * Remove documents from Solr index
     *
     * @param int|string|array $docIDs
     * @param string|array $queries if "all" specified and $docIDs are empty, then all documents will be removed
     * @return unknown
     */
    public function deleteDocs_sp($docIDs = array(), $queries = null) {
        $_deleteBySuffix = 'MultipleIds';
        $params = array();
        $solrObj = new Apache_Solr_Service(SOLRHOST, SOLRPORT, SOLRNAMESP);
        if (!empty($docIDs)) {
            if (!is_array($docIDs)) {
                $docIDs = array($docIDs);
            }
            $params = $docIDs;
        } elseif (!empty($queries)) {
            if ($queries == 'all') {
                $queries = array('*:*');
            }
            if (!is_array($queries)) {
                $queries = array($queries);
            }
            $_deleteBySuffix = 'Queries';
            $params = $queries;
        }
        if ($params) {
            $deleteMethod = sprintf('deleteBy%s', $_deleteBySuffix);
            try {
                $response = $solrObj->$deleteMethod($params);
                $solrObj->commit();
                $solrObj->optimize();
            } catch (Exception $ex) {
                $url = SOLRURL . "admin/cores?action=RELOAD&core=collection_groots_special_price";
                $this->httpGet($url);
                if ($ex->getMessage() != '"400" Status: Bad Request') {
                    echo $ex->getMessage();
                }
            }
        }

        return $this;
    }

}
