<?php                       
class PRODUCT extends REST 
{    
    public function productList($params) {
        $db     = new MYSQL('r');
        $object = new FUNCTIONS();
        $sql="Select retailer_id from retailer_product_quotation where retailer_id ='".$params['filter']['retailer_id']."' LIMIT 0,1";
        $count=$db->count($sql);

        if($count == 1)
        {
            $result = $object->productList_specialPrice($params);
            if(count($result['response']['response']['docs']) > 0)
            {
                $product_arr = array();
                foreach($result['response']['response']['docs'] as $val)
                {
                    $product_arr[$val['subscribed_product_id']] = $val['store_offer_price'];
                }
                $params_new = array();
                $params_new['filter']['subscribed_product_id'] = array_keys($product_arr);
                $result_new = $object->productList($params_new);
                foreach($result_new['response']['response']['docs'] as $key => $val)
                {
                    $result_new['response']['response']['docs'][$key]['store_offer_price'] = $product_arr[$val['subscribed_product_id']];
                }
            }
            else
            {
                return 0;
            }
        }
        else
        {
            unset($params['filter']['retailer_id']);
            $result_new = $object->productList($params);
        }
        
        return $this->json($result_new);
    }

    public function searchList($params) {
        $object = new FUNCTIONS();
        $result = $object->searchList($params);
        return $this->json($result);
    }
}
