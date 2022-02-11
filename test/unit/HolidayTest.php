<?php

use PHPUnit\Framework\TestCase;
use Zarte\Holiday\Holiday;

class HolidayTest extends TestCase {

    public function testdownfile() {
        $Holiday = new Holiday();
        $res = $Holiday->initCache(2022);
        $this->assertTrue($res,'更新假日缓存失败:'.$Holiday->errmsg);
    }


    public function testCheckDayStatus() {
        $Holiday = new Holiday();
        $res = $Holiday->getDayStatus(strtotime('2022-01-01 00:00:00'));
        $this->assertSame(1,$res,'判断元旦失败');
        $res = $Holiday->getDayStatus(strtotime('2022-01-29 00:00:00'));
        $this->assertSame(2,$res,'判断春节前补班失败');
        $res = $Holiday->getDayStatus(strtotime('2022-06-03 00:00:00'));
        $this->assertSame(1,$res,'判断端午节失败');
    }

    public function testDifferSeconds() {
        $Holiday = new Holiday();
        $res = $Holiday->getDifferSeconds(strtotime('2022-01-01 00:00:00'),strtotime('2022-01-02 00:00:00'));
        $this->assertSame(0,$res,'判断节假日');
        $res = $Holiday->getDifferSeconds(strtotime('2022-01-30 22:27:45'),strtotime('2022-02-11 16:07:00'));
        $this->assertSame(409155,$res,'判断春节工作秒数失败');
    }

}

