<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/4
 * Time: 9:40
 */

namespace Cbworker\Core\Http;

use Workerman\Protocols\Http;
use Illuminate\Support\ServiceProvider;
use Cbworker\Core\AbstractInterface\Response;

class HttpResponse extends ServiceProvider implements Response
{

  protected $_connection = null;

  protected $_code = 200;

  protected $_message = null;

  protected $_data = array();

  protected $_headers = array(
    'Access-Control-Allow-Origin:*',
    'Content-type: application/json;charset=utf-8'
  );

  protected $_raw = false;

  protected $_rawData = '';

  protected $_result = null;


  function __construct($connection)
  {
    $this->_connection = $connection;
  }

  public function send($message = '', $raw = false) {
    foreach ($this->_headers as $header) {
      Http::header($header);
    }
    if(!empty($message)) {
      $this->_connection->send($message, $raw);
    } else {
      $message = json_encode($this->build());
      $this->_connection->send($message);
      $this->_data = '';
    }
  }

  public function close() {
    $this->_connection->close();
  }

  public function build() {
    if($this->_raw) {
      return $this->_rawData;
    }
    return array_merge(array('code' => $this->getCode(), 'message' => $this->getMessage()), $this->_data);
  }

  public function header() {
    return $this->_headers;
  }

  public function getRaw() {
    return $this->_raw;
  }

  public function setRaw($bool = false) {
    $this->_raw = $bool;
  }

  public function getRawData() {
    return $this->_rawData;
  }

  public function setRawData($str) {
    $this->_rawData .= $str;
  }

  public function setCode($code = 0) {
    $this->_code = $code;
  }

  public function getCode() {
    return $this->_code;
  }

  public function setMessage($message) {
    $this->_message = $message;
  }

  public function getMessage() {
    if(empty($this->_message)) {
      $this->_message = config("lang.zh.{$this->_code}", 'unknown');
    }
    return $this->_message;
  }

  /**
   * [setHeader 设置头部信息]
   * @param array $params [description]
   */
  public function setHeader($params) {
    $this->_headers[] = $params;
  }

  /**
   * [setData 设置内容]
   * @param [type] $data [description]
   * @param string $raw  [description]
   */
  public function setData($data) {
    $this->_data = $data;
  }

  public function destroy() {
    $this->_connection = null;
    $this->_data = null;
    $this->_message = null;
    $this->_rawData = null;
  }


}
