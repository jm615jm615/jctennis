<?php
// 共通設定を読み込む
include_once("common.inc.php");
//パラメータ「m」に応じて処理を分岐させる
$m = isset($_GET["m"]) ? $_GET["m"] : "";
switch ($m) {
    case 'add'      : m_add(); break;    // イベントの新規追加
    case 'update'   : m_done(); break;   // TODOのステータスを完了にする
    default         : show_add_form();   // TODOの一覧を表示
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
function show_add_form() {
    $self = $_SERVER["SCRIPT_NAME"];
    
    show_html_header();
        echo <<< __FORM__
<div data-role="content" id="todo_form">
    <div data-role="content">
        <form action="$self" method="get">
        <h3>新規イベント登録フォーム</h3>
            <div data-role="fieldcontain">
                <label for="event_date">日付：</label>
                <input type="date" name="event_date" value="" />
            </div>
            <div data-role="fieldcontain">
                <label for="time_from">開始時刻：</label>
                <input type="time" name="time_from" value="" />
            </div>
            <div data-role="fieldcontain">
                <label for="time_to">終了時刻：</label>
                <input type="time" name="time_to" value="" />
            </div>
            <input type="hidden" name="m" value="add" />
            場所: <input type="text" name="place" value="" size="40" maxlength="140"/>
            <div data-role="fieldcontain">
                <label for="comment">コメント：</label>
                <textarea name="comment" id="comment"></textarea>
            </div>
            <button type="submit" data-theme="b">登録</button>
        </form>
    </div> 
</div>
__FORM__;
    
    show_html_footer();
}

// イベントを追加する処理
function m_add() {
    
    $event_date = s_trim_get("event_date");
    
    $time_from_h = htmlspecialchars(s_trim_get("time_from"));
    $time_from = str_replace(":", "", $time_from_h); 
    $time_to_h = htmlspecialchars(s_trim_get("time_to"));
    $time_to = str_replace(":", "", $time_to_h); 
    
    $place = s_trim_get("place");
    $comment = s_trim_get("comment");
    
    // SQLの指定
    $insert = "INSERT INTO event_master (title,event_date,time_from,time_to,place,comment,add_date,del_date)VALUES(:title,:event_date,:time_from,:time_to,:place,:comment,:add_date,:del_date)";
    // SQLを実行
    $db = s_get_db();
    $stmt = $db->prepare($insert);
    if (!$stmt) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }
    
    $stmt->bindValue(':title', null);
    $stmt->bindValue(':event_date', $event_date);
    $stmt->bindValue(':time_from', $time_from, PDO::PARAM_STR);
    $stmt->bindValue(':time_to', $time_to, PDO::PARAM_STR);
    $stmt->bindValue(':place', $place, PDO::PARAM_STR);
    $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
    $stmt->bindValue(':add_date', date('Y-m-d'));
    $stmt->bindValue(':del_date', null);
    
    $r = $stmt->execute();
    if ($r === false) { s_error("データベースへの挿入エラー".print_r($db->errorInfo(),true));exit; }
    // 変更内容を示すためにページをリロードする
    $self = $_SERVER["SCRIPT_NAME"];
    header("location: index.php");
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
