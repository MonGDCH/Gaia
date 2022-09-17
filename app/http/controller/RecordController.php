<?php

declare(strict_types=1);

namespace app\http\controller;

use mon\util\File;
use mon\util\Date;
use mon\http\Request;
use mon\http\Response;
use mon\ucenter\UCenter;
use app\service\LogService;
use mon\http\support\Controller;
use mon\http\exception\FileException;
use app\model\consumption\ViwUserBookModel;
use app\model\consumption\ConsumptionCateModel;
use app\model\consumption\ConsumptionRecordModel;
use app\model\consumption\ConsumptionRecordAnnexModel;

/**
 * 账单记录控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RecordController extends Controller
{
    /**
     * 查看记录
     *
     * @param Request $request  请求实例
     * @param integer $record_id    记录ID
     * @return Response
     */
    public function get(Request $request, int $record_id): Response
    {
        $userModel = UCenter::instance()->user();
        $baseInfo = ConsumptionRecordModel::instance()->alias('record')
            ->join(ConsumptionCateModel::instance()->getTable() . ' cate', 'record.cate_id=cate.id')
            ->join($userModel->getTable() . ' user', 'record.uid=user.id')
            ->field(['record.book_id', 'record.type', 'record.price', 'record.oper_time', 'record.comment', 'cate.title', 'cate.icon', 'cate.id AS cate_id', 'user.nickname'])
            ->where('record.id', $record_id)->where('record.status', 1)->find();
        if (!$baseInfo) {
            return $this->error('记录不存在');
        }
        // 验证用户是否拥有记录的查看权限
        $bookInfo = ViwUserBookModel::instance()->where('book_id', $baseInfo['book_id'])->where('uid', $request->uid)->where('status', 1)->find();
        if (!$bookInfo) {
            return $this->error('查看记录失败');
        }
        // 获取图片附件
        $annex = ConsumptionRecordAnnexModel::instance()->where('record_id', $record_id)->field('assets')->select();
        $imgs = [];
        foreach ($annex as $item) {
            $imgs[] = $item['assets'];
        }
        return $this->success('ok', [
            'cate_txt'  => $baseInfo['title'],
            'type'      => $baseInfo['type'],
            'icon'      => $baseInfo['icon'],
            'price'     => $baseInfo['price'],
            'oper_time' => $baseInfo['oper_time'],
            'date_txt'  => date('Y年m月d日', $baseInfo['oper_time']),
            'comment'   => $baseInfo['comment'],
            'user'      => $baseInfo['nickname'],
            'cate_id'   => $baseInfo['cate_id'],
            'imgs'      => $imgs,
        ]);
    }

    /**
     * 获取账本记录列表
     *
     * @param Request $request  请求实例
     * @param integer $pageSize 分页数
     * @param integer $page     当前页
     * @param integer $book_id  账本ID
     * @param string $date      年月日期
     * @return Response
     */
    public function list(Request $request, int $pageSize, int $page, int $book_id, string $date): Response
    {
        if (!check('date', $date . '-01')) {
            return $this->error("date params faild");
        }
        // 验证用户是否拥有记录的查看权限
        $bookInfo = ViwUserBookModel::instance()->where('book_id', $book_id)->where('uid', $request->uid)->where('status', 1)->find();
        if (!$bookInfo) {
            return $this->error('无权查看该账本');
        }
        // 获取月周期时间戳
        $monthGap = (new Date($date . '-01'))->getMonthTime();

        // page等于0时，查询获取总收入支出revenue, pay
        $revenue = 0;
        $pay = 0;
        if ($page == 0) {
            $sql = "SELECT SUM( IF ( type = 1, price, 0 ) ) AS revenue, SUM( IF ( type = 2, price, 0 ) ) AS pay 
                    FROM consumption_record 
                    WHERE `book_id` = ?
                    AND `status` = 1
                    AND `oper_time` BETWEEN ? AND ?";
            $monthData = ConsumptionRecordModel::instance()->query($sql, [$book_id, $monthGap[0], $monthGap[1]]);
            if ($monthData) {
                $revenue = $monthData[0]['revenue'];
                $pay = $monthData[0]['pay'];
            }
        }

        $listData = [];
        $dayData = [];
        // 获取月份中每个日期的收支
        $sql = "SELECT
                    oper_time,
                    SUM( IF ( type = 1, price, 0 ) ) AS revenue,
                    SUM( IF ( type = 2, price, 0 ) ) AS pay 
                FROM consumption_record 
                WHERE `book_id` = ?
                AND `status` = 1
                AND `oper_time` BETWEEN ? AND ?
                GROUP BY oper_time
                ORDER BY oper_time DESC
                LIMIT ?, ?";

        $dayData = ConsumptionRecordModel::instance()->query($sql, [$book_id, $monthGap[0], $monthGap[1], ($page * $pageSize), $pageSize]);
        // 遍历daydata，读取实际列表数据
        $field = ['record.id', 'record.price', 'record.comment', 'record.type', 'cate.icon', 'cate.title AS cate'];
        foreach ($dayData as $item) {
            $list = ConsumptionRecordModel::instance()->alias('record')
                ->join(ConsumptionCateModel::instance()->getTable() . ' cate', 'record.cate_id = cate.id')
                ->where('record.book_id', $book_id)->where('record.status', 1)
                ->where('record.oper_time', $item['oper_time'])
                ->field($field)->select();
            $listData[] = [
                'date'      => date('m月d日', $item['oper_time']),
                'revenue'   => $item['revenue'],
                'pay'       => $item['pay'],
                'list'      => $list
            ];
        }

        return $this->success('ok', [
            'revenue'   => floatval($revenue),
            'pay'       => floatval($pay),
            'listData'  => $listData
        ]);
    }

    /**
     * 查看统计信息
     *
     * @param Request $request  请求实例
     * @param integer $book_id  账本ID
     * @param integer $type     收支类型
     * @param string $date      年月日期
     * @return Response
     */
    public function statis(Request $request, $book_id, $type, string $date): Response
    {
        if (!check('date', $date . '-01')) {
            return $this->error("date params faild");
        }
        // 验证用户是否拥有记录的查看权限
        $bookInfo = ViwUserBookModel::instance()->where('book_id', $book_id)->where('uid', $request->uid)->where('status', 1)->find();
        if (!$bookInfo) {
            return $this->error('无权查看该账本');
        }

        // 获取月周期时间戳
        $monthGap = (new Date($date . '-01'))->getMonthTime();
        // 查排行榜
        $orderField = ['record.id', 'record.price', 'record.comment', 'record.type', 'cate.icon', 'cate.title AS cate'];
        $orderList = ConsumptionRecordModel::instance()->alias('record')
            ->join(ConsumptionCateModel::instance()->getTable() . ' cate', 'record.cate_id = cate.id')
            ->where('record.book_id', $book_id)->where('record.status', 1)
            ->whereBetween('record.oper_time', $monthGap)
            ->where('record.type', $type)
            ->order('price', 'desc')->field($orderField)
            ->limit(10)->select();

        // 查饼状图表
        $pieSql = "SELECT
                        cate.title AS `name`,
                        SUM( record.price ) AS `value` 
                    FROM
                        consumption_record record
                        LEFT JOIN consumption_cate cate ON record.cate_id = cate.id 
                    WHERE
                        record.`status` = 1 
                        AND record.type = ? 
                        AND record.book_id = ? 
                        AND `oper_time` BETWEEN ? AND ?
                    GROUP BY
                        record.cate_id";
        $pieData = ConsumptionRecordModel::instance()->query($pieSql, [$type, $book_id, $monthGap[0], $monthGap[1]]);

        // 查折线图表
        $dateStamp = strtotime($date . '-01');
        // 循环获取上6个月的记录
        $subquerySql = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = date('y-m', strtotime("-{$i} months", $dateStamp));
            $monthDateGap = (new Date('20' . $monthDate . '-01'))->getMonthTime();
            $subquerySql[] = "SELECT IFNULL(SUM(price), 0) AS `coin`, '{$monthDate}' AS `date` FROM consumption_record WHERE `status` = 1 AND `book_id` = {$book_id} AND `type` = {$type} AND `oper_time` BETWEEN {$monthDateGap[0]} AND {$monthDateGap[1]}";
        }
        $lineSql = implode(' UNION ', $subquerySql);
        $lineData = ConsumptionRecordModel::instance()->query($lineSql);

        // 总计
        $total = ConsumptionRecordModel::instance()->where('book_id', $book_id)->where('status', 1)->where('type', $type)->whereBetween('oper_time', $monthDateGap)->sum('price');

        return $this->success('ok', [
            'orderList' => $orderList,
            'pieData'   => $pieData,
            'lineData'  => $lineData,
            'total'     => $total
        ]);
    }

    /**
     * 添加记录
     *
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        $data = $request->post();
        // 设置默认操作时间
        if (isset($data['date']) && !empty($data['date']) && $data['date'] != 'null') {
            $data['oper_time'] = strtotime($data['date']);
        } else {
            $data['oper_time'] = strtotime(date('Y-m-d', time()));
        }
        // 调整图片参数
        if (isset($data['imgs']) && !empty($data['imgs']) && is_string($data['imgs'])) {
            $data['imgs'] = explode(',', $data['imgs']);
        }
        $save = ConsumptionRecordModel::instance()->add($data, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionRecordModel::instance()->getError());
        }

        return $this->success('ok');
    }

    /**
     * 修改记录
     *
     * @param Request $request  请求实例
     * @param integer $record_id    记录ID
     * @return Response
     */
    public function modify(Request $request, int $record_id): Response
    {
        $data = $request->post();
        // 设置默认操作时间
        if (isset($data['date']) && !empty($data['date'])) {
            $data['oper_time'] = strtotime($data['date']);
        } else {
            $data['oper_time'] = strtotime(date('Y-m-d'));
        }
        // 调整图片参数
        if (isset($data['imgs']) && !empty($data['imgs']) && is_string($data['imgs'])) {
            $data['imgs'] = explode(',', $data['imgs']);
        }
        $save = ConsumptionRecordModel::instance()->edit($data, $record_id, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionRecordModel::instance()->getError());
        }

        return $this->success('ok');
    }

    /**
     * 删除记录
     *
     * @param Request $request
     * @return Response
     */
    public function remove(Request $request): Response
    {
        $idx = $this->request->post('record_id');
        if (!check('int', $idx)) {
            return $this->error('params faild');
        }
        $save = ConsumptionRecordModel::instance()->remove($idx, $request->uid);
        if (!$save) {
            return $this->error(ConsumptionRecordModel::instance()->getError());
        }

        return $this->success('ok');
    }

    /**
     * 文件上传
     *
     * @param Request $request
     * @return Response
     */
    public function upload(Request $request): Response
    {
        $file = $request->file('file');
        // 验证文件类型
        if (!in_array($file->getExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
            return $this->error('upload file type faild!');
        }
        // 验证文件大小
        if ($file->getSize() > 10000000) {
            return $this->error('upload file size faild!');
        }
        $date = date('Ym');
        $saveDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . $date . DIRECTORY_SEPARATOR;
        if (!is_dir($saveDir)) {
            $createDir = File::instance()->createDir($saveDir);
            if (!$createDir) {
                return $this->error('创建存储目录失败，请检查写入权限：' . $saveDir);
            }
        }

        try {
            $fileName = uniqid(mt_rand(0, 999999) . 'abc') . '.' . $file->getExtension();
            $saveName = $saveDir . $fileName;
            $moveFile = $file->move($saveName);
            $path = '/upload/' . $date . '/' . $fileName;
            return $this->success('ok', ['url' => $path, 'cdn' => 'http://cdn.gdmon.com/']);
        } catch (FileException $e) {
            LogService::instance()->error('upload exception => ' . $e->getMessage(), 'http', true);
            return $this->error('保存上传文件异常');
        }
    }
}
