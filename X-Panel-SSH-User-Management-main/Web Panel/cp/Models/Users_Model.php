<?php

class Users_Model extends Model
{
    public function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        if (isset($_COOKIE["xpkey"])) {
            $key_login = explode(':', $_COOKIE["xpkey"]);
            $Ukey=$key_login[0];
            $Pkey=$key_login[1];
            $query = $this->db->prepare("select * from setting where adminuser='" .$Ukey. "' and login_key='" .$_COOKIE["xpkey"]. "'");
            $query->execute();
            $queryCount = $query->rowCount();
            $query_ress = $this->db->prepare("select * from admins where username_u='" . $Ukey . "' and login_key='" . $_COOKIE["xpkey"] . "'");
            $query_ress->execute();
            $queryCount_ress = $query_ress->rowCount();
            if ($queryCount == 0 && $queryCount_ress == 0) {
                header("location: login");
            }
        } else {
            header("location: login");
        }
    }

    public function users()
    {
        $query = $this->db->prepare("select * from users,Traffic where users.username=Traffic.user ORDER BY users.id DESC");
        $query->execute();
        $queryCount = $query->fetchAll();
        return $queryCount;
    }
    public function Get_settings()
    {
        $query = $this->db->prepare("select * from setting");
        $query->execute();
        $queryCount = $query->fetchAll();
        return $queryCount;
    }

    public function submit_ative($data_sybmit)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username=:user");
        $stmt->execute(['user' => $data_sybmit['username']]);
        $user = $stmt->fetch();
        $username=$data_sybmit['username'];
        $sql = "UPDATE users SET enable=? WHERE username=?";
        $this->db->prepare($sql)->execute(['true', $username]);
        shell_exec("bash Libs/sh/adduser " . $data_sybmit['username'] . " " . $user["password"]);
        header("Location: users");
    }
    public function submit_deative($data_sybmit)
    {
        $username=$data_sybmit['username'];
        $sql = "UPDATE users SET enable=? WHERE username=?";
        $this->db->prepare($sql)->execute(['false', $username]);
        shell_exec("sudo killall -u " . $data_sybmit['username']);
        shell_exec("bash Libs/sh/userdelete " . $data_sybmit['username']);
        header("Location: users");
    }
    public function submit_index($data_sybmit)
    {
        //print_r($data_sybmit);
        if(empty($data_sybmit['password']))
        {
            if($data_sybmit['pass_rand']=='number')
            {
                $chars = "1234567890";
            }
            else
            {
                $chars = "abcdefghijklmnopqrstuvwxyz1234567890";
            }
            $password = substr( str_shuffle( $chars ), 0, $data_sybmit['pass_char'] );
        }
        else
        {
            $password=$data_sybmit['password'];
        }
        $query = $this->db->prepare("select * from users where username='".$data_sybmit['username']."'");
        $query->execute();
        $queryCount = $query->rowCount();
        if ($queryCount < 1) {
            if(LANG=='fa-ir') {
                if (!empty($data_sybmit['finishdate'])) {
                    $finishdate = explode('/', $data_sybmit['finishdate']);
                    $finishdate = jalali_to_gregorian($finishdate[0], $finishdate[1], $finishdate[2], '-');
                } else {
                    $finishdate = '';
                }
            }
            else{
                $finishdate = $data_sybmit['finishdate'];
            }
            $sql = "INSERT INTO `users` (`username`, `password`, `email`, `mobile`, `multiuser`, `startdate`, `finishdate`, `finishdate_one_connect`, `enable`, `traffic`, `referral`, `info`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($data_sybmit['username'], $password, $data_sybmit['email'], $data_sybmit['mobile'], $data_sybmit['multiuser'], $data_sybmit['startdate'], $finishdate, $data_sybmit['finishdate_one_connect'], $data_sybmit['enable'], $data_sybmit['traffic'], $data_sybmit['referral'], $data_sybmit['info']));
            if ($stmt) {
                $sql = "INSERT INTO `Traffic` (`id`,`user`, `download`, `upload`, `total` ) VALUES (NULL,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $use_traffic=$data_sybmit['username'];
                $stmt->execute(array($use_traffic, '0', '0', '0'));
                $stmt = $this->db->prepare("SELECT * FROM Traffic WHERE user=:user");
                $stmt->execute(['user' => $use_traffic]);
                $user = $stmt->rowCount();
                if($user==0) {
                    $sql1 = "INSERT INTO `Traffic` (`user`, `download`, `upload`, `total` ) VALUES (?,?,?,?)";
                    $stmt1 = $this->db->prepare($sql1);
                    $stmt1->execute(array($use_traffic, '0', '0', '0'));
                }
                shell_exec("bash Libs/sh/adduser " . strtolower($data_sybmit['username']) . " " . $password);
                header("Location: users");
                return true;
            } else {
                return false;
            }
        } else {
            echo '
            <div class="p-4 mb-2" style="position: fixed;z-index: 9999;left: 0;">
              <div class="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                  <img src="' . path . 'assets/images/xlogo.png" class="img-fluid m-r-5" alt="XPanel" style="width: 17px">
                  <strong class="me-auto">XPanel</strong>
                  <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">'.confirm_re_user_lang.'</div>
              </div>
            </div>';
        }
    }

    public function delete_user($data_sybmit)
    {
        $username=$data_sybmit['username'];
        $query = $this->db->prepare("DELETE FROM users WHERE username=?")->execute([$username]);
        $this->db->prepare("DELETE FROM Traffic WHERE user=?")->execute([$username]);
        if($query)
        {
            shell_exec("sudo killall -u " . $username);
            shell_exec("sudo userdel -r " . $username);
            header("Location: users");
        }
    }

    public function reset_traffic($data_sybmit)
    {
        $username=$data_sybmit['username'];
        $sql = "UPDATE Traffic SET upload=?,download=?,total=? WHERE user=?";
        $this->db->prepare($sql)->execute(['0','0','0', $username]);
        header("Location: users");

    }
}
