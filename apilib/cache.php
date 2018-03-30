<?php

class Cache
{
    protected static $cacheDir = __DIR__ . '/cache';

    protected static function fullData()
    {
        if (!file_exists(self::$cacheDir . '/data.txt')) {
            return [];
        }
        $data = file_get_contents(self::$cacheDir . '/data.txt');
        $data = json_decode($data, true);

        return $data;
    }

    public static function getData($pare)
    {
        $data = self::fullData();
        return !empty($data[$pare]) ? $data[$pare] : null;
    }

    public static function setData($info)
    {
        $data = self::fullData();
        $data[$info['action']] = $info['data'];
        $file = fopen(self::$cacheDir . '/data.txt', 'w+');
        fwrite($file, json_encode($data));
        fclose($file);
    }
}