<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Admin\Model;

/**
 * Description of AdminModel
 *
 * @author qingf
 */
class AdminModel extends \Think\Model{
    protected $_validate = [
        ['username','require','账号不能为空',self::EXISTS_VALIDATE,'',self::MODEL_INSERT],
        ['username','','账号已存在',self::EXISTS_VALIDATE,'unique',self::MODEL_INSERT],
        ['password','require','密码不能为空',self::EXISTS_VALIDATE,'',self::MODEL_INSERT],
        ['repassword','password','两次密码不一致',self::EXISTS_VALIDATE,'confirm',self::MODEL_INSERT],
        ['email','email','邮箱格式不合法',self::EXISTS_VALIDATE,'',self::MODEL_INSERT],
        ['repassword','password','两次密码不一致',self::EXISTS_VALIDATE,'confirm',self::MODEL_UPDATE],
    ];
    
    protected $_auto = [
        ['salt','\Org\Util\String::randString',self::MODEL_BOTH,'function',6],
        ['add_time',NOW_TIME,self::MODEL_INSERT],
    ];
    
    /**
     * 1.新增管理员得到管理员id
     * 2.保存 [管理员-角色] 关联
     *  2.1有关联才执行addall
     *  2.2失败就回滚
     * 3.保存 [管理员-额外权限] 关联
     *  3.1有关联才allall
     *  3.2失败就回滚
     * 4.提交
     * @return boolean
     */
    public function addAdmin() {
        unset($this->data[$this->getPk()]);
        $this->startTrans();
        $this->data['password'] = salt_mcrypt($this->data['password'], $this->data['salt']);
        if(($admin_id = $this->add())===false){
            $this->rollback();
            return false;
        }
        
        //保存管理员角色
        //获取到角色列表
        $role_ids = I('post.role_id');
        if($role_ids){
            $data = [];
            foreach($role_ids as $role_id){
                $data[] = [
                    'admin_id'=>$admin_id,
                    'role_id'=>$role_id,
                ];
            }
            if(M('AdminRole')->addAll($data)===false){
                $this->error = '保存角色失败';
                $this->rollback();
                return false;
            }
        }
        
        
        //保存额外权限
        $permission_ids = I('post.permission_id');
        if($permission_ids){
            $data = [];
            foreach($permission_ids as $permission_id){
                $data[] = [
                    'admin_id'=>$admin_id,
                    'permission_id'=>$permission_id,
                ];
            }

            $admin_permission_model = M('AdminPermission');
            if($admin_permission_model->addAll($data) === false){
                $this->rollback();
                $this->error = '保存权限失败';
                return false;
            }
        }
        $this->commit();
        return true;
    }
    
    public function getList() {
        return $this->select();
    }
    
    /**
     * 修改管理员信息.
     * 如果需要修改密码,则会自动生成盐和密码.
     * @return boolean
     */
    public function updateAdmin() {
        $this->startTrans();
        $request_data = $this->data;
        if(empty($this->data['password'])){
            unset($this->data['password']);
            unset($this->data['salt']);
        } else{
            $this->data['password'] = salt_mcrypt($this->data['password'], $this->data['salt']);
        }
        //如果保存失败就返回
        if(count($this->data) > 1){
            if($this->save()===false){
                $this->rollback();
                return false;
            }
        }
        
        //保存角色关联
        $role_ids = I('post.role_id');
        //删除原有的角色
        $admin_role_model = M('AdminRole');
        $admin_role_model->where(['admin_id'=>$request_data['id']])->delete();
        if($role_ids){
            $data = [];
            //保存角色
            foreach($role_ids as $role_id){
                $data[] = [
                    'admin_id'=>$request_data['id'],
                    'role_id'=>$role_id,
                ];
            }
            if($admin_role_model->addAll($data) === false){
                $this->error = '保存角色失败';
                $this->rollback();
                return false;
            }
        }
        //保存额外权限
        $permission_ids = I('post.permission_id');
        //删除原有的关联权限
        $admin_permission_model = M('AdminPermission');
        $admin_permission_model->where(['admin_id'=>$request_data['id']])->delete();
        if($permission_ids){
            //执行添加
            $data = [];
            foreach($permission_ids as $permission_id){
                $data[] = [
                    'admin_id'=>$request_data['id'],
                    'permission_id'=>$permission_id,
                ];
            }
            if($admin_permission_model->addAll($data)===false){
                $this->error = '保存权限失败';
                $this->rollback();
                return false;
            }
        }
        $this->commit();
        return true;
    }
    
    /**
     * 获取管理员信息包括角色.
     * @param integer $id 管理员id.
     * @return type
     */
    public function getAdminInfo($id) {
        $row = $this->find($id);
        $row['role_ids'] = json_encode(M('AdminRole')->where(['admin_id'=>$id])->getField('role_id',true));
        $row['permission_ids'] = json_encode(M('AdminPermission')->where(['admin_id'=>$id])->getField('permission_id',true));
        return $row;
    }
    
    /**
     * 删除管理员,并删除对应的关联关系.
     * @param integer $id 管理员id.
     * @return boolean
     */
    public function deleteAdmin($id){
        $this->startTrans();
        if($this->delete($id) === false){
            $this->rollback();
            return false;
        }
        //删除角色关联关系
        if(M('AdminRole')->where(['admin_id'=>$id])->delete() === false){
            $this->error = '删除角色失败';
            $this->rollback();
            return false;
        }
        
        //删除权限关联关系
        if(M('AdminPermission')->where(['admin_id'=>$id])->delete() === false){
            $this->error = '删除角色失败';
            $this->rollback();
            return false;
        }
        $this->commit();
        return true ;
    }
}