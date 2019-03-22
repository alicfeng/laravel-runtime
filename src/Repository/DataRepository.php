<?php
/**
 * Created by PhpStorm   User: AlicFeng   DateTime: 19-3-20 下午8:52
 */

namespace AlicFeng\Runtime\Repository;


use AlicFeng\Runtime\Common\Application;
use AlicFeng\Runtime\Common\RedisKey;
use DateTime;
use Log;

class DataRepository
{
    private $redis;

    public function __construct()
    {
        $this->redis = app('redis');
    }

    public function list($starDate, $endDate)
    {
        // needed reload when specify startDate or endDate
        if ($starDate || $endDate) {
            $this->reload($starDate, $endDate);
            $this->redis->set(RedisKey::CONDITION_CACHE, 1);
            return json_decode($this->query(), true);
        }

        // Prevent the last read data from still retaining the cache
        if (1 === $this->redis->get(RedisKey::CONDITION_CACHE)) {
            $this->redis->set(RedisKey::CONDITION_CACHE, 0);
            $this->reload($starDate, $endDate);
        }
        return json_decode($this->query(), true);
    }

    /**
     * @functionName   reload analysis data by reading logfile
     * @description    reload analysis data by reading logfile
     * @author         Alicfeng
     * @datetime       19-3-21 下午10:16
     * @param string $starDate startDate
     * @param string $endDate endDate
     */
    public function reload($starDate, $endDate)
    {
        try {
            $startDateTime = $starDate ? new DateTime($starDate) : new DateTime('1970-01-01');

            $endDateTime = $endDate ? new DateTime($endDate) : new DateTime();
            $list        = [];
            $this->clear();

            if ('daily' === config('runtime.log', 'daily')) {
                if (!is_dir(Application::getRuntimeLogPath())) {
                    return;
                }
                $logs     = scandir(Application::getRuntimeLogPath());
                $logFiles = array_filter($logs, function ($value) {
                    /*filter . .. runtime.log*/
                    return !in_array($value, ['.', '..', 'runtime.log'], true);
                });
                $logFiles = array_filter($logFiles, function ($value) use ($startDateTime, $endDateTime) {
                    /*datetime filter*/
                    $time = strtotime(explode('.log', $value)[0]);
                    return $time >= $startDateTime->getTimestamp() && $time <= $endDateTime->getTimestamp();
                });
                foreach ($logFiles as $logFile) {
                    /*read content by loop through*/
                    $this->readFile($list, Application::getRuntimeLogPath() . $logFile, $startDateTime, $endDateTime);
                }
            } else {
                $logPath = Application::getRuntimeLogPath() . 'runtime.log';
                $this->clear();
                $this->readFile($list, $logPath, $startDateTime, $endDateTime);
            }
            /*SORT_DESC*/
            array_multisort(array_column($list, 'count'), SORT_DESC, $list);
            $this->redis->set(RedisKey::RUNTIME_LIST, json_encode($list, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * @functionName   read analysis data collection by logfile
     * @description    read analysis data collection by logfile
     * @author         Alicfeng
     * @datetime       19-3-21 下午10:10
     * @param array $list analysis data collection
     * @param string $file logfile path
     * @param DateTime $startDateTime start datetime
     * @param DateTime $endDateTime end datetime
     */
    public function readFile(array &$list, string $file, DateTime $startDateTime, DateTime $endDateTime)
    {
        if (file_exists($file)) {
            $runtimeLog = fopen($file, 'r');
            while (!feof($runtimeLog)) {
                $content = fgets($runtimeLog);
                if (strpos($content, Application::getSign()) !== false) {
                    $info = explode('||', explode(Application::getSign(), trim($content))[1]);

                    // fetch data O(∩_∩)O哈哈~
                    $message = [
                        'datetime' => current($info),
                        'method'   => next($info),
                        'router'   => next($info),
                        'consume'  => end($info)
                    ];

                    /*(⊙o⊙)嗯、继续*/
                    if ($message['datetime'] < $startDateTime->getTimestamp()) continue;
                    /*(⊙o⊙)嗯、完了*/
                    if ($message['datetime'] > $endDateTime->getTimestamp()) break;

                    $sign = md5($message['method'] . $message['router']);
                    if (array_key_exists($sign, $list)) {
                        $list[$sign]['count']   += 1;
                        $list[$sign]['max']     = max([$message['consume'], $list[$sign]['max']]);
                        $list[$sign]['min']     = min([$message['consume'], $list[$sign]['min']]);
                        $list[$sign]['average'] = intval(($message['consume'] + $list[$sign]['average']) / 2);
                    } else {
                        $list[$sign]['router']  = $message['router'];
                        $list[$sign]['method']  = $message['method'];
                        $list[$sign]['count']   = 1;
                        $list[$sign]['max']     = $message['consume'];
                        $list[$sign]['min']     = $message['consume'];
                        $list[$sign]['average'] = $message['consume'];
                    }
                }
            }
            fclose($runtimeLog);
        }
    }

    /**
     * @functionName   clear data of analysis
     * @description    clear data of analysis
     * @author         Alicfeng
     * @datetime       19-3-21 下午10:07
     */
    public function clear()
    {
        $this->redis->del(RedisKey::RUNTIME_LIST);
    }

    /**
     * @functionName   query data of analysis
     * @description    query data of analysis
     * @author         Alicfeng
     * @datetime       19-3-21 下午10:08
     * @return mixed|object
     */
    public function query()
    {
        return $this->redis->get(RedisKey::RUNTIME_LIST);
    }

    /**
     * @functionName   load request data into redis
     * @description    real time
     * @author         Alicfeng
     * @datetime       19-3-21 下午10:08
     * @param array $message
     */
    public function addToCache(array $message)
    {
        $cache     = $this->redis->get(RedisKey::RUNTIME_LIST);
        $container = json_decode($cache, true);
        $sign      = md5($message['method'] . $message['router']);
        if (false !== $cache && array_key_exists($sign, $container ?? [])) {
            $container[$sign]['count']   += 1;
            $container[$sign]['max']     = max($message['consume'], $container[$sign]['max']);
            $container[$sign]['min']     = min($message['consume'], $container[$sign]['min']);
            $container[$sign]['average'] = intval(($message['consume'] + $container[$sign]['average']) / 2);
        } else {
            $container[$sign]['router']  = $message['router'];
            $container[$sign]['method']  = $message['method'];
            $container[$sign]['count']   = 1;
            $container[$sign]['max']     = $message['consume'];
            $container[$sign]['min']     = $message['consume'];
            $container[$sign]['average'] = $message['consume'];
        }
        /*sort desc*/
        array_multisort(array_column($container, 'count'), SORT_DESC, $container);
        $this->redis->set(RedisKey::RUNTIME_LIST, json_encode($container, JSON_UNESCAPED_UNICODE));
    }
}
