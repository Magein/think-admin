<?php


namespace app\admin\controller;

use app\admin\logic\LoginLogic;
use app\admin\component\system_ip\SystemIpLogic;
use magein\php_tools\common\Password;
use app\admin\component\system_config\SystemConfigLogic;
use app\admin\component\system_log\SystemLogLogic;
use app\admin\component\system_user\SystemUserConstant;
use app\admin\component\system_user\SystemUserLogic;
use magein\render\admin\Cdn;
use think\Controller;
use think\Request;
use think\Env;

/**
 * Class Login
 * @package app\admin\controller
 */
class Login extends Controller
{

    use Cdn;

    public function index()
    {
        $config = SystemConfigLogic::instance()->getValue();
        $this->assign('config', $config);
        $this->assign('cdn', $this->cdn());
        return $this->fetch('admin@base/main/login');
    }

    public function login()
    {
        $username = Request::instance()->param('username');
        $password = Request::instance()->param('password');

        /**
         * 验证IP
         */
        if (Env::get('admin.check_ip')) {
            if (false === SystemIpLogic::instance()->checkIp()) {
                $this->error('登录失败，错误代码：1001',
                    [
                        'ip' => Request::instance()->ip(),
                        'check_ip' => Env::get('admin.check_ip')
                    ]);
            }
        }

        $record = SystemUserLogic::instance()->getByUsername($username);

        if (empty($record)) {
            $this->error('用户不存在');
        }

        if (false === (new Password())->check($password, $record['password'])) {
            $this->error('用户不存在');
        }

        if ($record['status'] == SystemUserConstant::STATUS_FORBID) {
            $this->error('用户已被禁止登录');
        }

        // 更新登录信息
        if (false === SystemUserLogic::instance()->updateLoginData($record['id'])) {
            $this->error('登录失败');
        };

        (new LoginLogic())->setLogin($record);

        SystemLogLogic::instance()->create('login', $record['id']);

        $this->success('登录成功', 'config/index');
    }

    public function logout()
    {
        LoginLogic::instance()->setLogin();

        $this->success('success', 'index');
    }
}