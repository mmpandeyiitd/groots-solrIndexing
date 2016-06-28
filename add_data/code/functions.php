<?php

require_once('connection.php');

function getALLProductList() {
    $Arr = array();
    $sql = "SELECT sp.subscribed_product_id, sp.base_product_id, sp.store_id, sp.store_price, sp.store_offer_price, sp.weight, sp.weight_unit, sp.length, sp.length_unit, sp.width, sp.height, sp.status AS subscribed_product_status, sp.diameter, sp.grade, sp.created_date as created_at, sp.modified_date as modified_at, sp.quantity, sp.is_cod, sp.sku, bp.base_product_id, bp.title, bp.description, bp.color, bp.pack_size, bp.pack_unit, bp.minimum_order_quantity, bp.tags, bp.specofic_keys as specific_key, bp.size, bp.is_configurable, bp.configurable_with, bp.status AS base_product_status, st.store_id, st.store_name, st.store_details, st.store_logo, st.seller_name, st.email, st.business_address, st.business_address_country, st.business_address_state, st.business_address_city, st.business_address_pincode, st.mobile_numbers, st.telephone_numbers, st.created_date AS store_create_date, st.status AS store_status, sp.subscribe_shipping_charge, 0 AS store_tax_per FROM subscribed_product sp INNER JOIN base_product bp ON bp.base_product_id = sp.base_product_id INNER JOIN store st ON st.store_id = sp.store_id INNER JOIN solr_back_log sbl ON sbl.subscribed_product_id = sp.subscribed_product_id LIMIT 0 , 1000";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
        return $Arr;
    }
}

function getALLProductList_sp() {
    $Arr = array();
    $sql = "SELECT spsbl.id, rpq.id as uniq_id, rpq.retailer_id, rpq.subscribed_product_id, rpq.effective_price, rpq.discount_per, rpq.created_at, sp.store_offer_price FROM special_price_solr_back_log as spsbl LEFT JOIN retailer_product_quotation as rpq on spsbl.id = rpq.id LEFT JOIN subscribed_product as sp on rpq.subscribed_product_id = sp.subscribed_product_id LIMIT 0 , 1000";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
        return $Arr;
    }
}

function getCategoryByBaseProductId($id) {
    
    $Arr = array();
    $sql = "SELECT pcm.base_product_id,pcm.category_id,c.is_mega_category,c.category_name FROM product_category_mapping pcm INNER JOIN category c ON pcm.category_id = c.category_id WHERE pcm.base_product_id=$id";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
        return $Arr;
    }
}

function getStoreDetailByProductId($id) {
    $Arr = array();
    $sql = "SELECT store_front_id FROM store_front_products_mapping WHERE base_product_id=" . $id;
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
        return $Arr;
    }
}

function getsubscribedidbybaseid($id) {
    $Arr = array();
    $sql = "SELECT sp.subscribed_product_id,sp.status as sp_status,bp.status as bp_status,st.status as st_status FROM subscribed_product as sp LEFT JOIN base_product as bp on sp.base_product_id = bp.base_product_id LEFT JOIN store as st on sp.store_id = st.store_id WHERE sp.base_product_id IN (" . implode(',',$id) .") AND sp.status=1 AND bp.status = 1 AND st.status = 1";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
        return $Arr;
    }
}

function checkbasestatus($id) {
    $Arr = array();
    $sql = "SELECT sp.subscribed_product_id,sp.status as sp_status,bp.status as bp_status,st.status as st_status FROM subscribed_product as sp LEFT JOIN base_product as bp on sp.base_product_id = bp.base_product_id LEFT JOIN store as st on sp.store_id = st.store_id WHERE sp.subscribed_product_id =".$id." AND sp.status=1 AND bp.status = 1 AND st.status = 1";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        return true;
    } else {
        return false;
    }
}

function getProductImageByProductId($id) {
    $Arr = array();
    $sql = "select media_url, thumb_url, is_default from media where base_product_id=" . $id . " ORDER BY is_default DESC";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
        return $Arr;
    }
}

function getColorProductByProductId($store_id, $configurable_with) {
    $Arr = array();
    $sql = "SELECT sp.subscribed_product_id, sp.base_product_id, bp.color AS value FROM subscribed_product AS sp LEFT JOIN base_product AS bp ON sp.base_product_id = bp.base_product_id LEFT JOIN store as st on sp.store_id = st.store_id WHERE sp.store_id = " . $store_id . " AND sp.status = 1 AND bp.status = 1 AND st.status = 1 AND bp.base_product_id IN (" . $configurable_with . ") AND bp.color != ''";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
        return $Arr;
    }
}

function getSizeProductByProductId($store_id, $configurable_with) {
    $Arr = array();
    $sql = "SELECT sp.subscribed_product_id, sp.base_product_id, sp.quantity, bp.size AS value, bp.minimum_order_quantity, sp.store_price, sp.store_offer_price, bp.order_placement_cut_off_date, bp.delevry_date FROM subscribed_product AS sp LEFT JOIN base_product AS bp ON sp.base_product_id = bp.base_product_id LEFT JOIN store as st on sp.store_id = st.store_id WHERE sp.store_id = " . $store_id . " AND sp.status = 1 AND bp.status = 1 AND st.status = 1 AND bp.base_product_id IN (" . $configurable_with . ") AND bp.size != ''";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    if ($count) {
        while ($dataArr = mysql_fetch_assoc($result)) {
            //Add base product media url
            $mediaData = getProductImageByProductId($dataArr['base_product_id']);
            $default_media_thumb_url = '';
            if ($mediaData) {
                foreach ($mediaData as $media) {
                    $mediaUrl = $media['media_url'];
                    $thumbUrl = $media['thumb_url'];
                    if ($media['is_default'] == 1) {
                      $default_media_thumb_url = $media['thumb_url'];
                    } 
                    $dataArr['media_url'][] = BASEMEDIAURL . PRODUCTPATH . $mediaUrl;
                    $dataArr['thumb_url'][] = BASEMEDIAURL . THUMBPRODUCTPATH . $thumbUrl;
                }
            } else {
                $dataArr['media_url'][] = BASEMEDIAURL . PRODUCTPATH . "supp_noimage-pdp.jpg";
                $dataArr['thumb_url'][] = BASEMEDIAURL . THUMBPRODUCTPATH . "supp_noimage.jpg";
            }

            if (isset($dataArr['thumb_url']['0']) AND ! empty($dataArr['thumb_url']['0']) AND $default_media_thumb_url !='') {
                $dataArr['default_thumb_url'] = $dataArr['thumb_url']['0'];
            }
            
            $Arr[] = $dataArr;
        }
        return $Arr;
    } else {
		return $Arr;
    }
}

function DeleteSolrBackLog($deletedAllIds) {
    $sql = "delete from solr_back_log where subscribed_product_id in (" . trim($deletedAllIds, ",") . ")";
    $result = mysql_query($sql);
    if (!$result) {
        return('Could not delete data: ' . mysql_error());
    }
    return "Deleted data successfully\n";
}

function DeleteSolrBackLog_sp($deletedAllIds) {
    $sql = "delete from special_price_solr_back_log where id in (" . trim($deletedAllIds, ",") . ")";
    $result = mysql_query($sql);
    if (!$result) {
        return('Could not delete data: ' . mysql_error());
    }
    return "Deleted data successfully\n";
}

/**
 * 
 * Fetch category paths from category ids 
 * @param array $categoryIds
 */
function getCategoryPathData($categoryIds = null) {
	if (!is_array($categoryIds))
        return false;
    $categoryIds = array(max($categoryIds));
    //$mysql = new MYSQL();
    $categoryPathData = $categoryData = array();
    foreach ($categoryIds as $categoryId) {
        $sql = "SELECT * from category where category_id = $categoryId";
        $category = mysql_query($sql);
        $category = mysql_fetch_assoc($category);
        switch ($category['level']) {
            case 2:
                $categoryData[] = $category['category_id'] . ':' . $category['category_name'];
                break;
            case 3:
                $sql = "SELECT * from category where category_id = " . $category['parent_category_id'];
                $l1cat = mysql_query($sql);
                $l1cat = mysql_fetch_assoc($l1cat);
                $categoryData[] = $l1cat['category_id'] . ':' . $l1cat['category_name'];
                $categoryData[] = $category['category_id'] . ':' . $category['category_name'];
                break;
            case 4:
                $sql = "SELECT * from category where category_id = " . $category['parent_category_id'];
                $l2cat = mysql_query($sql);
                $l2cat = mysql_fetch_assoc($l2cat);

                $sql = "SELECT * from category where category_id = " . $l2cat['parent_category_id'];

                $l1cat = mysql_query($sql);
                $l1cat = mysql_fetch_assoc($l1cat);
                $categoryData[] = $l1cat['category_id'] . ':' . $l1cat['category_name'];
                $categoryData[] = $l2cat['category_id'] . ':' . $l2cat['category_name'];
                $categoryData[] = $category['category_id'] . ':' . $category['category_name'];

                break;
            case 5:
                $sql = "SELECT * from category where category_id = " . $category['parent_category_id'];
                $l3cat = mysql_query($sql);
                $l3cat = mysql_fetch_assoc($l3cat);
                $sql = "SELECT * from category where category_id = " . $l3cat['parent_category_id'];
                $l2cat = mysql_query($sql);
                mysql_fetch_assoc($l2cat);
                $sql = "SELECT * from category where category_id = " . $l2cat['parent_category_id'];
                $l1cat = mysql_query($sql);
                $l1cat = mysql_fetch_assoc($l1cat);
                $categoryData[] = $l1cat['category_id'] . ':' . $l1cat['category_name'];
                $categoryData[] = $l2cat['category_id'] . ':' . $l2cat['category_name'];
                $categoryData[] = $l3cat['category_id'] . ':' . $l3cat['category_name'];
                $categoryData[] = $category['category_id'] . ':' . $category['category_name'];
                break;
            case 6:
                $sql = "SELECT * from category where category_id = " . $category['parent_category_id'];
                $l4cat = mysql_query($sql);
                $l4cat = mysql_fetch_assoc($l4cat);
                $sql = "SELECT * from category where category_id = " . $l4cat['parent_category_id'];
                $l3cat = mysql_query($sql);
                $l3cat = mysql_fetch_assoc($l3cat);
                $sql = "SELECT * from category where category_id = " . $l3cat['parent_category_id'];
                $l2cat = mysql_query($sql);
                $l2cat = mysql_fetch_assoc($l2cat);
                $sql = "SELECT * from category where category_id = " . $l2cat['parent_category_id'];
                $l1cat = $mysql->fetch($sql);
                $l1cat = mysql_fetch_assoc($l1cat);
                $categoryData[] = $l1cat['category_id'] . ':' . $l1cat['category_name'];
                $categoryData[] = $l2cat['category_id'] . ':' . $l2cat['category_name'];
                $categoryData[] = $l3cat['category_id'] . ':' . $l3cat['category_name'];
                $categoryData[] = $l4cat['category_id'] . ':' . $l4cat['category_name'];
                $categoryData[] = $category['category_id'] . ':' . $category['category_name'];
                break;
            default:
                break;
        }
        $categoryPathData[] = implode('|', $categoryData);
    }
    return array_values(array_unique($categoryPathData));
}

function rest_helper($url, $params = null,$cacheControl=0, $verb = 'POST', $format = 'json')
{
    $cparams = array(
    'http' => array(
    'method' => $verb,
    'ignore_errors' => true
    )
    );
    $cparams['http']['header'] ="api_key: ecomadmin\r\napi_password: ecompasswords\r\n";  
    if ($params !== null) {
        $params = http_build_query($params,null,'&');

        if ($verb == 'POST') {
            $cparams['http']['content'] = $params;
        } else {
            $url .= '?' . $params;
        }
    }
    $context = stream_context_create($cparams);
    $fp = fopen($url, 'rb', false, $context);
    if (!$fp) {
        $res = false;
    } else {

        $res = stream_get_contents($fp);
    }

    if ($res === false) {
        throw new Exception("$verb $url failed: $php_errormsg");
    }

    switch ($format) {
        case 'json':
            $r = $res;
            if ($r === null) {
                throw new Exception("failed to decode $res as json");
            }
            return $r;

        case 'xml':
            $r = simplexml_load_string($res);
            if ($r === null) {
                throw new Exception("failed to decode $res as xml");
            }
            return $r;
    }
    return $res;
}

function getProductDetails($product_id)
{
    $params = array('filter' => $product_id);
    $obj = rest_helper(getProductDetailUrl(),$params,0,'GET'
    );

    if($obj)
    {
        
        $data = json_decode($obj) ;
        return $data->response->response->docs;
    }
    else
    {
        return false ;
    }
}

function getProductDetailUrl(/*$product_id*/)
{
    return SOLRAPIURL.'product/productList';

    /*return $GLOBALS['url']."index.php?page_type=product&action_type=product_details&product_id=".$product_id ;*/
}

?>
