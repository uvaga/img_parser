<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 03.05.2017
 * Time: 19:33
 */
class DB {
    static protected $link;


    static function connect($config) {
        self::$link = new Mysqli($config['host'], $config['username'], $config['password'], $config["database"]);
    }


    static function escape ($s, $k='')
    {
        if ($k == 'img_content')
            return mysqli_real_escape_string(self::$link, $s);
        else
            return mysqli_real_escape_string(self::$link, htmlspecialchars($s));
    }

    static public function query($sql)
    {
        return mysqli_query(self::$link, $sql);
    }


    static function insert_sql($table_name, $values)
    {
        $sql = 'insert into '.$table_name.' (';
        $c = '';
        foreach ($values as $k => $v) {
            $sql .= $c . $k;
            $c = ', ';
        }
        $sql .= ') values(';
        $c = '';
        foreach ($values as $k => $v)
        {
            if (is_null($v))
                $sql .= $c . 'null';
            elseif (strcasecmp($v, 'now()') != 0)
            {
                if (is_bool($v)) $v = (($v)?(1):(0));
                $sql .= $c . '\''.self::escape($v, $k).'\'';
            }
            else
                $sql .= $c . '';
            $c = ', ';
        }
        $sql .= ')';
        //echo $sql;
        if (!self::query($sql)) return false;

        return self::get_last_id($table_name);
    }

    static function mysqli_result($res,$row=0,$col=0){
        $numrows = mysqli_num_rows($res);
        if ($numrows && $row <= ($numrows-1) && $row >=0){
            mysqli_data_seek($res,$row);
            $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
            if (isset($resrow[$col])){
                return $resrow[$col];
            }
        }
        return false;
    }

    static function get_last_id($table_name)
    {
        $sql = "SELECT MAX(id) FROM $table_name ;";
        $res = self::query($sql);
        return self::mysqli_result($res,0,0);
    }

    static public function get_count($table, $pk_arr = 0)
    {
        $s_fld = '';
        if (is_array($pk_arr))
            foreach ($pk_arr as $k => $v)
                $s_fld .= ( ($s_fld=='')?(''):(' and ') ) . $k . '=\''.self::escape($v).'\'';
        $sql = "SELECT count(*) as count FROM {$table}";
        if ($s_fld != '')
            $sql .= ' where ' . $s_fld;

        $data = self::fetchArray($sql);

        return $data[0]['count'];
    }

    static public function fetchArray($sql)
    {
        $result = self::query($sql);
        if (!$result) return false;
        $array = array();

        while ($a = mysqli_fetch_array($result, MYSQLI_ASSOC)) $array[] = $a;

        return $array;
    }
}