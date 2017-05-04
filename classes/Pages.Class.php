<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 03.05.2017
 * Time: 20:32
 */
class Pages
{
    const TABLE_NAME = 'pages';

    function __construct()
    {

    }

    /**
     * Получаем содержимое и заголовки запрошенной страницы
     *
     * @param string $url
     * @return array
     */
    static function getPageContent($url)
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
     * Ищем все ссылки и картинки на странице
     * ссылки для дальнейшего обхода страниц сайта
     * картинки -  для сохранения
     *
     * @param string $url
     * @return bool
     */
    static function findHrefs($url)
    {
        global $URL;
        $response = self::getPageContent($url);      //получаем контент запрошенной страницы $response = array(0 - заголовки ответа, 1 - содержимое страницы)
        if (!substr_count($response[0], "200 OK")) //если получаем код ответа отличный от 200 OK
            return false;                                   //то выходим из выполнения обхода страницы

        $page_id = self::savePageUrl($url);     //сохраняем URL страницы в список пройденных страниц
        Images::findImgHrefs($response[1], $page_id); //находим картинки на странице

        preg_match_all('/<a.*href=[\"\'](.*)[\"\'].*>/isU', $response[1], $matches);     //выбираем содержимое атрибута href у тэга <a>

        foreach ($matches[1] as $href)           // перебираем полученные ссылки
        {
            if (!in_array($href,["../","./"])) {
                if (!preg_match("~^" . ($URL) . "~isU", $href))     // если ссылка относительная или содержит урл другого внешнего домена
                    $target = $URL . $href;         // то добавляем переданный урл перед полученной ссылкой
                else
                    $target = $href;

                if (self::checkDublicates($target))//если URLа ссылки, найденной на странице еще нет в базе сохраненных страниц,
                {
                    self::findHrefs($target);      //то делаем обход по ссылкам найденным на странице
                }
            }

        }

        return true;
    }

    /**
     * Сохраняем URL полученной страницы в базе
     *
     * @param string $url
     * @return integer
     */
    static function savePageUrl($url)
    {
        $data = ["page_url" => $url];
        return DB::insert_sql(self::TABLE_NAME, $data);
    }

    /**
     * Проверяем на наличие переданного урла среди уже пройденных страниц
     *
     * @param string $url
     * @return bool
     */
    static function checkDublicates($url)
    {
        $data = ["page_url" => $url];

        return DB::get_count(self::TABLE_NAME, $data) > 0 ? false : true;
    }
}