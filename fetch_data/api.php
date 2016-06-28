<?php
    require_once("Rest.inc.php");
    require_once("config/config.php");
    function __autoload($classname) {
        $filename = strtolower($classname) .".php";
        require_once ($filename);
    }
    class API extends REST {

        public $data        = "";

        private $db         = NULL;

        public function __construct(){
            parent::__construct();              // Init parent contructor
        }
        public $userWithoutApiUsernameAndPassword   =   array('');
        public $userNotExitStore    =   array('lead');

        /*
        * Public method for access api.
        * This method dynmically call the method based on the query string
        *
        */

        public function processApi()
        {
            $req        = $_SERVER["REQUEST_URI"];
            $req1       = explode("?",$req);

            $request    = explode("/", $req1[0]) ;
            $class      = strtoupper($request['2']);
            $method     = strtolower($request['3']);

            if ((int)method_exists($class,$method) > 0)
            {
                /*if ($this->get_request_method() != "POST"      )
                {
                    SYSTEMLOG::log("Invalid request:".$_REQUEST['rquest']);
                    $this->response('Invalid request:'.$_REQUEST['rquest'],406);
                }*/

                if (!empty($this->_request))
                {
                    $apiKey         = $this->_request['api_key'];
                    $apiPassword    = $this->_request['api_password'];
                    
                    $result         = $this->validateCredential($apiKey,$apiPassword,$apiType);
                    
                    if(isset($apiKey) && isset($apiPassword) && $result==null)
                    {
                        $obj        = new $class();
                        $this->response($obj->$method($this->_request),200);
                    }
                    else
                    {
                        $result     = array('status' => 'Failed', 'message' => 'Invalid Api user and password');
                        //SYSTEMLOG::log("Invalid Api user and password");
                        $this->response($this->json($result),200);
                    }
                }
                else
                {
                    $result = array('status' => 'Failed', 'message' => 'Invalid parameters');
                    //SYSTEMLOG::log("Invalid parameters");
                    $this->response($this->json($result),200);
                }
            }
            else
            {
                $result = array('status' => 'Failed', 'message' => 'Invalid Api call:'.$_REQUEST['rquest']);
                //SYSTEMLOG::log("Invalid Api call:".$_REQUEST['rquest']);
                $this->response($this->json($result),200);
            }
        }

        protected  function validateCredential($apiKey,$apiPassword,$apiType='')
        {       
            if(($apiKey==CONFIG::APIKEY) && ($apiPassword = CONFIG::APIPASSWORD))
            {
                $result=null;    
            }
            else
            {
                $result = array('status' => 'Failed', 'message' => 'Invalid Api user and password');
            }
            return $result;
        }
    }

    // Initiiate Library
    /*error_reporting(E_ALL);
    ini_set("display_errors", '1');
    */
    $api = new API;
    $api->processApi();

?>
