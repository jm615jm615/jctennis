<?php
// 共通設定を読み込む
include_once("common.inc.php");
//パラメータ「m」に応じて処理を分岐させる
$m = isset($_GET["m"]) ? $_GET["m"] : "";
switch ($m) {
    case 'add'      : m_add(); break;    // TODOの新規追加
    case 'update'   : m_done(); break;   // TODOのステータスを完了にする
    default         : show_todo();       // TODOの一覧を表示
}

// TODOを表示する前処理
function show_todo() {
    $event_id = intval(s_trim_get("event_id"));
    if ($event_id <= 0) {
        s_error("不正なイベントです。");
    } else {
        show_group_todo($event_id); // グループ内のTODOを表示する
    }
}

// グループ内のTODOを表示する処理
function show_group_todo($event_id) {
    $self = $_SERVER["SCRIPT_NAME"];
    $event = s_get_event_info($event_id);
    if (!$event) {
        s_error("イベントの指定が間違っています。"); exit;
    }
    //イベント内容を取得
    $place = htmlspecialchars($event["place"]);
    $comment = htmlspecialchars($event["comment"]);
    $event_id = intval($event["event_id"]);
    $event_date = date('n月j日',strtotime($event["event_date"]));
    $event_week = "(".getYoubi($event["event_date"]).")";
    $time_from = htmlspecialchars(wordwrap($event["time_from"],2,":",true));
    $time_to = htmlspecialchars(wordwrap($event["time_to"],2,":",true));
    $time_span = $time_from."〜".$time_to;
    
    $user_id = s_get_user_id();
    $user_comment = htmlspecialchars(s_get_event_tran($event_id, $user_id)["comment"]);
        
    //参加者数を取得する
    $participant = htmlspecialchars(count_participant($event_id));
    //欠席者数を取得する
    $absence = htmlspecialchars(count_absence($event_id));
    //登録未定者数を取得する
    $undecided = htmlspecialchars(count_undecided($event_id));
    
    //参加者リストを作成する
    if($participant > 0){
        $participant_list = make_participant($event_id);
    }else {
        $participant_list = "<li ><font size='2'>なし</font></li>";
    }
    
    //欠席者リストを作成する
    if($absence > 0){
        $absence_list = make_absence($event_id);
    }else{
        $absence_list = "<li ><font size='2'>なし</font></li>";
    }
    
    //登録未定者数を作成する
    if($undecided > 0){
        $undecided_list = make_undecided($event_id);
    }else{
        $undecided_list = "<li ><font size='2'>なし</font></li>";
    }
    
    //ステータスによってボタンの表示を変える
    if(check_eventdate($event)){
        if(is_particiate($event_id, $user_id)){
            //登録済み
            $button = "<button type='submit' data-theme='b'>更新</button>";
            $button_m = "update";
            
            //出欠状態でトグルボタンの初期表示を設定する
            $event_tran = s_get_event_tran($event_id, $user_id);
            $selected_status = intval($event_tran["status"]); 
            $selected_no = "";
            $selected_yes = "";
            if($selected_status == 0){
                $selected_no = "selected";
            }else if($selected_status == 1){
                $selected_yes = "selected";
            }
        }else{
            //未登録
            $button = "<button type='submit' data-theme='b'>登録</button>";  
            $button_m = "add";
        }
    }else{
        //終わったイベントなので登録不可能にする
        $button = "<a href='#' data-role='button' id='disableButton'>登録不可</a>";
    }
    
    show_html_header();
        echo <<< __FORM__
<div data-role="content" id="todo_form">
    <div data-role="content">
        <h3>日時：$event_date$event_week$time_span</h3>
        <p>場所：$place</p>
        <p>コメント：$comment</p>
        <hr>
        <legend>出欠状況：</legend>
        <div data-role="collapsible-set">
                <!-- <div data-role="collapsible" data-theme="e" data-content-theme="e"> -->
                <div data-role="collapsible">
                        <h3>参加者：{$participant}名</h3>
                            <ul data-role="listview">
				$participant_list
                            </ul>
                </div>
                <div data-role="collapsible">
                        <h3>欠席者：{$absence}名</h3>
                            <ul data-role="listview">
				$absence_list
                            </ul>
                </div>
                <div data-role="collapsible">
                        <h3>未定　：{$undecided}名</h3>
                            <ul data-role="listview">
				$undecided_list
                            </ul>
                </div>
        </div>
        <hr>
        <form action="$self" method="get">
            <legend>出欠状況：</legend>
            <div data-role="fieldcontain">
                <select name="tennis_status" data-role="slider">
                    <option value="0" $selected_no>欠席</option>
                    <option value="1" $selected_yes>参加</option>
                </select>
            </div>
            <input type="hidden" name="m" value=$button_m />
            <input type="hidden" name="event_id" value="$event_id" />               
            <div data-role="fieldcontain">
                <label for="comment">コメント：</label>
                <textarea name="comment" id="comment"></textarea>
            </div>
            $button
        </form>
    </div> 
</div>
__FORM__;
    
    show_html_footer();
    //show_html_footer_menu();
}

// TODOを追加する処理
function m_add() {
    $user_id = s_get_user_id();
    $event_id = intval(s_trim_get("event_id"));
    $event = s_get_event_info($event_id);
    if (empty($event["event_id"])) {
        s_error("存在しないイベントです"); exit;
    }

    $status = intval(s_trim_get("tennis_status"));
    $comment = s_trim_get("comment");
    
    // SQLの指定
    $insert = "INSERT INTO event_tran (event_id,user_id,status,comment,add_date,del_date)VALUES(:event_id,:user_id,:status,:comment,:add_date,:del_date)";
    // SQLを実行
    $db = s_get_db();
    $stmt = $db->prepare($insert);
    if (!$stmt) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }
    
    $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':status', $status, PDO::PARAM_INT);
    $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
    $stmt->bindValue(':add_date', date('Y-m-d'));
    $stmt->bindValue(':del_date', null);
    
    $r = $stmt->execute();
    if ($r === false) { s_error("データベースへの挿入エラー".print_r($db->errorInfo(),true));exit; }
    // 変更内容を示すためにページをリロードする
    $self = $_SERVER["SCRIPT_NAME"];
    header("location: $self?event_id=$event_id");
}

// TODOを完了する処理
function m_done() {
    $user_id = s_get_user_id();
    $event_id = intval(s_trim_get("event_id"));
    $event = s_get_event_info($event_id);
    if (empty($event["event_id"])) {
        s_error("存在しないイベントです"); exit;
    }

    $status = intval(s_trim_get("tennis_status"));
    $comment = s_trim_get("comment");

    // SQLの指定
    $update = "UPDATE event_tran SET status=:status, comment=:comment  WHERE event_id=:event_id and user_id=:user_id";
    // SQLを実行
    $db = s_get_db();
    $stmt = $db->prepare($update);
    if (!$stmt) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }

    $stmt->bindValue(':status', $status, PDO::PARAM_INT);
    $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);    
    $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    
    $r = $stmt->execute();
    if ($r === false) { s_error("データベースへの更新エラー".print_r($db->errorInfo(),true));exit; }    
    
    // 変更内容を示すためにページをリロードする
    $self = $_SERVER["SCRIPT_NAME"];
    header("location: $self?event_id=$event_id");
}

//参加者リストを作成する
function make_participant($event_id) {
    
    $list = "";
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM event_tran WHERE event_id=$event_id and status='1'");
    
    foreach ($stmt as $row) {
        $user = s_get_user_info($row[user_id]);

        $nickname = htmlspecialchars($user["nickname"]);
        $comment = htmlspecialchars($row["comment"]);
        $str = $nickname."：".$comment;

        $list .= "<li ><font size='2' class='wordbreak'>$str</font></li>";
    }    
    return $list;
}

//欠席者リストを作成する
function make_absence($event_id) {
    
    $list = "";
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM event_tran WHERE event_id=$event_id and status='0'");
    
    foreach ($stmt as $row) {
        $user = s_get_user_info($row[user_id]);

        $nickname = htmlspecialchars($user["nickname"]);
        $comment = htmlspecialchars($row["comment"]);
        $str = $nickname."：".$comment;

        $list .= "<li ><font size='2' class='wordbreak'>$str</font></li>";
    }    
    return $list;
}

//登録未定者リストを作成する
function make_undecided($event_id) {
    
    $list = "";
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM users where not exists (select * from event_tran where event_id = $event_id and users.user_id = event_tran.user_id)");
    
    foreach ($stmt as $row) {
        $nickname = htmlspecialchars($row["nickname"]);

        $list .= "<li ><font size='2' class='wordbreak'>$nickname</font></li>";
    }    
    return $list;
}
