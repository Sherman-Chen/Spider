<?php
//php中curl的封装类
class spCurl{
    private $ci;
    private static $_instance;
    //单例模式
    public static function instance(){
        if(!isset(self::$_instance)){
            self::$_instance=new self();
        }
        return self::$_instance;
    }


    private function __construct(){
        $this->ci = curl_init();
        if($this->ci==false){
            echo 'curl_init失败';
            return;
        }
    }
    public function exec(){
        return curl_exec($this->ci);
    }
    public function getLastError(){
        return curl_errno($this->ci);
    }

    public function setopt($opt,$value){
        return curl_setopt($this->ci,$opt,$value);
    }
    public function __destruct(){
        curl_close($this->ci);
    }
    
}