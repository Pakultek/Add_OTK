<?php
session_start();

// Настройки подключения к БД
$host = 'local'; // Хост
$db   = 'db_name'; // БД
$user = 'user'; // Логин
$pass = 'pass'; // Пароль
$charset = 'utf8mb4'; // Кодировка

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
);

// Обработка POST-запросов (Добавление или Удаление)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ⚡ ЛОГИКА УДАЛЕНИЯ
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($user_id > 0) {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                $sql = "UPDATE Users SET flag_del = 1 WHERE N_usera = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $user_id]);
                
                $_SESSION['flash_message'] = 'Пользователь успешно удален!';
                $_SESSION['flash_type'] = 'success';
                
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Ошибка БД при удалении: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'error';
            }
        }
    } 
    // ⚡ ЛОГИКА ДОБАВЛЕНИЯ
    else {
        $num_propusk = isset($_POST['Num_propusk']) ? trim($_POST['Num_propusk']) : '';
        $fio         = isset($_POST['FIO']) ? trim($_POST['FIO']) : '';
        $num_otk     = isset($_POST['Num_OTK']) ? trim($_POST['Num_OTK']) : '';
        $num_prop2   = isset($_POST['Num_prop2']) ? trim($_POST['Num_prop2']) : '';

        if ($num_propusk === '' || $fio === '' || $num_otk === '') {
            $_SESSION['flash_message'] = 'Ошибка: Пожалуйста, заполните все обязательные поля!';
            $_SESSION['flash_type'] = 'error';
        } else {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                $num_prop2_value = ($num_prop2 === '') ? 0 : $num_prop2;
                
                $sql = "INSERT INTO Users (Num_propusk, FIO, Num_OTK, Num_prop2) 
                        VALUES (:num_propusk, :fio, :num_otk, :num_prop2)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array(
                    'num_propusk' => $num_propusk,
                    'fio'         => $fio,
                    'num_otk'     => $num_otk,
                    'num_prop2'   => $num_prop2_value
                ));
                
                $_SESSION['flash_message'] = 'Пользователь успешно добавлен!';
                $_SESSION['flash_type'] = 'success';
                
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Ошибка базы данных: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'error';
            }
        }
    }
}

// Получение списка пользователей для отображения
$users = array();
$error = '';
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // ⚡ ДОБАВЛЕНО УСЛОВИЕ: исключаем записи, где flag_del = 1
    $sql = "SELECT N_usera, Num_propusk, FIO, Num_OTK, Num_prop2
            FROM Users
            WHERE Num_OTK != 0 AND Num_OTK IS NOT NULL 
              AND (flag_del = 0 OR flag_del IS NULL)
            ORDER BY FIO";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Ошибка базы данных: ' . $e->getMessage();
}

// Всплывающие сообщения
$flash_message = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 30px; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 1100px; margin: 0 auto; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        .section { margin-bottom: 40px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #555; font-size: 14px; }
        .required::after { content: " *"; color: red; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s; }
        input[type="text"]:focus { outline: none; border-color: #4CAF50; }
        button { width: 100%; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s; }
        button:hover { background-color: #45a049; }
        
        /* Стили для кнопки удаления */
        button.btn-delete { 
            width: auto; 
            padding: 6px 12px; 
            font-size: 13px; 
            background-color: #f44336; 
            font-weight: normal;
        }
        button.btn-delete:hover { background-color: #d32f2f; }

        .message { padding: 12px; margin-bottom: 20px; border-radius: 4px; text-align: center; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #4CAF50; color: white; font-weight: 600; }
        tr:hover { background-color: #f5f5f5; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .empty { text-align: center; padding: 30px; color: #777; font-style: italic; }
        .counter { text-align: right; color: #777; font-size: 14px; margin-top: 10px; }
        .divider { border: none; border-top: 2px solid #e0e0e0; margin: 40px 0; }
    </style>
</head>
<body>
<div class="container">
    <h2>Управление пользователями</h2>
    
    <div class="section">
        <h3>Добавить нового пользователя</h3>
        
        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_type); ?>">
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="Num_propusk" class="required">Номер пропуска:</label>
                <input type="text" id="Num_propusk" name="Num_propusk" required>
            </div>
            <div class="form-group">
                <label for="FIO" class="required">ФИО:</label>
                <input type="text" id="FIO" name="FIO" required>
            </div>
            <div class="form-group">
                <label for="Num_OTK" class="required">Номер ОТК:</label>
                <input type="text" id="Num_OTK" name="Num_OTK" required>
            </div>
            <div class="form-group">
                <label for="Num_prop2">Номер пропуска 2 <span style="color: #999; font-weight: normal;">(необязательно)</span>:</label>
                <input type="text" id="Num_prop2" name="Num_prop2" placeholder="Оставьте пустым, если не нужно">
            </div>
            <button type="submit">Добавить пользователя</button>
        </form>
    </div>
    
    <hr class="divider">
    
    <div class="section">
        <h3>Список пользователей</h3>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (empty($users)): ?>
            <div class="empty">Пользователи не найдены.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер пропуска</th>
                        <th>ФИО</th>
                        <th>Номер ОТК</th>
                        <th>Номер пропуска 2</th>
                        <th style="text-align: center;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['N_usera']); ?></td>
                            <td><?php echo htmlspecialchars($u['Num_propusk']); ?></td>
                            <td><?php echo htmlspecialchars($u['FIO']); ?></td>
                            <td><?php echo htmlspecialchars($u['Num_OTK']); ?></td>
                            <td><?php echo htmlspecialchars($u['Num_prop2']); ?></td>
                            <td style="text-align: center;">
                                <!-- Форма для удаления с подтверждением -->
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['N_usera']); ?>">
                                    <button type="submit" class="btn-delete">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="counter">Всего найдено: <strong><?php echo count($users); ?></strong></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
