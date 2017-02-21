<?php

require_once('functions.php');
require_once('constants.php');
require_once('solr.php');

try {
    echo "\nSolr reindex log script started : " . date("Y-m-d H:i:s");
    $deletedAllIds = '';
    $updatedData = $deletedData = array();
    $allData = getALLProductList();
    $sampleData = getSampleProductPriceRange();
    //echo "<pre>";
    //print_r($sampleData);
    if ($allData) {
        foreach ($allData as $key => $value) {
            $deletedAllIds .= $value['subscribed_product_id'] . ",";
            if ($value['subscribed_product_status'] == 1 && $value['base_product_status'] == 1 && $value['store_status'] == 1)
            {
                $categoryData = getCategoryByBaseProductId($value['base_product_id']);
                $categories = array();
                $categories_name = array();
                $i = 0;
                if ($categoryData) {
                    foreach ($categoryData as $category) {
						$categories[] = $category['category_id'];
						$categories_name[] = $category['category_name'];
                    }
                }
                
                if ($categories) {
                    $allData[$key]['categories'] = $categories;
                    $allData[$key]['categories_name'] = $categories_name;
                    $allData[$key]['category_paths'] = getCategoryPathData($categories);
                }

                //Add base product media url
                $mediaData = getProductImageByProductId($value['base_product_id']);
                $default_media_thumb_url = '';
                if ($mediaData) {
                    foreach ($mediaData as $media) {
                        $mediaUrl = $media['media_url'];
                        $thumbUrl = $media['thumb_url'];
                        if ($media['is_default'] == 1) {
                          $default_media_thumb_url = $media['thumb_url'];
                        } 
                        $allData[$key]['media_url'][] = BASEMEDIAURL . PRODUCTPATH . $mediaUrl;
                        $allData[$key]['thumb_url'][] = BASEMEDIAURL . THUMBPRODUCTPATH . $thumbUrl;
                    }
                } else {
                    $allData[$key]['media_url'][] = BASEMEDIAURL . PRODUCTPATH . "noimage-pdp.jpg";
                    $allData[$key]['thumb_url'][] = BASEMEDIAURL . THUMBPRODUCTPATH . "noimage.jpg";
                }
                
                if (isset($allData[$key]['thumb_url']['0']) AND ! empty($allData[$key]['thumb_url']['0']) AND $default_media_thumb_url !='') {
                    $allData[$key]['default_thumb_url'] = $allData[$key]['thumb_url']['0'];
                }

                //Store front checkout url
                $allData[$key]['checkout_url'] = STOREFRONT_CHECKOUT_URL . base64_encode($value['subscribed_product_id']);

                //Add store front ids
                $storefrontData = getStoreDetailByProductId($value['base_product_id']);

                $storefronts = array();
                if (!empty($storefrontData)) {
                    foreach ($storefrontData as $storefront) {
                        $storefronts[] = $storefront['store_front_id'];
                    }
                }
                if (!empty($storefronts)){
                    $allData[$key]['store_front_id'] = $storefronts;
				}
				
                if(!empty($value['is_configurable']) && $value['is_configurable'] == 1)
                {
                        if (!empty($value['configurable_with'])) {
                                $allData[$key]['configurable_with'] = $color = $size = null;
                                if (!empty($value['color'])) {
                                        $color = getColorProductByProductId($value['store_id'], $value['configurable_with']);
                                }
                                if (!empty($value['size'])) {
                                        $size = getSizeProductByProductId($value['store_id'], $value['configurable_with']);
                                }

                                if (!empty($color) OR ! empty($size)) {
                                        $allData[$key]['configurable_with'] = json_encode(array('color' => $color, 'size' => $size));
                                }
                        }
                }
                else
                {
                        $allData[$key]['configurable_with'] = $color = $size = null;
                }
                if($value['is_sample'] == 1){
                    if(isset($sampleData[$value['base_product_id']])){
                        $allData[$key]['min_price'] = $sampleData[$value['base_product_id']]['min_price'];
                        $allData[$key]['max_price'] = $sampleData[$value['base_product_id']]['max_price'];
                    }
                }
                $allData[$key]['subscribed_product_id'] = $value['subscribed_product_id'];
                $allData[$key]['id'] = $value['subscribed_product_id'];
                $updatedData[] = $value['subscribed_product_id'];
            } else {
                $deletedData[] = $value['subscribed_product_id'];
            }
        }
        $solr = new SOLR();
        //print_r($allData);die;
        if ($allData) {
            echo "\nUpdated subscribed product Ids : " . implode("\n", $updatedData);
            $solr->saveEntityIndexes($allData);
        }
        if ($deletedData) {
            echo "\nDeleted subscribed product Ids : " . implode("\n", $deletedData);
            $solr->deleteDocs($deletedData);
        }
        if ($allData) {
            $queryDeleteIds = DeleteSolrBackLog($deletedAllIds);
            echo '\n'.$queryDeleteIds;
        }
    }

    $deletedAllIds = '';
    $all_ids = $delete_ids = $update_ids = array();
    $sp_allData = getALLProductList_sp();
    if ($sp_allData) 
    {
        foreach ($sp_allData as $key => $value) 
        {
            $deletedAllIds .= $value['id'] . ",";
            if($value['uniq_id'] == null || $value['uniq_id'] == '')
            {
                $delete_ids[] = $value['id'];
                unset($sp_allData[$key]);
            }
            else
            {
                if($value['discount_per'] > 0)
                {
                    $effective_price = $value['store_offer_price'] - ($value['store_offer_price'] * $value['discount_per']/100);
                }
                else
                {
                    $effective_price = $value['effective_price'];
                }
                $sp_allData[$key]['store_offer_price'] = $effective_price;
                $update_ids[] = $value['id'];
                unset($sp_allData[$key]['id']);
                unset($sp_allData[$key]['effective_price']);
                unset($sp_allData[$key]['discount_per']);
            }
        }
        $solr = new SOLR();
        if($sp_allData)
        {
            echo "\nUpdated Ids in special price table: " . implode("\n", $update_ids);
            $solr->saveEntityIndexes_sp($sp_allData);
        }
        if ($delete_ids) {
            echo "\nDeleted Ids: " . implode("\n", $delete_ids);
            $solr->deleteDocs_sp($delete_ids);
        }
        if ($sp_allData || $delete_ids) {
            $queryDeleteIds = DeleteSolrBackLog_sp($deletedAllIds);
            echo '\n'.$queryDeleteIds;
        }

    }

    echo "\nSolr reindex log script ended : " . date("Y-m-d H:i:s");
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
