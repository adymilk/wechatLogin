<?php
namespace app\index\controller;

use app\index\model\User;
use think\Controller;
use think\Session;

class Index extends Controller
{
    public function index()
    {
        //重定向
        $this->redirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx0224c2c452f6e2b9&redirect_uri=http://dianzan.alibaba9.com/index.php/index/Index/addUser&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect');
    }

    // 显示首页
    public function indexShow(){
        //查询数据库
        //降序排列显示被点赞最多的用户10条信息
        //票数最多的前十个人
        $row = User::table('table_user')->order('voted DESC')->limit(0,10)->select();
        $this->assign('user_top10',$row);
        // 显示所有人
        $row = User::table('table_user')->order('voted DESC')->select();
        $this->assign('allUser',$row);

        //票数做多的社区
        $row = User::table('table_community')->order('voted DESC')->select();
        $this->assign('allcommunityList',$row);

        //风采人物分页
        $count = User::table('table_user')->count();
        $pages = $count/2;
        $row = User::table('table_user')->order('voted DESC')->paginate(2,$pages);
        //变量赋值
        $this->assign("list", $row);
        // 获取分页显示
        $page = $row->render();
        $this->assign('page', $page);
//        // 社区表数据统计

        $shequCount = User::table('table_community')->count();
        //页数统计
        $shequPages = $shequCount/2;
        $row2 = User::table('table_community')->order('voted DESC')->paginate(2,$shequPages);
        $page2 = $row2->render();
        $this->assign('shequ',$row2);
        $this->assign('page2', $page2);

        //模板渲染
        return $this->fetch();
    }

    // 风云任务榜
    public function peopleList(){
        $row = User::table('table_community')->order('voted DESC')->limit(0, 10)->select();
        $this->assign('shequ',$row);
        //模板渲染
        return $this->fetch();


    }

    //存储用户信息
    public function addUser()
    {
        $appid = "wx0224c2c452f6e2b9";
        $secret = "c4ee683dd34968489e7325cd4ab2e504";

        $code = $_GET['code'];
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
//        $access_token = $json_obj['access_token'];
        $openid = $json_obj['openid'];

        $wx_openid = $openid;
//        dump($wx_openid);
        Session('wx_openid',$wx_openid);

        //测试openid
//      $wx_openid = 99999;
        //存储用户微信openid到数据库
        User::table('table_wx_user')->where('id',1)->update(['openid' => $wx_openid]);

        // 先查询数据库是否有微信用户登录记录
        $list =User::table('table_votedRecords')->where('openid',$wx_openid)->select();

        if (empty($list)){
            $data = ['openid' => $wx_openid];
            User::table('table_votedRecords')->insert($data);
        }else{
            //echo "用户已存在";
        }
        $this->redirect('index/indexShow');
    }

    //人物点赞美
    public function addVote()
    {
        if (!empty($_GET['zan'])) {
            //前端传过来的被点赞的人
            $name = $_GET['zan'];
            $wx_openid = Session('wx_openid');

            // 查询当前登录的用户是谁
            $list = User::table('table_wx_user')->where('id',1)->select();
            $openid = $list['0']['openid'];

            //查询是否有被点赞的记录
            $list =User::table('table_votedRecords') ->where('people',$name) ->select();
            //如果有记录
            if (!empty($list)){
                //判断当前用户是否有点赞 $name， $list 是三维数组

                $db_openid = $list['0']['openid'];


                if ($db_openid != $openid){
                    // 点赞字段加1
                    User::table('table_user')->where('name', $name)->setInc('voted', 1);
                    $row = User::table('table_user')->where('name', $name)->select();
                    if ($row) {
                        echo json_encode($row);
                        User::table('table_votedRecords')->insert(['openid' => $openid, 'people' => $name]);
                    }else{
                        echo "人物不存在！";
                    }
                }
            }else{
                // 点赞字段加1
                User::table('table_user')->where('name', $name)->setInc('voted', 1);
                $row = User::table('table_user')->where('name', $name)->select();
                if ($row) {
                    echo json_encode($row);
                    User::table('table_votedRecords')->insert(['openid' => $openid, 'people' => $name]);
                }else{
                    echo "人物不存在！";
                }
            }
//            dump($list);
        }
        else{
            echo 'false';
        }
    }

        //社区点赞

    public function addVoteSequ()
        {
            if (!empty($_GET['zan'])) {
                //前端传过来的被点赞的人
                $name = $_GET['zan'];
                $list = User::table('table_wx_user')->where('id',1)->select();
                $openid = $list['0']['openid'];

//            //查询点赞表
                $list =User::table('table_votedRecords') ->where('people',$name) ->select();
                //如果有记录
//            dump($list);
                if (!empty($list)){
                    //判断当前用户是否有点赞 $name
                    $db_openid = $list['0']['openid'];
//                dump($list);

                    if ($db_openid != $openid){
                        // 根据社区名字给点赞数+1
                        User::table('table_community')->where('name', $name)->setInc('voted', 1);
                        $row = User::table('table_community')->where('name', $name)->select();
                        if (!empty($row)) {
                            echo json_encode($row);
                            User::table('table_votedRecords')->insert(['openid' => $openid, 'people' => $name]);
                        }else{
                            echo "社区不存在！";
                        }
                    }
                }else{
                    // 根据社区名字给点赞数+1
                    User::table('table_community')->where('name', $name)->setInc('voted', 1);
                    $row = User::table('table_community')->where('name', $name)->select();
                    if (!empty($row)) {
                        echo json_encode($row);
                        User::table('table_votedRecords')->insert(['openid' => $openid, 'people' => $name]);
                    }else{
                        echo "社区不存在！";
                    }
                }
//            dump($list);
            }
            else{
                echo 'false';
            }
        }

    }