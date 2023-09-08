<?php


class MySQL {
    private static $connection;

    public static function connect($host, $username, $password, $dbname) {
        self::$connection = new mysqli($host, $username, $password, $dbname);

        if (self::$connection->connect_error) {
            die("Connection failed: " . self::$connection->connect_error);
        }
    }

    public static function query($sql) {
        if (self::$connection === null) {
            die("Database connection is not established.");
        }

        $result = self::$connection->query($sql);
        return $result;
    }

    public static function select($sql) {
        // 检查连接是否存在
        if (self::$connection === null) {
            die("Database connection is not established.");
        }

        $result = self::$connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            return $row;
        }
        return $result;
    }

}

