<?php
require_once ("config/config.php");

class FUNCTIONS{
     
    public function productList($params) { 
        try {
            $url = CONFIG::SOLR_URL;
            $query_condition = "*:*";
            $facets_condition = "*:*";
            $parameters = "*";
            $start = 0;
            $rows = 10;
            if (!empty($params['filter'])) {
                $filter = $params['filter'];
                $query_condition_res = $this->searching($filter);

                if ($query_condition_res['status'] == 0) {
                    return $query_condition_res;
                } else {
                    $query_condition = $query_condition_res['query_condition'];
                }
            }

            if (!empty($params['q'])) {
                if ($query_condition == "*:*") {
                    $query_condition = '';
                }
                $query_condition = $this->default_Search($params['q'], $query_condition);
            }
            
             if (!empty($params['pricefilter']['price_to']) && !empty($params['pricefilter']['price_from'])) {
                if ($query_condition == "*:*") {
                    $query_condition = '';
                }
                if (!empty($query_condition)) {
                    $query_condition.= " AND ";
                }
                $query_condition.="store_offer_price:[" . $params['pricefilter']['price_to'] . " TO " . $params['pricefilter']['price_from'] . "]";
            }
            
            if (!empty($params['facetsfilter'])) {
                $filter = $params['facetsfilter'];
                $query_condition_res = $this->facetsfilter($filter);
                if ($query_condition_res['status'] == 0) {
                    return $query_condition_res;
                } else {
                    $facets_condition = $query_condition_res['query_condition'];
                }
            }
            if (!empty($params['min']) && $params['min'] = 1) {
                $parameters = CONFIG::SOLR_MIN_PARAM;
            } else if (!empty($params['micro']) && $params['micro'] = 1) {
                $parameters = CONFIG::SOLR_MICRO_PARAM;
            } else if(!empty($params['auto_suggest']) && $params['auto_suggest'] = 1) {
                $parameters = CONFIG::SOLR_AUTO_SUGGEST_PARAM;
            }
            $facets_fields = $this->facets();
            $stats_fields = $this->stats();
            $sort_fileds = '';
            if (!empty($params['sort'])) {
                //$sort_fileds = $this->sorting($params['sort']);
                $sort_fileds = 'title asc';
            } else {
                $sort_fileds = 'title asc';
            }
            if (!empty($params['limit'])) {
                $rows = $params['limit'];
            }
            if (!empty($params['page'])) {
                $value = $params['page'];
                if ($value == "1") {
                    $start = (floatval($value) - 1) * floatval($rows);
                } else {
                    $start = ((floatval($value) - 1) * floatval($rows));
                }
            }
          
            //Complete Solr Url With Parameter
            $url = $url . "select?q=" . urlencode($query_condition) . "&fq=" . urlencode($facets_condition) . "&start=" . $start . "&rows=" . $rows . "&fl=" . $parameters . "&sort=" . urlencode($sort_fileds) . "&facet=true" . $facets_fields . "&stats=true" . $stats_fields . "&wt=json&indent=true";
            $product_list = $this->httpGet($url);

            $result['status'] = "Success";
            $result['msg'] = "Product List";
            $result['response'] = json_decode($product_list, true);
            return $result;
        } catch (Exception $ex) {
            $result['status'] = "Fail";
            $result['errors'] = $ex->getMessage();
            return $result;
        }
    }

    public function productList_specialPrice($params) { //Used in the booking Engine
        try {
            $url = CONFIG::SOLR_URL_SPECIAL_PRICE;
            $query_condition = "*:*";
            $facets_condition = "*:*";
            $parameters = "*";
            $start = 0;
            $rows = 10;
            if (!empty($params['filter'])) {
                $filter = $params['filter'];
                $query_condition_res = $this->searching($filter,1);

                if ($query_condition_res['status'] == 0) {
                    return $query_condition_res;
                } else {
                    $query_condition = $query_condition_res['query_condition'];
                }
            }

             if (!empty($params['pricefilter']['price_to']) && !empty($params['pricefilter']['price_from'])) {
                if ($query_condition == "*:*") {
                    $query_condition = '';
                }
                if (!empty($query_condition)) {
                    $query_condition.= " AND ";
                }
                $query_condition.="store_offer_price:[" . $params['pricefilter']['price_to'] . " TO " . $params['pricefilter']['price_from'] . "]";
            }
            
            $sort_fileds = '';
            if (!empty($params['sort'])) {
                //$sort_fileds = $this->sorting($params['sort']);
                $sort_fileds = 'title asc';
            } else {
                //$sort_fileds = 'subscribed_product_id asc';
                $sort_fileds = 'title asc';
            }
            if (!empty($params['limit'])) {
                $rows = $params['limit'];
            }
            if (!empty($params['page'])) {
                $value = $params['page'];
                if ($value == "1") {
                    $start = (floatval($value) - 1) * floatval($rows);
                } else {
                    $start = ((floatval($value) - 1) * floatval($rows));
                }
            }
          
            //Complete Solr Url With Parameter
            $url = $url . "select?q=" . urlencode($query_condition) . "&fq=" . urlencode($facets_condition) . "&start=" . $start . "&rows=" . $rows . "&fl=" . $parameters . "&sort=" . urlencode($sort_fileds) . "&wt=json&indent=true";

            $product_list = $this->httpGet($url);

            $result['status'] = "Success";
            $result['msg'] = "Product List";
            $result['response'] = json_decode($product_list, true);
            return $result;
        } catch (Exception $ex) {
            $result['status'] = "Fail";
            $result['errors'] = $ex->getMessage();
            return $result;
        }
    }

    public function searchList($params) { //Used in the booking Engine
        try {
            $url = CONFIG::SOLR_URL;
            $query_condition = "*:*";
            $parameters = CONFIG::SOLR_AUTO_SUGGEST_PARAM;
            $start = 0;
            $rows = 10;
            $sort_fileds = 'title asc';
            if (!empty($params['q'])) {
                if ($query_condition == "*:*") {
                    $query_condition = '';
                }
                $query_condition = $this->default_Search($params['q'], $query_condition);
            }
            //Complete Solr Url With Parameter
            $url = $url . "select?q=" . urlencode($query_condition) . "&start=" . $start . "&rows=" . $rows . "&fl=" . $parameters . "&group=true&group.field=title".urlencode($sort_fileds) ."&group.format=simple&wt=json&indent=true";

            $product_list = $this->httpGet($url);

            $result['status'] = "Success";
            $result['msg'] = "Product List";
            $result['response'] = json_decode($product_list, true);
            return $result;
        } catch (Exception $ex) {
            $result['status'] = "Fail";
            $result['errors'] = $ex->getMessage();
            return $result;
        }
    }

    public static function httpGet($url) {
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

    Public function searching($filter_new,$sp = 0) {
        if($sp == 1)
        {
            $filter['retailer_id'] = $filter_new['retailer_id'];
            if(isset($filter_new['subscribed_product_id'])){
                $filter['subscribed_product_id'] = $filter_new['subscribed_product_id'];
            }
            $searchParam = CONFIG::$SOLR_SEARCH_PARAM_SPECIAL_PRICE;
        }
        else
        {
            $filter = $filter_new;
            $searchParam = CONFIG::$SOLR_SEARCH_PARAM;    
        }
        $arr = array_keys($searchParam);

        $paramNotFound = array();
        $query_condition = '';
        foreach ($filter as $key => $value) {
            if (!in_array($key, $arr)) {
                $paramNotFound[] = $key;
                break;
            } else {
                if ($searchParam[$key] == 'int') {
                    if (!empty($query_condition)) {
                        $query_condition.= " AND ";
                    }
                    if (is_array($value)) {
                        $value = array_map('trim', $value);
                        $search_param = implode(",", $value);
                        $search_param = str_replace(' ', '\ ', $search_param);
                        $query_condition.= "(" . $key . " : " . str_replace(",", " OR " . $key . " : ", $search_param) . ") ";
                    } else {
                        $query_condition.= "(" . $key . " : " . trim($value) . ") ";
                    }
                } else {
                    if (!empty($key)) {
                        if (!empty($query_condition)) {
                            $query_condition.= " AND ";
                        }
                        $query_condition.= "(" . $key . " : *" . trim($value) . "*) ";
                    }
                }
            }
        }
        if (!empty($paramNotFound)) {
            $result['status'] = 0;
             $result['msg'] = "Invalid Searching Parameter";
        } else {
            $result['status'] = 1;
            $result['query_condition'] = $query_condition;
        }
        return $result;
    }

    Public function default_Search($default_field, $query_condition) {
        $defaultParam = CONFIG::$SOLR_DEFAULT_PARAM;
        if (!empty($default_field)) {
            if (!empty($query_condition)) {
                $query_condition.= " AND ";
            }
            $default_field = trim($default_field);
            $default_field = str_replace(' ', '?', $default_field);
            $query_condition.= "( ";
            $count = count($defaultParam);
            for ($i = 0; $i < $count; $i++) {
                if ($i != 0) {
                    $query_condition.= " OR ";
                }
                $query_condition.= $defaultParam[$i] . " : *" . trim($default_field) . "*";
            }
            $query_condition.= ") ";
        }

        return $query_condition;
    }

    Public function facets() {
        $facetParam = CONFIG::$SOLR_FACETS_PARAM;
        $facet_condition = '';
        foreach ($facetParam as $key => $value) {
            $facet_condition.='&facet.field=' . $value;
        }
        return $facet_condition;
    }

    Public function stats() {
        $statsParam = CONFIG::$SOLR_STATS_PARAM;
        $stats_condition = '';
        foreach ($statsParam as $key => $value) {
            $stats_condition.='&stats.field=' . $value;
        }
        return $stats_condition;
    }

    Public function sorting($sortingParam) {
        $sort_condition = '';
        foreach ($sortingParam as $key => $value) {
            $sort_condition.=$key . " " . $value . ",";
        }
        $sort_condition = rtrim($sort_condition, ',');
        return $sort_condition;
    }

    Public function facetsfilter($filter) {
        $searchParam = CONFIG::$SOLR_FACETS_PARAM;
        $arr = array_values($searchParam);
        $paramNotFound = array();
        $query_condition = '';
        foreach ($filter as $key => $value) {
            if (!in_array($key, $arr)) {
                $paramNotFound[] = $key;
                break;
            } else {
                if (!empty($query_condition)) {
                    $query_condition.= " AND ";
                }
                if (is_array($value)) {
                    $value = array_map('trim', $value);
                    $search_param = implode(",", $value);
                    $search_param = str_replace(' ', '\ ', $search_param);
                    $query_condition.= "(" . $key . " : " . str_replace(",", " OR " . $key . " : ", $search_param) . ") ";
                } else {
                    $query_condition.= "(" . $key . " : " . trim($value) . ") ";
                }
            }
        }
        if (!empty($paramNotFound)) {
            $result['status'] = 0;
            $result['msg'] = "Invalid Facets Filter Parameter";
        } else {
            $result['status'] = 1;
            $result['query_condition'] = $query_condition;
        }
        return $result;
    }

    public function storeList($params) { //Used in the booking Engine
        try {
            $url = CONFIG::SOLR_URL;
            $query_condition = "*:*";
            $facets_condition = "*:*";
            $parameters = "*";
            $start = 0;
            $rows = 10;
            if (!empty($params['filter'])) {
                $filter = $params['filter'];
                $query_condition_res = $this->searching($filter);

                if ($query_condition_res['status'] == 0) {
                    return $query_condition_res;
                } else {
                    $query_condition = $query_condition_res['query_condition'];
                }
            }
            $parameters = CONFIG::SOLR_STORE_PARAM;
            $facets_fields = $this->facets();
            $group_field   = '&group.field=store_id';
            $sort_fileds = '';
            $sort_fileds = 'title asc';
            if (!empty($params['limit'])) {
                $rows = $params['limit'];
            }
            if (!empty($params['page'])) {
                $value = $params['page'];
                if ($value == "1") {
                    $start = (floatval($value) - 1) * floatval($rows);
                } else {
                    $start = ((floatval($value) - 1) * floatval($rows));
                }
            }
          
            //Complete Solr Url With Parameter
            $url = $url . "select?q=" . urlencode($query_condition) . "&fq=" . urlencode($facets_condition) . "&start=" . $start . "&rows=" . $rows . "&fl=" . $parameters . "&sort=" . urlencode($sort_fileds) . "&facet=true" . $facets_fields . "&group=true" . $group_field . "&wt=json&indent=true";

            $store_list = $this->httpGet($url);

            $result['status'] = "Success";
            $result['msg'] = "Store List";
            $store_list = json_decode($store_list, true);
            $store_data = array();
            foreach($store_list['grouped']['store_id']['groups'] as $key => $value)
            {
                $store_data[$key]['store_id'] = $value['doclist']['docs'][0]['store_id'];
                $store_data[$key]['store_name'] = $value['doclist']['docs'][0]['store_name'];
                $store_data[$key]['store_logo'] = $value['doclist']['docs'][0]['store_logo'];
            }
            
            $result['response'] = $store_data;
            return $result;
        } catch (Exception $ex) {
            $result['status'] = "Fail";
            $result['errors'] = $ex->getMessage();
            return $result;
        }
    }

    public function storeDetail($params) { //Used in the booking Engine
        try {
            $url = CONFIG::SOLR_URL;
            $query_condition = "*:*";
            $facets_condition = "*:*";
            $parameters = "*";
            $start = 0;
            $rows = 10;
            if (!empty($params['filter'])) {
                $filter = $params['filter'];
                $query_condition_res = $this->searching($filter);

                if ($query_condition_res['status'] == 0) {
                    return $query_condition_res;
                } else {
                    $query_condition = $query_condition_res['query_condition'];
                }
            }
            $parameters = CONFIG::SOLR_STORE_DETAIL_PARAM;
            $facets_fields = $this->facets();
            $group_field   = '&group.field=store_id';
            $sort_fileds = '';
            $sort_fileds = 'title asc';
            if (!empty($params['limit'])) {
                $rows = $params['limit'];
            }
            if (!empty($params['page'])) {
                $value = $params['page'];
                if ($value == "1") {
                    $start = (floatval($value) - 1) * floatval($rows);
                } else {
                    $start = ((floatval($value) - 1) * floatval($rows));
                }
            }
          
            //Complete Solr Url With Parameter
            $url = $url . "select?q=" . urlencode($query_condition) . "&fq=" . urlencode($facets_condition) . "&start=" . $start . "&rows=" . $rows . "&fl=" . $parameters . "&sort=" . urlencode($sort_fileds) . "&facet=true" . $facets_fields . "&group=true" . $group_field . "&wt=json&indent=true";

            $store_list = $this->httpGet($url);

            $result['status'] = "Success";
            $result['msg'] = "Store List";
            $store_list = json_decode($store_list, true);
            $store_data = array();
            foreach($store_list['grouped']['store_id']['groups'] as $key => $value)
            {
                $store_data[$key]['store_id'] = $value['doclist']['docs'][0]['store_id'];
                $store_data[$key]['store_name'] = $value['doclist']['docs'][0]['store_name'];
                $store_data[$key]['store_logo'] = $value['doclist']['docs'][0]['store_logo'];
                $store_data[$key]['status'] = $value['doclist']['docs'][0]['status'];
            }
            $result['response'] = $store_data;
            return $result;
        } catch (Exception $ex) {
            $result['status'] = "Fail";
            $result['errors'] = $ex->getMessage();
            return $result;
        }
    }

}