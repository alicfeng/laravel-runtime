<?php
/**
 * Created by PhpStorm   User: AlicFeng   DateTime: 19-3-19 下午5:50
 */

namespace AlicFeng\Runtime\Middleware;

use AlicFeng\Runtime\Common\Application;
use AlicFeng\Runtime\Repository\DataRepository;
use Closure;
use Illuminate\Http\Request;
use Log;

class RuntimeMiddleware
{
    private $message = [];

    public function handle(Request $request, Closure $next)
    {
        /*trace request core message into log with trace-model*/
        if (true === config('runtime.trace', false)) {
            Log::info('trace request message begin');
            Log::info('router  : ' . $request->path());
            Log::info('method  : ' . $request->method());
            Log::info('ip      : ' . $request->ip());
            Log::info('params  : ' . json_encode($request->all(), JSON_UNESCAPED_UNICODE));
            Log::info('trace request message end');
        }
        return $next($request);
    }

    public function terminate(Request $request, $response)
    {
        // condition :: http request as well as setting started status
        if (false === app()->runningInConsole() && true === config('runtime.status', false)) {
            // message structure including method,router,time
            $this->message['datetime'] = time();
            $this->message['method']   = $request->method();
            $this->message['router']   = $request->path();
            $this->message['consume']  = intval((microtime(true) - LARAVEL_START) * 1000);

            /*real time load into redis( analysis )*/
            $dataRepository = app()->make(DataRepository::class);
            $dataRepository->addToCache($this->message);

            /*real time load into logfile( analysis )*/
            if ('daily' === config('runtime.log', 'daily')) {
                /*daily model*/
                $logPath = Application::getRuntimeLogPath() . date('Ymd') . '.log';
            } else {
                /*single model*/
                $logPath = Application::getRuntimeLogPath() . 'runtime.log';
            }
            /*specify logfile location*/
            Log::useFiles($logPath);
            Log::debug(Application::getSign() . implode('||', $this->message));
        }
    }
}