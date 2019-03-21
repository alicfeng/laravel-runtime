<?php

namespace AlicFeng\Runtime\Command;

use AlicFeng\Runtime\Common\RedisKey;
use AlicFeng\Runtime\Repository\DataRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Debug\Dumper;

class RuntimeAnalysisCommand extends Command
{
    protected $signature   = 'samego:runtime {help?} {--service=} {--start=} {--end=}';
    protected $description = 'samego:runtime';

    /*CLI参数*/
    private $service;// 服务名称
    private $help; // 帮助
    private $startDate = null;// 查询的开始时间
    private $endDate   = null;// 查询的结束时间

    private $dataRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dataRepository = app()->make(DataRepository::class);
    }

    public function handle()
    {
        /*memory_limit*/
        ini_set('memory_limit', config('memory_limit', '64M'));
        /*initialization*/
        $this->init();
        /*execute and work*/
        call_user_func([$this, $this->service]);
        return true;
    }

    /**
     * @functionName   reload
     * @description    reload into redis by reading log file
     * @author         Alicfeng
     * @datetime       19-3-20 上午10:13
     */
    private function reload()
    {
        $this->dataRepository->reload($this->startDate, $this->endDate);
        (new Dumper)->dump('reload finished');
        $this->analysis();
    }

    /**
     * @functionName   analysis
     * @description    analysis including router method count max min average
     * @author         Alicfeng
     * @datetime       19-3-20 上午10:22
     */
    private function analysis()
    {
        $headerTitle = ['router', 'method', 'count', 'max(mx)', 'min(mx)', 'average(mx)'];
        $message     = $this->dataRepository->list($this->startDate, $this->endDate);
        $this->table($headerTitle, $message ?? []);
    }

    /**
     * @functionName   clear redis data
     * @description    clear redis data
     * @author         Alicfeng
     * @datetime       19-3-20 下午7:13
     */
    public function clear()
    {
        $this->dataRepository->clear();
        (new Dumper)->dump('clean finished');
    }

    /**
     * @functionName   help
     * @description    help function
     * @author         Alicfeng
     * @datetime       19-3-20 上午10:23
     */
    private function help()
    {
        (new Dumper)->dump('usage: php artisan samego:runtime [help] [--service {reload|analysis|clear}] [--start] [--end]');
    }

    /**
     * @functionName   initialization
     * @description    initialize about var ...
     * @author         Alicfeng
     * @datetime       19-3-20 上午10:23
     */
    private function init()
    {
        $this->service   = $this->option('service');
        $this->startDate = $this->option('start');
        $this->endDate   = $this->option('end');
        $this->help      = $this->argument('help');
        if (
            /*cli help*/
            'help' == $this->help ||
            /*param error*/
            (!$this->service && !in_array($this->service, ['analysis', 'reload'], true))
        ) {
            $this->help();
        }
    }
}
