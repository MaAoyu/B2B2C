<?php
/**
 * 队列
*
*
*
*
* by 33hao 好商城V3  www.33hao.com 开发
*/
defined('InShopNC') or exit('Access Invalid!');
ini_set('default_socket_timeout', -1);
class queueControl extends BaseCronControl {

    public function indexOp() {
        if (ob_get_level()) ob_end_clean();

        $logic_queue = Logic('queue');

        $worker = new QueueServer();
        $queues = $worker->scan();
        while (true) {
            $content = $worker->pop($queues,1800);
            if (is_array($content)) {
                $method = key($content);
                $arg = current($content);

                $result = $logic_queue->$method($arg);
                if (!$result['state']) {
                    $this->log($result['msg'],false);
                }
//                 echo date('Y-m-d H:i:s',time()).' '.$method."\n";
//                 flush();
//                 ob_flush();
            } else {
                $model = Model();
                $model->checkActive();
                unset($model);
//                 echo date('Y-m-d H:i:s',time())."  ---\n";
//                 flush();
//                 ob_flush();
            }
        }
    }
}