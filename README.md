# 微信授权登录后实现点赞功能

# 实现流程

1、微信授权登录并获取用户基本信息
2、把用户微信昵称和头像url和 `openId` 存储到数据库
3、用户交互时，A 被任何用户点赞后其数据库对应字段加1
4、数据库查询按照点赞字段降序排列

```sql
SELECT * FROM `table_user` ORDER BY voted DESC
```

## 1.1 微信OAuth 2.0简介
OAuth（开放授权）是一个开放标准，允许用户让第三方应用访问该用户在某一网站上存储的私密的资源（如照片，视频，联系人列表），而无需将用户名和密码提供给第三方应用。
允许用户提供一个令牌，而不是用户名和密码来访问他们存放在特定服务提供者的数据。每一个令牌授权一个特定的网站（例如，视频编辑网站)在特定的时段（例如，接下来的2小时内）内访问特定的资源（例如仅仅是某一相册中的视频）。这样，OAuth允许用户授权第三方网站访问他们存储在另外的服务提供者上的信息，而不需要分享他们的访问许可或他们数据的所有内容。
我们这里主要模拟在微信公众号中使用OAuth2.0进行授权，获取用户的基本信息的过程。详细的开发文档可查看微信的官方文档。

## 1.2 配置
[获取微信接口测试号](https://mp.weixin.qq.com/debug/cgi-bin/sandboxinfo?action=showinfo&t=sandbox/index)
[官方文档](http://mp.weixin.qq.com/wiki/14/89b871b5466b19b3efa4ada8e577d45e.html)

## 1.3 关注测试号
用户只有关注了这个公众号了，才能通过打开有公众号信息的链接去授权第三方登录，并获取用户信息的操作。
![](http://images2015.cnblogs.com/blog/731178/201601/731178-20160114201853350-2117066660.png)

## 1.3 设置回调的域名
我们在微信客户端访问第三方网页（即我们自己的网页）的时候，我们可以通过微信网页授权机制，我们不仅要有前面获取到的appid和appsecret还需要有当用户授权之后，回调的域名设置，即用户授权后，页面会跳转到哪里。
> 当前页面找到 网页帐号 => 点击修改
**注意填写的是域名，不压迫包含http**

## 1.4 微信授权获取用户信息
- 拿 `appid` 换取 `code`
> https://open.weixin.qq.com/connect/oauth2/authorize?appid=`公众号的APPID`&redirect_uri=`用户同意授权后页面跳转到哪里`&response_type=code&scope=snsapi_userinfo&state=123wechat_redirect

- 拿`appid` + `secret` + `code` 换取 `access_token` 和 `openid`
> https://api.weixin.qq.com/sns/oauth2/access_token?appid=`公众号的APPID`&secret=`公众号的appsecret`&code=`前面换取的code`&grant_type=authorization_code

- 拿access_token和openid换取用户信息
> https://api.weixin.qq.com/sns/userinfo?access_token=`前面获取的access_token`&openid=`前面获取的openid`

# 完整代码
```php
<?php  
    header("Content-Type: text/html;charset=utf-8");
    
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
  
?>  
```

## 返回的用户 json 信息
```json
{
    "openid": "oG_Sm0cgiGpwNbU1Cs55vgQhltOg",
    "nickname": "Jackie Wang",
    "sex": 1,
    "language": "zh_CN",
    "city": "Jing",
    "province": "Shanghai",
    "country": "CN",
    "headimgurl": "http://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJIIB6bkgib26cibDg2b8vkoks4IE74P6R3GLNaFcJz4jPWXQy5gAQGtaJzauJicrm6ibsKlqe9tt9DCA/0",
    "privilege": []
}
```
# mysql 数据表结构
+------------+--------------+------+-----+---------+----------------+
| Field      | Type         | Null | Key | Default | Extra          |
+------------+--------------+------+-----+---------+----------------+
| id         | int(11)      | NO   | PRI | NULL    | auto_increment |
| name       | varchar(50)  | NO   |     | NULL    |                |
| headimgurl | varchar(100) | NO   |     | NULL    |                |
| voted      | int(10)      | NO   |     | 0       |                |
| openid     | varchar(50)  | NO   |     | NULL    |                |
+------------+--------------+------+-----+---------+----------------+




