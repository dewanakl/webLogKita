<?php

require_once 'config.php';
require_once 'DataBase.php';

$db = new DataBase;
$dt = new DateTime("now", new DateTimeZone('Asia/Jakarta'));

if (!session_id()) {
    session_start();
}

$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

header("Content-Type: application/json");

if ($_GET['act'] == 'register') {
    if (empty($_POST['nama']) || empty($_POST['username']) || empty($_POST['password'])) {
        echo json_encode([
            'status' => false
        ]);
        exit;
    }

    $db->query("INSERT INTO users(nama, username, password) VALUES(:nama, :username, :pass)");
    $db->bind(':nama', $_POST['nama']);
    $db->bind(':username', $_POST['username']);
    $db->bind(':pass', $_POST['password']);
    $status = $db->execute();

    echo json_encode([
        'status' => $status
    ]);
    exit;
}

if ($_GET['act'] == 'login') {
    $db->query("SELECT id FROM users WHERE username = :username AND password = :pass");
    $db->bind('username', $_POST['username']);
    $db->bind('pass', $_POST['password']);
    $status = $db->get() ?? false;

    if ($status) {
        $_SESSION['iduser'] = $status['id'];
    }

    echo json_encode([
        'status' => $status
    ]);
    exit;
}

if ($_GET['act'] == 'all') {
    $title = (!empty($_GET['title'])) ? $_GET['title'] : null;
    $order = ($_GET['order'] === 'a') ? 'DESC' : 'ASC';
    $init = intval($_GET['init']);
    $end = intval($_GET['end']);

    $db->query("SELECT posts.id, posts.nama, posts.tanggal, posts.image, users.nama AS author FROM posts JOIN users ON posts.users_id = users.id WHERE posts.nama LIKE :nama ORDER BY posts.tanggal $order LIMIT $end OFFSET $init");
    $db->bind(":nama", "%$title%");

    echo json_encode($db->getAll());
    exit;
}

if ($_GET['act'] == 'one') {
    $db->query("SELECT posts.post, posts.nama, posts.tanggal, posts.image, users.nama AS author FROM posts JOIN users ON posts.users_id = users.id WHERE posts.id = :id");
    $db->bind(":id", intval($_GET['id']));

    echo json_encode($db->get());
    exit;
}

if (empty($_SESSION['iduser'])) {
    echo json_encode([
        'status' => false
    ]);
    exit;
}

if ($_GET['act'] == 'user') {
    $db->query("SELECT nama FROM users WHERE id = :id");
    $db->bind('id', $_SESSION['iduser']);
    $status = $db->get();
    echo json_encode([
        'status' => $status['nama'] ?? false
    ]);
}

if ($_GET['act'] == 'logout') {
    unset($_SESSION);
    session_destroy();
    echo json_encode([
        'status' => true
    ]);
}

if ($_GET['act'] == 'add') {
    if ($_FILES['image']) {
        $img = $_FILES['image']['name'];
        $tmp = $_FILES['image']['tmp_name'];
        $path = 'uploads/' . uniqid() . '.' . pathinfo($img, PATHINFO_EXTENSION);
        move_uploaded_file($tmp, $path);
    }
    $db->query("INSERT INTO posts(nama, post, tanggal, image, users_id) VALUES(:nama, :isi, :tgl, :image, :id)");
    $db->bind('nama', $_POST['judul'] ?? '');
    $db->bind('isi', $_POST['isi'] ?? '');
    $db->bind('id', $_SESSION['iduser']);
    $db->bind('tgl', $dt->format("Y-m-d H:i:s"));
    $db->bind('image', $path);
    $status = $db->execute();
    echo json_encode([
        'status' => $status
    ]);
}

if ($_GET['act'] == 'delete') {
    $db->query("SELECT image FROM posts WHERE id = :id");
    $db->bind('id', $_POST['idpost']);
    $status = $db->get()['image'];
    unlink($status);

    $db->query("DELETE FROM posts WHERE id = :id");
    $db->bind('id', $_POST['idpost']);
    $status = $db->execute();
    echo json_encode([
        'status' => $status
    ]);
}

if ($_GET['act'] == 'edit') {
    $db->query("SELECT id, nama, post, image FROM posts WHERE id = :id");
    $db->bind('id', $_GET['id']);
    $status = $db->get();
    echo json_encode([
        'status' => $status
    ]);
}

if ($_GET['act'] == 'update') {
    $db->query("SELECT image FROM posts WHERE id = :id");
    $db->bind('id', $_POST['id']);
    $status = $db->get()['image'];

    if ($_FILES['image']) {
        $img = $_FILES['image']['name'];
        $tmp = $_FILES['image']['tmp_name'];
        $path = 'uploads/' . uniqid() . '.' . pathinfo($img, PATHINFO_EXTENSION);
        unlink($status);
        move_uploaded_file($tmp, $path);
    } else {
        $path = $status;
    }

    $db->query("UPDATE posts SET nama = :nama, post = :isi, image = :img WHERE id = :id");
    $db->bind('nama', $_POST['judul'] ?? '');
    $db->bind('isi', $_POST['isi'] ?? '');
    $db->bind('img', $path);
    $db->bind('id', $_POST['id']);
    $status = $db->execute();
    echo json_encode([
        'status' => $status
    ]);
}

if ($_GET['act'] == 'getbyuser') {
    $db->query("SELECT id, nama, post, tanggal FROM posts WHERE users_id = :id");
    $db->bind('id', $_SESSION['iduser']);
    $status = $db->getAll();
    echo json_encode($status);
}
