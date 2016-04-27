<?php
require_once ("config/config.php");
class MYSQL {
    public $DB_SERVER = '';
    public $DB_USER = '';
    public $DB_PASSWORD = '';
    public $DB = '';
    public function __construct($type='w') 
    {
        $this->dbConnect ($type); // Initiate Database connection
    }

    // Database connection
    private function dbConnect($type) 
    {
        try 
        {
            if($type=='w')
            {
                $this->DB_SERVER    = CONFIG::DB_SERVER;
                $this->DB_USER      = CONFIG::DB_USER_NAME;
                $this->DB_PASSWORD  = CONFIG::DB_USER_PASSWORD;
                $this->DB           = CONFIG::DB_NAME;    
            }
            elseif($type=='r')
            {
                $this->DB_SERVER    = CONFIG::DB_READ_SERVER;
                $this->DB_USER      = CONFIG::DB_READ_USER_NAME;
                $this->DB_PASSWORD  = CONFIG::DB_READ_USER_PASSWORD;
                $this->DB           = CONFIG::DB_READ_NAME;    
            }

            //$this->db = mysql_connect ( CONFIG::GETIT_DB_SERVER, CONFIG::mpdm, CONFIG::GETIT_DB_PASSWORD );
            $this->db = mysql_connect ( $this->DB_SERVER, $this->DB_USER, $this->DB_PASSWORD );
            if ($this->db) 
            {
                // echo CONFIG::DB;
                mysql_select_db ( $this->DB, $this->db ) or die ( "cannot select DB" );
            } 
            else 
            {
                throw new Exception ( sprintf ( "MySQL.Error(%d): %s", mysql_errno (), mysql_error () ) );
            }
        } 
        catch ( Exception $e ) 
        {
            //SYSTEMLOG::log ( $e->getMessage () );
        }
    }

    public function query($sql) 
    {
        try 
        {
            $result = mysql_query ( $sql, $this->db );

            if (! $result) 
            {
                throw new Exception ( sprintf ( "MySQL.Error(%d): %s", mysql_errno (), mysql_error () ) );
            }

            return $result;
        } 
        catch ( Exception $e ) 
        {
            //SYSTEMLOG::log ( $e->getMessage () );
        }
    }

    public function insert($sql) 
    {
        try 
        {
            $result = mysql_query ( $sql, $this->db );
            $result = mysql_insert_id ();

            if (! $result) 
            {
                throw new Exception ( sprintf ( "MySQL.Error(%d): %s", mysql_errno (), mysql_error () ) );
            }

            return $result;
        } 
        catch ( Exception $e ) 
        {
            //SYSTEMLOG::log ( $e->getMessage () );
        }
    }

    public function delete($sql) 
    {
        try 
        {
            $result = mysql_query ( $sql, $this->db );
            if (! $result) 
            {
                throw new Exception ( sprintf ( "MySQL.Error(%d): %s", mysql_errno (), mysql_error () ) );
            }

            return true;
        } 
        catch ( Exception $e ) 
        {
            //SYSTEMLOG::log ( $e->getMessage () );
        }
    }

    public function count($sql) 
    {
        $count = 0;
        try 
        {
            $result = mysql_query ( $sql, $this->db );
            $count = mysql_num_rows ( $result );
            if (! $result) 
            {
                throw new Exception ( sprintf ( "MySQL.Error(%d): %s", mysql_errno (), mysql_error () ) );
            }
            return $count;
        } 
        catch ( Exception $e ) 
        {
            echo $e->getMessage ();
            //SYSTEMLOG::log ( $e->getMessage () );
        }
    }

    public function fetch($sql) 
    {
        $result = $this->query ( $sql, $this->db );
        return mysql_fetch_array ( $result, MYSQL_ASSOC );
    }

    public function fetchAll($sql) 
    {
        $resultArray = null;
        $result = $this->query ( $sql, $this->db );

        while ( $row = mysql_fetch_array ( $result, MYSQL_ASSOC ) ) 
        {
            $resultArray [] = $row;
        }
        return $resultArray ? $resultArray : null;
    }

    public function begin() 
    {
        $this->query ( "BEGIN" );
    }

    public function commit() 
    {
        $this->query ( "COMMIT" );
    }
    
    public function rollback() 
    {
        $this->query ( "ROLLBACK" );
    }
}