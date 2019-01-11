<?php
    session_start();
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Редактирование списка адресатов</title>
        <style>
        <!--
        * {
            font-family : Arial, Verdana, sans-serif;
            font-size   : 12px;
        }
        .border_1 {
            border: 1px solid #ccc;            
        }
        td.border_1{
            padding: 5px;
        }
        -->
        </style>
    </head>
    <body>
<?php  
  require('standalone.php');
  // Check permissions
  $bAllowed = false;
  $oUsers   = cmsController::getInstance()->getModule('users');
  if($oUsers) {
      if($oUsers->is_auth()) {
        $iUserId  = $_SESSION['user_id'];
        $bAllowed = permissionsCollection::getInstance()->isAllowedMethod($iUserId, 'webforms', 'addresses');
      } else {
        $bAllowed = false;
      }
  } else {
      $bAllowed = false;
  }  
  // Output right content
  if($bAllowed) {
      if(isset($_REQUEST['data'])) {
          mysql_query('TRUNCATE TABLE cms_webforms');
          $sSQL = '';
          foreach($_REQUEST['data'] as $iAddrId => $aAddrData) {
              if(!isset($aAddrData['delete'])) {
                  $iId = intval($iAddrId);
                  $sEMail = mysql_real_escape_string($aAddrData['email']);
                  $sDesc  = mysql_real_escape_string($aAddrData['description']);
                  $sSQL  .= '("'.$iAddrId.'", "'.$sEMail.'", "'.$sDesc.'"),';
              }
          } 
          if(strlen($sSQL)) {
              $sSQL = 'INSERT INTO cms_webforms(id, email, descr) VALUES'.substr($sSQL, 0, strlen($sSQL)-1);
              mysql_query($sSQL);
          }
      }
      if(isset($_REQUEST['new'])) {
          $sEMail = mysql_real_escape_string($_REQUEST['new']['email']);
          $sDesc  = mysql_real_escape_string($_REQUEST['new']['description']);
          if(strlen($sEMail) && strlen($sDesc)) {
            $sSQL   = 'INSERT INTO cms_webforms(email, descr) VALUES("'.$sEMail.'", "'.$sDesc.'")';
            mysql_query($sSQL);
          }
      }
      $sSQL    = "SELECT * FROM cms_webforms";
      $rResult = mysql_query($sSQL);
?>
        <form method="post">
        <table width="100%" cellspacing="0" class="border_1">
            <tr class="border_1">
                <td width="45%" class="border_1" style="text-align:center;"><b>E-Mail</b></td>
                <td width="45%" class="border_1" style="text-align:center;"><b>Описание</b></td>
                <td width="10%" class="border_1" style="text-align:center;"><b>Удалить</b></td>
            </tr>
<?php
    while($aRow = mysql_fetch_assoc($rResult)) {
        $iRowId = $aRow['id'];
?>
            <tr class="border_1">
                <td class="border_1"><input style="width:100%;" type="text" name="data[<?php echo $iRowId; ?>][email]"       value="<?php echo $aRow['email'] ?>" /></td>
                <td class="border_1"><input style="width:100%;" type="text" name="data[<?php echo $iRowId; ?>][description]" value="<?php echo $aRow['descr'] ?>" /></td>
                <td class="border_1"><input style="width:100%;" type="checkbox" name="data[<?php echo $iRowId; ?>][delete]" /></td>
            </tr>
<?php
    }
?>
            <tr class="border_1">
                <td class="border_1"><input style="width:100%;" type="text" name="new[email]" /></td>
                <td class="border_1"><input style="width:100%;" type="text" name="new[description]" /></td>
                <td class="border_1">&nbsp;</td>
            </tr>
            <tr class="border_1">
                <td colspan="3" align="right" class="border_1"><input type="submit" value="Сохранить" /></td>                
            </tr>
        </table>
        </form>
<?php
  } else {
?>
        <table width="100%" style="border:0;">
            <tr><td align="center">
            <form method="post" action="/users/login_do/">
            <table style="border:0">
                <tr>
                    <td>Логин</td>
                    <td><input type="text" name="login" value="" /></td>
                </tr>
                <tr>
                    <td>Пароль</td>
                    <td><input type="password" name="password" value="" /></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right;"><input type="submit" value="Войти" /></td>                    
                </tr>
            </table>
            </form>
            </td></tr>
        </table>
<?php
  }
?>
    </body>
</html>