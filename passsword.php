<?php
#Error Reporting
error_reporting(0);
#Login Details
$Username = 'admin';
$Password = 'admin';



$LoginSuccessful = false;
// Check username and password:
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    if ($Username == $_SERVER['PHP_AUTH_USER'] && $Password == $_SERVER['PHP_AUTH_PW']) {
        $LoginSuccessful = true;
    }
}
// Login passed successful?
if (!$LoginSuccessful) {
    header('WWW-Authenticate: Basic realm="Vtiger Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    die("Login failed!\n");
} else {
    
    
    // Turn on debugging level 
    $Vtiger_Utils_Log = true;
    include_once('vtlib/Vtiger/Menu.php');
    include_once('vtlib/Vtiger/Module.php');

    function encrypt_password($username, $user_password, $crypt_type = '')
    {
        $salt = substr($username, 0, 2);
        if ($crypt_type == '') {
            $crypt_type = 'MD5';
        }
        if ($crypt_type == 'MD5') {
            $salt = '$1$' . $salt . '$';
        } elseif ($crypt_type == 'BLOWFISH') {
            $salt = '$2$' . $salt . '$';
        } elseif ($crypt_type == 'PHP5.3MD5') {
            $salt = '$1$' . str_pad($salt, 9, '0');
        }
        $encrypted_password = crypt($user_password, $salt);
        return $encrypted_password;
    }    
    
    $adb = PearDatabase::getInstance();
    
    if (isset($_POST['pwd2']) && isset($_POST['pwd1']) && isset($_POST['username']) && $_POST['pwd2'] == $_POST['pwd1'] && !empty($_POST['pwd1'])) {
        $error  = false;
        $status = "sucess";
        $sql    = 'SELECT user_name, crypt_type FROM vtiger_users WHERE status = "Active" and id = "' . $_POST['username'] . '" limit 1';
        $result = $adb->query($sql);
        if ($adb->num_rows($result) > 0) {
            while ($row = $adb->fetchByAssoc($result)) {
                $crypt_type = $row['crypt_type'];
                $user_name  = $row['user_name'];
            }
            $userid            = $_POST['username'];
            $encryptedPassword = encrypt_password($user_name, $_POST['pwd1'], $crypt_type);
            $query             = "UPDATE vtiger_users SET user_password=?, confirm_password=? where id=?";
            $adb->pquery($query, array(
                $encryptedPassword,
                $encryptedPassword,
                $userid
            ));
            if ($adb->hasFailedTransaction()) {
                if ($dieOnError) {
                    $error  = "error setting new password: [" . $adb->database->ErrorNo() . "] " . $adb->database->ErrorMsg();
                    $status = "error";
                }
            }
            if (isset($_POST['recreate']) && $_POST['recreate'] == '1') {
                require_once('modules/Users/CreateUserPrivilegeFile.php');
                createUserPrivilegesfile($userid);
                createUserSharingPrivilegesfile($userid);
                require_once($root_directory . 'user_privileges/user_privileges_' . $userid . '.php');
                require_once($root_directory . 'user_privileges/sharing_privileges_' . $userid . '.php');
            }
        } else {
            $error  = "Invalid User Selected!";
            $status = "error";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . ($status == "error" ? "?status=error&msg=" . $error : "?status=success"));
        exit;
    }
    

    
    
    $sql       = 'SELECT id, user_name, first_name, last_name FROM vtiger_users WHERE status = "Active"';
    $result    = $adb->query($sql);
    $listusers = '<select id="selectbasic" name="username" class="form-control">';
    while ($row = $adb->fetchByAssoc($result)) {
        $listusers .= '<option value="' . $row['id'] . '">' . $row['first_name'] . ' ' . $row['last_name'] . ' - (' . $row['user_name'] . ')</option>';
        
    }
    $listusers .= '</select>';
    
echo '<!doctype html>
<html lang="en"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Change User\'s Password</title>
<link rel="stylesheet" href="libraries/bootstrap/css/bootstrap.css" type="text/css" media="all" />
<script type="text/javascript" src="libraries/jquery/jquery.min.js"></script>    
<style>
html {
    font-size: 10pt;
}
body {
    background-color: rgba(250,250,250,0.5)
}
input, select, .uneditable-input {
    margin-left: 20px;
    height: 28px;
    font-family: tahoma;
}    
input[type="checkbox"] {
    margin-left: 20px;
}
.loginform {
    width: 510px;
    margin: 60px auto;
    padding: 25px;
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, 0.2), 
                inset 0px 1px 0px 0px rgba(250, 250, 250, 0.5);
    border: 1px solid rgba(0, 0, 0, 0.3);
}
.center {
    text-align:center;
}
.cf:before,
.cf:after {
    content: ""; 
    display: table;
}

.cf:after {
    clear: both;
}
.cf {
    *zoom: 1;
}


</style>
<script type="text/javascript">
  function checkForm(form) {
      $("#formerror").hide();
      $("formsuccess").hide();
    if(form.username.value == "") {
      //alert("Error: Username cannot be blank!");
      $("#formerror").show();
      $("#formerror").text("Error: Username cannot be blank!");
      form.username.focus();
      return false;
    }
    if(form.pwd1.value != "" && form.pwd1.value == form.pwd2.value) {
      if(form.pwd1.value.length < 6) {
        //alert("Error: Password must contain at least six characters!");
        $("#formerror").show();
        $("#formerror").text("Error: Password must contain at least six characters!");
        form.pwd1.focus();
        return false;
      }
    } else {
      //alert("Error: Please check that you\'ve entered and confirmed your password!");
      $("#formerror").show();
      $("#formerror").text("Error: Please check that you\'ve entered and confirmed your password!");
      form.pwd1.focus();
      return false;
    }
    return true;
  }
//<![CDATA[
$(window).load(function(){
$(".chb").change(function() {
    var checked = $(this).is(":checked");
    $(".chb").prop("checked",false);
    if(checked) {
        $(this).prop("checked",true);
    }
});
});//]]> 
  
  
</script>    
</head>

<body>
        <div class="container">

      <div class="starter-template">
<div class="loginform cf">
<img class="center" src="data:image/png;base64,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" title="پارس ویتایگر" alt="پارس ویتایگر">

<form class="form-horizontal" name="form" method="post" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return checkForm(this);">
<fieldset>

<!-- Form Name -->
<legend class="center" >Change User\'s Password</legend>';
    if (isset($_GET['status']) && $_GET['status'] == 'success') {
        echo '<div id="formsuccess" class="alert alert-success">User Password Changed Successfully</div>';
    }
    if (isset($_GET['status']) && $_GET['status'] == 'error' && isset($_GET['msg'])) {
        $style = "";
        $msg   = $_GET['msg'];
    } else {
        $style = 'style="display:none;"';
        $msg   = "";
    }
echo '<div id="formerror" class="alert alert-danger" '.$style.'>'.$msg.'</div>
<!-- Select Basic -->
<div class="form-group">
  <label class="col-md-4 control-label" for="selectbasic">CRM User:</label>
  <div class="col-md-4">
'.$listusers.'
 </div>
</div><br />

<!-- Password input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="pwd1">Password:</label>
  <div class="col-md-5">
    <input id="pwd1" name="pwd1" type="password" placeholder="New Password" class="form-control input-md" required="">
    
  </div>
</div><br />

<!-- Password input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="pwd2">Confirm Password:</label>
  <div class="col-md-5">
    <input id="pwd2" name="pwd2" type="password" placeholder="Confirm Password" class="form-control input-md" required="">
    
  </div>
</div><br />

<!-- Multiple Checkboxes (inline) -->
<div class="form-group">
  <label class="col-md-4 control-label" for="checkboxes">Privilege:</label>
  <div class="col-md-4">
    <label class="checkbox-inline" for="checkboxes-0">
      <input type="checkbox" name="recreate" id="checkboxes-0" value="1"  class="chb" checked>
      Recreate User Privilege Files
    </label>
    <label class="checkbox-inline" for="checkboxes-1">
      <input type="checkbox" name="recreate" id="checkboxes-1" value="0"  class="chb">
      Ignore User Privilege Files
    </label>
  </div>
</div><br />

<!-- Button -->
<div class="form-group">
  <label class="col-md-4 control-label" for="singlebutton"></label>
  <div class="col-md-4">
    <button id="singlebutton" name="singlebutton" class="btn btn-danger">Submit</button>
  </div>
</div>

</fieldset>
</form>
    
    <p style="margin-bottom:-20px; text-align:center; font-size:80%">Developed By <a href="http://www.parsvt.com" target="_blank">ParsVT Group</a>  |  <a href="http://forum.parsvt.com" target="_blank">Forum</a></p>
    </div>
        
      </div>

    </div>    
</body>
</html>';
}
?>