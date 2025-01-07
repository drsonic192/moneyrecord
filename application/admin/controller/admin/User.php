<?php

namespace app\admin\controller\admin;

use app\common\controller\Backend;
use think\Db;

class User extends Backend
{
    protected $model = null;
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
    }
    
    /**
     * Selectpage搜索
     */
    public function selectpage()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array)$this->request->request("q_word/a");
        //当前页
        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize", 10);
        
        // 获取管理员组的用户ID列表
        $adminUserIds = Db::name('auth_group_access')
            ->where('group_id', 1)  // 管理员组ID为1
            ->column('uid');
        
        $where = [];
        // 排除管理员组的用户
        if ($adminUserIds) {
            $where['id'] = ['not in', $adminUserIds];
        }
        
        if ($word) {
            $keyword = array_filter($word);
            if ($keyword) {
                $where['nickname|mobile'] = ['LIKE', '%' . reset($keyword) . '%'];
            }
        }
        
        // 获取已分配用户的企业信息
        $companyUsers = Db::name('company_user')
            ->alias('cu')
            ->join('company c', 'c.id = cu.company_id')
            ->where('cu.deletetime', 'null')
            ->field('cu.user_id, c.company_name')
            ->select();
        
        $boundUsers = [];
        foreach ($companyUsers as $item) {
            $boundUsers[$item['user_id']] = $item['company_name'];
        }
        
        $list = $this->model
            ->where($where)
            ->field('id,nickname,mobile')
            ->order('id desc')
            ->page($page, $pagesize)
            ->select();
        
        // 添加绑定信息
        foreach ($list as &$item) {
            // text 用于显示在列表中
            $item['text'] = $item['nickname'];
            if (isset($boundUsers[$item['id']])) {
                $item['text'] = $item['nickname'] . '<span style="color: red; font-size: 12px;"> -已绑定' . $boundUsers[$item['id']] . '</span>';
                $item['bound'] = true;
                $item['bound_company'] = $boundUsers[$item['id']];
            }
            // value 用于选中后显示在输入框中
            $item['value'] = $item['nickname'];
        }
        
        $result = ["total" => $this->model->where($where)->count(), "rows" => collection($list)->toArray()];
        return json($result);
    }
} 