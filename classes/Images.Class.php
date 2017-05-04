<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 03.05.2017
 * Time: 20:18
 */
class Images
{
    const TABLE_NAME = 'images';

    private function __construct()
    {

    }

    /**
     * получаем контент и заголовки страницы по урлу
     * @param string $url
     * @return array
     */
    static function getImgContent($url)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array('Expect:'));
        $response = curl_exec($ch);
        list($header, $body) = explode("\r\n\r\n", $response, 2);
        curl_close($ch);
        return [$header, $body];
    }

    /**
     * Сканируем контент страницы и выделяем оттуда ссылки на картинки из тэгов img
     *
     * @param string $content
     * @param integer $page_id
     * @return bool
     */
    static function findImgHrefs($content, $page_id)
    {
        global $URL;

        preg_match_all('/<img.*src=[\"\'](.*)[\"\'].*>/isU', $content, $matches); //выбираем все тэги картинок на странице

        foreach ($matches[1] as $img)
        {
            if (!preg_match("~^" . ($URL) . "~isU", $img))     // если ссылка на картинку относительная или содержит урл другого внешнего домена
                $target = $URL . $img;         // то добавляем домен перед полученной ссылкой
            else
                $target = $img;

            $response = self::getImgContent($target);      //получаем контент запрошенной картинки $response = array(0 - заголовки ответа, 1 - содержимое страницы)

            if (substr_count($response[0], "200 OK") //если получаем код ответа 200 OK
                && substr_count($response[0], "Content-Type: image")) //и тип контента является картинкой
                if (self::checkDublicates($response[1]))         //,то проверяем на присутствие такой же картинки в базе
                    self::saveImg($target, $response[1], $page_id);  //если картинки с таким же содержимым нет в базе, то сохраняем её

        }

        return true;
    }

    /**
     * Проверка на повтор содержимого картинки среди уже сохраненных в базе
     *
     * @param string $content
     * @return bool
     */
    static function checkDublicates($content)
    {
        $data = ["img_hash" => md5($content)];

        return DB::get_count(self::TABLE_NAME, $data) > 0 ? false : true;
    }

    /**
     * Сохраняем картинку в базе
     *
     * @param string $url
     * @param string $img_content
     * @param integer $page_id
     * @return bool
     */
    static function saveImg($url, $img_content, $page_id)
    {

        $img_name = self::getImgName($url);
        $data = [
            "img_name" => $img_name,
            "page_id" => $page_id,
            "img_content" => $img_content,
            "img_hash" => md5($img_content)
        ];

        return DB::insert_sql(self::TABLE_NAME, $data);
    }

    /**
     * Выделяем имя файла картинки из урла
     *
     * @param string $url
     * @return string
     */
    static function getImgName($url)
    {
        $exp = explode("/", $url);
        $file_name = end($exp);

        return $file_name;
    }
}