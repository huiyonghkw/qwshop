<?php
namespace App\Services;

use App\Http\Resources\Home\CashResource\CashCollection;
use App\Models\Cash;
use App\Models\UserCheck;

class CashService extends BaseService{


    // 获取提现列表
    public function getCash($auth='user'){
        $cash_model = new Cash();
        if($auth == 'user'){
            $cash_model = $cash_model->where('user_id','>',0)->where('store_id',0);
        }else{
            $cash_model = $cash_model->where('user_id',0)->where('store_id','>',0);
        }
        $list = $cash_model->orderBy('id','desc')->paginate(request()->per_page??30);
        return $this->format(new CashCollection($list));
    }

    // money
    public function add($auth = 'user'){
        $cash_model = new Cash();
        $money = abs(request()->money)+0;

        // 金额不能小于100
        if($money<100){
            return $this->format_error(__('users.cash_money_gt100'));
        }
        if($auth == 'user'){
            $user_service = new UserService;
            $user_info = $user_service->getUserInfo();
            $uc_model = new UserCheck();

            if($money>$user_info['money']){
                return $this->format_error(__('users.cash_money_insufficient'));
            }

            $uc_info = $uc_model->where('user_id',$user_info['id'])->first();
            if(!$uc_info){
                return $this->format_error(__('base.error'));
            }
            $cash_model->user_id = $user_info['id'];
            $cash_model->money = $money;
            $cash_model->name = $uc_info->name;
            $cash_model->card_no = $uc_info->card_no;
            $cash_model->bank_name = $uc_info->bank_name;

            // 资金冻结处理
            $ml_service = new MoneyLogService();
            $ml_service->editMoney('资金提现',$user_info['id'],-$money);
            $ml_service->editMoney('资金提现',$user_info['id'],$money,1);
        }

        // 店铺提现
        if($auth=='seller'){
            $store_id = $this->get_store(true);
            $cash_model->store_id = $store_id;
            $cash_model->money = $money;
            $cash_model->name = request()->name;
            $cash_model->name = request()->card_no;
            $cash_model->name = request()->bank_name;
        }

        $rs = $cash_model->save();
        return $this->format($rs,__('users.cash_success'));
    }

    // 修改提现到账状态
    public function edit($id,$auth='user'){
        $cash_model = new Cash();
        $cash_status = request()->cash_status;
        $refuse_info = request()->refuse_info??'';
        
        // 余额日志处理
        if($auth == 'user'){
            
        }

        // 余额日志处理
        if($auth == 'seller'){

        }

        $cash_info = $cash_model->where('id',$id)->first();
        if(!$cash_info){
            return $this->format_error(__('base.error'));
        }
        $cash_info->cash_status = $cash_status;
        $cash_info->refuse_info = $refuse_info;
        $rs = $cash_info->save();

        return $this->format($rs);
    }
}