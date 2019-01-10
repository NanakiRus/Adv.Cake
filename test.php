<?php
// Все задачи с использованиеь SQL тестировались на MariaDB 10.4
// 1 Задача
// Может ли когда-либо выполниться условие $x == 1 && $x == 2. Если может, то когда, если нет, то почему?
$x = true;
\assert(1 == $x && 2 == $x);
#################################

// 2 Задача
// Представьте что есть автомат для выдачи денег, с минимальной монетой в 50 копеек. Напишите функцию m_round(), для расчета сумму выдачи, минимизирующую суммарную ошибку округления. На вход функции дается вещественное число. На выходе функция отдает сумму, округленную до 50 копеек.
function m_round(float $x)
{
    return round($x * 2, 0) / 2;
}
#################################

// 3 Задача
// Вам досталась реляционная база данных со схемой, как на картинке. Вас попросили найти преподавателей, к которым хотя бы на один курс ходит мало студентов. Мало — меньше 50% от среднего размера курса. Напишите SQL, с помощью которого будете определять таких преподавателей. Укажите, какой диалект используете.

'SELECT *
FROM (SELECT name, professor_id, courses_students.course_id, count(student_id) as count_students
      FROM professors
             LEFT JOIN professors_courses ON professors.id = professors_courses.professor_id
             LEFT JOIN courses_students ON professors_courses.course_id = courses_students.course_id
      GROUP BY professor_id, courses_students.course_id) students_summ
where count_students < (SELECT avg(students_avg.c_s) / 2
                        FROM (SELECT count(student_id) as c_s FROM courses_students group by course_id) students_avg)'
#################################

// 4 Задача
//В прошлом году к нам на собеседование пришло много кандидатов, каждому мы задавали каверзные вопросы на знание тонких мест PHP, умение работать с базами данных, понимание основ ООП и различных фреймворков. По первому пункту мы получили удовлетворительный ответ от 90 претендентов, по второму — от 50, хорошо знают ООП, как оказалось, всего 40, а с фрейморками дела обстоит еще хуже — нас устроил ответ всего 20. Самое странное, что никто не смог дать ответ более чем на два вопроса. Сколько людей мы прособеседовали в поисках хорошего программиста? (Обоснуйте ответ)

// 2x+y=200
// Данная задача решается путём подстановки и по сути не имеет единстванно верного результата.
#################################

// 5 Задача
// Есть таблица Session. 
// id, user_id, login_time, logout_time
// В ней хранится id пользователя, время входа и время выхода из системы (может быть null, если визит еще не завершен). Определить в какое время за отдельно взятые сутки в системе находилось одновременно максимальное число пользователей. 
// Ответ: Скрипт на языке PHP, на вход принимает дату. Будут учитываться красота и оптимальность  SQL запроса и алгоритма расчета, а также полнота выдаваемого скриптом ответа.

function getMaxUsersActivity($date) {
            $query = 'SELECT * FROM `sessions` WHERE DATE(`login_time`) = :date ORDER BY login_time ASC';
            $db = new DB();
            $users = $db->query($query, [':date' => $date]);

            $times = [];
            $result = [];
            $i = 0;
            foreach ($users as $key => $user) {
                $login_time = \DateTime::createFromFormat('Y-m-d H:i:s', $user['login_time']);
                $logout_time = \DateTime::createFromFormat('Y-m-d H:i:s', $user['logout_time']);

                $times[$key] = [
                    'login' => $login_time->getTimestamp(),
                    'logout' => $logout_time->getTimestamp()
                ];

                if (isset($times[$key - 1])) {
                    $prev = $times[$key - 1];
                    if ($prev['logout'] < $times[$key]['login'] && 0 < $prev['logout']) {
                        $i++;
                    }
                    if (($prev['login'] <= $times[$key]['logout'] && $prev['logout'] >= $times[$key]['login']) ||
                        (0 > $prev['logout'] && $prev['login'] <= $times[$key]['login']) ||
                        (0 > $times[$key]['logout'] && $prev['logout'] >= $times[$key]['login'])) {

                        $result[$i][] = $users[$key - 1];
                        $result[$i][] = $user;
                    }
                }
            }

            function pluck($items, $key)
            {
                return array_map(function($item) use ($key) {
                    return \is_object($item) ? $item->$key : $item[$key];
                }, $items);
            }

            $result = \array_map(function ($items) {
                $items = \array_unique($items, \SORT_REGULAR);
                $items += [
                    'count' => \count($items),
                    'period' => \min(pluck($items, 'login_time')) . '/' . \max(pluck($items, 'logout_time'))
                ];
                return $items;
            }, $result);

            \usort($result, function ($a, $b) {
                return ($a['count'] < $b['count']);
            });

            return \array_shift($result);
        }
#################################

//6 задача
// Есть 3 таблицы (Желательно предположить, что их может быть и 4 и больше без принципиального изменения структуры итогового запроса)
// impression (id, time, project_id, ,,,) 
// click (id, time, project_id, ,,,) 
// order (id, time, project_id, ,,,)
// В каждой таблице могут быть десятки миллионов строчек
// Нужно написать эффективный запрос, который по заданному диапазону дат выводит таблицу. 
// project_id, imps_count, clicks_count, orders_count
// В которой перечислены все project_id, по которым есть хотя бы какая то активность за заданный период и количество записей из каждой из таблиц за тот же период. 
// Предположить какие индексы понадобятся.  Указать какой диалект используется.
// Считать, что project_id может быть много разных и заранее не известно каких, но их не много относительно общего количества строк в каждой таблице. Таблицы никак между собой не связаны и наличие к примеру клика, или заказа с определенным project_id не предполагает наличие показа. Могут быть дни где есть только показы, или только клики, или только заказы.  

// Дополнительно вывести 2 колонки CR и CTR
// CR - отношение кол. ва заказов к количеству кликов
// CTR - отношения кол-ва кликов к количеству показов

'SELECT project_id,
       imps_count,
       clicks_count,
       orders_count,
       clicks_count / orders_count * 100 AS CR,
       clicks_count / imps_count * 100   AS CTR
FROM (SELECT uniq_id.project_id,
             COUNT(impression.id) AS imps_count,
             COUNT(click.id)      AS clicks_count,
             COUNT(`order`.id)    AS orders_count
      FROM (SELECT DISTINCT project_id FROM `order`
            UNION 
            SELECT DISTINCT project_id FROM click
            UNION
            SELECT DISTINCT project_id FROM impression) uniq_id
             LEFT JOIN impression ON impression.project_id = uniq_id.project_id AND
                                     impression.time BETWEEN '2018-12-14 16:00:03' AND '2018-12-14 16:50:03'
             LEFT JOIN click ON click.project_id = uniq_id.project_id AND
                                click.time BETWEEN '2018-12-14 16:00:03' AND '2018-12-14 16:50:03'
             LEFT JOIN `order` ON `order`.project_id = uniq_id.project_id AND
                                  `order`.time BETWEEN '2018-12-14 16:00:03' AND '2018-12-14 16:50:03'
      ORDER BY uniq_id.project_id) counts'
