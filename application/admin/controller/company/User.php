<?php

namespace app\admin\controller\Company;

use app\common\controller\Backend;
use think\Db;

class User extends Backend
{
    protected $model = null;
    protected $companyModel = null;
    protected $userModel = null;
    protected $searchFields = 'realname,position';
    protected $noNeedRight = ['selectpage'];
    
    public function _initialize()
    {
        \think\Log::write('User控制器初始化', 'debug');
        \think\Log::write('Request: ' . $this->request->url(true), 'debug');
        
        parent::_initialize();
        $this->model = model('CompanyUser');
        $this->companyModel = model('Company');
        $this->userModel = model('User');
    }
    
    /**
     * 企业员工管理
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                    ->with(['company', 'user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                if (!$params['company_id']) {
                    $this->error(__('Please select company'));
                }
                
                if (!$this->model->checkUserExists($params['user_id'], $params['company_id'])) {
                    $this->error(__('User already exists in company'));
                }
                
                $result = $this->model->allowField(true)->save($params);
                if ($result === false) {
                    $this->error($this->model->getError());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        // 获取企业列表
        $companyList = $this->companyModel
            ->where('status', 'normal')
            ->field('id,company_name')
            ->select();
        $this->view->assign('companyList', $companyList);
        
        return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model
            ->alias('cu')
            ->join('mmoney_user u', 'u.id = cu.user_id')
            ->join('mmoney_company c', 'c.id = cu.company_id')
            ->join('mmoney_user_group g', 'g.id = u.group_id', 'LEFT')
            ->where('cu.id', $ids)
            ->field([
                'cu.*',                      // 企业员工表所有字段
                'u.id as user_id',           // 用户ID
                'u.nickname',                // 会员昵称
                'u.mobile',                  // 会员手机号
                'u.group_id',                // 会员组ID
                'g.name as group_name',      // 会员组名称
                'c.company_name'             // 企业名称
            ])
            ->find();

        if (!$row) {
            $this->error(__('No Results were found'));
        }

        // 确保视图中的值一致
        $viewData = [
            'id' => $row['id'],
            'company_id' => $row['company_id'],
            'user_id' => $row['user_id'],
            'nickname' => $row['nickname'],
            'realname' => $row['realname'],
            'position' => $row['position'],
            'role' => $row['role'],
            'status' => $row['status'],
            'user_text' => $row['nickname']  // 只显示昵称
        ];

        $this->view->assign([
            'row' => $viewData,
            'companyList' => $this->companyModel
                ->where('status', 'normal')
                ->field('id,company_name')
                ->select()
        ]);

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                if (!$params['company_id']) {
                    $this->error(__('Please select company'));
                }
                
                // 如果用户ID发生变化
                if ($params['user_id'] != $row['user_id']) {
                    // 检查用户是否已绑定其他企业员工
                    $bound = $this->model
                        ->where('user_id', $params['user_id'])
                        ->where('id', '<>', $ids)  // 排除当前记录
                        ->where('deletetime', 'null')
                        ->find();
                    
                    if ($bound) {
                        // 如果前端确认替换
                        if ($this->request->post('replace_binding')) {
                            // 解除原有绑定
                            $this->model
                                ->where('user_id', $params['user_id'])
                                ->where('deletetime', 'null')
                                ->update(['deletetime' => time()]);
                        } else {
                            $this->error(__('User already exists in company'));
                        }
                    }
                }
                
                $result = $row->allowField(true)->save($params);
                if ($result === false) {
                    $this->error($row->getError());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
    }
    
    /**
     * Selectpage搜索
     */
    public function selectpage()
    {
        $this->request->filter(['trim']);
        
        if ($this->request->isAjax()) {
            $list = [];
            $word = (array)$this->request->request("q_word/a");
            $field = $this->request->request('showField');
            $keyValue = $this->request->request('keyValue');
            
            if (!$field) {
                $field = 'nickname';
            }
            
            $page = $this->request->request('pageNumber', 1);
            $pagesize = $this->request->request('pageSize', 10);
            
            // 构建查询条件
            $where = [];
            if ($word) {
                foreach ($word as $k => $v) {
                    $where['nickname|mobile'] = ['LIKE', "%{$v}%"];
                }
            }
            if ($keyValue !== null) {
                $where['id'] = $keyValue;
            }
            // 排除后台管理员组的会员
            $where['group_id'] = ['neq', 1];
            
            $total = $this->userModel->where($where)->count();
            $list = $this->userModel
                ->where($where)
                ->field(['id', 'nickname', 'mobile'])
                ->page($page, $pagesize)
                ->select();
            
            // 获取已绑定用户信息
            $boundUsers = [];
            if ($list) {
                $userIds = array_column($list, 'id');
                $boundInfo = $this->model
                    ->alias('cu')
                    ->join('mmoney_company c', 'c.id = cu.company_id')
                    ->where('cu.user_id', 'in', $userIds)
                    ->where('cu.deletetime', 'null')
                    ->field('cu.user_id, c.company_name, cu.realname')
                    ->select();
                    
                foreach ($boundInfo as $info) {
                    if (!isset($boundUsers[$info['user_id']])) {
                        $boundUsers[$info['user_id']] = [];
                    }
                    $boundUsers[$info['user_id']][] = $info;
                }
            }
            
            $result = [];
            foreach ($list as $k => $v) {
                $boundText = '';
                if (isset($boundUsers[$v['id']])) {
                    $boundTexts = [];
                    foreach ($boundUsers[$v['id']] as $bound) {
                        $boundTexts[] = $bound['company_name'] . '-' . $bound['realname'];
                    }
                    $boundText = implode('，', $boundTexts);
                }
                
                $result[] = [
                    'id' => $v['id'],
                    'nickname' => $v['nickname'],
                    'mobile' => $v['mobile'],
                    'text' => $v['nickname'],
                    'name' => $v['nickname'],
                    'bound_info' => $boundText
                ];
            }
            
            return json([
                'list' => $result,
                'total' => $total
            ]);
        }
        return '';
    }
    
    /**
     * 获取当前用户的完整信息
     */
    public function getCurrentUserInfo($user_id)
    {
        $row = $this->userModel
            ->alias('u')
            ->join('mmoney_user_group g', 'g.id = u.group_id', 'LEFT')
            ->where('u.id', $user_id)
            ->field([
                'u.id',
                'u.nickname',
                'u.mobile',
                'u.group_id',
                'g.name as group_name'
            ])
            ->find();

        if ($row) {
            // 获取绑定信息
            $boundInfo = $this->model
                ->alias('cu')
                ->join('mmoney_company c', 'c.id = cu.company_id')
                ->where('cu.user_id', $user_id)
                ->where('cu.deletetime', 'null')
                ->field('c.company_name, cu.realname')
                ->find();

            if ($boundInfo) {
                $row['bound'] = true;
                $row['bound_company'] = $boundInfo['company_name'];
                $row['bound_realname'] = $boundInfo['realname'];
            }
        }

        return json($row);
    }

    /**
     * 检查用户绑定状态
     */
    public function checkUserBinding()
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $currentId = $this->request->post('current_id', 0);  // 当前记录ID
            
            // 查询用户是否已绑定其他企业员工
            $bound = $this->model
                ->where('user_id', $userId)
                ->where('id', '<>', $currentId)  // 排除当前记录
                ->where('deletetime', 'null')
                ->find();
            
            return json([
                'code' => $bound ? 1 : 0,
                'msg' => $bound ? '用户已绑定其他企业员工' : '用户未绑定'
            ]);
        }
        return '';
    }
} 