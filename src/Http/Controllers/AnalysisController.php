<?php
/**
 * Created by PhpStorm   User: AlicFeng   DateTime: 19-3-20 下午7:21
 */

namespace AlicFeng\Runtime\Http\Controllers;


use AlicFeng\Runtime\Repository\DataRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    private $redis;
    private $request;
    private $dataRepository;

    public function __construct(Request $request)
    {
        $this->redis          = app('redis');
        $this->dataRepository = app()->make(DataRepository::class);
        $this->request        = $request;
    }

    /**
     * @functionName   analysis page
     * @description    web display page
     * @author         Alicfeng
     * @datetime       19-3-21 下午10:01
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\think\response\View
     */
    public function analysis()
    {
        return view('runtime.analysis');
    }

    /**
     * @functionName   analysis data collection
     * @description    simple  {"code":"0000","ret":"success","data":{"ca0899f87f526f2f4a3268e08bef54ae":{"router":"runtime/analysis","method":"GET","count":16,"max":"621","min":"101","average":155},"8f30251dd30cba802e973ab84a57efce":{"router":"runtime/list","method":"POST","count":15,"max":"3295","min":"69","average":1947},"4bee8e732ffbdb24d6f1078277e03dae":{"router":"content/article/list","method":"GET","count":3,"max":"785","min":"682","average":720}}}
     * @author         Alicfeng
     * @datetime       19-3-21 下午9:58
     * @return string json format
     */
    public function list()
    {
        set_time_limit(0);
        $startDate = $this->request->get('startDate');
        $endDate   = $this->request->get('endDate');
        $data      = $this->dataRepository->list($startDate, $endDate);
        return json_encode(['code' => '0000', 'ret' => 'success', 'data' => $data]);
    }

    /**
     * @functionName   reload
     * @description    reload analysis data collection
     * @author         Alicfeng
     * @return string json format
     */
    public function reload()
    {
        $startDate = $this->request->get('startDate');
        $endDate   = $this->request->get('endDate');
        $this->dataRepository->reload($startDate, $endDate);
        return json_encode(['code' => '0000', 'ret' => 'success', 'data' => '']);
    }

    /**
     * @functionName   clear
     * @description    clear analysis data collection
     * @author         Alicfeng
     * @datetime       19-3-21 下午10:00
     * @return string json format
     */
    public function clear()
    {
        $this->dataRepository->clear();
        return json_encode(['code' => '0000', 'ret' => 'success', 'data' => '']);
    }
}