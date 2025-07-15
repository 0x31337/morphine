<?php
/**
 * JASOOS Engine Database API
 * responsible for manipulating the MySQL database in an easy-to-use method
 * sanitized and secured requests with fewer code than other frameworks
 * Please refer to the docs and read Database API section for more detailed information.
 * Note: Please @DO_NOT directly edit on this API on BDR without announcing the changes publicly.
 */
namespace Morphine\Engine;

require_once 'db.func.php';
if(!class_exists('Database'))
{
    class Database
    {
        private static $morph_db_host = '127.0.0.1'; // Database host (localhost, or address to remote host)
        private static $morph_db_name = 'Zerg'; // Database name.
        private static $morph_db_user = 'root'; // Database username.
        private static $morph_db_password = ''; // Database password.
        private static $jasDB; // instance of the database, initialized by default. i.e: self::set()
        private static $select; // a property to hold query results
        public function __construct(){
            self::set();
        }
        public static function set(){
            // Establishing a connection to the MySQL database.
            self::$jasDB = mysqli_connect(self::$morph_db_host, self::$morph_db_user, self::$morph_db_password)
            or die("Unable to connect the database : did you <i>install</i> Morphine ? <br> Please visit <a href='https://github.com/0x31337/morphine/wiki/Morphine-documentations#user-friendly-quickstart'>The User-friendly quickstart</a> to find out what's missing.");
            // selecting the database
            mysqli_select_db(self::$jasDB, self::$morph_db_name);
            mysqli_query(self::$jasDB, "SET NAMES 'utf8'");

        }
        public static function close(){
            // Closing the database connection. NOTE: coded only for exceptions.
            mysqli_close(self::$jasDB);
        }
        protected static function injectionSafe($entry){
            // custom SQL Injection protection.
            $patterns = array('"', '\'', '.', '-', '+', '/', '#', '<', '>', '&', '$', '!', '?', ')', ';', ',', '%');
            $v = str_replace($patterns, "", $entry);
            $v = mysqli_real_escape_string($v);
            return $v;
        }
        public function select($column, $table, $where = false, $isOR = false, $offset = false, $limit = false, $order = false, $search=false){
            // protecting from the SQL injection.
            /*$column = self::injectionSafe($column);
            $table = self::injectionSafe($table);
            */
            // selecting the ORDER BY
            if($order == "ASC")
            {
                $orderby = "ORDER BY id ASC";
            }
            elseif(is_array($order))
            {
                $order_comma = 0;
                foreach ($order as $key => $value) {
                    if($key == "column")
                    {
                        $order_column = $value;
                    }
                    else
                    {
                        if($key == "type")
                        {
                            $order_type = $value;
                        }

                    }
                }
                $orderby = "ORDER BY $order_column $order_type";
            }
            else
            {
                $orderby = "ORDER BY id DESC";
            }

            // Selecting a table from the database.


            if(!$where){
                if (is_array($search)) {
                    $i=0;
                    foreach ($search as $key => $arr) {

                        if($i==0)
                        {
                            $query = "SELECT $column FROM $table WHERE ".$search[$i]['column']." LIKE '%".$search[$i]['keyword']."%'";
                        }
                        else
                        {
                            $query .= " ".$search[$i]['type']." ".$search[$i]['column']." LIKE '%".$search[$i]['keyword']."%'";
                        }
                        $i++;
                    }
                    $query .= " $orderby";
                }
                else
                {
                    $query = "SELECT $column FROM $table $orderby";
                }
            }else{
                $wh = whereSort($where, $isOR);
                $query = "SELECT $column FROM $table WHERE $wh $orderby";
            }

            if($limit)
            {
                if($offset)
                {
                    $query .= " LIMIT $offset, $limit";
                }
                else
                {
                    $query .= " LIMIT $limit";
                }

            }
            //echo $query;
            self::$select = mysqli_query(self::$jasDB, $query) or die("Error while selecting Al-JASOOS columns : " . mysqli_error(self::$jasDB));
            return  $query;

        }
        public function getTotalRows(){
            // Get the number of rows from the specified table/column.
            return mysqli_num_rows(self::$select);
        }
        public function exists(){
            // used in the loops to read the table's rows
            return mysqli_fetch_assoc(self::$select);
        }
        public function insert($data){
            // The Ability to insert an Array into the database easily.
            return instfunc(self::$jasDB, $data);

        }

        public function batch_insert($data)
        {
            /* Example usage:
            $data = array(
                'table' => "your_table",
                'columns' => "column1|column2|column3",
                'values' => array(
                'values1' => array('value1a', 'value2a', 'value3a'),
                'values2' => array('value1b', 'value2b', 'value3b'),
                'values3' => array('value1c', 'value2c', 'value3c')
                )
            );*/
            return batchInstFunc(self::$jasDB, $data);
        }

        public function update($data){
            $upt = uptfunc($data);

            // TODO: Fix the bug: whenever this string appears in updated data, it doesn't update.
            if(strpos($upt, '987édgfg@g^pfgqùmldfgw:;bvmùcwl;ùùùgflkgfqdqdfnjg3054065468421fsd++sdf--fsd/*/*/sdfsdf::!:sdf;msdfaapmalbert01020308...:@20210807')){
                return false;
            }else{
                return mysqli_query(self::$jasDB, $upt) or false;// die('JASOOS Engine critical error (database): '.mysqli_error(self::$jasDB));
            }

        }
        public function __clone()
        {
            return new Database();
        }
        public function delete($data){
            $del = delfunc($data);
            if(strpos($del, 'Error')){
                return false;
            }else{
                return mysqli_query(self::$jasDB, $del) or die('JASOOS Engine critical error (database): '.mysqli_error(self::$jasDB));
            }
        }
        public function copy($data,$updateTarget=false){
            $cpy = copyData($data, $updateTarget);
            if(strpos($cpy, 'Error')){
                return $cpy;
            }else{
                if(mysqli_query(self::$jasDB, $cpy) == 1) return 1;
                return die('JASOOS Engine critical error (database): '.mysqli_error(self::$jasDB));
            }
        }
        public function move($data, $delRow=true, $updateTarget=false){
            $mv = moveData($data, $delRow, $updateTarget);
            if(strpos($mv, 'Error')){
                return $mv;
            }else{
                $sql1= mysqli_query(self::$jasDB, $mv[0]);
                if($sql1 == 1) $sql2= mysqli_query($mv[1]);
                if($sql2 == 1) return 1;
                return die('JASOOS Engine critical error (database): '.mysqli_error(self::$jasDB));
                //return $mv[0];
            }
        }
        public function unsafe_query($sql)
        {
            return mysqli_query(self::$jasDB, $sql);
        }
    }
}