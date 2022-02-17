<?php declare(strict_types=1);
namespace Zarte\Holiday;

class Holiday
{
    private $filepath='./';
    private $filenamepre='holiday_';
    private $apiurl='https://sp1.baidu.com/8aQDcjqpAAV3otqbppnN2DJv/api.php';
    public $errmsg ='';

    public function __construct()
    {

    }


    /**
     * 设置缓存文件路径
     * @param $path
     * @param string $filenamepre
     */
    public function setFilePath($path,$filenamepre='holiday_'){
        $this->filepath = $path.'/';
        $this->filenamepre = $filenamepre;
    }

    /**
     * 获取缓存文件路径
     * @return string
     */
    public function getFilePath():string
    {
        return $this->filepath;
    }


    /**
     * 获取特定日期节假日状态
     * @param int $day
     * @return int 0平日1假日2补班3周末
     * @throws \Exception
     */
    public  function getDayStatus(int $day):int
    {
        //默认中国标准时间
        date_default_timezone_set('PRC');
        if(!date('Ymd',$day)){
            throw new \Exception('day format error!');
        }
        $year = date('Y',$day);
        $list = $this->getYearHoliday($year);
        if($list===false){
            throw new \Exception($this->errmsg);
        }
        $matchdate = date('Ymd',$day);
        foreach ($list as $item){
            if($item['date']==$matchdate){
                return $item['status'];
            }
        }
        $week = date("w",$day);
        if($week>0&& $week<6){
            return 0;
        }else{
            return 3;
        }
    }

    /**
     * 返回两个日期相差不含非工作日的秒数
     * @param $stattime 时间戳（秒)
     * @param $endtime
     * @return int
     * @throws \Exception
     */
    public function getDifferSeconds(int $stattime,int $endtime):int
    {
        //默认中国标准时间
        date_default_timezone_set('PRC');
        if($stattime>=$endtime){
            $tmp = $endtime;
            $endtime = $stattime;
            $stattime = $tmp;
        }
        $seconds =0;
        while ($stattime<$endtime){
            //获取当天结束时间
           $dayendtime =  strtotime(date('Y-m-d 23:59:59',$stattime))+1;
           if($dayendtime>$endtime){
               $dayendtime=$endtime;
           }
            //判断当天是否为工作日
            $daystatus = $this->getDayStatus($stattime);
           if($daystatus==0|| $daystatus==2){
               $seconds +=($dayendtime-$stattime);
           }
            $stattime = $dayendtime;
        }
        return  $seconds;
    }
    /**
     * 检查文件缓存
     * @param $year
     * @return bool
     */
    public function checkCache($year){
        $file = $this->filepath.$this->filenamepre.$year.'.data';
        if(!file_exists($file)||!is_file($file)){
          return false;
        }else{
            return true;
        }
    }
    /**
     * 获取节假日与补班列表
     * @param $year
     * @return bool|mixed
     */
    public function getYearHoliday($year){
        $file = $this->filepath.$this->filenamepre.$year.'.data';
        if(!file_exists($file)||!is_file($file)){
          //自动获取缓存
            try{
                $this->downFile($year);
            }catch (\Throwable $e){
                $this->errmsg='downfile:'.$year.'-'.$e->getMessage();
                return false;
            }
        }
        $filecontent =file_get_contents($file);
        if(!$filecontent){
            $this->errmsg='file_get_contents year'.$year.' fail';
            return false;
        }
        $dataarr = json_decode($filecontent,true);
        if($dataarr===false){
            $this->errmsg='json year'.$year.' fail';
            return false;
        }

        return $dataarr;
    }

    /**
     * 生成缓存文件
     * @param $year
     * @return bool
     */
    public function initCache($year){
        if(!$year){
            return false;
        }
        try{
            return $this->downFile($year);
        }catch (\Throwable $e){
            $this->errmsg='downfile:'.$year.'-'.$e->getMessage();
            return false;
        }
    }
    private function downFile($year){
        //从百度接口获取
        $tn='wisetpl';
        $format='json';
        $resource_id='39043';
        date_default_timezone_set('PRC');
        $month=1;
        $yeardatalist = array();
        $term='';
        //按月获取数据
        while ($month<=12){
            $curmonth=$month;
            $query=urlencode($year.'年'.$month.'月');
            $month++;
            $t=time();
            $cb='op_aladdin_callback'.$t;
            $url = $this->apiurl.'?tn='.$tn.'&format='.$format.'&resource_id='.$resource_id.'&query='.$query.'&t='.$t.'&cb='.$cb;
            $return = $this->httpget($url,array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36'
            ));
            if($return){
               $machstr = $year.($curmonth<10?'0'.$curmonth:$curmonth);
               //返回值格式化为数组
               $list =  $this->formateContent($return,$cb);
               if(!isset($list['status']) || $list['status']!=0){
                   throw new \Exception('get month:'.$curmonth.' status fail');
               }
               foreach($list['data'][0]['almanac'] as $item){
                   if(date('Ym',strtotime($item['oDate']))==$machstr){
                       //当前月数据
                       if(!isset($item['status'])){
                           //非节假日直接判断
                           continue;
                       }
                       if(isset($item['term']) && $item['term']){
                           //春节部分假期会标识为立春
                           $term=$item['term'];
                       }
                       if($item['status']==2){
                           //补班
                           $yeardatalist[]=array(
                               'status'=>2,
                               'date'=>date('Ymd',strtotime($item['oDate'])),
                               'term'=>''
                           );
                       }else if($item['status']==1){
                           //假日
                           $yeardatalist[]=array(
                               'status'=>1,
                               'date'=>date('Ymd',strtotime($item['oDate'])),
                               'term'=>$term
                           );
                       }
                   }
               }
            }else{
                throw new \Exception('get month:'.$month.' fail');
            }
        }
        if(!$yeardatalist){
            throw new \Exception('get year:'.$year.' data fail');
        }
        //存储数据
        $res = file_put_contents($this->filepath.$this->filenamepre.$year.'.data',json_encode($yeardatalist));
        if(!$res){
            throw new \Exception('file_put_contents year:'.$year.' fail');
        }
        return true;
    }
    private function  formateContent($content,$cb='op_aladdin_callback'){
        if(strpos($content,'/**/'.$cb)===false){
           throw new \Exception('content formate err!'.$cb);
        }
        $p=strpos($content,'(');
        if(!$p){
            return array();
        }
        $content = substr($content,$p+1,-2);
        $content = @iconv('gbk','utf-8//IGNORE',$content);
        return json_decode($content,true);
    }

    private function httpget($url,$header=array()){
        $ch = curl_init($url);
        $header=array();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //跟踪重定向
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('curl fail:'.curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }


}
