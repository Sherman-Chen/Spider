<?php
require '../vendor/autoload.php';
require_once '../lagou.php';

//use PHPUnit\Framework\PHPUnit_Extensions_Database_TestCase;


class testSpider extends PHPUnit_Extensions_Database_TestCase{
    public function testSalary(){
        $lagou=new lagou('广州','php');
        //最小工资正常值测试
        $this->assertEquals('3.4',$lagou->getMinSalary('3.4k-10.1k'));
        $this->assertEquals('0',$lagou->getMinSalary('0k-12k'));
        $this->assertEquals('10',$lagou->getMinSalary('10k'));
        //最小工资非法值测试
        $this->assertEquals('0',$lagou->getMinSalary('10'));
        $this->assertEquals('0',$lagou->getMinSalary('10万'));

        //最大工资正常值测试
        $this->assertEquals('10.1',$lagou->getMaxSalary('3.4k-10.1k'));
        $this->assertEquals('3.4',$lagou->getMaxSalary('3.4k以上'));
        //非法值
        $this->assertEquals('0',$lagou->getMaxSalary('3.4k'));
    }
    public function getConnection(){
        $database = 'anay_web';
        $user = 'root';
        $password = 'test';
        $pdo = new PDO('mysql:dbname=anay_web;host=127.0.0.1', $user, $password);
        return $this->createDefaultDBConnection($pdo, $database);
    }
    public function getDataSet(){
        return $this->createFlatXmlDataSet('./xml/job.xml');
    }


    //测试插入到数据库
    public function testInsertDB(){
        $data=array(
            'positionId'=>1,
            'positionName'=>'php',
            'positionAdvantage'=>'没有',
            'salary'=>'10k-20k',
            'companyFullName'=>'php公司',
            'companySize'=>'10人',
            'companyLabelList'=>'没有',
            'industryField'=>'123',
            'education'=>'本科',
            'workYear'=>'10年',
            'createTime'=>'10 September 2000');
        $lagou=new lagou('广州','php');
        $lagou->insertDB($data);
        $queryTable = $this->getConnection()->createQueryTable(
            'job', 'SELECT * FROM job'
        );
        $expectedTable = $this->createFlatXmlDataSet("./xml/job.xml")
                              ->getTable("job");
        $this->assertTablesEqual($expectedTable, $queryTable);
    }
}