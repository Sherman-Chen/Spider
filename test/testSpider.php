<?php
require '../vendor/autoload.php';
require_once '../curl.php';
require_once '../spider.php';

use PHPUnit\Framework\TestCase;


class testSpider extends TestCase{
    private static $opt;
    private static $error=0;
    private $stub;

    public static function setopt($opt,$value){
        self::$opt[$opt]=$value;
        
    }
    public static function clearOpt(){
        self::$opt=array();
    }

    public function setUp(){
        $spider=spider::instance();
        $spider->setConfig(array(
        'proxy_open'=>false,/*是否打开代理*/
        'muti_proxy'=>false,/*使用多个代理*/
        'muti_proxy_times'=>2,/*当开启多代理的时候，每几次请求更换一次代理*/
        'analyze_url'=>'lagou',/**/
        'start_pageNum'=>1,
        'sleep_time'=>2,/**/
        'time_out'=>10,/*请求超时时间*/
        'reconnect'=>0,/*当请求超时的时候，重新连接多少次*/
        'reconnect_proxy'=>false,/*重新连接多次之后还是失败，则换另外一个代理*/
        'reconnect_proxy_time'=>2
    ));
    }

    public function __construct(){
        //清除设置
        //self::clearOpt();
        
        
        
    }
    //测试正常情况下的网络请求
    public function testNormal(){
        
        //建立桩件
        $stub = $this->createMock(spCurl::class);
        //通用的方法
        $stub->method('setopt')
             ->will($this->returnCallback(array('testSpider','setopt')));

        $opt=&self::$opt;
        $error=&self::$error;
        $stub->expects($this->exactly(2))->method('exec')
                   ->will($this->returnCallback(function()use (&$opt,&$error){
                       if(isset($opt[CURLOPT_URL])){
                           if($opt[CURLOPT_RETURNTRANSFER]==true){
                               $error=0;
                               return 'testNormal';
                           }else{
                               return true;
                           }
                       }else{
                           return false;
                       }
                   }));
        $stub->method('getLastError')
             ->will($this->returnCallback(function()use (&$error){
                return $error;
             }));
        
        $spider=spider::instance();
        $spider->setCurl($stub);
        $spider->setConfig('proxy_open',false);
        $spider->setConfig('muti_proxy',false);
        $spider->setConfig('reconnect',0);

        
        //没有设置url的时候返回false
        $this->assertEquals(false,$spider->getData());
        //设置了url，返回本方法名字
        $spider->setUrl('http://www.baidu.com');
        $this->assertEquals('testNormal',$spider->getData());
        
    }
    //测试超时重连
    public function testTimeOut(){
        //建立桩件
        $stub = $this->createMock(spCurl::class);
        //通用的方法
        $stub->method('setopt')
             ->will($this->returnCallback(array('testSpider','setopt')));
        
        $opt=&self::$opt;
        $error=&self::$error;

        $stub->expects($this->exactly(2))->method('exec')
                   ->will($this->returnCallback(function()use (&$opt,&$error){
                       if(isset($opt[CURLOPT_CONNECTTIMEOUT]) && is_numeric($opt[CURLOPT_CONNECTTIMEOUT])){
                           $error=CURLE_OPERATION_TIMEDOUT;
                           return false;
                       }else{
                           return  false;
                       }
                   }));
        $stub->method('getLastError')
             ->will($this->returnCallback(function()use (&$error){
                return $error;
             }));
        
        $spider=spider::instance();
        $spider->setCurl($stub);
        $spider->setConfig('proxy_open',true);
        $spider->setConfig('muti_proxy',false);
        $spider->setConfig('reconnect',1);//重连1次

        $spider->setTimeOut(3);
        $this->assertEquals(false,$spider->getData());
        
    }
    //测试超时换代理
    function testTimeOutProxy(){
        $proxys=array(
            '1.1.1.1:80',
            '1.1.1.1:180',
            '1.1.1.1:280',
            '1.1.1.1:380',
        );
        //建立桩件
        $stub = $this->createMock(spCurl::class);
        //通用的方法
        $stub->method('setopt')
             ->will($this->returnCallback(function($opt,$value)use($proxys){
                 static $time=0;
                 if($opt==CURLOPT_PROXY){
                    TestCase::assertEquals($proxys[$time++],$value);
                 }
                 call_user_func(array('testSpider','setopt'),$opt,$value);
                 
             }));
        
        $opt=&self::$opt;
        $error=&self::$error;

        $stub->expects($this->exactly(4))->method('exec')
                   ->will($this->returnCallback(function()use (&$opt,&$error,$proxys){
                       static $time=0,$firstTime=true;
                       static $num=4;
                       if($firstTime==true){
                           $firstTime=false;
                           TestCase::assertEquals($proxys[$time++],$opt[CURLOPT_PROXY]);
                       }
                       if($num--==0){
                           $num=3;
                           TestCase::assertEquals($proxys[$time++],$opt[CURLOPT_PROXY]);
                       }
                       

                       if(isset($opt[CURLOPT_CONNECTTIMEOUT]) && is_numeric($opt[CURLOPT_CONNECTTIMEOUT])){
                           $error=CURLE_OPERATION_TIMEDOUT;
                           return false;
                       }else{
                           return  false;
                       }
                   }));
        $stub->method('getLastError')
             ->will($this->returnCallback(function()use (&$error){
                return $error;
             }));
        
        $spider=spider::instance();
        $spider->setCurl($stub);
        $spider->setConfig('proxy_open',true);
        $spider->setConfig('muti_proxy',true);
        $spider->setConfig('muti_proxy_times',10);
        $spider->setConfig('reconnect_proxy',true);
        $spider->setConfig('reconnect_proxy_time',3);//代理请求三次还失败的话则换代理
        $spider->setProxy($proxys);

        $spider->setTimeOut(3);
        $this->assertEquals(false,$spider->getData());
    }
    //测试正常时换代理
    function testProxys(){

    }
}