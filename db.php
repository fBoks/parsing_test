<?php

$conn = sqlsrv_connect('DESKTOP-23EKPKB\SQLEXPRESS', ["Database" => "Tenders"]);

if ($conn == false) {
    print("Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error());
} else {
    print("Соединение установлено успешно");
}
