<?php

class Parser
{
    private $url;
    private $page;
    private $openTag = '<';
    private $closeTag = '>';
    private $result = [];

    public function __construct($url)
    {
        $this->url = $url;
    }

// инициализируем curl запрос
    public function curlInit()
    {
        $options = array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_HEADER => true,
//            CURLOPT_SSL_VERIFYPEER => false
        );
        $curl = curl_init();
        curl_setopt_array($curl, $options);

        $this->page = curl_exec($curl);
        if ($this->page == null) {
            echo 'Ошибка curl: ' . curl_error($curl);
            return curl_error($curl);
        }
        curl_close($curl);
    }

// получаем тэги
    public function getTags()
    {
        $startPoint = mb_strpos($this->page, $this->openTag);
        do {
            $this->page = mb_substr($this->page, $startPoint);
//            убираем знаки больше и меньше из тэга script, чтобы парсер их не распознавал как начало тэга
            $this->page = preg_replace('#<script.+</script>#U', '<script></script>', $this->page);
            $openTag = $this->openSearch();
            if (preg_match('#\\s#', $openTag)) {
                $clearTag = $this->clearTag($openTag);
            } else {
                $clearTag = $openTag;
            }
            $closeTag = $this->closeSearch($clearTag);
            $fullTag = $clearTag . $closeTag;
            if (array_key_exists($fullTag, $this->result) === false) {
                $this->result[$fullTag] = 1;
            } else {
                $this->result[$fullTag]++;
            }
            $startPoint = mb_strpos($this->page, $this->openTag);
        } while ($startPoint !== false);
        return $this->result;
    }

// ищем открывающий тэг
    public function openSearch(): string
    {
        $endTagPoint = 0;
        $endTagsNumber = 0;
        $startTagsNumber = 0;
        do {
            $endTagPointPrevious = $endTagPoint;
            $endTagPoint = mb_strpos($this->page, $this->closeTag, $endTagPoint);
            if ($endTagPoint !== false) {
                $endTagPoint = $endTagPoint + mb_strlen($this->closeTag);
                $endedTag = mb_substr($this->page, 0, $endTagPoint);
                $endTagsNumber = substr_count($this->page, $this->closeTag);
                $startTagsNumber = substr_count($this->page, $this->openTag);
            } else {
                $endedTag = mb_substr($this->page, 0, $endTagPointPrevious);
            }
        } while ($endTagsNumber < $startTagsNumber && $endTagPoint !== false);
        $this->page = mb_substr($this->page, $endTagPoint);
        return $endedTag;
    }

// очищаем от атрибутов
    public function clearTag($tag): string
    {
        do {
            preg_match('#<.+\s#', $tag, $clearTag);
            $tag = trim($clearTag[0]);
        } while (preg_match('#\\s#', $tag));
        return $tag . '>';
    }

// ищем закрывающий тэг
    public function closeSearch($clearTag): string
    {
        $closeTag = str_replace('<', '</', $clearTag);
        $endTagPoint = mb_strpos($this->page, $closeTag);
        if ($endTagPoint !== false) {
            $endTagPointClose = $endTagPoint + mb_strlen($closeTag);
            $pageCutStart = mb_substr($this->page, 0, $endTagPoint);
            $pageCutEnd = mb_substr($this->page, $endTagPointClose);
            $this->page = $pageCutStart . $pageCutEnd;
        } else {
            $closeTag = '';
        }
        return $closeTag;
    }
}

$start = new Parser('https://www.sports.ru');
$start->curlInit();
echo '<pre>';
print_r($start->getTags());
echo '</pre>';
