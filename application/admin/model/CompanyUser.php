<?php

namespace app\admin\model;

use think\Model;

class CompanyUser extends Model
{
    // 表名
    protected $name = 'company_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'role_text',
        'status_text',
        'createtime_text',
        'updatetime_text'
    ];
    
    // 追加搜索器
    protected $searchFields = 'realname,position';
    
    // 角色列表
    public static function getRoleList()
    {
        return ['admin' => __('Admin'), 'staff' => __('Staff')];
    }
    
    // 状态列表
    public static function getStatusList()
    {
        return [
            'normal' => __('Normal'),
            'hidden' => __('Hidden'),
            'unbind' => __('Unbind')
        ];
    }
    
    // 获取角色文本
    public function getRoleTextAttr($value, $data)
    {
        $role = $data['role'] ?? 'staff';
        $list = $this->getRoleList();
        return $list[$role] ?? '';
    }
    
    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 'normal';
        $list = $this->getStatusList();
        return $list[$status] ?? '';
    }
    
    // 关联企业
    public function company()
    {
        return $this->belongsTo('Company', 'company_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    // 关联会员
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    // 验证用户是否已是企业员工
    public static function checkUserExists($user_id, $company_id, $id = null)
    {
        $where = [
            'user_id' => $user_id,
            'company_id' => $company_id
        ];
        if ($id) {
            $where['id'] = ['<>', $id];
        }
        return !self::where($where)->find();
    }
    
    // 格式化创建时间
    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['createtime'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    
    // 格式化更新时间
    public function getUpdatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['updatetime'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    
    // 获取企业员工列表
    public function getList($where = [], $sort = 'id', $order = 'desc', $offset = 0, $limit = 10)
    {
        return $this->with(['company', 'user'])
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();
    }
    
    // 获取企业员工总数
    public function getTotal($where = [])
    {
        return $this->where($where)->count();
    }
} 