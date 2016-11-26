<?php


class spider{
    private $ci;

    private $config=array(
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
    );

    private static $_instance;
    private $proxy_times;
    private $proxy;
    private $proxy_no=0;
    private $reconnect_proxy_no=0;

    //构造函数，初始化curl，配置useragent
    private function __construct(){
        
    }

    //单例模式
    public static function instance(){
        if(!isset(self::$_instance)){
            self::$_instance=new self();
        }
        return self::$_instance;
    }
    public function setCurl(&$curl){
        $this->ci = &$curl; 
        $this->ci->setopt(CURLOPT_RETURNTRANSFER,true);
        $this->ci->setopt(CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95Safari/537.36 SE 2.X MetaSr 1.0');
    }
    //设置超时的时间
    public function setTimeOut($time){
        if(!isset($time) || !is_numeric($time)){
            $time=$this->config['time_out'];
        }
        $this->ci->setopt(CURLOPT_CONNECTTIMEOUT,$time);
    }

    //配置
    public function setConfig($config,$value=''){
        if(is_array($config)){
            $this->config=$config;
        }else{
            $this->config[$config]=$value;
        }
        
    }
    
    /*
    *设置代理
    */
    public function setProxy($proxy_url){
        if($this->config['proxy_open']==false){
            $this->ci->setopt(CURLOPT_HTTPPROXYTUNNEL, false);
            return;
        }
        if(!is_array($proxy_url)){
            $this->ci->setopt(CURLOPT_HTTPPROXYTUNNEL, TRUE); 
            $this->ci->setopt(CURLOPT_PROXY, $proxy_url); 
            return;
        }
        $this->proxy=$proxy_url;
        $this->setProxy($proxy_url[0]);

        
        
    }
    /*
    *设置url
    */
    public function setUrl($url){
        if(substr($url,0,4)=='https'){
            $this->ci->setopt( CURLOPT_SSL_VERIFYPEER, false); 
        }
        $this->ci->setopt( CURLOPT_URL, $url); 
    }

    //爬取数据
    public function getData(){
begin:
        $response = $this->ci->exec();
        //重连次数
        $reconnect_times=empty($this->config['reconnect'])?0:$this->config['reconnect'];
        
        //重新连接
        while($response==false && $this->ci->getLastError()==CURLE_OPERATION_TIMEDOUT && $reconnect_times-->0){
            $response = $this->ci->exec();
        }
        //重连失败则更换代理继续重新连接
        if($response==false && $this->config['proxy_open']==true
                            && $this->config['reconnect_proxy']==true){
            
            if($this->reconnect_proxy_no++>=$this->config['reconnect_proxy_time']){
                $this->proxy_times=$this->config['muti_proxy_times'];//请求多少次更换代理
                $this->setProxy($this->proxy[++$this->proxy_no]);
            }else{
                goto begin;
            }
            
            
        }
        $this->reconnect_proxy_no=0;
        //使用多个代理的话，需要处理代理更换
        if($this->config['proxy_open']==true && $this->config['muti_proxy']==true){
            if(isset($this->proxy_times) && $this->proxy_times--==0){
                $this->proxy_times=$this->config['muti_proxy_times'];//请求多少次更换代理
                //代理循环使用
                if($this->proxy_no<count($this->proxy)){
                    $this->setProxy($this->proxy[$this->proxy_no++]);
                }else{
                    $this->proxy_no=0;
                    $this->setProxy($this->proxy[$this->proxy_no++]);
                }
                
            }
        }
        

        return $response;
    }

    //关闭curl
    public function __destruct(){
        
    }


}
