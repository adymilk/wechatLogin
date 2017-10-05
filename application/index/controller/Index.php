<?php
namespace app\index\controller;

use app\index\model\User;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
        //查询数据库
        //降序排列显示被点赞最多的用户10条信息
        $row = User::table('table_user')->order('voted DESC')->limit(0,10)->select();
        //变量赋值
        $this->assign("list",$row);
        //模板渲染
        return $this->fetch();
    }


    //存储用户信息
    public function addUser(){
        if (isset($_GET['name'])&& isset($_GET['headimgurl'])){
            //用户昵称
            $name = $_GET['name'];
            //用户头像
            $headimgurl = $_GET['headimgurl'];
        }

        //向数据库存储用户信息
        $data = ['name' => $name,'headimgurl' => $headimgurl];
        User::table('table_user')->insert($data);
        $this->success("添加成功！",'Index/index');
    }

    //点击投票
    public function addVote(){
        if (isset($_GET['id'])|| !(isEmpty($_GET['id']))){
            $id = $_GET['id'];
        }
        //投票数加1
        User::table('table_user')->where('id',$id)->setInc('voted',1);
        $this->success("投票成功",'Index/index');
    }

    //取消投票
    public function cancelVote(){
        if (isset($_GET['id'])|| !(isEmpty($_GET['id']))){
            $id = $_GET['id'];
        }
        //投票数减1
        User::table('table_user')->where('id',$id)->setDec('voted',1);
        $this->success("取消投票",'Index/index');
    }
}
