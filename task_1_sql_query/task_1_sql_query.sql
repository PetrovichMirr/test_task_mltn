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


