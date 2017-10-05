<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 17-10-5
 * Time: 下午5:04
 */

namespace app\index\controller;


use think\Controller;

class Wechat extends Controller
{
    public function getUserInfo(){
//        header("Content-Type: text/html;charset=utf-8");

        $appid = "wxf24498aa64644cb9";
        $secret = "ab06b60618332a5fc63081f7ac3b75bb";

        $code = $_GET["code"];
        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_token_url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        $json_obj = json_decode($res,true);

        //根据openid和access_token查询用户信息
        $access_token = $json_obj['access_token'];
        $openid = $json_obj['openid'];
        $get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_user_info_url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);

        //解析json
        $user_obj = json_decode($res,true);
        $_SESSION['user'] = $user_obj;
        print_r($user_obj);

        echo '<br/><h1>姓名：'.$user_obj['nickname'].'</h1><br/>';
        echo '<br/><h1>头像：'.$user_obj['headimgurl'].'</h1><br/>';
        echo '<br/><h1>城市：'.$user_obj['city'].'</h1><br/>';
        echo '<br/><h1>国家：'.$user_obj['country'].'</h1><br/>';

        $name = $user_obj['nickname'];
        $headimgurl = $user_obj['headimgurl'];
        if (!empty($name)&& !empty($headimgurl)){
            //向数据库存储用户信息
            $data = ['name' => $name,'headimgurl' => $headimgurl];
            User::table('table_user')->insert($data);
            $this->success("添加成功！",'Index/index');
        }


        }
}