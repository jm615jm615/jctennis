<?php
// 共通設定を読み込む
include_once("common.inc.php");

$m = isset($_GET["m"]) ? $_GET["m"] : "";

// 管理ユーザーがいるかチェック
check_admin_user();
// モードごとに処理を分岐する
switch ($m) {
    case "try_login"    : try_login(); break;
    case "logout"       : m_logout(); break;
    case "signup"       : m_signup(); break;
    case "try_signup"   : m_try_signup(); break;
    //case "emailcheck"   : m_emailcheck(); break;
    default:
        show_login_form($_GET);
        break;
}

function check_admin_user() {
    // 管理者のユーザーがいるか調べる
    $admin_user = get_admin_users();
    if (!$admin_user) { // 管理者のユーザーがいない時、管理ユーザーを作成
        $m = isset($_GET["m"]) ? $_GET["m"] : "";
        if ($m == "try_signup") { m_try_signup_admin_user(); }
        else { show_user_signup_form("管理ユーザー作成", "admin", array()); }
        exit;
    }
}

// 管理者ユーザーの一覧を得る
function get_admin_users() {
    //管理ユーザーがいるか調べる
    $db = s_get_db();
    $stmt = $db->query("SELECT * FROM users WHERE user_type='admin'");
    return $stmt->fetchAll();
}

// ユーザー新規登録作成フォームを表示する
function show_user_signup_form($title, $user_type, $default, $errors = array()) {
    $self = $_SERVER["SCRIPT_NAME"];
    // 初期値のチェック
    $nickname  = isset($default["nickname"]) ? $default["nickname"]: "";
    $email = isset($default["email"]) ? $default["email"] : "";
    $nickname_h = htmlspecialchars($nickname, ENT_QUOTES);
    $email_h = htmlspecialchars($email, ENT_QUOTES);
    // 前回の入力でエラーがあった時の処理
    $error_str = implode($errors, "");
    show_html_header();
    echo <<< __FORM__
        <div id="user_form"><form action="$self" method="get">
          <div data-role="content" align="center">
            <h3>$title</h3>
            <input type="hidden" name="m" value="try_signup" />
            <input type="hidden" name="user_type" value="$user_type" />

            <input type="text" name="nickname" id="nickname" value="$nickname_h" placeholder="ニックネームを入力"/>
            <input type="text" name="email" id="email" value="$email_h" placeholder="ログインIDを入力（半角英数）"/>    
            <input type="password" name="password" id="password" value="" placeholder="パスワードを入力" />
            <input type="password" name="password2" id="password2" value="" placeholder="パスワードを再入力" />
            <br>
            <button type="submit" data-theme="b">作成</button>
            <div style='color:red;padding:8px;'>$error_str</div>
          </div>
        </form></div>
__FORM__;
    show_html_footer();
}

// 管理ユーザーの作成
function m_try_signup_admin_user() {
    //空
}

// サインアップフォームをチェックしてエラーがあれば戻り値に配列を返す
function check_signup_form() {
    // フォームのパラメータを取得
    $nickname  = s_trim_get("nickname");
    $email     = s_trim_get("email");
    $password  = s_trim_get("password");
    $password2 = s_trim_get("password2");
    //
    // ニックネームの入力エラーをチェック
    $errors = array();
    if ($nickname == "") {
        $errors[] = "<p>ニックネームが空です。</p>";
    }
    
    // ログインIDの入力エラーをチェック
    if ($email == "") {
        $errors[] = "<p>メールが空です。</p>";
    } else if (!is_alnum($email)){
        $errors[] = "<p>ログインIDに半角英数以外の文字が含まれています。</p>";
    }
    
    // パスワードの入力エラーをチェック
    if ($password == "") {
        $errors[] = "<p>パスワードが空です。</p>";
    } else if ($password != $password2) {
        $errors[] = "<p>再入力したパスワードが一致しません。</p>";
    } else if (!is_alnum($password)){
        $errors[] = "<p>パスワード半角英数以外の文字が含まれています。</p>";
    }
    
    //エラーチェックの結果を返す
    return $errors;
}

// 新規ユーザー登録
function m_signup() {
    show_user_signup_form("新規ユーザー登録", "normal", array());
}

// ユーザー登録チェック
function m_try_signup() {
    // 入力チェック
    $errors = check_signup_form();
    // エラーがあれば再入力
    if (count($errors) > 0) {
        show_user_signup_form("新規ユーザー登録", "normal", $_GET, $errors);
        exit;
    }
    $nickname = s_trim_get("nickname");
    $email = s_trim_get("email");
    $db = s_get_db();

    // 既存ユーザーのニックネームが重複しないかチェック
    if ($nickname != "") {
        $stmt = $db->prepare("SELECT * FROM users WHERE nickname=? LIMIT 1");
        $stmt->execute(array($nickname));
        $row = $stmt->fetch();
        if (isset($row["nickname"])) {
            $errors[] = "<p>ニックネームが既に使用中です。</p>";
        }
    }
    
    // 既存ユーザーのメールアドレスが重複しないかチェック
    if ($email != "") {
        $stmt = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute(array($email));
        $row = $stmt->fetch();
        if (isset($row["email"])) {
            $errors[] = "<p>メールアドレスが既に使用中です。</p>";
        }
    }
    // エラーがあれば再入力
    if (count($errors) > 0) {
        show_user_signup_form("新規ユーザー登録", "normal", $_GET, $errors);
        exit;
    }
    // 登録する
    $insert_query = <<< __SQL__
INSERT INTO users (nickname,email,password,user_type,agreement,token,ctime)
            VALUES(?,?,?,"normal",0,?,?);
__SQL__;
    // メールアドレスを確認するためのランダムなトークンを生成
    // トークンなんて別にいらないけど。。。（1/9）
    $token = sprintf("%04d", rand(0, 9999)); 
    $stmt = $db->prepare($insert_query);
    $stmt->execute(array(
        $nickname, $email, s_trim_get("password"),
        $token, time()
    ));
    $user_id = $db->lastInsertId(); // 今挿入したuser_idを取得する★

    //登録完了画面の表示
    show_html_header();
echo <<< __FORM__
      <div data-role="content"  valign="middle">
        <div id="complete_form">
          <div align="center">
           <br>
           <br>
           <br>
           <br>
           <br>
           <h3>登録が完了しました。</h3>
          </div>
          <div data-role="content">
            <a href="login.php" data-role="button" id="loginBtn" data-theme="b"> ログイン画面へ</a>
          </div>
        </form></div>
      </div>  
__FORM__;

    show_html_footer();
}


// ログインフォームを表示する
function show_login_form($default = array(), $errors = array()) {
    //現在のスクリプトパスを取得（ログインボタン押したときの遷移先として指定しておく）
    $self = $_SERVER["SCRIPT_NAME"];
    //エラー配列を一つの文字列に変換
    $error_str = implode("", $errors);
    //emailをhtmlで表示できるようにする
    $email_h = htmlspecialchars(s_trim_get("email"), ENT_QUOTES);
    //emailをhtmlで表示できるようにする
    $back = htmlspecialchars(s_trim_get("back"));
    show_html_header();
    echo <<< __FORM__
      <div data-role="content">
        <div id="user_form">
          <div align="center">
           <img src="./image/JCTennis_logo_4.png"/>
          </div>
          <div data-role="content" align="center">
            <form action="$self" method="get">
            <input type="hidden" name="m" value="try_login" />
            <input type="hidden" name="back" value="$back" />
            
            <input type="text" name="email" id="email" value="$email_h" placeholder="ログインIDを入力"/>
            <input type="password" name="password" id="password" value="" placeholder="パスワードを入力" />
            <br>
            <button type="submit" data-theme="b">ログイン</button>
            <div style='color:red;padding:8px;'>$error_str</div>
            <div><a href='login.php?m=signup'>→ユーザー新規登録</a></div>
          </div>
        </form></div>
      </div>  
__FORM__;
    show_html_footer();
}

// ログインフォームで送信したパラメータを調べてログイン処理を行う
function try_login() {
    $email = s_trim_get("email");
    $password = s_trim_get("password");
    //$password_hash = s_password_hash($password);
    $back = s_trim_get("back");
    // データベースと照合してログインするか判定する
    $db = s_get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND password=?");
    $stmt->execute(array($email, $password));
    $row = $stmt->fetch();
    if (empty($row["nickname"])) { // 一致結果がなければ再度入力
        show_login_form($_GET, array("メールかパスワードが正しくありません。"));
        exit;
    }

    // ログインを記録
    $_SESSION["login"] = array(
        "user_id" => $row["user_id"],
        "nickname" => $row["nickname"],
        "email" => $row["email"],
        "user_type" => $row["user_type"],
        "login_time" => time()
    );
    if ($back == "") $back = "index.php";
    // ログインさせる
    header("location: $back"); exit;
}

//ログアウト
function m_logout() {
    unset($_SESSION["login"]); // ログイン情報を削除
    header("location: login.php");// ログインページへジャンプ
}