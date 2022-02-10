<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashesController extends Controller
{
    protected $modelName = 'Cash';
    public $auth = 'users';
    
    public function store(Request $request)
    {
        $money = round(abs($request->money), 2);
        $storeInfo = $this->getService('Store')->getStoreInfo(true)['data'];
        if ($money == 0) {
            return $this->error(__('tip.cash.moneyZero'));
        }
        if ($storeInfo['store_money']<$money) {
            return $this->error(__('tip.cash.moneyNotEnough'));
        }
        $model = $this->getService('Cash', true);
        $model->name = $request->name??'';
        $model->bank_name = $request->bank_name??'';
        $model->card_no = $request->card_no??'';
        $model->store_id = $storeInfo['id'];
        $model->money = $money;
        $model->remark = $request->remark??'';
        try {
            DB::beginTransaction();
            $model->save();
            $rs = $this->getService('MoneyLog')->edit([
                'name'=>'商家提现',
                'money'=>-$money,
                'is_type'=>0,
                'is_belong'=>1,
            ]);
            if (!$rs['status']) {
                throw new \Exception($rs['msg']);
            }
            $rs = $this->getService('MoneyLog')->edit([
                'name'=>'商家提现',
                'money'=>$money,
                'is_type'=>1,
                'is_belong'=>1,
            ]);
            if (!$rs['status']) {
                throw new \Exception($rs['msg']);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
        
        return $this->success();
    }


    public function destroy($id)
    {
        $tableModel = $this->getService($this->modelName, true);
        if (!empty($where)) {
            $tableModel = $tableModel->where($where);
        }
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $storeId = $this->getService('Store')->getStoreId()['data'];
        $tableModel = $tableModel->where('store_id', $storeId)->whereIn('id', $idArray)->get();
        foreach ($tableModel as $v) {
            $this->getService('MoneyLog')->edit([
                'name'=>'取消提现',
                'money'=>$v['money'],
                'is_type'=>0,
                'is_belong'=>1,
            ]);
            $this->getService('MoneyLog')->edit([
                'name'=>'取消提现',
                'money'=>-$v['money'],
                'is_type'=>1,
                'is_belong'=>1,
            ]);
        }

        $rs = $this->getService($this->modelName, true)->where('store_id', $storeId)->whereIn('id', $idArray)->delete();
        if (!$rs) {
            return $this->formatError('find data error.');
        }
        return $this->format($rs);
    }
}
