<?php
/**
 * @version		$Id: Joomla.php 845 2013-04-10 06:28:35Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

//require_once dirname(__FILE__) . '/../KBIntegratorSynchronable.php';
//require_once dirname(__FILE__) . '/../../../components/com_dbconnect/models/data.php';

class JoomlaKBIIntegrator extends KBIntegratorSynchronable
{
    private $model;
    // TODO: make config
    private $section = 2;
    private $category = 2;

    protected function getModel()
    {
        if(!$this->model) {
            $this->model = new dbconnectModelData();
        }

        return $this->model;
    }

    protected function getSection()
    {
        return $this->section;
    }

    protected function getCategory()
    {
        return $this->category;
    }

    public function  __construct()
    {
    }

    public function getDataDescription()
    {
        return '';
    }

    public function getDocuments()
    {
        $date_format = '%d.%m.%y %H:%M';
        $documents = array();

        $model = $this->getModel();

        $joomla_documents = $model->getArticles($this->getSection(), $this->getCategory(), '', 'id', 'ASC', 0, 1000);

        foreach ($joomla_documents as $doc) {
            $document = new stdClass;
            /*
            ["title"]=> string(1) "2"
            ["id"]=> string(3) "267"
            ["cdate"]=> string(14) "18.09.12 07:19"
            ["categorie"]=> string(9) "IZI:Miner"
            ["section"]=> string(9) "IZI:Miner"
            */
            $ts = strptime($doc->cdate, $date_format);
            $unix = mktime((int) $ts['tm_hour'], (int) $ts['tm_min'], (int) $ts['tm_sec'], ((int) $ts['tm_mon']) + 1, (int) $ts['tm_mday'], ($ts['tm_year'] + 1900));

            $document->id = $doc->id;
            $document->name = $doc->title;
            $document->timestamp = strftime('%Y/%m/%d %H:%M', $unix);

            $documents[] = $document;
        }

        return $documents;
    }

    public function getDocument($id)
    {
        $model = $this->getModel();

        return $model->loadArticle($id)->text;
    }

    public function addDocument($id, $document, $path = false)
    {
        $model = $this->getModel();

        $model->newArticle($document->title, $document->text, '1', $this->getSection(), $this->getCategory());
    }

    public function deleteDocument($id)
    {
        // TODO: Implement deleteDocument() method.
    }
}
