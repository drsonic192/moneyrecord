<?php

namespace app\admin\controller\Company;

use app\common\controller\Backend;
use think\Db;

class Company extends Backend
{
    protected $model = null;
    protected $searchFields = 'company_name,company_code,contact_name';
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Company');
    }
    
    /**
     * 查看
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
                
                if (!$this->model->checkCompanyCode($params['company_code'])) {
                    $this->error(__('Company code already exists'));
                }
                
                if (!$this->model->checkCompanyName($params['company_name'])) {
                    $this->error(__('Company name already exists'));
                }
                
                $result = $this->model->allowField(true)->save($params);
                if ($result === false) {
                    $this->error($this->model->getError());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                if (!$this->model->checkCompanyCode($params['company_code'], $ids)) {
                    $this->error(__('Company code already exists'));
                }
                
                if (!$this->model->checkCompanyName($params['company_name'], $ids)) {
                    $this->error(__('Company name already exists'));
                }
                
                $result = $row->allowField(true)->save($params);
                if ($result === false) {
                    $this->error($row->getError());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
} 