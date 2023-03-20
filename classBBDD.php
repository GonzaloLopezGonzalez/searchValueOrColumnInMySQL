<?php
class BBDD
{
    private $config_file = 'config/configuration.ini';
    private $arr_connection = array();
    private $obj_db;

    public function __construct()
    {
        $this->arr_connection = parse_ini_file($this->config_file, true);
        $this->connect();
    }

    private function connect() :void
    {
        $this->obj_db = new mysqli('p:'.$this->arr_connection['database']['HOST_DB'], $this->arr_connection['database']['USER_DB'], $this->arr_connection['database']['PASSWORD_DB'], 'INFORMATION_SCHEMA');
        if ($this->obj_db->connect_errno > 0) {
            die('Unable to connect to database [' . $this->obj_db->connect_error . ']');
        }
    }

    public function searchColumnBySchema(String $columnName,String $schemaName) :array
    {
      if (!is_string($columnName)){
        throw new InvalidArgumentException($columnName . ' must be a string');
      }

      if (!is_string($schemaName)){
        throw new InvalidArgumentException($schemaName . ' must be a string');
      }

      if ($schemaName == 'INFORMATION_SCHEMA'){
        throw new Exception('Select another schema');
      }

      $query = 'SELECT DISTINCT TABLE_NAME,DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE (COLUMN_NAME = "'.strtoupper($columnName).'" OR COLUMN_NAME = "'.strtolower($columnName).'")  AND (TABLE_SCHEMA="'.strtoupper($schemaName).'" OR TABLE_SCHEMA="'.strtolower($schemaName).'")';

        return $this->executeQuery($query);
    }
    public function searchColumnInAllSchemas(string $columnName) :array
    {
      if (!is_string($columnName)){
        throw new InvalidArgumentException($columnName . ' must be a string');
      }

      $query = 'SELECT DISTINCT TABLE_NAME,TABLE_SCHEMA,DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE(COLUMN_NAME = "'.strtoupper($columnName).'" OR COLUMN_NAME = "'.strtolower($columnName).'")';
        return $this->executeQuery($query);
    }
    public function searchColumnValueInSchema(string $value,string $schemaName) :array
    {
      if (!is_string($value)){
        throw new InvalidArgumentException($schemaName . ' must be a string');
      }

      if (!is_string($schemaName)){
        throw new InvalidArgumentException($schemaName . ' must be a string');
      }

      if ($schemaName == 'INFORMATION_SCHEMA'){
        throw new Exception('Select another schema');
      }

      $arrResult = array();
      $textTypes = ['varchar','binary','varbinary','varbinary','tinytext','blob','text','mediumblob','mediumtext','longblob','longtext','enum','set'];
      $i = 0;
      $query = 'SELECT TABLE_NAME,COLUMN_NAME,DATA_TYPE from INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA="'.$schemaName.'"';
      $dataFieldsSchema = $this->executeQuery($query);
      unset($query);
      foreach ($dataFieldsSchema as $field){
        if (in_array($field['DATA_TYPE'],$textTypes) ){
          $fieldName = $field['COLUMN_NAME'];
          $tableName = $schemaName.'.'.$field['TABLE_NAME'];
          $fieldQuery = "SELECT {$fieldName} FROM {$tableName} WHERE {$fieldName}  = '$value' LIMIT 1";
          $searchResult = $this->executeQuery($fieldQuery);
          if (count($searchResult) > 0 )
          {
            $arrResult[$i]['COLUMN'] = $field['COLUMN_NAME'];
            $arrResult[$i]['TABLE'] = $field['TABLE_NAME'];
            $i++;
          }
        }
      }
      return $arrResult;

    }
    private function executeQuery(string $query) :array
    {
        $i = 0;
        $arr_result = array();
        $result = $this->obj_db->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $field=>$value) {
                    $arr_result[$i][$field] = utf8_encode($value);
                }
                $i++;
            }
            mysqli_free_result($result);
            unset($result);
        }
        return $arr_result;
    }

    public function __destruct()
    {
        mysqli_close($this->obj_db);
    }
}
