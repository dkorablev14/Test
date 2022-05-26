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

    public function curlInit()
    {
        $options = array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
        );
        $curl = curl_init();
        curl_setopt_array($curl, $options);

        $this->page = curl_exec($curl);
        if ($this->page === false) {
            echo 'Ошибка curl: ' . curl_error($curl);
            return curl_error($curl);
        }
        curl_close($curl);
    }

    public function getTags()
    {
        $startPoint = mb_strpos($this->page, $this->openTag);
        do {
            $workingTag = mb_substr($this->page, $startPoint);
            $endedTag = $this->endingSearch($workingTag, $this->openTag, $this->closeTag);
            if (preg_match('#\\s#', $endedTag)) {
                $clearTag = $this->clearTag($endedTag);
            } else {
                $clearTag = $endedTag;
            }
            if (array_key_exists($clearTag, $this->result) === false) {
                $this->result[$clearTag] = 1;
            } else {
                $this->result[$clearTag]++;
            }
            $this->page = str_replace($endedTag, '', $this->page, $countCloseTag);
            if ($countCloseTag > 1) {
                $this->result[$clearTag] += $countCloseTag - 1;
            }
            $startPoint = mb_strpos($this->page, $this->openTag);
        } while ($startPoint !== false);
    }

    public function endingSearch($workingTag, $innerStartTag, $endTag): string
    {
        $endTagPoint = 0;
        $endTagsNumber = 0;
        $startTagsNumber = 0;
        do {
            $endedTag = $workingTag;
            $endTagPointPrevious = $endTagPoint;
            $endTagPoint = mb_strpos($endedTag, $endTag, $endTagPoint);
            if ($endTagPoint !== false) {
                $endTagPoint = $endTagPoint + mb_strlen($endTag);
                $ended_word = mb_substr($endedTag, 0, $endTagPoint);
                $endTagsNumber = substr_count($endedTag, $endTag);
                $startTagsNumber = substr_count($endedTag, $innerStartTag);
            } else {
                $ended_word = mb_substr($endedTag, 0, $endTagPointPrevious);
            }
        } while ($endTagsNumber < $startTagsNumber && $endTagPoint !== false);
        return $ended_word;
    }

    public function clearTag($tag): string
    {
        do {
            preg_match('#<.+\s#', $tag, $clearTag);
            $tag = trim($clearTag[0]);
        } while (preg_match('#\\s#', $tag));
        return $tag . '>';
    }

    public function checkDoubleTags()
    {
        foreach ($this->result as $tag){

        }
    }
}

$start = new Parser('https://www.sports.ru/');
$start->curlInit();
$start->getTags();
$start->checkDoubleTags();