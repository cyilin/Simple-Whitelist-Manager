<?php
/*
 * Simple Whitelist Manager
 * 2015-09-12 cylin
 * v2
 * https://github.com/cyilin
 */
header("Cache-Control: no-cache");
header("Content-Type: text/html; charset=UTF-8");
session_start();
date_default_timezone_set('Asia/Hong_Kong');

$dsn = 'mysql:dbname=mc_5_whitelist;host=127.0.0.1';
$dbname = 'mc_5_whitelist';
$dbtable = 'tbl_users';
$dbuser = 'root';
$dbpassword = '';

$swm_password = ''; // login password

if (PHP_OS == 'WINNT' && in_array($_SERVER['REMOTE_ADDR'], array(
    '127.0.0.1',
    '::1'
))) { // test
    $dsn = 'mysql:dbname=whitelist;host=127.0.0.1';
    $dbname = 'whitelist';
    $dbuser = 'root';
    $dbpassword = 'root';
    $swm_password = '123456';
}

class whitelist
{

    var $dbh;

    var $db_table = 'tbl_users';

    var $db_name = 'whitelist';

    function dbconn($dsn, $dbuser, $dbpassword, $tab = 'tbl_users')
    {
        $this->db_table = $tab;
        try {
            $this->dbh = new PDO($dsn, $dbuser, $dbpassword);
            return true;
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
            return false;
        }
    }

    static function is_valid_name($name = '')
    {
        // http://gaming.stackexchange.com/questions/21806/what-is-the-format-of-minecraft-net-account-names
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
        $len = strlen($name);
        if ($len >= 3 && $len <= 16) {
            for ($i = 0; $i < $len; $i ++) {
                $pos = strpos($str, substr($name, $i, 1));
                if ($pos === false) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    function count()
    {
        $sql = "SELECT count(*) FROM `{$this->db_name}`.`{$this->db_table}`";
        return $this->dbh->query($sql)->fetchColumn();
    }

    function add($name = '', $email = null)
    {
        if ($name == '') {
            return false;
        }
        $stmt = $this->dbh->prepare("INSERT INTO `{$this->db_name}`.`{$this->db_table}` (`name`, `email`) VALUES (?, ?);");
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $email);
        return $stmt->execute();
    }

    function query($uid)
    {
        $stmt = $this->dbh->prepare("SELECT * FROM `{$this->db_name}`.`{$this->db_table}` WHERE `uid`=?;");
        $stmt->bindParam(1, $uid);
        
        if ($stmt->execute()) {
            return $stmt->fetch();
        } else {
            
            return false;
        }
    }

    function swm_list($offset, $length = 100)
    {
        $sql = "SELECT * FROM `{$this->db_name}`.`{$this->db_table}` LIMIT {$offset},{$length};";
        return $this->dbh->query($sql);
    }

    function remove($uid)
    {
        $stmt = $this->dbh->prepare("DELETE FROM `{$this->db_name}`.`{$this->db_table}` WHERE `uid`=?;");
        $stmt->bindParam(1, $uid);
        return $stmt->execute();
    }

    static function log($s = '')
    {
        /*
         * $date = date("Y-m-d");
         * $time = date("H:i:s");
         * $fp = fopen(__DIR__ . '/log/' . $date . '.log', "a");
         * fwrite($fp, "{$time} {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']} {$_SERVER['HTTP_USER_AGENT']} {$_SERVER['HTTP_REFERER']} {$_SERVER['REMOTE_ADDR']} {$s}\r\n");
         * fclose($fp);
         */
        return true;
    }
} // end class

$act = empty($_GET['act']) ? '' : $_GET['act'];
whitelist::log();
if ($act == 'login') {
    if (! file_exists(__DIR__ . '/whitelist.lock')) {
        touch(__DIR__ . '/whitelist.lock');
    }
    if (! empty($_POST['pw'])) {
        if (filesize(__DIR__ . '/whitelist.lock') > 20) {
            echo '<p>密码错误次数过多已禁止登录 请删除whitelist.lock后重新登录</p>';
            exit();
        } elseif ($swm_password == '') {
            echo '<p>请先设置密码</p>';
            exit();
        } elseif ($_POST['pw'] === $swm_password) {
            $_SESSION['swm_password'] = $swm_password;
            whitelist::log('login');
            echo '<p>登录成功 <a href="?" target="_self">[返回首页]</a></p>';
            $fp = fopen(__DIR__ . '/whitelist.lock', 'w');
            fwrite($fp, '1');
            fclose($fp);
        } else {
            echo '<p>密码错误</p>';
            whitelist::log('password error' . $_POST['pw']);
            $fp = fopen(__DIR__ . '/whitelist.lock', 'a+');
            fwrite($fp, '1');
            fclose($fp);
        }
    }
} elseif ($act == 'logout') {
    $_SESSION['swm_password'] = null;
    session_destroy();
    whitelist::log('logout');
    echo '<p>退出成功</p>';
    exit();
} elseif (! empty($_SESSION['swm_password']) && $_SESSION['swm_password'] === $swm_password) {
    
    $whitelist = new whitelist();
    
    $whitelist->dbconn($dsn, $dbuser, $dbpassword);
    $whitelist->db_name = $dbname;
    $whitelist->db_table = $dbtable;
    echo '<p><a href="?" target="_self">[首页]</a>  <a href="?act=add" target="_self">[添加白名单]</a>  <a href="?act=logout" target="_self">[退出登录]</a> </p>';
    
    if ($act == 'add') {
        if (! empty($_POST['name'])) {
            if (whitelist::is_valid_name($_POST['name'])) {
                $r = $whitelist->add($_POST['name'], ($_POST['email']) ? $_POST['email'] : null);
                if ($r) {
                    whitelist::log("add name:{$_POST['name']}");
                    echo '<h5>添加成功</h5>';
                } else {
                    echo '<h5>添加失败</h5>';
                }
            } else {
                echo '<h5>玩家名不符合要求</h5>';
            }
        }
        $html = '<form action="?act=add" method="post" target="_self">';
        $html .= '<p>玩家名（必填）: <input maxlength="16" name="name" required="required" type="text" /> email或QQ:<input maxlength="255" name="email" type="text" />（选填）</p>';
        $html .= '<p><input name="提交" type="submit" /></p>';
        $html .= '</form>';
        $html .= '<p>玩家名长度3-16字符，不可包含中文或特殊符号</p>';
        echo $html;
    } elseif ($act == 'del') {
        if (! empty($_POST['uid'])) {
            $q = $whitelist->query($uid = $_POST['uid']);
            $r = $whitelist->remove($_POST['uid']);
            if ($r) {
                whitelist::log("delete uid:{$q['uid']} name:{$q['name']}");
                echo '<h5>删除成功</h5>';
            } else {
                echo '<h5>删除失败</h5>';
            }
        }
        if (! empty($_GET['uid'])) {
            $r = $whitelist->query($uid = $_GET['uid']);
            if ($r) {
                $html = '<form action="?act=del" method="post" name="del" target="_self">';
                $html .= sprintf("<p>uid:%s 玩家名:%s email/qq:%s 添加时间:%s</p>", $r['uid'], $r['name'], $r['email'] ? $r['email'] : '无', $r['date']);
                $html .= '<p>确认删除白名单？<input name="uid" type="hidden" value="' . $_GET['uid'] . '" /></p>';
                $html .= '<p><input value="删除" type="submit" /></p>';
                $html .= '</form>';
            } else {
                $html = '<p>不存在此uid</p>';
            }
            echo $html;
        }
    } else {
        
        $length = 100;
        $page = isset($_GET['p']) ? $_GET['p'] : 1;
        $pagecount = ceil($whitelist->count() / $length);
        
        if ($page > $pagecount || $page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $length;
        
        echo '<p>翻页：';
        for ($i = 1; $i <= $pagecount; $i ++) {
            
            echo "<a href='?p={$i}'>{$i}</a> ";
        }
        echo '</p>';
        echo "<h5>记录总数:" . $whitelist->count() . " 当前第{$page}页</h>";
        echo "<table width='700px' border='1px'>";
        echo "<tr>";
        echo "<td>uid</td>";
        echo "<td>玩家名</td>";
        echo "<td>email/qq</td>";
        echo "<td>添加时间</td>";
        echo "</tr>";
        $res = $whitelist->swm_list($offset, $length);
        foreach ($res as $row) {
            echo "<tr>";
            echo "<td>{$row['uid']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>" . ($row['email'] ? $row['email'] : ' ') . "</td>";
            echo "<td>{$row['date']}</td>";
            echo '<td><a href="?act=del&uid=' . $row['uid'] . '" target="_self">[删除]</a></td>';
            echo "</tr>\r\n";
        }
    } // end
} else {
    $html = '<form action="?act=login" method="post" target="_self">';
    $html .= '<p>password:<input name="pw" required="required" type="password" /></p>';
    $html .= '<p><input value="login" type="submit" /></p>';
    $html .= '</form>';
    echo $html;
}







