<?php
// 共通設定を読み込む
include_once("common.inc.php");
//パラメータ「m」に応じて処理を分岐させる
$m = isset($_GET["m"]) ? $_GET["m"] : "";
switch ($m) {
    case 'update' : m_update(); break;   // イベントの更新
    case 'delete' : m_delete(); break;   // イベントの削除
    default       : show_event();        // イベント内容を表示
}

// イベントを表示する前処理
function show_event() {
    $event_id = intval(s_trim_get("event_id"));
    if ($event_id <= 0) {
        s_error("不正なイベントです。");
    } else {
        show_edit_form($event_id); // グループ内のTODOを表示する
    }
}

// グループ内のTODOを表示する処理
function show_edit_form($event_id) {
    $self = $_SERVER["SCRIPT_NAME"];
    
    $event = s_get_event_info($event_id);

    //イベント内容を取得
    $place = htmlspecialchars($event["place"]);
    $comment = htmlspecialchars($event["comment"]);
    $event_id = intval($event["event_id"]);
    $event_date = $event["event_date"];
    $time_from = date('H:i',strtotime($event["time_from"]));
    $time_to = date('H:i',strtotime($event["time_to"]));
    
    show_html_header();
        echo <<< __FORM__
<div data-role="content" id="show_edit_form">
    <div data-role="content">
        <form action="$self" method="get">
        <h3>イベント編集</h3>
            <div data-role="fieldcontain">
                <label for="event_date">日付：</label>
                <input type="date" name="event_date" value=$event_date />
            </div>
            <div data-role="fieldcontain">
                <label for="time_from">開始時刻：</label>
                <input type="time" name="time_from" value=$time_from />
            </div>
            <div data-role="fieldcontain">
                <label for="time_to">終了時刻：</label>
                <input type="time" name="time_to" value=$time_to />
            </div>
            <input type="hidden" name="m" value="update" />
            <input type="hidden" name="event_id" value="$event_id" />
            場所: <input type="text" name="place" value=$place size="40" maxlength="140"/>
            コメント: <input type="text" name="comment" value=$comment size="40" />
            <button type="submit" data-theme="b">更新</button>
        </form>
        <form action="$self" method="get">
            <input type="hidden" name="m" value="delete" />
            <input type="hidden" name="event_id" value="$event_id" />
            <button type="submit" data-theme="a">削除</button>
        </form>
    </div> 
</div>
__FORM__;
    
    show_html_footer();
}

// イベントを更新する処理
function m_update() {
    
    $event_id = intval(s_trim_get("event_id"));
    $event = s_get_event_info($event_id);
    if (empty($event["event_id"])) {
        s_error("存在しないイベントです"); exit;
    }

    $event_date = s_trim_get("event_date");
    
    $time_from_h = htmlspecialchars(s_trim_get("time_from"));
    $time_from = str_replace(":", "", $time_from_h); 
    $time_to_h = htmlspecialchars(s_trim_get("time_to"));
    $time_to = str_replace(":", "", $time_to_h); 
    
    $place = s_trim_get("place");
    $comment = s_trim_get("comment");

    // SQLの指定
    $update = "UPDATE event_master SET event_date=:event_date, time_from=:time_from, time_to=:time_to, place=:place, comment=:comment  WHERE event_id=:event_id";
    // SQLを実行
    $db = s_get_db();
    $stmt = $db->prepare($update);
    if (!$stmt) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }

    $stmt->bindValue(':event_date', $event_date);
    $stmt->bindValue(':time_from', $time_from, PDO::PARAM_STR);
    $stmt->bindValue(':time_to', $time_to, PDO::PARAM_STR);
    $stmt->bindValue(':place', $place, PDO::PARAM_STR);
    $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);    
    $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    
    $r = $stmt->execute();
    if ($r === false) { s_error("データベースへの更新エラー".print_r($db->errorInfo(),true));exit; }    
    
    // 変更内容を示すためにページをリロードする
    $self = $_SERVER["SCRIPT_NAME"];
    header("location: $self?event_id=$event_id");
}

// イベントを削除する処理
function m_delete() {
    
    $event_id = intval(s_trim_get("event_id"));
    $event = s_get_event_info($event_id);
    if (empty($event["event_id"])) {
        s_error("存在しないイベントです"); exit;
    }
    
    // SQLを実行
    $db = s_get_db();
    
    // イベントマスタの削除
    $delete_master = "DELETE FROM event_master WHERE event_id=:event_id";
    $stmt_m = $db->prepare($delete_master);
    if (!$stmt_m) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }
    
    $stmt_m->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    
    $rm = $stmt_m->execute();
    if ($rm === false) { s_error("データベースへの削除エラー".print_r($db->errorInfo(),true));exit; }
    
    // イベントトランの削除
    $delete_tran = "DELETE FROM event_tran WHERE event_id=:event_id";
    // SQLを実行
    $stmt_t = $db->prepare($delete_tran);
    if (!$stmt_t) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }
    
    $stmt_t->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    
    $rt = $stmt_t->execute();
    if ($rt === false) { s_error("データベースへの削除エラー".print_r($db->errorInfo(),true));exit; }
    
    // 一覧画面に戻る
    header("location: index_event.php");
}