<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Admin\Controller;

/**
 * Description of CaptchaController
 *
 * @author qingf
 */
class CaptchaController extends \Think\Controller{
    public function captcha(){
        $options = [
            'length'    =>  4,
        ];
        $verify = new \Think\Verify($options);
        $verify->entry();
        exit;
    }
}
