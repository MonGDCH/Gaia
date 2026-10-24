<?php

declare(strict_types=1);

namespace app\http\controller\{{MODULE}};

use mon\http\Request;
use support\http\Controller;
use app\service\{{NAME}}Service;

/**
 * {{DESC}} 控制器
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
class {{NAME}}Controller extends Controller
{
    /**
     * 列表
     *
     * @param Request $request 请求实例
     * @return \mon\http\Response
     */
    public function index(Request $request)
    {
        $data = $request->json();
        $result = {{NAME}}Service::instance()->getList($data);

        return $this->success('ok', $result);
    }

    /**
     * 详情
     *
     * @param Request $request 请求实例
     * @param string $id 记录ID
     * @return \mon\http\Response
     */
    public function detail(Request $request, string $id)
    {
        $info = {{NAME}}Service::instance()->getInfo((int) $id);

        return $this->success('ok', $info);
    }

    /**
     * 新增
     *
     * @param Request $request 请求实例
     * @return \mon\http\Response
     */
    public function add(Request $request)
    {
        $data = $request->json();
        $id = {{NAME}}Service::instance()->add($data);

        return $this->success('操作成功', ['id' => $id]);
    }
}
