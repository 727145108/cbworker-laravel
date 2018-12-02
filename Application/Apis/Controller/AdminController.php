<?php

/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/4
 * Time: 11:29
 */

namespace Application\Apis\Controller;

use Application\Apis\Events\Warehouse;
use Cbworker\Core\Http\Controller;
use Application\Apis\Services\b as bService;

class AdminController extends Controller
{
  
  protected $bService;
  
  function __construct(bService $bService)
  {
    echo "__construct \n";
    $this->bService = $bService;
  }
  
  public function Login() {
    logger()->error("this is Admin Login Error");
    logger()->debug("this is Admin Login Debug");
    logger()->info("this is Admin Login Info");
    //$items = Capsule::table('supply_stat')->where('id', 15)->get();
    $_id = request()->post('id', 1);
    $info = $this->bService->getDetail($_id);
    event(new Warehouse($info));
    response()->setData(['list' => $info]);
  }

  public function DownLoad() {
    $header  = "HTTP/1.1 200 OK\r\n";
    $header .= "Content-Type: application/vnd.ms-excel\r\n";
    $header .= "Connection: keep-alive\r\n";
    $header .= "Content-Disposition: attachment;filename=dddd.csv\r\n";
    $header .= "Cache-Control: max-age=0\r\n";
    $header .= "Pragma: no-cache;\r\n";
    $header .= "Transfer-Encoding: chunked\r\n";
    //Http::end('商户编号');
    //$len = strlen('debug,') + strlen('demo');
    //$header .= "Content-Length: ". $len ."\r\n\r\n";
    $header .= "\r\n";
    response()->send($header, true);

    ob_start();
    $fp = fopen('php://output', 'a');
    $header1 = ['订单ID','用户手机','姓名','订单状态', '订单金额', '调度费','支付金额','利润金额','开始时间','结束时间','骑行时间','骑行时间(秒)','骑行里程', '结束来源','车辆编号','所属辖区', '二级辖区'];
    foreach ($header1 as $i => $v) {
        $header1[$i] = iconv('utf-8', 'gbk', $v);
    }
    fputcsv($fp, $header1);
    $content = ob_get_contents();
    $length = dechex(strlen($content));
    response()->send("{$length}\r\n{$content}\r\n", true);
    ob_clean();   //清空（擦掉）输出缓冲区
    $data = ['123', '456'];

    echo "fdsaf,fdsfd\r\n";
    echo "fdsaf1111,fdsfd1111\r\n";

    foreach ($data as $key => $value) {
      $order[] = $value;
    }
    fputcsv($fp, $order);
    $content1 = ob_get_contents();    //返回输出缓冲区的内容
    $length1 = dechex(strlen($content1));
    response()->send("{$length1}\r\n{$content1}\r\n", true);
    response()->send("0\r\n\r\n", true);
    ob_end_clean();   //清空（擦除）缓冲区并关闭输出缓冲
    fclose($fp);
  }
}
