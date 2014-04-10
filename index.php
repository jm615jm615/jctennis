<?php
//まずはcommonが呼び出される
include_once("common.inc.php");

// ユーザーIDが指定されていれば、そのユーザーの情報を表示し、
// そうでなければ、自身の情報を表示する
$user_id = intval(s_trim_get("user_id"));
if ($user_id <= 0) $user_id = s_get_user_id ();
$status_image ="";
$list_h = "";
$list_evet = "";
$db = s_get_db();
$stmt = $db->query("SELECT * FROM event_master order by event_date desc LIMIT 10");
foreach ($stmt as $row) {
    
    $place = htmlspecialchars($row["place"]);
    $commnet = htmlspecialchars($row["comment"]);
    $event_id = intval($row["event_id"]);
    $event_date = date('n月j日',strtotime($row["event_date"]));
    $event_week = "(".getYoubi($row["event_date"]).")";
    $time_from = htmlspecialchars(wordwrap($row["time_from"],2,":",true));
    $time_to = htmlspecialchars(wordwrap($row["time_to"],2,":",true));
    $time_span = $time_from."〜".$time_to;
    
    //参加者数を取得する
    $participant = htmlspecialchars(count_participant($event_id));
    $participant_str = "参加者：".$participant."名";
    
    
    //イベントIDから出欠状況を取得する
    $status_image = check_status($user_id,$event_id);
    $status_image_h = htmlspecialchars($status_image);
    
    $list_event .= "<li data-icon='false'><a href='todo.php?event_id=$event_id' data-transition='slide'>";
    $list_event .= "<h6 class='wordbreak'>$event_date$event_week$time_span</h6>";
    $list_event .= "<p>$commnet</p>";
    $list_event .= "<p class='ui-li-aside'>$participant_str</p>";
    $list_event .= "</a></li>";
}

$user = s_get_user_info($user_id);



$nickname_h = htmlspecialchars($user["nickname"]);
show_html_header();
echo <<< __BODY__
<div class="menu">
</div>
      <div data-role="content">
        <div id="list_form">
            <ul data-role="listview"  data-dividertheme="c">
                <li data-role="list-divider" style="text-align:center">イベント一覧</li>
                $list_event
            </ul>
        </form></div>
      </div>
__BODY__;
//show_html_footer_menu();


//出欠状況をチェックする
function check_status($user_id,$event_id) {
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM event_tran WHERE user_id=$user_id and event_id=$event_id LIMIT 1");
    //$stmt->execute(array($user_id, $event_id));
    $row = $stmt->fetch();
    $image = "";

    //statusの値で判断
    if($row["status"] == "0"){
        $image = "face_cry.png";
    }else if($row["status"] == "1"){
        $image = "face_ok_2.png";
    }else{
        $image = "question.png";
    }
     
    return $image;
}
