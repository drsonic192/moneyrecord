<?php

namespace app\admin\controller;

use app\common\controller\Backend;

class Company extends Backend
{
    protected $model = null;
    
    // 快速搜索字段
    protected $searchFields = 'company_name,license_number,contact_person';
    
    // 数据限制字段
    protected $dataLimit = 'personal';
    
    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Company;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,如果需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        // 直接跳转到编辑页面
        $company = $this->model->find();
        if (!$company) {
            // 如果没有记录，先创建一条
            $this->model->save([
                'company_name' => '默认企业名称',
                'license_number' => '',
                'contact_person' => '',
                'address' => '',
                'status' => 'normal',
                'createtime' => time(),
                'updatetime' => time()
            ]);
            $company = $this->model->find();
        }
        $this->redirect('company/edit', ['ids' => $company['id']]);
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                try {
                    $result = $row->allowField(true)->save($params);
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No changes'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $this->error(__('No permission'));
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $this->error(__('No permission'));
    }

    /**
     * 批量更新
     */
    public function multi($ids = "")
    {
        $this->error(__('No permission'));
    }
} 