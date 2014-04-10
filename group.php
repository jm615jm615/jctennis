<?php
// 共通設定を読み込む
include_once("common.inc.php");
$m = isset($_REQUEST["m"]) ? $_REQUEST["m"] : "";
// モードごとに処理を分岐する
switch ($m) {
    case "create"       : m_create(); break;       // グループ作成フォームの表示
    case "insert_group" : m_insert_group(); break; // グループ作成実行したとき
    case "edit"         : m_edit(); break;         // グループ編集フォームの表示
    case "edit_group"   : m_edit_group(); break;   // グループ編集実行したとき
    case "del_group"    : m_del_group(); break;    // グループの削除(確認画面)
    case "del_group2"   : m_del_group2(); break;   // グループ削除実行
    case "join"         : m_join(); break;         // ユーザーのグループ参加処理
    case "quit"         : m_quit(); break;         // ユーザーのグループ脱退処理
    default             : show_groups(); break;    // グループ一覧の表示
}

// グループ作成フォームを表示する
function m_create() {
    show_create_group_form("insert_group", array());
}
// グループ編集フォームを表示する
function m_edit() {
    $group_id = intval(s_trim_get("group_id"));
    $group = s_get_group_info($group_id);
    if (!$group) { s_error("グループがありません。"); exit; }
    show_create_group_form("edit_group", $group);
}

function show_create_group_form($mode, $default, $errors = array()) {
    $self = $_SERVER["SCRIPT_NAME"];
    $name = isset($default["name"]) ? $default["name"] : "";
    $memo = isset($default["memo"]) ? $default["memo"] : "";
    $group_id = isset($default["group_id"]) ? intval($default["group_id"]): 0;
    $name_h = htmlspecialchars($name, ENT_QUOTES);
    $memo_h = htmlspecialchars($memo, ENT_QUOTES);
    $error_str = implode("", $errors);
    $caption = ($mode == "insert_group") ? "新規作成" : "編集";
    show_html_header();
    echo "<h3>グループの{$caption}</h3>";
    echo <<< __HTML__
<div id="user_form">
    <form action="$self" method="get">
        <input type="hidden" name="m" value="$mode" />
        <input type="hidden" name="group_id" value="$group_id" />
        <label>グループ名</label>
        <input type="text" name="name" value="$name_h" />
        <label>グループの説明</label>
        <input type="text" name="memo" value="$memo_h" />
        <label></label>
        <button type="submit">$caption</button>
    </form>
    <div style='color:red;'>$error_str</div>
</div>
__HTML__;
    if ($mode == "edit_group") {
        echo "<p><a href='$self?m=del_group&group_id=$group_id'>".
             "→グループを削除する</a></p>";
    }
    show_html_footer();
}

// グループの一覧を表示
function show_groups() {
    $db = s_get_db();
    $user_id = s_get_user_id();
    $stmt = $db->query("SELECT * FROM groups ORDER BY group_id DESC");
    $groups = $stmt->fetchAll();
    $list_h = "(まだありません)";
    if ($groups) { // グループがあるとき
        $list_h = "";
        // 自分の属しているグループを取得
        $stmt = $db->query("SELECT group_id FROM group_members WHERE user_id=$user_id");
        $mygroups = array();// 自分の属しているグループのIDをキーにする
        foreach ($stmt->fetchAll() as $g) {
            $mygroups[intval($g["group_id"])] = true;
        }
        // グループ一覧を作成
        foreach ($groups as $g) {
            $group_id = intval($g["group_id"]);
            $name_h = htmlspecialchars($g["name"]);
            $memo_h = htmlspecialchars($g["memo"]);
            // そのグループに参加しているか？
            if (isset($mygroups[$group_id])) {
                $link = "group.php?m=quit&group_id=$group_id";
                $join_link = "参加しています。(<a href='$link'>脱退</a>)";
            } else {
                $link = "group.php?m=join&group_id=$group_id";
                $join_link = "<a href='$link'>→参加する</a>";
            }
            $list_h .= <<< __GROUP__
<h4 class='group_head'>$name_h
            <a href="group.php?m=edit&group_id=$group_id">編集</a></h4>
<div class="group_item">
    <div class="group_left"><a href="todo.php?group_id=$group_id">
            <img src="image/group.png" /></a></div>
    <div class="group_right">
        <p>$memo_h</p>
        <p class="submenu">$join_link</p>
    </div>
</div>
<div style="clear:both;"></div>
__GROUP__;
        }
    }
    show_html_header();
    echo <<< __HTML__
<h3>グループの一覧</h3>
<div>$list_h</div>
<hr/>
<p><a href="group.php?m=create">→新規グループの作成</a></p>
__HTML__;
    show_html_footer();
}

function m_insert_group() {
    // パラメータのチェック
    $errors = array();
    $name = s_trim_get("name");
    $memo = s_trim_get("memo");
    $db = s_get_db();
    if ($name == "") {
        $errors[] = "<p>グループ名が入力されていません。</p>";
    }
    if (count($errors) == 0) {
        // グループ名の重複チェック
        $stmt = $db->prepare("SELECT * FROM groups WHERE name= ?");
        $stmt->execute(array($name));
        $row = $stmt->fetch();
        if (isset($row["name"])) {
            $errors[] = "既に同じ名前のグループがあります。";
        }
    }
    // エラーがあれば再入力
    if (count($errors) > 0) {
        show_create_group_form("insert_group", $_GET, $errors);
        exit;
    }
    // グループを作成する
    $db->beginTransaction();
    $insert_query = <<< __SQL__
INSERT INTO groups (name, memo, maker_id, ctime)
            VALUES (?, ?, ?, ?);
__SQL__;
    $user_id = $_SESSION["login"]["user_id"];
    $stmt = $db->prepare($insert_query);
    if (!$stmt) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }
    $r = $stmt->execute(array($name, $memo, $user_id, time()));
    if ($r === false) { s_error("データベースへの挿入エラー"); exit; }
    $group_id = $db->lastInsertId();
    // 作成したらユーザーは自動的に参加する
    $stmt = $db->prepare("INSERT INTO group_members (group_id,user_id,ctime)VALUES(?,?,?)");
    $r = $stmt->execute(array($group_id, $user_id, time()));
    if ($r === false) { s_error("データベースへの挿入エラー".print_r($db->errorInfo(),true));exit; }
    $db->commit();
    //
    $name_h = htmlspecialchars($name);
    $self = $_SERVER["SCRIPT_NAME"];
    s_message("<p><a href='$self'>グループ「{$name_h}」を作成しました。</a></p>",
            "グループを作成しました。");
}

function m_edit_group() {
    // パラメータのチェック
    $errors = array();
    $name = s_trim_get("name");
    $memo = s_trim_get("memo");
    $group_id = s_trim_get("group_id");
    $db = s_get_db();
    if ($name == "") {
        $errors[] = "<p>グループ名が入力されていません。</p>";
    }
    if (count($errors) == 0) {
        // グループ名の重複チェック
        $stmt = $db->prepare("SELECT * FROM groups WHERE name= ? AND group_id <> ?");
        $stmt->execute(array($name, $group_id));
        $row = $stmt->fetch();
        if (isset($row["name"])) {
            $errors[] = "既に同じ名前のグループがあります。";
        }
    }
    // エラーがあれば再入力
    if (count($errors) > 0) {
        show_create_group_form("edit_group", $_GET, $errors);
        exit;
    }
    // グループを作成する
    $db->beginTransaction();
    $insert_query = <<< __SQL__
UPDATE groups SET name=?, memo=?, maker_id=?, ctime=? WHERE group_id=?
__SQL__;
    $user_id = $_SESSION["login"]["user_id"];
    $stmt = $db->prepare($insert_query);
    if (!$stmt) { echo "データベースエラー:"; print_r($db->errorInfo()); exit; }
    $r = $stmt->execute(array($name, $memo, $user_id, time(), $group_id));
    if ($r === false) { s_error("データベースへの挿入エラー"); exit; }
    //
    $name_h = htmlspecialchars($name);
    $self = $_SERVER["SCRIPT_NAME"];
    s_message("<p><a href='$self'>グループ「{$name_h}」を編集しました。</a></p>",
            "グループを編集しました。");
}

// グループへの参加処理
function m_join() {
    $user_id = s_get_user_id();
    $group_id = intval(s_trim_get("group_id"));
    // グループが存在するか調べる
    $db = s_get_db();
    $r = $db->query("SELECT * FROM groups WHERE group_id=$group_id");
    $g = $r->fetch();
    if (empty($g["group_id"])) {
        s_error("存在しないグループです。"); exit;
    }
    // 既に参加しているならば再度追加しない
    $stmt = $db->query("SELECT * FROM group_members WHERE group_id=$group_id AND user_id=$user_id");
    $row = $stmt->fetch();
    if (isset($row["user_id"])) {
        s_error("既に参加しています。"); eixt;
    }
    // 追加する
    $insert_query = "INSERT INTO group_members ".
        "(group_id,user_id,ctime) VALUES (?,?,?)";
    $stmt = $db->prepare($insert_query);
    $r = $stmt->execute(array($group_id, $user_id, time()));
    if ($r === false) {
        s_error("データベースの挿入エラー"); exit;
    }
    // メッセージ
    $name_h = htmlspecialchars($g["name"]);
    s_message(
        "<div>".
        "<p>グループ「{$name_h}」に参加しました。</p>".
        "<p><a href='group.php'>→グループ一覧を見る</a></p>".
        "<p><a href='todo.php?group_id=$group_id'>→TODOを見る</a></p>".
        "</div>",
        "グループに参加しました！"
    );
}

// グループからの脱退処理
function m_quit() {
    $user_id = s_get_user_id();
    $group_id = intval(s_trim_get("group_id"));
    $db = s_get_db();
    $delete_query = "DELETE FROM group_members WHERE group_id=? AND user_id=?";
    $stmt = $db->prepare($delete_query);
    $r = $stmt->execute(array($group_id, $user_id));
    if ($r === false || $r <= 0) {
        s_error("データベースのエラーで脱退できませんでした。"); exit;
    }
    s_message("<a href='group.php'>脱退しました。</a>", "グループからの脱退処理");
}

// グループ削除確認（確認用）★
function m_del_group() {
    $group_id = intval(s_trim_get("group_id"));
    if ($group_id <= 0) { s_error("グループの指定が必要です。"); exit; }
    $group = s_get_group_info($group_id);
    if (!$group) { s_error("存在しないグループが指定されました。"); exit; }
    $name_h = htmlspecialchars($group["name"]);
    $s = $_SERVER["SCRIPT_NAME"];
    s_message(
            "<p>本当にグループ「{$name_h}」を削除してもよろしいですか？</p>".
            "<ul><li><a href='$s?m=del_group2&group_id=$group_id'>削除します。</a></li>".
            "<li><a href='$s'>いいえ、削除しません。</a></li></ul>",
            "グループ削除確認"
    );
}

function m_del_group2() {
    $group_id = intval(s_trim_get("group_id"));
    if ($group_id <= 0) { s_error("グループの指定が必要です。"); exit; }
    $group = s_get_group_info($group_id);
    if (!$group) { s_error("存在しないグループが指定されました。"); exit; }
    // 削除実行
    $db = s_get_db();
    $db->beginTransaction();
    $r = $db->exec("DELETE FROM groups WHERE group_id=$group_id");
    if ($r === false || $r <= 0) { // 実行に失敗したとき
        $db->rollBack();
        s_error("グループの削除に失敗しました。"); exit;
    }
    // グループに属しているTODOも同時に削除
    $r = $db->exec("DELETE FROM todo_list WHERE group_id=$group_id");
    if ($r === false) { // 実行に失敗したとき (TODOが0の場合もあるので実行失敗のみチェック)
        $db->rollBack();
        s_error("グループの削除に失敗しました。"); exit;
    }
    // グループに属しているユーザーを強制脱退させる
    $r = $db->exec("DELETE FROM group_members WHERE group_id=$group_id");
    if ($r === false) {
        $db->rollBack();
        s_error("グループの削除に失敗しました。"); exit;
    }
    $db->commit();
    // 削除した旨を表示
    $name_h = htmlspecialchars($group["name"]);
    $s = $_SERVER["SCRIPT_NAME"];
    s_message(
            "<p><a href='$s'>グループ「{$name_h}」を削除しました。</s></p>",
            "グループの削除"
    );
}
