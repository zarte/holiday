# holiday

节假日相关库,获取区间内工作日秒数
## 依赖
基于百度万年历接口,下一年放假补办安排需定时更新

## develop
git clone https://github.com/zarte/holiday.git  
cd holiday  
composer install

## demo
composer require zarte/holiday
```php
  $Holiday = new Holiday();
  $Holiday->setFilePath(config('FileSavePath'));
  $year=2021;
  if(!$Holiday->getYearHoliday($year)){
    var_dump($Holiday->errmsg);
   ... 
  }
  $starttime = strtotime('2021-01-01');
  $seconds = $Holiday->getDifferSeconds($starttime,time());

```

## Class Holiday

### setFilePath
设置缓存文件路径
```php
     /**
        * 设置缓存文件路径
        * @param $path
        * @param string $filenamepre
        */
```

### getDayStatus
获取特定日期节假日状态
```php
       /**
        * 获取特定日期节假日状态
        * @param int $day
        * @return int  0平日1假日2补班3周末
        * @throws \Exception
        */
```

### initCache
生成缓存文件
```php
      /**
          * 生成缓存文件
          * @param $year 2021
          * @return bool
          */
```

### getDifferSeconds
返回两个日期相差不含非工作日的秒数
```php
 /**
     * 返回两个日期相差不含非工作日的秒数
     * @param $stattime 时间戳（秒)
     * @param $endtime
     * @return int
     * @throws \Exception
     */
```

### getYearHoliday
获取节假日与补班列表
```php
  /**
      * 获取节假日与补班列表
      * @param $year
      * @return bool|mixed
      */
```
## 测试
php ./phpunit-6.5.3.phar  -c  ./phpunit.xml --filter=HolidayTest::testCheckDayStatus
php ./phpunit-6.5.3.phar  -c  ./phpunit.xml --filter=HolidayTest::testDifferSeconds