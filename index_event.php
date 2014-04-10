<?php
//まずはcommonが呼び出される
include_once("common.inc.php");

$list_evet = "";
$db = s_get_db();
$stmt = $db->query("SELECT * FROM event_master order by event_date desc");
foreach ($stmt as $row) {
    
    $place = htmlspecialchars($row["place"]);
    $event_id = intval($row["event_id"]);
    $event_date = date('n月j日',strtotime($row["event_date"]));
    $event_week = "(".getYoubi($row["event_date"]).")";
    
    if(check_eventdate($row)){
        $check = "";
    }else{
        $check = " - 完了 - ";
    }
    
    //参加者数を取得する
    $participant = htmlspecialchars(count_participant($event_id));
    $participant_str = "参加者：".$participant."名";        
    $list_event .= "<li data-icon='gear'><a href='edit.php?event_id=$event_id' data-transition='slide'>";
    $list_event .= "<h6 class='wordbreak'>$event_date$event_week$check</h6>";
    $list_event .= "</a></li>";
}

show_html_header();
echo <<< __BODY__
<div class="menu">
</div>
      <div data-role="content">
        <div id="list_form">
            <ul data-role="listview"  data-dividertheme="a">
                <li data-role="list-divider" style="text-align:center">イベント編集</li>
                $list_event
            </ul>
        </form></div>
      </div>
__BODY__;


