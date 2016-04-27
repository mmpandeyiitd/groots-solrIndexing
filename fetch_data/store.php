<?php                       
class STORE extends REST 
{   
    public function storeList($params) {
        $object = new FUNCTIONS();
        $result = $object->storeList($params);
        return $this->json($result);
    }

    public function storeDetail($params) {
        $object = new FUNCTIONS();
        $result = $object->storeDetail($params);
        return $this->json($result);
    }
}
