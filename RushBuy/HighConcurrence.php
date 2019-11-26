<?php
header("content-type:text/html;charset=utf-8");
//redis 解决秒杀的基础原理
$redis = new Redis();
$redis->connect('127.0.0.1',6379);
$redis->select(1);
$redia_name = 'miaosha';
$num = 10;//模拟有十件商品
$i = 20;//模拟有20个人同时抢购
$n = 0;//验证数据是否正确
while ($i--){
    $n++;
    $nil = mt_rand(1,50);
    if($redis->lLen($redis_name) < $num){
        $redis->rPush($redis_name,$nil);
        echo $nil.'秒杀成功,编号：'.$n.'<br>';
    }else{
        echo $nil.'秒杀结束,编号：'.$n.'<br>';
    }
}


echo "<br>";
echo "=================================";
echo "<br>";

//验证秒杀是否成功
while ($redis->lLen($redis_name) > 0){
    $str = $redis->lPop($redis_name);
    echo "秒杀成功用户".$str.'<br>';
}

$redis->close();


/**
 * 用 redis+锁 解决高并发（Redis实现分布式锁思路）
 * Class RedisLock
 */
class RedisLock {
    private $redisString;
    private $lockedNames = [];
    public function __construct($param = NULL){
        $this->redisString = RedisFactory::get($param)->string;
    }

    /**
     * 加锁
     * @param $name 锁的标识名
     * @param int $timeout 循环获取锁的等待超时时间，在此时间内会一直尝试获取锁直到超时，为0 表示失败后直接返回不等待
     * @param int $expire  当前锁的最大生存时间(秒)，必须大于0，如果超过生存时间锁仍未被释放，则系统会自动强制释放
     * @param int $waitIntervalUs 获取锁失败后挂起再试的时间间隔(微秒)
     * @return [type]      [description]
     */
    public function lock($name,$timeout = 0,$expire = 15,$waitIntervalUs= 100000){
        if ($name == null) return false;

        //获得当前时间
        $now = time();
        //获取锁失败是的等待超时时刻
        $timeoutAt = $now + $timeout;
        //获取锁的最大生存时刻
        $expireAt = $now + $expire;

        $redisKey = "lock:($name)";
        while (true){
            //将redisKey的最大生存时刻存到redis里，过了这个时刻该锁会被自动释放
            //Setnx（SET if Not eXists） 命令在指定的 key 不存在时，为 key 设置指定的值
            //1成功 0失败
            $result = $this->redisString->setnx($redisKey,$expireAt);

            if($result != false){ //判断原先没有，需要设置
                //设置key的失效时间
                //Expire 命令用于设置 key 的过期时间，key 过期后将不再可用。单位以秒计
                $this->redisString->expire($redisKey,$expireAt);
                //将锁标志放到lockedNames数组里
                $this->lockedNames[$name] = $expireAt;
                return true;
            }

            //以秒为单位，返回给定可以的剩余生存时间
            //TTL 命令以秒为单位返回 key 的剩余过期时间。
            //没有值-2  没有剩余时间-1 有时间返回时间
            $ttl=$this->redisString->ttl($redisKey);

            //ttl小于0表示可以上没有设置生存时间（key是不会不存在的，因为前面setnx会自动创建）
            //如果出现这种状况，那就是进程的的某个实例setnx成功后crash导致紧跟着的expire没有被调用
            //这时可以直接设置expire并把锁纳为己用
            if($ttl < 0){
                $this->redisString->set($redisKey,$expireAt);
                $this->lockedNames[$name] = $expireAt;
                return true;
            }

            /******循环请求锁部分*******/
            // microtime(true)计算php程序代码执行消耗时间  ||  Unix 时间戳的微秒数
            if($timeout <= 0 || $timeoutAt < microtime(true)) break;

            //隔 $waitIntervalUs 后继续请求
            usleep($waitIntervalUs);
        }
        return false;
    }

    /**
     * 解锁
     * @param $name   [description]
     * @return bool   [description]
     */
    public function unlock($name){
        //先判断是否存在此锁
        if ($this->isLocking($name)){
            //删除锁
            if($this->redisString->deleteKey("Lock:$name")){
                //清掉lockedNames里的锁标志
                unset($this->lockedNames[$name]);
                return true;
            }
        }
        return false;
    }

    /**
     *  释放当前所有获得的锁
     * @return bool
     */
    public function unlockAll(){
        //此标志是用来标志是否释放所有锁成功
        $allSuccess = true;
        foreach ($this->lockedNames as $name => $expireAt){
            if(false === $this->unlock($name)){
                $allSuccess = false;
            }
        }
        return $allSuccess;
    }

    /**
     * 给当前锁增加指定生存时间，必须大于0
     * @param $name
     * @param $expire
     * @return bool
     */
    public function expire($name,$expire){
        //先判断是否存在该锁
        if($this->isLocking($name)){
            //所指定的生存时间必须大于0
            $expire = max($expire,1);
            //增加锁生存时间
            if($this->redisString->expire("Lock:$name",$expire)) return true;
        }
        return false;
    }

    public function isLocking($name){
        //先看lockedNames[$name]是否存在该锁标志名
        if(isset($this->lockedNames[$name])){
            return (string)$this->lockedNames[$name] = (string)$this->redisString->get("Lock:$name");
        }
        return false;
    }
}

/**
 * 任务队列
 * Class RedisQueue
 */
class RedisQueue{
    private $_redis;
    public function __construct($param = null){
        $this->_redis = RedisFactory::get($param);
    }

    /**
     * 入队一个Task
     * @param $name  队列名称
     * @param $id   任务id（或者其数组）
     * @param int $timeout  入队超时时间(秒)
     * @param int $afterInterval
     * @return bool
     */
    public function enqueue($name,$id,$timeout = 10,$afterInterval = 0){
        //合法性检测
        if (empty($name) || empty($id) || $timeout <= 0) return false;
        //加锁
        if(!$this->_redis->lock->lock("Queue:{$name}",$timeout)){
            \Monolog\Logger::get('queue')->error("enqueue faild becouse of lock failure:name = $name,id = $id");
            return false;
        }

        //入队是以当前时间戳作为score
        $score = microtime(true) + $afterInterval;
        //入队
        foreach ((array)$id as $item){
            //先判断下是否已经存在id了
            if (false === $this->_redis->zset->getScore("Queue:$name",$item)){
                $this->_redis->zset->add("Queue:$name",$score,$item);
            }
        }
        //解锁
        $this->_redis->lock->unlock("Queue:$name");
        return true;
    }

    /**
     * 出队一个Task,需要指定$id 和 $score
     * 如果$score 与队列中的匹配则出队，否则认为该Task已被重新入队过，当前操作按失败处理
     *
     * @param $name
     * @param $id
     * @param $score
     * @param int $timeout
     * @return bool
     */
    public function dequeue($name,$id,$score,$timeout = 10){
        //合法性检测
        if(empty($name) || empty($id) || empty($score)) return false;

        //加锁
        if(!$this->_redis->lock->lock("Queue:$name",$timeout)){
            \Monolog\Logger::get('queue')->error("dequeue faild becouse of lock lailure:name = $name,id = $id");
            return false;
        }
        //出队
        //先取出redis的score
        $serverScore = $this->_redis->zset->getScore("Queue:$name",$id);
        $result = false;
        //先判断传进来的score和redis的score是否一样
        if($serverScore == $score){
            //删除该$id
            $result = (float)$this->_redis->zset->delete("Queue:$name",$id);
            if($result == false){
                \Monolog\Logger::get('queue')->error("dequeue faild because of redis delete failure:name = $name,id = $id");
            }
        }
        $this->_redis->lock->unlock("Queue:$name");
        return $result;
    }

    /**
     * 获取队列顶部若干个Task并将其出队
     * @param $name  队列名称
     * @param int $count  数量
     * @param int $timeout  超时时间
     */
    public function pop($name,$count=1,$timeout = 10){
        //合法性检测
        if(empty($name) || $count <= 0) return [];

        //加锁
        if(!$this->_redis->lock->lock("Queue:$name")){
            \Monolog\Logger::get('queue')->error("pop faild because of pop failure:name = $name,count = $count");
            return false;
        }

        //取出若干的Task
        $result = [];
        $array = $this->_redis->zset->getByScore("Queue:$name",false,microtime(true),true,false,[0,$count]);
        //将其放在$reault数组里 并 删除掉redis对应的id
        foreach ($array as $id => $score){
            $result[] = ['id'=>$id,'score'=>$score];
            //删除队列中的对应id
            $this->_redis->zset->delete("Queue:$name",$id);
        }
        //解锁
        $this->_redis->lock->unlock("Queue:$name");
        return $count == 1?(empty($result)?false:$result[0]):$result;
    }

    /**
     * 获取队列顶部的若干个Task
     * @param  [type]  $name  队列名称
     * @param  integer $count 数量
     * @return [type]         返回数组[0=>['id'=> , 'score'=> ], 1=>['id'=> , 'score'=> ], 2=>['id'=> , 'score'=> ]]
     */
    public function top($name,$count=1){
        //合法性检测
        if(empty($name) || $count < 1) return [];
        //取出若干个Task
        $result = [];
        $array = $this->_redis->zset->getByScore("Queue:$name",false,microtime(true),true,false,[0,$count]);
        //将其放在$reault数组里 不删除redis中的数据
        foreach ($array as $id => $score){
            $result[] = ['id'=>$id,'score'=>$score];
        }

        return $count == 1?(empty($result)?false:$result[0]):$result;
    }

    //////////////////////////////////////////////////////////////////////////////
    /// https://blog.csdn.net/yafei450225664/article/details/78924149
    /// https://blog.csdn.net/weixin_39278982/article/details/81216416

}