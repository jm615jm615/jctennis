<?php
// HTMLのテンプレートを出力する関数群
function show_html_header($title = "") {
    //global $menu_link_left,$menu_link;
    if ($title != "") $title .= "-";
    $title_h = htmlspecialchars($title);
    // ログインしているなら、ヘッダー、フッターのメニューを表示する
    $menu_link = "";
    
    if (is_logined ()) {
        
        //ページごとにヘッダーボタンを変更する
        $base = basename($_SERVER["SCRIPT_NAME"]);

        switch ($base) {
        case "index.php"    : $left = "<a href='index_event.php' data-role='button' data-icon='edit'><font color='#4aa0e0'>   Edit   </font></a>"; break;
        case "todo.php"     : $left = "<a href='index.php' data-role='button' data-icon='arrow-l' data-transition='slide' data-direction='reverse'><font color='#4aa0e0'>   Back   </font></a>"; break;
        case "index_event.php"    : $left = "<a href='index.php' data-role='button' data-icon='home'><font color='#4aa0e0'>   Home   </font></a>"; break;
        case "add.php"      : $left = ""; break;
        case "edit.php"     : $left = "<a href='index_event.php' data-role='button' data-icon='arrow-l' data-transition='slide' data-direction='reverse'><font color='#4aa0e0'>   Back   </font></a>"; break;
        default:
            break;
        }
        
        switch ($base) {
        case "index.php"    : $right = "<a href='#navPanel' data-role='button' data-icon='bars'><font color='#4aa0e0'> Menu </font></a>"; break;
        case "todo.php"     : $right = ""; break;
        case "index_event.php"    : $right = "<a href='add.php' data-role='button' data-icon='plus'><font color='#4aa0e0'>   New   </font></a>"; break;
        case "add.php"      : $right = "<a href='index_event.php' data-role='button' data-icon='delete' data-transition='fade' class='ui-btn-right' ><font color='#4aa0e0'>   Close   </font></a>"; break;
        case "edit.php"     : $right = ""; break;
        default:
            break;
        }
        
        $menu_link .= "<div data-role='header' data-position='fixed' data-tap-toggle='false' id='page_head' data-theme='c'>";
        $menu_link .= $left;
        $menu_link .= "<h2><font color='#4aa0e0'><i>JC Tennis</i></font></h2>";
        $menu_link .= $right;
        $menu_link .= "</div>"; 
    }

    echo <<< __HEAD__
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
<!-- <link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.min.css" /> -->
<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.0/jquery.mobile-1.3.0.min.css" />
<!-- <link rel="stylesheet" type="text/css" href="./lib/bartender.css" /> -->
<!-- <script src="http://code.jquery.com/jquery-1.6.4.min.js"></script> -->
<!-- <script src="http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.min.js"></script> -->
<script src="http://code.jquery.com/jquery-1.8.2.min.js"></script>
<script src="http://code.jquery.com/mobile/1.3.0/jquery.mobile-1.3.0.min.js"></script>
<script>

$(document).bind('pagecreate',function(e){
$('#enableButton').removeClass('ui-disabled');
$('#disableButton').addClass('ui-disabled');
});
    
$(document).ready(function(){ 
    function Checked() {
    var val = $("#hoge option:selected").text();
    }
    $("form").submit(function () {
    Checked();
    alert(val);
    }
});
    
</script>
    
<title>{$title_h}JC Tennis</title>

</head>

<body>
$menu_link
        
<div data-role="panel" data-position="right" data-position-fixed="true" data-display="reveal" data-theme="a" id="navPanel">
    <ul data-role="listview" data-inset="true" style="min-width:210px;" data-theme="c">
      <li data-icon="delete"><a href="#" data-rel="close">メニューを閉じる</a></li>
      <li data-icon='false'><a href="#">ユーザー情報</a></li>
      <li data-icon='false'><a href="#">メンバー一覧</a></li>
      <li data-icon='false'><a href="login.php?m=logout">ログアウト</a></li>
    </ul>
</div>

<style>
.ui-li-has-thumb .ui-btn-inner a.ui-link-inherit, .ui-li-static.ui-li-has-thumb {
padding-left: 10px;
min-height: 50px;
}
.ui-li-thumb, .ui-li-icon {
	position: inherit;
}
.ui-icon-my-home { 
  background:url(image/home_blue.png) no-repeat transparent; 
  border-radius: 0px;
  box-shadow: 0px 0px 0px #000;
}
.ui-icon-my-add { 
  background:url(image/note_blue.png) no-repeat transparent;
  border-radius: 0px;
  box-shadow: 0px 0px 0px #000;
}
.ui-icon-my-account {
  background:url(image/load_blue.png) no-repeat transparent;
  border-radius: 0px;
  box-shadow: 0px 0px 0px #000;
}
.ui-icon-my-setting {
  background:url(image/setting.png) no-repeat transparent;
  border-radius: 0px;
  box-shadow: 0px 0px 0px #000;
}
.ui-icon-my-close {
  background:url(image/white_close.png) no-repeat transparent;
  border-radius: 0px;
  box-shadow: 0px 0px 0px #000;
}
.ui-icon-my-power {
  background:url(image/power.png) no-repeat transparent;
  border-radius: 0px;
  box-shadow: 0px 0px 0px #000;
}
.ui-icon-my-user {
  background:url(image/white_user.png) no-repeat transparent;
  border-radius: 0px;
  box-shadow: 0px 0px 0px #000;
}    
#header .ui-btn-left, #header .ui-btn-right {
margin-top:15px;
}
.wordbreak {
	overflow: visible;
	white-space: normal;
}    
.containing-element .ui-slider-switch { width: 20em }
        
</style>

__HEAD__;
}

function show_html_footer() {
    echo <<< __FOOT__
</body>
</html>
__FOOT__;
}


function show_html_footer_menu() {
    echo <<< __FOOT_MENU__
<footer data-role="footer" data-theme="a" data-position="fixed" data-tap-toggle="false" data-id="nav01">
<div data-role="navbar">
<ul>
<li><a href="index.php" data-icon="home">ホーム</a></li>
<li><a href="page1.html" data-icon="plus">イベント追加</a></li>
<li><a href="page2.html" data-icon="gear">設定</a></li>
</ul>
</div>
</footer>
__FOOT_MENU__;
}