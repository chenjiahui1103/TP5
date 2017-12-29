<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/27
 * Time: 10:51
 */
namespace app\socket\controller;

use Workerman\Worker;
use Workerman\Lib\Timer;

class Socket
{
    private $worker = '';

    public function Server()
    {
        vendor('workerman.Autoloader');
        // 心跳间隔25秒
        define('HEARTBEAT_TIME', 25);
        //日志
//        Worker::$logFile = '/tmp/workerman.log';
//        // 证书最好是申请的证书
//        $context = array(
//            'ssl' => array(
//                'local_cert' => '/etc/nginx/conf.d/ssl/server.pem', // 也可以是crt文件
//                'local_pk'   => '/etc/nginx/conf.d/ssl/server.key',
//            )
//        );
//// 这里设置的是websocket协议
//        $worker = new Worker('websocket://0.0.0.0:4431', $context);
//// 设置transport开启ssl，websocket+ssl即wss
//        $worker->transport = 'ssl';
        $worker = new Worker('websocket://0.0.0.0:1234');
        $this->worker = $worker;
        // 设置实例的名称
        $worker->name = 'MyWebsocketWorker';
        //协议类设置
//        $worker->protocol = 'Workerman\\Protocols\\Http';
        //启动4个进程
        $worker->count = 1;
        $worker->onWorkerStart = function ($worker) {
            // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
            $inner_text_worker = new Worker('Text://0.0.0.0:5678');
            $inner_text_worker->onMessage = function ($connection, $buffer) {
                $worker = $this->worker;
                // $data数组格式，里面有uid，表示向那个uid的页面推送数据
                $data = json_decode($buffer, true);
                $uid = $data['uid'];
                // 通过workerman，向uid的页面推送数据
                $ret = sendMessageByUid($uid, $buffer, $worker);
                // 返回推送结果
                $connection->send($ret ? 'ok' : 'fail');
            };
            $inner_text_worker->listen();
        };
// 新增加一个属性，用来保存uid到connection的映射
        $worker->uidConnections = array();
// 当有客户端发来消息时执行的回调函数
        $worker->onMessage = function ($connection, $data) {
            $worker = $this->worker;
            // 判断当前客户端是否已经验证,既是否设置了uid
            if (!isset($connection->uid)) {
                // 没验证的话把第一个包当做uid（这里为了方便演示，没做真正的验证）
                $connection->uid = $data;
                /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
                 * 实现针对特定uid推送数据
                 */
                $worker->uidConnections[$connection->uid] = $connection;
            }
            //数据处理函数
            $this->checkData($data,$connection->uid);

        };

// 当有客户端连接断开时
        $worker->onClose = function ($connection) use ($worker) {
            if (isset($connection->uid)) {
                // 连接断开时删除映射
                unset($worker->uidConnections[$connection->uid]);
            }
        };
        // 向所有验证的用户推送数据
        function broadcast($message, $worker)
        {
            foreach ($worker->uidConnections as $connection) {
                $connection->send($message);
            }
        }

// 针对uid推送数据
        function sendMessageByUid($uid, $message, $worker)
        {
            if (isset($worker->uidConnections[$uid])) {
                $connection = $worker->uidConnections[$uid];
                $connection->send($message);
                return true;
            }
            return false;
        }

// 运行所有的worker（其实当前只定义了1个）
        Worker::runAll();
    }

    public function tuisong()
    {
        // 建立socket连接到内部推送端口
        $client = stream_socket_client('tcp://127.0.0.1:5678', $errno, $errmsg, 1);
// 推送的数据，包含uid字段，表示是给这个uid推送
        $data = array('uid' => 'tom', 'percent' => $_REQUEST['a']);
// 发送数据，注意5678端口是Text协议的端口，Text协议需要在数据末尾加上换行符
        fwrite($client, json_encode($data) . "\n");
// 读取推送结果
        echo fread($client, 8192);
    }
    public function checkData($data,$user)
    {
        $data = json_decode($data, true);
        if(empty($data['type'])){
            return false;
        }
        $this->makeData($data,$user);
    }
    public function makeData($data,$user)
    {
        switch ($data['type']) {
            case "user":
                $this->updateUser($data,$user);
                break;
            case 'draw':
                $this->updateImg($data,$user);
            default:
                break;
        }
    }

    // 向所有验证的用户推送数据
    public function broadcast($message)
    {
        $worker = $this->worker;
        foreach ($worker->uidConnections as $connection) {
            $connection->send($message);
        }
    }
// 针对uid推送数据
    public function sendMessageByUid($uid, $message)
    {
        $worker = $this->worker;
        if (isset($worker->uidConnections[$uid])) {
            $connection = $worker->uidConnections[$uid];
            $connection->send($message);
            return true;
        }
        return false;
    }
    //广播个人图片
    public function updateImg($data,$user)
    {
        $data=json_encode($data);
//        $this->sendMessageByUid($user,$data);
        $this->broadcast($data);
    }
    /**
    * 广播在线人数
    * @param  mixed $items
    * @return static
    */
    public function updateUser($data)
    {
        $data['data']=count($this->worker->uidConnections);
        $data=json_encode($data);
        $this->broadcast($data);
    }

}