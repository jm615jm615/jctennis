<?php
// 全てのプログラムファイルから参照される共通のファイル
// ログインチェックなどを行う
//------------------------------------------------------------------------------
// 初期設定
//------------------------------------------------------------------------------
// 言語の設定
mb_language("Japanese");
mb_internal_encoding("UTF-8");
// ユーザーの設定
$server_mail_from = "nadesiko_lang@yahoo.co.jp";             // サーバーメールアドレス★
//現在のスクリプトパスを取得
$script_name = $_SERVER["SCRIPT_NAME"];
// ライブラリの取り込み
include_once("lib/html-template.inc.php");

//------------------------------------------------------------------------------
$test = false;
if($test){
    //セッション変数を空に
    $_SESSION = array();
    //セッションクッキー削除
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/') ;
    }
    //セッション破棄
    session_destroy();
}

// セッションを開始
session_start();

//------------------------------------------------------------------------------
// MySQLへ接続する
$user = "root";
$pass = "root";

//文字コードの指定がないとPDOの文字化けが発生するので注意
$dsn = 'mysql:dbname=test;host=localhost;charset=utf8';

try{
    $db = new PDO($dsn, $user, $pass);
}catch (PDOException $e){
    print("MySQLへの接続に失敗しました。".$e->getMessage());
    die();
}
  //結果保持用メモリを開放する
  //スクリプトの実行後に自動的に開放されるから、
  //メモリの使用量が多すぎると懸念される場合にのみ必要らしい・・・12/29
  //mysql_free_result($result);

  //MySQLへの接続を閉じる
  //勝手に閉じられるのでわざわざしなくてもいいらしい・・・12/29
  //mysql_close($link) or die("MySQL切断に失敗しました。");


// データベースのオブジェクトを取得する関数★ ("s_"の接頭辞をつけておくと、入力補完が利いて便利)
function s_get_db() { global $db; return $db; }

// ログインチェック
if (!is_logined()) {
    // ログインしてなければログインページへジャンプ
    $base = basename($script_name);
    if ($base != "login.php") {
        //ログイン画面で戻る時のページを現在のページに指定する
        $url = "./login.php?back=$script_name";
        //$url = "./login.php";
        //ログイン画面に強制的にジャンプさせる
        header("location: $url"); exit;
    }
}

// セッション上でログインしているかどうか調べる関数
//loginに値があればtrue
function is_logined() {
    return isset($_SESSION["login"]);
}

//html-templateで呼び出される関数
function is_admin() {
    if (!is_logined()) return false;
    $user = s_get_login_info();
    return ($user["user_type"] == "admin");
}

// 空白フィールドを削除して送信されたフォームデータの値を取得
function s_trim_get($name) {
    $v = isset($_REQUEST[$name]) ? $_REQUEST[$name] : ""; // パラメータ取得
    $v = trim($v); // トリム
    return $v;
}

// 管理人を取得する
function s_get_admin_user() {
    //$db = s_get_db();
    $sql = "SELECT * FROM users WHERE user_type='admin' LIMIT 1";
    $result = mysql_query($sql) or die("クエリの送信に失敗しました。<br />SQL:".$sql);
}

function s_get_user_info($user_id) {
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM users WHERE user_id=$user_id");
    return $stmt->fetch();
}

// エラーを画面に表示する
function s_error($message, $title = "エラー") {
    show_html_header();
    echo "<h3>$title</h3><div>$message</div>";
    show_html_footer();
    exit;
}

// メッセージを画面に表示する
function s_message($message, $title = "お知らせ") {
    show_html_header();
    echo "<h3>$title</h3><div>$message</div>";
    show_html_footer();
    exit;
}

// ログイン情報を得る
function s_get_login_info() {
    return isset($_SESSION["login"]) ? $_SESSION["login"] : array();
}
// ログイン中のユーザーのユーザーIDを得る
function s_get_user_id() {
    $info = s_get_login_info();
    return isset($info["user_id"]) ? intval($info["user_id"]) : 0;
}

// イベントマスタの情報を取得する
function s_get_event_info($event_id) {
    $event_id = intval($event_id);
    $q = "SELECT * FROM event_master WHERE event_id=$event_id LIMIT 1";
    $db = s_get_db();
    $stmt = $db->query($q);
    $row = $stmt->fetch();
    return $row;
}

// 指定したグループに参加しているか調べる
function s_check_group_member($group_id, $user_id) {
    $db = s_get_db();
    $q = "SELECT * FROM group_members WHERE group_id=? AND user_id=? ";
    $r = $db->prepare($q);
    $r->execute(array($group_id, $user_id));
    $row = $r->fetch();
    return (isset($row["user_id"]));
}


// グループに属するTODOをHTMLで取得する
function s_get_group_todo($group_id, $status = 0, $limit = 100) {
    // TODOを取得する
    // (*1) SQLを作成する
    $q = "SELECT * FROM todo_list ".
        " WHERE status=$status AND group_id=$group_id ".
        " ORDER BY rank DESC, todo_id DESC LIMIT $limit";
    $item = "";
    // (*2) SQLを実行して結果を取り出す
    $db = s_get_db();
    $stmt = $db->query($q);
    // (*3) 取り出した結果を元にTODOの一覧をHTMLで作成
    foreach ($stmt as $row) {
        // (*4) カラムの値をHTMLに変換したりリンクを作ったりする
        $todo_id = intval($row["todo_id"]);
        $title_h = htmlspecialchars($row["title"]); // タイトル
        $date = date("m/d", $row["ctime"]);
        $user = s_get_user_info($row["user_id"]);
        $user_name_h = htmlspecialchars($user["nickname"]);
        $info = "<span class='item_info'>({$user_name_h} {$date})</span>";
        $rank = intval($row["rank"]);
        $star = show_todo_star($todo_id, $rank); // 重要度ランクのためのリンク
        if ($status == 0) {
            $link = "todo.php?m=done&todo_id=$todo_id&group_id=$group_id";
            $done_link = "<a href='$link'>完了</a>";
        } else {
            $link = "todo.php?m=readd&todo_id=$todo_id&group_id=$group_id";
            $done_link = "<a href='$link'>TODOに戻す</a>";
        }
        $div = "{$star} {$title_h} {$info} [$done_link]";
        $item .= "<div class='item'>$div</div>";
    }
    if ($item == "") {
        return "(ありません。)";
    }
    return $item;
}

function show_todo_star($todo_id, $rank) {
    $star = "";
    for ($i = 1; $i <= 5; $i++) {
        $link = "todo.php?m=setrank&todo_id=$todo_id&rank=$i";
        $star .= "<span class='star'><a href='$link'>";
        $star .= ($i <= $rank) ? "★" : "☆";
        $star .= "</a></span>";
    }
    return $star;
}

//半角英数チェック
function is_alnum($text) {
    if (preg_match("/^[a-zA-Z0-9]+$/",$text)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

//指定日の曜日を取得する
function getYoubi($date){
    $sday = strtotime($date);
    $res = date("w", $sday);
    $day = array("日", "月", "火", "水", "木", "金", "土");
    return $day[$res];
}

//参加者数を取得する
function count_participant($event_id) {
    $db = s_get_db();
    $stmt = $db->query("SELECT count(*) FROM event_tran WHERE event_id=$event_id and status='1'");
    $count = $stmt->fetchColumn();
    return $count;
}

//欠席者数を取得する
function count_absence($event_id) {
    $db = s_get_db();
    $stmt = $db->query("SELECT count(*) FROM event_tran WHERE event_id=$event_id and status='0'");
    $count = $stmt->fetchColumn();
    return $count;
}

//登録未定者数を取得する
function count_undecided($event_id) {
    $db = s_get_db();
    $stmt = $db->query("SELECT count(*) FROM users where not exists (select * from event_tran where event_id = $event_id and users.user_id = event_tran.user_id)");
    $count = $stmt->fetchColumn();
    return $count;
}

//参加者数を取得する
function is_particiate($event_id,$user_id) {
    $db = s_get_db();
    $stmt = $db->query("SELECT count(*) FROM event_tran WHERE event_id=$event_id and user_id=$user_id");
    $count = $stmt->fetchColumn();
    if($count > 0){
        return true;
    }else{
        return false;
    }

}

//イベントが終わってないかをチェック
function check_eventdate($event) {
    // 今日の日付を取得
    $dt = new DateTime();
    $dt->setTimeZone(new DateTimeZone('Asia/Tokyo'));
    $today = $dt->format('Y-m-d');
    // 比較する日付を設定
    $target_day = $event["event_date"];
    // 日付を比較（イベントの日付：今日の日付）
    if (strtotime($target_day) >= strtotime($today)) {
        return true;
    }else{
        return false;
    }
}

function s_get_event_tran($event_id,$user_id) {
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM event_tran WHERE event_id=$event_id and user_id=$user_id");
    return $stmt->fetch();
}