/* シッカリスト : データベース定義ファイル */

/* ユーザーテーブル */
CREATE TABLE users (
  user_id    INTEGER PRIMARY KEY, /* ユーザーID */
  nickname   TEXT,    /* ユーザーのニックネーム */
  email      TEXT,    /* Eメールアドレス */
  password   TEXT,    /* パスワード(ハッシュ化したもの) */
  user_type  TEXT,    /* ユーザー権限(admin か normal) */
  agreement  INTEGER, /* ユーザーが利用可能な状態かどうか */
                      /*   未設定:0 */
                      /*   メールアドレスチェックが完了:1 */
                      /*   管理人による承認が完了: 3 */
  token      TEXT,    /* メール認証などに使うトークン */
  ctime      INTEGER  /* 登録日 */
);

/* グループテーブル */
CREATE TABLE groups (
  group_id INTEGER PRIMARY KEY, /* グループID */
  name     TEXT,   /* グループの名前 */
  memo     TEXT,   /* グループの説明 */
  maker_id INTEGER,/* グループを作成したユーザー */
  ctime    INTEGER /* 作成日 */
);

/* 所属グループテーブル
 * (誰がどのグループに属しているか記録する) */
CREATE TABLE group_members (
  group_member_id INTEGER PRIMARY KEY, /* 識別用ID */
  user_id  INTEGER, /* 誰が */
  group_id INTEGER, /* どのグループに属しているか */
  ctime    INTEGER  /* 所属日 */
);

/* UNIQUEインデックスの生成
 * user_idとgroup_idの組み合わせを二重に登録できないよう制約をつける */
CREATE UNIQUE INDEX group_members_unique
  ON group_members (user_id, group_id);

/* TODOアイテム */
CREATE TABLE todo_list (
  todo_id  INTEGER PRIMARY KEY, /* TODOのID */
  group_id INTEGER,/* TODOの属するグループID */
  user_id  INTEGER,/* TODOの作成者 */
  title    TEXT,   /* TODOのテキスト */
  memo     TEXT,   /* TODOの詳細 */
  status   INTEGER,/* TODOの状態(0:未処理, 1:完了) */
  rank     INTEGER,/* TODOの重要度ランク(1-5) */
  ctime    INTEGER,/* TODOの作成日 */
  mtime    INTEGER /* ステータスの変更日 */
);

/* 掲示板 */
CREATE TABLE bbs (
  bbs_id   INTEGER PRIMARY KEY,
  group_id INTEGER,
  user_id  INTEGER,
  title    TEXT,
  body     TEXT,
  ctime    INTEGER
);

