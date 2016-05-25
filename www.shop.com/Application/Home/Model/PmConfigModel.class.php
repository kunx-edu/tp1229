<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Home\Model;

/**
 * Description of PmConfigController
 *
 * @author qingf
 */
class PmConfigModel{
    
    /**
     * 获取送货方式列表.
     * @return type
     */
    public function getDeliveryList() {
        return M('Delivery')->where(['status'=>1])->select();
    }
    
    /**
     * 获取支付方式列表.
     * @return type
     */
    public function getPaymentList() {
        return M('Payment')->where(['status'=>1])->select();
    }
}
