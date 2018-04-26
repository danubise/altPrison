<?php
/**
 * Created by vladlexx.
 * User: vladlexx
 * Date: 19.02.15
 * Time: 18:40
 * Version: 4.0
 * Update: 02.06.2015 11:00
 *
 *
 * Библиотека для упрощённой работы с базой данных MySQL используя драйвер mysqli
 *
 * Набор методов:
 * query - дополнение к стандартному методу query с логированием запросов
 * select - структурированное извелечение выбранных строк из одной или несколько таблиц
 * update - обновление столбцов в соответствии с их новыми значениями в строках существующей таблицы
 * insert - добавление новых строки в существующую таблицу
 * multi_insert - добавление новых строк в существующую таблицу
 * insert_queue - открытие очереди на добавление полей в mysql. Аналог метода multi_insert
 * insert_queue_add - добавление полей к очереди с заданным ключом
 * insert_queue_send - перенос очереди в mysql insert запрос и выполение его
 * insert_queue_status - проверка очереди на удачную конфигурацию
 * delete - удаление строки из таблицы
 *
 * Набор полей:
 * (public) query - поле, содержащие информацию по текущим запросам к базе данных
 *          query->count - количество запросов, выполняемых в пределе скрипта, к базе данных
 *          query->last - последний запрос, выполненный в пределе скрипта, к базе данных
 *          query->history - история запросов, выполненных в пределе скрипта, к базе данных
 * (public) version - версия с конфигурацией запущенной библиотеки
 * (public) history_limit - лимит истории запросов, выполненных в пределе скрипта, к базе данных
 *
 * (private) insert_queue_list - лист из очередей
 */

class db extends mysqli {

    public $query;
    public $version = '4.0';
    public $history_limit = 50;

    private $DEBUG;

    /**
     * Сбор, проверка входящих данных при объявлении класса и подключение к базе данных MySQL.
     * При некорректных входящих данных выводит соответствующую ошибку.
     * @param string|array $host string: хост базы данных | array: полная конфигурация в ассоативном массиве
     * @param string $user пользователь базы данных
     * @param string $pass пароль пользователя базы данных
     * @param string $db имя базы данных
     * @param bool $debug флаг режима дебага
     */
     public function version(){
         return "This is mysqli wraper";
     }

    public function __construct($host = '', $user = '', $pass='', $db='', $debug = false) {
        if(is_array($host)) {
            $config = $host;
            $host = $config['host'];
            $user = $config['user'];
            $pass = $config['password'];
            $db = $config['database'];
            $debug = (empty($config['debug'])?false:$config['debug']);
        }
        $this->DEBUG = ($debug?true:false);
        if(!empty($host) and !empty($user)) {
            parent::__construct($host, $user, $pass, $db);
            if (mysqli_connect_error()) {
                $msg = 'Ошибка подключения: '.$this->errnoText(mysqli_connect_errno()).' (host: '.$host.'; user: '.$user.'; db: '.$db.')';
                echo "\n<br>".$msg."<br>\n";
            }
            $this->query = new stdClass();
            $this->query->history = array();
            $this->insert_queue_list = array();
            $this->query->count = 0;
        } else {
            $msg = 'Ошибка запуска класса: не все параметры заданны.';
            echo "\n<br>".$msg."<br>\n";
        }
    }

    /**
     * Отключение от базы данных MySQL
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Дополнение к стандартному методу query с логированием запросов
     * @param string $query
     * @param int $resultmode
     * @return bool|mysqli_result
     */
    public function query($query, $resultmode = MYSQLI_STORE_RESULT) {
        $this->query->count++;
        $this->query->last = $query;
        if(count($this->query->history)>($this->history_limit-1) and $this->DEBUG===false) {
            unset($this->query->history[0]);
            $this->query->history = array_values($this->query->history);
        }
        if($this->DEBUG===true) {
            unset($start_time,$end_time,$time);
            $time_start = microtime(true);
        }
        $result = parent::query($query, $resultmode);
        if($this->DEBUG===false) {
            $this->query->history[] = $query;
        } else {
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $this->query->history[] = array(
                'query'=> $query,
                'time' => number_format($time,4)
            );
        }
        return $result;
    }

    /**
     * Структурированное извелечение выбранных строк из одной или несколько таблиц
     * @param mixed $query Запрос SELECT к таблице (без использования начального оператора SELECT)
     * @param bool $array Вернуть результат: true - в виде индексированного массива, false - в виде ассоциативного
     * @return array Структурированный массив по результатам выбранных строк из одной или несколько таблиц
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->select("* from `table`")
     * Array (
     *      [0] => Array (
     *          [id] => 1
     *          [row] => value
     *      )
     *      [1] => Array (
     *          [id] = 2
     *          [row] = value_2
     *      )
     *      ...
     *      [n] => Array (
     *          [id] => x
     *          [row] = value_x
     *      )
     * )
     *
     * ->select("`row` from `table")
     * Array (
     *      [0] => value
     *      [1] => value_2
     *      ...
     *      [n] => value_x
     * )
     *
     * ->select("* from `table` limit 1",0)
     * Array (
     *      [id] => 1
     *      [row] = value
     * )
     *
     * ->select("`row` from `table` limit 1",0)
     * value
     * ==========================================
     *
     */
    public function select($query, $array = true) {
        if($array == 0) $array = false; //адаптер старых версий
        $query = 'select '.$query;
        $response = false;
        if ($result = $this->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $response[] = ((count($row)==1)? end($row) : $row);
            }
        }
        if($array === false) {
            $response=$response[0];
        }
        return $response;
    }

    /**
     * Обновление столбцов в соответствии с их новыми значениями в строках существующей таблицы
     * @param mixed $table Таблица в базе данных
     * @param array|mixed $update Массив со столбцами и параметрами
     * @param mixed $where При каких параметрах обновлять
     * @return bool|mysqli_result При неудачной попытке обновить возвращает FALSE
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * Стандартное обновление одной ячейки в таблице:
     * ->update('table', 'row,new_value', 'id=0')
     * Выполнит mysql запрос: update `table` set `row`='new_value' where id=0
     *
     * Если надо обновить несколько ячеек в таблице:
     * ->update('table', array("row"=>"value", "row_2"="value_2"), 'id=1')
     * Выполнит mysql запрос: update `table` set `row`='value', `row_2`='value_2' where `id`=1
     *
     * Если необходимо выполнить обновление с командой (например: ADD DATE)
     * ->update('table', array("row"=>"value", "date"=>array("DATE_ADD(date,INTERVAL 1 MONTH)", cmd), "sum"=>array("sum+1", cmd)), 'id=2')
     * Выполнит mysql запрос: update `table` set `row`='value', `date`=DATE_ADD(date,INTERVAL 1 MONTH), `sum`=sum+1 where id=2
     * ==========================================
     *
     */
    public function update($table, $update, $where = false) {
        if($table) {
            $query = 'update `'.$table.'` set ';
            if(is_array($update)) {
                $array_keys = $this->get_row_keys($update);
                $update = $this->structure_value($update);
                foreach($update as $param=>$value) {
                    $query .= "`".$param."`=".((count($value)>1 and $value[1]==cmd)? $value[0] : "'".$value."'").((next($array_keys))? ',' : '');
                }
            }
            if(!is_array($update)) {
                if(count($update)==1 and count($update = explode(",", $update))==2) $query .= "`".$update[0]."`='".$update[1]."'";
            }
            if($where) $query .= " where ".$where;
            return $this->query($query);
        }
        return false;
    }

    /**
     * Добавление новых строки в существующую таблицу
     * @param mixed $table Таблица в базе данных
     * @param array $insert Массив состоящий из стоблбцов и параметров
     * @return bool|mixed При удачном добавлении возвращает ID добавленной строки в текущей таблицы
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->insert('table', array("row"=>"new_value", "date"=>"1994-04-29"))
     *  Выполнит mysql запрос: insert into `table` (`row`, `date`) values ('new_value', '1994-04-29')
     * ==========================================
     *
     */
    public function insert($table, $insert) {
        if($table) {
            $query = 'insert into `'.$table.'` (';
            $query_p = '';
            $query_v = '';
            if(is_array($insert)) {
                $array_keys = $this->get_row_keys($insert);
                $insert = $this->structure_value($insert);
                foreach($insert as $key=>$value) {
                    $next = next($array_keys);
                    $query_p .= "`".$key."`".(($next)? ',' : ') values (');
                    $query_v .= ((count($value)>1 and $value[1]==cmd)? $value[0] : "'".$value."'").(($next)? ',' : ')');
                    unset($next);
                }
                $query .= $query_p.$query_v;
            }
            if(!is_array($insert)) {
                if (count($insert) == 1 and count($insert = explode(",", $insert)) == 2) $query .= "(`" . $insert[0] . "`) values ('" . $insert[1] . "')";
            }
            if($this->query($query)===true) {
                return $this->insert_id;
            }
        }
        return false;
    }

    /**
     * Добавление новых строк в существующую таблицу
     * @param mixed $table Таблица в базе данных
     * @param array $insert Массив состоящий из стоблбцов и параметров
     * @return bool|mixed При удачном добавлении возвращает ID добавленной строки в текущей таблицы
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->insert('table', array("row"=>("new_value","new_value2"), "date"=>array("1994-04-29","2015-01-01")))
     *  Выполнит mysql запрос: insert into `table` (`row`, `date`) values ('new_value', '1994-04-29'), ('new_value2', '2015-01-01')
     * ==========================================
     *
     */
    public function multi_insert($table, $insert) {
        if($table) {
            $query = 'insert into `'.$table.'` (';
            $query_p = '';
            $query_v = '';
            if(is_array($insert)) {
                $count_row = 0;
                $array_keys = array();
                foreach($insert as $key=>$value) {
                    $array_keys[] = "`".$key."`";
                    if(is_array($value)) {
                        if(count($value)>$count_row) $count_row = count($value);
                    }
                }
                $query_p = implode(",",$array_keys).") values ";
                foreach($insert as $key=>$value) {
                    if(!is_array($value)) {
                        $insert[$key] = array($value);
                        for($i=1;$i<$count_row;$i++) {
                            $insert[$key][$i] = $insert[$key][0];
                        }
                    }
                }
                $count_keys = count($array_keys);
                for($i=0;$i<$count_row;$i++) {
                    $query_v .= '(';
                    $j=0;
                    foreach($insert as $key=>$value) {
                        $query_v .= "'".$value[$i]."'".($j==($count_keys-1)?"":", ");
                        $j++;
                    }
                    $query_v .= ')'.($i==($count_row-1)?'':', ');
                }
            }
            $query .= $query_p.$query_v;
            if($this->query($query)===true) {
                return $this->insert_id;
            }
        }
        return false;
    }

    ####    Группа insert QUEUE Mysql   #####
    private $insert_queue_list;
    /**
     * Открытие очереди на добавление полей в mysql. Аналог метода multi_insert
     * @param string $queue_name Ключ очереди
     * @param string $table Таблица в базе данных
     * @param string|array $row Список полей в базе данных для вставки
     * @return bool Возвращает TRUE при успешном создании очереди
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->insert_queue('first', 'test', 'row,row2,row3')
     * ->insert_queue('first', 'test', array('row','row2','row3'))
     *  Создаст очередь с ключём first для добавления в таблицу test полей row,row2,row3
     * ==========================================
     *
     */
    public function insert_queue($queue_name = '', $table = '', $row = '') {
        if($queue_name and $table and $row) {
            if(isset($this->insert_queue_list[$queue_name])) return false;
            if(!is_array($row)) {
                $row = explode(",", $row);
            }
            if(!is_array($row)) return false;
            $count = count($row);
            if(!$count) return false;
            foreach($row as $key=>$value) $row[$key] = '`'.$value.'`';
            $row = implode(",",$row);
            $this->insert_queue_list[$queue_name] = array(
                'open'=>1,
                'success'=>0,
                'query' => 'insert into `'.$table.'` ('.$row.') values ',
                'row' => $count,
                'values' => array()
            );
            return true;
        }
        return false;
    }

    /**
     * Добавление полей к очереди с заданным ключом
     * @param string $queue_name Ключ очереди
     * @param string|array $values Значения для полей
     * @return bool Возвращает TRUE при успешном добавление значений в заданную очередь
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->insert_queue_add('first', 'value,value2,value3')
     * ->insert_queue_add('first', array('value','value2','value3'))
     *  Добавит в очередь с ключом first значения value,value2,value3 для задданых полей
     * ==========================================
     *
     */
    public function insert_queue_add($queue_name = '', $values = '') {
        if(isset($this->insert_queue_list[$queue_name])) {
            if(!is_array($values)) {
                $values = explode(",",$values);
            }
            if(!is_array($values)) return false;
            $count = count($values);
            if($count!=$this->insert_queue_list[$queue_name]['row']) return false;
            foreach($values as $key=>$value) {
                $values[$key] = "'".$value."'";
            }
            $values = implode(",",$values);
            $this->insert_queue_list[$queue_name]['values'][] = $values;
            return true;
        }
        return false;
    }

    /**
     * Перенос очереди в mysql INSERT запрос и выполение его
     * @param string $queue_name Ключ очереди
     * @return bool Возвращает TRUE при удачном INSERT
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->insert_queue_send('first')
     * Выполнит mysql insert query при удачной конфигурации очереди (Проверить очередь можно методом insert_queue_status($queue_name))
     * ==========================================
     *
     */
    public function insert_queue_send($queue_name = '') {
        if($this->insert_queue_status($queue_name)===true) {
            $arr = $this->insert_queue_list[$queue_name]['values'];
            foreach($arr as $value) {
                $this->insert_queue_list[$queue_name]['query'] .= '('.$value.')'.(next($arr)?', ':'');
            }
            $query = $this->insert_queue_list[$queue_name]['query'];
            $this->insert_queue_list[$queue_name]['open'] = 0;
            if($this->query($query)!==false) {
                $this->insert_queue_list[$queue_name]['success'] = 1;
                return true;
            }
        }
        return false;
    }

    /**
     * Проверка очереди на удачную конфигурацию
     * @param string $queue_name Ключ очереди
     * @return bool Возвращает TRUE при правильной(удачной) конфигурации очереди
     */
    public function insert_queue_status($queue_name = '') {
        $count_row = $this->insert_queue_list[$queue_name]['row'];
        $is_success = $this->insert_queue_list[$queue_name]['success'];
        if($count_row and !$is_success) {
            return true;
        }
        return false;
    }

    /**
     * Удаление строки из таблицы
     * @param mixed $query Запрос DELETE к таблице (без использования начального оператора DELETE)
     * @return bool|mysqli_result
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->delete(" from `table` where `id`<2")
     * Выполнит mysql запрос: delete from `table` where `id`<2
     * ==========================================
     *
     */
    public function delete($query) {
        $query = 'delete '.$query;
        return $this->query($query);
    }


    /* Приватные методы */

    /**
     * Возвращение текста ошибки по ID
     * Публичные методы: __construct
     * @param $id int ID mysql-ошибки
     * @return mixed текст ошибки
     */
    private function errnoText($id) {
        $error[1044] = "у пользователя нет доступа к базе";
        $error[1045] = "ошибка авторизации (логин или пароль неверен)";
        $error[2003] = "сервер не найден";
        return $error[$id];
    }

    /**
     * Запись ключей массива как элементы
     * Публичные методы: update, insert
     * @param $data array массив данных
     * @return array ключи массива как отдельный массив
     */
    private function get_row_keys($data) {
        $array_keys = array();
        foreach($data as $key=>$value) {
            $array_keys[] = $key;
        }
        return $array_keys;
    }

    /**
     * Обработка элементов массива для корректной работы библиотеки
     * Публичные методы: update, insert
     * @param $data array массив данных
     * @return array обработанный массив данных
     */
    private function structure_value($data) {
        $structure = array();
        foreach($data as $key=>$value) {
            if(is_array($value) and $value[1]!==cmd) {
                $structure[$key] = $value[0];
            } elseif (is_object($value)) {
                $structure[$key] = ((end($value))? end($value) : "");
            } else {
                $structure[$key] = $value;
            }
        }
        return $structure;
    }


} 