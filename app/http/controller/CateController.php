<?php

declare(strict_types=1);

namespace app\http\controller;

use mon\http\Request;
use mon\http\Response;
use mon\http\support\Controller;
use app\model\consumption\ConsumptionCateModel;

/**
 * 分类控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class CateController extends Controller
{
    /**
     * 获取分类
     *
     * @param Request $request  请求实例
     * @param integer $type     分类类型
     * @param integer $custom   是否自定义
     * @return Response
     */
    public function getCate(Request $request, int $type, int $custom): Response
    {
        $data = ConsumptionCateModel::instance()->getUserCate($request->uid, $type, ($custom == 1));
        return $this->success('ok', $data);
    }

    /**
     * 新增分类
     *
     * @param Request $request  请求实例
     * @return Response
     */
    public function add(Request $request): Response
    {
        $data = $request->post();
        // 用户添加，设置默认的icon
        $data['icon'] = 'icon-mingxi';
        $save = ConsumptionCateModel::instance()->add($data, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionCateModel::instance()->getError());
        }

        return $this->success('ok');
    }

    /**
     * 编辑分类
     *
     * @param Request $request  请求实例
     * @param integer $cate_id  分类ID
     * @return Response
     */
    public function modify(Request $request, int $cate_id): Response
    {
        $data = $request->post();
        // 用户修改，设置默认的icon
        $data['icon'] = 'icon-mingxi';
        $save = ConsumptionCateModel::instance()->edit($data, $cate_id, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionCateModel::instance()->getError());
        }

        return $this->success('ok');
    }

    /**
     * 删除分类
     *
     * @param Request $request  请求实例
     * @return Response
     */
    public function remove(Request $request): Response
    {
        $cate_id = $request->post('cate_id');
        if (!check('int', $cate_id)) {
            return $this->error('params faild');
        }
        $save = ConsumptionCateModel::instance()->remove((int)$cate_id, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionCateModel::instance()->getError());
        }

        return $this->success('ok');
    }
}
