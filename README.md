# Тестовое задание (MLTN Company)

## Задача, требования


Тестовое задание Laravel PHP Developer

Необходимо решить 2 задания, результат залить в публичный репозиторий и в readme файле указать решение первого задания в виде запроса и пример вашей тестовой базы, на которой вы протестировали запрос, а так-же инструкцию и описание по второму заданию.

Зафиксируйте время, дату начала и конца выполнения тестового задания. Напишите это время в документе.

### Задание 1

**MySql**



Есть три таблицы:

**users**

| id | first_name | last_name | age |
|----|------------|-----------|-----|
| 1  | Ivan       | Ivanov    | 18  |
| 2  | Marina     | Ivanova   | 14  |
| 3  | Petr       | Sidorov   | 6   |
| 4  | Irina      | Sidorova  | 19  |
| 5  | Egor       | Lesov     | 11  |

**books**

| id | name                         | author              |
|----|------------------------------|---------------------|
| 1  | Romeo and Juliet             | William Shakespeare |
| 2  | Hamlet                       | William Shakespeare |
| 3  | Othello                      | William Shakespeare |
| 4  | War and Peace                | Leo Tolstoy         |
| 5  | Anna Karenina                | Leo Tolstoy         |
| 6  | The Prisoner in the Caucasus | Leo Tolstoy         |
| 7  | Crime and Punishment         | Leo Tolstoy   |
| 8  | The Idiot                    | Fyodor Dostoevsky   |

**user_books**

| id | user_id | book_id |
|----|------------|------|
| 1  | 1          | 2    |
| 2  | 1          | 3    |
| 3  | 1          | 5    |
| 3  | 2          | 2    |
| 3  | 2          | 3    |
| 3  | 3          | 1    |
| 3  | 3          | 6    |
| 3  | 4          | 4    |
| 3  | 4          | 5    |
| 3  | 5          | 7    |
| 3  | 5          | 8    |


**Необходимо написать SQL запрос, который найдет и выведет всех читателей, возраста от 7 и до 17 лет, которые взяли только 2 книги и все книги одного и того же автора.**

**Формат вывода:**

| ID | Name (first_name  last_name) | Author      | Books (Book 1, Book 2, ...) |
|----|------------------------------|-------------|-----------------------------|
| 1  | Ivan Ivanov                  | Leo Tolstoy | Book 1, Book 2, Book 3      |


### Задание 2

**PHP / Laravel**

Для выполнения этого задания используйте:

* Laravel 7.3
* PHP 7.3.14
* 10.4.12-MariaDB

Необходимо реализовать на  фреймворке LARAVEL RESTful API для работы с курсами обмена валют для BTC. В качестве источника курсов будем использовать: https://blockchain.info/ticker и будем работать только с этим методом.

Данное API будет доступно только после авторизации. Все методы будут приватными.
Написать middleware, который закрывает методы к апи и проверяет токен, сам токен для экономии времени можно статично записать в файл .env 

Для авторизации будет использоваться фиксированный токен (64 символа включающих в себя a-z A-Z 0-9 а так-же символы - и _ ), передавать его будем в заголовках запросов. Тип Authorization: Bearer.

**Важно:**

Весь код, которые производит какие-либо вычисления или операции с базой должен быть написать в сервисах. Все сервисы инициализировать через DI в контроллерах в методе __construct, либо в в экшене контроллера.

Для фильтрации, построения ответов от апи использовать библиотеку
https://github.com/spatie/laravel-query-builder

Все апи должны возвращать ресурсы или коллекции ресурсов в случае успеха

https://laravel.com/docs/8.x/eloquent-resources


**Формат ответа API: JSON (все ответы при любых сценариях JSON)**

Все значения курса обмена должны считаться учитывая нашу комиссию = 2%

Примеры запросов
GET `http://base-api.url/api/v1/rates?filter[currency]=USD` // фильтр по валюте
 
GET `http://base-api.url/api/v1/rates` // все курсы

**API должен иметь 2 метода:**

**1) rates: Получение всех курсов с учетом комиссии = 2% (GET запрос) в формате:**

```
{
	“USD” : <rate>,
...
}
```

В случае ошибки связанной с токеном: код ответа должен быть 403, в случае успеха код ответа 200 + данные.

Сортировка от меньшего курса к большему курсу.

В качестве параметров может передаваться интересующая валюта, в формате USD,RUB,EUR и тп В этом случае, отдаем указанные в качестве параметра `currency` значения.

**2) POST http://base-api.url/api/v1/convert**

Запрос на конвертацию валют, результат запроса сохранять в базу


Параметры:

```
currency_from: USD // исходная валюта
currency_to: BTC // валюта в которую конвертируем
value: 1.00 // количество единиц исходной валюты
```

```
convert: Запрос на обмен валюты c учетом комиссии = 2%. POST запрос с параметрами:
currency_from: USD
currency_to: BTC
value: 1.00
```

или в обратную сторону

```
currency_from: BTC
currency_to: USD
value: 1.00
```

В случае успешного запроса, отдаем:

```
{
	“currency_from” : BTC,
	“currency_to” : USD,
	“value”: 1.00,
	“converted_value”: 1.00,
	“rate” : 1.00,
	“created_at”: TIMESTAMP

}
```

В случае ошибки:

```
{
	“status”: “error”,
	“code”: 403,
	“message”: “Invalid token”
}
```

**Важно**, минимальный обмен равен 0,01 валюты from
Например: USD = 0.01 меняется на 0.0000005556 (считаем до 10 знаков)
Если идет обмен из BTC в USD - округляем до 0.01


## Решение

**Общее время выполнения тестового задания: около 3 рабочих дней.**

### Задание 1

**Ответ:**

```
SELECT `user_id` AS `ID`, `user_name` AS `Name`, `author` AS `Author`, `books_names` AS `Books` FROM

    ( SELECT *,
    @new_user := IF((@user_id IS NULL) OR (@user_id != `user_id`), 1, 0),
    @user_id := `user_id`,
    @books := IF(@new_user, `book_name`, CONCAT_WS(', ', @books, `book_name`)) AS `books_names`,
    @current_books_count := IF(@new_user, 1, @current_books_count + 1) AS `current_books_count` FROM

        ( SELECT `found_users`.`user_id` AS `user_id`, `found_users`.`user_name` AS `user_name`,
        `books`.`author` AS `author`, `books`.`name` AS `book_name` FROM

            ( SELECT `users`.`id` AS `user_id`,
                CONCAT_WS(' ', `users`.`first_name`, `users`.`last_name`) AS `user_name` FROM `user_books`
                JOIN `users` ON `user_books`.`user_id` = `users`.`id`
                JOIN `books` ON `user_books`.`book_id` = `books`.`id`
                WHERE (7 <= `users`.`age`) AND (`users`.`age` <= 17)
                GROUP BY `users`.`id`
                HAVING (COUNT(DISTINCT `user_books`.`book_id`)=2) AND (COUNT(DISTINCT `books`.`author`)=1)
            ) AS `found_users`
            JOIN `user_books` ON `found_users`.`user_id` = `user_books`.`user_id`
            JOIN `books` ON `user_books`.`book_id` = `books`.`id` ORDER BY `user_id`

        ) AS `found_users_books`

    ) AS `found_users_books_names` WHERE `current_books_count` = 2
```

В репозитории, в папке `task_1_sql_query` размещены файлы:

```
users.sql - содержимое таблицы users
books.sql - содержимое таблицы books
user_books.sql - содержимое таблицы user_books
task_1_sql_query.sql - решение задачи
```

### Задание 2

Решение: в папке `crypto_exchange` размещено приложение Laravel, разработанное на основании требований задания № 2.
Код приложения имеет подробные комментарии.

Для быстрого развёртывания:

Скопируйте себе папку с приложением;

* Создайте файл с переменными окружения `.env` и скопируйте в него содержимое файла `.env.example`. После этого укажите в файле `.env` свои значения, в частности, данные для подключения к базе данных и токен авторизации;
* Перейдите в терминале в директорию с приложением и запустите установку пакетов: `composer install`, `npm install`;
* Создайте таблицы в базе данных, запустив миграции (в терминале, из корневой директории приложения): `php artisan migrate`.

Для быстрого развёртывания базы дынных MariaDB можно воспользоваться образом docker (https://hub.docker.com/_/mariadb). Например, так:

```
docker pull mariadb:10.4.18-focal

sudo docker run -d --restart=unless-stopped -p 8306:3306  --name mariadb-10.4.18-focal -e MYSQL_ROOT_PASSWORD=YOUR_ROOT_PASSWORD -e MYSQL_DATABASE=test_task_mltn_crypto_exchange -e MYSQL_USER=test_task_mltn_crypto_exchange -e MYSQL_PASSWORD=YOUR_MYSQL_PASSWORD mariadb:10.4.18-focal --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
```

**где:**

`YOUR_ROOT_PASSWORD` - ваш пароль для пользователя БД root,

имя БД, пользователя и пароль:

```
MYSQL_DATABASE=test_task_mltn_crypto_exchange,

MYSQL_USER=test_task_mltn_crypto_exchange,

MYSQL_PASSWORD=YOUR_MYSQL_PASSWORD
```

`-p 8306:3306` - проброс портов в docker, настройка соединения с БД в Laravel при этом может быть примерно такой:

```
# Соединение с MariaDB в контейнере Docker
DB_CONNECTION=mysql
DB_HOST=172.17.0.1
DB_PORT=8306
DB_DATABASE=test_task_mltn_crypto_exchange
DB_USERNAME=test_task_mltn_crypto_exchange
DB_PASSWORD=YOUR_MYSQL_PASSWORD
```

Драйвер соединения для MariaDB в Laravel такой же, как для MySQL (это настройки по умолчанию, после установки не нужно дополнительных настроек).

Для возможности проведения тестов в браузере, без отправки заголовков с токеном авторизации можно отключить проверку авторизации, указав в .env: `AUTH_CHECK=false`

**Для тестирования API с отправкой заголовков авторизации можно использовать консольную утилиту Curl:**

**Метод GET**

```
curl -v -H 'Authorization: Bearer YOUR-TOKEN' http:/app-url/api/v1/rates
```

где:

`YOUR-TOKEN` - токен, указанный в .env

`http:/app-url/api/v1/rates` - URL- адрес метода API приложения

**Метод POST**

```
curl -v -H 'Authorization: Bearer YOUR-TOKEN' -d 'currency_from=BTC&currency_to=EUR&value=1' http:/app-url/api/v1/convert
```

где:

`YOUR-TOKEN` - токен, указанный в .env

`currency_from=BTC&currency_to=EUR&value=1` - параметры запроса

`http:/app-url/api/v1/convert` - URL- адрес метода API приложения