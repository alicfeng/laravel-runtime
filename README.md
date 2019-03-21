## Laravel Runtime

#### 前言

`laravel-runtime`此项目为`Laravel`中的插件

无论在开发、测试还是生产环境中，我们皆比较注重请求的数据，一般的做法是将有价值的数据写入在日志当中，便于调试、问题排查等，在`Request`到达控制器前处理数据信息的写入，在此使用`Http`中间件拦截处理，即在请求在中间件中做**日志记载**。

同时、在开发以及测试的时候，一个合格的开发者总是会关注其接口是否存在性能上的问题，比如接口请求的时间消耗多少等，此插件可以通过`CLI`模式在终端以表格的形式展示接口的基本状况，`web`端界面亦可以简约展示，如下图所示

- `CLI终端`

![runtime-cli](https://raw.githubusercontent.com/alicfeng/laravel-runtime/master/file/runtime.jpg)

- `WEB端`

![runtime-web](https://raw.githubusercontent.com/alicfeng/laravel-runtime/master/file/web.png)



#### 功能

- 接口请求的核心数据日志记载
- 接口请求记录分析



#### 环境要求

- `PHP`>=7.0
- `composer`
- `laravel`



#### 安装

```shell
composer require "alicfeng/laravel-runtime"
```



#### 配置

- 在`config/app.php`配置中添加

  ```php
  AlicFeng\Runtime\ServiceProvider\RuntimeServiceProvider::class
  ```

- 在`app/Http/Kernel.php`中添加中间件

  ```php
  \AlicFeng\Runtime\Middleware\RuntimeMiddleware::class
  ```

- 生成配置文件

  ```shell
  php artisan vendor:publish --provider="AlicFeng\Runtime\ServiceProvider\RuntimeServiceProvider"
  ```



#### 使用

- `CLI`

```shell
#使用帮助
➜ php artisan samego:runtime help
usage:
php artisan samego:runtime [help] [--service {reload|analysis}] [--start] [--end]

# 查看接口请求情况分析(支持时间段)
➜ php artisan samego:runtime --service=analysis
➜ php artisan samego:runtime --service=analysis --start={opition|strtotime|0}
➜ php artisan samego:runtime --service=analysis --end={opition|strtotime|time()}

# 分析数据重载
➜ php artisan samego:runtime --service=reload
```

- `WEB`

浏览器打开`$host/runtime/analysis`即可看到展示~