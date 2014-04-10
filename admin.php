<?php
// 共通設定を読み込む
include_once("common.inc.php");

// ログインしているのが管理人でなければならない★
if ($_SESSION["login"]["user_type"] != "admin") {
    show_html_header();
    echo "<h3>管理人でなければ操作することができません。</h3>";
    show_html_footer(); exit;
}
// 以下の操作は管理人のみが行えることとなる
$m = isset($_GET["m"]) ? $_GET["m"] : "";
switch ($m) {
    case "agree"  : m_agree(); break;
    default       : m_show(); break;
}

function m_agree() {
    $user_id = intval(s_trim_get("user_id"));
    $db = s_get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->execute(array($user_id));
    $row = $stmt->fetch();
    if (empty($row["user_id"])) {
        s_error("ユーザーが存在しません。"); exit;
    }
    if ($row["agreement"] == 0) {
        s_error("メール認証が終わっていないユーザーです。"); exit;
    }
    if ($row["agreement"] == 3) {
        s_error("既に認証済みのユーザーです。"); exit;
    }
    // 認証済みにセット
    $db->exec("UPDATE users SET agreement=3 WHERE user_id=$user_id");
    // メッセージを表示
    $nickname = $row["nickname"];
    $nickname_h = htmlspecialchars($row["nickname"]);
    show_html_header();
    echo "<h3>承認しました。</h3>";
    echo "<div>$nickname_h を承認しました。</div>";
    echo "<a href='admin.php'>→戻る</a>";
    show_html_footer();
    // 承認が完了したことをユーザーにメールで通知する
    $host = $_SERVER["HTTP_HOST"];
    $path = dirname($_SERVER["SCRIPT_NAME"]);
    $body = <<< __MAIL__
{$nickname}さん
お待たせしました。管理人による認証が完了しました。
以下よりログインしてください。

http://{$host}{$path}/login.php
__MAIL__;
    s_sendmail($row["email"], "認証完了のお知らせ", $body);
}

function m_show() {
    // 承認が必要なユーザーがいるか
    $agreelist = "";
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM users WHERE agreement=1");
    $rows = $stmt->fetchAll();
    if ($rows) {
        foreach ($rows as $row) {
            $user_id = $row["user_id"];
            $nickname_h = htmlspecialchars($row["nickname"]);
            $email_h = htmlspecialchars($row["email"]);
            $agreelist .= "<div>→<a href='admin.php?m=agree&user_id=$user_id'>";
            $agreelist .= "{$nickname_h}({$email_h})を承認する</a></div>";
        }
    }
    if ($agreelist == "") {
        $agreelist = "現在承認待ちのユーザーはいません。";
    }
    // ユーザー一覧のリストを作る
    $userlist = "";
    $q = "SELECT * FROM users";
    foreach ($db->query($q) as $user) {
        $nickname = htmlspecialchars($user["nickname"]);
        $email    = htmlspecialchars($user["email"]);
        $user_type = $user["user_type"];
        $agreement = $user["agreement"];
        $userlist .= "<tr><td>$nickname</td><td>$email</td><td>$user_type</td><td>$agreement</td></tr>";
    }

    //
    show_html_header();
    echo <<< __BODY__
<h3>管理画面</h3>
<h4>承認待ちユーザー</h4>
<p>$agreelist</p>
<h4>ユーザー一覧</h4>
<table border="1" cellpadding="8">
<tr><td>ニックネーム</td><td>メール</td><td>権限</td><td>認証状態</td></tr>
$userlist
</table>
__BODY__;
    show_html_footer();
}


