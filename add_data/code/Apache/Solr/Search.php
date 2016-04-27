<?php
/**
 * 
 * perform the solr search action 
 *
 */
require_once 'Service.php';
class Apache_Solr_Search{
	
	 /**
     * Search query params with their default values
     *
     * @var array
     */
    protected $_defaultQueryParams = array(
        'offset'         => '',
        'limit'          => '',
        'sort_by'        => array('customer_value' => 'desc'),
        'fields'         => array(),
        'solr_params'    => array(),
        'filters'        => array()
    );
    
     /**
     * Default suggestions count
     */
    const DEFAULT_SPELLCHECK_COUNT  = 1;
    
    /**
     *number of records found 
     * @var int
     */
    protected $_lastNumFound = 0;
    
    /**
     * Retrieve found document(products)from Solr index sorted by relevance
     * @param string $query
     * @param array $params
     * @return array
     */
 	public function geProductListByQuery($query, $params = array())
    {
    	$products = array();
    	
        return $_result = $this->_search($query, $params); 
 		//echo "<pre>";print_r($_result); die();
        if(!empty($_result['productList'])) {
            foreach ($_result['productList'] as $_product) {
                $products[]  = $_product;
            }
        } 
        $result = array(
            'productList' => $products,
            'facetedData' => (isset($_result['facets'])) ? $_result['facets'] : array(),
        	'suggestionsData' => (isset($_result['suggestions'])) ? $_result['suggestions'] : array()
        );
        
        
        
        return $result;
    }
    
	/**
	 * returns number of records found
	 * @return int
	 */
    public function getLastNumFound(){ 
    	return $this->_lastNumFound;
    }
 
    
    /**
     * Prepare search conditions from query
     *
     * @param string|array $query
     *
     * @return string
     */
    protected function prepareSearchConditions($query)
    {
        if (is_array($query)) {
            $searchConditions = array();
            foreach ($query as $field => $value) {
                if (is_array($value)) {
                    if ($field == 'price' || isset($value['from']) || isset($value['to'])) {
                        $from = (isset($value['from']) && strlen(trim($value['from'])))
                            ? $this->_prepareQueryText($value['from'])
                            : '*';
                        $to = (isset($value['to']) && strlen(trim($value['to'])))
                            ? $this->_prepareQueryText($value['to'])
                            : '*';
                        $fieldCondition = "$field:[$from TO $to]";
                    }
                    else {
                        $fieldCondition = array();
                        foreach ($value as $part) {
                            $part = $this->_prepareFilterQueryText($part);
                            $fieldCondition[] = $field .':'. $part;
                        }
                        $fieldCondition = '('. implode(' OR ', $fieldCondition) .')';
                    }
                }
                else {
                    if ($value != '*') {
                        $value = $this->_prepareQueryText($value);
                    }
                    $fieldCondition = $field .':'. $value;
                }
                
                if($field == 'name'){
                	 $fieldCondition = array();
					$fieldCondition[] = 'vendor_productname:' . $value;        	
					$fieldCondition[] = 'base_productname:' . $value;
					$fieldCondition = '('. implode(' OR ', $fieldCondition) .')';
                }
                if($field == 'categories'){
                	 $fieldCondition = array();
					$fieldCondition[] = 'child_categories:' . $value;        	
					$fieldCondition[] = 'categories:' . $value;
					$fieldCondition = '('. implode(' OR ', $fieldCondition) .')';
                }
                if($field == 'description'){
                	 $fieldCondition = array();
					$fieldCondition[] = 'vendor_description:' . $value;        	
					$fieldCondition[] = 'base_description:' . $value;
					$fieldCondition = '('. implode(' OR ', $fieldCondition) .')';
                }

                $searchConditions[] = $fieldCondition;
            }

            $searchConditions = implode(' AND ', $searchConditions);
            
        }
        else {
            $searchConditions = $this->_prepareQueryText($query);
        }

        return $searchConditions;
    }
    
    
     /**
     * Simple Search interface
     *
     * @param string $query 
     * @param array $params 
     * @return array
     */
    protected function _search($query, $params = array())
    {
    	
    	if ($query) {
			$searchConditions = $this->prepareSearchConditions($query);
    	} else{ 
    		
    		$searchConditions = '*:*';
    	}
    	
    	
            
    	
    	if (isset($params['solr_params']['use_handler']) && $params['solr_params']['use_handler'] == true) {
    		$params['solr_params']['qt'] = 'magento';
    	}
        $_params = $this->_defaultQueryParams;
        if (is_array($params) && !empty($params)) {
            $_params = array_intersect_key($params, $_params) + array_diff_key($_params, $params);
        }
        $offset = $_params['offset'];
        $limit  = $_params['limit'];

        $searchParams   = array();
        

        if (!is_array($_params['fields'])) {
            $_params['fields'] = array($_params['fields']);
        }

        if (!is_array($_params['solr_params'])) {
            $_params['solr_params'] = array($_params['solr_params']);
        }

        /**
         * Add sort fields
         */
        $sortFields = $this->_prepareSortFields($_params['sort_by']);
        /*foreach ($sortFields as $sortField) {
            $searchParams['sort'][] = $sortField['sortField'] . ' ' . $sortField['sortType'];
        }*/
        $sortSolrData = array();
    	foreach ($sortFields as $sortField) {
    		$sortSolrData[] = $sortField['sortField'] . ' ' . $sortField['sortType'];
        }
        if (!empty($sortSolrData)) 
        	$searchParams['sort'] = implode(',', $sortSolrData); 
        /**
         * Fields to retrieve
         */
        if ($limit && !empty($_params['fields'])) {
            $searchParams['fl'] = implode(',', $_params['fields']);
        }
  
        /**
         * Facets search
         */
        
      
        $params['solr_params']['facet'] = 'on';
        $useFacetSearch = (isset($params['solr_params']['facet']) && $params['solr_params']['facet'] == 'on');
        if ($useFacetSearch) { 
            $searchParams += $this->_prepareFacetConditions($params['facet']);
        }
        
        
    /**
         * Suggestions search
         */
        $useSpellcheckSearch = (
            isset($params['solr_params']['spellcheck'])
            && $params['solr_params']['spellcheck'] == 'true'
        );

        if ($useSpellcheckSearch) {
            if (isset($params['solr_params']['spellcheck.count'])
                && (int) $params['solr_params']['spellcheck.count'] > 0
            ) {
                $spellcheckCount = (int) $params['solr_params']['spellcheck.count'];
            } else {
                $spellcheckCount = self::DEFAULT_SPELLCHECK_COUNT;
            }

            $_params['solr_params'] += array(
                'spellcheck.collate'         => 'true',
                'spellcheck.dictionary'      => 'magento_spell',
                'spellcheck.extendedResults' => 'true',
                'spellcheck.count'           => $spellcheckCount
            );
             
        }
        
       // if(isset($params['use_handler'])){
        	//$_params['solr_params']['qt'] = 'magento';
      //  }

        /**
         * Specific Solr params
         */
        if (!empty($_params['solr_params'])) {
            foreach ($_params['solr_params'] as $name => $value) {
                $searchParams[$name] = $value;
            }
        }

        if (!empty($_params['filters']))
        	$searchParams['fq'] = $this->_prepareFilters($_params['filters']);
        /*
        if (!Mage::helper('cataloginventory')->isShowOutOfStock()) {
            $searchParams['fq'][] = 'in_stock:true';
        }*/
        	

        $searchParams['fq'] = implode(' AND ', $searchParams['fq']);
       
      
        try {
        	$service = new Apache_Solr_Service();
        	
        	$response = $service->search($searchConditions, $offset, $limit, $searchParams);
           
        	$data = $response->getRawResponse();
            
        	return $data;
            if (!isset($params['solr_params']['stats']) || $params['solr_params']['stats'] != 'true') {
              	$result = $this->_prepareQueryResponse($data, $params['max_price']);
              	
              	if(isset($params['advanced_search']) && $params['advanced_search']){
              		if (isset($params['solr_params']['spellcheck.count']) 
              		         && (int) $params['solr_params']['spellcheck.count'] > 0) {
               			 $spellcheckCount = (int) $params['solr_params']['spellcheck.count'];
           			 } else {
                		$spellcheckCount = self::DEFAULT_SPELLCHECK_COUNT;
            		}
              			$searchParams['spellcheck'] = 'true';
		                $searchParams['spellcheck.collate']= 'true';
		                $searchParams['spellcheck.dictionary']      = 'magento_spell';
		                $searchParams['spellcheck.extendedResults'] = 'true';
		                $searchParams['spellcheck.count']           = $spellcheckCount;
		            	$searchParams['qt'] = 'magento';
		            
		            $q = $params['name'];
		            $response = $service->search($q, $offset, $limit, $searchParams);
  		            $resultSuggestions = $this->_prepareSuggestionsQueryResponse(json_decode($response->getRawResponse()));
		          	$suggestions = array();
  		              /* Calc results count for each suggestion */
                    if (count($resultSuggestions) && $spellcheckCount > 0) {
                        /* Temporary store value for main search query */
                        $tmpLastNumFound = $this->_lastNumFound;
						unset($params['advanced_search']);
                        unset($params['solr_params']['spellcheck']);
                        unset($params['solr_params']['spellcheck.count']);
                        unset($params['spellcheck_result_counts']);

                       
                        foreach ($resultSuggestions as $key => $item) {
                            $this->_lastNumFound = 0;
                            $query = array('name' => $item['word']);
                            $this->geProductListByQuery($query, $params);
                            if ($this->_lastNumFound) {
                                $resultSuggestions[$key]['num_results'] = $this->_lastNumFound;
                                $suggestions[] = $resultSuggestions[$key];
                                $spellcheckCount--;
                            }
                            if ($spellcheckCount <= 0) {
                                break;
                            }
                        }
                        /* Return store value for main search query */
                        $this->_lastNumFound = $tmpLastNumFound;
                    }
                    $result['suggestions'] = $suggestions;
              	}
              	
              	
               /**
                 * Extract suggestions search results
                 */
                if ($useSpellcheckSearch) {
                    $resultSuggestions = $this->_prepareSuggestionsQueryResponse($data);
                /* Calc results count for each suggestion */
                    if (isset($params['spellcheck_result_counts']) && $params['spellcheck_result_counts']
                        && count($resultSuggestions)
                        && $spellcheckCount > 0
                    ) {
                        /* Temporary store value for main search query */
                        $tmpLastNumFound = $this->_lastNumFound;

                        unset($params['solr_params']['spellcheck']);
                        unset($params['solr_params']['spellcheck.count']);
                        unset($params['spellcheck_result_counts']);

                        $suggestions = array();
                        foreach ($resultSuggestions as $key => $item) {
                            $this->_lastNumFound = 0;
                            $this->geProductListByQuery($item['word'], $params);
                            if ($this->_lastNumFound) {
                                $resultSuggestions[$key]['num_results'] = $this->_lastNumFound;
                                $suggestions[] = $resultSuggestions[$key];
                                $spellcheckCount--;
                            }
                            if ($spellcheckCount <= 0) {
                                break;
                            }
                        }
                        /* Return store value for main search query */
                        $this->_lastNumFound = $tmpLastNumFound;
                    }/* else {
                        $suggestions = array_slice($resultSuggestions, 0, $spellcheckCount);
                    }*/
                        //$suggestions = array_slice($resultSuggestions, 0, $spellcheckCount);
                   // }
                    $result['suggestions'] = $suggestions;
                }
              	
            }else {
                $result = $this->_prepateStatsQueryResponce($data);
            } 

            return $result;
        } catch (Exception $e) {
            SYSTEMLOG::log($e);
        }
    }
    
    
    /**
     * Convert Solr Query Response found suggestions to string
     *
     * @param object $response
     * @return array
     */
    protected function _prepareSuggestionsQueryResponse($response)
    {
        $suggestions = array();

        if (array_key_exists('spellcheck', $response) && array_key_exists('suggestions', $response->spellcheck)) {
            $arrayResponse = $this->_objectToArray($response->spellcheck->suggestions);
            if (is_array($arrayResponse)) {
                foreach ($arrayResponse as $item) {
                    if (isset($item['suggestion']) && is_array($item['suggestion']) && !empty($item['suggestion'])) {
                        $suggestions = array_merge($suggestions, $item['suggestion']);
                    }
                }
            }

            // It is assumed that the frequency corresponds to the number of results
          /*  if (count($suggestions)) {
                usort($suggestions, array(get_class($this), 'sortSuggestions'));
            }*/
        }

        return $suggestions;
    }
    
   /**
     * Prepare facet fields conditions
     *
     * @param array $facetFields
     * @return array
     */
    protected function _prepareFacetConditions($facetFields)
    {
        $result = array();
 
        if (is_array($facetFields)) {
            $result['facet'] = 'on';
            foreach ($facetFields as $facetField => $facetFieldConditions) {
                if (empty($facetFieldConditions)) {
                    $result['facet.field'][] = $facetField;
                }
                else {
                    foreach ($facetFieldConditions as $facetCondition) {
                        if (is_array($facetCondition) && isset($facetCondition['from']) && isset($facetCondition['to'])) {
                            $from = (isset($facetCondition['from']) && strlen(trim($facetCondition['from'])))
                                ? $this->_prepareQueryText($facetCondition['from'])
                                : '*';
                            $to = (isset($facetCondition['to']) && strlen(trim($facetCondition['to'])))
                                ? $this->_prepareQueryText($facetCondition['to'])
                                : '*';
                            $fieldCondition = "$facetField:[$from TO $to]";
                        }
                        else {
                            $facetCondition = $this->_prepareQueryText($facetCondition);
                            $fieldCondition = $this->_prepareFieldCondition($facetField, $facetCondition);
                        }

                        $result['facet.query'][] = $fieldCondition;
                    }
                }
            }
        }

        return $result;
    }
    
/**
     * Prepare fq filter conditions
     *
     * @param array $filters
     * @return array
     */
    protected function _prepareFilters($filters)
    {
        $result = array();

        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $field => $value) {
                if (is_array($value)) {
                	if (isset($value['from']) || isset($value['to'])) {
                        $from = (isset($value['from'])) ? $this->_prepareFilterQueryText($value['from']) : '*';
                        $to = (isset($value['to'])) ? $this->_prepareFilterQueryText($value['to']) : '*';
                        $fieldCondition = "$field:[$from TO $to]";
                    }/* else if ($field == 'price' || isset($value['from']) || isset($value['to'])) {
                        $from = (isset($value['from']) && !empty($value['from'])) ? $this->_prepareFilterQueryText($value['from']) : '*';
                        $to = (isset($value['to']) && !empty($value['to'])) ? $this->_prepareFilterQueryText($value['to']) : '*';
                        $fieldCondition = "$field:[$from TO $to]";
                    }*/
                    else if (isset($value['like'])) {
                    	$fieldCondition = $field . ':*'.$this->_prepareFilterQueryText($value['like']).'*';
                    } else {
                        $fieldCondition = array();
                        foreach ($value as $part) {
                            $part = $this->_prepareFilterQueryText($part);
                            $fieldCondition[] = $this->_prepareFieldCondition($field, $part);
                        }
                        $fieldCondition = '(' . implode(' OR ', $fieldCondition) . ')';
                    }
                }
                else {
                	if($field != 'created_at' && $field != 'updated_at')
                    	$value = $this->_prepareFilterQueryText($value);
                    $fieldCondition = $this->_prepareFieldCondition($field, $value);
                }

                $result[] = $fieldCondition;
            }
        }

        return $result;
    }
    
   /**
     * Escape query text
     *
     * @param string $text
     * @return string
     */
    protected function _prepareQueryText($text)
    {   $text = strtolower($text);
        $words = explode(' ', $text);
        if (count($words) > 1) {
            foreach ($words as $key => &$val) {
                if (!empty($val)) {
                    $val = $this->_escape($val);
                } else {
                    unset($words[$key]);
                }
            }
            $text = '(' . implode(' ', $words) . ')';
        } else {
            $text = $this->_escape($text);
        }

        return $text;
    }
    
/**
     * Escape filter query text
     *
     * @param string $text
     * @return string
     */
    protected function _prepareFilterQueryText($text)
    {
        return $this->_escape($text);
    }
    
     /**
     * Escape a value for special query characters such as ':', '(', ')', '*', '?', etc.
     *
     * @param string $value
     * @return string
     */
    public function _escape($value)
    {
        //list taken from http://lucene.apache.org/java/docs/queryparsersyntax.html#Escaping%20Special%20Characters
        $pattern = '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?| |:|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }
    
    
    /**
     * Escape a value meant to be contained in a phrase for special query characters
     *
     * @param string $value
     * @return string
     */
    public function _escapePhrase($value)
    {
        $pattern = '/("|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * Convenience function for creating phrase syntax from a value
     *
     * @param string $value
     * @return string
     */
    public function _phrase($value)
    {
        return $this->_escapePhrase($value);
    }
     
     /**
     * Prepare solr field condition
     *
     * @param string $field
     * @param string $value
     * @return string
     */
    protected function _prepareFieldCondition($field, $value)
    {
        $fieldCondition = $field .':'. $value;
        return $fieldCondition;
    }
    
	/**
     * Convert Solr Query Response found documents to an array
     *
     * @param object $response
     * @return array
     */
    protected function _prepareQueryResponse($response, $boolMaxPrice = false )
    {
        $realResponse = $response->response;
    	if (empty($realResponse->docs)) {
            return array();
        }
        
        /*$_docs  = $realResponse->docs;

        if($boolMaxPrice == false) {
        	$this->_lastNumFound = (int)$realResponse->numFound;
        }
        if (!$_docs) {
            return array();
        }
        $result1 = array();
        foreach ($realResponse as $key => $res) {
            $result1[$key] = $this->_objectToArray($res);
        }*/
        //$result['productCount'] =  $this->_lastNumFound;
		//$result['productList'] =  $result1;
		//if(isset($response->facet_counts))
		//$result['facets'] = $this->_facetObjectToArray($response->facet_counts);
        return $realResponse;
    }

 /**
     * Convert an object to an array
     *
     * @param object $object The object to convert
     * @return array
     */
    protected function _objectToArray($object)
    {
        if(!is_object($object) && !is_array($object)){
            return $object;
        }
        if(is_object($object)){
            $object = get_object_vars($object);
        }

        return array_map(array($this, '_objectToArray'), $object);
    }
     
	/**
     * Convert facet results object to an array
     *
     * @param   object|array $object
     * @return  array
     */
    protected function _facetObjectToArray($object)
    {
        if(!is_object($object) && !is_array($object)){
            return $object;
        }

        if(is_object($object)){
            $object = get_object_vars($object);
        }

        $res = array();
        foreach ($object['facet_fields'] as $attr => $val) {
            foreach ((array)$val as $key => $value) {
                $res[$attr][$key] = $value;
            }
        }

        foreach ($object['facet_queries'] as $attr => $val) {
            if (preg_match('/\(categories:(\d+) OR child_categories\:\d+\)/', $attr, $matches)) {
                $res['categories'][$matches[1]]    = $val;
            } else {
                $attrArray = explode(':', $attr);
                $res[$attrArray[0]][$attrArray[1]] = $val;
            }
        }

        return $res;
    }
  
 	/**
     * Prepare sort fields
     *
     * @param array $sortBy
     * @return array
     */
    protected function _prepareSortFields($sortBy)
    {
        $result = array();
		$extraSort = null;
        /**
         * Support specifying sort by field as only string name of field
         */
        /*if (!empty($sortBy) && !is_array($sortBy)) {
            if ($sortBy == 'relevance') {            	
                $sortBy = 'score';
                //$extraSort = 'business_value';
            }elseif ($sortBy == 'position') {
            	$sortBy = 'score';
            	$extraSort = 'business_value';
            }elseif( $sortBy == 'name') {
            	$sortBy = 'alphaNameSort';
            }
             elseif ($sortBy == 'position') {            	
	            if(!Mage::registry('current_category')){
	            	$sortBy = 'score';
	             }else{
	                $sortBy = 'position_category_' . Mage::registry('current_category')->getId();
	             }
            } 
            elseif ($sortBy == 'price') {
            	//currently its sorting by base product price            	
                $websiteId       = Mage::app()->getStore()->getWebsiteId();
                $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();

                $sortBy = 'price_'. $customerGroupId .'_'. $websiteId;
            } 
			//$extraSort = 'business_value';
            $sortBy = array(array($sortBy => 'asc'));
        }*/

        foreach ($sortBy as $sortField => $sortType) {
            //$_sort = each($sort);
            //$sortField = $_sort['key'];
            //$sortType = $_sort['value'];
            /*if ($sortField == 'relevance') {
                $sortField = 'score';
                //$extraSort = 'business_value';
            } elseif ($sortField == 'position') {
               $sortField = 'score';
               $extraSort = 'business_value';
            }elseif( $sortField == 'name' ) {
            	$sortField = 'alphaNameSort';
            } elseif ($sortField == 'position') {
             if(!Mage::registry('current_category')){
            	$sortField = 'score';
             }else{
                $sortField = 'position_category_' . Mage::registry('current_category')->getId();
             }
            } 
            elseif ($sortField == 'price') {
                $websiteId       = Mage::app()->getStore()->getWebsiteId();
                $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();

                $sortField = 'price_'. $customerGroupId .'_'. $websiteId;
            }*/ 
            //$extraSort = 'business_value';
            $result[] = array('sortField' => $sortField, 'sortType' => trim(strtolower($sortType)));
            
            /*if(!is_null($extraSort)){
            	$result[] = array('sortField' => $extraSort, 'sortType' => 'desc');
            }*/
        }

        return $result;
    }
      
}