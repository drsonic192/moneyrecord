<?php

namespace app\admin\model;

use think\Model;

class Company extends Model
{
    // 表名
    protected $name = 'company';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    // 状态列表
    public static function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }
    
    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 'normal';
        $list = $this->getStatusList();
        return $list[$status] ?? '';
    }
    
    // 验证企业代码唯一性
    public static function checkCompanyCode($code, $id = null)
    {
        $where = ['company_code' => $code];
        if ($id) {
            $where['id'] = ['<>', $id];
        }
        return !self::where($where)->find();
    }
    
    /**
     * 验证企业名称唯一性
     */
    public static function checkCompanyName($name, $id = null)
    {
        $where = ['company_name' => $name];
        if ($id) {
            $where['id'] = ['<>', $id];
        }
        return !self::where($where)->find();
    }
} 