<?php


class util
{
    public function toGB($byte = 0){
        return round($byte/1024/1024/1024,2);
    }
    public function toByte($GB = 0){
        return round($GB*1024*1024*1024,2);
    }
}