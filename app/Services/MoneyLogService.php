<?php
namespace App\Services;

use App\Http\Resources\Home\MoneyLogResource\MoneyLogCollection;
use App\Models\MoneyLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MoneyLogService extends BaseService{

    /**
     * 修改用户金额 增加日志 function
     *
     * @param [type] $user_id 用户ID
     * @param [type] $money 金额
     * @param [type] $name 名称  订单支付|后台操作
     * @param integer $type 类型 0 金额 1冻结资金 2积分
     * @return void
     * @Description
     * @author hg <www.qingwuit.com>
     */
    public function editMoney($name,$user_id,$money,$type=0,$info=''){
        try{
            DB::beginTransaction();
            $user_model = new User();
            $ml_model = new MoneyLog();
            $ml_model->user_id = $user_id;
            $ml_model->money = $money;
            $ml_model->is_type = $type;
            $ml_model->name = $name;
            $ml_model->info = $info;
            $ml_model->save();
            $user_model = $user_model->find($user_id);
            switch($type){
                case 0:
                    $user_model->money += $money;
                break;
                case 1:
                    $user_model->frozen_money += $money;
                break;
                case 1:
                    $user_model->integral += $money;
                break;
            }
            $user_model->save();
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            return $this->format_error(__('users.money_log_error'));
        }
    }

    // 获取日志列表
    public function getMoneyLog($auth="user"){
        $money_log_model = new MoneyLog();
        if($auth == 'user'){
            $user_service = new UserService;
            $user_info = $user_service->getUserInfo();
            $money_log_model = $money_log_model->where('user_id',$user_info['id']);
        }
        $list = $money_log_model->where('is_type',request()->is_type??0)->paginate(request()->per_page??30);

        if($auth == 'user'){
            return $this->format(new MoneyLogCollection($list));
        }
        
    }
}
